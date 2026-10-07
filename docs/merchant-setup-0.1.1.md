# Merchant onboarding evidence — October 7, 2026

The owner authorized local activation, merchant configuration, Stripe test setup/Google Pay, testing, and deployment. Lightning setup was excluded and remains disabled for agent commerce.

## Verified changes and tests

- Local WordPress plugin 0.1.1 activated against the site's existing WooCommerce database. Service definitions for products 1963 and 1964 configured; paid bookings remain disabled.
- Existing production WooCommerce Stripe Apple Pay/Google Pay placement expanded to checkout, preserving product/cart placement. The checkout checkbox stayed checked after reload. This is configuration evidence, not proof that a shopper's eligible wallet payment succeeded.
- Plugin PHP syntax checks passed. The booking/payment suite passed 75 assertions in legacy storage and 75 in HPOS with synthetic processors and blocked customer emails.
- Sixteen dedicated webhook onboarding assertions passed: opt-in credential reuse, environment separation, encryption/tamper rejection, administrator permission, fixed merchant destination/events, stable retries, duplicate prevention, and disabled payment gates.
- Actual API responses passed seven schema checks and the packaged quick-start example check.

## Confirmed merchant rules

The owner supplied Friday/Saturday 09:00–19:00 and Sunday 10:00–16:00 in America/Los_Angeles. They supplied a $9.99 pickup or drop-off fee, provisionally interpreted as each merchant courier trip ($19.98 for both legs). Capacity, window duration, prepaid cancellation/refund policy and direct Stripe account ownership remain unresolved. Do not generate confirmed appointment windows or advertise working agent prepayment until resolved.

The existing sharpening products are owned by a seller account that does not have `manage_woocommerce`. The original direct-checkout permission gate therefore rejects them. Do not grant broad administrative capabilities or reassign product ownership to overcome that gate. Confirm that these are the platform's own services and implement a narrow, explicit operator-product approval if needed.

## Payment setup boundary

The plugin offers an administrator-only test-webhook setup form. It reuses official gateway keys only after explicit configuration, sends test credentials only to Stripe, stores the signing secret encrypted on the merchant server, and never includes keys in this repository or the skill. Real processor setup and end-to-end paid booking are separate evidence from the synthetic test results above.

Deployment status and actual Stripe setup result must be recorded after verification; this document does not assert they have completed.
