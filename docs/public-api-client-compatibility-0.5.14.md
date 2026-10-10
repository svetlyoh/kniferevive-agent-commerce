# Public booking API client compatibility — October 9, 2026

The production API is reachable anonymously and returns five handoff choices with explicit numeric fees. The earlier broad claim that anonymous bot access was blocked was based on the failing PowerShell client and was too broad.

At the same canonical booking-options URL and with the same JSON Accept and honest KnifeRevive User-Agent, PowerShell 7 received HTTP 403 HTML over HTTP/1.1 and HTTP 200 JSON over HTTP/2. Native curl, Node fetch and Python urllib also returned HTTP 200 JSON without login, cookies, tokens or challenge solving. The precise hosting rule or TLS/request characteristic behind the difference is not known; a protocol observation does not identify that internal rule.

PowerShell HTTP/2 also successfully read capabilities, availability and ZIP coverage. Live merchant plugin 0.5.13 advertises Small Knife Sharpening at $5, Large Knife Sharpening at $7, a $6 single-trip fee and $11 pickup/return combo. Fees remain merchant-configured. The unpaid self drop-off/collection option is fifth, with nothing due now. These discovery results are not an all-in tax quote, payment proof or appointment confirmation.

## Working request configuration

Use an existing HTTP client with `Accept: application/json`, a truthful identifying User-Agent when supported, and HTTP/2 when configurable. This does not require changing the merchant plugin, logging back into WooCommerce, or changing firewall settings.

For operator diagnostics on this PC or another PowerShell 7 host:

```powershell
pwsh -NoProfile -File tools/check_public_booking_api.ps1
```

The script reads four public GET endpoints, validates the five-option menu and dynamic fees, and emits a JSON report. It creates no session, booking, order or payment. It sends no customer details. It refuses redirects and stops on a failed response; it does not solve or retry a browser challenge. This operator script is outside the portable skill, which retains no executable dependencies.

For one direct PowerShell 7 read:

```powershell
Invoke-RestMethod -Uri 'https://kniferevive.com/wp-json/kniferevive-agent/v1/booking-options' -HttpVersion 2.0 -Headers @{Accept='application/json'; 'User-Agent'='KnifeRevive-Concierge/0.5.14'}
```

## Remaining host-specific failures

The affected buyer bot's HTTP response and runtime have not been observed. If it still receives HTTP 403 HTML, record the exact canonical public URL, UTC timestamp, HTTP status, client name/protocol and safe response/request IDs. Do not collect secrets or customer data. Request WordPress.com support to inspect the hosting event and support intentional anonymous discovery at `/wp-json/kniferevive-agent/v1/capabilities`, `/booking-options`, `/booking-availability` and `/booking-coverage`. Keep private session ownership, payment verification, application rate limits and existing site protections intact. Do not exclude the whole REST API or disable the firewall globally.

The site's current Premium hosting dashboard shows WAF and Defensive mode configuration as requiring Business/Commerce; it offers no usable route-exception control. This does not establish that Defensive mode is enabled. See [WordPress.com's Defensive mode documentation](https://wordpress.com/support/defensive-mode/).

Skill 0.5.14 documents the working client configuration and distinguishes host/client failures from disabled commerce. It cannot remotely reconfigure a third-party bot or force its renderer to preserve labels. Verify that bot using a fresh public API read before claiming its booking access or display is fixed.
