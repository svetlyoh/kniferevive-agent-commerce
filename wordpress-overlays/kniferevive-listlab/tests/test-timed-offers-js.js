const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const deadline = { textContent: 'Ends September 25, 2026 at 11:59 PM PDT' };
const countdown = { hidden: false };
const units = Object.fromEntries(['days', 'hours', 'minutes', 'seconds'].map(unit => [unit, { textContent: '--' }]));
const button = {
  textContent: 'Hide countdown',
  attributes: {},
  closest: () => offer,
  setAttribute(name, value) { this.attributes[name] = value; }
};
const offer = {
  dataset: { saleEnd: '3700000000' },
  querySelector(selector) {
    if (selector === '.krev-timed-offer__countdown') return countdown;
    if (selector === '.krev-timed-offer__deadline') return deadline;
    if (selector === '[data-krev-timed-offer-toggle]') return button;
    const unit = selector.match(/^\[data-krev-(days|hours|minutes|seconds)\]$/);
    return unit ? units[unit[1]] : null;
  }
};
const handlers = {};
const document = {
  readyState: 'complete',
  querySelectorAll: () => [offer],
  addEventListener(name, handler) { handlers[name] = handler; }
};
const window = {
  addEventListener() {},
  setInterval(handler) { this.tick = handler; return 1; }
};
const context = { document, window, Date: { now: () => 3600000000000 } };
const script = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/timed-offers.js'), 'utf8');
vm.runInNewContext(script, context);

assert.notEqual(units.seconds.textContent, '--', 'visible countdown updates');
handlers.click({ target: { closest: () => button } });
assert.equal(countdown.hidden, true, 'hide control conceals changing numbers');
assert.equal(button.textContent, 'Show countdown');
assert.equal(button.attributes['aria-expanded'], 'false');
assert.equal(deadline.textContent, 'Ends September 25, 2026 at 11:59 PM PDT', 'absolute deadline remains visible');
units.seconds.textContent = 'unchanged';
window.tick();
assert.equal(units.seconds.textContent, 'unchanged', 'hidden countdown stops updating');
handlers.click({ target: { closest: () => button } });
assert.equal(countdown.hidden, false, 'show control restores numbers');
assert.equal(button.textContent, 'Hide countdown');
assert.equal(button.attributes['aria-expanded'], 'true');
assert.notEqual(units.seconds.textContent, 'unchanged', 'show refreshes countdown immediately');
console.log('Timed-offer hide/show checks passed.');
