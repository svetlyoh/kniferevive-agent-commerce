# KnifeRevive email modernization — Codex implementation instructions

Date: October 29, 2026. Status: proposed implementation, not a record of deployed changes.

## Assignment

Modernize KnifeRevive buyer and seller emails: specific subjects, short useful copy, consistent mobile design and accurate calls to action. Amend existing Dispatch, buyer returns and ShipKit notices; add missing lifecycle notices only where an authoritative event exists. Keep the KnifeRevive logo. Preserve ordering, shipping, refunds, seller permissions and payment behavior.

The owner wants a contemporary, Gen Z-friendly voice. Interpret this as direct, warm and easy to scan—not slang, jokes about money, fake urgency, “bestie,” “no cap,” “lit,” or vague hype. Trust comes from stating what happened, what it means and what to do next.

This document authorizes an implementation scope, not unrelated financial operations. Do not purchase labels, issue refunds, release payouts, alter funding caps, enroll sellers, or enable paused financial features to demonstrate an email. Do not broadcast old events to customers. Prepare and test the release; document any remaining deployment or external-service steps accurately.

## Read before editing

1. [Current email catalogue](KNIFEREVIVE_EMAIL_SUBJECTS_AND_STANDARD_COPY.md).
2. [Send-path coverage and limitations](14_SEND_PATH_COVERAGE.md), [branding](13_SHARED_BRANDING_AND_VARIABLES.md), and the numbered feature documents in this directory.
3. [Source verification](SOURCE_VERIFICATION.json) and [live plugin inventory snapshot](LIVE_PLUGIN_INVENTORY.json).

The audit used local sources whose version numbers matched the October 29 live inventory. That does not prove byte-for-byte production equivalence. Inspect deployed versions, actual templates, saved WooCommerce subjects, saved Order Messenger settings, translation overrides, snippets and transport configuration before changing defaults. MailPoet database campaigns and hosted provider emails were not fully exported. Preserve that distinction in your report.

Dokan Pro has been removed and must not become a dependency. Dokan Lite remains. Use the existing custom sender/outbox architecture; do not edit vendor plugins or add a second mail transport. The repository may not contain every current production plugin. Obtain the matching source before editing an older copy.

## Copy contract

- Subject pattern: `Order #{order} shipped — KnifeRevive`. Put the actual event first, brand last. Prefer roughly 45–65 characters where practical; do not truncate away the distinguishing event or order number.
- Provide a short, event-specific preheader. Never put an address, secret link token, tracking number, bank detail or private message in the subject/preheader.
- Body order: meaningful heading; one or two sentences explaining the event; compact order/item/seller facts; next action; seller contact where relevant; quiet footer.
- Use contractions naturally. Remove “Please be advised,” “We would like to inform you,” “Congratulations on your successful transaction,” repeated greetings and repetitive thanks.
- Keep all necessary costs, deadlines, policy limits and security instructions. Simple wording must not erase obligations or turn estimates into promises.
- Escape dynamic text and headers. Strip CR/LF from subject variables. Sanitize allowed seller HTML without changing its meaning. Do not automatically rewrite seller messages with AI.
- Use verified event facts, not guesses from product names. No inference that all spices are untracked or all tech is tracked. Evaluate each shipment and its items.
- Format money from integer minor units and the actual currency. Dates use the appropriate existing store/account timezone and an explicit zone where needed.
- Every catalogue sender must be mapped to a replacement, a deliberate unchanged security message, or an external/uneditable owner. Do not silently omit lesser-used emails.

## Buyer and seller order subjects

These are template defaults. Interpolate only safe, authoritative values. If a condition is unknown, use the pending/review variant rather than a success claim.

| Event / recipient | Proposed subject | Core body and primary action |
|---|---|---|
| Paid order received / buyer | Order #{order} is in — KnifeRevive | “Your payment is confirmed. Here’s what you ordered.” Show purchased items and actual delivery method. **View order**. |
| Unpaid or on-hold order / buyer | Order #{order}: payment is still pending — KnifeRevive | “We’ve saved your order. Payment hasn’t been confirmed yet.” Explain the real payment step; do not promise shipment. **View payment details**. |
| New paid order / seller | New order #{order}: ready to fulfill — KnifeRevive | “You’ve got an order. Review your items and delivery details.” Show only this seller’s allocation. **Open seller order**. |
| New unpaid order / seller | Order #{order}: wait for payment — KnifeRevive | “Payment is still pending. Check the order before shipping.” **View order**. Preserve special pay-at-pickup handling. |
| Pickup ready / buyer | Order #{order} is ready for pickup — KnifeRevive | Real pickup instructions and location; no carrier or tracking promise. **View pickup details**. |
| Buyer question / seller | Buyer question for order #{order} — KnifeRevive | Show the buyer’s message and scoped items. **Reply to buyer** using the authorized reply path. |
| Buyer cancellation request / seller | Cancellation requested for order #{order} — KnifeRevive | “Your buyer asked to cancel. Review the request before taking action.” Request is not cancellation. **Review request**. |
| Cancellation confirmed / buyer and seller | Order #{order} canceled — KnifeRevive | State affected items and separate refund status. Do not say money was returned merely because canceled. **View order**. |
| Seller manual message / buyer | {seller} replied about order #{order} — KnifeRevive | Use a structured topic for a more specific subject when selected: “Photos for order…”, “Delivery details for order…”, or “Item question for order…”. Keep the seller’s message. **View order / Reply to seller**. |
| Address changed / buyer | Ship-to address updated for order #{order} — KnifeRevive | “Your seller updated the delivery address.” Show labeled previous address struck through and clearly labeled current address, reason and support path if not requested. |
| Partial refund confirmed / buyer | {amount} refunded for order #{order} — KnifeRevive | State the actual processed amount and payment destination description; bank posting may take time. **View refund**. |
| Full refund confirmed / buyer | Order #{order} refunded — KnifeRevive | Same authoritative receipt rules. Preserve the canonical WooCommerce financial receipt; do not create another receipt from Returns. |

For native WooCommerce processing/completed statuses, verify the actual trigger. “Completed” does not independently prove carrier delivery or a paid online transaction. Use “Order #{order} completed” with truthful context if that is all the state establishes.

## Shipping: tracked, untracked, labels and corrections

Use the existing shipment record and canonical Seller Orders shipping-mail pipeline. Do not send both a ShipKit shipment email and a Seller Orders shipment email for one event.

| Authoritative state | Subject | Required content |
|---|---|---|
| Label created only | Label ready for order #{order} — KnifeRevive | Seller: “Your label is ready. Download it, then mark the items shipped after handing them over.” Buyer, only if already part of the approved notification flow: “Your seller has prepared a label. Carrier handoff isn’t confirmed yet.” |
| Seller marked shipped, tracking present | Order #{order} shipped: tracking inside — KnifeRevive | “{seller} marked these items shipped on {date}.” Carrier/service, tracking number and validated **Track shipment** link. Distinguish carrier acceptance from a seller report. |
| Seller marked shipped, no tracking | Order #{order} shipped without tracking — KnifeRevive | “{seller} marked these items shipped on {date} using {service}. This shipment has no tracking number.” Include the reason when useful, message and screenshots if present. **View shipping details**, never a Track button. |
| Tracking newly added | Tracking added for order #{order} — KnifeRevive | “Your seller added tracking for these items.” Do not infer another shipment. **Track shipment**. |
| Tracking replaced | Tracking updated for order #{order} — KnifeRevive | Old number labeled Previous and struck through; new number labeled Current. Current carrier link only. |
| No-tracking message/photo updated | Shipping details updated for order #{order} — KnifeRevive | Identify the new note/screenshots. Do not repeat a “just shipped” claim. |
| Other shipment correction | Shipment details changed for order #{order} — KnifeRevive | List only relevant before/after fields; preserve the unchanged shipping state. |
| Carrier-confirmed delivery | Order #{order}: carrier reports delivered — KnifeRevive | Attribute the delivery claim to carrier evidence and show the recorded date. **View delivery details** and missing-item help. |
| Unused label voided | Unused label canceled for order #{order} — KnifeRevive | A canceled label is not a canceled order or confirmed refund. Explain the next real shipping step. |

Untracked example body (fixture only; not permission to email an actual buyer):

> **Your order is on its way**
>
> ThriftCloset marked your Chubritsa shipped on {date}.
>
> **Service:** USPS First-Class Mail (letter)
>
> **Tracking:** Not included with this service.
>
> **Estimated arrival:** {from}–{through}, provided by your seller. Arrival may vary.
>
> **From your seller**
>
> “Hi {first_name}, I’ve attached the estimated shipping details. This mail service keeps postage low and doesn’t include tracking. Have a question? Message me below. — Svet, ThriftCloset”
>
> **Shipping screenshots** — show only actual attachments.
>
> **View shipping details**
>
> **Need a hand? Message ThriftCloset directly.**

Omit the ETA section when unavailable; say “Your seller hasn’t provided an arrival estimate” only if useful. Do not add a guessed date. Screenshots are seller-provided information, not verified carrier delivery evidence. “Shipped without tracking” must never become “Tracking will arrive soon.” For split shipments, list the items in this shipment; do not claim the entire order shipped.

## Seller contact, privacy and attachments

Use a shared contact block: **“Need a hand? Message {storefront_name} directly.”** Include **Visit storefront**, **WhatsApp**, **Telegram**, and the existing secure order-message/email reply action only when available.

Read the seller’s current public contact settings from the canonical storefront provider. Validate HTTPS Telegram/WhatsApp destinations, normalize supported account formats, escape output and omit missing channels. Do not use an admin’s contact details as a substitute or expose private account phone numbers. Text buttons are sufficient; no tracking widgets or remote scripts. Do not automatically send a Telegram/WhatsApp message. Avoid embedding buyer addresses, email or payment data in prefilled chat URLs.

For multi-seller orders, pair each contact block with its seller and items. After Dispatch, use the authorized current fulfillment contact and clearly show both storefront names where the transition requires it. Seller-facing “Reply to buyer” must use the existing consented contact path, not expose unrelated buyer/seller details.

Keep formatted seller notes and private PNG/JPEG attachments working on phones. Preserve MIME/size/ownership checks, private storage and authorized downloads. No permanent public media URL for private order screenshots or labels. Show attachment captions and text fallback if images are blocked. Do not attach shipping labels to buyer emails. Preserve the guest order access mechanism; never expose an order key through an external chat URL.

## Onboarding, account, reminders and sharpening

| Event | Subject / copy direction |
|---|---|
| Account created | “Your KnifeRevive account is ready” — access instructions matching password/passkey flow; never send a password. |
| Seller setup saved | “Your seller setup is saved — KnifeRevive” — show actual remaining steps. Do not say store/payouts are fully ready unless both are verified. |
| Stripe setup checked | “Payout setup checked: finish your shop — KnifeRevive” — use only if verified; otherwise “Finish your payout setup — KnifeRevive.” |
| Seller ready | “Your shop is ready: add your first listing — KnifeRevive” — first-listing action and optional **Set up a passkey** secondary action. |
| Password reset / secure access | “Reset your KnifeRevive password” / “Your secure KnifeRevive sign-in link” — keep expiry and account-security instructions; no promotional language. Preserve token semantics. |
| Reminder confirmation | “Confirm your saved search — KnifeRevive” — confirm action, no invented urgency. |
| Reminder active | “Your saved search is on — KnifeRevive” — concise criteria and manage link. |
| Search match / back in stock / price drop | “New finds for your saved search — KnifeRevive”; “{short_item} is back — KnifeRevive”; “{short_item} is now {price} — KnifeRevive.” Verify actual event, shorten product text safely, include unsubscribe/manage preferences. |
| Sharpening requested | “Sharpening request received — KnifeRevive” — requested date is not an appointment confirmation. |
| Sharpening confirmed | “Sharpening booked for {date} — KnifeRevive” — only confirmed booking; actual pickup/drop-off details. |
| Sharpening progress | “Order #{order}: sharpening started — KnifeRevive”; “Order #{order}: final blade checks — KnifeRevive”; “Order #{order}: your knives are ready — KnifeRevive.” Match recorded stage. |

Cover admin notices and operator invitations too. Keep roles, access restrictions, secure setup links and incident details clear. Do not rewrite Stripe-, PayPal-, Google- or WordPress.com-owned messages as if KnifeRevive controls their templates. Inventory those as external.

## Seller money and ShipKit lifecycle notices

Display plain-language fields, not raw JSON/snapshot keys. Every notice explains whether the amount is a quote, authorization hold, captured charge, carrier adjustment, balance owed, waiver, refund or transfer. Preserve immutable financial records, existing gates, currency and authoritative totals.

| Committed event / recipient | Subject | Body / CTA |
|---|---|---|
| Sale recorded / seller | Order #{order}: your earnings breakdown — KnifeRevive | Actual seller earnings and deductions, current eligibility. **View My Money**. |
| Shipping information needed | Order #{order}: add shipping details — KnifeRevive | Explain missing data without promising immediate payout. **Update shipment**. |
| Delivery recorded / seller | Order #{order}: delivered, payout checks next — KnifeRevive | Delivery evidence recorded; holds/refunds and eligibility still apply. |
| Transfer eligible | Order #{order}: eligible for payout review — KnifeRevive | Not money sent. Mention next scheduled run only from actual configuration. |
| Stripe transfer confirmed | {amount} sent to your Stripe balance — KnifeRevive | Transfer reference and order allocation; **View My Money**. This is not necessarily a bank deposit. |
| Payout held | Order #{order}: payout needs a review — KnifeRevive | Public-safe reason and next step, no internal risk notes. |
| Earnings changed | Order #{order}: your earnings changed — KnifeRevive | Original/current amounts and supported reason, such as refund. |
| Label quote ready | Order #{order}: your label quote is ready — KnifeRevive | Service, label price, separately labeled buffer if used, total authorized/captured according to actual flow, quote expiry. **Review label price**. |
| Seller label payment captured | Order #{order}: label payment received — KnifeRevive | Receipt amount and currency. State separately whether label is ready or still being created. **View label status**. |
| Label available | Order #{order}: download your shipping label — KnifeRevive | Seller-only authenticated download, service, price, next handoff step. No “shipped” transition caused by this mail. |
| Label purchase failed / unknown | Order #{order}: label purchase needs a check — KnifeRevive | Known payment state; if uncertain, “We’re checking the payment and label. Don’t buy another yet.” **Check label status**. No blind retry prompt. |
| Carrier cost adjustment assessed | Order #{order}: carrier postage adjustment — KnifeRevive | Original charge, carrier adjustment, evidence/reason, resulting due/credit, actual dispute path/deadline. **Review adjustment**. |
| IOU recorded | Shipping balance due: {amount} — KnifeRevive | “You have {amount} in shipping costs to review.” Itemize related labels/orders and existing collection method. **Review shipping balance**. Do not imply automatic debit if none authorized. |
| IOU payment confirmed | Shipping payment received: {amount} — KnifeRevive | Amount applied, remaining balance and receipt. Payment pending/unknown gets a pending subject instead. |
| Dispute opened | Your shipping charge review is open — KnifeRevive | State what is paused, only if actually paused. **View review**. Suppress conflicting collection reminders. |
| Lifeline requested | Your Label Lifeline request is in — KnifeRevive | What is under review, no award/payout promise. **View Lifeline request**. |
| Lifeline approved, unpaid | Label Lifeline approved: {amount} — KnifeRevive | Approved support and actual next step; not “paid.” |
| Lifeline waiver posted | Label Lifeline cleared {amount} of shipping costs — KnifeRevive | “We waived {amount} from your shipping balance.” Remaining balance; explicitly not a cash payment. |
| Lifeline reimbursement confirmed | Label Lifeline: {amount} sent to Stripe — KnifeRevive | Actual confirmed transfer and My Money link. Not a bank deposit confirmation. |
| Lifeline declined / more evidence | Your Label Lifeline review: next steps — KnifeRevive | Specific public-safe reason or missing evidence; support path, no blame. |
| Postage refund requested | Order #{order}: postage refund requested — KnifeRevive | Request sent, not approved or paid. |
| Postage refund confirmed | Order #{order}: {amount} postage refund confirmed — KnifeRevive | Actual refund destination and state, no made-up posting date. |
| Buffer released / refunded | Order #{order}: postage buffer released — KnifeRevive | Distinguish uncaptured authorization released from captured payment refunded. Use “refunded” only for refund event. |
| Review reminder | Shipping charge review due {date} — KnifeRevive | Actual deadline and outstanding reason, no fabricated urgency. |
| Operational exception / admin | Ship Desk: {count} label requests need review | Scoped operator action, no buyer financial/private details in subject. |

Map these to existing ShipKit Notices.php and financial event adapters first. The audited version skips some fresh Shipping IOU assessment notices; add the missing notification hook at the authoritative committed transition, not by scraping My Money balances. Where a state adapter is absent, implement and test the read/event adapter or report a blocker—never invent a paid result. Existing settlement notification gates and blocked unsupported events remain protected.

Buyers receive shipment/service updates, not the seller’s label purchase receipt, IOU balance or Lifeline subsidy. Never turn these costs into buyer charges. Do not alter the 11 a.m./6 p.m. Pacific payout schedule or existing caps as part of this work.

## Dispatch email matrix

Amend the existing Dispatch pipeline. Do not create a parallel sender. Respect live-offer and notification gates, sandbox exclusions, seller agreements and buyer approval. Match offers to actual linked listings, condition, accessories and photos; never claim the two items are the exact same physical unit.

| State / recipient | Subject | Body / CTA |
|---|---|---|
| Enrollment submitted / seller | Your Dispatch signup is in review — KnifeRevive | Actual remaining steps. **View Dispatch**. |
| Proposed transfer / original seller A | Order #{order}: review a Dispatch handoff — KnifeRevive | Partner storefront, scoped items, projected earnings/incentive, obligations. **Review handoff**. |
| Assignment offer / receiving seller B | Order #{order}: can you ship this Dispatch item? | Buyer-safe destination info, matching inventory, actual offered compensation/incentive. **Review offer**. |
| Sellers accepted / sellers | Order #{order}: waiting for buyer approval — KnifeRevive | Do not ship or treat assignment as committed. **View handoff**. |
| Buyer consent requested / buyer | Order #{order}: review a nearby seller option — KnifeRevive | “We found a matching item from {seller_B}. Review the item and shipping details before choosing.” Original/new storefront links; faster arrival is a goal, not guarantee. **Review seller change**. |
| Buyer approved, commitment pending | Order #{order}: your seller choice is saved — KnifeRevive | Only if a distinct pending state exists; no premature assignment claim. |
| Handoff committed / buyer | Order #{order}: {seller_B} is your shipping seller | Original seller and current seller links, old origin struck through and new origin clearly current, city/state or approved coarse location only. **View order**. **Contact support** if concerned. |
| Handoff committed / seller A | Order #{order}: handed off to {seller_B} — KnifeRevive | No longer active fulfillment for A; show projected allocation/incentive and partner storefront. **View Dispatch breakdown**. |
| Handoff committed / seller B | Order #{order}: you’re up to ship — KnifeRevive | Assigned items, allowed ship-to details and obligations. **Open seller order**. |
| Declined / canceled proposal | Order #{order}: your original seller will ship — KnifeRevive | Only if original fulfillment actually continues; otherwise use hold/review wording. |
| Transfer hold / affected parties | Order #{order}: seller change is on hold — KnifeRevive | Relevant reason and next action, no confidential internal risk details. |
| Reversal committed / affected parties | Order #{order}: back with {seller_A} — KnifeRevive | Current responsibility, both storefronts, actual location update and projected incentive reversal. Do not say money was recovered unless confirmed. |
| Dispatch shipped / buyer | Use canonical shipping subject above | Include Dispatch seller context through the shipment pipeline; no duplicate shipment email. |
| Delivery recorded / sellers | Order #{order}: Dispatch settlement is in review | Breakdown, not a payout promise. |
| Allocation/settlement update / seller | Order #{order}: your Dispatch earnings changed | Explain projected, approved or paid state and exact amounts. **View My Money**. |

Seller A sees A’s full origin but only B’s permitted state/ZIP; seller B sees B’s full origin but only A’s permitted state/ZIP. Buyers must not receive private origin street addresses. Keep both storefront names visible in transfer views/emails. Never promise a default $2 incentive if the actual offer differs. Dispatch manual settlement remains separate from automatic marketplace payouts. The email implementation must not enable or execute financial transfers.

## Buyer returns email matrix

| Return event | Subject / essential next step |
|---|---|
| Request submitted / buyer | “Return requested for order #{order} — KnifeRevive” — request recorded, not approved; **View return**. |
| New request / seller | “Order #{order}: review a return request — KnifeRevive” — affected items, disclosed reason, actual response deadline if set; **Review request**. |
| More information needed | “Order #{order}: add details to your return — KnifeRevive” — exact requested information and secure upload; **Add return details**. |
| Approved | “Return approved for order #{order} — KnifeRevive” — authorized items, real return method/cost/deadline and instructions; **View return steps**. |
| Declined | “Order #{order}: your return decision — KnifeRevive” — clear decision, supported policy reason, support/escalation path. |
| Return label ready | “Order #{order}: your return label is ready — KnifeRevive” — authenticated label download and handoff instructions; not a refund receipt. |
| Buyer marked return shipped | “Order #{order}: return shipment recorded — KnifeRevive” — source of report; tracked/untracked rules apply. |
| Carrier delivery / receipt | “Order #{order}: return received for review — KnifeRevive” only if actual receipt state supports it; distinguish carrier delivery from seller inspection. |
| Refund pending / uncertain | “Order #{order}: refund is being checked — KnifeRevive” — no duplicate retry payment or promise of completion. |
| Refund confirmed | Use canonical WooCommerce refund receipt above; suppress a second Returns money-sent message. |
| Return closed / canceled | “Return closed for order #{order} — KnifeRevive” — public-safe outcome and any remaining next step. |
| Guest access link | “Open your return for order #{order} — KnifeRevive” — existing one-use expiry and security language, no token in subject. |

Preserve return status permissions, evidence privacy and admin-only notes. The audited Returns rollout had paused financial/label features; recheck the current state and do not enable them as a side effect of rendering changes.

## Email component and CSS specification

Implement a shared, versioned email rendering layer used through adapters in existing custom plugins and WooCommerce-supported hooks/templates. Avoid global string replacements or broad wp_mail filters that alter third-party security mail. Each event owns its subject/preheader/status/CTA; the shared layer owns layout and safe rendering. Provide a fallback if the shared renderer is unavailable.

- Existing KnifeRevive logo at top with useful alt text, explicit dimensions, HTTPS first-party image, and text brand fallback. A logo inside the email is distinct from BIMI inbox branding.
- Approximately 600px maximum desktop container, fluid width on mobile; 20–24px desktop padding and 16px narrow-screen padding. Use email-compatible presentation tables with role=presentation; no dependency on CSS grid, JavaScript or custom web fonts.
- Readable system fonts, 16px minimum body text, 1.5 line height, strong contrast and no information conveyed only by color. Buttons at least 44px high, wrapping/stacking cleanly at 320px and 200% text enlargement.
- Wide item title area. Render item image/title/details above quantities and totals on narrow layouts. Long titles, URLs, tracking IDs and seller names must wrap without making totals unreadable.
- Warm neutral brand background with a contrasting content surface. Use restrained green for confirmed progress, blue for neutral information and amber for review needed. Match the subject’s factual state; do not style pending money as confirmed success. Avoid large pink error panels and alarmist red except genuinely required errors.
- For tech item details, honor the dark surface/light text styling using explicit contrasting colors on both element and cell. Other categories use neutral/beige details. Mixed-category order items must remain legible without forcing the entire email dark. Test forced dark mode; never rely on white-on-transparent text.
- Inline essential styles; media queries enhance rather than rescue baseline readability. Use real headings, descriptive links, visible focus where supported and alt text. No image-only email, embedded forms, video, tracking widgets or essential content hidden in CSS.
- One primary action and a small, clearly labeled contact section; avoid huge repeated CTA blocks. Plain-text equivalent contains the same material facts and usable URLs.
- Keep generated HTML comfortably below Gmail clipping territory (internal target below 90KB before transport wrapping); optimize image weight separately. Do not hide marketing copy or keyword stuffing in preheaders.
- Footer identifies KnifeRevive, reason for this transactional email and appropriate help link. Marketing/subscription emails additionally include proper preference/unsubscribe facilities. Do not append promotional ads to financial/security notices.

## Reliable sends and migration

1. Inventory event owners and keys before adding hooks. One authoritative sender per event/recipient; preserve existing outbox, sent markers, locks and attachment lifetime.
2. Define an explicit event-to-recipient matrix. Recipient identity must be revalidated against the order, seller allocation and guest access policy. No cross-seller order leakage.
3. Keep immutable financial/event snapshots. Revalidate stale queued notices (e.g. a quote already expired, disputed IOU, canceled Dispatch offer). Supersede stale notices rather than send contradictory instructions.
4. Add semantic event IDs for genuinely new notices with uniqueness protection. A template version change must not change a historical dedupe key and replay old mail. Persist rollout cutoffs for new event families; never replay historical records by default.
5. Mail transport acceptance is not proof of delivery. Preserve delivery_unknown handling; do not blindly retry after ambiguous transport outcomes. Record bounded, redacted diagnostics.
6. Provide a migration for saved default subjects so old defaults can change intentionally. Detect owner customizations; preserve and report them or offer a previewed migration. Do not overwrite all option values on each request. Keep translations and HTML/plain pairs in sync.
7. Render event-specific snapshots/fixtures for review before enabling new sender hooks. No AI calls in checkout/payment hooks; deterministic templates are sufficient.
8. Preserve current enabled/disabled flags, financial limits, providers, order status rules and sender configuration. Record their before/after values in the implementation report.

## Deliverability and Gmail verification workstream

No subject, CSS or plugin can guarantee Inbox delivery, override a recipient’s filters/Trash rules, or guarantee a Gmail badge. Do not claim otherwise. Use authentic transactional copy, accurate sender identity and normal consent practices; no spam-filter evasion.

Audit the actual sending service, From domain, Reply-To, sanitized test-message authentication headers and all legitimate domain senders. Establish SPF/DKIM/DMARC alignment, transport security, provider bounce/complaint handling, and unsubscribe support where applicable. Check Google’s distinct bulk-sender requirements rather than assuming KnifeRevive sends 5,000 Gmail messages daily. Monitor reputation with Postmaster Tools where data is available. Google recommends SPF, DKIM and DMARC and requires reported spam rates below 0.3%; use a substantially lower operational target. Cite and recheck [Google sender guidelines](https://support.google.com/mail/answer/81126?hl=en).

Do not tighten DNS policy before inventorying every legitimate sender or purchase a certificate without owner approval. Do not expose DNS-provider credentials or signing keys. Report PASS/FAIL/NOT VERIFIED with evidence, not “spam-proof.”

Optional BIMI assessment: Gmail’s checkmark requires a Verified Mark Certificate (VMC); a Common Mark Certificate can support logo display without that checkmark. BIMI also requires qualifying DMARC enforcement and compliant logo/certificate hosting. This is domain-level verification, not an icon added to selected email templates. Assess logo eligibility, costs and existing domain configuration and give the owner a separate setup plan. Do not draw a fake verification tick in sender names. Source: [Google BIMI setup](https://knowledge.workspace.google.com/admin/security/set-up-bimi).

## Required verification and deliverables

Test with synthetic orders/fixtures and designated test inboxes, not real buyers. Any real customer messaging or financial test needs its own authorized scenario; this spec does not authorize purchases or payouts. Do not change live gateway mode for tests.

Required scenarios:

- Tracked shipment; untracked letter with formatted note and screenshot; no attachment; tracking added/changed/removed; unknown service; absent ETA; label-only state; split shipment and long item titles.
- Seller with both contact channels, one, neither, invalid values and a different current Dispatch seller. Logged-in and guest-safe links, multi-seller recipient isolation.
- Label captured but provider result unknown; label ready; adjustment disputed; IOU paid/part-paid; waiver versus cash Lifeline; refund requested versus confirmed; buffer hold release versus refund. No changes to payment amounts or execution.
- Dispatch pending/accepted/approved/committed/held/reversed, no faster-shipping guarantee, scoped origin privacy, incentive projection versus payment and no duplicate shipping email.
- Return pending/approved/declined/shipped/received/refund pending/refund confirmed, one canonical receipt, secure evidence and expiring guest links.
- Password/passkey/account flows, reminders opt-in/unsubscribe, sharpening requested versus booked; all catalogue entries accounted for.
- Duplicate hooks, queue retry, ambiguous send, stale event, settings migration, renderer unavailable, plain-text equivalence, escaping/header injection and unauthorized attachment access.
- Gmail web/mobile, Apple Mail/iPhone and Outlook render checks, narrow viewport, enlarged text, blocked images and dark mode. State clearly which clients were actually tested versus simulated.

Deliver:

1. Event inventory with old subject → new subject, body summary, recipient, trigger, sender owner and enabled state.
2. Versioned source changes, installable artifacts and migration/release notes; no vendor edits or Dokan Pro dependency.
3. HTML/plain-text fixture gallery and screenshots for the main edge cases, with test results and sanitized transport header evidence.
4. Deliverability/BIMI findings with explicit unresolved external requirements.
5. An implementation report separating implemented, tested, deployed, enabled and observed delivery. Link each changed sender to its source and describe any intentionally retained externally owned email.

Acceptance: a recipient can tell why the email arrived, what actually changed and what action is available within a few seconds. No misleading tracking, paid/refunded/delivered claims, duplicate receipts, leaked private data, changed financial policy or hidden activation of paused features.
