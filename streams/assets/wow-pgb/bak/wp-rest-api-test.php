<?php
require_once('assets/init.php');

if ($wo['loggedin'] == false) {
    Wo_RedirectSmooth(Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

$user_id = $wo['user']['user_id'];
$member_type = 4; // VIP
$member_pro = 1;
$time = time();

$update_data = array(
    'pro_type' => $member_type,
    'is_pro' => $member_pro,
    'pro_time' => $time
);

if (Wo_UpdateUserData($user_id, $update_data)) {
    echo "You have been upgraded to VIP status!";
} else {
    echo "Failed to upgrade to VIP status.";
}
?>