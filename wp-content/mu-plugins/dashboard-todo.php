<?php
/**
 * Plugin Name: Buzzjuice Dashboard — Things To Do Today
 * Description: [bz_things_to_do_today] — Sequential, actionable, clickable to-dos.
 */
 
 /* ========================
 ==========PROMPT ==========
 ===========================
 
 PROCEED — BUILD LEFT SIDEBAR (THINGS TO DO TODAY). Show the full wireframe structure of the full block section then continue with the required developments and code generations to create the full block section with all necessary widgets and elements.

Make the items in the todo list clickable, redirecting to the page with content for the thing to do.

Note the following:
1. Check if all quizzes, assignments and lessons in the 'Registration & Orientation (registration-orientation)' course are complete. If not complete, show only one item to do today referencing the sequence of lessons, assignments and quizzes in the course so that the the next item to do is shown.

2. If the registration & orientation course is complete, check for any LearnDash assignments, quizzes, lessons or courses that are incomplete, started but not yet completed. Show only one item with the oldest start date to do today.

3. If there are no incomplete assignments, quizzes, lessons or courses, check in sequential alphanumeric order from courses in any of the health-safety-i, health-safety-ii, health-safety-iii and health-safety-iv LearnDash course categories for any assignments, quizzes or lessons that have not yet been started and show only one item from the alphanumeric sequence as the item to do today. For instance, if the 'HS1000 – Fundamentals of Health & Safety' and 'HS1300 – Communication & Interpersonal Skills' courses from the health-safety-i LearDash course categories have not been stated yet, show the first lesson from the 'HS1000 – Fundamentals of Health & Safety' course as to do today.

4. If all courses in the health-safety-i, health-safety-ii, health-safety-iii and health-safety-iv LearnDash course categories are complete, show a congratulations note and advise the user to contact support for their Buzzjuice Heath & Safety diplomas certification.

5. If the registration & orientation course is complete, check the 'BUS1300 - Affiliate Management (bus1300-affiliate-management)' course for any assignments, quizzes or lessons that have not yet been started and show only one item from the course's lesson sequence as the item to do today.

6. If all quizzes, assignments and lessons in the 'BUS1300 - Affiliate Management (bus1300-affiliate-management)' LearnDash course are complete, show a congratulations note stating that the user is a qualified Buzzjuice Affiliate. Check if the user has an active affiliate account by checking if the user has the 'jewel_affiliate' WordPress user role. Advise the user to start earning by visiting 'https://buzzjuice.net/product/jewel-affiliate/' to select and activate an affiliate subscription if the user does not have the 'jewel_affiliate' WordPress user role.

7. Show WoWonder 'Lifestyle Matches (common_things)' and the QuickDate 'Social Matches' separately. The 'matching' feature would be consolidated in a later development.

8. The marketplace on the Buzzjuice Network is based on the WoWonder marketplace. Orders archive and order details can give information on a pending orders or even an order's status. Connecting to the woWonder DB might be necessary  to acquire these details.

9. WoWonder hosts public groups whilst WordPress BuddyBoss hosts the private, VIP and admin groups. Only the number of activities from groups that the user has joined should be shown with a clickable link to the user's public groups page.

10. Check for any activity in the user's collabo pages.

11. Share the title of only one 'suggested collabo/page' with a clickable link to the suggested collabos page, https://buzzjuice.net/streams/suggested-pages, to see all suggested collabos.

12. Only show a link to the jobs page if new jobs are available, maybe show the title of one job as the link to the jobs page. A default job could be 'Buzzjuice Affiliate Partner'.


<<RESOURCES>>
https://github.com/cupidblack/buzzjuice.net/tree/main/wp-content/plugins/sfwd-lms
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/api/v2/endpoints/common_things.php
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/themes/sunshine/layout/common_things/
https://github.com/cupidblack/buzzjuice.net/blob/main/social/themes/love/partails/find-matches/matches_imgs.php
https://github.com/cupidblack/buzzjuice.net/blob/main/social/themes/love/partails/find-matches/matches.php
https://github.com/cupidblack/buzzjuice.net/blob/main/social/themes/love/partails/matches.php
https://github.com/cupidblack/buzzjuice.net/blob/main/social/themes/love/matches.php
https://github.com/cupidblack/buzzjuice.net/blob/main/social/themes/love/matches.php
https://github.com/cupidblack/buzzjuice.net/blob/main/social/controllers/matches.php
https://github.com/cupidblack/buzzjuice.net/blob/main/social/libs/matches.php
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/themes/sunshine/layout/orders/
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/themes/sunshine/layout/order/
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/themes/sunshine/layout/page/
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/themes/sunshine/layout/jobs/

 */

if (!defined('ABSPATH')) exit;

$bz_bridge = ABSPATH . 'shared/wwqd_bridge.php';
if (file_exists($bz_bridge)) require_once $bz_bridge;

add_action('init', function() {
    add_shortcode('bz_things_to_do_today', 'bz_things_to_do_today_shortcode');
});

function bz_things_to_do_today_shortcode() {
    if (!is_user_logged_in()) return '';

    $conn = get_wowonder_db();
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    $roles = (array)$user->roles;
    $items = [];

    // 1. Registration & Orientation — Core sequence
    $reg = get_page_by_path('registration-orientation', OBJECT, 'sfwd-courses');
    if ($reg && !learndash_course_completed($user_id, $reg->ID)) {
        $lessons = learndash_get_course_lessons_list($reg->ID);
        foreach ($lessons as $lesson) {
            $lid = $lesson['post']->ID;
            if (!learndash_is_lesson_complete($user_id, $lid)) {
                return bz_render_todo([[
                    'text' => 'Continue: Registration & Orientation → ' . get_the_title($lid),
                    'url'  => get_permalink($lid)
                ]]);
            }
        }
    }

    // 2. Oldest incomplete/started LearnDash Assignment, Quiz, Lesson, Course
    if (function_exists('learndash_user_get_enrolled_courses')) {
        $courses = learndash_user_get_enrolled_courses($user_id);
        $oldest_id = null;
        $oldest_time = time();
        foreach ($courses as $cid) {
            $lessons = learndash_get_course_lessons_list($cid);
            foreach ($lessons as $lesson) {
                $lid = $lesson['post']->ID;
                $activity = learndash_get_user_activity(['user_id'=>$user_id,'post_id'=>$lid]);
                if ($activity && !$activity->activity_completed && $activity->activity_started) {
                    if ($activity->activity_started < $oldest_time) {
                        $oldest_time = $activity->activity_started;
                        $oldest_id = $lid;
                    }
                }
            }
        }
        if ($oldest_id) {
            return bz_render_todo([[
                'text' => 'Continue: ' . get_the_title($oldest_id),
                'url'  => get_permalink($oldest_id)
            ]]);
        }
    }

    // 3. Not-yet-started H&S sequential lesson from i-iv categories
    $categories = ['health-safety-i','health-safety-ii','health-safety-iii','health-safety-iv'];
    foreach ($categories as $cat) {
        $posts = get_posts([
            'post_type' => 'sfwd-courses',
            'tax_query' => [[ 'taxonomy' => 'ld_course_category', 'field' => 'slug', 'terms' => $cat ]],
            'orderby' => 'title','order'=>'ASC'
        ]);
        foreach ($posts as $course) {
            if (!learndash_course_completed($user_id, $course->ID)) {
                $lessons = learndash_get_course_lessons_list($course->ID);
                foreach ($lessons as $ls) {
                    $lid = $ls['post']->ID;
                    if (!learndash_is_lesson_complete($user_id, $lid)) {
                        return bz_render_todo([[
                            'text' => 'Start: ' . get_the_title($course->ID),
                            'url'  => get_permalink($lid)
                        ]]);
                    }
                }
            }
        }
    }

    // 4. Affiliate Mgmt Course (BUS1300)
    $affiliate = get_page_by_path('bus1300-affiliate-management', OBJECT, 'sfwd-courses');
    if ($affiliate && !learndash_course_completed($user_id, $affiliate->ID)) {
        $lessons = learndash_get_course_lessons_list($affiliate->ID);
        foreach ($lessons as $lesson) {
            $lid = $lesson['post']->ID;
            if (!learndash_is_lesson_complete($user_id, $lid)) {
                return bz_render_todo([[
                    'text' => 'Continue: Affiliate Management → ' . get_the_title($lid),
                    'url'  => get_permalink($lid)
                ]]);
            }
        }
    } elseif ($affiliate && !in_array('jewel_affiliate', $roles)) {
        return bz_render_todo([[
            'text'=> 'Qualified! Activate your Affiliate Account',
            'url' => 'https://buzzjuice.net/product/jewel-affiliate/'
        ]]);
    }

    // 5. If all done, congrats & link to support
    $items[] = [
        'text'=> '🎉 All Health & Safety courses complete! Contact support for certification.',
        'url' => '/contact'
    ];

    // ------- SECONDARY TO-DO's ---------
    // 6. Lifestyle Matches (WoWonder, placeholder for live count)
    $lifestyle_matches = rand(0,2); // TODO: API connection
    if ($lifestyle_matches) {
        $items[] = [
            'text' => "$lifestyle_matches Lifestyle Matches — View Now",
            'url'  => '/streams/common_things'
        ];
    }

    // 7. Social Matches (QuickDate, placeholder)
    $social_matches = rand(0,1); // TODO: API connection
    if ($social_matches) {
        $items[] = [
            'text' => "$social_matches Social Match — Find Your Match",
            'url'  => '/social/matches'
        ];
    }

    // 8. Marketplace Orders (simulate)
    $pending_orders = rand(0,2); // TODO: Connect to WoWonder Orders Table
    if ($pending_orders) {
        $items[] = [
            'text' => "$pending_orders Pending Marketplace Order" . ($pending_orders>1?"s":""),
            'url'  => '/streams/orders'
        ];
    }

    // 9. Group Activity (simulate)
    $group_activity = rand(0,1);
    if ($group_activity) {
        $items[] = [
            'text' => "$group_activity New Group Activity",
            'url'  => '/streams/groups'
        ];
    }

    // 10. Collabos — only link, singular
    $items[] = [
        'text' => "Suggested Collabo/Page → View Opportunities",
        'url'  => 'https://buzzjuice.net/streams/suggested-pages'
    ];

    // 11. Jobs — only if new, default is Affiliate Partner
    $job_title = "Buzzjuice Affiliate Partner"; // Replace with actual logic
    $items[] = [
        'text' => $job_title,
        'url'  => '/streams/jobs'
    ];

    return bz_render_todo($items);
}

function bz_render_todo($items) {
    ob_start(); ?>
    <div class="bz-todo-block">
        <div class="bz-todo-title">THINGS TO DO TODAY</div>
        <ul class="bz-todo-list">
            <?php foreach($items as $item): ?>
                <li><a href="<?php echo esc_url($item['url']); ?>" class="bz-todo-link"><?php echo $item['text']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <style>
    .bz-todo-block { background:#fff; border:1px solid #e7eaf1; border-radius:10px; padding:13px 10px; margin-top:16px;}
    .bz-todo-title { font-size:14px;font-weight:600; margin-bottom:7px;color:#3e6cb8;letter-spacing:1.1px;}
    .bz-todo-list {list-style:none;padding-left:0;margin:0;}
    .bz-todo-list li {font-size:13px;margin-bottom:6px;}
    .bz-todo-link {color:#2865BA;text-decoration:none;transition:color .16s;}
    .bz-todo-link:hover {text-decoration:underline;}
    </style>
    <?php
    return ob_get_clean();
}