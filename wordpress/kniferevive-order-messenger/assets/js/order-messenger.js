(() => {
  'use strict';
  const root=document.querySelector('.kr-om-root');
  if(!root || typeof KROrderMessenger==='undefined') return;
  const $=s=>root.querySelector(s),dialog=$('.kr-om-dialog'),launch=$('.kr-om-launch'),editor=$('.kr-om-editor'),textarea=$('#kr-om-message'),picker=$('#kr-om-files'),status=$('.kr-om-status'),send=$('[data-kr-om-send]'),preview=$('[data-kr-om-preview]'),previewPane=$('.kr-om-preview'),edit=$('[data-kr-om-edit]'),check=$('[data-kr-om-check]'),discard=$('.kr-om-discard');
  const order=root.dataset.orderId,config=KROrderMessenger;
  let context=null,files=[],requestId=null,uncertain=false,sending=false,accepted=false,historyEntry=false,suppressPop=false,savedScroll=[0,0],savedOverflow='',previousFocus=null;
  const actionArea=document.querySelector(`.krev-order-detail[data-order-id="${order}"] .krev-page-header, .krev-sharpening-detail[data-order-id="${order}"] .krev-page-header`);
  if(actionArea) actionArea.append(root);
  // The plugin root keeps all styles scoped when positioned in the existing action header.
  const announce=(text,error=false)=>{status.textContent=text;status.classList.toggle('kr-om-error',error);};
  const uuid=()=>crypto.randomUUID ? crypto.randomUUID() : '10000000-1000-4000-8000-100000000000'.replace(/[018]/g,c=>(+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c/4).toString(16));
  function el(tag,text,className) {const e=document.createElement(tag);if(text)e.textContent=text;if(className)e.className=className;return e;}
  async function api(suffix,method='GET',body) {
    let response;
    try { response=await fetch(config.api+order+'/'+suffix,{method,credentials:'same-origin',cache:'no-store',headers:{'X-WP-Nonce':config.nonce},body}); }
    catch(e) {const error=new Error('Connection interrupted. Delivery status is uncertain; check before sending again.');error.network=true;throw error;}
    let data;try{data=await response.json();}catch(e){const error=new Error('The server response could not be read. Check status before resending.');error.network=true;throw error;}
    if(!response.ok && !data.status) {const error=new Error(data.message || 'This action could not be completed.');error.code=data.code;throw error;}
    return data;
  }
  function formData(withId=false) {const data=new FormData();data.append('message',textarea.value);data.append('attachment_count',String(files.length));if(withId)data.append('request_id',requestId);files.forEach((f,i)=>data.append('screenshots[]',f.file,`screenshot-${i+1}.${f.file.type==='image/png'?'png':'jpg'}`));return data;}
  function controls() {
    const locked=sending||uncertain||accepted;
    textarea.disabled=locked;picker.disabled=locked || !context || !context.attachments.count;
    send.disabled=locked || !context || !context.can_send;preview.disabled=sending||!context;send.hidden=accepted;
    send.textContent=sending?'Sending…':'Send Update';check.hidden=!uncertain;
    $('.kr-om-upload').setAttribute('aria-disabled',picker.disabled?'true':'false');
    root.querySelectorAll('[data-kr-om-remove]').forEach(b=>b.disabled=locked);
  }
  function gallery(target,removable) {
    target.replaceChildren();files.forEach((f,i)=>{const figure=el('figure','','kr-om-shot'),img=el('img');img.src=f.url;img.alt='Screenshot '+(i+1);figure.append(img,el('figcaption',`Screenshot ${i+1} · ${f.file.type==='image/png'?'PNG':'JPEG'} · ${(f.file.size/1048576).toFixed(2)} MB`));
      if(removable){const button=el('button','Remove');button.type='button';button.dataset.krOmRemove='';button.disabled=sending||uncertain||accepted;button.addEventListener('click',()=>{if(sending||uncertain||accepted)return;URL.revokeObjectURL(files[i].url);files.splice(i,1);gallery($('[data-kr-om-shots]'),true);});figure.append(button);}target.append(figure);
    });
  }
  function addFiles(incoming) {
    if(!context || sending||uncertain||accepted)return;
    const limits=context.attachments;
    for(const file of incoming){
      if(!['image/png','image/jpeg'].includes(file.type)){announce('Use PNG or JPEG screenshots.',true);continue;}
      if(files.length>=limits.count){announce(`You can add up to ${limits.count} screenshots.`,true);continue;}
      if(file.size>limits.file_bytes || files.reduce((n,f)=>n+f.file.size,0)+file.size>limits.total_bytes){announce('Screenshots exceed the allowed size. Choose a smaller image.',true);continue;}
      files.push({file,url:URL.createObjectURL(file)});
    }
    gallery($('[data-kr-om-shots]'),true);
  }
  picker.addEventListener('change',()=>{addFiles(Array.from(picker.files));picker.value='';});
  $('.kr-om-upload').addEventListener('keydown',event=>{
    if(event.key==='Enter' || event.key===' '){event.preventDefault();if(!picker.disabled)picker.click();}
  });
  dialog.addEventListener('paste',event=>{
    if(sending||uncertain||accepted)return;
    const images=Array.from(event.clipboardData?.items||[]).filter(i=>i.kind==='file' && i.type.startsWith('image/')).map(i=>i.getAsFile()).filter(Boolean);
    if(images.length){if(!event.clipboardData.getData('text/plain'))event.preventDefault();addFiles(images);}
  });
  async function history() {try{const data=await api('history');const area=$('[data-kr-om-history]');area.replaceChildren();if(!data.attempts.length){area.append(el('p','No displayed attempts yet.'));return;}const list=el('ul');data.attempts.forEach(a=>list.append(el('li',`${a.created_at_gmt} UTC · ${a.status} · ${a.attachment_count} image(s)`)));area.append(list);}catch(e){$('[data-kr-om-history]').textContent='History could not be loaded.';}}
  function viewport() {
    dialog.style.setProperty('--kr-om-vh',(window.visualViewport?.height || window.innerHeight)+'px');
    dialog.style.setProperty('--kr-om-top',(window.visualViewport?.offsetTop || 0)+'px');
  }
  window.visualViewport?.addEventListener('resize',viewport);window.visualViewport?.addEventListener('scroll',viewport);window.addEventListener('resize',viewport);
  async function open() {
    if(dialog.open)return;previousFocus=document.activeElement;savedScroll=[window.scrollX,window.scrollY];savedOverflow=document.body.style.overflow;document.body.style.overflow='hidden';
    viewport();dialog.showModal();historyEntry=true;window.history.pushState({...window.history.state,krOrderMessenger:order},'',window.location.href);announce('Loading order details…');controls();
    try{context=await api('context');if(!dialog.open)return;
      $('[data-kr-om-to]').textContent=context.recipient || 'No valid email';$('[data-kr-om-reply]').textContent=context.reply_to || 'Unavailable';$('[data-kr-om-number]').textContent='#'+context.order_id+(String(context.order_id)!==context.order_number?' · '+context.order_number:'');$('[data-kr-om-placed]').textContent=context.placed+' ('+context.timezone+')';$('[data-kr-om-store]').textContent=context.store_name;
      $('[data-kr-om-heading-order]').textContent='Order #'+context.order_number;
      const items=$('[data-kr-om-items]');items.replaceChildren();context.items.forEach(i=>items.append(el('li',`${i.name} — Qty ${i.quantity}`)));$('#kr-om-subject').value=context.subject;
      const logo=$('[data-kr-om-logo]');if(context.logo){logo.src=context.logo;logo.hidden=false;}
      const l=context.attachments;$('[data-kr-om-limits]').textContent=`PNG or JPEG · up to ${l.count} screenshots · ${(l.file_bytes/1048576).toFixed(1)} MB each · ${(l.total_bytes/1048576).toFixed(1)} MB total. Images arrive as attachments.`;
      announce(context.can_send?'':context.disabled_reason,!context.can_send);controls();textarea.focus();history();
    }catch(e){announce(e.message,true);controls();}
  }
  function dirty(){return !accepted && (textarea.value.trim()!=='' || files.length>0);}
  function reset(){files.forEach(f=>URL.revokeObjectURL(f.url));files=[];textarea.value='';requestId=null;uncertain=false;sending=false;accepted=false;gallery($('[data-kr-om-shots]'),true);previewPane.hidden=true;editor.hidden=false;edit.hidden=true;preview.hidden=false;discard.hidden=true;$('.kr-om-details').open=false;}
  function close(skipHistory=false){dialog.close();document.body.style.overflow=savedOverflow;window.scrollTo(...savedScroll);previousFocus?.focus();if(historyEntry && !skipHistory){suppressPop=true;window.history.back();}historyEntry=false;reset();}
  function requestClose(fromPop=false){
    if(sending){if(fromPop){window.history.pushState({...window.history.state,krOrderMessenger:order},'',window.location.href);historyEntry=true;}announce('Wait for the send result before closing.');return;}
    if(dirty()){if(fromPop){window.history.pushState({...window.history.state,krOrderMessenger:order},'',window.location.href);historyEntry=true;}discard.hidden=false;$('.kr-om-scroll').inert=true;$('.kr-om-footer').inert=true;$('.kr-om-header').inert=true;$('[data-kr-om-keep]').focus();return;}
    close(fromPop);
  }
  root.querySelectorAll('[data-kr-om-close]').forEach(b=>b.addEventListener('click',()=>requestClose()));
  $('[data-kr-om-keep]').addEventListener('click',()=>{discard.hidden=true;$('.kr-om-scroll').inert=false;$('.kr-om-footer').inert=false;$('.kr-om-header').inert=false;textarea.focus();});
  $('[data-kr-om-discard]').addEventListener('click',()=>{$('.kr-om-scroll').inert=false;$('.kr-om-footer').inert=false;$('.kr-om-header').inert=false;close();});
  dialog.addEventListener('cancel',e=>{e.preventDefault();if(!discard.hidden){$('[data-kr-om-keep]').click();}else requestClose();});
  discard.addEventListener('keydown',e=>{if(e.key==='Tab'){e.preventDefault();const keep=$('[data-kr-om-keep]'),drop=$('[data-kr-om-discard]');(document.activeElement===keep?drop:keep).focus();}});
  window.addEventListener('popstate',()=>{if(suppressPop){suppressPop=false;return;}if(dialog.open){historyEntry=false;requestClose(true);}});
  window.addEventListener('beforeunload',e=>{if(dialog.open && (dirty()||sending)){e.preventDefault();e.returnValue='';}});
  launch.addEventListener('click',open);
  preview.addEventListener('click',async()=>{
    if(!textarea.value.trim()){announce('Drop your update here first.',true);textarea.focus();return;}
    preview.disabled=true;
    try{const data=await api('preview','POST',formData());$('.kr-om-preview iframe').srcdoc=data.html;$('[data-kr-om-preview-summary]').textContent=`To: ${data.recipient} · Subject: ${data.subject}`;gallery($('[data-kr-om-preview-shots]'),false);editor.hidden=true;previewPane.hidden=false;preview.hidden=true;edit.hidden=false;edit.focus();}
    catch(e){announce(e.message,true);}finally{controls();}
  });
  edit.addEventListener('click',()=>{previewPane.hidden=true;editor.hidden=false;preview.hidden=false;edit.hidden=true;textarea.focus();});
  function outcome(data){
    sending=false;announce(data.message+(data.detail?' '+data.detail:''),data.status!=='accepted');
    if(data.status==='accepted'){accepted=true;uncertain=false;files.forEach(f=>URL.revokeObjectURL(f.url));files=[];textarea.value='';gallery($('[data-kr-om-shots]'),true);gallery($('[data-kr-om-preview-shots]'),false);previewPane.hidden=true;editor.hidden=false;edit.hidden=true;preview.hidden=false;}
    else if(data.retry_safe){uncertain=false;requestId=null;}
    else uncertain=true;
    controls();history();
  }
  send.addEventListener('click',async()=>{
    if(sending||uncertain||accepted||!context?.can_send)return;
    if(!textarea.value.trim()){announce('Drop your update here first.',true);textarea.focus();return;}
    requestId=requestId || uuid();sending=true;announce('Sending…');controls();
    try{outcome(await api('send','POST',formData(true)));}
    catch(e){sending=false;if(e.network){uncertain=true;announce(e.message,true);}else{requestId=null;announce(e.message,true);}controls();}
  });
  check.addEventListener('click',async()=>{if(!requestId)return;check.disabled=true;try{outcome(await api('status?request_id='+encodeURIComponent(requestId)));}catch(e){announce(e.message,true);}finally{check.disabled=false;}});
})();
