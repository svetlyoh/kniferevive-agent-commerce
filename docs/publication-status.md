# Publication status

## Current skill version 0.1.1 — October 7, 2026

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
