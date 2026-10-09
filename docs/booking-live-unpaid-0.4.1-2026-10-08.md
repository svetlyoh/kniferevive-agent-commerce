# Approved production unpaid booking test — October 8, 2026

The owner approved deployment, unpaid orders at submission, the existing COD
method titled **Pay at Pickup / Delivery**, and one marked unpaid live test with
customer/seller/admin notifications. The owner then supplied the test customer
email explicitly. Recipient addresses and private links are omitted here.

Production plugin remains **0.4.1**. The bridge is now enabled with
`booking_order_timing=on_submit`, `booking_order_verified=true` and
`booking_offline_gateway_id=cod`. The only settings changed from the pre-test
snapshot were the verification flag and offline gateway selection. Native
gateway settings, seller ownership and all paid readiness gates were preserved.
This record is partial production acceptance, not a claim that every incident
acceptance criterion has passed.

One human form submission produced booking
**13a320ac5e0f014c4ab89356c6f4209e** and native WooCommerce **order #2980**.
The request and notes explicitly identify an owner-approved integration test,
not a real appointment. It remains requested, order pending, payment not_started.
No charge, refund, merchant confirmation or stock/capacity reservation was made.

| Native evidence | Result |
| --- | --- |
| Product / quantity | Large Knife Sharpening, 1964 × 1 |
| Requested intake | October 9, 2026; unconfirmed |
| Handoff | Customer drop-off and collection |
| Shipping method | Native `local_pickup` |
| Service / shipping | $7.00 / $0.00 |
| Existing WooCommerce taxes | $0.65 |
| Native unpaid total | $7.65 |
| Product's enabled Dokan seller | ThriftCloset, user 254861885 |
| Dokan displayed seller earning | $7.00 |
| Dokan displayed total commission | $0.65, including native product tax allocation |
| Receipt GET reload | Same booking and order #2980 |
| Native search by marked test name | Exactly one order |

The bridge verified the native owning-seller order query and vendor metadata
before linking the order. WooCommerce's Orders row independently attributes it
to that seller; the commission panel shows native accounting. The current browser
session uses a different seller identity/store and the native seller order detail
correctly denied access. **An actual ThriftCloset login check is still required**;
admin inbox access is not presented as proof of owning-seller browser access.
No login, permission, identity or ownership change was made to work around this.

All three distinct recipient outbox records reached `accepted_by_mailer` on
attempt 1. Action Scheduler actions **880413, 880414, 880415** completed via
Async Request at 20:20:51–20:20:52 UTC, without a manual run or resend. This
proves the deployed queue ran and mail was accepted, **not inbox delivery**.
The Gmail connector returned a reauthentication requirement. Actual receipt in
the customer, actual seller and configured admin inboxes remains unverified;
the owner was asked to check those exact test messages. No additional recipients
or historical notifications were sent.

The original incident reference **167c8b10ecc1d73f6ec1600d8b373ee5** remains
requested, with no linked order and unknown contact-sharing authorization.
It was not converted, confirmed or resent. Historical recovery requires a
renewed first-party sharing grant and checking for an existing native order.
Anonymous REST access still has the previously observed hosting challenge;
this test verifies the first-party human form, not working unattended REST.
Prepayment, wallet readiness, policy and capacity remain unavailable/unverified.
ClawHub remains 0.3.0; no new registry publication or security-audit claim.

Private before/after settings and detailed observations are retained under the
ignored `.runtime/booking-live-test-0.4.1-20261008` directory. Public evidence:
[sanitized results](booking-live-unpaid-0.4.1-2026-10-08.json) and
[customer receipt screenshot](screenshots/booking-live-unpaid-order-2980.jpg).

To stop new unpaid order creation, set `booking_order_verified=false` in the
validated Agent Commerce settings, preserving existing records. Do not delete
or duplicate #2980 to resolve mail or login uncertainty. The marked test remains
pending for inspection; do not confirm it as an actual appointment or mark paid.
