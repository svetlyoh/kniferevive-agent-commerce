# Marketplace photo recovery and default quantity

Listing skill 1.0.4 adds a concrete per-photo recovery procedure: traverse every
selected-item gallery control, verify active-image changes after loading, read
actual decoded dimensions and choose the largest observed source-served variant.
Inactive thumbnail order must not determine the main photo. Signed Facebook URLs
are preserved; no resizing parameters, signatures or hidden API endpoints are
guessed. Browser-native image download and native ListLab upload provide a
fallback when the existing server cannot fetch an image. Camera originals are
not claimed when only a Facebook-served version is exposed.

The bundled Python helper validates privately recorded browser observations,
deduplicates sizes by real photo identity, preserves source order and chooses
the largest full variant. Missing/gallery-count mismatches, thumbnail-only or
sub-500-pixel sources are reported before preparation. User-accepted small or
partial galleries remain disclosed. Ten photos can be submitted in one import;
overflow is retained and reported for native completion. The helper performs
no browser access, network requests, uploading, import or publication.

Marketplace Imports 1.0.3 defaults omitted/blank quantity to one on new import
preparation and review, preserving explicit zero or higher quantities and later
seller edits. The schema advertises this owner inventory default under
`package_defaults.inventory_default`; the private review page explains it.
Native ListLab's fresh editor already defaults quantity to one. Skill 1.0.4 now
sends one when source quantity is absent and labels it as a seller default,
not a verified Facebook inventory fact. This does not publish a draft or reset
existing sold-out products.

Validation: 92 fenced native import assertions, 11 photo-selection regression
tests, PHP lint for all five plugin files, skill validation and whitespace checks
passed. Package validation also verifies committed source and portable references.
The native import harness loads the installed ListLab product writer with its
synthetic seller roles; it does not load the optional full Dokan test stack.
Actual installation and execution inside Muse remain unverified.

## Selected listing recovery

The owner supplied [Marketplace item 1073573501970921](https://www.facebook.com/marketplace/item/1073573501970921/)
and [KnifeRevive product 3041](https://kniferevive.com/shop/chefs-knife/thyme-amp-table-kitchen-knife-set/).
Facebook exposes two gallery controls and two distinct full-frame source photos.
Both were decoded and saved through the browser at 443 × 960 pixels, preserving
portrait aspect ratio. The previous KnifeRevive main photo was 403 × 403 pixels
with no gallery. The newly uploaded native attachments also report 443 × 960.
No larger source variant was exposed through the inspected image controls,
`src` or `srcset`; this is recovery of the best available Facebook versions,
not proof of camera-original access or Google image compliance.

Marketplace Imports 1.0.3 was updated in place on kniferevive.com. WordPress
reported a successful update, and the live HTTP/2 schema returned version 1.0.3,
`max_images: 10` and `package_defaults.inventory_default.quantity: 1`.

Both recovered attachments were saved to existing product 3041 through the
native product editor as the cover and one gallery image. WordPress confirmed the product update;
reloading native ListLab showed exactly two uploaded photos, Cover photo and
Photo 2. The old cropped attachment was replaced as cover without deleting it
from the media library. Existing quantity zero and other product fields were
preserved. Quantity one is the default for new imports, not an inventory reset
for existing products. The saved ListLab gallery screenshot and live schema
JSON are in the owner's `releases/marketplace-import-1.0.3` documentation folder.
GitHub tags and release archives are published; their SHA-256 digests were
verified against the local packages. Actual Muse installation remains unverified.

## Muse update prompt

> Update my existing kniferevive-listing skill in place from https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.4/skills/kniferevive-listing. Import the complete folder, scripts and references into your active custom skill catalog. Keep one active copy and verify version 1.0.4 in a fresh chat. Follow the photo recovery procedure: collect every distinct gallery photo at the largest observed source-served resolution, verify gallery counts and decoded dimensions, preserve signed URLs, and report missing/small photos or ten-photo overflow before preparation. Default omitted new-listing quantity to 1, preserving explicit quantities including zero. Repair an existing listing in place. Tell me if browser, image download, HTTP or skill-update tools are unavailable.
