# Merchant Center diagnostics for the two new imports

Signed-in Merchant Center shows both recent skill-created products under the
existing API data source. This confirms automatic submission. The catalog
reports 30 total products and two not showing on Google; this is a Google
catalog snapshot, not proof that every current WooCommerce product is included.

| Native product | Google offer | Current blocking issues |
| --- | --- | --- |
| 3033, Cuisinart Electric Knife | kr-wc-3033 | Missing shipping weight; unable to show image |
| 3036, Wüsthof Culinar Model 4419 carving fork | kr-wc-3036 | Missing shipping weight; unable to show image |

The shipping-weight diagnostics specifically require total packaged weight.
The native site uses pounds; Merchant Sync maps native WooCommerce weight to
Google shippingWeight and queues product saves automatically. No weight was
invented. The owner was asked for each package's measured weight and units.
Pending those answers, the weight blockers remain unresolved.

Google's image diagnostic says a new image may take up to three days to appear.
Both exact submitted originals load in the browser and are 403 × 403 pixels:

- Cuisinart: https://kniferevive.com/wp-content/uploads/2026/10/marketplace-a311de8d80dfc47c.jpg
- Wüsthof: https://kniferevive.com/wp-content/uploads/2026/10/marketplace-2a1292c240419cf6.jpg

The mapper uses the original attachment URLs, not WordPress resize URLs.
Wüsthof has an informational warning to provide at least 500 × 500 pixels
before January 31, 2027. Full-resolution replacement photos are preferable
for both items; enlarging a thumbnail is not recovery of the original detail.
Browser accessibility does not prove Google has crawled the files. A robots.txt
inspection and Google product CSV export were blocked by the browser client;
no blocked resource was fetched through another route.

The fork also has a Discover/Demand Gen warning: used/refurbished products
cannot show on that channel. Its source describes it as pre-owned, so the
correct Used condition was retained. That channel restriction is distinct
from the two blockers for Shopping ads and Free listings.

Wüsthof's structured brand was missing despite the description naming the
maker. The existing native Wüsthof brand was selected and the product updated.
The save automatically submitted another successful Merchant API upload at
2026-10-10T19:43:51+00:00 (12:43:51 PM PDT). No manual reconciliation, new
Google source, condition relabeling or shipping-rate change was performed.

Evidence is retained in the owner's kniferevive-listing-1.0.2 release folder:
3033/3036 diagnostic text, 3033 diagnostics screenshot and 3036 brand-sync
screenshot. Actual Google approval remains pending correction and processing.

References: [Google image processing diagnostics](https://support.google.com/merchants/answer/12159032?hl=en),
[shipping-weight issue help linked by the account](https://support.google.com/merchants/answer/12468179?hl=en-US),
and [image size help linked by the account](https://support.google.com/merchants/answer/12159030?hl=en-US).
