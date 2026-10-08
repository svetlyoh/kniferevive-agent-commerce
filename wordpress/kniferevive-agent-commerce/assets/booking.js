(() => {
  'use strict';
  const input = document.querySelector('input[data-coverage]');
  const status = document.querySelector('#coverage-status');
  if (!input || !status) return;
  let revision = 0;
  async function coverage() {
    const current = ++revision;
    if (!/^[0-9]{5}$/.test(input.value)) {
      status.textContent = 'Enter a five-digit ZIP code to check service coverage.';
      return;
    }
    try {
      const url = new URL(input.dataset.coverage, window.location.origin);
      if (url.origin !== window.location.origin) throw new Error('Invalid coverage origin');
      url.searchParams.set('postal_code', input.value);
      const response = await fetch(url, {credentials: 'same-origin', redirect: 'error'});
      const data = await response.json();
      if (revision !== current) return;
      if (!response.ok || typeof data.message !== 'string') throw new Error('Coverage unavailable');
      status.textContent = data.message;
      const form = input.closest('form');
      const mode = form.querySelector('select[name="mode"]');
      if (mode) {
        for (const option of mode.options) {
          option.disabled = option.value !== 'pay_later_dropoff' && !data.prepayment_eligible;
        }
        if (mode.selectedOptions[0].disabled) mode.value = 'pay_later_dropoff';
      }
      const returns = form.querySelector('select[name="return_mode"]');
      if (returns) {
        returns.querySelector('option[value="courier_delivery"]').disabled = !data.pickup_eligible;
        if (!data.pickup_eligible) returns.value = 'customer_collection';
      }
      // Server validation remains authoritative, including when JavaScript is absent.
      input.setCustomValidity(data.coverage_state === 'outside_bay_area' ? data.message : '');
    } catch (_) {
      if (revision === current) status.textContent = 'Coverage could not be checked. The server will validate your ZIP before accepting a request.';
    }
  }
  input.addEventListener('change', coverage);
  if (input.value) coverage();
})();
