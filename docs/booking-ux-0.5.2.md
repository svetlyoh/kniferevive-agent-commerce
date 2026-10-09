# Booking and payment UX 0.5.2

October 8, 2026. Owner direction: clear, friendly coverage confirmation; remove the repeated quote editor; make secure payment the final booking action; separate cancellation/sharing buttons; clarify pickup versus drop-off and improve card-field presentation.

## Implementation

- Coverage appears in a prominent live status panel: green for service availability, amber outside the service zone, and neutral for address review. Mixed/unknown ZIPs do not claim availability. Bay Area drop-off-only and custom pickup exclusions state both available and unavailable options. Editing a ZIP clears the old confirmation. A failed client API request cannot overwrite an authoritative same-ZIP server result with a connection error. The Check service coverage POST remains usable without JavaScript or when the host challenges REST requests.
- Prepaid options use **Continue to secure payment**; unpaid drop-off uses **Send my booking request**. The private booking page ends its main actions with one secure-payment button. Ready billing details live in an expandable section; missing details remain visible.
- The booking quote editor and **Calculate native quote** button are removed from this flow. Native pricing still runs server-side before checkout. The combined consent-bound POST prepares the exact quote and original cart; it does not charge or create a replacement order. The payment screen shows the full total before final approval. Generic marketplace quote editing is unchanged.
- **Need to change plans?** groups **Cancel my booking request** and **Stop sharing my contact details** in separate cards with 24px separation. Explanations distinguish cancellation/refund and sharing revocation; existing records are retained.
- Human-facing handoff wording explicitly identifies both legs: **We pick up → you collect**, **You drop off → you collect**, **You drop off → we deliver**, or **We pick up → we deliver**. The sole native handoff rate is not another choice in the booking form. Native checkout displays **Pickup & return plan** and the actual journey. Merchant trip fees remain separate. Backend rate IDs/labels remain stable for existing quote hashes and financial records; display filters change the human wording.
- A separate booking-only checkout stylesheet improves field padding, input contrast, table spacing, payment container, native radio/checkbox sizes, mobile columns and final button. Stripe iframe styling uses its documented `wc_stripe_upe_params` Appearance API filter (labels above, 17px type, clearer borders/focus) plus its legacy Elements styling filter. No custom card input, token handling or vendor-file modification is introduced. Filters register only on the protected booking payment route.

## Checks

Booking suite: 66 assertions in classic and HPOS storage. Final lifecycle suite: 46 assertions in each, including the combined payment-preparation path, missing consent, wrong owner and original native cart binding. No real payment, refund or email was sent. PHP/JavaScript syntax and whitespace checks passed.

Browser verification checked a submitted synthetic pickup request, separated controls and direct private booking → native payment navigation without a quote button. Production coverage results and synthetic native checkout were separately checked at 390×844. Native checkout shows one form, preserved billing/pickup address, $12.99 for a $5 small knife plus one $7.99 trip, and no horizontal overflow. Place order was not clicked. Synthetic screenshots use a mock gateway; they do not verify hosted Stripe iframe rendering, wallet-device eligibility or real settlement.

## Deployment and operating limits

Agent Commerce **0.5.2 is deployed** over 0.5.1 through native WordPress update; the UI reports success. The exact 0.5.1 rollback ZIP is retained. Live BookingCheckoutFrontend.php and ListingFrontend.php match packaged source after newline normalization. Production served/unserved POST results and phone screenshots are recorded. Capacity 4, trip fee 799 cents, prepayment, launch approval, pay-before-confirmation and current county/custom-list settings are preserved. No new production booking/payment, schema change, seller-overlay update, credentials, mode switch or webhook change was made.

For later patches, read back critical live source, verify native update success, and check served/unserved POST results on production. Capture evidence without creating a new production booking or payment. Retain the existing processor/bot-access limitations from prepaid-booking-0.5.1.md: hosting REST challenge, unverified real processor/refund settlement, Lightning readiness false, published ClawHub skill 0.4.2.

Review the real hosted card fields using an authorized buyer's existing private checkout link; do not create/charge a production customer for visual testing. Payment approval stays with the buyer. An old or uncertain intent still follows its original reconciliation path.

Stripe integration reference: [WooCommerce's supported payment form styling](https://woocommerce.com/document/stripe/customization/style-payment-form/). Installed Stripe and WooCommerce source was inspected for hook names and native markup.
