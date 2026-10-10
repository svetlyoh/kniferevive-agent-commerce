# All-category KnifeRevive Concierge implementation and release record

October 10, 2026, America/Los_Angeles. Adapter/skill source **0.6.0**; generated
OpenAPI **1.8.0**, additive listing payload schema **1.2**. Versions were selected
after inspecting repository tags, GitHub releases and registry latest (0.5.14).

## Outcome and release state

Implemented dynamic goods category browsing, exact product identity search,
selectable host presentation guidance, preserved product/quantity review,
site-native shipping and protected original-order recovery. Physical knives are
included; sharpening services use the dedicated unchanged booking branch.

This record distinguishes source implementation/testing, deployment, publication,
registry verification and live payment/host proof. Final observations and package
hashes are recorded in the companion JSON evidence and release manifest. Until
deployment and listing launch readiness are verified, bots must use canonical
product pages for goods. Do not label the production payment pipeline complete.

## Baseline and concurrency

The handoff's repository baseline was clean at `88866d2d4824608c546029dc5076e6e6d8aa74d9`.
During the task, another owner-directed chat implemented confirmed booking status
on native seller orders and committed `da156a9`. Goods work used an isolated Git
checkout and was rebased onto that repair, preserving it. The separate test MySQL
instance uses loopback port **11029**, database **krev_agent_sandbox**, prefix
**krev_sandbox_**, and its own ignored data directory. No Local Sites/production
wp-config.php, customer database or unowned server was used or stopped.

Anonymous baseline `/listings?per_page=100` returned 30 published listings in art,
bread/carving/chef/paring/santoku/steak/utility knives, technology, world-coins,
world-spices and sharpening. New category route returned 404 before this release.
A cached capabilities read reported 0.5.13; a later unique read reported 0.5.17,
booking enabled and goods handoff unavailable, configured gateway ID stripe.
This discrepancy is consistent with caching, but the exact caching rule was not
established. The fresh public fact is separate disabled goods handoff. Public
configuration's `stripe_environment=test` does not certify the native gateway.

## Contracts and source mapping

- `GET /listing-categories` defaults to goods and returns native taxonomy IDs,
  slugs, labels, parent, canonical URL, direct visible goods counts and filters.
  New/populated categories appear without a hard-coded category list. Counts
  include unsupported checkout types; they do not imply stock/fulfillment approval.
- Additive `/listings` and detail `scope=goods` exclude configured service IDs and
  sharpening descendants, including mixed assignments. Unscoped legacy clients
  retain their service access. Native visibility and ListLab archives are honored.
- Public identity fields are SKU, stored public Model, manufacturer, MPN, valid
  GTIN, public specifications/images, category detail and weight/dimensions. Empty
  values remain null/empty. SKU uses CRUD; barcode uses native Global Unique ID;
  Model uses existing visible `pa_model-number`; brand hierarchy and MPN priority
  match the audited installed Merchant Sync code. Production plugin-editor parity
  remains unverified because admin browser permissions were unavailable.
- Case/whitespace-normalized exact filters use a hash index with composite exact
  lookup, preserve punctuation/leading zeros, and combine with AND. Search merges
  exact identity and native title/description keyword predicates; SQL pagination
  and counts follow visibility/scope/filtering without loading the full catalog.
  `matched_fields`/`match_type` distinguish exact and keyword results. Index rebuild
  is bounded and gates search until complete. GTIN formatting/check-digit validity
  is explicitly separate from authenticity and seller claims.
- Goods intents accept `scope=goods`, retain server-validated product IDs and
  quantities, and reject sharpening. Native quote uses destination-specific
  shipping methods/packages/classes, discounts, tax and fees. Added method/instance
  and native pickup metadata preserve fulfillment identity. Order binding rejects
  same-price changed methods/coupons too. Quotes reserve no stock.
- First-party review does not auto-select a missing shipping choice or request a
  delivery address for virtual goods. Ordinary carts remain protected. Scoped
  original goods-order recovery uses native order-pay, with original Stripe intent
  uncertainty checks. No marketplace Stripe sessions/transfers or gateway wildcard
  were added. Refund records, gateway acceptance and arrival remain separate.
- Skill/API/AI/OpenAPI and installation/demo guidance were updated together.
  Host-native cards/buttons have a numbered fallback. An actual browser-open tool
  opens the returned private review after selection; unsupported opening is
  disclosed. Disabled handoff opens/provides the canonical product page. Repo tests
  cannot certify Muse's renderer or its installed skill.

## Validation and remaining launch proof

Tests run through the existing fenced WordPress/WooCommerce stack with synthetic
gateway/processor responses and blocked outbound HTTP/email. The four storage
modes are classic and HPOS, each with synchronization on/off. The extended matrix
includes native listing, discovery, fulfillment, booking, bridge/lifecycle,
operator-service and gateway setup checks. Five sharpening options and storefront
regressions run in classic/HPOS with synchronization. Fixture shipping/tax/session
tables are explicitly reset between suites so earlier native zones cannot affect
later expected rates. Details/counts are captured after execution, not inferred
from historical reports. HTTP/browser review and native payment-handoff fixtures,
schema/source parity, PHP lint and skill validation have separate evidence.

Native seller-side tests load a captured Seller Orders candidate, documented in
the evidence. That local stack differs from production versions; simulated
payments/transfers/refunds are not real processor, seller payout or refund proof.

Production `listing_handoff_enabled`, pricing/live evidence, ceiling and policy
settings could not be inspected from authorized admin UI: Browser Use refused
because saved site permissions could not be verified. No security workaround was
attempted. Exact private setting blockers therefore remain unverified; public
goods handoff is unavailable. Required operator review is described in
[the guide](goods-operator-guide-0.6.0.md) and existing native launch runbook.

No real charge, production order, email, transfer/refund, gateway-mode switch or
launch-setting change was requested or performed for these goods checks. Actual
native test-account payment/webhook, seller accounting/transfer/refund, production
plugin/tax/shipping parity and actual Muse JSON/cards/selection/browser opening
remain release/launch proof, not assertions of completion.

## Packages, release and rollback

`tools/package.py` produces explicit plugin and portable skill archives with
archive and per-file SHA-256/byte manifests. Final hashes, commits and publication
status are recorded separately after packaging; any changed bundled file requires
new archives. Registry-generated Skill Cards are never authored as substitutes.
Exact-version ClawHub findings must be checked when that version is published.

See [operator recovery/rollback](goods-operator-guide-0.6.0.md). Preserve original
orders and financial ledgers; disable goods launch before restoring a preceding
code package after reconciliation. Preserve the latest booking/seller fixes when
selecting the preceding package. No production database rollback is appropriate.
