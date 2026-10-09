"""Package the minimal Seller Orders fix against an exact owner-supplied live backup."""
from pathlib import Path
import argparse, hashlib, json, shutil, zipfile

root=Path(__file__).resolve().parents[1]
parser=argparse.ArgumentParser()
parser.add_argument('baseline',type=Path,help='Captured production directory matching the overlay baseline manifest')
args=parser.parse_args()
baseline=args.baseline.resolve()
overlay=root/'wordpress-overlays/kniferevive-seller-orders'
manifest=json.loads((overlay/'baseline-manifest.json').read_text(encoding='utf-8-sig'))
actual={p.relative_to(baseline).as_posix():p for p in baseline.rglob('*') if p.is_file()}
expected={f['path']:f for f in manifest['files']}
if actual.keys()!=expected.keys():raise SystemExit('Baseline file inventory differs; inspect before packaging.')
for name,path in actual.items():
    if path.is_symlink() or hashlib.sha256(path.read_bytes()).hexdigest()!=expected[name]['sha256']:
        raise SystemExit('Baseline mismatch: '+name)
candidate=root/'.runtime/seller-booking-visibility-20261008/candidate/kniferevive-seller-orders'
# Reuse this exact controlled build directory; no recursive deletion or external paths.
candidate.mkdir(parents=True,exist_ok=True)
if any(p.is_file() and p.relative_to(candidate).as_posix() not in expected for p in candidate.rglob('*')):
    raise SystemExit('Unexpected build file; inspect before packaging.')
for name,path in actual.items():
    target=candidate/name;target.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(path,target)
for path in overlay.rglob('*.php'):
    name=path.relative_to(overlay).as_posix()
    if name not in expected:raise SystemExit('Overlay adds an unexpected file: '+name)
    shutil.copyfile(path,candidate/name)
out=root/'dist';out.mkdir(exist_ok=True)
release={'version':manifest['target_version'],'schema_version':manifest['schema_version'],'components':{}}
for source,version in [(baseline,manifest['baseline_version']),(candidate,manifest['target_version'])]:
    archive=out/f'kniferevive-seller-orders-{version}.zip'
    entries=[]
    with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED) as bundle:
        for name in sorted(expected):
            payload=(source/name).read_bytes()
            info=zipfile.ZipInfo('kniferevive-seller-orders/'+name,(2026,10,8,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED
            bundle.writestr(info,payload)
            entries.append({'path':info.filename,'sha256':hashlib.sha256(payload).hexdigest(),'bytes':len(payload)})
    release['components'][archive.name]={'sha256':hashlib.sha256(archive.read_bytes()).hexdigest(),'files':entries}
(out/'seller-orders-release-manifest.json').write_text(json.dumps(release,indent=2)+'\n',encoding='utf-8')
print(f"Verified {len(expected)}-file baseline; packaged rollback {manifest['baseline_version']} and overlay {manifest['target_version']}.")
