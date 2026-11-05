# KnifeRevive Campaign Landing 1.0.0

Read-only campaign content for **Your next find. Your first sale.** Uses the owner-supplied Wüsthof, Fire TV and Cutco photographs, real WooCommerce listing status and the existing seller setup. No checkout, seller registration, product database, REST endpoint, inventory writes or payment integration is added.

**Prepared only. Not installed or published.** The owner’s brief requires approval before activation/publication. Installing this plugin does not create a Page or enable tracking.

## Install after approval

1. Back up the site database/theme. Install and activate `kniferevive-campaign-landing-1.0.0.zip`.
2. Copy the separately supplied `page-your-next-find.html` to the active KnifeRevive child theme’s `templates/` directory. It adds one slug-specific block template using the current global header and footer; do not replace the theme directory.
3. Create a **draft** native WordPress Page: title `Your next find. Your first sale.`, slug `your-next-find`. Insert a core Shortcode block containing `[kniferevive_next_find]` (serialized source in `docs/next-find-page-content.html`). Keep the Page title out of the template: content supplies the one H1.
4. Open **WooCommerce → Next Find campaign**. Save the draft Page ID and the verified product IDs shown by the resolver. Known current records: Wüsthof538, Fire TV3196, Cutco1448. These are deployment observations, not hardcoded inventory in the renderer. The owner editor checks new bindings against approved source slugs; saved IDs survive subsequent legitimate slug changes. Reusing a product record for a different physical item requires a creative/photo review.
5. Leave **campaign custom events OFF**. Preview signed out and signed in; verify actual product availability, full photographed objects, current header/footer/menu, category links, privacy, seller setup and Dispatch qualification. Save current IDs as bindings before publication.
6. Obtain publication approval, publish the Page, purge hosting/page/CDN caches, and test both UTM/anchor destinations. Do not change any live ad URL or launch a campaign without separate authorization.

Proposed public route: `https://kniferevive.com/your-next-find/`. Marketplace entry: `#discover`; Dispatch entry: `#dispatch`. Both share one source of truth. The Page itself is not currently live.

## Safe behavior

- Featured title/URL/status are read through WooCommerce each render. No hardcoded price, quantity, fee promotion, return guarantee, verification badge or rating. Draft/private/hidden/password-protected/sold-out/missing products use a category fallback and “Explore similar.”
- The hero is editorial ad imagery, labelled as actual seller photographs with changing availability. Never replace source photos with generated products. Image derivatives resize complete source images without cropping, rotating, retouching or changing objects.
- Categories resolve real WooCommerce terms. Empty/missing terms point to current category discovery with neutral copy. Kitchen Knives uses existing `/shop/?kr_knives_only=1`.
- Only the four standard UTMs are carried to browse/seller/product links; finite length and character validation reject arrays, markup, emails and arbitrary extra parameters. Legal links have no campaign query. Canonical excludes UTMs.
- Dispatch is explicitly in development, with conceptual art and no guaranteed arrival time, compensation or automatic reassignment. Links go to actual seller setup/current terms; no handoff execution is offered.

## Assets and editing

All CSS lives under `.kr-campaign-*` and is enqueued only on the campaign Page. No new font or animation dependency. PHP copy is in `templates/landing.php`; product bindings are owner-configurable. The native Page remains an editable registered core Shortcode block. The theme template adds normal `wp:template-part` header/footer and `wp:post-content` blocks.

`assets/images/manifest.json` records original kit references, dimensions, checksums and responsive variants. These are packaged plugin assets, **not Media Library attachments**; no attachment IDs are invented. The original kit remains unchanged on the owner’s disk. Rebuild with `python tools/prepare-next-find-assets.py <kit-folder>` from the repository.

## Optional approved measurement

Default: no campaign JavaScript. If the owner later enables custom events, the existing KnifeRevive Meta Controls integration must report ready (including its expected dataset), the existing browser `fbq` sender must exist, marketing consent must be affirmative and GPC must be off. Uses that sender only; never loads a pixel, changes a dataset or sends PageView/Purchase. The official integration remains the sole standard-event owner.

Custom event names: `landing_page_view`, `browse_click`, `seller_signup_click`, `dispatch_explore_click`, `featured_product_click`, deduplicated per page/event/approved item slot. Payload contains fixed campaign label, `marketplace`/`dispatch`/`other` content context, and optionally `wusthof`/`fire-tv`/`cutco`. No arbitrary UTM value, URL, message or buyer PII is sent. Browser tests use a stub, not Meta receipt verification.

## Caching and rollback

No new persistent product-data cache or global cache bypass. Stock/product/status/deletion hooks coalesce invalidation of the campaign Page at shutdown; settings changes also invalidate it. WordPress post cache plus available `wp_cache_post_change()` are used, with `kr_campaign_landing_cache_purged($page_id)` for host/CDN integration. Confirm **actual hosted cache invalidation** before ads use the Page; a native test hook does not prove a CDN purge.

Rollback: unpublish the new Page to draft, deactivate this one plugin, restore/remove only the newly added slug-specific template from the backup, and purge relevant caches. Preserve Page content/media/source assets; do not alter existing products, onboarding, checkout, Dispatch, Meta settings or ad destinations. There is no uninstall data purge.
