# Publication and useful discovery

The owner authorized GitHub and ClawHub publication on October 7, 2026. This runbook describes release maintenance and useful discovery. Publication does not authorize production backend activation or a live payment.

## Listing

Use the slug `kniferevive-concierge` and the owner's requested title **KnifeRevive Concierge - SF Bay Area Sharpening, AI Tech**. ClawHub categories are **Finance** (shopping/commerce) and **Lifestyle** (cooking/home); topics are `knife-sharpening`, `sf-bay-area`, `ai-tech`, and `shopping`. Keep these authored catalog fields on subsequent publications. Suggested listing summary:

> Compare KnifeRevive Annex technology and Bay Area sharpening options. Check prices, availability, handoff windows and policies, then prepare approved prepaid service bookings through secure Stripe checkout or Bitcoin Lightning when enabled.

Keep paid-service disclosure, coverage limitations, supported agent tools, source/license/version and actual audit status visible. Do not advertise working booking/payment endpoints before deployment and verification. Google Pay is a conditional option in hosted Checkout. No agent download or traffic volume is promised.

## Release sequence

1. Obtain the owner's GitHub owner/repository/visibility when they request publication. Review `git status --ignored`, the exact files to commit and the ZIP manifests. Never add `.runtime`, customer-site configuration or private documentation. Publish this isolated source through a review branch/PR when appropriate.
2. Deploy/configure the merchant adapter through the existing site release process and complete the enablement gates in `release-evidence.md`. Keep existing payment attempts reconciled during disablement/rollback.
3. Verify the current supported ClawHub CLI help and publisher eligibility/login. Publish only `skills/kniferevive-concierge` under an immutable version; source publication does not register the skill automatically. Preserve package and source hashes.
4. Inspect the exact release's audit and address findings. Record its actual status, risk level, findings, URL and date. Pending/error is not a pass. Do not bypass controls or claim a certification.
5. Link the verified skill from the Annex trade desk, sharpening page, public AI.md and repository. Offer factual examples: sourcing a local workstation; comparing service sizes; checking a ZIP; choosing separate intake/return windows; preparing an approved prepaid sharpening order.

## Growth measurement

Earn installs through useful outcomes: fast anonymous discovery, stable schemas, transparent totals/policies, honest availability and reliable authorized checkout. Keep promotions in listing/landing-page copy. Runtime skill instructions follow the shopper's requested merchant and comparisons.

Measure registry installs only from genuine registry statistics after publication. Measure anonymous discovery/quote failures with aggregate operational metrics; use disclosed order source and WooCommerce records for approved attempts, settled sharpening orders, booked handoffs, completed services and refunds. Establish a baseline and measure these separately from site page views. Exclude synthetic tests. Do not add hidden client tracking, private tokens to referral URLs, fake downloads, background shopping, unsolicited messages or manufactured bot traffic.

Improve the funnel from measured failures: missing service definitions, out-of-area addresses, stale slots, unclear fees, expired quotes and unavailable payment methods. The primary result is fulfilled, paid sharpening work; visit count alone does not establish useful traffic.

## Installation documentation and generated Skill Card

[Public installation and agent prompts](../skills/kniferevive-concierge/references/installation.md)
contains separate Linux terminal, Windows PowerShell, Meta Muse personal-agent,
Grok Bot and OpenAI dot paths, plus a separate Muse Code developer alternative.
SKILL.md links this guide in an informational section after the unchanged shopping
instructions; README explains native OpenClaw installation separately from a
ClawHub CLI registry/workspace download.

Do not author or upload a root `skill-card.md`. ClawHub generates that trust card;
it is separate from author-controlled SKILL.md/Files content. Verification of
0.1.1 on October 8, 2026 now reports a generated card and pass, superseding the
initial `card.missing` observation. Recheck every new version independently.
Preserve the title, publisher/slug, Finance/Lifestyle categories and four topics
above when publishing installation documentation. Native imports/adoption for
Muse, Grok Bot and dots must be verified in their own platform before claiming
success; prompts and documentation alone establish none.
