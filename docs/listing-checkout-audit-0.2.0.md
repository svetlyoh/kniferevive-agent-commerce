# Native listing checkout audit — 8 October 2026

Candidate: merchant adapter **0.2.0**, portable skill **0.2.0**, API contract **1.1.0**.
Production adapter: **0.2.0**, deployed 8 October 2026 with listing handoff disabled.
Local adapter: **0.1.3**. Published skill: **0.1.2**; skill 0.2.0 is unpublished.
See [deployment verification](listing-checkout-deployment-2026-10-08.md) for the
post-install checks. No real processor purchase or refund was initiated.

## Verified inventory and differences

Local evidence came from installed plugin headers, gateway objects, nonsecret
settings observations and source inspection. Production evidence came from the
authenticated Plugins screen and read-only Stripe/Connect settings UI.

| Component | Local installed | Production installed | Observation |
|---|---|---|---|
| WordPress | 7.1.2 | 7.1.3 | Versions differ |
| WooCommerce | 11.0.1 active | 11.2.0 active | Production staging parity remains required |
| Official WooCommerce Stripe | 10.9.0 active | 11.0.1 active | Native charge owner; preserve managed endpoint |
| Dokan Lite | 5.1.2 active | 5.0.19 active | Different core versions |
| Dokan Pro | 4.0.6 active | 4.0.6 active | Production Stripe Connect and Stripe Express modules both disabled |
| Dokan Vendor Dashboard | 1.0.4 active | 1.0.4 active | Production UI labels it deprecated |
| KnifeRevive Connect | 0.1.6 inactive | 0.1.6 active | Production onboarding enabled, live selected; transfers disabled |
| Seller Orders | 1.2.0 active | 1.1.4 active | Local behavior is not proof of production behavior |
| Seller Commissions | 0.2.0 installed/inactive | 0.2.0 active | Uses native Dokan commission records |
| KnifeRevive Return Policies | 1.0.0 installed/inactive | 1.0.0 active | Native buyer terms and order snapshots retained; included in synthetic listing tests |
| Native Lightning | 0.1.13 inactive | 0.1.13 active | Activation alone does not prove settlement |
| Conditional Extra Fees | 1.1.67 active | 1.1.67 active | Actual calculator included in fenced tests |
| WooCommerce Tax | 3.6.12 active | 3.7.1 active | Automated production tax parity not tested |
| Agent Commerce | 0.1.3 active | 0.2.0 active | Deployed with native listing checkout gates off |

Local enabled gateway objects were `stripe` (UPE) and `stripe_link`, both reporting
live mode. Existing test/live secret and signing-secret presence was observed as
booleans only. The local checkout page contains the Checkout Block. Installed
payment extensions are not equivalent to enabled payment methods.

The actual local sharpening products were queried by category and mapped to their
canonical slugs: small 1963 and large 1964, both owned by the same seller account
without `manage_woocommerce`. These are observations, never constants in the
implementation. Public large/small listing pages identify ThriftCloset. Current
prices are always obtained through WC CRUD. Both local products are nonvirtual;
generic shipping descriptions do not establish service transport or appointment terms.
Local technology category contained no published products. Tests therefore used
synthetic tech/knife/art/spices/coins/service SKUs, not invented live listings.

## Webhook owners and proof

| Owner | Destination/events | Test evidence | Live evidence |
|---|---|---|---|
| Official WC Stripe | `https://kniferevive.com/?wc-api=wc_stripe`; gateway-managed events, inspect Workbench rather than copying an adapter event list | Production UI: Connected, Configured, Enabled, same managed destination | Same; UI reported successful delivery at **2026-10-07 21:27:32 UTC**, with at least one pending webhook. This is delivery evidence, not proof of a candidate listing purchase |
| Dokan Stripe module | Module/gateway dependent | Both Stripe Connect and Stripe Express switches were off in production Modules UI | No module payment/payout verified; native official Stripe + custom Connect is the observed store path |
| Custom KnifeRevive Connect | `/wp-json/kniferevive-connect/v1/stripe/webhook` | Production fields indicate no test secret or test signing secret configured | Live secret/signing-secret presence; onboarding enabled; automatic transfers **disabled**; UI says two allocations need attention. No transfer/refund evidence verified |
| Operator-service adapter | `/wp-json/kniferevive-agent/v1/stripe/webhook`; `checkout.session.completed`, `.expired`, `.async_payment_succeeded`, `.async_payment_failed`, `charge.refunded` | Prior dedicated endpoint registration; signed **synthetic** replay/reconciliation regression passes | No dedicated live service payment verified; service rail remains separately gated |
| Native Lightning | Existing coordinator/bridge owns invoice settlement | Existing coordinator tested with HTTP fixtures only | Outstanding-record notice observed; no real invoice settlement verified |

Custom Connect source handles `payment_intent.succeeded` by queuing its existing
distribution path, payment failure by recording failure, charge refunds through
its refund queue, disputes as manual review, account/transfer/payout events through
existing handlers. The new listing adapter never calls those queues or creates a
transfer. It observes native WC order completion after the original hooks.
Do not register another webhook or duplicate payouts for this handoff surface.

## Launch blockers

Real native Stripe test checkout, verified vendor transfer/refund, signed real
processor delivery/replay, disputes and production-version staging checks remain
**NOT RUN**. Production Connect test configuration is incomplete and transfers
are paused with unresolved allocations. Existing checkout/accounting must be
verified before enabling agent handoff. Google Pay's production UI notice says
express buttons need review; availability is conditional, never promised.

Public capabilities could not be revalidated anonymously: terminal access met a
JavaScript browser challenge, web retrieval failed, and the in-app browser blocked
that API navigation. Authorized admin access worked. This is an agent-access
compatibility blocker to investigate through the hosting provider; no challenge
or security rule was bypassed. It does not establish an API outage for all clients.

Actual sharpening fulfillment/ZIP coverage, tax/transport configuration, refund
terms and job capacity remain gates. Owner-supplied hours are Fri/Sat 9am–7pm,
Sun 10am–4pm, America/Los_Angeles, with **$7.99 per merchant trip** ($15.98 for
both). Customer-operated legs do not incur the corresponding merchant trip fee.
Native listing checkout adds none of these by inference; verified native rules
must charge/disclose them or services remain `needs_manual_review`.

## Sources and provenance

Installed source and authenticated settings are primary evidence for this store.
Protocol guidance was checked against [WooCommerce gateway contracts](https://developer.woocommerce.com/docs/features/payments/payment-gateway-api),
[Store API checkout](https://developer.woocommerce.com/docs/apis/store-api/resources-endpoints/checkout-order),
[managed Stripe webhooks](https://woocommerce.com/document/stripe/setup-and-configuration/stripe-webhooks/),
[Dokan Stripe Connect](https://dokan.co/docs/wordpress/modules/how-to-install-and-configure-dokan-stripe-connect/),
[Stripe event fulfillment](https://docs.stripe.com/checkout/fulfillment?payment-ui=stripe-hosted)
and [Stripe marketplaces](https://docs.stripe.com/connect/marketplace).
Public product crawl snippets are discovery context, not current binding prices.
Safe raw observations are retained locally under ignored `.runtime/listing-*-audit`
and `.runtime/listing-production-*.json`; credentials/PII are excluded from this report.
