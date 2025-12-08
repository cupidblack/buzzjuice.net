<?php

/**
 * [dzs_video source="https://localhost/wordpress/wp-content/uploads/2015/03/test.m4v" configs="minimalplayer" height="" type="video"]
 * @param array $atts
 * @param string $content
 * @return string|void
 */
function dzsvg_shortcode_player($atts = array(), $content = '') {
  // -- single video


  global $dzsvg;

  $dzsvg->slider_index++;

  $fout = '';


  $dzsvg->front_scripts();

  $margs = array(
    'width' => '100%', // -- the width , leave 100% for responsive
    'config' => '',  // -- player configuration name
    'height' => '300', // -- force a height
    'source' => '', // -- the mp4 source / youtube id / vimeo id
    'mediaid' => '', // -- link to a media element
    'sourceogg' => '', // the ogg source
    'autoplay' => 'off', // autoplay video
    'cuevideo' => 'on',  // autoload video
    'cover' => '',  // cover image
    'type' => 'video', // youtube / vimeo / video
    'cssid' => '', // force an id - leave blank preferably
    'single' => 'on', // leave on
    'loop' => 'off', // -- loop the video on ending
    'is_360' => 'off', // -- loop the video on ending
    'responsive_ratio' => 'off',
    'init_player' => 'on',// -- optional, will init the player if on
    'logo' => '', // -- optional logo for the video
    'link' => '', // -- a link where the
    'link_label' => esc_html__('Go to Link', DZSVG_ID),
    'logo_link' => '',
    'qualities' => '',
    'thumb' => '', // -- deprecated
    'thumbnail' => '', // -- thumbnail is the used one
    'playerid' => '',
    'init_on' => '',
    'extra_classes' => '', // leave blank
    'extra_classes_player' => '', // -- enter a extra css class for the player for example, entering "with-bottom-shadow" will create a shadow underneath the player
    'called_from' => 'from_shortcode_player',
    'title' => 'default', // -- title to appear on the left top
    'description' => 'default', // -- description to appear if the info button is enabled in video player configurations
    'autoplayWithVideoMuted' => 'off', // -- autoplay on mobile too with video muted
  );


  $player_index = $dzsvg->classView->index_players + 1;

  $default_margs = array_merge(array(), $margs);
  $margs = array_merge($margs, $atts);
  $margs['source'] = ClassDzsvgHelpers::sanitizeShortcodeAttrToWithoutLink($margs['source']);


  $embed_margs = array();
  // -- embed margs
  DzsvgView::generate_embedMargs($margs, $default_margs, $embed_margs);

  $original_margs = array_merge(array(), $margs);

  if ($margs['cssid'] == '') {
    $margs['cssid'] = 'vp' . ($player_index);
  }


  $video_post = null;

  foreach ($margs as $lab => $val) {
    $margs[$lab] = ClassDzsvgHelpers::sanitize_fromShortcodeAttr($margs[$lab]);
  }

  $lab = 'source';
  $margs[$lab] = ClassDzsvgHelpers::sanitize_forUrl($margs[$lab]);
  $lab = 'config';
  $margs[$lab] = ClassDzsvgHelpers::sanitize_forUrl($margs[$lab]);
  $lab = 'type';
  $margs[$lab] = ClassDzsvgHelpers::sanitize_forUrl($margs[$lab]);
  $lab = 'cover';
  $margs[$lab] = ClassDzsvgHelpers::sanitize_forUrl($margs[$lab]);
  $lab = 'qualities';
  if (isset($margs[$lab])) {

    $margs[$lab] = ClassDzsvgHelpers::sanitize_forUrl($margs[$lab]);
  }

  $lab = 'responsive_ratio';
  $margs[$lab] = ClassDzsvgHelpers::sanitize_forUrl($margs[$lab]);


  if ($margs['type'] == 'facebook') {


    $app_id = $dzsvg->mainoptions['facebook_app_id'];
    $app_secret = $dzsvg->mainoptions['facebook_app_secret'];


    $the_facebook_video_id = $margs['source'];
    $the_facebook_video_id_arr = array();
    if (strpos($margs['source'], '/')) {

      $the_facebook_video_id_arr = explode('/', $the_facebook_video_id);

      if ($the_facebook_video_id_arr[count($the_facebook_video_id_arr) - 1] == '') {
        $the_facebook_video_id = $the_facebook_video_id_arr[count($the_facebook_video_id_arr) - 2];
      } else {
        $the_facebook_video_id = $the_facebook_video_id_arr[count($the_facebook_video_id_arr) - 1];

      }
    }


    $posts = null;
    $response = null;

    if ($app_id && $app_secret) {

      require_once DZSVG_PATH . 'class_parts/src/Facebook/autoload.php'; // change path as needed

      $fb = new Facebook(array(
        'app_id' => $app_id,
        'app_secret' => $app_secret,
        'default_graph_version' => 'v2.10',
      ));


      $accessToken = $dzsvg->mainoptions['facebook_access_token'];

      $helper = $fb->getRedirectLoginHelper();

      if ($accessToken) {


        if (!isset($accessToken)) {
          if ($helper->getError()) {
            header('HTTP/1.0 401 Unauthorized');
            echo "Error: " . $helper->getError() . "\n";
            echo "Error Code: " . $helper->getErrorCode() . "\n";
            echo "Error Reason: " . $helper->getErrorReason() . "\n";
            echo "Error Description: " . $helper->getErrorDescription() . "\n";
          } else {
            echo 'Bad request';
          }
        }


        try {
          $response = $fb->get(
            '/' . $the_facebook_video_id . '?fields=source,embed_html',
            $accessToken
          );
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
          echo 'Graph returned an error: ' . $e->getMessage();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
        }
        $graphNode = $response->getGraphNode();


        if ($graphNode->getField('source')) {
          $margs['source'] = $graphNode->getField('source');
          $margs['type'] = 'normal';
        } else {
          if ($graphNode->getField('embed_html')) {
            echo $graphNode->getField('embed_html');
            return;
          }
        }

      }

    }
  }
  if ($margs['type'] == 'video' || $margs['type'] == 'normal') {

    if (is_numeric($margs['source'])) {


      $po = get_post($margs['source']);

      if ($po->post_type == DZSVG_POST_NAME) {

        $imgsrc = get_post_meta($margs['source'], 'dzsvp_featured_media', true);
      }

      if ($po->post_type == 'attachment') {
        $imgsrc = wp_get_attachment_url($margs['source']);
      }

      if ($margs['mediaid'] == '') {
        $margs['mediaid'] = $margs['source'];
      }
      $margs['source'] = $imgsrc;
    }
  }


  if ($margs['mediaid'] != '') {
    $auxpo = get_post($margs['mediaid']);
    if ($auxpo == false) {
      return '<div class="warning">Video does not exist anymore...</div>';
    } else {
      $video_post = $auxpo;
    }

    $post_id = $margs['mediaid'];

    if ($auxpo->post_type == 'attachment') {

      if ($margs['source'] == '') {

        $margs['source'] = $auxpo->guid;
      }
    }


    if ($auxpo->post_type == 'product' || $auxpo->post_type == DZSVG_POST_NAME) {

      if (get_post_meta($post_id, 'dzsvp_featured_media', true)) {

        if ($margs['source'] == '') {
          $margs['source'] = get_post_meta($post_id, 'dzsvp_featured_media', true);
        }
      }


      if ($video_post->post_content) {

        if ($margs['description'] == 'default') {

          $margs['description'] = $video_post->post_content;
        }
      }
    }

  }
  if (isset($margs['mp4']) && $margs['mp4']) {
    $margs['source'] = $margs['mp4'];
  }
  if (isset($margs['player']) && $margs['player'] != '') {
    $margs['config'] = $margs['player'];
  }

  if ($margs['title'] == 'default') {
    $margs['title'] = '';
    if (isset($margs['the_post_title']) && $margs['the_post_title']) {
      $margs['title'] = $margs['the_post_title'];
    }
  }
  if ($margs['description'] == 'default') {
    $margs['description'] = '';

    if ($content) {

      $margs['description'] = $content;
      $margs['striptags'] = 'off';
    }
  }


  $vpsettings = ClassDzsvgHelpers::getVideoPlayerConfig($margs);

  $its = array(0 => $margs,);
  $its = array_merge($its, $vpsettings);
  $its['playerConfigSettings'] = $vpsettings['settings'];


  unset($vpsettings['settings']['id']);


  $str_sourceogg = '';


  $str_cover = '';


  // -- generate embed code
  $embedCode = '';
  // -- for single player
  if (isset($vpsettings['settings']['enable_multisharer_button']) && $vpsettings['settings']['enable_multisharer_button'] == 'on') {
    // embed for player
    $embedCode = ClassDzsvgHelpers::generate_embedCode(array(
      'player_margs' => $embed_margs,
    ));
  }


  ClassDzsvgHelpers::player_fromMargsShortcodeToIts($margs, $its);


  if ($video_post) {
    $its[0]['video_post'] = $video_post;
  }

  if ((isset($vpsettings['settings']['use_custom_colors']) && $vpsettings['settings']['use_custom_colors'] == 'on')) {


    $fout .= ClassDzsvgHelpers::style_player('.vp' . $player_index, $vpsettings);
  }


  if (!(isset($margs['playerid']) && $margs['playerid'])) {

    $margs['playerid'] = ClassDzsvgHelpers::encode_toNumber($margs['source']);
  }


  $margs['called_from'] = 'from_shortcode_player';
  $margs['embed_code'] = $embedCode;


  if ($margs['init_player'] == 'on') {
    $margs['extra_classes'] .= ' is-single-video-player';
    $margs['extra_classes_player'] .= ' is-single-video-player';
  }


  $dzsSettingsArrayString = dzsvg_generate_audioplayer_settings(array(
    'call_from' => 'shortcode_player',
  ), $vpsettings, $its, $margs);

  if ($margs['init_player'] == 'on') {
    $margs['auto_init_player'] = 'on';
    $margs['auto_init_player_options'] = $dzsSettingsArrayString;
  }


  // -- player
  $fout .= $dzsvg->classView->parse_items($its, $margs);


  // -- self executing function closure


  // -- normal mode
  if ($margs['init_player'] == 'on') {

  }


  if ($margs['init_player'] == 'on' && $dzsvg->mainoptions['analytics_enable'] == 'on') {

    if (current_user_can('manage_options')) {

      $fout .= '<div class="extra-btns-con">';
      $fout .= '<span class="btn-zoomsounds stats-btn" data-playerid="' . $margs['playerid'] . '"><span class="the-icon"><i class="fa fa-tachometer" aria-hidden="true"></i></span><span class="btn-label">' . esc_html__('Stats', DZSVG_ID) . '</span></span>';
      $fout .= '</div>';


      ClassDzsvgHelpers::enqueueDzsVgShowcase();
    }


  }


  return $fout;

  // -- end shortcode_player()
}
