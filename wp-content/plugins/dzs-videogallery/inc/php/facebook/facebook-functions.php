<?php

function dzsvg_facebook_parseItems_facebookVideo($dzsvg, $videoSource) {


  $fout = '';


  $src = '';
  // -- todo: move to helper

  $arr = explode('/', $videoSource);

  $isFacebookVideoInline = false;


  $id = '';
  if ($arr[count($arr) - 1] == '') {
    $id = $arr[count($arr) - 2];
  } else {
    $id = $arr[count($arr) - 1];
  }


  // -- facebook parse
  $app_id = $dzsvg->mainoptions['facebook_app_id'];
  $app_secret = $dzsvg->mainoptions['facebook_app_secret'];
  $accessToken = $dzsvg->mainoptions['facebook_access_token'];


  if ($app_id && $app_secret && $accessToken) {

    $videoType = 'video';
    $facebookPath = DZSVG_PATH . 'class_parts/src/Facebook/autoload.php';
    require_once $facebookPath; // change path as needed


    $facebookArgs = array(
      'app_id' => $app_id,
      'app_secret' => $app_secret,
      'default_graph_version' => 'v2.3',
    );

    if ($dzsvg->mainoptions['facebook_default_access_token']) {
      $facebookArgs['default_access_token'] = $dzsvg->mainoptions['facebook_default_access_token'];
    }

    $fb = new Facebook\Facebook($facebookArgs);


    try {
      // Returns a `Facebook\FacebookResponse` object

      // we don't need thumbnails for now thumbnails,
      $response = $fb->get(
        '/' . $id . '/videos?fields=title,picture,description,source',
        $accessToken
      );


    } catch (Facebook\Exceptions\FacebookResponseException $e) {
      dzsvg_error_log('[dzsvg] Graph [while retrieving video] returned an error: ' . $e->getMessage()) ;
      $isFacebookVideoInline = true;

    } catch (Facebook\Exceptions\FacebookSDKException $e) {
      dzsvg_error_log('[dzsvg] Facebook SDK returned an error: ' . $e->getMessage()) ;
      $isFacebookVideoInline = true;

    }


    // -- facebook
    $fout .= ' data-sourcevp="' . $videoSource . '"';

  } else {
    $isFacebookVideoInline = true;
  }

  if ($isFacebookVideoInline) {

    // -- facebook iframe

    $videoType = 'inline';
    $videoSource = '<iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Ffacebook%2Fvideos%2F' . $id . '%2F&show_text=false&appId=998845010190473" width="100%" height="100%" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>';
  }

  return $fout;
}
function dzsvg_facebook_processGetAccessToken($dzsvgObject) {


  $app_id = $dzsvgObject->mainoptions['facebook_app_id'];
  $app_secret = $dzsvgObject->mainoptions['facebook_app_secret'];

  if ($app_id && function_exists('session_status')) {

    require_once DZSVG_PATH . 'class_parts/src/Facebook/autoload.php'; // change path as needed




    $facebookArgs = array(
      'app_id' => $app_id,
      'app_secret' => $app_secret,
      'default_graph_version' => 'v2.3',
    );

    if ($dzsvgObject->mainoptions['facebook_default_access_token']) {
      $facebookArgs['default_access_token'] = $dzsvgObject->mainoptions['facebook_default_access_token'];
    }


    $fb = new Facebook\Facebook($facebookArgs);

    foreach ($_COOKIE as $k => $v) {
      if (strpos($k, "FBRLH_") !== false) {
        $_SESSION[$k] = $v;
      }
    }


    $accessToken = '';

    $helper = $fb->getRedirectLoginHelper();

    if (isset($_GET['state'])) {
      $_SESSION['FBRLH_state'] = sanitize_key($_GET['state']);
    }


    try {

      // TODO: do we need redir_url ? $accessToken = $helper->getAccessToken($redir_url);
      $accessToken = $helper->getAccessToken();
      error_log('$accessToken - ' . print_r($accessToken, true));
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
      // When Graph returns an error
      echo '<div class="warning">Graph trying to access token.. returned an error: ' . $e->getMessage() . '</div>';

    } catch (Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues


      echo '<pre>redirect-from-facebook.php - Facebook 26 SDK returned an error: ' . $e->getMessage() . '...';

      echo '</pre>';

    }



    if(!is_string($accessToken)){

      $dzsvgObject->mainoptions['facebook_access_token'] = $accessToken->getValue();
      update_option($dzsvgObject->dboptionsname, $dzsvgObject->mainoptions);


      return $accessToken->getValue();
    }

  }


  return null;
}