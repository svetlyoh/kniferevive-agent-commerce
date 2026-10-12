/* global KREVImport */
(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  const storageKey = 'krev-marketplace-import-token';
  const fragment = new URLSearchParams(location.hash.slice(1));
  const query = new URLSearchParams(location.search);
  const inboxId = /^[1-9]\d{0,18}$/.test(query.get('inbox_id') || '') ? query.get('inbox_id') : null;
  let token = fragment.get('import');
  if (token && /^[a-f0-9]{64}$/.test(token)) {
    try { sessionStorage.setItem(storageKey, token); } catch (_) { /* current tab still works */ }
    history.replaceState(null, '', location.pathname + location.search);
  } else if (!query.has('inbox') && !inboxId) {
    try { token = sessionStorage.getItem(storageKey); } catch (_) { token = null; }
  }
  let state, preview;
  const message = text => { $('krev-import-message').textContent = text; };
  async function request(action, input = {}, method = 'POST') {
    const headers = { 'Content-Type': 'application/json' };
    if (KREVImport.nonce) headers['X-WP-Nonce'] = KREVImport.nonce;
    const body = method === 'GET' ? undefined : JSON.stringify({...(inboxId ? {inbox_id:inboxId} : {token}), ...input});
    const response = await fetch(KREVImport.root + action, {method, credentials:'same-origin', headers, body});
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'The import could not be completed.');
    return data;
  }
  let inboxTab = 'waiting', inboxPage = 1;
  const element = (tag, text, className) => { const e = document.createElement(tag); e.textContent = text; if (className) e.className = className; return e; };
  const actionLink = (text, url) => { const a = element('a', text, 'button'); a.href = url; return a; };
  const runButton = (text, fn) => { const b = element('button', text); b.type = 'button'; b.addEventListener('click', async () => { b.disabled = true; try { await fn(); } catch (error) { message(error.message); } finally { b.disabled = false; } }); return b; };
  function connectionState(data) {
    $('krev-inbox-connection').textContent = data.connected ? `Muse is connected. Photo preservation: ${data.preserve_photos ? 'on' : 'off'}.` : 'Muse is not connected yet.';
    $('krev-inbox-disconnect').hidden = !data.connected;
    $('krev-inbox-preserve').checked = !!data.preserve_photos;
  }
  async function loadInbox() {
    $('krev-inbox').hidden = false;
    if (!KREVImport.sellerReady) { $('krev-inbox').hidden = true; $('krev-import-login').hidden = false; message('Sign in to your seller account to open your Marketplace inbox.'); return; }
    message('Loading your inbox…');
    const data = await request(`inbox?tab=${inboxTab}&page=${inboxPage}`, {}, 'GET');
    connectionState(data);
    const tabs = $('krev-inbox-tabs'); tabs.replaceChildren();
    for (const [key, label] of [['waiting','Waiting'], ['drafts','Drafts / review'], ['published','Published'], ['archived','Archived'], ['all','All active']]) {
      const b = runButton(label, async () => { inboxTab = key; inboxPage = 1; await loadInbox(); }); b.setAttribute('aria-pressed', key === inboxTab ? 'true' : 'false'); tabs.append(b);
    }
    $('krev-inbox-count').textContent = `${data.total} item(s) · oldest first`;
    $('krev-inbox-page').textContent = `Page ${data.page} of ${data.pages}`;
    $('krev-inbox-prev').disabled = data.page <= 1; $('krev-inbox-next').disabled = data.page >= data.pages;
    const box = $('krev-inbox-items'); box.replaceChildren();
    if (!data.items.length) box.append(element('p', 'No items in this view.'));
    for (const item of data.items) {
      const card = element('article', '', 'krev-inbox-card');
      if (item.thumbnail_url) { const img = document.createElement('img'); img.src = item.thumbnail_url; img.alt = ''; img.loading = 'lazy'; card.append(img); }
      card.append(element('h2', item.title), element('p', `${item.state}${item.product_status ? ' · ' + item.product_status : ''} · Source price $${item.source_price ?? 'unknown'}${item.product_id ? ' · Product #' + item.product_id : ''}`));
      if (item.photo_counts) card.append(photoSummary(item.photo_counts), element('p', `${item.preserved_photos} photo(s) preserved in your media library.`));
      for (const warning of item.warnings || []) card.append(element('p', warning, 'krev-inbox-warning'));
      const actions = element('div', '', 'krev-inbox-actions');
      if (item.state !== 'archived') { actions.append(actionLink(item.product_id ? 'Review original import' : 'Open review / create draft', item.review_url)); if (item.edit_url) actions.append(actionLink('Continue in ListLab', item.edit_url)); }
      actions.append(runButton(item.state === 'archived' ? 'Restore to inbox' : 'Archive item', async () => { await request('inbox/' + item.id, {archived:item.state !== 'archived'}, 'PATCH'); await loadInbox(); })); card.append(actions); box.append(card);
    }
    message('Inbox ready. Creating a draft and publishing happen only when you open and complete an item.');
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
  function photoSummary(counts) {
    const p = document.createElement('p');
    p.textContent = counts ? `Photos: ${counts.found ?? 'unknown'} found on Facebook; ${counts.submitted} supplied. Draft copies: ${counts.copied ?? 'not copied yet'}. Source gallery ${counts.gallery_verified ? 'verified by extraction' : 'not verified'}.` : 'Source photo count was not recorded. Check the Facebook gallery before publishing.';
    return p;
  }
  function showResult(data) {
    $('krev-import-form').hidden = true;
    const box = $('krev-import-result'); box.replaceChildren(); box.hidden = false;
    const listing = data.listing; box.append(photoSummary(data.photo_counts));
    const sourcePrice = data.data?.source_price ?? state?.data?.source_price;
    if (sourcePrice !== undefined) box.append(element('p', 'Verified source price: $' + sourcePrice + ' USD.'));
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
    if (!token && !inboxId) { await loadInbox(); return; }
    $('krev-import-heading').textContent = 'Finish your Marketplace listing';
    if (inboxId && !KREVImport.sellerReady) { $('krev-import-login').hidden = false; message('Sign in to the seller account that owns this inbox item.'); return; }
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
    showPrice(state.price); showSpecs(state.data); $('krev-import-photos').append(photoSummary(state.photo_counts));
    for (const url of state.preview_photos || state.data.image_urls) { const img = document.createElement('img'); img.src = url; img.alt = 'Supplied item photo'; img.referrerPolicy = 'no-referrer'; $('krev-import-photos').append(img); }
    for (const warning of state.warnings || []) $('krev-import-photos').append(element('p', warning));
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
  $('krev-inbox-prev').addEventListener('click', () => { inboxPage--; loadInbox().catch(error => message(error.message)); });
  $('krev-inbox-next').addEventListener('click', () => { inboxPage++; loadInbox().catch(error => message(error.message)); });
  $('krev-inbox-refresh').addEventListener('click', () => loadInbox().catch(error => message(error.message)));
  $('krev-inbox-connect').addEventListener('submit', async event => {
    event.preventDefault(); const button = event.submitter; if (button) button.disabled = true;
    try {
      const data = await request('inbox/connection', {action:'connect', authorize_queue:$('krev-inbox-authorize').checked, preserve_photos:$('krev-inbox-preserve').checked});
      connectionState(data);
      $('krev-inbox-key').value = `Use my kniferevive-listing skill in inbox mode. For each item I ask you to list, send its complete extraction and photo_manifest to ${KREVImport.root}prepare with the additional JSON field inbox_key set to ${data.inbox_key}. Store this delivery key privately. Do not put it in URLs, descriptions, screenshots or logs. A state: queued response means it is waiting in my seller inbox, not a draft or published listing. Show the returned review link. My browser completion agent will process it later. Inbox: ${data.inbox_url}`;
      $('krev-inbox-key-box').hidden = false; message('Connection created. Copy the private setup prompt to Muse now; the key is shown only once.');
    } catch (error) { message(error.message); } finally { if (button) button.disabled = false; }
  });
  $('krev-inbox-copy').addEventListener('click', async () => { try { await navigator.clipboard.writeText($('krev-inbox-key').value); message('Private setup prompt copied. Paste it into Muse.'); } catch (_) { $('krev-inbox-key').select(); message('Select and copy the private setup prompt.'); } });
  $('krev-inbox-disconnect').addEventListener('click', async () => { try { const data = await request('inbox/connection', {action:'disconnect'}); connectionState(data); $('krev-inbox-key').value = ''; $('krev-inbox-key-box').hidden = true; message('Muse disconnected. Existing inbox items are retained.'); } catch (error) { message(error.message); } });
  load().catch(error => message(error.message));
})();
