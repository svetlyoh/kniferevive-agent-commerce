# Marketplace Imports 1.1.1

The ListLab import entry point now opens a durable seller inbox. Muse can deliver
selected items with an optional `inbox_key` on `/prepare`. Native seller setup
creates a once-shown key stored only as a hash. It grants delivery, not reading,
claiming, editing or publishing. Seller-scoped nonce-protected inbox views show
Waiting, Drafts / review, Published and Archived, oldest first, 20 per page.

Inbox records do not expire. Canonical source URLs deduplicate per
seller, and claim retains existing product IDs/edits. Archive affects only the
inbox. Optional authorized photo preservation copies original bytes on arrival
into the destination seller's native media; claims reuse those images. Errors
remain visible. New private handoffs expire after three days (259200 seconds from preparation). Previously issued links retain their original expiry; existing legacy handoffs still work.

The seller connection form checks “Allow Muse to queue items in my inbox” by
default. This form default does not create a connection or issue a delivery key;
the seller must submit Create / replace Muse connection.

The inbox is limited to 100 waiting items, 20 new arrivals per seller/hour, plus
existing site rate limits. An interrupted claim/delivery can leave a persistent
lock; recover the original record/product with administrator support, never
delete locks blindly or create replacement products. Keys can be rotated or
disconnected without deleting queued items. Media is retained on archive.

Deployment upgrades the existing plugin in place and creates one indexed inbox
table. It does not change store-wide pricing, shipping, returns or themes.
Browser completion uses the owner's separate prompt and current native forms.
See docs/marketplace-inbox-2026-10-12.md for validation and owner setup.
