# KnifeRevive Returns 0.1.0-rc2

Coordinated development candidate — not production-ready. See the October 28 implementation report under `KnifeRevive_Documentation/buyer-returns-architecture-2026-10-28`.

Requires coordinated Seller Orders 1.7.0-rc1, ShipKit 0.6.3-rc1, Connect 0.7.2-rc1, My Money 1.2.1-rc1, WooCommerce and Dokan Lite. Dokan Pro is not required. New requests, label issuance and refund execution default OFF; no live financial approval is granted by activation.

Cases retain existing Seller Orders IDs/tables and audit. ShipKit remains the only label/ledger/private-document authority; Woo remains the only original-payment refund executor. Money Moves remains read-only. Legacy transitions and native refund bypasses are fenced for v2 cases even when this workspace is unavailable.

Real original-gateway reconciliation, full competing-writer proof, canonical parent partial-refund entitlement handling and a provider/device/email pilot remain launch blockers. Do not fabricate launch-proof records, clear refund claims to retry, reuse existing postage/Lifeline budgets, or bypass upstream Woo label permissions.

Use feature switches rather than removing the coordinator while cases/labels/claims remain open. Existing private labels/evidence are not public Media Library attachments. Back up the matching full database/key records before any eventual production migration.

RC2 defers component loading until ShipKit interfaces are declared, including activation and missing-dependency handling. An early-load WordPress fixture and separate load-order regression cover this integration.
