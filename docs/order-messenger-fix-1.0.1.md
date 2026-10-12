# Order Messenger 1.0.1: Quick Update

Follow-up: the owner confirmed that Quick Update opens the composer on order 3093. The subsequent mobile design is deployed as 1.0.2; see `order-messenger-mobile-1.0.2.md`. The account limitation below describes the automation session during this release's QA.

The installed 1.0.0 plugin could render its endpoint button while skipping JavaScript and CSS: `is_wc_endpoint_url('seller-orders')` returned false because Seller Orders registered a WordPress rewrite endpoint but not WooCommerce's endpoint query-variable map. Messenger now registers the missing mapping through `woocommerce_get_query_vars`, preserving existing mappings. This fixes the composer asset gate and the endpoint-dependent cache/header behavior. No recipient, recorded-vendor ownership, nonce, manager-access, deduplication or rate-limit rule was changed.

The action is now **Quick Update**, a lime-green button with an email-send SVG icon and the existing button shape/font treatment. The composer heading matches; its send action is **Send Update**, styled blue. Mail transport outcome messages say Update rather than Ping. Email branding/settings remain independent.

Source was imported unchanged from the owner's `KnifeRevive_Site/wp-content/plugins/kniferevive-order-messenger` into this repository before the repair. That source exactly matched the supplied 1.0.0 ZIP and LocalWP copy. Updated source is mirrored back to both workspaces. Verified 1.0.1 ZIPs are saved under `KnifeRevive_Site/deployment-packages` and `KnifeRevive_Documentation/deployment-packages`; hashes are in `order-messenger-release-1.0.1.json`. The original 1.0.0 package remains available for rollback.

## Verification and deployment

- 72 real WordPress/WooCommerce/Dokan checks passed in the fenced `krev_agent_sandbox` database on loopback port 11029, including recognition of the custom WooCommerce endpoint, both JS/CSS enqueued for an authorized order and denied for another seller. Existing permissions, recipients, preview/send renderer equality, atomic delivery/deduplication, failure/recovery and attachment checks remain covered. Two emails were intercepted locally; no external mail was sent.
- 43 actual composer JavaScript/DOM checks passed. PHP lint and JavaScript syntax checks passed.
- A native browser opened the actual composer and fetched context/history against a synthetic local preview. Sending was disabled and every POST refused. The real button and dialog were visually checked. This is a local UI proof, not authenticated buyer-inbox acceptance.
- Native WordPress plugin replacement completed successfully on kniferevive.com. The Installed Plugins screen confirms active Order Messenger **1.0.1**. The live order page now has the WooCommerce endpoint/focus class, confirming the new endpoint map is active.
- The current browser account can view orders 3093 and 2656 through Seller Orders, but the Messenger root is not rendered for that account. Seller Orders has broader viewing permissions than Messenger's recorded-vendor checks, and Messenger's manager-access setting is disabled. Those restrictions remain intact. Verification of a real seller's button/context/preview still requires the affected seller session/order URL; the user was asked for that URL. No ownership was invented, manager access enabled or real buyer contacted to perform QA.

Evidence is in the owner's `KnifeRevive_Documentation/diagnostics/order-messenger-*1.0.1*` files: native log, lime button preview, composer preview and live plugin version screenshot. The test database and local preview server were stopped after checks. No theme, Seller Orders, payments or fulfillment action was deployed or changed by this repair.
