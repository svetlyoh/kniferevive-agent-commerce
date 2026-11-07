# KnifeRevive Social Hub — Codex Build and Implementation Instructions

**Status:** Engineering specification, not a claim that the plugin is built, installed, authorized, or publishing.  
**Prepared:** 2026-11-07  
**Target:** Independent WordPress/WooCommerce companion plugin named **KnifeRevive Social Hub** (slug: kniferevive-social-hub).  
**Primary outcome:** Automatically distribute eligible existing ListLab / Seller Cam product videos, **15 seconds or shorter**, to connected KnifeRevive social accounts, with the most direct permitted route back to the **exact** WooCommerce product listing.  
**Owner preference:** The administrator signs into existing social-media accounts from the plugin's Settings screen using official OAuth consent flows. Routine per-listing administrator approval is **not** required.  
**Deployment rule:** Build and test in a safe environment; do not install, activate, enable production auto-posting, or submit developer-app reviews without the owner's explicit approval.

---

## 0. Instructions to Codex — execute, do not merely summarize

Implement the specification as production-quality, independently installable WordPress plugin source, tests, documentation, and a reproducible ZIP. Work in the KnifeRevive repository:

- Repository: https://github.com/svetlyoh/kniferevive-agent-commerce
- Plugin directory to create: wordpress/kniferevive-social-hub/
- Engineering docs: docs/social-hub/
- Tests: tests/social-hub/ (or an isolated, consistently named equivalent)
- Build artifact: dist/kniferevive-social-hub-<version>.zip (generated; do not commit credentials, database exports, or temporary media)
- The plugin must be self-contained, without editing WooCommerce, Dokan, ListLab, Seller Cam, the theme, the existing Agent Commerce plugin, or the official Pinterest for WooCommerce plugin.

**Required execution order:**

1. Inspect the actual checked-out repository, current WordPress plugin sources, and site-specific integration docs. Record discovered facts and missing code/access in docs/social-hub/discovery.md. In particular, inspect the installed **Seller Cam** and **ListLab** implementations before deciding which metadata or hooks contain the video. Do not guess keys.
2. Verify current official API docs, policies, OAuth scopes, account/app review requirements, and product-link affordances. Record dates, URLs, and any unverified behavior in docs/social-hub/platform-capabilities.md. Do not copy outdated endpoint versions or permissions from prior discussion.
3. Build the independent plugin and test doubles first; never use real social accounts in automated tests.
4. Implement safe admin OAuth account connection, product/video discovery, queue, per-platform adapters, status dashboard, tracking, and compliance controls.
5. Run unit, WordPress/WooCommerce integration, security, queue, and browser tests; create a clean install ZIP and SHA-256 manifest.
6. Provide an operator setup guide and a five-to-ten-product pilot checklist. Leave global auto-publishing **OFF** by default. Ask the owner before any staging-to-production installation or real public posting.
7. Return a final implementation report with files changed, versions, exact tests/results, unsupported features, OAuth/app review blockers, pilot results (only if actually performed), and rollback procedure. Never claim a platform posted unless its API returned and reconciliation confirmed the post.

If repo or site source is unavailable, do not invent integrations. Implement a clearly documented adapter seam and list exactly what source/access is needed to finish it.

## 1. Business intent and boundaries

KnifeRevive is a multi-vendor WooCommerce marketplace. Sellers use ListLab at /my-account/listlab/ to create/edit WooCommerce products; an existing Seller Cam video feature associates a short product video with a listing. Categories span kitchen cutlery, technology/electronics, collectibles, art, and other finds. Listings can be new, used, refurbished, unique, or quickly sold out. The existing ListLab condition rules depend on the selected product category/subcategory; preserve those native values.

One source video should be reusable across multiple social channels without asking the seller to upload again. The **KnifeRevive brand's connected social accounts** are the publishing destinations for v1; sellers are not expected to connect their personal accounts. The owner wants organic discovery and product referrals, not a paid-ad campaign.

Do not build a second listing system, commerce ledger, catalog manager, inventory sync engine, or payment integration. WooCommerce remains authoritative for product status, URL, seller, price, stock, and checkout. Pinterest Bridge / Pinterest for WooCommerce remains responsible for product-catalog feed sync; Social Hub handles **individual organic video Pins/posts** and must not duplicate the catalog feed.

**Non-goals for v1:** creating Seller Cam videos, creating/altering seller listings, auto-generating AI video, music licensing, paid ads, automatic Instagram Shopping/YouTube Shopping approval, TikTok Shop checkout, impersonating sellers, bypassing platform API reviews, or guaranteeing product stickers / buy buttons.

## 2. Platform truth table — design around what is actually clickable

| Destination | Publishing approach | Direct clickable product URL in ordinary short video? | v1 stance |
| --- | --- | --- | --- |
| **Pinterest video Pin** | Pinterest API v5 media upload and Create Pin | A Pin's destination **link** can lead directly to the specific WooCommerce listing, subject to account/content restrictions. | **First priority**; connect business account, select board, validate video Pin and destination URL. |
| **Instagram Reel** | Official Instagram professional-account content publishing API | Plain URLs in Reel captions are generally **not clickable**. Shopping product tags/stickers require separate eligibility and may not be exposed through the chosen publishing API. | Publish eligible Reel; show a truthful profile/link-in-bio CTA; no false "tap to buy" promise. |
| **Facebook Page Reel** | Official Facebook Page Reels/video publishing API, current version verified during build | Page/post link behavior and Reel product-tag availability are **not interchangeable**; confirm actual rendered UI and API. | Implement Page publishing with link in text only where permitted; do not promise a Reel buy button. |
| **YouTube Short** | YouTube Data API videos.insert with OAuth; qualifying vertical short videos are classified by YouTube | **URLs in Shorts descriptions and comments are non-clickable** under YouTube's stated policy. Channel profile links or eligible YouTube Shopping features may offer other routes. | Publish Short, include product identity and appropriate profile CTA; do not count description URLs as direct clicks. |
| **TikTok video** | TikTok **Content Posting API exists** (Direct Post or Upload/Draft) | Ordinary caption URLs are not a dependable clickable product link; shopping/other features are separately gated. | Build optional adapter behind a capability flag; no unattended public auto-post unless developer audit, creator consent, permitted UX, and content policy allow it. |

**Important corrections to the earlier research draft:**

- TikTok **does have** an official Content Posting API. It requires approved scopes and, for public Direct Post, app audit; unaudited clients are restricted. Its user-experience and consent rules may conflict with completely unattended bulk publishing. Treat this as a platform gate, not a reason to invent unofficial browser automation.
- YouTube Data API does **not** have a documented universal shorts=true upload parameter. Use the official videos.insert flow and verify Shorts classification after upload. A YouTube API key alone is **not** sufficient to upload on behalf of a channel; use OAuth.
- YouTube explicitly says URLs in Shorts descriptions/comments are **not clickable**. Do not present them as product-link conversions.
- An organic Pinterest video Pin with a destination link is distinct from a merchant-catalog product Pin; do **not** assume every organic video Pin requires catalog approval, or that a catalog approval guarantees video Pin/shopping eligibility.
- Do not hardcode legacy Meta API versions, outdated permission names, old duration limits, or assume Facebook Page video uploads automatically become Reels. Verify current dedicated publishing flows.
- Do not overlay a generic KnifeRevive watermark, URL, or promotional text onto TikTok videos without confirming TikTok's Content Sharing Guidelines. Preserve source videos unless a platform-specific compliant variant is necessary.

**Primary sources to consult before coding:**

- Pinterest organic Pins: https://developers.pinterest.com/docs/work-with-organic-content-and-users/create-boards-and-pins/
- Pinterest catalog feed / video_link: https://help.pinterest.com/en/business/article/before-you-get-started-with-catalogs
- Instagram Platform documentation: https://developers.facebook.com/docs/instagram-platform/
- Facebook video publishing: https://developers.facebook.com/docs/video-api/
- YouTube upload API: https://developers.google.com/youtube/v3/docs/videos/insert
- YouTube clickable links policy: https://support.google.com/youtube/answer/13748639
- YouTube Shopping eligibility: https://support.google.com/youtube/answer/12257682
- TikTok Direct Post: https://developers.tiktok.com/docs/en/content-posting-api-get-started
- TikTok Direct Post API reference: https://developers.tiktok.com/docs/en/content-posting-api-reference-direct-post
- TikTok Content Sharing Guidelines: https://developers.tiktok.com/docs/en/content-sharing-guidelines
- WooCommerce CRUD: https://developer.woocommerce.com/docs/
- Action Scheduler: https://actionscheduler.org/
- WordPress security: https://developer.wordpress.org/plugins/security/

The sources above are reference starting points, **not** proof of approval for KnifeRevive's specific accounts, app, marketplace, knife categories, or media. Verify current documents at implementation time.

## 3. Product eligibility and consent

Default eligibility is **deny until verified**, with a reason code visible to the administrator. A publication may be enqueued only when all checks pass:

1. WooCommerce product exists, is published and publicly accessible, is not password-protected/private/trashed, and is eligible for purchase under existing store rules.
2. The product has a valid permalink on the canonical KnifeRevive host. Always resolve it through WooCommerce/WordPress, not by constructing a guessed slug.
3. The product has a Seller Cam video actually associated with it; discovery must verify the native attachment/media mapping. If the video is still processing, wait and retry discovery.
4. The video duration is **<= 15.0 seconds**, measured from the actual file/stream (prefer ffprobe when available), not only seller-entered metadata. Reject longer media by default; **do not silently trim** or speed it up.
5. Preferred source: 9:16 portrait, 1080x1920, 30 fps, MP4/H.264 video with compatible audio. Per-platform validation uses its current official specs. If safe transcoding is optional, preserve original, use a derived private temp asset, limit CPU/memory, and delete temp files. Reject unsupported files with actionable reasons.
6. Video file is owned/authorized for this listing; never upload arbitrary remote URLs, private seller files, or third-party content. Use verified media attachment and server-side file access or an explicitly allowlisted public source.
7. Seller granted **versioned, explicit authorization** for KnifeRevive to use their listing video in organic promotion and affirmed media rights. Add consent to the existing ListLab publish flow **only through supported extension hooks**, if available; otherwise implement a seller-scoped opt-in in the plugin with a documented UX handoff. No forced opt-in or assumed consent from unrelated marketplace terms.
8. Product is not excluded by seller opt-out, admin hold, category/product policy, legal restriction, account limitation, or global kill switch.
9. Stock/status is currently sellable (using native WooCommerce stock/backorder semantics). Do not publish already sold/unavailable unique listings.
10. A per-platform eligibility policy approves the product category and content. **Kitchen knives are not automatically allowed everywhere**. Keep potentially restricted knife/weapon categories **OFF** for a platform until the operator records an actual policy decision; never relabel prohibited goods to evade moderation.

Seller consent must be revocable for **future** publication. Provide a documented takedown request workflow for previously posted videos, with platform/API limitations clearly stated. Keep an audit trail of consent version, timestamp, actor, and withdrawal.

Do not require an admin to manually approve each eligible video. Instead use **automated rules + explicit consent + exception-only review**.

## 4. Discovery: inspect the real KnifeRevive stack first

Review the actual ListLab/Seller Cam plugin versions and source. Relevant repository context includes docs/muse-listlab-import-1.0.0.md and docs/metacategories-2026-10-11.md, which establish that ListLab writes native WooCommerce drafts/products and that condition/category profiles are native and dynamic. They **do not establish the Seller Cam video meta key**.

Produce a discovery table identifying:

- The hook emitted when ListLab publishes a listing.
- The hook/event when Seller Cam attaches/replaces/removes a video.
- The actual product-to-video relationship: attachment ID, post meta, custom table, URL, or API.
- Whether media is local, offloaded to object storage, private, or public; whether signed URLs expire.
- Existing seller consent/terms records, if any.
- Dokan vendor ownership mapping, product visibility and moderation rules.
- Current condition taxonomy/attribute implementation; do not replace with a simplified new/used model.
- Any existing Pinterest Bridge / Merchant Sync / Meta catalog plugin, feed ownership, and video_link support.
- Hosting PHP version, Action Scheduler availability, FFmpeg/ffprobe availability, cron reliability, HTTP egress limits, upload limits, and webhooks.

Build a narrow VideoSourceResolver interface with a documented site-specific implementation. It must return a typed media object or a machine-readable "not found / pending / unauthorized / invalid" result. No fallback scanning of random product URLs. Provide WordPress filters so later Seller Cam versions can supply the media source without modifying this plugin.

## 5. Admin settings and existing-account sign-in

Add **WooCommerce → Social Hub** with tabs:

- **Overview:** global paused/running state, counts, failures, latest activity, account health.
- **Connections:** Pinterest, Meta (Facebook Page + linked Instagram professional account), YouTube, TikTok; each has **Connect account**, **Reconnect**, **Disconnect**, status, account name/ID, scopes, token expiry, last health check, and last successful publish. Only real, available providers show enabled Connect buttons.
- **Publishing:** master enable switch (OFF by default), per-platform enable switch, pilot allowlist, automatic publishing for future eligible products, schedule/throttle, daily cap, initial backlog behavior (OFF), account/board/page/channel destination, and optional quiet hours.
- **Eligibility:** seller consent policy, category restrictions, per-platform allowlist/denylist, condition handling, video validation, stock requirements, content safety holds.
- **Queue / Posts:** product, seller, source video, destination, queued/publishing/published/failed/blocked state, public post link, UTM destination, attempts, error code, timestamps, retry/reconcile controls.
- **Analytics:** successful posts, measurable referrals, WooCommerce orders/revenue attribution (only when analytics consent/configuration permits), views/engagement where provider APIs allow, unavailable metrics clearly marked.
- **Diagnostics:** dependencies, cron/Action Scheduler status, OAuth redirect URI, API app configuration validation, permissions, media access test, safe test publish, webhook status, redacted logs.

### OAuth — user experience

The administrator can sign into their **existing** accounts directly from Settings using **official provider-hosted authorization**. The plugin must **never ask for or store their social passwords**.

- **Pinterest:** Connect with Pinterest OAuth, choose the authorized business account and destination board(s).
- **Meta:** One appropriate Meta/Facebook authorization flow; select a managed Facebook Page and eligible linked Instagram professional account, each with independently validated publishing permissions. Do not assume one login gives every capability.
- **YouTube:** Google OAuth 2.0; choose the channel/identity; request the narrowest upload scope supported by the desired feature. No API-key-only upload.
- **TikTok:** TikTok Login Kit / OAuth with approved Content Posting scopes, only if app is eligible; display privacy/creator controls required by its official UX guidelines. If required consent cannot be automated lawfully, use a draft/manual completion mode rather than pretending to support unattended public posts.

OAuth implementation requirements:

- HTTPS callback under a namespaced admin REST route; exact redirect URIs shown for developer-console configuration.
- CSRF-resistant state bound to the current authorized admin and one-time nonce; PKCE where supported; strict callback validation; no token in URLs/logs.
- App client IDs/secrets configured securely. Provide wp-config/environment constants or encrypted admin settings where appropriate. Explain that **OAuth login still requires registering/configuring developer apps and possibly platform app review**.
- Store tokens encrypted at rest using authenticated encryption and a server-managed key derived from strong secrets (or a site secret manager); non-autoloaded storage; rotate/reconnect safely. No raw access tokens in HTML, JavaScript, logs, REST responses, support bundles, or source control.
- Per-provider refresh strategy, token revocation/disconnect, scope drift detection, health checks, rate-limit handling, and clear "reconnect required" state.
- Only users with an explicitly checked admin capability (manage_woocommerce plus appropriate admin rights) can connect/disconnect or change global settings. Sellers must not see credentials or publish into brand accounts directly.
- Restrict outbound HTTP to documented provider API origins and validated provider-supplied upload hosts. Harden against SSRF, redirects to private networks, URL injection, oversized media, and credential exfiltration.

**Do not create any developer app on behalf of the owner without their authorization.** Provide a clear setup checklist for app IDs, secrets, permissions, redirect URIs, verification and review.

## 6. Architecture and folder structure

Recommended structure (adjust to repository conventions while preserving separation):

~~~
wordpress/kniferevive-social-hub/
  kniferevive-social-hub.php
  uninstall.php
  readme.txt
  composer.json                         # only if required; vendor deps packaged legally
  includes/
    Plugin.php
    Activation.php
    Dependencies.php
    Admin/
      Menu.php
      SettingsPage.php
      ConnectionsPage.php
      QueuePage.php
      DiagnosticsPage.php
    Domain/
      ProductSnapshot.php
      VideoAsset.php
      PublishJob.php
      PublishResult.php
      EligibilityResult.php
    Integration/
      WooCommerceProductRepository.php
      ListLabEventBridge.php
      SellerCamVideoSourceResolver.php
      SellerConsentRepository.php
      PinterestBridgeCompatibility.php
    Policy/
      EligibilityEvaluator.php
      PlatformPolicy.php
      MediaValidator.php
    OAuth/
      OAuthController.php
      TokenVault.php
      ConnectionRepository.php
    Queue/
      Scheduler.php
      JobRepository.php
      JobWorker.php
      Reconciler.php
      RateLimiter.php
    Platforms/
      PlatformAdapterInterface.php
      PinterestVideoPinAdapter.php
      InstagramReelsAdapter.php
      FacebookReelsAdapter.php
      YouTubeShortsAdapter.php
      TikTokContentPostingAdapter.php
    Tracking/
      AttributionUrlBuilder.php
      Analytics.php
    Security/
      HttpTransport.php
      AuditLogger.php
    Rest/
      AdminRoutes.php
      OAuthCallbackRoutes.php
  assets/
    admin.css
    admin.js
docs/social-hub/
  discovery.md
  platform-capabilities.md
  setup-and-oauth.md
  pilot-and-acceptance.md
  operations-and-rollback.md
  implementation-report.md
tests/social-hub/
  unit/
  integration/
  fixtures/
  browser/
~~~

Keep namespaces prefixed (e.g., KnifeRevive\SocialHub) and WordPress hooks, DB tables, options, REST routes, scheduled action names and CSS selectors collision-free (prefix: krev_socialhub_). Follow GPL-compatible licensing, PHP 8.2+ where the existing repository requires it, WooCommerce CRUD, and HPOS-safe patterns. Do not rely on direct order-table queries.

### Core interfaces

PlatformAdapterInterface should expose methods equivalent to:

- capabilities(Connection): supported media, direct-link type, tags, public publishing, analytics, delete/update, OAuth/app-review status.
- validate(ProductSnapshot, VideoAsset, Connection): typed pass/block result.
- prepare(PublishJob): canonical title, platform-specific caption/description, safe hashtags, cover, destination/CTA, upload mode.
- publish(PublishJob): durable provider request reference or confirmed post result.
- reconcile(PublishJob): query remote processing/publication status and return final ID/URL when available.
- deleteOrUpdate(PublishJob, reason): **optional**; must report unsupported instead of fabricating success.
- refreshConnection(Connection): provider-specific token maintenance.

Separate provider HTTP clients from business logic; allow full API mocking in tests. Platform-specific adapters must be disabled unless current official docs and granted account permissions justify their implementation.

### Event sequence

~~~
ListLab publishes product OR Seller Cam media becomes ready
    -> debounce / schedule product evaluation
    -> fetch fresh WC product and Seller Cam media
    -> evaluate consent + stock + category + media + account capability
    -> persist one idempotent job per eligible platform/account/video version
    -> Action Scheduler worker rechecks eligibility
    -> upload/publish through official platform API
    -> reconcile async processing, save remote post ID and URL
    -> record tracked destination + non-sensitive audit data
    -> expose results in WooCommerce > Social Hub
~~~

Do not perform network uploads inside the seller's product-save HTTP request. Multiple save hooks often fire; debounce them and make the worker idempotent.

## 7. Durable queue, idempotency and state

Use Action Scheduler (available with WooCommerce) for background tasks, with a fallback only if independently verified. Document how a real server cron invokes WP-Cron/Action Scheduler on this host.

Create schema-versioned custom InnoDB tables via dbDelta or a migration-safe equivalent:

- **connections:** provider, account/page/channel/board identity, display name, granted scopes, token vault reference, token expiry, status, app-review/capability flags, created/updated timestamps.
- **jobs:** product_id, vendor_id, video attachment/reference and checksum/fingerprint, platform, connection_id, target destination, unique idempotency key, status, retry count, next_run, lease owner/expiry, remote upload/session/publish ID, remote post ID/URL, tracked listing URL, created/updated/published timestamps, last error code.
- **attempts/events:** job_id, provider operation, safe request correlation ID, sanitized response/error classification, retryability, timestamp; no secret tokens, raw media, or buyer PII.
- **consents/holds** if native records are insufficient: seller ID, product ID, consent version, granted/revoked times, admin holds and reason.

Add indexes and a unique constraint for the **logical publication identity** (product + platform + account/destination + source-video fingerprint + publication generation). A video replacement is a new generation only under explicit policy. Do not create duplicate posts on ordinary title/price/stock edits.

Job states: **pending_eligibility → queued → uploading → processing → published**; alternate **blocked**, **waiting_auth**, **waiting_policy**, **retry_wait**, **uncertain_remote**, **failed_permanent**, **cancelled**, **takedown_requested**, **removed**. Use explicit allowed transitions and persist state before external calls.

- Rate-limit per provider/account; honor Retry-After and exponential backoff with jitter.
- Distinguish transport timeout/unknown outcome from a confirmed failure. On uncertain creation, **reconcile first** using provider operation IDs; never blindly repeat a post creation and risk duplicates.
- Keep bounded retries and an operator-only retry/reconcile button with capability and nonce checks.
- Use leases/locks so two workers cannot upload the same job simultaneously. Expired leases require reconciliation before a new attempt.
- Respect global pause and per-platform pause even when jobs are already queued.
- At each execution stage, re-read product status, seller consent, policy flags and connection validity.
- Uninstall defaults to **preserve audit/history** unless the operator explicitly selects destructive cleanup. Never revoke external posts as a side effect of uninstall.

## 8. Video pipeline and content preparation

**Hard rule: source videos must be 15 seconds or less.** Use measured duration, not a guessed duration from file name or a WordPress metadata placeholder.

Preferred source is portrait 9:16 at 30 fps, H.264/MP4; validate each provider's actual size, codec, resolution, frame rate, cover and audio requirements. Handle iPhone HEVC/MOV and rotation metadata explicitly: a 1080x1920 visual portrait can be stored as 1920x1080 with a rotation tag. Normalize only if safe and needed.

Do not trim longer clips without a separate owner-approved product requirement. If ffprobe is unavailable, fail safely with "duration unverified" or implement a tested alternative; do not mark the video valid based on untrusted input. Use limits on bytes, CPU, memory, disk and concurrency.

Captions should be short, accurate, accessible and category-aware:

- Product title, genuine brand/model, native condition, and an optional concise feature from seller-approved listing facts.
- Platform-appropriate hashtags (not keyword stuffing).
- A CTA matching actual capability: "View this item" for a clickable Pinterest Pin; "Find KnifeRevive via our profile" where links are not clickable.
- Do not promise stock/price that may change after posting. Prefer avoiding fixed prices in permanent social captions for one-off used listings.
- No fake scarcity, fabricated specs, misleading condition, or automatically added copyrighted music.
- Use source audio only when seller rights cover reuse on every destination.
- Cover frame should show the product and avoid unsafe/gory knife demonstrations. Use an existing approved product image or generated frame if allowed by API.
- Keep TikTok video free of prohibited promotional overlays/watermarks under its developer guidelines.

Avoid leaking seller phone numbers, email addresses, private serial numbers, private seller notes, order IDs, addresses, buyer data, or unpublished drafts in captions, media metadata, or public URLs.

## 9. Per-platform implementation details

### 9.1 Pinterest — MVP adapter

- Connect Pinterest business account through official OAuth and show accessible boards.
- Validate account/app scopes and whether posting organic video Pins is permitted.
- Use current Pinterest API v5 media registration/upload/status flow, then Create Pin with video media and appropriate cover.
- Set the Pin **link** to the exact canonical WooCommerce product URL with attribution parameters, subject to platform URL requirements.
- Save Pin ID, URL, board, media ID, status, and any errors.
- Test that the real Pin's destination opens the exact product listing on mobile and desktop.
- Handle API processing delay and media rejection.
- Detect if Pinterest for WooCommerce / Pinterest Bridge already creates a video Pin for the same product; avoid duplicate organic posts by a documented ownership rule.
- **Do not** change official Pinterest for WooCommerce code. Its catalog video_link, if supported, is a separate feature and may point to the same existing video source. A catalog video_link is not proof that an organic video Pin was created.

### 9.2 Instagram Reels — phase 2

- Connect via official Meta login and select the eligible Instagram professional account.
- Verify required current scopes, app review, account/Page linkage, media hosting requirements, creation container, processing state, and media_publish flow.
- Only publish when the Reel is processed and permissions are confirmed; store remote media ID and permalink.
- Do not represent caption URLs as clickable or claim product tags are available without tested Shopping API permissions and approved catalog linkage.
- Offer a non-deceptive profile CTA. Shopping tag integration is a separately gated enhancement.

### 9.3 Facebook Page Reels — phase 2

- Reuse the authorized Meta connection where scopes allow; select a managed Page.
- Implement the current **Page Reels** API, not an assumed ordinary Page video upload that may render differently.
- Verify upload/init, transfer, processing, finish/publish and permalink against current docs.
- Verify whether the published Reel actually exposes any clickable outbound link. If not, use an accurate Page/profile CTA and mark direct product click tracking unsupported.
- Treat Facebook Shops/product tags as a separate eligibility and implementation phase.

### 9.4 YouTube Shorts — phase 2

- Google OAuth 2.0 channel authorization with resumable videos.insert upload; specify snippet, category if valid, audience/made-for-kids setting based on actual content, privacy status, and appropriate metadata.
- Use a <=15s vertical video and verify YouTube classifies it as a Short. No shorts=true parameter.
- Put the product title/identity in description, optionally the URL for reference, but **do not claim the URL is clickable in a Short**.
- Provide a channel profile link strategy; YouTube Shopping tags are an optional separately approved future feature, not a default.
- Respect YouTube quota, audit restrictions, API project verification and per-channel upload permissions.
- Store video ID, watch URL, status, quota errors, and whether Shorts classification was verified.

### 9.5 TikTok — gated phase 3

- Implement against TikTok's official Content Posting API (Direct Post and/or Upload/Draft), not scraping or unofficial browser automation.
- Require approved video.publish or video.upload scopes, verified source URL domain where needed, app audit, explicit creator consent and UI controls (privacy, comment/duet/stitch options) mandated by the provider.
- If platform policy requires interactive confirmation per post, **do not** bypass it to satisfy the owner's no-admin-approval preference. Disable unattended auto-post and expose a truthful manual/draft workflow.
- Unreviewed/unapproved TikTok app posting must not be described as public; provider docs restrict unaudited clients.
- No TikTok Shop or off-platform buy button assumptions.
- Keep knife/weapon content disabled until policy review confirms suitability.

## 10. Stock, sold-out and removed products

On product status/stock changes, evaluate all queued and published jobs:

- **Queued/unpublished:** cancel or block jobs if product is unpublished, sold out, seller consent withdrawn, or category policy no longer allows posting.
- **Already published:** preserve remote post ID, mark local record as stale/sold, and surface options to edit/remove only when the platform supports it and policy permits. Do not claim a remote post was updated/deleted until verified.
- **Unique one-off product sold:** the original product URL should continue to display an accurate "sold/out of stock" state or a site-owned informative landing page. Do not silently redirect to an unrelated product or advertise sold inventory as available.
- If platform permits a post edit, update link/caption only after verifying allowed fields and destination rules. Otherwise provide an admin takedown action or documented manual step.
- Keep product URL and UTM attribution stable where possible. Do not expose a broken 404 for archived products if a safe sold listing can be retained by the existing store.
- Product deletion or seller deletion does not erase the audit record needed to locate a published video; apply retention and privacy policies.

## 11. Attribution, analytics and honest reporting

For destinations supporting a clickable URL, construct from get_permalink(product_id) using add_query_arg and proper URL escaping:

- utm_source = pinterest | facebook | instagram | youtube | tiktok
- utm_medium = organic_social
- utm_campaign = seller_cam_social_hub
- utm_content = stable opaque publication identifier (not seller PII)

Do not overwrite unrelated query parameters or change WooCommerce canonical product identity. Avoid exposing secret tokens in UTM values.

Track, when genuinely measurable: provider post ID, public URL, published timestamp, platform impressions/views, engagement, clicks to KnifeRevive, sessions, add-to-cart, WooCommerce purchase and attributed revenue. Integrate with existing consent-aware analytics if present; do not install an undisclosed tracking pixel or collect buyer data outside site privacy settings.

**Critical reporting rule:** A video view is not a click, and a non-clickable caption URL is not a product link. For Instagram/YouTube/TikTok profile-based journeys, show traffic as profile/referral/aggregate or **unattributed** unless a supported mechanism actually links the individual video to the product. Never invent a CTR or revenue attribution.

Dashboard: per-platform post success/failure, link capability, attributable sessions, purchases/revenue, and "not available" for unsupported API metrics. Include configurable retention and CSV export of **non-sensitive** operational records.

## 12. Safety, policy and marketplace constraints

KnifeRevive sells kitchen knives and other cutlery, including used goods. **Organic posting**, **merchant catalog eligibility**, **shopping product tags**, **paid ads**, and **TikTok Shop** have different rules. Do not infer that approval in one means approval in another. A product categorized as "Kitchen" is not automatically policy-compliant, and miscategorization must not be used to evade restrictions.

Build a policy matrix per platform and category, with operator-visible source URLs, last-reviewed date, and rationale. Conservative defaults:

- General eligible electronics/collectibles: evaluate normally, excluding any separately restricted goods (e.g., hazardous batteries, counterfeit items, regulated products).
- Kitchen cutlery: **hold by platform** until actual organic content/commerce policy and account eligibility are reviewed. A clearly culinary product may be treated differently from a weapon, but never guarantee acceptance.
- Pocket knives, hunting/combat knives, switchblades, other regulated weapons, explicit weapon demonstrations: blocked by default unless lawful and explicitly allowed by the specific platform and content flow; no bypass.
- Copyright, unsafe demonstrations, minors, personal data, seller consent: hold/deny as applicable.
- Do not use an automatic "adult content" label unless a provider has a real supported field and it is warranted.

Log block reason and applicable policy version. Allow an authorized operator to set a documented policy decision; do not offer an unlogged "force publish prohibited content" button.

## 13. Operator account-setup checklist

The owner wants to log into **existing accounts from plugin settings**. Codex must implement Connect buttons and write a precise setup guide for these prerequisites:

1. **Pinterest:** existing Pinterest business account; create/authorize developer app, set HTTPS redirect URI, obtain required publishing scopes, select a board, confirm account permits organic video Pins.
2. **Instagram + Facebook:** existing Facebook Page and eligible linked Instagram professional account; create/configure Meta developer app, business verification/app review as required, configure redirect URI and permissions, connect through one Meta authorization flow and select Page + Instagram account.
3. **YouTube:** existing Google/YouTube channel; Google Cloud project, enabled YouTube Data API v3, OAuth consent screen/client and authorized redirect URI, appropriate scopes, quota/project verification if needed. Sign into the existing Google account and choose channel.
4. **TikTok (optional):** existing TikTok account; TikTok developer app, Content Posting API product, verified media source domain if used, scopes, app audit, creator authorization, permitted publishing UX.
5. **WordPress hosting:** HTTPS, valid cron runner, server outbound HTTPS, enough temporary disk for videos, secure server secrets, working PHP extensions, and optional ffprobe/FFmpeg if the chosen validation/transcode strategy needs them.

The plugin should show each provider as **Not configured → Ready to connect → Connected / Limited / Review required / Reconnect required**, with explicit missing prerequisites. No social password entry, no unsupported "one click works everywhere" promise.

## 14. User experience and seller experience

- Sellers keep creating videos in existing ListLab/Seller Cam. Do **not** add another video uploader.
- Sellers see a concise marketing-consent control and an optional "Promote this listing on KnifeRevive social accounts" status/opt-out, only for their own listings, if the existing UI offers a safe extension hook.
- After consent, publishing occurs automatically once eligible and globally enabled; no routine admin review queue.
- Seller-facing status can say "Queued", "Posted to Pinterest", "Waiting for account authorization", or "Not eligible"; never expose credentials, provider errors with sensitive data, or other sellers' records.
- Admin sees account Connect buttons, per-platform controls, queue, post links, failures, and exception-only holds.
- Provide a **Dry run** button that evaluates a selected product and shows exactly what would be sent to each platform, without sending anything.
- Provide a **Test publish** action that is explicitly labeled as a real external post, with confirmation, one selected account/product, and no implicit global enable.

## 15. Testing and acceptance criteria

Build automated tests using a fenced WordPress/WooCommerce environment, synthetic vendors/products/media, mock HTTP responses and no live provider credentials. Match the repository's existing test/packaging conventions where practical. Test:

1. Product created as draft, pending moderation, private, trashed, out-of-stock, backorder-eligible, and published/in-stock.
2. Video missing, still processing, replaced, duplicate save, wrong attachment owner, unauthorized URL, expired signed URL, 15.0 seconds exactly, 15.01 seconds, rotated iPhone portrait, HEVC/MOV and invalid codecs.
3. Seller grants, denies, revokes consent; one vendor cannot control another vendor's products; admin-only OAuth and publish actions.
4. Knife/category policy holds, prohibited goods, seller/admin opt-outs, and global kill switch.
5. One job per product/platform/account/video version despite repeated WooCommerce/ListLab save hooks; uncertain remote outcomes reconcile without duplicate posts.
6. Token expiry, refresh, reconnect, invalid OAuth state, wrong callback user, missing scope, revoked permission, API 401/403/429/5xx, retry-after, upload timeout and asynchronous processing.
7. Pinterest video Pin has correct exact product link and cover; feed video_link does not generate duplicate Pins.
8. Instagram Reel and Facebook Page Reel use actual publishing flows; no false clickable-link or shopping-tag claims.
9. YouTube upload uses OAuth, valid resumable transfer, no invented shorts=true flag, and marks description URLs non-clickable.
10. TikTok remains gated until required audit/UX conditions; no unauthorized public publishing or forbidden overlays.
11. Product sold/unpublished during queue and after publishing; correct cancel/stale/takedown behavior.
12. Logs/REST/admin UI are redacted and capability-checked; no token/PII leak; SSRF and upload-host tests.
13. Plugin activation/deactivation/upgrade/rollback do not mutate other plugins, orders, seller balances, products, or catalog feeds.
14. PHP lint, WordPress Coding Standards, dependency/security checks, browser accessibility checks and clean ZIP installation.

**Pilot:** After owner approval, select **5–10 consented, published, in-stock listings** with valid <=15s videos and policy-approved categories. Start with **Pinterest only**, confirm actual Pin creation and exact clickable destination. Add Instagram/Facebook and YouTube one platform at a time, then TikTok only if permitted. Verify real mobile rendering, seller consent, post links, no duplicates, sold-out behavior and UTM analytics. Start with a strict daily cap (for example 5 posts/day, configurable) and a pilot allowlist; do not bulk backfill the existing catalog.

**Release gates:**

- Every enabled provider has a successful authenticated connection and a documented tested publish path.
- No publication happens when global switch is OFF, consent missing, policy blocked, product unavailable, or video duration >15s.
- At least one complete, owner-approved real pilot post per enabled provider has a confirmed remote ID/permalink and correct documented link behavior.
- Repeated saves/retries do not create duplicate remote posts.
- Token secrets are encrypted/redacted and account disconnect works.
- No routine admin approval required for eligible future listings.
- Admin can pause instantly, inspect queue, identify failures and request takedown.
- Clean install ZIP, source, version, migration notes, tests, SHA-256, and rollback instructions are delivered.

## 16. Build milestones and Codex deliverables

### Milestone A — Discovery + safe skeleton
Deliver discovery.md, capability matrix, plugin bootstrap, activation/schema, admin pages, mocked platform adapters, seller consent integration plan, and dry-run eligibility. No real posting.

### Milestone B — Pinterest MVP
Implement Pinterest OAuth, board selection, verified video source, validation, Action Scheduler jobs, video Pin upload/publish/reconcile, direct product URL, status dashboard, and tests. Run pilot only with owner permission.

### Milestone C — Meta + YouTube
Implement Meta account connection, Instagram Reels, Facebook Page Reels, Google OAuth and YouTube Shorts; show exact link limitations. Keep Shopping tags separate.

### Milestone D — TikTok feasibility and optional adapter
Verify developer audit, explicit creator UX and account suitability. Implement only supported, compliant API path; otherwise document "manual/draft only" with no fake automatic posting.

### Milestone E — Production readiness
Finish throttling, sold-out/takedown handling, analytics, security review, accessibility, docs, regression tests, ZIP build, release manifest, staging verification and owner-approved production rollout.

At the end of **each** milestone, give the owner a factual report: implemented source files, what was tested (mock vs live), blockers requiring their account setup, risks, and whether production posting remains disabled. Do not silently expand scope.

## 17. Explicit implementation decisions

- **One independent plugin:** KnifeRevive Social Hub.
- **No social passwords in WordPress:** use provider-hosted OAuth and Connect/Reconnect buttons in settings.
- **One video source:** existing Seller Cam, max 15 seconds, portrait preferred, 30 fps supported.
- **One product destination:** exact WooCommerce permalink; no generic homepage fallback presented as a product link.
- **Automatic by default only after setup:** no per-video admin approvals, but consent, category policy, account capability, and master switch remain mandatory.
- **Pinterest first:** direct organic video Pin URL is the most promising verified product-level referral path.
- **Instagram/Facebook/YouTube next:** publish for discovery, but respect actual clickability limitations.
- **TikTok conditional:** official API exists, yet audit/UX/content rules can prevent fully unattended posting.
- **No promise of traffic:** measure actual clicks, purchases and revenue rather than assuming views convert.
- **No automatic live deployment:** Codex may build, test and package; owner explicitly authorizes installation, account connection and real publishing.

## 18. Final response format for Codex

When implementation is finished, report:

1. Repository branch/commit and exact plugin ZIP path + SHA-256.
2. Actual ListLab/Seller Cam hook/media mapping discovered (or blocker if unavailable).
3. Provider-by-provider status: implemented, mocked, connected, app-review pending, real publish verified, and actual link behavior.
4. OAuth configuration steps the owner must complete, including exact callback URIs generated by the plugin.
5. Tests executed with pass/fail counts and limitations.
6. Installation/staging/pilot instructions, default switches, rollback, and operational monitoring.
7. Any deviation from this specification and why.

**Do not report "implemented" when only a specification, mock, or incomplete adapter exists. Do not claim external posts, verified app permissions, or public deployment without actual evidence.**
