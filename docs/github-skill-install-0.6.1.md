# GitHub distribution and existing-skill identity

October 10, 2026, America/Los_Angeles.

Portable skill **0.6.1** is available from the existing GitHub repository and
immutable `skill-v0.6.1` tag. The existing ClawHub listing remains **0.6.0**:
the server-side GitHub import failed before review/publication. No duplicate
listing, alternate publisher, fork or local ClawHub upload was created for 0.6.1.
The merchant plugin remains 0.6.0.

## Delivered source and installation guidance

Source changes in `df7c4744fd3195384115ae155a7618adc444a344` add instructions to
update the existing host installation, preserve `kniferevive-concierge` identity,
retain one active copy, and check workspace/shared copies and session reload.
If a host importer supports only a separate new skill, it must explain that
limitation before creating another active copy. A registry source URL and a
host's supported update mechanism are separate from publication provenance.

The complete source is
https://github.com/svetlyoh/kniferevive-agent-commerce/tree/skill-v0.6.1/skills/kniferevive-concierge.
The release is
https://github.com/svetlyoh/kniferevive-agent-commerce/releases/tag/skill-v0.6.1.
The tag points to manifest commit `9d697663a1265085fdf8e649a497e185379d2b7e`.
Skill frontmatter validation passed; all nine GitHub-hosted files independently
match the Git source/package manifest hashes. These were instruction changes;
no payment, shipping or booking implementation was modified.

## ClawHub import observation

The owner signed into the existing `svetlyoh` publisher account in the browser.
The Import from GitHub screen discovered the original repository's skill.
It selected six candidates by default; those were cleared and only KnifeRevive
was selected. Repository refresh observed the updated GitHub source. Clicking
Review selected failed in `githubImport:previewGitHubImportCandidate` with a
server error; a clean page reload and single-candidate retry failed again.
Observed request references were `7d04c912e9836baf` and `333e8d391bcb9362`.
At the owner's request, three additional controlled retries also failed: a
direct retry, a retry after Update list, and a clean-reload retry with only
KnifeRevive selected. Their request references were `0b7ce426b11b2982`,
`70de5cf05aeb4905` and `2bed9948e94b4477` (17:07–17:09 UTC, October 10).
All five observed attempts failed at the same preview action before review.
The error did not explain its cause. No claim about the underlying cause is made.

The form never reached the version/owner/license/publish review. No final publish
action occurred. The registry was independently read and still reports existing
owner `svetlyoh`, slug `kniferevive-concierge`, latest 0.6.0. Server-resolved GitHub
provenance for a new 0.6.1 version is therefore **not established**. A local CLI
upload with source metadata would not fulfill the requested server-side import,
so it was not used as a substitute. No automatic synchronization was configured.

When ClawHub's importer is working, select only the original repository's
`skills/kniferevive-concierge`, retain owner `svetlyoh`, slug
`kniferevive-concierge` and display name `KnifeRevive Concierge`, and publish an
unused version under that existing listing. Inspect the source commit and all
nine files. Do not accept a suggested `-2` slug or fork to bypass a conflict.
Then check exact-version provenance, hashes, scan and generated-card status.
No exact-version ClawHub scan/verification for 0.6.1 is claimed while unpublished.

## Muse prompt

> Update my existing KnifeRevive Concierge skill in place from
> https://github.com/svetlyoh/kniferevive-agent-commerce/tree/skill-v0.6.1/skills/kniferevive-concierge.
> Import the complete folder and references, preserve the existing skill identity,
> and keep one active copy. Do not create a new skill or duplicate standing
> instruction. Verify active version 0.6.1 and reload it. If switching the existing
> installation's source is unsupported, tell me before making any duplicate.

The supported Muse import/update facility, actual installed identity, renderer
and browser tools remain host-specific and were not verified on the user's Muse
account. The prompt is a request, not proof of an installed skill.

Packages, complete skill folder, hashes and the importer screenshot are saved in
`C:\Users\Svet\Documents\KnifeRevive_Documentation\releases\concierge-0.6.1`.
