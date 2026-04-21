<?php
/*
Plugin Name: BZJ: FFmpeg Core Control
Description: Central control for FFmpeg, BuddyBoss vendor patching, settings normalization, and script dependency rescue.
*/
defined('ABSPATH') || exit;

/* ------------------------------------------------------------------------
  1. Register All Dummy Script Handles (fixes WP 6.9+ and BuddyBoss dependency errors)
--------------------------------------------------------------------------- */
add_action('muplugins_loaded', function () {
    if (!class_exists('WP_Scripts')) require_once ABSPATH . WPINC . '/class.wp-scripts.php';
    global $wp_scripts;
    if (empty($wp_scripts) || !($wp_scripts instanceof WP_Scripts)) {
        $wp_scripts = new WP_Scripts();
    }
    $handles = [
        'bp-widget-members',
        'bp-jquery-query',
        'bp-jquery-cookie',
        'bp-jquery-scroll-to',
        'bp-media-dropzone',
        // Add future handles here when error logs show new ones
    ];
    foreach ($handles as $handle) {
        if (!isset($wp_scripts->registered[$handle])) {
            $wp_scripts->add($handle, '', [], null);
        }
    }
}, -10000);


/* ------------------------------------------------------------------------
  2. Always-On BuddyBoss Vendor Patch (PHP 8.1+ ArrayAccess/IteratorAggregate compatibility)
--------------------------------------------------------------------------- */
add_action('muplugins_loaded', function () {
    if (PHP_VERSION_ID < 80100) return;
    $alchemy_file = WP_PLUGIN_DIR . '/buddyboss-platform/vendor/alchemy/binary-driver/src/Alchemy/BinaryDriver/Configuration.php';
    if (!file_exists($alchemy_file)) return;
    $code = file_get_contents($alchemy_file);
    $already_patch = (
        strpos($code, 'function getIterator(): \Traversable') !== false &&
        strpos($code, 'function offsetExists(mixed $offset): bool') !== false &&
        strpos($code, 'function offsetGet(mixed $offset): mixed') !== false &&
        strpos($code, 'function offsetSet(mixed $offset, mixed $value): void') !== false &&
        strpos($code, 'function offsetUnset(mixed $offset): void') !== false
    );
    if ($already_patch) return;
    $code_fixed = preg_replace([
        '/function offsetExists\s*\(\$offset\)/',
        '/function offsetGet\s*\(\$offset\)/',
        '/function offsetSet\s*\(\$offset,\s*\$value\)/',
        '/function offsetUnset\s*\(\$offset\)/',
        '/function getIterator\s*\(\)/',
    ], [
        'function offsetExists(mixed $offset): bool // BZJ_PATCHED',
        'function offsetGet(mixed $offset): mixed // BZJ_PATCHED',
        'function offsetSet(mixed $offset, mixed $value): void // BZJ_PATCHED',
        'function offsetUnset(mixed $offset): void // BZJ_PATCHED',
        'function getIterator(): \Traversable // BZJ_PATCHED',
    ], $code);
    if ($code_fixed && $code_fixed !== $code) {
        file_put_contents($alchemy_file, $code_fixed);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BZJ PATCH: Auto-applied fix to BuddyBoss Alchemy Configuration.php');
        }
    }
}, 1);


/* ------------------------------------------------------------------------
  3. FFmpeg/FFprobe Path Control (NO BB_FFMPEG_BINARY_PATH constant needed!)
--------------------------------------------------------------------------- */
if (!defined('BZJ_FFMPEG')) {
    define('BZJ_FFMPEG', ABSPATH . 'shared/ffmpeg/ffmpeg');
}
if (!defined('BZJ_FFPROBE')) {
    define('BZJ_FFPROBE', ABSPATH . 'shared/ffmpeg/ffprobe');
}
function bzj_ffmpeg_path()   { return BZJ_FFMPEG; }
function bzj_ffprobe_path()  { return BZJ_FFPROBE; }
add_filter('bb_ffmpeg_path',          'bzj_ffmpeg_path');
add_filter('bb_ffprobe_path',         'bzj_ffprobe_path');
add_filter('bp_better_messages_ffmpeg_path', 'bzj_ffmpeg_path');
// Do NOT use BB_FFMPEG_BINARY_PATH or BB_FFPROBE_BINARY_PATH constants in wp-config.php.


/* ------------------------------------------------------------------------
  4. Media Runtime Contract Layer: Only allow BuddyBoss FFmpeg if binaries are valid
--------------------------------------------------------------------------- */
add_action('muplugins_loaded', function () {
    $ffmpeg  = BZJ_FFMPEG;
    $ffprobe = BZJ_FFPROBE;
    $valid = function ($bin) {
        return is_string($bin) && file_exists($bin) && is_executable($bin);
    };
    if (!$valid($ffmpeg) || !$valid($ffprobe)) {
        add_filter('bb_video_enabled', '__return_false', 999999);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[BZJ MRCL] FFmpeg/FFProbe not valid — disabled BuddyBoss video module');
        }
    }
}, 0);


/* ------------------------------------------------------------------------
  5. Settings Normalization Contract — Fix trim(null)/type errors in all BuddyBoss video settings
--------------------------------------------------------------------------- */
// Scalar normalization logic, always returns '' for null, trims strings
if (!function_exists('bzj_contract_scalar')) {
    function bzj_contract_scalar(&$v) {
        if ($v === null) $v = '';
        elseif (is_bool($v)) $v = $v ? '1' : '0';
        elseif (is_array($v) || is_object($v)) $v = '';
        else $v = trim((string) $v);
    }
}
// On update (save): guarantee no nulls, always string or ''
add_filter('pre_update_option_bp_video_admin_settings', function ($value) {
    if (is_array($value)) array_walk_recursive($value, 'bzj_contract_scalar');
    return $value;
}, 1);
// On option load: same guarantee applies before plugin sees it
add_filter('option_bp_video_admin_settings', function ($value) {
    if (is_array($value)) array_walk_recursive($value, 'bzj_contract_scalar');
    return $value;
}, 1);
// Defensive: for direct options/cache bypass (rare)
add_filter('pre_option_bp_video_admin_settings', function ($value) {
    if (is_array($value)) array_walk_recursive($value, 'bzj_contract_scalar');
    return $value;
}, 1);
// UI-layer safety for direct field callbacks, if present (future-proof)
add_filter('bp_video_admin_setting_callback_video_section_value', function ($val) {
    if ($val === null) return '';
    if (is_bool($val)) return $val ? '1' : '0';
    if (is_array($val) || is_object($val)) return '';
    return trim((string)$val);
}, PHP_INT_MAX);
// One-time self-heal: strips legacy nulls or objects right now
add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    static $done = false;
    if ($done) return; $done = true;
    $opt = get_option('bp_video_admin_settings');
    if (is_array($opt)) {
        $old = $opt;
        array_walk_recursive($opt, 'bzj_contract_scalar');
        if ($opt !== $old) update_option('bp_video_admin_settings', $opt);
    }
}, 1);


/* ------------------------------------------------------------------------
  6. FFmpeg Probing Control (enable only for admin/AJAX/REST/media actions)
--------------------------------------------------------------------------- */
add_filter('bb_video_enabled', function ($enabled) {
    if (
        is_admin() ||
        (defined('DOING_AJAX') && DOING_AJAX) ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        (php_sapi_name() === 'cli') ||
        (isset($_REQUEST['action']) && stripos($_REQUEST['action'], 'video') !== false)
    ) {
        return true;
    }
    return false;
}, 999999);


/* ------------------------------------------------------------------------
  7. [Optionally] Debug FFmpeg/FFProbe Path
--------------------------------------------------------------------------- */
/*
add_action('init', function () {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BZJ_FFMPEG: '  . bzj_ffmpeg_path());
        error_log('BZJ_FFPROBE: ' . bzj_ffprobe_path());
    }
});
*/

/* ------------------------------------------------------------------------
  8. [Optional] Remove problematic script localization if UI breaks
--------------------------------------------------------------------------- */
/*
// remove_action('wp_head', 'bp_nouveau_video_localize_scripts', 100);
*/
