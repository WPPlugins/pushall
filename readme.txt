=== PushAll ===
Contributors: iezhik, bupyc
Donate link: https://pushall.ru
Tags: push, notification, post, inform, ios, android, chrome, notifications, push notification, push notifications, service, rss, api, news, broadcast
Requires at least: 3.9
Tested up to: 4.5.3
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send Push Notifications to your users using PushAll service.

== Description ==

PushAll plugin allows websites to send notifications about new posts to their visitors. 
[PushAll service](https://pushall.ru "PushAll service web-site") support two most popular mobile operating systems 
in the world (Android and iOS), Google Chrome browser, Telegram messenger and we are currently working on adding several
new platforms.

You can use our service for free, just register on [PushAll web site](https://pushall.ru "PushAll service web-site") and 
create your channel.

== Installation ==

1. Upload `pushall` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure PushAll settings under 'Settings' menu in WordPress. 

== Frequently Asked Questions ==

= How can I obtain a PushAll channel credentials? =

Go to the 'Administration' section on [PushAll web site](https://pushall.ru "PushAll service web-site"), create a new
channel and click on it. Channel credentials can be found under 'API' tab.

== Screenshots ==

1. Post page with enabled PushAll plugin.

== Changelog ==

= 1.1.1 =
- Added widgets.
- Changed plugin structure.

= 1.0.7 =
Fixed bug when html and bb tags were not stripped from a scheduled posts body.

= 1.0.6 =
- Added support for the Scheduled posts.
- Fixed bug when Push message was sent when Channel ID and/or Channel Key was not set.
- Added log message when curl_exec is disabled by PHP settings.

= 1.0.5 =
Fixed versions mismatching.

= 1.0.3 =
Strip &nbsp; from Push message.

= 1.0.2 =
Strip shortcodes from Push message.

= 1.0.1 =
Fixed bug which produced PHP Warning while sending a Push notification.

= 1.0.0 =
Initial release

== Upgrade Notice ==

Just upload files :)