(() => {
  'use strict';
  const input = document.querySelector('input[data-coverage]');
  const status = document.querySelector('#coverage-status');
  if (!input || !status) return;
  const form = input.closest('form');
  const mode = form.querySelector('[name="mode"]');
  const returns = form.querySelector('[name="return_mode"]');
  const address = form.querySelector('[data-address-fields]');
  const estimate = form.querySelector('#booking-estimate');
  const coverageFields = form.querySelector('[data-coverage-fields]');
  let addressReview = address?.dataset.addressReview === 'true';
  let coverageResult = null;
  let revision = 0;
  const paid = () => !!mode && mode.value !== 'pay_later_dropoff';
  const trips = () => mode?.value === 'prepaid_pickup_delivery' ? 2 : (mode?.value === 'prepaid_dropoff_delivery' ? 1 : mode?.value === 'prepaid_pickup' ? 1 : 0) + (returns?.value === 'courier_delivery' ? 1 : 0);
  function updateChoices() {
    if (coverageFields) coverageFields.hidden = !paid();
    input.disabled = !paid();input.required = paid();
    if (!paid()) { input.setCustomValidity('');addressReview = false; }
    const bookingButton = form.querySelector('[data-submit-booking]');
    if (bookingButton && form.getAttribute('aria-busy') !== 'true') {
      bookingButton.textContent = paid() ? 'Continue to Payment' : 'Book my drop-off';
      bookingButton.dataset.originalLabel = bookingButton.textContent;
    }
    const needsAddress = paid() && (trips() > 0 || addressReview);
    const billing = form.querySelector('[data-billing-fields]');
    const different = form.querySelector('[name="billing_different"]');
    const needsBilling = paid() && (trips() === 0 || different?.checked);
    if (billing) {
      billing.hidden = !paid();
      const choice = billing.querySelector('[data-billing-choice]'); if (choice) choice.hidden = trips() === 0;
      const fields = billing.querySelector('[data-billing-inputs]'); if (fields) fields.hidden = !needsBilling;
      if (needsBilling) billing.open = true;
      for (const name of ['billing_address_1','billing_city','billing_postcode']) { const field = billing.querySelector(`[name="${name}"]`);if(field){field.required=needsBilling;field.disabled=!needsBilling;} }
    }
    if (address && needsAddress) address.open = true;
    // Keep the expansion open while a customer types.
    for (const name of ['address_1','city']) {
      const field = form.querySelector(`input[name="${name}"]`);
      if (field) field.required = needsAddress;
    }
    if (mode) mode.setCustomValidity(paid() && coverageResult && (!coverageResult.prepayment_eligible || (trips() > 0 && !coverageResult.pickup_eligible)) ? coverageResult.message + ' Choose drop-off with payment at collection, or check another service ZIP.' : '');
    const quantities = [...form.querySelectorAll('input[data-unit-minor]')];
    if (estimate && quantities.length) {
      const subtotal = quantities.reduce((sum,q) => sum + Number(q.dataset.unitMinor) * (Number(q.value)||0),0);
      const pricing = form.querySelector('[data-trip-minor]');
      const tripFee = trips() === 2 ? Number(pricing?.dataset.roundTripMinor || 0) : trips() * Number(pricing?.dataset.tripMinor || 0);
      const usd = value => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(value/100);
      for (const option of mode?.options || []) if(option.dataset.label) option.textContent=option.value==='pay_later_dropoff' ? `${option.dataset.label} — ${usd(Number(option.dataset.feeMinor))} trip fee` : `${option.dataset.label} — ${usd(subtotal)} + ${usd(Number(option.dataset.feeMinor))} ${option.value==='prepaid_pickup_delivery'?'round-trip fee':'trip fee'}/order`;
      estimate.textContent = `Sharpening: ${usd(subtotal)} · Trip fee: ${usd(tripFee)} per order · Estimated subtotal: ${usd(subtotal+tripFee)}, before taxes and disclosed fees. ${paid() ? 'Final tax and total on the payment screen.' : 'Nothing due now. Pay for sharpening when you collect your knives.'}`;
      const handoffCost = form.querySelector('#handoff-cost'); if(handoffCost)handoffCost.textContent=`Sharpening ${usd(subtotal)} + ${usd(tripFee)} trip fee per order = ${usd(subtotal+tripFee)} before tax. ${trips() ? `${trips()} merchant trip${trips()===1?'':'s'}.` : paid() ? 'You drop off and collect.' : 'Nothing due now. Pay for sharpening at pickup.'}`;
      quantities[0].setCustomValidity(quantities.some(q=>Number(q.value)>0) ? '' : 'Choose at least one knife.');
    }
  }
  function openReviewChoices(control) {
    const picker = document.createElement('dialog');
    picker.className = 'krev-choice-picker';picker.setAttribute('aria-labelledby','krev-choice-title');
    const heading = document.createElement('h2');heading.id='krev-choice-title';
    heading.textContent = control.id === 'mode' ? 'Choose your pickup & return plan' : 'Choose your service day';
    picker.append(heading);
    for (const option of control.options) {
      if (!option.value || option.disabled) continue;
      const choice = document.createElement('button');choice.type='button';choice.className='krev-choice-option';choice.textContent=option.textContent;
      if (option.selected) choice.setAttribute('aria-current','true');
      choice.addEventListener('click', () => { control.value=option.value;control.dispatchEvent(new Event('change',{bubbles:true}));picker.close(); });
      picker.append(choice);
    }
    const close = document.createElement('button');close.type='button';close.className='krev-choice-close';close.textContent='Keep my current choice';
    close.addEventListener('click',()=>picker.close());picker.append(close);
    picker.addEventListener('close',()=>{picker.remove();control.focus({preventScroll:true});});
    document.body.append(picker);picker.showModal();
  }
  form.addEventListener('click', event => {
    const link = event.target.closest('[data-edit-target]');
    if (!link || !form.contains(link)) return;
    const section = document.getElementById(link.dataset.editTarget);
    if (!section || !form.contains(section)) return;
    event.preventDefault();
    for (let parent = section.parentElement; parent; parent = parent.parentElement) if (parent.tagName === 'DETAILS') parent.open = true;
    const control = section.matches('input,select,textarea') ? section : section.querySelector('input:not([disabled]),select:not([disabled]),textarea:not([disabled])');
    section.scrollIntoView({block:'center'});
    if (control) {
      control.focus({preventScroll:true});
      if (control.tagName === 'SELECT') openReviewChoices(control);
    }
  });
  form.addEventListener('input',updateChoices);form.addEventListener('change',updateChoices);
  let submitting = false;
  window.addEventListener('pageshow', () => { submitting = false;form.removeAttribute('aria-busy');updateChoices(); });
  form.addEventListener('submit', event => {
    if (event.submitter?.value !== 'submit') return;
    if (submitting) { event.preventDefault();return; }
    submitting = true;form.setAttribute('aria-busy','true');event.submitter.textContent = paid() ? 'Opening payment…' : 'Sending drop-off request…';
  });
  document.querySelector('#form-errors')?.focus();updateChoices();
  async function coverage() {
    const current = ++revision;
    coverageResult = null;input.setCustomValidity('');updateChoices();
    if (!paid()) return;
    if (!/^[0-9]{5}$/.test(input.value)) { status.textContent = 'Enter a five-digit ZIP code to check service coverage.';return; }
    try {
      const url = new URL(input.dataset.coverage, window.location.origin);
      if (url.origin !== window.location.origin) throw new Error('Invalid coverage origin');
      url.searchParams.set('postal_code', input.value);
      const response = await fetch(url, {credentials:'same-origin',redirect:'error'});
      const data = await response.json();
      if (revision !== current || !paid()) return;
      if (!response.ok || typeof data.message !== 'string') throw new Error('Coverage unavailable');
      status.textContent = data.message;status.dataset.verifiedPostal = input.value;
      status.dataset.tone = data.address_review_required ? 'pending' : data.service_available ? 'available' : 'unavailable';
      coverageResult = data;addressReview = !!data.address_review_required;updateChoices();
    } catch (_) {
      if (revision === current && status.dataset.verifiedPostal !== input.value) {
        status.dataset.tone = 'pending';status.textContent = 'Tap “Check service coverage” to confirm your zone. The live check couldn’t connect.';
      }
    }
  }
  mode?.addEventListener('change',coverage);input.addEventListener('change',coverage);
  input.addEventListener('input', () => {
    if (status.dataset.verifiedPostal !== input.value) { ++revision;coverageResult = null;addressReview = false;status.dataset.tone = 'pending';status.textContent = 'New ZIP? Tap “Check service coverage” to confirm your zone.'; }
  });
  if (status.dataset.coverageConfirmed === 'true') status.focus();
  if (input.value && paid()) coverage();
})();
