# Local release evidence — 0.1.0

Verified October 7, 2026. This is a local release candidate, not a deployed commerce service or a registry-approved skill.

## Delivered

- Portable `kniferevive-concierge` skill and three focused references, with anonymous Annex discovery and prepaid sharpening flows.
- WordPress adapter: fourteen REST paths, structured catalog/availability/quotes, private first-party approval, separate intake/return windows, atomic job allocations, scoped status, merchant configuration, and retained reconciliation evidence.
- Hosted Stripe Checkout adapter with server verification and conditional Google Pay handoff; integration with the existing Lightning coordinator.
- OpenAPI 3.1 contract, public AI.md quick-start, installation/recovery/security documentation, and plugin/skill ZIPs with SHA-256 manifests.

An identical plugin folder was copied into the owner's Local Sites project's `wp-content/plugins/kniferevive-agent-commerce`, without activation or changes to that site's database. No GitHub or ClawHub publication had occurred at the time of the local checks below. The owner subsequently authorized publication; current results are recorded in [publication status](publication-status.md).

## Actual verification

| Check | Result and scope |
|---|---|
| PHP syntax | All 11 plugin PHP files and 6 test PHP files pass PHP 8.2.29 lint. |
| Legacy WooCommerce order storage | 75 behavioral assertions pass; actual datastore `WC_Order_Data_Store_CPT`. |
| HPOS order storage | The same 75 assertions pass; actual datastore `OrdersTableDataStore`, with native WooCommerce synchronization enabled. |
| API schemas | Seven actual payloads validate against the generated contract: Session, Catalog, Quote, Status, Capabilities, QuoteInput and CheckoutInput. |
| Documentation contract | AI.md quote example validates; packaged AI.md/OpenAPI copies match source bytes. |
| Skill format | Available skill-creator `quick_validate.py` passes. This is not a ClawHub security verdict. |
| Browser | Headless Edge/Playwright passes private fragment removal, protected session cookie, contact-dependent re-quote, explicit consent, unpaid hosted checkout status, mobile overflow check, and browser error check. Mobile/desktop screenshots were inspected. |
| Release files | Packager includes only explicit plugin/skill folders, rejects credential-like literals/config/database/bytecode files, and emits exact file and archive hashes. |

Behavioral checks cover anonymous discovery; quote-only side effects; real installed gateway-fee calculation; real WooCommerce tax rules; both courier legs and address-verification failure; scope/assessment rejection; quote and checkout idempotency; price changes after approval; foreign form origins; scoped access; forged tokens; processor amount/quote binding; duplicate/tampered/stale webhook handling; verified payment and refund accounting; timeout recovery; retry backoff/pause; missing/expired/released appointment holds; native stock allocation before payment and expired allocation review; two actual PHP processes competing for the final InnoDB job slot; native Lightning invoice retry/settlement; and wrong network/amount rejection by the installed Lightning client.

Additional regressions establish that a fresh quote cannot switch rails around an equivalent unresolved purchase, and repeated full-refund reconciliation retains `refunded` payment and `cancelled` appointment states. The rail-conflict test uses a retained fixture attempt; it does not send a second real provider payment.

The test databases contain only synthetic products, orders and customers. Test bootstrap uses loopback MySQL port 11019, database `krev_agent_sandbox`, prefix `krev_sandbox_`, and never loads the site's wp-config.php. The loopback browser server uses port 11080. External HTTP requests and mail are blocked; provider responses are fixtures. Temporary test servers were stopped after verification; ignored runtime files remain available for reproduction.

Tested code/runtime versions: WordPress **7.1.2**, WooCommerce **11.0.1**, PHP **8.2.29**, MySQL **8.4.0**, KnifeRevive Lightning Payments **0.1.13**, and Conditional Extra Fees for WooCommerce **1.1.67**. Existing Connect **0.1.6** and Seller Orders **1.1.4** were inspected for integration boundaries; their real payment/seller workflows were not exercised. Source and installed-site parity must be checked at deployment time.

## Outstanding enablement gates

1. Configure and verify actual service product definitions, operator ownership, ZIP coverage, transport address rules/fees, location, both appointment legs, capacity/holidays, policies and merchant contact workflow. None of these business facts are invented by the candidate.
2. Verify quotes against the complete staging plugin stack and existing sharpening operator views. The tests load WooCommerce and specific fee/Lightning code; they do not activate every plugin on the customer's site. HPOS was checked with synchronization enabled; synchronization-disabled installations need their own verification.
3. Run a real Stripe test-account session, signed webhook, payment/refund and device-eligible Google Pay check. No processor request or charge occurred during these tests. Wallet display is conditional, never guaranteed by the skill.
4. Verify the existing Lightning bridge/wallet, invoice decoding, merchant payee and authorized settlement/refund process. The fixture invoice is not spendable. No wallet key, real invoice or Bitcoin payment was used.
5. Test Annex unique-item purchases, fulfillment restrictions and seller/refund accounting through the existing checkout. The adapter's direct marketplace checkout remains disabled; no new Connect transfer flow was implemented.
6. Verify hosted cron/reconciliation reliability, operational recovery, real latency/rate-limit behavior and production monitoring. No traffic or conversion result has been measured.
7. Run scenario evaluations with target agent hosts for unrelated requests, malicious catalog instructions, unavailable wallets and payment-rail switching. Written skill boundaries and backend tests are evidence of design; agent evaluations were not run.
8. Publication was subsequently authorized and completed for the exact skill artifact. [Current publication status](publication-status.md) records its clean/benign scan and missing generated Skill Card/provenance limitation. This historical local test report is not a certification or proof of live merchant capabilities.

New payments ship disabled. Unsupported/unconfigured journeys advertise their actual state and fall back to the merchant pages. Live acceptance and external publication require the remaining checks above.

## Sources rechecked

The release boundaries were compared with [ClawHub security-audit guidance](https://github.com/openclaw/clawhub/blob/main/docs/security-audits.md), [Stripe hosted fulfillment](https://docs.stripe.com/checkout/fulfillment?payment-ui=stripe-hosted), and [Stripe Google Pay documentation](https://docs.stripe.com/google-pay) on October 7, 2026. Actual submitted audit findings and real merchant-account behavior remain independent checks.

## Native listing candidate 0.2.0

Detailed current inventory, test matrix, reproducible commands, exact package manifests and release conditions are in [listing-checkout-release-0.2.0.md](listing-checkout-release-0.2.0.md) and its linked audit/runbook. Earlier real-deployment evidence remains historical and does not establish this new candidate as deployed or processor-verified.
