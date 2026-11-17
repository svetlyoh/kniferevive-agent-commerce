/* Automated isolated browser QA. Never uses the owner's browser profile or sends messages/payments. */
const fs=require('fs');const path=require('path');
const modules=process.env.KREV_TEST_NODE_MODULES;
if(!modules)throw Error('Set KREV_TEST_NODE_MODULES to Playwright packages.');
const {chromium,firefox}=require(require.resolve('playwright',{paths:[modules]}));
const channel=process.env.KRC_BROWSER_CHANNEL||'msedge';
const root=path.resolve(__dirname,'..'),out=path.join(root,'output/seller-contact');fs.mkdirSync(out,{recursive:true});
const checks=[];const check=(name,pass)=>{checks.push({name,pass:Boolean(pass)});if(!pass)throw Error(name);};
(async()=>{
 const browser=channel==='firefox'?await firefox.launch({headless:true}):await chromium.launch({headless:true,channel});
 try{
  const context=await browser.newContext({viewport:{width:1280,height:900},reducedMotion:'reduce'});
  const page=await context.newPage();let events=[];const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{
   const u=new URL(route.request().url());
   if(u.hostname!=='127.0.0.1')return route.abort();
   if(u.pathname==='/no-collection'){events.push(route.request().postDataJSON());return route.fulfill({status:200,contentType:'application/json',body:'{"accepted":true}'});}
   return route.continue();
  });
  for(const width of [320,375,390,430,768,1280]){
   await page.setViewportSize({width,height:900});await page.goto('http://127.0.0.1:8795/both.html');
   check(`No overflow at${width}`,await page.evaluate(()=>document.documentElement.scrollWidth===innerWidth));
   check(`Three distinct surface CTAs at${width}`,await page.locator('.krc-trigger').count()===3);
   check(`Touch targets at${width}`,await page.locator('.krc-trigger').evaluateAll(els=>els.every(el=>{const r=el.getBoundingClientRect();return r.width>=44&&r.height>=44;})));
   await page.locator('.krc-contact--detail .krc-trigger').click();
   check(`Chooser visible at${width}`,await page.locator('dialog').evaluate(el=>el.open));
   check(`Chooser contained at${width}`,await page.locator('dialog').evaluate(el=>{const r=el.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.height<=innerHeight;}));
   if(width===390){await page.screenshot({path:path.join(out,'chooser-mobile-390.png')});}
   await page.keyboard.press('Escape');check(`Escape restores trigger at${width}`,await page.evaluate(()=>document.activeElement.classList.contains('krc-trigger')));
   if(width===390||width===1280)await page.screenshot({path:path.join(out,`product-and-cards-${width}.png`),fullPage:true});
  }
  await page.setViewportSize({width:1280,height:900});await page.goto('http://127.0.0.1:8795/both.html');await page.locator('.krc-contact--detail .krc-trigger').click();
  for(let i=0;i<12;i++){await page.keyboard.press('Tab');check('Modal focus stays inside '+i,await page.evaluate(()=>Boolean(document.activeElement.closest('dialog'))));}
  await page.evaluate(()=>Object.defineProperty(navigator,'clipboard',{configurable:true,value:{writeText:async value=>{window.krcCopied=value;}}}));
  await page.locator('dialog .krc-copy-button').click();check('Copy question produces item context',await page.evaluate(()=>window.krcCopied.includes('on KnifeRevive')&&window.krcCopied.includes('http://127.0.0.1:8803/')));
  check('Two safe external channel links',await page.locator('dialog a[data-krc-channel]').evaluateAll(els=>els.length===2&&els.every(el=>el.rel==='noopener noreferrer'&&el.target==='_blank')));
  await page.locator('.krc-close').click();check('No metrics when disabled',events.length===0);
  await page.evaluate(()=>{window.krcContact.analytics=true;window.wp_has_consent=()=>false;document.dispatchEvent(new Event('wp_listen_for_consent_change'));});
  await page.locator('.krc-contact--detail .krc-trigger').click();await page.locator('.krc-close').click();check('No metrics without consent',events.length===0);
  await page.evaluate(()=>{window.wp_has_consent=()=>true;document.dispatchEvent(new Event('wp_listen_for_consent_change'));});
  await page.locator('.krc-contact--detail').scrollIntoViewIfNeeded();await page.waitForTimeout(350);
  check('Consented eligibility and visible impression emitted',events.some(e=>e.event==='seller_contact_eligible_view')&&events.some(e=>e.event==='seller_contact_cta_impression'));
  check('Metric payload excludes contact details',events.every(e=>!('url'in e)&&!('question'in e)&&!JSON.stringify(e).includes('15551234567')&&!JSON.stringify(e).includes('FixtureSeller')));
  const before=events.filter(e=>e.event==='seller_contact_cta_impression').length;
  await page.evaluate(()=>{const clone=document.querySelector('.krc-contact--detail').cloneNode(true);document.querySelector('main').prepend(clone);});
  await page.locator('.krc-contact--detail').first().scrollIntoViewIfNeeded();await page.waitForTimeout(350);
  check('Carousel clone impressions deduplicate',events.filter(e=>e.event==='seller_contact_cta_impression').length===before);
  await page.locator('.krc-contact--detail .krc-trigger').first().click();check('Lazy clone opens shared modal',await page.locator('dialog').evaluate(el=>el.open));await page.keyboard.press('Escape');
  check('No duplicate chooser IDs after cloning',await page.evaluate(()=>{const ids=[...document.querySelectorAll('.krc-options[id]')].map(e=>e.id);return new Set(ids).size===ids.length;}));
  for(const variant of ['whatsapp','telegram','neither']){
   await page.goto(`http://127.0.0.1:8795/${variant}.html`);
   check(variant+' correct CTA count',await page.locator('.krc-trigger').count()===(variant==='neither'?0:3));
   check(variant+' no dead chooser',await page.locator('.krc-chooser').count()===0);
  }
  await page.goto('http://127.0.0.1:8795/dark.html');
  check('Actual kr-tech-theme body class present in dark fixture',await page.locator('body').evaluate(el=>el.classList.contains('kr-tech-theme')));
  check('Dark contact surface and text remain contrasted',await page.locator('.krc-trigger').first().evaluate(el=>{const s=getComputedStyle(el);return s.backgroundColor==='rgb(11, 21, 33)'&&s.color==='rgb(245, 251, 255)';}));
  await page.locator('.krc-contact--detail .krc-trigger').click();check('Dark chooser inherits the real Tech surface',await page.locator('dialog').evaluate(el=>getComputedStyle(el).backgroundColor==='rgb(11, 21, 33)'));await page.keyboard.press('Escape');
  const nojs=await browser.newContext({javaScriptEnabled:false,viewport:{width:390,height:844}});const fallback=await nojs.newPage();await fallback.route('**/*',r=>new URL(r.request().url()).hostname==='127.0.0.1'?r.continue():r.abort());
  await fallback.goto('http://127.0.0.1:8795/both.html');await fallback.locator('.krc-contact--detail summary.krc-trigger').click();check('No-JS channel chooser works',await fallback.locator('.krc-contact--detail .krc-options').isVisible());check('No-JS copy text remains available',await fallback.locator('.krc-contact--detail textarea').isVisible());await fallback.screenshot({path:path.join(out,'no-js-mobile.png'),fullPage:true});await nojs.close();
  check('No uncaught browser errors',errors.length===0);
  fs.writeFileSync(path.join(out,`browser-results-${channel}.json`),JSON.stringify(checks,null,2));console.log(`${checks.length} ${channel} browser checks passed; external navigation blocked; no production mutations.`);
 }finally{await browser.close();}
})().catch(e=>{console.error(e);fs.writeFileSync(path.join(out,'browser-results.json'),JSON.stringify(checks,null,2));process.exitCode=1;});
