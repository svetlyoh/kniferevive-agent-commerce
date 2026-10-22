# KnifeRevive — New Seller Onboarding: Codex Implementation Specification

**Status:** Implementation brief / not a claim of completed work  
**Prepared:** 2026-10-22  
**Audience:** Codex engineering agent, WordPress developer, QA, marketplace operator  
**Primary goal:** Ship a striking, mobile-first, high-conversion seller onboarding experience with truthful fees, clear promo deadlines, milestone confirmations, and **existing Stripe Express preserved**.  
**Scope:** Seller acquisition landing page + authenticated onboarding hub + integration with existing registration, storefront, shipping/dispatch, Stripe Express, ListLab, commissions, emails, and analytics.

> **Agent mission:** Audit the real deployed source and settings first; implement incrementally in a feature branch; demonstrate in staging; do not change live checkout/fees/payouts until the owner approves the precise fee and promotion policies. Do not present a prototype as production-complete.

---

## 0. Non-negotiable requirements

1. **Keep Stripe Express.** Reuse the existing Stripe Connect Express account-creation/link and hosted onboarding flow. Do not replace it with Stripe Standard, Stripe Custom, manual bank collection, or an additional Stripe account. Do not put bank details or identity documents in WordPress.
2. **Offer:** "NEW MARKETPLACE SELLERS — 0% seller marketplace commission for 6 months from signup." The offer begins at successful eligible seller account creation, **not** first listing, storefront completion, or Stripe completion. Display the exact start and end date/time and timezone after registration and in email.
3. **Do not invent fees.** Find the actual regular seller commission schedule, buyer-paid marketplace fee, payment processing fee payer, shipping/tax treatment, exceptions, and any gateway-specific waivers in live configuration and approved policy. If unavailable, keep the dependent UI behind a launch gate; use labeled placeholders only in staging.
4. **Buyer fee is not seller commission.** The buyer-paid marketplace fee must be shown separately from seller earnings and must not be silently deducted from seller proceeds. Check actual gateway/checkout implementation before promising this behavior.
5. **Existing flows survive.** Do not rewrite WooCommerce, Dokan, Stripe gateway, ListLab, current storefront, buyer checkout, or the separate Agent Commerce adapter. Do not edit vendor/plugin core. Use a standalone integration plugin and small, isolated theme components or a child theme, depending on the audited architecture.
6. **Real completion, not button clicks.** Stripe redirect does not mean payouts ready; opening a form does not mean saved; creating a draft does not mean published.
7. **Every meaningful step gets feedback.** Immediate accessible in-app confirmation; transactional email after major milestones, with deduplication and safe retries. Do not send an email for every minor field edit.
8. **Gen Z copy, grown-up trust.** Fresh, confident, concise; no excessive slang, false scarcity, made-up testimonials, misleading earnings promises, or vague fee language.
9. **No unauthorized deployment.** Work in a feature branch and staging. Record findings, diffs, tests, screenshots, and unresolved owner decisions. Production activation requires explicit owner sign-off.
10. **No retroactive giveaways.** Do not automatically grant the new-seller promo to existing sellers or reset a promo by changing roles, email, store slug, or reactivating an account.

---

## 1. Source-of-truth and mandatory reconnaissance

### 1.1 Read these project materials

Read all available local/project files before coding, including:

- KnifeRevive_Custom_GPT.md
- Download WordPress Site.txt
- Write Codex Instructions.txt
- KNIFEREVIVE_LISTLAB_TECHNICAL_IMPLEMENTATION_REPORT.md
- KNIFEREVIVE_WHATSAPP_SELLER_MESSAGING_IMPLEMENTATION_REPORT.md
- Any newer seller onboarding/current-process documentation and operator fee schedule.
- The site's active WordPress theme, custom plugins, WooCommerce/Dokan/Stripe plugin versions, relevant hooks, database schema, and configured email transport.
- The accessible companion repository: https://github.com/svetlyoh/kniferevive-agent-commerce . Its theme-overlays/kniferevive, wordpress-overlays/kniferevive-listlab, wordpress-overlays/kniferevive-seller-orders, wordpress/kniferevive-agent-commerce, docs, and AI.md may contain related code. **This repo may be only a source overlay, not the complete production site. Do not assume it is a deployable full-site snapshot.**

The supplied 2026-10-22 onboarding-process summary describes: seller registration under /my-account/; custom storefront under /my-account/storefront/; Stripe Express handoff under /my-account/payout-methods/; ListLab under /my-account/listlab/. Verify those paths and behavior in the actual version before wiring them.

### 1.2 Review live public pages and every relevant Help link

- https://kniferevive.com/
- https://kniferevive.com/about/ — **primary visual reference**
- https://kniferevive.com/help/ — inspect Shipping, Listing Instructions, Terms & Policies, Return Policy, Contact, and any child links
- https://kniferevive.com/listing-guide/ — confirm whether its older knife-specific terminology still applies to the current multi-category ListLab
- https://kniferevive.com/my-account/ — registration
- https://kniferevive.com/my-account/storefront/ — authenticated store customization
- https://kniferevive.com/my-account/payout-methods/ — existing Stripe Express
- https://kniferevive.com/my-account/listlab/ — listing creation

Do not claim that signed-in flows have been browser-tested unless a test seller was actually used. Review help/legal copy for shipping responsibilities, condition accuracy, prohibited listings, returns, messaging, tax, and payout claims; use canonical existing URLs in onboarding. If live URLs are unavailable or rate-limited, record the failure and inspect authoritative local source/content exports rather than inventing the content.

### 1.3 Audit report (deliver before implementation)

Create docs/seller-onboarding-audit.md containing:

| Question | Required evidence |
|---|---|
| Where is seller registration handled? | Source file/hook, role assignment, Dokan seller approval rules |
| What timestamp represents completed seller signup? | Server event and stored UTC value |
| How is storefront saved? | Current handler, fields, completion rules |
| Where are dispatch/shipping details saved? | Fields and required validation |
| How is Stripe Express account ID stored? | Existing integration, onboarding link, refresh/return handling |
| What proves payout readiness? | Actual Stripe Connect account fields and existing plugin behavior |
| What creates/publishes ListLab products? | WooCommerce CRUD path, draft/publish hooks |
| How are seller commissions calculated? | Exact active Dokan/WooCommerce hooks, fee rules, gateways, refund logic |
| How is buyer marketplace fee added/waived? | Actual checkout hook/configuration and fee payer |
| How are emails delivered? | SMTP/transactional provider, wp_mail/Action Scheduler, templates |
| Which Help policies are canonical? | Verified URLs and policy versions |
| What are existing seller impacts? | Role, capability, commission and payout compatibility |

**Stop-and-ask gate:** If any of the fee rules, seller eligibility policy, or payout prerequisites are unknown, complete the UI/prototype safely but do not enable money-affecting production logic or publish unverified numerical promises.

---

## 2. Product concept and design direction

### 2.1 Two coordinated experiences

**A. Public acquisition page** (recommended /sell/; check route conflicts before creating): A polished editorial landing page inspired by /about/, with the 0% six-month offer, who can sell, the real steps, a transparent fee explainer, a $30 example, help links, and a strong signup CTA. Existing "Become a Seller" links should point here after verification.

**B. Authenticated onboarding hub** (recommended /my-account/seller-setup/ or a conflict-free equivalent): Personalized checklist with persistent step states, exact promo dates, verified fee calculator, deep links into the existing forms, Stripe status, next action, and the ability to resume later. Returning sellers should see a compact progress card in My Account until done. Existing sellers should not be forced into the new flow.

**Avoid:** One giant registration form, mandatory long bios, modal mazes, a duplicate Stripe wizard, artificial countdowns, dark patterns, and disabling unrelated seller features without an approved policy.

### 2.2 Design system

Inspect the real /about/ page's fonts, typography scale, spacing, color tokens, rounded corners, borders, gradients, photography, image crops, editorial sections, animation rhythm, and responsive breakpoints. Reuse the site's actual design tokens and shared components. Do not guess brand colors or copy /about/ CSS wholesale.

Proposed page anatomy:

1. **Editorial hero:** eyebrow "SELL ON KNIFEREVIVE", headline "Your shop. Your vibe. Your next sale.", prominent "0% seller commission for your first 6 months", a small exact caveat "Clock starts when you sign up", primary CTA "Start selling", secondary "See how it works".
2. **Compact trust strip:** "Set up your shop / Connect Stripe Express / List your first find". Include time estimate only after measured or usability-tested; no fake social proof.
3. **Five visual step cards** with big step numerals, simple iconography, short explanation, clear required/optional labels.
4. **Interactive fee explainer:** seller vs buyer columns, $30 illustration, verified rate schedule and clear exclusions.
5. **Promo clock explanation:** six calendar months from signup, not from first sale; sample exact timestamp labeled as example, and real timestamp once signed in.
6. **Showcase:** storefront preview and actual approved seller/listing imagery only, never fabricated reviews or earnings.
7. **FAQ:** when 0% starts/ends, what Stripe does, what buyer fee means, what happens after six months, shipping/returns, payout eligibility, what can be listed, drafts.
8. **Final CTA:** "Make your shop official" and help link.

Authenticated hub:

- Hero "You're building something good." plus "Step X of 5" and a progress indicator.
- Promotion card with **Starts** / **Ends** / **Time zone** / **Normal commission afterward**; never show "0% forever".
- One prominent next-step CTA; completed steps expandable and editable.
- Optional extras collapsed under "Make it yours (optional)".
- Status vocabulary: Not started / In progress / Needs attention / Done / Waiting for Stripe.
- Sticky mobile bottom CTA with 44–48 px tap target; keyboard navigation, focus-visible, reduced-motion, WCAG 2.2 AA contrast.
- Inline loading/error/success states; reload/revisit must preserve server-confirmed status.

### 2.3 Voice and exact proposed microcopy

| Location | Copy |
|---|---|
| Public hero | **Your shop. Your vibe. Your next sale.** |
| Offer | **0% seller commission for 6 months.** Your clock starts the moment your seller account is created. |
| Primary CTA | **Start selling** |
| Account step | **First things first: make it official.** |
| Storefront step | **Make your storefront yours.** Add a name. Give it some personality. |
| Dispatch step | **Where are your finds shipping from?** Set the basics so buyers know what to expect. |
| Stripe step | **Set up your payouts with Stripe Express.** Secure setup, handled by Stripe. |
| First listing step | **Drop your first listing.** Good photos. Clear details. Ready when you are. |
| Completed state | **You're set. Let's get your first listing seen.** |
| Resume CTA | **Pick up where you left off** |
| Draft CTA | **Save it for later** |
| Stripe action-needed | **One more thing for payouts.** Stripe needs an update before you're fully set. |
| Promo clock | **Your 0% era: [start] → [end].** After that, standard seller commission applies. |
| Buyer fee | **Buyers may see a separate marketplace fee at checkout. That is not your seller commission.** |
| Fee disclaimer | **Your take-home depends on the listed price, standard commission after your promo, and any applicable seller-paid processing, shipping, refunds or adjustments.** |

Avoid slang such as "no cap", "slay", "bet", or "free money" in transactional, financial, identity, or compliance messages.

---

## 3. Seller journey and completion rules

The seller can enter from the public page, a registration link, or My Account. Deep links must return to the hub after save/Stripe handoff where possible, without changing the existing forms' semantics.

| Step | Experience | Mandatory to count done | Optional extras |
|---|---|---|---|
| 1. Create account | Existing seller registration at /my-account/ | Authenticated eligible seller account exists, server-side promo snapshot recorded | None |
| 2. Build storefront | Existing /my-account/storefront/ | Required store name saved and valid; verify exact current requirements | Tagline, cover/avatar, about, style preset, featured items, socials |
| 3. Set dispatch & basics | Existing dispatch form plus links to Shipping/Returns policies | Valid city, US state, ZIP and any other live mandatory fields; seller acknowledges applicable selling policies only if required/approved | Messaging/social links, advanced preferences |
| 4. Connect payouts | Existing /my-account/payout-methods/ | Stripe Express account linked **and actual payout readiness verified** (not redirect alone) | Stripe dashboard link |
| 5. Create first listing | Existing /my-account/listlab/ | First owned eligible listing published, or explicitly label "Draft saved" as in-progress; respect actual review/approval state | More photos, promoted listing, additional items |

**Important:** The steps are a guide, not an unapproved new block on existing capabilities. If sellers can draft before payouts are ready, retain that flexibility. If production rules require payout readiness before publication/checkout, reflect the verified rule in copy and enforcement. Never say a seller is "live" when their listing is only awaiting review or their payouts are restricted.

Registration fields reported in earlier process notes: choose "I am a seller", email/password, first/last name, shop name, shop URL slug, phone, and terms acceptance. Verify current registration schema; do not make the user type information twice. Prefill storefront from signup when safe.

Storefront process notes include store name max 80 characters; optional tagline max 140, about max 1200, cover/photo JPG/PNG/WebP with 8 MB or host limit, cover approx 4:1, focal point, visual preset, technology/art intros and featured listings. Do not require optional styling for completion. Dispatch location notes include city/state/ZIP without street address; preserve privacy.

ListLab process notes describe authenticated seller gate, draft support, required featured image to publish, up to 10 images, category/shipping validation, and WooCommerce CRUD. **Revalidate in code**; do not create a competing product editor. Respect server-side seller ownership, category, shipping, media, and REST initialization constraints.

Completion data must be **derived from authoritative saved objects** and cached only as a convenience. The UI cannot mark a step done by setting a client flag.

---

## 4. Six-month 0% seller commission — entitlement and policy

### 4.1 Proposed precise policy — owner approval required

**Proposed, not verified as existing policy:**

- Eligible only for a **new seller account** created during the active offer; no duplicate/recycled accounts or automatic retroactive grants.
- Start instant = authoritative successful seller-account creation timestamp. Persist UTC and display in the site's configured business timezone, with abbreviation and numeric UTC offset. Capture once, server-side.
- Duration = **six calendar months** from that instant, not 180 days. Define and unit-test month-end clamping. Recommended business timezone: the site's configured timezone (verify whether America/Los_Angeles); persist its IANA identifier in the promo record so later site-setting changes do not silently move a deadline.
- End instant is **exclusive**: promotional commission applies when the approved commission event timestamp is >= start and < end; standard schedule applies at/after end.
- Recommended commission event = verified successful order payment/capture timestamp, not unpaid cart creation. This avoids unbounded pending-order holds but requires clear disclosure. **Owner must approve** the event definition and edge cases before launch.
- Cancellations, refunds, partial refunds, chargebacks, shipping, coupons, multi-vendor orders, and offline/manual payment methods must follow documented approved rules; do not infer from this spec.
- Expired offers never restart on profile edits, account reactivation, or Stripe reconnect. Administrative overrides require a reason, role-gated approval, audit event, and explicit start/end fields.

**Illustrative date only:** Seller signs up October 22, 2026 at 2:37 PM America/Los_Angeles (PDT, UTC−07:00). With a same-local-wall-clock six-calendar-month rule, the promo ends **April 22, 2027 at 2:37 PM PDT**; the promo is no longer active at that instant. Use the actual seller's server-recorded values everywhere. Test a January 31 signup, leap years, and daylight-saving transitions; define a deterministic DST gap/overlap policy before production.

Do not compute six months by multiplying days or using naïve PHP +6 months that rolls an invalid day into the next month. Implement explicit calendar-month addition with day clamped to the target month's last valid day and an agreed DST resolution rule. Store the resulting UTC end instant once.

### 4.2 Data model

Prefer a dedicated plugin-owned table for immutable financial entitlements:

**wp_krev_seller_promotions** (respect actual prefix):
- id bigint PK
- seller_user_id bigint NOT NULL UNIQUE for this offer version, or UNIQUE(seller_user_id, offer_code)
- offer_code varchar (e.g. NEW_SELLER_6MO_2026)
- policy_version varchar
- status enum-like validated value: active/expired/revoked/exception
- started_at_utc datetime(6)
- ends_at_utc datetime(6)
- timezone_iana varchar
- commission_rate_override_bps integer = 0 for eligible promotional orders
- eligibility_source varchar, registration_event_id varchar
- created_at_utc, updated_at_utc, optional admin_override_reason and actor
- unique and supporting indexes

**wp_krev_seller_onboarding_events**:
- id PK, seller_user_id, event_key UNIQUE, event_type, payload_json **minimal non-PII**, occurred_at_utc, email_state, email_attempt_count, last_error_code, processed_at_utc.

**Onboarding state:** derive from authoritative account/storefront/dispatch/Stripe/product records. If a small cached progress row is needed, store schema version, updated timestamp and step state; rebuild when source objects change.

**Order audit:** attach immutable promo ID, policy version, event timestamp, actual seller rate in basis points, fee base and amount (minor currency units), and source fee schedule version to the correct seller order/item/commission record. Multi-vendor carts require seller-by-seller calculation. Preserve WooCommerce/Dokan accounting integrity and HPOS compatibility.

Use transactional unique insert or database uniqueness to prevent duplicate promo grants under concurrent registration callbacks. Do not trust browser-provided dates/rates. Avoid altering old orders or existing seller records.

### 4.3 Fee engine integration

1. Find the **single authoritative existing commission calculation path**. Audit Dokan version, vendor overrides, tax/shipping/coupon treatment, order status timing, split-order behavior, and gateway interactions. Do not assume a hook name without source proof.
2. Apply 0% only to the **eligible seller's seller marketplace commission** during the approved period. Do not globally set Dokan commission to 0%.
3. Record the applied rate at the approved financial event and make refunds/adjustments use the original recorded rate, not today's rate.
4. Prevent duplicate commission adjustments when webhooks, order hooks, retries, or manual status changes repeat.
5. Buyer marketplace fee, tax, shipping, payment processor fees, and optional seller services are separate amounts. No accidental double fee or fee charged on the buyer fee itself unless explicitly documented.
6. Reconcile the public claim of buyer marketplace fee waiver for Bitcoin Lightning against live code/policy; do not alter existing Lightning checkout.
7. Compare a synthetic $30 item before/after promo in a real staging WooCommerce order, including Stripe Express test account and refund, before turning on any financial flag.

---

## 5. Fees and the $30 example

### 5.1 Fee configuration — never fabricate

Build a read-only **fee facts provider** that reads the verified approved settings/rules or a versioned admin-approved policy configuration:

- regular seller commission rate(s) or tiers and their applicability;
- seller commission calculation base (item subtotal? discounted price? shipping/tax excluded?);
- buyer-paid marketplace fee rate/flat amount/min/max and calculation base;
- whether payment processing is charged to seller, buyer, or KnifeRevive and any fixed fee;
- whether seller-paid labels, shipping subsidies, refunds or other fees affect net;
- any payment-method-specific waiver and whether it is current.

If a rate is unavailable, display "Standard seller commission: see current seller fee policy" with a working link, and disable numerical after-promo claims. Do not quietly substitute a hypothetical rate into production copy.

### 5.2 Seller earnings example

Use a **$30 item price** with zero shipping/tax for the simplified illustration. Separate two columns:

| Line | During eligible 0% period | After promo |
|---|---:|---:|
| Item sold | $30.00 | $30.00 |
| Seller marketplace commission | $0.00 | −(approved rate × approved commission base) |
| Seller-paid processing (if any) | −actual seller share | −actual seller share |
| Seller-paid shipping/other adjustments (if any) | −actual amount | −actual amount |
| **Seller proceeds before other adjustments** | **$30.00 if no other seller-paid costs** | **$30.00 − regular seller commission, before other seller-paid costs** |

Below, clearly state: "A buyer-paid marketplace fee is charged to the buyer, not subtracted as seller commission. Your final payout can differ if you pay processing, shipping or other approved charges." Use actual cents/rounding from WooCommerce/Dokan when a real rate is known. Do not imply $30 lands in the bank if seller bears additional costs.

**Buyer-side example:** item $30.00 + buyer marketplace fee calculated from the verified schedule + applicable shipping/tax = checkout total. Label payment-method exceptions. Never invent a dollar value for the buyer fee.

If the business owner approves a regular commission of R% (not asserted here), then for an item-only $30 commission base the after-promo seller commission is $30 × R/100. Compute from real rate; do not publish R as a placeholder percentage.

### 5.3 Promo display

After signup show:

- **0% seller commission starts:** exact date, time, zone and offset.
- **0% seller commission ends:** exact date, time, zone and offset, clearly saying "exclusive — standard commission applies at this time".
- **After your promo:** verified standard rate/tier, policy link and example.
- **Buyer marketplace fee:** separately labeled buyer charge with verified rules.
- **Payouts:** Stripe Express onboarding/status, plus explicit note that funds timing and eligibility depend on Stripe and site policy.

Repeat consistent timestamps in the welcome email, onboarding dashboard, promo reminders, and relevant seller order breakdowns. Prefer absolute dates over a countdown alone.

---

## 6. Stripe Express integration — preserve existing setup

Use /my-account/payout-methods/ as the canonical payout destination unless audit finds a newer route. The onboarding hub should **deep-link or invoke the existing Connect with Stripe action**, not create its own onboarding session or duplicate Stripe Express account.

State machine:

- not_connected — no verified existing Express account
- setup_incomplete — connected account exists, requirements not complete
- action_required — Stripe reports requirements/past-due/restricted capability
- review_pending — Stripe is reviewing; do not promise payouts
- ready — actual relevant payout/transfer capabilities verified according to existing integration
- temporarily_unavailable — API failure/unknown; retain last known state with timestamp, do not mark ready

Inspect current Stripe Connect account data: details_submitted, payouts_enabled, charges_enabled where applicable, capabilities, requirements.currently_due/past_due, and the site's actual transfer architecture. Not all fields have the same meaning for every Connect charge type; define readiness using verified existing seller settlement design.

On return from Stripe, retrieve fresh account status server-side. If a webhook is available, verify its signature and environment, handle account.updated idempotently, and reconcile on page load as fallback. A successful browser redirect is **not** a verified completed Stripe account. Preserve Express dashboard access.

Never expose Stripe secret keys, identity documents, bank account details, or unmasked payout information in browser HTML, analytics, email, or logs. Existing Stripe keys and webhook endpoints stay untouched unless a change is proven necessary and approved. Do not change the separate Agent Commerce operator-service Stripe checkout path.

---

## 7. Email journey and confirmation logic

### 7.1 Delivery architecture

Use existing transactional email provider and WordPress/WooCommerce templating conventions. Prefer Action Scheduler if installed, with durable jobs and idempotent event keys; provide safe fallback if not. Store send state and attempts; retry transient failures with bounded exponential backoff; surface permanent failures to admin.

Events:

| Event | Trigger | Confirmation |
|---|---|---|
| seller_registered | Eligible seller account created + promo snapshot committed | Immediate in-app success and welcome/account email |
| storefront_completed | Required storefront data saved and valid first time | In-app toast + one milestone email |
| dispatch_completed | Required dispatch basics saved and valid first time | In-app toast + one milestone email |
| stripe_started | Existing Stripe handoff successfully initiated | In-app status; optional one helpful email if abandoned |
| stripe_ready | Verified Stripe state transitions to ready | In-app status + payout-ready email |
| stripe_action_required | Stripe requirements actually demand action | In-app warning + actionable email, rate-limited |
| first_draft_saved | First seller-owned draft persisted | In-app confirmation; email optional (avoid noise) |
| first_listing_published | First seller-owned eligible listing truly published | In-app celebration + listing link email |
| promo_30d/7d/1d | Scheduled relative to stored end instant | Helpful reminder; prevent duplicates |
| promo_expired | Actual end instant reached | Standard-rate notice with verified fee policy |

Do not send duplicates on repeated saves/webhooks. Reevaluate eligibility/status before each queued email. Never email passwords or private Stripe URLs; use authenticated site links. Respect transactional vs marketing distinctions and user communication preferences/law. Send account verification or password setup through the existing secure WordPress flow if applicable; do not fake "verified" status.

### 7.2 Welcome email — approved copy draft

**Subject:** Welcome to KnifeRevive ✨ Your 0% seller commission starts now

Hi [first name],

**Your KnifeRevive seller account is in. Let's make your shop yours.**

Your first **6 months of seller marketplace commission are 0%** — and the clock starts from your signup, not your first sale.

**Your promo dates**  
Starts: [exact start date/time, zone and UTC offset]  
Ends: [exact end date/time, zone and UTC offset]  
After that: [verified regular commission rate/tier and policy link]

**Here's your game plan:**
1. **Make your storefront yours** → [authenticated storefront link]
2. **Set your shipping/dispatch basics** → [authenticated setup link]
3. **Connect payouts with Stripe Express** → [authenticated payout-methods link]
4. **Drop your first listing** → [authenticated ListLab link]

Track your progress anytime → [authenticated onboarding hub]

**The fee basics:** Your promo removes KnifeRevive's **seller marketplace commission** on eligible sales. Buyers may pay a separate marketplace fee at checkout. Other costs, such as seller-paid processing or shipping, may still apply. See the full fee breakdown → [verified fee policy link].

Need a hand? [Help/contact link]

See you in the marketplace,  
**Team KnifeRevive**

### 7.3 Other email copy requirements

- **Storefront complete:** "Your shop has a look now. Next: set your dispatch basics." Link to next step.
- **Dispatch complete:** "Shipping basics? Done. Next: connect Stripe Express."
- **Stripe ready:** "Payout setup: good to go. Time to list your first find." Do not promise bank deposits on a specific date.
- **Stripe action needed:** "Stripe needs one more update. Finish it securely in Stripe Express." No sensitive details.
- **First listing live:** "Your first listing is live. Nice work." Link only to actually public listing; if awaiting review, use "Submitted for review" instead.
- **30/7/1 days before end:** "Your 0% seller commission ends [exact date/time]. Standard seller commission applies afterward: [verified schedule]." Include actual fee example.
- **Expired:** "Your 0% period has ended as of [exact time]. Here's your current commission schedule." Never change historical completed-order commission records.

Templates must include accessible plain-text fallback, mobile-friendly HTML, sender identity, correct From/Reply-To, real authenticated links, and previews in WP admin/staging. Test SPF/DKIM/DMARC configuration without altering DNS unless explicitly approved.

---

## 8. Technical implementation architecture

### 8.1 Code organization

Preferred: a small dedicated plugin, e.g. wp-content/plugins/kniferevive-seller-onboarding/:

~~~
kniferevive-seller-onboarding/
  kniferevive-seller-onboarding.php
  includes/
    class-installer.php
    class-eligibility.php
    class-promo-clock.php
    class-promotion-repository.php
    class-commission-adapter.php
    class-fee-facts.php
    class-onboarding-status.php
    class-stripe-express-status.php
    class-rest-controller.php
    class-email-events.php
    class-email-templates.php
    class-scheduler.php
    class-analytics.php
    class-admin-settings.php
  templates/
    sell-landing.php
    seller-onboarding.php
    email/
  assets/
    css/seller-onboarding.css
    js/seller-onboarding.js
  tests/
  README.md
~~~

Adjust to actual project conventions. Keep public page design integrated with active theme, not a standalone inconsistent design. Register/enqueue assets only on target pages. Do not overwrite active theme files without a clean, reviewable overlay/diff. Avoid plugin dependencies that are not already approved.

### 8.2 Integration and hooks

Discover actual versions and callback ordering. Hook into:

- verified seller registration completion (after user creation and seller role/approval classification);
- existing storefront save success;
- dispatch save success;
- existing Stripe Express status/return/webhook integration;
- ListLab draft/publish transition for seller-owned WooCommerce products;
- existing commission calculation and WooCommerce order payment/refund lifecycle;
- My Account navigation and public seller CTA routing.

**Do not guess hook names.** Document the chosen hooks, priorities, and source paths in the audit. Do not register REST routes at plugin file load; register on rest_api_init. Reuse WooCommerce CRUD and HPOS-safe APIs. Do not mutate products with raw post SQL.

### 8.3 REST and state

Suggested namespace: kniferevive/v1/seller-onboarding, with endpoints only as needed:

- GET /status — authenticated seller, own progress, exact promo times, sanitized fee facts, next step, safe Stripe readiness label.
- POST /events/resume — only for explicitly non-financial UI actions if necessary; never accept client "completed" claims.
- GET /fees/example?item_price_minor=3000 — server-calculated from verified fee facts, currency and policy version; no PII.
- POST /admin/reconcile — manage_woocommerce or narrower capability, nonce/authenticated, auditable and rate-limited; staging first.

Every private response: authorization, current-user ownership, no-store cache headers, safe serialization, input validation, appropriate nonces for cookie-authenticated writes, CSRF protection, and nonenumerating errors. Rate-limit abuse. Do not expose seller identity, Stripe IDs, bank data, secrets, or private links to guests.

### 8.4 Onboarding and Stripe status freshness

The hub reads source-of-truth on initial load and refreshes relevant states after an action. Avoid aggressive Stripe API polling; use webhooks, short-lived server cache, and manual "Check status" with throttling. Handle API outage as unknown, not complete or failed.

Registration welcome email is queued only after promo row creation commits. If mail fails, account and promo remain valid; admin can retry idempotently.

### 8.5 Admin controls

Create a restricted admin screen for:
- onboarding feature flags;
- verified fee policy version, link and read-only observed settings;
- promo policy version, eligibility rules and owner-approved effective date;
- email templates/previews and test send to approved admin address;
- queue health and delivery errors (without PII);
- aggregate funnel stats;
- seller lookup and audited promo correction/revocation, with explicit confirmation and reason.

**Do not let a casual UI toggle silently rewrite the real commission engine.** Require policy verification and an explicit production financial feature flag.

### 8.6 Feature flags

At minimum:
- seller_onboarding_public_page
- seller_onboarding_hub
- seller_onboarding_emails
- seller_promo_enrollment
- seller_promo_commission_override
- seller_fee_calculator
- seller_onboarding_analytics

Default all money-affecting flags **off** until approved and tested. Flag changes should be logged.

---

## 9. UX behavior and conversion improvements

- One clear next action per screen; progressive disclosure.
- Prefill existing account/shop name; avoid reentry.
- Save independently per current form, resume across devices.
- Allow optional storefront design extras to be skipped without false "incomplete" warnings.
- Explain *why* Stripe needs information; redirect to Stripe's own secure UI.
- Make payout state actionable with a safe "Continue with Stripe" CTA.
- Show an actual preview of the seller's storefront, when possible.
- Offer a first-listing checklist: clear main photo, honest condition, category, price, shipping, returns, stock. Adapt to multi-category listings; do not force knife-only attributes on art/technology/other items.
- Provide Help/Contact links at moments of friction, not only in footer.
- On registration errors preserve nonsecret entered data and clearly identify the problem.
- On mobile, keep labels above inputs, show short explanations, avoid giant illustrations that push CTA below the fold.
- Do not show unverifiable social proof, fake seller counts, guaranteed sales, or inflated earnings.
- Do not use countdown timers that imply the offer starts at first visit; actual promo countdown exists only after account creation.

### Measurement

Record privacy-respecting first-party funnel events without PII or sensitive Stripe fields:
seller_landing_view, seller_cta_click, seller_registration_started, seller_registered, storefront_completed, dispatch_completed, stripe_connect_started, stripe_ready, listing_draft_created, first_listing_published, onboarding_completed, onboarding_resume, onboarding_error, promo_expiry_notice_sent.

Metrics: landing→registration conversion; registration→first listing at 1/7/30 days; registration→Stripe ready; per-step abandonment and median time; promo awareness; email deliverability; support contacts; payout setup error rate. Use consent/privacy requirements and event dedupe. A/B test headline or CTA only after baseline is established; never A/B test undisclosed financial terms.

---

## 10. Test matrix and acceptance criteria

### 10.1 Unit tests

- Eligible new seller receives exactly one promo row; existing seller, buyer, repeated callback, concurrent registration and role toggle do not create unintended grants.
- Exact start/end UTC, six-calendar-month arithmetic, Jan 31 → target-month clamp, leap day, timezone changes, DST spring gap/fall overlap, inclusive start/exclusive end.
- Rate boundary: event 1 microsecond before end qualifies; at end does not.
- Promo expires without changing historical order fees.
- $30 example correct under actual approved fee facts and integer-cent rounding.
- No buyer marketplace fee included in seller commission override or seller earnings deduction.
- Email dedupe by event key and retries after transport failure.
- No completion from client-supplied "done" flags.

### 10.2 Integration tests

- Seller registration with actual Dokan role/approval path.
- Storefront and dispatch save handlers with real required fields.
- Existing Stripe Express account reused; no second Connect account; return with incomplete requirements stays incomplete; account.updated ready/action-required transitions.
- ListLab draft versus published versus pending-review versus deleted listing.
- Existing seller account unaffected and old dashboard links still work.
- WooCommerce legacy orders and HPOS, multi-vendor carts, coupons, shipping, tax, split payouts, order cancellation, full/partial refunds, disputes, and payment-method differences.
- Buyer marketplace fee remains buyer-paid under current checkout rules; Lightning exception remains intact if actually configured.
- Agent Commerce service checkout and native marketplace checkout unchanged.
- Registration email arrives once with exact dates; subsequent milestones once; expiry reminders correct.
- Broken SMTP/Stripe API handled without losing state or granting false readiness.
- REST guest access blocked; seller A cannot read seller B; CSRF/XSS/SQL injection defenses.
- No passwords, bank data, Stripe secrets, signed links, or sensitive tokens in logs/email/analytics.

### 10.3 Visual/accessibility tests

Desktop 1440px, tablet 768px, mobile 390px and 320px; Safari/iOS, Chrome/Android, Firefox; keyboard-only, screen reader, reduced motion, zoom 200%, high contrast. Verify style matches /about/ without CLS/overflow. Accessible progress labels, form errors and status messages. Check Core Web Vitals and asset load only on target routes.

### 10.4 Owner acceptance walkthrough

1. Visit /sell/ logged out; see 0% offer and clear six-month-from-signup caveat.
2. Register test seller; receive welcome email; exact dates match dashboard and DB.
3. Complete storefront; see confirmation and next step.
4. Complete dispatch; see confirmation and next step.
5. Connect existing Stripe Express test account; incomplete state does **not** falsely pass; after verified readiness step completes.
6. Save first ListLab draft; not called live. Publish or submit for review; status reflects actual publication.
7. View $30 example during promo and after promo with verified current rates; buyer fee is separate.
8. Simulate end boundary and paid order/refund; commission ledger is correct and idempotent.
9. Receive reminders with accurate timestamps; existing seller account remains unaffected.
10. Disable flags; original flows remain operational and no historical fees are recalculated.

---

## 11. Delivery phases and stop gates

**Phase 0 — Audit:** source map, screenshots of current About/Help/registration, fee schedule verification, Stripe Connect architecture, risk register. **No code affecting payments.**

**Phase 1 — Visual prototype:** public /sell/ page, onboarding hub layout, fee explainer with clearly nonproduction fixtures, responsive/a11y tests. Show screenshots and get copy/design approval.

**Phase 2 — Real progress:** server-derived statuses for account/storefront/dispatch/ListLab and deep links, safe resume, feature flags.

**Phase 3 — Stripe Express:** integrate read-only verified account status and existing Connect action; test hosted onboarding, refresh, action-required and return behavior.

**Phase 4 — Promo and financial integration:** only after written owner approval of eligibility, time rule, normal fee schedule, buyer fee, processing payer, commission event and refund semantics. Implement immutable entitlement + actual commission adapter + order audit + staging financial reconciliation.

**Phase 5 — Email automation:** welcome, milestone, Stripe action needed, listing live, 30/7/1-day and expired; idempotent queue, previews, deliverability.

**Phase 6 — Controlled rollout:** staging full test suite, owner review, limited rollout, dashboards/alerts, documented rollback. Do not publish to production on your own.

### Required output from Codex

Deliver:
1. docs/seller-onboarding-audit.md with evidence and unresolved decisions.
2. Working code and migrations in reviewable commits.
3. docs/seller-onboarding-architecture.md with hook map, schema, state diagram, Stripe Express reuse and fee accounting.
4. docs/seller-onboarding-fee-policy.md containing approved exact schedule and examples **only after verified**.
5. docs/seller-onboarding-email-copy.md with final templates/triggers.
6. docs/seller-onboarding-qa.md with commands, results, screenshots, failures, and acceptance signoff.
7. docs/seller-onboarding-rollout.md with feature flags, monitoring and rollback.
8. Short final summary of what is implemented, what was tested, what remains blocked, and exact files changed.

---

## 12. Owner decisions required before financial launch

Do **not** silently resolve these:

1. **Eligibility:** New seller registrations only? Buyer-to-seller conversions? Seller accounts awaiting admin approval? Fraud/duplicate account treatment?
2. **Promo timezone and arithmetic:** Which IANA timezone defines the six-calendar-month deadline? Confirm month-end clamp and DST ambiguity rule.
3. **Qualifying order event:** Paid/captured time (recommended), order-created time, or other? How are delayed/offline payments handled?
4. **Standard seller commission:** Exact rate/tier/minimum/maximum, commission base, and versioned policy URL.
5. **Buyer marketplace fee:** Exact rate/flat/minimum/maximum, calculation base, payer, refunds, and any Lightning waiver.
6. **Payment processing:** Who bears Stripe processing/Connect costs and whether any amount is passed through to sellers.
7. **Refunds and cancellations:** Which fee reversals apply, how partial refunds/disputes are handled, and what happens to promo eligibility on replacement orders.
8. **Seller go-live gate:** Must Stripe be fully ready before a listing can publish, or only before checkout/payout? Preserve current behavior until approved.
9. **Email reminders:** Approve 30/7/1-day schedule, sender identity, support address, and communication policy.
10. **Public claims:** Approve wording, eligible categories, prohibited items, shipping/returns summary, and exact Help links.

**Default:** If an answer is unknown, do not fabricate it. Implement the nonfinancial onboarding safely, label the missing policy, and request owner signoff.

---

## 13. Source notes and provenance

**Confirmed from accessible repository source (2026-10-22 inspection):** https://github.com/svetlyoh/kniferevive-agent-commerce has theme overlays, ListLab overlays, WooCommerce-related custom code and documentation. Its README explicitly separates the Agent Commerce operator-service Stripe checkout from existing native marketplace seller payment hooks. It also warns that repository overlays may not be complete deployable runtime packages.

**Public site evidence:** https://kniferevive.com/help/ currently surfaces Shipping, Listing Instructions, Terms & Policies, Return Policy, and Contact. https://kniferevive.com/listing-guide/ contains a knife-specific older listing guide. https://kniferevive.com/technology-trade-desk/ publicly advertises a buyer marketplace fee waiver for Lightning, but exact general fee amounts were not verified in this brief. Reconcile with active checkout/policy before publishing claims.

**Provided project context, requiring code confirmation:** New seller registration at /my-account/; storefront at /my-account/storefront/; payout setup at /my-account/payout-methods/ with Stripe Express; custom ListLab at /my-account/listlab/; optional storefront styling and messaging; no established single enforced onboarding sequence. These are implementation inputs, not substitutes for an audit.

**UX inspiration:** https://kniferevive.com/about/ (brand style), with quick-listing/progressive-disclosure patterns commonly used by peer marketplaces such as Mercari and Etsy. Do not claim an exact current competitor process without independently reviewing its latest official documentation.

**Policy status:** 0% seller commission for six months from signup is the owner's stated offer. Precise cutoff mechanics, standard commission, buyer fee and processing responsibility are **not verified here**. All proposed implementation rules require owner approval.

---

## 14. Final Codex instruction

Start by reporting the real source paths, plugin versions, fee settings, Stripe Express integration, and open policy decisions. Then implement **Phase 1 and Phase 2** without touching live money flows. Build the full solution in small, testable, reversible increments. Keep seller setup fast, beautifully branded, accessible, and honest. Preserve Stripe Express. Show exact promo start/end timestamps. Use verified fee data. Make every meaningful milestone feel rewarding and obvious. **Never mark a seller ready, a listing live, or a commission waived without authoritative server-side evidence.**
