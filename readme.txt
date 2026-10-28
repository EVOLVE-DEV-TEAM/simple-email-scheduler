=== Simple Email Scheduler ===
Contributors: adeelraza_786hotmailcom, elearningevolve
Donate link: https://link.elearningevolve.com/self-pay
Tags: email scheduler, recurring emails, newsletter automation, scheduled emails, wp-cron
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.0.4
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Schedule recurring WordPress emails and newsletters by day and time. Recipient lists, inbox preview text, test send, and delivery logs.

== Description ==

**Dedicated support**  
[Contact eLearning evolve](https://elearningevolve.com/contact/)

**Simple Email Scheduler** is a WordPress email scheduler for recurring newsletters and automated messages. Pick a day of the month and a time, choose recipients, write your content once, and WordPress cron sends it on schedule via `wp_mail()`.

= What you get =

* Recurring campaigns as a familiar admin post type (**Recurring Emails**)
* Day-of-month (1–31) and time scheduling, using your WordPress timezone
* Optional **inbox preview text** (preheader) shown under the subject in many inboxes
* **Send Test / Preview** to any address before you activate a campaign
* Saved recipient lists you can reuse across campaigns
* Delivery logs with status, time, and errors
* Active / inactive control so you can pause without deleting settings
* Works with SMTP plugins that hook `wp_mail()` (e.g. WP Mail SMTP)
* No external ESP or API keys required

= Typical uses =

* Monthly newsletters and digests
* Client or team reports on a fixed day each month
* Membership or course update emails
* Recurring reminders and announcements

This is not a full email marketing suite (no open/click tracking or drip sequences). For better deliverability, use an SMTP plugin alongside it.

= How it works =

1. Create a campaign under **Recurring Emails → Add New**
2. Set subject, optional inbox preview text, body, recipients, day, and time
3. Optionally send a test / preview email
4. Set status to Active and save
5. WordPress cron sends when the schedule matches; results appear in the log

== Installation ==

= Automatic =

1. Go to **Plugins → Add New**
2. Search for **Simple Email Scheduler**
3. Install and activate
4. Open **Recurring Emails** in the admin menu

= Manual =

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate under **Plugins**
3. Create your first campaign under **Recurring Emails**

== Frequently Asked Questions ==

= Does this work with SMTP plugins? =

Yes. It uses WordPress `wp_mail()`, so SMTP plugins apply automatically.

= What is inbox preview text? =

Optional preheader text many email clients show under the subject in the inbox. It is separate from the subject line.

= How do I test an email? =

On the campaign edit screen, enter an address and click **Send Test / Preview**. The subject is prefixed with `[TEST]`. The campaign does not need to be Active.

= What if I schedule day 31 in a shorter month? =

The plugin sends on the last day of that month.

= Can I pause sending? =

Yes. Set the campaign to Inactive. Settings are kept.

= Do I need API keys? =

No. Scheduling uses WP-Cron; sending uses `wp_mail()`.

== Our Other Plugins ==

1. [WPZoomy](https://wpzoomy.com/)
2. [Virtual Classroom – Video Conferencing with BigBlueButton (Free)](https://wordpress.org/plugins/video-conferencing-with-bbb/)
3. [Virtual Classroom for WordPress (Pro)](https://elearningevolve.com/products/wp-virtual-classroom/)
4. [LearnDash Student Voice](https://elearningevolve.com/products/learndash-student-voice/)
5. [UpdateGuard](https://wordpress.org/plugins/updateguard/)
6. [Topbar Buddy](https://wordpress.org/plugins/topbar-buddy/)
7. [MasterQuiz AI](https://masterquiz.io/)
8. [Add From Server Reloaded](https://wordpress.org/plugins/add-from-server-reloaded/)
9. [Evolve's LearnDash To Lifter](https://wordpress.org/plugins/evolve-learndash-to-lifter/)
10. [Zoomy Automator Integration](https://wordpress.org/plugins/zoomy-automator-integration/)

== Changelog ==

= 1.0.4 =
* Fixed monthly send gate so campaigns send again each month (broken current_time usage had blocked repeats after the first send).
* Removed a broken Quick Edit hook that could fatally error in wp-admin.
* Hardened admin script loading and registered inbox preview meta.

= 1.0.3 =
* Added optional inbox preview (preheader) text for inbox snippets under the subject.
* Added admin Send Test / Preview to email any address without activating the campaign.
* Updated readme to match current features.

= 1.0.2 =
* Maintenance and compatibility updates.

= 1.0.1 =
* Fixed custom post type registration by shortening the post type slug to a WordPress-safe length (20 chars max).
* Standardized post type usage with a shared constant across registration, admin, scheduler, and sender.
* Updated setup guidance to reflect the admin menu location.

= 1.0.0 =
* Initial release: monthly scheduling, recipients, lists, logs, active/inactive control.

== Upgrade Notice ==

= 1.0.4 =
Important scheduler fix so monthly emails can send every month again, plus admin stability fixes.

= 1.0.3 =
Adds inbox preview text and a one-click test email send from the campaign editor.
