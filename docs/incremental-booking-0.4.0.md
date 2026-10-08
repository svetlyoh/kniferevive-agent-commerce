# Incremental booking candidate 0.4.0 — October 8, 2026

**Plugin deployed October 8 after owner approval; order creation remains gated.
Skill 0.4.0 is not released or published to ClawHub.**
See [production observations and remaining acceptance](booking-deployment-0.4.0-2026-10-08.md).
Implementation baseline: 0.3.0, source e7e807a on existing draft PR #2. No theme/core,
gateway credential, Stripe mode, seller ownership, ListLab or WhatsApp integration
was changed. The new unpaid order bridge defaults to disabled. Existing paid-flow
readiness remains off in production. The owner's instruction to follow the
incremental brief authorizes local implementation; the brief separately requires
approval before deployment, real notifications/orders and payment activation.

## Incident evidence

| Fact | Evidence | Conclusion / remaining work |
| --- | --- | --- |
| Original reference `167c8b10ecc1d73f6ec1600d8b373ee5` exists | Read-only signed-in production Agent Commerce queue: requested, 2026-10-09, unpaid drop-off, Large Knife Sharpening ×1, ZIP 94565 | Persisted request, awaiting confirmation. No production order or new email was created during investigation. |
| Administrator received email | Owner's latest report | Corrects the brief's older statement that neither mailbox received it. Actual transport trace not inspected without log consent. |
| Seller received no booking notification | 0.3.0 notify addresses customer and site admin, omits product seller | Verified source defect; mailbox receipt still needs an approved real test. |
| Seller sees no local pickup order | 0.3.0 saves custom booking records; creates no WooCommerce order on submit; no seller request inbox | Verified source behavior and mismatch with the owner's expectation. Production queue exposes no order link. A full production Woo/Dokan metadata search remains to do before recovery; absence of a queue link alone is not proof of no other native order. |
| Lost-mail risk | 0.3.0 writes notified flag before wp_mail and ignores return | New recipient outbox replaces that behavior. Do not blindly reset old flags/resend. |
| Operational warnings | Production admin displays Dokan data update/deprecated-dashboard and Woo compatibility notices; WP Mail SMTP setup shown incomplete | No upgrade or SMTP change performed. Native local compatibility tests cannot establish production transport or dashboard health. |

Installed local test stack: WordPress core from the existing local site,
WooCommerce 11.0.1, Dokan Lite 5.1.2 / Pro 4.0.6, KnifeRevive seller orders 1.2.0,
native Stripe/Connect and commission/return plugins. Production DOM identifies
Twenty Twenty-Four with the KnifeRevive child theme. The actual custom_logo
attachment resolves to Knife_Revive_Logo_OG_V2.jpg; code discovers the attachment
dynamically and serves its same-origin media URL, without a hardcoded ID/CDN.

## Incremental modules and boundaries

- **BookingSeller:** an authenticated, product-owner scoped Dokan request inbox,
  available even if mail fails. Active original seller only; mixed sellers rejected;
  no manage_woocommerce grant. Native order link uses Dokan's own view nonce.
- **BookingOrderBridge:** approved timing on_submit or on_confirm; pending unpaid
  customer drop-off/collection only. Existing approved, enabled native cod/bacs/cheque
  arrangement required. Native gateway process_payment is never called. WC CRUD,
  native tax calculation, zero local_pickup shipping line, native Dokan attribution,
  split/sync and seller order query validation. Native commissions remain Dokan's.
  No manual ledger writes. No capacity/stock reservation before confirmation.
- **BookingOutbox:** deterministic booking + stage + role + recipient jobs, persisted
  with the booking transition. Customer/seller/admin links differ. Native mail
  transport via Action Scheduler with existing minute-cron sweep fallback, bounded
  retries, mail hooks, redacted errors and administrator reconciliation. Mailer
  acceptance is not inbox delivery. Crashed sending jobs require checked manual
  retry. Historical recovery handles one role/reference, never a bulk migration.
- **BookingAuthorization:** first-party unchecked sharing consent with CSRF,
  merchant/purpose/principal/booking/input hash/version/expiry proof. Expiry,
  revocation and changed input invalidate it. Purchase-review permission is separate
  from provider authentication, host wallet authority and verified settlement.
  Client-supplied approval booleans and portable draft PII are rejected.
- **BookingEvents:** bounded PII-free status facts on the existing authorized GET.
  Request/order/confirmation/cancellation facts are distinct; native verified payment
  and refund-recorded facts reconcile during scoped polling. No callback registration
  or outbound bot endpoint exists; push supported=false. No simulated chat receipt.
- **Human handoff:** new service-only draft links use a 30-minute opaque referral
  bound to the original shopper scope. A used referral alone cannot access the
  submitted request. Existing protected capability links remain compatible.
- **PrivateBrand / forms:** native global styles, font faces and discovered logo,
  CSP nonce, same-origin media/fonts, no wp_head/footer analytics hooks, no-store,
  no-referrer, secure scoped cookies. Four clear sections, one ZIP, per-SKU quantities,
  explicit day request, optional trip address, live fees, separate permission actions,
  preserved errors, no-JS coverage POST, support footer. Listing and legacy review
  screens use the same restricted wrapper.

## Host support and standards gaps

| Requirement | Evidence / host | Gap and implemented fallback | Test / status |
| --- | --- | --- | --- |
| Portable metadata/references | [Agent Skills specification](https://agentskills.io/specification) defines Markdown packages | No identity/payment/callback API implied by installation. Keep merchant-specific triggers and untrusted catalog isolation | Local skill validation and relative links; new registry audit pending |
| OpenClaw import | [Official skills docs](https://docs.openclaw.ai/tools/skills) describe workspace/shared skill loading and tool eligibility | Host's HTTP tools may consume API; no tested customer address/wallet/inbound callback grant | Portable human form and scoped polling supported; no host payment integration claimed |
| ClawHub provenance/security | Exact [0.3.0 evidence](booking-publication-0.3.0.md), eight matching source hashes and generated card | 0.3.0 Pass has disclosed warnings; cannot extrapolate to 0.4.0 | New artifact not submitted; no 0.4.0 audit/certification claimed |
| Muse, Grok, ChatGPT or another host | No concrete installed host provider contract/grant/inbound endpoint supplied | Text import or reusable prompt is not proof of installed integration. Structured choice controls and automatic chat receipts unverified | Choice-only API + first-party review; unknown host permissions, push=false |
| Two authorization gates | Server proof bound to exact input; native quote hash/expiry and existing payment validation | No delegated card charge or universal address grant; stored account/card not authority | Missing/expired/revoked/changed permissions fail closed; human reviews quote/provider checkout |
| Cards / Google Pay | [Stripe Checkout](https://docs.stripe.com/payments/checkout), [native Stripe settings](https://woocommerce.com/document/stripe/setup-and-configuration/settings-guide/) and [webhooks](https://woocommerce.com/document/stripe/setup-and-configuration/stripe-webhooks/) | Provider authentication, device eligibility, actual seller payout/refund and production webhooks unverified | Synthetic regression only; paid gates remain off; no production test-mode switch |
| Lightning | [BOLT11 specification](https://github.com/lightning/bolts/blob/master/11-payment-encoding.md), existing native coordinator | Existing order-bound invoice read only; real mainnet payee/settlement/refund test and independent host wallet mandate missing | Native fixture reads/replay/expiry remain tested; autonomous order/invoice/send disabled |
| Seller order visibility/accounting | [Dokan orders](https://dokan.co/docs/wordpress/vendor-dashboard/orders/), installed native APIs | Local pending orders verified; live installed-dashboard warnings and owner offline-method/timing approval remain | Four storage modes, owning/foreign sellers, native commission sum, local pickup, concurrency; production verification pending |
| CRUD/HPOS | [WooCommerce recipe](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/) | Classic adapter and HPOS metadata lookup use their respective native query paths | Classic/HPOS synchronization on/off; no core or native order SQL writes |
| Notifications | [wp_mail contract](https://developer.wordpress.org/reference/functions/wp_mail/) and [Action Scheduler](https://actionscheduler.org/api/) | Queue/transport/inbox differ; no provider delivery/bounce callback supplied | Accepted/failed/backoff/terminal/uncertain/retry synthetic tests; real inbox and deployed worker execution pending |
| Accessible private form | [WP global styles](https://developer.wordpress.org/themes/global-settings-and-styles/), [WCAG 2.2](https://www.w3.org/TR/WCAG22/) | Narrow native branding integration rather than arbitrary theme scripts; no certification claimed | Desktop/mobile visual, keyboard/labels/errors/no-JS/security checks; further assistive-technology audit remains |
| ACP/AP2/MCP/x402/L402/MPP | No implemented protocol binding or certification evidence | OpenAPI and SKILL.md alone are insufficient | Not advertised as compliant |

No universal registered bot is invented. The current portable integration retains
only the originating shopper scope and opaque correlation ID. Callback ownership,
status-sharing grants, HTTPS/DNS/SSRF checks, signing keys and delivery outbox are
future work only if an actual host offers an approved inbound API.

## Migration, approval and historical recovery

No schema version migration, gateway activation, seller reassignment or bulk
historical order creation is needed. New Store record kinds use the existing table.
Submitted booking records are retained rather than pruned as ephemeral drafts;
agree a data-retention/deletion policy before production rollout. Jobs/events have
90-day expiry metadata; mapping has a year expiry, with original native order
metadata available for recovery. Those expiries are not automatic purge timers:
the current pruner excludes these durable record kinds. Agree and implement the
retention policy separately. Do not delete ledger/order records as a retry mechanism.

New API contract 1.3.0 adds optional status facts. **Input tightening:** customer and
pickup_address on portable POST /bookings now return INVALID_REQUEST. Existing
clients that prefilled those fields must move contact sharing to the human form;
existing private booking links and scoped headers remain supported. Publish this
change explicitly when releasing, rather than describing it as wholly additive.

The owner subsequently approved deployment and order-at-submit timing, which is
saved with verification false and no offline method selected. The other decisions
and production verification below remain pending:

1. Create unpaid orders on submission (recommended for this incident) or after
   confirmation? Approve pending status, no pre-confirmation stock reservation,
   and an existing offline gateway/arrangement. COD is not assumed equivalent to
   pay at KnifeRevive drop-off; no native method is silently enabled.
2. Confirm actual seller account email, site admin recipient and daily capacity.
   Consent to inspect redacted transport/site logs and approve real test recipients.
3. Approve/edit brief section 7 cancellation/refund policy, deadlines, quality remedy,
   timeline, no-shows and trip-fee treatment; assign public URL/version/effective date.
   No policy text is published by this change.
4. Approve deployment window and rollback; first enable seller inbox/notifications
   with order bridge disabled. In an owner-approved controlled test window, enable
   the selected timing and existing offline method only for the approved unpaid
   test, verify the native order and actual mailboxes, and disable again if any
   acceptance condition fails. Leave it enabled for customers only after that
   verification and owner approval. Payment tests/activation
   and ClawHub publication need separate approvals. No callback host is registered.

After an approved deployment, recover reference 167c8b10ecc1d73f6ec1600d8b373ee5:
find its original record and native order metadata, inspect original delivery
history, verify current seller; reconcile only the seller notification in Agent
Commerce after the checkbox approval. The new seller inbox shows the existing
request without creating an order. Old records have no new sharing proof, so have
the human reopen the original protected page and renew contact-sharing consent
before order creation or confirmation. If the requested day is past, agree a fresh
request with the customer; do not silently reschedule. With the bridge approved,
reconcile the original reference into its single unpaid order; any existing paid,
cancelled, unexpected or duplicate order stops recovery for administrator review.

## Rollback without a charge

Disable the new order bridge first; pause/reconcile notification actions and keep
durable rows. Back up configuration and original plugin, then restore the preserved
0.3.0 ZIP if needed. Existing WC orders, Dokan records and booking mappings must
remain intact. Rolling back PHP does not cancel orders, erase notifications, refund
payments or revoke external wallet sends. Do not let old notified flags trigger
bulk resends. Keep payments off and reconcile the original request/order before
any retry. No main-branch merge or immutable tag overwrite is part of this work.

## Local acceptance evidence

The completed matrix passed **1,030 synthetic assertions**: 75 listing, 66 booking
and 67 bridge checks in each of four storage/synchronization modes, plus 91 legacy
checks in classic/HPOS and 16 Stripe setup fixtures. PHP/JavaScript syntax, skill
validation, 14 real response payloads against the generated contract and REST
object serialization passed as well. No external mail or processor was contacted.

Three browser submissions each produced one pending unpaid native order with
three separate recipient jobs. The installed Dokan Orders template and seller
query showed the correct owning seller, total and native commission calculation;
this is a local fixture, not evidence of the production dashboard. The browser
checks also covered a 390px viewport without horizontal overflow, script-blocked
submission, preserved validation errors, opaque referral handoff and receipt reload.

- [Native seller Orders screenshot](screenshots/seller-orders-0.4.0.jpg)
- [Seller request inbox](screenshots/seller-inbox-0.4.0.jpg)
- [Mobile form](screenshots/booking-mobile-0.4.0.jpg)
- [Script-blocked validation error](screenshots/booking-error-0.4.0.jpg)
- [Original-referral receipt](screenshots/booking-referral-receipt-0.4.0.jpg)

Reproduce in the isolated database with the existing sandbox setup and
`tests/run-listing.ps1 -PhpPath <local-php.exe> -WordPressRoot <local-WP-core>`.
It requires the dedicated loopback MySQL sandbox on port 11019; never substitute
a production database or load its wp-config.php. Browser fixtures use
`tests/ui-router.php`, `KREV_BOOKING_UI=1` and the same sandbox, with outbound HTTP
and mail intercepted. Recorded evidence and limits are in
[the test report](incremental-booking-tests-0.4.0.json); package hashes are in
[the manifest](release-manifest-0.4.0.json).

A passing local suite is not a production booking, mailbox delivery, payment or
ClawHub audit result. The source is in existing draft PR #2. Plugin deployment is
recorded separately; order-bridge enablement, historical repair and registry
publication remain pending their respective approvals and verification.
