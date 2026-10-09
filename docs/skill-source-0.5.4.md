# GitHub skill source correction — 0.5.4

The user's fresh install from commit `b80efa7` correctly reported skill 0.5.1. That commit updated the WordPress backend to 0.5.4 without updating `SKILL.md` or the booking reference. The skill files still offered three choices and the old $7.99-per-trip pricing. This was a source/version mismatch, not evidence of a failed download.

The GitHub skill metadata now declares 0.5.4. Its booking instructions match the deployed four choices, no ZIP check for customer drop-off/pay at collection, eligible ZIP checks for prepaid choices, $6 pickup/$11 combined transport for new requests, and `awaiting_payment` before verified native payment sends a prepaid request to merchant review. Existing quotes retain their prices. Native checkout session isolation is described without granting permission to clear another cart or bypass an existing order. Independent contact consent, human card checkout, scoped wallet authority, privacy and refund limits remain intact.

Installation now points to `main` for current GitHub source and explains how to resolve a commit, import the entire eight-file folder, check metadata/file integrity, and reload the host session. ClawHub remains 0.4.2. Older pinned commits/tags and registry installers do not supply this GitHub change. There is no new registry publication, audit, certification or processor-payment claim.

Validation: skill-creator frontmatter validator, documented booking example against the actual OpenAPI BookingInput schema, relative Markdown reference existence, UTF-8 packaging and archive file-for-file verification. The WordPress backend and production settings are unchanged by this source correction; its behavioral matrix remains documented in the 0.5.4 backend evidence.
