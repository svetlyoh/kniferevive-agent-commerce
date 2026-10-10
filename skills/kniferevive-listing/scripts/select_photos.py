"""Select observed photo variants; no fetching or browser/session access."""
import argparse
import json
from pathlib import Path
from urllib.parse import urlsplit


def select_photos(data, max_images=10, allow_small=False, allow_partial=False):
    if type(max_images) is not int or not 1 <= max_images <= 10:
        raise ValueError("max_images must be within the companion's 1–10 range")
    photos = data.get("photos")
    if not isinstance(photos, list) or not 1 <= len(photos) <= 100:
        raise ValueError("Provide 1–100 observed photo entries")
    grouped = {}
    for photo in photos:
        identity = photo.get("photo_id")
        if not isinstance(identity, str) or not identity.strip():
            raise ValueError("Each photo needs a stable observed identity/position")
        candidates = photo.get("candidates")
        if not isinstance(candidates, list) or len(candidates) > 30:
            raise ValueError("Provide bounded candidate arrays")
        grouped.setdefault(identity, []).extend(candidates)
    issues, chosen = [], []
    expected = data.get("expected_count")
    if expected is not None and (type(expected) is not int or expected < 1):
        raise ValueError("expected_count must be a positive integer when present")
    if data.get("gallery_complete") is not True or (expected is not None and expected != len(grouped)):
        issues.append({"code": "incomplete_gallery", "expected": expected, "found": len(grouped)})
    used_urls = set()
    for position, (identity, candidates) in enumerate(grouped.items(), 1):
        valid = []
        for candidate in candidates:
            url = candidate.get("url")
            if not isinstance(url, str) or len(url) > 2048:
                continue
            try:
                parsed = urlsplit(url)
                host = parsed.hostname or ""
                allowed = parsed.scheme == "https" and not parsed.username and not parsed.password and parsed.port is None and any(host == h or host.endswith("." + h) for h in ("fbcdn.net", "fbsbx.com"))
            except ValueError:
                continue
            width, height = candidate.get("width"), candidate.get("height")
            kind = candidate.get("kind")
            if not allowed or kind not in ("viewer", "original", "thumbnail") or type(width) is not int or type(height) is not int or min(width, height) < 1 or max(width, height) > 10000 or width * height > 25000000:
                continue
            valid.append({"url": url, "width": width, "height": height, "kind": kind})
        full = [c for c in valid if c["kind"] != "thumbnail"]
        if not valid:
            issues.append({"code": "missing_photo", "position": position})
            continue
        best = max(full or valid, key=lambda c: (c["width"] * c["height"], min(c["width"], c["height"])))
        if best["url"] in used_urls:
            issues.append({"code": "duplicate_photo_identity", "position": position})
            continue
        used_urls.add(best["url"])
        chosen.append(dict(best, position=position, photo_id=identity))
        if best["kind"] == "thumbnail" or min(best["width"], best["height"]) < 500:
            issues.append({"code": "small_or_thumbnail", "position": position, "width": best["width"], "height": best["height"]})
    blockers = [i for i in issues if not (i["code"] == "small_or_thumbnail" and allow_small) and not (i["code"] in ("incomplete_gallery", "missing_photo") and allow_partial)]
    # Never silently promote a later photo to main when gallery position 1 is missing.
    if not chosen or chosen[0]["position"] != 1:
        blockers.append({"code": "missing_main_photo"})
    result = {"ready": not blockers, "found": len(grouped), "selected": len(chosen), "included": min(len(chosen), max_images), "photos": chosen, "overflow_positions": [p["position"] for p in chosen[max_images:]], "issues": issues, "blockers": blockers}
    if result["ready"]:
        result["image_urls"] = [p["url"] for p in chosen[:max_images]]
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("input", type=Path)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--max-images", type=int, default=10)
    parser.add_argument("--allow-small", action="store_true")
    parser.add_argument("--allow-partial", action="store_true")
    args = parser.parse_args()
    if args.input.resolve() == args.output.resolve():
        parser.error("Keep observed input separate from the checked output")
    try:
        result = select_photos(json.loads(args.input.read_text(encoding="utf-8")), args.max_images, args.allow_small, args.allow_partial)
    except (ValueError, TypeError, AttributeError) as error:
        parser.error(str(error))
    args.output.write_text(json.dumps(result, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({k: result[k] for k in ("ready", "found", "selected", "included", "overflow_positions")}))
    return 0 if result["ready"] else 2


if __name__ == "__main__":
    raise SystemExit(main())
