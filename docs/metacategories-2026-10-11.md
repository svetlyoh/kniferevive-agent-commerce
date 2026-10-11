# Collections, Google subcategories and specification profiles

Follow-up: ListLab now restricts Item Condition by subcategory, falling back
to metacategory and category, and refreshes choices on every context change.
See [condition policy and deployed versions](category-condition-options-2026-10-11.md).

KnifeRevive now groups published products by browsing collection while retaining their selected Google product classifications. The category directory, shared Annex navigation and Shop Categories flyout use the same destinations. iPhones, Laptops and Mini-Computers open the corresponding Technology Trade Desk section. Home & Garden, Collectibles and Pokémon Cards have native themed landing pages using the Other Finds layout, collection inventories and seller links. Collectibles includes a dedicated Pokémon Cards section. The five theme images for iPhones, Laptops, Mini-Computers, Collectibles and Pokémon Cards are the owner-provided PNGs from the Metacategories folder, copied without modification into the shared directory/section image source. Collectibles has its own directory image and Pokémon has a separate linked thumbnail. Home & Garden retains its generated thematic photo; the exact prompt and built-in generation method are recorded in [the image notes](home-garden-image-generation-2026-10-11.md).

The Shop Categories branch sits between Models and Shop All. Kitchen Knives reuses the saved Knife Type children. Other groups expose only Google subcategories represented by catalog-visible, published listings. Empty groups remain accessible without fake product cards. Archive queries for empty groups return no products. Mini-Computers includes Chromecast's streaming classification and desktop systems; Apple-compatible watches are not misclassified as iPhones. The old Recently Added Tech carousel is replaced by three alternating image/text sections with independent inventories and carousels.

AI-Tech is an independent native Tech child. The narrow migration appends it only to the existing OpenClaw-ready Dell OptiPlex slug (live product 2417). Desktop Computers / Google 325 remains assigned; price, sale price, stock and merchant Google metadata are preserved. Metacategory selections never replace Google category IDs. Pokémon Cards is a native Collectibles child; actual cards use the official Collectible Trading Cards classification, Google 6997, rather than a fabricated Pokémon taxonomy ID.

## Six-field profiles and identifiers

The official [Google taxonomy](https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt), retrieved October 11, contains 34 children under Collectibles. All are imported as native WooCommerce children and mapped to their exact IDs. Existing imports remain intact: 421 Tech choices (including merchant aliases), 1,034 Home & Garden choices, 3 World Coins choices and 4 Art choices.

Every one of the 1,489 selectable Tech, Home & Garden and Collectibles rows has six distinct, predefined specification fields in [the profile catalog](../wordpress-overlays/kniferevive-product-attributes/data/category-specifications-2026-10-11.json). Root categories use appropriate six-field defaults. Example profiles:

| Product type | Six specifications |
| --- | --- |
| Mobile phones | Storage; display size; network technology; carrier status; battery health; color |
| Desktop/laptop computers | Processor; memory; storage; graphics; operating system; form factor |
| Trading cards | Card game/franchise; set/expansion; card name; card number; rarity/finish; card grade |

SKU, stated model/product code, genuine MPN and GTIN are separate optional identifiers. A phone model code such as A2111 is exported as a product detail, not invented as an MPN. Private IMEI/device serials are not requested. Brand and structured condition remain separate from the six specifications. Unknown values stay blank and are omitted from Google product details. Confirmed color/material/size also populate their standard Google attributes.

These are merchant specification profiles supplementing [Google's product data specification](https://support.google.com/merchants/answer/7052112?hl=en), not a claim that Google mandates six attributes for every category or that completing six fields guarantees approval. Actual identifiers, condition, images, shipping and other applicable feed requirements still matter.

ListLab's searchable Google subcategory selector fetches the selected child's profile, including when reopening an existing listing. Collection selection is separate and restricted to the native parent. The public Marketplace Imports schema advertises the same profile and native IDs. Preparation validates category/collection compatibility and uses the selected Google child for category pricing, including private review edits. Photo inventory, maximum image count, quantity default 1 and missing-parcel defaults remain unchanged.

## Bluetooth Gadgets follow-up

Bluetooth Gadgets (`bluetooth-gadgets`) is a fifth native Tech collection. Its
directory description, Annex links and Shop Categories flyout use the same
shared helpers as iPhones and Mini-Computers. A fourth alternating feature
section on `/technology-trade-desk/#bluetooth-gadgets` has the shared carousel
and an honest empty-inventory state, plus a replaceable SVG theme illustration.
Filtered Bluetooth archives inherit the canonical Tech palette automatically.

The official Google taxonomy mapping covers 17 relevant types: Bluetooth
Transmitters (5130), Audio Transmitters (5129), Audio & Video Receivers (241),
Headphones & Headsets (505771), Headphones (543626), Headsets (543627), Speakers
(249), USB Adapters (7182), Computer Interface Cards & Adapters (505299), Network
Cards & Adapters (290), Keyboards (303), Mice & Trackballs (304), Game Controllers
(301), Gaming Pads (543589), Game Remotes (543590), Watches (201), and GPS Tracking
Devices (6544). Paths are retained in the collection catalog for review.

Google type 5130 qualifies directly. Other candidate types need positive
Bluetooth evidence in the title or `pa_connectivity`, or explicit native seller
collection selection. “Wireless” by itself and negative Bluetooth wording do
not qualify. Phones and computers retain their existing collections. No feed
IDs or product attributes are rewritten by browsing rollup. ListLab and the
live Imports schema obtain the new choice from the native collection registry;
existing six-field profiles continue to follow the selected Google subtype.

Follow-up validation passed 54 native taxonomy checks, 56 collection checks
(including connectivity-field matching and wired-item exclusion), and 38
specification/landing checks: 148 native assertions. The existing canonical
Tech route/class/enqueue suite passed all 39 checks. PHP lint and complete
source/package byte verification passed. The test DB was verified as the
owned loopback sandbox on port 11029 and shut down after testing.

Live native replacement confirmed Product Attributes 0.3.7 → 0.3.8 and theme
2026.10.11.11 → 2026.10.11.12, both successful. Browser QA confirms the directory
description/artwork, top Annex link, dark Shop Categories flyout, alternating
Tech section and empty collection archive. The illustration loads, and both
the landing section and collection archive have `kr-tech-theme`; the archive
body is black. Current live inventory has no qualifying Bluetooth items, so
no subtype submenu or unrelated product is displayed. The public Tech Imports
schema returns native Bluetooth collection ID 5354 under Tech 1892 alongside
the four existing choices. Owner diagnostics retain the native test log,
public schema and `bluetooth-gadgets-tech-section.png` / menu screenshot.

## Cell Phone Accessories follow-up

Cell Phone Accessories (`cell-phone-accessories`) is the sixth native Tech
collection, with the same directory description, Annex links, Shop Categories
flyout, fifth alternating Tech section, carousel/empty state and canonical
black Tech palette. The section anchor is
`/technology-trade-desk/#cell-phone-accessories`; its SVG illustration is a
replaceable theme placeholder.

The mapping covers 29 official Google types. Sixteen qualify directly: the
complete Mobile Phone Accessories branch (cases, charms/straps, stands, camera
accessories, SIM/prepaid cards and tools, replacement parts), Mobile Phone
Batteries, Headphones & Headsets and their child types, and Headphone & Headset
Accessories/Cushions & Tips. Broader Screen Protectors, Power Adapters &
Chargers, suitable cable types, USB/audio adapters, styluses, flash memory
cards, General Purpose Batteries and Watches require phone evidence in the
title or `pa_compatible-devices`, or an explicit native seller assignment.
The exact paths, direct IDs and candidate IDs are in the collection catalog.

Overlap with Bluetooth Gadgets is intentional and independent: Bluetooth
headphones can appear in both. A laptop power supply does not become a phone
accessory from its charger category alone, and phones themselves do not roll
into this collection. The selected Google subtype, six-field specification
profile and Merchant Sync IDs remain unchanged. ListLab and Imports obtain
the added collection choice from the native registry without a skill update.

This follow-up passed 54 native taxonomy, 67 collection and 38 specification
checks (159 total), plus 39 canonical Tech route/class/enqueue checks, PHP lint
and complete runtime ZIP/source verification. The owned loopback test DB was
verified and shut down. Native live replacement confirmed Product Attributes
0.3.8 → 0.3.9 and theme 2026.10.11.12 → 2026.10.11.13, both successful.
Browser QA confirms the directory description/destination, top Annex link,
dark Shop Categories flyout, loaded illustration, fifth alternating Tech
section and black collection archive. Current inventory has no qualifying
listings, so the empty state is shown and no Google subtype menu is fabricated.
The live public Tech schema exposes all six choices, including native Cell
Phone Accessories ID 5355 under Tech 1892. Owner diagnostics retain
`phone-accessories-native-tests.txt`, `phone-accessories-live-tech-schema.json`,
and `cell-phone-accessories-tech-section.png` / menu proof.

## Muse skill

The separate seller skill is now `kniferevive-listing` **1.0.6**. It reads the live schema, extracts only supported facts for the six fields, separates browsing collections from Google subcategories and preserves complete-gallery extraction. Installation guidance remains in [references/installation.md](../skills/kniferevive-listing/references/installation.md). Update the existing identity in the host's active custom catalog, with one active copy. This release does not install or reload Muse remotely and does not publish the Listing skill on ClawHub. Concierge is unchanged.

Immutable installation folder: `https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.6/skills/kniferevive-listing`.

## Verification and deployment

Tech's canonical presentation file is `assets/css/tech-theme.css`, conditionally
loaded by `inc/tech-theme.php` for the entire Tech tree. See
[scope and future editing guidance](canonical-tech-theme-2026-10-11.md).

Final native WordPress/WooCommerce checks: 54 existing category assertions, 44 metacategory assertions and 38 specification/landing-page assertions. Marketplace import regressions: 108. Category selector behavior: 32. Searchable selector DOM checks: 27. Total: **303 assertions**, plus PHP lint and source/package verification. Native tests run only against the fenced `krev_agent_sandbox` DB, port 11029; external HTTP and mail are blocked in that harness.

Live deployment uses WordPress's native in-place plugin/theme upload replacement. Final runtime versions: Product Attributes **0.3.9**, ListLab **1.8.5**, Merchant Sync **0.8.8**, Marketplace Imports **1.0.6**, theme **2026.10.11.13**. Collectibles' bounded native import reports **34 of 34** complete. The final ListLab upload also corrects the searchable collection's accessibility label to match Collectibles or Tech. The image follow-up passed PHP lint and package verification; browser checks confirm all five supplied themes load in the corresponding directory and landing sections. Tech child headings, descriptions and links now have light colors appropriate to their dark directory card. Original image bytes are verified against the supplied files. Desktop/mobile checks preceded this image follow-up; the browser's later viewport override did not take effect, so the new image screenshots are labeled desktop rather than claiming a new mobile pass.

Owner artifacts are in `C:\Users\Svet\Documents\KnifeRevive_Documentation\releases\metacategories-2026-10-11`: complete runtime ZIPs preserving existing vendor dependencies/assets, skill ZIP, SHA-256 manifest and local rollback baselines. Earlier intermediate packages are retained for history; use the versions above and `manifest.json`. Rollback ZIPs are local prechange baselines, not downloaded production database backups. Runtime rollback does not delete imported terms or reverse native product assignments.

The full 1,489-row specification CSV, native test log, live public schemas and browser screenshots are in `C:\Users\Svet\Documents\KnifeRevive_Documentation\diagnostics\metacategories-2026-10-11`. Live QA checks the shared menu destinations, actual section products, Google-child filtering, selected ListLab profile and desktop/mobile geometry. No live test listing is published. Existing payment, feed account, security and storefront launch gates are unchanged.
