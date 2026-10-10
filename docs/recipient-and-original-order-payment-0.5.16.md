# Recipient name and existing-order payment recovery — adapter 0.5.16

Deployed 9 October 2026 Pacific. Portable skill remains 0.5.14.

## Problem and final behavior

Suppressing WooCommerce's separate shipping-address form also stopped core from
copying billing names into shipping names. The adapter restored the saved service
address, but not a recipient name. Stripe still sends that address as shipping
data and requires a name. The native posted-data adapter now supplies the name
from the consented booking contact, independently of billing-address edits. No
additional shipping-name or shipping-address UI is required.

After native order creation, the private payment page intentionally prevented
another cart checkout, but offered no usable route to pay the existing order.
It now displays the saved order, total and plan, with a CSRF-protected **Continue
payment for this order** action. That action validates the original order and
redirects to WooCommerce's native order-pay form. It does not create a replacement
booking, order or payment intent. Native gateway interfaces still collect and
authorize payment; no custom card handling was added.

Recovery requires the private booking grant, exact native order/quote/item/amount
binding, eligible coverage, current fulfillment consent and a valid service day.
Any existing Stripe intent is read through the installed native Stripe API. It
must still await buyer approval, with matching intent ID, amount, currency and
order metadata. Processing, succeeded, cancelled, mismatched or unreadable intents
cannot invite another payment. An expired hold can be renewed only after this
check and the existing capacity check; a full day remains unavailable. Existing
nonempty recipient names and all prices, taxes, fees and addresses stay intact.

Original-order payment URLs retain the booking tag and use the isolated booking
session, preserving ordinary carts. The tag cannot authorize access. Original
order/key checks run at `wp_loaded`, after WooCommerce registers order storage and
before native payment processing. Native order-pay ownership, email verification,
nonce, key and gateway validation remain in place. Original-order retry errors
become notices instead of uncaught exceptions. Payment completion and seller
submission still require the native gateway event.

## Verification

- 89 booking/fee assertions and 33 storefront assertions pass in each of classic
  and HPOS storage with synchronization enabled: 244 assertions total. They cover
  the named recipient, unchanged original total/order ID, isolated retry URL,
  tampered order-key rejection, processing intent rejection, amount mismatch,
  verified renewal and existing native Cash App payment evidence/seller submission.
- Actual HTTP requests through the fenced loopback router completed details →
  final native checkout POST → existing private booking → native order-pay → retry
  reaching the same order ID. The synthetic gateway requires a recipient name and
  deliberately throws before any charge. An ungranted browser remains blocked.
- All plugin PHP files pass syntax checks; the diff passes whitespace checks.
- Native WordPress replacement reported successful update. Public discovery
  returned four HTTP/2 200 JSON responses and adapter 0.5.16, retaining $6 single
  trip/$11 comeback combo and all five choices.
- The customer's existing pending order had a billing name and a blank recipient.
  Its missing shipping name was repaired through the existing native order editor;
  the update retained pending-payment status. No new production order, payment or
  refund was created by this repair. The customer's actual wallet approval and
  final paid completion remain untested by the agent.

Uploaded plugin ZIP SHA-256:
`02ed1490b9a663e9002a7edf284201959b5a846bc8d9da777e6c37ae95890cf7`.

## ClawHub version clarification

`clawhub skill verify @svetlyoh/kniferevive-concierge --version 0.5.14 --json`
now returns `ok: true`, `decision: pass`, no failure reasons, a generated Skill
Card, and clean/passed security. The earlier `card.missing` observation was a
registry-generation delay. Version 0.5.15 is not published as a skill; it was a
separate website plugin repair. No skill upload was necessary for this fix.
See [the current verification summary](clawhub-verification-recheck-0.5.14.json).
Server-resolved GitHub import provenance remains unavailable; no complete
independent certification is claimed.

## Customer and operator recovery

Reopen the original Muse private booking/payment link on the same phone, refresh,
and choose **Continue payment for this order**. Saved knives, intake day and plan
remain attached. Native checkout may ask the buyer to verify their receipt email.
Use the existing order, especially if an earlier wallet attempt's result is
uncertain. Never create another order to bypass a pending payment.

No skill reinstall or WooCommerce relogin is required. Keep these regressions in
the fenced database and loopback UI router; never load the real `wp-config.php`
or put a real processor into the synthetic test. Preserve private grants and
native ownership/key checks. Do not mark an order paid, remove the booking guard,
change its amount, or renew a processing/unknown payment's hold to force recovery.
