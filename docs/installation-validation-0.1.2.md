# Installation documentation validation — skill 0.1.2

Reviewed October 8, 2026. Publication status: **published** to GitHub and ClawHub; source review PR remains open, main unchanged.

## Change and boundaries

Added `references/installation.md` with Linux terminal, Windows PowerShell, Meta Muse personal-agent, Grok Bot, and OpenAI dot paths. Muse Code is explicitly a separate developer-tool option. SKILL.md includes a short human-facing install section and both relative and versioned source links; README and discovery documentation link to the guide. Publisher, slug, title, categories and topics are preserved.

The operational SKILL.md body and API, sharpening and payment reference files compare unchanged against the previous commit, apart from version metadata. No merchant code, configuration, payment or booking behavior changed. No root `skill-card.md` was authored or included.

## Checks performed

- Existing skill quick validator: passed.
- Existing package builder: passed. Skill ZIP contains exactly six expected files; every archived byte matches source. No credentials, local configuration or generated Skill Card entered the package.
- Local Markdown link checks and `git diff --check`: passed.
- PowerShell snippets: parser passed. Bash snippets contain only comments and ordinary `openclaw` commands; manually reviewed for shell compatibility.
- Installed ClawHub CLI 0.23.3 publish dry-run: `would-publish` 0.1.2, six files; fingerprint `bb4f52d2e8f623a217ccfc0f4f9e4e1fbaed82a1f4a7313419b68a1272dcd2b7`.
- Current official documentation confirms the native install, shared install, readiness and card verification commands; the installed ClawHub CLI help confirms publication options.

## Platform evidence and limits

Sources reviewed: [OpenClaw skills](https://docs.openclaw.ai/tools/skills), [ClawHub CLI](https://docs.openclaw.ai/clawhub/cli), [Muse Code local skills](https://dev.meta.ai/docs/muse-code/extending), [Muse personal agent](https://ai.meta.com/muse/), [Grok Bot private skills](https://docs.x.ai/grok-bot/skills-routines-and-automations), and [OpenAI dots and connected computers](https://learn.chatgpt.com/docs/dots/computers-and-apps).

OpenClaw is not on this computer's PATH; runtime installation/readiness was not tested, and no runtime was installed merely to test documentation. On an existing supported OpenClaw installation, the installer should run:

```text
openclaw skills install @svetlyoh/kniferevive-concierge
openclaw skills check
openclaw skills verify @svetlyoh/kniferevive-concierge --card
```

Muse, Grok Bot and dot adoption prompts have not been executed in those platforms and do not prove a native ClawHub installation. Consumer Muse import support was not independently established; its prompt explicitly falls back to supported saved instructions. Muse Code documentation supports local folders, and Grok Bot supports private skills; neither is an OpenClaw runtime. A dot needs an explicitly connected, permitted computer for local skills.

## Generated card and publication evidence

Pre-release recheck of published 0.1.1 now returns registry verification `ok: true`, `decision: pass`, generated card available. Earlier `card.missing` observations remain historical. Server-resolved GitHub-import provenance is still unavailable. Author-written installation help is separate from the registry-generated trust card. No audit result is an evergreen guarantee.

### Released 0.1.2 results

- [GitHub skill release](https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/skill-v0.1.2) and [tagged installation guide](https://github.com/svetlyoh/kniferevive-agent-commerce/blob/skill-v0.1.2/skills/kniferevive-concierge/references/installation.md) are live; guide HTTP 200 and rendered content verified.
- ClawHub owner, latest tag, title, Finance/Lifestyle categories and topics confirmed. Versions shows 0.1.2 as Latest. SKILL.md shows human installation guidance and both reference links. Files lists six files; the installation reference opens as raw Markdown with all five paths. The relative guide link is rewritten to the first-party file API; GitHub supplies a rendered fallback.
- All six registry SHA-256 hashes match source commit `474c3f6e2bfc77a65cce40e7866d4d31d9022765` and the packaged skill. Registry security is clean/benign, high confidence; version-specific public audit shows Pass. Scan archive manifest succeeded/written back; static analysis has no findings. Other retained scanner reports are null, so are not claimed as completed passing checks.
- The actual Skill Card tab is absent for 0.1.2. Registry verification fails only with `card.missing`; `clawhub skill verify @svetlyoh/kniferevive-concierge --version 0.1.2 --card` reports unavailable. Server-resolved GitHub import provenance is also unavailable. This remains a registry follow-up, independent from the passing security audit. No generated card was included in source or ZIP.
- [Exact registry/scan evidence](security-scan-0.1.2.json); [draft review PR](https://github.com/svetlyoh/kniferevive-agent-commerce/pull/1). Documentation changes were pushed to the review branch; no protected branch or production settings were changed.
