"""Package Marketplace Imports and the portable skill; deterministic LF source bytes."""
from pathlib import Path
import hashlib, json, re, zipfile, subprocess

root = Path(__file__).resolve().parents[1]
out = root / "dist"
out.mkdir(exist_ok=True)
manifest = {"plugin_version": "1.0.0", "skill_version": "0.6.2", "components": {}}
source_commit = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=root, text=True).strip()
manifest["source_commit"] = source_commit
for folder, archive in [("wordpress/kniferevive-listlab-import", "kniferevive-listlab-import-1.0.0.zip"), ("skills/kniferevive-concierge", "kniferevive-concierge-0.6.2.zip")]:
    base = root / folder
    entries = []
    with zipfile.ZipFile(out / archive, "w", zipfile.ZIP_DEFLATED) as bundle:
        for file in sorted(p for p in base.rglob("*") if p.is_file()):
            if file.is_symlink() or file.name.startswith(".") or file.suffix in {".log", ".sql", ".db", ".pyc"}:
                raise SystemExit("Forbidden package file: " + str(file))
            payload = file.read_bytes().replace(b"\r\n", b"\n")
            source = subprocess.check_output(["git", "show", source_commit + ":" + file.relative_to(root).as_posix()], cwd=root)
            if payload != source:
                raise SystemExit("Commit source differs; commit component edits before packaging: " + str(file))
            if re.search(rb"(?:sk_live_|whsec_)[A-Za-z0-9]{16,}", payload):
                raise SystemExit("Credential-like literal in package")
            name = file.relative_to(base.parent).as_posix()
            info = zipfile.ZipInfo(name, (2026, 10, 10, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            bundle.writestr(info, payload)
            entries.append({"path": name, "sha256": hashlib.sha256(payload).hexdigest(), "bytes": len(payload)})
    manifest["components"][archive] = {"sha256": hashlib.sha256((out / archive).read_bytes()).hexdigest(), "files": entries}
(out / "marketplace-import-release-manifest.json").write_text(json.dumps(manifest, indent=2) + "\n", encoding="utf-8")
print("Packaged Marketplace Imports 1.0.0 and Concierge 0.6.2 with exact file hashes.")
