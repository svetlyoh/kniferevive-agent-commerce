# Prepaid sharpening deployment and implementation runbook

October 8, 2026. Agent Commerce **0.5.1** and the narrow Seller Orders **1.1.6** overlay are deployed on KnifeRevive. The owner approved production deployment, prepaid requests before merchant confirmation, the public sharpening policy, and seller cancellation/refund controls. No staging requirement applies.

## Merchant controls

Open [WooCommerce → Agent Commerce → Prepaid pickup ZIP codes](https://kniferevive.com/wp-admin/admin.php?page=krev-agent-commerce#pickup-zip-coverage).

1. Check **Use a custom pickup ZIP list**.
2. Enter eligible ZIPs, one per line or separated by commas.
3. Click **Save pickup ZIP codes**. Invalid, mixed-county or unsupported ZIPs are rejected without changing saved settings.

Custom coverage is currently **off**, retaining supported Contra Costa and Santa Clara county coverage. Turning it on limits both merchant pickup and merchant return trips to the saved list. An empty enabled list disables merchant trips. Turning it off restores county coverage. This editor narrows the approved counties; it does not expand into other counties. Removing pickup coverage leaves eligible customer drop-off prepayment available and retains existing orders for merchant review.

Daily capacity remains **4 jobs per open day**, managed in the same screen under Daily sharpening capacity. Hours remain Friday/Saturday 9 AM–7 PM and Sunday 10 AM–4 PM, Pacific. Small service product 1963 is $5; large service product 1964 is $7. Each merchant trip is $7.99. Native WooCommerce calculates the final tax and fees.

## Implemented pipeline

ZIP coverage → small/large quantities and handoff → contact/address sharing consent → one submitted request → capacity hold → exact native quote and policy consent → native WooCommerce checkout on the protected booking URL → original Stripe payment → merchant date/address confirmation → private payment/refund receipt and seller order.

The phone address panel stays open while typing. A submitted eligible prepaid request can proceed to payment before date/address confirmation. Payment does not confirm an appointment or claim street-address verification. A 30-minute checkout hold reserves one daily job under locks. Verified payment retains the allocation; confirmation reuses it. Settlement after hold expiry preserves payment evidence and requires capacity/refund review instead of reclaiming capacity or starting another charge.

The checkout uses original WooCommerce cart, session, gateway fields and order metadata. Wrong browser, changed quote, changed payment method and mismatched booking/order bindings fail closed. Payment hooks observe native results; they do not charge. Native Stripe keys, account and webhook ownership are preserved. New PHP hooks required no WooCommerce re-login.

## Cancellation, refund and accounting

Unpaid cancellation closes the original unpaid order. Paid cancellation preserves charge evidence and requires a separate refund action. The owning seller can review and approve a full remaining service/trip refund from the booking inbox. CSRF, seller/order/quote binding, fresh balance and line/tax allocation checks apply. A durable attempt exists before the gateway call. Uncertain outcomes require reconciliation of that attempt and cannot automatically retry the financial action.

Refunds call the original native WooCommerce gateway. A recorded manual refund is reported separately from a gateway-accepted refund; neither claims bank arrival. Private bot status includes original rail and accurate partial/full amounts. Events identify individual refund occurrences and omit contact/address details. Polling never moves money.

The installed Dokan Pro stack skips Lite's WooCommerce refund balance observer. The owner-delegated service full-refund action therefore invokes the same native vendor allocation and balance/order adjustment hooks after gateway acceptance. It blocks when an existing Dokan refund request is pending. Generic Dokan request/approval flows and seller capabilities are unchanged; this action does **not** pretend to run Pro's separate approval API. Partial/manual/uncertain native adjustments still require management reconciliation. Connect transfer/reversal handlers retain ownership; this deployment did not issue seller transfers or resolve previous allocation backlogs.

Published approved policy: [Sharpening cancellations and refunds](https://kniferevive.com/sharpening-cancellations-and-refunds/), version `sharpening-2026-10-08`. A request KnifeRevive cannot accept receives a full refund of all unperformed services and trips. Cancellation and refund are separate actions.

## Evidence and limits

- Full isolated acceptance matrix: 75 listing, 66 booking and 87 bridge assertions in each classic/HPOS storage mode, with synchronization on/off; operator and Stripe setup regressions passed.
- Final stable lifecycle source: **44 assertions in each of the four configurations**, including native Dokan balance adjustment exactly once and no second gateway call on refund replay.
- Actual sandbox payloads validated against 16 OpenAPI schemas. Packaged contracts/AI.md match source. PHP syntax, booking JavaScript syntax and whitespace checks passed.
- Phone viewport 390 × 844: ZIP-first form, address panel stays open and focused during sequential typing, no horizontal overflow. Synthetic native checkout on the same private booking URL displayed the bound $12.99 small-knife plus one-trip quote and preserved address. No Place order click occurred.
- Production native update reports success. Live BookingLifecycle.php and Settings.php read back exactly match packaged source after newline normalization. Live ZIP save succeeded; settings retain capacity 4, prepaid launch on and pay-before-confirmation on.
- Native Stripe Apple Pay/Google Pay Checkout placement is enabled and persists after reload. Supported-device transaction, 3DS, real Stripe webhook and original-method refund settlement are **not verified by synthetic tests**.
- Native Stripe remains in its existing live mode. No real charge/refund was initiated, no production mode window was opened, no new production customer/appointment was created. Historical unpaid order 2980 was not repurposed.
- Lightning bot-wallet readiness remains false; this release does not claim authorized wallet payment/refund support is verified.
- Anonymous GET to `/wp-json/kniferevive-agent/v1/booking-options` still receives HTTP 403 with the host's JavaScript browser challenge before WordPress. The browser booking form works. Headless bot access is therefore **not verified and remains blocked from this PC**.
- ClawHub's published skill remains **0.4.2**. Local skill 0.5.1 is an unpublished candidate; earlier security scans do not audit this candidate.

## Next implementation/check sequence

1. Preserve settings/source backups and current payment evidence. Reload admin after PHP deployment; reconnect only if an actual login/account error appears.
2. Have the hosting administrator exclude the intended machine-readable Agent Commerce routes from the interactive JavaScript challenge while retaining their application authentication, rate limits and normal protections. Start by inspecting the public booking-options route and the exact protected routes an actual bot needs. Do not disable the site's firewall globally or use a browser-challenge workaround in the skill.
3. From the actual bot host, verify options, both services, custom ZIP eligibility and authenticated draft/request/private status. Test disabled ZIPs and other-seller access. Publish capability claims only after that host succeeds.
4. For processor evidence, use an owner-attended short native Stripe test-mode window on production, recording original mode and restoring it immediately on completion/failure. Do not leave test mode enabled while awaiting input. Use provider test fields; real money requires a concrete approved cap and purpose. Do not refund unrelated customer orders.
5. Verify one marked small-knife pickup request: native taxes/fees, one order, seller Local Pickup visibility, authentic payment event, full original-method refund, vendor balance and customer/bot receipt. Test Google Pay on a supported device independently. Keep gateway acceptance distinct from bank arrival.
6. Reconcile original uncertain refunds/accounting before any new action. Preserve generic Dokan approvals and native Connect handlers. Add no second refund consumer, broad seller permission or arbitrary bot payout address.
7. Once live bot/processor evidence exists, publish a versioned skill to ClawHub, inspect its exact-version audit and source hashes, and record missing card/provenance separately. Do not reuse an old audit or merge the draft PR automatically.

## Rollback

Disable new prepaid launch and pay-before-confirmation settings first, retaining all original order/payment/refund records. Restore the retained exact previous Agent Commerce ZIP/settings using native WordPress upload only if needed. Seller Orders has an exact 1.1.5 rollback package; do not substitute the unrelated local 1.2.0 source. Do not restore a database snapshot over live payments or reissue uncertain financial actions. Keep private backups and runtime data outside public packages.

Primary integration references: [WooCommerce payment gateway API](https://developer.woocommerce.com/docs/features/payments/payment-gateway-api/), [WooCommerce refunds](https://woocommerce.com/document/woocommerce-refunds/), [Stripe refunds](https://docs.stripe.com/refunds). Installed source determines the actual stack's hook behavior.
