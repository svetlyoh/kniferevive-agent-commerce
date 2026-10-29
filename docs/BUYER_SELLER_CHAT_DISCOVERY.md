# Buyer-to-seller contact discovery — 2026-10-25

## Verified production state

The WordPress.com connector confirms site236386850 / kniferevive.com. WordPress UI reports7.1.3; active WooCommerce11.2.1, Dokan5.3.0, Dokan Pro4.0.6, ListLab1.13.6, WhatsApp Seller Messaging1.0.4, Telegram Seller Messaging1.0.3, Seller Storefront1.0.10, Seller Orders1.4.1, Page Optimize0.6.4, Jetpack16.4-a.3, Meta Controls1.0.3. TwentyTwentyFour parent / KnifeRevive child theme confirmed from live body classes. Actual deployed contact helpers, price renderers, storefront renderer and four theme integrations were backed up through the authenticated native file editors. Local plugin mirrors are older in places; they are not treated as production snapshots.

Buyer Analytics was previously removed at owner request. Current Meta/Google conversion integrations are separate from the proposed first-party contact adoption analytics. The Meta-specific marketing choice must not be repurposed as permission for unrelated messaging analytics. This release will default analytics OFF, honor GPC, and require a statistics-consent integration; it introduces no third-party SDK.

## Reference-product findings

| Reference | Product/status/type | Authoritative public store | Legacy contact result as guest | Diagnosis |
|---|---|---|---|---|
| Jordan Ivanov Crescent Wound |2751 / publish / simple / in stock|ThriftCloset|Both Telegram and WhatsApp icons visible in the main price row|Missing icons not reproduced. There is no text Message seller CTA near the purchase decision, only small brand icons. They are siblings of the Product Price block, not children; querying only that block gives a false missing-button result.|
| Micron1100 SSD Cisco OEM |3381 / publish / simple /2 in stock|ThriftCloset|Both icons visible in the surrounding `kr-product-price-row--block`|Same structural issue. Initial child-only selector returned zero, but the actual price-row DOM verified both channels. No evidence supports claiming this live product is currently missing both icons.|

The signed-in art page also shows both icons. The helpers intentionally hide some controls when the current account equals the product author, while Shop All overrides that with `hide_for_owner=false`. This discrepancy is real source behavior; it is not proof that the current signed-in account owns either reference listing. The numeric author control was redacted by the browser; no attempt was made to bypass it. Store name/link are visible and both opt-in/validity checks passed, inferred from the actual helper-generated links. Exact private numeric author ID remains uncollected at this discovery stage; runtime resolution/tests use the actual product owner, not a hardcoded seller.

Public channel values and buyer details are deliberately omitted. Opening external chats or sending messages was not needed and was not performed. The old source's global flag, seller opt-in and valid destination must all pass to create each observed link. Browser-observed presence is a boolean diagnostic, not a claim of a real conversation or reply.

## Actual rendering paths and source-of-truth data

- WhatsApp plugin: `KR_WhatsApp_Helper`, `_kniferevive_whatsapp_enabled`, `_kniferevive_whatsapp_number`, `kr_whatsapp_enabled`; `KR_WhatsApp_Product_Button` owns classic loop/single prices and `render_block` product-price injection.
- Telegram: `KR_Telegram_Helper`, `_kr_telegram_enabled`, `_kr_telegram_mode`, `_kr_telegram_username`, `_kr_telegram_bot_username`, `kr_telegram_enabled`; `KR_Telegram_Product_Icon` replaces the WhatsApp price renderer and inserts Telegram in the block price row.
- Both resolve parent products for variations, then post authorship, with theme merchant filters. Legacy merchant filters permit some administrator sellers without checking active Dokan selling status. New code must require enabled Dokan seller status and use its product-owner resolver; do not infer ownership from a client event.
- Legacy WhatsApp normalization silently prepends1 to10-digit input. The new resolver/settings must never guess a country code; preserved canonical values remain in the existing meta fields.
- Legacy Telegram personal URLs include a text query. The new chooser must use the actual seller destination and provide Copy question, without promising personal-chat prefill. Seller bot mode uses existing `start=krp_ID` product-context convention.
- Theme `inc/product-subtitle.php`: `kr_shop_seller_contacts`, `kr_refresh_shop_seller_contacts`, block product-template filters. It currently shows a Messaging not set up placeholder and links to setup. New UI must hide ineligible CTAs instead.
- Theme `inc/knife-card.php`: builds separate Telegram and WhatsApp controls in the shared knife-card share container.
- Theme `inc/annex.php`: independent collection/tech/gallery/carousel price/contact builders; these require explicit integrations, not just a WooCommerce hook.
- Theme `inc/account-avatar.php`: storefront messaging editor already writes the same channel keys through existing ownership/nonce checks. Preserve it; add a better shared settings screen without a second data store.
- Storefront `KREV_Storefront_Renderer::contact_action` currently derives contact from the first product and exits for an empty store. It can fallback to a product Seller Info tab, creating an inconsistent/dead contact action. Replace only that renderer with a seller-level resolver.
- The live single-product layout uses WooCommerce Product Price and Add to Cart Form blocks; classic summary hooks alone are insufficient. The main CTA should be placed once after the block purchase form (even when its inner form is absent), plus a guarded classic-summary fallback. Card templates and related products use separate loops.
- Existing Order Messenger is seller-to-buyer order email. It is a separate feature; no recipient, permission, audit, email or payment code will be modified for messaging adoption.

## Architecture decision and rollout

Because rendering is scattered across two plugins and multiple theme templates, use the specification's isolated namespaced companion option: `wordpress/kniferevive-seller-contact/`. Keep both existing channel plugins for their current storage/contracts. Remove only their competing frontend price renderers while the companion is active, and delegate custom theme/card/storefront paths to one resolver/renderer. Deactivating the companion restores legacy fallback behavior; contact opt-ins are never migrated or auto-enabled.

New flags default master OFF, placements ON behind the master, analytics OFF, no new sticky purchase bar. An allowlist can stage rollout without opting any seller into a channel. No public Safe messaging page is claimed/published automatically; existing Help/Contact is available.

Production browser and source discovery only; there is no hosted staging account supplied. Automated tests will use an isolated local WordPress/WooCommerce database with outbound mail/network blocked, plus browser fixtures at required widths. Physical Safari, Android and Firefox are unverified unless actually exercised. No synthetic production purchases/mail. No deep-research comparison report was supplied or read.

## Separate owner-authorized Seller Orders patch

The user's direct request additionally authorizes View Listing beside purchased item title/quantity. This overrides the messaging brief's instruction not to edit Seller Orders only for this narrowly scoped, separately tested change. Production template was backed up and updated in place; no order reader, payout or fulfillment behavior changed. Published items retain canonical links at zero stock; unpublished items route to the private native ListLab editor only for authorized editors; deleted/inaccessible records show Listing unavailable. Eight isolated checks passed and live Cutco/order3415 readback confirmed the link.

## Measurement baseline

The existing UI has no `seller_contact_*` event instrumentation. Impressions/click/handoff baseline is **not measured**, not zero messages or zero engagement. Both sampled product details have visible legacy channel icons (2/2 sampled public products); this is not a marketplace-wide coverage estimate. No conversion improvement or A/B winner can be inferred. Proposed adoption measurement remains separately opt-in, off by default, and must not alter existing checkout conversion tags.
