# Custom-plugin email send-path coverage

Live inventory was read October 29, 2026. This read-only source scan inventories call sites, not actual deliveries. Matching plugin versions do not prove identical server files. No dependencies in vendor/tests were treated as standalone senders.

- **kniferevive-ads-controller** — [Settings.php:155](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/kniferevive-ads-controller/includes/Settings.php:155). Local version matches live: True.
- **kniferevive-agent-commerce** — [BookingOutbox.php:81](C:/Users/Svet/Documents/KnifeRevive_Documentation/email-catalogue-2026-10-29/version-matched-source/kniferevive-agent-commerce/includes/BookingOutbox.php:81). Local version matches live: True.
- **kniferevive-dispatch** — [class-krd-adapters.php:79](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-dispatch/includes/class-krd-adapters.php:79). Local version matches live: True.
- **kniferevive-order-address-contact** — [store.php:75](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-order-address-contact/includes/store.php:75). Local version matches live: True.
- **kniferevive-order-messenger** — [class-kr-order-message-admin.php:47](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-order-messenger/includes/class-kr-order-message-admin.php:47). Local version matches live: True.
- **kniferevive-order-messenger** — [class-kr-order-message-mailer.php:4](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-order-messenger/includes/class-kr-order-message-mailer.php:4). Local version matches live: True.
- **kniferevive-order-messenger** — [class-kr-order-message-mailer.php:51](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-order-messenger/includes/class-kr-order-message-mailer.php:51). Local version matches live: True.
- **kniferevive-reminders** — [service.php:339](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-reminders/includes/service.php:339). Local version matches live: True.
- **kniferevive-seller-onboarding** — [class-notifications.php:50](C:/Users/Svet/Documents/KnifeRevive_Documentation/onboarding-hotfix-dispatch-v4-2026-10-23/src/kniferevive-seller-onboarding/includes/class-notifications.php:50). Local version matches live: True.
- **kniferevive-seller-orders** — [class-krev-order-cancellations.php:115](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-order-cancellations.php:115). Local version matches live: True.
- **kniferevive-seller-orders** — [class-krev-order-contact.php:55](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-order-contact.php:55). Local version matches live: True.
- **kniferevive-seller-orders** — [class-krev-order-contact.php:82](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-order-contact.php:82). Local version matches live: True.
- **kniferevive-seller-orders** — [class-krev-return-mail.php:48](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-return-mail.php:48). Local version matches live: True.
- **kniferevive-seller-orders** — [class-krev-sharpening-workflow.php:127](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-sharpening-workflow.php:127). Local version matches live: True.
- **kniferevive-seller-orders** — [class-krev-shipment-mail.php:54](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-shipment-mail.php:54). Local version matches live: True.
- **kniferevive-shipkit** — [Notices.php:23](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-shipkit/includes/Notices.php:23). Local version matches live: True.
- **kniferevive-stripe-connect** — [class-kr-connect-settlement-events.php:82](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-stripe-connect/includes/class-kr-connect-settlement-events.php:82). Local version matches live: True.

## Limits

Core function calls can delegate to WooCommerce/account flows; no extra subject is assumed. Third-party hosted mail, saved snippets, WordPress.com templates and MailPoet database copy remain configuration/history audit gaps. No test message or mail queue was executed.
