/* Optional custom campaign events. Never loads a pixel or sends PageView/Purchase. */
(() => {
 'use strict';
 const config=window.krCampaign||{},root=document.querySelector('[data-kr-campaign-page]');if(!root||config.enabled!==true)return;
 const allowed=new Set(['browse_click','seller_signup_click','dispatch_explore_click','featured_product_click','landing_page_view']);const sent=new Set();
 const consent=()=>{try{if(navigator.globalPrivacyControl)return false;if(typeof window.wp_has_consent==='function')return window.wp_has_consent('marketing')===true;return document.cookie.split('; ').includes('kr_meta_marketing=yes');}catch{return false;}};
 const record=(name,slot='')=>{
  if(!allowed.has(name)||!consent()||typeof window.fbq!=='function')return;
  if(!['','wusthof','fire-tv','cutco'].includes(slot))slot='';
  const key=name+':'+slot;if(sent.has(key))return;sent.add(key);
  try{window.fbq('trackCustom',name,{campaign:'next_find_2026',campaign_content:['marketplace','dispatch'].includes(config.content)?config.content:'other',...(slot?{item_context:slot}:{})});}catch{}
 };
 record('landing_page_view');
 root.addEventListener('click',event=>{const a=event.target.closest('a[data-kr-campaign-event]');if(a&&root.contains(a))record(a.dataset.krCampaignEvent,a.dataset.krCampaignSlot||'');});
})();
