# Source-of-truth matrix

All live versions below were re-read on October 31, 2026. Matching a header is not a production hash comparison.

| Component | Active production | Identified local candidate | Repository state / verification |
|---|---|---|---|
| Returns | 0.1.0-rc4 | `KnifeRevive_Documentation/checkout-returns-hotfix-2026-10-29/kniferevive-returns/` | Matches version; complete candidate source and ZIP found. Not tracked on the instruction branch; no fresh full production diff. |
| Return Policies | 1.0.0 | Local Site `wp-content/plugins/kniferevive-return-policies/` | Matches header. Actual live taxonomy was inspected in the owner audit; four terms only. |
| Return Policy Setup | 1.0.0 | `dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/kniferevive-return-policy-setup/` | Historical source; must compare before editing. Do not run its old content installer against the updated public page. |
| Seller Orders | **1.7.0-rc3** | `email-money-deployment-2026-10-29/src/kniferevive-seller-orders/` is **1.7.0-rc2**; local repository copy is rc1 | **Complete matching rc3 source missing.** Live root required: `wp-content/plugins/kniferevive-seller-orders/`. Repository's `wordpress-overlays/kniferevive-seller-orders/` is not a complete current release. |
| ShipKit | 0.6.9-rc3 | `shipkit-activation-2026-10-29/kniferevive-shipkit/`; matching named release ZIP | Version matches; not production-file verified; repository runtime copy is 0.6.3-rc1 and untracked. |
| Stripe Connect | 0.7.2-rc1 | `KnifeRevive_Agent_Commerce/wordpress/kniferevive-stripe-connect/`; October 28 release ZIP | Header matches, but local directory is untracked. Full live diff still needed. |
| My Money | 1.2.2 | `email-money-deployment-2026-10-29/src/kniferevive-my-money/`; matching named ZIP | Header matches; repository runtime copy is 1.2.1-rc1 and untracked. |
| Dokan Lite | 5.3.0 | Existing local Woo/Dokan test dependencies | Active production version confirmed; retain seller/order ownership APIs and parent mappings. Full dependency parity still to verify. |
| WooCommerce | 11.2.1 | Existing local installation/test dependency | Active production version confirmed; HPOS/storage/config parity not established in this PR. |
| Woo Shipping | 2.4.3 | Existing installed/provider integration | Active production version confirmed; listing a plugin does not verify return-label APIs, permissions or funded purchase. |

Except for the Local Site and Agent Commerce entries, local paths are relative to `C:/Users/Svet/Documents/KnifeRevive_Documentation/`. The working local repository is `C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/`.

The original checkout has unrelated modified packing-slip files and untracked runtime sources/tests. It was not reset, cleaned, committed or switched. This documentation PR was prepared in a clean separate clone based on the exact instruction branch.

## Recovery reference, not full rollback

Private local release ZIPs were located for Returns rc4, ShipKit rc3, Connect rc1 and My Money 1.2.2. Full source hashes can identify those candidate packages. They do not include current case/order data or private-vault keys/files and do not prove Seller Orders rc3 can be restored. A complete rollback must be established before any runtime deployment.
