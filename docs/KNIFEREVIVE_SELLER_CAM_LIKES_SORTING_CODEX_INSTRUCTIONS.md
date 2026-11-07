# KnifeRevive — Seller Cam Likes, Popularity Sorting & Discovery Ranking
## Codex implementation addendum

**Prepared:** November 7, 2026  
**Status:** Implementation instructions — NOT a claim of deployed changes  
**Project:** KnifeRevive (WordPress / WooCommerce / Dokan / custom ListLab and Seller Cam components)  
**Prior brief:** [KNIFEREVIVE_SELLER_CAM_CODEX_IMPLEMENTATION.md](./KNIFEREVIVE_SELLER_CAM_CODEX_IMPLEMENTATION.md)

> **Codex kickoff prompt:** Audit the current KnifeRevive repository and live public UX before editing. Implement the product-linked likes, guest counts, account-specific liked state, sorting options, and Seller Spotlight discovery rankings described below as an additive, production-safe extension of the existing Seller Cam system. Preserve the previous Seller Cam brief, current WooCommerce product model, seller identity, storefronts, media, and inventory eligibility. Work in small testable phases, document actual file paths and data-model findings, and do not deploy without authorization.

## 1. Product intent and scope

Seller Cam is a **video-first presentation of a real, purchasable WooCommerce product**, not a standalone social video. The same product may be shown in the homepage “See it before you buy it” section, the dedicated **/seller-spotlight/** page, the seller’s storefront, and its canonical product page.

Add one consistent, trustworthy **heart/like relationship between a logged-in user and the underlying product ID**. Show the same count and viewer-specific state on each surface. Likes are engagement signals, **not** actual purchases, quality guarantees, seller ratings, or verified favorites.

**Target public pages to inspect:**
- https://kniferevive.com/ — “See it before you buy it” / Seller Spotlight reels
- https://kniferevive.com/seller-spotlight/ — video-led discovery and sorting
- Actual Dokan seller storefront URLs/templates discovered in source
- Canonical WooCommerce product pages and relevant Shop/category/search catalog views

**Audit requirement:** The research brief and conversation describe these surfaces, but this document does not assert inspected current code paths or installed sort controls. Codex must capture current screenshots (desktop/mobile), labels, carousel behavior, video source metadata, product IDs, query architecture, caches, and actual storefront hooks before making changes. Reuse existing video fields, not guessed metadata names. Do not invent a separate “video post” object.

## 2. UX recommendation — one clear heart, under every reel

Add a compact engagement/action row **immediately below each video card**, not floating over important product details, on both the homepage reels and /seller-spotlight/. Reuse the same component on seller storefront Seller Cam rows and optionally product pages. Do not show a like action for a card lacking a real eligible product.

Recommended row:
```text
[♡  12]     [View item →]     (optional: product price)
[♥  13]     [View item →]     (liked by this user)
```
Counts are illustrative only, never manufactured. Preserve seller identity, product price, and shopping CTA without making the row tall.

**States and behavior**
- **Not liked, signed in:** outlined heart; public count; clickable button. Accessible name “Like [product title]”; `aria-pressed="false"`.
- **Liked, signed in:** filled heart; same public count increased once; clickable to unlike. Accessible name “Unlike [product title]”; `aria-pressed="true"`. Retain on reload, across devices/sessions, and across all places the product appears.
- **Guest:** public real count visible. Heart may remain a button that opens a lightweight “Sign in to like this item” prompt with login link and return URL; **do not silently increment a guest like**. Make interaction explicit rather than presenting a mysteriously disabled heart. No signup wall for viewing/browsing/buying unless existing commerce rules require it.
- **Loading/error:** disable repeat taps while mutation is pending; maintain layout; optimistic update only with rollback on error, and reconcile to server-returned authoritative count and liked state. Announce result unobtrusively with accessible status text.
- **Zero likes:** show `♡ 0` (or hide the numeral only if usability tests support it); never create fake social proof or “LIVE”/viewer counts.
- **Keyboard/touch:** actual `<button type="button">` with >=44×44 CSS pixel target, visible focus, tooltips as enhancement only, and text/ARIA state independent of color.
- Do **not** conflate a heart with “Save for later” unless that feature is deliberately merged or separately designed.

**Color:** Recommend a **warm coral/pink active heart, e.g. #F43F5E** against an appropriately contrasted surface, rather than per-seller green. Inactive outline should be a neutral gray/white that passes contrast on the current card background; active heart remains the same recognizable global semantic color even when seller-specific video frames are metallic green, blue, etc. Add subtle 140–180ms scale feedback only on deliberate taps, respect `prefers-reduced-motion`; never make hearts permanently pulse or flash. Verify text/icon contrast in the actual theme (do not assume a sample hex passes WCAG everywhere). Seller branding applies to frames, not the meaning of the shared like action.

**Voice / Gen Z-friendly without forced slang:** concise, upbeat, shoppable: `Seller Cam`, `See it before you buy it`, `View item`, `Most liked`, `Trending`, `New drops`, `Most viewed`. For sign-in: `Sign in to drop a like`. For empty collection: `No videos yet — check back for fresh finds.` Avoid scattering “fire,” “vibing,” “slay,” excessive emoji, or false urgency through every control. Screen-reader labels should remain literal.

## 3. Sorting UX — define exact sorts, don't rename different algorithms alike

### Dedicated /seller-spotlight/ discovery page
Recommended **compact, horizontally scrollable chips/tabs** directly under page intro and above the video grid/carousel:
1. **Trending** — default **blended discovery** score (Section 5), not raw like count.
2. **Most liked** — descending **lifetime authentic likes**, deterministic ties: qualified views, then published date, then product ID. Do not call this “Trending.”
3. **New drops** — newest eligible product/listing videos first, use a documented date source (video activation date if reliable, otherwise product publish date; pick one).
4. **Most viewed** — descending **qualified video views** (if available), tied by recency. If measurement is not ready or population is empty, **hide this option** instead of showing a meaningless tab.
5. Optionally **For you** only if actual consent-aware per-user personalization exists; do **not** label a globally scored feed “For you.”

Tabs must work on mobile, with clear selected state (`aria-current` or tab semantics if using an actual tab panel), update the URL (`?sort=trending|most-liked|new|most-viewed`), preserve back/forward/navigation, and work with pagination or load-more. The selected sort should remain stable across page navigation. Ensure server-side ordering actually drives every page; do not sort just the first visible page of results in JS. Keep seller diversity in **Trending only** or as an explicit feed policy: strict **Most liked** must remain honestly descending, not secretly reshuffled.

### Homepage “See it before you buy it”
Keep a compact, curated **Trending** carousel with the already-approved multi-seller interleaving rule (1 seller: show their eligible items; 2: alternate if possible; 3+: first 3 from distinct eligible sellers when possible). Avoid putting four competing sort controls on a small carousel. Add a `See all Seller Cams` link to /seller-spotlight/ where visitors can choose sort. If product count grows enough, a simple optional sort control can be tested later.

### Shop, categories, search results, storefront products
Add **Most liked** in the existing WooCommerce sort dropdown **without replacing standard sort options** (default, newest, price, rating, etc.). Use exactly the same underlying product like count. For catalogs containing products without Seller Cam video, their like count may be zero; decide after existing favorite/like-system audit whether all products can receive likes from their product pages. **Preferred coherent model:** likes are associated with products, and a product page supports liking regardless of whether it has a video; Seller Cam only shows products with eligible videos. If an existing favorites service exists, audit data semantics first and do not create conflicting duplicate hearts.

**Do not add a universal “Trending” shop sort** until the user experience and data coverage justify it; sitewide **Most liked** is the initial requested behavior. For seller storefronts, preserve existing Dokan sorting and add the option only if supported safely by the actual query.

### Sort examples
| UI text | Exact behavior |
|---|---|
| Trending | Blended qualified engagement + freshness + diversity policy |
| Most liked | Raw unique-user like_count DESC, deterministic ties |
| New drops | Product/video publish date DESC |
| Most viewed | Qualified distinct-ish video views DESC |
| Popular | Avoid ambiguous label when an exact “Most liked” sort exists |

## 4. Data model, security, and count integrity

**Canonical engagement entity:** WordPress WooCommerce `product_id` (normalize variation IDs to parent when video belongs to parent). One active like per `(user_id, product_id)`, shared across surfaces. Do not store standalone carousel likes or per-session likes.

**Recommended schema (audit existing services first):**
- Custom table `${wpdb->prefix}krev_product_likes` with `user_id BIGINT UNSIGNED`, `product_id BIGINT UNSIGNED`, `created_at DATETIME`, `PRIMARY KEY (user_id, product_id)`, plus index on `product_id`.
- For cheap ordering, maintain derived `_krev_like_count` product metadata or a dedicated rollup table, **never trust it as the sole record of individual likes**. Update counts transactionally/atomically (or recompute on mutation and reconcile), guarantee nonnegative counts, and provide repair/recount job. If WordPress DB transaction behavior or table prefix differs, adapt to actual environment.
- Avoid huge arrays of product IDs in `usermeta`; avoid expensive count-per-product queries on every carousel render.
- Do not reuse customer account data beyond necessary authenticated IDs; do not expose lists of users who liked an item in public APIs. Expose only numeric count and `liked_by_me` for authenticated current viewer.

**API suggestions — exact routes depend on current plugin naming/audit:**
- `GET /wp-json/kniferevive/v1/engagement?ids=123,456`: batched public counts, current viewer states only when authenticated; validate max IDs, use prepared queries and only return public product data.
- `PUT /wp-json/kniferevive/v1/products/{id}/like`: explicit idempotent **set liked = true**.
- `DELETE /wp-json/kniferevive/v1/products/{id}/like`: explicit idempotent **set liked = false**.
Prefer idempotent set/unset over non-idempotent “toggle” to prevent accidental double actions/retries. Return authoritative `{product_id, count, liked_by_me}`. Names above are suggestions, not known existing endpoints.

Require login, WordPress REST nonce/CSRF protection and capability checks for mutations; validate real published eligible product and normalize ownership. For reading the count, guests must not need an auth nonce. Respect WordPress `rest_api_init` registration only — **never** prematurely register REST routes from `plugins_loaded` (known ListLab critical-error class). Enforce per-IP/user throttling, CSRF protection, input limits and idempotence. Disabled products should not acquire new likes; optionally preserve historical likes so restored products retain genuine engagement. Define cleanup policy for permanently deleted products and account deletion/privacy exports.

**Important cache rule:** Cache public aggregate counts separately from viewer-specific `liked_by_me`; never CDN-cache one logged-in customer's heart state and show it to a different user. After like/unlike, update/expire public counts on all surfaces (or tolerate a short documented stale count with mutation response authoritative), invalidate popularity ranking caches, and handle stock/product status cache transitions. Server-side product queries must use efficient joins/indexes or a lookup table, not an unindexed `meta_query` over an unbounded marketplace.

## 5. Seller Spotlight ranking — practical cold-start algorithm

Use **two distinct concepts**:
- **Explicit Most liked sort** = exact total likes descending.
- **Default Trending** = a scored recommendation feed balancing **quality engagement**, **qualified views**, and **freshness**. Avoid popularity monopoly and don't hide new eligible listings just because they have zero likes.

### Define qualified views first
A video impression is not a view. As MVP, count a **qualified view** only when the video is actually visible >=50% of its area, plays >=3 seconds (or >=50% duration for videos shorter than 6 seconds), and is not muted autoplay in a hidden/background tab. Deduplicate repeated plays by the same visitor/product within a **24-hour window**, using privacy-conscious random session ID for guests (not fingerprinting), and authenticated user ID for accounts. Avoid sending raw IP in analytics; throttle abuse. If actual watch instrumentation is absent or untrustworthy, **turn off Most viewed** and set view input to zero until available. Counts displayed to users should say exactly what they measure; don't show “live viewers.”

### Transparent MVP scoring proposal
For product p, use only eligible products:
- `L7` = unique users who liked within last 7 days (if historical like timestamps available; else use authentic total likes with weaker weight).
- `V7` = qualified deduplicated views in last 7 days.
- `A` = age in days since eligible product/video publication.
- `E = (L7 + 1) / (V7 + 20)` = smoothed engagement rate; denominator guards tiny-sample spikes.
- `F = exp(-A / 14)` = freshness decay (14-day characteristic scale).
- `TrendingScore = 4 * ln(1 + L7) + 1.2 * ln(1 + V7) + 3 * E + 2 * F`.
Scores are **initial heuristic, not proven optimal**. Use documented, configurable weights, not magic constants scattered across templates. Optionally add small capped cold-start boost to products with <10 qualified views during their first 7 days; never boost ineligible/out-of-stock products.

**Why it works:** likes are a stronger signal than views; views offer fallback when likes are absent; freshness ensures brand-new listings get discovery; logarithms prevent one heavily-viewed product from dominating forever; smoothing prevents 1 like/1 view from appearing unbeatable. When every item has zero views/likes, default ranking becomes freshness-first; apply seller interleaving and stable product-ID tie-breaker. When there are likes but no qualified views, use counts/freshness and mark engagement rate as unavailable rather than assuming views; keep the score function well-defined.

**Diversity and fairness:** On Trending only, produce ranked candidate pools **per seller**, then interleave: avoid adjacent same seller where alternatives exist; favor distinct sellers in first three slots if 3+ have inventory. Within each seller, preserve score order. Do not fabricate diversity or quietly alter explicit Most liked ordering. Deterministically seed stable ordering for one page/session to avoid jumpy results. Limit seller exposure only after ensuring basic quality/availability checks and measure impression share by seller, not protected demographics.

**Illustrative cold start (NOT site metrics):** New listing 0 likes/0 views may surface via freshness; older listing 0 likes/15 qualified views can surface via view evidence; listing with 5 genuine likes likely ranks strongly even with fewer views; a seller with many uploads should not occupy every homepage slot if others have eligible products. Monitor exposures, likes per qualified view, clickthrough to product, add-to-cart, conversion, latency, and out-of-stock leakage. Avoid ranking solely by total views, which rewards old inventory.

**Refresh cadence:** Recompute cheap rolling aggregates/ranking periodically or on events; invalidate on like/unlike, product publish/unpublish, stock transition, seller disable, and media status. Don't sort a huge unsliced list client-side. Implement pagination and avoid duplicates across pages as much as possible.

## 6. Inventory and video eligibility

Apply the eligibility rule from the prior Seller Cam brief **before** ranking or displaying. A card needs a valid product-linked video, published/public/not archived product, enabled Dokan seller, and effective WooCommerce in-stock/purchasable status (using WooCommerce APIs; respect variable products and stock semantics). Out-of-stock videos disappear from all public discovery immediately/after explicitly bounded cache expiry and **reappear automatically on restock** retaining historical authentic likes. Product pages may remain viewable with their historical count if WooCommerce exposes them, but unavailable products must not appear in Seller Cam discovery. No fabricated “LIVE,” simulated chat, fake viewers, or fake like counts.

## 7. Implementation order

**Phase A — audit** existing homepage and /seller-spotlight/ templates, Seller Cam component, product-to-video association, seller storefront, WooCommerce sort implementation, active plugins, current reactions/favorites, and cache behavior. Identify actual files, hooks, postmeta, JS patterns, database strategy and risks. Capture screenshots and a brief architecture map. Do not invent hook names.

**Phase B — backend foundations** introduce audited schema and batched counts, idempotent endpoints, nonce/auth and guest-safe reads, count reconciliation and tests. Implement stock/status eligibility checks.

**Phase C — UI and states** add below-reel hearts on homepage, /seller-spotlight/, seller storefront and product pages as appropriate; signed-in persistence, guest sign-in CTA, animations/ARIA and mobile. Ensure same canonical count everywhere.

**Phase D — sorting** add explicit Most liked WooCommerce ordering to shop/categories/search/seller products where supported. Add Seller Spotlight Trending, Most liked, New drops; add Most viewed only after qualified view measurement works. Honor deep links, pagination, empty states and cache keys.

**Phase E — ranking/instrumentation** deploy qualified views privacy-safely, blended Trending score, diversity interleaving, monitoring, dashboards as appropriate. Benchmark before/after Core Web Vitals, SQL query count, cache hit rate and mobile speed.

**Phase F — QA and rollout** staging or controlled flag rollout; unit/integration/browser tests with multiple sellers; rollback, migration plan, production enablement only after explicit approval. Keep previously working order flow and Seller Cam configuration untouched.

## 8. Acceptance tests (Codex must demonstrate)

- [ ] Guest sees authentic public total likes on every eligible reel; can still watch/shop; tapping heart requests sign-in without recording a guest like.
- [ ] Signed-in User A can like/unlike; survives reload/device/session; User B initially sees separate liked state but the updated shared total.
- [ ] Rapid taps, retries and concurrent users cannot create double votes, lost counts or negative counts.
- [ ] Same product shows **one identical count** and user-state across homepage, Seller Spotlight, storefront and product page.
- [ ] Heart styling communicates liked/unliked state independent of seller frame color; accessible label, focus, `aria-pressed`, touch target and reduced-motion all pass.
- [ ] Exact Most liked ordering works on all implemented surfaces; ties deterministic; default Trending is honestly identified and not confused with exact Most liked.
- [ ] New drops is chronological; Most viewed appears only with verified qualified-view instrumentation.
- [ ] New zero-like videos can be discovered; seller interleaving works on Trending for 1, 2 and 3+ sellers; explicit Most liked remains numeric.
- [ ] No video from out-of-stock, unpublished, archived, disabled seller or deleted product appears; restock restores eligibility without recreating video and without losing authentic likes.
- [ ] Sort selection persists in URL, pagination and back/forward; server sorting isn't limited to only visible page.
- [ ] Guest aggregate response is safe to cache; user-specific liked state is never leaked via shared/public cache.
- [ ] Per-user likes cannot be altered by forging someone else's `user_id`; account permissions, REST nonces and CSRF rules are enforced.
- [ ] Pagination, query timings, video bandwidth, Core Web Vitals, mobile iOS/Android performance and cache invalidation have before/after evidence.
- [ ] Tests demonstrate WooCommerce, Dokan seller stores, ListLab editor, Seller Cam custom appearance, checkout and shipping remain functional.
- [ ] Deployment and rollback instructions documented; no silent production deploy or destructive historical data reset.

## 9. Deliverables and change constraints

1. **Audit report** with exact inspected code paths/templates, active plugins/versions, schema and baseline screenshots.
2. **Implementation proposal** and concrete file-change list, migrations and backout procedure.
3. **Code + tests** for likes and correct sorted catalog/discovery feeds.
4. **QA record** (logged out + two test users + two/three sellers + stock transitions + mobile + keyboard).
5. **Performance/privacy notes** and monitoring plan.
6. **No production deployment without approval.** Avoid modifications to WordPress/WooCommerce/Dokan core. Reuse current theme/component architecture and hook patterns. If previous Seller Cam brief is only partially implemented, explicitly report the dependencies and implement in dependency order.

## 10. Research provenance / external references

**Observed competitor documentation (not evidence KnifeRevive already behaves this way):**
- Mercari Help Center explicitly distinguishes **Views** (where listing was shown), **Clicks** (listing opened), and **Likes** (shoppers who hearted an item): https://www.mercari.com/us/help_center/article/332/ . This is why KnifeRevive must not use impressions as video views.
- Mercari product listings can display total “Likes” alongside a heart, including zero: https://www.mercari.com/us/item/m14483922539/ (example observed in research; listing may change/disappear).
- TikTok Shop and Whatnot were requested as inspiration for shoppable, concise mobile video interactions and creator/live presentation. **Do not claim identical current platform-specific algorithms or exact placement from this document; verify live public interfaces or official material during implementation.** Whatnot live broadcasts are distinct from KnifeRevive's prerecorded Seller Cams.

**Design decisions here are recommendations, not reverse-engineered competitor behavior:** coral heart color, ranking formula/weights, 3-second qualified-view threshold, 24-hour dedupe, tabs, schema and endpoint names. Validate them against actual code, privacy and traffic.

**Do not imply “LIVE” on prerecorded product videos. Do not fake engagement.**
