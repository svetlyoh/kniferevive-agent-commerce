# KnifeRevive Concierge 0.5.13 — current merchant trip fees

Published as latest on [ClawHub](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge) and in the [GitHub skill release](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/skill-v0.5.13). Customer choices say **$6 trip fee** or **$11 round-trip fee**, without “/order”; explain once-per-order charging separately. Bots fetch current options before displaying new choices and use the merchant's live settings. [Plugin 0.5.13](trip-fee-settings-0.5.13.md) provides the dedicated dollar-value editor. Existing booking snapshots remain unchanged.

All eight registry files and a fresh isolated CLI download match immutable `skill-v0.5.13` source. The exact-version [public audit](https://clawhub.ai/svetlyoh/skills/kniferevive-concierge/security-audit?version=0.5.13) shows Pass and registry review is clean/benign with high confidence and no warnings. Static analysis has no findings. VirusTotal/SkillSpector completed passing results are not claimed.

Separate full verification still reports `card.missing`; generated-card, resolved-source provenance and full trust certification remain unavailable. No security gate was bypassed. See [sanitized verification evidence](skill-publication-0.5.13.json).

Update/import 0.5.13 and start a new bot session to load the revised instructions. This publication does not remotely reload a third-party bot or guarantee its renderer obeys labels. Anonymous merchant API reads still receive a hosting 403 challenge; the skill distinguishes published estimates from verified final checkout totals and offers the human booking form. No real production booking, payment, refund or notification was created in this update.
