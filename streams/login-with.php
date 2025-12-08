<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.wowonder.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com   
// +------------------------------------------------------------------------+
// | WoWonder - The Ultimate Social Networking Platform
// | Copyright (c) 2017 WoWonder. All rights reserved.
// +------------------------------------------------------------------------+
require_once('assets/init.php');
decryptConfigData();
$provider = "";
$types = array(
    'Google',
    'Facebook',
    'Twitter',
    'LinkedIn',
    'Vkontakte',
    'Instagram',
    'QQ',
    'WeChat',
    'Discord',
    'Mailru',
    'OkRu',
    'TikTok',
    'WordPress'
);

if (!empty($_GET['state']) && $_GET['state'] == 'OkRu' && !empty($_GET['code'])) {
    $_GET['provider'] = 'OkRu';
}

if (isset($_GET['provider']) && in_array($_GET['provider'], $types)) {
    $provider = Wo_Secure($_GET['provider']);
}

if (!empty($provider)) {

    if (!empty($_COOKIE['provider'])) {
        $_COOKIE['provider'] = '';
        unset($_COOKIE['provider']);
        setcookie('provider', '', -1);
        setcookie('provider', '', -1, '/');
    }
    setcookie('provider', $provider, time() + (60 * 60), '/');
}
else if(!empty($_COOKIE['provider']) && in_array($_COOKIE['provider'], $types)){
    
    $provider = Wo_Secure($_COOKIE['provider']);
}
if (!empty($provider) && $provider != 'OkRu') {
    require_once('assets/libraries/social-login/config.php');
    require_once('assets/libraries/social-login/vendor/autoload.php');
}

else if($provider == 'OkRu'){
    if (empty($_GET['code'])) {
        header("Location: https://connect.ok.ru/oauth/authorize?client_id=".$wo['config']['OkAppId']."&scope=VALUABLE_ACCESS&response_type=code&redirect_uri=".$wo['config']['site_url']."/login-with.php&layout=w&state=OkRu");
        exit();
    }
    require_once('assets/libraries/odnoklassniki_sdk.php');
}

use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;
if (isset($provider) && in_array($provider, $types)) {
    try {
        if ($provider == 'OkRu') {
            OdnoklassnikiSDK::SetOkInfo();
            if (!is_null(OdnoklassnikiSDK::getCode())){
                if(OdnoklassnikiSDK::changeCodeToToken(OdnoklassnikiSDK::getCode())){
                    $current_user = OdnoklassnikiSDK::makeRequest("users.getCurrentUser", null);
                    if (!empty($current_user)) {
                        $user_profile = ToObject($current_user);
                        $user_profile->identifier = $user_profile->uid;
                        $user_profile->lastName = $user_profile->last_name;
                        if (!empty($user_profile->pic_3)) {
                            $user_profile->photoURL = $user_profile->pic_3;
                        }
                        else if (!empty($user_profile->pic_2)) {
                            $user_profile->photoURL = $user_profile->pic_2;
                        }
                        else if (!empty($user_profile->pic_1)) {
                            $user_profile->photoURL = $user_profile->pic_1;
                        }
                    }
                    else{
                        echo " <b><a href='" . Wo_SeoLink('index.php?link1=welcome') . "'>Try again<a></b>";
                        exit();
                    }
                }
                else{
                    echo " <b><a href='" . Wo_SeoLink('index.php?link1=welcome') . "'>Try again<a></b>";
                    exit();
                }
            }
            else{
                echo " <b><a href='" . Wo_SeoLink('index.php?link1=welcome') . "'>Try again<a></b>";
                exit();
            }
        }
        else if ($provider == 'TikTok') {
            require_once('./assets/libraries/tiktok/src/Connector.php');
            $callback = $site_url_login . '/tiktok_callback';
            $_TK = new Connector($wo['config']['tiktok_client_key'], $wo['config']['tiktok_client_secret'], $callback);
            if (Connector::receivingResponse()) { 
                try {
                    $token = $_TK->verifyCode($_GET[Connector::CODE_PARAM]);
                    // Your logic to store the access token
                    $user_profile = $_TK->getUser();
                    $user_profile->identifier = $user_profile->union_id;
                    $user_profile->displayName = $user_profile->display_name;
                    $user_profile->firstName = $user_profile->display_name;
                    $user_profile->email = '';
                    $user_profile->profileURL = '';
                    $user_profile->lastName = '';
                    $user_profile->photoURL = $user_profile->avatar_larger;
                    $user_profile->description = '';
                    $user_profile->gender = '';
                    // Your logic to manage the User info
                    //$videos = $_TK->getUserVideoPages();
                    // Your logic to manage the Video info
                } catch (Exception $e) {
                    echo "Error: ".$e->getMessage();
                    echo '<br /><a href="'.$_TK->getRedirect().'">Retry</a>';
                    exit();
                }
            } else {
                header("Location: " . $_TK->getRedirect());
                exit();
            }
        }
        else{
            $hybridauth = new Hybridauth( $LoginWithConfig );


            $authProvider = $hybridauth->authenticate($provider);
            $tokens = $authProvider->getAccessToken();
            $user_profile = $authProvider->getUserProfile();


        }

        if ($user_profile && isset($user_profile->identifier)) {
            $name = $user_profile->firstName;
            $notfound_email = 'unknown_'; // Default prefix for unknown providers
            $notfound_email_com = '@unknown.com'; // Default email domain

            if ($provider == 'Google') {
                $notfound_email     = 'go_';
                $notfound_email_com = '@google.com';
            } else if ($provider == 'Facebook') {
                $notfound_email     = 'fa_';
                $notfound_email_com = '@facebook.com';
            } else if ($provider == 'Twitter') {
                $notfound_email     = 'tw_';
                $notfound_email_com = '@twitter.com';
            } else if ($provider == 'LinkedIn') {
                $notfound_email     = 'li_';
                $notfound_email_com = '@linkedIn.com';
            } else if ($provider == 'Vkontakte') {
                $notfound_email     = 'vk_';
                $notfound_email_com = '@vk.com';
            } else if ($provider == 'Instagram') {
                $notfound_email     = 'in_';
                $notfound_email_com = '@instagram.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'QQ') {
                $notfound_email     = 'qq_';
                $notfound_email_com = '@qq.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'WeChat') {
                $notfound_email     = 'wechat_';
                $notfound_email_com = '@wechat.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'Discord') {
                $notfound_email     = 'discord_';
                $notfound_email_com = '@discord.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'Mailru') {
                $notfound_email     = 'mailru_';
                $notfound_email_com = '@mailru.com';
                $name = $user_profile->displayName;
            } else if ($provider == 'OkRu') {
                $notfound_email     = 'okru_';
                $notfound_email_com = '@okru.com';
                $name = $user_profile->first_name;
            }
            $user_name  = $notfound_email . $user_profile->identifier;
            $user_email = $user_name . $notfound_email_com;
            if (!empty($user_profile->email)) {
                $user_email = $user_profile->email;
                if(empty($user_profile->emailVerified) && $provider == 'Discord') {
                    exit("Your E-mail is not verfied on Discord.");
                }
            }
            if (Wo_IsBanned($user_email)) {
                exit($wo['lang']['email_is_banned']);
            }
            if (Wo_EmailExists($user_email) === true) {
                Wo_SetLoginWithSession($user_email);
                header("Location: " . $config['site_url']);
                exit();
            } else {
                $social_url = '';
                if (!empty($user_profile->profileURL)) {
                    $social_url = substr($user_profile->profileURL, strrpos($user_profile->profileURL, '/') + 1);
                }

                $str = md5(microtime() ?: 'fallback_string');
                $id = substr($str, 0, 9);
                $user_uniq_id = (Wo_UserExists($id) === false) ? $id : 'u_' . $id;
                $imported_image = Wo_ImportImageFromLogin($user_profile->photoURL, 1);
                if (empty($imported_image)) {
                    $imported_image = $wo['userDefaultAvatar'];
                }
                $password = rand(1111, 9999);
                $re_data      = array(
                    'username' => Wo_Secure($user_uniq_id, 0),
                    'email' => Wo_Secure($user_email, 0),
                    'password' => Wo_Secure(md5((string) $password), 0),
                    'email_code' => Wo_Secure(md5(rand(1111, 9999) . time()), 0),
                    'first_name' => Wo_Secure($name),
                    'last_name' => Wo_Secure($user_profile->lastName),
                    'avatar' => Wo_Secure($imported_image),
                    'src' => Wo_Secure($provider),
                    'startup_image' => 1,
                    'lastseen' => time(),
                    'social_login' => 1,
                    'active' => '1'
                );
                if ($provider == 'Google') {
                    $re_data['about']  = Wo_Secure($user_profile->description);
                    $re_data['google'] = Wo_Secure($social_url);
                }
                if ($provider == 'Facebook') {
                    $fa_social_url       = @explode('/', $user_profile->profileURL);
                    if (!empty($fa_social_url[4])) {
                        $re_data['facebook'] = Wo_Secure($fa_social_url[4]);
                    }
                    $re_data['gender'] = 'male';
                    if (!empty($user_profile->gender)) {
                        if ($user_profile->gender == 'male') {
                            $re_data['gender'] = 'male';
                        } else if ($user_profile->gender == 'female') {
                            $re_data['gender'] = 'female';
                        }
                    }
                }
                if ($provider == 'Twitter') {
                    $re_data['twitter'] = Wo_Secure($social_url);
                }
                if ($provider == 'LinkedIn') {
                    $re_data['about']    = Wo_Secure($user_profile->description);
                    $re_data['linkedIn'] = Wo_Secure($social_url);
                }
                if ($provider == 'Vkontakte') {
                    $re_data['about'] = Wo_Secure($user_profile->description);
                    $re_data['vk']    = Wo_Secure($social_url);
                }
                if ($provider == 'Instagram') {
                    $re_data['instagram']   = Wo_Secure($user_profile->username);
                }
                if ($provider == 'QQ') {
                    $re_data['qq']   = Wo_Secure($social_url);
                }
                if ($provider == 'WeChat') {
                    $re_data['wechat']   = Wo_Secure($social_url);
                }
                if ($provider == 'Discord') {
                    $re_data['discord']   = Wo_Secure($social_url);
                }
                if ($provider == 'Mailru') {
                    $re_data['mailru']   = Wo_Secure($social_url);
                }
                if ($provider == 'OkRu') {
                    $re_data['okru']   = Wo_Secure($user_profile->uid);
                }
                if (!empty($_SESSION['ref']) && $wo['config']['affiliate_type'] == 0) {
                    $ref_user_id = Wo_UserIdFromUsername($_SESSION['ref']);
                    if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                        $re_data['referrer'] = Wo_Secure($ref_user_id);
                        $re_data['src']      = Wo_Secure('Referrer');
                        if ($wo['config']['affiliate_level'] < 2) {
                            $update_balance      = Wo_UpdateBalance($ref_user_id, $wo['config']['amount_ref']);
                        }
                        unset($_SESSION['ref']);
                    }
                }
                $wo['config']['user_registration'] = 1;
                if (preg_match_all('~@(.*?)(.*)~', $user_email, $matches) && !empty($matches[2]) && !empty($matches[2][0]) && Wo_IsBanned($matches[2][0])) {
                    die($wo['lang']['email_provider_banned']);
                }
                if (Wo_RegisterUser($re_data) === true) {
                    // Step 1: Set session for the newly registered WoWonder user
                    Wo_SetLoginWithSession($user_email);
                
                    // Step 2: Get the WoWonder user ID
                    $user_id = Wo_UserIdFromEmail($user_email);
                
                    // Step 3: Retrieve the WordPress user ID, username, and password
                    $wordpress_user_id = !empty($user_profile->identifier) ? $user_profile->identifier : null;
                    $wordpress_username = !empty($user_profile->displayName) ? $user_profile->displayName : null;
                    $wordpress_password = !empty($user_profile->password) ? $user_profile->password : null; // Assuming the password is available in $user_profile
                
                    if (!empty($wordpress_user_id) && !empty($user_id)) {
                        // Step 4: Ensure the username is unique in WoWonder
                        $wowonder_username = Wo_Secure($wordpress_username, 0);
                        if (Wo_UserExists($wowonder_username)) {
                            $str = md5(microtime());
                            $wowonder_username .= '_' . substr($str, 0, 4); // Append a unique substring
                        }
                        // Ensure that $wordpress_password is not null before hashing
                        $hashed_password = !empty($wordpress_password) ? password_hash($wordpress_password, PASSWORD_DEFAULT) : '';
                        // Step 5: Update the 'wp_user_id', 'username', and 'password' fields in the $wo_users table
                        $update_query = mysqli_query(
                            $sqlConnect,
                            "UPDATE " . T_USERS . " 
                             SET wp_user_id = '" . Wo_Secure($wordpress_user_id, 0) . "', 
                                 username = '" . Wo_Secure($wowonder_username, 0) . "', 
                                 password = '" . Wo_Secure($hashed_password, 0) . "' 
                             WHERE user_id = " . Wo_Secure($user_id, 0)
                        );
                        if ($update_query) {
                            error_log("✅ (login-with.php) Successfully linked WordPress user ID ($wordpress_user_id), set WoWonder username ($wowonder_username), and updated password for WoWonder user ID ($user_id).");
                        } else {
                            error_log("❌ Failed to update wp_user_id, username, or password for WoWonder user ID ($user_id).");
                        }
                    } else {
                        error_log("❌ Missing WordPress user ID or WoWonder user ID. Cannot update wp_user_id, username, or password.");
                    }
                
                    // Step 6: Handle referrer logic
                    if (!empty($re_data['referrer']) && is_numeric($wo['config']['affiliate_level']) && $wo['config']['affiliate_level'] > 1) {
                        AddNewRef($re_data['referrer'], $user_id, $wo['config']['amount_ref']);
                    }
                
                    // Step 7: Handle auto-follow, auto-page-like, and auto-group-join
                    if (!empty($wo['config']['auto_friend_users'])) {
                        Wo_AutoFollow($user_id);
                    }
                    if (!empty($wo['config']['auto_page_like'])) {
                        Wo_AutoPageLike($user_id);
                    }
                    if (!empty($wo['config']['auto_group_join'])) {
                        Wo_AutoGroupJoin($user_id);
                    }
                
                    // Step 8: Process the user's profile picture
                    if (!empty($user_profile->photoURL) && $imported_image != $wo['userDefaultAvatar'] && $imported_image != $wo['userDefaultFAvatar']) {
                        $explode2 = @end(explode('.', $imported_image));
                        $explode3 = @explode('.', $imported_image);
                        $last_file = $explode3[0] . '_full.' . $explode2;
                        $compress = Wo_CompressImage($imported_image, $last_file, $wo['config']['images_quality']);
                        if ($compress) {
                            Wo_UploadToS3($last_file);
                            $query = mysqli_query($sqlConnect, "INSERT INTO " . T_POSTS . " (`user_id`, `postFile`, `time`, `postType`, `postPrivacy`) VALUES ('$user_id', '" . Wo_Secure($last_file) . "', '" . Wo_Secure(time()) . "', 'profile_picture_deleted', '0')");
                            $sql_id = mysqli_insert_id($sqlConnect);
                            $sql_id = Wo_Secure($sql_id);
                            mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `post_id` = '$sql_id' WHERE `id` = '$sql_id'");
                            Wo_Resize_Crop_Image($wo['profile_picture_width_crop'], $wo['profile_picture_height_crop'], $imported_image, $imported_image, $wo['profile_picture_image_quality']);
                            Wo_UploadToS3($imported_image);
                        }
                    }
                
                    // Step 9: Send a welcome email
                    /*$wo['user'] = $re_data;
                    $wo['pass'] = $password;
                    $body = Wo_LoadPage('emails/login-with');
                    $headers = "From: " . $config['siteName'] . " <" . $config['siteEmail'] . ">\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    @mail($re_data['email'], 'Thank you for your registration.', $body, $headers);
                    */
                
                    // Step 10: Redirect the user to the start-up page
                    header("Location: " . Wo_SeoLink('index.php?link1=start-up'));
                    exit();
                }
            }
        }
    }
    catch (Exception $e) {
        echo $e->getMessage();
        echo " <b><a href='" . Wo_SeoLink('index.php?link1=welcome') . "'>Try again<a></b>";
    }
} else {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}