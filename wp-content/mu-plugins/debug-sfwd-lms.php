<?php
/**
 * MU Kernel: LearnDash Course Grid data contract safety layer
 */

if (!defined('ABSPATH')) exit;

/**
 * Normalize filter dataset before rendering
 */
add_filter('learndash_course_grid_filter_data', function ($filters) {

    if (!is_array($filters)) {
        return ['category' => []];
    }

    // enforce schema
    $filters['category'] = $filters['category'] ?? [];

    if (!is_array($filters['category'])) {
        $filters['category'] = [];
    }

    return $filters;

}, 1);

add_filter('do_shortcode_tag', function ($output) {

    if (strpos($output, 'sfwd-lms') !== false) {
        $output = preg_replace(
            '/\[\s*category\s*\]/',
            '[]',
            $output
        );
    }

    return $output;

}, 1);

add_action('init', function() {
    $file = WP_PLUGIN_DIR . '/sfwd-lms/includes/course-grid/templates/filter/layout.php';
    if (file_exists($file)) {
        $src = file_get_contents($file);
        if (strpos($src, 'bzj_safe_array') === false) {
            if (function_exists('bzj_log_once')) {
                bzj_log_once('debug-learndash.log', 'Patch missing in LearnDash course-grid layout, please reapply.', __FILE__, __LINE__, 600);
            }
        }
    }
});

add_action('init', function () {

    $file = WP_PLUGIN_DIR . '/sfwd-lms/includes/course/ld-course-steps-functions.php';

    if (file_exists($file)) {

        $contents = file_get_contents($file);

        if (strpos($contents, '$post_type = null') === false) {

            if (function_exists('bzj_log_once')) {
                bzj_log_once(
                    'debug-learndash.log',
                    'LearnDash post_type safety patch missing or reverted',
                    __FILE__,
                    __LINE__,
                    300
                );
            }
        }
    }

});

