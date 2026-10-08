# Production backend deployment — 8 October 2026

KnifeRevive Agent Commerce **0.2.0 is deployed and active** on kniferevive.com.
The existing 0.1.3 installation was replaced through WordPress's plugin ZIP update
flow. The final package SHA-256 is `228da47df3df397c886420255d412fb72d1b33ef25974762c7fb1db2aa8522b5`.
No Stripe mode, gateway credentials, webhooks, seller permissions, product authors,
transfers or service policies were changed. Existing configuration values were
preserved; the three new listing verification/handoff flags remain false and the
gateway allowlist is empty. The local WordPress install remains 0.1.3.

## Verified after deployment

- Anonymous `GET /wp-json/kniferevive-agent/v1/capabilities`: HTTP 200; adapter 0.2.0.
- Anonymous `GET /wp-json/kniferevive-agent/v1/listings?per_page=3`: HTTP 200 JSON.
  The reported missing route is fixed.
- Individual small/large sharpening listings (1963/1964): HTTP 200; catalog prices
  $5/$7 before taxes, transport or other fees; both `needs_manual_review`.
- Live capabilities, listing collection and both sharpening payloads pass the
  deployed OpenAPI schemas. The live contract equals the packaged contract.
- Deployment exposed a PHP empty-object serialization issue in two session schema
  definitions. Fixed by retaining nested JSON objects; the standalone regression
  `php -n tests/openapi-serialization.php` passes, along with PHP syntax validation.
  The corrected Api.php read back from production equals the local packaged source.
- Homepage and both canonical sharpening pages: HTTP 200, no critical-error text.
- Prior settings preserved. The final plugin remains active, version 0.2.0.

These are discovery/read-only smoke checks, not proof of payment, stock settlement,
seller notifications, Google Pay, Lightning, seller transfers or refund behavior.
The earlier isolated synthetic test matrix remains documented separately.

## Buyer experience implemented, pending enablement

An authorized bot prepares a private intent and may prefill the buyer's authorized
address/email. The human opens the first-party review link, reviews seller terms,
items, fees and total, and explicitly continues to native WooCommerce checkout.
The buyer confirms payment through the existing gateway. The adapter observes the
native order outcome; it never independently charges or marks a redirect as paid.
A paid sharpening order does not reserve an appointment.

## Remaining launch conditions

Native agent links remain unavailable until actual shipping/tax/fee parity and a
real gateway payment/refund are verified. The owner has no hosted staging site;
production Stripe has not been switched to test mode. The implementation guide
prohibits tests against the production WordPress database. Obtain an explicitly
agreed test arrangement before any production test-mode change; do not treat this
read-only deployment as payment acceptance evidence.

Sharpening also requires published cancellation/refund terms, pickup/delivery
coverage, service capacity and verified native transport charges. The owner's fee
is **$7.99 per merchant trip** ($15.98 for two merchant trips); customer-provided
legs avoid their corresponding merchant-trip fee. Recorded hours are Friday and
Saturday 9am–7pm, Sunday 10am–4pm, America/Los_Angeles. These are business inputs,
not evidence that WooCommerce already applies those fees or books those windows.
Product ownership remains with the existing seller; no privileged bypass is added.

## Rollback and provenance

Before updating, all 16 source files available in the production plugin editor and
its private settings were backed up under the ignored local runtime directory.
A rollback ZIP contains the actual production 0.1.3 code plus its prior license,
with a per-file hash manifest. This was a code replacement, with no database schema
migration. A provider database backup was unavailable under the current plan;
no backup-plan purchase or database restore was performed.

The final package and manifest correspond to source in the existing review branch.
There is no new GitHub release, ClawHub publication or security-audit claim. The
published portable skill remains 0.1.2; the 0.2.0 skill is a review artifact.
See the accompanying [sanitized results](listing-checkout-deployment-2026-10-08.json).
