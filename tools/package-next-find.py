"""Package the new plugin, one-file theme overlay and review bundle. No deployment."""
from pathlib import Path
import json,hashlib,zipfile,gzip
r=Path(__file__).resolve().parents[1];dist=r/'dist';dist.mkdir(exist_ok=True)
p=r/'wordpress/kniferevive-campaign-landing'
sha=lambda b:hashlib.sha256(b).hexdigest()
manifest={'version':'1.0.0','date':'2026-10-25','deployed':False,'published':False,'analytics_default':False,
 'proposed_page':'https://kniferevive.com/your-next-find/','source_kit':'KnifeRevive_Meta_Campaign_Updated_Ads_And_Codex_Kit',
 'product_bindings_observed_not_hardcoded':{'wusthof':538,'fire-tv':3196,'cutco':1448},
 'files':[],'payloads':{}}
for f in sorted(p.rglob('*')):
 if f.is_file():manifest['files'].append({'path':f.relative_to(p.parent).as_posix(),'bytes':f.stat().st_size,'sha256':sha(f.read_bytes())})
for name in ['campaign.css','campaign.js']:
 data=(p/'assets'/name).read_bytes();manifest['payloads'][name]={'raw_bytes':len(data),'gzip_bytes':len(gzip.compress(data)),'requested_by_default':name.endswith('.css')}
manifest['template']={'target':'wp-content/themes/kniferevive/templates/page-your-next-find.html','sha256':sha((r/'theme-overlays/kniferevive/templates/page-your-next-find.html').read_bytes())}
(r/'docs/NEXT_FIND_RELEASE_MANIFEST.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
def pack(target,entries):
 with zipfile.ZipFile(target,'w',zipfile.ZIP_DEFLATED) as z:
  for source,name in entries:
   assert '..' not in Path(name).parts and not name.startswith('/')
   info=zipfile.ZipInfo(name,(2026,10,25,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;z.writestr(info,source.read_bytes())
 with zipfile.ZipFile(target) as z:assert z.testzip() is None
plugin_zip=dist/'kniferevive-campaign-landing-1.0.0.zip'
pack(plugin_zip,[(f,f.relative_to(p.parent).as_posix()) for f in sorted(p.rglob('*')) if f.is_file()])
overlay_zip=dist/'kniferevive-next-find-template-overlay-1.0.0.zip'
pack(overlay_zip,[(r/'theme-overlays/kniferevive/templates/page-your-next-find.html',manifest['template']['target'])])
bundle=dist/'kniferevive-next-find-review-release-1.0.0.zip'
entries=[(plugin_zip,plugin_zip.name),(overlay_zip,overlay_zip.name)]
for name in ['NEXT_FIND_IMPLEMENTATION_REPORT.md','NEXT_FIND_RELEASE_MANIFEST.json','next-find-page-content.html','next-find-page-draft.json']:
 entries.append((r/'docs'/name,'docs/'+name))
for name in ['hero-chrome-390.png','hero-chrome-1440.png','dispatch-chrome-390.png','landing-chrome-1440.png','php-results.json','browser-results-chrome.json','browser-results-firefox.json','performance-summary.json']:
 entries.append((r/'output/next-find'/name,'qa/'+name))
pack(bundle,entries)
(dist/'SHA256SUMS.txt').write_text(''.join(sha(f.read_bytes())+'  '+f.name+'\n' for f in [plugin_zip,overlay_zip,bundle]),encoding='utf-8')
print(json.dumps({f.name:f.stat().st_size for f in [plugin_zip,overlay_zip,bundle]}))
