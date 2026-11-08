/* Public GET-only baseline, then LOCAL proposal rendering. No production writes or chat handoffs. */
const fs=require('fs'),path=require('path');
const {chromium}=require(require.resolve('playwright',{paths:[process.env.KREV_TEST_NODE_MODULES]}));
const root=path.resolve(__dirname,'..'),out=path.join(root,'output/seller-contact'),preview=path.join(root,'.runtime/seller-contact-preview');
const references={art:'https://kniferevive.com/shop/art/jordan-ivanov-abstract-modernist-expressionist-painting-crescent-wound-study-in-absence/',ssd:'https://kniferevive.com/shop/technology/tech-ssd-drives/micron-1100-256gb-m-2-2280-sata-iii-ssd-mtfddav256tbn-cisco-oem/'};
(async()=>{
 const browser=await chromium.launch({headless:true,channel:'chrome'});
 try{
  const context=await browser.newContext({viewport:{width:390,height:844},reducedMotion:'reduce'}),page=await context.newPage();
  await context.route('**/*',r=>{
   const u=new URL(r.request().url());
   const allowed=['127.0.0.1','kniferevive.com','i0.wp.com','i1.wp.com','i2.wp.com','s0.wp.com','fonts.gstatic.com','fonts.googleapis.com'];
   return r.request().method()==='GET'&&allowed.includes(u.hostname)?r.continue():r.abort();
  });
  const evidence=[];
  for(const [kind,url] of Object.entries(references))for(const width of [390,1280]){
   await page.setViewportSize({width,height:900});await page.goto(url,{waitUntil:'domcontentloaded',timeout:45000});
   const price=page.locator('.kr-product-price-row--block').first();await price.scrollIntoViewIfNeeded();
   await page.screenshot({path:path.join(out,`${kind}-before-public-${width}.png`)});
   const state=await page.evaluate(()=>({url:location.href,product:document.body.className.match(/postid-(\d+)/)?.[1],w:innerWidth,scroll:document.documentElement.scrollWidth,contacts:[...document.querySelectorAll('.kr-product-price-row--block')].slice(0,1).map(x=>({telegram:Boolean(x.querySelector('.kr-telegram-icon-link')),whatsapp:Boolean(x.querySelector('.kr-whatsapp-icon-link'))})),seller:document.querySelector('a[href*="/store/"]')?.textContent.trim()}));evidence.push(state);
   // Reuse the captured actual layout, but change only an isolated local document for review.
   const captured=(await page.content()).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi,'').replace(/<noscript\b[^>]*>[\s\S]*?<\/noscript>/gi,'');
   await page.goto('http://127.0.0.1:8795/both.html');await page.setContent(captured,{waitUntil:'domcontentloaded'});
   const component=fs.readFileSync(path.join(preview,'both.html'),'utf8');
   await page.evaluate(({component,url})=>{
    const fixture=new DOMParser().parseFromString(component,'text/html');
    const title=document.querySelector('main h1')?.textContent.trim()||'Listing';const seller=document.querySelector('a[href*="/store/"]');const store=seller?.textContent.trim()||'Seller';let n=0;
    const build=placement=>{
     const node=fixture.querySelector('.krc-contact--'+placement).cloneNode(true);node.dataset.krcStore=store;
     const heading=node.querySelector('.krc-options-title');if(heading)heading.textContent='Message '+store;
     const options=node.querySelector('.krc-options'),summary=node.querySelector('summary.krc-trigger');if(options&&summary){options.id='krc-proposal-'+(++n);summary.setAttribute('aria-controls',options.id);}
     const text=node.querySelector('textarea');if(text)text.value='Hi, I saw “'+title+'” on KnifeRevive and have a question about it: '+url;
     node.querySelectorAll('.krc-seller a').forEach(a=>{a.href=seller?.href||'#';if(a.textContent!=='Visit seller store')a.textContent=store;});return node;
    };
    document.querySelector('.kr-product-price-row--block .kr-product-contact')?.remove();
    const purchase=document.querySelector('.wp-block-woocommerce-add-to-cart-form');if(purchase)purchase.after(build('detail'));
    document.querySelectorAll('main .kr-shop-seller-contact').forEach(el=>el.replaceWith(build('card')));
    document.querySelectorAll('script').forEach(el=>el.remove());
    document.body.append(fixture.querySelector('#krc-dialog').cloneNode(true));
    const note=document.createElement('p');note.textContent='LOCAL PROPOSAL · not deployed · sample channel destinations · links disabled';note.style.cssText='position:fixed;bottom:0;left:0;right:0;padding:8px;background:#172033;color:#fff;font:12px system-ui;text-align:center;z-index:999999';document.body.append(note);
    document.querySelectorAll('form').forEach(f=>{f.action='#';f.addEventListener('submit',e=>e.preventDefault());});document.addEventListener('click',e=>{if(e.target.closest('a'))e.preventDefault();});
    window.krcContact={analytics:false,events:'/no-collection'};
   },{component,url});
   await page.addStyleTag({path:path.join(root,'wordpress/kniferevive-seller-contact/assets/contact.css')});await page.addScriptTag({path:path.join(root,'wordpress/kniferevive-seller-contact/assets/contact.js')});
   await page.locator('.krc-contact--detail .krc-trigger').scrollIntoViewIfNeeded();await page.screenshot({path:path.join(out,`${kind}-after-LOCAL-PROPOSAL-${width}.png`)});
   if(width===390){await page.locator('.krc-contact--detail .krc-trigger').click();await page.screenshot({path:path.join(out,`${kind}-chooser-LOCAL-PROPOSAL-390.png`)});await page.keyboard.press('Escape');}
  }
  for(const [label,url] of [['shop','https://kniferevive.com/shop/'],['store','https://kniferevive.com/store/thriftcloset/']]){
   await page.setViewportSize({width:390,height:844});await page.goto(url,{waitUntil:'domcontentloaded',timeout:45000});await page.screenshot({path:path.join(out,`${label}-before-public-390.png`)});
   const captured=(await page.content()).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi,'');
   await page.goto('http://127.0.0.1:8795/both.html');await page.setContent(captured,{waitUntil:'domcontentloaded'});
   await page.evaluate(({component,label})=>{
    const fixture=new DOMParser().parseFromString(component,'text/html');let n=0;
    const build=placement=>{const node=fixture.querySelector('.krc-contact--'+placement).cloneNode(true);node.dataset.krcStore='ThriftCloset';node.querySelectorAll('.krc-options-title').forEach(x=>x.textContent='Message ThriftCloset');const panel=node.querySelector('.krc-options'),summary=node.querySelector('summary.krc-trigger');if(panel&&summary){panel.id='krc-surface-'+(++n);summary.setAttribute('aria-controls',panel.id);}return node;};
    if(label==='shop')document.querySelectorAll('main .kr-shop-seller-contact').forEach(el=>el.replaceWith(build('card')));
    else {const target=document.querySelector('.krev-store-actions');if(target){target.querySelectorAll('a:not(.krev-store-button--secondary)').forEach(x=>x.remove());target.prepend(build('storefront'));}}
    document.body.append(fixture.querySelector('#krc-dialog').cloneNode(true));
    const note=document.createElement('p');note.textContent='LOCAL PROPOSAL · not deployed · sample channel destinations · links disabled';note.style.cssText='position:fixed;bottom:0;left:0;right:0;padding:8px;background:#172033;color:#fff;font:12px system-ui;text-align:center;z-index:999999';document.body.append(note);
    document.querySelectorAll('form').forEach(f=>{f.action='#';f.addEventListener('submit',e=>e.preventDefault());});document.addEventListener('click',e=>{if(e.target.closest('a'))e.preventDefault();});window.krcContact={analytics:false};
   },{component:fs.readFileSync(path.join(preview,'both.html'),'utf8'),label});
   await page.addStyleTag({path:path.join(root,'wordpress/kniferevive-seller-contact/assets/contact.css')});await page.addScriptTag({path:path.join(root,'wordpress/kniferevive-seller-contact/assets/contact.js')});
   const action=page.locator('.krc-trigger').first();if(await action.count())await action.scrollIntoViewIfNeeded();
   await page.screenshot({path:path.join(out,`${label}-after-LOCAL-PROPOSAL-390.png`)});
  }
  fs.writeFileSync(path.join(out,'reference-baseline.json'),JSON.stringify(evidence,null,2));console.log('Public GET-only reference captures and explicitly labelled local proposal screenshots saved. No production changes.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
