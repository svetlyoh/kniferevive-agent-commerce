# Seller Orders 1.1.5 overlay

Four-file UI patch against the exact captured production 1.1.4 plugin. This is not a standalone plugin or the unrelated local 1.2.0 build. Requires Agent Commerce 0.4.2 for appointment cards; without its filter provider the original lists continue to work.

Build with `python tools/package_seller_orders_overlay.py <baseline-directory>`. Every baseline file must match `baseline-manifest.json`; keep the private live backup locally. Output includes full candidate and rollback ZIPs plus per-file/archive hashes. The archive contains the existing 30 plugin files, four overlaid, without this README or manifest. Schema stays 1.1.4. See `docs/seller-booking-visibility-0.4.2.md` for approval, test, deployment and rollback evidence.
