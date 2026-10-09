# Storefront sharpening checkout 0.5.11

Customers who add configured sharpening services to the ordinary KnifeRevive cart now enter the same booking details and secure payment screens as bot buyers. Cart quantities prefill the booking form; prices, choices, coverage messages, policy disclosures, requested days and capacity come from the existing booking engine.

| Pickup and return plan | Payment | Trip fee per order |
| --- | --- | --- |
| You drop off + collect at shop | Prepay online | $0 |
| You drop off · they deliver | Prepay online | $6 |
| They pick up from you · you collect at shop | Prepay online | $6 |
| They pick up + deliver · comeback combo | Prepay online | $11 total |
| You drop off + collect at shop | Pay at pickup; nothing due now | $0 |

Sharpening charges are separate. Trips are taxable native fees, never the parcel shipping rate and never multiplied by knife quantity. Final configured taxes and payment fees appear before payment. Four prepaid choices require eligible ZIP coverage and verified payment before merchant submission; unpaid drop-off/collection skips the ZIP check. Pickup ZIPs and capacity remain managed in **WooCommerce → Agent Commerce**. Current fees are 600 and 1100 integer cents and daily capacity is 4.

## Cart and order protection

The adapter binds the original sharpening lines to a source fingerprint. Stale source edits fail before submission; visiting checkout and checking coverage do not create an order. Existing unpaid orders are preserved and linked through My account. Unsupported sharpening items require merchant review. Native classic/Store API order guards prevent ordinary sharpening checkout from bypassing booking checks.

Mixed carts are explicitly split: sharpening gets its own booking and order, other products remain in the normal cart with their usual shipping. Only captured service lines are consumed after an authorized, recoverable handoff exists. Both ordinary session storage and logged-in persistent carts preserve remaining products. Cart coupons are explicitly reapplied on the secure payment screen. Goods-only checkout stays native.

The existing binding and reviewed-total checks remain enforced. Suppress a false changed-items notice only while the private handoff is adding multiple service lines; clear that construction flag before final validation.

## Verification and deployment

All **774 assertions** passed across legacy and HPOS order storage with native synchronization enabled: 33 storefront, 75 listing checkout, 66 booking, 87 seller/order bridge, 66 lifecycle and 60 handoff-choice assertions per mode. Shipping/tax/session fixtures are reset between suites; an initial run exposed cross-suite fixture contamination, corrected by isolating these fixtures rather than changing pricing assertions. PHP syntax and Git whitespace checks passed.

The synthetic browser followed normal checkout with two small knives, one large knife and an ordinary product. Details prefilled $17 of sharpening and disclosed the split cart. Unpaid mode skipped ZIP and showed nothing due now. The combo reached native secure payment directly with a single $11 fee and **$28** total, zero parcel shipping and no changed-items warning. Address disclosure stayed open while typing. Earlier details verification measured 375px content in a 390px viewport; the final payment capture is desktop (the later viewport override returned 1280px). No mobile payment-screen verification is claimed from that capture.

WordPress reported **Plugin updated successfully** after replacing 0.5.10 with the tested 0.5.11 ZIP. Production ordinary cart checkout prefilled one $7 large-knife service and showed all five choices. Both single-trip options estimated $13, combo $18, and customer drop-off/collection $7 before final taxes/fees. The unpaid option disabled ZIP and showed its booking action. Live ZIP checks returned eligible pickup for 94565, pickup-not-yet-available for 94103 and outside-service messaging for 90001. The new source fingerprint and versioned 0.5.11 booking asset were present. Settings readback retained capacity 4, fees 600/1100 cents, taxable trips, prepayment enabled and payment-before-submission enforcement.

The production cart was initially empty; only the one verification item was added, then removed, and the empty cart was verified. No real contact details, booking submission, order, payment, refund or outbound email were created by production verification. Synthetic tests use an isolated loopback database, blocked outbound email/HTTP and a gateway that cannot process payment. Actual processor settlement and refunds remain separate evidence; this release does not claim them.

See [sanitized evidence](storefront-sharpening-checkout-0.5.11.json), [package manifest](plugin-release-manifest-0.5.11.json) and [implementation plan](storefront-sharpening-checkout-plan.md). Screenshots are retained locally in the owner's documentation diagnostics directory.

The portable skill remains the already published immutable **0.5.10**. This backend release does not require a bot reinstall or a new ClawHub version.
