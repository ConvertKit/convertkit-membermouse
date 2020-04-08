=== ConvertKit MemberMouse Integration ===
Contributors: nathanbarry, growdev, travisnorthcutt
Donate link: http://convertkit.com/
Tags: convertkit, email, marketing, membermouse
Requires at least: 3.0.1
Tested up to: 5.4
Stable tag: 1.1.2
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin integrates ConvertKit with Member Mouse.

== Description ==


[ConvertKit](https://convertkit.com) makes it easy to capture more leads and sell more products . This plugin makes it a little bit easier for those of us using Member Mouse to subscribe and tag customers that signup for memberships.

== Installation ==

1. Upload `convertkit-membermouse` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the settings page by clicking on the link under the plugin's name
4. Enter your ConvertKit API key, which you can find [here](https://app.convertkit.com/account/edit), and save the settings
5. Select a tag to add to customers who signup for each Membership Level
6. Save your settings


== Screenshots ==
1. ConvertKit MemberMouse settings page

== Frequently asked questions ==

= Does this plugin require a paid service? =

Yes, for it to work you must first have an account on ConvertKit.com

== Changelog ==

### 1.1.2 2020-04-08
* Switch to only use first names to match ConvertKit
* Apply tag on membership level change, not only initial joining of the site

= 1.0.2  2018-01-25 =
* Added tag to be applied when a membership cancels or a member is deleted.
* Added debug log setting

= 1.0.1=
* Fixed PHP short tag causing a T_STRING error.

= 1.0 =
* Initial release


== Upgrade notice ==

None.