# Install KnifeRevive Listing in Muse

Skill: `kniferevive-listing`, version **1.0.9**. This is a separate seller skill;
Concierge is for shopping and sharpening. Install the complete GitHub folder:

https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.9/skills/kniferevive-listing

Use the host's supported skill import facility. On the owner's reported Muse
host, the discoverable custom catalog is `~/workspace/skills`, so the complete
folder belongs at `~/workspace/skills/kniferevive-listing`. The old
`~/.openclaw/workspace/skills` directory was outside that host's catalog. Check
the actual catalog on another host. Retain one active copy of this new identity,
including `SKILL.md`, `LICENSE` and all reference files. Reload and verify
version 1.0.9 from a fresh chat; reading files in one chat is not installation.

Paste into Muse:

> Install or update the separate KnifeRevive Listing skill in place from https://github.com/svetlyoh/kniferevive-agent-commerce/tree/kniferevive-listing-v1.0.9/skills/kniferevive-listing. Import the entire folder and references into your active custom skill catalog (on this host, ~/workspace/skills/kniferevive-listing). Keep one active copy named kniferevive-listing and verify version 1.0.9 is discoverable in a fresh chat. When I point to a Facebook Marketplace listing and say “Prepare this for KnifeRevive,” read its attributes and USD price, verify every gallery position, collect all distinct photos at the largest source-served resolution, measure pixels and report missing/small photos or import-limit overflow; default omitted new-listing quantity to 1, preserve explicit quantities including zero, and update an existing listing in place for photo repairs, extract the stated brand and (for knives) knife/blade type, edge style and blade length with units, write both public descriptions in my KnifeRevive seller voice without Facebook sourcing, source URLs or references to the original seller, preserve supported item facts without inventing personal claims, and set short_description equal to description, collect Google feed fields wherever the source supports them, preserve explicit condition and otherwise default knives to Gently Used and other goods to Used only when allowed by the selected subcategory, metacategory or category condition options (label the default); leave unknown consumable condition for seller completion, use the live import schema and merchant pricing, fetch the six-field specification profile for the selected Tech, Home & Garden, or Collectibles Google subcategory, extract supported facts and genuine SKU/MPN/GTIN identifiers without inventing them, keep AI-Tech or Pokémon Cards collections separate from the Google subcategory, and open the private ListLab completion link. Tell me if required browser, HTTP or link-opening tools are unavailable. Never report a prepared link as a published listing.

For an existing Concierge 0.6.2 installation, update that same Concierge identity
in place to the complete `skill-v0.6.3/skills/kniferevive-concierge` GitHub folder.
Version 0.6.3 removes its seller import branch, preventing overlapping listing
instructions. Keep Concierge if shopping/sharpening is wanted; Listing works
independently. No second Concierge or renamed Concierge fork is needed.

Marketplace Imports 1.0.3 prefills matching short/long descriptions. Check the
live schema's package defaults: blank shipping fields use 15 oz and 1 × 6 × 4
inches (length × width × height), converted to store units; entered values take
priority. These are merchant defaults, not Facebook measurements. Check the
live schema version to confirm that merchant update is active. Category prices
are controlled by the merchant at **WooCommerce → Marketplace Imports**; skill
installation does not deploy or change the WordPress plugin.

Verify host capabilities separately: read the public import schema, then prepare
one user-selected item and open its private link. Stop before creating a real
product unless the seller chooses that first-party action. A successful schema
read is not proof of Facebook extraction, photo copying or publication. No new
seller credentials or cookies should be copied into Muse. No ClawHub publication
is claimed for this new skill; use its GitHub source.

Marketplace Imports 1.0.5 requires a counted photo_manifest for new preparations. Verify the installed skill actually inventories the gallery controls and traverses each position; file-for-file installation alone does not prove extraction. Before submitting, compare source_count against image_urls (up to the live cap), and report found/submitted/copied separately. Existing prepared links from older versions remain claimable.

Marketplace Imports 1.0.7 advertises the selected subcategory’s six specification fields and separate collection/subcategory IDs. Native category/collection changes arrive through the live schema; never use a stale hard-coded category list.

## Connect the seller inbox

Marketplace Imports 1.1.0 adds the native seller inbox. Follow [inbox setup](inbox.md) once after installing this skill. Copy the once-shown private setup prompt from the seller inbox into Muse; the GitHub skill alone does not configure a seller-specific destination key. Use the key only for delivery to /prepare, never for reading, claiming or publishing.
