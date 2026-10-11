# Searchable Google subcategories — October 11, 2026

Deployed in place on kniferevive.com: Product Attributes 0.3.5, ListLab 1.8.3,
and Merchant Sync 0.8.7. Native WordPress update pages confirmed all three
successful replacements. The existing Marketplace Imports companion and separate
Listing skill remain 1.0.5.

## Live category catalog

| KnifeRevive category | Native parent ID | Searchable choices | Google mapping |
| --- | ---: | ---: | --- |
| Tech | 1892 | 421 | Existing 417 Electronics choices plus four merchant aliases |
| World Coins | 1894 | 3 | Collectible Coins & Currency 217; Collectible Banknotes 543607; Collectible Coins 543606 |
| Art | 1889 | 4 | Artwork 9; Decorative Tapestries 500045; Posters, Prints, & Visual Artwork 500044; Sculptures & Statues 11 |
| Home & Garden | 4277 | 1,034 | Every descendant of Google's Home & Garden branch 536 |

Tech's iPhones and Mobile Devices / Smartphones choices map to Mobile Phones
267. Smart Watches and Apple Watches map to Watches 201, whose official path is
Apparel & Accessories > Jewelry > Watches. These four familiar seller labels are
merchant aliases, not additional official Google taxonomy categories.

The official source is [Google's taxonomy with IDs](https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt),
retrieved October 11, 2026. Its header is
`Google_Product_Taxonomy_Version: 2021-09-21`. The bundled snapshot is
`wordpress-overlays/kniferevive-product-attributes/data/google-category-groups-2026-10-11.json`.
Home & Garden uses relative full paths to distinguish similarly named descendants.
Art uses Google's Artwork branch and World Coins uses its Collectible Coins &
Currency branch, including each broad choice and all available descendants.

## Seller behavior and feed mapping

Select one of the four categories in ListLab. A second selector allows typing to
filter, clicking the dropdown arrow, and keyboard selection with arrows and Enter.
Changing the parent clears an incompatible prior child. Child choices are kept
out of the main category selector. The existing searchable model selector is
preserved. Native saves retain the chosen parent and child; child-only legacy
imports reopen with the correct parent. Cross-group selections are rejected
before product mutation.

Every imported child carries its exact Google ID and official path. Merchant
Sync automatically uses those mappings, including watch aliases outside the
Electronics branch. Home & Garden also has broad fallback 536. Existing product
assignments were preserved; this deployment does not bulk recategorize listings.
Category mapping alone does not establish Merchant Center approval.

The public Marketplace Imports schema exposes all 1,462 mapped children with
`is_subcategory`, `subcategory_parent_id`, and `google_category`. Muse's existing
Listing skill can obtain the choices from that live schema; this category change
does not require reinstalling the skill.

## Deployment and verification

The native WooCommerce > Google Subcategories importer reported completion for
421/421 Tech, 3/3 World Coins, 4/4 Art, and 1034/1034 Home & Garden. Its bounded,
idempotent batches reuse reserved native slugs and preserve term IDs. The previous
Tech progress was reused to import only the four additional aliases. Home & Garden
was created as a new root. All live mapped-child counts were independently checked
through the public schema.

Live browser checks verified iPhone and watch filtering, World Coins and Art
dropdown contents and filtering, all 1,034 Home & Garden choices, and Home & Garden
keyboard selection. The verification form was not submitted; no product was
published or saved for this check.

Validation passed: 54 native taxonomy/save/feed assertions, 32 JavaScript category
assertions, 27 jsdom searchable-selector assertions, and 108 existing Marketplace
Imports assertions (221 total), PHP syntax, JavaScript syntax, package checks,
and `git diff --check`. Tests used a fenced sandbox database on port 11029; that
temporary server was shut down afterward.

Full runtime release ZIPs, previous-version rollback ZIPs, hashes, screenshots,
taxonomy download, public schema, and native test output are retained under the
owner's `KnifeRevive_Documentation` folder. The package hashes are recorded in
[the release manifest](google-subcategories-release-manifest-2026-10-11.json).
Rollback ZIPs restore code versions; they do not remove imported category terms.
