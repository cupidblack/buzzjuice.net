<?php
//runtime-leaderboard-sanitizer
add_action('wp_insert_post', function ($post_id) {

    $keys = [
        'leaderboard_points_based',
        'leaderboard_time_based',
        'leaderboard_course_based'
    ];

    foreach ($keys as $key) {

        $raw = get_post_meta($post_id, $key, true);

        if (is_string($raw)) {

            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                update_post_meta($post_id, $key, $decoded);
                continue;
            }

            $unserialized = maybe_unserialize($raw);

            update_post_meta(
                $post_id,
                $key,
                is_array($unserialized) ? $unserialized : []
            );
        }
    }

}, 5);



/**
 * Enforces a consistent preference schema across all myCRED LearnDash hooks
 */

add_filter('mycred_setup_hooks', function ($hooks) {

    if (!is_array($hooks)) {
        return [];
    }

    foreach ($hooks as $key => $hook) {

        if (!is_array($hook)) {
            $hook = [];
        }

        if (!isset($hook['prefs']) || !is_array($hook['prefs'])) {
            $hook['prefs'] = [];
        }

        // Normalize schema BEFORE any hook logic runs
        $hook['prefs'] = array_merge([
            'limit' => [],
            'select_group' => '',
        ], $hook['prefs']);

        $hooks[$key] = $hook;
    }

    return $hooks;

}, 5);

add_action('init', function() {
    $files = [
        '/plugins/mycred-toolkit/includes/addons/mycred-learndash/inc/class-mycred-learndash-complete-topic-hook.php',
        '/plugins/mycred-toolkit/includes/addons/mycred-learndash/inc/class-mycred-learndash-complete-lesson-hook.php',
        '/plugins/mycred-toolkit/includes/addons/mycred-learndash/inc/class-mycred-learndash-group-hook.php',
        '/plugins/mycred-toolkit/includes/addons/mycred-learndash/inc/class-mycred-learndash-completing-course-hook.php'
    ];
    foreach ($files as $file) {
        $full = WP_CONTENT_DIR . $file;
        if (file_exists($full)) {
            $c = file_get_contents($full);
            if (strpos($c, 'bzj_safe_array') === false) {
                if (function_exists('bzj_log_once')) {
                    bzj_log_once(
                        'debug-mycred-toolkit.log',
                        "bzj_safe_array patch missing in $file -- reapply patch after update.",
                        __FILE__, __LINE__, 600
                    );
                }
            }
        }
    }
});