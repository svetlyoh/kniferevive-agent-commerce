# KnifeRevive public commerce API

Version 1 base: `https://kniferevive.com/wp-json/kniferevive-agent/v1`.
This repository is a local release candidate. Check the deployed `/capabilities`
response before assuming that any endpoint, booking mode, or payment rail works.
The installable skill is `skills/kniferevive-concierge/SKILL.md`; OpenAPI is at
`/openapi` and in `openapi/kniferevive-agent-v1.yaml`.

## Anonymous discovery

Sharpening bookings use `booking.enabled`, independently of legacy direct service
payments. GET `/booking-options` and `/booking-availability` for live services,
Pacific service days and request/payment readiness. Present the four customer
choices from `handoff_options`. Backend 0.5.6 also advertises four distinct `modes`:

1. Drop off and pay when collecting — no transport fee or ZIP check.
2. Drop off and prepay online — no transport fee; eligible ZIP and payment required.
3. We pick up, you collect — $6 pickup transport; eligible ZIP and payment required.
4. We pick up and deliver back, the comeback combo — $11 total transport;
   eligible ZIP and payment required.

Option 4 uses `mode=prepaid_pickup_delivery` and defaults to return delivery.
Legacy `mode=prepaid_pickup` plus `return_mode=courier_delivery` also works; option 3
uses the same mode plus `return_mode=customer_collection`. They are distinct
customer choices. Use live prices; existing requests retain their original quote.
Stored receipts retain the canonical pickup/return representation for compatibility.
POST `/bookings` with a
private session and idempotency key to prepare a draft; the human submits the
returned private review page. Creating a draft does not reserve or charge.
Only `appointment_confirmed=true` supports a confirmed booking. GET
`/bookings/{id}` reports appointment and payment separately. An eligible prepaid
request can prepare native checkout through POST `/bookings/{id}/checkout` when
enabled; the human reviews the final total and authorizes payment. Prepaid choices
remain `awaiting_payment` until verified native payment sends the request to the
merchant. Transport prices are separate from sharpening and applicable tax;
they are not all-in quotes. Missing coverage/capacity
means merchant review, not guaranteed availability. The public human booking URL
is advertised in capabilities and works without installing a skill.

Booking receipts include `refund_summary`: recorded amount and original-gateway
accepted amount are separate, and neither proves arrival in a bank or wallet.
Cancellation closes a bound unpaid service order without issuing a refund. Paid
cancellation preserves payment evidence and needs separate merchant refund review.
Native refund events are keyed per occurrence. Sellers use authenticated merchant
controls; buyers poll the same private booking receipt. A refund does not authorize
the bot to obtain merchant credentials or provide a new payout destination.

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

## Sharpening bookings (backend 0.5.6; check deployed capabilities)

Read `/booking-options` and its four `handoff_options` before proposing choices:
1. Customer drops off and pays when collecting: no ZIP lookup or ZIP field needed.
2. Customer drops off and prepays online: eligible service ZIP required; no transport fee.
3. KnifeRevive picks up; customer collects: eligible pickup ZIP and exact address; $6 transport.
4. KnifeRevive picks up and delivers back: eligible pickup ZIP and exact address; $11 total transport (the comeback combo).
Prices exclude sharpening and applicable taxes; trust live amounts and the final native quote.
API modes are `pay_later_dropoff`, `prepaid_dropoff`, `prepaid_pickup`, and
`prepaid_pickup_delivery`. The fourth defaults to return delivery; an explicitly
conflicting collection value is rejected. The legacy `prepaid_pickup` plus
`return_mode=courier_delivery` combination remains valid and shares the same
canonical draft/idempotency handling as the distinct fourth mode.

POST `/bookings` accepts service items, mode, preferred_date, optional postal_code
and return_mode. Contact/address fields are rejected in portable draft creation;
the human supplies them and approves their use on the protected form. Only
options 2–4 require `/booking-coverage`; they are limited to supported Contra
Costa and Santa Clara ZIPs, with the configured pickup ZIP list narrowing
merchant trips. Relay the live coverage message without guessing ZIP prefixes.
The customer can instead select option 1 and travel to KnifeRevive themselves.

Under `payment_required_before_submission=true`, options 2–4 save an
`awaiting_payment` request. Continue to Payment is required. Only verified native
gateway settlement submits it to the merchant and queues booking notifications;
an order, redirect, manually changed order status, or payment preparation alone
is insufficient. A native pending financial order may exist during payment;
it is not a submitted service appointment. The seller confirms the day/address
after payment. No charge is created by a GET or a draft.

The human uses the independent private booking link and WooCommerce's secure
checkout. Booking checkout has its own cart/session; other shopping carts and
pending orders remain saved. An existing or uncertain booking order cannot be
replaced. Return to its original order and reconcile it instead of retrying.
Expired unstarted checkout can resume the same intent, with explicit review.

Stripe and available Google Pay require human approval. A host-authorized
Lightning wallet may pay only the original native invoice returned by
`/bookings/{id}/wallet-invoice`, when separately enabled and verified. Never
provision a wallet, infer spending authority, or retry an uncertain send.

Payment, appointment, cancellation, and refund are independent facts. Native
seller cancellation does not issue a refund. Seller-approved refunds use the
original native gateway/order and expose scoped refund receipts; gateway
acceptance is not proof of bank/wallet arrival. Poll the original booking at
most three times, at least five seconds apart, then use the human status page.
No portable skill receives unsolicited bot-chat push events by itself.
