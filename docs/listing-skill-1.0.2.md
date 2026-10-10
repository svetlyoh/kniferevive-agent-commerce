# KnifeRevive Listing 1.0.2

The separate seller skill now opens the full Facebook Marketplace gallery and
collects every distinct item photo in source order, including images loaded as
the gallery advances. It prefers originals over card thumbnails and reports
found/submitted counts, inaccessible photos and import-limit overflow. The
current Marketplace Imports 1.0.1 companion accepts 10 photos total: the first
becomes the main image and the others become gallery images after seller claim.
For larger galleries the skill retains the remaining sources privately and
identifies exactly which photos need completion; it does not silently drop them
or create additional drafts. This instruction release does not raise that limit.

Muse also collects supported Google feed facts: title, matching long/short
descriptions, actual price, full photos, brand, model, stated MPN/GTIN and relevant
category attributes. Identifiers and measurements are never invented. Unsupported
structured fields are preserved as facts in the description and flagged for
native completion. When Facebook omits condition, the owner's defaults are
Gently Used for knives and Used for other categories, labeled as defaults and
populated in the actual native condition attribute. Explicit conditions prevail.

Validation: the skill validator passed, Markdown whitespace checks passed, and
packaging verifies every relative reference and committed source file. This is
an instruction update; actual Muse installation, gallery traversal and photo
copying have not been verified in the user's Muse runtime.

## Live Merchant Sync observations, October 10, 2026

Product 3033 (Cuisinart Electric Knife) explicitly described itself as New but
had no structured condition. Its native ListLab condition was set to New and
saved. The existing Merchant Sync 0.8.5 queue automatically uploaded offer
`kr-wc-3033` at `2026-10-10T19:08:22+00:00`; no manual sync/reconciliation was run.
The subsequent processed-product check confirmed Google category 665 and
processing complete, but Google eligibility was **disapproved**. Successful
submission therefore does not mean this listing can appear on Google.

The published-catalog snapshot examined 31 products, excluding two sharpening
services: 29 Merchant candidates, 25 successful upload records and four errors.
The four blocked coin listings were 2681 (Bulgaria 20 Stotinki 1999), 2601
(1977 US Kennedy Half Dollar), 2520 (Bulgaria 2 Lev 2015), and 2519 (Bulgaria
1 Lev 2022). Product 2681 has unresolved condition facts; the others have missing
actual condition. Those historical listings were not blanket rewritten with a
Facebook import default. The dashboard also contains errors for unpublished
products and warns about a possible competing primary source; no source was
deleted or disabled. The catalog can change after this snapshot.

The native admin box does not display Google's item-level disapproval reasons.
Merchant Center browser access reached Google's sign-in page; sign-in is needed
to inspect those diagnostics. The other successful-upload records are not a
claim of Google approval. A copy of the per-product audit and a screenshot of
3033's upload/eligibility are retained in the owner's release evidence folder.

Native Merchant Sync already queues published saves and stock/status changes,
with daily reconciliation. Manual refresh is not the normal fix for missing
condition or Google disapproval. See [Google's product data requirements](https://support.google.com/merchants/answer/7052112)
and [input versus processed product](https://developers.google.com/merchant/api/guides/quickstart/insert-first-product).

## Muse update

Update the existing standalone `kniferevive-listing` folder in place from:
https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.2/skills/kniferevive-listing

Import all references, keep one active copy in the host's actual custom skill
catalog, and verify 1.0.2 in a fresh chat. Concierge and the live companion
plugin are unchanged. See [the installation prompt](../skills/kniferevive-listing/references/installation.md).
