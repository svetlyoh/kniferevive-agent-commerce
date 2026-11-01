# PR 0 — Returns source verification and rollout audit

**Observed:** 2026-10-31, America/Los_Angeles. **Scope:** PR 0 only. **Production writes:** none.

Instruction: [Returns implementation runbook](../CODEX_KNIFEREVIVE_RETURNS_IMPLEMENT_AND_DEPLOY_2026-10-31.md), GitHub blob `4283c5e3e87be42d6873774ce511f248bee8479a`.

The runbook says **“Begin with PR 0 ONLY”** and **“After PR 0 review, implement PR 1 through PR 8 sequentially.”** This PR provides an audit for that review. It does not implement seller decisions, appeals or a label/payment engine.

## Findings

1. The live component versions and Return Desk controls match the owner's October 31 audit. Requests, return labels and refunds remain OFF; return budget is zero and reviewed policy version is unconfigured.
2. The linked repository branch does not contain complete tracked runtime sources for Returns, ShipKit, Connect, My Money or the current Seller Orders. It contains an older Seller Orders overlay. The separate existing local checkout contains untracked older runtime copies; using it as the deployable source would risk downgrading production.
3. Version-matching local source candidates were located for Returns rc4, Return Policies 1.0.0, ShipKit 0.6.9-rc3, Connect 0.7.2-rc1 and My Money 1.2.2. These are **local release candidates for comparison**, not byte-for-byte verified production exports.
4. The complete **Seller Orders 1.7.0-rc3** tree is not located in the inspected local documentation/repository roots. The newest identified source copy is rc2. The current site's Plugin File Editor navigates to Code Snippets, so that UI did not provide a fresh source export. No security settings were weakened to obtain access.
5. No current complete database/private-vault backup or exact full-source rollback package was established. Existing release ZIPs are retained as references, not represented as a proven full recovery point.

## Phase acceptance

| PR 0 deliverable | Status |
|---|---|
| Read complete runbook and owner audit | Complete |
| Fresh installed-version inventory | Complete — `live-plugins.json` |
| Read live Return Desk controls without saving | Complete — `live-controls.json` |
| Source-of-truth matrix | Complete with explicit missing/unverified entries |
| Source architecture, routes, permissions, jobs, states | Mapped from identified sources; current Seller Orders internals require rc3 comparison |
| Staging and rollback plan | Prepared — `STAGING_AND_ROLLBACK.md` |
| Reproducible staging environment matching production | **Blocked**, not established; complete matching source export required |
| Exact production rollback artifact including DB/private vault | **Not established** |
| PR 1 implementation or production deployment | **Not started**, per review checkpoint |

## What is needed next

Obtain a read-only export of production `wp-content/plugins/kniferevive-seller-orders/` version **1.7.0-rc3**, plus complete current source exports for comparison and a recoverable database/private-vault backup. Use owner-authorized hosting SFTP/SSH or a hosting backup download. Do not deploy a diagnostic plugin or change site security just to get the files during this audit. Secrets belong in private storage, never this PR.

After source recovery, compare all runtime files/hashes, import the verified plugin baseline into its intended source repository, then build the isolated rehearsal described in the staging plan. Review PR 0 before starting PR 1. Later financial launch also requires the separate policy/funding/provider approvals listed in `DECISIONS_AND_GAPS.md`.

## Verification performed in this phase

- Read-only WordPress.com plugin inventory and live Return Desk UI.
- Git tracked-tree/local-dirty-state inspection; original dirty checkout preserved.
- Local source header and SHA-256 inventory; no production-source equivalence inferred from matching version numbers.
- Public REST index and permission-denial probes are recorded separately when available. They are not a substitute for authenticated workflow tests.
- No runtime behavior changes, migrations, financial/provider tests or email delivery tests were performed. Historical passing suites are not claimed as newly executed.

See [source matrix](SOURCE_MATRIX.md), [architecture](ARCHITECTURE.md), [decisions/gaps](DECISIONS_AND_GAPS.md), and [staging/recovery plan](STAGING_AND_ROLLBACK.md).
