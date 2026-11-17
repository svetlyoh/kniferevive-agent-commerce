# Codex Build Brief — KnifeRevive Meta Campaign Landing Page

**Prepared:** 2026-10-25  
**Project:** KnifeRevive.com · WordPress, WooCommerce, Dokan, ListLab  
**Status:** Implementation instructions. **Do not deploy or publish automatically.**  
**Campaign:** “Your next find. Your first sale.” / “Your sale. A little backup.”

## 0. Your job

Build a **fast, responsive, conversion-oriented WordPress campaign landing page** that welcomes traffic from the two supplied Meta image ads. Explain *what KnifeRevive is*, invite visitors to browse real items and start their own shop, show the range of marketplace categories, and introduce **Dispatch** as an optional seller-to-seller fulfillment handoff concept. The campaign must feel authentic, approachable, young-adult-friendly and trustworthy; it must not feel like a generic knife store or a speculative income scheme.

**Proposed public URL:** `https://kniferevive.com/your-next-find/`  
Use **one landing page with two deep-link entry points**, not two divergent sites:

- Marketplace ad: `/your-next-find/?utm_source=meta&utm_medium=paid_social&utm_campaign=next_find_2026&utm_content=marketplace#discover`
- Dispatch ad: `/your-next-find/?utm_source=meta&utm_medium=paid_social&utm_campaign=next_find_2026&utm_content=dispatch#dispatch`

These are example link structures; preserve all actual ad manager UTM parameters. Do not require the user to click a prompt that blocks access or force an account before they can browse.

## 1. Inputs and trustworthy source of truth

This production-kit folder contains:

- `exports/kniferevive_01_marketplace_portrait_1080x1350.png` (and square / vertical equivalents)
- `exports/kniferevive_02_dispatch_portrait_1080x1350.png` (and square / vertical equivalents)
- `assets/original/wusthof_original.jpeg`, `fire_tv_original.jpeg`, `cutco_original.jpeg` — owner-supplied original seller photography, in that order
- `assets/wusthof.jpg`, `fire_tv.jpg`, `cutco.jpg` — carefully whitespace-trimmed *same photographs* for display; never substitute synthetic products
- `assets/dispatch_illustration.png` — conceptual handoff graphic, **not** a product screenshot
- `assets/web/*.webp` — optimized website variants
- `render_ads.py` — reproducible ad composer

Inspect the existing WordPress theme and any current campaign, category, seller, or about page before writing code. Respect site branding and global site header/navigation/footer. Existing public site destinations:

| Purpose | URL |
|---|---|
| Browse categories | `https://kniferevive.com/categories/` |
| Start seller onboarding | `https://kniferevive.com/sell/` |
| My Account | `https://kniferevive.com/my-account/` |
| Seller ListLab (authenticated) | `https://kniferevive.com/my-account/listlab/` |
| Technology Trade Desk | `https://kniferevive.com/technology-trade-desk/` |
| Books | `https://kniferevive.com/books/` |
| Shop products | `https://kniferevive.com/shop/` (verify actual Shop All URL and redirect behavior) |
| Wüsthof Gourmet knife | `https://kniferevive.com/shop/chefs-knife/wusthof-gourmet-chefs-knife-8-inch/` |
| Fire TV Stick 4K Select | `https://kniferevive.com/shop/technology/amazon-fire-tv-stick-4k-select-with-alexa-voice-remote/` |
| Cutco 1728 | `https://kniferevive.com/shop/chefs-knife/model-1728-msrp-150/` |
| Listing guide | `https://kniferevive.com/listing-guide/` |
| Terms and fees | `https://kniferevive.com/terms-and-conditions/` |
| Help | `https://kniferevive.com/help/` |
| Privacy | `https://kniferevive.com/privacy-policy/` (verify route) |

Do not hardcode inventory, prices, discount claims, verified testing claims, stock status, seller approval, guaranteed earnings, shipping promises or legal policy content. Read product details/status using WooCommerce when rendering product cards.

### Confirmed vs. unconfirmed feature facts

1. The live `/sell/` flow currently displays the steps **Account → Shop → Dispatch → Payouts → Finish**. Link to this flow; do not rebuild it on the campaign page.
2. KnifeRevive's custom **ListLab** creates and edits real WooCommerce listings for signed-in eligible sellers at `/my-account/listlab/`. Do not build a second listing editor.
3. The live Terms page (reviewed 2026-10-25) displays a **0% marketplace commission for a new seller's first six calendar months, then a standard 3%** (with exceptions and additional processing/checkout fees). **Do not put these numbers into evergreen campaign copy without confirming current live terms, administrator approval, and precise eligibility.** A plain `See current seller terms & fees` link is the safest default.
4. Dispatch handoff/compensation/eligibility is a **seller-focused feature concept whose precise production behavior must be inspected in code and policy before making a concrete promise**. The current ad wording is specifically qualified. Do not treat an onboarding step labeled Dispatch as proof that end-to-end reassignments or shared payouts are live.
5. Item conditions on marketplace pages are generally seller-reported. No claims of independent authentication, perfect condition, fixed return policy, guaranteed delivery times or manufacturer endorsement.

## 2. Campaign positioning & intended audience

**Primary audience:** young adult (18+) first-time sellers, students, side-hustle experimenters, collectors, and people who want to turn unused but worthwhile items into listings. **Secondary audience:** browsers who enjoy practical finds, interesting collectibles, knives, consumer tech and original art. Avoid suggesting minors can register for payouts without meeting seller/payment-provider age requirements. No “get rich fast,” cash-stack imagery, urgency timers or exaggerated promises.

**Message hierarchy:**

1. “Your next find. Your first sale.” — this is a two-sided marketplace, not simply a retail ad.
2. “Buy & sell kitchen knives, tech + more.” — the product mix is broader than the name suggests.
3. Real product photographs: Wüsthof Gourmet, Fire TV Stick 4K Select, Cutco 1728.
4. “Meet Dispatch. Sellers helping sellers ship.” — explain carefully, not as an automatic service.
5. Primary actions: browse genuine products; create a seller shop. Secondary: understand Dispatch and seller terms.

Voice: direct, contemporary, visually confident, friendly, short sentences, easy to skim. Gen Z flavor can appear naturally in microcopy (“Got something worth listing?”), never forced slang or fake hype.

**Visual system:** warm ivory `#F5F2EA`, charcoal `#191919`, clean white card surfaces, tasteful lime `#D8F36A` highlights, restrained soft shadows; large bold sans-serif headlines; consistent KnifeRevive global branding. No generated Wüsthof/Cutco/Amazon marks; photographed markings remain real. Contrast must meet WCAG AA; lime is an accent or dark-text background, not low-contrast text.

## 3. Page content — required sections and ready-to-use copy

### 3A. Hero (above fold): the same promise as the marketplace ad

Eyebrow: `KNIFEREVIVE · BUY + SELL MARKETPLACE`

H1 (preserve line breaks on desktop where legible):

> **Your next find.**  
> **Your first sale.**

Supporting copy:

> Find useful gear, distinctive pieces and items worth keeping. From kitchen knives to tech and other finds, KnifeRevive brings buyers and independent sellers together — and gives new sellers a place to start.

Two primary visible actions (no forced signup modal):

- `Explore what's for sale` → actual Shop All / categories URL; optionally scroll to `#featured` first on desktop if it helps discovery
- `Start your shop` → `/sell/` with campaign attribution carried over safely

Smaller tertiary action: `See how Dispatch works` → `#dispatch`.

Hero product collage using **exact uploaded photos** in clear product cards. Suggested order: Wüsthof, Fire TV Stick, Cutco. Product names: `Wüsthof Gourmet`, `Fire TV Stick 4K Select`, `Cutco 1728`. Keep metal reflections, wear, item markings, true carton printing and the Fire TV remote artwork intact. Do not beautify condition into new inventory. Cards may link to actual listings; graceful fallback to the relevant category if an item becomes unavailable. No prices in the static hero artwork.

### 3B. Quick orientation: “So, what is KnifeRevive?”

Heading: `A marketplace for the things worth passing on.`

Copy:

> KnifeRevive started with the idea that a good kitchen knife can have another chapter. Today, the marketplace also has technology, books, original art, coins, regional pantry finds and more. Browse what sellers have listed, check real item details, or give something of yours a new home.

Add a clean **buyer/seller split**:

- **Here to find something?** `Search listings, compare photos and condition, then purchase through the normal KnifeRevive checkout.` CTA `Browse listings`.
- **Here to sell something?** `Create your shop, add your own items with clear photos and descriptions, and manage listings in My Listings.` CTA `Become a seller`.

No inaccurate statements that KnifeRevive personally refurbishes/tests every seller item or provides insurance/escrow to all orders.

### 3C. Featured finds (real WooCommerce data)

Section heading: `Real finds. Real listing details.`

Three spotlight cards, in **Wüsthof → Fire TV → Cutco** sequence; use the owner-provided photographs for this ad-specific editorial feature, but fetch WooCommerce status, link, title and optional current price from actual products. Display a discreet note such as `Seller-listed item · See full description and condition` and a link `View item`. If draft/private/sold out, show `Explore similar` to a real category rather than offering a dead purchase CTA. If product source references are invalid, log an admin-visible warning; render approved categories instead of a broken card. **No hardcoded prices or invented star ratings.**

### 3D. Category discovery (why KnifeRevive is more than knives)

Heading: `More than the knife drawer.`

Responsive small collection cards with accurate links, sourced from current real WooCommerce categories / known landing pages. Feature:

- Kitchen Knives — useful, pre-owned kitchen tools, compare brand and actual condition
- Tech — streaming devices, computers, accessories, AI-focused experiments
- Books — specific editions and second chapters
- Art Trade Gallery — original work and artists
- Other Finds — world coins, spices, collectibles and interesting one-offs

Optional category tags: iPhones / laptops / mini-computers / home & garden if live. Never show empty collections as richly stocked. Each category navigates to a real relevant page; no hardcoded duplicate WooCommerce term data.

### 3E. Seller onboarding: small, real steps

Heading: `Got something worth listing?`  
Supporting copy: `Start with a shop. Add photos. Tell buyers what they should know. You're on your way.`

Four succinct visible steps (not a fake signup form):

1. `Make your account` — existing `/sell/` onboarding with passkey or password where supported.
2. `Set up your shop` — choose storefront details in existing registration flow.
3. `List the real item` — photo, brand/model, condition, included pieces, shipping and returns; use ListLab for eligible sellers, link to `/my-account/listlab/` only contextually.
4. `Manage the sale` — sellers handle fulfillment and applicable platform rules through existing WooCommerce/Dokan/KnifeRevive workflows.

CTA: `Start selling on KnifeRevive` → `/sell/`; secondary `See listing guide` → `/listing-guide/`. Link to live **terms and fees**, not an unsupported “free forever” banner.

### 3F. Dispatch feature module (deep link `id="dispatch"`)

Eyebrow: `SELLER TOOL · DISPATCH`  
H2: **`Your sale. A little backup.`**  
Subheading: `Meet Dispatch. Sellers helping sellers ship.`

Use `assets/dispatch_illustration.png` showing two illustrated seller cards, a box and arrow; label `Your order` and `Another seller fulfills`. Clearly describe it as a *conceptual illustration* rather than a screenshot.

Safe public-facing copy until verified in the live implementation:

> Sometimes another seller may have an eligible matching item and be better positioned to fulfill an order. Dispatch is designed to help sellers explore a handoff where the marketplace rules allow it, instead of leaving the buyer without options. Any handoff must be checked, accepted and handled through the approved order workflow.

Mini process diagram:

`An order needs fulfillment` → `Check eligible matching stock and seller availability` → `Confirm a permitted handoff` → `Participating seller ships; buyer receives normal order updates`

**Do not present this as an already functioning button or guaranteed service unless the actual code + policy checks show that it works.** If not production-ready, display a subtle `Feature in development` / `Learn about Dispatch` label. Do not solicit real orders for the handoff mechanism on this marketing page.

Financial language is **conditionally gated**: only after a verified production policy supports it may marketing say: `They ship. You may still earn.` paired immediately with `Eligible orders. Terms apply.`. Never promise a fixed reward, margin, guaranteed profit, zero risk, guaranteed faster delivery, automatic reassignment or ability to list products the seller does not possess/has no legal right to sell. Avoid a specific financial split because it was still under business-model discussion.

Operational points for the future feature spec (not tasks to build in this landing-page PR): distinguish original selling party, receiving fulfilling seller, exact matching item/condition, acceptance, stock reservation, delivery location, returns liability, buyer disclosure/consent where required, payment transfer/settlement, and written audit trail. **Never reveal customer shipping PII to another seller before an approved authorized handoff.**

CTA: `See current seller setup` → `/sell/`, secondary `Read the marketplace rules` → live terms. Do not display fabricated Dispatch usage statistics.

### 3G. Trust and transparency

Heading: `Details matter.`  
Text: `Look for seller photos, stated condition, what's included, shipping information, return terms and seller history where available. Used items have stories; clear descriptions help you decide.`

Show *real existing site affordances*, not fabricated reviews/badges:

- `Condition and wear, up close` — see photo / condition guide
- `Who you're buying from` — link to real storefront information when present
- `Shipping and return terms` — user-visible before purchase
- `A normal shopping checkout` — uses current WooCommerce checkout, no fake purchase interaction

For knife products, note seller-stated grades don't independently verify tip, edge or handle. For tech, functional testing is distinct from cosmetic appearance. Link to help, listing guide, returns and privacy. Don't make promises that apply only to some items.

### 3H. FAQ accordion (accessible and indexable)

At minimum:

**Is KnifeRevive only for knives?** `No. The marketplace includes kitchen knives plus tech, books, art and other changing categories.`

**Can I shop without becoming a seller?** `Yes. Browse available items and check the seller's product information before purchasing.`

**How do I start selling?** `Use the existing seller setup to create an account and shop, then add your own listings with photos and honest descriptions. Seller approval and payout setup follow current platform rules.`

**Can I sell used items?** `Many categories feature pre-owned goods. Follow KnifeRevive's category rules, accurately describe condition, and list only permitted items you can legally sell.`

**What is Dispatch?** `An eligible seller-to-seller order fulfillment handoff concept. Availability and compensation depend on current marketplace rules and feature status.`

**Does KnifeRevive guarantee every item's condition?** `No. Read the listing's seller-reported details, photos, tests (if supplied) and terms before buying.`

**What fees do sellers pay?** `See current seller terms and fees for introductory periods, category fees and payment-processing costs.`

Keep the FAQ accurate to the live system. The product and seller eligibility rules may evolve.

### 3I. Final dual CTA / footer

Headline: **`Find your next thing. Or sell your first.`**  
Buttons: `Explore KnifeRevive` → categories; `Start a shop` → sell.  
Footer links: How to browse, Terms & Fees, Contact, Privacy. Reuse existing site footer, consent controls and styling rather than creating a disconnected footer.

## 4. Technical implementation instructions

### Architecture / safe development

1. Inspect the active custom theme and any child theme, existing blocks, WooCommerce/Dokan customization, responsive tokens, page builders and template structure. Read repo README, coding conventions, build scripts and test mechanisms. Document confirmed assumptions before implementation.
2. **Prefer a native WordPress Page** with slug `your-next-find`, authored as accessible Gutenberg blocks/patterns or a dedicated page template in the existing **custom/child theme** as appropriate. If the theme is not safely editable, create a narrowly scoped namespaced plugin/shortcode without adding a large framework. Do not create another checkout, seller registration or product database.
3. Follow the site's existing layout and enqueue practices; scope styles under `.kr-campaign-*`. No global CSS resets, CSS/JS added to unrelated pages, or changes to `wp-admin`, WooCommerce core, Dokan core, Jetpack or ListLab core. Support PHP 8.4 and current WordPress version; read versions in target environment.
4. Register WordPress REST routes only on `rest_api_init` **if** new read-only routes are truly necessary; prefer normal `wc_get_product()` and standard server-rendering without public custom APIs. Do not initialize REST during `plugins_loaded` (see existing ListLab implementation report's prior crash).
5. Use proper capability checks, `esc_html`, `esc_attr`, `esc_url`, nonces for any write, and sanitized `wp_unslash` handling where appropriate. Do not accept arbitrary query strings as URLs or HTML. This campaign page should normally be read-only.
6. Resolve the **three products by verified slug/ID** and store IDs via admin-configurable settings rather than fragile raw literal IDs. Render their cards through WooCommerce APIs. Cache only safe public product data; respect WooCommerce stock/status and cache invalidation. Never expose draft/private products.
7. Upload approved imagery into WordPress Media Library or deploy approved theme assets, recording attachment IDs. Use responsive `<picture>`/`srcset` and `sizes`; lazy load below fold, eagerly load the true hero LCP image with appropriate priority. Add accurate alt text such as `Used Wüsthof Gourmet chef's knife, side view showing blade and handle`, `Amazon Fire TV Stick 4K Select retail box`, and `Cutco 1728 chef's knife with black handle, side view`; decorative Dispatch art may use empty alt when adjacent text explains it.
8. Treat owner-supplied images as the only source for the advertised products. Do not generate, replace or hallucinate packaging, scratches, rivets, markings, manufacturer logos, ports or remote buttons. A white-background crop is allowed if it shows the complete product. Preserve original source images as references.
9. Responsiveness: mobile-first 320px through large desktop; hero must not be too tall on phones, buttons full-width where appropriate, card stacking smooth, text comfortably legible, no horizontal scrolling; normal site menu preserved. Anchored Dispatch section must land below sticky headers using `scroll-margin-top`.
10. Accessibility: one H1, logical heading sequence, visible keyboard focus, semantic links/buttons, 44px comfortable tap targets, WCAG AA color contrast, alt text and accessible disclosures/accordion (`<details><summary>` or equivalent), no autoplay sound or nonessential motion. Respect `prefers-reduced-motion`.

### Analytics / privacy / campaign routing

- Keep existing site ad/marketing privacy consent. The live site already displays **“Advertising privacy”** with `Decline`, `Allow Meta measurement`, and `Advertising preferences`; **reuse it and honor user choice**. No new Meta Pixel or Conversions API request prior to opt-in. Avoid duplicate events if Meta/Jetpack integrations exist.
- Attribution: preserve standard UTM parameters (`utm_source`, `utm_medium`, `utm_campaign`, `utm_content`) where appropriate and don't leak arbitrary URL parameter values into the page. Campaign content is `marketplace` or `dispatch` for reporting; treat parameters as untrusted.
- If approved measurement is available, instrument a single landing-page view and deduplicated `browse_click`, `seller_signup_click`, `dispatch_explore_click`, `featured_product_click`, each with content context **and no PII**. Add events only within existing consent architecture.
- The Dispatch ad should land at `#dispatch`; marketplace ad should land at `#discover`, or top hero if testing demonstrates scroll creates confusion. Validate actual Facebook/Instagram webview behavior with anchors and UTMs.
- Do not render different factual terms/fees based solely on incoming ad variant. Both variants must share one source of truth.

### Performance / SEO

- Target practical mobile CWV: LCP ≤2.5s, CLS ≤0.1, INP ≤200ms under representative production network conditions; these are **targets, not claimed results**. No heavy slider, animation framework, trackers before consent, external font download or giant ad PNG as hero.
- Use optimized `assets/web/*.webp` or WordPress derivatives, meaningful width/height attributes to prevent layout shift, image sizes based on rendered card. Use CSS shapes for accents instead of large art files when possible. Protect WooCommerce checkout and current cache setup.
- SEO title suggestion: `Buy & Sell Knives, Tech & More | KnifeRevive`; description: `Discover used kitchen knives, technology and other finds. Browse seller listings or start your own shop on KnifeRevive. Learn about Dispatch.`
- Canonical URL without UTM strings (`/your-next-find/`). Standard indexability is acceptable if content is unique; do not blindly noindex without marketing direction. If FAQ structured data is used, it must match visible FAQ and current policy; do not expect Google rich result eligibility.
- Include branded open graph preview art using approved actual products, not fictitious item photography; never let the first auto-generated image be an accidental product badge.

## 5. Required build and acceptance tests

- [ ] Confirm exact live WordPress theme/plugin versions, seller signup, ListLab access route, category slugs, and current homepage/header/footer design.
- [ ] Page renders publicly at `/your-next-find/` without login, correctly redirects to HTTPS and keeps WP header/navigation/footer.
- [ ] In a narrow iPhone webview, first screen communicates **buy AND sell** plus primary CTA; no cut-off hero text.
- [ ] Wüsthof, Fire TV, Cutco owner images appear with intact object silhouettes and truthful photographed details. No synthetic products or fake reviews.
- [ ] Featured product links point to real WooCommerce product pages; out-of-stock/draft/unavailable cases have truthful fallback.
- [ ] Category cards route to real current archives / Annex landing pages, not broken paths or empty invented sections.
- [ ] Seller CTAs go to `/sell/`; existing five-step setup and ListLab remain unchanged. Test signed-out and logged-in flows.
- [ ] Dispatch page deep link works; illustration is identified conceptually; no automatic reassignment, profit guarantee or unverified availability promised.
- [ ] Live Terms link is accessible; no stale fixed fee claim, invented policies or payment-provider statements.
- [ ] Advertising consent is honored on first page load and on preference changes; no duplicate Meta events or PII leakage.
- [ ] WCAG keyboard/contrast/accordion checks; 320, 375, 390, 768, 1024, 1440px viewports; portrait/square/vertical ad-to-page message match.
- [ ] Test CWV/mobile performance or Lighthouse/PageSpeed; provide **measured** values and before/after bundle sizes, not guessed improvements.
- [ ] Test product data escape, malformed UTM params, removed products, empty categories, cache, and no-fatal guards when WooCommerce/Dokan is temporarily disabled.
- [ ] Validate no modifications to existing WooCommerce, Dokan, ListLab, WhatsApp messaging, checkout, payouts, REST hooks, or existing site URLs.
- [ ] Take desktop and mobile screenshots, compare visually against campaign PNGs, inspect PHP lint and any JS/CSS tests; confirm no warnings in logs.

## 6. Deliverables Codex must return

1. All code changes as a concise, reviewable PR or working patch in the existing repo, with no secrets.
2. Any approved landing-page template, scoped CSS/JS, reusable partials, image attachments/source references and README for updating them.
3. The exact new public page URL/slug, test/preview link, deployment instructions and **rollback procedure** (revert page/template and clear appropriate cache; never destroy content).
4. A small list of **verified** outstanding policy questions: Dispatch availability, handoff eligibility and seller compensation; seller age/payout restrictions; current seller fee promotion wording if it is to be marketed.
5. QA screenshots and a validation checklist with actual results, broken-links status, and performance findings.

**Definition of done:** The page is a credible, polished extension of the two ads; new visitors understand both shopping and selling; buyer/seller pathways genuinely work; Dispatch is compelling but correctly qualified; and no fragile core/plugins changes or misleading claims are introduced. **Do not publish/activate the new page or change live ad destinations without explicit owner approval.**

## 7. Sources checked for this brief

Public site pages checked 2026-10-25:

- `https://kniferevive.com/`
- `https://kniferevive.com/sell/`
- `https://kniferevive.com/categories/`
- `https://kniferevive.com/listing-guide/`
- `https://kniferevive.com/terms-and-conditions/`
- `https://kniferevive.com/shop/chefs-knife/wusthof-gourmet-chefs-knife-8-inch/`

Private project source files informing integration constraints:

- `KNIFEREVIVE_LISTLAB_TECHNICAL_IMPLEMENTATION_REPORT.md` (implementation report, ListLab 1.1.3)
- `KNIFEREVIVE_WHATSAPP_SELLER_MESSAGING_IMPLEMENTATION_REPORT.md`
- `KNIFEREVIVE_MAIN_IMAGE_INSTRUCTIONS(1).md` (approved campaign creative brief)

**Important:** Public access to individual Fire TV / Cutco pages and Technology Trade Desk intermittently returned access/rate-limit errors in the research tool; Codex must confirm those product URLs and their live state directly in the authenticated/local WooCommerce environment before building or publishing links.
