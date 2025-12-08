<?php

ini_set('date.timezone', 'Europe/London');
date_default_timezone_set("Europe/London");


if (1) {
  ini_set("display_errors", 1);


  foreach ($_COOKIE as $k => $v) {
    if (strpos($k, "FBRLH_") !== FALSE) {
      $_SESSION[$k] = $v;
    }
  }


  $app_id = '';
  $app_secret = '';

  require_once(dirname(__FILE__) . '/src/Facebook/autoload.php');
  define('FACEBOOK_SDK_V4_SRC_DIR', dirname(__FILE__) . '/sdk/Facebook/');

  $fb = new Facebook\Facebook([
    'app_id' => $app_id,
    'app_secret' => $app_secret,
    'default_graph_version' => 'v2.4',
  ]);


  $accessToken = '';
  $helper = $fb->getRedirectLoginHelper();

  if (isset($_GET['state'])) {

    $_SESSION['FBRLH_state'] = sanitize_key($_GET['state']);
  }
  try {
    $accessToken = $helper->getAccessToken();
  } catch (Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'redirect-from-facebook.php - Facebook 26 SDK returned an error: ' . $e->getMessage();
    var_dump("__GET__", $_GET, "__SESSION__", $_SESSION);
    var_dump($helper->getPersistentDataHandler());
    var_dump($helper->getError());
    exit;
  }

  if (isset($accessToken)) {
    // Logged in!

    $_SESSION['facebook_access_token'] = (string)$accessToken;
    ?>

    <script>setTimeout(function () {
        window.location.href = 'index.php';
      }, 100) </script>
    <?php
  } elseif ($helper->getError()) {
    // The user denied the request
    header("Location:index.php?err=or");
    exit;
  }

  session_write_close();
}