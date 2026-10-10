# Confirmed bookings in native sharpening orders

Implemented 9 October; deployed and verified 10 October 2026 Pacific. Agent Commerce 0.5.17 and Seller Orders 1.1.7.
Portable skill remains 0.5.14; no skill reinstall is required for this website change.

## Problem and resulting behavior

The booking adapter replaced native sharpening cards in Seller Orders → Local
Pickup with appointment cards. After confirmation, those cards showed only
“Service day confirmed”, hiding the native sharpening stage and its order link.
Confirmation also wrote its order metadata only on the unpaid bridge order,
omitting prepaid native orders.

Confirmed booking cards now show the native order number, current sharpening
stage/icon, selected services, intake day, handoff, payment and total. They retain
“Service day confirmed” as a separate booking fact and expose separate **View
Order** and **Review booking** links. The same linked order appears once, avoiding
the duplicate native card. Requested bookings retain their existing request card.

Confirmation uses the original validated Local Pickup order for both prepaid and
pay-at-pickup requests. It calls the installed native sharpening initializer,
records confirmation/day metadata and fills missing handoff wording. New workflow
initialization starts at **Book & choose handoff**. Confirmation is not proof that
the knives arrived; it never moves to Blade check, marks the order paid or changes
its total. Already progressed/completed stages and existing handoff text remain
intact. Reconfirmation does not duplicate native workflow initialization.

The displayed stage comes from `KREV_Sharpening_Workflow::dto()` and
`KREV_Sharpening_Assets::stages()`, not a second list of stage labels. The current
stage key stays visible even when a prepaid payment step is already completed.
Completed workflow uses its native completion flag.

Booking and order remain separate linked records. Seller booking confirmation
continues to reserve the intake day under the existing coverage/capacity/consent
checks; native sharpening progression continues on the original order.

## Authorization and financial boundaries

Order cards require the existing booking/seller authorization, original linked
order, native vendor attribution, exactly matching service quantities and one
native Local Pickup shipping line. Cancelled/refunded/failed orders cannot enter
this active booking-card adapter. Original booking/payment grants are unchanged.

Existing authorized sharpening operators receive the native sharpening-order
detail link and its existing stage controls. An owning seller without operator
permission receives the original authenticated Dokan order link. No global
sharpening capability or new seller permission is granted. Unrelated sellers and
anonymous visitors cannot see the booking cards.

Cards are read-only: viewing them does not initialize orders, send messages or
alter payments. Initialization/synchronization occurs during the authorized
confirmation action. Existing confirmed orders can display their native stage
immediately, without migration, another confirmation or another payment.

No new order/payment/refund is created by this change. Cancellation/refund and
native payment evidence retain their separate existing behavior.

## Verification and deployment record

- 97 bridge assertions in each of classic/HPOS storage with synchronization: 194.
  Coverage includes unpaid native stage initialization, preserved pending total,
  replay without duplicate audit, progressed native stage preservation, one
  rendered confirmed order card and seller authorization.
- 93 booking/fee assertions and 33 storefront assertions in each storage mode:
  252. Prepaid tests preserve the original paid order/transaction/total, show the
  native stage to the owning seller, and keep a paid Pay stage visible.
- Total: **446 assertions passed**. PHP syntax checks pass for both packages;
  `git diff --check` passes. Email and processor results in these tests are synthetic.
- The Seller Orders package preserves the captured 30-file production baseline
  with the versioned PHP overlay; the live pre-update template matched the
  previous tracked release. Schema remains 1.1.4.
- Native WordPress updates reported success for Agent Commerce 0.5.17 and Seller
  Orders 1.1.7. Public capabilities/options/availability/coverage returned four
  HTTP/2 200 JSON responses, adapter 0.5.17 and unchanged $6/$11 trip fees.
- Existing confirmed native order 3014 renders once in Local Pickup with
  **Book & choose handoff**, Paid, Processing, $5.61, and separate View Order and
  Review booking links. The original native sharpening detail opens with the same
  current stage and existing operator controls. No stage, date or payment was
  changed to create this live verification; the existing confirmed order was read.
- An existing requested paid order still renders as requested. No new production
  booking, order, charge, refund, confirmation or email was initiated by the agent.

The final Agent Commerce archive contains exactly the 40 reviewed tracked plugin files. An unrelated untracked catalog-expansion file appeared during the shared-workspace build; it is not included in the final redeployed archive, remains untouched in the workspace, and is not loaded by this booking release.

Packages:

- Agent Commerce 0.5.17 SHA-256:
  `8d158c5cda375c7cf102c0d2e06515cdad4ee01830523cbe5ba3f679a86a12c3`.
- Seller Orders 1.1.7 SHA-256:
  `394c381df1696b931bfb4df2116ecafe7a0deb2a6ad9ff482bee5e5fc06fd495`.

Screenshot saved locally:
`C:\Users\Svet\Documents\KnifeRevive_Documentation\diagnostics\confirmed-native-sharpening-local-pickup-0.5.17.png`.

Actual new merchant confirmation/payment/refund actions were exercised in the
fenced sandbox; no additional live financial test was needed for this display and
confirmation synchronization patch. The live operator order view was verified;
a separate owning-seller live login was not impersonated or newly tested.

## Operator use

Confirm the requested day in **Seller sharpening requests**. Then open
**My Account → Seller Orders → Local Pickup**. The confirmed entry shows the
native sharpening status. **View Order** opens the original order; authorized
operators use its normal stage controls. **Review booking** opens the separate
appointment record for confirmation/cancellation/refund review.

The requested date and native payment/order state remain distinct. Never advance
receipt or completion merely because the merchant accepted a day.

## Rollback

Restore the retained Agent Commerce 0.5.16 and Seller Orders 1.1.6 packages through
native WordPress update if necessary. Keep original booking/order/financial data
and native workflow metadata. Seller Orders schema remains 1.1.4; no table
migration or database restoration is required. Do not substitute the unrelated
Local Sites Seller Orders build for the captured production plugin.
