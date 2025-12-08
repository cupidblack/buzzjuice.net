<?php
require_once('assets/init.php');
decryptConfigData();
$f = '';
$s = '';
if (isset($_GET['f'])) {
    $f = Wo_Secure($_GET['f'], 0);
}

if (isset($_GET['s'])) {
    $s = Wo_Secure($_GET['s'], 0);
}
$hash_id = '';
if (!empty($_POST['hash_id'])) {
    $hash_id = $_POST['hash_id'];
} else if (!empty($_GET['hash_id'])) {
    $hash_id = $_GET['hash_id'];
} else if (!empty($_GET['hash'])) {
    $hash_id = $_GET['hash'];
} else if (!empty($_POST['hash'])) {
    $hash_id = $_POST['hash'];
}
$data = array();

/*BlueCrownR&D: WoW-PGB*/
$allow_array = array(
    'bank_transfer',
    'confirm_order',
    'wow_payment',
    'upgrade',
    'paystack',
    'cashfree',
    'payment',
    'pay_with_bitcoin',
    'coinpayments_callback',
    'paypro_with_bitcoin',
    'upload-blog-image',
    'wallet',
    'download_user_info',
    'movies',
    'funding',
    'stripe',
    'coinbase',
    'load_more_products',
    'yoomoney',
    'iyzipay',
    'fluttewave',
    'fortumo',
    'aamarpay',
    'pay_with_bitcoin',
);

if ($f == 'certification' && $s == 'download_user_certification' && !empty($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0) {
    $allow_array[] = 'certification';
}
$non_login_array = array(
    'bank_transfer',
    'confirm_order',
    'session_status',
    'open_lightbox',
    'get_welcome_users',
    'load_posts',
    'save_user_location',
    'load-more-groups',
    'load-more-pages',
    'load-more-users',
    'load_profile_posts',
    'confirm_user_unusal_login',
    'confirm_user',
    'confirm_sms_user',
    'resned_code',
    'resned_code_ac',
    'resned_ac_email',
    'contact_us',
    'google_login',
    'login',
    'register',
    'recover',
    'recoversms',
    'reset_password',
    'search',
    'get_search_filter',
    'update_announcement_views',
    'get_more_hashtag_posts',
    'open_album_lightbox',
    'get_next_album_image',
    'get_previous_album_image',
    'get_next_product_image',
    'get_previous_product_image',
    'open_multilightbox',
    'get_next_image',
    'get_previous_image',
    'get_next_video',
    'get_previous_video',
    'load-blogs',
    'load-recent-blogs',
    'get_no_posts_name',
    'search-blog-read',
    'search-blog',
    'coinbase',
    'load_more_products',
    'yoomoney',
    'iyzipay',
    'fluttewave',
    'fortumo',
    'aamarpay',
    'pay_with_bitcoin',
    'resend_two_factor',
    'cashfree',
);
if ($wo['config']['membership_system'] == 1) {
    $non_login_array[] = 'pro_register';
    $non_login_array[] = 'get_payment_method';
    $non_login_array[] = 'cashfree';
    $non_login_array[] = 'paystack';
    $non_login_array[] = 'pay_using_wallet';
    $non_login_array[] = 'get_paypal_url';
    $non_login_array[] = 'stripe_payment';
    $non_login_array[] = 'paypro_with_bitcoin';
    $non_login_array[] = '2checkout_pro';

    $non_login_array[] = 'bank_transfer';
    $non_login_array[] = 'stripe';
}
if (!in_array($f, $allow_array)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            exit("Restricted Area");
        }
    } else {
        exit("Restricted Area");
    }
}
if (!in_array($f, $non_login_array)) {
    if ($wo['loggedin'] == false && ($s != 'load_more_posts' && $s != 'filter_posts')) {
        if ($s != 'load-comments') {
            exit("Please login or signup to continue.");
        }
    }
}
if ($wo['loggedin'] && $wo['user']['banned'] == 1 && !in_array($f, $non_login_array)) {
    exit();
}






//BlueCrownR&D: WoW-PGB Add 'wow_payment' to allowed requests
if ($f == 'update_payment_settings' && Wo_CheckMainSession($hash_id) === true) {
    if (isset($_POST['wow_payment']) && in_array($_POST['wow_payment'], array('yes', 'no'))) {
        Wo_UpdateConfig('wow_payment', $_POST['wow_payment']);
    }
    if (isset($_POST['wow_store_url'])) {
        Wo_UpdateConfig('wow_store_url', $_POST['wow_store_url']);
    }
    // Add logic for wow_bridge_product_id
    if (isset($_POST['wow_market_id'])) {
        Wo_UpdateConfig('wow_market_id', $_POST['wow_market_id']);
    }

    if (isset($_POST['wow_pro_package_id'])) {
        Wo_UpdateConfig('wow_pro_package_id', $_POST['wow_pro_package_id']);
    }
    if (isset($_POST['wow_pro_package_id_2'])) {
        Wo_UpdateConfig('wow_pro_package_id_2', $_POST['wow_pro_package_id_2']);
    }
    if (isset($_POST['wow_pro_package_id_3'])) {
        Wo_UpdateConfig('wow_pro_package_id_3', $_POST['wow_pro_package_id_3']);
    }
    if (isset($_POST['wow_pro_package_id_4'])) {
        Wo_UpdateConfig('wow_pro_package_id_4', $_POST['wow_pro_package_id_4']);
    }
    
    if (isset($_POST['wow_wallet_topup_id'])) {
        Wo_UpdateConfig('wow_wallet_topup_id', $_POST['wow_wallet_topup_id']);
    }
    if (isset($_POST['wow_crowdfund_id'])) {
        Wo_UpdateConfig('wow_crowdfund_id', $_POST['wow_crowdfund_id']);
    }
    if (isset($_POST['wow_api_url'])) {
        Wo_UpdateConfig('wow_api_url', $_POST['wow_api_url']);
    }
    if (isset($_POST['wow_api_key'])) {
        Wo_UpdateConfig('wow_api_key', $_POST['wow_api_key']);
    }
    if (isset($_POST['wow_api_secret'])) {
        Wo_UpdateConfig('wow_api_secret', $_POST['wow_api_secret']);
    }
    if (isset($_POST['wow_webhook_secret'])) {
        Wo_UpdateConfig('wow_webhook_secret', $_POST['wow_webhook_secret']);
    }
}




$files = scandir('xhr');
unset($files[0]);
unset($files[1]);
if ($f != 'admin_setting' && $f != 'admincp') {
    if ($wo["loggedin"] && !empty($wo['user']) && $wo['user']['is_pro'] && !empty($wo["pro_packages"][$wo['user']['pro_type']]) && !empty($wo["pro_packages"][$wo['user']['pro_type']]['max_upload'])) {
        $wo['config']['maxUpload'] = $wo["pro_packages"][$wo['user']['pro_type']]['max_upload'];
    }
}
if (file_exists('xhr/' . $f . '.php') && in_array($f . '.php', $files)) {
    include 'xhr/' . $f . '.php';
} elseif (!empty($_GET['mode_type']) && in_array($_GET['mode_type'], array('linkedin', 'instagram'))) {
    include 'xhr/modes/' . Wo_Secure($_GET['mode_type']) . '.php';
}

//BlueCrownR&D: WoW-PGB- Add case for WooCommerce payment
if ($f == 'payment' && isset($_POST['payment_type']) && $_POST['payment_type'] === 'wow_payment') {
    require 'assets/wow-pgb/wow-pgb_init.php';
}

mysqli_close($sqlConnect);
unset($wo);
exit();