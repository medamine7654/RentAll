#!/usr/bin/env python3
import json
import os
import sys
import tempfile
from typing import List, Optional, Tuple

import numpy as np


def out(payload: dict, code: int = 0) -> None:
    print(json.dumps(payload))
    sys.exit(code)


def load_rgb(path: str, image_ops, max_side: int) -> np.ndarray:
    image = image_ops["Image"].open(path)
    image = image_ops["ImageOps"].exif_transpose(image)
    image = image.convert("RGB")
    image.thumbnail((max_side, max_side), image_ops["Image"].Resampling.LANCZOS)
    return np.array(image)


def face_encoding(image: np.ndarray, face_recognition, fast_mode: bool = False) -> Optional[np.ndarray]:
    variants: List[np.ndarray] = [image]

    best: Optional[Tuple[int, np.ndarray, Tuple[int, int, int, int]]] = None
    for variant in variants:
        upsample_values = [0] if fast_mode else [0, 1]
        for upsample in upsample_values:
            try:
                locations = face_recognition.face_locations(variant, number_of_times_to_upsample=upsample, model="hog")
            except Exception:
                locations = []
            for location in locations:
                top, right, bottom, left = location
                area = max(0, right - left) * max(0, bottom - top)
                if best is None or area > best[0]:
                    best = (area, variant, location)

    if best is None:
        return None

    _, best_variant, best_location = best
    encodings = face_recognition.face_encodings(best_variant, [best_location], num_jitters=1)
    if len(encodings) == 0:
        return None

    return encodings[0]


def load_cache(path: str) -> dict:
    try:
        if os.path.isfile(path):
            with open(path, "r", encoding="utf-8") as fh:
                data = json.load(fh)
            if isinstance(data, dict):
                return data
    except Exception:
        pass
    return {}


def save_cache(path: str, data: dict) -> None:
    try:
        parent = os.path.dirname(path)
        if parent and not os.path.isdir(parent):
            os.makedirs(parent, exist_ok=True)
        with tempfile.NamedTemporaryFile("w", encoding="utf-8", delete=False, dir=parent if parent else None) as fh:
            json.dump(data, fh)
            temp_path = fh.name
        os.replace(temp_path, path)
    except Exception:
        pass


if __name__ == "__main__":
    if len(sys.argv) < 4:
        out({"success": False, "matched": False, "user_id": None, "distance": None, "error": "Usage"}, 1)

    probe_path = sys.argv[1]
    manifest_path = sys.argv[2]
    threshold = float(sys.argv[3])
    cache_path = sys.argv[4] if len(sys.argv) >= 5 else os.path.join("var", "face-login", "face-identify-cache.json")
    max_side = int(os.environ.get("FACE_IDENTIFY_MAX_IMAGE_SIDE", "1100"))
    ambiguity_margin = float(os.environ.get("FACE_LOGIN_AMBIGUITY_MARGIN", "0.03"))

    if not os.path.isfile(probe_path) or not os.path.isfile(manifest_path):
        out({"success": False, "matched": False, "user_id": None, "distance": None, "error": "Fichiers invalides"}, 1)

    try:
        import face_recognition
        from PIL import Image, ImageOps
    except Exception:
        out({"success": False, "matched": False, "user_id": None, "distance": None, "error": "Moteur IA indisponible"}, 1)

    image_ops = {
        "Image": Image,
        "ImageOps": ImageOps,
    }

    try:
        with open(manifest_path, "r", encoding="utf-8") as fh:
            candidates = json.load(fh)
        if not isinstance(candidates, list):
            out({"success": False, "matched": False, "user_id": None, "distance": None, "error": "Manifest invalide"}, 1)

        cache = load_cache(cache_path)
        if not isinstance(cache, dict):
            cache = {}

        probe = load_rgb(probe_path, image_ops, max_side)
        probe_enc = face_encoding(probe, face_recognition, fast_mode=False)
        if probe_enc is None:
            out(
                {
                    "success": False,
                    "matched": False,
                    "user_id": None,
                    "distance": None,
                    "error": "Visage non detecte. Regardez la camera de face et avec plus de lumiere.",
                },
                1,
            )

        best_user_id = None
        best_distance = None
        second_best_distance = None
        seen_paths = set()

        for candidate in candidates:
            if not isinstance(candidate, dict):
                continue
            user_id = candidate.get("id")
            path = candidate.get("path")
            if user_id is None or not isinstance(path, str) or not os.path.isfile(path):
                continue
            seen_paths.add(path)

            try:
                stat = os.stat(path)
                mtime = int(stat.st_mtime_ns)
                size = int(stat.st_size)
            except Exception:
                continue

            candidate_enc = None
            entry = cache.get(path)
            if isinstance(entry, dict):
                enc = entry.get("enc")
                if (
                    entry.get("mtime") == mtime
                    and entry.get("size") == size
                    and isinstance(enc, list)
                    and len(enc) == 128
                ):
                    candidate_enc = np.array(enc, dtype=np.float64)

            if candidate_enc is None:
                candidate_img = load_rgb(path, image_ops, max_side)
                candidate_enc = face_encoding(candidate_img, face_recognition, fast_mode=True)
                if candidate_enc is None:
                    candidate_enc = face_encoding(candidate_img, face_recognition, fast_mode=False)
                if candidate_enc is None:
                    continue
                cache[path] = {
                    "mtime": mtime,
                    "size": size,
                    "enc": candidate_enc.tolist(),
                }

            dist = float(face_recognition.face_distance([probe_enc], candidate_enc)[0])
            if best_distance is None or dist < best_distance:
                if best_distance is not None:
                    if second_best_distance is None or best_distance < second_best_distance:
                        second_best_distance = best_distance
                best_distance = dist
                best_user_id = int(user_id)
            elif second_best_distance is None or dist < second_best_distance:
                second_best_distance = dist

        # Keep cache small and relevant to current candidate set.
        stale_keys = [key for key in cache.keys() if key not in seen_paths]
        for key in stale_keys:
            cache.pop(key, None)
        save_cache(cache_path, cache)

        if best_user_id is None or best_distance is None:
            out({"success": False, "matched": False, "user_id": None, "distance": None, "error": "Aucun compte facial utilisable."}, 1)

        if second_best_distance is not None and (second_best_distance - best_distance) < ambiguity_margin:
            out(
                {
                    "success": True,
                    "matched": False,
                    "user_id": None,
                    "distance": best_distance,
                    "error": "Reconnaissance ambigue. Rapprochez le visage et reessayez.",
                },
                0,
            )

        matched = best_distance <= threshold
        out(
            {
                "success": True,
                "matched": bool(matched),
                "user_id": best_user_id if matched else None,
                "distance": best_distance,
                "error": None,
            },
            0,
        )
    except Exception:
        out({"success": False, "matched": False, "user_id": None, "distance": None, "error": "Erreur de reconnaissance faciale"}, 1)
