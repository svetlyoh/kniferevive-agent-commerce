/* Progressive enhancement. No third-party SDK, automatic external requests, messages or payments. */
(() => {
  'use strict';
  const roots=new Set(document.querySelectorAll('.krc-contact'));
  const dialog=document.getElementById('krc-dialog');
  let trigger=null, activeRoot=null;
  const config=window.krcContact||{};
  const permitted=()=>{try{return config.analytics===true && !navigator.globalPrivacyControl && typeof window.wp_has_consent==='function' && window.wp_has_consent('statistics')===true;}catch{return false;}};
  const today=()=>new Date().toISOString().slice(0,10);
  let memorySession='';
  const session=()=>{
    if(memorySession)return memorySession;
    try {
      const key='krc-statistics-session';const saved=JSON.parse(sessionStorage.getItem(key)||'null');
      if(saved&&saved.day===today()&&/^[a-f0-9]{32}$/.test(saved.token))return memorySession=saved.token;
      const bytes=new Uint8Array(16);crypto.getRandomValues(bytes);
      memorySession=Array.from(bytes,b=>b.toString(16).padStart(2,'0')).join('');
      sessionStorage.setItem(key,JSON.stringify({day:today(),token:memorySession}));return memorySession;
    }catch{return '';}
  };
  const sent=new Set();
  const emit=(root,event,channel='')=>{
    if(!root||!permitted())return;
    const dims={event,placement:root.dataset.krcPlacement,product_id:Number(root.dataset.krcProduct||0),seller_id:Number(root.dataset.krcSeller||0),channel,device:matchMedia('(max-width:767px)').matches?'mobile':'desktop',session:session()};
    if(!dims.session)return;
    const key=[event,dims.placement,dims.product_id,dims.seller_id,channel].join('|');if(sent.has(key))return;sent.add(key);
    // A failed request never holds up navigation; no href, phone, username or question is recorded.
    try { fetch(config.events,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(dims),keepalive:true}).catch(()=>{}); } catch {}
  };
  const impressions=new Map();
  const visible=new Set();
  let observer=null;
  const startMetrics=()=>{
    if(!permitted())return;
    roots.forEach(root=>emit(root,'seller_contact_eligible_view'));
    visible.forEach(root=>emit(root,'seller_contact_cta_impression'));
  };
  if('IntersectionObserver'in window){
    observer=new IntersectionObserver(entries=>entries.forEach(entry=>{
      const root=entry.target;
      if(!entry.isIntersecting||entry.intersectionRatio<0.5){clearTimeout(impressions.get(root));impressions.delete(root);visible.delete(root);return;}
      if(impressions.has(root))return;
      const timer=setTimeout(()=>{if(document.visibilityState==='hidden'||!root.getClientRects().length)return;for(let el=root;el;el=el.parentElement){const style=getComputedStyle(el);if(style.visibility==='hidden'||style.opacity==='0'||style.display==='none')return;}visible.add(root);emit(root,'seller_contact_cta_impression');},250);
      impressions.set(root,timer);
    }),{threshold:[0,0.5,1]});
    roots.forEach(root=>observer.observe(root));
  }
  startMetrics();document.addEventListener('wp_listen_for_consent_change',startMetrics);document.addEventListener('visibilitychange',startMetrics);
  const bindCopy=scope=>scope.querySelectorAll('.krc-copy').forEach(box=>{
    const button=box.querySelector('.krc-copy-button'),text=box.querySelector('textarea'),status=box.querySelector('.krc-copy-status');
    if(!button||button.dataset.krcBound)return;button.dataset.krcBound='1';button.hidden=false;
    button.addEventListener('click',async()=>{
      try {if(!navigator.clipboard?.writeText)throw new Error();await navigator.clipboard.writeText(text.value);status.textContent='Question copied. Paste it into your chat.';}
      catch{text.focus();text.select();status.textContent='Select and copy the question above.';}
    });
  });
  roots.forEach(bindCopy);
  // Lazy-loaded cards and carousel clones share delegated controls and deduplicated impressions.
  const register=root=>{
    if(roots.has(root))return;roots.add(root);bindCopy(root);
    const panel=root.querySelector('.krc-options'),summary=root.querySelector('.krc-chooser>summary');
    if(panel&&summary){const id='krc-dynamic-'+roots.size;panel.id=id;summary.setAttribute('aria-controls',id);}
    observer?.observe(root);emit(root,'seller_contact_eligible_view');
  };
  new MutationObserver(changes=>changes.forEach(change=>change.addedNodes.forEach(node=>{
    if(node.nodeType!==1)return;if(node.matches('.krc-contact'))register(node);node.querySelectorAll('.krc-contact').forEach(register);
  }))).observe(document.body,{childList:true,subtree:true});
  const safeChannel=anchor=>{
    try {const u=new URL(anchor.href);return u.protocol==='https:' && !u.username && !u.password && !u.port && ((u.hostname==='wa.me'&&/^\/[1-9][0-9]{7,14}$/.test(u.pathname))||(u.hostname==='t.me'&&/^\/[A-Za-z][A-Za-z0-9_]{4,31}$/.test(u.pathname)));}catch{return false;}
  };
  document.addEventListener('click',event=>{
    const anchor=event.target.closest('a[data-krc-channel]');if(!anchor)return;
    const root=anchor.closest('.krc-contact')||(dialog?.contains(anchor)?activeRoot:null);
    if(!root||!safeChannel(anchor))return;
    if(anchor.classList.contains('krc-trigger'))emit(root,'seller_contact_cta_click');
    emit(root,'seller_contact_channel_click',anchor.dataset.krcChannel);
    emit(root,'seller_contact_external_handoff',anchor.dataset.krcChannel);
  });
  if(!dialog||typeof dialog.showModal!=='function')return;
  const title=dialog.querySelector('#krc-dialog-title'),body=dialog.querySelector('.krc-dialog-body'),close=dialog.querySelector('.krc-close');
  document.addEventListener('click',event=>{
    const summary=event.target.closest('.krc-chooser>summary');if(!summary)return;
    const root=summary.closest('.krc-contact');if(!root)return;
      event.preventDefault();trigger=summary;activeRoot=root;
      title.textContent='Message '+root.dataset.krcStore;
      const copy=root.querySelector('.krc-options').cloneNode(true);copy.removeAttribute('id');copy.querySelectorAll('[data-krc-bound]').forEach(x=>x.removeAttribute('data-krc-bound'));
      body.replaceChildren(copy);bindCopy(body);
      document.body.classList.toggle('krc-dark',Boolean(root.closest('.kr-tech-theme,.kr-tech-page,.kr-tech-product')));
      dialog.showModal();close.focus();emit(root,'seller_contact_cta_click');emit(root,'seller_contact_chooser_open');
  });
  close.addEventListener('click',()=>dialog.close());
  dialog.addEventListener('keydown',event=>{
    if(event.key!=='Tab')return;
    const controls=[...dialog.querySelectorAll('a[href],button:not([disabled]),textarea:not([disabled]),input:not([disabled]),select:not([disabled]),[tabindex="0"]')].filter(el=>el.getClientRects().length&&!el.hidden);
    const first=controls[0],last=controls.at(-1);
    if(!first){event.preventDefault();close.focus();return;}
    if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
    else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
  });
  dialog.addEventListener('click',event=>{if(event.target===dialog){const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();}});
  dialog.addEventListener('close',()=>{document.body.classList.remove('krc-dark');body.replaceChildren();trigger?.focus();activeRoot=null;});
  // Native showModal handles inert background and Escape; Tab wrapping and focus return are explicit.
})();
