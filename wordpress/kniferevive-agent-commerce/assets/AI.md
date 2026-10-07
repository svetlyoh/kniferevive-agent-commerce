# KnifeRevive public commerce API

Version 1 base: `https://kniferevive.com/wp-json/kniferevive-agent/v1`.
This repository is a local release candidate. Check the deployed `/capabilities`
response before assuming that any endpoint, booking mode, or payment rail works.
The installable skill is `skills/kniferevive-concierge/SKILL.md`; OpenAPI is at
`/openapi` and in `openapi/kniferevive-agent-v1.yaml`.

## Anonymous discovery

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

## Quote, private review, authorized checkout

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
