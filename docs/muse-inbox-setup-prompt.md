# Muse install prompt

Paste this into Muse after Marketplace Imports 1.1.2 is installed on KnifeRevive:

```text
Update my existing kniferevive-listing skill in place from:
https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.11/skills/kniferevive-listing

Import the complete folder, references and photo helper. Keep one active copy named kniferevive-listing in your active custom skill catalog (~/workspace/skills on this Muse host), preserving its existing identity. Do not create a duplicate skill or change kniferevive-concierge. Verify version 1.0.11 is discoverable from a fresh chat and the live import schema advertises inbox support.

Read references/inbox.md. Preserve my existing private seller inbox connection and photo-preservation consent. Queue each Facebook Marketplace item I select with complete attributes, verified source price, matching descriptions in my seller voice, every distinct original-resolution gallery photo and a truthful photo_manifest. Send inbox_key as the top-level delivery field on /prepare and, when needed, the original-file photo endpoint. Keep it private; never include it in URLs, descriptions, logs or skill files.

For new items, download the original gallery files, send photo_delivery_mode: original_files with /prepare, then upload every submitted slot through the photo endpoint or scripts/send_inbox_photos.py. This avoids KnifeRevive fetching Facebook's signed URLs. Keep the key in private process configuration. Read the actual response: outer state: queued acknowledges delivery; item_state identifies Waiting, an existing draft, publication or archive. Report duplicates accurately and retain their original review link. Require photo_delivery.ready: true with the expected preserved count before claiming the item and its photos are ready. Recover missing copies from older URL-mode arrivals into their original unclaimed item. Do not use screenshots or quietly recreate a private link. If download/upload tools are unavailable, report the photo blocker. My browser completion agent will create and complete the original drafts later when I ask.
```

One-time seller connection: open
https://kniferevive.com/?krev_listlab_import=review&inbox=1 → **Connect Muse to
my inbox** → authorize delivery and, if wanted, photo preservation → **Create /
replace Muse connection** → copy the once-shown private setup prompt into Muse.
“Allow Muse to queue items in my inbox” defaults to Yes. Creating the connection
still requires the seller to submit that form. New private import links last
72 hours; existing links retain their original expiry and queued inbox items do
not expire. Use the expiry returned by KnifeRevive as authoritative.

Then use the separate browser completion prompt and say **Process my inbox and
publish** or **Process the next 5 items and publish**. Plain “check the inbox”
only inspects and reports. Do not enable an unattended watcher by default.
