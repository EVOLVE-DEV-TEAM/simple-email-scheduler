=== Simple Email Scheduler ===
Contributors: adeelraza_786hotmailcom, elearningevolve
Donate link: https://link.elearningevolve.com/self-pay
Tags: email scheduler, recurring emails, newsletter automation, scheduled emails, click tracking
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Schedule recurring emails and newsletters, send previews, track link clicks, and review delivery logs — from your WordPress dashboard.

== Description ==

**Dedicated support**  
[Contact eLearning evolve](https://elearningevolve.com/contact/)

**Simple Email Scheduler** helps you send the same email on a schedule — monthly newsletters, client updates, team reports, and reminders — without remembering to hit send every time.

Write your message once, pick a day and time, choose who should receive it, and WordPress takes care of the rest using your site’s built-in mail and cron.

= What’s new in 2.0 =

* **Preview before you send** — email yourself (or anyone) a real preview of the campaign
* **Inbox preview text** — control the short line that appears under the subject in Gmail, Outlook, and Apple Mail
* **Link click tracking** — see who clicked and which links they opened, right inside the plugin
* **Clicks overview** — a dedicated Clicks screen next to your campaigns, plus click counts in the campaign list
* **Clearer scheduling** — day and time use your WordPress site timezone, with a note in the editor so there’s no guesswork
* **More reliable monthly sends** — campaigns keep sending each month as expected

= What you get =

* Recurring campaigns managed like normal WordPress posts (**Email Scheduler**)
* Day-of-month (1–31) and time scheduling in your WordPress timezone
* Optional inbox preview text under the subject line
* Send Test / Preview without activating the campaign
* Saved recipient lists you can reuse
* Link click tracking with totals and recent activity
* Delivery logs for recent sends
* Active / inactive control so you can pause anytime
* Works with SMTP plugins that use WordPress mail (including SiteGround SMTP, WP Mail SMTP, and similar)
* No third-party email marketing account required

= Typical uses =

* Monthly newsletters and digests
* Client or team reports on a fixed day each month
* Membership or course update emails
* Recurring reminders and announcements

This plugin focuses on scheduled sending and simple click insight. It is not a full email marketing suite (no open tracking, drip sequences, or signup forms). For best delivery, use an SMTP plugin alongside it.

= How it works =

1. Go to **Email Scheduler → Add New**
2. Set the subject, optional inbox preview text, message, recipients, day, and time
3. Optionally send a test / preview
4. Set status to Active and save
5. WordPress sends when the schedule matches — check Sends & Clicks on the campaign, or **Email Scheduler → Clicks** for an overview

== Installation ==

= Automatic =

1. Go to **Plugins → Add New**
2. Search for **Simple Email Scheduler**
3. Install and activate
4. Open **Email Scheduler** in the admin menu

= Manual =

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate under **Plugins**
3. Create your first campaign under **Email Scheduler**

== Frequently Asked Questions ==

= Does this work with SMTP plugins? =

Yes. It uses WordPress mail, so SiteGround SMTP, WP Mail SMTP, and similar plugins work automatically.

= What is inbox preview text? =

The short line many email apps show under the subject in the inbox. It is separate from the subject line and optional.

= How do I test an email? =

On the campaign edit screen, enter an address and click **Send Test / Preview**. It uses the real subject and content. The campaign does not need to be Active.

= Can I see who clicked my links? =

Yes. http(s) links in the email are tracked automatically. View clicks on the campaign’s **Sends & Clicks** box, in the Clicks column on All Emails, or under **Email Scheduler → Clicks**.

= What if I schedule day 31 in a shorter month? =

The plugin sends on the last day of that month.

= Can I pause sending? =

Yes. Set the campaign to Inactive. Your settings are kept.

= Do I need API keys? =

No. Scheduling uses WordPress cron; sending uses WordPress mail.

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

= 2.0.0 =
* Send a real preview of any campaign to yourself before it goes live
* Add optional inbox preview text so you control what appears under the subject in people’s inboxes
* Track link clicks and see who clicked which links
* New Clicks screen to review activity across all campaigns in one place
* Show click counts next to each campaign in the list
* Make scheduling clearer by showing your WordPress site timezone next to the send time
* Improve monthly sending so campaigns continue to go out each month reliably
* Keep the recent send log short and easy to scan (last 5 sends)

= 1.0.2 =
* Maintenance and compatibility updates

= 1.0.1 =
* Reliability and admin menu improvements

= 1.0.0 =
* First release: schedule monthly emails, manage recipients, and review delivery logs

== Upgrade Notice ==

= 2.0.0 =
Major update: preview emails, inbox preview text, link click tracking, and a dedicated Clicks screen — plus more reliable monthly sending.
