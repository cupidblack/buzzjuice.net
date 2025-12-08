<?php
// CONFIGURATION
$wowonder = [
    'host' => 'localhost',
    'user' => 'koware_iapd',
    'pass' => '42v[D=qOXk#E',
    'name' => 'koware_buzzjuice_streams',
    'users_table' => 'Wo_Users'
];
$wp = [
    'host' => 'localhost',
    'user' => 'koware_iapd',
    'pass' => '42v[D=qOXk#E',
    'name' => 'koware_buzzjuice',
    'prefix' => 'wp_'
];

// Connect to WoWonder
$wowonderDb = new mysqli($wowonder['host'], $wowonder['user'], $wowonder['pass'], $wowonder['name']);
if ($wowonderDb->connect_errno) die("WoWonder DB error: " . $wowonderDb->connect_error);

// Connect to WordPress
$wpDb = new mysqli($wp['host'], $wp['user'], $wp['pass'], $wp['name']);
if ($wpDb->connect_errno) die("WP DB error: " . $wpDb->connect_error);

// Helper: password hashing compatible with WP
require_once __DIR__ . '/streams/assets/class-phpass.php';
$wp_hasher = new PasswordHash(8, true);

function random_password($length = 12) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()'), 0, $length);
}

// Fields editable by users (BuddyBoss xProfile)
$xprofile_fields = [
    'username','email','first_name','last_name','avatar','cover','background_image','background_image_status',
    'relationship_id','address','working','working_link','about','school','gender','birthday','country_id',
    'website','facebook','google','twitter','linkedin','youtube','vk','instagram','qq','wechat','discord',
    'mailru','okru','language','phone_number','timezone','new_email','new_phone','city','state','zip',
    'school_completed','weather_unit','skills','languages','currently_working','share_my_location',
    'details','sidebar_data','order_posts_by','social_login','info_file','phone_privacy','have_monetization'
];

// System fields stored in wp_usermeta
$usermeta_fields = [
    'user_id','password','email_code','src','ip_address','follow_privacy','friend_privacy','post_privacy',
    'message_privacy','confirm_followers','show_activities_privacy','birth_privacy','visit_privacy','verified',
    'lastseen','showlastseen','emailNotification','e_liked','e_wondered','e_shared','e_followed','e_commented',
    'e_visited','e_liked_page','e_mentioned','e_joined_group','e_accepted','e_profile_wall_post','e_sentme_msg',
    'e_last_notif','notification_settings','status','active','admin','type','registered','start_up',
    'start_up_info','startup_follow','startup_image','last_email_sent','sms_code','is_pro','pro_time',
    'pro_type','pro_remainder','joined','css_file','referrer','ref_user_id','ref_level','balance',
    'paypal_email','notifications_sound','android_m_device_id','ios_m_device_id','android_n_device_id',
    'ios_n_device_id','web_device_id','lat','lng','last_location_update','last_data_update','last_avatar_mod',
    'last_cover_mod','points','wallet','daily_points','converted_points','point_day_expire','last_follow_id',
    'share_my_data','last_login_data','two_factor','two_factor_hash','two_factor_verified','code_sent',
    'time_code_sent','permission','banned','banned_reason','credits','authy_id','google_secret','two_factor_method'
];

// Fetch all WoWonder users
$q = "SELECT * FROM `{$wowonder['users_table']}`";
$res = $wowonderDb->query($q);
if (!$res) die("Error fetching users: " . $wowonderDb->error);

$count = 0;
while ($user = $res->fetch_assoc()) {
    $username = $user['username'];
    $email = $user['email'];
    $first_name = $user['first_name'] ?? '';
    $last_name = $user['last_name'] ?? '';
    $display_name = trim($first_name . ' ' . $last_name);
    $registered = $user['registered'] ?? date('Y-m-d H:i:s');

    $password_plain = random_password();
    $password_hash = $wp_hasher->HashPassword($password_plain);

    // Check if user exists
    $check = $wpDb->query("SELECT ID FROM `{$wp['prefix']}users` WHERE user_login = '" . $wpDb->real_escape_string($username) . "' OR user_email = '" . $wpDb->real_escape_string($email) . "' LIMIT 1");

    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $wp_user_id = $row['ID'];
        echo "User $username ($email) exists → updating meta.\n";
    } else {
        // Insert new user
        $insert = $wpDb->prepare("INSERT INTO `{$wp['prefix']}users` (user_login, user_pass, user_email, user_registered, display_name, user_status) VALUES (?, ?, ?, ?, ?, 0)");
        $insert->bind_param('sssss', $username, $password_hash, $email, $registered, $display_name);
        if (!$insert->execute()) {
            echo "Failed to insert $username: " . $insert->error . "\n";
            continue;
        }
        $wp_user_id = $insert->insert_id;
        $insert->close();

        echo "Inserted new user $username ($email) → WP ID $wp_user_id | Temp Password: $password_plain\n";
    }

    // INSERT or UPDATE wp_usermeta
    foreach ($usermeta_fields as $field) {
        if (!isset($user[$field]) || $user[$field] === null) continue;
    
        // 🛠 Use 'wo_user_id' instead of 'user_id' for usermeta
        $meta_key = ($field === 'user_id') ? 'wo_user_id' : $field;
        $meta_value = $user[$field];
    
        // Try update first
        $stmt = $wpDb->prepare("SELECT umeta_id FROM `{$wp['prefix']}usermeta` WHERE user_id = ? AND meta_key = ? LIMIT 1");
        $stmt->bind_param('is', $wp_user_id, $meta_key);
        $stmt->execute();
        $stmt->store_result();
    
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $update = $wpDb->prepare("UPDATE `{$wp['prefix']}usermeta` SET meta_value = ? WHERE user_id = ? AND meta_key = ?");
            $update->bind_param('sis', $meta_value, $wp_user_id, $meta_key);
            $update->execute();
            $update->close();
        } else {
            $stmt->close();
            $insert = $wpDb->prepare("INSERT INTO `{$wp['prefix']}usermeta` (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
            $insert->bind_param('iss', $wp_user_id, $meta_key, $meta_value);
            $insert->execute();
            $insert->close();
        }
    }


    // INSERT or UPDATE xprofile data
    // Insert or update BuddyBoss xprofile data
    foreach ($xprofile_fields as $field) {
        if (!isset($user[$field]) || $user[$field] === null) continue;
    
        // Get field_id by name
        $field_id_q = $wpDb->prepare("SELECT id FROM `{$wp['prefix']}bp_xprofile_fields` WHERE name = ? LIMIT 1");
        $field_id_q->bind_param('s', $field);
        $field_id_q->execute();
        $field_id_q->bind_result($field_id);
        $field_id_q->fetch();
        $field_id_q->close();
    
        if (empty($field_id)) {
            echo "❌ Field '$field' not found in xprofile_fields. Skipping...\n";
            continue;
        }
    
        // Check if the data already exists
        $check_data = $wpDb->prepare("SELECT id FROM `{$wp['prefix']}bp_xprofile_data` WHERE field_id = ? AND user_id = ? LIMIT 1");
        $check_data->bind_param('ii', $field_id, $wp_user_id);
        $check_data->execute();
        $check_data->store_result();
    
        if ($check_data->num_rows > 0) {
            $check_data->close();
            $update = $wpDb->prepare("UPDATE `{$wp['prefix']}bp_xprofile_data` SET value = ?, last_updated = NOW() WHERE field_id = ? AND user_id = ?");
            $update->bind_param('sii', $user[$field], $field_id, $wp_user_id);
            $update->execute();
            $update->close();
        } else {
            $check_data->close();
            $insert = $wpDb->prepare("INSERT INTO `{$wp['prefix']}bp_xprofile_data` (field_id, user_id, value, last_updated) VALUES (?, ?, ?, NOW())");
            $insert->bind_param('iis', $field_id, $wp_user_id, $user[$field]);
            $insert->execute();
            $insert->close();
        }
    }
    
    // Save original Wo_Users.user_id into xProfile field_id = 4 ("Wo_Users_user_id")
    $original_id = $user['user_id'];
    $field_id = 4; // Predefined in xprofile_fields table
    
    $check_orig = $wpDb->prepare("SELECT id FROM `{$wp['prefix']}bp_xprofile_data` WHERE field_id = ? AND user_id = ? LIMIT 1");
    $check_orig->bind_param('ii', $field_id, $wp_user_id);
    $check_orig->execute();
    $check_orig->store_result();
    
    if ($check_orig->num_rows > 0) {
        $check_orig->close();
        $update = $wpDb->prepare("UPDATE `{$wp['prefix']}bp_xprofile_data` SET value = ?, last_updated = NOW() WHERE field_id = ? AND user_id = ?");
        $update->bind_param('sii', $original_id, $field_id, $wp_user_id);
        $update->execute();
        $update->close();
    } else {
        $check_orig->close();
        $insert = $wpDb->prepare("INSERT INTO `{$wp['prefix']}bp_xprofile_data` (field_id, user_id, value, last_updated) VALUES (?, ?, ?, NOW())");
        $insert->bind_param('iis', $field_id, $wp_user_id, $original_id);
        $insert->execute();
        $insert->close();
    }

    // Ensure force reset flag is updated
    $meta_key = 'force_password_reset';
    $meta_value = '1';
    $stmt = $wpDb->prepare("SELECT umeta_id FROM `{$wp['prefix']}usermeta` WHERE user_id = ? AND meta_key = ? LIMIT 1");
    $stmt->bind_param('is', $wp_user_id, $meta_key);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $update = $wpDb->prepare("UPDATE `{$wp['prefix']}usermeta` SET meta_value = ? WHERE user_id = ? AND meta_key = ?");
        $update->bind_param('sis', $meta_value, $wp_user_id, $meta_key);
        $update->execute();
        $update->close();
    } else {
        $stmt->close();
        $insert = $wpDb->prepare("INSERT INTO `{$wp['prefix']}usermeta` (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
        $insert->bind_param('iss', $wp_user_id, $meta_key, $meta_value);
        $insert->execute();
        $insert->close();
    }

    $count++;
}

echo "✅ User sync complete. $count users processed.\n";
?>