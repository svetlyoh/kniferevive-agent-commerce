# KnifeRevive — Seller Cam: Codex Implementation Brief

**Date:** November 4, 2026  
**Status:** Approved product direction; implementation pending repository inspection  
**Platform:** WordPress, WooCommerce, Dokan, KnifeRevive custom plugins

## 1. Mission

Transform the existing homepage **Seller Spotlight** video carousel into **Seller Cam**: a compact, visually immersive, seller-branded, video-first way to shop *existing WooCommerce product listings*. Extend the same Seller Cam presentation to each Dokan seller storefront and add seller-configurable visual styling within seller settings.

**Fundamental product rule:** A Seller Cam is **not** a standalone video post or social media feed. Each video belongs to a specific sellable WooCommerce listing. Video is simply the lead media and browsing experience for that listing. Clicking/tapping the product call to action opens the underlying WooCommerce product page.

## 2. Inspect before editing (mandatory)

1. Locate existing homepage Seller Spotlight carousel implementation, templates, scripts, styles, player behavior, product-video metadata, and product-selection logic.
2. Locate Dokan storefront templates/routes and active seller settings integration (Dokan settings presently accessible through `/dashboard/#/`).
3. Inspect the ListLab integration at `/my-account/listlab/` and how product ownership and stock status are represented; reuse existing product-video storage rather than inventing duplicate video entities.
4. Identify active theme/child theme, custom plugins, asset pipeline, and caching behavior. Follow the site's established extension conventions. Do **not** edit WordPress, WooCommerce, or Dokan core.
5. Document actual inspected paths and existing product-video storage before coding. Avoid assuming any particular video field, endpoint, or theme hook exists.
6. Record a baseline for carousel size, height, loading behavior, and mobile performance.

## 3. Seller Cam player and shopping UX

- Rename the present formal **“Seller Video”** label to **“Seller Cam”** throughout the relevant experience.
- Preserve the section’s established marketing identity if desired: **SELLER SPOTLIGHT — See it before you buy it. Real items. Seller-shot videos. A closer look.**
- Use a **compact card height** on homepage desktop and mobile; the existing video frames are too tall. Maintain video aspect ratios with `object-fit: cover` or contained presentation as appropriate, without cropping critical product detail. Confirm sensible responsive dimensions based on the existing site.
- Prefer a social-style overlay over a conventional boxed media player: small **SELLER CAM** identifier, seller avatar/store name, optional product title and price, a minimal muted state control, subtle playback/progress indicator, and a clear **View item** action. Controls must remain accessible.
- **Never label prerecorded video “LIVE”** or use faux real-time viewer counts, chat activity, livestream timestamps, or similar deceptive signals. Movement and styling may evoke broadcast energy without implying a live transmission.
- Autoplay eligible **visible/active** muted videos when allowed by browser policy; pause offscreen videos, honor reduced-motion and data-saving preferences, and offer user-initiated play/pause plus mute/unmute. Avoid autoplaying audio.
- Do not download/play all carousel videos at once. Use poster images, lazy loading, visibility observation, and cleanup for unmounted slides.
- Make every playable card product-linked and expose accurate product name/price/availability from WooCommerce, not duplicated editable data.

## 4. Seller-specific appearance builder

Add a **Seller Cam Appearance** settings area integrated into the seller's current Dokan storefront/settings experience; discover actual extensibility hooks first. Sellers should configure appearance from **prebuilt choices** rather than writing code or uploading custom decoration files.

Configuration dimensions:

| Dimension | MVP control | Examples |
|---|---|---|
| Primary color | One palette/color choice | Automotive metallic green, cobalt, cherry red |
| Base pattern | One preset choice | Brushed metal, diagonal lines, fine grid, checker |
| Drawing/line motif | One preset choice | Pinstripe, angular outline, hand-drawn curves |
| Accent/light effect 1 | One preset choice | Soft glow, edge reflection |
| Accent/light effect 2 | One preset choice | Shimmer, highlight sweep |
| Motion style | One preset animation | Slow scan, flowing reflection, subtle pulse |

Provide a live preview, responsive preview option, restore-default action, explicit Save, and clear distinction between unsaved preview and persisted appearance. Support restrained combinations so patterns/effects never obscure the product or controls. Permit **None** for each optional effect/motif/motion. Validate contrast and maximum animation intensity. Defaults must be visually attractive.

### First seller default

**Thrift Closet** is currently the only seller known to have Seller Spotlight videos. Set or offer a branded preset inspired by a **1980s automotive metallic green finish** (deep green, brushed/metallic texture, subtle reflected edge light). Do not hardcode styling to this seller's account/name: use the ordinary seller metadata model, with a deterministic neutral default for everyone else. The seller can change these selections later.

### Style consistency

- Apply the same stored brand tokens on Seller Cam cards in homepage feeds and on the seller's storefront.
- Optionally echo restrained branded styling in the storefront Seller Cam section header; don't force it across the entire storefront or compromise product browsing readability.
- Only use server-approved preset IDs and sanitized colors. CSS variables are appropriate; do not accept executable CSS/JS from sellers.
- Store appearance **per vendor ID** (not per video, buyer, or product). Changing a preset updates every eligible Seller Cam owned by that seller.

## 5. Storefront Seller Cam section

Add a compact Seller Cam carousel/row to each Dokan seller storefront **above its standard product grid** and below the appropriate store identity/header. Only fetch/display videos associated with that seller's **eligible active product listings**. Do not introduce a separate upload flow for storefront video: derive from the existing listing-associated video. If a seller has no eligible videos, hide the entire section cleanly and leave the current storefront unchanged. Keep existing listing filters, grid, pagination, seller profile, and navigation intact.

## 6. Inventory and listing eligibility (critical)

A video qualifies for public feeds only when all conditions are met:

1. There is a real WooCommerce product associated with it and a valid, supported playable video.
2. The parent product is published, publicly visible, not archived/hidden, and permitted by existing marketplace moderation rules.
3. Its effective WooCommerce purchasability/stock state indicates **available/in stock**. Respect WooCommerce stock management and product type/variation semantics where applicable; do not rely on a hardcoded `quantity > 0` check for all types.
4. The vendor is enabled/allowed to sell, subject to current Dokan rules.

**Out of stock:** automatically omit the video from homepage, Seller Spotlight discovery, and seller storefront Seller Cam. **Restocked:** automatically include it again without a reupload. Deleted/unpublished/disabled listings and seller accounts must cease appearing. Ensure cache keys/invalidation respond to stock/status changes and confirm that expired cached results cannot show obsolete availability indefinitely. Recheck product state when creating product-linked output.

## 7. Multi-seller variety / feed ordering

Seller Spotlight homepage is a cross-seller discovery surface, **not** a contiguous block of videos grouped by vendor.

- **One eligible seller:** show that seller's qualified listings, without forcing diversity.
- **Two sellers:** alternate between them as far as product counts permit; avoid adjacent same-seller items when a different seller's item is available.
- **Three or more sellers:** prioritize at least three different vendors in the first three displayed cards when each has eligible inventory; continue round-robin/weighted interleaving rather than allowing one seller to dominate.
- Never fabricate products to satisfy the diversity rule. If one vendor runs out of eligible items, fill from remaining vendors gracefully.
- Keep ordering stable during a page session to avoid cards shifting mid-browse. Favor recency/quality within each seller's available videos, but do not give a single seller consecutive slots when alternatives exist.
- Each video retains its **own seller's** color/pattern/effects. To avoid competing motion, only the currently active/in-view card animates its decorative motion; inactive cards display **static** branded frames. Any number of cards may show static decoration simultaneously.
- A seller's storefront deliberately shows **only that seller's** videos and uses their consistent brand design throughout.

## 8. Implementation architecture / security

- Prefer an isolated, maintainable custom plugin or well-scoped existing KnifeRevive extension based on actual repository structure. Reuse current product video field/storage and carousel logic wherever safe.
- Seller appearance should be stored in protected vendor/user metadata or the site's established seller-settings mechanism. Version schema/defaults for future presets.
- On save, derive vendor identity from the authenticated user; enforce approved Dokan seller/owner permissions and nonce/REST CSRF protection. A seller must never modify another seller's branding by changing an incoming `vendor_id`.
- Sanitize and whitelist color, pattern, effect, motif, and animation preset values; escape all output. Restrict media/URL sources according to current site's safety model.
- If adding REST routes, **register only on `rest_api_init`**. The existing ListLab implementation previously experienced a critical failure from registering REST routes too early. Avoid modifying core plugins or internal rewrite lifecycle.
- Avoid new heavy animation/video libraries where CSS and browser APIs suffice. Enqueue assets only on pages displaying Seller Cam or its settings UI. Support caching and a clean absence-of-dependency fallback.
- Respect accessibility: keyboard-operable player controls, focus visible, meaningful labels, adequate text contrast, `prefers-reduced-motion` (static effects), no flashing, and no autoplay audio.
- Ensure desktop, tablet, and iOS/Android behavior. Handle browsers that disallow autoplay without making a blank frame.

## 9. Suggested phased work

**Phase A — Audit:** identify current file paths, schema, and behavior; publish an implementation map and a minimal-change proposal.

**Phase B — Shared Seller Cam card:** rename label, modernize compact video card/player, preserve item navigation, add eligibility filtering and reasonable lazy loading.

**Phase C — Appearance presets and controls:** seller settings, persistence, validation, preview, and per-seller styling. Include 1980s metallic-green preset for Thrift Closet.

**Phase D — Storefront:** inject vendor-specific Seller Cam above listing grid; hide when empty.

**Phase E — Discovery order, accessibility and performance:** add interleaving, active-only motion, cache invalidation, regressions, mobile testing.

Avoid a high-risk all-at-once rewrite; preserve current working homepage feed while incrementally integrating changes.

## 10. Acceptance tests

- [ ] Existing seller videos continue resolving to the same WooCommerce product listings.
- [ ] A product without a video does not create a Seller Cam card.
- [ ] An out-of-stock product disappears; after restocking it automatically reappears.
- [ ] Draft/private/deleted/archived and vendor-disabled products do not appear.
- [ ] Carousel uses 1-seller / 2-seller / 3+-seller interleaving rules.
- [ ] Seller Cam branding follows vendor across homepage and vendor storefront.
- [ ] Thrift Closet can choose/save a 1980s metallic-green preset and see it in preview and public cards.
- [ ] Seller A cannot modify Seller B’s appearance data.
- [ ] A vendor without eligible video has no empty storefront block.
- [ ] Only active cards animate; other frames remain visually distinctive but static.
- [ ] No false LIVE claims or synthetic real-time signals.
- [ ] Play/pause, mute, keyboard, reduced-motion, no-autoplay fallback are usable.
- [ ] Homepage video cards are demonstrably more compact than the current excessively tall versions.
- [ ] Video requests and decorative effects do not cause major regressions in load speed/Core Web Vitals; capture before/after measurements.
- [ ] Existing ListLab listing editor, WooCommerce shopping flow, Dokan store settings, shipping, and seller messaging remain functional.
- [ ] PHP/JS lint and relevant unit/integration/manual tests pass; provide evidence and rollback instructions.

## 11. Deliverables Codex must provide

1. Repository audit: actual inspected paths, current product video field, and affected hooks/components.
2. Implementation plan and list of modified/new files.
3. Working feature code with migration/default behavior and secure settings persistence.
4. Tests and concise QA report, including mobile, reduced-motion, stock transitions, and multiple-vendor ordering.
5. Deployment notes, caching guidance, and rollback instructions.

**Do not claim implementation complete until tested. Do not deploy production changes or alter sellers' saved settings without explicit authorization.**