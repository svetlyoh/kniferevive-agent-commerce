# Buyer–seller messaging implementation report

Date: 2026-10-25. Branch: `codex/buyer-seller-messaging-20261025`.

## Release status

**Messaging is prepared, not deployed.** The supplied brief, section 10, says: **“Do not deploy to production automatically.”** Production approval remains required after review of this release. No seller opt-in, live chat, advertising, payment, checkout or order email was changed for messaging.

The separate, directly requested **View Listing** Seller Orders change **is deployed** and live readback verified. Published products use their canonical URL even at zero stock. For unpublished products the link opens native ListLab only for a seller with edit permission; deleted or inaccessible records show “Listing unavailable.” Making a private listing public to satisfy an order-history link would be inappropriate. Eight tests cover publish/draft/pending/private/future/trash at zero stock, deletion and denied edit access. The exact production template was patched in place; the stale local full Seller Orders plugin was not uploaded.

## Verified baseline and implementation decision

See `BUYER_SELLER_CHAT_DISCOVERY.md`. Both reference listings currently show two legacy channel icons as a public guest. Therefore the reported missing-button issue was **not reproduced** for those current public pages. A signed-in art-page check also found them. Initial child-only inspection missed their sibling container and was corrected; it is not evidence of a live defect. Source inspection found scattered price/icon renderers, inconsistent owner hiding, a tiny icon-only action, and storefront contact depending on a first product. Those are the problems addressed.

Verified installed baselines: WordPress 7.1.3, WooCommerce 11.2.1, Dokan Lite 5.3.0/Pro 4.0.6, WhatsApp 1.0.4, Telegram 1.0.3, Storefront 1.0.10, Seller Orders 1.4.1 and the KnifeRevive child theme. Five production source baselines are recorded separately from feature changes. No deep-research report was supplied. No hosted staging account was supplied.

An isolated companion plugin was chosen because both messaging plugins expose contracts used elsewhere, while custom theme surfaces bypass their hooks. The companion retains those APIs/storage and replaces only competing frontend output. It does not replace the independent Order Messenger or Buyer Analytics (previously removed).

## Exact changes

### New `wordpress/kniferevive-seller-contact/`

| File | Responsibility |
|---|---|
| `kniferevive-seller-contact.php` | Bootstrap, dependency and HPOS declaration, two narrow public render bridges |
| `includes/Config.php` | Master/placement/analytics flags and optional seller allowlist |
| `includes/Resolver.php` | Server-owned product/vendor resolution, Dokan status, opt-ins, public canonical URLs and validated channel destinations |
| `includes/Renderer.php` | One secondary action, detail seller identity, generic storefront action, shared dialog and scoped assets |
| `includes/Plugin.php` | Woo classic/block integration and removal of competing legacy price renderers |
| `includes/Settings.php` | Own-account/Dokan settings, nonce/capability validation, legacy bookmark redirect, admin flags/diagnostics, cache invalidation |
| `includes/Analytics.php` | First-party consent checks, endpoint, HMAC dedupe/rate limit, aggregates and seven-day retention |
| `assets/contact.css`, `assets/contact.js` | Responsive chooser, no-JS fallback, keyboard/focus behavior, dynamic cards, optional nonblocking metrics |
| `README.md`, `CHANGELOG.md` | Setup, dependency, behavior and rollback documentation |

### Five integration overlays, not yet deployed

1. `theme-overlays/kniferevive/inc/product-subtitle.php`: Shop All/shared books contact renderer delegates to the canonical bridge; no duplicate controls or dead “not set up” placeholder.
2. `theme-overlays/kniferevive/inc/knife-card.php`: one readable action outside the old 22px icon/share container.
3. `theme-overlays/kniferevive/inc/annex.php`: three custom collection/tech/gallery/carousel contact paths delegate.
4. `theme-overlays/kniferevive/inc/account-avatar.php`: preserve existing ownership/nonces and meta; use strict shared number normalization and clearer opt-in notice.
5. `wordpress-overlays/kniferevive-seller-storefront/includes/class-krev-storefront-renderer.php`: actual seller-level generic contact, including an eligible empty store, independent of its first product.

The independent deployed order change is `wordpress-overlays/kniferevive-seller-orders/templates/seller-orders.php`. Differences already present in production (shipment summary/appointment initialization) are baselined separately, not new fulfillment work.

### Storage and hooks

No seller contact data migration or automatic opt-in. Reads/writes existing `_kniferevive_whatsapp_enabled`, `_kniferevive_whatsapp_number`, `_kr_telegram_enabled`, `_kr_telegram_mode`, `_kr_telegram_username`, `_kr_telegram_bot_username`; respects global `kr_whatsapp_enabled`/`kr_telegram_enabled`. Country codes are never guessed. Invalid enabled fields reject the save atomically.

Own option: `kr_seller_contact_v1`. Defaults: master OFF; detail/card/storefront ON behind master; analytics OFF; empty pilot list means all otherwise eligible sellers. No sticky bar is added. Activation creates only its own aggregate/dedupe tables (`{prefix}krc_counts`, `{prefix}krc_seen`), schema marker and account rewrite endpoint; seller choices remain untouched.

Hooks include `wp` priority100, `woocommerce_single_product_summary` priority31, `render_block_woocommerce/add-to-cart-form`, `render_block_woocommerce/product-price`, enqueue/footer, Woo account query/menu/endpoint, `dokan_settings_form_bottom`, `dokan_store_profile_saved`, metadata-change invalidation, REST registration and one optional prune cron. Server resolves product ownership with Dokan, including variation parent and authoritative vendor filter. Ineligible/disabled sellers fail closed; admin status alone is insufficient.

Saved contact/profile metadata coalesces product/transient invalidation once per seller/request and invokes `kr_seller_contact_settings_changed($seller_id)` for hosting integrations. No global cache bypass is added. Hosting/page/CDN cache behavior must be verified during the pilot; a local cache-invalidation unit check is not proof of production CDN purge. Existing Meta Controls can mark frontend responses private/no-store when configured, but this release does not assume that will always be present.

## Security, privacy and accessibility

- Canonical same-host public product context; no client-controlled external destination. Strict `wa.me`/`t.me` recipients; escaping and safe new-tab attributes. Hidden/private/draft/password-protected products have no public action.
- “Message seller” is secondary to purchasing. Visible disclosure keeps payment on KnifeRevive. No invented seller verification, response time or buyer-protection guarantee. Support link uses the existing Contact page; no unpublished Safe messaging page is claimed.
- Two channels use native modal with focus containment, Escape/close/focus return, labelled controls and mobile safe-area styles. Native details fallback remains usable without JS. One channel opens directly; Telegram copy fallback supplies context without promising personal-chat prefill.
- Scoped CSS matches actual `kr-tech-theme`: navy surface/light text on Tech, warm light surface elsewhere. No full theme or checkout layout replacement.
- Measurement is OFF, requires both client and server statistics consent and denies GPC. Meta advertising consent is not repurposed. No new SDK or consent provider. No raw phone/handle/email/IP/question/URL/session token in stored counts. HMAC dedupe and short-lived HMAC-IP rate keys are server-salted; endpoint rejects foreign origin/port, malformed data and obvious bots.
- Contact links work if metric delivery fails. Handoff records an activation, not message delivery, reply, app launch, sale or attribution.

## Measurements and exact KPI definitions

Baseline: both sampled public reference pages show legacy contact icons (2/2). This is a small inventory sample, not marketplace-wide coverage or conversion data. Existing `seller_contact_*` measurements do not exist; engagement is **unmeasured**, not zero. No traffic experiment was run and no improvement is claimed.

The first-party endpoint records the five specified events plus `seller_contact_eligible_view`, for a valid eligible rendered contact surface. Daily ephemeral session tokens are deduped across products/placement in separate all-product rows; per-product rows are diagnostic breakdowns. A blank-channel row is the overall unique count. Do not sum channel rows into unique people. Retention: current plus previous six UTC calendar days. No cross-device identity; daily session counts across dates are session-days, not unique buyers.

| KPI | Formula and available denominator |
|---|---|
| CTA visibility | Detail session-days with visible CTA / detail eligible-render session-days (`seller_contact_eligible_view`); denominator requires JS, statistics consent and eligible markup |
| CTA engagement | Overall CTA-click session-days / visible-CTA session-days for the same placement/date/device cohort |
| Channel handoff | Any-channel click session-days / CTA-click session-days; channel breakdowns are separately deduped |
| Seller activation | Active sellers with valid opted-in channel / active sellers eligible to configure messaging; admin reports a bounded inventory sample up to200 sellers, not all-site census |
| Seller coverage | Public eligible product views with CTA / all public eligible product views; **full view denominator is not collected by this contact-only endpoint**, so report inventory sample and eligible-render visibility separately, not a true all-view coverage percentage |
| Checkout guardrail | Add-to-cart/completed-checkout conversion in consented exposed traffic vs baseline/comparison from existing trusted commerce analytics; **not measured by this release**, no claim of attribution or winner |

Server sample also lists up to200 latest published products under current flags/pilot. State the sample size and date. Current admin aggregate is counts; the above formulas explain analysis, not guaranteed populated conversion metrics. Full-view coverage and a sufficiently powered experiment remain future measurement work requiring approval.

## Tests and evidence

All new PHP syntax checks and JavaScript syntax checks passed. Native isolated WordPress 7.1.2 (production7.1.3), Woo11.2.1, Dokan Lite5.3.0 and PHP8.4.16 (production8.4.27) were used; outbound WordPress HTTP/mail were blocked. Test prefixes/databases are isolated; production config was not loaded.

| Check | Result |
|---|---|
| Native contact integration | 77 passed: eligibility combinations, malformed values, disabled/admin/parent ownership, public/hidden/OOS, safe context, privacy/consent/GPC, origin/port/payload, dedupe, retention, settings authorization, cache hook, Woo block integration |
| Missing Dokan | Passed; fails closed |
| Order history listing links | 8 passed; live deployed template readback also matched |
| Headless installed Chrome | 69 checks passed |
| Headless installed Edge | 69 checks passed |
| Headless Firefox153 | 69 checks passed |
| Browser widths | 320,375,390,430,768,1280; chooser containment, touch targets, focus/Tab/Escape, no JS, lazy/carousel clones, consent, light/Tech dark contrast |
| Existing Agent Commerce native suite | 91 assertions passed with legacy storage; 91 passed with HPOS |
| Existing payload contract | 20 schema checks passed, then **pre-existing root/packaged AI.md mismatch** failed. Confirmed in base commit6bb1d62, left unchanged; hashes in `existing-contract-result.json` |
| Physical iPhone Safari / Android Chrome / VoiceOver / hardware keyboard zoom | Unverified; no access supplied. Browser responsive emulation is not physical-device certification |
| Full production Dokan Pro/ListLab/payment/email coexistence, hosted CDN opt-out | Not deployed or end-to-end verified; required pilot smoke checks below |

Evidence: `output/seller-contact/php-results.json`, browser-results-{chrome,msedge,firefox}.json, order-link-results.json, existing-contract-result.json and screenshots. Public before captures are GET-only. All **LOCAL-PROPOSAL** after images are isolated captured DOM layouts with sample channel destinations/disabled links; they do not show a production messaging deployment. Storefront after is a component placement proposal; actual template integration is in the supplied overlay. No chat messages, purchases or mail were sent. Private live Seller Orders proof is stored outside the public repository.

## Reproducible commands

Run on an isolated QA machine, never production. The bootstrap explicitly binds test MySQL10089, `krev_dispatch_qa`/`krchat_`; requires local WordPress core and installed Woo/Dokan test dependencies at paths documented in the bootstrap. It does not read wp-config. The existing API suite uses its separate sandbox MySQL11029.

```powershell
$php='C:/Users/Svet/AppData/Roaming/Local/lightning-services/php-8.4.16+1/bin/win64/php.exe'
$ini='C:/Users/Svet/Local Sites/kniferevive/app/public/.listlab-runtime-php.ini'
& $php -c $ini tests/seller-contact-integration.php
& $php -c $ini tests/seller-contact-no-dokan.php
& $php -c $ini tests/seller-order-listing-links.php
# Serve only generated fixtures, bound to loopback, while browser checks run:
# python -m http.server 8795 --bind 127.0.0.1 --directory .runtime/seller-contact-preview
$env:KREV_TEST_NODE_MODULES='C:/Users/Svet/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules'
$env:KRC_BROWSER_CHANNEL='chrome' # repeat msedge, firefox
& 'C:/Program Files/nodejs/node.exe' tests/seller-contact-browser.cjs
& 'C:/Program Files/nodejs/node.exe' tests/seller-contact-reference-screenshots.cjs
$env:KREV_TEST_DB_PORT='11029'
& $php -c $ini tests/integration.php 'C:/Users/Svet/Local Sites/kniferevive/app/public'
& $php -c $ini tests/storage-mode.php 'C:/Users/Svet/Local Sites/kniferevive/app/public' hpos
& $php -c $ini tests/integration.php 'C:/Users/Svet/Local Sites/kniferevive/app/public'
python -X utf8 tests/contract.py # requires cached sample payloads and schema dependencies; known baseline AI.md mismatch
```

## Staging/pilot deployment and rollback checklist

1. Owner approves final copy, pilot sellers, rollout date and production installation. Analytics/privacy approval is separate; proposed rollout leaves analytics OFF. No new privacy/terms page edits are included.
2. Back up DB, current messaging settings, five target source files and installed plugin/theme versions. Verify manifest normalized-LF baseline hashes against current production; rebase any intervening edits. Do not upload an entire stale theme or Seller Orders plugin.
3. Install/activate companion with master OFF, apply the five exact overlay files, flush only its account rewrite and purge host/page/CDN caches. Existing channel plugins remain active.
4. Enable approved pilot and placements. Test guest and owner, each channel combination, public/sold-out and hidden product, generic empty store, shop/gallery/books/Tech/related carousel cards, buyer messaging save/invalid/opt-out and old bookmarks. Confirm opt-out takes effect in **fresh cached guest** pages across every surface before widening rollout.
5. Smoke native Dokan settings, ListLab edit/import, Woo cart/variations/checkout and Agent Commerce goods handoff, seller orders and Order Messenger permissions/preview without placing orders or sending emails. Compare totals/shipping/payment choices; no changes expected.
6. Test physical Safari/Android/VoiceOver and enlarged text before claiming those devices passed. Record remaining issues; keep master OFF if a release gate fails.
7. Analytics remains OFF unless the consent owner approves and client/server statistics consent is verified through the site's real consent provider. Denied/GPC must yield no collection. Existing Meta-only choice is insufficient.
8. Immediate disable: uncheck master, purge caches. This hides the new UI. Restore prior UI by deactivating companion and restoring backed-up five overlays, purge caches, and verify legacy buttons plus checkout. Seller channel settings remain intact; do not delete opt-in meta. Deactivation clears only its own prune cron. Tables can stay for diagnosis; no uninstall purge occurs automatically.
9. Seller Orders rollback is independent: restore its one backed-up template if required. Messaging rollback must not remove the already-approved View Listing change.

Remaining owner decision: approve installation of the messaging companion plus narrow integrations with analytics OFF, and choose/confirm a pilot. A blank allowlist enables all otherwise eligible, already opted-in sellers; it never enrolls sellers automatically.
