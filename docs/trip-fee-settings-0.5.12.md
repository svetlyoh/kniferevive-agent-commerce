# Editable sharpening trip fees

The merchant can set both prices at **WooCommerce → Agent Commerce → Sharpening trip fees**. Enter dollar amounts for **One pickup or delivery trip** and **Pickup + delivery · comeback combo**, then press **Save trip fees**. Current prices are $6 and $11. These are taxable booking fees charged once per order, separate from sharpening and parcel shipping.

The dedicated editor changes only the two booking fee settings, validates cents and range, requires `manage_woocommerce` and a WordPress nonce, and preserves coverage, capacity, tax configuration and payment gates. Each save is atomic: an invalid amount leaves both prices unchanged. Existing bookings and orders keep their frozen fee and tax policy; new quotes read the current settings. No payment credentials, gateway hooks, or new login are needed.

Both `/booking-options` and `/capabilities` return customer-facing labels containing the current numeric amounts: **$6 trip fee** and **$11 round-trip fee**. The additive `plan_label` field preserves the fee-free journey for the web form's calculated sharpening subtotal. Customer labels omit `fee/order`; per-order charging is explained separately. The same engine supplies the ordinary sharpening cart and bot bookings.

Skill 0.5.13 instructs bots to fetch current options before presenting new choices and retain the numeric fee when shortening labels. Registry publication cannot reload a third-party bot's cached skill or force its renderer to comply.

Verification and production deployment results will be recorded after completion. No real booking or financial transaction is needed to test this change.
