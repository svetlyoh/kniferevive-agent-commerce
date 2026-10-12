# Marketplace inbox — 12 October 2026

Marketplace Imports **1.1.0** upgrades the existing import entry point into a
durable, seller-specific queue. Listing skill **1.0.9** supports delivery to it.
The completion agent retrieves the queue through native browser forms when the
owner issues a run command. Delivering an item does not create a product.

## Flow and owner setup

1. Sign in to the intended seller account and open
   https://kniferevive.com/?krev_listlab_import=review&inbox=1.
2. Open **Connect Muse to my inbox**, authorize queue delivery and optionally
   authorize preservation of supplied photos. Create the connection and copy
   the once-shown private setup prompt into Muse. No key is bundled in GitHub.
3. Update the existing `kniferevive-listing` skill in place from
   https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.9/skills/kniferevive-listing.
   Keep one active copy in Muse's active custom skill catalog.
4. Muse sends each selected item's complete extraction and counted gallery to
   `/prepare` with `inbox_key`, then reports **Queued**, not draft or published.
5. Use `KNIFEREVIVE_LISTLAB_COMPLETION_AGENT_PROMPT.md` in a browser-enabled chat.
   Say **Process my inbox and publish**, **Process the next 5 items and publish**,
   **Process my inbox as drafts**, or **Resume my inbox drafts and publish**.

The browser snapshots the batch at run start, opens each original review,
creates its seller draft, follows ListLab, applies the existing completion
rules, saves/reopens/verifies and publishes only under the chosen run command.
Later arrivals await the next run. Per-item failures remain drafts/attention and
are reported; they do not create replacements or prevent other eligible items
in the snapshot from being processed. Authentication/site-wide failures stop
the run. No unattended watcher is installed.

Pricing remains a per-item 15% markup, rounded up to whole USD. Native store-wide
import pricing is unchanged. Shipping defaults to existing **knives**. Missing
package fields use 15 oz and 1 × 6 × 4 in, converted to native units. Returns
use final item price: phone/Apple Watch $120+ No Returns; every remaining $50+
item $10 / 15 days; remaining below $50 $5 / 15 days. Earlier free thresholds
are superseded. Public descriptions match in the seller's voice, without
Facebook sourcing or original-seller references. Existing all-photo, Google
subcategory/specification/condition and quantity-one rules are preserved.

## Persistence and access

One indexed `krev_marketplace_inbox` table plus private preparation records;
no two-hour expiry for inbox records. Existing private token handoffs retain
their old expiry and same-tab login flow. Native queue reads, status, preview,
claim, archive and connection controls require an enabled seller session and
WP REST nonce, with exact owner checks even for another administrator.

Delivery keys are random 256-bit secrets stored only as hashes. They grant
delivery to one seller, never inbox reading/product editing/publication. Key
rotation/disconnection or capability revocation prevents subsequent delivery;
existing items remain. Canonical source deduplication retains original queue
IDs and existing seller product edits across re-delivery and claims. Archive
is reversible and affects neither the product nor preserved media.

Optional photo preservation uses the existing bounded HTTPS host allowlist,
image validation, original-resolution checks and ten-photo cap. It creates
native media owned by the destination seller without impersonating them.
Claims reuse preserved media after source URL expiry. Warnings remain visible;
neither preservation nor delivery proves a complete source gallery.

Capacity: 100 waiting items, 20 new arrivals/seller/hour and existing site
limits. Photo download interruptions can leave an attention item or persistent
claim lock requiring original-record recovery with admin support. Do not delete
locks blindly or generate replacements. Preserved media is retained on archive.

## Validation and deployment evidence

Native sandbox regression and browser DOM validation results are recorded in
the owner release report after packaging. Tests fence the database and block
external HTTP/email. No production inventory is created for testing.

Deploy only the existing Marketplace Imports plugin in place. No theme or
other plugin replacement is needed. Connection creation is a separate native
seller setup action; deployment does not automatically grant Muse a key.
Owner packages and release manifest identify committed source and all file
hashes. Rollback to 1.0.7 removes the inbox interface, but preserves its stored
data; legacy imports continue. Re-enable 1.1.0 to restore the inbox.
