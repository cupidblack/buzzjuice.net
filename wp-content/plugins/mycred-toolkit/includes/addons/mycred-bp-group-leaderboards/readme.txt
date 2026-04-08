=== myCred BP Group Leaderboards ===
Contributors: mycred, wpexpertsio
Tags: mycred, BuddyPress, BuddyPress groups, tokens, leaderboards
Requires at least: 4.8
Tested up to: 6.6.1
Stable tag: 1.2.8
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This add-on allows you to setup leaderboards for your BuddyPress groups that are based on the groups members balances. These leaderboards are generated automatically as soon as a group has more then one user and supports leaderboard setups for multiple point types. Leaderboards are cached to prevent unnecessary database queries and updated whenever a group member gains or loses points.

You can set the size of the leaderboard and the option to append members to the end of the leaderboard that might not had made it into set size. These settings can be set generically for all groups or you can let group admins change them for each group.

= myCred BP Group Leaderboards Features =
Here are the most notable features of myCred BP Group Leaderboards plugin.

* **Leaderboard Based on Current Balance (default):** The leaderboard is based on a users current balance. This was the leaderboard this plugin rendered in previous versions.
* **Leaderboard Based on Total Balance:** This will generate a leaderboard based on a users total balance. Total balances are calculated by adding up all point gains a user has then deducting any manual adjustments an admin might have made.
* **Leaderboard Based on Today’s Gains:** This will generate a leaderboard based on members total accumulated points today. This is done by adding up all point gains and losses from the beginning of each day until now.
* **Leaderboard Based on This Week’s Gains:** This will generate a leaderboard based on members total accumulated points this week. This is done by adding up all point gains and losses from the the start of each week until now. Resets each week.
* **Leaderboard Based on This Month’s Gains:** This will generate a leaderboard based on members total accumulated points this month. This is done by adding up all point gains and losses from the start of each month until now.
* **Leaderboard Based on Date Range:** This option allows a leaderboard creation between two dates. When this option is selected, two hidden fields will become available where you can set the start and end date. These dates must be well formatted either YYYY-MM-DD or MM/DD/YYYY.

= Caching =
* This version also includes caching of leaderboards to cut down on database queries. Each groups leaderboard(s) are cached until a member balance changes or if you save your leaderboard settings in your group admin area.

= Plugin Requirements =

* [myCred 1.8+](https://wordpress.org/plugins/mycred/)
* [BuddyPress 2.5+](https://wordpress.org/plugins/buddypress/)
* WordPress 5.0+
* PHP 5.3+

= More myCred Freebies Integrations = 

* [myCred H5P](https://mycred.me/store/mycred-h5p)
* [myCred Credly](https://mycred.me/store/mycred-credly)
* [myCred - Learndash](https://www.mycred.me/store/mycred-learndash/)
* [LifterLMS Plugin Integration with myCred ](https://www.mycred.me/store/mycred-lifterlms-integration)
* [myCred for Event Espresso 4.6+](https://www.mycred.me/store/mycred-for-event-espresso-4)
* [myCred for Wp-Pro-Quiz](https://mycred.me/store/mycred-for-wp-pro-quiz/)
* [myCred for Rating Form](https://www.mycred.me/store/mycred-for-rating-form)
* [myCred Birthdays](https://www.mycred.me/store/mycred-birthdays)
* [myCred for WP-PostViews](https://www.mycred.me/store/mycred-for-wp-postviews)
* [myCred for TotalPoll](https://mycred.me/store/mycred-for-totalpoll)
* [myCred Gutenberg](https://www.mycred.me/store/mycred-gutenberg)
* [myCred for Events Manager Pro](https://www.mycred.me/store/mycred-for-events-manager-pro)
* [myCred for BuddyPress Compliments](https://www.mycred.me/store/mycred-for-buddypress-compliments)
* [myCred Retro](https://www.mycred.me/store/mycred-retro)
* [myCred for Courseware](https://www.mycred.me/store/mycred-for-courseware)
* [myCred for GD Star Rating](https://www.mycred.me/store/mycred-for-gd-star-rating)
* [myCred for BuddyPress Links](https://mycred.me/store/mycred-for-buddypress-links)
* [myCred for BP Album and BP Gallery](https://mycred.me/store/mycred-for-bp-album-bp-gallery)
* [myCred Elementor](https://mycred.me/store/mycred-elementor/)

= DOCUMENTATION AND SUPPORT =
For more information visit our **[Documentation Page](http://codex.mycred.me/chapter-iv/freebies/bp-group-leaderboards/)**.

== Installation ==

1. Go to Plugins > Add New.
2. Under Search, type myCred BP Group Leaderboards
3. Find myCred BP Group Leaderboards and click Install Now to install it
4. If successful, click Activate Plugin to activate it and you are ready to go.

== Changelog ==

= 1.2.8 =
New – Compatible with WordPress Version 6.6.1.

= 1.2.7 =
New – Compatible with WordPress Version 6.5.2.

= 1.2.6 =
New – Compatible with WordPress Version 6.2.2.

= 1.2.5 =
New – Compatible with WordPress Version 5.8.1.

= Version 1.2.4 =
FIx - date range setting were not saving.
Fix – warnings and notices on front-end.

= Version 1.2.3 =
Improvement - Leaderboard settings in the Multisite environment.

= Version 1.2.2 =
Improvement - Get plugin updates from wordpress.org

= Version 1.2.1 =
FIX - Error on install/activate myCred BP Group leaderboards

= Version 1.2 =
Compatible with WordPress 5.0.2
Tested with myCred 1.8

= Version 1.1.1 =
FIX – Leaderboard settings page not available when used on Multisites.
Tested with WP 4.8 and BuddyPress 2.9

= Version 1.1 = 
FIX – When using multiple point types, the checkbox does not save the selected point types.
FIX – When using multiple point types, the point type filter is not usable when viewing a leaderboard.
FIX – Leaderboard caches are not deleted when a point type is deleted.
NEW – Added option to select leaderboard types.
NEW – Added actions and filters to allow better customizations of the plugin.
TWEAK – Excluded users can now view a leaderboard even though they are no in it.
TWEAK – Changed plugin domain to bpmycredleaderboard.
UPDATE – Updated translation files.

= Version 1.0 =
Initial Release