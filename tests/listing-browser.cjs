const fs=require('fs'),path=require('path');
const {chromium}=require(require.resolve('playwright',{paths:[process.env.KREV_TEST_NODE_MODULES]}));
const root=path.resolve(__dirname,'..');
(async()=>{
  const browser=await chromium.launch({headless:true,channel:'msedge'});
  try {
    const page=await browser.newPage({viewport:{width:390,height:844}}),failures=[];
    page.on('pageerror',e=>failures.push(e.message));
    await page.route('**/*',route=>new URL(route.request().url()).hostname==='localhost'?route.continue():route.abort());
    const fixture=JSON.parse(fs.readFileSync(path.join(root,'.runtime/listing-ui-fixture.json'),'utf8'));
    await page.goto(fixture.review_url);await page.getByLabel('Receipt email').waitFor();
    if(new URL(page.url()).hash)throw Error('Private token fragment remained in address bar');
    if(!(await page.locator('main').innerText()).includes('Estimate only'))throw Error('Incomplete address presented final total');
    await page.getByLabel('Receipt email').fill('browser@example.invalid');
    for(const kind of ['Billing','Delivery'])for(const [label,value] of [['Street address','123 Synthetic Street'],['City','San Francisco'],['State code','CA'],['Postal code','94110']])await page.getByLabel(`${kind} ${label}`,{exact:true}).fill(value);
    await page.getByRole('button',{name:'Calculate native quote'}).click();
    await page.getByRole('button',{name:'Continue to secure payment'}).waitFor();
    if(!(await page.locator('main').innerText()).includes('All-in total: $12.00 USD'))throw Error('Native quote mismatch');
    if(!(await page.locator('main').innerText()).includes('Synthetic buyer return policy displayed for review.'))throw Error('Assigned buyer return terms missing');
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('Mobile review overflows');
    await page.screenshot({path:path.join(root,'.runtime/listing-review-mobile.png'),fullPage:true});
    const approved=page.getByRole('checkbox');if(await approved.isChecked())throw Error('Consent was preselected');
    await page.setViewportSize({width:1280,height:900});await page.screenshot({path:path.join(root,'.runtime/listing-review-desktop.png'),fullPage:true});
    await approved.check();await page.getByRole('button',{name:'Continue to secure payment'}).click();
    await page.getByRole('heading',{name:'Native WooCommerce checkout'}).waitFor();
    if(await page.locator('form.checkout').count()!==1){fs.writeFileSync(path.join(root,'.runtime/listing-native-error.html'),await page.content());throw Error('Native WooCommerce checkout form absent: '+await page.locator('body').innerText());}
    if(!(await page.locator('body').innerText()).includes('Synthetic listing browser test'))throw Error('Selected native cart line missing');
    const cookie=(await page.context().cookies()).find(c=>c.name==='krev_agent_session');
    if(!cookie?.httpOnly||cookie.sameSite!=='Strict')throw Error('Private session cookie unprotected');
    await page.screenshot({path:path.join(root,'.runtime/listing-native-checkout.png'),fullPage:true});
    await page.goto(fixture.status_url);await page.getByText('Payment: not_started',{exact:true}).waitFor();
    if(failures.length)throw Error(failures.join('\n'));
    console.log('PASS: private fragment exchange, estimate/final review, unselected buyer consent, protected cookie, mobile/desktop layout, native WC checkout form and unpaid status. No Pay action or external request.');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
