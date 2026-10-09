# Operations and recovery

## Enablement

Activate locally/staging and configure WooCommerce → Agent Commerce. Keep live acceptance disabled until actual service rules and processor tests pass. The settings JSON explicitly exposes `pricing_verified`, `live_verified`, per-rail enablement, and the operating facts. Missing facts return `unconfigured`/an explicit error; they do not create working-looking slots.

Stripe credentials are server constants named in README. Existing Lightning credentials belong to that gateway, and are not copied by this plugin. Keep the WooCommerce Stripe customer gateway and Connect seller accounting in their existing mode. This adapter's distinct gateway handles only operator-owned service charges and refunds.

Courier enablement additionally requires an authorized merchant integration attached to `krev_agent_address_verified`. It receives `(false, normalized_address, leg_kind)` and must return exactly `true` only for an address actually verified against the merchant's transport rules. Do not replace it with an unconditional true callback. It must be deterministic/auditable and its rule changes must invalidate/reprice affected quotes. No external geocoding/address credentials are embedded in this release.

## Reconciliation

Use WP-Cron with a reliable hosting scheduler. Each minute selects at most 30 due attempts. Transient failures use increasing retry intervals up to one hour and pause after eight failures. Explicit review failures pause automatically. Successful paid/refunded attempts are checked less frequently; verified webhooks and shopper status calls can reconcile sooner. An active site's cron is a reliability requirement; frontend redirects are insufficient.

The admin page lists retained attempts/change requests and links to the WooCommerce order. Its **Reconcile original attempt** action resets the automatic retry pause and checks the existing record. It never allocates a new attempt or changes its payment rail.

For a provider timeout, check the original request using its saved idempotency key and session/invoice reference. Never issue a fresh invoice/session or switch rails merely because the caller did not receive a response. Stripe create replay is refused after the hold window or 23 hours, conservatively within provider retention. Interrupted WooCommerce order creation searches its durable attempt metadata; missing/ambiguous results need operator review instead of blind recreation.

For a paid order with an expired appointment hold, contact the customer through the authorized merchant process and offer an appropriate replacement slot or refund. Do not give the late payer a slot allocated to another customer. Use existing sharpening stages and operator history; this API cannot modify them.

For a refund timeout, resolve the retained refund request before another refund. Stripe verified charge/refund evidence is reconciled into WooCommerce without initiating a second processor refund. Full refunds cancel allocation; partial refunds and disputes require operator review. Lightning refunds are handled manually through the established gateway/operator procedure with recipient authorization and exchange-rate terms.

## Disablement and rollback

Turn `enabled`/rail flags off to reject new quotes/checkouts while the plugin continues reconciling existing accepted attempts. Do not fully deactivate while issued payments remain unresolved: deactivation clears this plugin's cron hook and HTTP callback. If rollback is required, keep a compatible reconciler/webhook endpoint running for prior attempts. Retain auxiliary rows, WooCommerce orders and native Lightning records. Restore code/configuration through the established deployment process; never delete records to fix a duplicate.

Operational policies, addresses, slot capacity and payment modes must be reviewed before production deployment. Changes to pricing/policies/configuration affect the quote hash and require new approval. Slots with active allocations cannot be moved. Removing a slot disables new sales while retaining old evidence.

## Reproduce tests on Windows

The included test bootstrap **never loads the actual WordPress wp-config.php**. It uses dedicated loopback MySQL port 11019, database `krev_agent_sandbox`, prefix `krev_sandbox_`, synthetic administrator/customer data, and blocked outbound HTTP/mail. It loads WordPress/WooCommerce code from a provided installed core directory. The fixture database may be reset only while those exact fences hold.

1. Start a separate locally initialized MySQL instance bound to `127.0.0.1:11019`, with its data directory under `.runtime`. This must not be the Local Sites customer's database.
2. Run PHP 8.2+ with mysqli, mbstring and openssl extensions: `tests/sandbox-bootstrap.php <WordPress-directory> install`, then `tests/integration.php <WordPress-directory>`. The latter includes two child processes racing a real InnoDB slot and real installed Lightning coordinator/client code with HTTP fixtures.
   To cover both order-storage implementations, run `tests/storage-mode.php <WordPress-directory> legacy` or `hpos`, then run integration checks in a fresh process. The helper uses native WooCommerce synchronization and refuses an incomplete switch. It only modifies the fenced synthetic database.
3. Install pinned `tests/requirements.txt` into `.runtime/python` and run `python tests/contract.py`. Run `python tools/build_contract.py` after API/doc edits.
4. Run the skill-creator `quick_validate.py` against `skills/kniferevive-concierge` and PHP syntax checks across plugin/test PHP files.
5. For browser tests, run `tests/ui-fixture.php <WordPress-directory>`, set `KREV_TEST_WP_ROOT` for the fixture PHP server, and serve `tests/ui-router.php` at loopback port 11080. Set `KREV_TEST_NODE_MODULES` to the installed Playwright modules directory and run `node tests/browser.cjs`. It uses headless Edge and blocks all non-local browser requests. It writes synthetic screenshots only under `.runtime`.
6. Stop these separately launched test processes after verification. Do not publish `.runtime` or expose the fixture router on public interfaces.

## Future publication

Run the exact artifact manifest/secret review, then push to the owner's explicitly requested GitHub owner/repository/visibility. Publish only the skill folder on ClawHub. Verify publisher login and current CLI help, inspect the actual version's audit and preserve its URL/findings. Pending/error is not a completed review. Update source/version/capability documentation after actual deployment. No engagement farming, unsolicited promotions, artificial installs or misleading audit badges are part of release.

## Native listing candidate 0.2.0

Use [the native listing runbook](listing-checkout-runbook-0.2.0.md) for the actual new JSON controls, separate live-mode gate, gateway diagnostics, existing-order recovery and staged rollback. Original operator-service retries/webhooks above are a different payment owner. Do not route native vendor payments through that adapter. Preserve all financial rows/ledgers after expiry or feature disablement.
