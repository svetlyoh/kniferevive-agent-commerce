/* WooCommerce's interactive filters update the URL while retaining this header. */
(() => {
  'use strict';
  document.addEventListener('change', event => {
    const checkbox = event.target;
    if (!(checkbox instanceof HTMLInputElement) || checkbox.name !== 'kr_knives_only') return;
    const form = checkbox.closest('.kr-shop-scope-form');
    if (!form) return;
    const destination = new URL(form.action, window.location.href);
    destination.search = window.location.search;
    for (const key of [...destination.searchParams.keys()]) {
      if (['paged', 'page', 'product-page', 'pagename'].includes(key) || /^query-\d+-page$/.test(key)) destination.searchParams.delete(key);
    }
    if (checkbox.checked) destination.searchParams.set('kr_knives_only', '1');
    else destination.searchParams.delete('kr_knives_only');
    window.location.assign(destination.href);
  });
})();
