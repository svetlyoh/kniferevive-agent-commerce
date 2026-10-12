// DOM tests only; real devices, native clipboard and layout still require browser QA.
const fs=require('fs'),path=require('path'),assert=require('assert');
const {JSDOM}=require(process.argv[2]||'jsdom');
const html=fs.readFileSync(process.argv[3],'utf8'),ctx=JSON.parse(fs.readFileSync(process.argv[4],'utf8'));
const source=fs.readFileSync(path.join(__dirname,'../assets/js/order-messenger.js'),'utf8');
let checks=0;
const check=(ok,label)=>{assert.ok(ok,label);checks++;};
async function drain(){for(let i=0;i<8;i++)await new Promise(r=>setImmediate(r));}
function setup({canSend=true,sendResult={status:'accepted',message:'Ping sent ✨',detail:'Your message was handed off for email delivery.',retry_safe:false},network=false}={}){
  const dom=new JSDOM(html,{url:'http://127.0.0.1:8088/my-account/seller-orders/'+ctx.order_id+'/',runScripts:'outside-only'}),w=dom.window,d=w.document,calls=[],revoked=[];
  w.HTMLDialogElement.prototype.showModal=function(){this.setAttribute('open','');};w.HTMLDialogElement.prototype.close=function(){this.removeAttribute('open');};
  w.scrollTo=(x,y)=>calls.push({scroll:[x,y]});w.URL.createObjectURL=()=> 'blob:qa-'+Math.random();w.URL.revokeObjectURL=url=>revoked.push(url);
  w.KROrderMessenger={api:'http://127.0.0.1:8088/wp-json/kniferevive/v1/order-messages/',nonce:'test-nonce'};
  let activeSend=sendResult;
  w.fetch=async(url,opts)=>{
    calls.push({url,...opts});let data;
    if(url.endsWith('/context'))data={...ctx,can_send:canSend,disabled_reason:'No valid recipient'};
    else if(url.endsWith('/history'))data={attempts:[]};
    else if(url.endsWith('/preview'))data={html:'<html><body><h1>Same server renderer</h1></body></html>',recipient:ctx.recipient,subject:ctx.subject,attachment_count:opts.body.getAll('screenshots[]').length};
    else if(url.includes('/status?'))data=activeSend;
    else if(url.endsWith('/send')){if(network)throw new Error('Connection failed');data=activeSend;}
    return {ok:true,json:async()=>data};
  };
  w.eval(source);return{dom,w,d,calls,revoked,setOutcome:value=>activeSend=value};
}
function paste(s,files,text=''){
  const ev=new s.w.Event('paste',{bubbles:true,cancelable:true});
  Object.defineProperty(ev,'clipboardData',{value:{items:files.map(file=>({kind:'file',type:file.type,getAsFile:()=>file})),getData:()=>text}});
  s.d.querySelector('#kr-om-message').dispatchEvent(ev);return ev;
}
(async()=>{
  let s=setup();const $=q=>s.d.querySelector(q);
  check($('.krev-page-header .kr-om-root')!==null,'plugin positioned in observed action header');
  check(!$('.kr-om-dialog').open,'composer initially closed');
  $('.kr-om-launch').click();await drain();
  check($('.kr-om-dialog').open,'Ping Buyer opens in-page dialog');
  check(s.w.location.pathname.endsWith('/'+ctx.order_id+'/'),'open preserves order URL');
  check(s.w.history.state.krOrderMessenger===String(ctx.order_id),'composer owns one same-URL history entry');
  check($('[data-kr-om-to]').textContent===ctx.recipient,'authorized recipient shown');
  check($('[data-kr-om-reply]').textContent===ctx.reply_to,'registered seller reply address shown');
  check($('#kr-om-subject').readOnly,'subject cannot be edited');
  check(s.d.querySelector('input[name=recipient]')===null,'no recipient-editing input');
  check(s.d.activeElement===$('#kr-om-message'),'focus enters message editor');
  $('#kr-om-message').value='Draft update';
  const png=new s.w.File([new Uint8Array([1,2,3])],'pasted.png',{type:'image/png'});
  let ev=paste(s,[png]);check(ev.defaultPrevented,'image-only paste handled');
  check(s.d.querySelectorAll('[data-kr-om-shots] img').length===1,'clipboard image thumbnail appears');
  ev=paste(s,[png],'normal text');check(!ev.defaultPrevented,'mixed text paste preserves default text behavior');
  check(s.d.querySelectorAll('[data-kr-om-shots] img').length===2,'mixed clipboard image retained');
  $('[data-kr-om-remove]').click();check(s.d.querySelectorAll('[data-kr-om-shots] img').length===1 && s.revoked.length===1,'remove revokes thumbnail URL');
  paste(s,[new s.w.File(['svg'],'x.svg',{type:'image/svg+xml'})]);check(s.d.querySelectorAll('[data-kr-om-shots] img').length===1,'unsupported pasted image rejected');
  Object.defineProperty($('#kr-om-files'),'files',{value:[new s.w.File([new Uint8Array([1,2])],'selected.jpg',{type:'image/jpeg'})],configurable:true});$('#kr-om-files').dispatchEvent(new s.w.Event('change'));
  check(s.d.querySelectorAll('[data-kr-om-shots] img').length===2,'JPEG file picker image retained');
  $('[data-kr-om-preview]').click();await drain();
  check(!$('.kr-om-preview').hidden && $('.kr-om-editor').hidden,'server-rendered preview opens');
  check($('.kr-om-preview iframe').srcdoc.includes('Same server renderer'),'preview uses response HTML');
  check($('.kr-om-preview iframe').getAttribute('sandbox')==='','preview scripts isolated');
  check(s.calls.filter(c=>c.url?.endsWith('/send')).length===0,'preview never calls send');
  check(s.d.querySelectorAll('[data-kr-om-preview-shots] img').length===2,'preview shows all attachment thumbnails');
  $('[data-kr-om-edit]').click();check($('#kr-om-message').value==='Draft update' && s.d.querySelectorAll('[data-kr-om-shots] img').length===2,'preview-back preserves exact text/images');
  $('[data-kr-om-close]').click();check(!$('.kr-om-discard').hidden,'closing a draft asks keep/discard');
  $('[data-kr-om-keep]').click();check($('.kr-om-discard').hidden && $('.kr-om-dialog').open && $('#kr-om-message').value==='Draft update','Keep Editing preserves draft');
  s.w.dispatchEvent(new s.w.PopStateEvent('popstate'));check(!$('.kr-om-discard').hidden && $('.kr-om-dialog').open,'browser Back cannot silently discard draft');$('[data-kr-om-keep]').click();
  $('[data-kr-om-send]').click();$('[data-kr-om-send]').click();await drain();
  const sends=s.calls.filter(c=>c.url?.endsWith('/send'));
  check(sends.length===1,'double-click sends only one request');
  check(sends[0].headers['X-WP-Nonce']==='test-nonce','REST nonce included');
  check(sends[0].body.getAll('screenshots[]').length===2,'all images submitted as multipart files');
  check(sends[0].body.get('attachment_count')==='2','expected image count prevents silently dropped uploads');
  check(/^[a-f0-9-]{36}$/.test(sends[0].body.get('request_id')),'request UUID included');
  check(!sends[0].body.has('recipient') && !sends[0].body.has('seller_id'),'client does not supply authoritative addresses/ownership');
  check($('.kr-om-status').textContent.includes('handed off for email delivery'),'success wording does not claim receipt');
  check($('#kr-om-message').value==='' && s.d.querySelectorAll('[data-kr-om-shots] img').length===0,'accepted draft and private thumbnails cleared');
  check($('[data-kr-om-send]').hidden,'accepted draft cannot be resent');
  $('[data-kr-om-close]').click();check(!$('.kr-om-dialog').open && s.d.body.style.overflow==='','Back to Order closes and restores scrolling');
  s.dom.window.close();
  s=setup({sendResult:{status:'failed',message:"That ping didn't go through. Try again.",retry_safe:true}});s.d.querySelector('.kr-om-launch').click();await drain();s.d.querySelector('#kr-om-message').value='Keep my retry draft';paste(s,[new s.w.File(['x'],'x.png',{type:'image/png'})]);s.d.querySelector('[data-kr-om-send]').click();await drain();
  check(s.d.querySelector('#kr-om-message').value==='Keep my retry draft' && s.d.querySelectorAll('[data-kr-om-shots] img').length===1,'known failure preserves text/screenshots');
  check(!s.d.querySelector('[data-kr-om-send]').disabled,'explicit retry allowed after known failure');
  const oldUuid=s.calls.find(c=>c.url?.endsWith('/send')).body.get('request_id');s.d.querySelector('[data-kr-om-send]').click();await drain();const lastUuid=s.calls.filter(c=>c.url?.endsWith('/send')).at(-1).body.get('request_id');check(oldUuid!==lastUuid,'explicit retry uses new UUID');s.dom.window.close();
  s=setup({network:true});s.d.querySelector('.kr-om-launch').click();await drain();s.d.querySelector('#kr-om-message').value='Uncertain draft';s.d.querySelector('[data-kr-om-send]').click();await drain();
  check(s.d.querySelector('[data-kr-om-send]').disabled && !s.d.querySelector('[data-kr-om-check]').hidden,'network uncertainty blocks resend and offers status');
  check(s.calls.filter(c=>c.url?.endsWith('/send')).length===1,'no automatic network retry');
  s.setOutcome({status:'accepted',message:'Ping sent ✨',retry_safe:false});s.d.querySelector('[data-kr-om-check]').click();await drain();check(s.calls.some(c=>c.url?.includes('/status?')) && s.calls.filter(c=>c.url?.endsWith('/send')).length===1,'status recovery reads state without sending');s.dom.window.close();
  s=setup({canSend:false});s.d.querySelector('.kr-om-launch').click();await drain();check(s.d.querySelector('[data-kr-om-send]').disabled && s.d.querySelector('.kr-om-status').textContent==='No valid recipient','invalid buyer disables sending');s.dom.window.close();
  console.log(`PASS: ${checks} composer/clipboard/navigation DOM checks. No browser or network.`);
})().catch(e=>{console.error(e);process.exitCode=1;});
