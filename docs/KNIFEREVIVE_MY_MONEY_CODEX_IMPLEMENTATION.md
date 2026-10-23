# KnifeRevive — “My Money” Seller Tab
## Codex implementation brief: reuse Dokan + WooCommerce accounting and existing Stripe Express payout flow

**Document type:** implementation instructions for Codex  
**Scope:** seller-facing finances UI inside KnifeRevive My Account  
**Status:** specification, not a claim that the feature is deployed  
**Core rule:** **BUILD A READ-ONLY FINANCIAL VIEW OVER THE ACCOUNTING THAT ALREADY EXISTS. DO NOT CREATE A SECOND COMMISSION, BALANCE, REFUND, TRANSFER, OR PAYOUT ENGINE.**

---

## 0. Mission and non-negotiable principle

Create a modern, mobile-first seller-facing **My Money** tab in KnifeRevive's existing **My Account** seller experience. It must make it immediately clear:

- What I sold
- What the buyer paid (where appropriately attributable to this seller)
- What KnifeRevive/Dokan took as marketplace commission
- Any other *actually recorded and seller-borne* fees
- What I earned on each order
- What was canceled, refunded, or reversed
- What is pending or available according to the existing Dokan setup
- What has been transferred/disbursed to Stripe Express, versus what has actually been paid out to a bank, if independently confirmed
- How to open the existing Stripe Express dashboard for additional payout detail

**Dokan and WooCommerce already calculate and record these values. The task is to find, read, reconcile, and explain those existing records.** Use the currently installed versions and their supported APIs/helpers/hooks. Do not apply a new percentage to sales, infer a commission from current settings for historical orders, or treat an order's WooCommerce gross total as the seller's earnings.

**Priority order:** authoritative stored Dokan accounting/withdrawal records → WooCommerce orders/refunds and seller line-item ownership → existing Dokan Stripe Express transaction/transfer evidence → verified Stripe connected-account balance/payout evidence, if available. If two sources disagree, do not silently choose a fabricated number; surface an internal reconciliation flag and show only verified seller-facing facts.

## 1. Inspect the existing installation FIRST — mandatory discovery phase

Before coding, inspect the real KnifeRevive site/plugin workspace and produce `MY_MONEY_DISCOVERY.md` with evidence for each item below:

1. Active WordPress, WooCommerce, Dokan Lite/Pro, Dokan Stripe Express, Stripe gateway, theme, My Account customizations, ListLab plugin, and any existing seller/withdrawal/commission add-ons; record versions.
2. How seller accounts and capabilities are recognized; how My Account is rendered; whether the intended URL is `/my-account/`, and whether ListLab already adds `/my-account/listlab/`. Do **not** assume an endpoint is available without checking.
3. Where the current seller dashboard already shows sales, reports/statements, balances, earnings, withdrawal requests, Stripe Express connection, and payout/disbursement status.
4. The actual Dokan APIs, service objects, functions, data stores, order meta, withdrawal records, commission snapshots, vendor order relationships, and refund records **in this installed version**. Use supported public interfaces where available; document any unavoidable internal reads and compatibility risk.
5. How the site handles **suborders and mixed-vendor orders**. Prove that seller totals include only the seller's allocated items, taxes, shipping, fees, refunds, and commissions, rather than the full parent order.
6. Whether payment processing fees are seller-borne or platform-borne, and whether a recorded value exists. Never estimate or display a made-up Stripe fee.
7. Whether Dokan Stripe Express is active and its actual disbursement mode (on payment complete, on order complete, delayed), plus any manual withdraw/threshold configuration. Do not conflate these flows.
8. Which connected Stripe account IDs, transfer IDs, payout IDs, balance transactions, webhook events, and settlement timestamps are **already persisted**. Determine whether the site has permission and a safe supported integration path to query additional Stripe facts.
9. Existing ListLab/My Account JS, CSS, route registration, REST conventions, security model, accessibility patterns, loading skeletons, responsive layout, and navigation behavior.
10. Relevant project documentation and existing implementation reports, including the ListLab technical implementation report, seller messaging report, and repository documentation. Verify their statements against the installed code rather than assuming they match production.

**Discovery deliverable:** a mapping table: UI field → exact source (class/function/table/field) → owner/scope → source status semantics → known limitations → test case. Include real example records with personal/financial identifiers redacted. If a source cannot be established, mark the field **unsupported** and do not ship it as a fact.

### Source-of-truth matrix to establish

| UI field | Expected authority | Hard rule |
|---|---|---|
| Order identity/date/status | WooCommerce CRUD + Dokan seller ownership | Never expose another seller's order |
| Seller gross sales | Dokan vendor order/line allocation, reconciled to WC | Never use full parent order for a multi-seller sale |
| Marketplace commission | **Stored Dokan order commission/earning record** | Never recompute using current commission settings |
| Seller earnings | Dokan's recorded seller earning/net amount | No independent replacement formula |
| Refund amount and status | WC refund objects + Dokan refund/commission adjustments | A refund request is not a successful payment refund |
| Cancellation | WC order/suborder status and financial evidence | Cancellation does not imply a refund |
| Seller balance/withdrawal | Dokan balance, withdraw, statement and relevant Stripe Express module records | No custom balance engine |
| Transfer/disbursement to connected Stripe account | Existing gateway/Dokan transfer record, verified if necessary | Transfer ≠ bank payout |
| Bank payout | Stripe connected-account payout record, **only if authorized and verified** | Do not infer bank arrival from a Dokan withdrawal or transfer |
| Seller-borne processing/withdraw charges | Existing recorded fee records | Show only if attributable and real |

## 2. Placement and navigation

- Seller-facing tab label: **My Money**.
- Add it to the **existing KnifeRevive My Account seller navigation** next to the existing seller tools/ListLab, following current navigation and endpoint conventions.
- Suggested endpoint slug: `my-money` (e.g. `/my-account/my-money/`), but reuse an existing route if one is already present. Verify endpoint rewrite handling and refresh rewrite rules only through standard WordPress activation/migration flow; do not flush on every request.
- Render only for authenticated, eligible sellers; admins may use existing authorized seller-view facilities if present, but no arbitrary `seller_id` impersonation.
- Buyers should not see seller financial navigation. A direct buyer/guest URL must return an appropriate denial or redirect without leaking data.
- This is **not** a new checkout, wallet, withdrawal-request, or payment settings page. Link to existing Dokan/Stripe settings for actions that already exist.

## 3. Experience and tone

Use a clean, contemporary visual language: high legibility, generous spacing, restrained accent color, subtle borders, 12–16px radius, accessible contrast, concise labels, smooth but reduced-motion-aware disclosure animation. Match KnifeRevive's current design tokens rather than introducing an unrelated design system.

**Gen Z-friendly but financially precise:** short, human copy; no slang that obscures money.

Recommended copy:

- Page: **My Money**
- Intro: “See what you've earned, what's still processing, and where your payouts stand.”
- KPI: **Total earned** — explain the date range and whether before/after refunds
- KPI: **Ready to withdraw** or **Available** — **only** if Dokan's actual balance definition supports that label
- KPI: **On the way** — only for a real verified payout/transfer state, with an accurate destination
- KPI: **Paid out** — only for confirmed payout records, not order payment
- Activity heading: **Money moves**
- Filters: **All**, **Sales**, **Refunds**, **Transfers / withdrawals**, **Payouts** (only categories with real data)
- “View breakdown”, “Why did this change?”, “Open Stripe dashboard”, “View order”
- Helpful info: “A sale can be paid by the buyer before your earnings are available to withdraw.”
- Empty state: “No money moves yet. Your sales and payouts will show up here.”
- Stripe not connected: “Connect Stripe Express to set up your payout account.”
- Missing verification: “Payout details aren't available here yet. Check Stripe for the latest status.”

Avoid: “cash landed” when a transfer merely initiated, “instant payout” unless verified, celebratory copy around refunds, and unqualified “net profit” (expenses and taxes outside the platform may be excluded).

## 4. Page anatomy

### A. Header

**My Money** with an accessible date range selector (default: last 30 days; also 7 days, 90 days, custom if feasible), a compact “How it works” disclosure, and a link to existing Stripe Express dashboard/payment settings. Date ranges and timezone must be explicit; use store/account timezone consistently.

### B. Summary cards

Prefer up to four cards, sourced from existing records:

1. **Sales** — seller-attributable gross sales in selected period, clearly defined.
2. **Your earnings** — Dokan's seller earnings for the period, with refunds/adjustments treated according to the existing ledger.
3. **Available** — live Dokan withdrawable/available balance if verified; this is a *current balance*, not the sum of the selected period.
4. **Paid out** — verified settled payout/withdrawal amounts, with the label adjusted to source semantics.

If “pending” is reliable, show it as a small line within the balance area. **Do not force four cards by inventing unavailable data.** Do not combine a date-range aggregate and a live balance without labeling the difference. If Stripe bank payouts are not accessible, use “Sent to Stripe” only when a verified transfer supports it; otherwise display a link and a transparent unavailable state.

Each card has a tooltip/disclosure defining precisely what is included, especially shipping, taxes, commissions, refunds, and processing fees.

### C. Money moves — chronological activity

One unified **seller-scoped, paginated, server-sorted** activity stream, newest first. Each item shows:

- Semantic icon and event label (sale, refund, cancellation, adjustment, withdrawal, transfer, payout)
- Order number or payout/withdrawal reference when applicable
- Event date/time
- Primary amount with currency
- One status badge grounded in the authoritative source
- Expand/collapse chevron; entire row keyboard accessible
- Optional product thumbnail/title when appropriate and already authorized

**Important:** different event types have different money semantics. A sale's amount may be *seller earnings*, while a refund is a *negative adjustment*; label consistently. If needed, show “Order total” as a secondary figure rather than pretending it is seller earnings. Prevent duplicate activity entries for a single financial movement when WC/Dokan/Stripe each record the same underlying event.

### D. Expandable Stripe-style detail drawer/panel

An inline disclosure below the selected row (mobile) or a side detail panel (desktop), without losing list scroll position. Keep one open at a time by default, support deep links to an activity item if feasible, and include:

- Event title, precise status, timestamp, order reference
- Seller's products and quantities
- **Order breakdown** (only source-backed components): seller-attributable item subtotal, seller-attributable shipping/tax if applicable, discounts, actual marketplace commission, seller-borne processing/withdrawal fees if recorded, seller earnings
- **Financial changes**: partial refund(s), commission reversal(s), seller balance adjustment(s), chargeback/dispute adjustments when supported, with dates and references
- **Money journey**: buyer payment recorded → Dokan earning recorded → eligibility/hold state → Dokan withdrawal/disbursement/Stripe transfer → Stripe payout → bank paid, **only for events independently evidenced**
- Contextual “View order” and “Open Stripe dashboard” links to existing authenticated destinations

Do not claim a completed payout timeline merely because an order is marked completed. Show “Not available” rather than manufactured status milestones.

### E. Helpful explanations

A short accordion “How your money works”:

1. **Sale:** the customer pays for an order; your earnings are the amount Dokan records for your seller share.
2. **KnifeRevive fee:** Dokan applies the existing commission rules; the fee shown is the historical recorded amount for that order.
3. **Refund/cancellation:** refunds and commission adjustments affect earnings when actually recorded; cancellation alone may not move money.
4. **Transfer vs payout:** funds moving to your Stripe Express balance are not necessarily already in your bank account.
5. **More details:** Stripe Express is the source for bank payout timing where that information is not available in KnifeRevive.

## 5. Refunds, cancellations, and strikethroughs — precise rules

**Never delete, overwrite, or visually erase the original sale.** Preserve original amounts and show adjustment events.

### Full refund example (illustrative ONLY; not an accounting formula)

A row may display:

- “Order #1234 · Refunded”
- Original seller earnings: ~~$85.00~~
- Adjusted seller earnings: **$0.00** **only if** Dokan's actual recorded net for that order is zero
- Expanded detail: original sale, actual refund event(s), commission reversal(s), final recorded earnings, refund processing state

If Dokan still records a nonzero balance or the refund is pending, show the *actual* recorded amount and a pending/review status instead of zero.

### Partial refund

- Show original amount and actual updated seller earning if both are authoritative.
- Prefer “$85.00 originally → $60.00 after refund” with an adjustment of “−$25.00” when those figures are recorded and reconciled.
- Do **not** strike through the entire order as if fully refunded.
- Item-level returns should show the affected item and quantity; unaffected items remain normal.

### Cancellation without refund

- Show “Canceled” as the **order** status.
- If no captured payment/earning occurred, no fabricated negative refund entry.
- If the order was paid, distinguish “Canceled · Refund pending” from “Refunded” based on the actual gateway/Dokan state.

### Failed/pending refund, dispute, chargeback

- “Refund requested” or “Refund processing” is **not** “Refunded.”
- A WooCommerce refund record is not by itself proof that the external payment processor returned funds to the buyer. Use existing gateway evidence and clearly label scope.
- Stripe transfer reversal and Stripe refund are distinct events. Do not assume one implies the other.
- Keep historical values and an accessible text explanation, not just a red strikethrough.

### Styling and accessibility

- Use semantic `<del>` for a superseded original **display** amount when a verified replacement exists; use an explicit accessible label “Original amount.”
- Never strike through an amount if no replacement value is verified.
- Use signed adjustments (`−$25.00`) with clear “Refund adjustment” wording; do not rely solely on red/green color.
- Use localized currency formatting, minus signs, timezone, and zero-decimal currency handling; do all calculations in the precision supported by the authoritative source and avoid floating-point money arithmetic.
- A strikethrough is a **presentation convention**, not a modification to the ledger.

## 6. Dokan and Stripe Express: keep each layer's meaning

**Do not confuse these five states:**

1. **Customer paid** — payment verified by the existing WC gateway.
2. **Seller earned** — Dokan records the seller share/commission.
3. **Eligible/available** — Dokan's withdrawal/disbursement rules permit movement.
4. **Transferred/disbursed to Stripe Express** — a real Dokan/Stripe transfer or settlement event exists.
5. **Paid out to bank** — Stripe's connected-account payout is confirmed by a real payout record/status; “paid” from Stripe is not a guarantee of final bank availability in every edge case.

Dokan Stripe Express may disburse **on payment complete**, **on order complete**, or **after a configured delay**. Manual withdrawal configuration may behave differently from adaptive gateway settlement. Read actual configuration and existing records before displaying eligibility dates or controls.

- Reuse Dokan's existing connected-account onboarding and “Visit Stripe dashboard” flow. Never create a second Connect account, bank-account editor, OAuth flow, or payout scheduler.
- If a seller is not connected, use the existing secure onboarding route.
- If Stripe data is fetched server-side, use existing authorized platform credentials and verified connected account ownership; never send secret keys or raw connected account IDs to the browser.
- If Stripe bank payout status cannot be read with current integration and permissions, **do not pretend**. Show verified Dokan disbursement and a clear “See bank payout status in Stripe Express” link.
- Do not call a Dokan withdrawal “paid to bank” unless the source actually verifies that outcome.
- Avoid extra Stripe API calls if Dokan already exposes the needed verified data. If needed, use bounded caching, webhooks/reconciliation already owned by the gateway, and rate-limit/error handling. Never build a competing webhook processor without a documented gap and approval.
- The UI is read-only. No new seller-triggered refund, withdrawal, transfer, or payout actions in this feature.

## 7. Engineering architecture

### 7.1 Ownership and plugin placement

Implement in the existing custom KnifeRevive seller/My Account extension (or a small companion plugin if no suitable extension exists). Follow the project's established ListLab route/component conventions. **Do not modify WooCommerce, Dokan, Dokan Pro, Dokan Stripe Express, payment gateway, or WordPress core files.** Do not patch their databases or alter accounting hooks.

Prefer an adapter/service structure:

- `SellerMoneyAccess`: logged-in seller eligibility and resource ownership checks
- `SellerMoneyRepository`: reads seller-scoped Dokan/WC records using supported interfaces
- `SellerMoneyNormalizer`: maps records to stable UI view models **without changing source amounts**
- `SellerMoneyStripeAdapter`: optional, read-only gateway facts where verified/authorized
- `SellerMoneyController`: REST responses, pagination, validation and no-store headers
- `SellerMoneyView`: My Account endpoint + frontend application

Names are illustrative; conform to existing project conventions.

### 7.2 REST/data contract (proposed; adapt to actual code)

Register authenticated read-only endpoints using `rest_api_init` and a versioned KnifeRevive namespace, e.g.:

- `GET /wp-json/kniferevive-seller-money/v1/summary?from=YYYY-MM-DD&to=YYYY-MM-DD`
- `GET /wp-json/kniferevive-seller-money/v1/activity?from=...&to=...&type=...&page=1&per_page=20`
- `GET /wp-json/kniferevive-seller-money/v1/activity/{opaque_or_verified_id}`

Actual route namespace may be reused from the existing My Account plugin; do not duplicate registered routes.

Use `permission_callback`, current user identity, nonce/session protections appropriate to WP REST cookie auth, seller ownership checks **on every lookup**, strict date/filter/limit validation, bounded pagination, safe serialization, and `Cache-Control: private, no-store`. Never accept an untrusted vendor ID to choose a seller. Do not return buyer addresses, full buyer PII, payout bank numbers, gateway secrets, or other vendors' records.

Example **view model**, not a new ledger or database schema:

```json
{
  "id": "sale:source-backed-id",
  "kind": "sale",
  "order_number": "1234",
  "occurred_at": "2026-10-20T15:00:00Z",
  "currency": "USD",
  "seller_earnings_minor": 8500,
  "status": {
    "label": "Earned",
    "source": "dokan_recorded_earning"
  },
  "breakdown": {
    "seller_gross_minor": 10000,
    "marketplace_commission_minor": 1500,
    "processing_fee_minor": null,
    "seller_earnings_minor": 8500
  },
  "related_events": [],
  "verification": {
    "accounting": "verified",
    "bank_payout": "not_available"
  }
}
```

**Example only:** do not hardcode a 15% fee or treat this shape as proof that Dokan stores the fields as shown. Null means unknown/not applicable; zero means verified zero. For every derived value, document the exact derivation and cross-check with Dokan's official seller report for the same order. If Dokan reports a value that cannot be explained, preserve the Dokan figure and flag the mismatch internally.

### 7.3 Money and reconciliation safeguards

- Treat existing Dokan transaction/earning/withdrawal records as the financial authority; WooCommerce provides order/refund context, not a replacement payout ledger.
- Deduplicate parent/suborder records and refund/withdrawal/transfer events by verified source identifiers.
- Preserve historical commission snapshots; changing a seller's commission today must not rewrite older rows.
- Multiple currencies: partition totals by currency; never sum USD and EUR into a single card.
- Distinguish order creation date, payment date, refund date, Dokan posting date, transfer date, and Stripe payout date. The chosen date range must state which event dates it filters.
- Handle pending, failed, retried, partially reversed, disputed, and out-of-order webhook situations without double counting.
- If a recorded Stripe transfer has a different value than Dokan earnings due to fees/adjustments, show source-backed components and a clear reconciliation state; do not silently alter Dokan's commission.
- No new financial write tables are needed for this read-only UI. A bounded, non-authoritative read cache is permissible only if necessary and invalidated appropriately.
- Use WC CRUD/HPOS-compatible access; do not depend on legacy `wp_posts`/`postmeta` order queries when WooCommerce data stores are available.
- Existing services and sharpening orders should appear only if the seller actually owns the relevant seller-accounting record. Do not accidentally attribute platform-operated sharpening revenue to a marketplace seller.

## 8. UI behavior and performance

- Desktop: summary cards + responsive activity table with expandable detail; mobile: stacked summary cards + tappable activity cards and inline accordions.
- Support keyboard activation, focus-visible, aria-expanded/aria-controls, Escape close where relevant, semantic headings and status text, accessible currency/strikethrough descriptions, reduced motion.
- One open detail at a time; loading details on demand is acceptable, with a skeleton and non-destructive error retry.
- Filters and date range preserve browser history if existing My Account UI does so.
- No N+1 queries: batch WooCommerce/Dokan reads, paginate on the server, cap `per_page`, benchmark realistic seller order volume.
- Loading state must not show `$0.00` as a placeholder. Use skeleton/“Loading…”; distinguish verified zero, unavailable, and request failure.
- Avoid speculative “arrives Tuesday” estimates; only show provider-provided dates with the appropriate estimate disclaimer.
- Links to Stripe Express must use existing trusted Dokan/Stripe account-dashboard URL creation; do not hardcode an account-specific URL.
- Offer CSV export only if it can safely reuse an existing Dokan seller-scoped report/export; otherwise leave it out of v1.

## 9. Acceptance tests (must run on staging with synthetic records)

Create automated integration tests in both WooCommerce HPOS and legacy storage mode **where supported by the project**, plus browser/responsive/accessibility tests.

| Scenario | Expected result |
|---|---|
| Seller A and Seller B share parent order | Each sees only their own suborder/allocated earnings; no cross-seller leaks |
| Global vs vendor vs product commission override | Historical row equals **Dokan's stored** commission, not a newly calculated percentage |
| Commission rate changes after sale | Old transaction remains unchanged |
| Full refund, commission reversal | Original retained, refund adjustment shown, correct recorded final seller earnings |
| Partial refund of one item | Only affected seller allocation adjusted; unaffected lines remain |
| Canceled unpaid order | Canceled order shown appropriately, no invented refund |
| Canceled paid order with refund pending | Distinct cancellation and refund states |
| WC refund recorded, gateway unconfirmed | Never claim buyer received money |
| Dokan withdrawal approved, no Stripe bank payout evidence | “Withdrawal approved”/verified transfer label, **not** “Paid to bank” |
| Dokan Stripe Express transfer successful, payout pending | Clearly separates Stripe transfer from bank payout |
| Stripe payout failed/reversed | Status is not “Paid out”; show verified failure/reversal |
| Seller-borne processing fee not recorded | Omit fee or show “Not available”; never estimate |
| Multi-currency seller | Separate per-currency summaries, no invalid mixed total |
| Seller with no orders | Useful empty state, no fictitious balance |
| Unconnected Stripe Express seller | Existing connect CTA; no payout claims |
| API request for another seller's activity | 403/404 and no sensitive data |
| Guest/buyer visits tab/API | Denied, no data leakage |
| Large seller activity history | Server pagination and acceptable query count |
| Mobile, keyboard, screen reader, reduced motion | Full usable disclosure and status experience |
| Unavailable Dokan/Stripe data | Honest unavailable state, no fake zero or success |

### Financial parity test — launch blocker

For at least ten synthetic orders covering multiple commission rules, shipping/tax allocation, multi-vendor parent/suborders, partial/full refunds, reversals, and payout/withdrawal statuses, compare every visible **My Money** amount to the installed Dokan seller report, WC order/refund evidence, and available Stripe Express records. Any unexplained mismatch is a **launch blocker**. Document whether Dokan's reports themselves include/exclude each fee category and date.

## 10. Build sequence for Codex

**Phase 1 — Discovery and mapping (do not skip):** inspect existing code/configuration; identify exact authoritative fields, payout rail, seller ownership model, and My Account insertion point. Write `MY_MONEY_DISCOVERY.md` and unresolved questions.

**Phase 2 — Read-only adapter:** implement seller-scoped retrieval, normalized view models, status source annotations, event deduplication, reconciliation warnings, and test fixtures. **No payout/refund/commission writes.**

**Phase 3 — UI:** add **My Money** navigation, KPI cards, date range, Money moves list, expandable detail, strikethrough logic, explanatory copy, Stripe dashboard link, loading/empty/error states.

**Phase 4 — Verification:** parity tests against Dokan, WooCommerce refunds, and Stripe Express evidence; cross-seller permission tests; accessibility/responsiveness/performance.

**Phase 5 — Handoff:** provide changed-file list, exact source-to-UI mapping, screenshots of desktop/mobile/full-refund/partial-refund/payout states, test results, known gaps, and a rollback plan. Deploy only after explicit approval.

## 11. Explicit non-goals and forbidden shortcuts

- **Do not** implement custom commission formulas, new earnings ledger, custom vendor wallet, or alternative withdrawal/payout engine.
- **Do not** change Dokan settings, Stripe payout schedules, connected accounts, bank details, or payment gateway configuration.
- **Do not** recalculate historical commission from current vendor/product/global rules.
- **Do not** treat “WC order completed,” “Dokan withdrawal approved,” “Stripe transfer created,” and “Stripe bank payout paid” as interchangeable.
- **Do not** use direct SQL that bypasses WooCommerce HPOS CRUD for orders.
- **Do not** expose Stripe secret keys, account IDs, or financial records across sellers.
- **Do not** write “paid,” “refunded,” “available,” or an ETA without authoritative status evidence.
- **Do not** add refund or payout action buttons as part of this read-only v1.
- **Do not** edit vendor plugins/core; use supported integration points.
- **Do not** deploy or claim a live Stripe reconciliation test unless actually performed.

## 12. External references and how to use them

These are **product/technical references**, not a substitute for auditing the installed versions:

- Dokan seller statements: https://dokan.co/docs/wordpress/vendor-guide/creating-income-withdraw-statements/ — existing income/withdraw statement and export concepts.
- Dokan commission configuration: https://dokan.co/docs/wordpress/tutorials/how-to-setup-dokan-vendor-commission/ — multiple commission scopes and overrides; **use stored order results**.
- Dokan Stripe Express: https://dokan.co/docs/wordpress/modules/dokan-stripe-express-module/ — connected account onboarding, dashboard handoff, and configurable disbursement timing.
- Dokan withdrawal options: https://dokan.co/docs/wordpress/settings/withdraw-options/ — manual withdrawal rules are not automatically applicable to adaptive payment gateways.
- Dokan Stripe Connect (different module; **do not confuse with Express**): https://dokan.co/docs/wordpress/modules/how-to-install-and-configure-dokan-stripe-connect/ — useful example of refunds and transfer reversals, but verify actual installed Express behavior.
- WooCommerce payment/refund concepts: https://woocommerce.com/document/woopayments/managing-money/
- Stripe Connect documentation: https://docs.stripe.com/connect ; Stripe payouts: https://docs.stripe.com/payouts — distinguish transfers/balance transactions from connected-account bank payouts.
- UX inspirations: Etsy seller payment account and Mercari balance/activity for approachable summaries; Stripe/PayPal transaction detail for disclosure patterns. **Borrow interaction patterns, not their financial semantics.**

### KnifeRevive repository context

The existing `kniferevive-agent-commerce` repository documents that native WooCommerce/Dokan checkout owns marketplace seller accounting. Its Agent Commerce adapter is a separate merchant-operated sharpening/service integration and **must not be mistaken for the seller payout source**. Consult `README.md`, `AI.md`, and relevant `docs/` files. Inspect the actual ListLab/My Account plugin and production/staging setup before choosing code location.

## 13. Definition of done

A seller can open **My Money** in KnifeRevive My Account and understand their sales, **Dokan-recorded** commissions and earnings, refunds/cancellations, existing withdraw/transfer records, and the **verified** Stripe Express payout state (or a transparent link to Stripe when bank status is not accessible). Every figure is traceable to an existing authoritative record, expandable details explain changes without erasing history, no financial logic has been duplicated, no other seller's data is visible, and the implementation passes the parity/security/accessibility tests above.

**Instruction to Codex:** Start by auditing and reporting the current Dokan/WooCommerce/Stripe Express implementation. Implement only what the installed system can support truthfully. When unsure, preserve the existing financial behavior and mark the UI value unavailable rather than inventing accounting.
