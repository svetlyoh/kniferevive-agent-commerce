# Booking deployment — October 8, 2026

Production WordPress successfully replaced **0.2.0 with 0.3.0**. The public
[booking page](https://kniferevive.com/?krev_agent=booking) accepts sharpening
requests independently of the unconfigured legacy service adapter. Large Knife
Sharpening product 1964 was verified anonymously at $7; the owner-approved
8-inch chef knife definition is configured. Original seller ownership is retained.

The owner's hours, Pittsburg location, phone and $7.99 merchant-trip fee are
configured. Business permission for prepaid requests and host-wallet payment is
recorded. **Actual prepayment and authorized wallet payment remain unavailable**:
daily capacity, public prepaid cancellation/refund terms, native pricing/service
fulfillment and payment/seller verification are not yet complete. No verification
flag was manufactured. No production booking test write, charge, wallet send,
refund, Stripe mode switch or seller reassignment occurred.

## Verified live behavior

- Anonymous capabilities: adapter 0.3.0, booking enabled, payment gates false.
- `/booking-options`: three modes, verified Large SKU/price, $7.99 trip fee,
  unknown daily capacity and merchant confirmation required.
- `/booking-coverage`: Pittsburg 94565 and San Jose 95112 qualify for county
  eligibility; San Francisco 94103 receives pickup-coming-soon; Los Angeles
  90001 receives the SF Bay Area only message; 95033 requires boundary review.
- The live human coverage form shows both exact requested messages. Other Bay
  Area ZIPs disable prepaid/pickup selections; outside ZIPs show no booking form.
- Public availability returns request windows, not confirmed reservations.
- The live OpenAPI document exactly matches the packaged 1.2.0 contract.

An identifiable `KnifeRevive-Concierge/0.3.0` HTTP client with JSON Accept obtained
anonymous JSON successfully. A PowerShell default-client request encountered a
hosting browser challenge; it was not solved or bypassed. Clients must handle
non-JSON/challenge responses with a normal human site handoff rather than assume
the API returned a quote. Initial capability reads briefly reflected the prior
cached state; the 30-second cache subsequently returned the verified current state.

## Tests

The final fenced acceptance matrix passed: 75 native listing checks and 60
booking checks in each of classic/HPOS × synchronization on/off, 91 legacy
operator regression checks in classic/HPOS, and 16 Stripe setup fixture checks.
Schema validation covers actual Booking, BookingCoverage and WalletInvoice
payloads. PHP syntax, REST empty-object serialization and portable skill
validation passed. All processor and invoice data were synthetic.

Browser testing on the isolated loopback site submitted an unpaid request and
showed `requested`, `payment:not_started`, $7 service and $0 merchant trips.
The server coverage form was tested in isolation and production. The optional
automatic ZIP-change JavaScript enhancement was not confirmed by the in-app
browser; the tested server form and authoritative validation work without it.
No customer email delivery or real processor settlement was claimed.

## Backup and evidence

The pre-upgrade settings, a pre-enable settings snapshot and exact 0.2.0 rollback
ZIP are preserved in the ignored local deployment folder. No new database schema
was introduced. Native gateway reconciliation and preexisting orders remain in
place. Do not deactivate payment reconciliation or delete financial rows to roll
back. Follow the [booking runbook](booking-release-0.3.0.md).

Exact archive hashes and sanitized live observations are in
[deployment results](booking-deployment-2026-10-08.json); file-by-file hashes are in
[release manifest](release-manifest-0.3.0.json). Publication and new-version audit
results are recorded in [publication evidence](booking-publication-0.3.0.md):
0.3.0 is published, the audit outcome is Pass, and three static-scanner warnings
remain disclosed.
