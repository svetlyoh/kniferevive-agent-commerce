# Changelog

## Skill and guide 0.5.5 — October 9, 2026

- Made the four customer choices explicit in the skill entrypoint and explained why three internal API mode identifiers represent four handoff choices.
- Corrected stale introductory AI.md guidance that still described three modes and $7.99 transport; new-request pickup is $6 and combined pickup/delivery is $11 total.
- Clarified that a hosting challenge is not proof that prepayment or the fourth choice is disabled. Booking, payment, authorization and refund behavior are unchanged.

## GitHub skill source 0.5.4 — October 9, 2026

- Corrected the skill metadata left at 0.5.1 while the backend advanced to 0.5.4.
- Documented four booking choices, ZIP checks only for prepaid choices, $6 pickup and $11 combined transport on new requests, awaiting-payment merchant submission and separate booking checkout sessions.
- Updated current-source installation links and distinguished GitHub imports from the unchanged ClawHub 0.4.2 publication. This source update claims no new registry audit and does not change payment authorization or backend settings.

## Skill 0.4.2 — October 8, 2026

- Updated the portable skill for consent-scoped unpaid booking receipts and native seller-order facts, keeping request, confirmation and payment states separate.
- Clarified daily capacity as jobs rather than knife count and added the direct human booking fallback for unavailable/challenged APIs.
- Updated versioned installation references and removed duplicate stale candidate notices. Merchant paid gates and wallet authority are unchanged.
- Registry publication and exact-version audit are tracked in `docs/skill-publication-0.4.2.md`.

## Candidate 0.2.0 native listing handoff — October 8, 2026

- Added all-category published listings, private expiring/idempotent intents, canonical native WC quotes and CSRF-protected buyer review into existing checkout.
- Preserved browser carts and native Dokan/Connect hooks; bound classic/Store API orders, guarded retries and added scoped payment/fulfillment/scheduling status.
- Added disabled-by-default handoff/pricing/live gates, narrow service fulfillment approvals, admin-only test/live diagnostics and recovery of an existing interrupted order.
- Included assigned native return terms in buyer review/quote binding and pruned expired abandoned quote context without deleting financial evidence.
- Updated skill source, listing reference, API contract 1.1.0, AI.md and operator audit/runbook/checklist. No live activation, ClawHub publication or new processor session path.
- Fenced behavioral/browser tests and actual seller/fee plugin comparisons; real processor and production-version evidence remain separate launch gates.

## Skill 0.1.2 installation documentation — October 8, 2026

- Added Linux terminal and Windows PowerShell OpenClaw installation/readiness instructions, shared installation and WSL guidance.
- Added separate Meta Muse personal-agent, Grok Bot private-skill and OpenAI dot adoption prompts, with a clearly separate Muse Code local-skill alternative.
- Linked human-facing installation guidance from SKILL.md and README; distinguished native OpenClaw installation from ClawHub CLI downloads and registry-generated Skill Cards.
- Shopping, quoting, consent, payment, booking and merchant backend behavior unchanged. No native installation/adoption on third-party platforms is claimed.

## 0.1.3 per-trip correction — October 7, 2026

- Corrected merchant courier price to $7.99 for each merchant trip, $15.98 for pickup plus return delivery, and $0 courier fee for customer drop-off plus collection, before applicable tax.
- Updated administrator instructions and merchant setup records. Combined total is staged as 1598 cents; courier transport and agent payments remain disabled pending operational facts.
- Five additional pricing assertions verify both fee lines, either one-way trip and summation without an override (91 assertions per WooCommerce storage mode).

## 0.1.2 courier pricing — October 7, 2026

- Optional exact combined pickup/return delivery total, with deterministic integer-cent allocation. The then-confirmed merchant price was $7.99 combined and $4.00 for one trip; this was superseded by the owner's per-trip correction in 0.1.3.
- Validation rejects incomplete courier configuration, incompatible tax treatment, and combined prices exceeding separate trips. Null preserves existing per-leg pricing; a confirmed price can be staged without enabling courier transport.
- Eleven added behavioral assertions pass in each WooCommerce storage mode (86 per mode). Address verification and payment enablement gates remain intact.

## 0.1.0 — October 7, 2026

Initial KnifeRevive Concierge storefront skill and merchant adapter candidate.

- Anonymous Annex technology discovery and structured sharpening guidance.
- Quote-bound purchase approval, independent intake/return reservations, private status and controlled payment reconciliation.
- Stripe hosted Checkout with conditional Google Pay and integration with the existing Lightning coordinator.
- OpenAPI/AI.md guides, sandbox tests, release manifests and operations/security instructions.

Merchant deployment/configuration, real payment verification and operational coverage remain enablement gates. The skill falls back to the merchant pages when the API is unavailable or capabilities are disabled. Registry publication is not proof of live booking/payment availability or a security certification.
## 0.1.1 merchant onboarding — October 7, 2026

- Explicit, disabled-by-default reuse of the official WooCommerce Stripe keys, separated by test/live mode.
- Administrator-only registration of a dedicated test webhook. Signing secrets are encrypted with AES-256-GCM using a key derived from WordPress salts; they never appear in the skill, public API, or configuration JSON.
- Registration retains its original idempotency key after uncertainty and requires manual review after credential changes or expiry. Setup never enables live payments.
- Sixteen merchant setup assertions and the existing 75 assertions in each WooCommerce order storage mode pass with synthetic processors.
