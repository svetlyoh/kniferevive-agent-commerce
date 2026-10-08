# Merchant onboarding evidence — October 7, 2026

The owner authorized local activation, merchant configuration, Stripe test setup/Google Pay, testing, and deployment. Lightning setup was excluded and remains disabled for agent commerce.

## Verified changes and tests

- WordPress merchant plugin installed and active locally and on KnifeRevive. The initial live deployment was 0.1.1; the latest per-trip correction is 0.1.3. Service definitions for products 1963 and 1964 configured; paid agent bookings remain disabled.
- Live dedicated Stripe TEST webhook registered successfully through the administrator setup button. Reloaded settings reported test key and dedicated test signing secret present. No live webhook or real processor payment was verified.
- Existing production WooCommerce Stripe Apple Pay/Google Pay placement expanded to checkout, preserving product/cart placement. The checkout checkbox stayed checked after reload. This is configuration evidence, not proof that a shopper's eligible wallet payment succeeded.
- Plugin/test PHP syntax checks passed. Version 0.1.3 booking/payment tests passed 91 assertions in legacy storage and 91 in HPOS with synthetic processors and blocked customer emails.
- Sixteen dedicated webhook onboarding assertions passed: opt-in credential reuse, environment separation, encryption/tamper rejection, administrator permission, fixed merchant destination/events, stable retries, duplicate prevention, and disabled payment gates.
- Actual API responses passed seven schema checks and the packaged quick-start example check.

## Confirmed merchant rules

The owner supplied Friday/Saturday 09:00–19:00 and Sunday 10:00–16:00 in America/Los_Angeles. Their latest correction makes each merchant trip $7.99. Merchant pickup plus return delivery therefore costs $15.98 total; either one-way merchant trip costs $7.99; customer drop-off plus collection costs $0 in courier fees. Configure each courier leg fee_minor=799 and transport_round_trip_minor=1598 (or null to sum per-leg fees). This supersedes the earlier combined-price and half-fee interpretation. Applicable tax remains determined by WooCommerce. Capacity, window duration, prepaid cancellation/refund policy, transport tax treatment, exact verified coverage and direct Stripe account ownership remain unresolved. Do not generate confirmed appointment windows or advertise working agent prepayment until resolved.

The existing sharpening products are owned by a seller account that does not have `manage_woocommerce`. The original direct-checkout permission gate therefore rejects them. Do not grant broad administrative capabilities or reassign product ownership to overcome that gate. Confirm that these are the platform's own services and implement a narrow, explicit operator-product approval if needed.

## Payment setup boundary

The plugin offers an administrator-only test-webhook setup form. It reuses official gateway keys only after explicit configuration, sends test credentials only to Stripe, stores the signing secret encrypted on the merchant server, and never includes keys in this repository or the skill. Real processor setup and end-to-end paid booking are separate evidence from the synthetic test results above.

During initial deployment, an installer diagnostic saved itself recursively and WordPress paused Code Snippets. The installer was deactivated, moved to recoverable Trash, and Code Snippets resumed. The temporary repair code was removed, the merchant entry file restored and verified against release source, and WordPress recovery mode exited. The merchant backend and normal admin were then verified. No seller permissions or product ownership were changed. This incident is distinct from the 0.1.2 pricing update, which uses native plugin file editing with WordPress validation and saved-source readback.
