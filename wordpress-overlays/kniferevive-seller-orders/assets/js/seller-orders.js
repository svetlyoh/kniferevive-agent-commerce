(function () {
  'use strict';
  function initialize() {
  var cfg = window.KREVOrders || {};
  function request(path, options) {
    options = options || {};
    options.credentials = 'same-origin';
    options.headers = options.headers || {};
    options.headers['X-WP-Nonce'] = cfg.nonce;
    if (!(options.body instanceof FormData)) {
      options.headers['Content-Type'] = 'application/json';
      if (options.body && typeof options.body !== 'string') options.body = JSON.stringify(options.body);
    }
    return fetch(cfg.root + path, options).then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok) throw new Error(data.message || 'Request failed.');
        return data;
      });
    });
  }
  function notice(element, message, error) {
    var item = document.createElement('div');
    item.className = 'krev-notice ' + (error ? 'is-error' : 'is-success');
    item.textContent = message;
    element.prepend(item);
  }
  var copy = document.getElementById('krev-copy-address');
  if (copy) copy.addEventListener('click', function () {
    navigator.clipboard.writeText(document.getElementById('krev-ship-address').innerText).then(function () { copy.textContent = 'Copied'; });
  });
  var detail = document.querySelector('.krev-order-detail');
  if (detail) {
    var order = detail.dataset.orderId;
    var tracking = detail.querySelector('.krev-tracking-form');
    if (tracking) tracking.addEventListener('submit', function (event) {
      event.preventDefault();
      request('seller-orders/' + order + '/tracking', { method: 'POST', body: Object.fromEntries(new FormData(tracking).entries()) }).then(function () { location.reload(); }).catch(function (error) { notice(tracking, error.message, true); });
    });
    var fulfill = detail.querySelector('.krev-fulfill');
    if (fulfill) fulfill.addEventListener('click', function () {
      request('seller-orders/' + order + '/fulfill', { method: 'POST', body: {} }).then(function () { location.reload(); }).catch(function (error) { notice(detail, error.message, true); });
    });
    var fulfillmentStatus = detail.querySelector('[data-fulfillment-status]');
    if (fulfillmentStatus) fulfillmentStatus.dataset.confirmed = fulfillmentStatus.value;
    if (fulfillmentStatus) fulfillmentStatus.addEventListener('change', function () {
      var confirmed = fulfillmentStatus.dataset.confirmed;
      fulfillmentStatus.disabled = true;
      var message = fulfillmentStatus.parentNode.querySelector('[data-status-message]');
      message.textContent = 'Saving…';
      request('seller-orders/' + order + '/fulfillment-status', { method: 'PATCH', body: { status: fulfillmentStatus.value } }).then(function (data) {
        fulfillmentStatus.dataset.confirmed = data.fulfillment_status; message.textContent = 'Saved';
      }).catch(function (error) { fulfillmentStatus.value = confirmed; notice(detail, error.message, true); message.textContent = ''; }).finally(function () { fulfillmentStatus.disabled = false; setTimeout(function () { message.textContent = ''; }, 1800); });
    });
    detail.addEventListener('click', function (event) {
      var button = event.target.closest('.krev-return-action');
      if (!button) return;
      var box = button.closest('.krev-return'), action = button.dataset.action, payload = {};
      if (action === 'reject') { payload.reason = window.prompt('Rejection reason:') || ''; if (!payload.reason) return; }
      if (action === 'received') {
        payload.received_quantity = window.prompt('Quantity received:', '1') || '';
        payload.condition_note = window.prompt('Condition note:', 'Condition acceptable') || '';
        if (!payload.received_quantity) return;
      }
      request('returns/' + box.dataset.returnId + '/' + action, { method: 'POST', body: payload }).then(function () { location.reload(); }).catch(function (error) { notice(box, error.message, true); });
    });
    detail.addEventListener('click', function (event) {
      var button = event.target.closest('.krev-return-refund');
      if (!button) return;
      var box = button.closest('.krev-return'), path = 'returns/' + box.dataset.returnId + '/refund';
      request(path, { method: 'POST', body: { confirm: false } }).then(function (data) {
        var p = data.preview;
        var message = 'Item refund: ' + p.item_refund + '\nTax: ' + p.tax_refund + '\nReturn cost: -' + p.return_cost + '\nProposed refund: ' + p.proposed_refund + '\n\nIssue this WooCommerce refund?';
        if (!window.confirm(message)) return null;
        return request(path, { method: 'POST', body: { confirm: true, refund_payment: true, restock_items: false } });
      }).then(function (data) { if (data) location.reload(); }).catch(function (error) { notice(box, error.message, true); });
    });
  }
  document.querySelectorAll('.krev-request-return').forEach(function (button) {
    button.addEventListener('click', function () {
      if (button.parentNode.querySelector('.krev-return-request-form')) return;
      var form = document.createElement('form');
      form.className = 'krev-return-request-form'; form.enctype = 'multipart/form-data';
      form.innerHTML = '<input type="hidden" name="order_item_id" value="' + button.dataset.item + '"><label>Quantity<input type="number" name="quantity" min="1" max="' + button.dataset.max + '" value="1" required></label><label>Reason<select name="reason" required><option value="">Choose</option><option>Item not as described</option><option>Wrong item received</option><option>Item damaged in transit</option><option>Missing item / quantity</option><option>Other</option></select></label><label>Explanation<textarea name="explanation" required></textarea></label><label>Requested resolution<select name="requested_resolution"><option value="refund">Refund</option><option value="replacement">Replacement</option></select></label><label>Optional photos<input type="file" name="evidence[]" accept="image/jpeg,image/png,image/webp" multiple></label><button class="button" type="submit">Submit Return Request</button><button class="button krev-return-cancel" type="button">Cancel</button>';
      button.parentNode.appendChild(form);
      form.querySelector('.krev-return-cancel').onclick = function () { form.remove(); };
      form.onsubmit = function (event) { event.preventDefault(); request('orders/' + button.dataset.order + '/returns', { method: 'POST', body: new FormData(form) }).then(function () { location.reload(); }).catch(function (error) { notice(form, error.message, true); }); };
    });
  });
  document.querySelectorAll('.krev-return-tracking-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var box = form.closest('.krev-return');
      request('returns/' + box.dataset.returnId + '/tracking', { method: 'POST', body: Object.fromEntries(new FormData(form).entries()) }).then(function () { location.reload(); }).catch(function (error) { notice(form, error.message, true); });
    });
  });
  var sharpening = document.querySelector('.krev-sharpening-detail');
  if (sharpening) {
    var stageForm = sharpening.querySelector('.krev-stage-form');
    var stageButtons = sharpening.querySelectorAll('[data-krev-stage]');
    var stageSave = sharpening.querySelector('[data-krev-stage-save]');
    var stageChoice = sharpening.querySelector('[data-krev-stage-choice]');
    var stageFeedback = sharpening.querySelector('.krev-stage-feedback');
    var stageBusy = false;
    function saveStage(data, button) {
      if (stageBusy) return;
      stageBusy = true;
      stageButtons.forEach(function (item) { item.disabled = true; });
      if (stageSave) stageSave.disabled = true;
      button.disabled = true;
      if (stageFeedback) stageFeedback.textContent = 'Saving stage…';
      request('sharpening-orders/' + sharpening.dataset.orderId + '/stage', { method: 'POST', body: data }).then(function () { location.reload(); }).catch(function (error) {
        stageBusy = false;
        stageButtons.forEach(function (item) { item.disabled = false; });
        button.disabled = false;
        if (stageSave) stageSave.disabled = false;
        if (stageFeedback) stageFeedback.textContent = error.message;
        else notice(stageForm, error.message, true);
      });
    }
    stageButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        if (stageBusy || !stageForm) return;
        stageForm.elements.stage.value = button.dataset.krevStage;
        stageButtons.forEach(function (item) { item.classList.toggle('is-selected', item === button); item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); });
        if (stageChoice) stageChoice.textContent = 'Selected: ' + button.querySelector('strong').textContent;
        if (stageSave) stageSave.disabled = false;
        if (stageFeedback) stageFeedback.textContent = '';
      });
    });
    if (stageSave && stageForm) stageSave.addEventListener('click', function () { saveStage(Object.fromEntries(new FormData(stageForm).entries()), stageSave); });
    if (stageForm) stageForm.elements.stage.addEventListener('change', function () {
      var selected = sharpening.querySelector('[data-krev-stage="' + stageForm.elements.stage.value + '"]');
      if (selected) selected.click();
    });
    if (stageForm) stageForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = Object.fromEntries(new FormData(stageForm).entries());
      data.complete = !!stageForm.querySelector('[name=complete]:checked');
      saveStage(data, stageForm.querySelector('[type=submit]'));
    });
    var completeSharpening = sharpening.querySelector('.krev-complete-sharpening');
    if (completeSharpening) completeSharpening.addEventListener('click', function () {
      completeSharpening.disabled = true;
      request('sharpening-orders/' + sharpening.dataset.orderId + '/stage', { method: 'POST', body: { stage: 'return', complete: true } }).then(function () { location.reload(); }).catch(function (error) { completeSharpening.disabled = false; notice(sharpening, error.message, true); });
    });
  }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
}());
