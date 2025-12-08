<?php

if (!defined('ABSPATH')) // Or some other WordPress constant
  exit;








if (!function_exists('GetUserIDFromUsername')) {
  function GetUserIDFromUsername($username) {
    // For some reason, changing the user agent does expose the user's UID
    $options = array('http' => array('user_agent' => 'some_obscure_browser'));
    $context = stream_context_create($options);
    $fbsite = file_get_contents('https://www.facebook.com/' . $username, false, $context);

    // ID is exposed in some piece of JS code, so we'll just extract it
    $fbIDPattern = '/\"entity_id\":\"(\d+)\"/';
    if (!preg_match($fbIDPattern, $fbsite, $matches)) {
      throw new Exception('Unofficial API is broken or user not found');
    }
    return $matches[1];
  }

}


include_once(DZSVG_PATH.'inc/php/parse-media-apis/parse-youtube.php');
include_once(DZSVG_PATH.'inc/php/parse-media-apis/parse-vimeo.php');
include_once(DZSVG_PATH.'inc/php/parse-media-apis/parse-facebook.php');