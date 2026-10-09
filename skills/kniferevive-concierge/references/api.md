# API workflow

Production base: `https://kniferevive.com/wp-json/kniferevive-agent/v1`.
Use the merchant's `/openapi` endpoint for the exact released JSON contract.
Only capabilities returned by the deployed service are actionable.

For unpaid or optionally prepaid sharpening bookings, use the separate
`booking.enabled` capability and [booking workflow](booking.md). It does not
require legacy `/quotes` or `sharpening.direct_checkout`; a disabled legacy
service workflow must not hide an enabled unpaid booking request option.

1. `GET /capabilities`, `GET /catalog?category=technology|sharpening`, and
   `GET /service-area?postal_code=94110` require no login or secrets. Availability
   is `GET /availability?postal_code=94110`, optionally filtered by `kind`.
2. Legacy technology results use `existing_woocommerce_checkout`. Marketplace
   discovery uses `/listings` across published categories, and native buyer
   handoff when enabled. See [listing checkout](listing-checkout.md) for its six
   separate routes. No marketplace provider sessions are created by the adapter.
3. To quote supported services, create a private guest session with
   `POST /sessions`, JSON `{}`, and a unique `Idempotency-Key` of 12–128 letters,
   digits, colons, underscores, or hyphens. Retain its returned session token
   privately. Its lifetime is two hours; a fresh session cannot access old orders.
4. Use `X-Krev-Agent-Session` and `Idempotency-Key` for `POST /quotes`.
   Required fields: `items` (product_id/quantity), `postal_code`, `intake`, `return`,
   `rail`, and `booking_mode`. Service products are simple operator-owned products.
   The merchant returns a hash, expiry, totals, and private review URL.
5. Open that private review URL for the customer. The fragment transfers the
   capability to a first-party HttpOnly cookie. Missing customer information is
   collected there, recalculated, and reviewed as a new quote. The customer then
   approves and creates payment details. Do not automate the approval click.
6. Alternatively, after the customer approves the final quote, retrieve
   `GET /quotes/{id}` to obtain its `consent_id`. `POST /checkout-attempts` takes
   only `quote_id`, `quote_hash`, `consent_id` and an idempotency key. Reuse the
   same request/key on network failure. A quote cannot create two attempts.
7. Retrieve `/checkout-attempts/{id}` or `/orders/{id}` with the session header.
   Numeric WooCommerce order IDs are not API authorization. Request cancellation
   or rescheduling via `/orders/{id}/change-requests`, with `action`, `reason`,
   and an idempotency key. The result is a merchant-review request, not a refund.

Do not forward the token to other hosts or put it in API query strings. Private
responses use no-store. Public catalog freshness is 30 seconds and quote expiry
is ten minutes. A final quote may change after address-based tax calculation.
Never treat an unknown shipping charge as free.

Handle `UNCONFIGURED`, `OUT_OF_AREA`, `ADDRESS_REVIEW_REQUIRED`,
`ASSESSMENT_REQUIRED`, and disabled rails with an honest site handoff.
`QUOTE_CHANGED`/`QUOTE_EXPIRED` require a new quote and matching consent.
`BUSY` permits retrying the same operation. For HTTP 429 wait 60 seconds.
`PAYMENT_UNRESOLVED` requires checking the original equivalent purchase; another
quote, payment method or shopper session must not be used to bypass it.
`unknown` or `review_required` payment state requires original-attempt status
checking or merchant assistance, not a fresh charge.

Booking-specific routes accept the private `X-Krev-Booking` capability returned
with a draft, independently of the general two-hour shopper token. See
[Booking requests](booking.md) for its narrow scope and expiry. Use JSON Accept;
if the host HTTP tool supports an identifying User-Agent, identify this merchant
client honestly (e.g. KnifeRevive-Concierge/0.5.13). A hosting challenge or non-JSON
response is not an API result. Use the human booking page; never bypass the challenge.
