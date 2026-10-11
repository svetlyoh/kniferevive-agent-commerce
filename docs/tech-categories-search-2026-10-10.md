# ListLab searchable categories and models

Deployed on kniferevive.com, October 10, 2026. ListLab **1.8.2**, Product Attributes **0.3.4**, Merchant Sync **0.8.6**, and Marketplace Imports **1.0.4** update the existing installations.

## Seller behavior

Select Tech as the main category, then type in **Tech subcategory** to filter 417 native category options. The dropdown button shows all options. Arrow keys select a highlighted option; Enter confirms; Escape restores the saved selection. Search text alone never changes the product category. Model has the same search and dropdown controls wherever the category schema offers that specification. The existing custom model entry remains available.

The Electronics descendants come from Google's official taxonomy retrieved October 10, 2026: https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt. Readable relative paths distinguish duplicate leaf names. Stable native term slugs `tech-google-<ID>` and term metadata map each selection to its exact Google category. The Tech parent is native slug `technology`; its broad fallback is Google 222 Electronics. Manual exact-category overrides remain supported. The full automatic mapping table is read-only in Merchant Sync to avoid a very large manual-settings form.

The category import completed **417/417** in nine native admin batches. Import retries reuse existing terms. Existing products are not bulk recategorized. ListLab persists parent and selected child, validates that the child belongs to Tech before saving, and inherits parent specification and return-policy rules. Legacy callers using a child category ID continue to work. The full native catalog includes broad intermediate categories as well as specific leaves; choose the most specific applicable category.

Product **3039**, Google Chromecast Streaming Device, was saved through native ListLab with Tech and **Video > Video Players & Recorders > Streaming & Home Media Players**. Reopening confirmed persistence. Native Merchant Sync reports Google category **5276**, automatic API status **successful**, and last sync **2026-10-11T00:28:30+00:00** (October 10, 5:28:30 PM PDT), with no error. Google's processing and eligibility fields remain unreported; this is successful submission, not proof of approval.

Adding hundreds of categories exposed a large-form risk in Marketplace Imports pricing. Version 1.0.4 disables numeric inputs for unchecked overrides, enables them when the checkbox is checked, and rejects a form missing its final completeness marker without changing saved settings. Parent pricing inheritance is unchanged. Deployment itself does not change pricing settings.

## Validation

- 30 assertions against actual WooCommerce and WordPress in the fenced local sandbox cover taxonomy creation, idempotence, native persistence, malformed/mismatched selections, schema inheritance, return policy inheritance, Like New -> Used condition, Google fallback and exact mapping, legacy API compatibility, and clearing a child selection.
- 17 JavaScript category selector assertions cover state changes, field visibility and failed schema rollback.
- 27 DOM assertions using jsdom exercise search filtering, keyboard selection, clearing, invalid query protection, focus-out/Escape restoration, disabled controls, dropdown browsing and custom model entry.
- Category label decoding preserves native IDs and safely decodes WordPress entities before frontend escaping.
- Existing marketplace import (92), listing visibility (40), video validation (48), condition policy (15), category mapping and GTIN suites passed for the category implementation. Later search and large-pricing-form changes received focused tests; the full sandbox suites were not rerun for those UI-only changes.
- Large-pricing-form tests render 440 categories, prevent an incomplete POST from saving, and preserve enabled overrides from a complete POST.
- Live browser checks confirmed filtering in both fields, keyboard category selection, persisted Chromecast category, native upload success for each plugin, and automatic submission.

The isolated test MySQL instance was verified and shut down after the native suites. No production DB direct access was used for deployment or edits.

## Source, packages and rollback

Source snapshots are under `wordpress-overlays/` for Product Attributes, ListLab and Merchant Sync. Third-party vendor code is omitted from Git, including Merchant Sync dependencies and ListLab ZXing; apply snapshots over the existing installations. Source hashes use LF-normalized bytes; release manifests verify original deployment bytes. Marketplace Imports remains under `wordpress/kniferevive-listlab-import`. The owner release directory contains complete runtime ZIPs, exact source verification, SHA-256 manifests, test output and reconstructed rollback packages:

`C:\Users\Svet\Documents\KnifeRevive_Documentation\releases\tech-categories-2026-10-10`

Final runtime packages: Product Attributes 0.3.4; ListLab 1.8.2; Merchant Sync 0.8.6; Marketplace Imports 1.0.4. Intermediate ListLab 1.8.0/1.8.1 ZIPs remain for audit history.

The Product Attributes rollback 0.3.3 ZIP is reconstructed from the **local prechange baseline**. The live upload comparison showed **0.3.2** before upgrading to 0.3.4. It is not a production backup of 0.3.2. Likewise the other reconstructed rollback ZIPs are explicitly identified as local baselines, not downloaded production backups. Rolling back runtime does not remove imported terms or recategorize existing products; preserve native term IDs and assignments.

DOM tests require jsdom, either installed normally or supplied with `KREV_JSDOM_PATH`. Native tests use the existing fenced sandbox bootstrap. Focused pricing test: `php tests/import-pricing-large-catalog.php`.

Browser proof is saved under the owner's `diagnostics/`: `listlab-subcategory-search-2026-10-10.png`, `listlab-model-search-2026-10-10.png`, and `3039-streaming-merchant-sync-2026-10-10.png`.
