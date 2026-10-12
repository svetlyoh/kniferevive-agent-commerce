# Seller Orders 1.1.9 overlay

1.1.9 turns the eight sharpening illustrations into authorized operator stage
selectors with an explicit Save selected stage action, saving/error feedback and
duplicate-submit protection. Customers retain the read-only timeline. Endpoint
query-variable mappings are registered with WooCommerce. Rewinding a completed
workflow clears only its sharpening completion metadata; payment/order status
remain separate. Scripts initialize after DOM readiness, including optimized
asset delivery. The production baseline and schema stay unchanged.

Narrow patch against the exact captured production 1.1.5 plugin. Preserve all 30 baseline files and schema 1.1.4. This is not the unrelated local 1.2.0 build. Agent Commerce 0.5.x supplies paid/unpaid appointment cards and correct merchant-trip handoff labels.

The overlay excludes WooCommerce refund objects from native order lists and fixes a reproduced sharpening-dashboard refund DTO fatal. Confirmed booking cards now display the native sharpening stage and original-order link while retaining separate booking review. Agent Commerce 0.5.17 initializes/synchronizes the confirmed original order without changing payment or advancing receipt. It retains prior pickup-list behavior, seller ownership and permissions. See docs/confirmed-booking-native-orders-0.5.17.md.

Build with `python tools/package_seller_orders_overlay.py <baseline-directory>`. Every baseline file must match `baseline-manifest.json`; private production backups stay local. Output includes full candidate and rollback ZIPs with per-file/archive hashes. Never manually copy the unrelated local plugin over production. See docs/prepaid-booking-0.5.1.md for deployment and test status.

Seller Orders 1.1.8 and Agent Commerce 0.5.18 replace Dokan booking order links with native sharpening detail links. HTML and REST detail allow the active owning seller to read only the validated original booking order. Customer returns and operator/stage write permissions remain unchanged. See docs/seller-native-order-link-0.5.18.md.
