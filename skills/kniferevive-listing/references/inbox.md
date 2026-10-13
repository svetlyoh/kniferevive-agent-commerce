# Seller inbox delivery (Marketplace Imports 1.1.0+)

Use inbox mode when the user has connected this skill to their KnifeRevive
seller inbox and supplied the delivery key privately. Queue each selected item
with the usual complete extraction; the browser completion agent processes it
later when the user asks. Do not select unrelated items or start a watcher.

1. Read the live `/schema`. Require `inbox.supported: true`; if unavailable,
   report it and use the existing private-link flow only with user agreement.
2. One-time setup: the seller signs in at
   https://kniferevive.com/?krev_listlab_import=review&inbox=1, opens
   **Connect Muse to my inbox**, authorizes delivery, optionally authorizes
   preserving photos, and copies the private setup prompt into Muse. A key is
   shown once. Store it in the host's private user configuration if supported,
   otherwise explain it may need to be supplied again in a fresh chat. Never
   write it into skill files, public memory, URLs, descriptions or logs.
3. POST the normal extraction and truthful `photo_manifest` to `/prepare`, with
   the additional top-level JSON field `inbox_key`. Keep each item's all-photo
   payload in one request. Use existing gallery, attribute and description rules.
   No product is created or published by delivery. Do not call claim from Muse
   or use the key to read the inbox; it grants delivery only.
4. Require the actual HTTP status and JSON response. With Marketplace Imports
   1.1.2+, read `item_state`: `queued` is Waiting; `draft`, `published` and
   `archived` refer to the retained original in those views. Outer `state: queued`
   is a delivery acknowledgement, not proof it remains Waiting. On an older
   plugin, `already_queued: true` leaves its current state unverified: ask the
   seller to open the original review link. Show that link and the extracted
   title/source price. Read `photo_delivery` for actual submitted, preserved,
   missing and ready counts; found counts still come from gallery extraction.
   Never turn a missing copied count into zero confirmed copies or a success.
   Queue delivery does not
   return a price quote; use native review for current merchant pricing.
5. `already_queued: true` means the original item was retained. Do not overwrite
   facts, archive/restore it automatically, create another source URL, or claim
   it again to force an update. Repairs use the existing native item/product.
   On timeout, inspect the owner's inbox through the authorized browser or retry
   the exact same source/key once; do not fall back to a new private preparation.
   On 409 busy/full or 429 limit, report the state and wait for user action.
   A disconnected/rotated/revoked key requires reconnecting; do not bypass it.

Inbox records do not expire. Up to 100 waiting items and 20 new items per
seller per hour, plus existing site limits. ListLab uses owner-scoped signed-in
access with a native nonce. Inbox item IDs are not read credentials. Repeated
canonical Facebook sources deduplicate per seller, including archived items.
New private handoffs without an inbox key use a three-day token in Marketplace Imports 1.1.1+. Previously issued links retain their original expiry.

Photo preservation is optional and seller-authorized. It copies up to ten
supported original images into that seller's media library on arrival, keeping
their bytes available after Facebook URLs expire. Failures appear in the inbox;
never assume every supplied URL copied. Claim reuses preserved media and retries
missing photos. Incomplete-gallery warnings still require repair or explicit
seller acceptance before publication. Never fabricate photos or extract only
metadata thumbnails because the inbox can preserve images.

## Reliable original-file delivery (Marketplace Imports 1.1.2+)

For the owner's queued-photo workflow, use the seller's photo-preserving
connection. Download all original source-served gallery bytes privately, then
include top-level `photo_delivery_mode: "original_files"` with `/prepare`.
This queues promptly without server fetches of expiring Facebook URLs. Send every
submitted gallery slot through the original-file endpoint before reporting ready.
Keep the bytes until the
receipt confirms preservation. Signed Facebook URLs can fail on KnifeRevive
even while Muse can view the photos. Three-day import tokens do not extend them.
If `photo_delivery.missing` is positive (including older URL-mode arrivals), send the originals to the returned
first-party `/inbox/{inbox_id}/photos` endpoint, one missing slot per POST, with
JSON `inbox_key`, `photo_index` (one-based position in the submitted image_urls)
and `image_base64` (raw original JPEG/PNG/WebP bytes, not a data URI). The server
limits each file to 5 MB, validates actual pixels, preserves gallery order and
deduplicates retries. Native photo-preservation consent is required; Muse cannot
enable it with the delivery key.

When Python and local source downloads are available, use
`scripts/send_inbox_photos.py --inbox-id ID --files PRIVATE_FILES_JSON` with the
key in the process environment `KREV_INBOX_KEY`, never in command arguments.
The JSON is a list of `{"photo_index": 1, "path": "/absolute/original.jpg"}`.
Use the exact original downloads from the authorized gallery, not screenshots
or synthesized images. If originals cannot be downloaded/uploaded with the
available tools, report the photo blocker; do not claim readiness. Fetch the
live schema to verify support instead of assuming installing the skill added it.

Require `photo_delivery.ready: true` and the expected preserved count before
saying the item and its photos are ready in the inbox. Photo recovery writes only
missing slots in unclaimed active imports; it cannot change an existing product
or archived import. On 409, inspect the original review and do not create a
replacement. If preservation is off, explain that pending URLs are not durable
and ask the seller to enable it. Do not silently fall back to private-link mode
when the user requested their inbox. New draft creation is blocked on failed
supplied photos unless the seller explicitly accepted incomplete photos.

The browser completion prompt handles draft creation, final 15% markup,
shipping, returns, verification and publication when the user runs it. Keep
queue delivery and product publication as distinct states.
