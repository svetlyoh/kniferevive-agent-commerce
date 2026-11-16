# KnifeRevive — PayPal checkout readiness incident: Codex fix, validation, and deployment runbook

**Prepared:** 2026-11-16  
**Target:** `https://kniferevive.com` (WordPress + WooCommerce + Dokan, live PayPal Payments **4.2.2** at audit)  
**Owner workflow:** Codex agent: investigate → reproduce → minimally fix → verify → stage → safely release → monitor  
**Status:** **IMPLEMENTATION INSTRUCTIONS ONLY. No fix, provider reconciliation, production deployment, or successful payment has been verified by this document.**  
**Data classification:** This file is safe to place in a **public** GitHub repository: no customer names, addresses, cookies, bearer tokens, signed cart URLs, PayPal credentials, or full provider order/capture IDs. Retain full raw logs and evidence only in restricted storage.

> **Non-negotiable payment safety:** Before encouraging another buyer payment or mutating incident orders, reconcile all PayPal provider attempts. Never issue capture, authorize, refund, void, replay, or automatic new payment calls during read-only investigation. Do not retroactively mark failed orders paid without verified funds and the merchant's authorized reconciliation procedure. Do not disable fraud protection, bypass signatures, patch WooCommerce/PayPal vendor files, or silently switch credentials. Stop the deployment if payment correctness cannot be demonstrated.

## 0. Assignment and desired outcome

**Codex: take ownership of this incident and do the work, not just summarize it.** Locate the actual deployed checkout integrations; build version-matched tests; implement a supported, reversible mitigation or documented upstream-compatible fix; deploy only when the gates below pass. If a gate cannot pass because live provider evidence or merchant authorization is unavailable, complete all read-only and sandbox tasks, prepare the release candidate, and report **BLOCKED — NOT DEPLOYED** with exact missing evidence. Never substitute assumptions for pass results.

### Definition of done

1. For incident WooCommerce order **#3848**, its known PayPal order attempts are matched to authoritative provider status and capture/authorization records, or documented as unresolved with no further charging or automated recovery.
2. Root cause is **reproduced** under installed PayPal Payments 4.2.2, including the actual Store API / Blocks path (not inferred from an unrelated historical webhook report). If not reproducible, deploy **diagnostics only** after independent safety review, not a speculative payment-processing patch.
3. A tested fix prevents stale attempt identity/approval state from creating an unsafe checkout outcome, without intercepting or duplicating PayPal's capture/authorization engine.
4. Duplicate, out-of-order, delayed, and invalid webhooks are handled by the native gateway or by a tested minimal extension without duplicate fulfillment or false payment states.
5. Both **Blocks and classic checkout** work with KnifeRevive shipping, taxes, seller fees, fraud compatibility, Dokan, and Trade Desk logic enabled.
6. A release artifact, test transcript, rollback steps, post-deploy monitoring report, and precise installed production versions are recorded. Release must be reversible without losing payment evidence.

## 1. Incident facts: confirmed vs unconfirmed

### Confirmed from merchant-provided read-only audit

- On **November 16, 2026**, WooCommerce order **#3848**, physical-goods cart, **USD 27.28**, repeatedly failed with `The payment is not ready for processing yet.` from `OrderProcessor.php:125` in production PayPal Payments **4.2.2**.
- At **22:10:02 UTC (14:10:02 Pacific)**, a WooCommerce Store API checkout request `POST /wp-json/wc/store/v1/checkout` fetched a PayPal order with **HTTP 200** and then threw the readiness exception. **No capture or authorization API call is shown in that request.** The response log did **not** include the fetched PayPal order's body/status.
- Several PayPal order creations and shipping recalculations are visible during the session. The comparison of their true IDs, approval status, association with #3848, and funding source remains incomplete.
- Fraud protection logs **allowed** correlated PayPal express order-creation attempts; no correlated fraud rejection was established. Stripe setup-intent blocks involving other identities are not relevant to this order.
- A one-cent shift between subtotal and tax occurred during shipping recalculation, but **total stayed USD 27.28**; do not label this the cause without reproducing a provider/merchant mismatch.
- Native PayPal gateway logging is **enabled**. Prior WooCommerce `place-order-debug` logs were deleted; no actor/cause is established.
- The November 16 Trade Desk UI change suppresses product-smart-buttons and Pay Later display on qualifying product pages. The inspected code does not alter webhook registration or verification; other cart/order-pay filter effects still require integration testing.
- Historical order **#3787**, November 14 Pacific: two identical readiness failures, then a later successful PayPal attempt. The failure therefore **predates** November 16 Trade Desk changes. The successful payment establishes that the merchant can sometimes receive payments, not that #3848 has settled.
- No authoritative PayPal provider capture history, webhook delivery history, or complete native browser approval trace has yet been reconciled for #3848. The merchant reports no payment received; treat that as an important observation, **not proof of no capture**.

### 4.2.2 source facts, independently checked against official tag

**Source of truth:** `https://github.com/woocommerce/woocommerce-paypal-payments/tree/4.2.2` — **not** the stale local workspace vendor directory reporting version 4.1.3.

- `modules/ppcp-wc-gateway/src/Processor/OrderProcessor.php`: `process()` takes only a `WC_Order`, first tries the session order, then the WC order `_ppcp_paypal_order_id` metadata, then a session-validated posted order ID. It refetches the PayPal order, skips if remote status is `COMPLETED`, calls `add_paypal_meta()`, checks readiness for physical goods, and only then patches/captures/authorizes.
- The `order_is_ready_for_process()` implementation **accepts `APPROVED` or `CREATED`**. For other statuses, payment-source and card/3DS logic governs. Therefore **the exception does not by itself prove the buyer failed to approve**, and a naive `CREATED = always invalid` patch would alter gateway behavior.
- Gateway 4.2.2 has a processing lock and paid-status/transaction-ID guard, but those guards alone do **not** establish correct attribution among multiple PayPal attempts.
- `modules/ppcp-webhooks/src/Handler/CheckoutOrderApproved.php` obtains the webhook `resource.id`, yet its processing call still has the shape `order_processor->process( $wc_order )`; the webhook's resource ID is not passed directly as a parameter. This is a **plausible identity-conflict path**, **not** the proven origin of the observed `/wc/store/v1/checkout` failure.

### Relevant upstream evidence

1. [Issue #4559 — different PayPal attempt selected when webhook approves an older attempt](https://github.com/woocommerce/woocommerce-paypal-payments/issues/4559). Closed on **2026-10-23** after maintainer referenced [PR #4534](https://github.com/woocommerce/woocommerce-paypal-payments/pull/4534), released in 4.1.2. The discussion distinguishes a fixed uncaught-webhook-exception problem from concern about the **remaining order identity mismatch**. Treat the issue as reproduction guidance, **not** proof a complete underlying fix shipped.
2. [Issue #4684 — `add_paypal_meta()` records metadata before failed readiness](https://github.com/woocommerce/woocommerce-paypal-payments/issues/4684). Open when checked; illustrates false/stale payment metadata risks. Do not automatically erase successful or historical metadata.
3. [Official 4.2.2 release / changelog](https://github.com/woocommerce/woocommerce-paypal-payments/releases/tag/4.2.2): SDK v6 is the default since 4.2.0, including on existing stores. SDK v5 remains a documented fallback via `woocommerce.feature-flags.woocommerce_paypal_payments.sdk_v6_enabled`; **use only for a controlled staging A/B experiment first**. 4.2.2 includes checkout/failed-retry-related fixes, so compare the actual current release before alleging missing fixes.
4. [WooCommerce PayPal troubleshooting](https://woocommerce.com/document/woocommerce-paypal-payments/troubleshooting/): generic failure text is non-specific; inspect logs and conflicts. **Do not change invoice prefixes** without an observed `DUPLICATE_INVOICE_ID` response.
5. [PayPal Orders v2 API](https://developer.paypal.com/api/rest/integration/orders-api): `GET /v2/checkout/orders/{id}` is a read-only order-details call; capture and authorize endpoints are **POST** and prohibited during triage.
6. [PayPal webhooks integration](https://developer.paypal.com/api/rest/webhooks/rest/): confirm registered webhook endpoint, verify signatures, event delivery response codes and retry history. A successful HTTP 2xx delivery is not synonymous with successful internal fulfillment unless processing/queue outcomes are accounted for.

## 2. Phase A — read-only provider reconciliation (hard gate)

**Scope:** #3848 as of November 16; compare #3787 only as a separate known successful reference. Run with least-privileged, approved live credentials; do not expose keys in shell history or logs. If provider access is missing, request controlled, redacted evidence from merchant and mark blocker; do not fake statuses.

1. Freeze raw evidence in an access-controlled location **outside public GitHub**: WooCommerce order notes, gateway logs, fraud logs, PHP/WP logs, existing browser HAR (sanitized), WooCommerce Action Scheduler failures, environment/version manifest, and PayPal dashboard event history. Do not commit exported orders or log files.
2. Read #3848 using **WooCommerce CRUD** (`wc_get_order()` and `get_meta_data()`), HPOS-safe; do not assume `wp_postmeta` is authoritative. Collect all PayPal order IDs from order metadata and retained logs, including failed attempts, session-attached IDs, posted retry, custom/supplementary IDs, event `resource.id`, and capture IDs. Only create a **masked** correlation report (first/last characters), not raw identifiers.
3. For each real PayPal order ID, perform **GET-only** against the correctly matched LIVE merchant environment: `GET /v2/checkout/orders/{id}`; read `status`, `intent`, `purchase_units`, `custom_id`, `invoice_id`, `reference_id`, `amount`, `currency`, `payee`, `payment_source` *type only*, `payments.captures[]`, `payments.authorizations[]`, and relevant `links` **without following mutation links**. Check capture state (`COMPLETED`, `PENDING`, `DENIED`, refund status if shown), amount/currency, and the true merchant/receiver. Resolve capture IDs through **GET** if needed. No `POST`/`PATCH` requests.
4. In PayPal LIVE webhook event/delivery history, inspect `CHECKOUT.ORDER.APPROVED`, `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.PENDING`, `PAYMENT.CAPTURE.DENIED`, authorization events as configured, and related retries around the audit window. Record delivery HTTP status, time, event ID, and event `resource.id` (masked in shared output). Verify **which app/webhook ID and endpoint** apply to the site's live merchant account; no blind resubscribe, test notifications, or credential changes.
5. Cross-check PayPal transaction activity/merchant ledger for captures that could occur via other requests or callbacks, not only the failed browser request. Compare Woo notes and merchant balance in the **same merchant account and currency**.
6. Record a private per-attempt table: `WC order`, `checkout attempt`, `session correlation`, `PayPal order ID`, `status`, `capture/authorization ID and status`, `amount`, `currency`, `custom_id`, `invoice_id`, `webhook event and result`, `Woo transaction/status`, `confidence`, `next permitted action`.
7. Decision: if **any** attempt is captured/authorized/pending or results are contradictory, mark the incident **RECONCILIATION REQUIRED** and **do not initiate another charge or status mutation**. If every found attempt has verified no successful funds movement, document this carefully; absence of events/notes is insufficient proof. Do not solicit another payment solely from this conclusion without merchant-led buyer remediation.

**Phase A output:** `private/paypal-order-3848-evidence-YYYYMMDD` in a restricted location (not repo) plus a publicly safe, masked summary in the QA report. **No payment side effects.**

## 3. Phase B — inspect deployed call paths and prove cause

1. From production administration and deployment manifests, inventory exact active versions, PHP/WP/WC/Dokan, PayPal Payments, Woo Checkout Blocks, HPOS, the active Astra/child theme, Store API hooks, Trade Desk, fraud protection, fees, coupons, shipping, taxes, checkout refresh, carts, and payment method filters. Snapshot current plugin settings and registered PayPal webhooks privately.
2. Map all native checkout hooks, including `woocommerce_store_api_checkout_*`, `woocommerce_blocks_*`, `woocommerce_available_payment_gateways`, `woocommerce_payment_gateways`, `woocommerce_cart_calculate_fees`, shipping rate recalculation, classic checkout, `order-pay`, and the fraud compatibility filter. **Search all production-loaded plugins, mu-plugins, theme, and generated assets**, not only two older local checkout directories. Identify which code fires during the actual failing request; log hook names, call ordering, and delta, not PII.
3. Review the **tagged 4.2.2** PayPal frontend SDK v6 create-order/approve/cancel/error and Store API handoff, especially whether the active `paypal_order_id` corresponds to the buyer's actual approved PayPal order. Trace order A creation → buyer approval → Store API request → session resolution → `_ppcp_paypal_order_id` → order retrieved → readiness check. Analyze B/A overlap caused by page reload, double click, shipping callback, stale JS closure, or retry. Correlate using per-attempt opaque correlation ID; never expose a full provider ID in frontend HTML or public logs.
4. Add **gated, non-sensitive diagnostics** where a supported extension point exists. Each log record: timestamp UTC, request/hook class, WC order internal ID (keep private), HMAC/truncated hash of PayPal attempt ID, attempt generation, event type, remote status, intent, source kind, checkout mode, SDK version, currency, normalized amount, Woo amount, and outcome category. Never log payer/email/address, tokens, cookies, Authorization headers, `purchase_units` address fields, full payment-source details, complete PayPal IDs, or signed cart links.
5. Reproduce in isolated fixture and sandbox with actual **4.2.2**, not 4.1.3. Mock the PayPal Orders v2 responses and webhook payloads to isolate chronology; then validate with **sandbox merchant and buyer accounts** and real signed sandbox webhooks. Test PayPal popup approval and canceled/abandoned popup; read merchant and browser network traces with sensitive fields redacted.
6. Perform a staging **SDK v6 vs v5** A/B with *identical* versions, shipping, fraud, cart, and fees. Official flag is `woocommerce.feature-flags.woocommerce_paypal_payments.sdk_v6_enabled`; first establish this exact filter and precedence in source. Use only a versioned test-only plugin/mu-plugin toggled in staging; do not casually switch production.
7. Try one change at a time in staging for **diagnostic comparison**, never silently disable fraud protection on production. Keep special Trade Desk product behavior and default non-Trade-Desk checkout separate.
8. Classify actual root cause as one of: wrong PayPal attempt selected; approval callback missing/out-of-order; PayPal state genuinely not ready; Woo/PayPal amount mismatch after totals update; SDK version conflict; webhook-only recovery conflict; third-party hook effects; or **unknown**. Require event-by-event evidence to claim any one.

**Phase B output:** redacted sequence diagram plus a failing automated test whose assertion demonstrates the *specific* incorrect binding/state transition. If none fails under realistic conditions, **do not deploy a speculative capture/state-machine change**.

## 4. Phase C — implement minimal, reversible fix

**Priority:** supported vendor/official update or supported integration hook → isolated KnifeRevive compatibility plugin → vendor upstream proposal if current API lacks a safe hook. **Never** edit `wp-content/plugins/woocommerce-paypal-payments/` deployed vendor files or shadow its core classes with an autoloader hack.

### C1. Explicit payment-attempt lifecycle

If tests prove a stale identity/async race, implement a minimal isolated plugin, e.g. `kniferevive-paypal-attempt-guard` under the **actual deployed site repository** (do not assume this agent-commerce docs repo is the checkout source). Its responsibilities must be limited and feature-gated:

- Associate each buyer-initiated attempt with an opaque server-issued `attempt_uuid` plus WC order ID, current checkout session, gateway, LIVE/SANDBOX environment, merchant-account identifier, currency and **integer-minor-unit** total. Store high-value mappings privately using HPOS-safe order metadata and/or a purpose-built indexed table for atomic compare-and-swap; do not rely on global session state alone.
- Keep an append-only attempt history: `initiated`, `provider_created`, `awaiting_buyer`, `approved`, `payment_processing`, `captured/authorized`, `abandoned/cancelled`, `superseded`, `review_required`. These are **local observation states**, not proof of PayPal settlement; authoritatively verify remote status before financial transitions.
- On every new attempt, atomically advance an order-scoped generation, without deleting historical successful transaction metadata or losing earlier PayPal order IDs. A later creation must not silently replace the identity attached to an earlier browser approval/verified webhook.
- Require the callback's verified provider order ID to match a known attempt, Woo order, live/sandbox environment, merchant, currency and exact total. If an older attempt A is approved after a newer attempt B is created, **do not blindly discard A and ask for another charge**. Put it into reconciliation state until its provider capture/authorization outcome is checked. A mismatched attempt must not rebind order meta or trigger capture for B.
- For a buyer-canceled popup or `PAYER_ACTION_REQUIRED` state, show a plain, specific prompt to **finish approval** or safely choose another payment method **only when the provider has been checked and no payment outcome is uncertain**. Provide a guarded "Check payment status" / support route when uncertain. The client must not automatically create a new PayPal order or resubmit capture.
- Validate that the gateway 4.2.2 considers `CREATED` processable; do **not** blanket block it or reinterpret the vendor's 3DS behavior. A local guard must not bypass PayPal's real readiness, 3DS, or payer action requirements.
- Where a supported extension point can guard before PayPal processing, ensure it is idempotent and the decisions cannot be overridden by a stale JS callback. If no supported hook can ensure correct provider ID selection, **stop** and prepare an upstream issue/PR or wait for a vendor-supported solution rather than monkey-patching the processor.

### C2. Webhook correctness and recovery

- Prefer the gateway's existing official endpoint/verified handlers; do not create a second listener that races to capture the same PayPal order.
- Confirm **signature verification before trusting payloads**, right merchant environment, event idempotency, true resource-to-attempt identity (including `resource.id` / supplementary order ID), and amount/currency/intent before any Woo financial or fulfillment transition.
- Deduplicate events, capture IDs, and fulfillment transitions using a durable key and atomic mechanism; `CHECKOUT.ORDER.APPROVED` alone must **not** mark an order paid. `PAYMENT.CAPTURE.PENDING` must not be represented as completed. Distinguish approved, authorized, captured, pending, denied and refunded states.
- Handle valid but out-of-order/mismatched events by durable quarantine and operator alert for reconciliation. Acknowledge with 2xx **only once safely persisted/queued or completed**; if infrastructure cannot durably accept a transient event, preserve retry behavior rather than falsely signaling success. Never make unverified events eligible for payment mutations.
- On already-`COMPLETED` PayPal order, reconcile capture ID/status through **read-only** provider APIs and Woo status history, without a second capture. A general timeout is `unknown/review_required`, not a reason to recreate a payment.
- If the gateway's fixed webhook catch-all returns HTTP 200 with a `success:false` body, ensure diagnostic alerts notice it; don't assume 200 means fulfillment succeeded. Preserve provider event IDs in restricted logs for support.

### C3. Checkout totals & plugin compatibility

- Freeze/validate the final WC total and currency at provider order creation and again before the supported native capture flow. Compare line item, shipping, tax, seller fees, and order total. If totals changed **after** approval, require a sanctioned reapproval or support path; never change approved totals or tax silently and then capture.
- Investigate the observed one-cent subtotal/tax redistribution with exact minor-unit arithmetic; do not change taxes/fees as a hunch.
- Retain current fraud protection rules and compatibility filters; avoid making them permissive for PayPal. Keep Trade Desk product-page UI suppression and its unrelated order metadata filters unchanged unless a targeted regression test proves interference.
- Keep buyer checkout errors actionable but conservative: never tell the buyer "payment failed, pay again" where an in-flight capture or unverified webhook could still settle.

### C4. Visibility / support UI

- Add an admin-only per-order read-only **PayPal attempt timeline** (masked IDs, outcomes, event references, last checked timestamp, alert flags), protected by appropriate Woo admin capability, using `wc_get_order()` and CRUD/meta APIs.
- Admin may manually request a **read-only** reconciliation, rate-limited and permission-gated. No automatic capture, no admin button to force success, and no reset of captured state. If merchant approval is needed for a payment-affecting operation, it belongs in a separate explicitly authorized workflow outside this fix.
- Add a minimal buyer-facing error path for failed approval and an uncertainty path for provider timeouts that avoids double payment invitations.

## 5. Phase D — acceptance tests (all required)

**Must run against true 4.2.2 source and deployed-version plugin stack; unit tests alone are not enough.** Existing isolated Trade Desk `paypal-product-ui.php` passing 10 assertions is a **UI boundary** check, not provider checkout evidence.

| Test | Expected result |
|---|---|
| PayPal status `PAYER_ACTION_REQUIRED` / non-approved popup | No capture or authorization by KnifeRevive; buyer gets accurate action or guarded uncertainty message |
| Remote `APPROVED` | The matching native gateway attempt follows vendor processing exactly once; no unrelated newer order substituted |
| Remote `CREATED` | Behavior matches 4.2.2 vendor readiness contract and payment-source specifics; do **not** mark it categorically invalid |
| Remote `COMPLETED` with actual capture | Read-only reconciliation; no second capture; Woo not wrongly marked Failed if capture verified |
| A created, B created, A approved, B unapproved | No processing of B under A's approval; record A safely; no lost event or duplicate fulfillment |
| A and B both approved near-simultaneously | One authorized financial outcome/fulfillment; second candidate quarantined/reconciled; no duplicate capture |
| Two concurrent Store API checkout requests | Stable atomic attempt identity, no double order/payment/stock decrements |
| Webhook duplicate + out-of-order events | Exactly-once fulfillment/state transitions; event ID and capture ID deduped |
| Invalid webhook signature or wrong merchant/env/currency/amount | Reject/ignore without payment mutation, create audited security event |
| Network timeout after possible capture | Unknown/review state; check same attempt; **never automatic retry charge** |
| Currency, shipping, tax and fee changes; one-cent reallocation | No unapproved amount mutation; either exact synchronized totals or safe reapproval |
| Buyer switches PayPal to Stripe/other rail | Stale PayPal metadata cannot relabel/refund/capture another gateway's payment |
| Guest / logged-in buyer, mobile / desktop, PayPal popup/redirect | Proper approval and return binding; no stale callback rebind |
| Classic checkout, Blocks checkout, order-pay retry | Each tested with real gateway enabled, shipping/fraud/fees active |
| Trade Desk and regular knife product | Current Trade Desk UI logic stays scoped; checkout works on both |
| CAPTURE and AUTHORIZE intents (if merchant supports both) | Capture/authorize statuses are handled separately; never treat authorization as settled capture |
| HPOS and legacy order storage fixture | Correct atomic metadata and no raw direct postmeta assumptions |
| Webhook endpoint temporarily inaccessible | Queued/retried events reconcile; errors observable; no unsafe 2xx false success |
| Payment already successful in an older attempt | Never display an unrestricted new-charge action or clear transaction metadata |

Automate with PHPUnit/WordPress integration tests, fake PayPal REST responses, deterministic delayed callbacks, and Playwright/browser-level sandbox checkout; collect redacted HAR screenshots. Assert **negative calls** (`capture`, `authorize`, `refund`, `void`, `create`) in all uncertain or unapproved paths. Verify no new duplicate Woo orders, no repeated Dokan seller payouts, no double stock reductions or seller notifications. Existing Trade Desk fixtures must still pass.

## 6. Phase E — packaging and staged deployment

### Pre-release guardrails

1. Create a dedicated work branch and open a PR in the **actual production checkout source repo**; reference this handoff. The connected `svetlyoh/kniferevive-agent-commerce` repo may host this document but is not presumed to contain every installed WordPress plugin.
2. Identify the smallest independently installable plugin/package and use a **feature flag default OFF**. Plugin activation must not process historical orders, register new PayPal capture callbacks, alter webhook subscriptions, or run unsafe migration. Every write should be bounded, reversible, and HPOS-compatible.
3. Enforce PHP lint and static analysis, JS checks, WooCommerce integration tests, 4.2.2 version-matched PayPal sandbox tests, and regression tests across shipping/fraud/fees/Trade Desk. Include QA for real native gateway in both Blocks and classic checkout.
4. Save and verify rollback artifact, prior plugin versions/settings, database backup and restore procedure; ensure no backups, raw logs, or secrets enter GitHub. Confirm hosting deployment mechanism/permissions rather than assuming WordPress.com lets Codex write files directly.
5. Prepare a staged rollout plan with health gates: nonfinancial sanitized logging only, counters for readiness errors, unresolved attempt IDs, webhook `success:false`, capture-to-Woo mismatch, checkout completion rate, failed-to-paid retry transitions, duplicate transaction/capture signals, and scheduler backlog.

### Release gates

**Gate 1 (provider):** #3848 reconciled or isolated as explicitly unresolved with **no intervention**; no new checkout/charge authorized for the incident.  
**Gate 2 (diagnosis):** an exact failed test proves the targeted fault OR only a no-side-effect diagnostic release is proposed.  
**Gate 3 (correctness):** all tests in Phase D pass using real installed gateway in sandbox; third-party fraud and fees remain enabled.  
**Gate 4 (review):** independent payment-safety code review confirms no duplicate capture or refund path; merchant has explicitly approved release and scheduled post-deploy watch.  
**Gate 5 (ops):** rollback demonstrated in staging and production backup available.

If **any gate fails**, **do not release a payment-altering fix**; deliver the PR, diagnostics report, and exact blocker. **Do not present deployment as completed.**

### Production rollout after gates pass

1. Deploy a versioned ZIP/plugin or vetted supported upstream update through the site's **documented** deployment pipeline. Do not FTP-edit vendor files. Keep change OFF initially.
2. Enable diagnostics/correlation at sanitized low volume; verify the release is loaded without affecting other gateways. Turn on the **verified, tested** compatibility guard only for a controlled, explicitly approved rollout. Do not manipulate live buyer sessions or repeat incident payments as a smoke test.
3. Verify normal public cart/checkout display, gateway availability, zero PHP fatals, Store API health, webhooks registered/healthy, checkout rate, order-state transitions, stock, email and Dokan seller accounting. Use **legitimate buyer-authorized** live transactions only if merchant specifically authorizes them, and reconcile each before further live tests.
4. Monitor for at least one meaningful payment cycle; compare against predeploy baseline. Escalate if readiness errors persist, captures appear without Woo payment, Woo shows paid without capture, webhook failures repeat, or duplicate capture/payout suspected. **Disable only the new compatibility feature** and follow rollback plan; preserve reconciliation logs/metadata.
5. Publish a sanitized release report noting package/version, commit, test evidence, source of installed gateway, deploy time, rollback artifact, monitoring findings, unresolved incident #3848 state, and any upstream issue filed.

## 7. Codex execution sequence / reporting format

Execute the phases in order and report results clearly. Do not produce a plausible-sounding final claim in place of proof.

```text
1. INVESTIGATE (read-only): match provider order/capture/webhook history for #3848.
2. INSPECT: enumerate actual production plugins, hook graph, checkout SDK,
   tagged WooCommerce PayPal Payments 4.2.2 source.
3. REPRODUCE: exact failing Store API trace with mock and PayPal sandbox;
   identify whether attempt A/B, approval state, total changes or other hook caused it.
4. IMPLEMENT: smallest vendor-supported/isolated fix; feature OFF by default;
   no duplicate capture, no vendor file patch, no raw secrets in source.
5. TEST: run all Phase D cases and existing checkout/Trade Desk/fraud suites.
6. REVIEW: prepare PR, test evidence, rollback, backup and release checklist.
7. DEPLOY: only after all release gates and merchant approval; if blocked,
   deliver tested candidate and state BLOCKED -- NOT DEPLOYED.
8. VERIFY: monitor and report real status (including #3848 payment uncertainty).
```

In the final handoff, include:

- `ROOT_CAUSE`: confirmed with trace, or explicitly unproven.
- `PROVIDER_RECONCILIATION`: confirmed payment/no confirmed payment/unresolved with evidence timestamps; sensitive IDs privately stored.
- `CHANGED_FILES`: actual paths and why each was needed.
- `TESTS`: exact commands, PASS/FAIL, limitations and screenshots or sanitized summaries.
- `DEPLOYMENT`: **not deployed / diagnostics only / limited release / fully enabled**, plus exact site version and UTC time if actually deployed.
- `ROLLBACK`: exact artifact, switch, and tested procedure.
- `OPEN_RISKS`: unresolved approval, mismatched capture, webhook event or account issue, and operational follow-ups.

## 8. Explicit prohibited shortcuts

- **No** automatic second PayPal charge, capture, authorization, void, or refund to "see if it works."
- **No** code that marks `CHECKOUT.ORDER.APPROVED`, `CREATED`, HTTP 200, or a Woo note as proof of capture.
- **No** status edits to #3848/#3787 or clearing `_ppcp_*` metadata without independent transaction reconciliation and explicitly authorized remedial workflow.
- **No** arbitrary delay/sleep loop that captures later, blanket `try/catch` that silently acknowledges lost payments, or `return;` inserted into the vendor readiness exception.
- **No** assumption that SDK v6, shipping cents, fraud protection, Trade Desk UI or PayPal webhook delivery is at fault until tested.
- **No** changes to merchant invoice prefix solely due to retry reuse; require actual `DUPLICATE_INVOICE_ID` provider response.
- **No** storing/committing full IDs, invoice/tokens, unredacted PayPal logs, customer info, or browser network dumps in public GitHub.

---

**Codex starting prompt:**

> Read this entire file. Work through Phases A–E in order. Start with read-only PayPal reconciliation for KnifeRevive order #3848 and a production-version 4.2.2 Store API checkout reproduction. Implement and deploy the smallest supported, reversible fix only after verifying the root cause, passing every payment safety regression, and satisfying the release gates. Never initiate or repeat payment operations during investigation. If provider evidence or authorization is unavailable, stop before production changes and deliver a QA-tested candidate plus an explicit blocked deployment report. Do not confuse saving this Markdown file to GitHub with shipping a fix to kniferevive.com.