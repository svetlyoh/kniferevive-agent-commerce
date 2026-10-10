# Saved booking payment handoff — merchant adapter 0.5.15

Deployed 9 October 2026 Pacific. Portable skill remains 0.5.14.

## Cause and repair

The private native checkout tagged WooCommerce's `wc_ajax_url` for quote updates,
but left `checkout_url` untagged. Core `checkout.js` uses the latter for the final
checkout POST. That request therefore opened the ordinary storefront session and
hit the sharpening guard asking for a day and pickup/return plan, although the
private booking already had both. Both native script URLs now carry the original
booking tag. The tag selects a session; it does not grant access. The private
booking authorization and isolated native cookie remain required.

The installed official Stripe extension also changes a Cash App order's gateway
from the reviewed parent `stripe` to `stripe_cashapp`. Native payment observation,
merchant original-order recovery and same-order payment retry now recognize that
specific subtype only for a booking bound to that order, with official Stripe
order-helper metadata identifying `cashapp`. Arbitrary `stripe_*` gateways and
incorrect subtype metadata are rejected. Initial order creation still requires
the reviewed parent gateway, original amount, items, addresses and quote hash.
An order's status, redirect or UI selection alone cannot verify payment. A native
payment-complete event with paid date and transaction evidence is still required.

Official references: [WooCommerce Stripe payment methods](https://woocommerce.com/document/stripe/setup-and-configuration/additional-payment-methods/)
and the [official Stripe extension source](https://github.com/woocommerce/woocommerce-gateway-stripe).
Implementation was checked against the extension installed on this PC.

## Verification and deployment

- 80 booking/fee assertions and 33 storefront assertions passed in each of classic
  and HPOS storage with synchronization enabled: 226 checks total. Added negative
  subtype/gateway checks, original Cash App order recovery/retry, and synthetic
  payment-complete verification. Existing seller submission and three notification
  job assertions also passed. Outbound payments and email are blocked.
- `tests/native-checkout-handoff.py` exercised actual HTTP requests through the
  fenced `tests/ui-router.php`: ordinary sharpening cart → details submission →
  private native payment screen → final WooCommerce checkout POST using the URL
  actually rendered by core. Both rendered URLs retain the booking. The Cash App
  selection reaches the synthetic gateway through the booking guard, native order
  creation and original quote binding. The fixture gateway deliberately throws
  before payment. An ungranted second browser receives 403 on the same tagged URL.
- All merchant plugin PHP files pass syntax checks; `git diff --check` passes.
- Native WordPress plugin replacement reported success. Installed plugin list
  shows active 0.5.15. The public diagnostic received HTTP/2 200 JSON on capabilities,
  booking options, availability and eligible ZIP coverage, reporting adapter 0.5.15
  and the unchanged $6 single-trip/$11 comeback-combo fees.

Uploaded ZIP SHA-256:
`2d940918c8b505457474920f8b7af3b7d4b87713fb8f991c28236019882b2b5b`.
Deployment screenshot is retained locally in the documentation diagnostics folder
as `cashapp-handoff-fix-0.5.15.png`.

No production booking, order, payment, refund or notification was created by these
checks. Real mobile Cash App authorization, processor settlement and refund arrival
remain customer/operator checks. The supplied booking reference alone does not
authorize reading its private customer details, so its individual state is not
claimed here. No payment settings, coverage or existing booking records were changed.

## Customer recovery and operator instructions

Reopen and refresh the original private payment link from Muse in the same mobile
browser. Continue the existing booking instead of creating another one. Saved knife
quantities, day and transport plan remain the source for native payment. If an unpaid
quote expired, use the existing saved-details refresh action; it retains the booking
reference. Check original payment status before any retry if Cash App already charged.

No bot skill reinstall or WooCommerce relogin is needed for this backend repair.
Do not remove `StorefrontBooking::guardOrder`, accept a reference as an access grant,
replace the ordinary cart, or mark a booking paid from a redirect/UI result. Keep
payment evidence tied to its original order and transaction. For regressions, use
the synthetic database/bootstrap and loopback router only; never load live or Local
Sites `wp-config.php`, and never substitute the real Stripe processor into the HTTP
test. Rollback ZIP 0.5.14 is retained locally but reintroduces the final POST bug.
