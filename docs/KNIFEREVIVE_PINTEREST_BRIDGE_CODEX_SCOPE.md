# KnifeRevive Pinterest Bridge — Codex Implementation Specification

**Status:** Specification only — not built, installed, or deployed  
**Date:** 2026-11-07  
**Website:** https://kniferevive.com  
**Plugin slug:** `kniferevive-pinterest-bridge`  
**Initial rollout:** Five-product pilot, then controlled automatic synchronization

## Objective

Build an independent, update-safe WordPress companion plugin for the official [Pinterest for WooCommerce](https://github.com/woocommerce/pinterest-for-woocommerce) plugin. Reuse existing WooCommerce/Dokan/KnifeRevive ListLab products and Seller Cam videos. Do **not** modify the official Pinterest plugin, ListLab, Seller Cam, WooCommerce, Dokan, or WordPress core.

Main functions:
1. Map KnifeRevive's existing detailed item condition values accurately to Pinterest's `new`, `used`, or `refurbished`.
2. Filter Pinterest feed eligibility by condition, category, stock, publication status, product-specific exclusions, and platform policy.
3. Include an existing Seller Cam product video through Pinterest's `video_link` feed attribute **only if the official plugin offers a safe supported extension point**.
4. Synchronize eligible listings automatically across all Dokan sellers, using the official plugin's feed/scheduling system.
5. Provide admin diagnostics, a dry run, and a five-product pilot without requiring routine per-listing admin approval.

**Crucial policy limitation:** Pinterest's merchant guidelines distinguish resale/consignment marketplaces, which may be eligible only for Ads distribution. Submitting only brand-new products does **not** necessarily make KnifeRevive eligible for free organic shopping distribution. An accepted feed does not equal approved merchant status or guaranteed impressions.

## Existing KnifeRevive architecture

- ListLab is at `/my-account/listlab/` and writes ordinary WooCommerce products using standard CRUD.
- Dokan owns seller relationships; do not assume products belong to the site administrator.
- Seller Cam already supports listing videos. Reuse its stored media and relationship to product ID rather than developing recording or editing features.
- Condition storage specifics and video URL structure are **not yet verified**. Codex must inspect actual installed code before making implementation decisions.

```text
Seller -> ListLab / WooCommerce -> WooCommerce product
                                      |
                           KnifeRevive Pinterest Bridge
                           - native condition mapping
                           - catalog eligibility
                           - optional Seller Cam video_link
                                      |
                           Pinterest for WooCommerce
                                      |
                           Pinterest catalog review
                                      |
                           Purchaser visits KnifeRevive listing
```

## Mandatory code discovery before implementation

Record installed versions and source paths for WordPress, WooCommerce, Dokan, ListLab, Seller Cam, and Pinterest for WooCommerce.

Inspect:
- All ListLab condition names, values, input fields, taxonomy/attribute/metadata locations, and validation.
- Seller Cam product-to-video storage, attachment IDs, source video MIME types, public URL accessibility, and signed/expiring links. A poster image or playback page is **not** a direct video file URL.
- Pinterest plugin hooks/filters for per-product feed condition, feed exclusion, and custom `video_link` fields.
- Whether `_wc_pinterest_condition` is honored during current feed building, and whether updating metadata invalidates/rebuilds the feed.
- Existing Merchant Center/product feed customizations for conflicts (read-only).

If stable extension hooks cannot achieve a requirement, **report the limitation and an alternative**. Do not patch the upstream plugin, silently add an incompatible feed, or claim functionality is complete without a verified path.

## Condition mapping

Keep ListLab's original seller-selected condition unchanged. Map according to actual item meaning, not keyword similarity:

| Seller condition (examples only) | Pinterest value |
|---|---|
| Factory sealed / unopened and never used | `new` |
| New open box | Exclude pending explicit policy/condition definition |
| Like new, previously used | `used` |
| Excellent / good / fair used | `used` |
| Professionally refurbished | `refurbished` only when accurate |
| Unknown / unmapped | Exclude (fail closed) |

Make the actual discovered condition-to-feed mapping editable by administrators. Never classify an item as `new` simply because its label contains "new". Trigger an official feed refresh when mapping changes.

## Eligibility and defaults

Admin-only screen: **Settings → KnifeRevive Pinterest Bridge**.

- Plugin disabled until configured.
- Initial allowlist: exactly five manually designated pilot product IDs.
- Include genuinely `new`: enabled once validated.
- Include `used`: disabled.
- Include `refurbished`: disabled.
- Unknown/open-box ambiguous condition: excluded until correctly mapped.
- Exclude draft, pending, hidden/archived, deleted, unavailable, or policy-ineligible products.
- For the initial pilot, exclude out-of-stock one-off products; document behavior and check official-plugin inventory handling.
- Enable included/excluded categories and individual-product exclusions.
- Sellers cannot override global feed policies.
- After pilot, publish eligible products automatically; do not require admin approval for every listing.

Maintain separate states for **local eligibility**, **Pinterest catalog acceptance**, and **organic distribution eligibility**. Only report Pinterest approval when corroborated by actual Pinterest status.

## Existing Seller Cam video reuse

For an eligible product, resolve the preexisting Seller Cam video by WooCommerce product ID and verify the media URL is public and direct. If available and permitted:
- Support Pinterest's documented `video_link` catalog attribute, subject to current spec verification, with MP4/MOV/M4V where accepted.
- Do not re-record, re-upload, or transcode content unless separately approved.
- Add the attribute only through a verified safe hook in Pinterest for WooCommerce.
- Without a compatible hook, retain normal image-based product synchronization and report video feed support as **blocked/pending design**, not delivered.
- A product without video is still valid for regular image-based feed inclusion.
- Respect seller authorization to reuse Seller Cam content for off-site marketing.
- Prefer short portrait videos, approximately 10–15 seconds, but do not require sellers to change existing footage.

Note: A Pinterest catalog `video_link` is different from independently publishing a Pinterest Video Pin. Instagram Reels, Facebook Reels, YouTube Shorts, and TikTok belong to the separate future KnifeRevive Social Hub project.

## Synchronization and multi-seller support

Reuse the official Pinterest plugin's scheduled catalog feed. Apply identical rules regardless of Dokan vendor. Handle create/publish/edit/price update/stock change/archive/unpublish/delete, with updates reflected in the next supported feed cycle. Do not promise real-time publication.

Use stable product IDs, avoid duplicate catalog entries, make re-runs idempotent, and honor WordPress capability checks. Do not disclose API keys, seller contact information, or private video URLs in diagnostics/logs.

## Admin dry run and diagnostics

Provide:
- WooCommerce product ID, title, seller, category, native condition, mapped Pinterest condition.
- Eligibility state (`eligible`, `excluded`, `blocked`) and explicit reasons.
- Seller Cam video presence, whether the URL is valid/public, whether `video_link` is really emitted.
- Last known feed generation status; Pinterest approval only where confirmed by Pinterest.
- Five-product preview and redacted sample records before first production synchronization.

## Five pilot acceptance tests

1. Published, in-stock, genuinely new knife, with photo and valid Seller Cam video: eligible **only if Pinterest content policy permits**; `video_link` included only if supported.
2. Published, in-stock, new technology item without video: eligible image-only entry.
3. Published used item: excluded under default policy.
4. Ambiguous open-box or unmapped condition: excluded.
5. Draft/archived or sold-out one-off listing: excluded or updated in accordance with documented stock policy.

Verify feed values/links, images, prices, stock, exact mapped condition, optional video, seller independence, scheduled updates, cache invalidation, duplicate prevention, safe rollback, and zero impact on storefront checkout. Differentiate local feed success from Pinterest catalog/merchant approval.

## Phased delivery

**A. Discovery:** Document actual source fields, installed versions, hook feasibility, restrictions.  
**B. Safe pilot:** Build installable companion plugin, admin mappings, eligibility preview, pilot allowlist; no bulk syncing by default.  
**C. Video:** Add supported `video_link` integration, or document blocker and proposed alternative.  
**D. Live pilot:** User connects Pinterest Business account and catalog, inspects diagnostics; no paid ads authorized.  
**E. Scale:** Broaden allowed products only once business eligibility and pilot behavior are understood.

## User setup (outside Codex scope)

- Create a Pinterest Business account, verify `kniferevive.com`, and install the official Pinterest for WooCommerce plugin locally.
- Complete catalog onboarding only when ready; check whether the official plugin automatically enables product sync.
- Give Codex local source access to ListLab, Seller Cam, WooCommerce, Dokan, and Pinterest plugin.
- Confirm marketplace eligibility with Pinterest before projecting **free shopping traffic**.
- Choose five accurately described test listings and confirm seller permission to distribute videos.
- Authorize any production pilot activation separately.

## Required Codex deliverables

- Installable `kniferevive-pinterest-bridge.zip` and complete plugin source.
- `README.md` for configuration, compatibility, limitations, security, and rollback.
- Technical discovery report citing inspected files, hooks, condition fields, Seller Cam media relationship, and plugin versions.
- Test report with redacted feed samples, pass/fail results, and clearly distinguished local/Pinterest statuses.
- Documented known blockers and unresolved policy/approval questions.

## Out of scope

Do not alter upstream plugins; build video recording; auto-publish to unrelated social platforms; misrepresent merchant eligibility; create paid campaigns; or claim guaranteed organic shopping traffic.

## References

- [Pinterest for WooCommerce source](https://github.com/woocommerce/pinterest-for-woocommerce)
- [WooCommerce plugin documentation](https://woocommerce.com/document/pinterest-for-woocommerce/)
- [Pinterest WooCommerce connection](https://help.pinterest.com/en/business/article/link-your-woocommerce-and-pinterest-accounts)
- [Pinterest catalog requirements](https://help.pinterest.com/en/business/article/before-you-get-started-with-catalogs)
- [Pinterest merchant guidelines](https://policy.pinterest.com/en/merchant-guidelines)
- Internal report: `KNIFEREVIVE_LISTLAB_TECHNICAL_IMPLEMENTATION_REPORT.md` (Codex must verify against installed source).
