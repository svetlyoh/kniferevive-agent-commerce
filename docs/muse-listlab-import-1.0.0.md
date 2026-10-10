# Marketplace Imports 1.0.0 and Concierge 0.6.2

October 10, 2026. Implements the [requested plan](muse-listlab-import-plan.md).

## Seller workflow

Point to a Facebook Marketplace listing in Muse and say **“List this on
KnifeRevive.”** Concierge reads the actual referenced item, obtains KnifeRevive's
category/attribute schema and sends its details, source price and original image
URLs to the preparation API. It opens a private link for completion.

The review page shows the source price, merchant-calculated item price and a
separate shipping notice. Change the category/title/description/quantity there.
Sign in to an enabled seller account, confirm you can sell the item/use its
photos, and create a ListLab draft. **Complete listing in ListLab** opens the
existing seller editor for shipping/returns, images, specifications and final
publication. Preparation and draft creation never publish the product.

Muse still needs actual source-reading, HTTP and browser-opening facilities.
When Facebook cannot be read, use user-supplied facts/photos, disclose missing
content and retain the first-party completion path. A prompt does not add tools.

## Merchant pricing controls

Install/activate `kniferevive-listlab-import-1.0.0.zip` alongside WooCommerce and
KnifeRevive ListLab (tested against local 1.7.1). Open **WooCommerce → Marketplace
Imports**. Defaults are 20% markup, $0 fixed addition, $0 minimum, and $0.01
rounding up: **Facebook $25 → KnifeRevive $30 + native shipping**.

Use percentage markup, fixed dollar addition, minimum selling price and rounding
increment globally. Enable a category override to configure all four values for
that category. Children inherit the closest configured parent; a closer child
override wins. A fixed $5 addition with 0% also maps $25 to $30. Percentage
markup is measured against source cost; it is not gross profit margin.

Shipping is excluded from the formula. The seller chooses the site's existing
Shipping Policy in ListLab, and native checkout calculates available shipping
and tax. Facebook shipping promises/location are not copied as site policy.
The plugin requires USD/two decimal places and rejects ambiguous/non-USD prices.
Existing products are not repriced when an administrator changes import rules.
Unclaimed reviews use current settings; a stale accepted price returns 409.

## Contracts and state

Namespace: `/wp-json/kniferevive-listlab-import/v1`.

| Route | Access | Result |
|---|---|---|
| GET `/schema?category_id=<id>` | Anonymous | Actual category/native attribute schema and supported extraction fields |
| POST `/prepare` | Anonymous, bounded | Two-hour private review link and category-derived price; no product/image download |
| POST `/status` | Private token; seller-bound after claim | Prepared data, current price, schema, original listing if created |
| POST `/preview` | Private token; seller-bound after claim | Validate edits/category and recalculate current price |
| POST `/claim` | Enabled seller + native WordPress authentication + token | Seller-owned native ListLab draft and completion link |

Prepare input includes direct Facebook `source_url`, numeric-string
`source_price`, `currency: "USD"`, existing `category_id`, item `title`,
`description`, native `attributes` and up to ten `image_urls`. Optional native
fields are listed by the schema. Unknown fields (author/status/arbitrary meta/
client price) are rejected. Title and price are required; uncertain quantity,
shipping/returns and photos can be completed in the native draft.

Private operations send JSON `token`, not a query-string credential. Preview
accepts `changes` for the native listing-field allowlist. Claim also needs
`authorized_to_list: true` and the exact displayed `expected_price`. Cookie-based
claims use WordPress's `X-WP-Nonce` protection. The plugin issues no new seller
credential or delegated publishing grant. See WordPress's
[authentication contract](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/).

The review link carries a random 256-bit token only in its fragment. The
standalone first-party page strips it from the address bar and retains it in
that tab's session storage across login. It loads no theme analytics. Tokens
are hashed server-side, private responses are no-store, and expired preparation
records are cleaned hourly. Login on another device/tab needs the original link.

Claims retain ticket and seller/source locks and a native seller-scoped
idempotency key. The same Facebook item returns the same seller's original draft
even when another preparation ticket was made. It preserves seller edits and
does not restore a trashed original automatically. A busy/interrupted lock needs
operator inspection of the original draft before clearing the specific lock;
do not delete locks blindly or create a replacement to bypass uncertainty.

Photos are accepted only from HTTPS Facebook CDN host families, with no userinfo,
custom port, redirect or forwarded seller cookies. The server uses WordPress's
[safe HTTP client](https://developer.wordpress.org/reference/functions/wp_safe_remote_get/),
a five-MB per-photo limit and JPEG/PNG/WebP dimension checks. Photos become
seller-owned native media. Partial failures create an incomplete draft with
persistent warnings; the seller adds missing photos. Uploaded native image IDs
must be authorized for that seller. Source price, canonical URL and pricing
snapshot are retained as protected product metadata, not public guarantees.

## Validation and availability

Fenced synthetic native WordPress/WooCommerce/ListLab tests passed 67 assertions:
global/category pricing, inheritance, fixed markup, minimum/rounding, malformed
source data, seller access, foreign media rejection, no product on preparation,
private expiry, native draft ownership/quantity/price, same-source retries,
seller-edit preservation, bounded native photo copying, persistent photo-failure
warnings, review category recalculation and native CPU/condition attribute
storage visible for storefront/feed projection. PHP lint, JavaScript syntax and skill
frontmatter validation passed. Browser review verifies the $25/$30 separation,
seller login requirement and native draft completion handoff. No payment/order
pipeline was modified or exercised by this plugin.

Plugin deployment, exact-version ClawHub publication and actual Muse extraction/
automatic skill discovery are recorded separately; source/package tests do not
certify those integrations. Do not claim a live Marketplace import until the
merchant plugin and the 0.6.2 host skill have been installed and checked.

Rollback: deactivate **KnifeRevive Marketplace Imports**. Native products/media
already created remain in ListLab. The plugin does not modify ListLab files,
shipping settings, payment settings or existing product prices. Expiring private
preparation options are not inventory; preserve source metadata on real drafts.
