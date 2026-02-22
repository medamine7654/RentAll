#!/usr/bin/env python3
import json
import os
import sys
import time
from typing import List, Optional, Tuple

import numpy as np


def out(payload: dict, code: int = 0) -> None:
    print(json.dumps(payload))
    sys.exit(code)


def main() -> None:
    if len(sys.argv) < 3:
        out({"success": False, "match": False, "distance": None, "error": "Usage: face_verify.py <image_a> <image_b>"}, 1)

    image_a = sys.argv[1]
    image_b = sys.argv[2]

    if not os.path.isfile(image_a):
        out({"success": False, "match": False, "distance": None, "error": "Image A introuvable."}, 1)

    if not os.path.isfile(image_b):
        out({"success": False, "match": False, "distance": None, "error": "Image B introuvable."}, 1)

    try:
        import face_recognition
        from PIL import Image, ImageEnhance, ImageOps
    except Exception:
        out(
            {
                "success": False,
                "match": False,
                "distance": None,
                "error": "Moteur IA indisponible. Veuillez contacter le support technique.",
            },
            1,
        )

    try:
        max_side = int(os.environ.get("FACE_MAX_IMAGE_SIDE", "1000"))
        budget_seconds = float(os.environ.get("FACE_VERIFY_BUDGET_SECONDS", "9.0"))
        start = time.monotonic()

        def load_rgb(path: str) -> np.ndarray:
            image = Image.open(path)
            image = ImageOps.exif_transpose(image)
            image = image.convert("RGB")
            # Keep enough detail while avoiding very large arrays that slow down detection.
            image.thumbnail((max_side, max_side), Image.Resampling.LANCZOS)
            return np.array(image)

        def build_variants(img: np.ndarray, for_document: bool) -> List[np.ndarray]:
            base = Image.fromarray(img)
            variants: List[np.ndarray] = [img]

            # Improve visibility on low-contrast ID photos.
            auto = ImageOps.autocontrast(base)
            variants.append(np.array(auto))

            contrast = ImageEnhance.Contrast(auto).enhance(1.5)
            sharp = ImageEnhance.Sharpness(contrast).enhance(1.4)
            variants.append(np.array(sharp))

            if for_document:
                # ID cards are often photographed rotated.
                for angle in (90, 180, 270):
                    variants.append(np.array(base.rotate(angle, expand=True)))
                    variants.append(np.array(sharp.rotate(angle, expand=True)))

                # Improve detection on small portrait photos printed on the card.
                w, h = sharp.size
                up2 = sharp.resize((max(1, int(w * 2)), max(1, int(h * 2))), Image.Resampling.BICUBIC)
                variants.append(np.array(up2))

            return variants

        def largest_face_encoding(image: np.ndarray, for_document: bool) -> Optional[np.ndarray]:
            def find_best_candidate(variant_list: List[np.ndarray], upsample_list: List[int]) -> Optional[Tuple[int, np.ndarray, Tuple[int, int, int, int]]]:
                best: Optional[Tuple[int, np.ndarray, Tuple[int, int, int, int]]] = None
                for variant in variant_list:
                    if time.monotonic() - start > budget_seconds:
                        return best
                    for upsample in upsample_list:
                        if time.monotonic() - start > budget_seconds:
                            return best
                        try:
                            locations = face_recognition.face_locations(
                                variant,
                                number_of_times_to_upsample=upsample,
                                model="hog",
                            )
                        except Exception:
                            locations = []

                        if len(locations) == 0:
                            continue

                        for location in locations:
                            top, right, bottom, left = location
                            area = max(0, right - left) * max(0, bottom - top)
                            if best is None or area > best[0]:
                                best = (area, variant, location)
                return best

            def quadrant_crops(arr: np.ndarray) -> List[np.ndarray]:
                h, w = arr.shape[:2]
                if h < 80 or w < 80:
                    return []
                h1 = h // 4
                h3 = (h * 3) // 4
                w1 = w // 4
                w3 = (w * 3) // 4
                return [
                    arr[0:h3, 0:w3],
                    arr[0:h3, w1:w],
                    arr[h1:h, 0:w3],
                    arr[h1:h, w1:w],
                ]

            # Fast pass first.
            best_candidate = find_best_candidate([image], [0])
            if best_candidate is None:
                # Fallback pass with enhancement and slight upsampling.
                best_candidate = find_best_candidate(build_variants(image, for_document), [0, 1])
                if best_candidate is None:
                    # Last chance: search on document sub-regions where the ID portrait is often tiny.
                    if for_document and (time.monotonic() - start <= budget_seconds):
                        cropped_variants: List[np.ndarray] = []
                        for variant in build_variants(image, True):
                            cropped_variants.extend(quadrant_crops(variant))
                        best_candidate = find_best_candidate(cropped_variants, [0, 1])
                        if best_candidate is None:
                            return None
                    else:
                        return None

            _, best_variant, best_location = best_candidate
            encodings = face_recognition.face_encodings(best_variant, [best_location], num_jitters=1)
            if len(encodings) == 0:
                return None
            return encodings[0]

        image_a_data = load_rgb(image_a)
        image_b_data = load_rgb(image_b)

        enc_a = largest_face_encoding(image_a_data, for_document=False)
        enc_b = largest_face_encoding(image_b_data, for_document=True)

        if enc_a is None:
            out(
                {
                    "success": False,
                    "match": False,
                    "distance": None,
                    "error": "Visage non detecte sur le selfie. Reprenez une photo nette, bien eclairee et de face.",
                },
                1,
            )
        if enc_b is None:
            if time.monotonic() - start > budget_seconds:
                out(
                    {
                        "success": False,
                        "match": False,
                        "distance": None,
                        "error": "Traitement trop long. Recadrez la photo pour ne garder que la CIN puis reessayez.",
                    },
                    1,
                )
            out(
                {
                    "success": False,
                    "match": False,
                    "distance": None,
                    "error": "Visage non detecte sur la piece d identite. Assurez-vous que la photo est nette, complete et bien eclairee.",
                },
                1,
            )

        distance = float(face_recognition.face_distance([enc_a], enc_b)[0])
        threshold = float(os.environ.get("FACE_MATCH_THRESHOLD", "0.62"))
        match = distance <= threshold

        out({"success": True, "match": bool(match), "distance": distance, "error": None}, 0)
    except Exception:
        out(
            {
                "success": False,
                "match": False,
                "distance": None,
                "error": "La verification faciale a echoue. Merci de reessayer avec des photos plus nettes.",
            },
            1,
        )


if __name__ == "__main__":
    main()
