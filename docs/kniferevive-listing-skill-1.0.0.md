# Separate KnifeRevive Listing skill 1.0.0

The seller workflow now has its own identity, `kniferevive-listing`. It reads a
user-referenced Facebook Marketplace item, extracts factual attributes, source
USD price and real photos, maps the live category schema, sends `/prepare` and
opens the private ListLab completion link. It needs Muse's existing browser,
HTTP and link-opening facilities. It is independent of KnifeRevive Concierge.

Install the complete [GitHub folder](https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.0/skills/kniferevive-listing).
See [the Muse prompt](../skills/kniferevive-listing/references/installation.md).
On the owner's reported host it belongs in `~/workspace/skills/kniferevive-listing`,
with one active copy and fresh-chat discovery. Concierge 0.6.3 removes the seller
workflow while preserving shopping, Google-feed search and sharpening; update an
existing Concierge in place if that older combined version was installed.

The existing Marketplace Imports 1.0.0 plugin remains active and unchanged.
Its global/category settings default to 20% markup: source $25 becomes a $30 item
price, with native shipping separate. The seller reviews rights and details and
finishes publishing in ListLab. The skill does not add a Facebook browser button.

Read-only production verification on October 10, 2026: PowerShell HTTP/2 with
`Accept: application/json` returned schema version 1.0.0, USD and 21 categories.
HTTP/1.1 previously encountered hosting browser verification. This resolves that
client's schema read, not every Muse host's access. Actual Muse installation,
Facebook extraction, preparation and real photo import remain unverified. No
production product or order was created by these checks. Existing plugin tests
passed 67 native assertions; this split changes skill routing, not plugin code.

The release includes Listing 1.0.0, Concierge 0.6.3 and exact source/package hashes.
Neither new skill version is claimed to be on ClawHub.
