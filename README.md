# KnifeRevive Agent Commerce

The new [Muse Marketplace → ListLab importer](docs/muse-listlab-import-1.0.0.md)
adds a separate WooCommerce companion plugin with category pricing controls and
private seller completion links. The separate [KnifeRevive Listing 1.0.1 skill](skills/kniferevive-listing/SKILL.md)
routes “List this on KnifeRevive” to that workflow. Concierge 0.6.3 handles shopping
and sharpening. See [installation and validation](docs/kniferevive-listing-skill-1.0.0.md)
and [description/knife attribute prefilling in 1.0.1](docs/marketplace-import-1.0.1.md).

A merchant storefront skill and WooCommerce adapter. **Agent Commerce 0.5.17** is deployed with five sharpening choices, two-screen native prepaid checkout, seller-scoped Local Pickup orders and daily capacity 4. Confirmed bookings now show the original order’s native sharpening stage in Seller Orders → Local Pickup, with separate order and booking links; see [the 0.5.17 implementation](docs/confirmed-booking-native-orders-0.5.17.md). The [recipient-name and original-order recovery](docs/recipient-and-original-order-payment-0.5.16.md) supplies the saved contact name and resumes payment on the same pending order. The [saved-booking/Cash App handoff repair](docs/cashapp-booking-handoff-0.5.15.md) retains the booking on the final payment POST and recognizes verified native Cash App payment events. The [checkout POST repair](docs/checkout-routing-fix-0.5.14.md) prevents customer names from triggering a Page Not Found error. The normal sharpening cart now enters the same booking flow with its knife quantities prefilled; mixed-cart goods stay available for separate ordinary checkout. Both fees are editable in **WooCommerce → Agent Commerce → Sharpening trip fees**; see [the live controls and label verification](docs/trip-fee-settings-0.5.13.md). One pickup or delivery trip costs $6 per order; pickup plus delivery is the $11 comeback combo. Trip charges are taxable native fees, separate from sharpening and the store's parcel shipping policy. The unpaid drop-off/collection choice is last and skips ZIP checking; four prepaid choices require eligible coverage and completed native payment before merchant submission. Booking sessions preserve other storefront carts, and a different billing address preserves the service destination. See the [storefront implementation and verification record](docs/storefront-sharpening-checkout-0.5.11.md).

Installed/published versions and payment gates are recorded in [publication status](docs/publication-status.md), [0.4.2 skill evidence](docs/skill-publication-0.4.2.md) and [live seller visibility/capacity evidence](docs/seller-booking-visibility-0.4.2.md). A release does not enable payments. Real payment, seller settlement/refund and policy checks remain required. See the [inventory/webhook audit](docs/listing-checkout-audit-0.2.0.md) and [native checkout runbook](docs/listing-checkout-runbook-0.2.0.md).

[Get the skill on ClawHub](https://clawhub.ai/svetlyoh/kniferevive-concierge) · [Download 0.5.14](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/skill-v0.5.14) · [0.5.14 audit](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge/security-audit?version=0.5.14)

## Install with OpenClaw

Current skill **0.5.14** retains visible numeric trip fees and adds verified HTTP client compatibility guidance. Public production discovery works with PowerShell HTTP/2, Node fetch, Python urllib and curl; the failing PowerShell HTTP/1.1 client still receives a hosting HTML challenge. See [the live evidence and read-only diagnostic](docs/public-api-client-compatibility-0.5.14.md). All eight published registry files and an isolated download match the [immutable GitHub skill source](https://github.com/svetlyoh/kniferevive-agent-commerce/tree/skill-v0.5.14/skills/kniferevive-concierge). The affected buyer bot and renderer still need their own fresh API read; no remote reload or payment is claimed. See [this version's publication/security record](docs/skill-publication-0.5.14.md) and [the five-choice implementation](docs/booking-trip-fees-two-screens-0.5.7.md).

From an already installed OpenClaw environment, in a Linux terminal or Windows
PowerShell:

```text
openclaw skills install @svetlyoh/kniferevive-concierge
openclaw skills check
```

See [Installation and agent prompts](skills/kniferevive-concierge/references/installation.md)
for workspace/shared installation, WSL guidance, and separate Meta Muse, Grok Bot
and OpenAI dot adoption prompts. Muse Code is a separate developer-tool pathway.
A prompt does not prove native installation or authorize payment.

The ClawHub CLI also supports downloading a registry skill into its selected
workspace directory. This is a registry download; use the native commands above
for an active OpenClaw workspace. Published version example:

```sh
clawhub install @svetlyoh/kniferevive-concierge --version 0.5.14
```

Check the [exact-version publication evidence](docs/skill-publication-0.4.2.md) for audit results, warnings and provenance limits. Earlier passes do not certify a new version. The Skill Card is registry-generated; author-written installation help appears in SKILL.md and the linked guide.

## Components

- `skills/kniferevive-concierge`: portable text-only skill, with MIT-0 license and no installers, binaries, wallet setup, required credentials, or background promotion.
- `wordpress/kniferevive-agent-commerce`: standalone plugin, PHP 8.2+, WooCommerce CRUD/HPOS support, InnoDB storage, merchant settings, private review pages, and payment reconciliation.
- `openapi/kniferevive-agent-v1.yaml`: generated OpenAPI 3.1 contract, encoded as JSON (valid YAML).
- `AI.md`: factual public API quick-start, also packaged in the plugin and served at `/AI.md` after activation.
- `tests`: real WordPress/WooCommerce/MySQL behavioral tests with synthetic customers and blocked external requests; separate browser and schema checks.

The implementation followed the owner's October 7, 2026 architecture brief. See `docs/architecture.md`, `docs/data-flow-and-permissions.md`, `docs/operations-and-recovery.md`, and `docs/release-evidence.md` for integration decisions, operational limits, and actual verification.

Version 0.1.3 passed 91 behavioral assertions in each of WooCommerce's legacy and HPOS storage modes, including $7.99 per merchant trip and $15.98 for pickup plus return delivery. Version 0.1.1 passed 16 merchant Stripe setup assertions; the original release passed schema, skill, and browser checks. See [merchant setup evidence](docs/merchant-setup-0.1.1.md) for current deployment and remaining gates. [Publication and discovery](docs/publication-and-discovery.md) describes release and measurement steps.

## Local installation and configuration

Install the plugin ZIP through WordPress, or copy its folder into `wp-content/plugins` and activate it on a staging/local site. Activation creates four auxiliary tables and a reconciliation job; it creates no customer order, invoice, transfer, wallet, or payment account. It never edits existing plugins or core files.

Open **WooCommerce → Agent Commerce**. The settings editor accepts validated JSON. Start from the default configuration and enter actual business facts:

1. `merchant_ids`: WordPress operators with `manage_woocommerce`, whose service products are owned by KnifeRevive. Direct checkout rejects seller-owned marketplace services.
2. `services`: published simple products in `knife-sharpening`, each with its exact size/scope definition. Neither product IDs nor prices are hardcoded.
3. `postal_codes`, `location`, `policy_url`, `policy_version`, and optional `return_policy_url`: verified coverage/location and publicly reviewed service/cancellation/refund policies.
4. `slots`: stable IDs, handoff kind, ISO timestamp start/end with UTC or correct LA offset, capacity in **jobs**. Configure intake and later return separately. Occupied slots cannot be moved or reduced below their allocations. Removed windows stop accepting new bookings; their old holds/evidence remain.
5. `transport`: optional per-leg integer-cent fee, taxability and tax class, plus an independently installed merchant address verifier. `transport_round_trip_minor` optionally sets an exact combined pickup/return price (null preserves separate pricing). For current booking requests, use `booking_trip_fee_minor: 600`, `booking_round_trip_minor: 1100`, and `booking_transport_taxable: true`. Older direct-commerce transport configuration and existing records are separate; do not migrate quoted amounts. Both courier legs must share tax treatment. The total may be staged with `transport: []` while coverage/taxes/verifier remain unresolved. Customer drop-off/collection incur no courier fee.
6. `pending_scheduling`: enable only if the merchant actually sells explicitly unscheduled prepayment under the displayed policies.
7. `pricing_verified`: set only after comparing the actual site's cart fee/tax plugins with API quotes. Quote pricing runs existing before-calculation and fee hooks with an in-memory cart/session. Cart-session persistence hooks are omitted; compatibility with other pricing plugins must be tested.
8. `enabled`, `stripe_enabled`, `lightning_enabled`: enable only the tested journeys. `live_verified` is an explicit operator gate for live Stripe and Lightning acceptance.

The technology taxonomy defaults to `technology`; change `technology_category` if the actual site's Annex category differs. Technology results deliberately use the existing WooCommerce checkout rather than this adapter's direct orders. Mixed tech/service carts are rejected by the direct service interface.

## Stripe configuration

For WordPress-managed hosting, an administrator can set `stripe_use_woocommerce_keys` to `true` in WooCommerce → Agent Commerce. This explicitly reuses the official WooCommerce Stripe gateway's existing secret key for the selected environment; configured server constants take precedence. Click **Connect dedicated Stripe test webhook** from the HTTPS production administration page to register only this adapter's test events. Registration does not enable payments or change the official gateway's webhooks. The dedicated signing secret is encrypted in a non-autoloaded WordPress option using AES-256-GCM and a key derived from WordPress salts. Changing those salts invalidates decryption and requires administrator review of the original Stripe endpoint. Live webhook configuration still uses a server constant and requires a separate verified launch.


Supply constants through the site's server-managed configuration or secret manager. Their names are:

- `KREV_AGENT_STRIPE_TEST_SECRET_KEY`
- `KREV_AGENT_STRIPE_TEST_WEBHOOK_SECRET`
- `KREV_AGENT_STRIPE_LIVE_SECRET_KEY`
- `KREV_AGENT_STRIPE_LIVE_WEBHOOK_SECRET`

Never put their values in this repository, the skill, API requests, or a shared transcript. The adapter uses a documented Stripe API version, `2025-03-31.basil`, and the exact origin `https://api.stripe.com`, with TLS verification and no redirects. Verify the pinned version and session parameter behavior against the merchant account in a real Stripe test environment before enabling live payments.

Webhook destination: `/wp-json/kniferevive-agent/v1/stripe/webhook`. Subscribe to `checkout.session.completed`, `checkout.session.expired`, relevant `charge.refunded`, and dispute events. Webhooks have signature/time/environment checks; reconciliation retrieves the session/charge independently. Only card-family methods are offered in the initial hosted flow. Google Pay requires appropriate Stripe settings and customer device eligibility; hosted Checkout handles its payment interface. Follow the current [Stripe Google Pay instructions](https://docs.stripe.com/google-pay?platform=web) and verify actual wallet display.

The adapter's payment method is `krev_agent_checkout`, separate from the existing Stripe gateway. It does not trigger the current Connect plugin's `stripe*` seller-transfer path. Direct orders are therefore restricted to operator-owned services. Existing Stripe checkout and seller payments are preserved. Refunds for adapter orders use its operator-only WooCommerce refund gateway; uncertain refund requests retain their original idempotency key and block a different refund until reconciled.

## Lightning configuration

Requires the existing `kniferevive-lightning-payments` plugin, whose approved merchant IDs, acceptance, maximum amount, bridge credentials, and settlement integration must already work. It was tested against local version **0.1.13** with mocked bridge responses, not a live Lightning payment. The adapter reuses the installed `Coordinator`, `Repository`, `Client`, and `Settings`; it does not distribute their code or credentials.

The shopper sees an order-bound BOLT11 invoice and scoped status. Node keys, bridge credentials, and Access credentials stay backend-only. The existing bridge validates mainnet/amount/immutable payment bindings. Shopper wallet invoice decoding, payee policy, and spending authorization remain independent. Refunds and late-settlement exceptions go to the existing Lightning operator workflow.

## Build and test

Run `python tools/build_contract.py` after changing the API/docs. It writes the contract and plugin copies of OpenAPI and AI.md. Install test-only Python requirements into an isolated runtime directory; they are excluded from release archives. Test command details are in `docs/operations-and-recovery.md`.

Run PHP syntax checks, `tests/integration.php` against the dedicated loopback sandbox, `tests/contract.py`, the skill validator, and `tests/browser.cjs`. The fixture database is `krev_agent_sandbox` on loopback port 11019 with prefix `krev_sandbox_`; the test bootstrap never loads the real site's wp-config.php. The browser server is a separate loopback fixture on port 11080. Never point these bootstrap scripts at production or public networking.

Run `python tools/package.py` to produce the plugin and skill ZIPs and an exact file/hash manifest. The packager includes only explicit component folders and rejects secret/config/database/bytecode files. Activate/review locally or on staging; publish the tested source and exact skill version only when the owner requests it.

## Scope and maintenance

The skill's network origins are KnifeRevive and verified Stripe checkout handoffs. Status links are private, and guest sessions expire after two hours. A fresh anonymous session cannot recover an old session's orders; use normal merchant assistance after expiry. The public skill cannot change inventory, order stages, seller payouts, refunds, or customer permissions.

Attribution stores a disclosed task source on quotes/orders. There is no background crawler, download farming, outbound marketing, or hidden tracking pixel. Measure completed service orders and refunds using merchant records; genuine registry installs can be measured only after actual publication. Performance targets and ClawHub audit outcomes remain unverified until measured/submitted.

Skill/docs/tools: MIT-0. WordPress plugin: GPL-2.0-or-later. External dependencies retain their own licenses.
