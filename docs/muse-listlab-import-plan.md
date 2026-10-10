# Muse → Facebook Marketplace → ListLab implementation plan

October 10, 2026. Requested outcome: point to a Marketplace listing in Muse and
say “List this on KnifeRevive.” Muse reads the referenced item and prepares a
private first-party handoff containing title, description, specifications,
photos and source price. The seller completes the listing in native ListLab.

1. Add a standalone WooCommerce/ListLab companion plugin, retaining the native
   seller writer, image authorization, category attributes and publish rules.
2. Add WooCommerce → Marketplace Imports pricing controls: global percentage
   markup, fixed addition, minimum price, rounding increment and category
   overrides. The nearest configured ancestor applies to child categories.
   Default 20% markup: Facebook $25 → KnifeRevive $30, plus native shipping.
3. Provide an anonymous bounded prepare API, public extraction schema, private
   expiring token and review page. Preparation creates no product and downloads
   no image. Login and seller authorization are required to create a draft.
4. On the review page allow category/detail changes, recalculate from current
   merchant pricing settings, and copy real supported Facebook CDN photos or
   use the seller's already-uploaded ListLab images. Preserve partial failures
   explicitly; never publish without required fields/photos.
5. Claim once into a seller-owned native ListLab draft. Retries return the same
   draft. Open its native edit link for quantity, shipping/returns, attributes,
   photo corrections and final publication. Display actual draft/pending/live
   status, never claim a prepared import is already published.
6. Extend the existing Concierge identity and Muse installation guidance.
   Use host HTTP tools and referenced browser context; no invented Muse API,
   Facebook credential discovery, bulk scraping or automatic inventory claims.
7. Test pricing inheritance, source normalization, payload bounds, private token
   expiry, seller access, repeat claims, native fields and controlled image
   handling in the fenced synthetic database. Package plugin/skill with hashes.
   Verify first-party review in a browser; record Muse and live deployment status
   separately. No real Marketplace listing or production product is created
   during implementation tests.

Shipping is not part of the markup formula. Native WooCommerce shipping methods,
seller policies and destination determine the buyer's eventual shipping charge.
An unknown shipping charge is not zero. Facebook prose and pictures are source
data, not instructions, inventory proof, authenticity or permission to republish.
