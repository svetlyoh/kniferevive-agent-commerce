/* Own headless GET-only browser. Saves public chrome + native QA-rendered fragment locally. */
const fs=require('fs'),path=require('path');
const {chromium}=require(require.resolve('playwright',{paths:[process.env.KREV_TEST_NODE_MODULES]}));
const root=path.resolve(__dirname,'..'),preview=path.join(root,'.runtime/next-find-preview'),out=path.join(root,'output/next-find');
const urls=['/shop/','/categories/','/sell/','/my-account/listlab/','/technology-trade-desk/','/books/','/art-trade-gallery/','/other-finds/','/listing-guide/','/terms-and-conditions/','/help/','/help/privacy-policy/','/help/dispatch-terms/','/return-policy/','/contact/','/product-category/chefs-knife/'];
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try{
  const ctx=await browser.newContext(),page=await ctx.newPage();
  await ctx.route('**/*',r=>{const u=new URL(r.request().url());return r.request().method()==='GET'&&['kniferevive.com','i0.wp.com','i1.wp.com','i2.wp.com','s0.wp.com'].includes(u.hostname)?r.continue():r.abort();});
  await page.goto('https://kniferevive.com/',{waitUntil:'domcontentloaded'});
  const chrome=await page.evaluate(()=>({head:[...document.head.querySelectorAll('style,link[rel="stylesheet"]')].map(e=>e.outerHTML).join('\n'),header:[...document.querySelectorAll('.wp-site-blocks>.kr-header,.wp-site-blocks>.kr-drawer-backdrop,.wp-site-blocks>.kr-drawer')].map(x=>x.outerHTML).join(''),footer:document.querySelector('.wp-site-blocks>.kr-footer')?.outerHTML,bodyClass:document.body.className,lang:document.documentElement.lang,title:document.title}));
  if(!chrome.header||!chrome.footer)throw Error('Actual site header/footer not found');
  const strip=s=>s.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi,'');
  const fragment=fs.readFileSync(path.join(preview,'fragment.html'),'utf8');
  const html=`<!doctype html><html lang="en-US"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>Next Find · local draft preview</title><link rel="canonical" href="https://kniferevive.com/your-next-find/"><meta name="description" content="Discover kitchen knives, technology and other finds. Browse seller listings or start your own shop on KnifeRevive. Learn about Dispatch.">${strip(chrome.head)}<link rel="stylesheet" href="campaign-assets/assets/campaign.css"></head><body class="${chrome.bodyClass.replace(/home|blog/g,'')} kr-campaign-preview"><div class="wp-site-blocks">${strip(chrome.header)}<main id="wp--skip-link--target" class="wp-block-group kr-campaign-main"><div class="wp-block-post-content">${fragment}</div></main>${strip(chrome.footer)}</div><p style="position:fixed;bottom:0;left:0;right:0;margin:0;padding:7px 12px;background:#191919;color:#fff;font:11px/1.4 system-ui;text-align:center;z-index:99999">LOCAL DRAFT · Native WooCommerce test rendering · Captured site header/footer · Not published</p></body></html>`;
  fs.writeFileSync(path.join(preview,'index.html'),html);fs.cpSync(path.join(root,'wordpress/kniferevive-campaign-landing/assets'),path.join(preview,'campaign-assets/assets'),{recursive:true});
  const results=[];
  for(let i=0;i<urls.length;i+=4){await Promise.all(urls.slice(i,i+4).map(async route=>{const start=Date.now();try{const r=await ctx.request.get('https://kniferevive.com'+route,{timeout:30000});const html=await r.text();results.push({route,status:r.status(),final_url:r.url(),ms:Date.now()-start,title:html.match(/<title>(.*?)<\/title>/is)?.[1],protected_login:route.includes('listlab')&&r.url().includes('login')});}catch(e){results.push({route,error:e.message});}}));}
  const inventory=await ctx.request.get('https://kniferevive.com/wp-json/wc/store/v1/products?slug=wusthof-gourmet-chefs-knife-8-inch,amazon-fire-tv-stick-4k-select-with-alexa-voice-remote,model-1728-msrp-150');
  const products=(await inventory.json()).map(p=>({id:p.id,slug:p.slug,name:p.name,permalink:p.permalink,is_in_stock:p.is_in_stock}));
  fs.writeFileSync(path.join(out,'public-discovery.json'),JSON.stringify({checked_at:new Date().toISOString(),products,links:results},null,2));
  console.log('Local native-rendered preview with captured site chrome prepared; public destinations audited. No production writes.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
