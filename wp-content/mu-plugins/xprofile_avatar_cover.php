<?php
/**
 * Normalize avatar/cover URLs before saving or rendering.
 */
function normalize_streams_url($url, $type = 'avatar') {
    if (empty($url) || !is_string($url)) {
        return '';
    }

    $site_url = rtrim(home_url(), '/'); // e.g. https://buzzjuice.net
    $url = trim($url);

    // Remove query parameters like ?v=123
    $url = preg_replace('/(\?|&)(v|ver|cache)=[0-9]+/i', '', $url);

    // 🔧 Normalize: remove ALL /streams/ variants
    $url = preg_replace('#^(https?://)?buzzjuice\.net/?#i', '', $url);
    $url = preg_replace('#^/?streams/#i', '', $url);
    $url = preg_replace('#^(\.\./streams/)+#', '', $url);

    // Ensure it starts with upload/
    $url = ltrim($url, '/');
    if (strpos($url, 'upload/') !== 0) {
        return ''; // invalid, ignore
    }

    // ✅ Return clean, single /streams/ prefix
    return '/streams/' . $url;
}


/**
 * Save avatar and cover into xProfile (normalized).
 */
add_action('bp_core_avatar_uploaded', 'update_avatar_and_cover_xprofile_fields');
add_action('xprofile_data_after_save', 'update_avatar_and_cover_xprofile_fields', 20, 1);

function update_avatar_and_cover_xprofile_fields($data) {
    static $has_run = [];

    $user_id = is_object($data) && isset($data->user_id)
        ? (int)$data->user_id
        : (is_numeric($data) ? (int)$data : 0);

    if (!$user_id || isset($has_run[$user_id])) {
        return;
    }
    $has_run[$user_id] = true;

    $avatar_url = bp_core_fetch_avatar([
        'item_id' => $user_id,
        'object'  => 'user',
        'type'    => 'full',
        'html'    => false
    ]);

    $cover_url = bp_attachments_get_attachment('url', [
        'item_id'    => $user_id,
        'object_dir' => 'members',
        'type'       => 'cover-image'
    ]);

    if (function_exists('xprofile_set_field_data')) {
        $normalized_avatar = normalize_streams_url($avatar_url, 'avatar');
        $normalized_cover  = normalize_streams_url($cover_url, 'cover');

        if (!empty($normalized_avatar)) {
            xprofile_set_field_data('avatar', $user_id, $normalized_avatar);
            update_user_meta($user_id, 'avatar_version', time()); // bump version
        }

        if (!empty($normalized_cover)) {
            xprofile_set_field_data('cover', $user_id, $normalized_cover);
            update_user_meta($user_id, 'cover_version', time());
        }
    }
}

/**
 * Fetch avatar from xProfile (normalized + versioning).
 */
add_filter('bp_core_fetch_avatar_url', 'custom_bp_avatar_from_xprofile', 10, 2);
function custom_bp_avatar_from_xprofile($avatar_url, $params) {
    if (empty($params['item_id']) || $params['object'] !== 'user') {
        return $avatar_url;
    }

    $user_id = (int) $params['item_id'];
    $custom_avatar = xprofile_get_field_data('avatar', $user_id);

    if (!empty($custom_avatar)) {
        $custom_avatar = normalize_streams_url($custom_avatar, 'avatar');
        if (!empty($custom_avatar)) {
            // Convert to absolute URL
            $absolute = rtrim(home_url(), '/') . $custom_avatar;
            $version  = (int) get_user_meta($user_id, 'avatar_version', true);
            if ($version > 0) {
                $absolute .= '?v=' . $version;
            }
            return esc_url($absolute);
        }
    }

    return $avatar_url;
}

/**
 * Override avatar <img> HTML
 */
add_filter('bp_core_fetch_avatar', 'custom_bp_avatar_html_override', 10, 2);
function custom_bp_avatar_html_override($html, $params) {
    if (empty($params['item_id']) || $params['object'] !== 'user') {
        return $html;
    }

    $url = custom_bp_avatar_from_xprofile('', $params);
    if (empty($url)) {
        return $html;
    }

    $width   = !empty($params['width'])  ? (int) $params['width']  : 150;
    $height  = !empty($params['height']) ? (int) $params['height'] : 150;
    $class   = esc_attr($params['class'] ?? 'avatar');
    $alt     = esc_attr($params['alt'] ?? 'User avatar');
    $style   = ($params['type'] === 'full') ? 'style="object-fit: cover;"' : '';

    return sprintf(
        '<img src="%s" class="%s" width="%d" height="%d" alt="%s" %s loading="lazy" decoding="async" />',
        esc_url($url),
        $class,
        $width,
        $height,
        $alt,
        $style
    );
}

/**
 * Cover fetch override (normalized + version).
 */
add_filter('bp_attachments_pre_get_attachment', 'custom_bp_cover_from_xprofile', 10, 2);
function custom_bp_cover_from_xprofile($pre_value, $args) {
    if (
        empty($args['object_dir']) || 
        empty($args['item_id']) || 
        empty($args['type']) || 
        $args['object_dir'] !== 'members' || 
        $args['type'] !== 'cover-image'
    ) {
        return $pre_value;
    }

    $user_id = (int) $args['item_id'];
    $custom_cover = xprofile_get_field_data('cover', $user_id);

    if (empty($custom_cover)) {
        return $pre_value;
    }

    $custom_cover = normalize_streams_url($custom_cover, 'cover');
    if (empty($custom_cover)) {
        return $pre_value;
    }

    $absolute = rtrim(home_url(), '/') . $custom_cover;
    $version  = (int) get_user_meta($user_id, 'cover_version', true);
    if ($version > 0) {
        $absolute .= '?v=' . $version;
    }

    return esc_url_raw($absolute);
}
