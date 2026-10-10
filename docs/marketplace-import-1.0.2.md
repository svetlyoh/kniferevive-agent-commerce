# Marketplace Imports 1.0.2: default shipping package

At the owner's request, blank package fields now use 15 oz and 1 × 6 × 4 inches
(length × width × height). The companion advertises those defaults and converted
store-unit values in its public schema, prefills import preparations and applies
the same blank-field rule to native ListLab writes and later saves of imported
products. Fields are handled independently; seller-entered values, including an
explicit zero, are preserved. Existing unrelated WooCommerce saves and virtual
products are excluded. There is no blanket catalog backfill.

In the current live store's pounds/inches units, the default values are
0.9375 lb, length 1, width 6, height 4. The private review page identifies these
as store defaults and directs the seller to check/replace them in ListLab.
They are not claimed as measurements extracted from Facebook. Native Merchant
Sync maps the saved WooCommerce weight to Google and queues eligible saves.

Listing skill 1.0.3 describes these defaults, unit conversion and preservation
of actual source package values. It retains complete gallery extraction and
the previously requested condition defaults. Blade length remains a separate
product attribute. Its actual installation in Muse remains unverified.

Validation: 88 fenced native assertions passed, including preparation/review,
physical draft storage, later saves, native ListLab REST creation, preserving
entered values, metric conversion, virtual exclusions and request-scope cleanup.
PHP syntax checks passed for all five plugin PHP files. Skill validation and
package source/reference verification are performed before publication.

Deployment and the two live product updates are recorded separately after
verification; Google approval and image crawl completion are not implied.
