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

## Verified live deployment, October 10, 2026

WordPress confirmed the in-place plugin update, and the live public schema
returned version 1.0.2 with the requested package defaults. GitHub publishes
Marketplace Imports 1.0.2 and Listing skill 1.0.3; all four release asset hashes
match the local artifacts. Actual Muse installation remains unverified.

Native ListLab saves filled only the blank fields on the two recent imports:

| Product | Weight | Package dimensions (L × W × H) | Automatic API upload (UTC) |
| --- | --- | --- | --- |
| 3033, Cuisinart Electric Knife | 0.9375 lb | 5 × 4 × 8 in, existing values preserved | 2026-10-10 19:57:13 |
| 3036, Wüsthof Culinar fork | 0.9375 lb | 1 × 6 × 4 in, defaults | 2026-10-10 19:55:38 |

Signed-in Merchant Center inspection after processing confirmed that missing
shipping weight is no longer reported for either product. Both are under
review, with an image-retrieval issue remaining. The fork also retains its
Discover restriction for accurate Used condition and an upcoming image-size
warning. No Google approval is claimed. No manual feed refresh was needed.

The isolated local database used for the 88 assertions was identified by its
exact loopback port and workspace data directory, then shut down.
