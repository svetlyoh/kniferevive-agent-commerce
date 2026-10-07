"""Build explicit plugin/skill ZIPs and per-file SHA-256 manifests; exclude runtimes."""
from pathlib import Path
import hashlib
import json
import zipfile
import re

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'dist'
OUT.mkdir(exist_ok=True)
plugin_version = re.search(r'\* Version: ([0-9.]+)', (ROOT / 'wordpress/kniferevive-agent-commerce/kniferevive-agent-commerce.php').read_text()).group(1)
manifest = {'version': plugin_version, 'components': {}}
for folder, archive in [('wordpress/kniferevive-agent-commerce',f'kniferevive-agent-commerce-{plugin_version}.zip'), ('skills/kniferevive-concierge','kniferevive-concierge-0.1.0.zip')]:
    base = ROOT / folder
    files = sorted(p for p in base.rglob('*') if p.is_file())
    entries = []
    for file in files:
        if file.is_symlink() or file.name.startswith('.') or file.name in {'wp-config.php'} or file.suffix.lower() in {'.pyc','.pyo','.pyd','.log','.sql','.sqlite','.db'}:
            raise RuntimeError(f'Forbidden artifact: {file.relative_to(ROOT)}')
        payload = file.read_bytes()
        # Real credential literals cannot enter the public components. Runtime access stays server-managed.
        text = payload.decode('utf-8')
        import re
        if re.search(r'(?:sk_live_|whsec_)[A-Za-z0-9]{16,}', text):
            raise RuntimeError(f'Credential-like literal in {file.relative_to(ROOT)}')
        entries.append({'path': file.relative_to(base.parent).as_posix(), 'sha256': hashlib.sha256(payload).hexdigest(), 'bytes': len(payload)})
    with zipfile.ZipFile(OUT / archive,'w',zipfile.ZIP_DEFLATED) as bundle:
        for file in files: bundle.write(file,file.relative_to(base.parent).as_posix())
    manifest['components'][archive] = {'sha256': hashlib.sha256((OUT / archive).read_bytes()).hexdigest(), 'files': entries}
(OUT / 'release-manifest.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
print('Built plugin/skill ZIPs and exact release-manifest.json.')
