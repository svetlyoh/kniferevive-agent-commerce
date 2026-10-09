# Storefront sharpening checkout — implementation plan

Owner request, October 9, 2026: replicate the deployed sharpening booking flow, pricing, wording and checks when customers buy sharpening through the regular KnifeRevive cart and checkout.

## Shared experience

Use the existing Agent Commerce booking implementation rather than a second shipping/booking engine. A normal cart containing configured sharpening services opens “Your knife game plan” at the normal checkout URL, with its knife quantities already selected. The existing secure KnifeRevive payment screen is step two. No booking, order, invoice or payment is created by visiting checkout.

The choices, in order, are:

1. You drop off + collect at shop · prepay — sharpening + $0 trip fee.
2. You drop off · they deliver · prepay — sharpening + $6 trip fee per order.
3. They pick up from you · you collect at shop · prepay — sharpening + $6 trip fee per order.
4. They pick up + deliver · comeback combo · prepay — sharpening + $11 total trip fee per order.
5. You drop off + collect at shop · pay at pickup · nothing due now — $0 trip fee; sharpening payable at pickup.

Read all labels, service prices and fees from the existing booking implementation/settings. Trips are taxable native order fees, never the parcel $7.99 shipping policy and never multiplied by knife count. Final configured taxes, gateway fees and total remain visible at payment. Existing orders/quotes are not repriced.

## Checks and authorization

Reuse live service/product eligibility, ZIP coverage and administrator pickup ZIP controls, trip address/county review, requested open day, daily capacity 4, policy disclosure, independent contact sharing consent and payment authorization. All four prepaid options require eligible coverage and successful native payment before seller submission. Option 5 skips ZIP checking, creates the authorized unpaid local-pickup seller order and still requires merchant day confirmation. Cancellation and refund remain independent and use the existing seller/order lifecycle.

Protect the normal checkout entry server-side. Bind its source sharpening cart lines and quantities to a fingerprint shown in the form. Reject stale cart edits before creating a booking. Customer edits to knives on the booking form remain allowed and are reflected in the new booking. Only remove the captured source sharpening lines after an authorized, recoverable booking handoff has been prepared; leave unrelated products intact. Do not remove lines on coverage checks, validation failures or GET. Keep existing native pending orders intact and direct their buyer to the original order rather than preparing a duplicate.

Mixed carts are explicitly split: sharpening uses its own booking/order, physical goods stay in the cart for ordinary checkout. Unsupported sharpening variations or unconfigured sharpening products must show a review message rather than silently shipping them as parcels. Ordinary goods-only checkout and existing bot/private booking routes remain unchanged.

## Implementation and verification

1. Add a narrow storefront-to-booking adapter to the existing plugin and reuse its details/payment templates. Preserve nonce/origin checks and private native checkout session isolation.
2. Add meaningful sandbox tests for cart classification, quantity prefills, stale-source rejection, no GET/coverage mutations, exact line consumption, mixed-cart preservation, pending-order protection and shared taxable trip fees.
3. Verify ordinary cart → booking details → native payment using a fenced synthetic database with outbound mail/payment blocked; check small/large knives, all five choices, in/out-of-coverage ZIP messages, mobile address editing and totals. Do not alter the real local site's configuration/database.
4. Package and deploy the reviewed plugin through the existing WordPress admin upload/replace workflow under the owner's ongoing implementation/deployment authorization. Verify production version and the cart entry without submitting a real booking or payment. Restore any temporary verification cart edits.
5. Record results, screenshots and remaining actual processor/refund limitations; push source/evidence to the owner's existing GitHub repository. Keep the already published immutable ClawHub 0.5.10 skill unchanged; this is merchant checkout plumbing, not a new registry skill publication.

## Completion record

Completed October 9, 2026. Agent Commerce 0.5.11 is deployed on kniferevive.com. Normal sharpening cart checkout now prefills the shared details screen, preserves ordinary products and uses the five existing handoff/payment choices. All 774 synthetic assertions passed in legacy/HPOS storage, including source-cart protections, fee/tax snapshots, seller order linkage and lifecycle checks. The browser reached native payment with $17 sharpening plus one $11 combo fee and no false changed-items warning. Live coverage messages, source fingerprint, versioned assets and existing capacity 4 / taxable $6 and $11 settings were verified. The single temporary production cart item was removed and the empty cart verified; no production booking/order/payment/refund/email was created.

The completion record and sanitized evidence are saved in C:\Users\Svet\Documents\KnifeRevive_Agent_Commerce\docs\storefront-sharpening-checkout-0.5.11.md and its JSON evidence. Screenshots are in this documentation folder's diagnostics directory. The final payment screenshot is desktop; do not claim later viewport overrides produced mobile payment verification. Processor settlement, seller payouts and actual gateway refunds were not exercised in this checkout-routing release. The published ClawHub skill remains 0.5.10.
