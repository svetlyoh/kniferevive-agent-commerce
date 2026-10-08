# KnifeRevive — incremental implementation instructions for Sol 6.1 Codex

**Status:** instructions only; no implementation, deployment, merchant configuration changes, or payment enablement is authorized by this file.  
**Owner request:** October 8, 2026.  
**Primary workspace:** resume the EXISTING KnifeRevive Codex project/workspace where the WordPress site is being developed; do not start a replacement project.  
**Agent-commerce source:** https://github.com/svetlyoh/kniferevive-agent-commerce  
**Current development baseline to inspect:** draft PR #2, branch feat/native-listing-checkout, based on draft PR #1 (docs/concierge-installation-0.1.2). Compare with deployed site and the currently published ClawHub artifact before changing anything.

## 0. Non-negotiable scope and working method

1. Make **incremental** changes to the existing KnifeRevive WordPress/WooCommerce/Dokan and agent-commerce implementation. Read the actual current Codex project, theme, installed plugins, configuration and Git branches first. Preserve the existing seller marketplace, ListLab, WhatsApp seller messaging, Stripe/Connect and Lightning workflows. Do not overwrite another agent's in-progress work or merge the draft PRs without review.
2. Treat this document as a task brief, not proof that payments, authorization, live merchant verification, ClawHub publication, or in-chat payment integrations already work. Record verified, unverified, disabled, and blocked states separately. Do not claim conformance/certification without testing the exact deployed version.
3. Start with a concise code/config inventory and an incremental diff plan; ask the site owner the decisions in section 6 before enabling any paid flow. Implement small, reviewable changes with tests, versioned docs and rollback instructions. Do not silently deploy, publish a new skill, activate gateways, alter merchant keys, change seller ownership or grant administrative capabilities.
4. Preserve the production 0.3.0 behavior documented in docs/booking-deployment-2026-10-08.md: unpaid booking requests are enabled; actual prepayment, native wallet payment and autonomous order/invoice creation are NOT verified/enabled. The older main branch is not the production feature baseline. Read PR #2 and compare to live /capabilities; the repo and ClawHub skill versions may differ.
5. Register WordPress REST routes only on rest_api_init; keep WooCommerce CRUD/HPOS and Dokan ownership checks. Never modify WordPress/WooCommerce/Dokan/Stripe/Lightning core files. Do not use the old unsafe early REST-registration pattern.

## 1. Audit the current bot skill and compare with other agent skills

Inspect at minimum:

- skills/kniferevive-concierge/SKILL.md, references/api.md, booking.md, payments.md, listing-checkout.md, installation.md, sharpening.md.
- openapi/kniferevive-agent-v1.yaml and generated plugin copies; AI.md and tools/build_contract.py.
- wordpress/kniferevive-agent-commerce/includes/Booking.php, BookingFrontend.php, BookingCoverage.php, ListingCheckout.php, ListingFrontend.php, Frontend.php, Api.php, Settings.php, Payments.php, GatewayDiagnostics.php, and the native Lightning coordinator integration.
- assets/storefront.css, storefront.js, booking.js; tests/booking.php, listing-checkout.php, integration.php, browser tests; docs/booking-release-0.3.0.md, booking-deployment-2026-10-08.md and docs/publication-status.md.
- Existing WordPress Twenty Twenty-Four templates, Site Editor patterns, Site Logo block/media attachment, WooCommerce checkout templates, real published policy pages and site branding.

Research how currently supported **host applications** import skills and provide customer identity, contact/address permission, checkout handoff and independently authorized payments. Compare official Agent Skills / OpenClaw / ClawHub documentation, real examples of skills using hosted payment checkout, WooCommerce native checkout, Stripe customer saved payment methods, and Lightning wallet payment permissions. Distinguish portable text-only SKILL.md instructions from host-specific integrations. A downloaded skill is NOT an OAuth grant, address book, stored card, wallet or permission to spend.

Use official, current sources as the normative baseline, including:
- https://agentskills.io/specification
- https://docs.openclaw.ai/
- https://docs.stripe.com/payments/checkout
- https://woocommerce.com/document/stripe/setup-and-configuration/settings-guide/
- https://woocommerce.com/document/stripe/setup-and-configuration/stripe-webhooks/
- https://developer.wordpress.org/themes/
- https://www.w3.org/TR/WCAG22/
- https://github.com/lightning/bolts/blob/master/11-payment-encoding.md

**Deep-research handoff:** incorporate the separate deep-research findings when available. For every proposed host, document what is actually supported, how authorization is obtained and proven, whether the bot can render structured service choices in-app, and the safe human handoff when it cannot. Do not assume ChatGPT, OpenClaw, Muse, Grok or any other bot shares a universal address/payment API. Do not claim ACP, AP2, MCP, x402, L402, MPP or other protocol compliance just because a Markdown skill or OpenAPI file exists.

Produce a standards gap table in the implementation PR: requirement, current evidence, applicable host/protocol, gap, proposed incremental fix, test, and status. Verify skill metadata, reference links, prompt-injection isolation, least privilege, data minimization, HTTPS origins, scoped capabilities, disclosure, provenance and version-specific registry audit. Preserve the existing clean-audit evidence but do not extrapolate it to a new release; separately report the documented missing Skill Card/provenance where still applicable.

## 2. Authorization and customer data: two independent gates

Implement an explicit, server-enforced **address/fulfillment permission** state and an independent **payment authority** state. A chatbot's assertion that the user "has authorized" is never sufficient.

**Address permission** requires the user to approve sharing a particular contact/delivery/pickup address with KnifeRevive for this booking, with purpose and scope. The source may be an authenticated WooCommerce account, a host-provided scoped identity/address grant or the private first-party form. A saved address is not automatically approved for bot disclosure. The merchant must independently validate the address and county for courier service; a five-digit ZIP is preliminary coverage only. Record consent/version/expiry or proof reference without exposing raw PII to unrelated bots. Do not scrape contacts, bot memory, browsers or account profiles.

**Payment authority** requires merchant checkout readiness AND a customer-controlled, rail-specific authorization bound to KnifeRevive, the exact quote/order, maximum all-in total, currency, service, handoff, policy version and expiry. Distinguish:
- merchant permission to accept a rail;
- customer approval of a quoted purchase;
- actual processor card authentication or independent wallet authorization;
- verified payment settlement.
They are different. A saved WooCommerce/Stripe card token does not itself authorize an agent to charge it. The current KnifeRevive flow does not provide a verified delegated card-spending integration; keep card checkout human-controlled until a supported provider mechanism is proven and explicitly approved.

**Required decision matrix:**

| Address authorized for this order? | Payment authorized for this order/rail? | Bot behavior |
| --- | --- | --- |
| No | No | Offer service choices, then open secure first-party form for contact/address and quote review; human completes provider checkout or chooses pay-at-service. |
| Yes | No | Prefill only permitted address/contact details; still require human review of exact price and secure card/Lightning checkout. |
| No | Yes | Obtain the missing address through the first-party form; any changed tax, trip fee, location, scope or total invalidates the earlier payment mandate and requires renewed approval. |
| Yes | Yes | Offer in-app service/date/quantity selections and itemized quote. Only proceed to the **supported** provider-specific authorized path if both independently verifiable grants and merchant gates cover the final quote. Otherwise hand off to first-party review/checkout. |

The skill may ask the host whether a scoped grant exists only through an explicitly supported and user-approved host interface. If the host has no such interface, return **authorization_unknown** and show the secure KnifeRevive form; never infer "authorized" from being logged in, having installed a skill, or having a payment method on file. Do not put credentials, raw addresses or private capability tokens in public URLs, bot logs, telemetry, referral parameters, screenshots or messages.

Define distinct states for address_authorization (unknown, needs_user, granted_for_order, expired, revoked, merchant_review_required), payment_authorization (unknown, needs_user, authorized_for_quote, expired, revoked, unsupported), payment_state and booking_state. Implement any new API fields only if required by an actual integration; do not add fake authorization booleans trusted from clients. Bind proof to the authenticated principal, quote hash, merchant, rail, expiry and server-side record. Make revocation, stale price, new date, increased knife quantity, and changed transport invalidate authorization.

## 3. In-app sharpening booking UX and fallback

The ideal bot conversation should allow a customer to select, without leaving the app **when that host genuinely supports structured controls**:

1. KnifeRevive sharpening service(s) and number of knives **per SKU**; use current WooCommerce product IDs, descriptions, definitions, stock and prices. Do not invent knife-size classifications or fixed prices.
2. Preferred customer drop-off or merchant pickup **date** from the live request-day list, shown in America/Los_Angeles; distinguish a requested day from a confirmed appointment and do not invent time slots. Explain intake and return are separate.
3. Return by customer collection or merchant delivery, subject to eligibility and trip charges. Preserve the three current booking modes: pay_later_dropoff, prepaid_dropoff and prepaid_pickup. Do not silently convert a requested paid pickup to drop-off.
4. Postal/county eligibility and exact pickup-address review when required. Preserve the current Contra Costa/Santa Clara prepaid/pickup eligibility and existing Bay Area/outside-area messaging until the owner changes the business rules. Unknown/mixed-county ZIPs require merchant review.
5. An itemized review with product quantities, service subtotal, merchant trips ($7.99 each at current configuration, $15.98 for two), discounts if any, actual WooCommerce taxes and fees, currency, grand total, preferred date, booking state, cancellation terms, and payment readiness. An estimate is not a final binding charge.
6. Explicit actions to edit choices, request the booking, review a confirmed booking, continue to secure checkout if enabled, check status, or contact support.

The server remains authoritative for coverage, available dates, fees, taxes, quote expiry, stock, merchant confirmation and gateway eligibility. Existing draft -> requested -> confirmed flow is the baseline. A booking request must not automatically charge a card or wallet, reserve an unconfirmed day, or claim the appointment is confirmed. Preserve stable idempotency keys, original-attempt reconciliation and separate booking/payment states.

Where the host cannot render safe structured choices or cannot supply verifiable address/payment authority, offer the existing **private first-party booking/review page**. A bot must not submit the human approval form, impersonate a user, trigger a payment by navigating a link, or claim a payment is successful from a browser redirect. Human choice should be possible without installing a skill. Keep unpaid requests working even while payment rails are disabled.

## 4. Improve the WordPress booking/signup/review form using Twenty Twenty-Four

The current BookingFrontend.php emits a minimal standalone HTML page with a plain "KnifeRevive" text heading and custom storefront.css; it does **not** yet inherit the real Twenty Twenty-Four Site Editor header, Site Logo block or site styling. Improve it incrementally while preserving its scoped session cookies, security headers, POST/redirect/GET flow, server validation, nonce/CSRF protection, and no-store/no-referrer behavior.

**Design requirements:**

- Use the site's **actual installed KnifeRevive logo**, WordPress Twenty Twenty-Four typography, colors, spacing, buttons, responsive patterns and brand identity. Discover the existing Site Logo/media attachment and template parts; do not invent a new logo, copy a third-party logo, or hardcode a media ID. Use native block-theme/template hooks or a narrowly scoped plugin integration; do not replace the theme, edit Twenty Twenty-Four core files, or globally restyle WooCommerce/Dokan.
- Keep booking and private payment review visually consistent with the site and each other. Preserve clear KnifeRevive branding, a "Book knife sharpening" heading, readable location/hours, accessible return to site, secure checkout trust copy and a clear support footer. Do not insert external analytics, marketing pixels or third-party scripts on private review pages.
- Create a compact, mobile-first stepper or clear sections: **Knives → Service day & handoff → Contact/address → Review & request**. Show only required fields. Reuse a single postal code input rather than asking twice; conditionally show street address only for pickup/delivery or merchant county review. Pre-fill only data specifically authorized for this order. Never prefill card numbers.
- Use quantity steppers or accessible numeric fields with min/max and a visible subtotal; require at least one knife. Date choices come from live booking availability and are labeled **requested day**. Show the real service definition and the current merchant-trip fee without treating a request as a reservation.
- Present pay-at-service vs prepaid preference honestly. If payment is disabled, label it **Prepayment not yet available** and never render an enabled "Pay now" action. If a confirmed booking becomes payable, show a separate review of actual final WooCommerce totals and a provider-controlled checkout action.
- Provide inline validation, field-level errors, preserved user input after errors, focus management, loading and duplicate-submit protection, clear expiry/recovery messages, no-JavaScript form fallback, keyboard navigation, 44px-ish touch targets, sufficient contrast and WCAG 2.2 AA review. No deceptive urgency, surprise add-ons or pre-checked purchase authorization.
- A user must be able to view cancellation terms and contact support before submitting. Display booking state and payment state as separate readable labels. Do not display full street address or private token in public/shared status.
- Maintain private-page security: HTTPS, cache prevention, no-referrer, CSP reviewed against the actual WordPress block-theme assets, strict same-origin form actions, nonce/CSRF, escaping, scoped cookie lifetime and no external leakage. If the existing restrictive CSP conflicts with theme scripts/styles, redesign the integration safely rather than simply removing CSP.

Also inspect the legacy Frontend.php and ListingFrontend.php review screens so the same KnifeRevive styling and support links apply without breaking existing marketplace checkout or seller accounts.

## 5. Card checkout, Google Pay, Lightning and invoices

**Cards / Stripe / WooCommerce:**
- Keep current new-agent payment gates **OFF** until owner approvals and real verification. For native seller-owned sharpening products, use the existing WooCommerce/Dokan checkout and its actual configured gateway. Do not use the legacy operator-only Stripe adapter to charge seller-owned services, bypass seller accounting, transfer funds to the wrong merchant or grant manage_woocommerce to the seller.
- Ask the site owner to verify WooCommerce → Settings → Payments → Stripe connection, account payment/payout status, test/live mode, saved payment methods if wanted, express methods, HTTPS and native gateway webhooks. Keep the separate dedicated agent Stripe webhook distinct from WooCommerce's existing gateway webhook.
- If no verifiable delegated card payment authority exists, the **human enters or selects a card only inside the payment provider's secure hosted/embedded WooCommerce/Stripe fields**. Never collect PAN, CVC, 3DS code or card token in KnifeRevive's custom signup/booking form or a chatbot conversation.
- Even when a saved payment method is visible to an authenticated customer, require an eligible gateway/customer confirmation flow. Verify payment by native WooCommerce/Stripe evidence and reconciliation, not by redirect, order creation or bot narration. Google Pay is conditional on gateway settings, browser/device and user authorization.

**Bitcoin Lightning / invoices:**
- Reuse the installed KnifeRevive Lightning plugin's native WooCommerce gateway, coordinator, bridge, order-bound BOLT11 invoice and settlement checks; do not build a second wallet, copy merchant credentials, generate unbound invoices or expose node keys.
- Keep booking_wallet_verified and related readiness flags false until real native invoice generation, mainnet/payee verification, settlement and refund handling are tested with the owner. The existing /bookings/{id}/wallet-invoice endpoint reads an already-created native invoice; it does not create a new order or invoice. Do not claim autonomous wallet checkout exists.
- For a customer without an authorized wallet, show the original invoice in a secure human-controlled payment page with amount in sats, fiat quote, invoice expiry, payment status and QR/copy action only if native gateway provides valid data. Never auto-pay.
- For an agent with a **separately authorized host wallet**, independently validate BOLT11 signature, network, recipient/payee provenance, payment hash, exact amount, expiry, locked quote and routing-fee limit before a single wallet send. Merchant permission to receive bot payments is not customer wallet authorization. Preserve original payment hash/idempotency and reconcile an uncertain send before any retry; never automatically switch payment rails.
- Lightning refunds are a distinct manual merchant procedure unless a tested, authorized native refund flow exists. A cancellation request does not equal a refund. No double invoice/charge on reload or retry.

## 6. Questions the Codex agent MUST ask the KnifeRevive site and WooCommerce administrator

Do not ask the owner for passwords, Stripe secret keys, webhook signing secrets, full card details, seed phrases, Lightning macaroons or sensitive values in chat. Request **confirmation of admin-screen statuses and decisions**, with secrets entered only into official dashboards/server secret management. Ask in small batches and record approved answers:

**Business/service setup**
1. Confirm actual public service policy URL and version; should the proposed short policy in section 7 be approved or edited? What cancellation cutoff, refunds for unperformed prepaid service, incurred pickup fee, quality remedy, rescheduling rules and required legal exceptions apply?
2. Confirm actual daily job capacity, merchant confirmation procedure, holiday exceptions, date/time rules and return scheduling. Existing owner hours: Friday/Saturday 09:00–19:00; Sunday 10:00–16:00 Pacific. A requested date is not a guaranteed slot.
3. Confirm pickup location, phone, approved county/street-address verification method, address data retention and whether a logged-in customer's saved WooCommerce billing/shipping address may be offered for explicit per-order approval.
4. Confirm the exact sharpening product SKUs, their seller ownership, whether these are KnifeRevive's own services or Dokan vendor services, real service definitions, price/tax class, and whether the native shipping configuration wrongly duplicates $7.99 merchant-trip fees. Never change ownership/virtual flags to bypass checkout.
5. Confirm whether a customer may request a booking without creating a WordPress account; which fields are required for receipt and fulfillment; and whether the site logo/TT4 branding currently in the Site Editor is the desired source.

**WordPress/WooCommerce/Stripe**
6. Ask the owner to open WordPress Admin → WooCommerce → Settings → Payments and confirm the **actual enabled card gateway**, connected merchant account, live/test status and payment/payout eligibility. Ask them to resolve any provider identity/business verification **in the provider dashboard**; the Codex agent must not represent verification as completed.
7. In the installed Stripe extension's settings, confirm webhooks for test and live, checkout display, card methods, Google Pay/Apple Pay eligibility, saved-payment-method behavior and 3DS/SCA flow. Distinguish native WooCommerce Stripe webhook from the agent adapter's dedicated test/live webhook. Ask whether a controlled test environment exists; do not flip production to test mode without a maintenance plan.
8. Confirm WooCommerce checkout pages, tax configuration, shipping/service fee mapping, seller commission/payout routing, receipt emails, payment status and refund workflow for a seller-owned sharpening SKU. Ask permission to run a small authorized end-to-end **test** with rollback/refund evidence before setting readiness flags.
9. Confirm whether the owner wants human-controlled checkout only or has a specific, documented agent-payment provider that issues verifiable **shopper** delegated spending authorization. A stored card or "bot has permission" statement is not sufficient. Do not promise automatic card payment in the current version.

**Lightning**
10. Ask the owner to open WooCommerce → Settings → Payments and the existing KnifeRevive Lightning settings to confirm gateway enabled/acceptance status, actual merchant payee, bridge health, supported network, invoice expiration, maximum amount, callback/webhook settlement and reconciliation. Do not request credentials in chat.
11. Confirm whether Lightning invoices are generated only through native buyer checkout, whether a buyer may safely copy/scan an order-bound invoice, and which merchant support procedure handles expiry, late payment, mistaken sends and manual refunds. Ask for an approved real test transaction and verification evidence before enabling any bot-wallet flag.
12. Confirm the user-facing supported payment rails, whether Lightning payment from an independently authorized host wallet is allowed, and the wallet's recipient/amount/routing-fee controls. Do not turn on autonomous order/invoice creation unless a new separately reviewed implementation truly supports it.

**Deployment/owner approval**
13. Present a redacted readiness checklist and request explicit approval for each gate: booking_enabled, booking_prepaid_enabled, listing_handoff_enabled, listing_pricing_verified, listing_live_verified, booking_wallet_enabled, booking_wallet_verified, legacy enabled/pricing_verified/stripe_enabled/lightning_enabled/live_verified. Explain which are business preferences vs actual verified readiness.
14. Obtain permission separately before staging tests that send customer emails, create orders, perform payments/refunds, publish policy text, release to ClawHub, or deploy to production. Do not treat approval of this instructions document as payment-activation authorization.

## 7. Short, restrictive sharpening cancellation/refund policy — DRAFT FOR OWNER APPROVAL

Do not silently publish or label this as the current legally binding KnifeRevive policy. First obtain the owner's approval, confirm California consumer-law requirements and add an effective date, version and canonical HTTPS page. Keep marketplace **goods** returns and individual Dokan seller terms separate from this **sharpening service** policy.

Suggested concise public-facing text, subject to review:

> **KnifeRevive sharpening — cancellations and refunds**  
> Sharpening appointments are subject to KnifeRevive confirmation. To cancel or change a request, contact us before the service begins. Completed sharpening services are generally non-refundable, except where required by law or where KnifeRevive agrees that the service was not provided as promised. For prepaid services cancelled before work starts, KnifeRevive will review a refund of the unperformed service; merchant pickup/delivery fees already incurred may be non-refundable only where lawfully disclosed and permitted. Changes to confirmed appointments depend on availability. If there is a service problem, contact the Support Crew promptly. Cancelling a booking does not automatically issue a card or Lightning refund.

Ask the owner to specify any cancellation deadline, service quality remedy, actual refund timeline, no-show treatment, transport fees and whether a prepaid booking that the merchant cannot confirm is automatically eligible for full refund. Do not create prepaid checkout until these answers and a public policy URL/version are approved. Link the final policy next to quote totals and the human consent action; include the same policy URL/version in the bot quote/API.

**Support links to show on the form, policy page, booking status and payment/reconciliation error states:**
- [Chat on Telegram](https://t.me/svetlyoh?text=Hi%20KnifeRevive%20Support%20Crew!%20I%20need%20help%20with%20a%20question%20about%20KnifeRevive.)
- [Chat on WhatsApp](https://wa.me/14152999611?text=Hi%20KnifeRevive%20Support%20Crew!%20I%20need%20help%20with%20a%20question%20about%20KnifeRevive.)
- [Email the Support Crew](mailto:knifereviveofficial@gmail.com?subject=KnifeRevive%20support)

Use proper URL escaping, external-link security attributes and visible accessible labels. Do not route general customer support to a Dokan seller's private WhatsApp unless that is specifically the relevant seller contact action.

## 8. Tests, acceptance criteria and delivery

**Do not enable payments merely because these tests pass.** Validate on the existing fenced synthetic sandbox and, only with permission, an approved merchant test environment. Preserve existing classic/HPOS and synchronization on/off tests, coverage and native gateway behavior.

Acceptance matrix:
- Skill install/import and documentation: valid frontmatter, references and registry version; no installer or hidden credential access; bot with no host authorization correctly falls back to the human form.
- Four address/payment permission combinations; absent, expired, revoked, forged or mismatched authorization; no address leaked to an unapproved merchant; tax/quantity/date/rail/fee/policy changes invalidate stale quote authority.
- Anonymous and authenticated customers; previously saved address requiring new sharing approval; authorized address without payment; authorized wallet without address; bot without any supported wallet/card authorization; no forced account creation unless owner approves.
- In-app service/knife quantity/preferred date/return choice and final itemized total; request vs confirmed booking; county/mixed ZIP/unknown/outside-area paths; customer drop-off vs merchant trip fees; no invented slots or tax totals.
- Theme/branding: actual Site Logo, Twenty Twenty-Four header/footer/typography, mobile and desktop, WCAG 2.2 AA keyboard/screen-reader review, inline errors, no-JS, slow network, duplicate submits, form resumption, strict CSP and no third-party private-page requests.
- Disabled card/Lightning gates show honest alternatives; card PAN/CVC never reaches KnifeRevive plugin/skill; saved cards still require provider confirmation; 3DS and Google Pay eligibility; real gateway/webhook reconciliation in approved test environment.
- Native Lightning invoice only after native order, validated amount/payee/hash/expiry, no second invoice on refresh, single authorized send, timeout reconciliation, late settlement, cancellation without automatic refund.
- Quote/booking/consent/idempotency replay and concurrency, status token scoping, session expiry, private link leakage prevention, logging redaction, WooCommerce CRUD/HPOS, Dokan seller accounting and no legacy gateway bypass.
- Short policy shown before human approval; owner-approved version/URL; three support channels work; policy and support remain accessible from failure and cancellation states.

Deliverables for the **future implementation PR**:
1. A file-by-file incremental change summary and migration/rollback notes.
2. Evidence table showing actual host-skill authorization support versus safe handoff, with sources and tests; clear remaining standards gaps.
3. Screenshots or local visual evidence for mobile/desktop Twenty Twenty-Four booking and review, without exposing real customer data.
4. Updated SKILL.md/references, OpenAPI/generated copies and public docs only if behavior changed; exact version and publication status, with no false ClawHub audit claim.
5. Test logs for classic/HPOS, booking/payment boundaries, theme, browser and accessibility; redacted Stripe/Lightning setup evidence.
6. A concise **owner action checklist** with outstanding admin questions and **explicit payment-gate hold points**. Ask the owner to approve policy, capacity, checkout verification and deployment independently.

**Definition of done:** a bot can guide sharpening service selection and quote review without overclaiming permissions; missing authorization routes the human to a branded, secure Twenty Twenty-Four form; verified host permissions are used only within their exact scope; the user sees an itemized total before provider checkout; booking and payment states are truthful; payment remains disabled until actual merchant verification and explicit owner enablement.
---

## 9. Incident addendum — missing booking emails, invisible Dokan drop-off order, and bot acknowledgement (October 8, 2026)

**New owner-reported incident:** A human followed a bot-provided KnifeRevive booking link, submitted the form with an email address, but neither the merchant nor the KnifeRevive administrator received an email; the merchant cannot find the customer drop-off request in the seller dashboard; the drop-off did not process as a WooCommerce order. The owner also wants the KnifeRevive WordPress plugin to notify the originating bot, by the booking/order reference, when KnifeRevive has received the form and when a genuine WooCommerce order has been persisted.

**Priority:** Treat this as a **real integration defect and operational incident**, not proof that a card, order or email was delivered. Do not mark it resolved until an authorized end-to-end test proves seller visibility, notifications and the correct bot-facing acknowledgement. Preserve the current user's original request and do not create a second order or charge during investigation.

### 9.1 Evidence-based diagnosis from the current 0.3.0 source (not a live-server finding)

The development source explains the reported symptoms; validate the exact production version and the specific booking before attributing the incident:

1. **No WooCommerce order on request is intentional in the current implementation.** The first comment of wordpress/kniferevive-agent-commerce/includes/Booking.php states that booking requests do not create orders, invoices or payments. Booking::create saves a draft to the agent-commerce Store; Booking::submit changes it to requested. A native WooCommerce order is only possible later via Booking::checkout and ListingCheckout after merchant confirmation and separately enabled payment gates. The public form therefore currently creates a **booking request**, not a WooCommerce/Dokan sale. This is a functional mismatch with the owner's requested drop-off workflow, not evidence of a failed Stripe transaction.
2. **Merchant notifications are missing in the code path.** Booking::notify currently calls wp_mail for the customer's email and the WordPress get_option('admin_email') address. It does not address the Dokan seller who owns the sharpening product, nor the seller's configured operational email. Consequently a seller should not be expected to receive a booking-request notification from this method.
3. **Potential lost-notification bug.** Booking::notify sets the per-stage notified flag to true **before** calling wp_mail; it does not inspect either wp_mail return value, persist per-recipient delivery outcomes or schedule retries. A transport failure can leave a booking permanently marked as notified. A true wp_mail return only means the sending mechanism accepted the request; it does not prove inbox delivery. Do not simply unset flags and blindly resend; reconcile the original event and delivery history first.
4. **The vendor dashboard only shows actual Dokan-recognized orders.** The custom booking Store queue is exposed under WordPress/WooCommerce → Agent Commerce for users with manage_woocommerce. It is not currently a Dokan seller booking inbox. Seller order-email and dashboard behavior normally follows Dokan-recognized WooCommerce orders, which this flow does not create.
5. **No originating-bot push integration is evidenced.** The existing skill documents a scoped booking-status GET and a private booking link. It does not implement a general bot registration, authenticated outbound callback, delivery queue or callback acknowledgement. A SKILL.md download alone does not supply a routable bot endpoint or authorize KnifeRevive to send messages into a bot conversation.
6. The release/deployment documentation explicitly states that the live 0.3.0 verification did **not** prove customer email delivery or real processor settlement. Thus the current reports must be investigated, not dismissed based on sandbox test counts.

**Code entry points:** BookingFrontend::render (form POST), Booking::submit, Booking::notify, Booking::response, Booking::admin, Booking::confirm, Booking::checkout; Api::bookingCreate/bookingGet/bookingCheckout; ListingCheckout::bindOrder/orderCreated/paymentObserved/status; Settings; Store; the plugin bootstrap; WooCommerce/Dokan email hooks and seller-order dashboard filters. Re-check the live plugin ZIP/version and WordPress error logs before patching.

### 9.2 First diagnose and recover the original customer booking safely

In the EXISTING Codex workspace, ask the owner for the **non-secret booking reference** shown on the confirmation page and approximate Pacific-time submission, and separately ask whether they consent to checking redacted site logs. Do not ask for the private booking-access token, customer street address, card data or login credentials. On the approved admin system:

1. Confirm whether the browser actually reached the **requested** state and the server saved customer email, SKU/quantity, seller product author, requested date, mode and submitted_at. Search the Agent Commerce booking queue and original Store record by reference. Distinguish draft, requested, confirmed, cancelled, order-linked and paid states. Do not rely on an email being received as evidence that a booking exists.
2. Check WooCommerce → Orders by reference/booking metadata, using wc_get_orders and WooCommerce CRUD compatible with HPOS; also inspect Dokan's seller view. If no order exists, record **booking received / WooCommerce order not created**. If an order exists, reconcile its native ID, seller ownership, status, line items, billing contact and any existing payment/stock state before attempting repair.
3. Check WordPress Settings → General administrator email versus the inbox the owner expected; WooCommerce → Settings → Emails recipient settings; Dokan seller account email and order email enablement. Distinguish the custom booking email from the standard WooCommerce **New order** email: the latter cannot fire without a qualifying order/event.
4. Check wp_mail_failed/wp_mail_succeeded instrumentation, PHP/server error logs, SMTP provider logs and WordPress/host cron/Action Scheduler health. Verify authenticated From domain, SPF, DKIM, DMARC, suppression/bounce, spam quarantine and actual delivery trace. Never claim wp_mail=true means the merchant or customer received it. Request owner approval before a test email to real recipients.
5. Show the owner a redacted incident record: booking reference, persisted booking state, WooCommerce order ID or **none**, expected recipient roles (customer/vendor/admin), delivery attempt/result by role, and the next safe recovery action. Never print private booking tokens, full addresses or email bodies into shared logs.
6. Provide a narrowly scoped **admin-only reconciliation/re-notify** tool for the *existing* booking/event with explicit confirmation, idempotency and audit trail; distinguish retrying a failed notification from duplicating a booking or generating a fresh WooCommerce order. No automatic migration of historical booking requests into paid orders. If production email is broken, offer a visible admin/seller dashboard alert independent of email.

### 9.3 WordPress/WooCommerce plugin architecture: extend the existing plugin, not a generic "skill plugin"

**Answer to the owner's plugin question:** Yes, KnifeRevive needs a WordPress-side integration to connect agent skill bookings to WooCommerce/Dokan orders and back to bots, but **the repository already contains the KnifeRevive Agent Commerce WordPress plugin** at wordpress/kniferevive-agent-commerce. Extend that plugin with a small, isolated **Booking Order Bridge + Notification/Agent Event Bridge** module instead of installing an unrelated third-party "bot skill" or replacing WooCommerce/Dokan. Add a separately installable add-on only if the existing project architecture/ownership explicitly requires independent deployment. Keep the portable skill as an API consumer, not the server-side order authority.

The WordPress plugin must own: validated human form submissions; persistent booking records; native order creation/linking where approved; seller/admin/customer notification events; scoped bot subscription or status polling; delivery/retry observability; and security gates. WooCommerce remains authoritative for **orders, taxes, totals, payment, stock and order status**. Dokan remains authoritative for **seller attribution, order access, commissions and seller payout workflows**. Stripe/Lightning gateway plugins remain authoritative for payment. The bot only receives status facts, never credentials or payment authority.

**Implement in small, reviewable phases, without changing payment settings:**

**Phase A — notifications and merchant request visibility, independently of order creation**
- Add a seller-scoped booking-request inbox to the existing Dokan dashboard (or an approved Dokan-compatible dashboard integration) with booking reference, requested day, product/quantity, drop-off/pickup mode, payment state, actions and status. Ensure the seller can only access bookings whose products belong to their active Dokan seller account; administrators may view all. Avoid granting sellers manage_woocommerce. Preserve the existing admin queue.
- Resolve recipients from the *actual service product's seller/author and Dokan seller identity*, not the bot, arbitrary user-provided email, site-wide admin address or guessed merchant ID. For mixed-seller requests, reject or split through an explicitly reviewed workflow; do not leak one seller's customer details to another. Ask the owner which business/admin recipients should receive copies.
- Generate distinct **customer receipt**, **seller new booking request** and **administrator new booking request** events after the durable state transition. Include the non-secret booking reference, requested day, mode, item summary, honest status **awaiting merchant confirmation**, and a recipient-appropriate secure action link. Never claim a WooCommerce order number if none exists; do not email private capability tokens to the vendor or admin. Customer private link may be sent only to the verified booking email using the existing narrowly scoped access design.
- Replace the pre-send notified flag with a **durable per-event, per-recipient outbox**. Insert one notification job per event and recipient with unique key (booking ID + transition/version + recipient role/address), state pending/sending/accepted_by_mailer/failed, attempt count, next attempt and last sanitized error. Enqueue via WooCommerce's Action Scheduler after commit; if scheduling fails, retain the outbox and surface an alert. Backoff transient failures; never infinitely retry invalid addresses. Reconciliation should not emit duplicate customer confirmations or merchant jobs.
- Use the site's configured, authenticated transactional email transport (ask admin before installing/configuring an SMTP provider). A successful wp_mail call means **accepted by the sending mechanism**, not delivered; if the mail provider exposes delivery/bounce webhooks, distinguish delivered/bounced states. Instrument WordPress wp_mail_failed/wp_mail_succeeded and correlate by internal event ID without logging PII. Provide admin health status, redacted delivery history and a permissioned retry action. Test actual mailbox receipt only with owner-approved recipients.
- Keep seller inbox and admin queue functional when mail or WP-Cron is down. Add an admin alert for stuck notification jobs and an operational runbook. The human confirmation page should immediately show the persisted booking reference and status without waiting for emails or bot callbacks.

**Phase B — real unpaid drop-off order in WooCommerce, subject to owner choice and Dokan verification**
- Ask the owner explicitly whether **submitting a customer drop-off form should create an unpaid WooCommerce order immediately** (recommended for the reported expectation, after compatibility checks), or whether an unpaid booking request should first appear in the seller inbox and become a WooCommerce order only after seller confirmation. Document this timing in the customer UI and skill. Do not change order timing without approval.
- For the owner-approved **order-at-submit** design, persist a single WooCommerce **unpaid, not-yet-confirmed** order for the same booking reference, using WooCommerce CRUD and a server-enforced unique booking↔order link. Link the exact approved seller-owned product IDs, quantities, service date/mode, order contact, tax/fee data and metadata; preserve original Dokan seller ownership. Calculate totals through the native WooCommerce mechanisms. Record an explicit pay-at-drop-off or unpaid/manual-payment arrangement approved by the owner; **never mark the order paid or completed**, call payment_complete, create Stripe/Lightning charges, or claim an appointment is confirmed.
- Choose and test an order status that WooCommerce and the installed Dokan version actually support for an **unconfirmed unpaid service request**. A generic pending/on-hold status or a custom awaiting-merchant-confirmation status has implications for stock reservation, emails, vendor visibility, commission calculation and checkout; verify these empirically. A standard "Cash on delivery" gateway is not automatically equivalent to **pay at KnifeRevive drop-off** for a virtual sharpening service. Ask the admin which native offline/manual payment option is approved; do not silently enable COD or fabricate a paid order.
- The normal Dokan vendor **Orders** page must show the created order to the correct seller with correct totals/status and seller share, including HPOS. If creating an order via wc_create_order bypasses required Dokan checkout hooks, adapt through supported WooCommerce/Dokan APIs/hooks and test seller suborders, commissions, fees and notifications; do not write directly to WooCommerce posts/meta tables or manually invent Dokan ledger rows. Verify classic checkout and Checkout Blocks if a buyer checkout path is used.
- For **prepaid** requests, preserve the current **merchant-confirmation → buyer-reviewed native checkout → verified payment** boundary. Do not create a second WooCommerce order if the new booking already has an unpaid order. Either reuse the original order safely with WooCommerce's supported pay-for-order path and renewed quote review, or use a clearly reconciled single-order strategy after tests; if unsupported, leave prepayment disabled. Existing ListingCheckout intent/order invariants and Lightning coordinator bindings must not be bypassed.
- If a safe Dokan-recognized order cannot be created at submission, fail closed: keep the booking as **requested** in the merchant booking inbox, notify merchant/admin, and tell the user **no WooCommerce order yet**. Do not display a fake WooCommerce order ID. The booking must not disappear because order creation fails; record a retryable integration error for administrator reconciliation.
- Use a durable booking↔WooCommerce-order mapping, one-to-one invariant, locking/idempotency and a compensating/recovery path for the gap between booking commit and order creation. On retries, timeouts, double clicks, browser reloads, payment retries or callback replays, return the original booking and order; never create duplicate seller orders or duplicate financial events. Protect stock/capacity against premature reservations. Make state transitions and seller approval explicit.

**Phase C — bot/agent acknowledgement and two-way status contract**
- The bot-generated booking link should carry only an **opaque, expiring referral/correlation ID** issued by KnifeRevive, not a raw chat ID, full email, street address, bot API key, WooCommerce order key, payment token or private booking access token. Bind it server-side to the originating bot integration and a scoped booking draft/intent. Do not assume the referral itself proves user identity or permission to access a later booking.
- When the human submits the form and the booking is durably saved, create a server event **booking.request_received**. Only after WooCommerce persists and validates the native order should the plugin emit a distinct **woocommerce.order_created** event. Later emit **booking.confirmed**, **booking.cancelled**, **payment.verified** or **refund.review_required** only on actual authoritative transitions. Bot copy must never say "order received/paid" merely because a booking draft exists, an email was queued, a browser redirected or a callback was attempted.
- A portable SKILL.md file cannot receive inbound messages by itself. **First implement safe status polling**: the originating agent may call the existing scoped GET /bookings/{id} with its valid booking-specific authorization; add an opaque event cursor or limited event-status endpoint if needed, with expiration, least privilege, rate limits and no broad PII. The skill should remember the non-secret reference, use bounded polling only when the host supports it, and otherwise tell the customer to check the first-party status page.
- **Optional push for bot hosts that support a real callback/webhook:** build an admin-approved integration registration and challenge/verification workflow. Associate a stable bot/app integration ID, a specific allowed HTTPS callback origin/endpoint, permitted event types, a per-integration signing key or OAuth credential, user consent to share booking status with that bot, a scoped booking correlation ID, expiration and revocation. Prove endpoint ownership. Never trust a callback URL submitted in a public booking form or arbitrary query string.
- Implement server-side HTTPS allowlisting, public DNS/IP validation at connection time (including redirects and DNS rebinding), blocked private/loopback/link-local/metadata hosts, strict egress timeouts and payload size limits. Do not follow redirects to unapproved hosts. No callbacks to bot platforms without a documented inbound API and required credentials. For hosts without one, **polling or the human confirmation link is the supported fallback**, not a simulated chat notification.
- Queue outbound events in the same durable outbox, independent of the email queue and order transaction. Sign each delivery with a per-integration HMAC-SHA256 over the **exact body plus timestamp**, include event ID, timestamp and key version; verify registration/consent before each attempt. Receiving bots must verify signature, freshness/replay window and correlation. Require 2xx acknowledgement; retry temporary errors with bounded exponential backoff and jitter; stop on revocation or permanent 4xx; provide a dead-letter/replay-by-admin path. Design for **at-least-once delivery with idempotent consumption**, not fictitious exactly-once webhook transport. No arbitrary bot commands, prompts or payment instructions are accepted in callbacks.
- Keep the event envelope intentionally minimal and non-sensitive. Example **illustrative** schema (not a claim that any current host accepts it):

~~~json
{
  "schema_version": "1",
  "event_id": "opaque-event-id",
  "type": "woocommerce.order_created",
  "correlation_id": "opaque-referral-id",
  "booking_reference": "non-secret-booking-reference",
  "order_reference": "public-safe-order-reference-or-null",
  "booking_state": "requested",
  "order_state": "pending",
  "payment_state": "not_started",
  "merchant_confirmation_required": true,
  "occurred_at": "ISO-8601-UTC"
}
~~~

  Do **not** include customer name, email, phone, street address, exact location, access token, order key, Stripe data, invoice preimage or wallet details in the outbound event. If the bot needs more, require a separately consented, authenticated, purpose-limited query; order references must not be treated as bearer credentials.
- The bot's user-facing confirmation should say, as supported by the actual event, **"KnifeRevive received your sharpening request [reference]; the merchant still needs to confirm the service day"**. If a real unpaid WooCommerce order exists, it may additionally say **"Unpaid WooCommerce order [reference] was created; no payment has been taken."** If only the booking was persisted, say **"No WooCommerce order has been created yet."** On callback delivery failure, the booking/order remains valid; the plugin must not roll back or retry order creation merely to send a message.
- Update the skill references and generated OpenAPI contract **only after implementing and testing** these new optional registration/status/event fields; maintain backwards compatibility and truthful capability flags such as agent_event_push_supported, agent_event_polling_supported and booking_creates_woocommerce_order. Do not advertise push or order-at-submit in ClawHub before the actual deployed plugin supports them.

### 9.4 Required owner/admin decisions for this incident

Ask these questions **before implementing the relevant production switch** (use admin UI/status evidence, never request passwords or secret values in chat):

1. What was the **original non-secret booking reference** and approximate date/time? Was the final page showing **requested**? Is the same booking visible under WooCommerce → Agent Commerce?
2. Which Dokan seller account owns the sharpening SKU(s), which verified seller notification address should be used, and should the seller see booking requests before confirming them? Is that seller enabled and able to see native Dokan orders?
3. Should the form create an **unpaid WooCommerce order immediately on request** or only after the merchant confirms the date? Which supported manual/pay-at-drop-off gateway and unpaid order status should be used? Should stock be reserved before confirmation?
4. Which verified admin recipient(s) should receive a copy? Does WordPress Settings → General use the expected email? Which transactional SMTP/email provider is installed, and are SPF/DKIM/DMARC and bounce logs available?
5. Which **actual bot host/app** generated the link, and does that host expose an inbound authenticated webhook or app messaging API? If not, is scoped polling plus a human status page acceptable? May the plugin share **minimal booking/order status** back to the originating bot, and for how long?
6. May Codex run owner-approved synthetic end-to-end tests that create an **unpaid** WooCommerce order and send test emails/callbacks in staging? Which production deployment window and rollback approver apply?

### 9.5 Acceptance criteria and regression tests for the new bridge

Do not mark this issue fixed until the following evidence is captured in the implementation PR:

- **Original incident:** lookup by booking reference correctly distinguishes persisted request, order creation and email delivery; no duplicate order or unintended payment while diagnosing.
- **Customer drop-off:** a real form POST produces exactly one persistent booking; the user immediately sees its reference and **requested** state. If the approved order-at-submit mode is enabled, exactly one native WooCommerce unpaid order is linked to it, visible to the correct Dokan seller and admin, with correct SKU/quantity, totals, seller commission, status and no payment.
- **Other modes:** prepaid drop-off and pickup still require merchant confirmation, county checks, exact address where applicable, native quote review and verified gateway; no early charge, duplicate order or duplicate merchant-trip fee.
- **Notifications:** the customer, actual product seller and configured admin each receive their intended notification after a request, with distinct event/recipient records. Test accepted, failed, bounced (where provider supports), deferred, duplicate and manually retried email states; verify real inbox receipt only with approved test recipients. Mail outage does not erase merchant/admin dashboard visibility.
- **Bot return receipt:** a registered, consented test bot receives the correct **booking.request_received** event and, only when applicable, a separate **woocommerce.order_created** event correlated to the original bot referral. Verify signed payload, 2xx acknowledgement, retries, replay/idempotency, expired/revoked consent, webhook endpoint offline, 4xx/5xx, DNS rebinding/SSRF attempts and no PII leaks. For a bot host without callback support, scoped polling returns the same truthful status.
- **Concurrency and storage:** double-click, POST retry, browser back/reload, worker crash between booking/order commit, stale order status, cancelled booking, classic orders vs HPOS and HPOS synchronization on/off, Dokan vendor permission boundaries, seller disabled, mixed-seller request and refund/Lightning regression. A callback or mail retry must never create another WooCommerce order or charge.
- **Operational evidence:** a permissioned dashboard shows booking↔order link, seller visibility, email and webhook outbox states, retries and reconciliation, with redacted logs; scheduled actions actually run on the deployed host. Include a no-charge rollback and historical-booking recovery procedure.

### 9.6 Research precedents and authoritative references for Codex

These are **implementation precedents**, not claims that they are already configured on KnifeRevive:

- **Current KnifeRevive evidence:** wordpress/kniferevive-agent-commerce/includes/Booking.php (explicit booking-only request, notification code), BookingFrontend.php (human form), ListingCheckout.php (native order hooks and payment verification), docs/booking-release-0.3.0.md and docs/booking-deployment-2026-10-08.md. Compare deployed code and database before deciding what actually happened to this customer.
- **WooCommerce email recipients and events:** https://woocommerce.com/document/configuring-woocommerce-settings/emails/ — New Order mail is an order notification, not a substitute for a separate booking-request email.
- **WooCommerce email troubleshooting and SMTP:** https://woocommerce.com/document/email-faq/ and https://woocommerce.com/document/email-smtp-providers/ — wp_mail uses the host's mail transport; mail acceptance and inbox delivery differ.
- **WordPress wp_mail return contract:** https://developer.wordpress.org/reference/functions/wp_mail/ — true does **not** guarantee receipt; wp_mail_failed and wp_mail_succeeded are available for diagnostics.
- **Dokan vendor order workflow:** https://dokan.co/docs/wordpress/vendor-dashboard/orders/ — sellers normally see and are notified about actual orders of their products; a custom booking Store record is not automatically a vendor order.
- **WooCommerce HPOS CRUD guidance:** https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/ — use WC_Order CRUD, wc_get_orders and WC order metadata methods rather than direct post/meta SQL.
- **WooCommerce webhooks:** https://woocommerce.com/document/webhooks/ and https://developer.woocommerce.com/docs/apis/rest-api/v3/webhooks/ — signed HTTP deliveries, configurable endpoints, delivery logs and failure handling are established patterns. Their standard order webhooks target **registered receiver URLs**, not arbitrary chatbot conversations; KnifeRevive's bot bridge must establish the receiving host's actual capabilities and permissions.
- **Action Scheduler job queue:** https://actionscheduler.org/api/ — use initialized queue APIs for durable background notification delivery and operational monitoring. Action Scheduler is a mechanism, not proof of final email or bot delivery.
- **Skill host boundary:** https://agentskills.io/specification — portable skill instructions do not establish a universal bot inbound webhook, address grant or payment authorization. A host-specific bridge is optional and must be honestly described.

**Implementation PR deliverable:** a concise incident root-cause table (verified code behavior vs observed production evidence vs remaining unknowns), a proposed order-at-submit decision with Dokan test results, the existing-plugin extension design, a minimal bot event/status contract with host-specific support matrix, recipient-specific notification delivery evidence, tests and rollback instructions. **This Markdown file is still instructions only; it authorizes no production order creation, email blast, bot callback registration, payment activation or deployment.**
