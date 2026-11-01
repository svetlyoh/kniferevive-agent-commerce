# Policy decisions and capability matrix

These are proposed future decisions, not new policy or financial authorizations. The original document explicitly requires PR 0 review before implementation and separate sign-off before live financial features.

| Decision | Recommended record for later review | Current |
|---|---|---|
| New-purchase clock | Versioned 15 calendar days from verified delivery; documented pickup/untracked fallback; historical unknowns require review | Unconfigured; no historical backfill |
| 30-day options | Separate prospective release only | Four existing terms remain |
| Free/fault returns | Buyer $0; identify an approved real postage funding source | Return budget $0; no allocation approved here |
| $5/$10 remorse | Contribution no greater than purchased cap, actual accepted quote or exact accepted checkout total; no hidden netting | Buyer-paid flow not live |
| Postage shortfall | Explicit seller/platform responsibility and funded capacity before purchase | No use of unrelated $100 postage or $50 Lifeline budgets |
| Damage/unsafe/authenticity | Buyer-safe review; no automatic seller debit; no ordinary unsafe-item label | Protected review retained |
| Seller final decisions | Routine known-policy cases only; admin override/high-risk exceptions | Seller response only |
| Formal appeals | Both parties; one open appeal per decision revision; proposed 7-day window with support discretion | Help only, no formal appeal |
| Refund scope | Original paid amounts/tax, reviewed shared shipping/fees/promotions/partial-parent allocation | Execution OFF |
| Provider | Evaluate Shippo/EasyPost sandbox against existing Woo-assisted authority later | No provider changed or registered |
| Payout holds | Narrow, approved seller-at-sale protection only | No new holds enabled |
| Evidence retention | Safe audit without unnecessary raw location metadata | Existing private processing retained |

## Truthful capability matrix

| Capability | Source exists | Deployed | Full staging E2E proved in PR 0 | Enabled live |
|---|---|---|---|---|
| Purchased policy snapshots | Yes | Yes | No | Capture exists; no new terms installed |
| New return request | Yes | Yes | No | **OFF** |
| Seller agree/alternative/review | Yes | Yes | No | Existing-case permissions only; no new request launch |
| Seller final approve/decline | No in current coordinator | No | No | No |
| Formal two-sided appeals | No | No | No | No |
| Private messages/evidence | Yes | Yes | No | Existing authorized-case paths; not delivery/permission certified by this audit |
| Existing label PDF reprint | Yes | Yes | No | Ready-document authority only; issuance OFF |
| Free/paid return label issuance | Partial assisted orchestration | Installed disabled | No | **OFF** |
| Buyer contribution payment | Incomplete October 29 candidate | Not installed as completed buyer flow | No | No |
| Tracking/inspection | Orchestration exists | Yes | No | Not an automatic refund trigger |
| Refund execution | Guarded orchestration, incomplete integrations | Yes disabled | No | **OFF** |
| Notification outbox | Yes | Yes | No new delivery test | Delivery/retry not proved |

## Hard engineering gaps

1. Recover complete Seller Orders rc3 and verify all current production files against candidate releases.
2. Establish a complete restorable database/private-vault/code baseline and matching isolated environment.
3. Add seller decision commands with ownership, sold-policy, quantity, risk, revision and prior-financial checks.
4. Add formal decision-linked appeals, response/admin queues and transactional notifications without erasing existing records.
5. Prove provider quote/purchase/retrieve/void/tracking behavior and private-file HTTP access; quote refresh must make a real request and expose clear failure state.
6. Finish shipment grouping, contribution orders, shortfall reserves, fee-only payment isolation and failure/refund reconciliation.
7. Finish canonical seller entitlement changes and actual installed-gateway lookup/reconciliation under replay/concurrency.
8. Exercise buyer/seller/recipient/guest/admin E2E, accessibility and actual email delivery with controlled test identities before launch.

No estimated completion date or passing provider test is invented by this inventory.
