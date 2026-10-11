(function () {
  'use strict';
  var interval = 0;

  function offers() {
    return Array.prototype.slice.call(document.querySelectorAll('[data-krev-timed-offer]'));
  }

  function tick() {
    var now = Math.floor(Date.now() / 1000);
    offers().forEach(function (offer) {
      var remaining = Number(offer.dataset.saleEnd) - now;
      if (remaining <= 0) {
        if (offer.dataset.expired) return;
        offer.dataset.expired = 'true';
        offer.classList.add('is-expired');
        var label = offer.querySelector('.krev-timed-offer__label');
        var countdown = offer.querySelector('.krev-timed-offer__countdown');
        var toggle = offer.querySelector('[data-krev-timed-offer-toggle]');
        if (label) label.textContent = 'Offer ended';
        if (countdown) countdown.hidden = true;
        if (toggle) toggle.hidden = true;
        window.setTimeout(function () { window.location.reload(); }, 750);
        return;
      }
      // Hiding the countdown stops its visible updates without changing the sale.
      var countdown = offer.querySelector('.krev-timed-offer__countdown');
      if (countdown && countdown.hidden) return;
      var values = [Math.floor(remaining / 86400), Math.floor(remaining % 86400 / 3600), Math.floor(remaining % 3600 / 60), remaining % 60];
      ['days', 'hours', 'minutes', 'seconds'].forEach(function (unit, index) {
        var node = offer.querySelector('[data-krev-' + unit + ']');
        if (node) node.textContent = String(values[index]).padStart(2, '0');
      });
    });
  }

  function toggleCountdown(button) {
    var offer = button.closest('[data-krev-timed-offer]');
    var countdown = offer && offer.querySelector('.krev-timed-offer__countdown');
    if (!countdown) return;
    var hide = !countdown.hidden;
    countdown.hidden = hide;
    button.setAttribute('aria-expanded', String(!hide));
    button.textContent = hide ? 'Show countdown' : 'Hide countdown';
    if (!hide) tick();
  }

  function init() {
    tick();
    if (!interval && offers().length) interval = window.setInterval(tick, 1000);
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-krev-timed-offer-toggle]');
    if (button) toggleCountdown(button);
  });

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
  window.addEventListener('pageshow', init);
}());
