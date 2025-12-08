<?php
// 1. Add rewrite rules
add_action('init', function () {

    // Existing shortlink
    add_rewrite_rule('^r/hs1/?$', 'index.php?shortlink=hs1', 'top');

    // Custom shortlink — escape @ in regex
    add_rewrite_rule('^dry80/?$', 'index.php?shortlink=dry80', 'top');

    // Telegram shortlinks — no dots to avoid regex issues
    add_rewrite_rule('^telegram/?$', 'index.php?shortlink=telegram', 'top');
    add_rewrite_rule('^t.me/?$', 'index.php?shortlink=telegram', 'top'); 
    add_rewrite_rule('^tme/?$', 'index.php?shortlink=telegram', 'top'); 
});

// 2. Register query var
add_filter('query_vars', function ($vars) {
    $vars[] = 'shortlink';
    return $vars;
});

// 3. Handle redirect
add_action('template_redirect', function () {
    $slug = sanitize_text_field(get_query_var('shortlink'));

    if (!$slug) return;

    // Redirect map
    $map = [
        'hs1'       => 'https://buzzjuice.net/course/registration-orientation/?utm_source=telegram&utm_medium=pdf&utm_campaign=hs_diploma_launch',
        'dry80'     => 'https://docs.google.com/forms/d/e/1FAIpQLSdIENspBqltfxUq4uawzQS7gm_wWkSTcLimeIhMR7j0qPMKaw/viewform',
        'telegram'  => 'https://t.me/+IuV7FS16Xjw5NDdk', // Telegram invite
    ];

    // If matched, redirect
    if (isset($map[$slug])) {
        wp_redirect($map[$slug], 301);
        exit;
    }
});
