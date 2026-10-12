# Google Merchant feed preparation

Use this checklist when preparing a referenced Facebook listing. The merchant
sync service generates the Google offer ID, KnifeRevive landing-page/image URLs,
final item price/currency and stock availability from the native product. Muse
prepares source facts, not a direct Google upload or approval.

Collect as much of the following as the listing actually provides:

- Actual title and item description; keep short description equal to long.
  Rewrite supported facts in the KnifeRevive lister's seller voice using
  [description writing](marketplace-import.md#description-writing); exclude
  Facebook sourcing and all references to the original seller from both fields.
  Feed limits are 150 characters for title and 5,000 for description; flag excess
  source text for review instead of silently claiming it is feed-ready.
- Every original product photo, with a usable main photo and remaining views
  in the gallery. Follow the complete-gallery extraction and limit reporting
  in [the listing workflow](marketplace-import.md); prefer full images over
  thumbnails. Do not invent photos or remove ownership watermarks.
- Explicit USD source price. The merchant computes the KnifeRevive price and
  native shipping; do not copy Facebook delivery offers into store shipping.
- Maker/brand, model and any explicitly stated manufacturer part number (MPN).
  Keep SKU, model and MPN distinct. Do not invent an MPN from a model number.
- A stated or clearly legible barcode/GTIN, preserving leading zeros. Use
  `global_unique_id` when the schema supports it. An unknown barcode does not
  mean the manufacturer never assigned one; do not set identifier-exists false
  merely because Facebook omitted identifiers.
- Structured condition, matching the source when present. When absent, apply
  the owner's rule: **Gently Used** for knives, **Used** for other goods only when
  allowed by the selected subcategory/collection/category condition field. Otherwise
  leave it blank for seller completion; never assume New for consumables.
  State that it was defaulted in both descriptions without naming Facebook or
  the original seller; explain the missing source condition in the private handoff.
  Map it to the real category condition option; both defaults become Google
  `used`. Do not send a descriptive grade as a Google condition enum, infer
  sharpening, or create refurbishment/warranty evidence.
- Category-specific facts: knife/blade type, edge style and blade length/unit;
  other relevant specifications, materials, color, size or variant facts when
  supplied. Use the current native schema, not guessed attribute names.
- Explicit item quantity and packaged weight/dimensions, when stated. When quantity
  is absent, use the owner's default of one for a new listing and label it as a
  seller default; never assert verified stock availability from a source card.
  Preserve explicit zero and existing inventory on photo-only repairs. For missing
  package fields, the owner authorizes 15 oz and 1 × 6 × 4 inches (length × width
  × height), as merchant defaults rather than measurements. Marketplace Imports
  1.0.2 fills blank fields independently, preserving supplied values; read
  `/schema.package_defaults` for the configured store units and converted values.

Use only fields advertised by `/schema`. Map brand/condition/specifications to
native `attributes`. If an MPN or other extracted fact has no supported import
field, preserve it in the description and flag the missing structured merchant
field for completion; do not invent a top-level `mpn` or private metadata key.
Source facts do not establish inventory ownership or photo rights.

Tech, Home & Garden, and Collectibles expose six predefined specification fields
per Google subcategory under `specification_profile.fields`. Fetch `/schema` with
the selected native Google child as `category_id`; a parent schema is only a
general fallback. Marketplace Imports 1.0.6 advertises `subcategory_id` and
`metacategory_id` separately. Use the advertised IDs, not labels or guessed IDs.
Retain the Google child when assigning AI-Tech or Pokémon Cards. Pokémon's native
collection rolls up confirmed Pokémon trading cards while the feed uses Google
6997. Trading-card facts are game/franchise, set/expansion, card name, card number,
rarity/finish, and grade; do not assert grading or authentication without evidence.

Fill each advertised field only from the listing or a readable product label.
Keep unsupported facts in the description and mention missing specifications at
handoff. `pa_model-code` holds a stated product/model code, such as an Apple model
identifier; `pa_mpn` holds a genuine manufacturer-issued part number. Do not turn
a model code into an MPN. An IMEI or device serial is neither an MPN nor a GTIN.
Use existing native term IDs or the import's
supported custom-value prefix for numeric text rather than mistaking a code for a
term ID. Seller SKU remains the separate `sku` field and cannot substitute for a
manufacturer identifier. The six fields supplement core feed title, description,
price, image, availability, brand, condition and shipping; they do not establish
Google approval by themselves.

The seller reviews quantity, selling/photo rights, shipping and returns in
ListLab, then publishes. Native Merchant Sync automatically queues eligible
published saves when its verified sync gate is enabled. Draft preparation is
not publication, successful upload is not Google approval, and unknown status
must not be described as ready. Surface missing fields instead of asking for a
manual refresh as the default remedy.

On October 10, 2026, product 3033's text said New but its structured condition
was empty. Filling the native condition and saving in ListLab triggered a
successful automatic Merchant API upload. This is why Muse must set the actual
condition attribute, not only write “Condition” in the description.

Official references: [Google product data specification](https://support.google.com/merchants/answer/7052112)
and [Merchant API processed product versus input](https://developers.google.com/merchant/api/guides/quickstart/insert-first-product).
