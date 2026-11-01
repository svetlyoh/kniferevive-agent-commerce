# KnifeRevive Returns: Codex Implementation and Deployment Runbook

**Specification date:** 2026-10-31  
**Audience:** Codex / WordPress-WooCommerce engineer / KnifeRevive owner  
**Status:** Engineering instructions, NOT authorization to enable financial features or rewrite published return rights  
**Priority:** Preserve buyer/seller trust, purchased terms, payment integrity, and private evidence  
**Primary evidence:** KNIFEREVIVE_CURRENT_RETURN_POLICY_IMPLEMENTATION_2026-10-31.md, verified 2026-10-31 (the owner's attached read-only production audit).

## Codex: first instruction

Read this document and the primary audit in full. Inspect the actual working tree, current deployed plugin versions, and matching release source before editing anything. **Do not assume this GitHub repository contains the live Returns/ShipKit source.** If any required plugin source is absent, report the exact missing repository/path and STOP that phase; do not create a competing return system. Implement in small, independently testable PRs. Deploy code behind OFF feature flags, then enable features only after the owner signs off on policy, funding, live-payment tests, and rollback.

Do not change production orders, cases, refunds, card charges, labels, taxonomy assignments, seller balances, or policy text merely to test. Use staging and sandbox fixtures first. Never invent a passing test or a successful live transaction.

## 1. Verified baseline: do not confuse installed with working

The attached 2026-10-31 report establishes the following. Treat these as documented observations, not proof of a complete end-to-end test:

| Capability | Baseline | Required result |
| --- | --- | --- |
| Purchased listing return terms | Implemented, captured in original Woo order-item snapshot | Preserve immutable terms, category rules and historic unknowns |
| Four live policies | Free / $5 / $10, each 15 days; No Returns | Do NOT silently install 30-day options or shorten to 72 hours |
| Buyer request form and nine reasons | Coordinator/UI exists, but **new requests OFF** | Safely enable only after policy review and tests |
| Seller response | Agree / propose alternative / request review; message required; routes to admin | Add seller-level approval for eligible routine cases with controlled exceptions |
| Buyer/seller messages and photos | Private evidence, shared messaging and Help path exist in source | Audit and improve UI; do not build duplicate storage |
| Admin review | Exists and is the current final decision authority | Retain override and mandatory review for disputed/high-risk cases |
| Formal appeal | **NOT implemented**; only Need help/reconsideration | Dedicated appeal action for BOTH buyer and seller |
| Return labels | Private Print/Download PDF for an **already issued** label | Real issuance pipeline and same private PDF for paid and free labels |
| Buyer-paid return label checkout | **NOT LIVE**; Oct 29 candidate incomplete | Fee-only checkout, exact quote, verified payment and label purchase |
| Return label issuance | **OFF** | Leave OFF until provider, funding, privacy and recovery tests pass |
| Refund execution | **OFF** | Leave OFF until gateway reconciliation and settlement checks pass |
| Return postage budget | **$0** | Configure approved funding before any free/shortfall label is purchased |
| Admin email | Outbox events implemented in source | Prove delivery/retry, not just enqueue |
| Tracking, inspection, refunds | Orchestration exists in source, unlaunched financially | Integrate safely; do not assume automatic refund on delivery |

Observed active versions: Return Policies 1.0.0; Return Policy Setup 1.0.0; Returns 0.1.0-rc4; Seller Orders 1.7.0-rc3; ShipKit 0.6.9-rc3; Stripe Connect 0.7.2-rc1; My Money 1.2.2. Dokan Lite is present; Dokan Pro is not required.

Observed Return Desk targets: seller response 48h, address confirmation 24h, buyer handover 7 calendar days, inspection 2 business days. These are review targets, NOT automatic forfeiture or refund deadlines.

Current policy references:
- https://kniferevive.com/return-policy/
- https://kniferevive.com/terms-and-conditions/
- https://kniferevive.com/sharpening-cancellations-and-refunds/

Relevant existing code authorities, if present: Policy.php, Cases.php, Access.php, Guest.php, Labels.php, Tracking.php, Inspection.php, Refunds.php, Jobs.php, Screens.php, Rest.php. Existing taxonomy: kr_return_policy. Existing order snapshots: _kr_return_policy_snapshot and _kr_return_policy_v2. Existing private case route: /?kr_return_case=ID (case ID is NOT authorization).

## 2. Marketplace research: transferable patterns, not policy replacements

| Marketplace | Official practice relevant to design | KnifeRevive takeaway |
| --- | --- | --- |
| Etsy | Listing-specific seller policies; buyer contacts seller; agreement through messages; escalation/case support for qualifying problems | Seller-first communication with a durable case timeline and admin escalation |
| Mercari | Formal order-level return request, evidence, review, carrier return label for approved eligible cases, tight platform-specific deadlines | Clear stages, evidence and controlled prepaid label issuance; **do not copy its 72h deadline** |
| Swappa | Private sale-page seller/support discussion; seller-defined voluntary returns; seller responsible for not-as-advertised return shipping, buyer for remorse | Shared private case and fair fault-based postage allocation |
| eBay | Buyer selects reason; seller handles request; buyer or seller return postage depends on reason and disclosed policy; progress and label access in return details | Reason-based cost matrix, label link, timeline, and platform intervention |

Official sources reviewed 2026-10-31:
- Etsy seller returns: https://help.etsy.com/hc/en-us/articles/360000572888-Refunds-Returns-and-Exchanges-for-Sellers
- Etsy buyer returns: https://help.etsy.com/hc/en-us/articles/115015440807-How-to-Return-or-Exchange-an-Item-on-Etsy
- Mercari returns: https://www.mercari.com/us/help_center/article/62/
- Swappa returns: https://swappa.com/policies/returns-and-refunds
- Swappa not-as-advertised: https://swappa.com/faq/answer/not-as-advertised
- eBay seller returns: https://www.ebay.com/help/returns-refunds/returns-refunds-sellers/returns-refunds?id=4079
- eBay return postage: https://www.ebay.com/help/buying/resolving-buying-problems/return-postage?id=4066

**Corrections to earlier draft research:** KnifeRevive already has a partial private case, messaging, evidence, admin-review and PDF-download architecture. Do NOT describe those as wholly nonexistent. Swappa's official policy says the seller pays when an item is not as advertised; do NOT use a third-party summary claiming all Swappa return shipping is buyer-paid. A Poshmark outbound shipping charge is not evidence of a universal buyer-paid return fee. Above all, do NOT replace KnifeRevive's purchased 15-day terms with Mercari's 72-hour window.

## 3. Product decisions the owner MUST approve before financial rollout

Prepare a decision record with the following proposals and seek explicit owner/legal review. Implement safe code paths, but keep relevant flags OFF until signed:

1. **Window anchor:** For NEW purchases only, propose a clearly displayed 15-calendar-day window starting on verified delivery, with a documented fallback for untracked pickup and a support path when delivery evidence is unknown. Existing historical purchases with an unknown anchor go to human review; never backfill invented delivery dates.
2. **30-day policies:** Not live. Add only in a separate reviewed release, and only for listings/new purchases where actually offered.
3. **Free Returns:** The buyer's label contribution is $0. Confirm whether the seller, platform or a funded reserve ultimately pays actual postage. Do not debit seller cards without contractual authorization.
4. **$5/$10 Returns:** These are disclosed purchased return costs, NOT automatically the carrier's real postage. Recommended conservative rule for eligible buyer-remorse returns: buyer's maximum postage contribution is the purchased $5/$10 amount, and never more than the explicit amount accepted at checkout. The difference between real postage and contribution must have an approved seller/platform funding source. No undisclosed surcharge or double charge. An alternative full-carrier-rate model requires NEW prospective disclosures and owner/legal approval, not retroactive application.
5. **Seller-fault / material not-as-described / wrong item:** Buyer contributes $0 for a justified approved return. Actual funding allocation requires documented finding and approved reserve/recovery policy.
6. **Damaged in transit:** Buyer contributes $0 while liability/insurance/carrier responsibility is investigated. Do not assume seller fraud or automatically charge seller.
7. **No Returns / final sale:** Not a bar to materially not-as-described, unsafe, authenticity, statutory or marketplace-protection review.
8. **Appeal period:** Propose 7 calendar days after a decision, with support discretion for delayed notice, accessibility, carrier issues and applicable rights. An appeal is NOT an automatic payment reversal.
9. **Seller decision scope:** Seller may approve an ordinary eligible return directly once terms and evidence are checked; seller may decline with reason; disputed seller-fault claims, unsafe/authenticity, unknown snapshots and conflicts require admin review. Admin can override with recorded finding.
10. **Refund scope:** Merchandise, tax, original shipping, promotions, shared multi-seller fees and partial refunds need allocation rules reviewed by finance/legal. Never silently deduct label fees from merchandise refunds.
11. **Shipping provider:** Evaluate a return-label API adapter (Shippo first as a candidate, EasyPost as fallback) against the existing ShipKit; approve production provider and costs only after sandbox and one controlled real-label test.
12. **Payout holds:** Use only authorized, narrowly scoped seller-at-sale settlement protections; verify Stripe Connect and seller agreement. Never describe funds as escrow unless they legally and technically are.

## 4. Proposed user journeys

### Buyer: account to refund

**My Account > Purchases > View Order > Request a Return**. Select exact purchased line/quantity, choose reason, write explanation, add photos where needed, and submit. Show purchased policy and remaining request window without rewriting it. Display a case ID, seller name, current step, due date if known, and a prominent **What happens next** panel. Do not ask buyer to email KnifeRevive as the default.

**My Account > Returns > Case**: show the timeline, seller response, messages/evidence, status, appeal control, shipping details, and label actions. After seller approval and required review, confirm return-from/destination and safe package weight/dimensions. Display carrier/service, actual label quote, purchased policy fee, buyer amount due, who funds the remainder, and quote expiry. **Require buyer acceptance of the exact amount**. If buyer owes money, show **Pay $X for return label** using native secure checkout. If buyer owes $0, show **Get my free return label** without a payment step. In BOTH cases, when issuance succeeds show **Download PDF label** and **Print label**; also send a private account link by email. Never claim label ready until provider success and private PDF is stored.

Then show packing instructions, **Drop off by [date]**, carrier tracking, **Carrier accepted**, **In transit**, **Delivered to return recipient**, **Inspection in progress**, **Refund approved**, **Refund submitted**, **Refund confirmed**. Display only verified events as verified. A buyer clicking **I dropped it off** is a buyer report, not carrier acceptance.

### Seller: review and inspect

**Seller Orders > Returns**: list cases assigned to seller-at-sale with status, deadline, affected units, reason, evidence indicator, and **Review request**. Case detail shows purchased policy, sale-time description/photos, buyer note/photos, response options **Approve return**, **Propose resolution**, **Decline with reason**, **Ask KnifeRevive to review**, plus **Message buyer** and **Appeal decision**. Show who is expected to pay shipping based on evidence and policy, but do not allow seller to override fee silently.

Seller confirms an authorized return address, receives tracking updates, records physical receipt, serial/SKU/quantity/condition, and uploads inspection photos if discrepancy. Seller does not directly execute unrestricted gateway refunds; retain Return Desk financial approval/reconciliation.

### Admin: fair adjudication

**WooCommerce > Return Desk** queues: New, Seller response overdue, Needs help, Appeals, Awaiting address, Funding blocked, Label error/uncertain, In transit exception, Inspection discrepancy, Refund pending/uncertain, Resolved. Admin sees original purchased terms, listing snapshot, shared timeline, restricted admin-only notes, evidence, immutable decisions, exact label funding ledger, payment provider IDs, and refund history. Admin receives queued email notifications on creation, appeals, seller nonresponse and important transitions. Every admin override requires actor, timestamp, rationale and finding; notify both parties of a customer-safe explanation.

## 5. Status contract and transitions

**Do not replace existing v2 case states wholesale.** Inspect Cases.php and map actual states to these customer-facing steps. Extend only where necessary. Keep status, substatus, responsible actor, next action and deadline distinct.

Conceptual progression:

~~~text
request_submitted
  -> seller_response_due
     -> seller_approved -> review_if_required
     -> seller_declined -> appeal_available / admin_review
     -> alternative_proposed -> buyer_accepts_or_counters
     -> seller_overdue -> admin_review
  -> return_authorized
  -> address_confirmation
  -> postage_responsibility_agreed
  -> quote_pending -> quote_ready
  -> buyer_payment_due [only if buyer_amount > 0]
     -> payment_verified
  -> funding_ready [includes buyer_amount = 0]
  -> label_purchase_pending -> label_ready
  -> carrier_accepted -> in_transit -> delivered
  -> inspection_due -> refund_review
  -> refund_approved -> refund_submitted -> refund_provider_confirmed
  -> resolved
~~~

Other branches: declined_with_reason, resolved_without_return, withdrawn_when_safe, quote_expired, funding_blocked, label_uncertain, carrier_exception, inspection_dispute, refund_uncertain. Formal appeal is an overlay with its own lifecycle: appeal_open -> evidence_exchange -> admin_decision -> appeal_closed; do not erase the underlying case or financial history. Every state change uses revision checking and an idempotency key. If a label purchase or refund result is uncertain, reconcile with the provider before any retry.

## 6. Return reasons and required evidence

Reuse existing reason keys: mismatch, wrong_item, damage, change_of_mind, unsafe, authenticity, missing, cancel, other.

| Reason | Buyer evidence | Routing | Buyer label charge |
| --- | --- | --- | --- |
| mismatch | Explain difference; photos of received item and relevant listing comparison; permit accessible support exception | Seller response then admin if disputed | $0 if confirmed material mismatch |
| wrong_item | Photo of item, packaging/label if available | Seller response; admin if disputed | $0 if confirmed |
| damage | Damage and packaging photos when available; safe handling | Review cause and carrier claim | $0 while approved fault claim reviewed |
| change_of_mind | Explanation; condition confirmation | Purchased policy and seller approval | Purchased disclosed amount only, capped as approved |
| unsafe | Safe description/photos only; do not require risky handling | Admin safety review; NO automatic ordinary label | $0 pending safe resolution |
| authenticity | Photos/identifiers if safe; no irreversible testing | Admin review; NO automatic ordinary label | $0 pending finding |
| missing | Shipment/order help, not physical return | Order help | No label |
| cancel | Before-shipment cancellation workflow | Order help | No label |
| other | Explanation; ask for evidence as appropriate | Admin review when ambiguous | Pending determination |

Do not require buyers to touch exposed knife edges or perform hazardous tests for evidence. Preserve original photo metadata/audit, scan uploads, validate MIME and size, and serve through role-checked private endpoints.

## 7. Technical architecture: extend, do not duplicate

- Reuse Returns 0.1.0-rc4 case coordinator, Seller Orders case storage, ShipKit private-document and label authority, Return Policies taxonomy/snapshots, Stripe Connect settlement authority and My Money read-only projections.
- Add formal appeals as a separate case-linked record or existing extensible event model; no second parallel return engine.
- Add explicit seller decision command with policy validation, seller-at-sale authorization, quantity reservation and optimistic concurrency; retain admin-only adjudication for protected/disputed cases.
- Extend Rest.php with versioned, permission-checked actions as appropriate: create/list/detail, seller-decision, shared-message, evidence-upload, appeal-open, appeal-response, appeal-decision, address-confirm, quote-refresh, quote-accept, fee-checkout-create, label-status, label-download, inspection-submit, refund-review. Use the actual established REST namespace; do not invent conflicting routes.
- Access.php and Guest.php remain the only order/case access authority. Enforce buyer identity, seller-at-sale, authorized recipient and admin role on every read/write/download. REST nonce is CSRF mitigation, not permission. Case ID, order number and file path confer no access.
- Use WooCommerce CRUD/HPOS-compatible order APIs; do not query wp_posts/wp_postmeta directly for order ownership or refunds. Preserve existing _kr_return_policy_snapshot and _kr_return_policy_v2; never mutate purchased terms from listing edits.
- Private PDFs and photos: no public Media Library URLs or guessable uploads/returns directory. Store encrypted or access-controlled files outside public web access, with short-lived authenticated download endpoints, safe Content-Disposition, audit and retention controls. A signed URL alone must not bypass role checks.
- Outbox notifications: transactional enqueue on state changes, deduplicated delivery keys, retries, observable failed queue. Emails contain order/case and secure link, not sensitive attachments or full address/PII.
- Record append-only timeline events with actor, role, timestamp, old/new state, reason, request/revision/idempotency keys, provider refs and privacy scope. Redact secrets from logs.
- WhatsApp seller messages are not the official return record. All return agreements, evidence, appeals and decisions must live on-platform.

### Minimum persistence / ledger fields

Existing case ID, order ID, original order item ID, seller-at-sale ID, buyer/guest authorization, purchased policy hash, quantity reservation, reason, requested resolution, timestamps, current revision, message/evidence IDs, seller decision and rationale, admin finding, appeal ID/status, address confirmation version, package dimensions, quote/provider/rate ID/expiry, buyer contribution and funding source, fee-only Woo order ID, verified payment transaction ID, provider label transaction ID, label status/tracking/PDF document ID, shipment grouping ID, carrier events, inspection records, refund allocation, Woo refund ID, gateway refund ID, seller recovery/IOU and outbox IDs. Keep provider secrets only in secure settings.

## 8. Shipping API, funding, and label PDF

**Do not assume WooCommerce checkout shipping methods or admin-only Woo label features expose a stable programmatic rate/label purchase API.** Existing outbound ShipKit Order #3453 reported live Woo options unavailable; that is not proof return labels work. Diagnose the rate handler and provider capability separately. Prefer an adapter interface that supports a tested Shippo implementation and optionally EasyPost; keep outbound ShipKit behavior unchanged.

Adapter operations: validate_address, quote_return, purchase_label, retrieve_label_pdf, get_tracking, void_or_refund_label_if_supported, reconcile_transaction. Providers and carrier services must be vetted for return-from/return-to US addresses, parcel weight/dimensions, hazardous/prohibited goods rules, and shipping sharp knives safely. Do not assume every carrier/service accepts every knife. Verify restrictions before quoting.

**Buyer-paid flow** (only after seller authorization, any required admin review, both addresses and explicit postage allocation):
1. Request fresh carrier quote server-side; show real price, carrier, service, expiry and buyer's purchased fee cap.
2. Persist quote + immutable funding agreement. If buyer owes $0, skip checkout entirely.
3. If buyer owes > $0, create one **fee-only** WooCommerce payment order linked to original order/case. Use native PCI-compliant checkout. Do not process card numbers in custom code.
4. On **verified** gateway payment completion, lock the case and reserve label purchase once. Browser success redirect is NOT proof of payment.
5. Check total provider cost is funded by buyer contribution plus approved seller/platform budget; if not, stop in funding_blocked with admin alert. Never buy a label with unfunded shortfall.
6. Call provider with persistent purchase identity and reconcile uncertain responses before retry. Never buy a second label merely because the user refreshes.
7. Verify returned label PDF, tracking, address and parcel match approved case. Save privately, mark label_ready, notify buyer. Expose same Print/Download PDF for paid and free returns.
8. If provider fails after payment, show **Payment received. Label is being prepared. You have not been charged twice.** Queue recovery and admin alert; reconcile/refund fee-only payment if label cannot be provided under owner-approved rules. Do not claim immediate delivery.
9. For free return, repeat steps 4-7 without buyer checkout; require approved funding. A $0 checkout order is not required.

Quote expiry: block purchase until requoted/reaccepted if price or contribution changes. Never silently increase buyer charge. Show exact fee and explicit checkbox/confirmation before payment. Fee-only payment and merchandise refund are different ledgers and must not be silently netted.

Shipping label failure states need actionable UI: quote_unavailable, address_invalid, parcel_invalid, carrier_restricted, funding_blocked, payment_pending, payment_verified_label_pending, provider_uncertain, label_ready. Log safe error codes, correlation ID, upstream HTTP status and sanitized provider message; never expose raw credentials or sensitive addresses. **Refresh options** must invoke a real server request, show loading, render success or a clear error, and preserve request history.

## 9. Formal appeals: both sides, fair review

- Show **Appeal this decision** to both buyer and seller for eligible decisions. Keep **Need help?** for general support.
- Appeal form: disputed decision, reason, requested outcome, explanation, additional photos/files, acknowledgement that appeal does not instantly reverse shipping or refund.
- Notify other party and Return Desk; show response opportunity and admin review target. Proposed 7-day submission period is owner decision, not a live policy fact.
- One open appeal per decision version; repeated clicks are idempotent. Allow new appeal for a genuinely new decision or material new evidence subject to admin policy.
- Admin records evidence considered, finding, decision and any prospective changes. Notify both parties with understandable rationale; preserve admin-private material.
- Hold only future reversible financial actions where technically and contractually permitted; do not retroactively reverse a confirmed refund or issued carrier label.
- Escalate safety/authenticity, nonresponsive seller, suspected return fraud, changed return address, lost package and payment dispute without automatically penalizing either party.

## 10. Incremental Codex work orders

For **every** work order: create a separate branch/PR; add tests, screenshots or CLI evidence, migration/rollback notes, affected plugin versions and a completion matrix. Do not mark complete from unit tests alone.

### PR 0 — Inventory, source verification and deployment safety

**Tasks:** Identify exact repositories and plugin roots for Returns, Return Policies, Seller Orders, ShipKit, Stripe Connect, My Money and Dokan adapters. Compare source to live versions and the attached audit. Enumerate existing tables, REST routes, case states, capabilities, flags, WP-Cron/Action Scheduler and outbox behavior. Export read-only config and a redacted architecture map. Inspect current Return Desk switches without changing them.

**Acceptance:** A source-of-truth matrix (code path, deployed version, actual status, missing tests), reproducible staging environment and rollback artifact. STOP if source cannot be located. No production writes.

### PR 1 — Policy contract and purchased-term safeguards

**Tasks:** Introduce a reviewed, versioned policy contract for NEW purchases only; preserve historical unknown anchors for human review. Ensure knives, technology <= $150 (including exactly $150), phones > $150 exception, tech > $150 non-phone, world coins/spices and unclassified categories route per published rules. Verify selected 15-day terms, No Returns and seller-at-sale snapshot. Add a customer-visible policy summary and window basis. Do not install 30-day terms yet.

**Acceptance:** Historical listing edits never change purchased rights; unknown anchor never silently expires a claim; legal exceptions remain reviewable; no 72h substitution. Flags OFF.

### PR 2 — Seller-first approvals and status clarity

**Tasks:** Extend Cases.php with seller_approve and seller_decline_with_reason commands for ordinary eligible requests. Validate owner/seller-at-sale, policy, units, revision, prior refunds and existing label/appeal state. Keep admin review for disputed/unsafe/authenticity/unknown snapshot/high-risk cases. Reuse messages/photos; improve buyer/seller Return Desk pages with step tracker, next action, due date and accessible mobile controls.

**Acceptance:** Seller can approve or decline with explanation; buyer sees immediate next step; seller cannot approve another seller's order or override protected fault claim; all actions audited. Do not turn request acceptance ON yet.

### PR 3 — Formal two-sided appeals and notifications

**Tasks:** Add appeal_open, appeal_response, appeal_decide and appeal timeline/UI; admin Needs Appeal queue; deduplicated email outbox for request, seller action, appeal, address, label, delivery, inspection, refund and errors. Add admin-private vs shared visibility controls and evidence attachments.

**Acceptance:** Buyer and seller independently appeal the same type of decision; admin resolves with rationale; no duplicate appeals from double-click; private notes never leak; queued emails are actually delivered/retried in staging; sender/from address verified.

### PR 4 — Return-label provider spike, no real charges

**Tasks:** Define label adapter and implement provider sandbox for quote/purchase/void/retrieve PDF/tracking; compare Shippo and EasyPost against Woo-assisted ShipKit using official APIs and current account permissions. Prove exact rate and PDF issuance for US return addresses, package constraints, knife/carrier eligibility and failure recovery. Instrument Refresh button end-to-end. Do not change outbound labels.

**Acceptance:** Quote success, quote timeout, malformed address, expired rate, forbidden service, provider 4xx/5xx and duplicate request all produce deterministic results. A sandbox PDF can be privately downloaded by authorized buyer only. Owner approves provider before PR 5.

### PR 5 — Buyer contribution checkout and funding ledger

**Tasks:** Implement cap/zero-cost rules only after signed owner decision. Build quote acceptance and fee-only Woo order via current payment gateway. Link parent original order, seller scope, case, quote and funding ledger. Verify payment through gateway/webhook reconciliation. Prevent double checkout/charges; support failed/abandoned payments and refunds of fee-only payments as a separate flow. Validate seller/platform shortfall reserve.

**Acceptance:** Free and seller-fault return: buyer $0, no card step. $5/$10 eligible return: buyer never charged more than purchased cap or confirmed checkout total. Paid and unpaid cases cannot buy unfunded labels. Webhook replay and two browser tabs do not double charge. Feature OFF in production.

### PR 6 — Real label issuance, private PDF and manual fallback

**Tasks:** Integrate funded purchase through ShipKit authority and chosen provider. Support both paid and free paths. Return the same private PDF for Download and Print, including reprints without new postage. Email secure account link. Track uncertain provider responses and reconciliation. If manual Ship Desk fallback is used, display realistic pending state; do not advertise instant labels.

**Acceptance:** One funded case -> at most one active purchased label per shipment group; correct from/to; buyer gets printable PDF regardless of buyer contribution; unauthorized users get 403; failed provider after paid fee creates recoverable pending case, not silent loss. Budget must be approved before controlled live test.

### PR 7 — Tracking, inspection, financial reconciliation

**Tasks:** Connect verified carrier events, buyer-reported drop-off, destination receipt and recipient inspection without conflating them. Handle multiple units, partial/multi-seller parent orders, shipment grouping, serials, discrepancies and restock-once. Retain admin refund approval and gateway lookup/idempotency. Integrate seller recovery/Shipping IOU and My Money projection without automatic unauthorized debit.

**Acceptance:** Carrier delivered does not auto-refund; refund approved != gateway submitted != provider confirmed != bank settled; replay cannot double-refund; disputes preserve evidence and reconciliation; original tax/shipping allocations are checked.

### PR 8 — Accessibility, policy copy and staged launch

**Tasks:** Update proposed public return-policy wording ONLY after owner/legal approval. Add order and seller help text, responsive timeline, accessible upload controls, email templates, clear label cost breakdown and safe knife packing instructions. Document support fallback, manual exceptions and customer service runbook. Pilot with controlled buyer/seller test identities and one low-value real label/payment only after explicit approval.

**Acceptance:** Full buyer, seller and admin walkthroughs on mobile and desktop; successful paid label and free label PDF; formal appeals from both parties; emails delivered; no policy regression; signed release checklist. Turn switches ON separately in the approved order, not in one blanket activation.

## 11. Required automated and manual test matrix

At minimum include:
1. Buyer, seller-at-sale, recipient, guest verified link and admin permissions; unrelated seller/buyer forbidden.
2. REST CSRF, capability, object ownership, revision conflict and direct PDF URL guessing.
3. Policy snapshot immutability, old unknown anchor, category threshold exactly $150, phones exception, No Returns exceptions, unconfigured 30-day policy.
4. Duplicate case, overlapping quantities, partial refund, multi-line/multi-seller order and old legacy case.
5. Seller approve, decline, counteroffer, no response at 48h, admin override, buyer and seller appeal, duplicate appeal.
6. Required photo prompts and secure upload; unsafe item never enters ordinary label purchase automatically.
7. Buyer contribution $0 for Free/seller fault, correct purchased $5/$10 cap, quote expiry, address changes, no undisclosed postage.
8. Payment success, failure, abandonment, asynchronous webhook, duplicate webhook, payment captured/provider timeout and fee-only refund reconciliation.
9. Provider quote timeout, unavailable rates, invalid package, prohibited carrier service, purchase uncertain, duplicate refresh, private PDF, free PDF, reprint without repurchase.
10. Tracking first scan vs buyer report, delivery vs inspection, damaged/empty return, lost parcel, seller unavailable.
11. Refund approval, gateway submit/lookup, replay, partial tax/fee allocations, seller settlement/IOU, chargeback and uncertain outcomes.
12. Outbox queue processing, email delivery/retry, redacted logs, accessibility, mobile screens, WC HPOS and plugin compatibility.
13. Full return flow from order to final refund with both funding branches, and separate formal appeal walkthroughs for each role.

Evidence required: test command/output, fixtures, screenshots, redacted webhook/provider logs, code coverage for money and permissions, exact plugin build hashes, live/staging parity checks, manual tester sign-off. No claims of completion without this evidence.

## 12. Deployment and rollback checklist

**Before each deployment:** backup database and private return files; record exact active plugin versions and flags; confirm compatible PHP/WP/Woo/Dokan versions; prepare signed release ZIPs; check schema migrations are forward-safe; stage with representative historic orders/cases; test order and fee-only payment webhooks; document rollback for code and data. Never expose API keys in Git.

**Deployment order:** ship code with all return creation/label/refund financial flags OFF -> run read-only health and permissions checks -> verify admin queue/outbox -> enable request creation for controlled pilot only after reviewed policy version is set -> test seller response/appeal -> enable return label issuance after provider and budget sign-off -> enable refund execution only after separate finance sign-off and reconciliation. Each switch must be independently reversible and recorded.

**Rollback:** disable request creation/label issuance/refund execution without deleting cases; preserve any paid fee-only order, pending/issued label, provider transaction and refund; reconcile uncertain external operations before retry. Do not downgrade a database schema if irreversible; use compatibility migrations and manual recovery procedures. Re-enable manual support path prominently.

**Production go/no-go:** owner-signed policy clock and postage allocation; budget > 0 when necessary; verified provider account and knife-shipping eligibility; zero critical auth/payment defects; staging full E2E both label paths; formal appeals both roles; emails actually delivered; Stripe Connect refund/seller payout review; controlled live test signed off. If any fails, leave flags OFF.

## 13. User-facing copy (proposed, not yet published)

- Request screen: **Start a return** — Choose the item and tell the seller what happened. Add photos if the item arrived damaged or different from the listing. **Don't ship it yet.**
- Submitted: **Request sent to your seller.** We'll keep the conversation and updates here. Expected seller response: within 48 hours; if overdue, KnifeRevive can review.
- Seller: **A buyer requested a return.** Review the purchased policy, their explanation and photos. Approve, propose another solution or decline with a reason. Either side can ask KnifeRevive to review.
- Seller approved: **Your seller agreed to the return.** Next, confirm addresses and the return-shipping amount. Do not send the item until your label is ready.
- Paid quote: **Return shipping: $5.00 paid by you** (example ONLY, derived from actual purchased terms and confirmed quote). **No payment is taken until you approve this amount.**
- Free quote: **Your return label is covered.** No payment needed. Download the PDF once it's ready.
- Pending provider: **Your payment was received. We're preparing your label.** You won't need to pay again. We'll notify you when the PDF is ready.
- Label ready: **Your return label is ready.** Download PDF | Print label. Pack the knife securely so the edge cannot cut through the package; follow carrier rules.
- Appeal: **Disagree with this decision?** Ask KnifeRevive to review. Add a note and any new evidence. Both buyer and seller can appeal.
- Refund: **Refund approved — not sent yet.** We'll update this page when the payment provider confirms the refund was submitted.

Avoid playful phrases that minimize a damaged, unsafe or expensive-item dispute. Show precise next action and actual status; never say refunded when only approved.

## 14. Deliverables and final Codex reporting format

For each PR provide:
- Source inventory and exact files changed (and why reuse vs extension was chosen).
- Tests executed and raw pass/fail summary; manual verification evidence.
- New/changed API contracts, schema migrations, feature flags, permissions and webhook/idempotency guarantees.
- Policy decisions still awaiting owner approval.
- Staging deployment URL or release ZIP and build hash; production deployment only after the go/no-go checklist.
- Current flags and outstanding financial/provider exceptions.
- Rollback and reconciliation instructions.
- A truthful capability matrix: implemented in source / deployed disabled / tested on staging / tested in production / enabled live.

**Do not finish with “done” if buyer-paid labels, free PDFs, formal appeals, seller approval or refunds have not been verified end-to-end.** A passing unit test is not a working marketplace return.

## 15. Codex start prompt

Read docs/CODEX_KNIFEREVIVE_RETURNS_IMPLEMENT_AND_DEPLOY_2026-10-31.md and the owner's KNIFEREVIVE_CURRENT_RETURN_POLICY_IMPLEMENTATION_2026-10-31.md. Begin with PR 0 ONLY. Locate the actual plugin sources and active releases; audit against the attached baseline; produce a gap matrix and safe staging plan. Do not edit production data or enable any feature. Stop and request missing source or owner policy decisions rather than guessing. After PR 0 review, implement PR 1 through PR 8 sequentially, each with tests and a rollback plan. Deploy code behind OFF flags; enable live payments, labels and refunds only after explicit approval and real reconciliation proof.
