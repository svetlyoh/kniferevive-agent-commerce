# Shop All and Shop Knives Only — October 13, 2026

The shop archive previously showed Shop Knives and unconditionally applied a knife-category allowlist. Theme 2026.10.13.2 makes the full catalog the default and adds a prominent accessible checkbox beside Shop All. The checked URL is /shop/?kr_knives_only=1. Switching preserves the native query filters and sorting and resets pagination. The canonical knife category list, including Electric Knife and descendants, applies only when selected; sharpening services are excluded from that scope. Other collection/category pages keep their own filters.

The control renders through the existing core query-title block. Heading and breadcrumb read Shop All; the checkbox reflects the selected scope. The tap target is at least 48 pixels high, has a keyboard focus indicator, wraps below the heading on mobile, auto-submits when JavaScript is available, and offers an Apply button without JavaScript.

Changed source: functions.php loads inc/shop-scope.php; annex.php gates the existing positive knife query; assets/css/shop-scope.css styles only the shop header. Existing assets and theme features are preserved in the complete runtime package. PHP syntax passes; live filter, pagination and responsive verification will be recorded after deployment.

The change handler reads the live URL because WooCommerce interactive sorting/filtering can leave server-rendered hidden fields stale. It preserves current query parameters while clearing pagination.
