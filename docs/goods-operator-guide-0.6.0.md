# Goods discovery and native checkout operator guide

Source candidate: adapter **0.6.0**, portable skill **0.6.0**, OpenAPI **1.8.0**.
Prepared October 10, 2026, Pacific. Consult the implementation/release report for
actual deployed/published versions. Installing a skill does not enable checkout.

## Seller product data

- SKU: native WooCommerce product editor → Product data → Inventory → SKU.
- Model: existing global **Model** (`pa_model-number`) attribute. ListLab exposes
  it in knife Specifications. Native product editor → Product data → Attributes
  can assign the same Model attribute to technology and other goods; enable
  **Visible on the product page**. Do not copy SKU into Model. Current ListLab
  schemas omit Model from some other category editors; no extra field is inferred
  or added by this adapter. Seller access to the native attribute editor depends
  on the site's existing seller permissions. Missing structured models remain
  null; a title/description-only model can match keyword search.
- Barcode/GTIN: ListLab → Specifications → Barcode / GTIN, where currently
  offered, or native Inventory → Global Unique ID. Preserve leading zeros. The
  adapter accepts only valid GTIN-8/12/13/14 check digits. Native uniqueness rules
  remain owned by WooCommerce.
  When native Global Unique ID is empty, audited Merchant Sync legacy GTIN fields
  are fallback sources; invalid identifiers remain null with validation evidence.
- Manufacturer: existing `product_brand` assignment. The deepest assigned series
  resolves to its top-level manufacturer, following Merchant Sync semantics.
  Generic store/technology placeholders are omitted; assignment is a seller claim.
- MPN: current Merchant Sync allowlist, in priority order `_wc_gla_mpn`,
  `_google_mpn`, `_mpn`, `mpn`. Use the existing integration's product editor;
  this adapter adds no independent MPN editor or writes to these fields.
- Public specifications/images/weight/dimensions: native product fields and visible
  attributes. Arbitrary metadata and non-exported private attributes are excluded.

Product search also reads **KnifeRevive Product Attributes**' existing Merchant
Sync detail allowlist: knife specifications, CPU/RAM/storage/GPU/system, artwork,
coin and spice details. These explicit public Google-export values can be searched
even when absent from the title. The skill shows all matching items with real
thumbnail images and links, following pagination or disclosed chat batches. This
public source projection neither reads Google credentials nor certifies approval
or public Google Shopping rank. Non-allowlisted private fields remain excluded.

Draft, private, hidden and ListLab-archived products are excluded. Catalog-only
and search-only listings remain public in the agent directory. Native global
hide-out-of-stock is honored. Goods exclude configured sharpening service IDs
and the `knife-sharpening` taxonomy/descendants even under mixed assignments;
physical knives remain included. Complex products can be discovered but retain
explicit checkout limitations. Seller disablement is never overridden.

## Native fulfillment controls

Use **WooCommerce → Settings → Shipping → Shipping zones/methods/classes** and
the installed seller shipping controls. These remain the source of rates,
destination eligibility, fees and package splitting. Configure a descriptive
pickup method title/location; modern native pickup metadata is displayed when
available. Classic pickup without structured location data requires buyer/seller
location confirmation. Delivery is offered only as an available native rate.
Virtual goods have no delivery-address form or shipping-method selection.

Read-only live audit on October 10: US selling/shipping, taxes/coupons enabled,
block checkout, no parcel shipping zones, Dokan shipping disabled, and one free
enabled block pickup location at 304 Kapalua Bay Circle, Pittsburg, CA 94565.
Pickup is titled "Pick Up / Drop Off". Do not promise delivery from this audit.
Manage pickup under **Shipping → Local pickup**; block-only settings are distinct
from classic shipping-zone Local pickup. The adapter uses native availability and
location metadata; it does not create a shipping method or switch checkout type.

Goods add no sharpening transport fees or booking county/capacity rules. Legitimate
native marketplace fees and taxes remain. Shipping is unknown until destination,
gateway and all package choices are supplied. A native zero rate is shown as zero;
unknown is null. Package order, method/instance, charge, tax, destination, coupons,
items and quantities are bound to buyer review and revalidated at order creation.
Native payment gateways, stock, Dokan accounting and refund hooks retain ownership.

## Agent checkout controls and launch proof

**WooCommerce → Agent Commerce** manages `listing_handoff_enabled`,
`listing_pricing_verified`, `listing_live_verified`, `listing_gateway_ids`,
`listing_max_minor`, `listing_policy_url`, `listing_policy_version` and native
gateway evidence. No flag is enabled by this source change. USD/two-decimal limits
remain as in the prior adapter. A listed gateway is not proof of live settlement.
See [the existing launch runbook](listing-checkout-runbook-0.2.0.md) for ordinary
versus agent checkout, staging payment/transfer/refund comparison and gradual launch.

Live launch blockers: goods handoff, pricing verification and live verification
are false; only `stripe` is allowed, maximum is $1000, listing policy URL/version
are empty. Native Stripe is selected live and still needs goods operator review.
Do not flip verification flags based on synthetic test results. These settings
were inspected read-only and retained.

Read `/capabilities`: goods handoff is separate from prepaid sharpening. When it
is unavailable the skill opens the original product page; it must not claim a
prefilled review. It calls only deployed/advertised discovery contracts.

Upgrade builds an exact-identity hash index, 100 products per bounded batch after
native taxonomies are initialized and through the existing maintenance job.
`identifier_search_ready=false` and `DISCOVERY_INDEX_BUILDING` prevent false
no-match results until complete. Native product CRUD, allowlisted metadata and
brand/model term updates refresh or invalidate the index. Do not edit SQL behind
native CRUD. Keep WordPress scheduled maintenance running on larger catalogs.

## Recovery and rollback

Existing browser carts and pending orders block goods handoff; they are preserved.
The buyer can finish/clear their own cart or use the normal product page. No
automatic cart replacement was introduced.

The private review's **Continue payment for this order** keeps the original order.
Goods recovery verifies the scoped intent, original items/quantity, amount,
currency, gateway, destination, coupons, shipping and unexpired review. Native
Stripe recovery can reuse an original intent awaiting buyer action; processing,
succeeded, mismatched and unreadable intents block retry. Other gateways and
expired reviews require merchant reconciliation of that original order. Native
key/ownership/email/nonces remain required. The booking-only Cash App subtype
exception is not widened to goods or arbitrary `stripe_*` IDs.

Disable `listing_handoff_enabled` first if a launch problem occurs. Preserve
adapter intent/order guards, native gateways/webhooks, seller/refund ledgers and
the independent sharpening flow while existing orders reconcile. Retain the
identity index; it contains public identity hashes, not customer data. After
reconciliation restore the preceding tested plugin/code/settings package; do not
restore a stale database or delete order/idempotency/payment evidence. Old reviews
may require refresh when the new schema changes their hash. Never create another
order to bypass uncertain payment.

## Verification boundary

Fenced synthetic native tests cover implementation behavior. Actual native
processor payment, seller transfer/refund arrival, production shipping/plugin
parity, and Muse HTTP/cards/selection/browser opening require separate proof.
This task requests no live charge, production launch-setting change, seller
transfer or refund. Admin inspection is complete for the controls recorded above;
native processor settlement, seller payout/refund arrival and Muse rendering still
need separate verification.
