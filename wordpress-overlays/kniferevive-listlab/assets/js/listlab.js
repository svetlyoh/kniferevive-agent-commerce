(() => {
  'use strict';
  const customAttributePrefix = '__krev_custom__:';

  const app = document.querySelector('#krev-listlab');
  if (!app || !window.KREVListLab || app.dataset.initialized === 'true') return;
  app.dataset.initialized = 'true';

  const query = new URLSearchParams(location.search);
  const esc = value => String(value ?? '').replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
  })[character]);
  const state = {
    tab: ['active', 'drafts', 'hidden'].includes(query.get('tab')) ? query.get('tab') : 'active',
    sort: 'newest', search: '', page: 1, listing: null, schema: null, images: [], uploads: [], video: null, videoUrl: '', videoUpload: null,
    sharedVideos: null, sharedVideoLoading: false, sharedVideoError: '', sharedVideoPage: 1,
    dirty: false, draftKey: '', draftTimer: 0, restoreCandidate: null, schemaLoading: false,
    clearUndo: null, clearUndoTimer: 0
  };
  const account = (params = '') => KREVListLab.accountUrl + (params ? `?${params}` : '');
  const edit = id => account(`action=edit&product_id=${id}`);
  const image = item => item?.thumbnail_url || item?.url || '';
  const sellerKey = String(KREVListLab.sellerId || KREVListLab.sellerName || 'seller').replace(/[^a-z0-9_-]/gi, '_');
  const draftIndexKey = `krev_listlab_draft_index:${sellerKey}`;

  const api = async (path, options = {}) => {
    const response = await fetch(KREVListLab.root + path, {
      credentials: 'same-origin',
      headers: {
        'X-WP-Nonce': KREVListLab.nonce,
        ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
        ...(options.headers || {})
      },
      ...options
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw data;
    return data;
  };

  const profile = () => `<a class="ll-profile" href="${esc(KREVListLab.myAccountUrl)}" aria-label="My Account" title="My Account"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="8" r="3.5"></circle><path d="M4.5 20c.6-4 3.2-6 7.5-6s6.9 2 7.5 6"></path></svg><span>My Account</span></a>`;
  const header = () => `<header class="ll-topbar"><div class="ll-brand"><strong>List Lab</strong></div>${profile()}</header>`;
  const frame = html => header() + `<main class="ll-main">${html}</main><div class="screen-reader-text" data-status role="status" aria-live="polite" aria-atomic="true"></div><div class="ll-toast" data-toast hidden></div>`;
  const money = value => value === '' || value === null ? 'Price not set' : new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value));

  function announce(message) {
    const status = app.querySelector('[data-status]');
    if (!status) return;
    status.textContent = '';
    window.setTimeout(() => { status.textContent = message; }, 20);
  }

  function showToast(message, actionLabel = '', action = null, duration = 4500) {
    const toast = app.querySelector('[data-toast]');
    if (!toast) return;
    toast.innerHTML = `<span>${esc(message)}</span>${actionLabel ? `<button type="button" data-toast-action>${esc(actionLabel)}</button>` : ''}`;
    toast.hidden = false;
    announce(message);
    clearTimeout(showToast.timer);
    const button = toast.querySelector('[data-toast-action]');
    if (button) button.onclick = () => { action?.(); toast.hidden = true; };
    showToast.timer = setTimeout(() => { toast.hidden = true; }, duration);
  }

  function timedOfferSummary(product) {
    const offer = product.timed_offer || {}, end = offer.ends_at ? new Date(offer.ends_at) : null, start = offer.starts_at ? new Date(offer.starts_at) : null;
    if (offer.state === 'active' && end) { const hours = Math.max(0, Math.ceil((end.getTime() - Date.now()) / 3600000)); return `<p class="ll-timed-summary"><strong>Timed offer</strong><span>Ends ${esc(end.toLocaleString())} · ${Math.floor(hours / 24)}d ${hours % 24}h remaining</span></p>`; }
    if (offer.state === 'scheduled' && start && end) return `<p class="ll-timed-summary"><strong>Sale scheduled</strong><span>Starts ${esc(start.toLocaleString())} · Ends ${esc(end.toLocaleString())}</span></p>`;
    if (offer.state === 'expired') return `<p class="ll-timed-summary"><strong>Sale ended</strong><span>Regular price: ${esc(money(product.regular_price))}</span></p>`;
    return '';
  }

  function whatnotDemoExpired(product) {
    const demo = product.whatnot_demo || {};
    if (!demo.enabled || !demo.url) return false;
    const starts = Date.parse(demo.starts_at_utc || '');
    return demo.state === 'expired' || (Number.isFinite(starts) && Date.now() >= starts);
  }

  function whatnotExpiredNotice(product, editor = false) {
    if (!whatnotDemoExpired(product)) return '';
    const demo = product.whatnot_demo;
    const detail = editor ? 'Choose a new future date and time, or turn off the Whatnot demo.' : 'Edit this listing to schedule another show.';
    return `<div class="ll-demo-expired${editor ? ' ll-demo-expired--editor' : ''}"><strong>Whatnot show expired</strong><p>Buyers no longer see the Whatnot link on this listing. ${detail}</p><p class="ll-demo-old-link"><span>Previous show link (seller only):</span> <code>${esc(demo.url)}</code></p></div>`;
  }

  function card(product) {
    const title = product.title || 'Untitled listing';
    const safeTitle = esc(title);
    const status = product.archived ? 'Hidden' : product.status === 'publish' ? 'Active' : 'Draft';
    const photo = image(product.featured_image);
    const date = product.created_date ? new Date(product.created_date).toLocaleDateString('en-CA', { year: '2-digit', month: '2-digit', day: '2-digit' }).replace(/-/g, '/') : '';
    const priceHtml = product.price_html || esc(money(product.regular_price));
    const outOfStock = product.stock_status === 'outofstock' || Number(product.quantity) <= 0;
    const priceEditor = `<div class="ll-quick-price" data-quick-price="${product.id}"><label>Price <span>$</span><input type="text" inputmode="decimal" value="${esc(product.regular_price)}" data-regular aria-label="Regular price for ${safeTitle}"></label>${product.sale_price !== '' ? `<label>Sale <span>$</span><input type="text" inputmode="decimal" value="${esc(product.sale_price)}" data-sale aria-label="Sale price for ${safeTitle}"></label>` : ''}<small data-quick-message aria-live="polite"></small></div>`;
    const quantityEditor = `<div class="ll-quick-quantity" data-quick-quantity="${product.id}"><span>Qty</span><button type="button" data-qty-step="-1" aria-label="Decrease quantity for ${safeTitle}">−</button><input type="number" min="0" step="1" value="${Number(product.quantity) || 0}" data-quantity aria-label="Quantity for ${safeTitle}"><button type="button" data-qty-step="1" aria-label="Increase quantity for ${safeTitle}">+</button><small data-quick-message aria-live="polite"></small></div>`;
    return `<article class="ll-card" data-listing-id="${product.id}"><a class="ll-card-photo krev-listing-thumb" href="${edit(product.id)}" aria-label="Edit ${safeTitle}">${photo ? `<img src="${esc(photo)}" alt="${safeTitle} listing thumbnail">` : '<span>No photo</span>'}</a><div class="ll-card-body"><div class="ll-card-title"><div><h2><a href="${edit(product.id)}">${safeTitle}</a></h2>${product.is_knife && product.subtitle ? `<p>${esc(product.subtitle)}</p>` : ''}</div><button type="button" data-more="${product.id}" class="ll-more" aria-label="Actions for ${safeTitle}" aria-controls="ll-menu-${product.id}" aria-expanded="false">•••</button></div><div class="ll-price-stock">${priceEditor}${quantityEditor}</div>${timedOfferSummary(product)}${whatnotExpiredNotice(product)}<div class="ll-meta"><b class="ll-status ll-${status.toLowerCase()}">${status}</b>${date ? `<span class="ll-created"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"></circle><path d="M12 7v5l3 2"></path></svg>${esc(date)}</span>` : ''}${product.sku ? `<span>SKU ${esc(product.sku)}</span>` : ''}<span class="ll-views"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>${Number(product.views_30d) || 0} views · 30d</span></div><div class="ll-card-actions"><a class="ll-button ll-secondary" href="${edit(product.id)}" aria-label="Edit ${safeTitle}">Edit</a>${product.view_url ? `<a class="ll-button ll-quiet" href="${esc(product.view_url)}" target="_blank" rel="noopener" aria-label="View ${safeTitle} (opens in a new tab)">View</a>` : ''}</div>${listingMenu(product)}</div></article>`;
  }

  function listingMenu(product) {
    const title = esc(product.title || 'Untitled listing');
    if (product.archived) return `<div id="ll-menu-${product.id}" class="ll-actions-menu" role="group" aria-label="Actions for ${title}" hidden><button data-action="restore" data-id="${product.id}" aria-label="Restore ${title}">Restore listing</button><button data-action="duplicate" data-id="${product.id}" aria-label="Duplicate ${title}">Duplicate</button><button class="ll-danger" data-action="delete" data-id="${product.id}" aria-label="Delete ${title} permanently">Delete permanently</button></div>`;
    if (product.status === 'publish') return `<div id="ll-menu-${product.id}" class="ll-actions-menu" role="group" aria-label="Actions for ${title}" hidden><button data-action="archive" data-id="${product.id}" aria-label="Hide ${title}">Hide Listing</button><button data-action="duplicate" data-id="${product.id}" aria-label="Duplicate ${title}">Duplicate</button><button data-action="draft" data-id="${product.id}" aria-label="Move ${title} to drafts">Move to Drafts</button>${product.view_url ? `<button data-action="copy" data-url="${esc(product.view_url)}" aria-label="Copy link for ${title}">Copy listing link</button>` : ''}</div>`;
    return `<div id="ll-menu-${product.id}" class="ll-actions-menu" role="group" aria-label="Actions for ${title}" hidden><button data-action="publish" data-id="${product.id}" aria-label="Publish ${title}">Publish</button><button data-action="duplicate" data-id="${product.id}" aria-label="Duplicate ${title}">Duplicate</button><button class="ll-danger" data-action="delete" data-id="${product.id}" aria-label="Delete ${title} permanently">Delete permanently</button></div>`;
  }

  async function listings() {
    app.innerHTML = frame(`<section class="ll-hero"><div><p>SELLER WORKSPACE</p><h2>My Listings</h2><span>Build, manage, and publish your inventory.</span></div><a class="ll-button ll-primary" href="${account('action=new')}">+ Add listing</a></section><section class="ll-controls"><div class="ll-tabs" role="group" aria-label="Listing status">${[['active', 'Active (Published)'], ['drafts', 'Drafts'], ['hidden', 'Hidden']].map(([key, label]) => `<button type="button" data-tab="${key}" class="${key === state.tab ? 'on' : ''}" aria-pressed="${key === state.tab ? 'true' : 'false'}">${label}</button>`).join('')}</div><div class="ll-filter"><label class="ll-search"><span aria-hidden="true">⌕</span><span class="screen-reader-text">Search your listings</span><input data-search type="search" placeholder="Search your listings" value="${esc(state.search)}"></label><select data-sort aria-label="Sort listings"><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="price_high">Price: high to low</option><option value="price_low">Price: low to high</option><option value="alpha">Alphabetical</option></select><nav class="ll-pagination" id="ll-pagination" aria-label="Listing pages"></nav></div></section><section class="ll-list" id="ll-list" aria-busy="true"><p class="ll-loading">Loading your listings…</p></section><a class="ll-fab" href="${account('action=new')}" aria-label="Add listing">+</a>`);
    app.querySelector('[data-sort]').value = state.sort;
    app.querySelectorAll('[data-tab]').forEach(button => { button.onclick = () => { state.tab = button.dataset.tab; state.page = 1; history.replaceState({}, '', account(`tab=${state.tab}`)); listings(); }; });
    let searchTimer;
    app.querySelector('[data-search]').oninput = event => { state.search = event.target.value; state.page = 1; clearTimeout(searchTimer); searchTimer = setTimeout(load, 180); };
    app.querySelector('[data-sort]').onchange = event => { state.sort = event.target.value; state.page = 1; load(); };
    await load();
    const flash = sessionStorage.getItem('krev_listlab_status');
    if (flash) { sessionStorage.removeItem('krev_listlab_status'); announce(flash); }
  }

  async function load() {
    document.querySelectorAll('body > .ll-actions-menu').forEach(menu => menu.remove());
    const box = app.querySelector('#ll-list');
    if (!box) return;
    box.innerHTML = '<p class="ll-loading">Loading your listings…</p>';
    box.setAttribute('aria-busy', 'true');
    announce('Loading listings.');
    try {
      const response = await api(`listings?tab=${state.tab}&sort=${state.sort}&search=${encodeURIComponent(state.search)}&per_page=25&page=${state.page}`);
      if (!response.items.length && response.total && state.page > Math.ceil(response.total / 25)) { state.page = Math.ceil(response.total / 25); return load(); }
      box.innerHTML = response.items.length ? response.items.map(card).join('') : `<div class="ll-empty"><h2>No ${esc(state.tab)} listings</h2><p>${state.search ? 'Try another title or SKU.' : 'Your listings will appear here.'}</p>${state.tab === 'active' && !state.search ? `<a class="ll-button ll-primary" href="${account('action=new')}">Create a listing</a>` : ''}</div>`;
      setupListingActions(); pagination(response.total);
      box.setAttribute('aria-busy', 'false');
      announce(`${response.total} ${response.total === 1 ? 'listing' : 'listings'} loaded.`);
    } catch (error) { box.setAttribute('aria-busy', 'false'); box.innerHTML = `<p class="ll-message" role="alert">${esc(error.message || 'Unable to load your listings.')}</p>`; announce(error.message || 'Unable to load your listings.'); }
  }

  function pagination(total) {
    const nav = app.querySelector('#ll-pagination');
    if (!nav) return;
    const pages = Math.ceil((Number(total) || 0) / 25);
    nav.innerHTML = Array.from({ length: 5 }, (_, index) => { const page = index + 1, enabled = page <= pages; return `<button type="button" data-page="${page}" class="${page === state.page ? 'on' : ''}" ${enabled ? '' : 'disabled'} aria-label="Page ${page}">${page}</button>`; }).join('');
    nav.querySelectorAll('[data-page]').forEach(button => { button.onclick = () => { const page = Number(button.dataset.page); if (page !== state.page) { state.page = page; load(); } }; });
  }

  function setupListingActions() {
    let openMenu = null, openTrigger = null;
    const closeMenu = focus => { if (!openMenu) return; openMenu.hidden = true; openTrigger?.setAttribute('aria-expanded', 'false'); if (focus) openTrigger?.focus(); openMenu = openTrigger = null; };
    const positionMenu = () => { if (!openMenu || !openTrigger) return; const r = openTrigger.getBoundingClientRect(), edge = 10, width = Math.min(240, innerWidth - edge * 2); openMenu.style.width = `${width}px`; openMenu.style.left = `${Math.max(edge, Math.min(innerWidth - width - edge, r.right - width))}px`; openMenu.style.top = '0'; const height = openMenu.offsetHeight, below = innerHeight - r.bottom; openMenu.style.top = `${Math.max(edge, below >= height + edge ? r.bottom + 6 : r.top - height - 6)}px`; };
    const actionButtons = Array.from(app.querySelectorAll('[data-action]'));
    actionButtons.forEach(button => { button.onclick = async () => {
      const type = button.dataset.action, id = button.dataset.id;
      if (type === 'copy') { try { await navigator.clipboard.writeText(button.dataset.url); button.textContent = 'Link copied'; } catch (_) { prompt('Copy this listing link:', button.dataset.url); } return; }
      if (type === 'duplicate') { location.href = account(`action=duplicate&product_id=${id}`); return; }
      if (type === 'delete' && !confirm('Delete this listing permanently? This cannot be undone.')) return;
      if (type === 'archive' && !confirm('Hide this listing? You can restore it later.')) return;
      try { button.disabled = true; await api(type === 'delete' ? `listings/${id}` : `listings/${id}/${type}`, { method: type === 'delete' ? 'DELETE' : 'POST' }); await load(); announce(`Listing ${type === 'delete' ? 'deleted' : type === 'archive' ? 'hidden' : type === 'draft' ? 'moved to drafts' : type === 'restore' ? 'restored' : 'published'}.`); }
      catch (error) { alert(error.message || 'Action failed.'); button.disabled = false; }
    }; });
    app.querySelectorAll('[data-more]').forEach(button => { button.onclick = event => { event.stopPropagation(); const next = document.querySelector(`#ll-menu-${button.dataset.more}`); if (openMenu) closeMenu(false); openMenu = next; openTrigger = button; document.body.appendChild(next); next.hidden = false; button.setAttribute('aria-expanded', 'true'); positionMenu(); next.querySelector('button')?.focus(); }; });
    document.addEventListener('pointerdown', event => { if (openMenu && !openMenu.contains(event.target) && event.target !== openTrigger) closeMenu(false); }, { once: false });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && openMenu) { event.preventDefault(); closeMenu(true); } });
    window.addEventListener('resize', positionMenu, { passive: true });
    window.addEventListener('scroll', positionMenu, { passive: true });
    setupQuickEdits();
  }

  function setupQuickEdits() {
    app.querySelectorAll('[data-quick-quantity]').forEach(box => {
      const input = box.querySelector('[data-quantity]'), message = box.querySelector('[data-quick-message]'); let confirmed = Number(input.value) || 0, saving = false;
      const save = async raw => { const value = Number(raw); if (String(raw).trim() === '' || !Number.isInteger(value) || value < 0) { input.value = confirmed; message.textContent = 'Use a whole number'; box.classList.add('is-error'); return; } if (value === confirmed || saving) { input.value = confirmed; return; } saving = true; box.classList.add('is-saving'); message.textContent = 'Saving…'; box.querySelectorAll('input,button').forEach(control => { control.disabled = true; }); try { const product = await api(`listings/${box.dataset.quickQuantity}/quick-edit`, { method: 'PATCH', body: JSON.stringify({ quantity: value }) }); confirmed = Number(product.quantity) || 0; input.value = confirmed; message.textContent = 'Saved'; } catch (error) { input.value = confirmed; message.textContent = error.message || 'Could not save'; box.classList.add('is-error'); } finally { saving = false; box.querySelectorAll('input,button').forEach(control => { control.disabled = false; }); box.classList.remove('is-saving'); setTimeout(() => { message.textContent = ''; box.classList.remove('is-error'); }, 2200); } };
      box.querySelectorAll('[data-qty-step]').forEach(button => button.onclick = () => save(confirmed + Number(button.dataset.qtyStep)));
      input.onkeydown = event => { if (event.key === 'Enter') { event.preventDefault(); save(input.value); } if (event.key === 'Escape') { input.value = confirmed; input.blur(); } };
      input.onblur = () => save(input.value);
    });
    app.querySelectorAll('[data-quick-price]').forEach(box => {
      const regular = box.querySelector('[data-regular]'), sale = box.querySelector('[data-sale]'), message = box.querySelector('[data-quick-message]'); let confirmedRegular = regular.value, confirmedSale = sale?.value ?? '', saving = false;
      const save = async () => { if (saving || (regular.value === confirmedRegular && (!sale || sale.value === confirmedSale))) return; saving = true; box.classList.add('is-saving'); message.textContent = 'Saving…'; box.querySelectorAll('input').forEach(control => { control.disabled = true; }); try { const payload = { regular_price: regular.value }; if (sale) payload.sale_price = sale.value; const product = await api(`listings/${box.dataset.quickPrice}/quick-edit`, { method: 'PATCH', body: JSON.stringify(payload) }); confirmedRegular = product.regular_price; confirmedSale = product.sale_price; regular.value = confirmedRegular; if (sale) sale.value = confirmedSale; message.textContent = 'Saved'; } catch (error) { regular.value = confirmedRegular; if (sale) sale.value = confirmedSale; message.textContent = error.message || 'Could not save'; box.classList.add('is-error'); } finally { saving = false; box.querySelectorAll('input').forEach(control => { control.disabled = false; }); box.classList.remove('is-saving'); setTimeout(() => { message.textContent = ''; box.classList.remove('is-error'); }, 2500); } };
      [regular, sale].filter(Boolean).forEach(input => { input.onkeydown = event => { if (event.key === 'Enter') { event.preventDefault(); save(); } if (event.key === 'Escape') { regular.value = confirmedRegular; if (sale) sale.value = confirmedSale; input.blur(); } }; input.onblur = save; });
    });
  }

  const select = (values, selected, attrs = '') => {
    const options = values || [], isAttribute = attrs.includes('data-attribute');
    const selectedValue = String(selected || '').startsWith(customAttributePrefix)
      ? String(selected).slice(customAttributePrefix.length)
      : selected;
    const customSelected = isAttribute && selectedValue && !options.some(value => String(value.id) === String(selectedValue));
    const customData = customSelected ? ` data-custom-value="${esc(selectedValue)}"` : '';
    return `<select ${attrs}${customData}><option value="">Select an option</option>${options.map(value => `<option value="${value.id}" ${String(value.id) === String(selectedValue) ? 'selected' : ''}>${esc(value.name)}</option>`).join('')}${customSelected ? `<option value="${esc(selectedValue)}" selected>${esc(selectedValue)} (custom)</option>` : ''}${isAttribute ? '<option value="__custom__">+ Add a custom value…</option>' : ''}</select>`;
  };
  const base = { id: 0, title: '', subtitle: '', description: '', short_description: '', regular_price: '', sale_price: '', timed_offer: { enabled: false, state: 'none', starts_at: null, ends_at: null, timezone: '' }, whatnot_demo: { enabled: false, url: '', date: '', time: '', timezone: '', state: 'none' }, quantity: 1, sku: '', global_unique_id: '', categories: [], attributes: {}, sharpened: false, condition_resolution: null, refurbishment_evidence: { restoration_confirmed: false, like_new_confirmed: false, warranty_terms: '', needs_review: false }, featured_image: null, gallery: [], shipping_policy: 0, return_policy: 0, weight: '', dimensions: { length: '', width: '', height: '' } };
  const barcodeScanner = { active: false, stream: null, timer: 0, controls: null, reader: null, focus: null, detecting: false };
  const barcodeFormats = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf'];
  async function schema(category) { state.schema = await api(`listings/schema?category_id=${Number(category) || 0}`); }

  function imageTile(item, index) {
    const name = `${state.listing.title || 'Listing'} ${index === 0 ? 'cover photo' : `photo ${index + 1}`}`;
    const safeName = esc(name);
    return `<figure class="${index === 0 ? 'cover' : ''}" draggable="true" data-image="${index}"><a class="ll-image-preview" href="${esc(item.url || image(item))}" target="_blank" rel="noopener" aria-label="Preview ${safeName} (opens in a new tab)"><img src="${esc(image(item))}" alt="${esc(item.alt || name)}"></a><figcaption>${index === 0 ? 'Cover photo' : `Photo ${index + 1}`}<span>Uploaded</span></figcaption><div class="ll-image-actions"><a href="${esc(item.url || image(item))}" target="_blank" rel="noopener" aria-label="Preview ${safeName} (opens in a new tab)">Preview</a><button type="button" data-cover="${index}" ${index === 0 ? 'disabled' : ''} aria-label="Set ${safeName} as cover">Make cover</button><button type="button" data-move="left" data-index="${index}" ${index === 0 ? 'disabled' : ''} aria-label="Move ${safeName} left">←</button><button type="button" data-move="right" data-index="${index}" ${index === state.images.length - 1 ? 'disabled' : ''} aria-label="Move ${safeName} right">→</button><button type="button" data-remove="${index}" aria-label="Remove ${safeName}">Remove</button></div></figure>`;
  }

  function uploadTile(upload) {
    const label = upload.status === 'uploading' ? 'Uploading…' : 'Upload failed';
    return `<figure class="ll-upload-state ${upload.status}"><img src="${esc(upload.preview)}" alt=""><figcaption>${label}</figcaption>${upload.status === 'failed' ? `<div class="ll-image-actions"><button type="button" data-retry="${upload.key}">Retry</button><button type="button" data-cancel-upload="${upload.key}">Remove</button></div>` : '<span class="ll-spinner" aria-hidden="true"></span>'}</figure>`;
  }

  function photoSection() {
    return `<section class="ll-section ll-photo-section" data-section="images"><header><b>01</b><div><h2>Photos <span class="ll-required" aria-hidden="true">*</span><span class="screen-reader-text"> required</span></h2><p>Add up to 10 photos. The first photo is the cover.</p></div></header><div class="ll-photos">${state.images.map(imageTile).join('')}${state.uploads.map(uploadTile).join('')}<label class="ll-upload"><input data-files type="file" accept="image/jpeg,image/png,image/webp" multiple hidden><span>+</span><strong>Add photos</strong><small>JPEG, PNG, or WebP</small></label></div></section>`;
  }

  function videoLinkInfo(value) {
    try {
      const url = new URL(value), host = url.hostname.toLowerCase(), path = url.pathname;
      if (url.protocol !== 'https:' || url.username || url.password || url.port) return null;
      let id = '';
      if (host === 'youtu.be') id = path.match(/^\/([A-Za-z0-9_-]{11})\/?$/)?.[1] || '';
      if (['youtube.com', 'www.youtube.com', 'm.youtube.com'].includes(host)) id = path === '/watch' ? url.searchParams.get('v') || '' : path.match(/^\/(?:shorts|embed)\/([A-Za-z0-9_-]{11})\/?$/)?.[1] || '';
      if (/^[A-Za-z0-9_-]{11}$/.test(id)) return { url: `https://www.youtube.com/watch?v=${id}`, embed: `https://www.youtube-nocookie.com/embed/${id}` };
      if (['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'].includes(host)) { id = path.match(/^\/(?:video\/)?([0-9]+)\/?$/)?.[1]; if (id) return { url: `https://vimeo.com/${id}`, embed: `https://player.vimeo.com/video/${id}` }; }
    } catch (_) {}
    return null;
  }

  function videoSection() {
    const video = state.video, pending = state.videoUpload, busy = pending?.status === 'uploading';
    const hostingNotice = KREVListLab.videoHostingAvailable === false ? '<p class="ll-video-hosting-notice" role="status">Video uploads are not enabled by KnifeRevive’s WordPress.com hosting plan yet.</p>' : '';
    const status = pending ? `<div class="ll-video-status" role="status"><strong>${esc(pending.file.name)}</strong><p>${busy ? 'Uploading video…' : esc(pending.error || 'Upload failed.')}</p>${busy ? '<span class="ll-spinner" aria-hidden="true"></span>' : '<button type="button" data-retry-video>Retry</button> <button type="button" data-cancel-video>Dismiss</button>'}</div>` : '';
    const linked = videoLinkInfo(state.videoUrl), unavailable = KREVListLab.videoHostingAvailable === false;
    const preview = video || linked ? `<div class="ll-video-preview">${video ? `<video controls playsinline preload="metadata" src="${esc(video.url)}" aria-label="Listing video preview"></video><p>${esc(video.title || 'Listing video')}</p>` : `<iframe src="${esc(linked.embed)}" title="Listing video preview" loading="lazy" allow="fullscreen; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>`}<a href="${esc(video ? video.url : linked.url)}" target="_blank" rel="noopener">Open video</a> <button type="button" data-remove-video ${busy ? 'disabled' : ''}>Remove video</button></div>` : '';
    return `<section class="ll-section ll-video-section" data-section="video"><header><b>02</b><div><h2>Video <small>Optional</small></h2><p>Add one video to show your item in action. MP4, WebM, or MOV, up to ${esc(KREVListLab.maxVideoUploadLabel || '100 MB')}. MP4 (H.264) is recommended for browser playback.</p></div></header>${preview}${status}${hostingNotice}<label class="ll-video-picker ${busy || unavailable ? 'is-busy' : ''}" for="ll-video-file"><strong>${video ? 'Replace video' : 'Upload video'}</strong><input id="ll-video-file" data-video-file type="file" accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime" ${busy || unavailable ? 'disabled' : ''}></label>${sharedVideoSection(busy)}<label class="ll-video-link-field" for="ll-video-url">YouTube or Vimeo link <small>Optional</small><input id="ll-video-url" data-video-url type="url" value="${esc(state.videoUrl)}" placeholder="https://www.youtube.com/watch?v=…" ${busy ? 'disabled' : ''}></label><p class="ll-video-link-help">Use a public video that allows embedding. A video link replaces an uploaded video.</p><button type="button" data-apply-video-url ${busy ? 'disabled' : ''}>Use video link</button></section>`;
  }

  function sharedVideoSection(busy) {
    if (!KREVListLab.sharedVideosAvailable) return '';
    const data = state.sharedVideos;
    let html = '<div class="ll-shared-videos"><h3>Shared upload folders</h3><p>Choose a video submitted through an upload link assigned to your account.</p>';
    html += `<button type="button" data-load-shared-videos ${busy || state.sharedVideoLoading ? 'disabled' : ''}>${state.sharedVideoLoading ? 'Loading videos…' : data ? 'Refresh shared videos' : 'Choose from shared videos'}</button>`;
    if (state.sharedVideoError) html += `<p role="alert">${esc(state.sharedVideoError)}</p>`;
    if (data) {
      html += '<div class="ll-shared-video-grid">' + data.items.map(item => `<article><video controls playsinline preload="metadata" src="${esc(item.url)}" aria-label="Preview ${esc(item.title)}"></video><strong>${esc(item.title)}</strong><small>${esc(item.folder)}</small><button type="button" data-select-shared-video="${item.id}" ${busy ? 'disabled' : ''}>Use this video</button></article>`).join('') + '</div>';
      if (!data.items.length) html += '<p>No videos in your shared folders yet.</p>';
      if (data.pages > 1) html += `<p>Page ${data.page} of ${data.pages} <button type="button" data-shared-page="${data.page - 1}" ${data.page <= 1 || state.sharedVideoLoading ? 'disabled' : ''}>Previous</button><button type="button" data-shared-page="${data.page + 1}" ${data.page >= data.pages || state.sharedVideoLoading ? 'disabled' : ''}>Next</button></p>`;
    }
    return html + '</div>';
  }

  async function loadSharedVideos(page = 1) {
    if (!KREVListLab.sharedVideosAvailable || state.sharedVideoLoading || uploadsBusy()) return;
    const editorId = state.listing?.id || state.listing?.client_uuid;
    state.sharedVideoLoading = true; state.sharedVideoError = ''; state.sharedVideoPage = page; renderEditor();
    try { state.sharedVideos = await api(`shared-videos?page=${page}`); }
    catch (error) { state.sharedVideoError = error.message || 'Unable to load shared videos.'; }
    finally { state.sharedVideoLoading = false; if (state.listing && (state.listing.id || state.listing.client_uuid) === editorId) renderEditor(); }
  }

  function setupVideoManager() {
    const shared = app.querySelector('[data-load-shared-videos]');
    if (shared) shared.onclick = () => loadSharedVideos(state.sharedVideoPage);
    app.querySelectorAll('[data-shared-page]').forEach(button => { button.onclick = () => loadSharedVideos(Number(button.dataset.sharedPage)); });
    app.querySelectorAll('[data-select-shared-video]').forEach(button => { button.onclick = () => {
      if (uploadsBusy()) return;
      const selected = state.sharedVideos?.items.find(item => item.id === Number(button.dataset.selectSharedVideo));
      if (!selected) return;
      state.video = selected; state.videoUrl = ''; state.videoUpload = null; state.dirty = true; persistDraftNow(); renderEditor(); showToast('Shared video selected. Save your listing to keep it.');
    }; });
    app.querySelector('[data-video-file]').onchange = event => {
      const file = event.target.files[0];
      if (!file || state.videoUpload?.status === 'uploading') return;
      if (!/\.(mp4|webm|mov)$/i.test(file.name)) { showToast('Use an MP4, WebM, or MOV video.'); event.target.value = ''; return; }
      if (!file.size || file.size > Number(KREVListLab.maxVideoUploadBytes || 104857600)) { showToast(`Choose a video smaller than ${KREVListLab.maxVideoUploadLabel || '100 MB'}.`); event.target.value = ''; return; }
      uploadVideo({ file, status: 'queued' });
    };
    const remove = app.querySelector('[data-remove-video]'), retry = app.querySelector('[data-retry-video]'), cancel = app.querySelector('[data-cancel-video]');
    if (remove) remove.onclick = () => { state.video = null; state.videoUrl = ''; state.dirty = true; persistDraftNow(); renderEditor(); };
    if (retry) retry.onclick = () => uploadVideo(state.videoUpload);
    if (cancel) cancel.onclick = () => { state.videoUpload = null; renderEditor(); };
    app.querySelector('[data-apply-video-url]').onclick = () => {
      const linked = videoLinkInfo(app.querySelector('[data-video-url]').value.trim());
      if (!linked) { showToast('Enter a public HTTPS YouTube or Vimeo video link.'); return; }
      state.videoUrl = linked.url; state.video = null; state.videoUpload = null; state.dirty = true; persistDraftNow(); renderEditor(); showToast('Video link added. Save your listing to keep it.');
    };
  }

  async function uploadVideo(upload) {
    state.videoUpload = upload; upload.status = 'uploading'; upload.error = ''; renderEditor();
    const data = new FormData(); data.append('file', upload.file);
    try {
      const video = await api('media/video', { method: 'POST', body: data });
      if (!video?.id || !video?.url) throw new Error('The uploaded video could not be opened. Please try again.');
      state.video = video; state.videoUrl = ''; state.videoUpload = null; state.dirty = true; persistDraftNow(); renderEditor(); showToast('Video uploaded. Save your listing to keep it.');
    } catch (error) { upload.status = 'failed'; upload.error = error.message || 'Video upload failed.'; renderEditor(); }
  }

  function textInput(field, label, required, options = '') {
    const value = state.listing[field] ?? '';
    return `<div class="ll-field wide" data-field-wrap="${field}"><div class="ll-field-head"><label for="ll-${field}">${label}${required ? ' <span class="ll-required" aria-hidden="true">*</span><span class="screen-reader-text"> required</span>' : ' <small>Optional</small>'}</label><div class="ll-field-toolbar"><button type="button" class="krev-paste" data-paste="${field}" aria-label="Paste ${label.toLowerCase()}">Paste</button><button type="button" class="krev-field-clear" data-clear="${field}" aria-label="Clear ${label.toLowerCase()}" title="Clear ${label.toLowerCase()}">×</button></div></div><div class="ll-clear-confirm" data-clear-confirm="${field}" hidden><span>Clear ${label.toLowerCase()}?</span><button type="button" data-clear-cancel>Cancel</button><button type="button" data-clear-approve="${field}">Clear</button></div><input id="ll-${field}" data-field="${field}" type="text" value="${esc(value)}" ${required ? 'required aria-required="true"' : ''} ${options}></div>`;
  }

  function textareaField(field, label, required, textareaClass) {
    return `<div class="ll-field wide" data-field-wrap="${field}"><div class="ll-field-head"><label for="ll-${field}">${label}${required ? ' <span class="ll-required" aria-hidden="true">*</span><span class="screen-reader-text"> required</span>' : ' <small>Optional</small>'}</label><div class="ll-field-toolbar"><button type="button" class="krev-paste" data-paste="${field}" aria-label="Paste ${label.toLowerCase()}">Paste</button><button type="button" class="krev-field-clear" data-clear="${field}" aria-label="Clear ${label.toLowerCase()}" title="Clear ${label.toLowerCase()}">×</button></div></div><div class="ll-clear-confirm" data-clear-confirm="${field}" hidden><span>Clear ${label.toLowerCase()}?</span><button type="button" data-clear-cancel>Cancel</button><button type="button" data-clear-approve="${field}">Clear</button></div><textarea id="ll-${field}" class="${textareaClass}" data-field="${field}" ${required ? 'required aria-required="true"' : ''}>${esc(state.listing[field])}</textarea></div>`;
  }

  function timedOfferFields(product) {
    const offer = product.timed_offer || base.timed_offer;
    const split = value => value ? [value.slice(0, 10), value.slice(11, 16)] : ['', ''];
    const [startDate, startTime] = split(offer.starts_at);
    const [endDate, endTime] = split(offer.ends_at);
    const enabled = Boolean(offer.enabled && offer.state !== 'expired');
    const scheduled = enabled && Boolean(offer.starts_at) && offer.state === 'scheduled';
    return `<div class="wide ll-timed-offer" data-field-wrap="timed_offer"><label class="ll-checkbox"><input data-timed-offer-enabled type="checkbox" ${enabled ? 'checked' : ''}><span><strong>Make this a timed offer</strong><small>Use WooCommerce’s scheduled sale dates. The offer ends at the date and time you choose.</small></span></label><div class="ll-timed-offer-fields" ${enabled ? '' : 'hidden'}><p class="ll-timed-offer-heading">Timed offer</p><fieldset><legend>Sale starts</legend><label class="ll-radio"><input data-sale-start-mode type="radio" name="sale-start-mode" value="immediately" ${scheduled ? '' : 'checked'}> Immediately</label><label class="ll-radio"><input data-sale-start-mode type="radio" name="sale-start-mode" value="scheduled" ${scheduled ? 'checked' : ''}> Schedule start</label><div class="ll-timed-date-grid" data-sale-start-fields ${scheduled ? '' : 'hidden'}><label>Start date<input data-sale-start-date type="date" value="${esc(startDate)}"></label><label>Start time<input data-sale-start-time type="time" value="${esc(startTime)}"></label></div></fieldset><fieldset><legend>Offer ends</legend><div class="ll-timed-date-grid"><label>End date<input data-sale-end-date type="date" value="${esc(endDate)}" required></label><label>End time<input data-sale-end-time type="time" value="${esc(endTime)}" required></label></div></fieldset><p class="ll-timed-timezone">Time zone: ${esc(offer.timezone || 'Site time zone')}</p></div></div>`;
  }

  function whatnotDemoFields(product) {
    const demo = product.whatnot_demo || base.whatnot_demo;
    const zones = KREVListLab.timezones || ['UTC'];
    const browserZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const defaultZone = [browserZone, KREVListLab.siteTimezone, 'UTC'].find(zone => zones.includes(zone)) || 'UTC';
    const zone = demo.timezone || defaultZone;
    const enabled = Boolean(demo.enabled);
    return `<section class="ll-section ll-whatnot-demo" data-field-wrap="whatnot_demo"><header><b>07</b><div><h2>Live Demo <small>Optional</small></h2><p>Promote this listing in a scheduled Whatnot show.</p></div></header><div class="ll-grid"><label class="wide ll-checkbox"><input data-demo-enabled type="checkbox" ${enabled ? 'checked' : ''}><span><strong>Promote this item in an upcoming Whatnot demo</strong><small>Only this listing will show the demo promotion.</small></span></label><div class="wide ll-demo-fields" ${enabled ? '' : 'hidden'}>${whatnotExpiredNotice(product, true)}<label data-field-wrap="whatnot_demo.url">Whatnot show link<input data-demo-url type="url" inputmode="url" value="${esc(demo.url)}" aria-describedby="ll-demo-url-help"><small id="ll-demo-url-help">Paste a Whatnot show link, including a show-share link. Profile and referral links are not accepted.</small></label><div class="ll-demo-date-grid"><label data-field-wrap="whatnot_demo.date">Show date<input data-demo-date type="date" value="${esc(demo.date)}"></label><label data-field-wrap="whatnot_demo.time">Start time<input data-demo-time type="time" value="${esc(demo.time)}"></label></div><label data-field-wrap="whatnot_demo.timezone">Time zone<select data-demo-timezone>${zones.map(item => `<option value="${esc(item)}" ${item === zone ? 'selected' : ''}>${esc(item)}</option>`).join('')}</select></label><p class="ll-demo-preview" data-demo-preview>KnifeRevive will show an Upcoming Demo notice on this listing and a Watch live demo button on its product page.</p></div></div></section>`;
  }

  function specificationField(attribute, product) {
	let selected = (product.attributes[attribute.taxonomy] || [])[0] || '';
	if (!selected && attribute.default_value) {
	  const defaultOption = (attribute.options || []).find(item => String(item.name).toLowerCase() === String(attribute.default_value).toLowerCase());
	  selected = defaultOption ? defaultOption.id : '';
	}
	if (attribute.taxonomy === 'product_brand') {
	  const option = (attribute.options || []).find(item => String(item.id) === String(selected));
	  const value = option ? option.name : selected;
	  const listId = `ll-brand-options-${product.id || 'new'}`;
	  const placeholder = attribute.label === 'Artist / Brand' ? 'Search or enter an artist or brand…' : 'Search or enter a brand…';
	  return `<label data-field-wrap="product_brand">${esc(attribute.label)} <small>Optional</small><input data-attribute="product_brand" list="${listId}" type="text" value="${esc(value)}" placeholder="${esc(placeholder)}" autocomplete="off"><datalist id="${listId}">${(attribute.options || []).map(item => `<option value="${esc(item.name)}"></option>`).join('')}</datalist></label>`;
	}
    if (attribute.field_type === 'decimal' || attribute.taxonomy === 'pa_msrp') {
      const option = (attribute.options || []).find(item => String(item.id) === String(selected));
      const value = option ? option.name : selected;
      return `<label data-field-wrap="${esc(attribute.taxonomy)}">${esc(attribute.label)} <small>Optional</small><input data-attribute="${esc(attribute.taxonomy)}" type="number" min="0" step="0.01" inputmode="decimal" value="${esc(value)}" placeholder="0.00"></label>`;
    }
    return `<label data-field-wrap="${esc(attribute.taxonomy)}">${esc(attribute.label)}${attribute.required ? ' <span class="ll-required" aria-hidden="true">*</span><span class="screen-reader-text"> required</span>' : ''}${select(attribute.options, selected, `data-attribute="${esc(attribute.taxonomy)}" ${attribute.required ? 'required aria-required="true"' : ''}`)}</label>`;
  }

  function refurbishmentEvidence(product, lead, summary = 'Google-qualified refurbished evidence') {
    const evidence = product.refurbishment_evidence || base.refurbishment_evidence;
    return `<details class="ll-refurb-evidence" ${evidence.restoration_confirmed || evidence.like_new_confirmed || evidence.warranty_terms ? 'open' : ''}><summary>${esc(summary)}</summary><p>${esc(lead)} A return policy is not a warranty.</p><label class="ll-checkbox"><input data-evidence="restoration_confirmed" type="checkbox" ${evidence.restoration_confirmed ? 'checked' : ''}><span>Professional restoration confirmed</span></label><label class="ll-checkbox"><input data-evidence="like_new_confirmed" type="checkbox" ${evidence.like_new_confirmed ? 'checked' : ''}><span>Like-new appearance confirmed</span></label><label>Warranty terms or policy reference<textarea data-evidence="warranty_terms">${esc(evidence.warranty_terms || '')}</textarea></label>${evidence.needs_review ? '<p class="ll-inline-warning">This duplicated listing needs its refurbishment evidence reconfirmed before publication.</p>' : ''}</details>`;
  }

  function conditionControls(product) {
    if (!state.schema.is_knife && !state.schema.is_tech) return '';
    if (state.schema.is_tech) return `<div class="ll-condition-controls wide" data-field-wrap="refurbishment_evidence"><p class="ll-classification-preview"><strong>Tech feed condition:</strong> Pre-owned is sent to Google as Used unless the complete evidence below confirms this item is genuinely refurbished.</p>${refurbishmentEvidence(product, 'Complete this only when this pre-owned tech item was professionally restored, looks like new, and has customer-visible warranty coverage.')}</div>`;
    const classification = product.sharpened ? 'Refurbished' : (product.condition_resolution?.classification_label || 'Based on actual condition');
    return `<div class="ll-condition-controls wide" data-field-wrap="sharpened"><label class="ll-checkbox"><input data-sharpened type="checkbox" ${product.sharpened ? 'checked' : ''}><span><strong>Sharpened</strong><small>Check if this knife has been sharpened. Adds the Sharp badge and places it under Refurbished on KnifeRevive.</small></span></label><p class="ll-classification-preview"><strong>KnifeRevive classification:</strong> <span data-classification-preview>${esc(classification)}</span></p>${refurbishmentEvidence(product, 'For a sharpened knife with an Item Condition other than New, Google receives Refurbished. Complete these details when supporting a Refurbished condition without checking Sharpened.', 'Additional refurbishment evidence')}</div>`;
  }

  function normalizedGtin(value) { return String(value || '').trim().replace(/[\s-]+/g, ''); }
  function validGtin(value) {
    const gtin = normalizedGtin(value);
    if (!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(gtin)) return false;
    let sum = 0;
    for (let position = 0, index = gtin.length - 2; index >= 0; index--, position++) sum += Number(gtin[index]) * (position % 2 === 0 ? 3 : 1);
    return (10 - (sum % 10)) % 10 === Number(gtin.at(-1));
  }
  function gtinName(value) { return ({ 8: 'GTIN-8', 12: 'UPC / GTIN-12', 13: 'EAN / GTIN-13', 14: 'GTIN-14' })[normalizedGtin(value).length] || 'barcode'; }
  function barcodeContext(schema) {
    if (schema.is_knife) return 'Optional. Recommended if you have the original box or packaging. Scan the barcode or enter the number printed below it.';
    if (schema.is_world_spices) return 'Optional. Look at the package for the barcode. Scan it with your camera or enter the number printed below the barcode.';
    return 'Optional. Look at the back or bottom of the item, or on its original packaging, for a UPC/EAN/GTIN barcode. Scan it or enter the number printed below it.';
  }
  function barcodeField(product, schema) {
    if (!schema.is_knife && !schema.is_tech && !schema.is_world_spices) return '';
    const value = product.global_unique_id || '';
    return `<label class="wide ll-gtin-field" data-field-wrap="global_unique_id">Barcode / GTIN <small>Optional</small><div class="ll-gtin-entry"><input data-global-unique-id type="text" inputmode="numeric" autocomplete="off" value="${esc(value)}" aria-describedby="ll-gtin-help ll-gtin-status"><button type="button" class="ll-button ll-secondary" data-scan-barcode>Scan barcode</button></div><span id="ll-gtin-help" class="ll-gtin-help">${esc(barcodeContext(schema))}</span><span class="ll-gtin-benefit">A manufacturer barcode can help Google show this item in relevant searches.</span><span id="ll-gtin-status" class="ll-gtin-status" data-gtin-status role="status" aria-live="polite"></span></label><div class="ll-barcode-scanner" data-barcode-scanner hidden role="dialog" aria-modal="true" aria-label="Scan product barcode"><div class="ll-barcode-scanner-panel"><h3>Scan product barcode</h3><p>Point the camera at the product barcode.</p><video data-barcode-video autoplay muted playsinline></video><p data-barcode-scan-status role="status" aria-live="polite"></p><button type="button" class="ll-button ll-secondary" data-cancel-barcode-scan>Cancel</button></div></div>`;
  }

  function updateGtinStatus(input, normalize = false) {
    if (!input) return;
    const value = normalizedGtin(input.value);
    if (normalize && value) input.value = value;
    const status = app.querySelector('[data-gtin-status]');
    if (!status) return;
    status.className = 'll-gtin-status';
    if (!value) status.textContent = '';
    else if (validGtin(value)) { status.textContent = `✓ ${gtinName(value)}`; status.classList.add('is-valid'); }
    else { status.textContent = '⚠ This barcode number does not have a valid GTIN check digit. Check the number printed below the barcode.'; status.classList.add('is-invalid'); }
  }

  function scannerStatus(message) { const status = app.querySelector('[data-barcode-scan-status]'); if (status) status.textContent = message; }
  function scannerError(error) {
    if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') return 'Camera access was not allowed. You can type the barcode instead.';
    if (error?.name === 'NotFoundError' || error?.name === 'OverconstrainedError') return 'No usable camera was found. You can type the barcode instead.';
    return 'The camera could not start. You can type the barcode instead.';
  }
  function stopBarcodeScan({ focus = true } = {}) {
    clearInterval(barcodeScanner.timer); barcodeScanner.timer = 0;
    try { barcodeScanner.controls?.stop?.(); } catch (_) {}
    try { barcodeScanner.reader?.reset?.(); } catch (_) {}
    barcodeScanner.stream?.getTracks?.().forEach(track => track.stop());
    const video = app.querySelector('[data-barcode-video]');
    if (video) { try { video.pause(); } catch (_) {} video.srcObject = null; }
    const panel = app.querySelector('[data-barcode-scanner]');
    if (panel) { panel.setAttribute('hidden', ''); panel.onkeydown = null; }
    const input = barcodeScanner.focus;
    Object.assign(barcodeScanner, { active: false, stream: null, timer: 0, controls: null, reader: null, focus: null, detecting: false });
    if (focus) input?.focus();
  }
  function acceptBarcode(value) {
    const input = app.querySelector('[data-global-unique-id]'), gtin = normalizedGtin(value);
    if (!validGtin(gtin)) { scannerStatus('Barcode was not recognized. Try again or type it manually.'); return; }
    input.value = gtin; state.listing.global_unique_id = gtin; state.dirty = true; updateGtinStatus(input, true); scheduleDraftSave();
    if (navigator.vibrate) navigator.vibrate(80);
    stopBarcodeScan();
  }
  async function nativeBarcodeDetector() {
    if (!('BarcodeDetector' in window)) return null;
    let formats = barcodeFormats;
    try {
      if (typeof window.BarcodeDetector.getSupportedFormats === 'function') {
        const supported = await window.BarcodeDetector.getSupportedFormats();
        formats = barcodeFormats.filter(format => supported.includes(format));
      }
      return formats.length ? new window.BarcodeDetector({ formats }) : null;
    } catch (_) { return null; }
  }
  const barcodeCameraConstraints = () => ({
    facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 }, focusMode: { ideal: 'continuous' }
  });
  async function startZxingScan(video) {
    const zx = window.ZXingBrowser;
    if (!zx?.BrowserMultiFormatReader || !zx?.BarcodeFormat) throw new Error('Barcode scanner unavailable');
    barcodeScanner.reader = new zx.BrowserMultiFormatReader();
    barcodeScanner.reader.possibleFormats = [zx.BarcodeFormat.EAN_13, zx.BarcodeFormat.EAN_8, zx.BarcodeFormat.UPC_A, zx.BarcodeFormat.UPC_E, zx.BarcodeFormat.ITF];
    scannerStatus('Looking for a barcode… Hold it steady inside the frame.');
    barcodeScanner.controls = await barcodeScanner.reader.decodeFromConstraints({ video: barcodeCameraConstraints(), audio: false }, video, result => { if (result) acceptBarcode(result.getText()); });
  }
  async function startBarcodeScan(input) {
    if (barcodeScanner.active) return;
    if (!navigator.mediaDevices?.getUserMedia) { updateGtinStatus(input); showToast('Camera scanning is unavailable here. Type the barcode instead.'); return; }
    const panel = app.querySelector('[data-barcode-scanner]'), video = app.querySelector('[data-barcode-video]');
    barcodeScanner.active = true; barcodeScanner.focus = input; panel.hidden = false; scannerStatus('Starting camera…');
    panel.onkeydown = event => {
      if (event.key === 'Escape') { event.preventDefault(); stopBarcodeScan(); return; }
      if (event.key !== 'Tab') return;
      const focusable = [...panel.querySelectorAll('button:not([disabled]), [href], input:not([disabled])')];
      if (!focusable.length) return;
      const first = focusable[0], last = focusable[focusable.length - 1];
      if ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last)) { event.preventDefault(); (event.shiftKey ? last : first).focus(); }
    };
    panel.querySelector('[data-cancel-barcode-scan]')?.focus();
    try {
      const detector = await nativeBarcodeDetector();
      if (detector) {
        barcodeScanner.stream = await navigator.mediaDevices.getUserMedia({ video: barcodeCameraConstraints(), audio: false });
        video.srcObject = barcodeScanner.stream; await video.play(); scannerStatus('Looking for a barcode…');
        let nativeAttempts = 0;
        barcodeScanner.timer = setInterval(async () => {
          if (barcodeScanner.detecting || !barcodeScanner.active) return;
          barcodeScanner.detecting = true;
          try {
            const result = await detector.detect(video);
            if (result?.[0]?.rawValue) { acceptBarcode(result[0].rawValue); return; }
            if (++nativeAttempts >= 20) {
              clearInterval(barcodeScanner.timer); barcodeScanner.timer = 0;
              barcodeScanner.stream?.getTracks?.().forEach(track => track.stop()); barcodeScanner.stream = null; video.srcObject = null;
              await startZxingScan(video);
            }
          } catch (_) {} finally { barcodeScanner.detecting = false; }
        }, 250);
        return;
      }
      await startZxingScan(video);
    } catch (error) {
      scannerStatus(scannerError(error));
      barcodeScanner.active = false;
      try { barcodeScanner.controls?.stop?.(); } catch (_) {}
      barcodeScanner.stream?.getTracks?.().forEach(track => track.stop()); barcodeScanner.stream = null;
    }
  }

  function specificationControls(product, currentSchema) {
    if (!currentSchema.attributes.length) return (currentSchema.is_knife || currentSchema.is_tech ? conditionControls(product) : '<p>Select a category to reveal specifications.</p>') + barcodeField(product, currentSchema);
    const hasCondition = currentSchema.attributes.some(attribute => attribute.taxonomy === 'pa_condition');
    return currentSchema.attributes.map(attribute => specificationField(attribute, product) + (attribute.taxonomy === 'pa_condition' ? conditionControls(product) : '')).join('') + ((currentSchema.is_knife || currentSchema.is_tech) && !hasCondition ? conditionControls(product) : '') + barcodeField(product, currentSchema);
  }

  function categorySelection(product = state.listing) {
    const chosen = (product.categories || []).map(id => state.schema.categories.find(row => Number(row.id) === Number(id))).filter(Boolean);
    const child = chosen.find(row => row.is_tech_child);
    return { parent: Number(product.category_id) || (child ? Number(state.schema.tech_category_id) : Number(product.categories?.[0])) || 0,
      child: Number(product.subcategory_id) || Number(child?.id) || 0 };
  }
  function subcategoryField(product, currentSchema) {
    const selection = categorySelection(product);
    if (selection.parent !== Number(currentSchema.tech_category_id) || !selection.parent) return '';
    return `<label class="wide" data-field-wrap="subcategory_id">Tech subcategory ${select(currentSchema.tech_subcategories || [], selection.child, 'data-field="subcategory_id"')}<small>Choose the closest Google product category. This selection is used in the Google product feed.</small></label>`;
  }

  function fields() {
    const product = state.listing, currentSchema = state.schema;
    return `<section class="ll-section"><header><b>03</b><div><h2>Listing details</h2><p>Help buyers understand exactly what you are selling. Fields marked required must be completed before publishing.</p></div></header><div class="ll-grid">${textInput('title', 'Listing title', true, 'maxlength="120" spellcheck="true" autocapitalize="sentences"')}${textInput('subtitle', 'Subtitle', false, 'spellcheck="true" autocapitalize="sentences"')}<label class="wide" data-field-wrap="category_id">Category <span class="ll-required" aria-hidden="true">*</span><span class="screen-reader-text"> required</span>${select(currentSchema.categories.filter(row => !row.is_tech_child), categorySelection(product).parent, 'data-field="category_id" required aria-required="true"')}</label>${subcategoryField(product, currentSchema)}${textareaField('description', 'Description', true, 'krev-description')}${textareaField('short_description', 'Short description', false, 'krev-short-description')}</div></section><section class="ll-section ll-specifications ${state.schemaLoading ? 'is-loading' : ''}" data-section="attributes"><header><b>04</b><div><h2>Specifications</h2><p>Options update to match your category.</p></div></header><div class="ll-grid ll-schema-fields" aria-busy="${state.schemaLoading ? 'true' : 'false'}">${state.schemaLoading ? '<p class="ll-schema-loading"><span class="ll-spinner" aria-hidden="true"></span>Loading specifications…</p>' : specificationControls(product, currentSchema)}</div></section><section class="ll-section"><header><b>05</b><div><h2>Price & inventory</h2><p>Set a clear price and available quantity.</p></div></header><div class="ll-grid ll-price-grid"><label data-field-wrap="regular_price">Regular price <small>Optional</small><input data-field="regular_price" type="text" inputmode="decimal" value="${esc(product.regular_price)}"></label><label data-field-wrap="sale_price">Sale price <small>Optional</small><input data-field="sale_price" type="text" inputmode="decimal" value="${esc(product.sale_price)}"><span class="ll-inline-warning" data-sale-warning hidden>Sale price should not exceed the regular price.</span></label>${timedOfferFields(product)}<label data-field-wrap="quantity">Quantity <span class="ll-required" aria-hidden="true">*</span><span class="screen-reader-text"> required</span><input data-field="quantity" type="text" inputmode="numeric" value="${esc(product.quantity)}" required aria-required="true"></label><label>SKU <small>Optional</small><input data-field="sku" type="text" value="${esc(product.sku)}"></label></div></section><section class="ll-section"><header><b>06</b><div><h2>Shipping & returns</h2><p>Choose policies that apply to this listing.</p></div></header><div class="ll-grid"><label class="wide" data-field-wrap="shipping_policy_id">Shipping policy <span class="ll-required" aria-hidden="true">*</span><span class="screen-reader-text"> required</span>${select(currentSchema.shipping_policies, product.shipping_policy, 'data-field="shipping_policy_id" required aria-required="true"')}</label><label>Weight <small>Optional</small><input data-field="weight" type="text" inputmode="decimal" value="${esc(product.weight)}"></label><label data-field-wrap="return_policy_id">Return policy <small>Optional</small>${select(currentSchema.return_policies, product.return_policy, 'data-field="return_policy_id"')}</label><div class="wide ll-dimensions"><span>Package dimensions <small>Optional</small></span><label>Length<input data-field="length" type="text" inputmode="decimal" value="${esc(product.dimensions.length)}"></label><label>Width<input data-field="width" type="text" inputmode="decimal" value="${esc(product.dimensions.width)}"></label><label>Height<input data-field="height" type="text" inputmode="decimal" value="${esc(product.dimensions.height)}"></label></div></div></section>`;
  }

  function requiredProgress() {
    const requiredAttributes = state.schema.attributes.filter(attribute => attribute.required);
    const completeAttributes = requiredAttributes.map(attribute => state.listing.attributes[attribute.taxonomy]?.[0] || attribute.default_value);
    return {
      completed: [state.listing.title.trim(), state.images.length, state.listing.description.trim(), state.listing.categories[0], state.listing.quantity !== '' && state.listing.quantity !== null, state.listing.shipping_policy, ...completeAttributes].filter(Boolean).length,
      total: 6 + requiredAttributes.length
    };
  }
  const uploadsBusy = () => state.uploads.some(upload => ['queued', 'uploading'].includes(upload.status)) || state.videoUpload?.status === 'uploading';

  async function editor(id = 0, copy = false) {
    stopBarcodeScan({ focus: false });
    app.innerHTML = frame('<p class="ll-loading">Opening your listing…</p>');
    try {
      let product = { ...base, dimensions: { ...base.dimensions }, client_uuid: crypto.randomUUID?.() || `${Date.now()}-${Math.random()}` };
      if (id) product = await api(`listings/${id}`);
      if (copy) product = { ...product, id: 0, duplicated_from_id: id, title: `${product.title || 'Untitled listing'} — Copy`, client_uuid: crypto.randomUUID?.() || `${Date.now()}-${Math.random()}`, modified_date: '', whatnot_demo: { ...base.whatnot_demo }, refurbishment_evidence: { ...(product.refurbishment_evidence || {}), restoration_confirmed: false, like_new_confirmed: false, needs_review: true } };
      state.listing = product;
      state.images = [product.featured_image, ...(product.gallery || [])].filter(Boolean);
      state.video = product.video || null; state.videoUrl = product.video_url || ''; state.videoUpload = null;
      state.uploads = []; state.dirty = false;
      state.draftKey = draftKeyFor(product);
      state.restoreCandidate = findRecoveryCandidate(product, copy);
      await schema(product.categories[0]);
      renderEditor();
    } catch (error) { app.innerHTML = frame(`<p class="ll-message">${esc(error.message || 'Unable to open listing.')}</p>`); }
  }

  function renderEditor() {
    const { completed: done, total } = requiredProgress(), editing = Boolean(state.listing.id), backTab = editing && state.listing.status !== 'publish' ? 'drafts' : 'active';
    const publishDisabled = uploadsBusy();
    const buttons = editing ? (state.listing.status === 'publish' ? '<button class="ll-button ll-secondary" data-close type="button">Close</button><button class="ll-button ll-primary" data-save="save" type="button">Save</button>' : `<button class="ll-button ll-secondary" data-close type="button">Close</button><button class="ll-button ll-secondary" data-save="draft" type="button">Save draft</button><button class="ll-button ll-primary" data-save="publish" type="button" ${publishDisabled ? 'disabled' : ''}>Publish</button>`) : `<button class="ll-button ll-secondary" data-save="draft" type="button">Save draft</button><button class="ll-button ll-primary" data-save="publish" type="button" ${publishDisabled ? 'disabled' : ''}>Publish listing</button>`;
    const recovery = state.restoreCandidate ? `<aside class="ll-recovery" data-recovery><div><strong>Restore your unsaved listing?</strong><span>A newer draft is saved on this device.</span></div><button type="button" data-restore>Restore</button><button type="button" data-discard-draft>Discard</button></aside>` : '';
    app.innerHTML = frame(`<section class="ll-editor-head"><a href="${account('tab=' + backTab)}">← My Listings</a><p>${editing ? 'EDIT LISTING' : 'NEW LISTING'}</p><h2>${editing ? esc(state.listing.title || 'Untitled listing') : 'Create a listing'}</h2></section>${recovery}<section class="ll-progress" aria-label="Listing progress"><span>Listing progress</span><strong data-progress-label>${done === total ? 'Ready to publish' : `${done} of ${total} required items complete`}</strong></section><section class="ll-validation" data-validation role="alert" tabindex="-1" hidden></section><form id="ll-form" class="krev-listlab-editor" novalidate>${photoSection()}${videoSection()}${fields()}${whatnotDemoFields(state.listing)}</form><footer class="ll-save"><div><b>${done === total ? 'Ready to publish' : `${done} of ${total} required items complete`}</b><small>${uploadsBusy() ? 'Wait for uploads to finish' : done === total ? 'All required items are complete' : 'Complete required items to publish'}</small></div><div>${buttons}</div></footer>`);
    const regularPriceLabel = app.querySelector('[data-field-wrap="regular_price"]');
    const regularPriceMark = regularPriceLabel?.querySelector(':scope > em');
    if (regularPriceMark) { const optional = document.createElement('small'); optional.textContent = 'Optional'; regularPriceMark.replaceWith(optional); }
    bindEditor(); updateProgress(); updateSaleWarning(); setupKeyboardViewport();
  }

  function updateProgress() {
    const { completed: done, total } = requiredProgress(), busy = uploadsBusy();
    const top = app.querySelector('[data-progress-label]'), label = app.querySelector('.ll-save b'), hint = app.querySelector('.ll-save small'), publish = app.querySelector('[data-save="publish"]');
    const text = done === total ? 'Ready to publish' : `${done} of ${total} required items complete`;
    if (top) top.textContent = text;
    if (label) label.textContent = text;
    if (hint) hint.textContent = busy ? 'Wait for uploads to finish' : done === total ? 'All required items are complete' : 'Complete required items to publish';
    app.querySelectorAll('[data-save]').forEach(button => { button.disabled = busy; });
  }

  function listingData() {
    const values = {}, attributes = {};
    app.querySelectorAll('[data-field]').forEach(field => { values[field.dataset.field] = field.value; });
    app.querySelectorAll('[data-attribute]').forEach(field => {
      const value = field.dataset.customValue
        ? `${customAttributePrefix}${field.dataset.customValue}`
        : field.value;
      if (value) attributes[field.dataset.attribute] = [value];
    });
    const payload = { title: values.title ?? state.listing.title, subtitle: values.subtitle ?? state.listing.subtitle, description: values.description ?? state.listing.description, short_description: values.short_description ?? state.listing.short_description, category_id: Number(values.category_id ?? categorySelection().parent) || 0, subcategory_id: Number(values.subcategory_id ?? categorySelection().child) || 0, attributes: Object.keys(attributes).length ? attributes : state.listing.attributes, regular_price: values.regular_price ?? state.listing.regular_price, sale_price: values.sale_price ?? state.listing.sale_price, quantity: values.quantity ?? state.listing.quantity, sku: values.sku ?? state.listing.sku, shipping_policy_id: Number(values.shipping_policy_id ?? state.listing.shipping_policy) || 0, return_policy_id: Number(values.return_policy_id ?? state.listing.return_policy) || 0, weight: values.weight ?? state.listing.weight, length: values.length ?? state.listing.dimensions.length, width: values.width ?? state.listing.dimensions.width, height: values.height ?? state.listing.dimensions.height, featured_image_id: state.images[0]?.id || 0, gallery_ids: state.images.slice(1).map(item => item.id), client_uuid: state.listing.client_uuid, duplicated_from_id: state.listing.duplicated_from_id || 0 };
    const timed = app.querySelector('[data-timed-offer-enabled]');
    payload.video_id = Number(state.video?.id) || 0;
    payload.video_url = state.videoUrl;
    if (timed) {
      payload.timed_offer_enabled = timed.checked;
      payload.sale_start_mode = app.querySelector('[data-sale-start-mode]:checked')?.value || 'immediately';
      ['sale_start_date', 'sale_start_time', 'sale_end_date', 'sale_end_time'].forEach(key => { payload[key] = app.querySelector(`[data-${key.replaceAll('_', '-')}]`)?.value || ''; });
    }
    const demoEnabled = app.querySelector('[data-demo-enabled]');
    if (demoEnabled) payload.whatnot_demo = { enabled: demoEnabled.checked, url: app.querySelector('[data-demo-url]')?.value.trim() || '', date: app.querySelector('[data-demo-date]')?.value || '', time: app.querySelector('[data-demo-time]')?.value || '', timezone: app.querySelector('[data-demo-timezone]')?.value || '' };
    const gtin = app.querySelector('[data-global-unique-id]');
    if (gtin) payload.global_unique_id = gtin.value;
    if (state.schema.is_knife || state.schema.is_tech) { if (state.schema.is_knife) payload.sharpened = Boolean(app.querySelector('[data-sharpened]')?.checked); payload.refurbishment_evidence = { restoration_confirmed: Boolean(app.querySelector('[data-evidence="restoration_confirmed"]')?.checked), like_new_confirmed: Boolean(app.querySelector('[data-evidence="like_new_confirmed"]')?.checked), warranty_terms: app.querySelector('[data-evidence="warranty_terms"]')?.value || '' }; }
    return payload;
  }

  function syncField(field) {
    const key = field.dataset.field;
    if (key === 'category_id' || key === 'subcategory_id') return;
    if (['length', 'width', 'height'].includes(key)) state.listing.dimensions[key] = field.value;
    else if (key === 'shipping_policy_id') state.listing.shipping_policy = Number(field.value) || 0;
    else if (key === 'return_policy_id') state.listing.return_policy = Number(field.value) || 0;
    else state.listing[key] = field.value;
    state.dirty = true; clearFieldValidation(key);
    updateProgress(); updateSaleWarning(); scheduleDraftSave();
  }

  // Keep native select values and change handlers as the source of truth.
  function searchableSelect(field, label) {
    if (field.dataset.searchable) return;
    field.dataset.searchable = 'true';
    const root = document.createElement('span'); root.className = 'll-search-select';
    const input = document.createElement('input'); input.type = 'text'; input.autocomplete = 'off';
    input.setAttribute('role', 'combobox'); input.setAttribute('aria-label', label);
    input.setAttribute('aria-autocomplete', 'list'); input.setAttribute('aria-expanded', 'false');
    input.placeholder = `Search ${label.toLowerCase()}…`;
    const button = document.createElement('button'); button.type = 'button'; button.className = 'll-search-toggle';
    button.textContent = '▾'; button.setAttribute('aria-label', `Show ${label.toLowerCase()} options`);
    const list = document.createElement('span'); list.className = 'll-search-options'; list.hidden = true;
    list.id = `ll-options-${field.dataset.field || field.dataset.attribute}`; list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', `${label} options`); input.setAttribute('aria-controls', list.id);
    const status = document.createElement('span'); status.className = 'screen-reader-text'; status.setAttribute('role', 'status');
    field.before(root); root.append(input, button, list, status, field); field.hidden = true; field.tabIndex = -1;
    let matches = [], active = -1;
    const selectedLabel = () => field.value ? field.selectedOptions[0]?.textContent || '' : '';
    const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); input.value = selectedLabel(); };
    const highlight = index => {
      active = index;
      [...list.querySelectorAll('[role="option"]')].forEach((option, i) => { option.classList.toggle('is-active', i === active); });
      const option = list.querySelectorAll('[role="option"]')[active];
      if (option) { input.setAttribute('aria-activedescendant', option.id); option.scrollIntoView?.({ block: 'nearest' }); }
      else input.removeAttribute('aria-activedescendant');
    };
    const choose = index => {
      if (field.disabled || !matches[index]) return;
      field.value = matches[index].value;
      // Attribute custom-value prompts and category schema refresh still run normally.
      field.dispatchEvent(new Event('change', { bubbles: true }));
      close(); if (input.isConnected) input.focus();
    };
    const open = query => {
      if (field.disabled) return;
      const words = query.toLocaleLowerCase().trim().split(/\s+/).filter(Boolean);
      matches = [...field.options].filter(option => !option.disabled && (!words.length || words.every(word => option.textContent.toLocaleLowerCase().includes(word)) || option.value === '__custom__'));
      list.replaceChildren(); active = -1;
      matches.forEach((option, index) => {
        const row = document.createElement('span'); row.setAttribute('role', 'option'); row.id = `${list.id}-${index}`;
        row.setAttribute('aria-selected', String(option.value === field.value)); row.textContent = option.textContent;
        row.onmousedown = event => event.preventDefault(); row.onclick = () => choose(index); list.append(row);
      });
      if (!matches.length) { const empty = document.createElement('span'); empty.className = 'll-search-empty'; empty.textContent = 'No matching options'; list.append(empty); }
      list.hidden = false; input.setAttribute('aria-expanded', 'true'); input.removeAttribute('aria-activedescendant');
      status.textContent = `${matches.length} options available.`;
    };
    input.value = selectedLabel(); input.disabled = button.disabled = field.disabled;
    input.oninput = () => open(input.value);
    input.onclick = () => open(input.value === selectedLabel() ? '' : input.value);
    input.onkeydown = event => {
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault(); if (list.hidden) open('');
        if (matches.length) highlight(event.key === 'ArrowDown' ? (active + 1) % matches.length : (active <= 0 ? matches.length - 1 : active - 1));
      } else if (event.key === 'Enter' && !list.hidden) { event.preventDefault(); if (active >= 0) choose(active); else if (matches.length === 1) choose(0); }
      else if (event.key === 'Escape') { event.preventDefault(); close(); }
    };
    button.onmousedown = event => event.preventDefault();
    button.onclick = () => { if (list.hidden) { input.focus(); open(''); } else close(); };
    root.addEventListener('focusout', event => { if (!root.contains(event.relatedTarget)) close(); });
  }

  function setupSearchableSelects() {
    const subcategory = app.querySelector('select[data-field="subcategory_id"]');
    if (subcategory) searchableSelect(subcategory, 'Tech subcategory');
    const model = app.querySelector('select[data-attribute="pa_model-number"]');
    if (model) searchableSelect(model, 'Model');
  }

  function bindEditor() {
    const form = app.querySelector('#ll-form');
    const close = () => { const tab = state.listing.status === 'publish' ? 'active' : 'drafts'; stopBarcodeScan({ focus: false }); persistDraftNow(); if (!state.dirty || confirm('Discard changes?\n\nYour unsaved changes are saved on this device until you discard them.')) location.href = account('tab=' + tab); };
    app.querySelectorAll('[data-close]').forEach(button => { button.onclick = close; });
    form.querySelectorAll('[data-field]').forEach(field => {
      field.addEventListener('input', () => syncField(field));
      ['click', 'keyup', 'select'].forEach(type => field.addEventListener(type, () => rememberSelection(field)));
    });
    form.querySelector('[data-files]').onchange = event => upload([...event.target.files]);
    setupVideoManager();
    setupClipboardActions(); setupClearActions(); setupImageManager(); setupDraftRecovery(); setupValidationNavigation();
    const category = form.querySelector('[data-field="category_id"]');
    category.onchange = () => changeCategory(category);
    const subcategory = form.querySelector('[data-field="subcategory_id"]');
    if (subcategory) subcategory.onchange = () => changeCategory(subcategory);
    setupSearchableSelects();
    const gtin = form.querySelector('[data-global-unique-id]');
    if (gtin) {
      updateGtinStatus(gtin);
      gtin.addEventListener('input', () => { state.listing.global_unique_id = gtin.value; state.dirty = true; updateGtinStatus(gtin); scheduleDraftSave(); });
      gtin.addEventListener('blur', () => { if (validGtin(gtin.value)) { gtin.value = normalizedGtin(gtin.value); state.listing.global_unique_id = gtin.value; updateGtinStatus(gtin); } });
      form.querySelector('[data-scan-barcode]').onclick = () => startBarcodeScan(gtin);
      form.querySelector('[data-cancel-barcode-scan]').onclick = () => stopBarcodeScan();
    }
    app.querySelectorAll('[data-save]').forEach(button => { button.onclick = () => saveListing(button); });
    const sharpened = form.querySelector('[data-sharpened]');
    if (sharpened) sharpened.onchange = () => { state.listing.sharpened = sharpened.checked; const preview = form.querySelector('[data-classification-preview]'); if (preview) preview.textContent = sharpened.checked ? 'Refurbished' : 'Based on actual condition'; state.dirty = true; scheduleDraftSave(); };
    form.querySelectorAll('[data-evidence]').forEach(field => { field.addEventListener('input', () => { const key = field.dataset.evidence; state.listing.refurbishment_evidence[key] = field.type === 'checkbox' ? field.checked : field.value; state.dirty = true; scheduleDraftSave(); }); });
    const timed = form.querySelector('[data-timed-offer-enabled]');
    if (timed) {
      const controls = form.querySelector('.ll-timed-offer-fields');
      const startFields = form.querySelector('[data-sale-start-fields]');
      timed.onchange = () => { controls.hidden = !timed.checked; state.dirty = true; scheduleDraftSave(); };
      form.querySelectorAll('[data-sale-start-mode]').forEach(control => { control.onchange = () => { startFields.hidden = control.value !== 'scheduled' || !control.checked; state.dirty = true; scheduleDraftSave(); }; });
      form.querySelectorAll('[data-sale-start-date], [data-sale-start-time], [data-sale-end-date], [data-sale-end-time]').forEach(control => { control.onchange = () => { state.dirty = true; scheduleDraftSave(); }; });
    }
    const demoToggle = form.querySelector('[data-demo-enabled]');
    if (demoToggle) {
      const demoFields = form.querySelector('.ll-demo-fields');
      demoToggle.onchange = () => { demoFields.hidden = !demoToggle.checked; state.listing.whatnot_demo = { ...(state.listing.whatnot_demo || base.whatnot_demo), enabled: demoToggle.checked }; state.dirty = true; scheduleDraftSave(); };
      form.querySelectorAll('[data-demo-url], [data-demo-date], [data-demo-time], [data-demo-timezone]').forEach(control => { control.addEventListener('input', () => { state.listing.whatnot_demo = listingData().whatnot_demo; state.dirty = true; const key = ['url', 'date', 'time', 'timezone'].find(name => control.hasAttribute(`data-demo-${name}`)); clearFieldValidation('whatnot_demo.' + key); scheduleDraftSave(); }); });
    }
  }

  function rememberSelection(field) {
    if (typeof field.selectionStart === 'number') { field.dataset.selectionStart = field.selectionStart; field.dataset.selectionEnd = field.selectionEnd; }
  }

  function setupClipboardActions() {
    app.querySelectorAll('[data-paste]').forEach(button => { button.onclick = async () => {
      const field = app.querySelector(`[data-field="${button.dataset.paste}"]`);
      try {
        if (!navigator.clipboard?.readText) throw new Error('Clipboard API unavailable');
        const text = await navigator.clipboard.readText();
        if (!text) { showToast('Clipboard is empty.'); field.focus(); return; }
        insertTextIntoField(field, text); field.dispatchEvent(new Event('input', { bubbles: true })); field.focus(); showToast('Text pasted.'); ensureFieldVisible(field);
      } catch (_) { field.focus(); showToast('Tap and hold in the field, then choose Paste.'); }
    }; });
  }

  function insertTextIntoField(field, text) {
    const hasRememberedSelection = field.dataset.selectionStart !== undefined;
    let start = hasRememberedSelection ? Number(field.dataset.selectionStart) : field.value.length;
    let end = hasRememberedSelection ? Number(field.dataset.selectionEnd) : start;
    start = Math.max(0, Math.min(start, field.value.length)); end = Math.max(start, Math.min(end, field.value.length));
    let insertion = text;
    if (!hasRememberedSelection && field.value) {
      const separator = field.tagName === 'TEXTAREA' ? (field.value.endsWith('\n') ? '' : '\n\n') : (/\s$/.test(field.value) ? '' : ' ');
      insertion = separator + text;
    }
    field.setRangeText(insertion, start, end, 'end'); rememberSelection(field);
  }

  function setupClearActions() {
    app.querySelectorAll('[data-clear]').forEach(button => { button.onclick = () => {
      const confirmation = app.querySelector(`[data-clear-confirm="${button.dataset.clear}"]`);
      app.querySelectorAll('[data-clear-confirm]').forEach(other => { if (other !== confirmation) other.hidden = true; });
      confirmation.hidden = false;
      confirmation.onkeydown = event => { if (event.key === 'Escape') { event.preventDefault(); confirmation.hidden = true; button.focus(); } };
      confirmation.querySelector('[data-clear-cancel]').focus();
    }; });
    app.querySelectorAll('[data-clear-cancel]').forEach(button => { button.onclick = () => { const confirmation = button.closest('[data-clear-confirm]'); const key = confirmation.dataset.clearConfirm; confirmation.hidden = true; app.querySelector(`[data-clear="${key}"]`)?.focus(); }; });
    app.querySelectorAll('[data-clear-approve]').forEach(button => { button.onclick = () => {
      const key = button.dataset.clearApprove, field = app.querySelector(`[data-field="${key}"]`), previous = field.value;
      button.closest('[data-clear-confirm]').hidden = true;
      if (!previous) { field.focus(); return; }
      field.value = ''; field.dispatchEvent(new Event('input', { bubbles: true })); field.focus();
      state.clearUndo = { key, previous }; clearTimeout(state.clearUndoTimer);
      showToast(`${fieldLabel(key)} cleared.`, 'Undo', undoClear, 8000);
      state.clearUndoTimer = setTimeout(() => { state.clearUndo = null; }, 8000);
    }; });
  }

  function undoClear() {
    if (!state.clearUndo) return;
    const field = app.querySelector(`[data-field="${state.clearUndo.key}"]`);
    if (field) { field.value = state.clearUndo.previous; field.dispatchEvent(new Event('input', { bubbles: true })); field.focus(); ensureFieldVisible(field); }
    state.clearUndo = null; clearTimeout(state.clearUndoTimer);
  }

  function fieldLabel(key) {
    return ({ title: 'Listing title', subtitle: 'Subtitle', description: 'Description', short_description: 'Short description' })[key] || 'Field';
  }

  function setupImageManager() {
    app.querySelectorAll('[data-remove]').forEach(button => { button.onclick = () => { if (confirm('Remove this photo from the listing?')) { state.images.splice(Number(button.dataset.remove), 1); markDirtyAndRender(); } }; });
    app.querySelectorAll('[data-cover]').forEach(button => { button.onclick = () => { const index = Number(button.dataset.cover); state.images.unshift(state.images.splice(index, 1)[0]); markDirtyAndRender(); }; });
    app.querySelectorAll('[data-move]').forEach(button => { button.onclick = () => { const from = Number(button.dataset.index), to = button.dataset.move === 'left' ? from - 1 : from + 1; moveImage(from, to); }; });
    app.querySelectorAll('[data-retry]').forEach(button => { button.onclick = () => { const uploadState = state.uploads.find(item => item.key === button.dataset.retry); if (uploadState) uploadOne(uploadState); }; });
    app.querySelectorAll('[data-cancel-upload]').forEach(button => { button.onclick = () => { const index = state.uploads.findIndex(item => item.key === button.dataset.cancelUpload); if (index >= 0) { URL.revokeObjectURL(state.uploads[index].preview); state.uploads.splice(index, 1); renderEditor(); } }; });
    app.querySelectorAll('[data-image]').forEach(tile => {
      tile.ondragstart = event => { event.dataTransfer.setData('text/plain', tile.dataset.image); };
      tile.ondragover = event => event.preventDefault();
      tile.ondrop = event => { event.preventDefault(); moveImage(Number(event.dataTransfer.getData('text/plain')), Number(tile.dataset.image)); };
    });
  }

  function moveImage(from, to) {
    if (from < 0 || to < 0 || from >= state.images.length || to >= state.images.length || from === to) return;
    state.images.splice(to, 0, state.images.splice(from, 1)[0]); markDirtyAndRender();
  }

  function markDirtyAndRender() { state.dirty = true; persistDraftNow(); renderEditor(); }

  async function upload(files) {
    const remaining = 10 - state.images.length - state.uploads.length;
    if (remaining <= 0) { showToast('A listing can have up to 10 photos.'); return; }
    const accepted = files.slice(0, remaining);
    if (accepted.length < files.length) showToast('Only the first available photos were added. The limit is 10.');
    for (const file of accepted) {
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { showToast(`${file.name} must be JPEG, PNG, or WebP.`); continue; }
      const uploadState = { key: `${Date.now()}-${Math.random()}`, file, preview: URL.createObjectURL(file), status: 'queued' };
      state.uploads.push(uploadState);
    }
    renderEditor();
    state.uploads.filter(item => item.status === 'queued').forEach(item => uploadOne(item));
  }

  async function uploadOne(uploadState) {
    uploadState.status = 'uploading'; renderEditor();
    try {
      const body = new FormData(); body.append('file', uploadState.file);
      const uploaded = await api('media', { method: 'POST', body });
      if (!state.images.some(item => Number(item.id) === Number(uploaded.id))) state.images.push(uploaded);
      const index = state.uploads.indexOf(uploadState);
      if (index >= 0) state.uploads.splice(index, 1);
      URL.revokeObjectURL(uploadState.preview); state.dirty = true; persistDraftNow(); renderEditor(); showToast('Image uploaded.');
    } catch (error) { uploadState.status = 'failed'; uploadState.error = error.message || 'Upload failed'; renderEditor(); showToast('Upload failed. Tap Retry to try again.'); }
  }

  async function changeCategory(field) {
    if (state.schemaLoading) return;
    stopBarcodeScan({ focus: false });
    const previousCategory = state.listing.categories[0] || 0, previousSelection = categorySelection(), previousSchema = state.schema;
    const isSubcategory = field.dataset.field === 'subcategory_id';
    const parent = isSubcategory ? previousSelection.parent : Number(field.value) || 0;
    const child = isSubcategory ? Number(field.value) || 0 : 0;
    const nextCategory = child || parent;
    state.listing.category_id = parent; state.listing.subcategory_id = child;
    const previousAttributes = { ...state.listing.attributes };
    state.listing.categories = [parent, child].filter(Boolean);
    state.schemaLoading = true; state.dirty = true; scheduleDraftSave();
    app.querySelectorAll('[data-field="category_id"], [data-field="subcategory_id"]').forEach(control => { control.disabled = true; });
    app.querySelectorAll('.ll-search-select input, .ll-search-toggle').forEach(control => { control.disabled = true; });
    const spec = app.querySelector('.ll-specifications');
    if (spec) { spec.classList.add('is-loading'); const grid = spec.querySelector('.ll-schema-fields'); if (grid) { grid.setAttribute('aria-busy', 'true'); grid.innerHTML = '<p class="ll-schema-loading"><span class="ll-spinner" aria-hidden="true"></span>Loading specifications…</p>'; } }
    try {
      await schema(nextCategory);
      const valid = {};
      state.schema.attributes.forEach(attribute => {
        const oldValue = previousAttributes[attribute.taxonomy]?.[0];
        if (oldValue && (!/^\d+$/.test(String(oldValue)) || attribute.options.some(option => String(option.id) === String(oldValue)))) valid[attribute.taxonomy] = [oldValue];
      });
      state.listing.attributes = valid; state.schemaLoading = false;
      if (Object.keys(previousAttributes).length > Object.keys(valid).length && previousCategory) showToast('Specifications not available in the new category were removed.');
      renderEditor();
    } catch (error) {
      state.listing.categories = [previousSelection.parent, previousSelection.child].filter(Boolean); state.listing.category_id = previousSelection.parent; state.listing.subcategory_id = previousSelection.child; state.schema = previousSchema; state.listing.attributes = previousAttributes; state.schemaLoading = false; renderEditor(); showToast(error.message || 'Unable to load category specifications.');
    }
  }

  function updateSaleWarning() {
    const regular = parseFloat(state.listing.regular_price), sale = parseFloat(state.listing.sale_price), warning = app.querySelector('[data-sale-warning]');
    if (warning) warning.hidden = !(Number.isFinite(regular) && Number.isFinite(sale) && sale > regular);
  }

  async function saveListing(button) {
    if (state.schemaLoading) { showToast('Wait for category specifications to finish loading.'); return; }
    const mode = button.dataset.save, editing = mode === 'save', publish = mode === 'publish' || (editing && state.listing.status !== 'draft');
    clearValidation();
    const progress = requiredProgress();
    if (uploadsBusy()) { showToast('Wait for uploads to finish before saving.'); return; }
    if (publish && (progress.completed !== progress.total || uploadsBusy())) { showClientValidation(); return; }
    const demo = listingData().whatnot_demo;
    if (demo?.enabled) {
      const errors = {};
      if (!demo.url) errors['whatnot_demo.url'] = 'Enter the direct Whatnot show link.';
      else {
        try { const parsed = new URL(demo.url); const path = parsed.pathname.split('/').filter(Boolean); const first = (path[0] || '').toLowerCase(); const share = first === 's' && path.length === 2 && /^[A-Za-z0-9_-]+$/.test(path[1]); const direct = path.length >= 2 && !['user', 'invite', 'referral', 's', 'shop', 'store', 'listing', 'category'].includes(first); if (parsed.protocol !== 'https:' || !['whatnot.com', 'www.whatnot.com'].includes(parsed.hostname.toLowerCase()) || parsed.username || parsed.password || parsed.port || parsed.hash || parsed.search || !(share || direct)) errors['whatnot_demo.url'] = 'Enter a Whatnot show URL or show-share link on whatnot.com.'; }
        catch (_) { errors['whatnot_demo.url'] = 'Enter a valid Whatnot show URL.'; }
      }
      if (!demo.date) errors['whatnot_demo.date'] = 'Enter the show date.';
      if (!demo.time) errors['whatnot_demo.time'] = 'Enter the show start time.';
      if (!(KREVListLab.timezones || []).includes(demo.timezone)) errors['whatnot_demo.timezone'] = 'Choose a valid time zone.';
      if (Object.keys(errors).length) { showValidation(errors); return; }
    }
    button.disabled = true; button.textContent = 'Saving…';
    app.querySelector('.ll-main')?.setAttribute('aria-busy', 'true');
    announce('Saving listing.');
    try {
      const saved = await api(state.listing.id ? `listings/${state.listing.id}` : 'listings', { method: state.listing.id ? 'PUT' : 'POST', body: JSON.stringify({ ...listingData(), publish }) });
      state.dirty = false; removeDraft();
      if (editing) { sessionStorage.setItem('krev_listlab_status', 'Listing saved.'); location.href = account('tab=' + (state.listing.status === 'publish' ? 'active' : 'drafts')); return; }
      if (!publish) { sessionStorage.setItem('krev_listlab_status', 'Draft saved.'); location.href = account('tab=drafts'); return; }
      app.innerHTML = frame(`<section class="ll-success"><h2>Your listing is live!</h2><p>${esc(saved.title)}</p><a class="ll-button ll-primary" href="${esc(saved.view_url)}" target="_blank" rel="noopener">View listing</a> <a class="ll-button ll-secondary" href="${account('tab=active')}">My listings</a></section>`);
      announce('Your listing is live.');
    } catch (error) {
      const errors = error?.data?.errors;
      if (errors) showValidation(errors); else showToast(error.message || 'Save failed.');
      button.disabled = false; button.textContent = editing ? 'Save' : (publish ? 'Publish listing' : 'Save draft');
      app.querySelector('.ll-main')?.setAttribute('aria-busy', 'false');
    }
  }

  function showClientValidation() {
    const errors = {};
    if (!state.listing.title.trim()) errors.title = 'Enter a listing title.';
    if (!state.images.length) errors.featured_image_id = uploadsBusy() ? 'Wait for the product photo to finish uploading.' : 'Add a product photo.';
    if (!state.listing.shipping_policy) errors.shipping_policy_id = 'Select a shipping policy.';
    if (!state.listing.categories[0]) errors.category_id = 'Select a product category.';
    if (!state.listing.description.trim()) errors.description = 'Enter a description.';
    if (state.listing.quantity === '' || state.listing.quantity === null) errors.quantity = 'Enter a quantity.';
    state.schema.attributes.filter(attribute => attribute.required).forEach(attribute => {
      const field = app.querySelector(`[data-attribute="${attribute.taxonomy}"]`);
      if (!field?.value) errors[attribute.taxonomy] = `Select ${attribute.label.toLowerCase()}.`;
    });
    showValidation(errors);
  }

  const validationTarget = key => ({ featured_image_id: '[data-section="images"]', images: '[data-section="images"]', video_id: '[data-section="video"]', video_url: '[data-section="video"]', title: '[data-field-wrap="title"]', description: '[data-field-wrap="description"]', category_id: '[data-field-wrap="category_id"]', subcategory_id: '[data-field-wrap="subcategory_id"]', shipping_policy_id: '[data-field-wrap="shipping_policy_id"]', quantity: '[data-field-wrap="quantity"]', regular_price: '[data-field-wrap="regular_price"]', sale_price: '[data-field-wrap="sale_price"]', return_policy_id: '[data-field-wrap="return_policy_id"]' })[key] || `[data-field-wrap="${key}"]`;

  function showValidation(errors) {
    const summary = app.querySelector('[data-validation]');
    const entries = Object.entries(errors);
    if (!summary || !entries.length) return;
    summary.innerHTML = `<strong>${entries.length} ${entries.length === 1 ? 'item needs' : 'items need'} attention before publishing:</strong><ul>${entries.map(([key, message], index) => `<li><button id="ll-error-${index}" type="button" data-error-target="${esc(key)}">${esc(message)}</button></li>`).join('')}</ul>`;
    entries.forEach(([key], index) => {
      const target = app.querySelector(validationTarget(key));
      const field = target?.matches('input,textarea,select,button') ? target : target?.querySelector('input,textarea,select,button');
      target?.classList.add('ll-invalid');
      if (field) { field.setAttribute('aria-invalid', 'true'); const ids = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean)); ids.add(`ll-error-${index}`); field.setAttribute('aria-describedby', [...ids].join(' ')); }
    });
    summary.hidden = false;
    setupValidationNavigation();
    summary.focus();
  }

  function setupValidationNavigation() { app.querySelectorAll('[data-error-target]').forEach(button => { button.onclick = () => focusValidationTarget(button.dataset.errorTarget); }); }
  function focusValidationTarget(key) {
    const target = app.querySelector(validationTarget(key)); if (!target) return;
    const focusable = target.matches('input,textarea,select,button') ? target : target.querySelector('input,textarea,select,button');
    target.scrollIntoView({ block: 'center', behavior: 'smooth' }); setTimeout(() => focusable?.focus({ preventScroll: true }), 250);
  }
  function clearFieldValidation(key) {
    const target = app.querySelector(validationTarget(key));
    const field = target?.matches('input,textarea,select,button') ? target : target?.querySelector('input,textarea,select,button');
    target?.classList.remove('ll-invalid');
    if (field) { field.removeAttribute('aria-invalid'); const ids = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && !id.startsWith('ll-error-')); if (ids.length) field.setAttribute('aria-describedby', ids.join(' ')); else field.removeAttribute('aria-describedby'); }
  }
  function clearValidation() { const summary = app.querySelector('[data-validation]'); if (summary) { summary.hidden = true; summary.innerHTML = ''; } app.querySelectorAll('.ll-invalid').forEach(item => item.classList.remove('ll-invalid')); app.querySelectorAll('[aria-invalid="true"]').forEach(field => { field.removeAttribute('aria-invalid'); const ids = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && !id.startsWith('ll-error-')); if (ids.length) field.setAttribute('aria-describedby', ids.join(' ')); else field.removeAttribute('aria-describedby'); }); }

  function draftKeyFor(product) { return `krev_listlab_draft:${sellerKey}:${product.id ? `listing-${product.id}` : `new-${product.client_uuid}`}`; }
  function safeStorageGet(key) { try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch (_) { return null; } }
  function safeStorageSet(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); return true; } catch (_) { return false; } }

  function findRecoveryCandidate(product, copy) {
    if (product.id) {
      const candidate = safeStorageGet(draftKeyFor(product));
      const serverTime = Date.parse(product.modified_date || '') || 0;
      return candidate?.savedAt > serverTime ? { ...candidate, key: draftKeyFor(product) } : null;
    }
    if (copy) return null;
    const index = safeStorageGet(draftIndexKey), candidate = index?.key ? safeStorageGet(index.key) : null;
    return candidate?.meaningful ? { ...candidate, key: index.key } : null;
  }

  function draftSnapshot() {
    const payload = listingData();
    return { version: 1, savedAt: Date.now(), meaningful: Boolean(payload.title.trim() || payload.description.trim() || payload.short_description.trim() || state.images.length || state.video || state.videoUrl || payload.category_id || payload.sku || payload.global_unique_id || payload.regular_price || payload.sale_price), listing: { ...state.listing, title: payload.title, subtitle: payload.subtitle, description: payload.description, short_description: payload.short_description, regular_price: payload.regular_price, sale_price: payload.sale_price, quantity: payload.quantity, sku: payload.sku, global_unique_id: payload.global_unique_id ?? state.listing.global_unique_id, category_id: payload.category_id, subcategory_id: payload.subcategory_id, categories: [payload.category_id, payload.subcategory_id].filter(Boolean), attributes: payload.attributes, shipping_policy: payload.shipping_policy_id, return_policy: payload.return_policy_id, weight: payload.weight, dimensions: { length: payload.length, width: payload.width, height: payload.height }, client_uuid: payload.client_uuid }, video: state.video, videoUrl: state.videoUrl, images: state.images.map(item => ({ id: item.id, url: item.url, thumbnail_url: item.thumbnail_url, alt: item.alt || '' })) };
  }

  function scheduleDraftSave() { clearTimeout(state.draftTimer); state.draftTimer = setTimeout(persistDraftNow, 750); }
  function persistDraftNow() {
    if (!state.listing || !state.dirty) return;
    const snapshot = draftSnapshot();
    if (!snapshot.meaningful) return;
    state.draftKey = draftKeyFor(state.listing);
    if (safeStorageSet(state.draftKey, snapshot) && !state.listing.id) safeStorageSet(draftIndexKey, { key: state.draftKey, savedAt: snapshot.savedAt });
  }

  function setupDraftRecovery() {
    const restore = app.querySelector('[data-restore]'), discard = app.querySelector('[data-discard-draft]');
    if (restore) restore.onclick = async () => {
      const candidate = state.restoreCandidate;
      if (!candidate) return;
      state.listing = { ...base, ...candidate.listing, dimensions: { ...base.dimensions, ...(candidate.listing.dimensions || {}) } };
      state.images = candidate.images || []; state.draftKey = candidate.key; state.restoreCandidate = null; state.dirty = true;
      state.video = candidate.video || null; state.videoUrl = candidate.videoUrl || ''; state.videoUpload = null;
      await schema(state.listing.categories[0]); renderEditor(); showToast('Unsaved listing restored.');
    };
    if (discard) discard.onclick = () => { const candidate = state.restoreCandidate; if (candidate?.key) { try { localStorage.removeItem(candidate.key); } catch (_) {} } state.restoreCandidate = null; app.querySelector('[data-recovery]')?.remove(); };
  }

  function removeDraft() {
    clearTimeout(state.draftTimer);
    try { if (state.draftKey) localStorage.removeItem(state.draftKey); const index = safeStorageGet(draftIndexKey); if (index?.key === state.draftKey) localStorage.removeItem(draftIndexKey); } catch (_) {}
  }

  let viewportSetup = false, viewportFrame = 0;
  function setupKeyboardViewport() {
    if (viewportSetup) { updateVisualViewport(); return; }
    viewportSetup = true;
    const schedule = () => { cancelAnimationFrame(viewportFrame); viewportFrame = requestAnimationFrame(updateVisualViewport); };
    window.visualViewport?.addEventListener('resize', schedule, { passive: true });
    window.visualViewport?.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('orientationchange', schedule, { passive: true });
    document.addEventListener('focusin', event => { if (event.target.closest?.('#krev-listlab') && event.target.matches('input,textarea,select')) { schedule(); setTimeout(() => ensureFieldVisible(event.target), 180); } });
    document.addEventListener('focusout', () => setTimeout(schedule, 120));
    window.addEventListener('pageshow', schedule, { passive: true });
    updateVisualViewport();
  }

  function updateVisualViewport() {
    const viewport = window.visualViewport;
    const hidden = viewport ? Math.max(0, window.innerHeight - viewport.height - viewport.offsetTop) : 0;
    document.documentElement.style.setProperty('--krev-keyboard-inset', `${hidden}px`);
    app.style.setProperty('--krev-keyboard-inset', `${hidden}px`);
    document.body.classList.toggle('krev-keyboard-open', hidden > 100);
  }

  function ensureFieldVisible(field) {
    const viewport = window.visualViewport;
    if (!viewport) return;
    const rect = field.getBoundingClientRect(), top = viewport.offsetTop + 12, bottom = viewport.offsetTop + viewport.height - 18;
    if (rect.top >= top && rect.bottom <= bottom) return;
    field.scrollIntoView({ block: 'center', behavior: 'smooth' });
  }

  app.addEventListener('change', event => {
    const field = event.target.closest('[data-attribute]');
    if (!field) return;
    if (field.value !== '__custom__') { delete field.dataset.customValue; state.listing.attributes[field.dataset.attribute] = field.value ? [field.value] : []; clearFieldValidation(field.dataset.attribute); state.dirty = true; updateProgress(); scheduleDraftSave(); return; }
    const value = prompt('Enter a new value for this specification:');
    if (!value?.trim()) { field.value = ''; return; }
    const custom = value.trim(); field.add(new Option(`${custom} (custom)`, custom, true, true)); field.dataset.customValue = custom; state.listing.attributes[field.dataset.attribute] = [custom]; field.closest('[data-field-wrap]')?.classList.remove('ll-invalid'); state.dirty = true; updateProgress(); scheduleDraftSave();
  });
  document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') persistDraftNow(); });
  window.addEventListener('pagehide', () => { persistDraftNow(); stopBarcodeScan({ focus: false }); });
  window.addEventListener('beforeunload', event => { if (state.dirty) { persistDraftNow(); event.preventDefault(); event.returnValue = ''; } });

  const action = query.get('action'), id = Number(query.get('product_id')) || 0;
  if (action === 'new') editor();
  else if (action === 'edit' && id) editor(id);
  else if (action === 'duplicate' && id) editor(id, true);
  else listings();
})();
