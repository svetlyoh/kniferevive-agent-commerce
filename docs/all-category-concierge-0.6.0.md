# All-category KnifeRevive Concierge implementation and release record

October 10, 2026, America/Los_Angeles. Adapter/skill source **0.6.0**; generated
OpenAPI **1.8.0**, additive listing payload schema **1.2**. Versions were selected
after inspecting repository tags, GitHub releases and registry latest (0.5.14).

## Outcome and release state

Implemented dynamic goods category browsing, exact product identity search,
selectable host presentation guidance, preserved product/quantity review,
site-native shipping and protected original-order recovery. Physical knives are
included; sharpening services use the dedicated unchanged booking branch.

Plugin **0.6.0 is deployed and active**. Portable skill **0.6.0 is published** on
[GitHub](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/skill-v0.6.0)
and [ClawHub](https://clawhub.ai/svetlyoh/kniferevive-concierge), where it is latest.
Live search is ready; goods checkout remains disabled by the retained launch
gates, so bots use canonical product pages. Actual Muse rendering and real
payment/seller/refund settlement remain unverified.

Tested source commit: `75092826ccf05aabf1306607407ff8350ed9a221`. Immutable
`plugin-v0.6.0` / `skill-v0.6.0` tags point to manifest commit
`5510486faa2d852e9d46418df95a445f7012c769`; bundled source is identical.
See [exact release evidence](concierge-release-evidence-0.6.0.json) and
[archive/per-file hashes](concierge-release-manifest-0.6.0.json).

## Baseline and concurrency

The handoff's repository baseline was clean at `88866d2d4824608c546029dc5076e6e6d8aa74d9`.
During the task, another owner-directed chat implemented confirmed booking status
on native seller orders and committed `da156a9`. Goods work used an isolated Git
checkout and was rebased onto that repair and `a40263095a79bd43bd30ee3d356b038f2ff3f5f2`
(Agent Commerce 0.5.18 / Seller Orders 1.1.8 native seller order links), preserving
both repairs. The separate test MySQL
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
  match the audited Merchant Sync sources. Read-only production plugin-editor
  inspection confirms native GTIN, exact MPN priority, canonical title and the
  installed Google product-details allowlist. This is source-mapping evidence,
  separate from Google ingestion/approval.
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

The owner's additional instruction is included in the skill: relevant product
searches show every matching KnifeRevive item directly in Muse/chatbots with its
actual thumbnail, current price and canonical product link. Cards/buttons use
available host primitives; numbered inline images/links are the fallback. Follow
all result pages and disclose totals/remaining matches when host limits require
batches. The merchant's native Google-feed detail mapping supplies searchable
CPU/RAM/GPU, knife, art, coin and spice values. Source attributes are normalized
without calling Merchant Sync's mutating mapper or exposing its credentials.
Google publication/approval/rank remains explicitly `not_checked`. This is not
an assertion that public Google Shopping returned those products. Identity index
schema 2 adds public feed value text with hashed exact lookups, native term/category
invalidation and bounded rebuild. Arbitrary private attributes/meta stay excluded.

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

The full classic/HPOS matrix (synchronization on/off) passed. Final focused checks
passed 60 discovery, 25 fulfillment and 75 listing assertions; browser review
preserved quantity 2 into native checkout, and HTTP recovery reached the same
original booking order. Contract schemas/source parity, skill validation and all
57 PHP files passed. The matrix preceded final focused source fixes; the evidence
records each run without claiming every late change received another full matrix.

Native seller-side tests load captured Seller Orders 1.1.8, also observed active
in production. Other local stack versions/configuration can differ; simulated
payments/transfers/refunds are not real processor, seller payout or refund proof.

Admin browser access became available after the session update; the earlier
permission blocker was not bypassed. Read-only live settings show
`listing_handoff_enabled=false`, `listing_pricing_verified=false`,
`listing_live_verified=false`, gateways `[stripe]`, maximum 100000 cents ($1000),
and empty listing policy URL/version. Native Stripe reports selected live mode
and needs operator review; no goods payment evidence is certified. Goods handoff
therefore remains unavailable independently of enabled sharpening.

Native WooCommerce settings sell/ship to the United States, use USD/two decimals,
and enable taxes/coupons. No explicit shipping zones exist; Rest of world has no
methods. Dokan per-product/country shipping is disabled. Native block pickup is
enabled, titled "Pick Up / Drop Off", with no added charge and one enabled pickup
location at 304 Kapalua Bay Circle, Pittsburg, CA 94565. Checkout uses blocks.
Only actual native returned rates can be offered; this configuration does not
establish parcel/local-delivery availability. Synthetic tests additionally cover
this real block-pickup configuration and location metadata, without changing live
settings. Required operator review is described in
[the guide](goods-operator-guide-0.6.0.md) and existing native launch runbook.

No real charge, production order, email, transfer/refund, gateway-mode switch or
launch-setting change was requested or performed for these goods checks. Actual
native test-account payment/webhook, seller accounting/transfer/refund, production
plugin/tax/shipping parity and actual Muse JSON/cards/selection/browser opening
remain release/launch proof, not assertions of completion.

The production WordPress ZIP update visibly completed successfully from 0.5.18
to 0.6.0; the installed row shows active 0.6.0. Read-only post-upgrade settings
confirmed the same goods gates, gateway IDs, maximum and empty policies, with
booking enabled/approved. Seller Orders stayed at 1.1.8. Anonymous capabilities
advertise goods/feed search and a completed index. Discovery returns **28 goods
in 11 categories**. Searching `Intel Integrated Graphics` returns Dell product
2417 with `matched_fields=[google_feed_attributes]`, thumbnail and canonical URL.
Exact model `4562/20` returns product 538. The `knife` search returns **16 distinct
results across four pages** at five per page; pagination and counts matched.
Post-upgrade sharpening discovery retains all five options and 0/600/600/1100/0
cent trip fees. No buyer intent/order was created by these public reads.

## Packages, release and rollback

`tools/package.py` produces explicit plugin and portable skill archives with
archive and per-file SHA-256/byte manifests. Final hashes, commits and publication
status are recorded separately after packaging; any changed bundled file requires
new archives. Registry-generated Skill Cards are never authored as substitutes.
Exact-version ClawHub inspection shows latest 0.6.0, clean/benign security with
high confidence and no warnings. All nine registry and isolated-download file
hashes match the release manifest. Full `skill verify --version 0.6.0` separately
returns `ok=false`, `card.missing`; the requested registry-generated card is
unavailable, server-resolved GitHub import provenance is unavailable and signature
is unsigned. VirusTotal/SkillSpector report objects are null. No card was authored
or uploaded, and these absent checks are not claimed as passing certification.

See [operator recovery/rollback](goods-operator-guide-0.6.0.md). Preserve original
orders and financial ledgers; disable goods launch before restoring a preceding
code package after reconciliation. Preserve the latest booking/seller fixes when
selecting the preceding package. No production database rollback is appropriate.
