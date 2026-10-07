# Changelog

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
