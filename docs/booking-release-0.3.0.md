# Sharpening booking release 0.3.0

The previous production adapter 0.2.0 exposes listings but cannot create booking
requests without legacy service settings. Version 0.3.0 adds a separate booking
workflow that accepts unpaid requests independently of Stripe and the legacy
operator-service configuration. The three choices are unpaid customer drop-off,
prepaid customer drop-off and prepaid merchant pickup.

## Business rules authorized by the owner

- Friday/Saturday 9am–7pm, Sunday 10am–4pm, America/Los_Angeles.
- Customer drop-off: 304 Kapalua Bay Cir, Pittsburg; +1 (415) 483-2814.
- An 8-inch chef knife qualifies as Large Knife Sharpening. Price is read from
  WooCommerce; owner example/current listing price is $7.
- $7.99 for each merchant trip; two merchant trips $15.98. Customer-operated
  legs incur no merchant-trip fee. Native taxes, shipping and other fees remain
  separately disclosed by the authoritative cart.
- Pickup and prepayment eligibility: Contra Costa and Santa Clara counties.
  Other SF Bay Area counties: unpaid customer drop-off, pickup-coming-soon text.
  Outside SF Bay Area: no sharpening bookings, SF Bay Area only text.
- An explicitly authorized shopper bot wallet may pay directly. Merchant
  permission to receive payment does not authorize a customer's wallet send.

## Coverage data and boundary behavior

The index is generated from the [US Census 2020 ZCTA-to-county relationship
file](https://www.census.gov/geographies/reference-files/time-series/geo/relationship-files.2020.html),
with source SHA-256 and retrieval date inside the packaged JSON. The nine Bay
Area counties follow [MTC's county list](https://census.bayareametro.gov/cities-counties).
No ZIP-prefix inference or address transmission to a third-party geocoder occurs.

Census ZCTAs approximate delivery ZIPs; they are not a complete current USPS
directory and may omit post-office-only or newly introduced ZIPs. Unknown ZIPs
receive address-review messaging and cannot self-qualify. Mixed county ZIPs need
a full street address and merchant county approval before confirmation/payment.
An eligible ZIP never proves that a specific pickup address is approved.
`tools/build_booking_coverage.py` reproduces the index from the source download.

The user-facing messages are exactly:

> Pickup service is not available in your area but will be available in the near future.

> We do not currently offer sharpening services in your area. We are operating in the SF Bay Area only.

## State and payment boundaries

API draft → explicit human request → merchant confirmation → separately prepared
native checkout → native verified payment. A draft/request creates no order,
invoice, charge or slot reservation. Confirmation reserves one shared daily job
using an InnoDB lock. Missing capacity means requests only, not unlimited jobs.
Cancellation releases capacity and reports `refund_state=not_issued`; it neither
refunds nor voids a native payment. Paid/pending orders need merchant review.

Private booking access uses a separately signed, booking-specific capability
through the service date plus one day, capped at 31 days. It cannot authorize
general listing routes or another booking. Drafts expire after 30 minutes. Email
contains a private fragment link; no token enters API query strings. Delayed
checkout creates a fresh two-hour shopper scope; an expired token stays expired.
Linked checkout evidence remains available for booking/payment reconciliation.

Prepayment requires public service/cancellation terms and version, verified
native service fulfillment/pricing, an enabled native gateway and separate live
verification. The owner can authorize `booking_prepaid_enabled=true` while
operational readiness remains false. Never mark verification flags true from
fixture tests, key presence or a successful plugin upload.

`booking_wallet_enabled` records permission to accept bot wallet payment.
`booking_wallet_verified` additionally requires actual native Lightning
settlement verification and seller/order compatibility. Scoped
`/bookings/{id}/wallet-invoice` reads only an existing native order's invoice;
GET never creates a new invoice/order. It checks owner, booking, native intent,
gateway, order/quote total, immutable bridge bindings, expiry and stock hold.
The bot must independently decode and verify BOLT11, recipient, mainnet, exact
amount, wallet spending/fee ceiling and payment hash before one authorized send.
Unknown results require original-payment reconciliation, never another send.
Stripe and Google Pay remain buyer checkout flows.

**Autonomous native order/invoice creation is not implemented.** Native checkout
must first create the order and invoice. `direct_wallet_enabled=false` says so;
`authorized_wallet_payment_enabled` separately describes paying that invoice.
The merchant server does not own or provision the shopper's wallet.

## Configure and operate

Use WooCommerce → Agent Commerce, preserving the existing validated settings.
Set booking location/phone, vetted catalog service IDs/definitions, the owner's
weekly hours and `booking_enabled=true`. Business prepayment/wallet permissions
may be true, but leave readiness flags false until the following are complete:

1. Obtain actual daily capacity and public prepaid cancellation/refund terms.
2. Confirm service seller ownership and native gateway seller accounting.
3. Verify native shipping for these nonvirtual sharpening products, so generic
   shipping does not duplicate merchant trip fees. Do not change product
   ownership, seller capabilities or virtual flags to bypass native checkout.
4. Verify a native test payment, notifications, commission/payout routing,
   refund, and webhook reconciliation using the merchant-approved environment.
   No staging site exists. Switching the production Stripe gateway to test mode
   needs a bounded maintenance plan; this release does not switch modes.
5. Verify actual native Lightning recipient/bridge/settlement/refund handling
   before accepting host-wallet payments. No shopper wallet is available here.

Confirm requested jobs in the admin booking queue after capacity is configured.
For merchant trips, or mixed-county ZIPs, verify the exact street address and
county before checking the admin address-review box. Return timing is arranged
separately; confirming intake does not promise same-day completion/delivery.

## Validation and deployment evidence

The isolated tests use synthetic `krev_agent_sandbox` on loopback MySQL 11019,
never the user's live/local site database. The matrix runs classic order storage
and HPOS, with synchronization enabled/disabled. It includes native listing
checkout regressions, booking ownership/idempotency, county rules/messages,
exact $7/$7.99 arithmetic, shared capacity races and scoped original-invoice
validation. Test invoice text is intentionally not spendable.

Real processor payment, wallet send, transfer/refund, production test write,
customer email delivery and ClawHub security re-audit are not fixture claims.
Final live deployment observations and artifact checksums are recorded separately.

## Rollback

Preserve booking records, allocation rows, linked WooCommerce/Dokan orders and
native Lightning attempts. Disable new booking/payment flags if needed while
keeping native reconciliation running. The 0.2.0 ZIP and pre-upgrade settings
backup restore code/configuration, but 0.2.0 cannot serve new booking records;
retain 0.3.0 support for already submitted requests until they are resolved.
There is no new database schema migration in this release.
