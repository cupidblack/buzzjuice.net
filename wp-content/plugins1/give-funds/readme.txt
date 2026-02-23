=== Give - Funds and Designations ===
Contributors: givewp, wordimpress, dlocc, webdevmattcrom
Donate link: https://givewp.com/
Tags: givewp, donation funds, donations, donation plugin, wordpress donation plugin, wp donation, donors, display donors, give donors, anonymous donations
Requires at least: 5.0
Tested up to: 6.1
Requires PHP: 7.0
Stable tag: 1.2.0
Requires Give: 2.24.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Give your donors the option of designating gifts to specific funds within the organization.

== Description ==

This plugin requires the GiveWP core plugin activated to function properly. When activated, it adds the ability to designate and organize revenue in various funds.

= Minimum Requirements =

* WordPress 4.9 or greater
* PHP version 5.6 or greater
* MySQL version 5.6 or greater

= Installation =

1. Activate the plugin
2. Go to Donations > Funds

== Changelog ==
= 1.2.0: January 26th, 2023 =
* New: Funds can now be manually sorted by dragging and dropping them in the admin
* Enhancement: Fund column now displays in the new donations list table

= 1.1.0: February 24th, 2022 =
* New: Order of funds to select from can now be controlled by the admin.

= 1.0.4: November 22nd, 2021 =
* Fix: Funds from renewals are now correctly assigned. Please update to Recurring 1.12.7 as well!

= 1.0.3: May 19th, 2021 =
* Fix: The wp_funds table creation now works on MySQL 5.5+
* Fix: Bulk actions on the donations admin table now work properly

= 1.0.2: December 22nd, 2020 =
* New: Donations made when the add-on is deactivated will now be assigned to the general fund when reactivated
* Fix: Emails will no longer be prevented from going out if a fund email tag isn't working
* Fix: Problems with database migrations will not be surfaced with clearer logging
* Fix: Improved compatibility with legacy MySQL MyISAM tables

= 1.0.1: November 5th, 2020 =
* Fix: Prevent Funds from running before necessary GiveWP migration has run
* Fix: Correct documentation URI

= 1.0.0: October 28th, 2020 =
* Initial plugin release. Yippee!
