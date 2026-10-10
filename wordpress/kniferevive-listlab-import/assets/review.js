/* global KREVImport */
(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  const storageKey = 'krev-marketplace-import-token';
  const fragment = new URLSearchParams(location.hash.slice(1));
  let token = fragment.get('import');
  if (token && /^[a-f0-9]{64}$/.test(token)) {
    try { sessionStorage.setItem(storageKey, token); } catch (_) { /* current tab still works */ }
    history.replaceState(null, '', location.pathname + location.search);
  } else {
    try { token = sessionStorage.getItem(storageKey); } catch (_) { token = null; }
  }
  let state, preview;
  const message = text => { $('krev-import-message').textContent = text; };
  async function request(action, input) {
    const headers = { 'Content-Type': 'application/json' };
    if (KREVImport.nonce) headers['X-WP-Nonce'] = KREVImport.nonce;
    const response = await fetch(KREVImport.root + action, {method:'POST', credentials:'same-origin', headers, body:JSON.stringify({token, ...input})});
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'The import could not be completed.');
    return data;
  }
  function changes() {
    const values = {title:$('krev-import-title').value, description:$('krev-import-description').value, category_id:$('krev-import-category').value};
    if ($('krev-import-quantity').value !== '') values.quantity = $('krev-import-quantity').value;
    return values;
  }
  function showPrice(p) {
    $('krev-source-price').textContent = '$' + p.source_price;
    $('krev-target-price').textContent = '$' + p.regular_price;
  }
  function showSpecs(data) { $('krev-import-specs').textContent = JSON.stringify(data.listing.attributes || {}, null, 2); }
  function showResult(data) {
    $('krev-import-form').hidden = true;
    const box = $('krev-import-result'); box.replaceChildren(); box.hidden = false;
    const listing = data.listing;
    if (!listing || !listing.edit_url) { message('The original draft needs seller review in ListLab.'); return; }
    const p = document.createElement('p'); p.textContent = listing.title + ' — ' + listing.status + '. Item price: $' + listing.regular_price + ' + native shipping.'; box.append(p);
    const link = document.createElement('a'); link.className = 'button'; link.href = listing.edit_url; link.textContent = 'Complete listing in ListLab'; box.append(link);
    for (const warning of data.warnings || []) { const w = document.createElement('p'); w.textContent = warning; box.append(w); }
    message('Your original listing is ready to complete in ListLab.');
  }
  async function refreshPreview() {
    $('krev-import-create').disabled = true;
    preview = await request('preview', {changes:changes()}); showPrice(preview.price); showSpecs(preview.data);
    $('krev-import-create').disabled = !state.seller_ready;
  }
  async function load() {
    if (!token) { message('Open the private listing link returned by Muse after saying “List this on KnifeRevive.”'); return; }
    state = await request('status', {});
    if (state.listing) { showResult(state); return; }
    preview = {data:state.data, price:state.price, schema:state.schema};
    const listing = state.data.listing;
    const select = $('krev-import-category');
    for (const category of state.schema.categories) { const option = document.createElement('option'); option.value = category.id; option.textContent = category.name; select.append(option); }
    select.value = listing.category_id;
    $('krev-import-title').value = listing.title || '';
    $('krev-import-description').value = listing.description || '';
    $('krev-import-quantity').value = listing.quantity ?? '';
    $('krev-import-source').href = state.data.source_url;
    showPrice(state.price); showSpecs(state.data);
    for (const url of state.data.image_urls) { const img = document.createElement('img'); img.src = url; img.alt = 'Copied item photo'; img.referrerPolicy = 'no-referrer'; $('krev-import-photos').append(img); }
    $('krev-import-form').hidden = false;
    $('krev-import-login').hidden = state.seller_ready;
    $('krev-import-create').disabled = !state.seller_ready;
    message(state.seller_ready ? 'Check your item, then create a private seller draft.' : 'Sign in to an enabled seller account to finish. Your prepared item is retained in this tab.');
  }
  $('krev-import-category').addEventListener('change', () => refreshPreview().catch(error => message(error.message)));
  $('krev-import-refresh').addEventListener('click', () => refreshPreview().then(() => message('Price refreshed. Review it before creating the draft.')).catch(error => message(error.message)));
  $('krev-import-form').addEventListener('submit', async event => {
    event.preventDefault(); $('krev-import-create').disabled = true;
    message('Copying photos and creating your ListLab draft…');
    try {
      // Use the price last displayed. A merchant rule change returns 409 and requires a refreshed review.
      const result = await request('claim', {changes:changes(), expected_price:preview.price.regular_price, authorized_to_list:$('krev-import-rights').checked});
      showResult(result);
    } catch (error) { message(error.message); $('krev-import-create').disabled = false; }
  });
  load().catch(error => message(error.message));
})();
