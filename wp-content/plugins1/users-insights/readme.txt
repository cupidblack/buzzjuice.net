=== Users Insights WordPress Plugin ===

- Plugin Name: Users Insights
- Plugin URI: https://usersinsights.com/
- Description: Everything about your WordPress users in one place
- Version: 4.7.0
- Author: Pexeto
- License: GPLv2 or later
- License URI: http://www.gnu.org/licenses/gpl-2.0.html
- Copyright: Pexeto 2016-2024

Users Insights is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.


== Installation ==

1. Upload the users-insights.zip file from the Plugins -> Add New -> Upload Plugin page.
2. Activate the plugin
3. Go to the UsersInsights page to access the Users Insights users table, filters and other functionality.
4. Visit the Features page: https://usersinsights.com/features/ to learn more about all of the Users Insights functionality


== Using the Geolocation Module ==

1. Copy the Geolocation API license key that comes with your purchase - you should have received an email containing the license key. You can also access your API keys from your account page: http://usersinsights.com/account/
2. Go to the Module Options -> Geolocation, click on the "Settings" button of the Geolocation Module and paste the Geolocation API license key into the license field.
3. Activate the license and then Activate the module

== Updating Users Insights ==

1. Go to your account on the UsersInisghts site: http://usersinsights.com/account/ and download the latest version of the plugin
2. Delete the currently installed plugin from the Plugins page of your WordPress installation
3. Upload and activate the latest version of the plugin (you can follow the instructions from the "Installation" section above)


== Changelog ==

4.7.0
- New: WooCommerce: implement support for WooCommerce 8.5+ order attribution/origin tracking, allowing you to explore UTM sources, referring sites and other order origin information. The following features are available:
    * Order origin types and order origin sources columns in the user table (listing the origin information of the user's orders)
    * Order origin types and order origin sources filters (standalone and in the Placed an order filter)
    * Top order origin types and top order origin sources reports with option to filter by date
    * Order origin information added to the user profile order list
- New: Reports PDF export - added an option to export the visible chart reports of current view to a PDF file. The PDF also includes the chart data in a table.
- New: Easy Digital Downloads 3.0+: Advanced "Placed an order filter" with an option to filter users by the status, total amount, product and date of their orders
- New: Easy Digital Downloads 3.0+: "Orders by Status" report, showing the distribution of order statuses over time with color-coded segments
- Fixed: Easy Digital Downloads: issue with timezone not being applied correcty to the earnings report
- Easy Digital Downloads 3.0+: count partial refunds as sales
- WooCommerce: respect WooCommerce decimal setings in the Lifetime value column
- WooCommerce: In Performance Comparson report, fix NaN in Sales and Refunds labels when Orders is 0
- WooCommerce: Do not show nonpublic statuses such as auto-draft and trash in the Performance Comparison and Orders by Status reports
- Other minor bug fixes and improvements

4.6.0
- New: WooCommerce: added an option to filter users by product category purchased. The filter allows you to search users who have purchased products from a selected product category. This filter is part of the "Placed an order" filter.
- New: WooCommerce "New vs Returning customers" report, showing the number of new and returning customers over time with color-coded segments. (replaces the New customers report)
- New: WooCommerce "Orders by Status" report, showing the distribution of order statuses over time with color-coded segments. Available in general WooCommerce reports and per-product reports.
- New: WooCommerce "Performance Comparison" report - compares the number of orders, sales, and refunds for a selected recent period (last 7 days, 30 days, etc.) against the corresponding previous period. Available in general WooCommerce reports and per-product reports.
- New: WooCommerce "Abandoned Carts" report - showing the number of abandoned carts over time of logged in users
- New: WooCommerce Product Category reports section, offering detailed reports for a selected product category including:
    * Sales (over time)
    * Orders by Status (over time, with color-coded segments for statuses)
    * Items Sold (over time)
    * Total Amount of Items Sold (over time)
    * Best Selling Products
    * Order Status Breakdown
- New: LearnDash Course Reports section, offering the following reports for a selected course:
    * Active students (over time)
    * Students started course (over time)
    * Students completed course (over time)
    * Average time to complete course (distribution chart)
    * Student progress breakdown
- New: LearnDash Quiz Reports section, offering the following reports for a selected quiz:
    * Quiz attempts - number of attempts over time, with color-coded segments showing passes vs fails
    * Quiz score - quiz score distributions
    * Quiz attempts distribution
    * Most correctly answered questions
    * Most incorrectly answered questions
    * Time spent on quiz (distribution chart)
- New: Leardash: add quiz attempt date and duration in the "Quiz attempts" section of user profile
- New: Last seen distribution report, showing the number of users who last logged in in the most recent periods (today, last 7 days, last 30 days, etc.)
- Fixed: Dark overlay on maps in Safari
- Fixed: Some WooCommerce product reports counting different variations of the same product within the same order separately
- Fixed: PHP 8.2 deprecation warnings
- Other minor bug fixes and improvements

4.5.0
- Introduce compatibility with the upcoming Paid Memberships Pro 3.0 update. Paid Memberships Pro 3.0 now supports multiple memberships per user and therefore Users Insights fields that were designed to work with single membership per user (Level, Status, Start Date and End Date) were replaced with: Member Status, Active Memberships, Mamberships (number) fields and an additional "Has membership" advanced filter has been introduced.

4.4.2
- Update the geolocation map tiles provider as current provider will no longer be available from November 2023
- Update the map JavaScript packages
- Fixed: BuddyPress group number field query error in MySQL 8

4.4.1
- WooCommerce Subscriptions module: Implemented support for the new WooCommerce high performance order storage feature (COT/HPOS)
- Added an option to filter WooCommerce payment methods report by date
- Fixed: issue with the sorting of some columns that contain float numbers

4.4.0
- WooCommerce module: Implemented support for the new WooCommerce high performance order storage feature (COT/HPOS)

4.3.0
- New: Introduced a new "WooCommerce Product Reports" section - it allows you to select a product and explore the following reports:
   * Frequently bought together (top products that are frequently ordered with the selected product)
   * Sales
   * Items sold
   * Items sold total amount
   * Top ordered variations (for variable products)
   * Top ordered attributes (for variable products)
   * Order statuses
- New: Allow moving back in time in all period/time based reports. The time based reports used to show the last N days/weeks/months/years - this feature now allows moving the report in time, e.g. on a monthly report that shows the last 12 months by default, you can now navigate to the previous 12 months, etc.
- New: LearnDash - add a Group column to the table showing the groups that the user belongs to
- New: LearnDash - add course date started and course date completed to course activity in user profile section
- New: WooCommerce - added an option to filter the Order status report by date
- Fixed: Issue with sorting date fields on the table in ascending order with MySQL 8
- Fixed: Some of the table fields/reports loaded in UTC instead of the WordPress site timezone
- Fixed: Profile Builder Pro - country field options not available in filter select
- Fixed: Date picker not working with Finnish language since the 4.2.0 update
- LearnDash: Make private courses available to select in course filters and module options for the users who can access private posts
- LearnDash: Handle cases when LearnDash stores multiple course activities per user. For course start date and end date we now take the first date the course has been started and the last date the course has been completed.
- WooCommerce: Add a filter/hook "usin_wc_successful_order_statuses" allowing to change the statuses used in "Successful orders" and "Lifetime value" fields. By default the completed and processing statuses are used.
- WooCommerce: Add a filter/hook "usin_wc_sale_order_statuses" allowing to change the sale order statuses used in reports (Sales, Sales Total, Product Sales, Product Sales Total, Product Items Sold). By default completed, processing and on hold are used.
- Change how schema updates run. The "usin_version_installed" option key now refers to the database version, and the "usin_version_update" action now runs only on schema update.
- Other general improvements and minor bug fixes

4.2.1
- Fixed: a layout issue with the filter buttons
- Fixed: a notice generated during export in some cases

4.2.0
- Changed the minimum required PHP version to 5.3
- New: Added a search to the eye-icon menu list of fields that allows showing/hiding fields on the table.
- New: Page visit tracking module: Added a "Page visits" column showing the number of page visits that Users Insights has recorded for each user
- New: WooCommerce Memberships: Added an "Active memberships" column containing comma separated names of the currently active memberships for each user
- New: Memberpress: Added an "Active memberships" column containing comma separated names of the currently active memberships for each user
- New: WooCommerce: Added a date filter to the "Top ordered products" report
- New: WooCommerce: Added a date filter to the "Top coupons" report
- New: WooCommerce: Added a "Most refunded products" report analyzing the products from refunded orders. The report also supports filtering by the date of the orders.
- New: BuddyBoss/BuddyPress: Added support for the BuddyBoss Profile Types feature. When BuddyBoss is active, the Profile Type field will be available instead of the BuddyPress Member Type.
- Renamed "Users Insights" -> "Module Options" menu to "Settings"
- Improved: BuddyPress: Show activity date in the "Activity Updates" list of user profile
- Improved: Custom fields: When attempting to register a field with a key for which there is already existing meta data stored, show a notification requiring to confirm that this is the intended key.
- Improved: Enable search in filters dropdowns when they contain more than 10 options (it used to be activated on 20).
- Improved: WooCommerce: Change the user profile orders “View all” link to open the WooCommerce orders page that uses the default WooCommerce functionality to filter the orders by user
- Improved: WooCommerce Subscriptions: Change the user profile subscriptions “View all” link to open to the WooCommerce subscriptions page that uses the default WooCommerce Subscriptions functionality to filter the subscriptions by user
- Improved label rotations and spacing of reports with long labels
- Gravity Forms: Ignore deleted forms is user table and reports
- Fixed: Segment menu dropdown toggle issue when deleting a segment or clicking outside of the dropdown to close it
- Fixed: Gravity Forms: Issue with field data loading on the user table when a multi-select field is registered with the same meta key in multiple forms using the User Registration addon
- Fixed: Do not count products from WooCommerce subscriptions into "Top ordered products" report - only the ones from the related WooCommerce orders are counted
- Fixed: Do not count WooCommerce subscriptions records data into the WooCommerce "Top billing countries/states/cities" and "Payment methods used" reports. Only the ones from the WooCommerce orders are counted.
- Fixed: BuddyPress: Member Type field not available when groups option is disabled
- Other various code and UI improvements, and minor bug fixes

4.1.1
- Fixed: Module Options page not loading after the recent Ultimate Member plugin 2.2.3 update.

4.1.0
- Introduced support for the upcoming Easy Digital Downloads (EDD) 3.0 update which includes major database changes
- EDD module - added a button linking to the EDD customer profile in the Users Insights profile actions section (top of the page)
- EDD module - show order ID in user profile order list
- EDD module - renamed the "Orders" field to "Purchases" for consistency with the EDD naming

4.0.1
- Fixed: Gravity Forms module - multiselect and checkbox fields data not formatted correctly in some cases
- Fixed: PHP7 warning with the WooCommerce Subscriptions module

4.0.0
- Redesigned some of the elements in the user table and profile sections
- New: WooCommerce module: Implemented cart-related fields and filters for sites that have persistent cart enabled:
   * "Cart has items/is empty" filter - allowing you to find the users who currently have (or don't have) any items in their cart
   * "Has product in cart" filter allowing to search users by the products that they have in cart
   * List current cart items in user profile
- New: WooCommerce module: Implemented a "Number of items per order" report
- New: BuddyPress module: Introduced support for the BuddyPress member types feature including:
   * Member Type column and filter in the user table
   * Show member type in user profile
   * Visual report showing the number of users belonging to the top member types
- New: LearnDash module: Implemented per-course analytics that can be enabled from the Module Options page. For each enabled course there will be a Course started/completed date columns and filters available in the user table, as well as Number of students who have started/completed the course over time reports in the Reports section.
- New: LearnDash module: Implemented per-quiz results that can be enabled from the Module Options page. For each enabled quiz there will be a separate column in the user table showing the results from all attempts of the users.
- New: LearnDash module: Implemented a "Courses completed" report showing the number of courses that have been completed over time.
- LearnDash module: Changed the colors that are applied to quiz results - now green is applied to passed attempts and red is applied to failed attempts.
- LearnDash module: Renamed the "Courses started by students" report to "Courses started"
- Major changes affecting the filter results of the fields that are stored in a serialized format - this includes the BuddyPress, Ultimate Member and MemberPress checkbox/multiselect fields. Serialized empty array values are now considered as nulls (not set/not present) in the is set/is not set filters. Additionally the "does not include" filter now also returns the users with empty value (no items selected) in the results, as they technically don't include any values.
- Fixed: layout issue of the reports with long titles
- Fixed: reports eye-icon menu closes when clicking on a report checkbox to toggle its visibility
- Fixed: wrong font applied to filter button in WordPress 5.6
- Various layout and spacing improvements
- Other general improements and minor bug fixes

3.9.3
- MemberPress module: Custom user fields with type "date" are now treated as text fields due to a recent change in MemberPress, after which user field dates can be stored in different formats depending on the WordPress settings (e.g. one value can be stored in a "2020-10-11" format and another in a "October 11, 2020"). With multiple date formats, we can't instruct the database query what date format to use and therefore we can only treat these fields like standard text fields.
- Fixed: user avatar not displayed in user table/profile when avatars are disabled under Settings -> Discussion
- Fixed: conflict with the Gravity Forms Survey addon, where the invokation of GFAPI::get_forms() causes the form entries page showing the entry IDs instead of their values
- Fixed: missing select sprite image for low resolution displays

3.9.2
- Fixed: Events Calendar module - filters not listing event options since a recent update of Events Calendar

3.9.1
- Improved the plugin updater - enabled support for the upcoming WordPress 5.5 auto-updates feature (single site only) and fixed an issue where the update notification is not shown in some cases

3.9.0
- New: Gravity Forms module: introduced support for forms that are not linked via the User Registration add-on. For each enabled form in the Module Options -> Gravity Forms section the following features are available:
   * "Has submitted form [form name] with ..." advanced filter in the Users Insights table allowing you to filter the users by the values that they have submitted in their forms. For example you can apply a filter "Has submitted form Satisfaction Survey with 'Rating: Satisfied' and 'Would recommend product: Yes'"
   * A separate section on the Reports page including form-specific reports for each enabled form. This includes number of submissions over time, as well as graphical reports of the form field data. These reports reflect all submissions (including non-user submissions).
- New: WooCommerce module: intoduced a "Successful orders" field (column and filter), showing the number of successful orders (orders with status "completed" and "processing")
- New: WooCommerce Memberships module: Introduced a new advanced filter called "Has a membership". This filter allows you to search your users based on the memberships that they have by different criteria. For example, you can now find all users who have a membership with plan X, status Active and start date between date X and X.
- Improved: WooCommerce module - show order item quantity in user profile when it is different than 1
- Improved: LearnDash module: use custom LearnDash labels when present for courses/lessons/quzzes in the Users Insights profile and reports section
- Gravity Forms module: Renamed the "Has/has not completed form" filter to "Has/has not submitted form". Also renamed the main Gravity Forms reports tab to "Gravity Forms users"
- General code improvements and minor bug fixes

3.8.2
- Fixed: MemberPress - in some cases an unexpected fields configuration format causing an error in dashboard

3.8.1
- Fixed: styling issues of some of the inputs in WordPress 5.3
- Fixed: Profile Builder Pro issue when filtering select (multiple) fields where the options contain special characters
- Fixed: bbPress Reply titles not displayed in the user profile section
- Improved the character escaping of the text-based filters
- Other minor improvements and bug fixes

3.8.0
- New: MemberPress integration allowing you to explore and filter the users' membership data and activity, as well as the MemberPress custom/profile fields data
- New: Made the User ID field available as a table column/filter
- General code improvements and minor bug fixes

3.7.1
- Fixed: issue with Gravity Forms introduced in the 3.7 update - when a user has submissions of different forms, only one of the form's entries was displayed (but multiple times)
- Changed the way the Paid Memberships Pro current membership is determined when a user has multiple membership records. Now it is determined based on the last created membership record for the user (by ID), instead of start date, as in some cases PMPro sets identical start date and time to memberships that were created on different dates.
- Prefix the IDs of the post activity in the user profile section to avoid collisions with other activity items. This might affect previously stored visibility/order settings in the user profile.

3.7.0
- New: Introduced a Page Visit Tracking feature, allowing you to see which pages/posts your users visit and filter your users based on their visits
- New: Profile Builder Pro integration, allowing you to list and search your users' Profile Builder Pro fields
- New: Introduced a new advanced filter in the WooCommerce module, called "Placed an order". This filter allows you to search your users based on the orders they have placed by different order criteria. For example, you can now find all users who have placed an order between date X and X, with order status X, including product X and order value between X and X.
- New: Introduced a new advanced filter in the WooCommerce Subscriptions module, called "Has a subscription". This filter allows you to search your users based on different subscription criteria, such as Start date, End date, Status and Product.
- New: Introduced a User Profile field management, allowing you to hide and reorder the profile fields and user activity, as well as add section titles.
- New: Introduced a "Drop-down select" custom field type, allowing you to specify a set of options to choose as a value for the field
- Redesign of the User Profile section
- Added a "Clear all" button to the filters section, when there are two or more filters applied, allowing you to remove all of the filters
- WooCommerce Subscriptions: for each subscription listed in the user profile, also include the start date, end date, next payment date and a link to the related orders
- WooCommerce Subscriptions: removed the "Is subscribed to" filter, as this can be now replaced with the new "Has a subscription" with "Product X AND Status active"
- WooCommerce Subscriptions: changed the way the "Next payment" field data is retrieved for consistency with the WooCommerce Subscriptions table data: now it also shows past dates for the active subscriptions
- LeranDash: Renamed the "Has/has not enrolled in course" in filter to "Has/has not engaged in course", to be more clear that it actually shows the users who had some activity in the course and not the ones who have access to the course but haven't started it yet
- LearnDash: Separated the "Courses" user activity in user profile into two different lists - "Course Activity" showing progress on all courses that the user has ever engaged in (regardless of whether the user currently has access to the course) and "Course Access" listing all courses that the user has access to (regardless of activity)
- Privacy: added an option to export and erase page visits (from the Page Visit Tracking module) upon user request 
- Privacy: added a suggested text to the Privacy Policy suggestions related with the Page Visit Tracking functionality
- Changed the username link in the user table to be an actual link, instead of attaching a click event to open the user profile. This allows opening the user profile in a new tab.
- User profile: Disable zoom on scroll on the map, as very often the scroll is intended to scroll down the page
- Allow line breaks in note content (notes are no longer created when the Enter key is pressed)
- Fixed: With numeric user meta fields when the value is empty and stored as an empty string, a filter like "smaller than X" or "equals 0" returns those fields
- Fixed: WooCommerce Ordered products filter shows users who have a Subscription with the selected product, but not an actual order that contains the product
- Renamed the "is bigger than" and "is smaller than" numeric operators to "is greater than" and "is less than" respectively
- Various code/style improvements and other minor bug fixes


3.6.6
- New: WooCommerce module - added Billing country, Billing state and Billing city fields and filters
- New: BuddyPress module - added an Active Users report, displaying the number of users who have any kind of BuddyPress activity recorded, supporting daily, weekly, monthly and yearly periods
- New: Ultimate Member module - added an Account status field and filter
- Improved: BuddyPress module - performance optimisation of the way the xProfile fields are loaded in the user profile page
- Learndash module: added a CSS class to the score progress bar, setting the exact score, so that the styles of the separate values can be customized if needed
- Fixed: BuddyPress module - checkboxes and multi-select boxes fields reports not displayed, due to a code change from a previous update
- General code improvements and minor bug fixes

3.6.5
- Easy Digital Downloads reports: Support the Software Licensing 3.6 database structure changes

3.6.4
- Privacy Policy suggestions: Updated the suggested text when the Geolocation module is active to include more details about how the geolocation works, such as how the data is processed and stored
- Removed the usage of the wp_doing_ajax() function on the reports page, to support older WordPress versions

3.6.3
- Implemented GDPR tools available when running WordPress 4.9.6 or newer:
    - Tools to export the Users Insights data when using the WordPress 4.9.6 Personal Data Exporter
    - Tools to remove the Users Insights data when using the WordPress 4.9.6 Personal Data Eraser
    - Regsiters a Privacy module in Module Options where the settings can be configured
    - Suggests texts to add to the WordPress Privacy Policy page
    - More info: https://usersinsights.com/gdpr/
- Moved the Last Seen and Sessions fields to a separate "Activity" module that can be deactivated if needed. This module will be inactive on new installations by default
- Changed the way Geolocation and Device Info are detected, so it also works with the "Activity" module deactivated (it used to depend on the last seen date)
- Make the user table ordered by Date Registered by default when the Activity module is inactive
- Activity module: Increased the minimum time of inactivity required to one hour in order to consider a new user visit as a new session
- Removed the functionality that copies the BuddyPress last login date to the Users Insights last seen field upon module activation
- Fixed: Support the Gravity Forms 2.3 database table name changes
- General code improvements

3.6.2
- Improved: implemented autoload for the plugin files
- Improved: Ultimate Member - make fields available on the Users Insights table based on their privacy settings
- Fixed: Ultimate Member - bug with filtering by an option field when the option has a trailing space
- General code improvements and minor bug fixes

3.6.1
- New: WooCommerce module - added support for the YITH Wishlist and WooCommerce Wishlist plugins, allowing to filter users based on the products that they have in wishlist, as well as explore the individual user wishlists in the user profile section
- New: Easy Digital Downloads module - introduced an "Earnings" report
- New: Event Tickets module - add support for the new PayPal ticket sales functionality, allowing to filter users based on the tickets purchased
- New: WooCommerce subscriptions - introduced a filter allowing to segment the users based on the subscription product that they are subscribed to
- Improved: BuddyPress module - provide a dropdown of the available options when filtering a checkboxes or multiselect field
- Improved: Ultimate Member module - make the 10-star based rating reports show each rating in a separate bar, instead of combining them into ranges
- Fixed: BuddyPress module - do not show unconfirmed group users when filtering by BuddyPress group
- General improvements and minor bug fixes


3.6.0
- New: Events Calendar integration, detecting the data from the Events Calendar and its Events Tickets & Events Tickets Plus extensions
- General code improvements and minor bug fixes

3.5.1
- Optimized the loading of the WooCommerce First Order field - it is now loaded as part of the main query only when used in the filters or the table is sorted by it.

3.5.0
- New: Introduced integration for the Paid Memberships Pro plugin
- New: WooCommerce features:
    - First Order date field & filter in the user table
    - Total Sales Amount report
    - List WooCommerce coupons used in the user profile section
    - Added a link in the WooCommerce order screen linking to the Users Insights profile page of the customer 
    - WooCommerce Memberships: Ended Memberships report
    - WooCommerce Memberships: displayed the cancelled date of the membership (when available) in the user profile section
- Improved: Allow floating labels in the reports that represent amounts
- Improved: Enable AJAX search in the WooCommerce products filter when there is a large number of products available
- Improved: Icons style
- Fixed: Filtering by role issue when there are roles that contain the filtered role as part of their name
- Fixed: Issue with removing expired licenses


3.4.1
- Fixed: empty error message displayed in the Create Segment dialog

3.4.0
- New: Introduced Reports (beta) for most of the modules - now available under Users Insights -> Reports
- WooCommerce module: show order total price in the user profile order list section
- EDD module: show order total price in the user profile order list section
- General code improvements and optimizations

3.3.1
- Fixed: WooCommerce review stars not displayed in user profile section (since 3.3.0 update)
- Fixed: Overflow issue of the custom fields table

3.3.0
- New: Introduced Segments - you can now save your frequently used filters as segments and easily apply them later
- Introduced compatibility with the upcoming Ultimate Member 2.0
- Fixed: Column ordering from the eye icon menu sometimes doesn't work properly
- Fixed: Do not show the bulk action button if the current user is not allowed to update users
- Fixed: Updated the Browser library to fix a PHP7 deprecation notice & detect the Edge browser
- General code improvements

3.2.0
- New: WooCommerce "Has used coupon" filter, showing all customers that have used a selected coupon/discount code
- New: WooCommerce number of reviews column & filter, showing the number of product reviews that each customer has left
- New: List WooCommerce reviews in the user profile page
- New: LearnDash "Has/has not enrolled to course" filters, showing all the users that have/have not enrolled to a particular course, regardless of whether they have completed it or not
- New: LearnDash Number of courses in progress column & filter, showing the number of courses that each user has started but not completed
- New: Added First Name and Last Name as separate columns
- Improved the way the roles are displayed on the table - lists all the role names assigned to the user
- WooCommerce query optimizations - improved the way the Number of Orders, Last Order and Lifetime Value columns are loaded on the table, especially when the table is not sorted by any of these fields


3.1.1
- Fixed: Alignment issue in the user table footer
- Improved: Allow HTML data in the user table
- General code improvements

3.1.0
- Added: Bulk add/remove group functionality
- Improved: The way the WooCommerce Lifetime Value data is loaded - since WooCommerce doesn't always
update this value correctly (it is sometimes set to null), instead of using the WooCommerce value,
we now compute it in the database query
- Improved: General UI improvements of the checkboxes and the dialogs
- Fixed: Compatibility issues with the upcoming WooCommerce 2.7
- General code improvements


3.0.0
- Added: LearnDash Module - detects the LearnDash user activity and makes it available in the user table and filters
- Added: Icons to the user activity list in the user profile section
- Added: A refresh button in the license section of the Module Options page, allowing to refresh the license status
- Fixed: WooCommerce Memberships - cannot filter by membership status when there are no columns from the memberships module visible on the table
- General code improvements

2.9.0
- Added: WooCommerce Memberships module (beta) - retrieves and displays the user data from the WooCommerce Memberships extension
- Added: Next Payment field to the WooCommerce Subscriptions module
- Improved: The style of the elements like EDD & WooCommerce orders in the user profile section
- General code improvements

2.8.0
- Added: WooCommerce Subscriptions module (beta) - retrieves and displays the WooCommerce Subscriptions extension user data, such as number of subscriptions and subscription status
- Added: WooCommerce Lifetime Value field, showing the total amount spent by each user
- Minor bug fixes

2.7.0
- Improved: Introduced custom capabilities for accessing the Users Insights page, managing groups & custom fields and managing options
- Improved: The maps design in the map view and user profile sections
- Improved: The design of the filters - added a search to the option list when it's too long and added icons to the fields to improve the visibility
- Added: Option to filter BuddyPress users by the groups that they belong/don't belong to
- Added: "View Ultimate Member Profile" button in the user profile section
- Added: A read-only date type for the custom fields section. This field can be used to retrieve already stored user meta from a date type. The filters will provide date-based operators and also the table will allow sorting by this field in a chronological order.
- Improved: Replaced the year/month/day selects with a date picker
- General code and design improvements - better dialogs, tooltips on the action buttons, etc.

2.6.1
- Fixed: bug with the date filters

2.6.0
- Replaced Google maps with Leaflet maps (http://leafletjs.com/). Using map tiles by OpenStreetMap contributors(http://www.openstreetmap.org/copyright) and map layers by Stamen Design (http://stamen.com/)
- General code improvements

2.5.0:
- Added: "Is set" and "Is not set" operators for the option fields in the filters
- Added: support for the User Tags extension of Ultimate Member
- Improved: Query optimizations for the single user profile section when a large number of custom fields are registered
- Improved: Ultimate Member Module - provide a drop-down with the available options for the multi-option and checkbox fields in the filters
- Fixed: Issue with the available year range when filtering by a date field
- Fixed: Ultimate Member Module - add support for radio fields that store the data in a PHP serialized format
- Code improvements and minor bug fixes

2.4.2:
- Fixed: Issue with the database query when using special characters
- Added: A debug page that can be helpful to troubleshoot issues

2.4.1:
- Fixed: User table not loading when the Gravity Forms & User Registration Add-on are active, but the Gravity Forms Module of Users Insights is inactive

2.4.0:
- Added: Gravity Forms Module - Provides Gravity Forms related filters and data. Detects and displays the custom user data saved with the Gravity Forms User Registration Add-on.
- Added: New multi-option filter type that works like the text type, but only searches strings for a query - it doesn't include string options like "starts with" or "ends with", as usually those options are saved as serialized or JSON data
- Improved: BuddyPress & Ultimate Member: for performance and usability reasons make the custom user profile fields hidden on the table by default, so that when there are too many fields registered they won't be all displayed on the table
- General code improvements


2.3.0
- Added: BuddyPress Module - automatically detects and displays the custom user profile fields in the user table
- Fixed: Saved year not selected when editing a date filter and date not reset properly when changing the field to filter by option

2.2.0
- Added: Ultimate Member Module - automatically detects and displays the custom user fields data generated with the Ultimate Member forms
- Added: Option to change the default columns order in the Users Insights table
- Added: Option to set the year range for the date fields filters
- Improved: General design and responsive layout improvements
- Fixed: WP 4.5 compatibility issue - color options not displayed when editing a group

2.1.0
- Added: automatic plugin updates from the dashboard. Added a Users Insights License section in the Module Options page that allows adding one global license for both the geolocation and automatic updates
- Improved: Query Optimizations - major refactoring to optimize the query in the users table and the export
- Improved: Design improvements on the modules page
- Fixed: issues with the BuddyPress module in some cases on multi-site
- Fixed: issue with filtering users by role in some cases
- Fixed: issue with the bbPress query when the table includes a left join that returns more than one row per user (e.g. applying a filter "group is set" and the user has more than one group set)
- Fixed: EDD filtering by product ordered not working in some cases
- Fixed: cannot remove all the assigned groups from a user
- Fixed: BuddyPress Groups Created list not displayed in the user profile section on multisite


2.0.1
- Made the CRM features (groups, notes and custom fields) more customizable - added hooks that can be used to change some of their options and functionality from 3rd party plugins_url
- Fixed: Empty map element displayed on the user profile section when the user has a location saved, but the geolocation module is disabled
- Fixed: WooCommerce module - exclude trashed orders from the orders column
- Fixed: Issue with editing a custom field value from the user profile - when the field is a number field and the value of the field is deleted, it shows "null" instead of an empty value
- Fixed: Filtering by role not showing any results
- Improved: The geolocation lookup functionality
- General code improvements

2.0.0
- Added CRM Features, such as:
- Added an option to assign groups to users
- Added notes section where you can add notes for each user
- Added custom user meta fields - added an interface to register custom fields and after the fields are registered, they are available in the users table and filters and they can be updated in the user profile section
- General code improvements and minor bug fixes

1.1.1
- Improved: EDD Module - changed the URL of the View Orders link in the profile section to open the default EDD Payments page filtered by the selected customer (rather than Users Insights generating the payments info)
- Improved: EDD + Geolocation Module - Run the check to save location on purchase confirmation
- Improved: EDD Module - general DB query improvements: made the query joins use the EDD customer ID, instead of relying that the customer will be an author of the payment post
- Fixed: EDD Module - issue with the Lifetime Value filter


1.1.0
- Added: Easy Digital Downloads support, included as a separate module, it retrieves and displays data from the Easy Digital Downloads orders made by the WordPress users
- Fixed: WP 4.4 issue - the line height of the number inputs in the filters section is too big
- Fixed: issue with columns that are casted - apply the casting when ordering by the column as well
- General code improvements and minor bug fixes
