"""Resize supplied photographs without cropping/retouching; preserve source hashes."""
from pathlib import Path
import hashlib,json,argparse
from PIL import Image
p=argparse.ArgumentParser();p.add_argument('kit',type=Path);a=p.parse_args()
root=Path(__file__).resolve().parents[1];out=root/'wordpress/kniferevive-campaign-landing/assets/images';out.mkdir(parents=True,exist_ok=True)
manifest={}
for key,source in [('wusthof','assets/web/wusthof.webp'),('fire-tv','assets/web/fire_tv.webp'),('cutco','assets/web/cutco.webp'),('dispatch','assets/dispatch_illustration.png'),('og-marketplace','exports/kniferevive_01_marketplace_square_1080x1080.png')]:
 raw=Image.open(a.kit/source)
 im=raw.convert('RGBA' if 'A' in raw.getbands() else 'RGB')
 variants=[]
 for width in ([480,960] if key=='dispatch' else [1080] if key=='og-marketplace' else [320,640,960]):
  w=min(width,im.width);h=round(im.height*w/im.width)
  scaled=im.resize((w,h),Image.Resampling.LANCZOS)
  target=out/f'{key}-{width}.webp';scaled.save(target,'WEBP',quality=86,method=6)
  variants.append({'file':target.name,'width':w,'height':h,'bytes':target.stat().st_size})
 manifest[key]={'source':source,'source_sha256':hashlib.sha256((a.kit/source).read_bytes()).hexdigest(),'source_width':im.width,'source_height':im.height,'variants':variants}
(out/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
print(json.dumps({k:sum(v['bytes'] for v in d['variants']) for k,d in manifest.items()}))
