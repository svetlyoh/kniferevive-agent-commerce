# KnifeRevive Next Find campaign — implementation and review

Date: 2026-10-25. Branch: `codex/next-find-campaign-20261025`, based on remote main5ac2417. Existing dirty local packing-slip/address work and the separate messaging release were left untouched.

Review: [draft PR #5](https://github.com/svetlyoh/kniferevive-agent-commerce/pull/5).

## Status and review links

**Built and locally tested; NOT deployed, activated or published.** No Meta ad destination, campaign, budget, pixel, dataset or account was changed. The brief says “Do not publish/activate the new page or change live ad destinations without explicit owner approval.”

Local preview while the preview server runs: `http://127.0.0.1:8799/index.html`. Marketplace entry adds `?utm_source=meta&utm_medium=paid_social&utm_campaign=next_find_2026&utm_content=marketplace#discover`; Dispatch entry uses `utm_content=dispatch#dispatch`.

Proposed future public Page: `https://kniferevive.com/your-next-find/`. No production Page exists yet. It must not be used as a live ad destination until installation, publication and hosted smoke checks pass.

The preview combines a **real native WordPress/WooCommerce-rendered campaign fragment** from isolated test records with a **GET-only capture of the actual public site header/footer/styles**. It is labelled LOCAL DRAFT, uses no production authenticated cookies, and includes no live tracking/purchase code. This is not a deployed WordPress staging site. Captured menu/cart controls are visual references; the real production header/footer scripts must be tested after installation. Header/footer sources themselves are not edited.

## Discovery before code

- Primary instructions read: `KnifeRevive_Documentation/KnifeRevive_Codex_Landing_Page.md`; a copy is preserved as `docs/KNIFEREVIVE_NEXT_FIND_CAMPAIGN_BRIEF.md`.
- Reviewed the supplied production-kit README and both portrait ads, asset inventory and web photo sources. No product images were generated. The kit originals, exports and offline ad editor remain untouched.
- Live theme: KnifeRevive child of Twenty Twenty-Four; confirmed theme tokens: system sans, white/contrast/surface/copper, wide1200px. Campaign ivory/charcoal/lime are explicitly brief-approved accents, scoped to the new Page. Existing theme page/about/category patterns were inspected.
- Live Woo11.2.1, Dokan5.3.0/Pro4.0.6, ListLab1.13.6, Dispatch0.1.4, Meta Controls1.0.3 and official Meta for WooCommerce3.7.8 confirmed read-only. WordPress frontend assets identify7.1.3; native QA core is7.1.2/PHP8.4.16. Production PHP8.4 compatibility is intended; hosted execution remains unverified until install.
- Existing `/sell/` displays **Account → Shop → Dispatch → Payouts → Finish**, with passkey/password choices. It is linked, not rebuilt. Guest ListLab resolves to the existing account sign-in route. Campaign native tests verify identical browse content for guest/logged-in; completion of real signup or Stripe onboarding was not attempted.
- Current public featured records were verified via WordPress connector and Woo Store API: Wüsthof538, Fire TV3196, Cutco1448, all public/in stock at audit time. These observations are not fixed stock promises. Renderer reads current status. Store API snapshot and destination audit are in `output/next-find/public-discovery.json`.
- Actual privacy route is `/help/privacy-policy/`; Dispatch terms `/help/dispatch-terms/`. Sixteen destination checks returned200, including the expected guest ListLab/account redirect. No404 destination was found. `/shop/` remains its existing route; the category-specific Knife shortcut uses `kr_knives_only=1`.
- Inspected Dispatch code/manual-settlement report: live adapters still reject unverified handoffs; real handoff cohort/matching/settlement work is not established by enrollment alone. Therefore Page copy says **Feature in development**, identifies conceptual art, and makes no compensation, guaranteed speed or automatic reassignment promise.
- Existing Meta Controls readiness verifies the expected dataset and controls official sender consent. Its cookie/WordPress Consent API/GPC behavior was inspected; the campaign creates no new consent control or tracking integration.

## Architecture and files

Native WordPress Page plus one slug-specific child-theme block template and a small isolated shortcode plugin. This avoids editing existing theme/plugin core or recreating commerce flows. It follows the existing theme’s shortcode/page pattern. Core `shortcode`, `post-content`, `group` and `template-part` blocks are registered on the live site.

| Path | Change |
|---|---|
| `wordpress/kniferevive-campaign-landing/kniferevive-campaign-landing.php` | New optional-dependency bootstrap; activation publishes nothing |
| `includes/Campaign.php` | Public read-only product/category rendering, safe UTMs, page-only assets, canonical/title/description/Jetpack OG metadata and stock/status cache hooks |
| `includes/Admin.php` | WooCommerce → Next Find campaign; nonce/capability-protected Page/product bindings, diagnostics, optional measurement switch OFF |
| `templates/landing.php` | Hero/collage, orientation split, native product spotlights, categories, seller steps, qualified Dispatch module, trust details, seven FAQs and final dual CTA |
| `assets/campaign.css` | Scoped responsive layout, focus,44px targets, reduced motion; no global reset |
| `assets/campaign.js` | Optional1317-byte custom-event adapter through existing official Meta sender, not enqueued by default |
| `assets/images/*` | Responsive derivatives of the supplied whole photos, Dispatch concept and approved square ad OG art; source hashes in manifest |
| `theme-overlays/kniferevive/templates/page-your-next-find.html` | New template only, preserving current header/footer, one main and one content H1 |
| `docs/next-find-page-content.html` | Editable native core Shortcode block; no unregistered block |
| `tests/next-find-*`, `tools/prepare-next-find-assets.py`, `tools/next-find-preview.cjs` | Native/browser/absence checks, reproducible asset preparation and local review capture |

No modifications to existing WooCommerce/Dokan/ListLab/Dispatch/WhatsApp/checkout/payout/Meta integration source. No REST route, cron job, product write, public feed change, background import or external font/animation dependency is added by the release. The native test bootstrap creates/deletes only disposable `krev_campaign_qa` / `krcl_` fixtures and blocks WordPress outbound HTTP/mail; production config is never loaded.

## Product data, source photos and privacy

Product IDs are owner-configurable under `kr_campaign_landing_v1`; zero uses the verified source slug until the owner saves resolved IDs. Saved IDs remain authoritative through legitimate slug changes. New arbitrary bindings are rejected if they do not match the approved photo’s source product. A seller repurposing a bound product for a different physical item still requires a creative review; the tool does not perform visual authentication.

No hardcoded price, quantity, discount, return policy, fee promotion, rating, seller approval, fixed reward or delivery time. Unavailable/private/draft/hidden/password-protected/deleted products never supply private product titles/URLs: use approved editorial labels/photos and real category fallback. The source photo itself is already owner-approved ad material. There is no shared data cache containing private products.

Product photographs are resized whole with contain layouts, without cropping, rotating, retouching, recoloring or synthetic replacements. Source references: kit `assets/web/{wusthof,fire_tv,cutco}.webp`; original seller photos remain in kit `assets/original/`. Dispatch illustration is conceptual, not a product screenshot. `assets/images/manifest.json` records input hashes and output dimensions/bytes. Packaged assets were chosen instead of Media Library uploads; **no attachments or attachment IDs were created**.

Only standard four UTMs with a restricted96-character alphabet/length are propagated to appropriate same-site paths; arrays, markup, emails, oversized and unknown parameters are dropped. Incoming values never select a destination URL or factual fee/Dispatch variant. Legal links omit campaign values. Canonical is the clean future Page URL.

Tracking OFF means zero campaign JavaScript/SDK. If separately approved, optional events require existing Meta Controls readiness, official `fbq`, marketing consent and no GPC. Payload uses fixed campaign/content/approved item slot only; arbitrary UTMs and PII are excluded. It never sends PageView, ViewContent or Purchase and does not add CAPI. Stub tests are not evidence of Events Manager receipt. Current production consent settings and official standard events remain untouched.

## Actual validation

| Check | Result / limit |
|---|---|
| Native WordPress/WooCommerce integration | **51 passed**: all source slots, public state, future/draft/private/trash, stock/hidden/password/deleted fallbacks, source binding/rename, escapes, malformed UTMs, canonical, empty categories, scoped assets/cache and guest/owner rendering |
| Missing WooCommerce/Dokan | Safe category fallback passed; no fatal dependency assumption |
| Native final runtime log | **0 new PHP warnings/errors** in successful final runs; historical fixture setup errors remain only in the isolated QA log |
| Chrome headless | **61 passed**, six widths320/375/390/768/1024/1440; header/footer, no overflow, image integrity/loading, first-screen buyer/seller actions, keyboard FAQ/focus, anchors, links, no JS, consent/GPC/dedupe/no standard-event duplication |
| Firefox153 headless | **61 passed** using the same assertions; physical iOS/Android/Instagram webview not claimed |
| Lighthouse mobile, local draft | Accessibility100, Best Practices100, Agentic Browsing100; SEO66 solely because preview is intentionallynoindex. Live template does not addnoindex. Scores are local, not production certification |
| Public link audit |16/16 returned200, with expected ListLab guest account redirect; product records independently verified |
| PHP/JS syntax | Passed; no core/plugin modifications or production requests with write methods |
| Actual signup/Stripe, live checkout/order, hosted cache and Meta receipt | Unverified; no real signup, financial action, order, message or mail was performed |
| Safari/Android/VoiceOver,200% text zoom, real Facebook/Instagram anchors | Unverified; use post-install device checklist before ads go live |

Screenshots under `output/next-find/` include desktop/mobile full page, above-fold hero and Dispatch anchor. They are visibly labelled local draft. Browser audits use isolated temporary profiles and intercept non-GET/tracking requests. No personal browser profile is used for automated QA.

## Measured performance and payload

Chrome DevTools MCP trace:390×844,1× pixel ratio, Fast4G emulation,4× CPU slowdown, cold reload, reduced motion; captured global site CSS plus local native content. **Local lab LCP3025ms, CLS0.00, TTFB3ms. INP unavailable** (no representative real-user interaction sample), no field data for this unpublished Page. LCP was the supporting paragraph, not a product image;3022ms render delay dominated. This first trace misses the2.5s LCP target, while CLS meets0.1. Do not label it a production CWV pass.

Trace identified13 blocking stylesheets,12 inherited site/commerce bundles and the new campaign stylesheet. Combined estimated blocking savings259ms; this is a diagnostic estimate, not measured improvement. No font requests or script requests occurred in the local snapshot’s stylesheet/font/script network filter. The broad inherited CSS was retained to preserve header/footer and avoid unrequested sitewide optimization. Image-delivery insight estimated15.7KB saving but0ms LCP benefit; not prioritized over correctness.

New CSS before final mobile Dispatch ordering17766 bytes /3733 gzip; final file adds a small mobile-only ordering rule. Optional JS1317 bytes /710 gzip, **0 requested by default**. Exact final sizes and ZIP hashes are in the release manifest.

Provided product web assets total196642 bytes. Selected1×320px variants total20848 bytes;640px variants total66320 bytes (about66% fewer bytes than the three provided sources at that selection). These are asset-size comparisons, not a measured page-speed improvement. Browser srcset may select960px for higher density/wider slots. Dispatch illustration source1276207 bytes becomes26352/65448-byte WebPs with the original transparency preserved. The giant ad PNG is not used as the hero; the approved square ad is used only for OG metadata.

Targets follow [Google Web Vitals](https://web.dev/articles/vitals): LCP≤2.5s, CLS≤0.1, INP≤200ms at field75th percentile. [Lighthouse documentation](https://developer.chrome.com/docs/lighthouse/performance/performance-scoring) explains lab variability. Re-measure hosted Page TTFB/LCP/CLS/interaction after publication; static local loopback TTFB does not represent WordPress hosting. DevTools file-export restrictions prevented the requested custom raw trace path, so structured findings/insight evidence and the Lighthouse JSON were saved instead.

## Deployment and rollback

1. Owner reviews copy, photograph/product bindings, Dispatch qualification and release package. Approval is still required to install/activate/publish. Campaign custom measurement remains OFF unless separately approved.
2. Back up DB, current theme and the target template path. Install only the new plugin ZIP and add only the new template file. Neither exists as a deployed feature yet; do not replace an entire old theme/plugin directory.
3. Create native draft Page with provided core Shortcode block, exact slug and template. Configure/save current Page ID and verified product IDs in WooCommerce → Next Find campaign; check current stock and image identity again. Keep current SEO plugin if present; configure its title/description/OG instead of adding duplicate metadata. The plugin supplies fallback metadata/Jetpack OG only on its Page.
4. Preview as guest and signed-in user with actual global menus/footer/advertising preference control. Verify anchors, UTM retention, sold-out/private fallback, native ListLab/sell routing, portrait/square/vertical message match, Safari/Android and200% zoom. Do not place a paid order or change Stripe setup for this check.
5. Publish only after approval. Confirm200, HTTPS, canonical without UTMs, one H1, one description/OG set and correct real-product OG art. Production should be indexable; remove noindex only if an isolated preview wrapper was accidentally imported (do not import that wrapper).
6. Purge page/CDN caches. Verify stock/status changes trigger invalidation of **this landing Page**, including a fresh guest cached visit. Plugin coalesces own Page post-cache/available host function invalidation and offers `kr_campaign_landing_cache_purged($page_id)`; actual WordPress.com/CDN integration remains a rollout check, not proven by the native hook test. No global cache bypass is added.
7. Re-run hosted performance and check existing checkout/cart, seller setup, ListLab, messaging, Dispatch and payout screens read-only. Only after these pass may the owner separately update ad destinations. No ad launch/budget change is part of this release.
8. Rollback by returning the new Page to draft, deactivating this new plugin, restoring/removing only the added template using the backup, and purging relevant cache. Retain Page content and asset/source references; never delete existing products, settings or seller content. Existing marketplace pages, checkout and ad assets remain unchanged.

## Verified policy questions retained for owner review

- Dispatch: inspected implementation does not establish working live handoffs; no compensation/speed promise is marketed. A future switch to live wording requires actual production workflow/policy verification.
- Seller age/payout eligibility: handled by existing seller/payment-provider terms and setup; the landing page does not invite minors or bypass verification. No exact age/payout promise was introduced.
- Fees: existing Terms remain the source of truth. No0%/3% promotion is copied into evergreen campaign text; advertising any fee promotion later requires current eligibility/copy approval.

No answer to these questions is needed to review this conservative draft. Publication approval is the remaining release decision.
