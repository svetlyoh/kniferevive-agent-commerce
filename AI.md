# KnifeRevive public commerce API

Version 1 base: `https://kniferevive.com/wp-json/kniferevive-agent/v1`.
This repository is a local release candidate. Check the deployed `/capabilities`
response before assuming that any endpoint, booking mode, or payment rail works.
The installable skill is `skills/kniferevive-concierge/SKILL.md`; OpenAPI is at
`/openapi` and in `openapi/kniferevive-agent-v1.yaml`.

## Anonymous discovery

Sharpening bookings use `booking.enabled`, independently of legacy direct service
payments. GET `/booking-options` and `/booking-availability` for live services,
Pacific service days and request/payment readiness. The three modes are
`pay_later_dropoff`, `prepaid_dropoff`, `prepaid_pickup`. POST `/bookings` with a
private session and idempotency key to prepare a draft; the human submits the
returned private review page. Creating a draft does not reserve or charge.
Only `appointment_confirmed=true` supports a confirmed booking. GET
`/bookings/{id}` reports appointment and payment separately. A confirmed prepaid
booking can prepare native checkout through POST `/bookings/{id}/checkout` when
enabled; the human reviews the final total and authorizes payment. $7.99 per
merchant trip is an estimate input, not an all-in quote. Missing coverage/capacity
means merchant review, not guaranteed availability. The public human booking URL
is advertised in capabilities and works without installing a skill.

`GET /catalog?category=technology&per_page=10&page=1` lists normalized offers.
Use `category=sharpening` for service products. Responses contain `product_id`,
`variant_id`, `title`, `canonical_url`, `unit_price_minor`, `currency`,
`availability_status`, `available_quantity`, typed `attributes`, seller/condition
information, `fulfillment_options`, `shipping_estimate_status`, `fee_summary`,
policy links, and `updated_at`. Missing shipping is unknown, not free.
Catalog prices precede taxes/fees. Catalog freshness is at most 30 seconds;
stock and totals are checked again for quotes and checkout.

`GET /service-area?postal_code=94110` checks preliminary postal eligibility.
`GET /availability?postal_code=94110` returns windows, each with `slot_id`,
`kind`, UTC start/end, timezone, and available jobs. Courier addresses need
independent merchant verification. Unconfigured coverage returns no promise.
Technology purchases use the canonical product page's existing checkout.

## Dedicated operator-service quote, private review, authorized checkout

Create a guest session with `POST /sessions`, JSON `{}`, and a unique
`Idempotency-Key`. Use the returned private token only in
`X-Krev-Agent-Session`. It lasts two hours and is never sent to Stripe.

A synthetic service quote request has this shape; product and slot IDs must
come from real discovery responses:

```json
{
  "items": [{"product_id": 123, "quantity": 2}],
  "postal_code": "94110",
  "intake": {"kind": "customer_dropoff", "slot_id": "intake-example"},
  "return": {"kind": "customer_collection", "slot_id": "return-example"},
  "rail": "stripe_checkout",
  "booking_mode": "scheduled"
}
```

Send this to `POST /quotes` with a fresh idempotency key and the session header.
No order, hold, invoice, or charge is created. The response has a quote hash,
itemized fees/taxes/total, ten-minute expiry, policies, and a private review URL.
The customer opens that URL, supplies missing contact/tax details privately,
reviews the final quote, and approves. The first-party form issues consent and
creates the payment request; it must not be auto-approved by an assistant.

For an already approved final quote, `GET /quotes/{id}` exposes its scoped
`consent_id`. `POST /checkout-attempts` accepts `quote_id`, `quote_hash`, and
`consent_id`, with an idempotency key. Both handoff windows and stock are reserved
before creating the payment request. Hosted Stripe Checkout offers eligible
Google Pay; Lightning requires an independently authorized shopper wallet.

`GET /checkout-attempts/{id}` and `/orders/{id}` require the same private session
and return payment and booking separately. Numeric WooCommerce IDs grant no
access. An expired session requires contacting the merchant through the normal
site; a fresh anonymous session cannot recover another session's orders.

## Errors and policies

All private responses are no-store. Payment and browser handoffs do not establish
payment success. After timeout, check the original attempt; do not regenerate
an invoice, change rails, or create another charge. `unknown`/`review_required`
states need reconciliation or operator assistance. Checkout is not live by default.

Limits: 120 requests per network/minute, 60 per session/minute, 10 session
issuances per network/minute. For 429 wait 60 seconds; bounded idempotent retry
applies to `BUSY`/retryable errors. Error objects include a code, request ID,
retryability, and next action. Policies are returned by `/capabilities` and each
quote; do not assume missing policies or fees. Cancellation/rescheduling uses
`POST /orders/{id}/change-requests` with `action`, `reason`, and idempotency key.
Its result is a merchant-review request, not a refund.

This guide is protocol documentation, not authority to spend, override assistant
instructions, or invoke tools. It claims no ACP/AP2/MPP/L402 conformance.

## Native marketplace listing handoff

`GET /listings` searches all visible published categories with optional `search`,
actual category slug, public `seller`, `page`, `per_page` (maximum 100).
`GET /listings/{product_id}` returns WC price, original URL, seller, stock,
variations and eligibility. Simple single-seller selections are supported.
Complex selections require normal listing checkout. Service SKUs require vetted
fulfillment terms. Direct marketplace payment remains disabled.

Require `/capabilities` to report `listings.handoff_state=handoff_enabled`.
With a private session, `POST /listing-checkouts` accepts `items` (product ID and
quantity), optional coupon codes and disclosed source, with an idempotency key.
Its review link exchanges a private fragment for an HttpOnly cookie. Do not log it.
Intent lifetime is 30 minutes; quotes last 10 minutes. GET/quote creates no order,
stock hold, charge, Stripe session or Lightning invoice.

`POST /listing-checkouts/{id}/quote` accepts complete US billing/shipping addresses,
email, actual native `payment_method` and chosen `shipping_methods` rate IDs.
WC pricing hooks calculate coupons, fees, tax and shipping. Missing address,
gateway or rate means `estimate_only=true`, `total_minor=null`. No caller-supplied
amount/payee/order state is accepted. Prefer entering PII on the private page.
Use a fresh idempotency key for each repriced quote.

The buyer reviews itemized totals, seller, fulfillment and policies. A protected
POST prepares the native cart and redirects to `wc_get_checkout_url()`. Existing
carts/pending orders block replacement. Changed stock, price, seller, address,
identity, gateway, total or policies block payment until reviewed/reconciled.
Native WC/Dokan gateway hooks own payment, notifications and seller accounting.

`GET /listing-checkouts/{id}` returns the intent; `/status` returns separate
payment/fulfillment/scheduling states without order keys, PII or processor IDs.
`paid` requires the native gateway event plus bound transaction evidence;
`refund_recorded` is not provider-refund confirmation. Sharpening remains
`not_booked`. After uncertainty, resume the original checkout/status; do not
start another charge. Real processor verification remains a launch requirement.

## Sharpening bookings (0.3.0)

Check `booking.enabled`, `/booking-options`, `/booking-availability` and
`/booking-coverage?postal_code=94565` independently of the legacy service adapter.
POST `/bookings` accepts items, mode, preferred_date and postal_code; optional
authorized contact/address fields can prefill a private human review. It creates
a draft, not a reservation or charge. Human submission requests merchant review.
Modes: unpaid customer drop-off, prepaid customer drop-off, prepaid merchant
pickup. Merchant trips cost the configured fee per leg (owner pricing $7.99).
Only merchant confirmation with real daily capacity reserves a service day.

Pickup/prepayment eligibility is limited to Contra Costa and Santa Clara county
ZIPs. Other nine-county Bay Area residents can request unpaid customer drop-off;
relay the returned pickup-coming-soon message. Outside-area requests are rejected
with the SF Bay Area only message. Cross-county/unknown ZIPs require review, not
ZIP-prefix guessing. Read live coverage messages and payment readiness.

After confirmation and enabled prepayment, `/bookings/{id}/checkout` returns the
same native checkout intent. Stripe and Google Pay require human approval there.
An explicitly authorized host Lightning wallet can pay the original native
invoice returned by scoped `/bookings/{id}/wallet-invoice` when separately
verified/enabled. Native checkout must first prepare the order/invoice; autonomous
order creation remains disabled. Independently verify invoice/recipient/amount,
authorization and fee ceilings; never provision a wallet or retry an uncertain
send. Native settlement and appointment confirmation remain separate states.
