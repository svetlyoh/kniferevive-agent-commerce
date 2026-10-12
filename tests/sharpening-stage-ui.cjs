const fs=require('fs'),assert=require('assert');
const {JSDOM,VirtualConsole}=require(process.argv[2]);
const html=fs.readFileSync(process.argv[3],'utf8');
const js=fs.readFileSync('wordpress-overlays/kniferevive-seller-orders/assets/js/seller-orders.js','utf8');
let checks=0;
const check=(value,label)=>{assert.ok(value,label);checks++;};
async function drain(){for(let i=0;i<6;i++)await new Promise(r=>setImmediate(r));}
async function setup(fail=false){
  const virtualConsole=new VirtualConsole();
  const dom=new JSDOM(html,{url:'https://example.invalid/my-account/sharpening-orders/100/',runScripts:'outside-only',virtualConsole});
  const w=dom.window,calls=[];
  w.KREVOrders={root:'https://example.invalid/wp-json/kniferevive/v1/',nonce:'synthetic-nonce'};
  w.fetch=async(url,options)=>{calls.push({url,...options});return {ok:!fail,json:async()=>fail?{message:'Only authorized operators can change stages.'}:{current_stage:JSON.parse(options.body).stage}};};
  w.eval(js);await drain();return {dom,w,d:w.document,calls};
}
(async()=>{
  let s=await setup();
  const buttons=[...s.d.querySelectorAll('[data-krev-stage]')];
  check(buttons.length===8,'actual native template exposes all eight buttons');
  for(const b of buttons){b.click();check(s.d.querySelector('.krev-stage-form').elements.stage.value===b.dataset.krevStage && b.getAttribute('aria-pressed')==='true','each icon selects its matching stage');}
  check(s.calls.length===0,'selecting icons alone never writes or emails');
  const form=s.d.querySelector('.krev-stage-form');
  form.elements.handoff.value='Keep synthetic pickup details';form.elements.internal_note.value='Synthetic note';
  form.elements.stage.value='blade-check';form.elements.stage.dispatchEvent(new s.w.Event('change'));
  check(s.d.querySelector('[data-krev-stage="blade-check"]').classList.contains('is-selected'),'dropdown and icon selection remain synchronized');
  const save=s.d.querySelector('[data-krev-stage-save]');save.click();save.click();await drain();
  check(s.calls.length===1,'double save cannot duplicate a stage request');
  const request=s.calls[0],body=JSON.parse(request.body);
  check(request.url.endsWith('/stage') && request.method==='POST' && request.headers['X-WP-Nonce']==='synthetic-nonce','selection save uses native nonce-protected POST route');
  check(body.stage==='blade-check' && body.handoff==='Keep synthetic pickup details' && body.internal_note==='Synthetic note','selected stage preserves entered handoff and note');
  check(body.complete===undefined,'icon save never completes the order automatically');s.dom.window.close();
  s=await setup(true);s.d.querySelector('[data-krev-stage="hone-test"]').click();s.d.querySelector('[data-krev-stage-save]').click();await drain();
  check(s.d.querySelector('.krev-stage-feedback').textContent.includes('authorized operators'),'server error is visible in the live status region');
  check(!s.d.querySelector('[data-krev-stage-save]').disabled && !s.d.querySelector('[data-krev-stage="hone-test"]').disabled,'failed save restores editable controls');
  check(s.d.querySelector('.krev-stage-form').elements.stage.value==='hone-test','failed save preserves selection');s.dom.window.close();
  console.log(`PASS: ${checks} stage selection/save DOM checks; no network or email.`);
})().catch(e=>{console.error(e);process.exitCode=1;});
