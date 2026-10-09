# Four-choice discovery correction — 0.5.5

The public human booking form already lists four choices. `/booking-options` also contains four `handoff_options`, while its backward-compatible `modes` array has three internal identifiers. Options 3 and 4 share `prepaid_pickup` but use different `return_mode` values. A bot that builds its menu from `modes` alone omits the pickup-plus-delivery choice.

The skill entrypoint now includes a four-row customer-choice table and explicitly warns against equating API mode count with choice count. The booking reference explains the same distinction. A hosting HTTP 403/non-JSON challenge must result in a first-party handoff, not a claim that only three choices or no prepayment are available.

The introductory section of AI.md still contained the old three-mode wording and $7.99 fee despite its later four-choice section. This guide correction removes that contradiction and packages the corrected guide in plugin 0.5.5. The plugin's checkout, payment, booking and refund implementation is unchanged from 0.5.4; no production settings or legacy orders are altered.

The skill and plugin declare 0.5.5. Current-source installation uses `main`; older immutable commits remain unchanged. The previously used feature branch is advanced without rewriting history so branch-based installers no longer see only `b80efa7`. ClawHub publication remains 0.4.2, with no new audit/certification claim.

Validation includes skill frontmatter, actual OpenAPI booking example, relative-reference targets, file-for-file ZIP packaging, contract/document synchronization, PHP syntax, deployed source readback and the visible public four-choice form. Real processor and direct wallet settlement remain unverified. The bot's installed copy and runtime context cannot be inspected from this chat; its exact commit and full response were requested to distinguish remaining stale installs from rendering errors.

## Deployment verification

Native WordPress upload/replace showed current 0.5.4/uploaded 0.5.5 and “Plugin updated successfully.” The deployed plugin header reports 0.5.5. The AI.md asset read back through the authorized source editor matches SHA-256 `306ef3e530b47ad42075c3def2436b71c0f30c32fceee96d074e6791d61c8b39`, the packaged source hash. Its introduction lists four customer choices and no longer contains $7.99. Direct anonymous HTTP requests still return 403 and the browser blocked opening AI.md; public HTTP delivery is therefore not claimed as verified. No access protection was bypassed.

Native hosting caches were cleared. The public form visibly exposes all four choices with $6/$11 transport. Stored capacity 4, pickup 600 cents, combined transport 1100 cents, prepaid enabled and payment-before-submission true were read back without saving any settings. No production booking, payment, refund or test email was created.

ZIP comparison against 0.5.4 proves the only plugin changes are the AI.md asset and version header. The existing checkout/auth/payment logic is byte-identical. This is a discovery/documentation correction; the 0.5.4 behavioral results remain historical evidence, not newly rerun money-flow tests.
