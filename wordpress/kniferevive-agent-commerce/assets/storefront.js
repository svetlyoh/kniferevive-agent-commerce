(() => {
  'use strict';
  const main = document.querySelector('main[data-attach]');
  const status = document.getElementById('session-status');
  const fragment = new URLSearchParams(location.hash.slice(1));
  const token = fragment.get('session');
  if (!main || !token) return;
  history.replaceState(null, '', location.pathname + location.search);
  if (!/^[a-f0-9]{32}\.[a-f0-9]{64}$/.test(token)) {
    status.textContent = 'Invalid private session link.';
    return;
  }
  const endpoint = new URL(main.dataset.attach, location.origin);
  if (endpoint.origin !== location.origin) {
    status.textContent = 'The shopper session endpoint must use this site.';
    return;
  }
  status.textContent = 'Opening your private review…';
  fetch(endpoint, {
    method: 'POST', credentials: 'same-origin', redirect: 'error', referrerPolicy: 'no-referrer',
    headers: {'Content-Type': 'application/json', 'X-Krev-Agent-Session': token}, body: '{}'
  }).then(response => {
    if (!response.ok) throw new Error();
    location.reload();
  }).catch(() => { status.textContent = 'Your private session could not be opened. Request a fresh review link.'; });
})();
