<?php

/* ✅ 1. TOTAL USERS → [user_count]+ members
🔧 Method (Shortcode)
✅ Use in Elementor:

Use a Shortcode widget:

[user_count]
*/

function buzzjuice_user_count_shortcode() {
    $count = count_users();
    return "💕" . number_format($count['total_users']);
}
add_shortcode('user_count', 'buzzjuice_user_count_shortcode');

/*✅ 2.COUNT SPECIFIC ROLES (Your Lifestyle System)

You want:

classic_lifestyle
silver_lifestyle
rockstar_lifestyle
premium_lifestyle
jewel_affiliate
🔧 Shortcode: */
function bj_lifestyle_users_count() {

    $roles = [
        'classic_lifestyle',
        'silver_lifestyle',
        'rockstar_lifestyle',
        'premium_lifestyle',
        'jewel_affiliate'
    ];

    $user_query = new WP_User_Query([
        'role__in' => $roles,
        'fields' => 'ID'
    ]);

    return "⚡" . number_format(count($user_query->get_results()));
}
add_shortcode('count_active_lifestyle_users', 'bj_lifestyle_users_count');
/*✅ Use:
[count_active_lifestyle_users] active members
*/

/* ✅ 3. ACTIVITY → “X joined today”
🔧 Step 1: Count users registered today */

function buzzjuice_users_today() {
    global $wpdb;

    $today = date('Y-m-d 00:00:00');

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(ID) FROM $wpdb->users WHERE user_registered >= %s",
            $today
        )
    );

    return (int) $count;
}

/*🔧 Step 2: Combine into shortcode */
function buzzjuice_activity_social_proof() {
    $today_count = buzzjuice_users_today();

    // Fake localized number (10% of total)
    $local_estimate = ceil($today_count * 0.1);

    return "🔥 {$today_count}";
}
add_shortcode('bz_activity_today', 'buzzjuice_activity_social_proof');

/*🔧 Step 2: Combine into shortcode */
function buzzjuice_activity_social_proof_nearby() {
    $today_count = buzzjuice_users_today();

    // Fake localized number (10% of total)
    $local_estimate = ceil($today_count * 0.1);

    return "📍 ~{$local_estimate}";
}
add_shortcode('bz_activity_today_nearby', 'buzzjuice_activity_social_proof_nearby');

/*✅ Elementor:
[bz_activity_today] [bz_activity_today_nearby]
⚠️ Optional (More Accurate Location)

If you want real geo-based numbers:

Use IP detection (e.g. Cloudflare header or GeoIP)
Store user location in user_meta
Query by country/city */