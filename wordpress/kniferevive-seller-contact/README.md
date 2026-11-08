# KnifeRevive Seller Contact 1.0.0

One secondary **Message seller** action for listings/cards and **Contact seller** for storefronts. Uses the existing KnifeRevive WhatsApp/Telegram opt-ins; it does not send messages, change orders or provide off-site checkout.

Requires WordPress 6.7+, PHP 8.2+, WooCommerce and Dokan Lite. Keep the existing WhatsApp/Telegram plugins: their storage and helper APIs are retained. This companion replaces only their competing frontend price/contact renderers. The supplied narrow theme/storefront overlays are required for custom KnifeRevive surfaces.

## Installation after owner approval

1. Back up database and the five overlay target files. Check their production checksums against the release manifest; reconcile any intervening changes rather than overwriting them.
2. Install `kniferevive-seller-contact-1.0.0.zip` through Plugins. Activate, leaving master and analytics OFF.
3. Apply the separate integration overlay archive to its exact `wp-content` paths. It is not an installable plugin/theme and must not replace whole directories.
4. Open **WooCommerce → Seller contact**. Select a pilot seller allowlist, enable the approved placements, then enable the master. Analytics stays OFF until consent/privacy approval and statistics-consent verification.
5. Purge hosting/page/CDN caches and test an opted-in seller and an opted-out seller as a guest. In particular, confirm opt-out removes cached contact links from product, gallery, shop and store pages.

Sellers use **My Account → Buyer messaging** or the Buyer messaging fields at the bottom of Dokan store settings. Private test links use saved values. Activation does not opt anyone in or rewrite their contact data.

## Behavior

- One valid channel: direct external link. Two: accessible chooser with a no-JavaScript inline fallback. Neither: no contact button.
- WhatsApp includes the canonical public product title/link. Telegram personal chats open the actual username; **Copy question** supplies item context. Existing seller-bot `start=krp_ID` is preserved.
- Suspended/inactive sellers and nonpublic/hidden/password-protected products have no public action. Out-of-stock published listings remain contactable. Cart/checkout do not receive new actions or assets.
- Seller diagnostics show reasons/booleans, not private channel values. No invented ratings, verification or reply guarantees.

## Optional measurement

Analytics is OFF by default, requires the WordPress Consent API's `statistics` permission on both client and server, and respects GPC. The existing Meta advertising choice is not reused. Without a functioning statistics-consent provider, leave measurement OFF. No new consent provider or third-party SDK is installed.

Events are first-party, rate-limited and deduplicated per daily ephemeral browser session. Two own database tables retain the current and prior six UTC days; deactivation leaves data/settings intact. No raw contact value, IP, message, URL or browser session token is stored in those tables. A channel activation is not a sent message or confirmed app launch.

## Rollback

Uncheck master to hide the new contact UI immediately; this intentionally does not revive legacy duplicate controls while the companion remains active. To restore the prior UI, deactivate this companion and restore the five backed-up overlay files, then purge caches. Leave the existing messaging plugins and seller meta intact. See the full implementation report for checks and limits.
