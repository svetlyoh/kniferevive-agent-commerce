# Isolated rehearsal and recovery plan

**Status:** Plan prepared; production-matching staging is **not established** because complete current source/recovery material is unavailable. Do not run an old fixture suite and call it production parity.

## A. Recovery prerequisites

1. Read-only export the full active plugin directories from hosting, especially Seller Orders **1.7.0-rc3**, and their matching theme/must-use adapters. Record relative filenames and SHA-256 hashes. Do not export secrets to GitHub.
2. Obtain a consistent hosting database backup and the corresponding encrypted private vault/files and key references. Store them privately with a timestamp and documented recovery permissions. A code ZIP does not restore a label, reservation or refund record.
3. Capture current non-secret switch values; preserve credential identities and gateway mode separately without printing key values.
4. Restore to an isolated environment and prove restoration before creating runtime deployment packages. The existing old local site and 2026-10-26 database export are not claimed as current recovery points.

## B. Reproducible sandbox boundary

- Dedicated local WordPress root and a new database prefix/name specific to this project. Bind any HTTP/MySQL services to loopback only; never load production `wp-config.php`.
- Match WordPress 7.1.4, WooCommerce 11.2.1, Dokan Lite 5.3.0, Woo Shipping 2.4.3 and all verified coordinated custom releases. Confirm PHP/extensions through current hosting health before matching the runtime. Do not infer PHP from local tooling.
- Disable real cron scheduling/traffic; invoke only identified workers deliberately against fixtures.
- Network deny by default. Test-only gateway/provider clients and keys must be positively bound to their sandbox accounts; stop if any live-mode credential or endpoint appears.
- Replace mail with a local capture sink. Assert every generated email stayed in the sink; use no real customer address, payment, inventory or order.
- Generate synthetic fixtures for 15-day Free/$5/$10 and No Returns, unknown anchors, threshold $150, phone exception, knife/spice/coin categories, multi-seller/partial-refund orders, separate recipients, legacy cases, private evidence and uncertain provider outcomes.
- Test both HPOS and legacy order storage. Reuse existing authority tests only after adjusting them to the verified source manifests; do not copy an older plugin tree wholesale.
- Private PDF/evidence storage gets a sandbox-only vault key and directory. No production key/data is needed for synthetic tests. A separately authorized sanitized restore rehearsal may be used for migration parity.

## C. Verification sequence after PR 0 review

1. Freeze and hash baseline sources, run load-order/checkout callback smoke, then existing native regression suites.
2. Verify current unconfigured/OFF settings do not accept a request, buy a label, issue a refund or add a payout hold.
3. Run buyer/guest/seller/recipient/admin permission and raw HTTP private-file tests with synthetic fixtures, including unrelated-user and stale-revision cases.
4. Implement PR 1 only after the review checkpoint. Preserve historical snapshots and unknowns; add no 30-day terms in this release.
5. PR 2/3: seller decisions and formal appeals behind independent OFF flags; race tests, exact decision linkage, email capture/retry and mobile review.
6. PR 4+: actual provider sandbox tests and fee-only payment tests only after account/provider/funding choices. Persist redacted evidence and exact API contracts. A fixture PDF is not proof of a provider-issued label.
7. No production pilot until matched-source parity, recovery proof, policy/funding sign-offs and all required staged tests pass.

## D. Rollback/reconciliation

For future runtime deployments, first pause new requests, return label issuance and refund execution individually; preserve all case, fee-only payment, label, provider, refund and ledger records. Reconcile uncertain external operations before retrying. Maintain private file access and pending-work recovery.

Rollback code only to a verified compatible full set. Do not restore an entire older database over newer paid/refunded records, delete claims, reverse successful refunds blindly, or downgrade the known rc4 checkout fix. Use forward-compatible migrations; schema changes need an explicit data-recovery plan.

## E. Current artifacts

This PR contains only documentation and redacted observations. It includes no executable release, no database export, no private-vault backup and no claim that a production rollback has been proven. Local historical release ZIPs remain available as comparison references. Runtime deployment is blocked until A is complete.
