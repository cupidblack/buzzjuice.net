=== Give - Currency Switcher ===
Contributors: givewp
Tags: donations, donation, ecommerce, e-commerce, fundraising, fundraiser, gateway, currency, currency switcher
Requires at least: 4.9
Tested up to: 5.9
Stable tag: 1.5.1
Requires Give: 2.11.0
License: GPLv3
License URI: https://opensource.org/licenses/GPL-3.0

Provide your donors with the ability to give using currency of their choice.

== Description ==

Allow donors the option to give using currency options predefined by admins.

== Installation ==

= Minimum Requirements =

* WordPress 4.9 or greater
* PHP version 5.6 or greater
* MySQL version 5.6 or greater
* Some payment gateways require fsockopen support (for IPN access)

= Manual installation =

The manual installation method involves downloading our donation plugin and uploading it to your server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Changelog ==
= 1.5.1: March 17th, 20222 =
* Fix: The dismiss button for an invalid currency was absolutely massive on Multi-Step forms. It's a reasonable size once again
* Fix: Improved some documentation within the plugin

= 1.5.0: July 27th, 2021 =
* New: Exchange services have been removed for one, simple, free API compliments of GiveWP
* New: Exchange rates now automatically update every day (unless they are manually set)

= 1.4.0: May 20th, 2021 =
* New: Decimals now look great and are better handled using the new Multi-Step template decimal option introduced in GiveWP 2.11.0
* Fix: The currency levels are now correctly updated when a new payment method is selected

= 1.3.14: April 13th, 2021 =
* Fix: Resolved issue where Currency > Decimals setting was affecting donation calculations and reducing amounts

= 1.3.13: November 13th, 2020 =
* Fix: Prevent duplicate database updates from 1.3.12

= 1.3.12: October 27th, 2020 =
* New: Currency exchange code and amount are now stored in the new give_revenue table
* Fix: Improved compatibility with the Multi-step form
* Fix: Allow for 0 as a valid number of decimals in settings

= 1.3.11: June 30th, 2020 =
* Fix: Resolved an issue with the previous release which was causing a JavaScript error for donors when attempting to switch currencies.

= 1.3.10: June 29th, 2020 =
* Fix: When a donor is logged in and Fee Recovery is active the fee amount could be inaccurate with some configurations.
* Fix: Preventing conflicts with GiveWP 2.7 version. Note: The Currency Switcher add-on is currently not compatible with the first GiveWP Form Template but we're working on making it compatible in an upcoming release.

= 1.3.9: June 17th, 2020 =
* Fix: Adjusted a JavaScript conditional check to prevent a JS error when Currency Switcher is not enabled on a donation form.

= 1.3.8: June 16th, 2020 =
* Fix: Resolved a conflict with Fee Recovery that was formatting currencies incorrectly when using comma-delimited currencies.

= 1.3.7: February 20th, 2020 =
* Fix: Resolved an issue with Currency Switcher not properly setting the currency when only one currency is supported by a particular payment gateway.
* Fix: When a logged in donor's preferred currency was auto-switched there was an issue with the donor then giving a custom amount in that currency that has been fixed.
* Fix: Resolved an issue with currency formatting the total amount correctly when the donor switches the payment gateway.
* Fix: An "Invalid amount" notice would display incorrectly when using custom currency amount in a multi-level donation form. Now no incorrect errors will display.

= 1.3.6: January 15th, 2020 =
* Fix: Resolved a bug where an invalid amount notice would appear for donors who submit a donation where the converted currency donation amount is equal to a donation level of the base currency.

= 1.3.5: November 15th, 2019 =
* Fix: With Currency Switcher active and configured with multiple currencies, a set donation form would clear out the amount at the bottom of the form when you switch payment gateways.
* Fix: Resolved an issue when Currency Switcher is activated it throws a JavaScript error on a page with the `[give_profile_editor]` shortcode on it.
* Fix: Resolved PHP warnings when the Per Form Gateways add-on and Currency switcher are both activated.

= 1.3.4: October 28th, 2019 =
* Fix: Resolved an issue with a JavaScript error appearing when switching currencies based on certain configurations.

= 1.3.3: August 7th, 2019 =
* Fix: Allow seamless transition to another currency when a donor switches to a payment gateway that does not support the currently selected currency.
* Fix: When you have a goal set with large amounts and you switch the currency the human readable large amount is now properly converted. Previously when a large goal was set, for instance above one million, Currency Switcher would swap to a different currency and the "millions" word would be removed incorrectly.

= 1.3.2: May 7th, 2019 =
* Fix: Resolved an issue where setting the per-form Currency Switcher default currency setting didn't take effect on the front end of the form and incorrectly defaulted to the base currency global setting.

= 1.3.1: December 13th, 2018 =
* New: Added compatibility with the Payfast payment gateway.
* Tweak: Improved the notice that displays for currency support per gateway so admins have a clearer understanding.
* Fix: Resolved an issue where changing the currency for a donation form with a goal would adjust the goal.
* Fix: Ensure that the donation level amount respects zero decimal configurations on page load.
* Fix: EUR renewals no longer cause reports to display incorrect amounts.
* Fix: Removed the apostrophe for the thousands separator for the Taiwan New Dollars currency.
* Fix: Ensure that strings displayed on the donation form from currency switcher are translatable.

= 1.3.0: October 2nd, 2018 =
* New: Admins now have the ability to set the default currency per donation form.
* Fix: The recurring donation amount was not properly being converted when switching to some currencies.
* Fix: Stats could be conflicting when currency switcher is active in the dashboard widget and on reports.

= 1.2.2: July 30th, 2018 =
* Important: You must be running Give Core 2.2.0+ in order to use this add-on update. Please update Give Core to the latest version prior to completing this update.
* New: Added an option for admins to enable or disable the option to switch to the users preferred currency.
* Tweak: Use give_get_donation_levels filter to filter the variable amounts after Give 2.2.0.
* Fix: Ensure two decimal places aren't added on conversion and export.
* Fix: Ensure that moving around the donation form levels won't cause errors.
* Fix: Only schedule CRON jobs if automatic updates enabled.
* Fix: Use uninstall.php instead of deactivation hook.

= 1.2.1: June 18th, 2018 =
* Fix: Resolved geolocation not working properly due to incorrect usage of the is_admin conditional check.
* Fix: Amount conversions now use the donation form's base amount for improved conversions between currencies.
* Fix: The admin decimal separator field is can now be left blank to more accurately depict the currency formatting in use.

= 1.2.0: June 6th, 2018 =
* New: Added amount validation to the set donation amount admin donation form fields for Currency Switcher.
* Tweak: When a donor switches to a gateway that doesn't support his/her currency the noticed displayed does not auto-dismiss anymore to give the donor a chance to acknowledge the change.
* Fix: The add-on now properly formats the currency amount using the per form currency's formatting settings instead of using global currency setting.
* Fix: Improved the responsive layout of the admin fields for Currency Switcher.
* Fix: If a minimum donation amount is not met the notice displayed will now display the switched currency properly.
* Fix: Wehn an admin defined recurring donation amount is enabled the currency selected will now properly display.
* Fix: Ensure that when a donation form is in "Button" mode that the currency acronym doesn't display multiple times when the modal popup is closed.
* Fix: The add-on will now properly display the currency acronym on first page load even if a payment gateway doesn't support switching currencies.

= 1.1: May 3rd, 2018 =
* New: Performance improvements for fewer on-the-fly calculations so your site loads faster and your server works less.
* Tweak: Added compatibility for Give Core 2.1+ - Please update if you haven't yet!
* Tweak: Adjusted the logic for calculating the donation amount when switching currencies.
* Tweak: XE.com is not the default exchange rate provider for its ease of use.
* Fix: Properly convert the min/max donation amounts when switching currencies.
* Fix: If the thousands separator is blank in Give's settings this caused currency calculation inaccuracies.
* Fix: jQuery was having issues with Dutch (netherland) translated donation forms.
* Fix: PayPal Pro gateway compatibility - now the properly converted currency is passed to the gateway.
* Fix: Dropdown display bug in Firefox.

= 1.0.3: March 23rd, 2018 =
* Fix: The "Google Finance" exchange rate API has been fixed. Google had changed the URL for this free API and a workaround fix has been implemented to continue using Google for API changes.

= 1.0.2: March 1st, 2018 =
* Fix: When decimal and comma are switched from the standard the math becomes inaccurate for custom and minimum amounts. This has been resolved so that custom amounds and minimum amounts, including fee calculations, are correct.

= 1.0.1: February 22nd, 2018 =
* Fix: Support added for embedded donation forms. If your currency switching dropdowns weren't displaying properly for embedded (shortcode) forms then they will now!
* Tweak: Settings UI improvement to conditionally display the API exchange rate fields if enabled in WP-Amin.

= 1.0.0 =
* Initial plugin release. Yippee!
