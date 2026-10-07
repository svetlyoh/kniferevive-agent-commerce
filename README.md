# KnifeRevive Agent Commerce

A local release candidate implementing the KnifeRevive Concierge storefront skill and a WordPress/WooCommerce adapter. Annex technology discovery is available through a structured catalog and existing product checkout. The adapter supports authorized, prepaid, operator-owned sharpening orders through hosted Stripe Checkout or the existing KnifeRevive Lightning coordinator, with separate intake/return appointments.

**Merchant plugin 0.1.1; portable skill 0.1.0 — release candidates. New payments are disabled by default.** Deployment, processor setup and operational rules require verification before the skill can advertise working booking or payments. The owner authorized GitHub and ClawHub publication on October 7, 2026; publication does not verify live payments. See [publication status](docs/publication-status.md) for the skill release and audit results.

[Get the skill on ClawHub](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge) · [Download the release](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/v0.1.0) · [Security audit](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge/security-audit?version=0.1.0)

Install the published skill with the ClawHub CLI:

```sh
clawhub install @svetlyoh/kniferevive-concierge --version 0.1.0
```

The observed ClawHub security result is clean/benign with no warnings. Its separate Skill Card verification currently reports `card.missing`; full card/provenance verification is therefore not claimed. See the version-specific evidence before deciding to install.

## Components

- `skills/kniferevive-concierge`: portable text-only skill, with MIT-0 license and no installers, binaries, wallet setup, required credentials, or background promotion.
- `wordpress/kniferevive-agent-commerce`: standalone plugin, PHP 8.2+, WooCommerce CRUD/HPOS support, InnoDB storage, merchant settings, private review pages, and payment reconciliation.
- `openapi/kniferevive-agent-v1.yaml`: generated OpenAPI 3.1 contract, encoded as JSON (valid YAML).
- `AI.md`: factual public API quick-start, also packaged in the plugin and served at `/AI.md` after activation.
- `tests`: real WordPress/WooCommerce/MySQL behavioral tests with synthetic customers and blocked external requests; separate browser and schema checks.

The implementation followed the owner's October 7, 2026 architecture brief. See `docs/architecture.md`, `docs/data-flow-and-permissions.md`, `docs/operations-and-recovery.md`, and `docs/release-evidence.md` for integration decisions, operational limits, and actual verification.

The local release passed 75 behavioral assertions in each of WooCommerce's legacy and HPOS storage modes, seven actual API schema checks, skill validation, and browser review/checkout checks. Version 0.1.1 also passed 16 merchant Stripe setup assertions. See [release evidence](docs/release-evidence.md) for the original tested scope and remaining gates. The Local Sites plugin is activated with paid booking disabled. [Publication and discovery](docs/publication-and-discovery.md) describes release and measurement steps.

## Local installation and configuration

Install the plugin ZIP through WordPress, or copy its folder into `wp-content/plugins` and activate it on a staging/local site. Activation creates four auxiliary tables and a reconciliation job; it creates no customer order, invoice, transfer, wallet, or payment account. It never edits existing plugins or core files.

Open **WooCommerce → Agent Commerce**. The settings editor accepts validated JSON. Start from the default configuration and enter actual business facts:

1. `merchant_ids`: WordPress operators with `manage_woocommerce`, whose service products are owned by KnifeRevive. Direct checkout rejects seller-owned marketplace services.
2. `services`: published simple products in `knife-sharpening`, each with its exact size/scope definition. Neither product IDs nor prices are hardcoded.
3. `postal_codes`, `location`, `policy_url`, `policy_version`, and optional `return_policy_url`: verified coverage/location and publicly reviewed service/cancellation/refund policies.
4. `slots`: stable IDs, handoff kind, ISO timestamp start/end with UTC or correct LA offset, capacity in **jobs**. Configure intake and later return separately. Occupied slots cannot be moved or reduced below their allocations. Removed windows stop accepting new bookings; their old holds/evidence remain.
5. `transport`: optional per-leg integer-cent fee, taxability and tax class, plus an independently installed merchant address verifier. It stays unavailable when verification cannot be established.
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
