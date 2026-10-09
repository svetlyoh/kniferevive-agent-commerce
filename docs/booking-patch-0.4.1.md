# Deployed asset patch 0.4.1 — October 8, 2026

**Production plugin: 0.4.1. This patch record describes the initially gated state.**
Subsequent owner approval and the unpaid live test are recorded in
[the current enablement evidence](booking-live-unpaid-0.4.1-2026-10-08.md).
The owner-approved 0.4.0 deployment is recorded in
[the deployment report](booking-deployment-0.4.0-2026-10-08.md).
Live visual verification then found the unversioned stylesheet response still
contained the old 0.3.0 CSS (1,041 bytes). The 0.4.1 patch adds `ver=0.4.1` to
the private wrapper's CSS and JavaScript URLs so updated files receive fresh
asset URLs. It changes no booking, authorization, seller or payment rules.

WordPress's native updater confirmed replacement of 0.4.0 with 0.4.1. The live
entry reads 0.4.1 and matches source. The canonical booking page and a contact-free
coverage POST return versioned assets, a flex header and the correctly positioned
skip link. The observed versioned stylesheet's bytes match packaged source.
One Large Knife still estimates $7 plus $0 merchant trips. No booking/order was
submitted, no email sent, and production settings exactly match the approved
0.4.0 post-timing snapshot. The original request remains in the inbox.

PHP syntax passed for both changed files. Live source/settings comparison and
observed stylesheet hashing verified this narrow patch. The earlier 1,030 local
assertions cover booking logic, which this patch does not change. Automated REST
access, the separate seller login, native order accounting, deployed notification
workers and real inbox receipt remain unverified as described in the deployment
report. COD selection and one live unpaid test/recipient approval remain pending.

The 0.3.0 production-source rollback ZIP/settings and the prior 0.4.0 ZIP are
preserved locally. Restore the compatible plugin with order creation disabled
without deleting durable records. No new ClawHub skill release/audit or payment
activation occurred; the registry remains 0.3.0.

See [patch verification](booking-patch-0.4.1.json),
[artifact manifest](release-manifest-0.4.1.json) and
[current public form](screenshots/booking-live-0.4.1.jpg).
