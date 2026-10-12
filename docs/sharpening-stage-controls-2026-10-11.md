# Sharpening stage controls and Quick Update

Deployed 11 October 2026 Pacific: Seller Orders 1.1.9 and Order Messenger 1.0.3.

## Behavior

The illustrated sharpening timeline previously consisted of read-only articles. Existing operators had a Stage form below the cards, but could not select the illustrations. Each illustration is now a native button for an existing authorized sharpening operator. Select any of the eight stages, then use **Save selected stage**. The existing dropdown stays synchronized. Selection alone never saves a stage or sends an email. Saving has visible progress/errors and blocks duplicate submissions. The existing form remains available for notes, handoff information and ready date. Ordinary buyers keep the read-only timeline.

The change uses the existing native stage REST endpoint and operator check. The script initializes after the DOM is ready, including optimized/bundled asset delivery. Seller Orders registers its three custom endpoint query variables with WooCommerce without overriding existing mappings. Selecting an earlier step reopens the sharpening completion flag, while payment and WooCommerce status stay independent. Unknown stages and cancelled/refunded/failed progression remain blocked. Get them back still requires payment recorded by WooCommerce. The existing Complete Order action and existing stage-notification mail behavior are retained; icon selection does not complete or charge an order.

Quick Update now appears in the sharpening order header, using the same mobile composer. Its server-side scope reuses the existing native sharpening-operator permission, validates the order/customer relationship, and limits mixed-order context to sharpening services. Registered customers use their account email; guest customers use the recorded billing email. Default replies go to the authenticated operator's registered account email. No product-author ownership inference or global merchandise manager-access setting was introduced. The existing master Messenger enabled setting, nonce, attachment rules, audit, deduplication and rate limits apply.

## Validation

- 117 native WordPress/WooCommerce/Dokan assertions passed in **each** of classic and HPOS storage with synchronization. Both runs intercepted all mail locally (13 messages per run), including an operator message through the real Messenger send handler. No external mail or payment request was made.
- Native checks cover all eight persisted transitions, read-only customer rendering, ordinary buyer/unrelated seller/anonymous write denial, missing messaging nonce, capability revocation, recorded customer recipient/operator reply, sharpening-only context for mixed orders, unpaid-return rejection, invalid stage and reopening completed workflow.
- 18 frontend checks exercise the actual PHP stage template and actual JavaScript: every icon selects its matching stage, selecting never posts, dropdown synchronization, explicit save with nonce, preserved note/handoff, duplicate suppression and visible failure recovery. The existing 43 composer/clipboard/navigation checks also pass.
- PHP lint passed for 23 changed/component PHP files; both scripts passed syntax checking.
- Native browser on live order 3014: eight selectable icons, Blade check selection, enabled save action and synchronized dropdown. No save was performed. Refresh confirmed persisted stage remains **handoff**, Payment complete and Processing. Quick Update opened, retrieved the authenticated customer context and enabled Send Update. No message was sent or draft text submitted.
- At 390×844, the live timeline used two columns without horizontal overflow; the composer covered the screen and its footer remained within it. Physical keyboard/photo-picker behavior remains a device QA item inherited from the mobile release.

Proof: `C:/Users/Svet/Documents/KnifeRevive_Documentation/diagnostics/sharpening-3014-stage-controls-1.1.9.png`. Native logs are `sharpening-native-legacy.txt` and `sharpening-native-hpos.txt` in the same diagnostics directory. Temporary browser tabs and the fenced test database were closed after verification.

## Packages and source

Seller Orders is built against the verified 30-file production 1.1.5 capture plus the existing reviewed overlays, now including the reviewed JS/CSS/workflow changes. It retains schema 1.1.4 and excludes the unrelated local 1.2.0 build. The package builder now includes JS/CSS overlays and still rejects files absent from the baseline inventory. The owner's unrelated local Seller Orders source is preserved; canonical production changes are in `wordpress-overlays/kniferevive-seller-orders` and the verified candidate/package.

Both packages replaced the existing installed plugins through native WordPress; both update pages reported success. Archives are in both owner deployment-package directories. Previous 1.1.8/1.0.2 packages remain for rollback. Messenger source is mirrored to the owner and LocalWP folders.

- Seller Orders 1.1.9 SHA256: `59ec78df740875fa15d3ebda3deb9a64376596f110d4bf4c6ace6941a02a99cb`
- Messenger 1.0.3 SHA256: `52cff635f2479a1ab30ecdf213be613b4c4efdad798459ed6cf24e988c9db6f0`

Per-file manifests are `dist/seller-orders-release-manifest.json` (owner-local) and `docs/order-messenger-release-1.0.3.json`.
