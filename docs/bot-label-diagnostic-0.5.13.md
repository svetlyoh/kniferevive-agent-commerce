# KnifeRevive v0.5.13 — incorrect bot option labels

The supplied screenshot has pay-at-pickup first and omits the numeric fees on delivery, pickup and combo buttons. These violate the published v0.5.13 instructions. The screenshot alone cannot distinguish stale instructions, a skill that was not invoked, a separate button renderer, or a model that read the skill and failed to follow it.

GitHub main and ClawHub latest were checked. All eight authored registry files match the immutable `skill-v0.5.13` source. The generated Skill Card has no conflicting menu. The PC's usual OpenClaw and Codex skill directories contain no active KnifeRevive installation; that does not identify the phone bot's remote installation.

Source: https://github.com/svetlyoh/kniferevive-agent-commerce/blob/skill-v0.5.13/skills/kniferevive-concierge/SKILL.md

ClawHub: https://clawhub.ai/svetlyoh/skills/kniferevive-concierge

## Prompt for the affected bot

Copy the following into the affected bot. This is a display diagnostic, not permission to book or pay.

```text
Diagnose your KnifeRevive Concierge v0.5.13 display. Read the active skill's
SKILL.md and references/booking.md using your supported tools. Report which
file or URL you actually read and the version you found. If you cannot read
the active skill, say so; a version number or installation message alone
does not prove its instructions were loaded.

Render a dry-run menu for one 5-inch knife: $5 sharpening, using the currently
published $6 one-trip fee and $11 pickup-plus-delivery combo. These are estimates
before applicable tax and require live checkout verification. Use these exact
five button labels in this order; if buttons are unavailable, show a numbered
list with the same labels:

1. You drop off + collect · prepay — $5 + $0 trip fee
2. You drop off → they deliver · prepay — $5 + $6 trip fee
3. They pick up → you collect at shop · prepay — $5 + $6 trip fee
4. They pick up + deliver · comeback combo — $5 + $11 round-trip fee
5. You drop off + collect · pay at pickup — nothing due now · $0 trip fee

Keep the dollar amounts in the actual buttons. “+ fee”, “+ fees”, and
“+ trip fee” without a dollar amount are incomplete labels. Explain separately
that trip fees are charged once per order and sharpening is payable at pickup
for option 5. This diagnostic ends with the displayed menu; it submits no
booking and sends no payment.
```

## Interpreting the response

- No supported file read: determine the bot host's actual import/invocation mechanism. Reading a Skill Card or repository summary is insufficient.
- Active file is older: update that specific installation through the host's supported flow and verify the active copy; preserve unrelated skills and credentials.
- Active file is v0.5.13 but the generated menu is wrong: inspect the host's separate button builder, cached examples, and instruction precedence. Regenerate labels from current `handoff_options` rather than an old menu. This requires the bot/app name and access to its configuration or code.
- Plain text is correct but buttons are wrong: the button renderer is rewriting or truncating the labels. Fix its label construction and validate what is visible on screen.

The merchant plugin already has editable single-trip and combo settings at WooCommerce → Agent Commerce → Sharpening trip fees. New API labels include numeric amounts. Anonymous API reads can still receive the hosting 403 challenge, so bots must distinguish published estimates from live checkout totals. A skill update cannot remotely force another app to reload instructions or obey its button templates.
