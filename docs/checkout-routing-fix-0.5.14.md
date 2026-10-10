# Native sharpening checkout POST repair — Agent Commerce 0.5.14

Deployed on 2026-10-09 Pacific. The portable ClawHub/GitHub skill remains 0.5.14.

## Problem and behavior

The ordinary sharpening cart renders the first booking screen at `/checkout/`.
Its contact input used `name="name"`. WordPress reads `name` from POST as a
public page/post slug selector before `template_redirect`. A customer name
therefore changed the main query, `is_checkout()` became false, and the
storefront booking adapter never ran. The published checkout page and its
WooCommerce assignment were valid; an empty cart correctly redirected to Cart.

The form now submits `customer_name`, which is outside WordPress public query
variables. The consented booking still stores `customer.name`, and native
billing name prefill is preserved. Existing private-link forms can use the old
field through the internal fallback. A previously open storefront form needs
a fresh GET/reload before submitting; do not resubmit its old POST.

No checkout page, payment gateway, coverage, capacity, shipping policy or existing
booking/order was migrated. The five choices and $6 single-trip / $11 combo fees
remain unchanged. Secure payment still uses the isolated native booking session.

## Verification

- Before repair, a disposable anonymous sharpening cart reached the details
  screen with HTTP 200; POST of the old contact name plus an intentionally invalid
  nonce returned HTTP 404 and the theme's Page Not Found screen. No booking was
  created by that diagnostic.
- Seven regression assertions use actual WordPress POST request parsing to prove
  the old name hijacks checkout and the rendered replacement preserves coverage
  and Continue to payment routing. The saved name and all contact field names are
  also checked.
- 216 existing native fee/booking assertions passed across classic and HPOS with
  synchronization enabled: 75 booking-option and 33 storefront assertions in each
  mode. All merchant plugin PHP files passed syntax checks.
- The loopback browser fixture now parses the actual WordPress checkout query;
  it no longer forces `woocommerce_is_checkout` true to conceal routing bugs.
  A synthetic HTTP session completed cart → coverage POST → details POST → HTTP
  303 private booking payment URL → HTTP 200 KnifeRevive secure payment, retaining
  the buyer name and $11 combo fee. Outbound mail/payment is fenced; Place order
  was not submitted.
- WordPress's existing-plugin replacement reported successful update, and the
  installed plugin list shows active version 0.5.14.
- Live GET, coverage POST with the renamed contact field, and a deliberately
  incomplete submission all returned HTTP 200. The latter displayed ordinary
  required-field validation and “No booking was submitted,” preserving the name.
  Public options report adapter 0.5.14 and fees 600/1100 cents.

No production booking, order, payment, refund or notification was created during
verification. Actual paid completion and seller refund arrival are not claimed
by this routing test. See [machine-readable evidence](checkout-routing-fix-0.5.14.json).

## Operator recovery

Return to the existing cart's checkout using a fresh page load, review the booking
details and choose Continue to payment. A failure before the old storefront
handler ran did not transfer/remove its cart items. If the customer already has a
booking reference or order from another attempt, reopen its private payment link
and status instead of creating a replacement. No skill reinstall or WooCommerce
relogin is needed for this server-side repair.

Run `tests/booking-form-routing.php` through the existing fenced test bootstrap;
never load the live or Local Sites `wp-config.php`. The normal test runner includes
this regression. Rollback ZIP 0.5.13 is retained locally, but reverting it
reintroduces this checkout POST failure.
