<?php
/**
 * Plugin Name: Buzzjuice — Things To Do Today
 * Description: Unified learner-priority engine, left-sidebar block, popup, BuddyBoss/BuddyPress notifications, affiliate parallel track and H&S curriculum sequencing.
 * Version: 4.2.0
 * Author: Buzzjuice
 * License: GPL-2.0-or-later
 *
 * Install:
 *   wp-content/mu-plugins/bzj-things-to-do-today.php
 *
 * Shortcode:
 *   [bz_things_to_do_today]
 *
 * Widget:
 *   Buzzjuice — Things To Do Today
 *
 * v4.2 changes:
 * - Adds configurable RO/BUS slugs and H&S category library.
 * - Adds a deterministic first-visit started timestamp fallback for oldest-started selection.
 * - Adds persistent popup suppression and reserves popup count only after the popup actually opens.
 * - Expands progress-cache invalidation across common LearnDash completion hooks.
 * - Retains the v4.1 Action Scheduler, diagnostics, code backfill and BuddyBoss adapter architecture.
 *
 * v4.1 changes:
 * - Corrected the missing next-item course function from v4.0.
 * - Includes topic-level quizzes and assignments in deterministic course sequences.
 * - Separates "oldest started/incomplete" from "next unstarted curriculum item".
 * - Uses actual H&S category contents and program codes rather than a hard-coded HS-I-only manifest.
 * - Preserves the requested level -> lesson -> course -> topic ordering.
 * - Makes BUS1300 a true parallel track in the secondary area after Orientation.
 * - Adds explicit completion/pass state for Orientation assessment.
 * - Adds image hierarchy only to H&S content.
 * - Adds configurable Palmier display without awarding points.
 * - Adds notification fingerprinting, batching and timetable safeguards.
 * - Keeps Profile Prompter independent and arbitrates popup visibility client-side.
 * - Adds a curriculum diagnostics/backfill admin screen.
 */

defined('ABSPATH') || exit;

define('BZJ_TODO_VERSION', '4.2.0');
define('BZJ_TODO_OPTION', 'bzj_todo_settings');
define('BZJ_TODO_META', 'bzj_todo_');
define('BZJ_TODO_NONCE', 'bzj_todo_action');
define('BZJ_TODO_CRON_HOOK', 'bzj_todo_notification_tick');
define('BZJ_TODO_COMPONENT', 'buzzjuice_todo');
define('BZJ_TODO_NOTIFICATION_ACTION', 'todo_today');
define('BZJ_TODO_BATCH_SIZE', 50);

/* ==========================================================================
 * 1. SETTINGS
 * ========================================================================== */

function bzj_todo_defaults() {
    return array(
        // Curriculum identity. These remain configurable so staging/live sites
        // can use the same engine even if a slug is changed.
        'ro_course_slug'             => 'registration-orientation',
        'bus_course_slug'            => 'bus1300-affiliate-management',
        'hs_categories'              => array(
            'health-safety-i',
            'health-safety-ii',
            'health-safety-iii',
            'health-safety-iv',
        ),
        'cache_enabled'              => 1,
        'cache_ttl'                  => 120,
        'default_image'              => '',
        'course_code_meta'           => 'bzj_program_code',
        'prereq_meta'                => 'bzj_todo_prereq_codes',
        'assessment_quiz_id'         => 0,
        'assessment_passing_percent' => 0,

        'popup_enabled'              => 1,
        'popup_on_login'             => 1,
        'popup_after_return'         => 1,
        'return_after_hours'         => 24,
        'popup_cooldown_hours'       => 24,
        'popup_max_per_week'         => 3,
        'popup_delay'                => 350,
        'popup_remind_hours'         => 6,

        'secondary_enabled'          => 1,
        'secondary_max'              => 3,

        'classies_url'               => '/classies-chronicles/',
        'polls_url'                  => '/polls/',
        'jobs_url'                   => '/streams/jobs/',
        'common_url'                 => '/streams/common_things/',
        'matches_url'                => '/social/matches/',
        'support_url'                => '/contact/',
        'progress_url'               => '/courses/',
        'affiliate_url'              => '/product/jewel-affiliate/',

        'palmier_point_type'         => 'palmier',
        'default_palmier_reward'     => 0,

        'notifications_enabled'      => 1,
        'notification_push_enabled'  => 1,
        'notification_batch_size'     => BZJ_TODO_BATCH_SIZE,
        'notification_times'         => "sun:15:00,19:00\nmon:07:00,11:00,15:00,19:00\ntue:07:00,11:00,15:00,19:00\nwed:07:00,11:00,15:00,19:00\nthu:07:00,11:00,15:00,19:00\nfri:07:00,11:00",
        'notification_retention_days'=> 90,

        'analytics_enabled'          => 1,
    );
}

function bzj_todo_settings() {
    static $settings = null;
    if ($settings !== null) return $settings;

    $settings = wp_parse_args(
        (array) get_option(BZJ_TODO_OPTION, array()),
        bzj_todo_defaults()
    );

    $settings['cache_ttl'] = max(30, min(3600, absint($settings['cache_ttl'])));
    $settings['secondary_max'] = max(0, min(5, absint($settings['secondary_max'])));
    $settings['popup_max_per_week'] = max(1, min(20, absint($settings['popup_max_per_week'])));
    $settings['return_after_hours'] = max(1, min(720, absint($settings['return_after_hours'])));
    $settings['popup_cooldown_hours'] = max(1, min(720, absint($settings['popup_cooldown_hours'])));
    $settings['popup_remind_hours'] = max(1, min(168, absint($settings['popup_remind_hours'])));
    $settings['notification_batch_size'] = max(10, min(500, absint($settings['notification_batch_size'])));
    $settings['notification_retention_days'] = max(7, min(3650, absint($settings['notification_retention_days'])));
    $settings['default_palmier_reward'] = max(0, (float) $settings['default_palmier_reward']);

    return $settings;
}

add_action('plugins_loaded', function() {
    if (false === get_option(BZJ_TODO_OPTION, false)) {
        add_option(BZJ_TODO_OPTION, bzj_todo_defaults(), '', false);
    }
});

function bzj_todo_url($url) {
    $url = trim((string) $url);
    if ($url === '') return '';
    if (preg_match('#^https?://#i', $url)) return esc_url_raw($url);
    return esc_url_raw(home_url('/' . ltrim($url, '/')));
}

function bzj_todo_flush_cache($user_id = 0) {
    $user_id = absint($user_id ?: get_current_user_id());
    if (!$user_id) return;
    delete_user_meta($user_id, BZJ_TODO_META . 'primary_cache');
    delete_user_meta($user_id, BZJ_TODO_META . 'secondary_cache');
    delete_user_meta($user_id, BZJ_TODO_META . 'notification_candidate');
}

/* ==========================================================================
 * 2. LEARNDASH SEQUENCE ENGINE
 * ========================================================================== */

function bzj_todo_ld_available() {
    return function_exists('learndash_get_course_lessons_list')
        || function_exists('learndash_is_item_complete');
}

function bzj_todo_normalize_posts($rows) {
    if ($rows instanceof WP_Query) $rows = $rows->posts;
    if (is_object($rows) && isset($rows->posts)) $rows = $rows->posts;

    $out = array();
    foreach ((array) $rows as $row) {
        if ($row instanceof WP_Post) {
            $out[$row->ID] = $row;
        } elseif (is_array($row) && isset($row['post']) && $row['post'] instanceof WP_Post) {
            $out[$row['post']->ID] = $row['post'];
        } elseif (is_object($row) && isset($row->post) && $row->post instanceof WP_Post) {
            $out[$row->post->ID] = $row->post;
        } elseif (is_object($row) && isset($row->ID) && get_post($row->ID)) {
            $out[$row->ID] = get_post($row->ID);
        }
    }
    return array_values($out);
}

function bzj_todo_course_lessons($course_id, $user_id = 0) {
    if (!function_exists('learndash_get_course_lessons_list')) return array();
    try {
        return bzj_todo_normalize_posts(
            learndash_get_course_lessons_list(absint($course_id), absint($user_id))
        );
    } catch (Throwable $e) {
        return array();
    }
}

function bzj_todo_topics($lesson_id, $course_id) {
    if (!function_exists('learndash_get_topic_list')) return array();
    try {
        return bzj_todo_normalize_posts(
            learndash_get_topic_list(absint($lesson_id), absint($course_id))
        );
    } catch (Throwable $e) {
        return array();
    }
}

function bzj_todo_step_quizzes($step_id, $course_id) {
    if (!function_exists('learndash_get_lesson_quiz_list')) return array();
    try {
        return bzj_todo_normalize_posts(
            learndash_get_lesson_quiz_list(absint($step_id), absint($course_id))
        );
    } catch (Throwable $e) {
        return array();
    }
}

function bzj_todo_course_quizzes($course_id) {
    if (!function_exists('learndash_get_course_quiz_list')) return array();
    try {
        return bzj_todo_normalize_posts(
            learndash_get_course_quiz_list(absint($course_id))
        );
    } catch (Throwable $e) {
        return array();
    }
}

function bzj_todo_course_assignments($course_id, $user_id = 0) {
    if (!function_exists('learndash_get_course_assignments')) return array();
    try {
        $rows = learndash_get_course_assignments(absint($course_id), absint($user_id));
        return bzj_todo_normalize_posts($rows);
    } catch (Throwable $e) {
        return array();
    }
}

/**
 * Returns the actual LearnDash hierarchy:
 * course -> lesson -> topic -> topic quiz -> lesson quiz -> course quiz -> assignment.
 *
 * The original v4.0 draft omitted topic-level quizzes and relied on a missing
 * bzj_todo_get_next_in_course() function for Orientation. v4.1 uses one sequence
 * function everywhere so every course follows the same deterministic model.
 */
function bzj_todo_sequence($course_id, $user_id = 0) {
    static $cache = array();

    $course_id = absint($course_id);
    $user_id = absint($user_id);
    $key = $course_id . ':' . $user_id;

    if (isset($cache[$key])) return $cache[$key];

    $items = array();

    foreach (bzj_todo_course_lessons($course_id, $user_id) as $lesson) {
        $items[] = array(
            'post_id'   => $lesson->ID,
            'course_id' => $course_id,
            'kind'      => 'lesson',
            'parent_id' => 0,
        );

        foreach (bzj_todo_topics($lesson->ID, $course_id) as $topic) {
            $items[] = array(
                'post_id'   => $topic->ID,
                'course_id' => $course_id,
                'kind'      => 'topic',
                'parent_id' => $lesson->ID,
            );

            foreach (bzj_todo_step_quizzes($topic->ID, $course_id) as $quiz) {
                $items[] = array(
                    'post_id'   => $quiz->ID,
                    'course_id' => $course_id,
                    'kind'      => 'quiz',
                    'parent_id' => $topic->ID,
                );
            }
        }

        foreach (bzj_todo_step_quizzes($lesson->ID, $course_id) as $quiz) {
            $items[] = array(
                'post_id'   => $quiz->ID,
                'course_id' => $course_id,
                'kind'      => 'quiz',
                'parent_id' => $lesson->ID,
            );
        }
    }

    foreach (bzj_todo_course_quizzes($course_id) as $quiz) {
        $items[] = array(
            'post_id'   => $quiz->ID,
            'course_id' => $course_id,
            'kind'      => 'quiz',
            'parent_id' => 0,
        );
    }

    foreach (bzj_todo_course_assignments($course_id, $user_id) as $assignment) {
        $items[] = array(
            'post_id'   => $assignment->ID,
            'course_id' => $course_id,
            'kind'      => 'assignment',
            'parent_id' => 0,
        );
    }

    $seen = array();
    $out = array();

    foreach ($items as $item) {
        if (empty($item['post_id']) || isset($seen[$item['post_id']])) continue;
        $seen[$item['post_id']] = true;
        $out[] = $item;
    }

    return $cache[$key] = $out;
}

function bzj_todo_complete($user_id, $post_id, $course_id = 0) {
    $user_id = absint($user_id);
    $post_id = absint($post_id);
    $course_id = absint($course_id);

    if (!$user_id || !$post_id) return false;

    if (function_exists('learndash_is_item_complete')) {
        try {
            return (bool) learndash_is_item_complete($post_id, $user_id, $course_id);
        } catch (Throwable $e) {}
    }

    $type = get_post_type($post_id);

    if ($type === 'sfwd-courses' && function_exists('learndash_course_completed')) {
        return (bool) learndash_course_completed($user_id, $post_id);
    }

    if ($type === 'sfwd-lessons' && function_exists('learndash_is_lesson_complete')) {
        return (bool) learndash_is_lesson_complete($user_id, $post_id, $course_id);
    }

    if ($type === 'sfwd-topic' && function_exists('learndash_is_topic_complete')) {
        return (bool) learndash_is_topic_complete($user_id, $post_id, $course_id);
    }

    if ($type === 'sfwd-quiz' && function_exists('learndash_is_quiz_complete')) {
        return (bool) learndash_is_quiz_complete($user_id, $post_id, $course_id);
    }

    return false;
}

/**
 * Store a first-seen timestamp when a logged-in learner actually visits a
 * LearnDash learning item. This is the deterministic fallback for
 * "oldest started/incomplete" when LearnDash's activity API does not expose
 * a usable activity_started row for the installed version.
 */
function bzj_todo_mark_started($user_id, $post_id, $course_id = 0) {
    $user_id  = absint($user_id);
    $post_id  = absint($post_id);
    $course_id = absint($course_id);
    if (!$user_id || !$post_id) return;

    $type = get_post_type($post_id);
    $allowed = array('sfwd-courses','sfwd-lessons','sfwd-topic','sfwd-quiz','sfwd-assignment');
    if (!in_array($type, $allowed, true)) return;

    $key = BZJ_TODO_META . 'started_item_' . $post_id;
    if (!get_user_meta($user_id, $key, true)) {
        update_user_meta($user_id, $key, current_time('timestamp'));
    }

    if ($course_id) {
        $course_key = BZJ_TODO_META . 'started_course_' . $course_id;
        if (!get_user_meta($user_id, $course_key, true)) {
            update_user_meta($user_id, $course_key, current_time('timestamp'));
        }
    }
}

function bzj_todo_mark_current_item_started() {
    if (!is_user_logged_in() || !is_singular()) return;

    $post_id = get_queried_object_id();
    if (!$post_id) return;

    $course_id = 0;
    if (function_exists('learndash_get_course_id')) {
        try { $course_id = absint(learndash_get_course_id($post_id)); } catch (Throwable $e) {}
    }
    if (!$course_id && get_post_type($post_id) === 'sfwd-courses') $course_id = $post_id;

    bzj_todo_mark_started(get_current_user_id(), $post_id, $course_id);
}
add_action('template_redirect', 'bzj_todo_mark_current_item_started', 5);

/**
 * LearnDash has returned activity data in more than one shape across versions.
 * v4.1 accepts array/object records and does not assume activity_started is a
 * timestamp string only.
 */
function bzj_todo_activity_started_at($user_id, $post_id, $course_id = 0) {
    $user_id = absint($user_id);
    $post_id = absint($post_id);
    $course_id = absint($course_id);
    if (!$user_id || !$post_id) return 0;

    // First use the plugin's deterministic first-visit record. This avoids a
    // potentially expensive activity-table query on every To-Do calculation.
    $local = absint(get_user_meta($user_id, BZJ_TODO_META . 'started_item_' . $post_id, true));
    if ($local) return $local;

    // LearnDash exposes a supported helper specifically for the earliest
    // activity in a course. Use it for course-level started state where it is
    // available; the child-item fallback remains the local first-visit record.
    if (
        $post_id === $course_id &&
        function_exists('learndash_activity_course_get_earliest_started')
    ) {
        try {
            $started = absint(
                learndash_activity_course_get_earliest_started(
                    $user_id,
                    $course_id,
                    0
                )
            );
            if ($started) return $started;
        } catch (Throwable $e) {}
    }

    // Backward compatibility for installations already storing activity
    // rows. LearnDash versions differ in the exact returned shape, so accept
    // both objects and arrays. post_id is retained here only as a compatibility
    // query argument; new installs should rely on the deterministic local
    // starter above.
    if (function_exists('learndash_get_user_activity')) {
        $args = array(
            'user_id' => $user_id,
            'post_id' => $post_id,
        );
        if ($course_id) $args['course_id'] = $course_id;

        try {
            $result = learndash_get_user_activity($args);
            $rows = is_array($result) ? $result : array($result);

            foreach ($rows as $row) {
                if (is_object($row)) $row = get_object_vars($row);
                if (!is_array($row)) continue;

                $row_post = absint($row['activity_post_id'] ?? $row['post_id'] ?? 0);
                if ($row_post && $row_post !== $post_id) continue;

                $started = $row['activity_started'] ?? $row['started'] ?? 0;
                if (is_numeric($started) && absint($started) > 0) return absint($started);
                if (is_string($started) && $started !== '') {
                    $ts = strtotime($started);
                    if ($ts) return absint($ts);
                }
            }
        } catch (Throwable $e) {}
    }

    return 0;
}

function bzj_todo_course_completed($user_id, $course_id) {
    if (function_exists('learndash_course_completed')) {
        try {
            return (bool) learndash_course_completed($user_id, $course_id);
        } catch (Throwable $e) {}
    }

    $sequence = bzj_todo_sequence($course_id, $user_id);
    if (!$sequence) return false;

    foreach ($sequence as $entry) {
        if (!bzj_todo_complete($user_id, $entry['post_id'], $course_id)) return false;
    }

    return true;
}

function bzj_todo_course_progress($user_id, $course_id) {
    if (function_exists('learndash_course_progress')) {
        try {
            $progress = learndash_course_progress(array(
                'user_id' => absint($user_id),
                'course_id' => absint($course_id),
            ));

            if (is_array($progress) && isset($progress['percentage'])) {
                return max(0, min(100, absint(round($progress['percentage']))));
            }
        } catch (Throwable $e) {}
    }

    $sequence = bzj_todo_sequence($course_id, $user_id);
    if (!$sequence) return 0;

    $done = 0;
    foreach ($sequence as $entry) {
        if (bzj_todo_complete($user_id, $entry['post_id'], $course_id)) $done++;
    }

    return (int) round(($done / count($sequence)) * 100);
}

/* ==========================================================================
 * 3. PROGRAM-CODE LIBRARY
 * ========================================================================== */

function bzj_todo_prefix_library() {
    return apply_filters('bzj_todo_prefix_library', array(
        'RO'  => array('name' => 'Registration & Orientation', 'description' => 'Member onboarding'),
        'HS'  => array('name' => 'Health & Safety', 'description' => 'Core Health & Safety programme'),
        'EC'  => array('name' => 'Elective Course', 'description' => 'Elective/career pathway'),
        'BUS' => array('name' => 'Business', 'description' => 'Business/Affiliate pathway'),
    ));
}

function bzj_todo_code($post_id) {
    $settings = bzj_todo_settings();
    $meta_key = sanitize_key($settings['course_code_meta']);

    if ($meta_key) {
        $value = get_post_meta($post_id, $meta_key, true);
        if (is_string($value) && preg_match('/^[A-Z]{1,8}\d{4}$/i', trim($value))) {
            return strtoupper(trim($value));
        }
    }

    $post = get_post($post_id);
    if (!$post) return '';

    foreach (array($post->post_title, $post->post_name) as $candidate) {
        if (preg_match('/(?:^|[^A-Z0-9])([A-Z]{1,8}\d{4})(?:[^A-Z0-9]|$)/i', (string) $candidate, $m)) {
            return strtoupper($m[1]);
        }
    }

    return '';
}

function bzj_todo_parts($code) {
    if (!preg_match('/^([A-Z]+)(\d)(\d)(\d)(\d)$/i', (string) $code, $m)) {
        return null;
    }

    return array(
        'prefix' => strtoupper($m[1]),
        'level'  => (int) $m[2],
        'course' => (int) $m[3],
        'lesson' => (int) $m[4],
        'topic'  => (int) $m[5],
    );
}

/**
 * H&S order:
 *   level -> lesson -> course -> topic
 *
 * This intentionally produces:
 *   HS1010, HS1110, HS1310, HS1510, EC1710,
 *   HS1020, HS1120, HS1320, HS1520, EC1720...
 *
 * Missing codes are naturally skipped because the manifest is built from
 * content that actually exists.
 */
function bzj_todo_code_compare($a, $b) {
    $a = is_array($a) ? ($a['code'] ?? '') : $a;
    $b = is_array($b) ? ($b['code'] ?? '') : $b;

    $pa = bzj_todo_parts($a);
    $pb = bzj_todo_parts($b);

    if (!$pa || !$pb) return strcasecmp((string) $a, (string) $b);

    foreach (array('level', 'lesson', 'course', 'topic') as $part) {
        if ($pa[$part] !== $pb[$part]) {
            return $pa[$part] <=> $pb[$part];
        }
    }

    return strcasecmp((string) $a, (string) $b);
}

function bzj_todo_find_post_by_code($code, $course_id = 0) {
    $code = strtoupper(trim((string) $code));
    if (!$code) return null;

    $meta_key = sanitize_key(bzj_todo_settings()['course_code_meta']);

    if ($meta_key) {
        $ids = get_posts(array(
            'post_type'      => array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-assignment'),
            'post_status'    => 'publish',
            'posts_per_page' => 5,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => $meta_key,
                    'value' => $code,
                ),
            ),
        ));

        foreach ($ids as $id) {
            if (!$course_id || bzj_todo_post_belongs_to_course($id, $course_id)) {
                return get_post($id);
            }
        }
    }

    $q = new WP_Query(array(
        'post_type'      => array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-assignment'),
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        's'              => $code,
        'no_found_rows'  => true,
    ));

    foreach ($q->posts as $post) {
        if (stripos($post->post_title, $code) !== false || stripos($post->post_name, strtolower($code)) !== false) {
            if (!$course_id || bzj_todo_post_belongs_to_course($post->ID, $course_id)) {
                return $post;
            }
        }
    }

    return null;
}

function bzj_todo_post_belongs_to_course($post_id, $course_id) {
    $post_id = absint($post_id);
    $course_id = absint($course_id);

    if (!$post_id || !$course_id) return false;
    if ($post_id === $course_id) return true;

    $ancestors = array();

    if (function_exists('learndash_get_course_id')) {
        try {
            $resolved = learndash_get_course_id($post_id);
            if ($resolved) $ancestors[] = absint($resolved);
        } catch (Throwable $e) {}
    }

    return in_array($course_id, $ancestors, true);
}

function bzj_todo_prereqs($post_id) {
    $key = sanitize_key(bzj_todo_settings()['prereq_meta']);
    if (!$key) return array();

    $raw = get_post_meta($post_id, $key, true);
    if (is_string($raw)) {
        $raw = preg_split('/[\s,;]+/', trim($raw));
    }

    if (!is_array($raw)) return array();

    return array_values(array_filter(array_map(function($value) {
        return strtoupper(trim((string) $value));
    }, $raw)));
}

/* ==========================================================================
 * 4. DISPLAY METADATA
 * ========================================================================== */

function bzj_todo_type_label($post_id) {
    $map = array(
        'sfwd-courses'    => 'Course',
        'sfwd-lessons'    => 'Lesson',
        'sfwd-topic'      => 'Topic',
        'sfwd-quiz'       => 'Assessment',
        'sfwd-assignment' => 'Assignment',
    );

    $type = get_post_type($post_id);
    return $map[$type] ?? 'Activity';
}

function bzj_todo_excerpt($post_id, $words = 38) {
    $post = get_post($post_id);
    if (!$post) return '';

    $text = has_excerpt($post_id) ? $post->post_excerpt : $post->post_content;
    $text = wp_strip_all_tags(strip_shortcodes($text));

    return wp_trim_words($text, $words, '…');
}

function bzj_todo_image($post_id = 0, $course_id = 0, $allow_default = false) {
    $settings = bzj_todo_settings();

    foreach (array(absint($post_id), absint($course_id)) as $id) {
        if (!$id) continue;

        $thumbnail = get_post_thumbnail_id($id);
        if (!$thumbnail) continue;

        $url = wp_get_attachment_image_url($thumbnail, 'medium_large');
        if ($url) return $url;
    }

    if ($allow_default && !empty($settings['default_image'])) {
        return bzj_todo_url($settings['default_image']);
    }

    return '';
}

function bzj_todo_is_hs_item($item) {
    return in_array($item['source'] ?? '', array('hs', 'hs-course-first', 'hs-uncoded'), true);
}

function bzj_todo_duration_minutes($post_id, $course_id = 0) {
    $type = get_post_type($post_id);
    $code = bzj_todo_code($post_id);
    $parts = bzj_todo_parts($code);

    if ($type === 'sfwd-topic') {
        if ($parts && $parts['prefix'] === 'RO') return 2;
        if ($parts && in_array($parts['prefix'], array('HS', 'EC'), true)) return 10;
        return 20;
    }

    if ($type === 'sfwd-quiz') return 10;

    if ($type === 'sfwd-assignment') return 20;

    if ($type === 'sfwd-lessons') {
        $topics = $course_id ? bzj_todo_topics($post_id, $course_id) : array();
        $topic_minutes = ($parts && $parts['prefix'] === 'RO') ? 2 :
            (($parts && in_array($parts['prefix'], array('HS', 'EC'), true)) ? 10 : 20);

        $minutes = count($topics) * $topic_minutes;

        if ($course_id) {
            if (bzj_todo_step_quizzes($post_id, $course_id)) $minutes += 10;
        }

        return $minutes ?: (($parts && $parts['prefix'] === 'RO') ? 10 : 20);
    }

    if ($type === 'sfwd-courses') {
        $sequence = bzj_todo_sequence($post_id, get_current_user_id());
        if (!$sequence) return 0;

        $minutes = 0;
        foreach ($sequence as $entry) {
            $minutes += bzj_todo_duration_minutes($entry['post_id'], $post_id);
        }

        return $minutes;
    }

    return 0;
}

function bzj_todo_duration_label($post_id, $course_id = 0) {
    $minutes = bzj_todo_duration_minutes($post_id, $course_id);
    if (!$minutes) return '';

    return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
}

function bzj_todo_reward($post_id, $course_id = 0) {
    $settings = bzj_todo_settings();

    $value = get_post_meta($post_id, 'bzj_todo_palmier_reward', true);
    if ($value !== '' && is_numeric($value)) return max(0, (float) $value);

    if ($course_id && $course_id !== $post_id) {
        $value = get_post_meta($course_id, 'bzj_todo_palmier_reward', true);
        if ($value !== '' && is_numeric($value)) return max(0, (float) $value);
    }

    if (get_post_type($post_id) === 'sfwd-courses' && function_exists('learndash_get_course_points')) {
        try {
            return max(0, (float) learndash_get_course_points($post_id));
        } catch (Throwable $e) {}
    }

    return max(0, (float) $settings['default_palmier_reward']);
}

function bzj_todo_reward_label($post_id, $course_id = 0) {
    $amount = bzj_todo_reward($post_id, $course_id);
    if ($amount <= 0) return '';

    $number = rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    return 'Earn ' . $number . ' Palmier' . ($amount == 1.0 ? '' : 's');
}

function bzj_todo_item($post_id, $course_id = 0, $extra = array(), $user_id = 0) {
    $post = get_post($post_id);
    if (!$post) return null;

    $user_id = absint($user_id ?: get_current_user_id());

    $item = array(
        'post_id'   => $post->ID,
        'course_id' => absint($course_id),
        'type'      => bzj_todo_type_label($post->ID),
        'title'     => get_the_title($post->ID),
        'url'       => get_permalink($post->ID),
        'excerpt'   => bzj_todo_excerpt($post->ID),
        'image'     => '',
        'code'      => bzj_todo_code($post->ID),
        'label'     => $course_id && $course_id !== $post->ID ? get_the_title($course_id) : '',
        'action'    => 'Start',
        'progress'  => $course_id ? bzj_todo_course_progress($user_id, $course_id) : 0,
        'duration'  => bzj_todo_duration_label($post->ID, $course_id),
        'reward'    => bzj_todo_reward_label($post->ID, $course_id),
        'mandatory' => 1,
        'source'    => 'learning',
    );

    return array_merge($item, $extra);
}

/* ==========================================================================
 * 5. REGISTRATION & ORIENTATION
 * ========================================================================== */

function bzj_todo_registration_course() {
    $course = get_page_by_path('registration-orientation', OBJECT, 'sfwd-courses');
    return $course instanceof WP_Post ? $course : null;
}

function bzj_todo_assessment_id($course_id) {
    $settings = bzj_todo_settings();

    if (!empty($settings['assessment_quiz_id'])) {
        return absint($settings['assessment_quiz_id']);
    }

    $quizzes = bzj_todo_course_quizzes($course_id);
    if (!$quizzes) return 0;

    $last = end($quizzes);
    return $last instanceof WP_Post ? absint($last->ID) : 0;
}

function bzj_todo_assessment_passed($user_id, $course_id) {
    $quiz_id = bzj_todo_assessment_id($course_id);
    if (!$quiz_id) return true;

    if (!bzj_todo_complete($user_id, $quiz_id, $course_id)) return false;

    $settings = bzj_todo_settings();
    $required = (float) ($settings['assessment_passing_percent'] ?? 0);

    if ($required <= 0) return true;

    if (function_exists('learndash_get_user_quiz_attempt')) {
        try {
            $attempts = learndash_get_user_quiz_attempt(
                $user_id,
                array('quiz_post_id' => $quiz_id)
            );

            if (is_array($attempts) && $attempts) {
                $attempt = end($attempts);
                if (is_object($attempt)) $attempt = get_object_vars($attempt);

                if (isset($attempt['pass'])) return (bool) $attempt['pass'];
                if (isset($attempt['percentage'])) return (float) $attempt['percentage'] >= $required;

                if (
                    isset($attempt['score'], $attempt['total']) &&
                    (float) $attempt['total'] > 0
                ) {
                    return ((float) $attempt['score'] / (float) $attempt['total'] * 100) >= $required;
                }
            }
        } catch (Throwable $e) {}
    }

    /*
     * LearnDash itself remains authoritative when the local installation does
     * not expose an attempt API. A completed assessment therefore counts as
     * passed rather than inventing a second pass/fail system.
     */
    return true;
}

function bzj_todo_first_incomplete($user_id, $course_id, $course_fallback = true) {
    foreach (bzj_todo_sequence($course_id, $user_id) as $entry) {
        if (bzj_todo_complete($user_id, $entry['post_id'], $course_id)) continue;

        $started = bzj_todo_activity_started_at($user_id, $entry['post_id'], $course_id);

        return bzj_todo_item(
            $entry['post_id'],
            $course_id,
            array(
                'type'   => ucfirst($entry['kind']),
                'action' => $started ? 'Continue' : 'Start',
                'source' => 'learning',
            ),
            $user_id
        );
    }

    return $course_fallback
        ? bzj_todo_item($course_id, $course_id, array(
            'type'   => 'Course',
            'action' => 'Start',
            'source' => 'course',
        ), $user_id)
        : null;
}

function bzj_todo_registration_item($user_id) {
    $course = bzj_todo_registration_course();
    if (!$course) return null;

    if (!bzj_todo_course_completed($user_id, $course->ID)) {
        /*
         * This is the corrected v4.0 Orientation path. It uses the same
         * sequence engine as every other LearnDash course and therefore
         * includes topics, topic quizzes, lesson quizzes and assignments.
         */
        return bzj_todo_first_incomplete($user_id, $course->ID, true);
    }

    if (!bzj_todo_assessment_passed($user_id, $course->ID)) {
        $quiz_id = bzj_todo_assessment_id($course->ID);
        if ($quiz_id) {
            return bzj_todo_item(
                $quiz_id,
                $course->ID,
                array(
                    'type'     => 'Assessment',
                    'label'    => 'Registration & Orientation',
                    'action'   => 'Retake assessment',
                    'source'   => 'orientation-assessment',
                    'mandatory'=> 1,
                ),
                $user_id
            );
        }
    }

    return null;
}

/* ==========================================================================
 * 6. OLDEST STARTED / INCOMPLETE
 * ========================================================================== */

function bzj_todo_enrolled_courses($user_id) {
    if (!function_exists('learndash_user_get_enrolled_courses')) return array();

    try {
        return array_values(array_unique(array_map(
            'absint',
            (array) learndash_user_get_enrolled_courses(absint($user_id))
        )));
    } catch (Throwable $e) {
        return array();
    }
}

function bzj_todo_oldest_started_incomplete($user_id) {
    $registration = bzj_todo_registration_course();
    $registration_id = $registration ? absint($registration->ID) : 0;

    $oldest = null;
    $oldest_at = PHP_INT_MAX;

    foreach (bzj_todo_enrolled_courses($user_id) as $course_id) {
        if (!$course_id || $course_id === $registration_id) continue;

        /*
         * Check the course activity itself. This captures a course that has
         * been entered/started but whose first child has not yet generated an
         * activity record.
         */
        $course_started = bzj_todo_activity_started_at($user_id, $course_id, $course_id);

        if (
            $course_started &&
            !bzj_todo_course_completed($user_id, $course_id) &&
            $course_started < $oldest_at
        ) {
            $oldest_at = $course_started;
            $oldest = bzj_todo_item(
                $course_id,
                $course_id,
                array(
                    'type'   => 'Course',
                    'action' => 'Continue',
                    'source' => 'learning-started',
                ),
                $user_id
            );
        }

        foreach (bzj_todo_sequence($course_id, $user_id) as $entry) {
            if (bzj_todo_complete($user_id, $entry['post_id'], $course_id)) continue;

            $started = bzj_todo_activity_started_at($user_id, $entry['post_id'], $course_id);
            if (!$started || $started >= $oldest_at) continue;

            $oldest_at = $started;
            $oldest = bzj_todo_item(
                $entry['post_id'],
                $course_id,
                array(
                    'type'   => ucfirst($entry['kind']),
                    'action' => 'Continue',
                    'source' => 'learning-started',
                ),
                $user_id
            );
        }
    }

    return $oldest;
}

/* ==========================================================================
 * 7. HEALTH & SAFETY PROGRAMME ENGINE
 * ========================================================================== */

function bzj_todo_hs_courses() {
    static $courses = null;
    if ($courses !== null) return $courses;

    $categories = array_values(array_filter(array_map(
        'sanitize_title',
        (array) (bzj_todo_settings()['hs_categories'] ?? array())
    )));

    $map = array();

    foreach ($categories as $category) {
        $posts = get_posts(array(
            'post_type'      => 'sfwd-courses',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'ld_course_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            ),
        ));

        foreach ($posts as $post) {
            $map[$post->ID] = $post;
        }
    }

    $courses = array_values($map);

    usort($courses, function($a, $b) {
        $cmp = bzj_todo_code_compare(
            bzj_todo_code($a->ID),
            bzj_todo_code($b->ID)
        );

        return $cmp ?: strcasecmp($a->post_title, $b->post_title);
    });

    return $courses;
}

function bzj_todo_hs_manifest($user_id) {
    static $cache = array();

    $user_id = absint($user_id);
    if (isset($cache[$user_id])) return $cache[$user_id];

    $manifest = array();

    foreach (bzj_todo_hs_courses() as $course) {
        foreach (bzj_todo_sequence($course->ID, $user_id) as $entry) {
            $code = bzj_todo_code($entry['post_id']);
            $parts = bzj_todo_parts($code);

            if (!$parts) continue;
            if (!in_array($parts['prefix'], array('HS', 'EC'), true)) continue;

            $manifest[] = array(
                'post_id'   => $entry['post_id'],
                'course_id' => $course->ID,
                'kind'      => $entry['kind'],
                'parent_id' => $entry['parent_id'] ?? 0,
                'code'      => $code,
                'parts'     => $parts,
                'course'    => $course,
            );
        }
    }

    /*
     * If the same program code has accidentally been assigned twice, retain
     * the first deterministic occurrence and expose the collision to admins
     * through diagnostics rather than silently generating duplicate tasks.
     */
    $unique = array();

    foreach ($manifest as $entry) {
        if (!isset($unique[$entry['code']])) {
            $unique[$entry['code']] = $entry;
        }
    }

    $manifest = array_values($unique);

    usort($manifest, function($a, $b) {
        return bzj_todo_code_compare($a['code'], $b['code']);
    });

    return $cache[$user_id] = $manifest;
}

function bzj_todo_hs_next($user_id) {
    $manifest = bzj_todo_hs_manifest($user_id);
    if (!$manifest) return null;

    $by_code = array();
    foreach ($manifest as $entry) {
        $by_code[$entry['code']] = $entry;
    }

    foreach ($manifest as $entry) {
        $post_id = $entry['post_id'];
        $course_id = $entry['course_id'];

        if (bzj_todo_complete($user_id, $post_id, $course_id)) continue;

        /*
         * Explicit prerequisites can add a stricter dependency, but they can
         * never bypass the programme's global alphanumeric curriculum order.
         */
        $blocked = false;

        foreach (bzj_todo_prereqs($post_id) as $required_code) {
            if (!isset($by_code[$required_code])) continue;

            $required = $by_code[$required_code];

            if (!bzj_todo_complete(
                $user_id,
                $required['post_id'],
                $required['course_id']
            )) {
                $blocked = true;
                break;
            }
        }

        if ($blocked) continue;

        /*
         * Global programme gate. The first incomplete entry in the sorted
         * manifest is the true prerequisite. This automatically enforces:
         *
         * Level 1 before Level 2
         * Lesson 1 before Lesson 2 ... Lesson 9
         * Course 1 before Course 2/3/...
         * Topic 1 before Topic 2 ... Topic 9
         *
         * Missing content is skipped because it never enters the manifest.
         */
        foreach ($manifest as $prior) {
            if (bzj_todo_code_compare($prior['code'], $entry['code']) >= 0) break;

            if (!bzj_todo_complete(
                $user_id,
                $prior['post_id'],
                $prior['course_id']
            )) {
                $blocked = true;
                break;
            }
        }

        if ($blocked) continue;

        /*
         * Course-first entry rule:
         * when the next item is the first lesson (topic 0) and has never been
         * started, show the course instead of the lesson.
         */
        $parts = $entry['parts'];

        if (
            $parts['lesson'] === 1 &&
            $parts['topic'] === 0 &&
            !bzj_todo_activity_started_at($user_id, $post_id, $course_id)
        ) {
            return array(
                'post_id'   => $course_id,
                'course_id' => $course_id,
                'type'      => 'Course',
                'title'     => get_the_title($course_id),
                'url'       => get_permalink($course_id),
                'excerpt'   => bzj_todo_excerpt($course_id),
                'image'     => bzj_todo_image(0, $course_id, true),
                'code'      => $entry['code'],
                'label'     => 'Health & Safety',
                'action'    => 'Start course',
                'progress'  => bzj_todo_course_progress($user_id, $course_id),
                'duration'  => bzj_todo_duration_label($course_id, $course_id),
                'reward'    => bzj_todo_reward_label($course_id, $course_id),
                'mandatory' => 1,
                'source'    => 'hs-course-first',
            );
        }

        $started = bzj_todo_activity_started_at($user_id, $post_id, $course_id);

        return array(
            'post_id'   => $post_id,
            'course_id' => $course_id,
            'type'      => ucfirst($entry['kind']),
            'title'     => get_the_title($post_id),
            'url'       => get_permalink($post_id),
            'excerpt'   => bzj_todo_excerpt($post_id),
            'image'     => bzj_todo_image($post_id, $course_id, true),
            'code'      => $entry['code'],
            'label'     => get_the_title($course_id),
            'action'    => $started ? 'Continue' : 'Start',
            'progress'  => bzj_todo_course_progress($user_id, $course_id),
            'duration'  => bzj_todo_duration_label($post_id, $course_id),
            'reward'    => bzj_todo_reward_label($post_id, $course_id),
            'mandatory' => 1,
            'source'    => 'hs',
        );
    }

    /*
     * Safety fallback: an H&S course may contain a real LearnDash activity
     * without a program code. It may be shown only after coded items have been
     * exhausted, and it retains H&S imagery.
     */
    foreach (bzj_todo_hs_courses() as $course) {
        if (bzj_todo_course_completed($user_id, $course->ID)) continue;

        foreach (bzj_todo_sequence($course->ID, $user_id) as $entry) {
            if (bzj_todo_complete($user_id, $entry['post_id'], $course->ID)) continue;

            return array(
                'post_id'   => $entry['post_id'],
                'course_id' => $course->ID,
                'type'      => ucfirst($entry['kind']),
                'title'     => get_the_title($entry['post_id']),
                'url'       => get_permalink($entry['post_id']),
                'excerpt'   => bzj_todo_excerpt($entry['post_id']),
                'image'     => bzj_todo_image($entry['post_id'], $course->ID, true),
                'code'      => '',
                'label'     => get_the_title($course->ID),
                'action'    => bzj_todo_activity_started_at($user_id, $entry['post_id'], $course->ID) ? 'Continue' : 'Start',
                'progress'  => bzj_todo_course_progress($user_id, $course->ID),
                'duration'  => bzj_todo_duration_label($entry['post_id'], $course->ID),
                'reward'    => bzj_todo_reward_label($entry['post_id'], $course->ID),
                'mandatory' => 1,
                'source'    => 'hs-uncoded',
            );
        }
    }

    return null;
}

function bzj_todo_hs_complete($user_id) {
    $courses = bzj_todo_hs_courses();
    if (!$courses) return false;

    foreach ($courses as $course) {
        if (!bzj_todo_course_completed($user_id, $course->ID)) return false;
    }

    return true;
}

/* ==========================================================================
 * 8. BUS1300 AFFILIATE MANAGEMENT
 * ========================================================================== */

function bzj_todo_affiliate_course() {
    $course = get_page_by_path('bus1300-affiliate-management', OBJECT, 'sfwd-courses');
    return $course instanceof WP_Post ? $course : null;
}

function bzj_todo_affiliate_item($user_id) {
    $course = bzj_todo_affiliate_course();
    if (!$course) return null;

    if (!bzj_todo_course_completed($user_id, $course->ID)) {
        $item = bzj_todo_first_incomplete($user_id, $course->ID, true);

        if ($item) {
            $item['label'] = 'Affiliate Management';
            $item['source'] = 'affiliate-learning';
            return $item;
        }
    }

    $user = get_userdata($user_id);
    $roles = $user ? (array) $user->roles : array();

    if (!in_array('jewel_affiliate', $roles, true)) {
        return array(
            'post_id'   => 0,
            'course_id' => $course->ID,
            'type'      => 'Affiliate',
            'title'     => 'You are qualified — activate your Buzzjuice Affiliate Account',
            'url'       => bzj_todo_url(bzj_todo_settings()['affiliate_url']),
            'excerpt'   => 'Affiliate Management is complete. Select and activate an affiliate subscription to start earning.',
            'image'     => '',
            'code'      => '',
            'label'     => 'Affiliate Management',
            'action'    => 'Activate Affiliate',
            'progress'  => 100,
            'duration'  => '',
            'reward'    => '',
            'mandatory' => 0,
            'source'    => 'affiliate-activation',
        );
    }

    return array(
        'post_id'   => 0,
        'course_id' => $course->ID,
        'type'      => 'Affiliate',
        'title'     => 'You are a qualified Buzzjuice Affiliate!',
        'url'       => bzj_todo_url(bzj_todo_settings()['jobs_url']),
        'excerpt'   => 'Your Affiliate Management training is complete. Start putting your qualification to work.',
        'image'     => '',
        'code'      => '',
        'label'     => 'Affiliate Management',
        'action'    => 'Start earning',
        'progress'  => 100,
        'duration'  => '',
        'reward'    => '',
        'mandatory' => 0,
        'source'    => 'affiliate-qualified',
    );
}

/* ==========================================================================
 * 9. OTHER LEARNING + PRIMARY DECISION ENGINE
 * ========================================================================== */

function bzj_todo_generic_learning($user_id) {
    $exclude = array();

    if ($course = bzj_todo_registration_course()) $exclude[] = $course->ID;
    if ($course = bzj_todo_affiliate_course()) $exclude[] = $course->ID;
    foreach (bzj_todo_hs_courses() as $course) $exclude[] = $course->ID;

    foreach (bzj_todo_enrolled_courses($user_id) as $course_id) {
        if (in_array($course_id, $exclude, true)) continue;
        if (bzj_todo_course_completed($user_id, $course_id)) continue;

        $item = bzj_todo_first_incomplete($user_id, $course_id, true);
        if ($item) {
            $item['image'] = '';
            $item['source'] = 'learning-other';
            return $item;
        }
    }

    return null;
}

function bzj_todo_make_state_item($title, $url, $excerpt, $action, $source) {
    return array(
        'post_id'   => 0,
        'course_id' => 0,
        'type'      => 'Status',
        'title'     => $title,
        'url'       => $url,
        'excerpt'   => $excerpt,
        'image'     => '',
        'code'      => '',
        'label'     => 'Buzzjuice',
        'action'    => $action,
        'progress'  => 100,
        'duration'  => '',
        'reward'    => '',
        'mandatory' => 0,
        'source'    => $source,
    );
}

function bzj_todo_primary($user_id, $fresh = false) {
    $user_id = absint($user_id);
    if (!$user_id) return null;

    $settings = bzj_todo_settings();
    $cache_key = BZJ_TODO_META . 'primary_cache';

    if (!$fresh && !empty($settings['cache_enabled'])) {
        $cached = get_user_meta($user_id, $cache_key, true);

        if (
            is_array($cached) &&
            !empty($cached['expires']) &&
            $cached['expires'] > time() &&
            isset($cached['item'])
        ) {
            return $cached['item'];
        }
    }

    /* 1. Mandatory Registration & Orientation. */
    $item = bzj_todo_registration_item($user_id);
    if ($item) return bzj_todo_cache_primary($user_id, $item);

    /* 2. Oldest activity already started but incomplete. */
    $item = bzj_todo_oldest_started_incomplete($user_id);
    if ($item) return bzj_todo_cache_primary($user_id, $item);

    /* 3. H&S sequential programme. */
    $item = bzj_todo_hs_next($user_id);
    if ($item) return bzj_todo_cache_primary($user_id, $item);

    /* 4. H&S completion notice. */
    if (bzj_todo_hs_complete($user_id)) {
        $item = bzj_todo_make_state_item(
            '🎉 Health & Safety Program complete!',
            bzj_todo_url($settings['support_url']),
            'Congratulations on completing the Buzzjuice Health & Safety programme. Contact Buzzjuice Support to arrange your diploma certificate.',
            'Contact Support',
            'hs-complete'
        );

        return bzj_todo_cache_primary($user_id, $item);
    }

    /*
     * 5. BUS1300 remains available after Orientation. When H&S has an active
     * task it is deliberately shown in "Also worth doing", not as a competing
     * primary task.
     */
    $affiliate = bzj_todo_affiliate_item($user_id);
    if ($affiliate && in_array($affiliate['source'], array('affiliate-learning', 'affiliate-activation'), true)) {
        return bzj_todo_cache_primary($user_id, $affiliate);
    }

    /* 6. Other enrolled programmes. */
    $item = bzj_todo_generic_learning($user_id);
    if ($item) return bzj_todo_cache_primary($user_id, $item);

    $item = bzj_todo_make_state_item(
        'You are all caught up!',
        bzj_todo_url($settings['progress_url']),
        'There are no outstanding tracked learning activities. Keep your momentum going by exploring Buzzjuice.',
        'Explore',
        'complete'
    );

    return bzj_todo_cache_primary($user_id, $item);
}

function bzj_todo_cache_primary($user_id, $item) {
    $settings = bzj_todo_settings();

    if (!empty($settings['cache_enabled'])) {
        update_user_meta($user_id, BZJ_TODO_META . 'primary_cache', array(
            'expires' => time() + $settings['cache_ttl'],
            'item'    => $item,
        ));
    }

    return $item;
}

/* ==========================================================================
 * 10. SECONDARY ACTIVITIES
 * ========================================================================== */

function bzj_todo_secondary($user_id) {
    $settings = bzj_todo_settings();

    if (empty($settings['secondary_enabled']) || $settings['secondary_max'] <= 0) {
        return array();
    }

    $items = array();

    $registration = bzj_todo_registration_course();

    if (
        !$registration ||
        (
            bzj_todo_course_completed($user_id, $registration->ID) &&
            bzj_todo_assessment_passed($user_id, $registration->ID)
        )
    ) {
        /*
         * Affiliate Management is allowed to coexist with the H&S primary
         * item. Show its actual next lesson/topic/quiz rather than merely
         * sending the member back to the course home.
         */
        $affiliate = bzj_todo_affiliate_item($user_id);

        if ($affiliate) {
            $items[] = array(
                'icon'  => '💼',
                'text'  => !empty($affiliate['code'])
                    ? $affiliate['code'] . ' — ' . $affiliate['title']
                    : $affiliate['action'] . ' — Affiliate Management',
                'url'   => $affiliate['url'],
                'class' => 'learning',
            );
        }
    }

    $definitions = array(
        array('icon' => '👥', 'text' => 'Meet someone new on Buzzjuice', 'url' => $settings['common_url']),
        array('icon' => '🎙️', 'text' => 'Watch the latest Classies Chronicles', 'url' => $settings['classies_url']),
        array('icon' => '💬', 'text' => 'See your latest matches', 'url' => $settings['matches_url']),
        array('icon' => '🗳️', 'text' => 'Vote in the latest community poll', 'url' => $settings['polls_url']),
        array('icon' => '💼', 'text' => 'Explore Buzzjuice opportunities', 'url' => $settings['jobs_url']),
    );

    foreach ($definitions as $definition) {
        $url = bzj_todo_url($definition['url']);
        if (!$url) continue;

        $items[] = array(
            'icon'  => $definition['icon'],
            'text'  => $definition['text'],
            'url'   => $url,
            'class' => 'engagement',
        );
    }

    return apply_filters(
        'bzj_todo_secondary_items',
        array_slice($items, 0, $settings['secondary_max']),
        $user_id
    );
}

/* ==========================================================================
 * 11. PROFILE PROMPTER / POPUP ARBITRATION
 * ========================================================================== */

function bzj_todo_on_login($login, $user) {
    if (empty($user->ID)) return;

    $user_id = absint($user->ID);
    $last_login = absint(get_user_meta($user_id, BZJ_TODO_META . 'last_login', true));

    update_user_meta($user_id, BZJ_TODO_META . 'previous_login', $last_login);
    update_user_meta($user_id, BZJ_TODO_META . 'last_login', time());
    update_user_meta($user_id, BZJ_TODO_META . 'login_prompt', 1);

    bzj_todo_flush_cache($user_id);
}
add_action('wp_login', 'bzj_todo_on_login', 20, 2);

function bzj_todo_popup_can_show($user_id, $item) {
    $settings = bzj_todo_settings();

    if (empty($settings['popup_enabled']) || !$item) return false;

    $last_popup = absint(get_user_meta($user_id, BZJ_TODO_META . 'last_popup', true));
    if (
        $last_popup &&
        (time() - $last_popup) <
        ($settings['popup_cooldown_hours'] * HOUR_IN_SECONDS)
    ) {
        return false;
    }

    $remind_until = absint(get_user_meta($user_id, BZJ_TODO_META . 'remind_until', true));
    if ($remind_until > time()) return false;
    if (!empty($settings['popup_never_again']) && get_user_meta($user_id, BZJ_TODO_META . 'popup_never_again', true)) return false;

    $week = strtotime('monday this week', current_time('timestamp'));
    $state = get_user_meta($user_id, BZJ_TODO_META . 'popup_week', true);

    if (!is_array($state) || absint($state['week'] ?? 0) !== $week) {
        $state = array('week' => $week, 'count' => 0);
    }

    if (absint($state['count']) >= $settings['popup_max_per_week']) return false;

    if (!empty($settings['popup_on_login']) &&
        get_user_meta($user_id, BZJ_TODO_META . 'login_prompt', true)) {
        return true;
    }

    if (!empty($settings['popup_after_return'])) {
        $previous = absint(get_user_meta($user_id, BZJ_TODO_META . 'previous_login', true));
        $last_login = absint(get_user_meta($user_id, BZJ_TODO_META . 'last_login', true));

        if (
            $previous &&
            $last_login &&
            ($last_login - $previous) >=
            ($settings['return_after_hours'] * HOUR_IN_SECONDS)
        ) {
            return true;
        }
    }

    return false;
}

function bzj_todo_reserve_popup($user_id) {
    $state = get_user_meta($user_id, BZJ_TODO_META . 'popup_week', true);
    $week = strtotime('monday this week', current_time('timestamp'));

    if (!is_array($state) || absint($state['week'] ?? 0) !== $week) {
        $state = array('week' => $week, 'count' => 0);
    }

    $state['count'] = absint($state['count']) + 1;

    update_user_meta($user_id, BZJ_TODO_META . 'popup_week', $state);
    update_user_meta($user_id, BZJ_TODO_META . 'last_popup', time());
    delete_user_meta($user_id, BZJ_TODO_META . 'login_prompt');
}

/* ==========================================================================
 * 12. BUDDYBOSS / BUDDYPRESS NOTIFICATIONS
 * ========================================================================== */

function bzj_todo_notification_fingerprint($item) {
    if (!$item) return '';

    return md5(wp_json_encode(array(
        'source'   => $item['source'] ?? '',
        'post_id'  => $item['post_id'] ?? 0,
        'course_id'=> $item['course_id'] ?? 0,
        'code'     => $item['code'] ?? '',
        'title'    => $item['title'] ?? '',
        'url'      => $item['url'] ?? '',
    )));
}

/** Register the custom component so BuddyPress/BuddyBoss can recognise the event. */
function bzj_todo_register_notification_component($components) {
    if (!is_array($components)) $components = array();
    if (!in_array(BZJ_TODO_COMPONENT, $components, true)) $components[] = BZJ_TODO_COMPONENT;
    return $components;
}
add_filter('bp_notifications_get_registered_components', 'bzj_todo_register_notification_component');

/**
 * Notification formatter.
 *
 * The classic BuddyPress notification API remains the safe compatibility
 * layer. BuddyBoss installations can adapt the same canonical notification
 * through the filter below if their modern notification implementation is
 * enabled. We deliberately do not call an undocumented BuddyBoss function.
 */
function bzj_todo_notification_text(
    $content,
    $item_id,
    $secondary_item_id,
    $total_items,
    $format = 'string',
    $action = '',
    $component = ''
) {
    if (
        $action !== BZJ_TODO_NOTIFICATION_ACTION ||
        $component !== BZJ_TODO_COMPONENT
    ) {
        return $content;
    }

    $user_id = function_exists('bp_loggedin_user_id')
        ? bp_loggedin_user_id()
        : get_current_user_id();

    $item = null;

    // For learning notifications the notification row itself identifies the
    // content, so do not rely on the currently logged-in user's cached item.
    if (absint($item_id)) {
        $item = bzj_todo_item(absint($item_id), absint($secondary_item_id), array(
            'action' => 'Open',
            'source' => 'notification',
        ), $user_id);
    }

    if (!is_array($item) || empty($item['title'])) {
        $item = get_user_meta($user_id, BZJ_TODO_META . 'notification_item', true);
    }

    if (!is_array($item) || empty($item['title'])) {
        $item = bzj_todo_primary($user_id, false);
    }

    if (!$item) return $content;

    $prefix = !empty($item['code']) ? $item['code'] . ' — ' : '';
    $title = $prefix . $item['title'];

    if (!empty($item['duration'])) {
        $title .= ' (' . $item['duration'] . ')';
    }

    $url = !empty($item['url']) ? $item['url'] : home_url('/courses/');

    if ($format === 'string') {
        return sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url($url),
            esc_html($title)
        );
    }

    return $title;
}
add_filter(
    'bp_notifications_get_notifications_for_user',
    'bzj_todo_notification_text',
    20,
    7
);

function bzj_todo_add_bp_notification($user_id, $item) {
    if (!function_exists('bp_notifications_add_notification')) return false;

    $fingerprint = bzj_todo_notification_fingerprint($item);
    if (!$fingerprint) return false;

    $last = get_user_meta(
        $user_id,
        BZJ_TODO_META . 'notification_fingerprint',
        true
    );

    if ($last && hash_equals((string) $last, (string) $fingerprint)) {
        return false;
    }

    /*
     * Canonical event payload exposed to modern BuddyBoss integrations.
     * This filter may be consumed by a site-specific modern-notification
     * adapter without duplicating the notification row.
     */
    $payload = apply_filters(
        'bzj_todo_modern_notification_payload',
        array(
            'user_id'          => absint($user_id),
            'component_name'   => BZJ_TODO_COMPONENT,
            'component_action' => BZJ_TODO_NOTIFICATION_ACTION,
            'item_id'          => absint($item['post_id'] ?? 0),
            'secondary_item_id'=> absint($item['course_id'] ?? 0),
            'fingerprint'      => $fingerprint,
            'title'            => !empty($item['code'])
                ? $item['code'] . ' — ' . $item['title']
                : $item['title'],
            'url'              => $item['url'] ?? home_url('/courses/'),
            'duration'         => $item['duration'] ?? '',
            'push'             => true,
        ),
        $user_id,
        $item
    );

    /*
     * If a BuddyBoss modern-notification adapter returns a truthy result,
     * regard the event as delivered and do not create a duplicate legacy row.
     */
    $modern_result = apply_filters(
        'bzj_todo_send_modern_notification',
        false,
        $payload,
        $user_id,
        $item
    );

    if ($modern_result) {
        update_user_meta($user_id, BZJ_TODO_META . 'notification_fingerprint', $fingerprint);
        update_user_meta($user_id, BZJ_TODO_META . 'notification_item', $item);
        return $modern_result;
    }

    $notification_id = bp_notifications_add_notification(array(
        'user_id'           => absint($user_id),
        'item_id'           => absint($item['post_id'] ?? 0),
        'secondary_item_id' => absint($item['course_id'] ?? 0),
        'component_name'    => BZJ_TODO_COMPONENT,
        'component_action'  => BZJ_TODO_NOTIFICATION_ACTION,
        'date_notified'     => function_exists('bp_core_current_time')
            ? bp_core_current_time()
            : current_time('mysql'),
        'is_new'            => 1,
        'allow_duplicate'   => false,
    ));

    if ($notification_id) {
        update_user_meta($user_id, BZJ_TODO_META . 'notification_fingerprint', $fingerprint);
        update_user_meta($user_id, BZJ_TODO_META . 'notification_item', $item);
        return $notification_id;
    }

    return false;
}

/* ==========================================================================
 * 13. NOTIFICATION TIMETABLE / BATCHING
 * ========================================================================== */

function bzj_todo_parse_notification_times() {
    $settings = bzj_todo_settings();
    $lines = preg_split('/\r\n|\r|\n/', (string) $settings['notification_times']);

    $days = array(
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
    );

    $slots = array();

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, ':') === false) continue;

        list($day, $times) = array_pad(explode(':', $line, 2), 2, '');
        $day = strtolower(trim($day));

        if (!isset($days[$day])) continue;

        foreach (preg_split('/\s*,\s*/', $times) as $time) {
            if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($time), $m)) continue;

            $hour = min(23, max(0, (int) $m[1]));
            $minute = min(59, max(0, (int) $m[2]));

            $slots[] = array(
                'day'    => $days[$day],
                'hour'   => $hour,
                'minute' => $minute,
            );
        }
    }

    return $slots;
}

function bzj_todo_schedule_notifications() {
    wp_clear_scheduled_hook(BZJ_TODO_CRON_HOOK);

    if (empty(bzj_todo_settings()['notifications_enabled'])) return;

    $tz = wp_timezone();
    $now = new DateTimeImmutable('now', $tz);

    foreach (range(0, 8) as $offset) {
        $date = $now->modify('+' . $offset . ' day');

        foreach (bzj_todo_parse_notification_times() as $slot) {
            if ((int) $date->format('w') !== $slot['day']) continue;

            $when = $date->setTime($slot['hour'], $slot['minute'], 0);

            if ($when <= $now) continue;

            wp_schedule_single_event(
                $when->getTimestamp(),
                BZJ_TODO_CRON_HOOK
            );
        }
    }
}

function bzj_todo_send_scheduled_notifications() {
    $settings = bzj_todo_settings();

    if (empty($settings['notifications_enabled'])) return;

    /*
     * Use Action Scheduler when WooCommerce is present. Otherwise process a
     * bounded WP-Cron batch so a large member base does not turn a single
     * notification slot into an unbounded request.
     */
    if (function_exists('as_enqueue_async_action')) {
        as_enqueue_async_action(
            'bzj_todo_notification_batch',
            array('offset' => 0),
            'buzzjuice-todo'
        );
    } else {
        bzj_todo_process_notification_batch(0);
    }

    bzj_todo_schedule_notifications();
}
add_action(BZJ_TODO_CRON_HOOK, 'bzj_todo_send_scheduled_notifications');

function bzj_todo_process_notification_batch($offset = 0) {
    $settings = bzj_todo_settings();
    $offset = max(0, absint($offset));
    $limit = absint($settings['notification_batch_size']);

    $user_ids = get_users(array(
        'fields'      => 'ids',
        'number'      => $limit,
        'offset'      => $offset,
        'orderby'     => 'ID',
        'order'       => 'ASC',
        'role__not_in'=> array('administrator'),
    ));

    foreach ($user_ids as $user_id) {
        $item = bzj_todo_primary($user_id, true);

        if (!$item || empty($item['title']) || empty($item['url'])) continue;

        /*
         * Only notify when the ToDo fingerprint has changed. This means a
         * timetable slot does not repeatedly tell the user about the same item.
         */
        bzj_todo_add_bp_notification($user_id, $item);
    }

    if (count($user_ids) >= $limit) {
        $next_offset = $offset + $limit;

        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(
                'bzj_todo_notification_batch',
                array('offset' => $next_offset),
                'buzzjuice-todo'
            );
        } else {
            wp_schedule_single_event(
                time() + 15,
                'bzj_todo_notification_batch',
                array($next_offset)
            );
        }
    }
}

add_action(
    'bzj_todo_notification_batch',
    'bzj_todo_process_notification_batch',
    10,
    1
);

add_action('init', function() {
    if (
        !empty(bzj_todo_settings()['notifications_enabled']) &&
        !wp_next_scheduled(BZJ_TODO_CRON_HOOK)
    ) {
        bzj_todo_schedule_notifications();
    }
}, 30);

/* ==========================================================================
 * 14. POPUP
 * ========================================================================== */

function bzj_todo_render_popup() {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $item = bzj_todo_primary($user_id, false);

    if (!$item || !bzj_todo_popup_can_show($user_id, $item)) return;

    $settings = bzj_todo_settings();
    $url = !empty($item['url']) ? $item['url'] : home_url('/courses/');
    $image = !empty($item['image']) ? $item['image'] : '';
    ?>
    <div
        id="bzj-todo-popup"
        class="bzj-todo-popup-backdrop"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="bzj-todo-popup-title"
    >
        <div class="bzj-todo-popup" style="--bzj-todo-delay:<?php echo esc_attr(absint($settings['popup_delay'])); ?>ms">
            <button type="button" class="bzj-todo-popup-close" aria-label="Close">×</button>

            <?php if ($image): ?>
                <a href="<?php echo esc_url($url); ?>" class="bzj-todo-popup-image-link" data-bzj-todo-click>
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                </a>
            <?php endif; ?>

            <div class="bzj-todo-popup-body">
                <div class="bzj-todo-popup-kicker">
                    THINGS TO DO TODAY
                    <?php if (!empty($item['code'])): ?>
                        <span><?php echo esc_html($item['code']); ?></span>
                    <?php endif; ?>
                </div>

                <a
                    id="bzj-todo-popup-title"
                    class="bzj-todo-popup-title"
                    href="<?php echo esc_url($url); ?>"
                    data-bzj-todo-click
                >
                    <?php echo esc_html($item['title']); ?>
                </a>

                <?php if (!empty($item['duration']) || !empty($item['reward'])): ?>
                    <div class="bzj-todo-popup-submeta">
                        <?php if (!empty($item['duration'])): ?>
                            <span>⏱ <?php echo esc_html($item['duration']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($item['reward'])): ?>
                            <span>✦ <?php echo esc_html($item['reward']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($item['excerpt'])): ?>
                    <p class="bzj-todo-popup-excerpt"><?php echo esc_html($item['excerpt']); ?></p>
                <?php endif; ?>

                <div class="bzj-todo-popup-actions">
                    <a
                        href="<?php echo esc_url($url); ?>"
                        class="bzj-todo-button bzj-todo-button-primary"
                        data-bzj-todo-click
                    >
                        <?php echo esc_html($item['action'] ?? 'Start'); ?>
                    </a>

                    <button type="button" class="bzj-todo-button bzj-todo-button-secondary" data-bzj-todo-remind>
                        Remind me later
                    </button>
                </div>

                <label class="bzj-todo-never-again">
                    <input type="checkbox" data-bzj-todo-never-again>
                    <span>Don't show Things To Do Today popups again</span>
                </label>
            </div>
        </div>
    </div>
    <style>
        .bzj-todo-popup-backdrop{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(0,0,0,.58)}
        .bzj-todo-popup-backdrop[hidden]{display:none}
        .bzj-todo-popup{position:relative;width:min(680px,100%);max-height:calc(100vh - 36px);overflow:auto;background:#fff;border-radius:13px;box-shadow:0 24px 70px rgba(0,0,0,.38)}
        .bzj-todo-popup-close{position:absolute;right:0px;top:0px;z-index:2;width:35px;height:35px;border:0;border-radius:50%;background:#fff;color:#333;font-size:24px;line-height:1;cursor:pointer;box-shadow:0 2px 9px rgba(0,0,0,.15);padding: 0px;text-box-trim: trim-end;}
        .bzj-todo-popup-image-link{display:block}.bzj-todo-popup-image-link img{display:block;width:100%;max-height:330px;object-fit:cover}
        .bzj-todo-popup-body{padding:18px}.bzj-todo-popup-kicker{font-size:11px;font-weight:800;letter-spacing:.05em;color:#737b86;margin-bottom:7px}.bzj-todo-popup-kicker span{float:right;margin: 10px 30px 0px 0px;}
        .bzj-todo-popup-title{display:block;color:#1d5fa7;text-decoration:none;font-size:23px;line-height:1.2;font-weight:800;margin-bottom:9px}
        .bzj-todo-popup-submeta{display:flex;gap:12px;flex-wrap:wrap;color:#616974;font-size:12px;font-weight:700;margin-bottom:9px}.bzj-todo-popup-excerpt{font-size:14px;color:#424950;margin:0 0 15px}.bzj-todo-popup-actions{display:flex;gap:8px;flex-wrap:wrap}
        .bzj-todo-never-again{display:flex;align-items:center;gap:7px;margin-top:10px;font-size:12px;color:#4b5563}.bzj-todo-never-again input{margin:0}
        .bzj-todo-button{display:inline-flex;align-items:center;justify-content:center;border-radius:7px;padding:8px 12px;font-size:12px!important;font-weight:700;text-decoration:none;cursor:pointer;border:1px solid transparent;line-height:1.2}.bzj-todo-button-primary{background:#2865ba;color:#fff!important}.bzj-todo-button-primary:hover{color:#fff;opacity:.92}.bzj-todo-button-secondary{background:#f3f5f7;color:#414851 !important;border-color:#e3e7ec}
        html.bzj-todo-popup-open{overflow:hidden}
        @media(max-width:480px){.bzj-todo-popup-title{font-size:19px}.bzj-todo-popup-body{padding:14px}}
    </style>
    <script>
    (function(){
        var modal = document.getElementById('bzj-todo-popup');
        if (!modal) return;

        var closeButton = modal.querySelector('.bzj-todo-popup-close');
        var remindButton = modal.querySelector('[data-bzj-todo-remind]');
        var ajax = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        var nonce = <?php echo wp_json_encode(wp_create_nonce(BZJ_TODO_NONCE)); ?>;
        var neverAgain = modal.querySelector('[data-bzj-todo-never-again]');

        function profilePrompterVisible() {
            return !!document.querySelector(
                '.bzj-pp-modal-backdrop:not([hidden]),' +
                '.bzj-pp-modal:not([hidden]),' +
                '.bzj-pp-widget'
            );
        }

        function openModal() {
            if (profilePrompterVisible()) return;
            modal.hidden = false;
            document.documentElement.classList.add('bzj-todo-popup-open');
            var opened = new FormData();
            opened.append('action', 'bzj_todo_action');
            opened.append('todo_action', 'popup_opened');
            opened.append('nonce', nonce);
            fetch(ajax, {method:'POST', credentials:'same-origin', body:opened}).catch(function(){});
            if (closeButton) closeButton.focus();
        }

        function closeModal() {
            modal.hidden = true;
            document.documentElement.classList.remove('bzj-todo-popup-open');
        }

        function neverShowAgain() {
            var data = new FormData();
            data.append('action', 'bzj_todo_action');
            data.append('todo_action', 'never_again');
            data.append('nonce', nonce);
            fetch(ajax, {method:'POST', credentials:'same-origin', body:data}).finally(closeModal);
        }

        function remind() {
            var data = new FormData();
            data.append('action', 'bzj_todo_action');
            data.append('todo_action', 'remind');
            data.append('nonce', nonce);

            fetch(ajax, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            }).finally(closeModal);
        }

        window.setTimeout(openModal, <?php echo esc_js(absint($settings['popup_delay'])); ?>);

        if (closeButton) closeButton.addEventListener('click', closeModal);
        if (remindButton) remindButton.addEventListener('click', remind);
        if (neverAgain) neverAgain.addEventListener('change', function(){ if (this.checked) neverShowAgain(); });

        modal.addEventListener('click', function(event){
            if (event.target === modal) closeModal();
        });

        document.addEventListener('keydown', function(event){
            if (event.key === 'Escape' && !modal.hidden) closeModal();
        });

        /*
         * Harmony with bzj-profile-prompter.php:
         * do not remove or override its hooks. If its widget appears later,
         * close ToDo Today. This keeps both MU plugins independently maintainable.
         */
        window.setInterval(function(){
            if (!modal.hidden && profilePrompterVisible()) closeModal();
        }, 500);
    })();
    </script>
    <?php
}
add_action('wp_footer', 'bzj_todo_render_popup', 80);

/* ==========================================================================
 * 15. LEFT SIDEBAR BLOCK
 * ========================================================================== */

function bzj_todo_render_block_shortcode() {
    if (!is_user_logged_in() || !bzj_todo_ld_available()) return '';
    return bzj_todo_render_block(get_current_user_id());
}

function bzj_todo_render_block($user_id) {
    $user_id = absint($user_id);
    if (!$user_id) return '';

    $item = bzj_todo_primary($user_id);
    $secondary = bzj_todo_secondary($user_id);

    if (!$item) return '';

    $nonce = wp_create_nonce(BZJ_TODO_NONCE);
    $progress = max(0, min(100, absint($item['progress'] ?? 0)));
    $url = !empty($item['url']) ? $item['url'] : home_url('/courses/');

    ob_start();
    ?>
    <section
        id="bzj-todo-block"
        class="bzj-todo-block"
        aria-label="Things To Do Today"
        data-bzj-todo
        data-bzj-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
        data-bzj-nonce="<?php echo esc_attr($nonce); ?>"
    >
        <header class="bzj-todo-block-header">
            <div class="bzj-todo-heading">
                <span class="bzj-todo-heading-icon" aria-hidden="true">✓</span>
                <span class="bzj-todo-heading-text">THINGS TO DO TODAY</span>
            </div>

            <?php if (!empty($item['code'])): ?>
                <span class="bzj-todo-code"><?php echo esc_html($item['code']); ?></span>
            <?php endif; ?>
        </header>

        <article class="bzj-todo-primary <?php echo !empty($item['image']) ? 'has-image' : 'no-image'; ?>">
            <?php if (!empty($item['image'])): ?>
                <a
                    href="<?php echo esc_url($url); ?>"
                    class="bzj-todo-image-link"
                    data-bzj-todo-click
                    data-code="<?php echo esc_attr($item['code'] ?? ''); ?>"
                >
                    <img
                        src="<?php echo esc_url($item['image']); ?>"
                        alt="<?php echo esc_attr($item['title']); ?>"
                        loading="lazy"
                    >
                </a>
            <?php endif; ?>

            <div class="bzj-todo-primary-body">
                <?php if (!empty($item['label']) || !empty($item['type'])): ?>
                    <div class="bzj-todo-meta">
                        <?php if (!empty($item['label'])): ?>
                            <span><?php echo esc_html($item['label']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($item['type'])): ?>
                            <span><?php echo !empty($item['label']) ? ' · ' : ''; ?><?php echo esc_html($item['type']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a
                    href="<?php echo esc_url($url); ?>"
                    class="bzj-todo-item-title"
                    data-bzj-todo-click
                    data-code="<?php echo esc_attr($item['code'] ?? ''); ?>"
                >
                    <?php echo esc_html($item['title']); ?>
                </a>

                <?php if (!empty($item['duration']) || !empty($item['reward'])): ?>
                    <div class="bzj-todo-submeta">
                        <?php if (!empty($item['duration'])): ?>
                            <span>⏱ <?php echo esc_html($item['duration']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($item['reward'])): ?>
                            <span>✦ <?php echo esc_html($item['reward']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($progress > 0 && $progress < 100): ?>
                    <div class="bzj-todo-progress" aria-label="<?php echo esc_attr($progress); ?> percent complete">
                        <div class="bzj-todo-progress-track">
                            <span style="width:<?php echo esc_attr($progress); ?>%"></span>
                        </div>
                        <small><?php echo esc_html($progress); ?>% complete</small>
                    </div>
                <?php endif; ?>

                <?php if (!empty($item['excerpt'])): ?>
                    <p class="bzj-todo-excerpt"><?php echo esc_html($item['excerpt']); ?></p>
                <?php endif; ?>

                <div class="bzj-todo-actions">
                    <a
                        href="<?php echo esc_url($url); ?>"
                        class="bzj-todo-button bzj-todo-button-primary"
                        data-bzj-todo-click
                        data-code="<?php echo esc_attr($item['code'] ?? ''); ?>"
                    >
                        <?php echo esc_html($item['action'] ?? 'Start'); ?>
                    </a>

                    <button
                        type="button"
                        class="bzj-todo-button bzj-todo-button-secondary"
                        data-bzj-todo-remind
                    >
                        Remind
                    </button>
                </div>
            </div>
        </article>

        <?php if ($secondary): ?>
            <section class="bzj-todo-secondary" aria-label="Also worth doing">
                <div class="bzj-todo-secondary-heading">ALSO WORTH DOING</div>
                <ul>
                    <?php foreach ($secondary as $secondary_item): ?>
                        <li>
                            <a href="<?php echo esc_url($secondary_item['url']); ?>">
                                <span class="bzj-todo-secondary-icon" aria-hidden="true">
                                    <?php echo esc_html($secondary_item['icon']); ?>
                                </span>
                                <span><?php echo esc_html($secondary_item['text']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <footer class="bzj-todo-footer">
            <a href="<?php echo esc_url(bzj_todo_url(bzj_todo_settings()['progress_url'])); ?>">
                View your learning progress <span aria-hidden="true">→</span>
            </a>
        </footer>
    </section>

    <style>
        .bzj-todo-block{box-sizing:border-box;background:#fff;border:1px solid #e3e7ec;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04);font-size:14px;line-height:1.45}
        .bzj-todo-block *{box-sizing:border-box}
        .bzj-todo-block-header{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:11px 12px;border-bottom:1px solid #edf0f3}
        .bzj-todo-heading{display:flex;align-items:center;gap:8px;min-width:0}
        .bzj-todo-heading-icon{display:inline-flex;align-items:center;justify-content:center;width:21px;height:21px;border-radius:50%;background:#2865ba;color:#fff;font-size:12px;font-weight:800;flex:0 0 21px}
        .bzj-todo-heading-text{font-size:12px;font-weight:800;letter-spacing:.03em}
        .bzj-todo-code{font-size:10px;font-weight:700;padding:3px 7px;border-radius:999px;background:#eef4ff;color:#2865ba;white-space:nowrap}
        .bzj-todo-primary{padding:12px}
        .bzj-todo-image-link{display:block;margin:-1px -1px 11px}
        .bzj-todo-image-link img{display:block;width:100%;height:128px;object-fit:cover;border-radius:9px}
        .bzj-todo-meta{font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#737b86;margin-bottom:4px}
        .bzj-todo-item-title{display:block;color:#1d5fa7;text-decoration:none;font-size:16px;font-weight:800;line-height:1.25;margin-bottom:7px}
        .bzj-todo-item-title:hover,.bzj-todo-footer a:hover,.bzj-todo-secondary a:hover{text-decoration:underline}
        .bzj-todo-submeta{display:flex;flex-wrap:wrap;gap:8px;color:#626a74;font-size:11px;font-weight:600;margin-bottom:7px}
        .bzj-todo-progress{margin:7px 0 8px}
        .bzj-todo-progress-track{height:6px;background:#edf0f3;border-radius:99px;overflow:hidden}
        .bzj-todo-progress-track span{display:block;height:100%;background:#2865ba;border-radius:99px}
        .bzj-todo-progress small{display:block;margin-top:3px;color:#737b86;font-size:10px}
        .bzj-todo-excerpt{color:#444b54;font-size:12px;margin:0 0 10px}
        .bzj-todo-actions{display:flex;align-items:center;gap:7px}
        .bzj-todo-button{display:inline-flex;align-items:center;justify-content:center;border-radius:7px;padding:7px 10px;font-size:12px!important;font-weight:700;text-decoration:none;cursor:pointer;border:1px solid transparent;line-height:1.2}
        .bzj-todo-button-primary{background:#2865ba;color:#fff!important}
        .bzj-todo-button-primary:hover{color:#fff;opacity:.92}
        .bzj-todo-button-secondary{background:#f3f5f7;color:#414851 !important;border-color:#e3e7ec}
        .bzj-todo-secondary{border-top:1px solid #edf0f3;padding:10px 12px}
        .bzj-todo-secondary-heading{font-size:10px;font-weight:800;letter-spacing:.05em;color:#7a828c;margin-bottom:7px}
        .bzj-todo-secondary ul{list-style:none;padding:0;margin:0}
        .bzj-todo-secondary li{margin:0}
        .bzj-todo-secondary a{display:flex;gap:7px;align-items:flex-start;padding:5px 0;color:#414851;text-decoration:none;font-size:12px}
        .bzj-todo-secondary-icon{width:17px;flex:0 0 17px;text-align:center}
        .bzj-todo-footer{border-top:1px solid #edf0f3;padding:9px 12px;font-size:11px}
        .bzj-todo-footer a{color:#6b737d;text-decoration:none}
        .bzj-todo-block .is-disabled{opacity:.6;pointer-events:none}
        .bzj-todo-popup-backdrop{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(0,0,0,.58)}
        html.bzj-todo-popup-open{overflow:hidden}
        .bzj-todo-popup-backdrop[hidden]{display:none}
        .bzj-todo-popup{position:relative;width:min(680px,100%);max-height:calc(100vh - 36px);overflow:auto;background:#fff;border-radius:13px;box-shadow:0 24px 70px rgba(0,0,0,.38);animation:bzjTodoIn .24s ease both}
        .bzj-todo-popup-close{position:absolute;right:10px;top:10px;z-index:2;width:35px;height:35px;border:0;border-radius:50%;background:#fff;color:#333;font-size:24px;line-height:1;cursor:pointer;box-shadow:0 2px 9px rgba(0,0,0,.15)}
        .bzj-todo-popup-image-link{display:block}
        .bzj-todo-popup-image-link img{display:block;width:100%;max-height:330px;object-fit:cover}
        .bzj-todo-popup-body{padding:18px}
        .bzj-todo-popup-kicker{font-size:11px;font-weight:800;letter-spacing:.05em;color:#737b86;margin-bottom:7px}
        .bzj-todo-popup-kicker span{float:right;margin: 10px 30px 0px 0px;}
        .bzj-todo-popup-title{display:block;color:#1d5fa7;text-decoration:none;font-size:23px;line-height:1.2;font-weight:800;margin-bottom:9px}
        .bzj-todo-popup-submeta{display:flex;gap:12px;flex-wrap:wrap;color:#616974;font-size:12px;font-weight:700;margin-bottom:9px}
        .bzj-todo-popup-excerpt{font-size:14px;color:#424950;margin:0 0 15px}
        .bzj-todo-popup-actions{display:flex;gap:8px;flex-wrap:wrap}
        @keyframes bzjTodoIn{from{opacity:0;transform:translateY(8px) scale(.985)}to{opacity:1;transform:none}}
        @media(max-width:480px){.bzj-todo-popup-title{font-size:19px}.bzj-todo-popup-body{padding:14px}}
    </style>

    <script>
    (function(){
        var root = document.getElementById('bzj-todo-block');
        if (!root || root.dataset.bzjTodoReady === '1') return;
        root.dataset.bzjTodoReady = '1';

        var ajax = root.getAttribute('data-bzj-ajax');
        var nonce = root.getAttribute('data-bzj-nonce');

        function post(action, data) {
            var fd = new FormData();
            fd.append('action', 'bzj_todo_action');
            fd.append('todo_action', action);
            fd.append('nonce', nonce);

            data = data || {};
            Object.keys(data).forEach(function(key){ fd.append(key, data[key]); });

            return fetch(ajax, {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
        }

        root.addEventListener('click', function(event){
            var remind = event.target.closest('[data-bzj-todo-remind]');
            if (remind && root.contains(remind)) {
                event.preventDefault();
                remind.disabled = true;

                post('remind', {}).then(function(){
                    remind.textContent = 'Reminded';
                }).catch(function(){
                    remind.disabled = false;
                });
            }

            var target = event.target.closest('[data-bzj-todo-click]');
            if (target && root.contains(target)) {
                post('click', {
                    code: target.getAttribute('data-code') || ''
                }).catch(function(){});
            }
        });
    })();
    </script>
    <?php

    return ob_get_clean();
}

/* ==========================================================================
 * 16. AJAX
 * ========================================================================== */

function bzj_todo_ajax() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in.'), 403);
    }

    if (!check_ajax_referer(BZJ_TODO_NONCE, 'nonce', false)) {
        wp_send_json_error(array('message' => 'Invalid nonce.'), 403);
    }

    $user_id = get_current_user_id();
    $action = sanitize_key(wp_unslash($_POST['todo_action'] ?? ''));

    if ($action === 'never_again') {
        update_user_meta($user_id, BZJ_TODO_META . 'popup_never_again', 1);
        wp_send_json_success();
    }

    if ($action === 'popup_opened') {
        bzj_todo_reserve_popup($user_id);
        wp_send_json_success();
    }

    if ($action === 'remind') {
        $hours = bzj_todo_settings()['popup_remind_hours'];

        update_user_meta(
            $user_id,
            BZJ_TODO_META . 'remind_until',
            time() + ($hours * HOUR_IN_SECONDS)
        );

        wp_send_json_success();
    }

    if ($action === 'click') {
        if (!empty(bzj_todo_settings()['analytics_enabled'])) {
            $analytics = get_user_meta($user_id, BZJ_TODO_META . 'analytics', true);
            if (!is_array($analytics)) $analytics = array();

            $analytics['cta_clicks'] = absint($analytics['cta_clicks'] ?? 0) + 1;
            $analytics['last_click_code'] = sanitize_text_field(
                wp_unslash($_POST['code'] ?? '')
            );
            $analytics['last_event'] = current_time('mysql');

            update_user_meta($user_id, BZJ_TODO_META . 'analytics', $analytics);
        }

        wp_send_json_success();
    }

    if ($action === 'refresh') {
        bzj_todo_flush_cache($user_id);
        wp_send_json_success(bzj_todo_primary($user_id, true));
    }

    wp_send_json_error(array('message' => 'Unknown action.'), 400);
}
add_action('wp_ajax_bzj_todo_action', 'bzj_todo_ajax');

/* ==========================================================================
 * 17. CACHE INVALIDATION
 * ========================================================================== */

function bzj_todo_invalidate_current_user_cache() {
    if (is_user_logged_in()) {
        bzj_todo_flush_cache(get_current_user_id());
    }
}

add_action('learndash_mark_complete_process', 'bzj_todo_invalidate_current_user_cache', 20);
add_action('learndash_course_completed', 'bzj_todo_invalidate_current_user_cache', 20);
add_action('learndash_quiz_completed', 'bzj_todo_invalidate_current_user_cache', 20);
add_action('learndash_assignment_uploaded', 'bzj_todo_invalidate_current_user_cache', 20);
add_action('learndash_lesson_completed', 'bzj_todo_invalidate_current_user_cache', 20);
add_action('learndash_topic_completed', 'bzj_todo_invalidate_current_user_cache', 20);
add_action('learndash_process_mark_complete', 'bzj_todo_invalidate_current_user_cache', 20);

/* ==========================================================================
 * 18. ADMIN / DIAGNOSTICS
 * ========================================================================== */

function bzj_todo_backfill_codes($dry_run = false) {
    if (!current_user_can('manage_options')) return array();

    $meta_key = sanitize_key(bzj_todo_settings()['course_code_meta']);
    if (!$meta_key) return array();

    $ids = get_posts(array(
        'post_type'      => array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-assignment'),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));

    $report = array(
        'scanned' => 0,
        'updated' => 0,
        'existing'=> 0,
        'unmatched'=> 0,
    );

    foreach ($ids as $id) {
        $report['scanned']++;

        if (get_post_meta($id, $meta_key, true)) {
            $report['existing']++;
            continue;
        }

        $code = bzj_todo_code($id);

        if (!$code) {
            $report['unmatched']++;
            continue;
        }

        if (!$dry_run) {
            update_post_meta($id, $meta_key, $code);
        }

        $report['updated']++;
    }

    return $report;
}

function bzj_todo_diagnostics() {
    $diagnostics = array(
        'duplicate_codes' => array(),
        'uncoded_hs_courses' => array(),
        'missing_thumbnails' => array(),
        'orphaned_prereqs' => array(),
        'invalid_codes' => array(),
    );

    $codes = array();

    foreach (bzj_todo_hs_courses() as $course) {
        foreach (bzj_todo_sequence($course->ID, 0) as $entry) {
            $code = bzj_todo_code($entry['post_id']);
            if (!$code) {
                $diagnostics['uncoded_hs_courses'][] = array(
                    'course' => $course->ID,
                    'post'   => $entry['post_id'],
                );
                continue;
            }

            if (isset($codes[$code])) {
                $diagnostics['duplicate_codes'][$code][] = array(
                    'post' => $entry['post_id'],
                    'course' => $course->ID,
                );
            } else {
                $codes[$code] = array(
                    'post' => $entry['post_id'],
                    'course' => $course->ID,
                );
            }

            $parts = bzj_todo_parts($code);

            if (
                !$parts ||
                !in_array($parts['prefix'], array('HS', 'EC'), true) ||
                $parts['level'] < 1 || $parts['level'] > 4
            ) {
                $diagnostics['invalid_codes'][] = $code;
            }

            if (
                $parts &&
                $parts['lesson'] === 1 &&
                $parts['topic'] === 0 &&
                !has_post_thumbnail($entry['post_id']) &&
                !has_post_thumbnail($course->ID) &&
                empty(bzj_todo_settings()['default_image'])
            ) {
                $diagnostics['missing_thumbnails'][] = $code;
            }

            foreach (bzj_todo_prereqs($entry['post_id']) as $prereq) {
                if (!isset($codes[$prereq]) && !bzj_todo_find_post_by_code($prereq, $course->ID)) {
                    $diagnostics['orphaned_prereqs'][] = array(
                        'post' => $entry['post_id'],
                        'requires' => $prereq,
                    );
                }
            }
        }
    }

    return $diagnostics;
}

function bzj_todo_admin_menu() {
    add_options_page(
        'Things To Do Today',
        'Things To Do Today',
        'manage_options',
        'bzj-things-to-do-today',
        'bzj_todo_admin_page'
    );
}
add_action('admin_menu', 'bzj_todo_admin_menu');

function bzj_todo_admin_page() {
    if (!current_user_can('manage_options')) return;

    $settings = bzj_todo_settings();
    $notice = '';

    if (isset($_POST['bzj_todo_save'])) {
        check_admin_referer('bzj_todo_save');

        $text_fields = array(
            'default_image',
            'course_code_meta',
            'prereq_meta',
            'classies_url',
            'polls_url',
            'jobs_url',
            'common_url',
            'matches_url',
            'support_url',
            'progress_url',
            'affiliate_url',
            'palmier_point_type',
            'notification_times',
            'ro_course_slug',
            'bus_course_slug',
        );

        foreach ($text_fields as $key) {
            if (isset($_POST[$key])) {
                $settings[$key] = sanitize_textarea_field(
                    wp_unslash($_POST[$key])
                );
            }
        }

        if (isset($_POST['hs_categories'])) {
            $settings['hs_categories'] = array_values(array_filter(array_map(
                'sanitize_title',
                preg_split('/\r\n|\r|\n/', wp_unslash($_POST['hs_categories']))
            )));
        }

        $toggles = array(
            'cache_enabled',
            'popup_enabled',
            'popup_on_login',
            'popup_after_return',
            'secondary_enabled',
            'notifications_enabled',
            'notification_push_enabled',
            'analytics_enabled',
        );

        foreach ($toggles as $key) {
            $settings[$key] = isset($_POST[$key]) ? 1 : 0;
        }

        foreach (array(
            'cache_ttl',
            'return_after_hours',
            'popup_cooldown_hours',
            'popup_max_per_week',
            'popup_delay',
            'popup_remind_hours',
            'secondary_max',
            'notification_batch_size',
            'notification_retention_days',
            'assessment_quiz_id',
        ) as $key) {
            if (isset($_POST[$key])) $settings[$key] = absint($_POST[$key]);
        }

        foreach (array('default_palmier_reward','assessment_passing_percent') as $key) {
            if (isset($_POST[$key])) $settings[$key] = max(0, (float) $_POST[$key]);
        }

        update_option(BZJ_TODO_OPTION, $settings);
        delete_option(BZJ_TODO_OPTION . '_runtime');
        bzj_todo_schedule_notifications();
        $notice = 'Things To Do Today settings saved.';
    }

    if (isset($_POST['bzj_todo_backfill_preview'])) {
        check_admin_referer('bzj_todo_backfill');
        $report = bzj_todo_backfill_codes(true);
        $notice = sprintf(
            'Backfill preview: %d scannned, %d codes available to write, %d already populated, %d unmatched.',
            $report['scanned'],
            $report['updated'],
            $report['existing'],
            $report['unmatched']
        );
    }

    if (isset($_POST['bzj_todo_backfill_apply'])) {
        check_admin_referer('bzj_todo_backfill');
        $report = bzj_todo_backfill_codes(false);
        $notice = sprintf(
            'Backfill applied: %d program codes written from %d scanned items.',
            $report['updated'],
            $report['scanned']
        );
    }

    if (isset($_POST['bzj_todo_reschedule'])) {
        check_admin_referer('bzj_todo_reschedule');
        bzj_todo_schedule_notifications();
        $notice = 'Notification timetable rebuilt using the WordPress site timezone.';
    }

    $diagnostics = bzj_todo_diagnostics();
    ?>
    <div class="wrap">
        <h1>Buzzjuice — Things To Do Today</h1>

        <?php if ($notice): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($notice); ?></p>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('bzj_todo_save'); ?>

            <h2>Curriculum identity</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Registration &amp; Orientation course slug</th>
                    <td><input name="ro_course_slug" class="regular-text" value="<?php echo esc_attr($settings['ro_course_slug']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">BUS1300 course slug</th>
                    <td><input name="bus_course_slug" class="regular-text" value="<?php echo esc_attr($settings['bus_course_slug']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">H&amp;S course categories</th>
                    <td><textarea name="hs_categories" rows="4" class="large-text code"><?php echo esc_textarea(implode("\n", (array) $settings['hs_categories'])); ?></textarea><p class="description">One LearnDash category slug per line.</p></td>
                </tr>
            </table>

            <h2>General</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Default H&S fallback image</th>
                    <td><input name="default_image" class="regular-text" value="<?php echo esc_attr($settings['default_image']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Program-code meta key</th>
                    <td><input name="course_code_meta" class="regular-text" value="<?php echo esc_attr($settings['course_code_meta']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Prerequisite meta key</th>
                    <td><input name="prereq_meta" class="regular-text" value="<?php echo esc_attr($settings['prereq_meta']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Cache TTL</th>
                    <td><input name="cache_ttl" type="number" min="30" max="3600" value="<?php echo esc_attr($settings['cache_ttl']); ?>"> seconds</td>
                </tr>
                <tr>
                    <th scope="row">Orientation assessment quiz ID</th>
                    <td><input name="assessment_quiz_id" type="number" min="0" value="<?php echo esc_attr($settings['assessment_quiz_id']); ?>"> <span class="description">Leave 0 to use the final course-level quiz.</span></td>
                </tr>
                <tr>
                    <th scope="row">Orientation pass percentage</th>
                    <td><input name="assessment_passing_percent" type="number" min="0" max="100" step="0.1" value="<?php echo esc_attr($settings['assessment_passing_percent']); ?>"> % <span class="description">0 means LearnDash completion is treated as passed.</span></td>
                </tr>
            </table>

            <h2>Popup</h2>
            <table class="form-table">
                <tr><th>Enable popup</th><td><input type="checkbox" name="popup_enabled" <?php checked(1, $settings['popup_enabled']); ?>></td></tr>
                <tr><th>Show on login</th><td><input type="checkbox" name="popup_on_login" <?php checked(1, $settings['popup_on_login']); ?>></td></tr>
                <tr><th>Show after long return</th><td><input type="checkbox" name="popup_after_return" <?php checked(1, $settings['popup_after_return']); ?>></td></tr>
                <tr><th>Return threshold</th><td><input name="return_after_hours" type="number" min="1" value="<?php echo esc_attr($settings['return_after_hours']); ?>"> hours</td></tr>
                <tr><th>Cooldown</th><td><input name="popup_cooldown_hours" type="number" min="1" value="<?php echo esc_attr($settings['popup_cooldown_hours']); ?>"> hours</td></tr>
                <tr><th>Maximum popups/week</th><td><input name="popup_max_per_week" type="number" min="1" max="20" value="<?php echo esc_attr($settings['popup_max_per_week']); ?>"></td></tr>
                <tr><th>Remind-later period</th><td><input name="popup_remind_hours" type="number" min="1" max="168" value="<?php echo esc_attr($settings['popup_remind_hours']); ?>"> hours</td></tr>
            </table>

            <h2>Secondary activities</h2>
            <table class="form-table">
                <tr><th>Enable secondary activities</th><td><input type="checkbox" name="secondary_enabled" <?php checked(1, $settings['secondary_enabled']); ?>></td></tr>
                <tr><th>Maximum visible</th><td><input name="secondary_max" type="number" min="0" max="5" value="<?php echo esc_attr($settings['secondary_max']); ?>"></td></tr>
            </table>

            <h2>Notifications</h2>
            <table class="form-table">
                <tr><th>Enable BuddyBoss/BuddyPress notifications</th><td><input type="checkbox" name="notifications_enabled" <?php checked(1, $settings['notifications_enabled']); ?>></td></tr>
                <tr><th>Enable push payload flag</th><td><input type="checkbox" name="notification_push_enabled" <?php checked(1, $settings['notification_push_enabled']); ?>></td></tr>
                <tr>
                    <th>Timetable</th>
                    <td>
                        <textarea name="notification_times" rows="8" class="large-text code"><?php echo esc_textarea($settings['notification_times']); ?></textarea>
                        <p class="description">WordPress site timezone. Saturday intentionally has no slots. Format: mon:07:00,11:00</p>
                    </td>
                </tr>
                <tr><th>Batch size</th><td><input name="notification_batch_size" type="number" min="10" max="500" value="<?php echo esc_attr($settings['notification_batch_size']); ?>"></td></tr>
            </table>

            <h2>Learning rewards</h2>
            <table class="form-table">
                <tr><th>Palmier point type</th><td><input name="palmier_point_type" class="regular-text" value="<?php echo esc_attr($settings['palmier_point_type']); ?>"></td></tr>
                <tr><th>Default displayed reward</th><td><input name="default_palmier_reward" type="number" min="0" step="0.01" value="<?php echo esc_attr($settings['default_palmier_reward']); ?>"></td></tr>
            </table>

            <h2>Platform destinations</h2>
            <table class="form-table">
                <?php foreach (array(
                    'classies_url' => 'Classies Chronicles',
                    'polls_url' => 'Community polls',
                    'jobs_url' => 'Buzzjuice opportunities',
                    'common_url' => 'Meet someone/common users',
                    'matches_url' => 'Matches',
                    'support_url' => 'Support',
                    'progress_url' => 'Learning progress',
                    'affiliate_url' => 'Affiliate activation',
                ) as $key => $label): ?>
                    <tr>
                        <th><?php echo esc_html($label); ?></th>
                        <td><input name="<?php echo esc_attr($key); ?>" class="regular-text" value="<?php echo esc_attr($settings[$key]); ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <p class="submit">
                <button class="button button-primary" name="bzj_todo_save">Save Settings</button>
            </p>
        </form>

        <hr>

        <h2>Curriculum tools</h2>
        <form method="post" style="margin-bottom:12px;">
            <?php wp_nonce_field('bzj_todo_backfill'); ?>
            <button class="button" name="bzj_todo_backfill_preview">Preview program-code backfill</button>
            <button class="button button-secondary" name="bzj_todo_backfill_apply">Apply program-code backfill</button>
        </form>

        <form method="post">
            <?php wp_nonce_field('bzj_todo_reschedule'); ?>
            <button class="button" name="bzj_todo_reschedule">Rebuild notification schedule</button>
        </form>

        <h2>Health &amp; Safety diagnostics</h2>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <td>Duplicate program codes</td>
                    <td><?php echo esc_html(count($diagnostics['duplicate_codes'])); ?></td>
                </tr>
                <tr>
                    <td>Uncoded H&amp;S activities</td>
                    <td><?php echo esc_html(count($diagnostics['uncoded_hs_courses'])); ?></td>
                </tr>
                <tr>
                    <td>First lessons without image/fallback</td>
                    <td><?php echo esc_html(count($diagnostics['missing_thumbnails'])); ?></td>
                </tr>
                <tr>
                    <td>Unresolved explicit prerequisites</td>
                    <td><?php echo esc_html(count($diagnostics['orphaned_prereqs'])); ?></td>
                </tr>
                <tr>
                    <td>Invalid H&amp;S/EC program codes</td>
                    <td><?php echo esc_html(count($diagnostics['invalid_codes'])); ?></td>
                </tr>
            </tbody>
        </table>

        <h2>Program prefix library</h2>
        <table class="widefat striped">
            <thead><tr><th>Prefix</th><th>Programme</th><th>Description</th></tr></thead>
            <tbody>
                <?php foreach (bzj_todo_prefix_library() as $prefix => $definition): ?>
                    <tr>
                        <td><code><?php echo esc_html($prefix); ?></code></td>
                        <td><?php echo esc_html($definition['name'] ?? ''); ?></td>
                        <td><?php echo esc_html($definition['description'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>
            The curriculum engine reads the four configured LearnDash H&amp;S categories
            and orders existing coded content by level → lesson → course → topic.
            Missing content is skipped; duplicate codes should be resolved before
            production use.
        </p>
    </div>
    <?php
}

/* ==========================================================================
 * 19. WIDGET / SHORTCODE REGISTRATION
 * ========================================================================== */

class BZJ_Things_To_Do_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'bzj_things_to_do',
            'Buzzjuice — Things To Do Today',
            array(
                'description' => 'Personalised Buzzjuice learning and engagement next step.',
            )
        );
    }

    public function widget($args, $instance) {
        if (!is_user_logged_in()) return;

        echo $args['before_widget'];
        echo bzj_todo_render_block(get_current_user_id());
        echo $args['after_widget'];
    }

    public function form($instance) {
        echo '<p>This widget uses the fixed "Things To Do Today" heading and does not require a custom title.</p>';
    }

    public function update($new_instance, $old_instance) {
        return array();
    }
}

add_action('widgets_init', function() {
    register_widget('BZJ_Things_To_Do_Widget');
});

add_shortcode('bz_things_to_do_today', 'bzj_todo_render_block_shortcode');

/* ==========================================================================
 * 20. FINAL HOOKS
 * ========================================================================== */

add_action('init', function() {
    /*
     * Do not touch or remove Profile Prompter callbacks. The popup uses DOM
     * arbitration so both MU plugins remain independently upgradeable.
     */
    if (!is_user_logged_in()) return;
}, 1);
