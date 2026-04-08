=== myCred Retro ===
Contributors: mycred, wpexpertsio
Tags: mycred, retroactive, points, reward, loyalty
Requires at least: 4.8
Tested up to: 6.6.1
Stable tag: 1.2.5
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

== Give points retroactively for past events! ==

Sometimes, when you install myCred on a website, you might already have existing content that you would like to reward / deduct points for. That’s where this plugin can come in handy. It supports retroactive payouts for:
* User registrations
* Published content
* Published comments

Version 1.1 has built-in support for myCred 1.7 and can handle large volume of data.

Remember to disable and delete this plugin once you have finished using it.

= Adjusting the session threshold =
By default, the plugin will run 150 entries per session to prevent your server from timing out. You can change this value using the MYCRED_RETRO_MAX constant. The value you set should be a number that your server can handle within the max execution time limit set. Increasing this value will run more tasks in one session and speed up the process, however it will also put more strain on your server. It’s highly recommended that you run this tool when your website is either offline or have low traffic as the site response time will slow down. This of course will only apply when the tool is running.

To adjust this threshold, add the following to your wp-config.php file before running any tool:

define( 'MYCRED_RETRO_MAX', 1000 );

Remember to remove it once you are done using this plugin.

= Log Entries =
You can choose to give your users points without a log entry if you prefer. This will be a fast task to run then also adding in a log entry for each payout. However if there is no log entry of the event, you can not give badges for these past events either! There is also a risk of users getting points for comments/content, multiple times in certain situations. These log entries will be the same entries the corresponding hook leaves, in case you want to continue rewarding users for publishing content or comments in the future.

= Retroactive Comments =
1 First, you must select the comment status you want to reward. If you do not have comments of a particular type, the type option will be disabled in the dropdown menu.
2 If you are using multiple point types, select the point type you want to award.
3 Provide a positive value for giving points or a negative value for taking points from the author.
4 Set the log entry template.
5 Click Start

= Retroactive Content =
1 First, you must select the post type you want to reward. If you do not have posts of a particular type, the type option will be disabled in the dropdown menu.
2 If you are using multiple point types, select the point type you want to award.
3 Provide a positive value for giving points or a negative value for taking points from the author.
4 Set the log entry template.
5 Click Start

= Retroactive Signups =
1 First, you must select the role you want to reward. If you do not have users of a particular role, the role option will be disabled in the dropdown menu.
2 If you are using multiple point types, select the point type you want to award.
3 Provide a positive value for giving points or a negative value for taking points from the author.
4 Set the log entry template.
5 Click Start

= Plugin Requirements =

* [myCred 1.8+](https://wordpress.org/plugins/mycred/)
* WordPress 5.0+
* PHP 5.3+

= More myCred Freebies Integrations = 

* [myCred H5P](https://mycred.me/store/mycred-h5p)
* [myCred Credly](https://mycred.me/store/mycred-credly)
* [myCred - Learndash](https://www.mycred.me/store/mycred-learndash/)
* [LifterLMS Plugin Integration with myCred ](https://www.mycred.me/store/mycred-lifterlms-integration)
* [myCred BP Group Leaderboards](https://www.mycred.me/store/mycred-bp-group-leaderboards)
* [myCred for Event Espresso 4.6+](https://www.mycred.me/store/mycred-for-event-espresso-4)
* [myCred for Wp-Pro-Quiz](https://mycred.me/store/mycred-for-wp-pro-quiz/)
* [myCred for Rating Form](https://www.mycred.me/store/mycred-for-rating-form)
* [myCred Birthdays](https://www.mycred.me/store/mycred-birthdays)
* [myCred for WP-PostViews](https://www.mycred.me/store/mycred-for-wp-postviews)
* [myCred for TotalPoll](https://mycred.me/store/mycred-for-totalpoll)
* [myCred Gutenberg](https://www.mycred.me/store/mycred-gutenberg)
* [myCred for Events Manager Pro](https://www.mycred.me/store/mycred-for-events-manager-pro)
* [myCred for BuddyPress Compliments](https://www.mycred.me/store/mycred-for-buddypress-compliments)
* [myCred for Courseware](https://www.mycred.me/store/mycred-for-courseware)
* [myCred for GD Star Rating](https://www.mycred.me/store/mycred-for-gd-star-rating)
* [myCred for BuddyPress Links](https://mycred.me/store/mycred-for-buddypress-links)
* [myCred for BP Album and BP Gallery](https://mycred.me/store/mycred-for-bp-album-bp-gallery)
* [myCred Elementor](https://mycred.me/store/mycred-elementor/)


= DOCUMENTATION AND SUPPORT =
For more information visit our **[Documentation Page](https://www.mycred.me/store/mycred-retro/)**.

== Installation ==

1. Go to Plugins > Add New.
2. Under Search, type myCred Retro
3. Find myCred Retro and click Install Now to install it
4. If successful, click Activate Plugin to activate it and you are ready to go.

== Changelog ==

= 1.2.5 =
New – Compatible with WordPress Version 6.6.1.

= 1.2.4 =
New – Compatible with WordPress Version 6.5.2.

= 1.2.3 =
New – Compatible with WordPress Version 6.2.2.

= 1.2.2 =
New – Compatible with WordPress Version 5.8.1.

= 1.2.1 =
Improvement – Get plugin updates from wordpress.org

= 1.2 =
NEW – Updated plugin to support 1.8.

= 1.1 =
NEW – Updated plugin to support 1.7.
NEW – Improved task manager to better handle large volumes of data.
NEW – Added translation support.

= 1.0 =
Initial release