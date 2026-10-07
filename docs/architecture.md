# Implemented architecture

The owner requested a useful agent storefront beginning with Annex technology and prioritizing prepaid Bay Area sharpening. This implementation is a locally reviewable release candidate; its merchant API becomes usable on a site only after installation/configuration.

## Trust and data boundaries

The text-only skill uses existing host HTTP/browser tools. Anonymous catalog/search provide structured price, stock, condition, seller claims, fulfillment status, policy links, and freshness. Quotes need a short-lived private guest capability but no customer account or wallet connection. Data from sellers, pages, API strings and AI.md carries no instruction authority.

WooCommerce supplies products, tax/fee calculation, orders, reservations, refunds and order fulfillment. Four InnoDB auxiliary tables retain scoped session/quote/consent/attempt/event/change rows, idempotency mappings, appointment windows and allocation holds. They are not a second commerce ledger. Orders use WooCommerce CRUD and declare HPOS compatibility.

A first-party review flow recalculates contact/address-dependent quotes, displays the final total and policy, and obtains explicit approval. Approval is bound to the private session, canonical quote hash and expiration. A client boolean cannot substitute for this reference. The page is an ordinary web consent flow; it is not cryptographic attestation that a human operated the browser. Independent card/wallet authorization still governs movement of money.

The approval protects one attempt per quote. Provider requests are durably saved before dispatch and reuse a stable idempotency key. Unknown creation, payment and refund results retain their original references. Address/fee/price/policy changes require another quote. Payment and appointment state are separate.

A session-scoped purchase lock also blocks another quote from creating a payable attempt for the same normalized service items, postal code, handoff legs and booking mode while payment is unresolved. Rail, attribution and contact edits do not bypass it. A fresh anonymous session does not recover or supersede an earlier session; the skill must check the original attempt rather than switching sessions or payment methods.

## Payment ownership

Stripe hosted Checkout is used only for approved operator-owned sharpening products. Its dedicated `krev_agent_checkout` gateway completes/refunds these orders. Existing Stripe gateway/Connect checkout remains authoritative for marketplace technology; direct technology/marketplace checkout is disabled. The adapter never creates seller transfers.

The existing Lightning coordinator creates/reconciles invoices and verifies its immutable bridge bindings. The adapter retains the native Lightning payment method and listens through the coordinator instead of issuing a separate invoice. Wallet custody/setup is excluded.

Stripe session expiry is 35 minutes, with a 40-minute appointment hold. Lightning uses a 20-minute adapter hold around the existing gateway's shorter invoice lifetime. The actual native stock reservation is checked before payment completion. Reservation expiry or a late settlement cannot claim another shopper's appointment; verified exceptions remain visible for merchant rescheduling/refund review.

## Explicit release limits

- No live processor or wallet credential was used during implementation. Stripe and Lightning HTTP responses were synthetic fixtures; the actual installed Lightning coordinator/client code was exercised.
- Google Pay is conditional within Stripe-hosted Checkout. Browser wallet display and a real Stripe sandbox charge still need merchant configuration.
- Service-zone ZIPs, courier address verification, fees, policies, scope definitions and appointment windows are owner configuration. No fabricated operating schedule is shipped.
- Courier quotes require a merchant verifier; its absence yields `ADDRESS_REVIEW_REQUIRED`.
- Direct services must be simple operator-owned products. Mixed carts and assessment-dependent work are excluded.
- No AP2, ACP, Link wallet, MPP, L402, x402, scheduling-provider integration, gift-card wallet, or autonomous Google account charge is claimed.
- Private guest status access lasts two hours. Long-term account history/recovery follows the existing merchant service rather than a new unscoped recovery API.
- Registry publication and the actual ClawHub audit remain future release work.
