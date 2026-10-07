# Data flow and permissions

| Actor/component | Reads/sends | Authority |
|---|---|---|
| Anonymous shopper | Published product attributes, prices, stock, preliminary service ZIP eligibility and windows | Discovery only; rate limited. |
| Private shopper session | Selected service/quantity, postal code, both handoff legs, selected rail; optional fulfillment contact and billing details | Its own quotes/attempts/order status/change requests. |
| First-party review page | Quote, complete contact/tax details, policy, explicit approval | Issues a quote/session-bound consent and starts its one checkout attempt. |
| Stripe adapter | Locked amount/currency, display lines, receipt email, attempt ID and quote hash | Server-side creation/retrieval/refund using merchant-managed credentials. |
| Lightning adapter | Existing WooCommerce order, amount and native coordinator UUID | Uses existing bridge/coordinator; exposes only payment invoice/status to shopper. |
| Shopper wallet | Trusted checkout invoice plus independently authorized spending bounds | Wallet-controlled payment. The skill receives no seed/admin authority. |
| Merchant operator | Retained attempts, change requests, WooCommerce order references and fulfillment | Configuration, explicit reconciliation and existing refund/service operations. |

The skill requires no merchant credential, local filesystem, environment scan, installer, binary, background service, or proactive shopping. It does not transmit conversations, contacts, unrelated local data or cross-site tracking information. Optional source strings provide task attribution and are untrusted diagnostic values rather than authenticated agent identity.

Guest bearer tokens are derived from random session IDs and a server HMAC, with only token hashes retained. Tokens travel in a protected header or HttpOnly/SameSite Strict first-party cookie. A private review URL temporarily carries one in its fragment; the script removes it before first-party exchange. API URLs never carry credentials. TLS is required outside explicitly local environments. Private pages use no-referrer/no-store, no third-party analytics and a restrictive CSP. Quote-specific CSRF plus first-party browser-origin checks protect form actions.

Public discovery: 120 requests/network/minute. Private sessions: 60 requests/minute. Anonymous issuance: ten/minute. Rate records use salted network hashes, never raw IP address storage. Existing hosting/access logs have their own retention and should redact sensitive headers/cookies.

Expired session, quote, consent and rate rows are pruned after an additional day. Financial attempt/idempotency/event/change evidence is retained for reconciliation and merchant recordkeeping; the operator must apply an appropriate retention/export policy. There is no destructive uninstall hook. Database deletion is not part of this release.

The ClawHub package contains only the skill Markdown/reference/license files. Backend code, tests, mocked keys, test databases, dependencies, screenshots and production configuration are excluded. The publisher should report the exact submitted version's actual audit result; this document does not assert a completed audit or certification.
