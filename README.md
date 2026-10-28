# Simple Email Scheduler

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v3%2B-green.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
[![WordPress.org](https://img.shields.io/badge/WordPress.org-simple--email--scheduler-orange.svg)](https://wordpress.org/plugins/simple-email-scheduler/)

**WordPress plugin to schedule recurring emails and newsletters** — set a day of the month and time, manage recipient lists, preview inbox text, send a test email, and track delivery logs. Uses WordPress cron and `wp_mail()` (works with SMTP plugins).

**Plugin name:** Simple Email Scheduler  
**Repo / slug history:** `monthly-email-automation`  
**Author:** [Evolve](https://elearningevolve.com/) · [WordPress.org profile](https://profiles.wordpress.org/adeelraza_786hotmailcom/)

---

## What it does

Create email campaigns as a custom post type (**Recurring Emails**). Each campaign has:

- Subject and HTML body (WordPress editor)
- Optional **inbox preview text** (preheader shown under the subject in Gmail, Outlook, Apple Mail, etc.)
- Day of month (1–31) and send time
- Recipients (inline or saved lists)
- Active / inactive status
- Delivery log per send

WordPress cron checks daily. When the day and time match, active campaigns are sent automatically.

---

## Features

| Feature | Details |
| --- | --- |
| Recurring schedule | Day of month (1–31) + time (24-hour); respects WordPress timezone |
| Month-end safety | Day 31 on a shorter month sends on the last day of that month |
| Recipients | Unlimited addresses per campaign; save and reuse lists |
| Inbox preview text | Optional preheader for inbox snippet (separate from subject) |
| Test / preview send | Send a `[TEST]` copy to any address without activating the campaign |
| Delivery logs | Status, recipient, timestamp, and error message per attempt |
| Pause anytime | Active / inactive toggle; settings kept when paused |
| Native mail stack | `wp_mail()` + WP-Cron — compatible with WP Mail SMTP and similar plugins |
| No SaaS required | No API keys or third-party ESP required |

---

## Requirements

- WordPress 6.0+
- PHP 7.4+
- WP-Cron enabled (default), or a real cron hitting `wp-cron.php`

---

## Installation

### From WordPress.org

1. **Plugins → Add New**
2. Search for **Simple Email Scheduler**
3. Install and activate

### From this repository

```bash
cd wp-content/plugins/
git clone https://github.com/EVOLVE-DEV-TEAM/monthly-email-automation.git simple-email-scheduler
```

Activate under **Plugins**, then open **Recurring Emails** in the admin menu.

---

## Quick start

1. Go to **Recurring Emails → Add New**
2. Set **Email Subject** and write the body in the editor
3. Optionally add **Inbox Preview Text**
4. Add recipients or load a saved list
5. Choose day of month and send time; set status to **Active**
6. Save — or use **Send Test / Preview** first to verify content and preheader

---

## Who it’s for

- Monthly newsletters and digests
- Recurring client or team reports
- Membership or course update emails
- Promotional or reminder emails on a fixed monthly cadence

Not a full ESP: no open/click tracking, drip sequences, or list signup forms. For delivery reliability, pair with an SMTP plugin.

---

## How scheduling works

1. Campaigns are stored as posts (`simesc_auto_email`)
2. A daily cron event looks for active campaigns due today
3. Matching campaigns are sent via `wp_mail()`
4. Each attempt is written to the delivery log table

---

## FAQ

**Can I send to many recipients?**  
Yes. Add addresses on the campaign or reuse a saved list.

**How do I preview before going live?**  
Use **Send Test / Preview** on the campaign edit screen. Subject is prefixed with `[TEST]`. Saved subject, body, and inbox preview text are used.

**What is Inbox Preview Text?**  
The short line many clients show under the subject in the inbox. It is optional and does not replace the subject.

**Does it work with SMTP plugins?**  
Yes. Anything that hooks `wp_mail()` applies.

**Can I pause a campaign?**  
Set status to Inactive. Turn it Active again when ready.

**External services or API keys?**  
Not required.

---

## Developer notes

### Layout

```
simple-email-scheduler/
├── assets/js/admin.js
├── includes/
│   ├── class-mea-admin.php
│   ├── class-mea-email-sender.php
│   ├── class-mea-post-type.php
│   └── class-mea-scheduler.php
├── languages/
├── monthly-email-automation.php
├── readme.txt
└── uninstall.php
```

### Hooks

**Actions**

- `simesc_email_send_before`
- `simesc_email_send_after`

**Filters**

- `simesc_email_from_address`
- `simesc_email_from_name`
- `simesc_email_content_type`

---

## Changelog

### 1.0.4

- Fixed monthly send gate (campaigns can send again each new month)
- Removed broken Quick Edit callback that could fatal in admin
- Hardened admin JS enqueue / localization

### 1.0.3

- Inbox preview (preheader) field for inbox snippet text
- Admin **Send Test / Preview** for a one-off test to any address
- Version bump and docs aligned with current features

### 1.0.2 / 1.0.1

- Post type slug length fix and shared constants
- Admin menu / enqueue consistency

### 1.0.0

- Initial release: monthly schedule, recipients, lists, logs, active toggle

---

## Support

- [WordPress.org support](https://wordpress.org/support/plugin/simple-email-scheduler/)
- [Contact Evolve](https://elearningevolve.com/contact/)
- [GitHub issues](https://github.com/EVOLVE-DEV-TEAM/monthly-email-automation/issues)

**Support the project:** [Donate](https://link.elearningevolve.com/self-pay)

---

## License

GPL-3.0 or later.

---

**Related searches:** WordPress email scheduler, recurring newsletter plugin, schedule monthly emails WordPress, WordPress cron email automation, inbox preheader WordPress, test email before send WordPress
