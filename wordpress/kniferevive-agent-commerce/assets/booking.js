(() => {
  'use strict';
  const input = document.querySelector('input[data-coverage]');
  const status = document.querySelector('#coverage-status');
  if (!input || !status) return;
  const form = input.closest('form');
  const mode = form.querySelector('select[name="mode"]');
  const returns = form.querySelector('select[name="return_mode"]');
  const address = form.querySelector('[data-address-fields]');
  const estimate = form.querySelector('#booking-estimate');
  let addressReview = address?.dataset.addressReview === 'true';
  let coverageResult = null;
  function updateChoices() {
    const trips = (mode?.value === 'prepaid_pickup' ? 1 : 0) + (returns?.value === 'courier_delivery' ? 1 : 0);
    if (address && (trips > 0 || addressReview)) address.open = true;
    for (const name of ['address_1','city']) {
      const field = form.querySelector(`input[name="${name}"]`);
      if (field) field.required = trips > 0 || addressReview;
    }
    if (mode) mode.setCustomValidity(coverageResult && mode.value !== 'pay_later_dropoff' && !coverageResult.prepayment_eligible ? coverageResult.message + ' Edit your handoff choice; your selection has not been changed.' : '');
    if (returns) returns.setCustomValidity(coverageResult && returns.value === 'courier_delivery' && !coverageResult.pickup_eligible ? coverageResult.message + ' Edit the return option.' : mode?.value === 'pay_later_dropoff' && returns.value === 'courier_delivery' ? 'Choose customer collection for pay-at-service, or explicitly choose a prepaid preference.' : '');
    const quantities = [...form.querySelectorAll('input[data-unit-minor]')];
    if (estimate && quantities.length) {
      const subtotal = quantities.reduce((sum,q) => sum + Number(q.dataset.unitMinor) * (Number(q.value)||0),0);
      const tripFee = trips * Number(form.querySelector('[data-trip-minor]')?.dataset.tripMinor || 0);
      const usd = value => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(value/100);
      estimate.textContent = `Services: ${usd(subtotal)} · Merchant trips: ${usd(tripFee)} · Estimated subtotal: ${usd(subtotal+tripFee)} USD, before taxes and other disclosed fees. No payment now.`;
      quantities[0].setCustomValidity(quantities.some(q=>Number(q.value)>0) ? '' : 'Choose at least one knife.');
    }
  }
  form.addEventListener('input',updateChoices);
  form.addEventListener('change',updateChoices);
  const submitButton = form.querySelector('button[value="submit"]');
  if (submitButton) submitButton.dataset.originalLabel = submitButton.textContent;
  let submitting = false;
  window.addEventListener('pageshow', () => {
    submitting = false;
    form.removeAttribute('aria-busy');
    const button = form.querySelector('button[value="submit"]');
    if (button) button.textContent = button.dataset.originalLabel || 'Continue with booking';
  });
  form.addEventListener('submit', event => {
    if (event.submitter?.value !== 'submit') return;
    if (submitting) { event.preventDefault(); return; }
    submitting = true;
    // Keep the submitter enabled during serialization; disable only after form data is built.
    form.setAttribute('aria-busy','true');
    event.submitter.textContent = 'Sending request…';
  });
  const errorSummary = document.querySelector('#form-errors');
  if (errorSummary) errorSummary.focus();
  updateChoices();
  let revision = 0;
  async function coverage() {
    const current = ++revision;
    coverageResult = null;
    input.setCustomValidity('');
    updateChoices();
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
      coverageResult = data;
      addressReview = !!data.address_review_required;
      if (mode) {
        for (const option of mode.options) {
          option.disabled = option.value !== 'pay_later_dropoff' && (!data.prepayment_eligible || (option.value === 'prepaid_pickup' && !data.pickup_eligible));
        }
      }
      if (returns) {
        returns.querySelector('option[value="courier_delivery"]').disabled = !data.pickup_eligible;
      }
      // Server validation remains authoritative, including when JavaScript is absent.
      input.setCustomValidity(data.coverage_state === 'outside_bay_area' ? data.message : '');
      updateChoices();
    } catch (_) {
      if (revision === current) status.textContent = 'Coverage could not be checked. The server will validate your ZIP before accepting a request.';
    }
  }
  input.addEventListener('change', coverage);
  if (input.value) coverage();
})();
