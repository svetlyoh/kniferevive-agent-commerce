# Muse install prompt

Paste this into Muse after Marketplace Imports 1.1.1 is installed on KnifeRevive:

```text
Update my existing kniferevive-listing skill in place from:
https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.10/skills/kniferevive-listing

Import the complete folder, references and photo helper. Keep one active copy named kniferevive-listing in your active custom skill catalog (~/workspace/skills on this Muse host), preserving its existing identity. Do not create a duplicate skill or change kniferevive-concierge. Verify version 1.0.10 is discoverable from a fresh chat and the live import schema advertises inbox support.

Read references/inbox.md. Once I privately supply the setup prompt from my KnifeRevive seller inbox, use its inbox_key only as the top-level delivery field on /prepare. Queue each Facebook Marketplace item I select with the complete extracted attributes, verified source price, both matching descriptions in my seller voice, all distinct original-resolution gallery photos and a truthful photo_manifest. Keep the key private; never include it in URLs, public descriptions, logs or skill files. Report state: queued as waiting in my inbox, not a draft or published listing. Repeated source submissions retain the original item; do not create a replacement to bypass errors. My signed-in browser completion agent will retrieve the queue when I ask and complete the original drafts. If required tools or private key storage are unavailable, tell me accurately before claiming the integration is ready.
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
