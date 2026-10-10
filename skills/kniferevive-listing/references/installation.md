# Install KnifeRevive Listing in Muse

Skill: `kniferevive-listing`, version **1.0.1**. This is a separate seller skill;
Concierge is for shopping and sharpening. Install the complete GitHub folder:

https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.1/skills/kniferevive-listing

Use the host's supported skill import facility. On the owner's reported Muse
host, the discoverable custom catalog is `~/workspace/skills`, so the complete
folder belongs at `~/workspace/skills/kniferevive-listing`. The old
`~/.openclaw/workspace/skills` directory was outside that host's catalog. Check
the actual catalog on another host. Retain one active copy of this new identity,
including `SKILL.md`, `LICENSE` and both reference files. Reload and verify
version 1.0.1 from a fresh chat; reading files in one chat is not installation.

Paste into Muse:

> Install or update the separate KnifeRevive Listing skill in place from https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.1/skills/kniferevive-listing. Import the entire folder and references into your active custom skill catalog (on this host, ~/workspace/skills/kniferevive-listing). Keep one active copy named kniferevive-listing and verify version 1.0.1 is discoverable in a fresh chat. When I point to a Facebook Marketplace listing and say “Prepare this for KnifeRevive,” read its attributes, USD price and photos, extract the stated brand and (for knives) knife/blade type, edge style and blade length with units, set short_description equal to description, use the live import schema and merchant pricing, and open the private ListLab completion link. Tell me if required browser, HTTP or link-opening tools are unavailable. Never report a prepared link as a published listing.

For an existing Concierge 0.6.2 installation, update that same Concierge identity
in place to the complete `skill-v0.6.3/skills/kniferevive-concierge` GitHub folder.
Version 0.6.3 removes its seller import branch, preventing overlapping listing
instructions. Keep Concierge if shopping/sharpening is wanted; Listing works
independently. No second Concierge or renamed Concierge fork is needed.

Marketplace Imports 1.0.1 prefills matching short/long descriptions. Check the
live schema version to confirm that merchant update is active. Category prices
are controlled by the merchant at **WooCommerce → Marketplace Imports**; skill
installation does not deploy or change the WordPress plugin.

Verify host capabilities separately: read the public import schema, then prepare
one user-selected item and open its private link. Stop before creating a real
product unless the seller chooses that first-party action. A successful schema
read is not proof of Facebook extraction, photo copying or publication. No new
seller credentials or cookies should be copied into Muse. No ClawHub publication
is claimed for this new skill; use its GitHub source.
