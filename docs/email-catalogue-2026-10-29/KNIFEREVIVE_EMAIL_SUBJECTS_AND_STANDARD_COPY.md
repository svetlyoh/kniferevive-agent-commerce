# KnifeRevive buyer and seller email catalogue

Prepared October 29, 2026, America/Los_Angeles.

This catalogue records **email subjects, standard reusable wording, variable fields, recipients and triggering conditions** found in KnifeRevive's available source. It is documentation only: no email was sent, no live setting was changed, and no order/payment was modified.

## Evidence and scope

- Live WordPress.com plugin inventory was read today; see LIVE_PLUGIN_INVENTORY.json.
- Local source matching live versions was selected where available; SOURCE_VERIFICATION.json records source/version matches and hashes. Matching version numbers do not prove identical deployed bytes.
- “Active plugin” does not mean all of its messages are enabled. Feature flags, authorization, saved settings, order state and provider responses can block sending.
- WooCommerce and Order Messenger copy below is source/default copy where configurable. Saved production subjects/footers, Code Snippets, translations, MailPoet database campaigns and historical emails were not fully exported. Those are explicit coverage gaps, not omitted messages assumed absent.
- All names, amounts, dates, item descriptions, addresses, tracking numbers, seller messages and secure URLs shown in braces are placeholders. No real purchaser data or access token is included.
- The source reference sections preserve exact spelling, punctuation, conditional variants and technical gates. “Literal wording reference” lists fragments from the renderer; they are not all concatenated into every message, and may include internal/control text. Curated sections describe delivered content.
- Dokan Pro is removed and is not part of this catalogue's active mail architecture. Dokan Lite remains.

## Files

- [Seller orders, buyer questions and cancellation](01_SELLER_ORDERS_AND_BUYER_CONTACT.md)
- [Shipping, tracking, screenshots and address changes](02_SHIPPING_TRACKING_AND_ADDRESS.md)
- [Seller-to-buyer Quick Note / Order Messenger](03_SELLER_MESSAGES_AND_ATTACHMENTS.md)
- [Seller onboarding and passkey encouragement](04_SELLER_ONBOARDING.md)
- [Seller payment, earnings and My Money emails](05_PAYOUTS_AND_MY_MONEY.md)
- [ShipKit, label options, postage and Label Lifeline](06_SHIPKIT_AND_LABEL_LIFELINE.md)
- [Dispatch buyer and seller emails](07_DISPATCH.md)
- [Buyer returns and seller return updates](08_BUYER_RETURNS.md)
- [Buyer restock, price-drop and saved-search reminders](09_REMINDERS_AND_SAVED_SEARCHES.md)
- [Knife sharpening booking and service-stage emails](10_SHARPENING.md)
- [Native WooCommerce emails](11_NATIVE_WOOCOMMERCE.md)
- [Account/security and other senders](12_ACCOUNT_SECURITY_AND_OTHER_SENDERS.md)
- [Shared wording and branding](13_SHARED_BRANDING_AND_VARIABLES.md)
- [Custom-plugin send-path coverage](14_SEND_PATH_COVERAGE.md)

## Important review findings

1. Subject prefixes are inconsistent: `[KnifeRevive]`, `KnifeRevive:`, suffix `· KnifeRevive`, and some unprefixed seller payout/onboarding subjects all exist. This report preserves them rather than silently rewriting them.
2. Shipping-label readiness, actual shipment, tracking correction and untracked shipment have different wording. Label creation alone does not mean carrier handover.
3. A generic return update and a native financial-refund email are different purposes. Return financial features remain incomplete/paused according to the last deployment; this inventory does not enable them.
4. Several payout templates exist but have explicit send gates; `payout`, `owed` and `recovery_review` are blocked in the current source.
5. Free-form seller/buyer messages and MailPoet authored campaigns cannot be exhaustively expressed as fixed generic wording. Current DB-stored copy and actual delivery require separate read-only configuration/history evidence.

## Reading placeholders

`{order_id}` is the internal database ID; `{order_number}` is the customer-facing number and may differ. `{seller_name}` / `{store_name}` come from the store/account. Monetary placeholders preserve their original currency formatting; no current fee percentage is inferred from an email template. `{subject_heading}` is one selected conditional heading. Actual secure links, email addresses, item identifiers and stored signatures are not exported.

## Full text catalogue

The following curated sections repeat the per-family files for convenient searching. Their exact source appendices remain in the separate files.


---

# Seller orders, buyer questions and cancellation

Audit date: October 29, 2026 (America/Los_Angeles).


These senders belong to active Seller Orders 1.7.0-rc1 and Order Address & Contact 1.0.0. Plugin activity is verified; actual delivery, recipient settings and hooks were not exercised.

## New seller order

Recipient: each seller whose items are in the order. Trigger: processing or on-hold transition; per-seller sent marker prevents a repeat.

**Subject:** `[KnifeRevive] New seller order #{order_number}`

**Standard body:**

> You received a KnifeRevive order #{order_number}.
>
> {item_name} x {quantity}
>
> Manage your items: {seller_order_url}
>
> Buyers may request cancellation before you mark their items shipped. Approve a request using Buyer Request, or cancel unavailable items using Out of stock. Cancellation triggers a refund; the Support Crew handles refunds requiring manual processing.
>
> Questions about your right to cancel? KnifeRevive Support Crew (site admin): {support_email}

## Buyer message / cancellation request

Recipient: chosen order seller. Reply-To: signed-in buyer's verified account email.

**Subjects:**

- `[KnifeRevive] Buyer message — Order #{order_number}`
- `[KnifeRevive] Buyer cancellation request — Order #{order_number}`

**Body:** Buyer: {buyer_display_name} <{buyer_email}>; Order #{order_number}; {buyer_message}; “Reply to this email to respond directly to the buyer.” Cancellation requests also say: “The order is not cancelled yet. To approve, open Seller Orders and choose Cancel Order with reason Buyer Request.” Followed by {seller_order_url} and KnifeRevive Support Crew (site admin): {support_email}.

## Buyer question through the address/contact module

**Subject:** `[KnifeRevive] Buyer question — Order #{order_number}`

**Body:** “A buyer has a quick question about order #{order_number}.” {buyer_message}. “Reply to this email to respond directly to the buyer.” **Open your seller order** button.

These are two separate code paths; documenting both does not mean one click necessarily sends both.

## Seller cancellation

Recipients: buyer, seller and support (deduplicated).

**Subject:** `[KnifeRevive] Seller cancellation — Order #{order_number}`

**Body labels:** Order #; Reason; Cancellation / refund status; Refund amount ({amount} {currency}).

**Conditional text:** “The payment has not been confirmed refunded. The Support Crew will arrange the refund.” / “Only this seller's items and item taxes are included. Shared shipping and fees require Support Crew review. Other sellers' items remain active.” Ends with KnifeRevive Support Crew (site admin): {support_email}.


---

# Shipping, tracking, screenshots and address changes

Audit date: October 29, 2026 (America/Los_Angeles).


Seller Orders 1.7.0-rc1 / Order Address & Contact 1.0.0. Buyer receives the shipping message. Seller email is Reply-To when valid. Shipment email feature flag must allow sending.

## Shipment subject variants

Each has the exact pattern **`KnifeRevive: {heading} - Order #{order_number}`**.

| Event / condition | Heading inserted into subject |
| --- | --- |
| Shipped, tracked | Your items have shipped |
| Shipped, untracked | Your items have shipped - tracking not provided |
| Tracking added | Tracking is now available |
| Corrected shipment | Your shipment details were updated |
| Previous tracking changed, new tracking present | Your tracking number has been updated |
| Previous tracking changed, now untracked | Tracking details have been updated |
| Label prepared only | Your shipping label is ready |
| Unused label voided | An unused shipping label was canceled |
| Fallback | Shipping update |

## Shared standard body

“Your seller has sent you a shipping update about your KnifeRevive order.”

Sections/labels: **Order details**, **Order**, **Placed**, **Seller**, **Carrier**, **Service**, **Tracking number** (or **New tracking number**), **Items in this shipment**, **Shipped**, **Estimated arrival**, **Message from your seller**, **Seller note**, **Screenshots attached**, **Need a hand?**

Values are populated from the order and committed shipment: order number/date/timezone, store name, carrier/service, tracking, purchased item names and quantities, actual shipped date, optional arrival range, seller-formatted explanation, optional seller note, attachment descriptions.

Conditional standard sentences:

- “Use the new tracking number below. The previous number is no longer the current tracking for this shipment.”
- “The previous tracking number is no longer current. Ask your seller for the latest details.”
- “Previous tracking - no longer current” (old number is struck through).
- “Carrier tracking link unavailable. Ask your seller for details.”
- “Label canceled. The unused label is no longer current. Check your order for any other shipment or new label.”
- “Label ready. Your seller has a shipping label. Carrier handover and delivery have not been confirmed.”
- “Actual mailing date not provided.”
- “(seller estimate, not guaranteed).” / “No reliable arrival estimate has been provided.”
- “The images below are attached to this email as separate files. These are seller-supplied screenshots, not carrier verification.”
- “Ask {store_name} directly about this shipment.”
- “You can also reply to this email.”
- “Checked out as a guest? Use your original order confirmation or reply to your seller for help accessing your order.”

Buttons/links: **Track on {carrier}**, **Visit {seller_name} storefront**, **Need a hand? Contact your seller**, **View your order and seller contact options** (sign in required), **Contact KnifeRevive support**. Each Screenshot {index} ({extension}) is a separate attachment. Carrier/tracking explanations vary by service; see the exact source reference below.

## Shipping address update

**Subject:** `[KnifeRevive] Shipping address updated — Order #{order_number}`

**Body:** “Your seller, {seller_name}, updated the shipping address for their items in order #{order_number}.”

Labels: **Ship to — current address**, **Previous address — no longer used for these items** (old address struck through), **Reason**, **Applies to these items** ({item_name} × {quantity}).

“If you did not request this change, reply to this email or contact KnifeRevive support before dispatch.”

“This updates the delivery address only. Billing details, item prices, taxes, shipping charges and payment remain unchanged. A shipping-address update is not confirmation of dispatch.”

Links: **View your order**, **Contact support**. Address values and explanation are variable, not generic copy.


---

# Seller-to-buyer Quick Note / Order Messenger

Audit date: October 29, 2026 (America/Los_Angeles).


Active Order Messenger 1.0.4. Manual transactional messages from the seller; the message itself is free text and cannot be exhaustively listed as generic wording. The following are **source defaults**, overridable by saved owner settings.

**Default subject:** `[KnifeRevive] Order #{order_id} — Message from your seller`

Supported subject substitutions: `{order_id}`, `{order_number}`, `{store_name}`. The default uses internal order ID, which can differ from a displayed/custom order number.

**Heading:** A message about your order

**Intro:** Your seller has sent you an update about your KnifeRevive order.

**Standard sections/labels:** Order details; Order; Placed; Seller; {item_name} — Qty {quantity}; Message from your seller; {seller_written_message}.

**Attachment section:** Screenshots attached. “The images below are attached to this email as separate files.” Screenshot {index} ({extension}).

**Buttons/footer:** View Your Order; “Checked out as a guest? Use your original order confirmation or contact your seller by replying to this email for help accessing your order.”; {optional_owner_footer_note}; Visit {site_name}. The default footer note is empty. Summary-first/message-first is configurable.

The recipient is the registered purchaser's account email, or guest billing email. Seller/support Reply-To is configurable. Sharpening scope uses “KnifeRevive Sharpening” as store name.

Administrative-only diagnostic subject: `[KnifeRevive] Order Messenger diagnostic test`.

Administrative support-mailbox verification subject: `Verify KnifeRevive Order Messenger support Reply-To`. Body: “Confirm access to this support mailbox by opening the following link while signed in as a KnifeRevive administrator:” {verification_link}. These two are not routine buyer/seller order mail.


---

# Seller onboarding and passkey encouragement

Audit date: October 29, 2026 (America/Los_Angeles).


Active Seller Onboarding 0.3.1. Transactional notices require onboarding email/flow flags, participating seller state and the recorded completion conditions. These are not a promise that every sign-up has received both messages.

## Stripe payout setup verified

**Subject:** Payout setup checked. Keep going.

**Heading:** One step closer.

**Body:** Stripe has verified your payout setup. Head back to KnifeRevive to finish your shop setup. Stripe can request more information later.

**Button:** Finish my setup

## Seller setup completed

**Subject:** Your KnifeRevive seller setup is saved

**Heading:** You’re set. What’s your first find?

**Body:** Your shop details, dispatch location and verified Stripe payout setup are saved. Add a passkey to secure your sign-in, then create your first listing. Listings still follow the marketplace’s review and publishing rules.

**Button:** Open my seller setup

## Shared footer

KNIFEREVIVE · SELLER SETUP

**Add a passkey** · **Need a hand?**

“We’ll never ask for your bank details or device PIN by email. Open KnifeRevive directly if you’re unsure about a link.”

Initial WooCommerce account-creation/password setup mail is a separate sender; see 11_NATIVE_WOOCOMMERCE.md and 12_ACCOUNT_SECURITY_AND_OTHER_SENDERS.md. No additional standalone “passkey added successfully” email was established by the source search.


---

# Seller payment, earnings and My Money emails

Audit date: October 29, 2026 (America/Los_Angeles).


Active Stripe Connect 0.7.2-rc1 owns these templates; My Money 1.2.1-rc1 is the linked destination, not a separate mail executor. **Availability is gated** by `KR_CONNECT_SETTLEMENT_NOTIFICATIONS_ENABLED === true` and an administrator capability in dispatch(). Live constant and sender execution were not verified in this read-only audit. A template existing does not establish that it is automatically sent.

The subjects below have **no automatic `[KnifeRevive]` prefix** in this sender. Exact standard body follows each subject:


## paid

**Subject:** Your sale is in — here’s what happens next

> Payment is recorded. Your seller transfer is held for review. Open the order for shipping and earnings details.

## shipping

**Subject:** Add shipping details to keep your payout moving

> Add tracking when available. Without tracking, the review clock starts when you mark the order Shipped. Eligibility starts seven days later, subject to other checks and the approved batch.

## delivered

**Subject:** Delivered — your earnings are in review

> Independent delivery evidence was recorded. Any eligibility date is an estimate and remains subject to refund and dispute checks.

## eligible

**Subject:** Your earnings are ready for the next transfer review

> Your earnings passed the recorded checks for review. A transfer still requires owner approval and a final recheck.

## sent

**Subject:** We’ve sent your earnings to Stripe

> The verified transfer was sent to your Stripe Express account. This is not confirmation that your bank received it.

## held

**Subject:** An update on your payout

> Your seller transfer needs review. Open My Money for the available explanation, or contact KnifeRevive for help.

## refund

**Subject:** Your earnings changed for an order

> A refund or cancellation needs reconciliation. Open the existing order breakdown for recorded amounts and status.

## payout

**Subject:** Stripe payout update

> A processor payout update was recorded. Only the specifically verified payout status should be shown; a transfer is not bank receipt.

## owed

**Subject:** Your seller balance needs attention

> An existing reconciled seller balance needs review. No card or bank debit is authorized by this notice. Contact support to review the source records.

## recovery_review

**Subject:** Action needed: proposed recovery review

> Review the itemized amount, proposed method, legal basis and meaningful advance notice with support. This notification does not authorize a debit.

Each permitted message adds **Order #{suborder_id}**, **View My Money** and **Need a hand?**. The sent notice also adds **Transfer: {amount} {currency}**. `sent` requires a recorded verified transfer. The `payout`, `owed` and `recovery_review` templates are explicitly blocked in this release pending authoritative adapters. Some existing wording mentions owner approval; that is recorded copy, not a statement that the current automatic payout schedule is off. No payout controls were changed by this documentation task.


---

# ShipKit, label options, postage and Label Lifeline

Audit date: October 29, 2026 (America/Los_Angeles).


Active ShipKit 0.6.3-rc1; seller transactional notices (operator_alert is for the configured operator/admin). Actual notice queues and settings were not modified or exercised.

| Kind | Exact subject |
| --- | --- |
| lifeline | Your Label Lifeline update · KnifeRevive |
| quote_ready | Your label options are ready · KnifeRevive |
| adjustment | Your shipping cost review · KnifeRevive |
| refund_confirmed | Your postage refund was confirmed · KnifeRevive |
| buffer_review | Your postage buffer is ready for review · KnifeRevive |
| adjustment_reminder | Your shipping review deadline · KnifeRevive |
| operator_alert | Ship Desk needs an operator · KnifeRevive |

Heading is the subject text before “ · KnifeRevive”.

**Shared standard body:** “This ShipKit update reflects your records as of {date_time_timezone}.” Then a snapshot table: field names replace underscores with spaces; monetary values use USD with two decimals where specified by the renderer.

“Review the exact amount and current status before taking action. A seller payment does not mean a label has been purchased. Refunds return to the original payment source.”

**Button:** Open ShipKit / request review

Snapshot rows vary with notice kind. Exact fields and conditions appear below. Shipping-to-buyer notices after label/shipment changes use the separate shipping mailer in 02_SHIPPING_TRACKING_AND_ADDRESS.md.


---

# Dispatch buyer and seller emails

Audit date: October 29, 2026 (America/Los_Angeles).


Active Dispatch 0.2.0 source. These are **conditional live-workflow templates**, not proof live handoffs/customer notices are enabled. Sending requires both `notifications_enabled` and `dispatch_live_offers_enabled`, a live route and an authorized participant. Sandbox/demo notices are not sent.

Every subject is **`[KnifeRevive] {subject below}`**.

| Event | Subject suffix |
| --- | --- |
| enrolled | Your Dispatch enrollment is in review |
| invite | Dispatch request: can you ship this? |
| partner_accepted | Your partner is in. Waiting on buyer approval. |
| buyer_review | Your order. A closer handoff to review. |
| buyer_approved | The buyer approved this item and partner |
| committed | Handoff confirmed. Meet your shipping seller. |
| fallback | Your original seller is keeping the order. |
| shipped | Your Dispatch shipment update |
| delivered | Delivery recorded. Settlement is in review. |
| exception | Your handoff is on hold for review. |
| reversed | Handoff reversed. Back with your original seller. |
| settlement | Your Dispatch breakdown has an update. |


**Shared body:** {subject heading}; Order #{order_id} · {state}.

“A nearby seller may reduce shipping distance. Both units were reviewed for matching model, variant, condition and included accessories. They are separate physical items: review their actual photos and details. Faster delivery is an aim, not a confirmed arrival promise.”

**Original seller:** {linked_store_name}. **Fulfilling seller:** {linked_store_name}.

**Previous item location:** {old_visible_location}, struck through after committed change. **New ship-from** or **Proposed ship-from:** {new_visible_location}. Location visibility depends on recipient role.

Seller-only: **Agreed amount: {currency} {amount}.** “Projection only until verified settlement.” Reversal variant: “The projected incentive and partner allocation were reversed.”

**Review your handoff**; **Need a hand? Contact support**. “If you object, ask for review from your handoff page. Dispatch can stop an unshipped route; an already shipped parcel needs reconciliation.”

Non-live preview adds **SANDBOX EMAIL PREVIEW — NOT SENT**. Fallback subject suffix: **Dispatch update**. No default $2 claim is hardcoded in the email body; amounts come from the route.


---

# Buyer returns and seller return updates

Audit date: October 29, 2026 (America/Los_Angeles).


Returns 0.1.0-rc2 and Seller Orders 1.7.0-rc1 are active. Last deployment verification left new return requests, return label issuance and refund execution OFF. This audit did not enable them or independently re-read their settings. Template presence is not proof an operational financial return was completed.

## New coordinated return workspace

**Subject:** `KnifeRevive return update · Order #{order_number}`

Recipients: buyer, seller, assigned return recipient and support as allowed. Admin-private messages notify admin only. No private conversation text/evidence is copied into the generic update.

**Body:** “Your KnifeRevive return has an update.” **Order #{order_number} · Return #{return_id}**; {item_title} · Qty {quantity}; {next_step_sentence}; **View return details**; “Sign in as the purchasing customer or assigned seller. Guest buyers can request a new secure email-access link.” **Need help?**

The exact next-step sentence is selected from Cases::next(), reproduced below. Includes “Your refund is approved. It has not been sent yet.” and “Refund sent through the original payment method. Bank timing may vary.” These represent separate states.

## Guest access link

**Subject:** Your secure KnifeRevive return link

**Body:** “Open your private KnifeRevive return workspace. This link expires in 30 minutes and can be used once.” **Open return workspace**. “If you did not request it, ignore this email.” Actual secure link is generated per request and is not included in this report.

## Legacy return outbox (compatibility source; not an additional promised active workflow)

**Subject:** `[KnifeRevive] Return {Title_Cased_Event} — Order #{order_number}`

Body labels: Return #{return_id} / Order #{order_number}; Item; Quantity; Status; Reason; Purchased policy; Requested resolution; Rejection reason; Decision / support note; Confirmed return address; Confirmed ship-by date; Purchased return deduction; Return carrier; Tracking; Shipped; Received quantity; Receipt date; Condition; Recorded refund; View return (sign in); Support.

Standard caveats: “Seller approval is permission to return, not a payment refund. Replacement requests need manual coordination.” / “No prepaid label is created.” / “Marketplace management must review before issuing funds.” / “Payment settlement depends on the payment provider.” These are legacy-path copy, not descriptions of the new label workspace.

For legacy `refunded` mail, the buyer is removed from recipients because WooCommerce owns the buyer's financial refund email. The new generic workspace update is separate from the native financial email; deduplication/status coordination remains a documented launch gap.


---

# Buyer restock, price-drop and saved-search reminders

Audit date: October 29, 2026 (America/Los_Angeles).


Active Set Reminders 1.0.1. Email-only opt-in alerts; confirmation, request state and queue conditions apply.

| Kind | Exact subject |
| --- | --- |
| Manage link | Manage Your KnifeRevive Reminders |
| Confirmation | Confirm your KnifeRevive reminder 🔔 |
| Activated | 🎉 You’re in! KnifeRevive reminder activated |
| Multiple updates | 🔔 Your KnifeRevive finds are here |
| Single-alert fallback | 🔔 A KnifeRevive find you’re watching is here |
| Single price drop | 🔥 Price drop: {product_name} — {formatted_price} |
| Single back in stock | It’s back! {product_name} — {formatted_price} |
| Single new search match | New match: {product_name} — {formatted_price} |

**Shared greeting:** Hey {first_name}! or Hey there!

**Manage:** “Your private link is ready. It expires in seven days.” **Manage my reminders**.

**Confirm:** “One quick step! Confirm this email to switch your chosen reminder on. This link expires in seven days. If you didn’t request it, you can ignore this email.” **Confirm my reminder**.

**Activated:** “We got you! Your reminder is on. We’ll email when your chosen find is back, drops in price, or a new listing matches your search.”

**Alert:** “Hey, a find you’re watching has an update 👀”; {product_name}; optional product image; “Just dropped from {old_price} to {new_price}.” / “Back in stock at {price}.” / “Your saved search has a match at {price}.” **Take a look**.

“Availability and prices can change. Restock and price-drop reminders finish after one alert; saved searches stay on until you pause or delete them.”

**Shared ending:** “You receive these email-only alerts because you requested them.” **Manage, pause or delete reminders**. **Privacy · KnifeRevive**. Wrapper heading: KnifeRevive Reminders.


---

# Knife sharpening booking and service-stage emails

Audit date: October 29, 2026 (America/Los_Angeles).


Active Agent Commerce 0.6.1 and Seller Orders 1.7.0-rc1 provide distinct booking and order-stage messages.

## Booking request / confirmation

**Subject:** `KnifeRevive sharpening {stage} — {preferred_date}`

Stage is the booking event (requested or confirmed). Recipients are separate queued customer/seller/admin jobs.

**Body template:** “Sharpening booking {stage}. Reference: {booking_reference}”; “Requested day: {preferred_date} (Pacific time).”; “Handoff: {mode}; return: {return_mode}.”; {service_title} × {quantity}.

Conditional text: “The service day is confirmed.” / “The requested day awaits merchant confirmation.”; “Pay when you collect your sharpened knives.” / “Online payment verified on the original WooCommerce order.”; “Native order reference: {number} ({status}).” / “No WooCommerce order has been created yet.”

Role-specific endings: **Private booking link:** {secure_customer_link}; **Review in your signed-in seller booking inbox:** {seller_url}; **Review in WooCommerce → Agent Commerce:** {admin_url}. **Support:** {configured_booking_phone}.

## Order-stage notice to buyer

**Subject:** `[KnifeRevive] {stage_label} — Order #{order_number}`

| Stage | Exact stage label |
| --- | --- |
| blade-check | We received your knives / Blade check |
| sharpen-shape | Sharpening has started |
| hone-test | Hone & test |
| pay | Your knives are ready |
| return | Get them back / pickup-return information |

**Body:** `Your KnifeRevive sharpening order is now at: {stage_label}.`


---

Native WooCommerce account/order variants, exact templates, and shared theme additions are in files 11–13. See their source reference sections for the full unabridged conditional wording.
