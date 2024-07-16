=== ConvertKit MemberMouse Integration ===
Contributors: nathanbarry, growdev, travisnorthcutt
Donate link: http://convertkit.com/
Tags: convertkit, email, marketing, membermouse
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 5.6.20
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin integrates ConvertKit with Member Mouse.

== Description ==

[ConvertKit](https://convertkit.com) makes it easy to capture more leads and sell more products. This plugin makes it a little bit easier for those of us using Member Mouse to subscribe and tag customers that signup for memberships, products or bundles.

**New to ConvertKit? [Creating an account](https://app.convertkit.com/users/signup?plan=newsletter-free&utm_source=wordpress&utm_term=en_US&utm_content=readme) is 100% free for your first 10,000 subscribers, making ConvertKit an email marketing solution for everyone - whether you're new to email marketing or a seasoned professional email marketer.**

== Installation ==

1. Upload the `convertkit-membermouse` folder to the `/wp-content/plugins/` directory
2. Active the ConvertKit MemberMouse Integration plugin through the 'Plugins' menu in WordPress

== Configuration ==

1. Visit the settings page by clicking on the link under the plugin's name
2. Enter your ConvertKit API key, which you can find [here](https://app.convertkit.com/account/edit), and save the settings
3. Select a tag to add to customers who signup for each Membership Level
4. Save your settings

== Screenshots ==

1. ConvertKit MemberMouse settings page

== Frequently asked questions ==

= Does this plugin require a paid service? =

No. You must first have an account on [convertkit.com](https://convertkit.com?utm_source=wordpress&utm_term=en_US&utm_content=readme), but you do not have to use a paid plan!

== Changelog ==

### 1.2.1 2024-07-16
* Fix: Settings: Improved UI

### 1.2.0 2024-07-09
* Added: Tag on Product purchase
* Added: Tag on Bundle purchase / assignment
* Fix: Settings: Add 'None' option when tagging by Membership Level, Product or Bundle
* Fix: Ensure code meets WordPress Coding Standards

### 1.1.3 2024-06-04
* Updated: Support for WordPress 6.5.3

### 1.1.2 2020-04-08
* Switch to only use first names to match ConvertKit
* Apply tag on membership level change, not only initial joining of the site

### 1.0.2 2018-01-25
* Added tag to be applied when a membership cancels or a member is deleted.
* Added debug log setting

### 1.0.1
* Fixed PHP short tag causing a T_STRING error.

### 1.0
* Initial release

== Upgrade notice ==

