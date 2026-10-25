/* Isolated headless browser QA. No owner profile, production writes, messages, purchases or trackers. */
const fs=require('fs'),path=require('path');
const {chromium,firefox}=require(require.resolve('playwright',{paths:[process.env.KREV_TEST_NODE_MODULES]}));
const root=path.resolve(__dirname,'..'),out=path.join(root,'output/next-find'),channel=process.env.KRCL_BROWSER||'chrome';
const checks=[];const check=(name,pass)=>{checks.push({name,pass:!!pass});if(!pass)throw Error(name);};
(async()=>{
 const browser=channel==='firefox'?await firefox.launch({headless:true}):await chromium.launch({channel,headless:true});
 try{
  const ctx=await browser.newContext({viewport:{width:390,height:844},reducedMotion:'reduce'}),page=await ctx.newPage();let externalTracking=0;const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await ctx.route('**/*',r=>{const u=new URL(r.request().url());if(/facebook|connect\.facebook|google-analytics|doubleclick/.test(u.hostname))externalTracking++;return r.request().method()==='GET'&&['127.0.0.1','kniferevive.com','i0.wp.com','i1.wp.com','i2.wp.com','s0.wp.com'].includes(u.hostname)?r.continue():r.abort();});
  for(const width of [320,375,390,768,1024,1440]){
   await page.setViewportSize({width,height:900});await page.goto('http://127.0.0.1:8799/index.html',{waitUntil:'networkidle'});
   check('No horizontal overflow at '+width,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   check('One H1 at '+width,await page.locator('h1').count()===1);
   check('Normal site header and footer at '+width,await page.locator('.kr-header').count()===1&&await page.locator('.kr-footer').count()===1);
   check('Hero photos load at '+width,await page.locator('.kr-campaign-photo img').evaluateAll(els=>els.length===3&&els.every(x=>x.complete&&x.naturalWidth>0)));
   check('Full product photographs use contain at '+width,await page.locator('.kr-campaign-photo img').evaluateAll(els=>els.every(x=>getComputedStyle(x).objectFit==='contain')));
   check('All prominent campaign actions meet 44px at '+width,await page.locator('.kr-campaign-button,.kr-campaign-item-link,.kr-campaign-faq summary').evaluateAll(els=>els.every(x=>x.getBoundingClientRect().height>=44)));
   check('No purchase form at '+width,await page.locator('.kr-campaign form').count()===0);
   if(width===375||width===390)check('Buy and sell buttons visible in first screen at '+width,await page.locator('.kr-campaign-intro .kr-campaign-button').evaluateAll(els=>els.length===2&&els.every(x=>x.getBoundingClientRect().bottom<=innerHeight)));
   if(width===390||width===1440){await page.screenshot({path:path.join(out,`landing-${channel}-${width}.png`),fullPage:true});await page.screenshot({path:path.join(out,`hero-${channel}-${width}.png`)});}
  }
  await page.setViewportSize({width:390,height:844});await page.goto('http://127.0.0.1:8799/index.html?utm_source=meta&utm_medium=paid_social&utm_content=dispatch#dispatch');
  check('Dispatch anchor below sticky-header allowance',await page.locator('#dispatch').evaluate(x=>x.getBoundingClientRect().top>=100&&x.getBoundingClientRect().top<200));
  await page.screenshot({path:path.join(out,`dispatch-${channel}-390.png`)});
  const faq=page.locator('.kr-campaign-faq details').first();await faq.locator('summary').focus();await page.keyboard.press('Enter');check('Keyboard opens native FAQ',await faq.getAttribute('open')!==null);await page.keyboard.press('Enter');check('Keyboard closes native FAQ',await faq.getAttribute('open')===null);
  const cta=page.locator('.kr-campaign-intro .kr-campaign-button').first();await cta.focus();check('Visible keyboard focus',await cta.evaluate(x=>getComputedStyle(x).outlineStyle!=='none'&&parseFloat(getComputedStyle(x).outlineWidth)>=3));
  check('Seller links retain whitelisted campaign attribution',await page.locator('.kr-campaign a[data-kr-campaign-event="seller_signup_click"]').evaluateAll(els=>els.every(a=>a.href.startsWith('https://kniferevive.com/sell/')&&new URL(a.href).searchParams.get('utm_content')==='dispatch')));
  check('No campaign JS or SDK loaded by default',await page.locator('script').count()===0);check('No tracking requests before consent',externalTracking===0);
  // Optional sender tested against a stub only. No Meta requests or claimed Events Manager receipt.
  await page.evaluate(()=>{window.krEvents=[];window.fbq=(...args)=>window.krEvents.push(args);window.krCampaign={enabled:true,content:'dispatch'};document.cookie='kr_meta_marketing=no; Path=/';document.addEventListener('click',e=>{if(e.target.closest('.kr-campaign a'))e.preventDefault();});});
  await page.addScriptTag({path:path.join(root,'wordpress/kniferevive-campaign-landing/assets/campaign.js')});await cta.click();check('Declined consent sends no custom events',await page.evaluate(()=>window.krEvents.length===0));
  await page.evaluate(()=>{document.cookie='kr_meta_marketing=yes; Path=/';});await cta.click();await cta.click();check('Consented repeated browse action is deduplicated',await page.evaluate(()=>window.krEvents.filter(x=>x[1]==='browse_click').length===1));
  await page.evaluate(()=>Object.defineProperty(navigator,'globalPrivacyControl',{configurable:true,value:true}));await page.locator('.kr-campaign-intro a[data-kr-campaign-event="seller_signup_click"]').click();check('GPC overrides accepted cookie',await page.evaluate(()=>window.krEvents.length===1));
  check('Custom events never initialize a dataset or duplicate PageView/Purchase',await page.evaluate(()=>window.krEvents.every(x=>x[0]==='trackCustom'&&!['PageView','Purchase','ViewContent'].includes(x[1]))));
  check('Event payload has context only, no URLs or UTMs',await page.evaluate(()=>window.krEvents.every(x=>Object.keys(x[2]).every(k=>['campaign','campaign_content','item_context'].includes(k)))));
  const accepted=await ctx.newPage();await accepted.goto('http://127.0.0.1:8799/index.html');await accepted.evaluate(()=>{window.krEvents=[];window.fbq=(...args)=>window.krEvents.push(args);window.krCampaign={enabled:true,content:'marketplace'};document.cookie='kr_meta_marketing=yes; Path=/';document.addEventListener('click',e=>{if(e.target.closest('.kr-campaign a'))e.preventDefault();});});await accepted.addScriptTag({path:path.join(root,'wordpress/kniferevive-campaign-landing/assets/campaign.js')});
  check('Accepted initial visit sends one custom landing view',await accepted.evaluate(()=>window.krEvents.length===1&&window.krEvents[0][1]==='landing_page_view'));
  await accepted.locator('.kr-campaign-photo-card-wusthof').click();await accepted.locator('.kr-campaign-photo-card-wusthof').click();check('Featured action deduplicates approved item context',await accepted.evaluate(()=>window.krEvents.filter(x=>x[1]==='featured_product_click').length===1&&window.krEvents.find(x=>x[1]==='featured_product_click')[2].item_context==='wusthof'));await accepted.close();
  check('No page JavaScript errors',errors.length===0);
  const noJS=await browser.newContext({javaScriptEnabled:false,viewport:{width:390,height:844}}),np=await noJS.newPage();await np.goto('http://127.0.0.1:8799/index.html');await np.locator('.kr-campaign-faq summary').first().click();check('FAQ works without JavaScript',await np.locator('.kr-campaign-faq details').first().getAttribute('open')!==null);check('Browse and seller destinations work without JavaScript',await np.locator('.kr-campaign-intro a.kr-campaign-button').count()===2);await noJS.close();
  fs.writeFileSync(path.join(out,`browser-results-${channel}.json`),JSON.stringify(checks,null,2));console.log(`${channel}: ${checks.length} browser checks passed.`);
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
