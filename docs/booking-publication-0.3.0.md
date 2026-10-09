# Booking skill publication 0.3.0

The [KnifeRevive Concierge skill](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge)
is published as **0.3.0**, with the requested title, **Lifestyle** category and
knife-sharpening, sf-bay-area, ai-tech and shopping topics. All eight submitted
source files match the registry's SHA-256 hashes. The registry also supplies a
generated skill-card.md.

The [audit](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge/security-audit)
shows **Pass** for 0.3.0. Overall moderation and VirusTotal are clean; the AI
review rates it benign with high confidence. SkillSpector still reports three
findings: two Medium (installer prompt authorization and marketplace category
scope) and one Low (license reuse text). Its aggregate severity is LOW. The AI
review explains why these excerpts do not grant additional runtime authority.
This is a pass with warnings, not a claim that every scanner found zero issues.
The sanitized metadata and source hash comparison are preserved in
[publication results](booking-publication-0.3.0.json).

## Install the release from GitHub

Use the [complete versioned skill directory](https://github.com/svetlyoh/kniferevive-agent-commerce/tree/skill-v0.3.0/skills/kniferevive-concierge)
or [download the complete skill ZIP](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/download/skill-v0.3.0/kniferevive-concierge-0.3.0.zip).
Import SKILL.md together with its references using the destination agent's
supported import process. Providing a URL to a chat is not proof of installation.
The installer reference inside 0.3.0 retains an older source link; the pinned
links above point to the correct release. The published release and tag are
preserved rather than silently overwritten.

## Live merchant readiness

The [booking page](https://kniferevive.com/?krev_agent=booking) accepts requests.
Daily capacity is unknown, so a request is not a confirmed reservation.
Prepaid preferences, county restrictions and $7.99 merchant-trip pricing are
configured, but actual prepayment and wallet payment remain disabled until
capacity, public cancellation/refund terms, native seller/shipping accounting
and real gateway verification are complete. No charge, refund, wallet send or
production Stripe test-mode switch occurred.

Authorized wallet support pays an existing verified native Lightning invoice.
Native order/invoice preparation still requires checkout; autonomous order
creation is not implemented. See the [merchant runbook](booking-release-0.3.0.md)
and [deployment evidence](booking-deployment-2026-10-08.md).
