# KnifeRevive — Stripe Connect settlement and pending-transfer rollout
## Codex implementation instructions | production-only, fail-closed

**Date:** 2026-10-23  
**Audience:** Codex agent working on the existing KnifeRevive WordPress / WooCommerce / Dokan marketplace.  
**Status:** Implementation specification. **NOT** an authorization to send funds.  
**Primary request:** Preserve the current Stripe Connect integration, improve settlement controls and seller messaging, and prepare existing pending seller payments for supervised release without a staging environment.

> **Critical rule:** The absence of a test environment is **not** approval to release all queued transfers blindly. Codex must not turn on the global automatic queue, trigger a live transfer, issue a refund, reverse a transfer, or debit a seller without explicit human approval of a reconciled release manifest. No approval is implied by this document.

---

## 1. Baseline and source-of-truth discipline

The prior KnifeRevive settlement assessment, referenced as `KNIFEREVIVE_STRIPE_ADMIN_SELLER_SETTLEMENT_SETUP_2026-10-23.md`, described the following **reported** state (verify against the live installation; the referenced report was not independently re-opened while preparing this specification):

- Platform-operated **Stripe Connect / separate charges and transfers**: buyer payments are taken by the KnifeRevive platform, with seller allocations destined for individual **Stripe Express** accounts.
- Existing custom **KnifeRevive Stripe Connect** plugin, reportedly version **0.1.6**, plus **My Money** UI, reportedly **1.0.1**.
- WooCommerce orders and Dokan's **stored seller allocations/commissions** are the financial authority. Do not recompute historical commissions from today's percentage settings.
- The **automatic transfer queue is globally disabled**. This is a protective condition; leave it disabled until a new scoped release system is independently accepted.
- At least one **refunded order #2960** was reportedly still queued for transfer, and legacy **order #2054** was unresolved. These are **mandatory quarantine cases**, not routine payout candidates.
- The report reportedly discussed **seven historical allocations**. Discover their actual identities and current states; do not assume seven remain payable.
- The proposed scoped release gate is **not yet verified as deployed**.

**Do not assert any of these as fresh production facts without inspection.** Read the report if available in the workspace; otherwise note its absence in the discovery output. Inspect deployed plugin code, actual WP/WC/Dokan configuration, Stripe dashboard/API in **read-only mode**, existing queue records, and transfer logs. Never print or commit API keys, customer PII, bank details, or full webhook secrets.

Related existing specification: `docs/KNIFEREVIVE_MY_MONEY_CODEX_IMPLEMENTATION.md`. Its fundamental rule still applies: **no parallel commission/balance/withdrawal engine**.

## 2. Product decision: how seller money should move

**Recommended initial policy for NEW orders** (proposal, not a statement of existing Stripe settings):

1. Capture the customer's payment through the current WooCommerce gateway. Keep the seller allocation on the **platform** until eligible. Customer payment, seller earnings, Stripe transfer, and bank payout are different events.
2. Seller must submit valid carrier tracking and the platform must independently verify **carrier-delivered** status. A seller clicking “completed” or editing WooCommerce order status **must never** be sufficient evidence for payout.
3. Normal seller release eligibility: **72 hours after verified delivery**, assuming payment is final, no open refund/return/cancellation/dispute, no fraud flags, seller Stripe account is enabled, and the order's stored Dokan allocation reconciles. This is a marketplace risk policy, not a Stripe settlement rule.
4. Run **one weekly platform-to-Express transfer batch** (e.g., Tuesdays at 15:00 store timezone), moving only allocations that were already eligible at the batch cutoff. Sellers can be told “Eligible after delivery review; transfers are processed weekly.” A Tuesday release may mean additional wait until the next Tuesday. **Stripe Express-to-bank payout timing is separately controlled by the connected account's actual payout settings and bank rails.**
5. **New sellers:** initial 3 successfully delivered orders or first 30 calendar days (whichever is later), use **7 days after verified delivery** before weekly release, subject to manual risk review; do not present this as a guarantee. Higher-risk sellers/orders may receive longer holds only within Stripe, network, contractual, and applicable legal limits, with a documented reason and review/appeal route.
6. If tracking is unavailable, contradictory, or untrusted, **manual review** must decide whether alternative independent proof is adequate. No automatic release from a mere time-since-order or seller-selected status. Escalate aged cases so legitimate sellers are not stranded indefinitely.
7. Explicitly disclose the policy in seller terms/onboarding before applying it, and show seller-specific estimated eligibility dates as **estimates**, not promises.
8. Do not assume a platform-side weekly transfer batch also configures Stripe Express **bank payouts** to weekly. Verify which connected-account payout schedule the platform is authorized to control and whether the seller can change it; leave bank payout schedules unchanged until approved.

**Why this policy:** It is more seller-friendly than a long blanket hold while reducing the “seller takes money and never ships” risk. A 72-hour post-delivery window is an initial operational choice, **not** a guarantee against returns, chargebacks, or fraud. Reassess after actual loss/fulfillment data. Do not retroactively impose the new hold on historical allocations without contractual/legal review; apply the historical exception procedure below.

## 3. Hard safety invariants

- **No money movement on deploy, page load, webhook replay, cron activation, plugin activation, database migration, or admin report generation.**
- Keep the **legacy global queue disabled**; replace it with an **allowlist-only release path** that requires explicit reviewed batch approval. Never “unpause” the old queue to drain it.
- **Default deny** if any input is missing, stale, inconsistent, or ambiguous. A failure to query Stripe means **HOLD**, not “probably unpaid.”
- **Order status is not payment truth.** Check gateway charge/payment intent state, Stripe refunds/disputes, WC refund objects, Dokan seller allocation and adjustments, seller ownership, destination account, prior transfer/reversal evidence.
- **Canceled, fully refunded, disputed, chargeback, payment-failed, fraud-flagged, or unfulfilled** allocations are ineligible. A partially refunded order must use the **verified adjusted seller allocation** and be reviewed for correct amount; if reconciliation fails, HOLD.
- **One seller/order/allocation/currency movement exactly once**, using a durable unique allocation key and a Stripe idempotency key. Prevent duplicates across concurrent cron runs, webhooks, HTTP retries, and manual actions.
- Recheck eligibility **immediately before** creating any transfer; a previously approved manifest is not an immutable right to transfer if a refund/dispute arrives.
- **Stripe transfer is not bank payout.** Never label a successful `tr_...` as money received in a bank.
- Do not create a second connected account for a seller. Resolve the currently authorized account from the existing Dokan/Connect mapping and **block account swaps** pending review.
- Preserve immutable audit records, but redact identifiers in logs and exports as appropriate. No raw API credentials in database audit entries.
- No automatic seller card/bank debit, no fabricated “negative balance paid” event, and no debt collection automation without documented legal/contractual consent and supported Stripe product workflow.

## 4. Mandatory discovery deliverable — before code changes

Create `docs/STRIPE_SETTLEMENT_DISCOVERY.md` documenting:

1. Active plugin versions, relevant code paths/hooks/classes, WooCommerce HPOS compatibility, Dokan suborder ownership and historical commission snapshot source.
2. Exact Stripe **charge type**, account IDs' ownership mapping, existing onboarding flow, whether Stripe Express transfer destination is determined from seller settings, and whether destination can change.
3. Actual queue table/record schema, queue processor triggers, disabling flag, cron schedule, current queue entries, lock and retry behavior.
4. All payment, charge, refund, dispute, transfer, transfer reversal, connected payout, and webhook IDs already stored; any gaps.
5. Existing webhook endpoint/signature verification, event deduplication, event ordering and replay handling, plus installed API version and Stripe SDK version.
6. Existing Dokan seller commission/earnings/withdrawal semantics; avoid double-subtracting commission or refunds.
7. Whether the Stripe platform account has enough **available** balance and what funds are pending; platform auto-payout schedule may affect availability.
8. Existing My Money labels and seller email infrastructure, WP mail deliverability, templates and idempotency.
9. Risk and legal constraints: Stripe account/region restrictions, connected-account negative balance liability, platform terms and seller consent for reserves/recoveries.
10. A reconciliation inventory of **every historical pending allocation**, including #2960 and #2054, with all sensitive fields redacted.

**Stop condition:** If you cannot determine the current transfer owner/processor or cannot query authoritative Stripe transfer history, implement read-only reporting only. Do not build or run a release action.

## 5. Historical pending allocations: release plan without staging

The user has no separate test environment and wants to release legitimate pending seller money. Support a **production-only, read-only preflight followed by explicitly approved one-by-one releases**; this is not the same as “release as is.”

### 5.1 Generate a read-only candidate manifest

Create a CLI/admin **READ-ONLY** command, e.g. `wp kniferevive settlement inspect-pending --format=csv` (exact name may change to fit existing architecture). It must **not** call any Stripe money-writing endpoint.

For each allocation, export:

- Internal allocation ID; WC parent/suborder ID; seller ID (redacted in portable output); stored Dokan seller net in **integer minor units**; currency.
- PaymentIntent/charge ID and status, capture and availability status, actual refund amount/status, dispute/chargeback flags, WooCommerce/Dokan refund and cancellation records.
- Verified shipping/delivery evidence where applicable, seller risk status, historical policy applicability.
- Connected Stripe Express destination **at order time** versus current destination, onboarding/capability status, and whether any account change occurred.
- Existing Stripe transfers and reversals matched by charge, transfer group, allocation metadata, destination and amount; do not trust a single local “sent” flag.
- Existing queue status, last attempts/errors, expected vs. already transferred vs. remaining amount.
- Decision: **HOLD / NEEDS_REVIEW / ELIGIBLE_FOR_APPROVAL**, with machine-readable reason codes.
- Timestamp, source references, data freshness, reconciliation checksum, and operator signoff fields.

**Mandatory:** `#2960` must be **HOLD** until the refunded state and seller net are proven and reconciled. `#2054` must be **HOLD / NEEDS_REVIEW** until its unresolved history is explained. **Do not infer that either is payable.** Any additional refunded/canceled/disputed/duplicate record must also HOLD.

### 5.2 Operator review and explicit approval

1. A human administrator compares the manifest against WooCommerce/Dokan and the Stripe **live dashboard**. Include the platform available balance and ensure legitimate sellers are not accidentally paid twice.
2. The human signs off **specific allocation IDs, destination accounts, amounts, currencies, and batch hash**. Do not accept a generic “approve all” or checkbox default.
3. Separate high-risk or uncertain historical items from clearly verified ones. Pay only those independently confirmed as owed and not previously transferred. **Do not retroactively invent delivery dates.**
4. Document why a historical item was released or held, seller communication, and the approval identity/time.
5. If the human cannot validate the records, **leave the queue disabled** and escalate to an accountant/payment specialist or Stripe support.

### 5.3 Narrow live release path (implement only after discovery)

- Build an admin-only `release-approved` action/CLI that accepts **explicit allocation IDs** plus signed/immutable manifest hash, not “release everything pending.”
- Require WP CLI or strongly authenticated privileged admin + CSRF protection, dual confirmation, and preferably a second approver for production money movement.
- Under a transaction/unique lock, reload authoritative records, verify no refund/dispute/destination change/duplicate transfer, check approved amount and platform balance, and atomically mark `releasing` with a unique idempotency key.
- Call the **existing Connect transfer service**, not a parallel money engine. If the existing service cannot safely enforce the gates, refactor it behind the new guard rather than creating competing processors.
- Use a stable Stripe idempotency key scoped to the unique financial allocation + intended amount/currency/destination/version. Respect Stripe's idempotency retention semantics; **also** enforce a permanent local uniqueness constraint and Stripe history reconciliation. An idempotency key alone is not lifetime duplicate protection.
- Use `source_transaction` only when appropriate for the actual captured charge and payment method; it ties funding availability to the source charge but **does not** enforce the marketplace delivery hold or protect against subsequent refunds. `transfer_group` is correlation metadata, **not** a deduplication or authorization mechanism.
- Persist Stripe transfer ID, destination, amount, currency, charge reference, timestamps, actor, and immutable reconciliation outcome.
- On timeout/unknown response, query Stripe before retrying. On Stripe error, leave item held/retryable with a precise error; **never** create a second transfer speculatively.
- Release **one approved item at a time** initially, with a human verifying the resulting Stripe transfer and local ledger before continuing. Set a small admin-configurable per-run count and total-amount cap; default release count **zero** until approved.
- Keep the old global queue disabled during and after the historical rollout. No live money movement is authorized by these instructions.

### 5.4 Production-only verification

Even without a staging site, run non-financial checks before release: PHP lint/static analysis, unit tests for pure eligibility functions, database migration dry-run where possible, WP CLI read-only inventory, webhook signature validation using non-production fixtures, dry-run eligibility decisions, log inspection, and reconciliation of sample production records. If automated tests cannot run, **do not substitute blind payment execution**: manual read-only reconciliation and explicit operator approval remain mandatory.

**Rollback reality:** a successful Stripe transfer cannot be “rolled back” like a code deployment. Reversal may be possible under Stripe's conditions but is **not guaranteed**, especially after funds leave the connected account. Have a documented incident plan, admin contact, and an emergency disable switch **before** the first release.

## 6. Implement future settlement gate

Add a small, versioned **eligibility decision service** layered over existing WooCommerce/Dokan/Stripe records; no new earnings math. Suggested pure API:

`evaluate_allocation( allocation_id, now, policy_version ) -> { decision, reasons[], amount_minor, currency, destination, earliest_release_at, evidence_refs[] }`

Eligibility is **true only if ALL** are verified:

1. Real, successful captured payment and correct seller-scoped Dokan allocation; currency and stored seller amount reconcile.
2. No active or unresolved cancellation, refund, return, chargeback, dispute, risk hold, fraud investigation, or payment reversal.
3. Seller account is active, owned by that seller, has requisite Stripe capabilities, and has not been swapped since approval.
4. Delivery verified independently; seller cannot self-approve. Hold elapsed according to versioned seller policy.
5. No previous transfer for the same obligation, accounting for partial transfers/reversals.
6. Platform balance/funding path can support the transfer; all necessary source records fresh.
7. Weekly cutoff reached, allocation explicitly selected for the approved batch, and release gate enabled **for this batch only**.

A refund/cancellation/dispute webhook must invalidate any pending approval and block future transfer. Implement event deduplication and a final locked eligibility recheck at execution. Avoid race conditions with an outbox/atomic state transition; document what remains non-atomic across WP database and Stripe.

## 7. Stripe Connect setup and settings — verify, don't replace

**Do not ask Codex to enter secret keys into source control or invent settings.** Produce `docs/STRIPE_CONNECT_ADMIN_SETUP_CHECKLIST.md` with screenshot-free, version-aware instructions for an authorized operator:

- Confirm **live vs test** platform Stripe account and correct platform ownership; verify API keys and webhook signing secret via existing secure WP config/secret storage. Never echo secrets.
- Confirm Connect Express is enabled and that existing sellers are connected through the **existing** onboarding/account-link flow. Check `charges_enabled`, `payouts_enabled`, and requirements, but recognize these are not sufficient to establish seller eligibility.
- Confirm the active WooCommerce gateway creates platform charges compatible with the existing **separate charges and transfers** design. Avoid changing charge type or introducing a second gateway.
- Inspect Connect webhook destinations and signed delivery for payment success/failure, refunds, disputes, account changes, transfers/reversals and connected-account payout status **where events and account scope are supported**. Use existing event handlers; do not blindly register duplicates.
- Verify the **platform's** automatic bank payouts do not drain available funds required for seller transfers; recommend an appropriate operational reserve with finance approval.
- Inspect **Express account** bank payout schedule and any Stripe-controlled minimum/negative balance settings. Distinguish a **weekly platform transfer schedule** from the Express **bank payout schedule**. Only adjust bank schedules where the platform is authorized and the seller agreement supports it; do not silently change sellers' payout preferences.
- Check Stripe-supported country/currency restrictions, account requirements, supported transfer funding, connected-account negative-balance liability, and refunds/disputes policy.
- Leave the global auto-transfer processor **OFF**. Enable only the newly reviewed batch release path after written approval.
- Record the precise UI setting names/API capabilities actually present in this installation, and include a reversible configuration-change log. Do not perform live account setting changes automatically.

## 8. Refunds, cancellations, disputes, and money already transferred

**Before transfer:** immediately HOLD affected allocations; reduce the candidate amount only from **verified existing Dokan/WC adjustments**; never transfer an unadjusted amount while a refund is pending.

**After transfer:** a buyer refund **does not automatically undo a separate Stripe transfer**. Record seller liability according to existing Dokan/marketplace contract and actual gateway events. Where permitted and supported, assess a partial/full **transfer reversal** tied to the original transfer, with its own operator controls and idempotency. Stripe may reject reversal if the connected account has insufficient available funds (subject to applicable reserves). Do not promise recovery.

**If seller owes KnifeRevive:** show a clearly labeled **negative seller balance / amount owed** based on authoritative reconciled entries. Apply legally and contractually authorized offsets against future eligible transfers only when supported, documented and visible to the seller. Do **not** charge a saved seller card, debit a bank account, or treat Stripe Express payout bank details as a reusable debit authorization without separate valid consent, supported payment-method setup/mandate, required notice, and legal review. If recovery is not supported, create a human-operated invoice/dispute workflow; no automatic card capture.

**Dispute handling:** on dispute creation, immediately HOLD any unreleased seller share, notify risk/admin, and show the seller only appropriately scoped information. Preserve the original sale and all subsequent adjustments. Do not mark a chargeback as resolved until processor evidence confirms it.

## 9. Seller emails and My Money copy

Use existing WP/WooCommerce transactional email infrastructure and template conventions. Store event notification keys so retries/webhooks cannot spam duplicate emails. Ensure SPF/DKIM/DMARC and delivery monitoring are operational before enabling notifications.

Required events (subject to actual verified status):

| Event | Seller-facing subject example | Required content |
|---|---|---|
| Sale paid, earnings held | “Your sale is in — here's what happens next” | Order reference, verified earnings, shipping/tracking action, explanation that funds are not yet eligible |
| Shipping needed | “Add tracking to keep your payout moving” | Safe link, deadline only if actually in seller terms |
| Carrier delivery verified | “Delivered! Your earnings are in review” | Earliest **estimated** eligibility date and refund/dispute caveat |
| Eligibility achieved | “Your earnings are ready for the next transfer batch” | Actual amount and expected weekly batch date, subject to final checks |
| Batch initiated / transfer created | “We've sent your earnings to Stripe” | Verified transfer amount, date, Stripe Express destination context, **not** “in your bank” |
| Transfer blocked or delayed | “An update on your payout” | Plain reason category, next step, support link; do not expose fraud investigation details |
| Refund/cancellation affects earnings | “Your earnings changed for order #…” | Recorded adjustment, amount, status, link to breakdown |
| Stripe payout status verified | “Stripe payout update” | Only use when connected-account payout status was actually observed |
| Negative balance detected | “Your seller balance needs attention” | Reconciled amount, order references, how to dispute, support |
| **Before proposed recovery** | **“Action needed: proposed recovery of seller balance”** | Amount, legal/contractual basis, method, **planned date**, itemized explanation, appeal/contact path, and meaningful advance notice; **do not initiate any charge** until consent and legal review confirm authorization |
| Recovery attempted / succeeded / failed | Accurate event-specific subject | Only after a real authorized processor action; no invented success |

**My Money statuses:** `Payment received`, `Waiting for shipment`, `Delivered — in review`, `Held for review`, `Eligible for next batch`, `Sent to Stripe Express`, `Bank payout pending`, `Bank payout paid`, `Refunded`, `Adjustment`, `Amount owed`. Render only when evidence supports the state; a Dokan withdrawal/Stripe transfer is not a bank payout.

Mobile/Gen Z tone: concise, transparent, non-patronizing. Show a timeline, “Why is this held?” explainer, estimated next batch, verified fee breakdown, and support route. No fake countdown timer or guaranteed payday.

## 10. Code boundaries and security

- Prefer edits in the **existing KnifeRevive Stripe Connect plugin** and existing **My Money** integration. Preserve installed Dokan/WooCommerce internals; no core/vendor plugin edits.
- Use WooCommerce CRUD APIs/HPOS-compatible reads, actual Dokan stored commission and seller ownership, Stripe SDK already installed, and the current account connection mapping.
- Strict admin capabilities, seller scoping, nonces/CSRF protection, least-privilege REST routes, and server-side validation of all amounts and destination IDs.
- Represent money in integer minor units and ISO currency. Never use floats for transfer amounts.
- Use durable locks, unique database constraints, state machine transitions, replay-safe webhooks, and immutable audit entries.
- Gate release operations behind a separate feature flag default **OFF**. Deployment cannot toggle it.
- Log redacted error categories and correlation IDs. Do not log full Stripe account tokens, seller bank details, secret keys or sensitive customer data.
- Avoid new cron that moves money until the human-approved release gate and event reconciliation are proven.
- Do not create a new Stripe Connect account, payout engine, fee/commission calculation, seller wallet, or off-session charge system.

## 11. Required acceptance scenarios

Implement automated tests where feasible and manual read-only checks where tests cannot run. **Do not declare live transfers safe merely because code compiles.**

1. Refunded **#2960** is held and never released without resolved, independently reconciled facts.
2. Unresolved **#2054** is held.
3. A canceled paid order, a fully refunded order, an open refund request, a dispute, and a chargeback are blocked.
4. Partial refund results in only the actual authoritative adjusted seller net, or HOLD if unresolved.
5. A seller marks an order “complete” without verified carrier delivery: **HOLD**.
6. Verified delivery + 72h elapsed + weekly cutoff + all other checks: eligible for **approval**, not auto-transfer.
7. New seller still within probation: longer hold and correct explanation.
8. Mixed-vendor parent order: each seller sees only their Dokan allocation; no parent-total overpayment.
9. Stripe destination changed since approval: HOLD.
10. Existing transfer found in Stripe but local queue still pending: HOLD and reconcile; no duplicate.
11. Concurrent job and manual action: one release claim, not two transfers.
12. Stripe timeout after unknown outcome: query and reconcile; do not blindly retry.
13. Refund arrives after manifest approval but before release: HOLD at final recheck.
14. Platform insufficient available funds: HOLD/error, never mislabel as transferred.
15. Stripe transfer succeeded: show “Sent to Stripe Express,” not “Paid to bank.”
16. Refund after transfer: liability/reversal review workflow, **not** an assumed successful reversal.
17. Seller negative balance without recovery mandate: no off-session card/bank debit.
18. Unauthorized seller/buyer cannot access other seller finances or admin release routes.
19. Webhook replay: no duplicate transfer, balance change or email.
20. Deployment/migration/plugin activation: zero money-moving API calls; legacy global queue remains OFF.

## 12. Codex execution order and deliverables

**Phase A — inspection (read-only):** inspect source/config, produce `STRIPE_SETTLEMENT_DISCOVERY.md`, historical allocation inventory, known gaps, and a short risk register. If the referenced settlement report is unavailable, say so.

**Phase B — code with no live money movement:** implement the pure eligibility gate, historical manifest export, admin-only approval flow, scoped release service **behind disabled flag**, event reconciliation, audit logs, seller notices, and My Money statuses. Provide a file-by-file diff summary and test outputs.

**Phase C — Stripe admin checklist:** produce `STRIPE_CONNECT_ADMIN_SETUP_CHECKLIST.md`, identifying which existing Stripe/Connect settings require a human to verify. Do not change live settings or credentials.

**Phase D — production read-only rehearsal:** generate actual manifest and evidence for each pending allocation, flag #2960/#2054, verify Stripe history, and record per-item `HOLD` / `NEEDS_REVIEW` / `ELIGIBLE_FOR_APPROVAL`. No transfer calls.

**Phase E — human signoff and controlled live execution (separate task):** only after an operator explicitly approves exact allocation IDs, amounts, destinations and batch hash, release a single approved item and verify it before proceeding. The **Codex agent must stop and request approval** at this boundary. Never drain the legacy queue globally.

**Phase F — monitor:** reconcile local transfer records to Stripe, inspect webhook failures and payout notifications, track seller support issues, and publish a post-release audit. Maintain emergency stop control.

### Required final Codex report

- Actual files changed and their purpose.
- Current installed Stripe/Dokan/WooCommerce versions and source-of-truth map.
- Before/after settings, with **no secrets**.
- Pending allocations manifest summary: counts and amounts by decision, with quarantined IDs.
- Unit/static/manual verification performed and what could **not** be tested.
- Feature flags and confirmation the global queue remains OFF.
- Any live transfer actions taken: **none unless separately and explicitly authorized**.
- Remaining risks and required human approvals.

## 13. References for implementation verification

- Stripe — Separate charges and transfers: https://docs.stripe.com/connect/separate-charges-and-transfers
- Stripe — Create transfer: https://docs.stripe.com/api/transfers/create
- Stripe — Transfer reversals: https://docs.stripe.com/api/transfer_reversals
- Stripe — Manage connected-account payouts: https://docs.stripe.com/connect/payouts-connected-accounts
- Stripe — Idempotent requests: https://docs.stripe.com/api/idempotent_requests
- Stripe — Connect account negative balances: https://docs.stripe.com/connect/account-balances
- Internal — `docs/KNIFEREVIVE_MY_MONEY_CODEX_IMPLEMENTATION.md`
- Internal — `KNIFEREVIVE_STRIPE_ADMIN_SELLER_SETTLEMENT_SETUP_2026-10-23.md` (read if available; reported baseline above requires verification)

**Final instruction to Codex:** Deliver code, a production-ready **read-only** reconciliation report, and an explicit human approval checkpoint. **Do not release pending funds “as is,” especially refunded or unresolved allocations.** The objective is to pay sellers correctly without creating a second payment or commission system, not to empty a queue at any cost.
