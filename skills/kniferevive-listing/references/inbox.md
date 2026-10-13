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
4. Require the actual JSON response. `state: queued` means **Queued in your
   KnifeRevive inbox**. Show the returned item review link, title, source price
   and found/submitted photo counts from extraction. Do not report a copied
   count until the signed-in inbox/claim confirms it. Queue delivery does not
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

The browser completion prompt handles draft creation, final 15% markup,
shipping, returns, verification and publication when the user runs it. Keep
queue delivery and product publication as distinct states.
