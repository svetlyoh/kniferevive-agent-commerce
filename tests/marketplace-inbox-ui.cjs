// Native review template/production script under a controlled browser DOM.
const fs = require('node:fs'), path = require('node:path'), assert = require('node:assert/strict');
const {JSDOM} = require(path.join(process.argv[2], 'jsdom'));
const base = path.join(__dirname, '../wordpress/kniferevive-listlab-import');
const html = fs.readFileSync(path.join(base, 'templates/review.php'), 'utf8').replace(/<\?php[\s\S]*?\?>/g, '').replace(/<script[\s\S]*?<\/script>/g, '');
const script = fs.readFileSync(path.join(base, 'assets/review.js'), 'utf8');
const tick = () => new Promise(r => setTimeout(r, 25));
let checks = 0;
function check(value, label) { assert.ok(value, label); checks++; }
async function setup(search, ready, handler, hash = '') {
  const dom = new JSDOM(html, {url:'https://kniferevive.com/' + search + hash, runScripts:'outside-only'});
  const w = dom.window; w.KREVImport = {root:'https://kniferevive.com/wp-json/kniferevive-listlab-import/v1/', nonce:ready ? 'native-nonce' : '', sellerReady:ready};
  const calls = [];
  w.fetch = async (url, options) => { const call = {action:url.split('/v1/')[1], ...options, input:options.body ? JSON.parse(options.body) : null}; calls.push(call); const data = handler(call); return {ok:true,json:async()=>data}; };
  w.eval(script); await tick(); return {w,calls,dom};
}
(async () => {
  const data = {items:[{id:10,state:'queued',title:'<img onerror=bad()> Item',source_price:'25.00',photo_counts:{found:8,submitted:8,copied:null,gallery_verified:true},preserved_photos:8,warnings:[],review_url:'https://kniferevive.com/?krev_listlab_import=review&inbox=1&inbox_id=10'}],page:1,pages:2,total:21,connected:false};
  const a = await setup('?krev_listlab_import=review&inbox=1',true,c => c.action.startsWith('inbox?') ? data : c.action === 'inbox/connection' ? {connected:true,preserve_photos:true,inbox_key:'7.'+'a'.repeat(64),inbox_url:'https://kniferevive.com/?krev_listlab_import=review&inbox=1'} : {archived:true});
  const $ = id => a.w.document.getElementById(id);
  check(!$('krev-inbox').hidden && $('krev-import-form').hidden, 'generic import opens queue, not empty one-item form');
  check(a.calls[0].method === 'GET' && a.calls[0].headers['X-WP-Nonce'] === 'native-nonce' && !a.calls[0].body, 'queue reads use native nonce without token in GET');
  check(!$('krev-inbox-items').querySelector('img') && $('krev-inbox-items').textContent.includes('<img onerror=bad()>'), 'untrusted titles rendered as text');
  check($('krev-inbox-next').disabled === false && $('krev-inbox-prev').disabled === true, 'bounded page controls');
  $('krev-inbox-next').click(); await tick(); check(a.calls.at(-1).action.includes('page=2'), 'next page retrieves second oldest group');
  $('krev-inbox-items').querySelector('button').click(); await tick(); check(a.calls.some(c => c.method === 'PATCH' && c.input.archived === true), 'archive stays separate from product publication');
  check($('krev-inbox-key-box').hidden, 'delivery key absent from initial queue view');
  check($('krev-inbox-authorize').checked && !a.calls.some(c => c.action === 'inbox/connection'), 'Allow Muse defaults to yes without automatically creating a connection');
  $('krev-inbox-authorize').checked = true; $('krev-inbox-preserve').checked = true;
  $('krev-inbox-connect').dispatchEvent(new a.w.Event('submit',{bubbles:true,cancelable:true})); await tick();
  check(a.calls.some(c => c.action === 'inbox/connection' && c.input.authorize_queue && c.input.preserve_photos), 'seller consent controls photo preservation');
  check(!$('krev-inbox-key-box').hidden && $('krev-inbox-key').value.includes('7.'+'a'.repeat(64)), 'once-shown setup prompt supplies destination privately');
  data.items = []; data.total = 0;
  $('krev-inbox-refresh').click(); await tick();
  check($('krev-inbox-items').textContent.includes('Opened imports move to Drafts / review'), 'empty Waiting view explains where retained drafts go');
  a.dom.window.close();
  const b = await setup('?krev_listlab_import=review&inbox=1',false,()=>{throw new Error('unexpected request');});
  check(b.calls.length === 0 && !b.w.document.getElementById('krev-import-login').hidden, 'signed-out queue requests seller login before retrieval'); b.dom.window.close();
  const review = {seller_ready:true,data:{source_price:'25.00',source_url:'https://www.facebook.com/marketplace/item/123/',image_urls:['https://source.invalid/photo.jpg'],listing:{title:'Test item',description:'Item facts',category_id:1,quantity:1,attributes:{}}},price:{source_price:'25.00',regular_price:'30.00'},schema:{categories:[{id:1,name:'Knives'}]},photo_counts:{found:1,submitted:1,copied:null,gallery_verified:true},preview_photos:['https://kniferevive.com/photo.jpg']};
  const c = await setup('?krev_listlab_import=review&inbox=1&inbox_id=10',true,call => call.action === 'status' ? review : {listing:{id:50,title:'Test item',status:'draft',regular_price:'30.00',edit_url:'https://kniferevive.com/my-account/listlab/?action=edit&product_id=50'},photo_counts:{found:1,submitted:1,copied:1,gallery_verified:true}});
  check(c.calls[0].input.inbox_id === '10' && !('token' in c.calls[0].input), 'owner review uses inbox ID and no bearer token');
  check(c.w.document.querySelector('#krev-import-photos img').src === review.preview_photos[0], 'review prefers preserved native image');
  c.w.document.getElementById('krev-import-rights').checked = true;
  c.w.document.getElementById('krev-import-form').dispatchEvent(new c.w.Event('submit',{cancelable:true})); await tick();
  check(c.calls.at(-1).input.expected_price === '30.00' && c.calls.at(-1).input.authorized_to_list, 'claim preserves native price and rights contract');
  check(c.w.document.getElementById('krev-import-result').textContent.includes('Verified source price: $25.00'), 'original source price remains available for completion markup'); c.dom.window.close();
  const token = 'b'.repeat(64);
  const d = await setup('?krev_listlab_import=review',true,()=>review,'#import='+token);
  check(d.calls[0].input.token === token && d.w.location.hash === '' && d.w.sessionStorage.getItem('krev-marketplace-import-token') === token, 'legacy same-tab handoff remains supported'); d.dom.window.close();
  console.log(`${checks} marketplace inbox DOM assertions passed.`);
})().catch(e => {console.error(e);process.exit(1);});
