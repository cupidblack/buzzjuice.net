<?php
add_action('init', function () {
    $file = WP_CONTENT_DIR . '/themes/buddyboss-theme/inc/plugins/elementor/widgets/bb-forums.php';
    if (file_exists($file) &&
        strpos(file_get_contents($file), 'bzj_safe_string') === false
    ) {
        if (function_exists('bzj_log_once')) {
            bzj_log_once(
                'debug-buddyboss-theme.log',
                'bb-forums.php patch missing: REAPPLY bzj_safe_string() fix to str_replace.',
                __FILE__, __LINE__, 600
            );
        }
    }
});