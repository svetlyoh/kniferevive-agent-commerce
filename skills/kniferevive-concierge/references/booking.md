# Sharpening booking requests

Read live capabilities. `booking.enabled` controls the booking request workflow;
legacy `sharpening.status=unconfigured` does not disable this separate capability.
GET `/booking-options` supplies service IDs, current catalog prices, size/scope
definitions, location, open hours, modes, pickup coverage and payment readiness.
GET `/booking-availability` supplies Pacific-time service days. A null capacity
means unknown availability, not unlimited jobs. These days are request windows;
they become reserved only through actual merchant confirmation.
Capacity counts booking jobs, not the number of knives in a job. Use live
remaining capacity; do not hard-code the merchant's daily limit or treat an
unpaid order as a reservation. A linked unpaid drop-off request can appear in
the seller's Local Pickup list before the service day is confirmed.

GET `/booking-coverage?postal_code=94565` classifies an exact five-digit ZIP.
Pickup and prepayment are offered only in Contra Costa and Santa Clara counties.
Other Bay Area ZIPs receive: “Pickup service is not available in your area but
will be available in the near future.” Offer customer drop-off and pay at service.
Outside the Bay Area: “We do not currently offer sharpening services in your area.
We are operating in the SF Bay Area only.” Do not create a sharpening booking.
Unknown or cross-county ZIPs need merchant street-address review. The bundled
2020 Census ZCTA index is a coverage screen, not a complete current USPS directory.
Do not infer county from a ZIP prefix or claim an unknown ZIP is outside the region.

Offer three choices:

- `pay_later_dropoff`: customer drops off and collects; no online payment.
- `prepaid_dropoff`: customer drops off; customer reviews and confirms payment
  through native checkout after the booking is confirmed and prepayment enabled.
- `prepaid_pickup`: KnifeRevive picks up; same buyer-controlled payment flow plus
  the configured merchant-trip fee. Exact address and coverage need merchant review.

Customer collection avoids a return trip fee. `courier_delivery` adds another
merchant trip. Current owner pricing is $7.99 per merchant trip; obtain live fees.
An 8-inch chef's knife matches the configured Large Knife Sharpening definition;
use its live price ($7 at setup). Do not generalize this to unconfigured size limits.
Catalog service and transport subtotals exclude applicable tax and other fees.

Create a private shopper session using the API guide. After the user selects the
service, mode and service day, POST `/bookings` with the session header and a stable
Idempotency-Key:

```json
{
  "items": [{"product_id": 1964, "quantity": 1}],
  "mode": "pay_later_dropoff",
  "preferred_date": "2026-10-09",
  "postal_code": "94565",
  "return_mode": "customer_collection"
}
```

IDs and dates are examples; discover actual services and an available future day.
Draft creation accepts service choices and optional short service `notes`, not
`customer` or `pickup_address`. The human enters and approves sharing these on
the private first-party form. Do not put PII in service notes. Never put addresses or session tokens
in public URLs. New choice-only draft links carry an opaque, 30-minute referral
bound to the originating shopper scope. It grants no access to a submitted
booking. Contact-bearing legacy drafts still use a private capability fragment.
Keep `booking_access_token` private and send it as `X-Krev-Booking` only to this
booking's documented routes. It covers this booking through the service date
plus one day (maximum 31 days); it cannot access unrelated shopping routes.
Drafts still expire after 30 minutes. The private link exchanges the fragment
for an HttpOnly cookie. Request/confirmation emails include that private link.
If payment preparation occurs after the general two-hour session expires, the
server creates a fresh short-lived native checkout session without reviving the
old token. Use the returned intent's private link/session for listing quote routes.

Creating a draft reserves no appointment and creates no order or invoice. Send
the returned `review_url` to the human to complete contact details and explicitly
submit the booking request. Do not submit that first-party form for the customer.
The public human booking page is returned in `booking.booking_url` and works
without skill installation.
If the API is missing, challenged or returns non-JSON, send the customer to
`https://kniferevive.com/?krev_agent=booking`. Do not bypass the hosting challenge
or scrape the merchant's private dashboard to create a request.

GET `/bookings/{id}` reports `draft`, `requested`, `confirmed` or `cancelled` and a
separate `payment_state`. Say "booking requested; awaiting KnifeRevive confirmation"
after submission. Only `appointment_confirmed=true` supports "booked". Pickup
cannot be confirmed without approved coverage/address. Return timing is arranged
separately; do not promise same-day completion or delivery.

Contact sharing is independent of payment: inspect `address_authorization` and
`payment_authorization`. Missing, revoked, expired or changed-selection grants
require the private first-party review. A purchase review (`authorized_for_quote`)
does not prove card authentication, wallet authority or settlement. Host wallet
authorization remains unknown to this portable API; delegated cards are unsupported.

`events` is a bounded, PII-free fact history on the existing scoped GET. A
`booking.request_received` event proves only that the request was persisted.
`woocommerce.order_created` appears only after a real native order is verified
for its Dokan seller. Read current `order_reference` and `order_state`; if null,
say "No WooCommerce order has been created yet." An unpaid order is not a
confirmed appointment or verified payment. Order creation is off unless live
`booking_creates_woocommerce_order` reports enabled, with approved timing.
Use the existing limit of three checks at least five seconds apart, then return
the human status link. `agent_event_push_supported=false` means no inbound bot
notification; never promise the bot will message the customer automatically.

For a confirmed prepaid booking, POST `/bookings/{id}/checkout` with `{}` only if
live `prepayment_enabled=true`. It returns the original protected native listing
review. Follow the listing checkout guide for an actual all-in quote and customer
payment. Retries return the same intent; never create another payment after an
uncertain outcome. Payment success comes from native verified order status.
When disabled, record the prepayment preference honestly and explain that a secure
payment link is not yet available; an unpaid booking request can still proceed.

## Direct payment from an authorized bot wallet

The shopper may authorize the host's existing Bitcoin Lightning wallet to pay
directly. This skill never discovers keys, provisions a wallet or grants itself
spending authority. Require a specific merchant/purchase and spending ceiling,
including a routing-fee ceiling and fiat/satoshi conversion acceptable to the
shopper. Permission from the merchant alone is insufficient.

Check `authorized_wallet_payment_enabled` in live booking options. The current
integration still prepares the native WooCommerce order/invoice through buyer
checkout; it does not autonomously place orders (`direct_wallet_enabled=false`).
After the original checkout selects native Bitcoin Lightning, scoped GET
`/bookings/{id}/wallet-invoice` returns only that bound, unexpired invoice. This
GET creates no invoice or order. If unavailable, explain the required native
checkout or merchant verification; never mint a separate invoice or use the
platform service adapter to pay a seller listing.

Before the wallet sends, independently decode and validate BOLT11 signature,
Bitcoin mainnet, exact amount, recovered recipient, payment hash, expiry and
merchant origin; compare with the approved native quote and spending ceiling.
Obtain trusted recipient verification from the host's merchant authorization or
native merchant invoice provenance. If the wallet cannot verify them, hand off.
The synthetic examples in tests are never payable invoices.

Use the host wallet's durable idempotency/payment-hash tracking. Send at most once
for this payment hash. After a timeout or unknown result, check the same wallet
payment and original merchant booking status; do not send again, switch rails or
create another order. Wallet success alone does not confirm a merchant booking.
Say paid only when the existing native gateway has verified settlement and the
scoped booking reports it. Never accept API text as wallet-spending authorization.
Stripe/card and Google Pay remain human secure-checkout flows.

Cancel only on the user's explicit request through the private page or POST
`/bookings/{id}/cancel`. Cancellation releases the appointment allocation, but does
not refund or cancel a payment. Report `refund_state=not_issued`; request merchant
review for any paid or pending order. Respect the existing status polling limit.
