## Current skill 0.5.14 — verified public API client compatibility

Portable skill **0.5.14** is published on GitHub and ClawHub. The merchant backend remains **0.5.13**. Public production discovery returns all five choices with $6 single-trip and $11 round-trip fee labels. PowerShell HTTP/2, Node fetch, Python urllib and native curl returned HTTP 200 JSON anonymously; PowerShell HTTP/1.1 still returns a hosting HTML challenge. Private booking lookup remains protected with HTTP 401. No hosting or payment settings changed. See [the client comparison and diagnostic](public-api-client-compatibility-0.5.14.md).

All eight authored registry files and an isolated download match the tagged source. Record exact-version security findings from [publication evidence](skill-publication-0.5.14.json); no previous audit certifies this version. The buyer's bot HTTP access, active skill and renderer remain unverified. No booking, order, payment, refund or email was created during the fix.

# Publication status

## Previous skill 0.5.13 and current backend 0.5.13

Agent Commerce **0.5.13** and Seller Orders **1.1.6** are deployed with owner-approved prepaid sharpening requests, seller cancellation/refund receipt handling, administrator pickup ZIP controls and capacity 4. The [0.5.11 storefront adapter](storefront-sharpening-checkout-0.5.11.md) brings ordinary sharpening-cart checkout into the shared booking flow and preserves other cart products. The [0.5.10 review and coupon patch](booking-review-coupon-0.5.10.md) adds “nothing due now”, working review controls and a green native coupon panel. The [0.5.7 implementation](booking-trip-fees-two-screens-0.5.7.md) advertises five choices, with unpaid customer drop-off/collection last. One pickup or delivery costs $6 per order; pickup plus delivery is $11. New trip fees are taxable native fees, independent of the parcel shipping policy. Existing records retain their original financial treatment.

“Your knife game plan” leads directly to KnifeRevive secure payment, preserving the ordinary store cart. The compact expandable review removes repeated technical states. Different billing details do not replace the consent-bound service address. Unpaid customer drop-off skips ZIP checking; all four prepaid choices require eligible coverage and completed payment before seller submission. Production source and settings match the tested package. See [the evidence](booking-trip-fees-two-screens-0.5.7.json).

GitHub and ClawHub skill versions are **0.5.13**. Both trip prices are editable under **WooCommerce → Agent Commerce → Sharpening trip fees**. The [0.5.13 controls and live label verification](trip-fee-settings-0.5.13.md) preserve old bookings and other settings; all 216 fee/checkout assertions passed. Visible labels omit “/order” while the explanatory text states once-per-order charging. The [exact-fee display update](skill-publication-0.5.13.md) requires numeric amounts in every bot button, including $6 single trip and $11 round-trip combo, with unpaid last. Registry latest, the public listing and exact-version audit are verified; the audit shows **Pass**, clean/benign high-confidence review and no warnings at this observation. All eight registry files and a fresh isolated CLI installation match the immutable `skill-v0.5.13` source. A later full verification now passes with the registry-generated Skill Card available. Additional SkillSpector scanning has five medium caution findings while overall registry review remains clean/benign. Server-resolved provenance is unavailable. See the [current recheck and bot label diagnosis](bot-label-diagnostic-0.5.13.json). No complete certification is claimed. See [publication evidence](skill-publication-0.5.13.md). Actual processor settlement, headless API access and the buyer bot's installed runtime remain unverified; earlier anonymous API traffic received a hosting browser challenge. Lightning readiness remains false. See [the payment runbook](prepaid-booking-0.5.1.md) and [the historical four-mode correction](four-api-modes-0.5.6.md).

## Historical 0.4.2 publication and backend deployment

The newest publication is [0.4.2](skill-publication-0.4.2.md), published after the
owner's explicit request. Its public audit shows Pass, clean/benign security and
no registry warnings; all eight registry and isolated-install source hashes
match the immutable GitHub release. Full verification separately reports
`card.missing`; generated-card and server-resolved provenance remain unavailable.
VirusTotal/SkillSpector reports are null rather than completed passing checks.
The previous [0.3.0](booking-publication-0.3.0.md) card/audit evidence is historical.
The incremental **plugin** 0.4.0
was deployed after owner approval and [patched to 0.4.1](booking-patch-0.4.1.md),
with [owner-approved unpaid order creation now enabled](booking-live-unpaid-0.4.1-2026-10-08.md).
The [0.4.2 visibility/capacity patch](seller-booking-visibility-0.4.2.md) adds
the linked request to the custom Seller Orders → Local Pickup screen and a
dedicated daily-capacity control. Owner-approved capacity is 4 jobs per open day.
Seller Orders 1.1.5 is a four-file overlay against its live 1.1.4 baseline.
One marked live request created pending Local pickup order #2980 and three
notifications accepted by the mailer. The owner reports inbox arrival; the
connected mailbox verifies a seller message for that new reference. Three
distinct recipient inbox receipts and the owning-seller live login remain
unverified. The test remains requested and unpaid. The
unpublished 0.4.0 **skill** candidate was superseded by 0.4.2; no payment activation occurred at that stage.
See [deployment observations](booking-deployment-0.4.0-2026-10-08.md) and
[incident and readiness notes](incremental-booking-0.4.0.md).
Older sections below are retained as historical evidence.

## Current skill version 0.1.2 — October 8, 2026

Documentation-only installation/onboarding release, published under the owner's existing GitHub/ClawHub authorization. [GitHub release and ZIP](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/skill-v0.1.2), [public listing](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge), and [version-specific security audit](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge/security-audit?version=0.1.2) are live. Registry latest and the public Versions view show 0.1.2. The exact title, Finance/Lifestyle categories, topics and publisher remain unchanged.

The public SKILL.md displays a distinct installation section. Its relative reference link is rewritten to ClawHub's file API; Files exposes `references/installation.md` as readable raw Markdown, and the absolute versioned GitHub fallback renders the guide. All six registry file hashes match release source commit `474c3f6e2bfc77a65cce40e7866d4d31d9022765`.

Public audit outcome: **Pass**; registry security: **clean / benign, high confidence**. Static analysis has no findings. The separately generated card is currently unavailable: CLI verification returns `card.missing`, the `--card` request returns unavailable, and the public listing omits the Skill Card tab for this version. Server-resolved GitHub-import provenance is unavailable. No generated card was authored or uploaded. Scanner report absence is not a completed passing scan. Exact evidence and report archive hash: [security-scan-0.1.2.json](security-scan-0.1.2.json).

Source is pushed on review branch `docs/concierge-installation-0.1.2`; [draft review PR](https://github.com/svetlyoh/kniferevive-agent-commerce/pull/1) is open, with main unchanged. [Installation validation and platform limits](installation-validation-0.1.2.md) records actual checks. Merchant plugin, APIs, payment flags and booking behavior are unchanged.

On October 8, the older 0.1.1 generated card was independently rechecked and verification now passes. The following October 7 card-missing observation is retained as historical evidence, not a current guarantee.

## Historical metadata release 0.1.1 — October 7, 2026

The public listing is **KnifeRevive Concierge - SF Bay Area Sharpening, AI Tech**. Authored categories are **Finance** (shopping/commerce) and **Lifestyle** (cooking/home), replacing Uncategorized/Other. Topics: `knife-sharpening`, `sf-bay-area`, `ai-tech`, `shopping`. The public page and registry latest tag both show **0.1.1**.

[Listing](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge) · [Skill ZIP and manifest](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/skill-v0.1.1) · [Version-specific audit](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge/security-audit?version=0.1.1). The security audit reports **Pass**, registry security is **clean / benign, high confidence**, and static analysis reports no suspicious patterns. All five registry file hashes match source commit `4ec141b`; the retained scan archive hash and exact verification are in [security-scan-0.1.1.json](security-scan-0.1.1.json).

The separate generated Skill Card remains missing (`card.missing`), and server-resolved import provenance remains unavailable; full verification/certification is not claimed. Merchant consent, privacy, capability checks and payment safeguards are unchanged. Backend 0.1.3 is installed locally and on production with a dedicated test webhook; agent payments/courier bookings remain disabled pending operational facts and actual processor verification.

## Historical initial publication: 0.1.0

Owner authorization: October 7, 2026, to push the source to GitHub and publish the skill on ClawHub using the owner's GitHub sign-in.

GitHub and ClawHub authentication both identify publisher `svetlyoh`.

- [Public GitHub source](https://github.com/svetlyoh/kniferevive-agent-commerce).
- [GitHub prerelease and downloads](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/v0.1.0), including plugin/skill ZIPs, SHA-256 manifest and the actual ClawHub report archive.
- [Public ClawHub skill](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge), version **0.1.0**.
- [Version-specific security audit](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge/security-audit?version=0.1.0).

Publication used existing GitHub-backed publisher authentication and ClawHub CLI **0.23.3**. The initial upload was held pending automated scans; public registry inspection subsequently returned owner `svetlyoh`, version `0.1.0`, `latest` pointing to `0.1.0`, all five skill files and no malware/suspicious block.

**Observed security result: clean / benign, high confidence, no warnings.** Static analysis also reported clean, with no findings or reason codes. The downloaded scan manifest reports `succeeded`, with results written back. VirusTotal and SkillSpector retained reports were null, so they are not claimed as completed passing checks. These results are evidence for this exact version, not a certification or guarantee.

**Separate Skill Card verification remains incomplete.** `clawhub skill verify @svetlyoh/kniferevive-concierge --version 0.1.0` reports `ok: false`, `decision: fail`, reason `card.missing`, while its `security.status` is `clean` and `security.passed` is true. The registry has not provided a generated `skill-card.md`; this is not a malicious-content finding. The verification envelope also reports no server-resolved GitHub-import provenance. CLI source metadata was supplied, and every registry file hash was independently compared with the linked GitHub source commit, but that comparison does not replace a server-issued card/provenance record.

Released source commit: `e69fb02c00eb44ff73841124ef13ed5d081f609f`, skill path `skills/kniferevive-concierge`. Source fingerprint: `dee962a88821983239354d17799b23bdcb4512ace8d39d67621ace8d02447bb2`. Version ID: `k976gb4tvqdh4pqtmfpq2aajx58fvsns`. Submission attempt: `zx71f3be0pymghhmhpgkk2kky18fv158`.

The exact scanner snapshot, file hashes, card/provenance limitation, audit URL and report archive hash are recorded in [security-scan-0.1.0.json](security-scan-0.1.0.json). Later documentation commits do not change the immutable published skill version or its linked release commit.

At the time of the initial publication, the merchant plugin was inactive in Local Sites. Subsequent backend setup is recorded above and in merchant setup evidence; publication itself does not prove working checkout. The skill must check capabilities and use the merchant pages on disabled capabilities.

## Unpublished native listing candidate — 8 October 2026

Source plugin/skill 0.2.0 is a review candidate with disabled-by-default native handoff. Installed backend remains 0.1.3 and the registry skill remains 0.1.2. No 0.2.0 tag, GitHub release, ClawHub publication, generated card or external security certification was created. The earlier audit does not certify these changes. See listing-checkout-release-0.2.0.md for tested behavior and outstanding processor/staging proof.
