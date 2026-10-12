# KnifeRevive Order Messenger 1.0.3

1.0.3 adds the same mobile Quick Update composer to native sharpening orders.
Messaging reuses the existing sharpening-operator capability, validates the order
and customer relationship on every request, and exposes only sharpening services
in mixed-order context. Default replies go to the authenticated operator's
registered account email. Global merchandise manager access remains disabled
unless separately enabled in settings. The master enabled setting still applies.

1.0.2 makes the composer mobile first: a full-screen phone sheet with a compact
order heading, buyer address and message before expandable order/email details.
Send Update is full-width in the persistent footer; controls have 48px touch
targets, input text stays at least 16px, and safe-area/visual-viewport sizing
keeps the actions within the visible screen. Desktop uses a centered dialog.

1.0.1 registers the existing seller-orders endpoint in WooCommerce's query-variable
map. Without that compatibility registration, its endpoint conditional returned
false and skipped the composer JavaScript/CSS even while the endpoint hook rendered
the button. Authorization and recorded order ownership remain unchanged.
The entry action is now Quick Update, with a lime-green email-send icon button;
the composer uses Send Update. Seventy-two native integration checks and 43 DOM
checks passed, including authorized asset loading and cross-seller denial.

An independent WordPress plugin for **seller → buyer** transactional email from KnifeRevive's existing seller order page. Screenshots can be pasted or selected, previewed and sent as genuine PNG/JPEG attachments. The entire composer stays on the same order page.

**Replies go to the seller's current registered KnifeRevive email.** This follows the owner's explicit instruction overriding the original brief's support-only Reply-To default. KnifeRevive's configured WooCommerce/WP Mail SMTP sender remains the From address; the plugin does not impersonate the seller's Gmail sender. An optional verified-support mode is available in settings.

## Installation and editable theme/format

1. Back up the database. Install `kniferevive-order-messenger-1.0.3.zip` using Plugins → Add New → Upload Plugin, then activate it.
2. Enable WooCommerce, Dokan Lite and KnifeRevive Seller Orders. Missing dependencies disable Messenger gracefully with an administrator notice. PHP 8.2+ is required; PNG/JPEG processing requires GD. MySQL/MariaDB advisory locks must be available.
3. Configure **WooCommerce → Order Messenger** as an administrator (`manage_options`). Change feature/seller/manager access, Reply-To mode, screenshot limits, message limits, detailed history visibility and log retention. No SMTP credentials are stored here.
4. Under the same settings page, change **Email and composer theme**, **Email content format**, heading, introduction, message heading, subject pattern, transactional footer note, logo, colors, type size and email width. Default branding inherits the actual KnifeRevive WooCommerce logo/header/footer and styles. Custom colors are scoped to this plugin; section order can be summary-first or message-first. Changes affect future previews/messages without editing the site theme.
5. Subject placeholders are `{order_id}`, `{order_number}` and `{store_name}`. `{order_id}` is required and retained server-side. Sellers cannot edit the subject or addresses. Administrative copy fields accept text, not executable HTML/PHP.
6. Use **Preview saved branding** to inspect the same WooCommerce renderer without sending. **Diagnostic test email** resolves the selected existing seller's account address on the server. A new support-email override must be verified using a mailbox link while signed in as an administrator before support mode can send.
7. Exclude `/my-account/seller-orders/*` and `/wp-json/kniferevive/v1/order-messages/*` from edge caches. Responses are private/no-store; the order page also uses `DONOTCACHEPAGE` and no-referrer. Configure PHP uploads/temp outside all public web roots. Reliable WordPress cron runs detailed-log cleanup daily and stale private-image cleanup hourly.

No theme, WooCommerce, Dokan, ListLab, Product Grid, WhatsApp or Seller Orders source modifications are needed. Set Reminders is separate and untouched.

## Integration

Source inspection found `KREV_Orders_Endpoints::seller_orders()` registered on `woocommerce_account_seller-orders_endpoint`, with `absint($value)` routing to `templates/seller-order-detail.php`. That template has no dedicated action-area extension hook. Messenger uses the existing **`woocommerce_account_seller-orders_endpoint` hook at priority 30** to render its independently authorized button/dialog after the existing callback. Its scoped script positions the plugin root in the observed `.krev-order-detail[data-order-id] .krev-page-header`. If the header is changed in a future Seller Orders release, the button remains usable in the fallback position after the screen.

The existing buyer → seller contact hook and recipient behavior are preserved. Assets load only for authorized seller-order details, not other pages or the orders list. PHP classes use the consistent `KR_Order_Messenger` prefix; CSS uses `kr-om-`.

## Authorization and order context

- Every REST route is registered **only** on `rest_api_init`, with explicit permission callbacks, logged-in WordPress identity, valid REST nonce and fresh order authorization. Missing orders and forbidden orders return the same generic error.
- Enabled Dokan sellers must match stored order-time ownership: `_dokan_vendor_id`, corroborated with Dokan's `dokan_orders` ledger when present. Multiple/conflicting stored sellers are rejected. Current product authorship is never used for Messenger permissions.
- A mixed-vendor parent is resolved through Dokan's actual `get_child_orders()` API to exactly one child owned by the current seller. Context/email include only that child order's items. Managers are separately disabled by default; enabling access permits unambiguous single-vendor orders, not arbitrary vendor selection on mixed parents.
- Historical orders without reliable stored ownership cannot send. The original Seller Orders screen/index currently derives ownership from product authors and refreshes its index; that index is not a trustworthy order-time snapshot and is deliberately not reused. No ownership is backfilled from today's product author. Review genuine historical order records if migration is needed.
- Registered buyers use the current valid `WP_User::user_email` associated with the parent/buyer order. Missing/deleted/invalid registered accounts do not silently fall back to another billing mailbox. Guests use valid order billing email. All address resolution is server-side; posted seller IDs/emails/permission flags are ignored.
- The order ID/display order number, purchase date/time in the site's timezone, seller store and purchased item names/quantities come from WooCommerce CRUD APIs. Registered buyers get the authenticated parent-order view link. Guests get the selected vendor order's real WooCommerce receipt URL/key, preventing a parent receipt link from disclosing other vendors' items to the seller preview.

## Email and screenshots

`KR_Order_Messenger_Mailer` extends `WC_Email`. It uses the existing header/footer templates, sender filters, CSS inliner, configured transport and a multipart plain-text alternative. Preview and sending share the renderer. Seller content is plain text escaped into HTML with readable line breaks. There is no automatic promotional content.

Screenshots use file input and `ClipboardEvent.clipboardData.items`; normal text paste is preserved. Initial supported formats are PNG/JPEG. WebP/SVG/HTML/executables are rejected. Both browser and server enforce image count, per-file and total sizes; advertised limits also respect PHP's upload settings. The browser submits an expected attachment count, and missing/dropped files fail the request before sending. The server validates actual MIME, extension, decoded content, dimensions, memory budget and file size, then decodes/re-encodes images to remove metadata and appended payloads.

Images are sent as **separate email attachments**, clearly labelled in the real email and preview; thumbnails in the composer/preview use private browser object URLs. No permanent Media Library item or public screenshot URL is created. Temporary processed images live outside the web root with restricted permissions, are deleted in `finally` after the attempt, and have an hourly crash-recovery sweep for files older than two hours. If safe processing/storage is unavailable, attachments fail explicitly; text-only messages do not require image storage.

Preview is displayed in a sandboxed iframe and never sends mail. On mobile the dialog becomes a viewport-height sheet with separate scrollable content and fixed header/action areas, responsive thumbnails and visual-viewport keyboard sizing. Native dialog focus, Escape, draft discard prompts and same-URL history entries preserve the order page, focus and scroll position. Drafts/images are held only in browser memory and cleared after acceptance/discard.

## Routes

Base `/wp-json/kniferevive/v1/order-messages/{order_id}`:

| Method / path | Purpose |
| --- | --- |
| GET `/context` | Private recipient, Reply-To, order summary, subject and effective screenshot limits |
| POST `/preview` | `multipart/form-data`: message, attachment_count, screenshots[]; renders the actual email without sending |
| POST `/send` | Same multipart fields plus UUIDv4 request_id; reauthorizes, claims, sends once, records outcome |
| GET `/history` | Authorized vendor's last 30 permitted attempt records; no recipient address or message body |
| GET `/status?request_id=…` | Recover a known attempt after interrupted client connectivity without resending |

Use WordPress cookie authentication and `X-WP-Nonce`. Addresses, subject and sender/store ownership are never accepted from the browser as authoritative.

## Duplicate protection, audit and retention

- MySQL advisory locks for vendor and acting user make hourly order and rolling daily seller/user limits atomic. Defaults: 5 pings/vendor/order/hour and 20/vendor and sender/day. Failed attempts count toward abuse limits. Preview requests are limited separately.
- A unique vendor/effective-order/UUID claim is written before transport submission; changed payloads cannot reuse the UUID. Double-clicks, parent/child representations and repeats do not send twice.
- Statuses: attempting, accepted, failed, unknown. Acceptance means handed to the mail service, not received/opened. A false transport result permits an explicit new attempt; thrown exceptions, stale attempts and interrupted client responses remain uncertain and are never automatically resent. Check Status reads the previous attempt. No message-send cron or automatic retries exist.
- `${prefix}kr_order_messages` stores IDs, timestamp, subject, keyed recipient hash, image count, UUID and outcome/error code. It stores no plaintext recipient, message body/excerpt or screenshot binary. Accepted sends add a **private** WooCommerce note with sender ID and timestamp, without triggering a customer note email.
- Detailed logs expire after the configured retention (default 90 days). `${prefix}kr_order_messages_dedupe` retains minimal payload-hash/status/UUID-key tombstones so old UUIDs cannot send again after history expiry; these have no email or message content and are removed when the corresponding order is deleted. Turning off displayed history does not remove security/audit records used for rate limits and deduplication.
- Deactivation stops both cleanup schedules and removes Messenger's runtime integration; existing seller-order functionality stays intact. Data is retained for recovery. Deleting the plugin files does not silently erase audit records.

## Verified and outstanding

- 69 integration checks in HPOS and the same 69 in legacy order storage, using synthetic fixtures and intercepted mail.
- 43 composer/clipboard/navigation DOM checks; 26 real authenticated HTTP/multipart/SMTP/inbox checks.
- Controlled Local SMTP inbox actually received one branded message with both PNG and JPEG attachments, plain-text alternative, actual logo reference, footer and registered-seller Reply-To. Duplicate UUID did not deliver another message. Received HTML matches preview after SMTP line-ending normalization; temporary images were removed.
- One authorized external diagnostic through the local copy's configured WP Mail SMTP/Brevo transport was rejected with **HTTP 401**. No external Gmail delivery is claimed. Correct/verify the local or production provider authentication before rollout; no SMTP credential was printed, copied into this plugin or altered.
- Connected browser inventory was empty. Actual screenshot clipboard/device behavior, visual layout at 320/375/390/430/768px, iPhone Safari/Android Chrome, keyboard/focus/Back behavior in real browsers, and the authenticated production reference order 3093 remain unverified. DOM tests are not those device tests. Reference order 3093 does not exist in the local database.

Tests are for a dedicated development/staging site only, not production: other plugins' order/product hooks can have effects. `tests/integration.php` can run under WP-CLI after activation; `tests/ui.cjs` requires jsdom and paths to a saved composer/context. QA fixtures/bootstrap/SMTP gates are not included in this plugin ZIP. See the implementation report for concrete results, snapshots and final counts.

## Deployment, rollback and future templates

Production deployment requires approval under the supplied instructions. Back up the database, upload the ZIP, activate dependencies and verify the settings, recorded seller ownership, private uploads, provider authentication and cache exclusions. Complete a real buyer/seller test with screenshot paste and confirmed inbox receipt before broad rollout.

Rollback: deactivate Order Messenger and clear caches. No existing source files need restoration. Keep the audit tables until their retention/business requirements are reviewed; dropping them deliberately discards duplicate protection. To change templates later, use the standard WooCommerce template override path `kniferevive-order-messenger/emails/order-message.php` and `kniferevive-order-messenger/emails/plain/order-message.php` in an approved child-theme customization, or update this isolated plugin. Routine colors/layout/copy/logo changes need only the admin settings.

References: [WooCommerce WC_Email](https://woocommerce.github.io/code-reference/classes/WC-Email.html), [WooCommerce HPOS](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/), [WordPress REST route registration](https://developer.wordpress.org/reference/functions/register_rest_route/).
