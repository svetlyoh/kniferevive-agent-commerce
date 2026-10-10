# Google Merchant feed preparation

Use this checklist when preparing a referenced Facebook listing. The merchant
sync service generates the Google offer ID, KnifeRevive landing-page/image URLs,
final item price/currency and stock availability from the native product. Muse
prepares source facts, not a direct Google upload or approval.

Collect as much of the following as the listing actually provides:

- Actual title and item description; keep short description equal to long.
  Prefer the original factual wording, without seller contacts or promotion.
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
  the owner's rule: **Gently Used** for knives, **Used** for all other categories.
  State that it was defaulted, in both descriptions and the private handoff.
  Map it to the real category condition option; both defaults become Google
  `used`. Do not send a descriptive grade as a Google condition enum, infer
  sharpening, or create refurbishment/warranty evidence.
- Category-specific facts: knife/blade type, edge style and blade length/unit;
  other relevant specifications, materials, color, size or variant facts when
  supplied. Use the current native schema, not guessed attribute names.
- Explicit item quantity and packaged weight/dimensions, when stated. Otherwise
  omit quantity; never assert stock availability from a source card. For missing
  package fields, the owner authorizes 15 oz and 1 × 6 × 4 inches (length × width
  × height), as merchant defaults rather than measurements. Marketplace Imports
  1.0.2 fills blank fields independently, preserving supplied values; read
  `/schema.package_defaults` for the configured store units and converted values.

Use only fields advertised by `/schema`. Map brand/condition/specifications to
native `attributes`. If an MPN or other extracted fact has no supported import
field, preserve it in the description and flag the missing structured merchant
field for completion; do not invent a top-level `mpn` or private metadata key.
Source facts do not establish inventory ownership or photo rights.

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
