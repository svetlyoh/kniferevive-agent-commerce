# Native listing checkout operator runbook — candidate 0.2.0

Deploy only after the acceptance checklist and owner deployment authorization.
Do not enable this candidate on production to obtain test evidence. Use an isolated
staging database, test processor account/vendor accounts, and staging webhook
destination that cannot update production orders. Never copy live keys into tests.

## Real controls

**WooCommerce → Agent Commerce** has the existing validated configuration JSON,
save action, operator-service diagnostics and dedicated test-webhook connection
control. Candidate 0.2.0 adds read-only native gateway/test/live diagnostics,
operator-reported evidence and recent listing-intent states. It does not add a
Stripe session button, appointment scheduler or automatic payout control.

Merge the following real fields into the existing JSON (preserve other settings):

```json
{
  "listing_handoff_enabled": false,
  "listing_pricing_verified": false,
  "listing_live_verified": false,
  "listing_gateway_ids": [],
  "listing_policy_url": "",
  "listing_policy_version": "",
  "listing_services": [],
  "listing_max_minor": 100000,
  "gateway_evidence": []
}
```

Use actual checkout gateway IDs, verified HTTPS KnifeRevive purchase/return URLs,
and the existing `return_policy_url`. Maximum is in USD minor units (default $1,000,
validated upper bound $10,000). `krev_agent_checkout` cannot pay marketplace listings.
`listing_handoff_enabled` and `listing_pricing_verified` must both be true after
native shipping/tax/coupon/fee comparison. An enabled allowlisted gateway must
exist. Test mode permits staging handoff; live/unknown mode additionally requires
`listing_live_verified=true` after separate verification and owner authorization.
These are operator assertions, not automatic certification.

The review page shows assigned native product return terms when the Return
Policies plugin is active. A changed assignment/term requires a refreshed quote.
The original plugin still captures order-item policy snapshots; the adapter does
not replace the return ledger. Existing merchant administrators may sell their
own listings subject to native seller checks; other authors must be enabled Dokan
sellers. No role is modified or disabled seller overridden.

The existing maintenance job removes expired disposable listing quotes and
abandoned review-only intents after a 24-hour grace period. It retains handed-off,
linked and interrupted financial records for reconciliation. Keep the existing
cron scheduler working; token expiry still blocks access without that cleanup.

Each service approval in `listing_services` requires live `product_id`, HTTPS
`terms_url`, nonempty `fulfillment_note`, `policy_version`, and
`native_fulfillment_verified` boolean. Query actual IDs. Verify seller relationship,
fulfillment/transport/refunds/coverage and native checkout prices before true.
This narrowly permits native checkout, never a platform charge or appointment.
It grants no capabilities and changes no product owner. Keep unresolved services out.

`gateway_evidence` entries use actual `gateway_id`, `environment` (`test`/`live`),
`state`, integer Unix `checked_at` (not future), and a redacted internal `reference`.
Allowed states: `disabled`, `configured_test`, `test_payment_verified`,
`live_webhook_configured`, `live_payment_verified`, `needs_operator_review`.
Store labels such as `staging-check-20261008`; never keys or raw processor IDs.
The UI separates observed configuration from this reported proof.

**WooCommerce → Settings → Payments → Stripe → Settings → Configure connection**:
inspect Live and Test tabs without replacing existing keys. Native managed
webhooks should point to the same environment's store `?wc-api=wc_stripe`.
Inspect webhook success/failure and pending deliveries. Reconfigure only when
needed and authorized; the Agent Commerce dedicated webhook button is for the
separate operator-service endpoint, not marketplace payments. Verify express
checkout placement/settings before promising Google Pay.

**Dokan → Modules**: inspect Stripe/Stripe Express module activation; if active,
inspect its actual entry under **WooCommerce → Settings → Payments** and vendor
payout settings. A module being installed does not mean checkout uses it. Preserve
module-managed webhook destinations/events. Do not invent a Dokan webhook URL.

**WooCommerce → Stripe Connect** (custom plugin): real controls are Enable payout
onboarding, Environment, Automatic seller transfers, test/live secrets/signing
secrets, and connection verification. **WooCommerce → Connect reconciliation**
shows existing allocations/retries. Keep transfers paused until seller accounts,
allocations, gateway charge ownership, refunds and duplicate handling pass.
Do not automatically retry outstanding live allocations while testing.

## Required staging proof

Verify ordinary browser checkout first, then equivalent agent handoff. Compare
actual totals, selected shipping, discounts, fees, taxes, product quantities, seller,
Dokan `order_total`/`net_amount` and commission snapshots. Complete a **real Stripe
test-mode payment through the actual native gateway**, confirm one native order,
stock, notifications and provider reference, then compare transfer records and a
refund through the same gateway. Test Checkout Block and classic checkout if used.
Repeat with the production versions and shipping/tax extensions.

In **Stripe Dashboard → Workbench → Webhooks**, select the correct test/live
destination; inspect event type, delivery result and owning payment. Replay an
already handled sandbox event and a delayed event: no duplicate order, completion,
stock reduction or seller transfer. Verify raw-body signature, account/environment,
amount/currency/original intent binding through the owning gateway. A redirected
browser or configured key is not settlement. Record sanitized proof references.
Use real refunds/disputes fixtures through the original owner, not manual paid status.

## Safe recovery and rollback

After a timeout, inspect the original opaque intent and WC order/gateway outcome;
never create a second order, invoice or transfer. Native buyer receipt/account and
the original gateway's order-pay route remain the payment retry surfaces. Changed
gateway or amount requires merchant review. Expired quotes cannot create orders.
Emptied carts detach their browser marker; durable financial evidence remains.

`preparing_cart` or `creation_started` without a linked order blocks retries.
Search native orders for `_krev_listing_intent`. A server-side WordPress developer
console running as a merchant administrator may call
`KnifeRevive\AgentCommerce\ListingCheckout::recoverOriginalOrder($opaqueIntent)`.
The method uses WC CRUD and links exactly one existing order with matching quote,
items, gateway and total. It cannot create an order or payment. Zero/multiple or
changed matches require manual investigation. There is no public recovery endpoint.
Do not edit the database to clear this lock or manually mark an order paid.

First set `listing_handoff_enabled=false` to stop new preparations. Retain the
candidate's guard/status code while original native orders finish or reconcile;
do not remove it during outstanding intents. Leave the native gateway, managed
webhooks, Connect distribution/refund handlers and existing service flow running.
After reconciliation, restore the prior plugin package/configuration backup.
Preserve WC orders, adapter records/idempotency, Connect ledgers and signing secrets.
Do not roll back a customer database to a pre-purchase snapshot or delete evidence.
Before any authorized production migration, take a fresh provider backup and private
code/settings backup. Existing local `.runtime/pre-listing-source.zip` and sandbox
SQL snapshots are development rollback evidence, not production backups.
