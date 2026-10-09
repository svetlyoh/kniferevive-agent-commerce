# Clear pickup and delivery labels — 0.5.8

The owner requested concise bot choices that explicitly identify who transports each leg and show exact trip fees. Skill and live handoff-menu wording now uses “they” for KnifeRevive and “at shop” for collection at the advertised location. Prepay is explicit on the first four choices; the unpaid drop-off/collection choice remains last. Bot instructions require service subtotal + numerical trip fee on every button, including $0, and prohibit vague “+ fee” text or omission of the collection leg.

This is a copy-only update. The five API modes, coverage, $6 single trip, $11 comeback combo, taxable per-order fees, existing orders and authorization/payment hooks are unchanged. Checkout and receipt handoff descriptions use the same perspective. The selector price suffix is shorter.

Changed PHP and JavaScript pass syntax checks; skill frontmatter and all packaged source files/references validate. The previous 1,416 behavioral assertions belong to 0.5.7 and were not rerun for this wording-only patch. Native WordPress upload/replace reports “Plugin updated successfully”. A fresh public load uses 0.5.8 assets and shows all five revised labels. Selecting two $5 small knives displays $10 sharpening plus $0/$6/$6/$11/$0 trip fees. No production booking or payment was submitted.

See [live evidence](booking-clear-labels-0.5.8.json) and [package manifest](release-manifest-0.5.8.json). GitHub skill version is 0.5.8; ClawHub remains 0.4.2 and no new registry audit is claimed. An installed bot must reload the current GitHub skill to pick up the instructions; merchant-side deployment does not rewrite an already loaded bot skill.
