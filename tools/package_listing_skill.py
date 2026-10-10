"""Package the separate Listing skill and Concierge without the seller branch."""
from pathlib import Path
import hashlib
import json
import re
import subprocess
import zipfile

root = Path(__file__).resolve().parents[1]
out = root / "dist"
out.mkdir(exist_ok=True)
commit = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=root, text=True).strip()
manifest = {"source_commit": commit, "components": {}}
for slug, version in [("kniferevive-listing", "1.0.0"), ("kniferevive-concierge", "0.6.3")]:
    base = root / "skills" / slug
    entries = []
    archive = out / f"{slug}-{version}.zip"
    with zipfile.ZipFile(archive, "w", zipfile.ZIP_DEFLATED) as bundle:
        for file in sorted(p for p in base.rglob("*") if p.is_file()):
            if file.is_symlink() or file.name.startswith(".") or file.suffix in {".log", ".sql", ".db", ".pyc"}:
                raise SystemExit(f"Forbidden skill package file: {file}")
            payload = file.read_bytes().replace(b"\r\n", b"\n")
            source = subprocess.check_output(["git", "show", commit + ":" + file.relative_to(root).as_posix()], cwd=root)
            if payload != source:
                raise SystemExit(f"Commit skill changes before packaging: {file}")
            if re.search(rb"(?:sk_live_|whsec_)[A-Za-z0-9]{16,}", payload):
                raise SystemExit("Credential-like literal in package")
            if file.suffix == ".md":
                for target in re.findall(r"\]\(([^)]+)\)", payload.decode("utf-8")):
                    if "://" in target or target.startswith("#"):
                        continue
                    resolved = (file.parent / target.split("#", 1)[0]).resolve()
                    if not resolved.is_relative_to(base.resolve()) or not resolved.is_file():
                        raise SystemExit(f"Missing or nonportable skill reference: {file}: {target}")
            name = file.relative_to(base.parent).as_posix()
            info = zipfile.ZipInfo(name, (2026, 10, 10, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            bundle.writestr(info, payload)
            entries.append({"path": name, "sha256": hashlib.sha256(payload).hexdigest(), "bytes": len(payload)})
    manifest["components"][archive.name] = {"version": version, "sha256": hashlib.sha256(archive.read_bytes()).hexdigest(), "files": entries}
(out / "listing-skill-release-manifest.json").write_text(json.dumps(manifest, indent=2) + "\n", encoding="utf-8")
print("Packaged Listing 1.0.0 and Concierge 0.6.3; all references and committed file hashes verified.")
