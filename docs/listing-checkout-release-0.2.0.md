# Native listing checkout candidate — evidence and release checklist

**8 October 2026. Source candidate only; not deployed or published.**
Adapter and skill versions are **0.2.0**; generated OpenAPI version is **1.1.0**,
capabilities schema **1.1**. Installed local/production adapter remains **0.1.3**;
the published ClawHub skill remains **0.1.2**. New listing flags default off.
This implementation is ready for source review and isolated staging validation.
The brief's full real-payment definition of done has **not** been demonstrated.

## Deliverables and boundaries

- [Actual local/production audit](listing-checkout-audit-0.2.0.md): versions, charge
  owners, webhook destinations, seller ownership and enablement blockers.
- Source: `wordpress/kniferevive-agent-commerce/includes/{ListingCart,
  ListingCheckout,ListingFrontend,GatewayDiagnostics}.php`, existing API/settings
  wiring, isolated tests, generated OpenAPI/AI.md and skill references. Native
  WooCommerce, Dokan, Stripe and theme source were not edited or replaced.
- [Actual-control merchant runbook](listing-checkout-runbook-0.2.0.md): configuration,
  staging proof, original-order recovery and rollback.
- [Exact package manifest](release-manifest-0.2.0.json): archive and every bundled
  file's SHA-256, byte count and path. Archives are local under ignored `dist/`.
- [Redacted matrix results](listing-checkout-test-results-0.2.0.json): assertion
  counts, storage modes and synthetic processor boundary.

Exact local archives (SHA-256):

- `kniferevive-agent-commerce-0.2.0.zip`:
  `228da47df3df397c886420255d412fb72d1b33ef25974762c7fb1db2aa8522b5`
- `kniferevive-concierge-0.2.0.zip`:
  `06226ee509e96049c162fa0eaf4485a852af06090b9878320035b460692cda9e`

Only eligible simple products and a single seller per intent are supported.
Variations/add-ons and unreviewed sharpening fulfillment require the original
listing or merchant assistance. Quote-only operation creates no order or stock
reservation. Buyer review uses a private session and CSRF-protected acceptance;
native WooCommerce checkout creates the order and owns payment authorization.
The adapter adds no Stripe marketplace sessions, transfers or paid status writes.
Native order and gateway events control status. Paid sharpening is not a booking.

## Recorded checks

| Check | Result | Evidence boundary |
|---|---|---|
| Legacy orders, synchronization on/off | PASS — 75 listing assertions each | Fenced synthetic database, real installed plugin code |
| HPOS orders, synchronization on/off | PASS — 75 listing assertions each | Same; native CRUD/order binding and recovery |
| Existing operator-service regressions | PASS — 91 assertions each, legacy/HPOS | Signed synthetic webhook duplicate/delay/tamper/refund tests; HTTP provider fixtures |
| Existing merchant Stripe setup | PASS — 16 assertions | Every processor response is a fixture |
| Native Dokan seller accounting comparison | PASS in listing matrix | Native unpaid agent/ordinary orders match gross, commission/net allocation; no settled transfer |
| Quantity-one concurrency | PASS in listing matrix | Two PHP processes use native WC stock reservation; at most one reservation; no pair of real settled payments tested |
| Native fee/shipping/coupon/tax checks | PASS in listing matrix | Real Conditional Fees calculator, native coupon/shipping rules and synthetic WC tax rate; automated production tax not tested |
| Assigned return policies | PASS in listing matrix | Actual plugin terms displayed, changed terms reject handoff, native order snapshot retained |
| Personal-data retention | PASS in listing matrix | Expired abandoned review/quote context pruned; linked and interrupted financial evidence retained |
| Mobile/desktop listing review and native checkout browser | PASS | Headless Edge; actual WC classic checkout form in loopback router; no Pay action or external request |
| Existing operator-service browser | PASS | Synthetic unpaid processor handoff and private session only |
| Generated API payload/schema, AI.md and packaged copies | PASS | Actual synthetic API samples plus copy parity |
| PHP syntax and skill source validator | PASS | Syntax/frontmatter only, not a ClawHub security verdict |
| Real native Stripe test-mode purchase and signed processor replay | NOT RUN | Isolated staging account/webhook setup required |
| Real vendor transfer, refund and dispute outcome | NOT RUN | Connect test credentials absent in production UI; transfers paused, two allocations need attention |
| Production-version staging and actual Checkout Block/theme/browser | NOT RUN | Production Woo/Stripe/Dokan/Seller Orders/tax versions differ |
| Actual small/large sharpening purchase and logistics booking | NOT RUN | Seller, fulfillment, coverage, refund and capacity gates remain |
| Device-eligible Google Pay and actual Lightning settlement | NOT RUN | No wallet or provider payment initiated |
| Candidate ClawHub security audit/card provenance | NOT RUN | Prior version's audit does not certify 0.2.0 |
| Production activation / ClawHub or GitHub release | NOT RUN | No installation, tag, release or registry publication in this task |

The loaded listing test stack uses WordPress **7.1.2**, WooCommerce **11.0.1**,
PHP **8.2.29**, MySQL **8.4.0**, WC Stripe **10.9.0**, Dokan Lite **5.1.2**,
Dokan Pro **4.0.6**, custom Connect **0.1.6**, Seller Orders **1.2.0**, Seller
Commissions **0.2.0**, Return Policies **1.0.0** and Conditional Fees **1.1.67**.
The actual Stripe extension loads, but its payment gateway is replaced by a
synthetic test gateway with the same ID. No test calls its real `process_payment`.
Mail, cron and external HTTP are blocked. Dokan's native schema builders install
only fenced sandbox tables, without module activation or telemetry opt-in.
Native Lightning **0.1.13** is exercised through existing HTTP fixture tests.
These checks do not certify the full production plugin stack or seller settlement.

## Reproduce the commands

From the repository, first start the separate loopback MySQL server described in
[operations](operations-and-recovery.md#reproduce-tests-on-windows). It must use
port **11019**, database **krev_agent_sandbox**, prefix **krev_sandbox_**, and an
ignored `.runtime` data directory. Bootstrap never loads the real wp-config.php.

```powershell
$taskPhp = 'C:\Users\Svet\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe'
$taskWp = 'C:\Users\Svet\Local Sites\kniferevive\app\public'
$taskPython = 'C:\Users\Svet\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe'
$taskNode = 'C:\Users\Svet\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe'
$taskExt = (Join-Path (Split-Path $taskPhp) 'ext').Replace('\','/')
$taskPhpArgs = @('-n','-d',"extension_dir=$taskExt",'-d','extension=mysqli','-d','extension=mbstring','-d','extension=openssl','-d','extension=curl','-d','memory_limit=512M')
& $taskPython tools/build_contract.py
powershell -NoProfile -File tests/run-listing.ps1 -PhpPath $taskPhp -WordPressRoot $taskWp
& $taskPython tests/contract.py
& $taskPython 'C:\Users\Svet\.codex\skills\.system\skill-creator\scripts\quick_validate.py' skills/kniferevive-concierge
Get-ChildItem wordpress,tests -Recurse -Filter '*.php' | ForEach-Object { & $taskPhp -n -l $_.FullName; if ($LASTEXITCODE -ne 0) { throw 'PHP syntax failed' } }
git diff --check
```

`run-listing.ps1` runs storage modes serially, fails on a nonzero exit **or** missing
PASS marker, and writes ignored `.runtime/listing-acceptance-results.json` and logs.
It includes the existing signed synthetic webhook and merchant-setup regressions.
The API contract tests use actual sandbox payloads, not hand-written success JSON.

Listing browser setup (after the matrix):

```powershell
$env:KREV_LISTING_TEST_STACK = '1'
& $taskPhp @taskPhpArgs tests/listing-ui-fixture.php $taskWp
$env:KREV_LISTING_UI = '1'
$env:KREV_TEST_WP_ROOT = $taskWp
$env:KREV_TEST_NODE_MODULES = 'C:\Users\Svet\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\node_modules'
$taskServer = Start-Process -FilePath $taskPhp -ArgumentList (@($taskPhpArgs) + @('-S','127.0.0.1:11080','tests/ui-router.php')) -WorkingDirectory (Get-Location).Path -WindowStyle Hidden -RedirectStandardOutput '.runtime/listing-ui-server.out.log' -RedirectStandardError '.runtime/listing-ui-server.err.log' -PassThru
& $taskNode tests/listing-browser.cjs
Stop-Process -Id $taskServer.Id
Remove-Item Env:KREV_LISTING_UI
Remove-Item Env:KREV_LISTING_TEST_STACK
```

To check the old operator browser: with the listing server stopped and listing
environment variables removed, run `tests/integration.php` to recreate its service
fixtures, then `tests/ui-fixture.php` using the same PHP
arguments/root, start the same loopback router without `KREV_LISTING_UI`, and run
`node tests/browser.cjs`. Stop that separate process. Do not parallelize database
fixtures/modes. Browser screenshots remain private under `.runtime`.

Build only after validation: `python tools/package.py`. Capture the resulting
`dist/release-manifest.json` as `docs/release-manifest-0.2.0.json`. Any bundled
source change requires new archives and a refreshed manifest. Review source pushes
are separate from release tags/registry publication and production activation.

## Release conditions

- [x] Inspect installed/local and production versions, charge owners, actual
  settings and service authors without copying secrets.
- [x] Keep defaults off, operator-service payment code unchanged, no seller role
  or product-owner changes, no native plugin/theme edits.
- [x] Validate isolated matrix, old regressions, contracts, browser and exact
  candidate artifacts; publish source only for review.
- [ ] Prepare isolated staging with production plugin versions, test account and
  vendor test accounts, staging-only webhook destination and suppressed customer mail.
- [ ] Confirm ordinary native checkout first, then equivalent agent checkout.
  Verify a real test payment, native stock/order, seller ledger/commission, transfer,
  refund and provider signed duplicate/delayed delivery. Complete the settled
  quantity-one race. Record redacted payment proof, never credentials/PII.
- [ ] Verify native Checkout Block/theme, automated tax, shipping/custom pricing,
  notifications, vendor views, original-order retry, disablement and refunds.
- [ ] Resolve Connect test configuration and allocation review through its owner;
  do not enable transfers or retry live allocations merely to test the adapter.
- [ ] Verify both real sharpening listings' current terms and native charges,
  including **$7.99 per merchant-operated trip** if applicable. Approve only those
  individually verified service IDs; appointment capacity/confirmation stays separate.
- [ ] Restore reliable authorized agent access to public capabilities without
  bypassing browser challenges or exposing admin authentication.
- [ ] Take fresh production code/settings/provider database backups, obtain owner
  deployment authorization and separately verify live gateway/webhook configuration.
  Enable gradually through the real controls in the runbook.
- [ ] Publish the exact tested skill only when authorized, inspect its own ClawHub
  audit/card/provenance result, record URL/hash and actual capability state. No
  artificial installs, background shopping or unsupported audit claims.

## Rollback

Set `listing_handoff_enabled=false` first. Keep status/order guards while original
native orders reconcile; preserve gateway webhooks, seller allocation/refund hooks
and the separate service reconciler. Do not delete auxiliary financial rows,
idempotency, WC orders or Connect records. After all outstanding intents/orders are
resolved, restore the prior plugin/configuration backup. Never restore a stale
database over subsequent customer purchases. The [runbook](listing-checkout-runbook-0.2.0.md#safe-recovery-and-rollback)
contains the administrator-only original-order binding recovery method.

## Reusable capabilities announcement

KnifeRevive Concierge discovers published KnifeRevive listings and SF Bay Area
sharpening services. Merchant backend 0.2.0 is deployed: anonymous listing discovery,
individual sharpening products and contract 1.1.0 are accessible. Private native
checkout preparation is implemented but disabled pending real gateway payment/refund
verification, native pricing and service fulfillment approval. The published skill
is still 0.1.2; skill 0.2.0 has no new ClawHub audit or publication. Buyers authorize
payment through the existing checkout, and payment never confirms an appointment.
Google Pay and Lightning require verified support from the enabled native gateways.

Deployment verification: [8 October 2026](listing-checkout-deployment-2026-10-08.md).
