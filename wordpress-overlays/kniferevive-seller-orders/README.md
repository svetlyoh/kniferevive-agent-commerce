# Seller Orders 1.1.7 overlay

Narrow patch against the exact captured production 1.1.5 plugin. Preserve all 30 baseline files and schema 1.1.4. This is not the unrelated local 1.2.0 build. Agent Commerce 0.5.x supplies paid/unpaid appointment cards and correct merchant-trip handoff labels.

The overlay excludes WooCommerce refund objects from native order lists and fixes a reproduced sharpening-dashboard refund DTO fatal. Confirmed booking cards now display the native sharpening stage and original-order link while retaining separate booking review. Agent Commerce 0.5.17 initializes/synchronizes the confirmed original order without changing payment or advancing receipt. It retains prior pickup-list behavior, seller ownership and permissions. See docs/confirmed-booking-native-orders-0.5.17.md.

Build with `python tools/package_seller_orders_overlay.py <baseline-directory>`. Every baseline file must match `baseline-manifest.json`; private production backups stay local. Output includes full candidate and rollback ZIPs with per-file/archive hashes. Never manually copy the unrelated local plugin over production. See docs/prepaid-booking-0.5.1.md for deployment and test status.
