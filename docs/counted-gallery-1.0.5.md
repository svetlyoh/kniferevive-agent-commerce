# Counted Facebook galleries: Listing and Marketplace Imports 1.0.5

The owner reported that Muse 1.0.4 imported only one small image from Marketplace
item 2032002757484110. Muse confirmed it sent one 403 × 403 URL to `/prepare`.
The source page exposed eight photo controls; traversing them recovered eight
distinct 720 × 960 source-served images. Successful preparation had only confirmed
receipt of the one-image payload, not completion of the Facebook gallery.

Product 3046 was repaired through native ListLab without creating another
product. Its saved gallery contains eight photos, its cover is 720 × 960, and
quantity 1 and price $43 were preserved. These are the largest observed source
files; no larger camera originals were exposed. Signed CDN URLs and downloaded
photos remain in private owner diagnostics, outside this repository.

The skill now requires an inventory based on gallery controls/counter or verified
end/wrap traversal, visits every position, and checks the serialized photo count
before preparation. The helper emits `photo_manifest` alongside `image_urls`.
Metadata URL count alone cannot establish the source gallery count.

Marketplace Imports 1.0.5 requires the manifest for new preparations and rejects
missing contracts, unverified galleries and mismatched counts. Galleries above
ten retain their full source count while this import is capped at ten photos.
Missing/small photos require specific seller acceptance. Downloaded files below
500 pixels on either axis are rejected unless the small-source limitation was
accepted. Preparation and claim report found/submitted/copied separately; review
labels photos as supplied until bytes are actually copied. Old prepared links
without a manifest remain claimable. Retries preserve the original listing.

The server validates declared counts and downloaded bytes; it cannot independently
prove that Muse inspected Facebook or truthfully declared the source count.
Muse still needs an authorized browser that can traverse the source gallery.

Validation: 108 native sandbox assertions passed, including missing/mismatched
manifests, cap handling, malformed fields, actual small-file rejection, legacy
prepared-link compatibility and existing seller/pricing/default behavior. All 14
photo-selection tests passed. The private eight-image observed fixture returned
ready with eight found/selected/included. Skill validation and JavaScript syntax
checks passed. Native ListLab reload confirmed the saved eight-photo repair;
the public product gallery exposes 720 × 960 original dimensions for all eight.

Install/update one existing `kniferevive-listing` identity from:
https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.5/skills/kniferevive-listing

Import the entire folder, including references and the photo helper, into the
active custom skill catalog. Verify 1.0.5 in a fresh Muse chat. No Muse installation
or ClawHub publication is performed by this source change.
