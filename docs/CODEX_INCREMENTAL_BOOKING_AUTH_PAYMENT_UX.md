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
