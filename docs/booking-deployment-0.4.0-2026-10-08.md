# Booking deployment 0.4.0 — October 8, 2026

Subsequently patched to [0.4.1 for versioned assets](booking-patch-0.4.1.md).
The following records the initial 0.4.0 deployment and unchanged order/payment gates.

**Plugin 0.4.0 is deployed and active. Unpaid order creation remains gated.**
The owner approved production deployment and unpaid order creation on submission.
WordPress's native uploader successfully replaced 0.3.0 with the reviewed 0.4.0
ZIP from source commit `00df20c`. No ClawHub publication, payment, gateway
activation, native vendor upgrade or historical booking conversion occurred.

## Configuration and verification

- The approved timing is saved as `booking_order_timing=on_submit`.
- `booking_order_verified=false` and `booking_offline_gateway_id=""` keep native
  order creation disabled pending offline-method approval and a live unpaid test.
- The existing COD gateway is enabled, titled **Pay at Pickup / Delivery**, with
  explicit knife drop-off/counter payment instructions, local pickup restriction
  and virtual orders accepted. It was inspected but not changed. BACS and cheque
  were disabled. A question requesting approval to use this existing arrangement
  for the order bridge is pending; no new offline method was enabled.
- A separate question requests permission for one pending unpaid live test order,
  using the seller email as the test-customer address, and notifications to the
  customer/seller/configured-admin roles. No such test has been performed.
- Prepaid and wallet business preferences were already true before this update;
  they are not readiness proof. Listing handoff/pricing/live readiness and wallet
  verification remain false. Capacity remains unknown and no public service policy
  version is configured. Actual prepayment is unavailable, as the form states.

The deployed entry file reads version 0.4.0 and includes the new bridge modules;
its source matches the reviewed package after newline normalization. Existing
settings were preserved apart from the approved timing field. No database schema
migration was introduced. The updater retained plugin activation.

The real HTTPS booking form shows the native KnifeRevive logo, four sections,
one ZIP field, support links and unchecked sharing/request controls. Selecting
one Large Knife and checking coverage produces **$7 services + $0 merchant trips**,
with no online payment. This coverage POST contained no customer contact data,
submitted no booking, and created no order. [Live public form screenshot](screenshots/booking-live-0.4.0.jpg).

The deployed signed-in sharpening inbox contains original reference
`167c8b10ecc1d73f6ec1600d8b373ee5`. The administrator view shows its request; this
is not proof that a separate seller login or native seller order has been tested.
The Agent Commerce queue reports **Native order: none; Bridge: not_created;
Address/contact authorization: unknown**. The original booking was not altered,
confirmed, converted, rescheduled or resent. A full native-order metadata search
and delivery history review remain necessary before historical recovery; the
queue's missing link alone is insufficient to prove no other order exists.

## Limits and remaining acceptance

The live admin reports WordPress **7.1.3** and WooCommerce **11.2.0**, newer than
the local synthetic test stack. Existing Dokan data-update/deprecated-dashboard,
Woo compatibility and Stripe express-placement notices remain. They were not
resolved by modifying unrelated native plugins/settings. Native order visibility,
commission accounting, worker execution and real inbox receipt must be verified
on this deployed stack before leaving the order bridge enabled.

Public REST verification from the identifiable automated HTTP client returned
**403, a hosting browser challenge**, for capabilities, booking-options and
OpenAPI. A direct in-app browser REST navigation reported blocked-by-client. No
challenge was solved, bypassed, disabled or allowlisted. These are **not passing
API/schema checks**. Ordinary human booking/admin pages work. Agent clients must
handle non-JSON responses and use the human handoff; hosting API access remains an
operational issue to investigate through the provider's supported controls.

The 1,030 local synthetic assertions and contract checks remain valid local
evidence in [the implementation report](incremental-booking-0.4.0.md). They do
not establish production payment, native orders, notifications or API access.

## Backup, next steps and rollback

Before replacement, all **25 plugin-editor source files** were saved and compared
with reviewed 0.3.0 source after newline normalization. The large public coverage
asset was read in chunks to avoid UI-output truncation. A rollback ZIP contains
those live files plus the preserved 0.3.0 LICENSE omitted by the editor. Private
settings snapshots, rollback manifest and ZIP are in the ignored local deployment
directory. The upload archive matches the [0.4.0 manifest](release-manifest-0.4.0.json).
This is a file/settings backup, not a full production database backup.

After offline-method and live-test approval, use a controlled test window for the
selected arrangement and one explicitly marked pending unpaid test request.
Verify one native order, actual product-owner visibility/commission, zero courier
fee for customer drop-off/collection, no charge, and the three recipient jobs.
Confirm the deployed worker runs and check intended inbox receipt. Disable the
bridge again on any failure; never manufacture a readiness result. Prepayment
and autonomous wallet activation remain separate work.

To roll back, keep/restore order creation disabled, preserve durable booking/mail/
order rows and settings, and replace the plugin with the saved 0.3.0 ZIP. Do not
delete records, reset old notification flags or refund/charge as a rollback step.
The original customer's contact-sharing renewal and one-reference recovery still
require the procedure in [the runbook](incremental-booking-0.4.0.md).
