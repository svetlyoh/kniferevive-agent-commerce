# KnifeRevive — Seller Spotlight & Video-Enabled Product Cards

**Codex implementation specification | 2026-11-04**  
**Status:** Approved product/UX direction; implementation pending source inspection and validation.  
**Platform:** WordPress, WooCommerce, Dokan, KnifeRevive ListLab, existing KnifeRevive product-grid/theme components.

## 0. Mission and non-negotiable constraints

Build **Seller Spotlight**, a video-enabled product discovery experience inspired by the appeal of Whatnot item demonstrations, **not** a live auction or streaming clone. Seller-uploaded, prerecorded product videos should enhance *existing* product cards and populate a dedicated, category-filterable Seller Spotlight page automatically. Purchases must occur on the existing WooCommerce product listing; never add a buy-now/cart checkout shortcut to Spotlight.

**Preserve existing listing, product-card, seller ownership, stock, pricing, attributes, descriptions, accessibility, and checkout behaviors. Homepage performance is a primary acceptance criterion.** Do not modify WordPress/WooCommerce/Dokan core.

### Confirmed decisions

- **One source video per listing**, authored in ListLab; do not introduce a parallel seller video library.
- **New/replacement video uploads:** maximum **15 seconds**; use compatible, efficiently compressed delivery format. Existing longer videos are **grandfathered** if playable; never delete, trim, or migrate them destructively.
- **Ordinary catalog product cards:** retain current photo and card layout. After ~**300 ms hover** on capable desktop pointers, replace only the photo region with a **silent, ~5-second teaser**. Stop and restore poster/photo on mouse-out or after teaser completes. Do not display timeline, volume, or player chrome on these cards. On touch, explicit play-icon tap starts teaser. Provide accessible way to open the full demonstration in Spotlight. Keep ordinary product navigation available.
- **Dedicated Seller Spotlight page:** more generous video viewing area than ordinary product cards, with optional sound and standard accessible playback controls, and a **discreet product strip immediately below video** showing name, current price, seller (if appropriate), and **“View Item”** link to the full listing. Do not cover merchandise with large CTAs.
- **Discovery:** tabs **All | Knives | Other Finds | Art | Technology**; use actual WooCommerce `product_cat` relationships, not duplicate category tables. Default All; retain tab and scroll position when navigating to a listing and back where practical.
- **Automatic eligibility:** published, publicly visible product, attached playable video, currently purchasable/in-stock, and if managed inventory is enabled, quantity > 0; exclude archived/hidden/draft/pending, out-of-stock, and backorder-only products. Verify Dokan author/vendor state and marketplace visibility rules. Automatically enter/leave feed when status, stock, category, or video changes.
- **Homepage:** small set (~4–5) of attractive **image-only** Seller Spotlight preview cards with a link to full Spotlight. No initial video requests or autoplay. Admin pinning is optional *future* work, not necessary to ship v1.
- **Performance:** use existing WordPress hosting/media delivery initially; **no Cloudflare Stream dependency or additional paid service**. Do not download video sources during initial listing/homepage render. At most one preview/video plays at a time; stop previous playback on hover change, category switch, scroll offscreen, page blur, unmount, or navigation.

## 1. First inspect the actual repository/site; do not assume undocumented video functionality

**Required before coding:** locate live source of the ListLab video field, its REST endpoints, media storage/meta keys, and product DTO; product card template(s) used by homepage, category archives, search, vendor storefronts, custom grids; theme CSS aspect ratios; existing product page video implementation; WooCommerce stock/visibility logic and relevant caches. Search all custom plugins and active theme/child theme for video storage and rendering. Assess current WordPress.com host restrictions, allowed MIME types, `ffmpeg` availability, PHP execution limits, media upload limits, cron/background jobs, and permissions. Run a baseline mobile/desktop performance profile.

The supplied ListLab **v1.1.3** technical report describes **image-only** media endpoints (`/kniferevive/v1/media` accepts JPEG/PNG/WebP) and **no documented video upload**, so the actual current repository may be newer. **Never assert video upload exists solely from the report.** User reports one video field now; inspect production/current code, adapt existing field if found, or add exactly one backward-compatible field if not. Do not overwrite image upload behavior. Keep seller and media ownership gates.

Before editing, produce a concise implementation map: current file paths, actual video metadata, existing behavior, host transcoding capability, and planned changes. If a dependency cannot be verified, implement a safe supported fallback and document it.

## 2. Seller recording/upload and media processing

- Provide one video per WooCommerce product in ListLab, preserving current product-video association where present. Support phone camera capture (`accept` and capture hint where appropriate) **and** upload from library; do not require an in-browser video editor in v1.
- New uploads: enforce **duration <=15 seconds server-side** (not only JavaScript), validate actual MIME/container/codec by media probing rather than trusting filename; max bytes should be a configurable safe budget based on actual host constraints. Return clear errors with corrective action. Preserve draft workflow, seller ownership, current product update/edit permissions, and media access checks.
- **Recommended delivery target:** progressive MP4 (H.264/AVC + AAC when audio present; web-optimized `moov` atom/faststart), 720p maximum for *new delivery variants* (maintain input proportions, never upscale), 24–30 fps, roughly 0.8–1.5 Mbps video bit rate as a starting target with quality tuning. These are targets, not guarantees; set a practical byte budget after testing with real knives/coins/jewelry where close details matter. Do not degrade fine defects to meet arbitrary size. Original 9:16, square, and landscape orientation must be supported. Preserve audio on the full video; **teaser is silent with no audio track**.
- Create a separate **up-to-five-second teaser** derivative (e.g., compact MP4 H.264, lower resolution/bitrate appropriate for product card) with a first-frame/poster image. **Do not rely only on a JS `pause()` at 5 seconds**: that does not guarantee limiting transferred bytes. Aim for <~0.3–0.7 MB teaser when practical; validate actual quality and sizes.
- Use server-side FFmpeg only if confirmed available and safe, ideally asynchronous/background processing with bounded jobs and retriable failures. **Never assume FFmpeg exists on WordPress.com hosting.** If unavailable, propose/implement an appropriate privacy-conscious **browser-side transcoding** flow only after proving acceptable performance on real phones, or use a graceful v1 fallback: existing full video remains available on product page/Spotlight and normal cards remain photo-only until a compliant teaser is produced. **Do not silently stream an entire long original video into the hover card.** Clearly surface processing status and any failures to sellers/admins.
- For grandfathered existing videos >15 seconds: preserve intact and associate them with Spotlight if browser playable; generate teaser variants where possible; never reject unchanged saves to unrelated fields just because an older clip exceeds 15 seconds. Replacing an old video must satisfy new-upload limits. If incompatible, retain original association and show an admin/seller warning; avoid broken preview.
- Prevent duplicate attachments and orphaned derivatives; track original/teaser/poster attachment IDs and generation state as private product metadata or a similarly maintainable record. Keep media lifecycle, permissions, deletion/replace cleanup and retry behavior safe. No public unauthenticated upload endpoints.

## 3. Universal product-card enhancement

- Reuse current KnifeRevive card markup/components wherever possible. **Do not change image container footprint, aspect ratio, product details, price, condition, attributes, buttons, or card height.** Initial frame is standard product photo; overlay small, accessible video indicator only if usable teaser exists.
- Video fits *within* the existing media box (`object-fit: contain` recommended for accurate condition presentation), centered with neutral letterbox if aspect ratios differ. Never stretch, clip a blade edge, or force videos to match poster crop. Absolutely no CLS/layout jump.
- Desktop: only with `hover: hover` and `pointer: fine`, wait ~300 ms of intentional pointer presence, then attach teaser URL and play **muted, playsinline**; stop and restore image at earliest of 5 seconds, leaving card, player replaced, reduced-motion change or page backgrounding. On frequent mouse passes, avoid speculative downloads; reject stale async `play()` promises. Do not replay constantly during one stationary hover. Keyboard focus must never trigger surprise motion; accessible explicit preview button may be used.
- Touch: retain still thumbnail; distinct tappable **Preview video** icon launches five-second muted inline teaser. Separate **Watch Full Video** affordance opens that same product anchored/selected in Seller Spotlight; product/title/image link to normal product detail remains obvious, with no click hijacking.
- No audio controls on teaser. Do not show controls UI on card. Respect `prefers-reduced-motion` (no hover autoplay; explicit tap only), data-saver/network conditions where detectable, visibility and battery considerations. Handle missing/unplayable video gracefully with original image.
- Ensure *at most one active video globally*, e.g., a shared preview controller/event bus scoped across all grids and Spotlight, destroying sources and releasing resources on disposal. Loading one teaser must not trigger downloads for its neighbors.

## 4. Dedicated `/seller-spotlight/` page

Create a normal WordPress page (or stable registered endpoint/shortcode/block) named **Seller Spotlight — Watch & Shop**, preserving existing header/footer/navigation and theme styling. Use existing products and the **same single product video**, never duplicate catalog records.

- Responsive visual layout: generous portrait-friendly video windows, approximately two columns desktop and one column phone as a starting **design hypothesis**; tune via testing, not hard-code blindly. Larger playback frames than standard cards, support original orientation with `object-fit: contain` and no cropping. Optional featured carousel may be added if justified; avoid duplicate clutter/weight in v1.
- Tabs **All, Knives, Other Finds, Art, Technology** at top; horizontal scroll for tabs on narrow screens and clearly selected state. Filter dynamically without full-page reload while retaining URL/query-state for deep linking and back navigation. Use actual product category IDs/terms determined by the site, not guesswork on slugs. Other Finds may include collectible coins, jewelry, etc.
- Use posters until a buyer intentionally plays. For **full Spotlight video**, use visible play/pause, replay, seek where supported, volume/mute, captions where provided. **Start muted by default**; only unmute by deliberate user action; no simultaneous audio. Browser autoplay permissions must never block basic use.
- Compact product strip below, not over video: title, current price (including sale where applicable), seller name if appropriate, **View Item →** link to real single-product URL. No add-to-cart action on Spotlight. For sold-out/unpublished mid-session, remove or gracefully update before purchase; ultimate product page enforces availability.
- Preserve selected tab, item focus and scroll position on back navigation (history state/session storage with safe fallback). Avoid navigation traps and provide canonical/product deep link.
- Stable browsing order and pagination/infinite loading in **small batches**, with duplicate avoidance. No loading all video records or video sources at once. Consider ~12–24 product cards per request, adjust empirically. Use existing WordPress query/REST patterns with pagination, safe escaping, public permissions, and no exposure of private products. No unnecessary new DB tables.

## 5. Homepage discovery

Insert a restrained **Seller Spotlight / Watch & Shop** section into homepage consistent with KnifeRevive styling, using approximately four to five eligible listings shown as **still posters only**, plus an **Explore Seller Spotlight** link. Responsive horizontal scrolling or grid as appropriate. Do not render `<video src>`/iframe/player bundles eagerly. Defer below-the-fold images; do **not** lazy-load above-the-fold LCP image. Cache only IDs/metadata from qualifying listings, refresh/invalidate on product publish/unpublish, edit, stock update, video replacement, category change, visibility/archived change, or seller restriction. Confirm current pricing/availability at request/render time as needed, without an expensive per-card query loop.

## 6. Eligibility and synchronization

Use WooCommerce-supported product APIs and standard WP/Dokan author/access checks. Qualifying products: `publish`, public/search-visible, attached compatible video, `is_purchasable()`, `is_in_stock()` and if stock managed `get_stock_quantity()>0`, plus no backorders-only qualification. Evaluate edge cases (variable products, product-level and variation inventory, reservations/stock sync, Dokan suspended stores, private listings) explicitly. Prefer one efficient query with bounded pagination and reliable exclusion/filtering, plus final product-level validation where essential. Avoid N+1 REST calls. Create a cache invalidation matrix and tests.

## 7. Performance and privacy budgets

- **Absolutely no autoplay or video download on initial homepage/category render**, including `preload=metadata` fetching bytes prematurely. `preload="none"`, do not assign `src` until intended activation; posters/images lazy-loaded appropriately.
- No teaser download until hover dwell/tap. One active player at a time; cancel outstanding pending loads on leave/category switch; preload **posters only** for likely next cards, not original video files.
- Keep bundles small and page-scoped; do not enqueue full Spotlight player code on ordinary listings if not necessary. Prevent CSS/JS conflicts via namespaced assets.
- Benchmark before/after on mobile throttled connection and desktop: LCP, CLS, INP, JS transfer, network waterfall, number of video requests without interaction (**must be zero**), total bytes, and time from hover/tap to first frame. Set realistic performance budgets based on baseline; no regressions to existing site speed accepted without explicit signoff.
- Test many product cards and concurrent visitors. Media requests use normal auth-free public CDN/static hosting where permitted by host; avoid proxying video byte ranges through slow PHP endpoints. Support range requests/content-type and browser caching if host allows. Do not install paid infrastructure by default.

## 8. Accessibility and reduced-motion behavior

- All video interactions keyboard and touch accessible; controls labeled (`Preview video`, `Watch full video`, `Pause`, `Mute`, `View Item`); visible focus states; no hover-only access to essential information.
- Respect reduced motion, no audible autoplay, minimum touch target sizes, readable contrast, screen reader headings and tab state. Five-second teasers must truly stop and revert; no repeating animated previews or cycling autoplay grid.
- For narrated full videos, allow sellers to attach captions/transcript or provide a clearly described path for adding accurate text alternatives; identify WCAG captions requirements and document limitations before launch. Don't claim a pass if no caption option exists.
- Handle play promise rejection and browsers that disable autoplay: show poster + play icon. Avoid surprise music and abrupt playback on tab switch.

## 9. Security, compatibility and maintainability

- Seller identity derived server-side (`get_current_user_id()` / existing ListLab auth), never trusted from posted seller ID. Verify product ownership, nonce, MIME/duration, upload quota, product edit permissions; sanitize/escape URLs and titles. Restrict bulk media generation and do not expose draft/hidden assets in public feed.
- Maintain compatibility with existing image upload, Dokan vendor accounts, WordPress.com hosting, all existing gallery/product templates, WooCommerce product detail, product grid custom plugin, SEO metadata, caching, and REST route life cycle. **Only register REST routes on `rest_api_init`**, as documented in the ListLab critical-error fix.
- Prefer a contained feature module with clear interfaces and rollback plan. Do not rewrite ListLab or replace templates broadly. No new paid subscriptions/plugins without express approval.
- Version assets, write migration/backfill as idempotent actions, never destructively modify original video attachments. Ensure plugin-disable rollback leaves listings/photos fully functional.

## 10. QA matrix / acceptance tests

1. Existing listing with no video: exactly same photo/card and normal detail purchase flow.
2. New valid 15s video, portrait and landscape: seller upload; original + silent 5s derivative, full clip in Spotlight, same card dimensions.
3. New >15s video rejected server-side with clear message; replacing grandfathered clip requires <=15s.
4. Existing 30s+ playable clip remains attached, stays eligible, can receive 5s teaser; editing unrelated fields does not reject it.
5. Missing FFmpeg/processing failure: no site fatal, no bulky hover stream, photo-only card and diagnosable seller/admin state; full video where supported.
6. Hover under 300ms: no teaser network request; sustained hover: preview plays muted once and stops at 5s; leaving early cancels/reverts.
7. Mobile tap: no hover dependence; explicit teaser, full Spotlight CTA, normal View Item; iOS Safari and Android Chrome.
8. Multiple hovers/cards: exactly one active player; no simultaneous sound; category/tab/background clears active playback.
9. No video requests on homepage/category initial load; screenshot compare dimensions and CLS=0 for media swap.
10. Spotlight tabs and catalog mapping work for real categories; URL/back restores selected category/item where supported.
11. Product sold out, archived, unpublished, seller restricted, or video removed: excluded from feed, not shown stale after reasonable cache invalidation; restock re-eligible.
12. Price changes and sale pricing show current WooCommerce amounts; View Item leads to correct actual product; no direct add-to-cart in Spotlight.
13. Keyboard, screen reader, reduced motion, captions/accessible fallback checked; autoplay rejected case graceful.
14. Mixed video codecs, corrupt uploads, huge files, aborted uploads, repeat replaces, concurrent requests, and thumbnail-generation failures handled safely.
15. Baseline vs after performance results presented for desktop and throttled mobile; include network waterfall proof of **zero video bytes at initial render**.

## 11. Required Codex deliverables

1. **Discovery report:** actual code paths, media/meta association, observed versions, host capability checks, before/after design decisions, performance baseline.
2. **Implementation:** scoped plugin/theme/ListLab changes; responsive UI; media processing strategy; migrations/backfill and rollback if required.
3. **Test evidence:** automated unit/integration checks plus manual browser desktop/mobile QA, network recordings, stock/category/role edge cases, failure modes.
4. **Documentation:** seller upload help, site-admin troubleshooting, video presets, feature flags, what happens if teaser transcoding unavailable, data/backward compatibility and deployment steps.
5. **Final implementation report:** changed files, features completed, unresolved host constraints and follow-on options. Do not report tests as passed unless actually executed.

## 12. External standards and references

These support *general technical design*; they are not claims about the exact current KnifeRevive repository:

- [MDN: Autoplay guide](https://developer.mozilla.org/en-US/docs/Web/Media/Guides/Autoplay): muted or no-audio autoplay is more reliable; handle playback rejection, use `playsinline` where needed.
- [web.dev: Lazy loading video](https://web.dev/articles/lazy-loading-video): defer media downloads and use posters/preload choices; reference updated July 2026.
- [W3C WCAG 2.2 SC 2.2.2](https://www.w3.org/WAI/WCAG22/Understanding/pause-stop-hide): motion and user control; indirect hover-triggered movement can count as starting automatically.

**Product intent:** emulate the immediacy of a Whatnot seller showing a real item's details, with discreet product context; do not mimic auctions, live chat, scarcity timers, or misleading “LIVE” presentation.

---

### Suggested work order

1. Repository inspection + baseline measurements.
2. Media storage mapping, validation and teaser-processing feasibility spike.
3. Universal lightweight teaser behavior in one existing product-card component.
4. Seller Spotlight public page and filter/query implementation.
5. Homepage image-only discovery section.
6. Integration tests, accessibility/performance QA, deployment + rollback docs.

**Do not skip the initial inspection. In particular, confirm ListLab's current video field in actual source before implementation.**