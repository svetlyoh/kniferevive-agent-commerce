"""Build separately installable plugin and narrow integration archive; no site credentials."""
import argparse, hashlib, json, zipfile
from pathlib import Path

root = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument('--baseline-root', type=Path, required=True)
args = parser.parse_args()
dist = root / 'dist'
dist.mkdir(exist_ok=True)
plugin = root / 'wordpress/kniferevive-seller-contact'
overlays = []
for name in ['account-avatar.php', 'annex.php', 'knife-card.php', 'product-subtitle.php']:
    overlays.append((root / 'theme-overlays/kniferevive/inc' / name,
                     args.baseline_root / 'theme/inc' / name,
                     'wp-content/themes/kniferevive/inc/' + name))
overlays.append((root / 'wordpress-overlays/kniferevive-seller-storefront/includes/class-krev-storefront-renderer.php',
                 args.baseline_root / 'kniferevive-seller-storefront/includes/class-krev-storefront-renderer.php',
                 'wp-content/plugins/kniferevive-seller-storefront/includes/class-krev-storefront-renderer.php'))

def sha(data):
    return hashlib.sha256(data).hexdigest()

def lf(path):
    return path.read_bytes().replace(b'\r\n', b'\n')

manifest = {'version': '1.0.0', 'date': '2026-10-25', 'messaging_deployed': False,
            'hash_mode': 'SHA256 of UTF-8 bytes with CRLF normalized to LF; no other whitespace changes',
            'defaults': {'master': False, 'analytics': False}, 'integration_files': [], 'plugin_files': []}
for src, before, target in overlays:
    manifest['integration_files'].append({'source': src.relative_to(root).as_posix(), 'target': target,
                                         'before_sha256_lf': sha(lf(before)), 'after_sha256_lf': sha(lf(src))})
for src in sorted(plugin.rglob('*')):
    if src.is_file():
        manifest['plugin_files'].append({'path': src.relative_to(plugin.parent).as_posix(), 'sha256_lf': sha(lf(src))})
order = root / 'wordpress-overlays/kniferevive-seller-orders/templates/seller-orders.php'
manifest['separate_already_deployed_order_patch'] = {
    'target': 'wp-content/plugins/kniferevive-seller-orders/templates/seller-orders.php',
    'before_sha256_lf': sha(lf(args.baseline_root / 'seller-orders.php')),
    'after_sha256_lf': sha(lf(order)), 'included_in_messaging_archives': False}
dest = root / 'docs/BUYER_SELLER_CHAT_RELEASE_MANIFEST.json'
dest.write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')

def archive(path, entries):
    with zipfile.ZipFile(path, 'w', zipfile.ZIP_DEFLATED) as z:
        for source, name in entries:
            assert not name.startswith('/') and '..' not in Path(name).parts
            info = zipfile.ZipInfo(name, (2026, 10, 25, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            z.writestr(info, source.read_bytes())
    with zipfile.ZipFile(path) as z:
        assert z.testzip() is None

archives = [dist / 'kniferevive-seller-contact-1.0.0.zip', dist / 'kniferevive-seller-contact-1.0.0-integration-overlays.zip']
archive(archives[0], [(p, p.relative_to(plugin.parent).as_posix()) for p in sorted(plugin.rglob('*')) if p.is_file()])
archive(archives[1], [(src, target) for src, _, target in overlays])
(dist / 'SHA256SUMS.txt').write_text(''.join(sha(p.read_bytes()) + '  ' + p.name + '\n' for p in archives), encoding='utf-8')
print(json.dumps({'archives': [{'name': p.name, 'bytes': p.stat().st_size} for p in archives], 'overlay_files': len(overlays)}))
