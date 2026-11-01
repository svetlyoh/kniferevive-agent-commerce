# Existing authorities and gaps

This map comes from the version-matching local Returns/ShipKit candidates and identified Seller Orders rc2 source. It is not a production SQL dump or proof that rc2 equals the live rc3 build.

## Data ownership

| Data | Authority | Inspected storage |
|---|---|---|
| Purchased policy | Return Policies + Returns Policy | `kr_return_policy` taxonomy; immutable `_kr_return_policy_snapshot` and `_kr_return_policy_v2` on Woo line items |
| Order, seller-at-sale, refund units | Woo CRUD, Seller Orders and parent-order mapping | HPOS-compatible order APIs; original charged parent and child line mapping |
| Return case | Seller Orders `KREV_Returns` | `{prefix}krev_returns` |
| Case state/timeline/revision/idempotency | Seller Orders `KREV_Return_Workflow`, Returns `Store` | `{prefix}krev_return_details`, versioned JSON `v2`; original IDs preserved |
| Notifications | `KREV_Return_Mail`, Returns `Jobs` | `{prefix}krev_return_outbox`; recipient/event/revision dedupe |
| Label attempts/private files/postage | ShipKit Application/Labels/Documents | `{prefix}sk_{test|live}_shipkit_records`, `_shipkit_journals`, `_shipkit_lines`; encrypted/private vault outside public media access |
| Original-payment refund | Returns Refunds + existing Seller Orders refund authority + Woo gateway | Parent payment reservation and provider reconciliation, not a second refund worker |
| Seller recovery/IOU | Connect + ShipKit ledgers | Existing settlement/recovery authorities; no unauthorized automatic deduction |
| Seller money display | My Money projections | Read-only case cost/IOU rows; do not add descriptive rows to sales totals |

Returns does not introduce a second case table. It extends the existing workflow/detail record and uses charged-parent locking plus transactional mutations. Exact production table names, engines and schema columns still require read-only DB verification after source recovery; no database inventory query was executed against production in PR 0.

## Existing HTTP contracts

Live public REST index confirms namespace `kniferevive-returns/v1`:

| Route | Methods | Authority / purpose |
|---|---|---|
| `/guest/request` | POST | Secure guest access email request; do not invoke merely to test on production |
| `/guest/exchange` | POST | One-use guest grant exchange |
| `/orders/{id}/cases` | GET, POST | Purchased-order projection and request creation |
| `/cases/{id}` | GET | Authorized buyer/seller/recipient/admin case projection |
| `/cases/{id}/actions/{action}` | POST | Existing action dispatcher; role, revision and request-key checks |
| `/cases/{id}/files/{file}` | GET | Role-checked private evidence/PDF response |

Anonymous GET `/cases/0` returned **401 `return_auth`**. This verifies one anonymous-denial path only, not every ownership or download permission. See `http-observations.json`.

Private case UI: `/?kr_return_case=ID`; order workspace: `/?kr_returns_order=ID`; admin: WooCommerce → Return Desk. IDs, REST nonces and download paths do not confer object ownership.

`Access::buyer` checks the purchasing customer on the original order or verified guest authority. `Access::view` distinguishes admin, buyer, seller at sale and explicitly assigned recipient. Admin authority uses `manage_woocommerce` or `krev_returns_manage`. Ordinary case help/messages are shared; admin-private notes/evidence require admin access.

## Existing state contract

Current case progression includes `seller_response_due`, `admin_review`, `approved`, `declined`, `withdrawn`, `resolved`. Legacy projections also map old `requested`, `rejected`, `closed`, `escalated`, `refunded` records without fabricating historical provider evidence. `label`, `shipment`, `inspection`, `refund`, `postage` and `deadlines` remain separate subrecords.

Current seller response choices are `agree`, `alternative`, `review`, followed by admin review. Final `review` is admin-only. No seller final approval/decline command or formal appeal record/command exists in the inspected current source.

Reasons remain `mismatch`, `wrong_item`, `damage`, `change_of_mind`, `unsafe`, `authenticity`, `missing`, `cancel`, `other`. Unsafe/authenticity claims cannot receive ordinary physical return approval automatically. Missing/cancellation/nonphysical or unpaid cases use order-help routing.

Upcoming appeals must be an overlay tied to a specific immutable decision revision, not a replacement state machine or parallel case store. An appeal cannot undo an already confirmed refund or carrier purchase.

## Jobs and outbox

- Returns registers hourly `kr_returns_deadlines`, with cursor-bounded attempts/case review. Targets cause escalation/reminders, not automatic denial, refund, penalty or loss of purchased rights.
- Identified Seller Orders source registers hourly `krev_return_mail_sweep` and per-event `krev_return_mail` jobs. A sweep schedules up to 100 queued records with fewer than three attempts. Failed sends retry after 120 seconds, up to the attempt limit; exceptions route to review. **Confirm this against recovered rc3 before changing the worker.**
- `wp_mail` success records `sent-by-server`, not recipient delivery. Actual delivery/retry evidence remains missing. No test email was sent in PR 0.
- ShipKit registers `krev_shipkit_tick` on a five-minute schedule. Its existing purchase/reconciliation work must not be duplicated by a Returns worker.
- WP-Cron/Action Scheduler actual live queue contents, worker execution health and scheduler traffic were not exported. Registration in source is not proof of execution.

## Load order and permanent fences

ShipKit loads at `plugins_loaded` priority 99. Returns uses its guarded later coordinated loader; activation/upgrade must preserve dependency checks. The rc4 Returns checkout adapter accepts Woo's native four arguments; rolling back to rc3 would restore a known checkout TypeError.

Persistent Seller Orders/Connect refund fences and private-vault integration must remain active even if the Returns workspace is paused. No single-plugin downgrade is an acceptable substitute for a coordinated compatibility review.

## Evidence privacy issue to resolve

The runbook requests preservation of original photo metadata/audit, while the existing implementation processes private images and strips identifying metadata. Preserve upload time, actor, safe content hash and processing audit; do not silently start retaining raw EXIF/location or originals. Any additional retention requires an explicit privacy/data-retention decision before rollout.
