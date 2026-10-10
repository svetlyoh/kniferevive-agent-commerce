# Seller View Order repair — Agent Commerce 0.5.18 / Seller Orders 1.1.8

## Problem and implementation

Confirmed sharpening booking cards used the authenticated Dokan order URL for a non-operator seller. That deprecated dashboard route returned the seller to My Account instead of the order. Both the confirmed card and the seller booking inbox now link directly to `/my-account/sharpening-orders/{original-order-id}/`.

The native HTML detail and native REST GET detail use one sharpening-specific read permission. Existing operator/customer access remains intact. An active owning seller can read only its original merchant-visible requested/confirmed booking order, validated against the stored booking, original listing intent or unpaid-order link, native vendor attribution, exactly matching service items/quantities and Local Pickup handoff. Invalid/missing links fail closed. References and order IDs alone do not authorize access.

This does not grant the seller the global sharpening operator capability, customer return permissions or stage-write access. Native operator controls and REST stage writes retain their existing authorization. Read requests never charge, create orders, initialize stages, confirm appointments or send emails. Payment, refunds, service day and stage remain separate facts. The owning seller's detail page links back to Seller Orders → Local Pickup.

## Validation and release

- Synthetic owning-seller HTML and REST detail reads pass for unpaid bookings; unrelated/anonymous/disabled sellers and tampered native vendor attribution are denied.
- Prepaid original-order intent binding and owning-seller native detail are covered separately.
- Operator-only controls remain hidden from a non-operator seller; native stage-change attempts are rejected. Buyer permissions remain unchanged.
- Classic storage and HPOS, with synchronization: 105 bridge + 95 paid-booking assertions per mode = 400 assertions. Test emails and processor evidence are synthetic; no real appointment or charge is used.
- Seller Orders is built against the exact captured 30-file production 1.1.5 baseline with the reviewed overlay; schema remains 1.1.4. The two newly overlaid production files (REST reader and native detail template) match that baseline before deployment.
- Agent Commerce package contains exactly the 40 reviewed tracked plugin files. No unrelated catalog-discovery implementation is included.
- Retain existing 0.5.17 / 1.1.7 archives for rollback. Replacing the existing plugin packages preserves settings and order records.

Portable skill remains 0.5.14. This website repair does not require a Muse/ClawHub reinstall.

## Live deployment verification — 10 October 2026 Pacific

Both existing installed plugins were updated successfully through the authenticated WordPress plugin uploader. The live versions are Agent Commerce 0.5.18 and Seller Orders 1.1.8. All seven changed PHP source files were read back through the plugin editor and match the reviewed source after line-ending normalization.

Clicked View Order on the live Local Pickup card for 3014. It opens `https://kniferevive.com/my-account/sharpening-orders/3014/`, showing Knife Sharpening #3014, Payment complete, Processing, Small Knife Sharpening quantity 1 and current Book & choose handoff timeline stage. Paid state, $5.61 total and confirmed 2026-10-16 intake day remain intact on its order card. No real order was confirmed, charged, refunded or advanced during verification.

Screenshot: `C:/Users/Svet/Documents/KnifeRevive_Documentation/diagnostics/seller-view-order-3014-0.5.18.png`.

Live browser checks use the already authenticated operator. Owning non-operator seller access is proven in the fenced synthetic role tests, without impersonating a production seller. Refresh the existing Seller Orders → Local Pickup page before using its updated View Order button. Previously copied Dokan URLs are stale; use the native URL above.


Archive SHA256 `kniferevive-agent-commerce-0.5.18.zip`: `1948075804bd561b0852745e354029f7a10d87567bf944e5f9b4c635cfa583c8`.

Archive SHA256 `kniferevive-seller-orders-1.1.8.zip`: `c378a1c7f7c4a6461fc5798879f246470d4af21d6ff12f3882418b2041a975ae`.
