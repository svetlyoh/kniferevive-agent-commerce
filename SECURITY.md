# Security reporting and release authority

Report vulnerabilities privately to the KnifeRevive repository owner through an enabled GitHub private vulnerability-reporting channel when that repository is published. Do not include customer data, tokens, wallet secrets or real payment credentials in a public issue.

This is a release candidate. See [publication status](docs/publication-status.md) for the actual version-specific ClawHub audit state. The skill is purpose-scoped and text-only, declares its optional existing-wallet boundary, uses known merchant/payment origins, and obtains explicit purchase authority. Review the exact artifact and runtime/backend controls before enablement. The scope and safety of a deployed merchant endpoint cannot be inferred from a Markdown package alone.

The merchant plugin uses scoped sessions, private responses, quote-bound consent, idempotent attempts/refunds, deterministic totals, atomic appointment holds, verified Stripe signatures and independently retrieved settlement, existing Lightning invoice binding, and controlled retries. Wallet and hosted checkout consent remain independent.

Live payment verification, actual Google Pay display, deployed gateway/plugin compatibility and merchant operational policies are required before live acceptance. A successful local test or skill-format validation is not a certification or processor approval.
