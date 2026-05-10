#!/usr/bin/env python3
import json
import math
import sys
from typing import Any, Dict, List


def to_float(value: Any, default: float = 0.0) -> float:
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def clamp(value: float, min_value: float, max_value: float) -> float:
    return max(min_value, min(max_value, value))


def sigmoid(x: float) -> float:
    if x >= 0:
        z = math.exp(-x)
        return 1.0 / (1.0 + z)
    z = math.exp(x)
    return z / (1.0 + z)


def score(payload: Dict[str, Any], threshold: float) -> Dict[str, Any]:
    failed_attempts = clamp(to_float(payload.get("failed_attempts")), 0.0, 200.0)
    suspicious_score = clamp(to_float(payload.get("suspicious_score")), 0.0, 999.0)
    hours_since_last_login = clamp(to_float(payload.get("hours_since_last_login")), 0.0, 24.0 * 365.0)
    minutes_since_last_failed = clamp(to_float(payload.get("minutes_since_last_failed")), 0.0, 24.0 * 365.0 * 60.0)
    ip_changed = 1.0 if to_float(payload.get("ip_changed")) >= 1.0 else 0.0
    is_success = 1.0 if to_float(payload.get("is_success")) >= 1.0 else 0.0
    hour_utc = clamp(to_float(payload.get("hour_utc")), 0.0, 23.0)

    is_failure = 1.0 - is_success
    rapid_retry = 1.0 if minutes_since_last_failed <= 4.0 else 0.0
    off_hours = 1.0 if (hour_utc <= 5.0 or hour_utc >= 23.0) else 0.0
    stale_account = 1.0 if hours_since_last_login >= 24.0 * 45.0 else 0.0

    # Lightweight logistic model tuned for suspicious login behaviors.
    linear = (
        -1.6
        + 0.28 * min(failed_attempts, 12.0)
        + 0.035 * min(suspicious_score, 120.0)
        + 0.85 * ip_changed
        + 0.55 * rapid_retry
        + 0.32 * off_hours
        + 0.25 * stale_account
        + 0.30 * is_failure
    )

    risk_score = clamp(sigmoid(linear), 0.0, 1.0)
    suspicious = risk_score >= threshold

    reasons: List[str] = []
    if failed_attempts >= 3:
        reasons.append("multiple_failed_attempts")
    if suspicious_score >= 5:
        reasons.append("historical_risk_score_high")
    if ip_changed >= 1.0:
        reasons.append("ip_changed_since_last_login")
    if rapid_retry >= 1.0:
        reasons.append("rapid_retry_pattern")
    if off_hours >= 1.0:
        reasons.append("off_hours_login")
    if stale_account >= 1.0:
        reasons.append("inactive_account_login")

    return {
        "success": True,
        "risk_score": round(risk_score, 4),
        "suspicious": suspicious,
        "reasons": reasons,
        "error": None,
    }


def main() -> int:
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Missing payload"}))
        return 1

    raw_payload = sys.argv[1]
    raw_threshold = sys.argv[2] if len(sys.argv) >= 3 else "0.72"

    try:
        payload = json.loads(raw_payload)
        if not isinstance(payload, dict):
            raise ValueError("payload must be object")
    except Exception as exc:
        print(json.dumps({"success": False, "error": f"Invalid payload: {exc}"}))
        return 1

    threshold = clamp(to_float(raw_threshold, 0.72), 0.4, 0.98)
    result = score(payload, threshold)
    print(json.dumps(result))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
