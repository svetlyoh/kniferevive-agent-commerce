# KnifeRevive — Buyer-to-Seller Messaging Adoption
## Codex implementation instructions: WhatsApp, Telegram, seller trust, and consistent product-page placement

**Document type:** actionable implementation specification for a Codex coding agent  
**Target:** KnifeRevive WordPress + WooCommerce + Dokan marketplace  
**Status:** instructions only; NOT a claim that code is deployed or that live pages were successfully inspected  
**Repository:** `svetlyoh/kniferevive-agent-commerce`  
**Priority:** P0 diagnosis, P1 consistent CTAs, P2 trust and seller onboarding, P3 measurement and optimization  
**Non-negotiable:** Preserve native WooCommerce checkout, Dokan seller ownership, existing ListLab functionality, and the existing seller-to-buyer Order Messenger. Never redirect buyers to off-platform payment.

---

## 0. Mission and product decision

**Build the feature, not just a proposal.** Diagnose the missing buttons, implement a coherent mobile-first buyer-to-seller contact experience, write tests, verify on staging, and prepare a reversible release. Do not deploy to production without explicit owner approval.

The default design is:

1. **Product detail (highest priority):** a clearly labeled **Message seller** secondary CTA in the purchase decision area, visually close to Add to Cart / Buy but not replacing or overpowering secure checkout. Show it for eligible, opted-in sellers regardless of whether a WooCommerce add-to-cart form is present. Include a seller identity panel nearby.
2. **Shop/product grids and carousels:** replace two competing WhatsApp/Telegram buttons with **one compact Message seller** CTA on eligible product cards. Keep the entire product title/image navigation and existing Add to Cart behavior intact. Do not nest buttons/links inside a card-wide anchor.
3. **Seller storefront:** a generic **Contact seller** action in the storefront header or seller-information area, for questions not tied to a product. On a product detail page, a secondary text link inside the seller panel may trigger/scroll to the SAME message chooser; do not create multiple visually dominant duplicate contact buttons.
4. **Channel choice:** when both WhatsApp and Telegram are available, a single accessible chooser offers both. When only one is enabled, the CTA can open that channel directly (or use a one-option chooser only if needed for a consistent trust warning). When neither is available, hide the contact CTA entirely; never show a dead link.
5. **Trust:** display real seller/store identity and clear safe-buying guidance, not invented verification badges, fabricated response rates, or implied KnifeRevive control of external chats. Buyers must know that purchases and payments should remain on KnifeRevive.
6. **Measurement:** record *visible CTA impressions*, CTA activations, channel selections and external-link handoffs. Do not report these as messages sent, replies received, or sales caused by messaging; those outcomes cannot be inferred from external-app clicks.

This is the initial design hypothesis, not a proven conversion winner. Make the layout configurable and instrument it so a later experiment can compare variants.

## 1. Mandatory discovery — inspect before editing

Inspect the actual site workspace, installed plugin sources, current templates, active theme, and staging environment. Do not assume the checked-in repository is a complete production snapshot.

Known repository paths from the accessible source tree:

- `theme-overlays/kniferevive/functions.php` and the child-theme overlays: potential integration points, not proof of production deployment.
- `wordpress-overlays/kniferevive-listlab/`: ListLab-related implementation; preserve its listing/editor flows.
- `wordpress/kniferevive-order-messenger/`: **seller-to-buyer transactional order email**, a separate feature. Do NOT repurpose, rename, or alter its order authorization, recipient rules, audit tables or email UI for this task.
- `wordpress/kniferevive-agent-commerce/` and `wordpress/kniferevive-listlab-import/`: unrelated checkout/import contracts that must not regress.
- Existing `docs/KNIFEREVIVE_MY_MONEY_CODEX_IMPLEMENTATION.md` demonstrates the project's discovery-first implementation-brief convention.

The previously supplied WhatsApp implementation summary describes seller opt-in and phone storage in Dokan settings, seller resolution via product author, rendering through `woocommerce_after_add_to_cart_form`, and no server-side click analytics. **Treat those as leads to verify against the actual installed code, not guaranteed current behavior.** The Telegram integration and current shop-card source are not yet verified.

Produce `docs/BUYER_SELLER_CHAT_DISCOVERY.md` with:

- Active versions of WordPress, WooCommerce, Dokan, theme/child theme, ListLab, WhatsApp plugin, Telegram plugin or theme code, and caching/minification plugins.
- Exact plugin paths, class/function names, database/meta keys, setting names, hooks, template overrides, shortcode/block integrations, and CSS/JS that currently render contact controls.
- Whether /shop/ is a standard WooCommerce loop, custom template, ListLab component, AJAX grid, carousel, or mixture. Trace all product-card types, including related products and mobile sliders.
- The real seller ownership source for a product, including variations, vendor account status, unpublished/hidden products, migrated listings and products owned by administrator accounts.
- Which sellers opted into WhatsApp/Telegram and which channels are valid. Inspect with administrator authorization; **never paste seller phone numbers, private tokens or customer information into reports or logs**.
- Current placement and DOM of Add to Cart, seller panel, seller storefront header, and any theme overrides; note any missing WooCommerce action hooks.
- The site's existing analytics stack, consent rules, caching/CDN behavior, and staging/deployment/rollback procedure.
- Baseline screenshots or DOM evidence for desktop and 390px mobile on the three reference pages, with a precise note if a live page is inaccessible.
- Whether the deep-research comparison report has arrived; use it as a review input if available, but do not block engineering on an unavailable report or claim to have read it.

**Reference URLs (must be tested):**

- `https://kniferevive.com/shop/`
- `https://kniferevive.com/shop/art/jordan-ivanov-abstract-modernist-expressionist-painting-crescent-wound-study-in-absence/`
- `https://kniferevive.com/shop/technology/tech-ssd-drives/micron-1100-256gb-m-2-2280-sata-iii-ssd-mtfddav256tbn-cisco-oem/`

For EACH reference product, capture a private diagnostic table: product ID/type/status, canonical seller ID and seller status, contact opt-in flags, presence/validity of each channel (boolean only), relevant template and hooks, rendered CTA count, console errors and cache behavior. Determine whether missing buttons are intentional eligibility behavior or a rendering bug. **Do not make up contact information or silently opt a seller in.**

Investigate these likely failure modes independently:

- Seller opted out, no valid number/username, or store disabled.
- Product `post_author` differs from Dokan's authoritative current seller, or a migrated/admin-owned listing lacks a valid seller.
- The existing `woocommerce_after_add_to_cart_form` hook never fires for a theme override, external/grouped/variable/out-of-stock product, block template, or product without a form.
- Theme CSS hides the control; JS removes it; caching serves stale HTML; mobile-only layout moves it offscreen.
- Telegram is injected only on archive cards, while WhatsApp is injected only on single-product forms.
- A custom carousel duplicates, omits or lazy-loads the relevant DOM.

**Discovery gate:** no implementation changes until the root cause and source-of-truth settings are documented.

## 2. Implementation architecture

Prefer **one canonical contact resolver and one rendering system** reused across product detail, shop cards, carousels and storefronts. Reuse the installed WhatsApp plugin's seller settings and public contract where safe. Avoid a second competing store of phone numbers or opt-in flags.

Decision after discovery:

- If the existing buyer-to-seller contact plugin is maintainable and its source is available, extend/refactor it in place with tests and backward-compatible setting migration.
- If the implementation is scattered in theme snippets or inaccessible, propose an isolated, namespaced companion plugin (suggested repo path: `wordpress/kniferevive-seller-contact/`) and explicitly disable only the old duplicate renderers after migration. **Do not activate two competing renderers.**
- Avoid editing WordPress/WooCommerce/Dokan core, payment code, seller orders, or the independent Order Messenger.
- Follow repository PHP 8.2+ conventions where applicable; check the site's actual minimum requirements first.

Suggested internal interfaces (adapt names to existing code):

- `Contact_Resolver::for_product(WC_Product $product)` returns an eligibility result with authoritative seller ID, public store name/link, supported channels and diagnostic reason code. A variable product uses the parent seller; never trust a seller ID supplied by the browser.
- `Contact_Resolver::for_seller(int $seller_id)` returns storefront eligibility only after active seller and opt-in checks.
- `Contact_Renderer::product_detail(...)`, `::product_card(...)`, `::storefront(...)` and `::channel_chooser(...)` share the same resolved data and accessibility behavior.
- `Contact_Analytics` records privacy-preserving events only when enabled and consent requirements are satisfied.
- `Contact_Admin` provides feature flags and authorized diagnostics without leaking private seller settings.

Use filters/actions for customization. Keep templates/CSS/JS scoped to the feature, loaded only on relevant pages. Support a no-JavaScript fallback. Avoid duplicate controls from both WooCommerce hooks and a custom ListLab renderer. Treat theme/block templates as separate integration cases rather than assuming a single hook covers all.

## 3. Exact UI requirements

### 3.1 Product detail

Desktop reading order:

1. Product name, price, stock/condition and primary Add to Cart / Buy.
2. A visible, keyboard-focusable **Message seller** secondary action in or immediately adjacent to the purchase block.
3. Compact seller trust panel: **Sold by [actual store name]**, link **Visit seller store**, and only verified/grounded details.
4. Under/near the contact action, concise disclosure: **Ask questions on WhatsApp or Telegram. For your protection, pay only through KnifeRevive checkout.**

For layouts where the add-to-cart form is missing, keep the contact CTA visible when the seller is eligible; do not attach it exclusively to `woocommerce_after_add_to_cart_form`. Choose an appropriate WooCommerce summary hook (often around `woocommerce_single_product_summary` after the purchase action), or a template/block-specific slot, after inspecting the actual theme. Ensure it works for out-of-stock listings without implying availability.

On narrow screens, keep the action within the first practical decision area. If a sticky purchase bar already exists, consider a two-action sticky bar (**Buy** / **Message**) without covering product content, browser UI, cookie controls or the keyboard. Do not add a second sticky contact control if the theme already supplies one. Respect iOS safe-area insets.

### 3.2 Shop grid, search, carousels and related products

Replace the two separate branded buttons with one small, recognizable text CTA: **Message seller** (optional chat icon). Position it consistently beneath the product price/stock area or alongside existing product actions, depending on the actual card structure. It must be visible without hover on touch devices.

Do not:

- Add two large WhatsApp/Telegram buttons to every dense card.
- Break the product-card click target, Add to Cart, variation selector, slider swipe gestures or lazy loading.
- Duplicate a CTA when cards are cloned by a carousel.
- Show the CTA for an ineligible seller or create an empty placeholder.

Card CTA may open the same channel chooser directly, retaining the specific product context. Use one implementation path for desktop and mobile; keep the UI responsive. If there is only one channel, a direct link is acceptable.

### 3.3 Seller storefront and product seller panel

Add **Contact seller** in the seller storefront header/info section for sellers with an enabled valid channel. This is a generic store-level conversation. Prefill the store name only; do not attach an unrelated product URL.

The product-page seller panel should link to the store and may have a smaller **Contact seller** text action using the *same* product-context chooser as the primary detail CTA. Avoid competing visual priorities.

### 3.4 Channel chooser

When both channels are enabled, use an accessible dialog on desktop and bottom sheet on mobile:

**Message [store name]**

- **WhatsApp** — Open chat in WhatsApp
- **Telegram** — Open seller chat in Telegram

Below choices: **You are leaving KnifeRevive to chat. Never send payment or card details in messages. Complete purchases through KnifeRevive checkout.**

Behavior:

- Accessible name/role, focus management, Escape and close action, keyboard activation, clear focus outline, at least 44×44px targets (48px preferred), 16px+ form text, no hover-only interactions.
- Works with screen readers, reduced motion and no-JS fallback.
- No forced account creation or login to open external channels.
- Use HTTPS links and `target="_blank" rel="noopener noreferrer"` where appropriate; preserve sensible mobile app handoff.
- No deceptive **Verified seller**, **Online now**, **Usually replies in 5 minutes**, **Safe chat**, or **KnifeRevive protected conversation** claims without evidence.

## 4. Link generation and data handling

**WhatsApp:** only use an explicitly opted-in, validated international number. Prefer canonical E.164 validation with a reputable phone-number library where already available; do not guess missing country codes. Build the public link with digits only: `https://wa.me/15551234567?text=...`, using proper URL encoding. The number in this example is fictional; never hardcode it. WhatsApp supports prefilled text but **opening the link does not prove the buyer sent it**.

Default product text, dynamically escaped/encoded from real WooCommerce data:

> Hi, I saw “[product title]” on KnifeRevive and have a question about it: [canonical product URL]

Keep the text short, non-promotional and free of buyer personal data. Respect variation/product canonical URL and never embed a private preview/checkout/session token.

**Telegram:** accept only a validated public seller username/approved deep-link type discovered in the existing implementation. Prefer `https://t.me/USERNAME` for a username. Do **not** use a Telegram share URL as though it opened a specific seller conversation. Do **not** promise automatic product-message prefill for Telegram; if unsupported, provide a clearly labeled **Copy product link** or **Copy question** affordance in the chooser.

**Privacy:** sellers must explicitly consent to public messaging links. A public WhatsApp link inherently exposes the seller's contact number; explain that to sellers at opt-in. Never publish private email, personal data, order data, phone number lists or raw chat content through analytics, REST responses or debug logs. Escape output, sanitize settings and validate capabilities on settings updates.

**Security:** derive product and seller context server-side. Do not accept arbitrary seller IDs, numbers, usernames, URLs or redirect targets from client events as authoritative. Restrict channel URLs to approved WhatsApp/Telegram patterns. No open redirects, unsafe HTML, executable settings values, or buyer-facing contact for hidden/private/draft products. Honor disabled/banned seller status.

## 5. Seller onboarding and trust

Add or improve seller-facing Dokan settings in the existing store settings UI:

- Separate opt-in toggles: **Allow buyers to contact me on WhatsApp** and **Allow buyers to contact me on Telegram**.
- WhatsApp international-number field with formatting guidance and a private **Test link**.
- Telegram username field with validation and a private **Test link**.
- Preview of how the **Message seller** CTA appears on a product and storefront.
- Plain-language notice: **Your enabled WhatsApp number or Telegram username may be visible to visitors. Keep payments on KnifeRevive.**
- Save with nonce/capability checks, ownership validation and CSRF protection. Do not opt sellers in automatically during migration.

Trust details to show *only when authoritative*: actual Dokan store name, storefront link, real seller policies, real seller membership date if appropriate, and any **genuinely implemented** verification state. Never invent seller ratings, buyer protection guarantees, badges, response time or identity checks.

Consider a concise site-wide **Safe messaging** help page explaining external chat risks, reporting suspicious sellers, avoiding deposits/wire transfers and using on-site checkout. Link it near the chooser only if the page is actually published. Make seller-reported abuse easy to escalate through existing support; do not claim chat moderation or platform access to external message content.

## 6. Analytics: instrument adoption, not imagined conversations

First check whether GA4, Matomo, Plausible or a first-party event system is already installed. Prefer the existing consent-compliant analytics path; avoid introducing a new third-party SDK for this task.

Minimum event schema:

| Event | When it fires | Allowed dimensions |
| --- | --- | --- |
| `seller_contact_cta_impression` | CTA enters viewport and is actually visible (deduplicate per page/placement) | placement, product ID, seller ID, device class |
| `seller_contact_cta_click` | User activates Message seller | placement, product ID if any, seller ID, available channel count |
| `seller_contact_chooser_open` | Both-channel chooser becomes visible | placement, product ID if any |
| `seller_contact_channel_click` | User selects WhatsApp or Telegram | channel, placement, product ID if any, seller ID |
| `seller_contact_external_handoff` | Browser starts opening an approved external URL | channel, placement, product ID if any |

Use an IntersectionObserver or equivalent for real impressions, not merely page-load counts. Deduplicate obvious duplicate carousel impressions and exclude bots where feasible. If using a first-party endpoint, implement sensible anonymous rate limits and retention, no raw phone numbers, usernames, IP addresses, message bodies, private URLs or buyer PII. A tracking failure must never block the link. Avoid claiming successful app launch from a `sendBeacon` or link click.

Document exact KPI formulas:

- **CTA visibility rate** = product-detail sessions with at least one visible eligible CTA / eligible product-detail sessions.
- **CTA engagement rate** = sessions with CTA click / sessions with visible CTA.
- **Channel handoff rate** = sessions with channel click / sessions with CTA click.
- **Seller activation** = active sellers with at least one valid opted-in channel / active sellers eligible to configure messaging.
- **Seller coverage** = public eligible product views with a contact CTA / public eligible product views.
- **Checkout guardrail** = add-to-cart and completed-checkout conversion for exposed traffic versus baseline/comparison, using existing trusted commerce analytics.

Do not label channel clicks as “messages sent.” External WhatsApp/Telegram reply, buyer-to-seller delivery and chat-to-sale attribution require explicit integrations/consent or surveys and are **out of scope** for this release.

Provide an admin-only aggregate dashboard or report (not necessarily a full custom dashboard in v1): channel availability, CTA coverage, impressions, clicks, handoffs and opt-in by placement. A small CSV/report using the existing analytics tool is sufficient. Record a pre-release baseline even if the measured value is zero.

## 7. Rollout flags and configuration

Provide administrator-controlled settings, preferably in the existing contact plugin:

- Master enable/disable.
- Product-detail CTA on/off.
- Shop/card CTA on/off.
- Storefront CTA on/off.
- Channel chooser behavior (auto single-channel; chooser for two).
- Analytics on/off, with existing consent handling.
- Optional sticky-mobile action on/off (default off until visually validated).
- Optional staged rollout by allowlisted seller IDs or percentage, if the current system supports stable assignment.

Feature flags must default safely on upgrade, preserve current seller opt-ins, and permit immediate rollback of new UI without deleting seller contact settings. Do not change payment settings or merchant ownership. Do not enable off-site checkout.

## 8. Execution plan for Codex

### Phase A — reproduce and explain the missing-button issue (P0)

1. Inventory actual production/staging code and active templates.
2. Test both reference product URLs as guest and logged-in user, desktop and mobile.
3. Trace server eligibility and rendering, inspect DOM/CSS/JS and compare with a shop card for the same product.
4. Document the root cause for each product; distinguish seller opted out from broken rendering.
5. Write failing automated regression tests before fixing a confirmed bug.
6. Preserve opt-out: if the seller has no valid opted-in channel, absence of CTA is expected and must not be “fixed” by exposing contact data.

### Phase B — consolidate UI (P1)

1. Implement canonical seller/channel resolution.
2. Render product-detail CTA independently of the add-to-cart form; support actual theme/template variants.
3. Replace duplicate shop card buttons with one CTA; cover grids, carousels, search and related products that actually share this card renderer.
4. Add storefront contact entry and product seller panel link.
5. Build accessible chooser with progressive enhancement, external-link safety copy and correct channel URLs.
6. Preserve current WhatsApp and Telegram settings or migrate with explicit tests and no opt-in changes.

### Phase C — seller adoption and trust (P2)

1. Improve Dokan channel settings, validation, preview and test links.
2. Show actual seller/store information and safe-payment notice.
3. Add a safe-messaging help link only when published.
4. Provide admin diagnostics for missing CTA reasons without exposing contact values.
5. Pilot with willing sellers before broad release.

### Phase D — metrics and experiment (P3)

1. Instrument impressions, clicks and channel handoffs.
2. Validate analytics consent, deduplication, bot filtering and privacy.
3. Baseline the existing shop card performance.
4. After adequate traffic, compare current/new layout or single CTA variants with a stable A/B assignment; monitor add-to-cart and checkout as guardrails.
5. Do not declare a winner from small/noisy samples. Report counts, rates, period, sample size and uncertainty.

## 9. Tests — required acceptance matrix

Write or update automated tests plus browser/device evidence for:

**Eligibility:** opted-in WhatsApp only, Telegram only, both, neither; malformed phone/handle; suspended/non-seller account; admin-owned/migrated product; product ownership changes; published/hidden/draft/private; out-of-stock; simple/variable/external/grouped products; guest vs logged-in; missing Dokan; plugin disabled; escaped Unicode product titles.

**Placement:** exactly one primary product-detail CTA when eligible; exactly one compact CTA per shop/card instance; seller storefront generic CTA; no duplicated primary action in seller panel; no CTA for ineligible sellers; no broken product-card link, Add to Cart, slider swipe, lazy load or checkout.

**Link safety:** valid WhatsApp URL and encoded item text; Telegram opens the correct seller username without false prefill; safe external link attributes; no private URL/token; no open redirect or arbitrary seller ID injection; seller opt-out takes effect after cache invalidation.

**Responsive and accessibility:** actual browser tests at 320, 375, 390, 430, 768 and 1024+ pixels; desktop Chrome and Firefox; mobile Safari and Android Chrome where available; keyboard navigation, focus trapping/return, Escape, screen reader labels, reduced motion, zoom, touch targets, sticky bars and safe-area/keyboard handling. If physical-device/browser access is unavailable, mark those tests **unverified** rather than asserting they passed.

**Analytics:** real viewport impression, deduplication in carousels, consent denial, event loss, guest rate limit, no PII, nonblocking external handoff and correct KPI denominators.

**Regression:** existing ListLab listing editor/import, Dokan seller settings, WooCommerce checkout/cart/variations, Agent Commerce goods handoff, seller orders and the independent Order Messenger remain unaffected.

At minimum, run syntax/static analysis, existing repo test suite, relevant PHP integration tests and automated browser tests on staging. Save evidence and exact commands. No synthetic test purchases or mail sends on production.

## 10. Release, rollback and definition of done

Create a branch and reviewable PR with small, coherent commits. Deliver:

1. `docs/BUYER_SELLER_CHAT_DISCOVERY.md` — verified current state and two-product root-cause analysis.
2. Source changes in the correct existing plugin or isolated new plugin, plus integration/template changes and a changelog.
3. Unit/integration/browser tests and their reproducible commands.
4. Before/after screenshots for the reference product pages, shop grid, mobile card, chooser and seller storefront.
5. `docs/BUYER_SELLER_CHAT_IMPLEMENTATION_REPORT.md` — exact files changed, settings/meta migrations, hooks, compatibility decisions, security/privacy notes, tests passed/failed/unverified and measured baseline.
6. A staging deployment/rollback checklist, including DB backup, plugin/theme backup, cache purge, feature flag off, seller-setting preservation and post-rollback verification.
7. A short list of items requiring owner approval: final copy, seller pilot group, privacy/terms changes, rollout date, analytics consent and production deployment.

**Release gates:** no PHP fatal errors, no missing buttons for *eligible* sellers on the two reference products, no buttons for opted-out sellers, one CTA per surface, correct product-specific WhatsApp context, correct Telegram recipient, accessible mobile UI, no seller contact leaks, checkout unchanged, and all relevant tests passing or explicitly documented as outstanding.

**Do not deploy to production automatically.** Prepare the release and request approval. Never claim a button click means a real conversation, seller reply or completed sale.

## 11. Copy and visual priority — final default

- Primary commerce CTA: **Add to cart** / **Buy now** (existing site behavior).
- Secondary product CTA: **Message seller**.
- Compact grid CTA: **Message seller**.
- Storefront CTA: **Contact seller**.
- Product panel: **Sold by [store name]** · **Visit seller store**.
- Chooser heading: **Message [store name]**.
- External-chat disclosure: **Chat opens WhatsApp or Telegram. For your protection, pay only through KnifeRevive checkout.**
- WhatsApp prefill: **Hi, I saw “[product title]” on KnifeRevive and have a question about it: [product URL]**.
- Disabled contact: show **no button**, not “Chat unavailable,” unless an owner-approved informational design specifically requires it.

Use the site's actual typography, colors and existing button components. Favor readable text over brand-icon-only buttons. Never visually style external messaging as a payment or buyer-protection guarantee.

---

## 12. First Codex prompt

**Start now with discovery and implementation on a new working branch.** Read this file and the actual installed WordPress/Dokan/WooCommerce/KnifeRevive code. Inspect the three reference URLs. Produce the discovery report, identify why each product page lacks the reported contact buttons, and then implement the specified unified Message seller UX in the correct plugin/template integration. Add tests, staging evidence, adoption analytics and a rollback plan. Preserve seller opt-in, native checkout, ListLab and Order Messenger. If production-only sources, credentials, or browser access are unavailable, stop that specific operation and document the exact missing prerequisite; continue all safe local work. Do not deploy without approval.
