<?php
require realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once __DIR__ . '/requests/wp_user_bridge.php';

$uri = $config->uri;
if (substr($uri, -1) == '/') {
    $uri = substr($uri, 0, -1);
}

// Connect to WordPress DB
$wpDb = get_wp_db();

global $db;

if (isset($_GET['code']) && !empty($_GET['code'])) {
    $app_id        = $config->wowonder_app_ID;
    $app_secret    = $config->wowonder_app_key;
    $wowonder_url  = $config->wowonder_domain_uri;
    $code          = Secure($_GET['code']);
    $url           = $wowonder_url . "/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}";
    $time          = time();
    
    /* BlueCrownR&D: Social Login & Registration Logic */
    // Create a stream context with SSL options
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $get = file_get_contents($url, false, $context);
    //$get           = file_get_contents($url);
    
    $wo_json_reply = json_decode($get, true);
    $access_token  = '';
    if (is_array($wo_json_reply) && isset($wo_json_reply['access_token'])) {
        $access_token    = $wo_json_reply['access_token'];
        $type            = "get_user_data";
        $url             = $wowonder_url . "/api_request?access_token={$access_token}&type={$type}&cache={$time}";
        
        /* BlueCrownR&D: Social Login & Registration Logic */
        $user_data_json  = file_get_contents($url, false, $context);
        //$user_data_json  = file_get_contents($url);
        $user_data_array = json_decode($user_data_json, true);

            // Debug: Print the JSON response
            /* echo '<pre>';
            print_r($user_data_array);
            echo '</pre>';
            exit(); // Stop further execution for debugging purposes
            */
            
        if (is_array($user_data_array) && !empty($user_data_array) && isset($user_data_array['user_data'])) {
            
            // Step 1: Obtain login credentials (from GET, POST, or OAuth callback)
            $user_data_login_or_email = $user_data_array['user_data'];
            $login_or_email = $user_data_login_or_email['email'];
            
            // Step 2: Fetch WordPress user by login or email
            $wp_user = wp_get_user_by_login_or_email($wpDb, $login_or_email);
            if (!$wp_user) {
                // User not found, handle registration or error
                echo "No WordPress user found for $login_or_email. <a href='$uri'>Return back</a>";
                exit();
            }
            
            //$user_data       = $user_data_array['user_data'];

            $user_data       = wp_get_full_user_data($wpDb, $wp_user['ID']);

            $wp_user_id  = $user_data['ID'] ?? '';
            $wo_user_id   = $user_data['meta']['wo_user_id'] ?? '';
            $username    = $user_data['xprofile']['username'] ?? $user_data['user_login'] ?? '';
            $user_email  = $user_data['xprofile']['email'] ?? $user_data['user_email'] ?? '';
            $password    = $user_data['meta']['password'] ?? '';
            $first_name  = $user_data['xprofile']['first_name'] ?? $user_data['meta']['first_name'] ?? '';
            $last_name   = $user_data['xprofile']['last_name'] ?? $user_data['meta']['last_name'] ?? '';
            $full_name   = $user_data['display_name'] ?? '';
            $gender      = $user_data['xprofile']['gender'] ?? $user_data['meta']['gender'] ?? 'male';
            $avatar      = $user_data['xprofile']['avatar'] ?? $user_data['meta']['avatar'] ?? $config->userDefaultAvatar;
            $cover       = $user_data['xprofile']['cover'] ?? $user_data['meta']['cover'] ?? '';
            $address     = $user_data['xprofile']['address'] ?? $user_data['meta']['address'] ?? '';
            $about       = $user_data['xprofile']['about'] ?? $user_data['meta']['about'] ?? '';
            $about_clean = preg_replace('/<br\s*\/?>/i', "\n", $about ?? '');
            $birthday    = $user_data['xprofile']['birthday'] ?? $user_data['meta']['birthday'] ?? '';
            $country     = $user_data['xprofile']['country_id'] ?? $user_data['meta']['country'] ?? '';
            $website     = $user_data['xprofile']['website'] ?? $user_data['meta']['website'] ?? '';
            $facebook    = $user_data['xprofile']['facebook'] ?? $user_data['meta']['facebook'] ?? '';
            $google      = $user_data['xprofile']['google'] ?? $user_data['meta']['google'] ?? '';
            $twitter     = $user_data['xprofile']['twitter'] ?? $user_data['meta']['twitter'] ?? '';
            $linkedin    = $user_data['xprofile']['linkedin'] ?? $user_data['meta']['linkedin'] ?? '';
            $youtube     = $user_data['xprofile']['youtube'] ?? $user_data['meta']['youtube'] ?? '';
            $vk          = $user_data['xprofile']['vk'] ?? $user_data['meta']['vk'] ?? '';
            $instagram   = $user_data['xprofile']['instagram'] ?? $user_data['meta']['instagram'] ?? '';
            $qq          = $user_data['xprofile']['qq'] ?? $user_data['meta']['qq'] ?? '';
            $wechat      = $user_data['xprofile']['wechat'] ?? $user_data['meta']['wechat'] ?? '';
            $discord     = $user_data['xprofile']['discord'] ?? $user_data['meta']['discord'] ?? '';
            $mailru      = $user_data['xprofile']['mailru'] ?? $user_data['meta']['mailru'] ?? '';
            $language    = $user_data['xprofile']['language'] ?? $user_data['meta']['language'] ?? 'english';
            $ip_address  = $user_data['meta']['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
            $verified    = $user_data['meta']['verified'] ?? 0;
            $lastseen    = $user_data['meta']['lastseen'] ?? time();
            $type        = $user_data['meta']['type'] ?? 'user';
            $active      = $user_data['meta']['active'] ?? 1;
            $admin       = $user_data['meta']['admin'] ?? 0;
            $registered  = $user_data['meta']['registered'] ?? $user_data['user_registered'] ?? date('Y-m-d H:i:s');
            $phone_number= $user_data['xprofile']['phone_number'] ?? $user_data['meta']['phone_number'] ?? '';
            $timezone    = $user_data['xprofile']['timezone'] ?? $user_data['meta']['timezone'] ?? '';
            $lat         = $user_data['meta']['lat'] ?? '';
            $lng         = $user_data['meta']['lng'] ?? '';
            $last_location_update = $user_data['meta']['last_location_update'] ?? '';
            $is_pro      = $user_data['meta']['is_pro'] ?? 0;
            $pro_time    = $user_data['meta']['pro_time'] ?? 0;
            $pro_type    = $user_data['meta']['pro_type'] ?? 0;
            $balance     = $user_data['meta']['balance'] ?? '0';
            $paypal_email= $user_data['meta']['paypal_email'] ?? '';
            $web_device_id = $user_data['meta']['web_device_id'] ?? '';
            $two_factor  = $user_data['meta']['two_factor'] ?? 0;
            $two_factor_verified = $user_data['meta']['two_factor_verified'] ?? 0;
            $new_email   = $user_data['xprofile']['new_email'] ?? $user_data['meta']['new_email'] ?? '';
            $new_phone   = $user_data['xprofile']['new_phone'] ?? $user_data['meta']['new_phone'] ?? '';
            $permission  = $user_data['meta']['permission'] ?? '';
            $info_file   = $user_data['xprofile']['info_file'] ?? $user_data['meta']['info_file'] ?? '';
            $city        = $user_data['xprofile']['city'] ?? $user_data['meta']['city'] ?? '';
            $state       = $user_data['xprofile']['state'] ?? $user_data['meta']['state'] ?? '';
            $zip         = $user_data['xprofile']['zip'] ?? $user_data['meta']['zip'] ?? '';
            $referrer    = $user_data['meta']['referrer'] ?? '';
            $confirm_followers = $user_data['meta']['confirm_followers'] ?? 0;
            $paystack_ref = $user_data['meta']['paystack_ref'] ?? '';


            $user = LoadEndPointResource('users');
            if( $user ){

                $dbEmail = $user->isEmailExists($user_email);
                $emailExist = false;
                if(isset($dbEmail['email']) && $dbEmail['email'] == $user_email){
                    $emailExist = true;
                }

                if ($emailExist) {
                
                    $update_query = $db->where('email', $user_email)->update('users', [
                        'is_pro' => $is_pro,
                        'pro_time' => $pro_time,
                        'pro_type' => $pro_type
                    ]);
                
                    if ($update_query) {
                        // Log the user into a session
                        

                        
                        $user->SetLoginWithSession($user_email);
                        header('Location: ' . $uri);
                        exit();
                    } else {
                        // Handle update failure (optional)
                        echo "Failed to update user subscription details.";
                        exit();
                    }
                } else {

                    if (!empty($user_data['avatar'])) {
                        $imported_image = $user->ImportImageFromLogin($user_data['avatar'], 1);
                    }
                    if (empty($imported_image)) {
                        $imported_image = $config->userDefaultAvatar;
                    }
                    $str            = md5(microtime());
                    /* BlueCrownR&D: Social Login & Registration Logic */
                    $id             = $username;
                    //$user_uniq_id   = ($user->isUsernameExists($id) === false) ? $id : $id . '_' . substr($str, 0, 4);
                    $user_uniq_id   = $id;
                    //$password   = rand(111111, 999999);
                    //$password_hash   = password_hash($password, PASSWORD_DEFAULT, array('cost' => 11));
                    $gender       = (isset($user_data['gender'])) ? $user_data['gender'] : 'male';
                    if($gender == 'male'){
                        $gender = 0;
                    }else{
                        $gender = 4526;
                    }
                    $re_data    = array(
                            'wp_user_id'        => isset($wp_user_id) ? Secure($wp_user_id, 0) : '',
                            'wow_user_id'       => Secure($wo_user_id, 0),
                            'username'          => Secure($username, 0),
                            'email'             => Secure($user_email, 0),
                            'password'          => Secure($password, 0),
                            'first_name'        => isset($first_name) ? Secure($first_name, 0) : '',
                            'last_name'         => isset($last_name) ? Secure($last_name, 0) : '',
                            'avatar'            => $imported_image, // Assume this is set properly from WoWonder's image
                            //'cover'             => isset($cover) ? Secure($cover, 0) : '',
                            'gender'            => isset($gender) ? Secure($gender, 0) : '',
                            'address'           => isset($address) ? Secure($address, 0) : '',
                            'about'             => Secure(stripslashes(html_entity_decode($about_clean)), 0),
                            'birthday'          => isset($birthday) ? Secure($birthday, 0) : '',
                            'country'           => isset($country) ? Secure($country, 0) : '',
                            'website'           => isset($website) ? Secure($website, 0) : '',
                        
                            'facebook'          => isset($facebook) ? Secure($facebook, 0) : '',
                            'google'            => isset($google) ? Secure($google, 0) : '',
                            'twitter'           => isset($twitter) ? Secure($twitter, 0) : '',
                            'linkedin'          => isset($linkedin) ? Secure($linkedin, 0) : '',
                            //'youtube'           => isset($youtube) ? Secure($youtube, 0) : '',
                            //'vk'                => isset($vk) ? Secure($vk, 0) : '',
                            'instagram'         => isset($instagram) ? Secure($instagram, 0) : '',
                            'qq'                => isset($qq) ? Secure($qq, 0) : '',
                            'wechat'            => isset($wechat) ? Secure($wechat, 0) : '',
                            'discord'           => isset($discord) ? Secure($discord, 0) : '',
                            'mailru'            => isset($mailru) ? Secure($mailru, 0) : '',
                        
                            'language'          => isset($language) ? Secure($language, 0) : 'english',
                            'ip_address'        => isset($ip_address) ? Secure($ip_address, 0) : '',
                            'verified'          => isset($verified) ? (int)$verified : 0,
                            'lastseen'          => time(),
                            'type'              => isset($type) ? Secure($type, 0) : 'user',
                            'active'            => '1',
                            //'admin'             => isset($admin) ? (int)$admin : 0,
                            'registered'        => isset($registered) ? Secure($registered, 0) : date('Y-m-d H:i:s'),
                        
                            'phone_number'      => isset($phone_number) ? Secure($phone_number, 0) : '',
                            'timezone'          => isset($timezone) ? Secure($timezone, 0) : '',
                            'lat'               => isset($lat) ? Secure($lat, 0) : '',
                            'lng'               => isset($lng) ? Secure($lng, 0) : '',
                            'last_location_update' => isset($last_location_update) ? Secure($last_location_update, 0) : '',
                            
                            'is_pro'            => isset($is_pro) ? (int)$is_pro : 0,
                            'pro_time'          => isset($pro_time) ? (int)$pro_time : 0,
                            'pro_type'          => isset($pro_type) ? (int)$pro_type : 0,
                            //'balance'           => isset($balance) ? Secure($balance, 0) : '0',
                            'paypal_email'      => isset($paypal_email) ? Secure($paypal_email, 0) : '',
                        
                            'web_device_id'     => isset($web_device_id) ? Secure($web_device_id, 0) : '',
                            'two_factor'        => isset($two_factor) ? (int)$two_factor : 0,
                            'two_factor_verified' => isset($two_factor_verified) ? (int)$two_factor_verified : 0,
                            'new_email'         => isset($new_email) ? Secure($new_email, 0) : '',
                            'new_phone'         => isset($new_phone) ? Secure($new_phone, 0) : '',
                            'permission'        => isset($permission) ? Secure($permission, 0) : '',
                        
                            'info_file'         => isset($info_file) ? Secure($info_file, 0) : '',
                            'city'              => isset($city) ? Secure($city, 0) : '',
                            'state'             => isset($state) ? Secure($state, 0) : '',
                            'zip'               => isset($zip) ? Secure($zip, 0) : '',
                            'referrer'          => isset($referrer) ? Secure($referrer, 0) : '',
                            'confirm_followers' => isset($confirm_followers) ? (int)$confirm_followers : 0,
                            'paystack_ref'      => isset($paystack_ref) ? Secure($paystack_ref, 0) : '',
                        
                            'src'               => 'wowonder',
                            'start_up'          => 0,
                            'social_login'      => 1
                    );

                    $regestered_user = $user->register($re_data);
                    if ($regestered_user['code'] == 200) {
                        

                        
                        $user->SetLoginWithSession($user_email);
                        $user_id = $regestered_user['userId'];
                        if (!empty($user_data['avatar']) && $imported_image != $config->userDefaultAvatar) {
                            $explode2  = @end(explode('.', $imported_image));
                            $explode3  = @explode('.', $imported_image);
                            $last_file = $explode3[0] . '_full.' . $explode2;
                            $compress  = CompressImage($imported_image, $last_file, 50);
                            if ($compress) {
                                $upload_s3 = UploadToS3($last_file);
                                Resize_Crop_Image($config->profile_picture_width_crop, $config->profile_picture_height_crop, $imported_image, $imported_image, $config->profile_picture_image_quality);
                                $upload_s3 = UploadToS3($imported_image);
                            }
                        }
                        $body = Emails::parse('social-login', array(
                            'first_name' => $re_data['first_name'] . ' ' . $re_data['last_name'],
                            'username' => $re_data['username'],
                            'password' => $password
                        ));
                        SendEmail($re_data['email'], $config->site_name . ' ' . __('Thank you for registering on Buzzjuice Social Connect!'), $body);
                        header('Location: ' . $uri . '/steps');
                        exit();
                    } else { var_dump($regestered_user); }

                }
            }else{
                var_dump($user);
            }

        }else{
            echo 'else';
            var_dump($user_data_array);
        }

    } else {
        echo __('Error found, please try again later.') . "<a href='" . $uri . "'>".__('Return back')."</a>";
    }
} else {
    echo "<a href='" . $uri . "'>".__('Return back')."</a>";
}
?>