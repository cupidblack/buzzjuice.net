<?php


/**
 * @param $playlistSettingsMerged - its settings
 * @return array
 */
function dzsvg_generate_javascript_setting_for_playlist($playlistSettingsMerged) {


  global $dzsvg;
  $fout = '';
  $foutArr = array();


  if (isset($playlistSettingsMerged)) {

    $arrPlayerSettingsArray = include(DZSVG_PATH . 'configs/config-playlist-options.php');


    foreach ($arrPlayerSettingsArray as $key => $optArr) {

      $jsName = $key;
      $jsName = str_replace('dzsap_meta_', '', $jsName);
      if (isset($optArr['jsName']) && $optArr['jsName']) {
        $jsName = $optArr['jsName'];
      }


      $value = null;

      if (isset($playlistSettingsMerged[$key])) {
        $value = $playlistSettingsMerged[$key];
      }


      if ($key === 'skin_html5vg') {
        if (isset($value) == false || $value === 'skin-custom') {
          $value = 'skin-pro';
        }
        $value = str_replace('_', '-', $value);
      }


      if (isset($optArr['default']) && $value !== null && $value === $optArr['default']) {
        continue;
      }

      $isCanBeEmptyString = true;

      if (isset($optArr['canBeEmptyString']) && $optArr['canBeEmptyString'] === false) {
        $isCanBeEmptyString = false;
      }

      if ($isCanBeEmptyString === false && $value === '') {
        continue;
      }


      if ($value !== null) {
        $foutArr[$jsName] = $value;

        if ($fout) {
          $fout .= ',';
        }
        $fout .= '' . $jsName . ':"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';
      }
    }


    if ($dzsvg->mainoptions['navigation_view_easing_duration'] !== '' && $dzsvg->mainoptions['navigation_view_easing_duration'] != '20') {
      $jsName = 'navigation_viewAnimationDuration';
      $value = intval($dzsvg->mainoptions['navigation_view_easing_duration']);
      $foutArr[$jsName] = $value;
    };
  }

  return $foutArr;
}

/**
 * @param $videoPlayerSettingsMerged - vpsettings merged with prev func margs
 * @return string[]
 */
function dzsvg_generate_javascript_setting_for_player($videoPlayerSettingsMerged) {

  global $dzsvg;
  $fout = '';
  $foutArr = array();

  // -- some presanitize


  $lab = 'init_on';
  if (isset($videoPlayerSettingsMerged[$lab]) && $videoPlayerSettingsMerged[$lab] === '') {
    $videoPlayerSettingsMerged[$lab] = 'init';
  }

  $lab_deprecated = 'skin_html5vp';
  $lab_correct = 'design_skin';
  if (isset($videoPlayerSettingsMerged[$lab_deprecated]) && $videoPlayerSettingsMerged[$lab_deprecated]) {
    if (!(isset($videoPlayerSettingsMerged[$lab_correct]) && $videoPlayerSettingsMerged[$lab_correct])) {
      $videoPlayerSettingsMerged[$lab_correct] = $videoPlayerSettingsMerged[$lab_deprecated];
    }
  }


  if (isset($videoPlayerSettingsMerged)) {

    $arrPlayerSettingsArray = include(DZSVG_PATH . 'configs/config-player-options.php');


    foreach ($arrPlayerSettingsArray as $key => $optArr) {

      $jsName = $key;
      $jsName = str_replace('dzsap_meta_', '', $jsName);
      if (isset($optArr['jsName']) && $optArr['jsName']) {
        $jsName = $optArr['jsName'];
      }


      $value = null;

      if (isset($videoPlayerSettingsMerged[$key])) {
        $value = $videoPlayerSettingsMerged[$key];
      }


      if ($key == 'skinwave_wave_mode_canvas_waves_number' || $key == 'skinwave_wave_mode_canvas_waves_padding' || $key == 'skinwave_wave_mode_canvas_reflection_size') {
        if (!$value) {
          $value = $dzsvg->mainoptions[$key];
        }
      }


      if (isset($optArr['default']) && $value !== null && $value === $optArr['default']) {
        continue;
      }


      if ($value !== null) {
        $foutArr[$jsName] = $value;

        if ($fout) {
          $fout .= ',';
        }
        $fout .= '' . $jsName . ':"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';
      }
    }
  }


  $jsName = 'ad_show_markers';
  $value = 'on';
  $foutArr[$jsName] = $value;
  $fout .= '' . $jsName . ':"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';


  if ($dzsvg->mainoptions['videoplayer_end_exit_fullscreen'] == 'off') {
    $jsName = 'end_exit_fullscreen';
    $value = $dzsvg->mainoptions['videoplayer_end_exit_fullscreen'];
    $foutArr[$jsName] = $value;
    $fout .= '"' . $jsName . '":"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';
  }


  if ($dzsvg->mainoptions['analytics_enable'] == 'on') {

    $jsName = 'action_video_view';
    $value = 'wpdefault';
    $foutArr[$jsName] = $value;
    $fout .= '"' . $jsName . '":"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';

    $jsName = 'action_video_contor_60secs';
    $value = 'wpdefault';
    $foutArr[$jsName] = $value;
    $fout .= '"' . $jsName . '":"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';

  }


  if (isset($videoPlayerSettingsMerged['enable_quality_changer_button']) && $videoPlayerSettingsMerged['enable_quality_changer_button'] == 'on') {

    $jsName = 'settings_extrahtml_before_right_controls';
    $value = '<div class="dzsvg-player-button quality-selector show-only-when-multiple-qualities">{{svg_quality_icon}}<div class="dzsvg-tooltip">{{quality-options}}</div></div>';


    // todo: we do not need this here


  }

  if ($dzsvg->mainoptions['settings_trigger_resize'] == 'on') {


    $jsName = 'settings_trigger_resize';
    $value = '1000';
    $foutArr[$jsName] = $value;
    $fout .= '"' . $jsName . '":"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';
  };


  if (isset($videoPlayerSettingsMerged['youtube_defaultquality'])) {
    if ($videoPlayerSettingsMerged['youtube_defaultquality'] == 'hd') {

      $jsName = 'settings_suggestedQuality';
      $value = $videoPlayerSettingsMerged['youtube_hdquality'];
      $foutArr[$jsName] = $value;
      $fout .= '"' . $jsName . '":"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';


    }
    if ($videoPlayerSettingsMerged['youtube_defaultquality'] == 'sd') {
      $jsName = 'settings_suggestedQuality';
      $value = $videoPlayerSettingsMerged['youtube_sdquality'];
      $foutArr[$jsName] = $value;
      $fout .= '"' . $jsName . '":"' . ClassDzsvgHelpers::sanitize_for_javascript_double_quote_value($value) . '"';


    }

  }


  // -- for single player
  if (isset($videoPlayerSettingsMerged['embed_code']) && $videoPlayerSettingsMerged['embed_code']) {


    $jsName = 'embed_code';
    $value = $videoPlayerSettingsMerged['embed_code'];
    $foutArr[$jsName] = $value;
    $fout .= '"' . $jsName . '":\'' . $value . '\'';


  }


  if (isset($videoPlayerSettingsMerged['responsive_ratio']) && $videoPlayerSettingsMerged['responsive_ratio']) {


    $jsName = 'responsive_ratio';
    $value = $videoPlayerSettingsMerged['responsive_ratio'];
    $foutArr[$jsName] = $value;
    $fout .= '"' . $jsName . '":\'' . $value . '\'';


  }


  // -- end todo

  return array(
    'foutArr' => $foutArr,
    'fout' => $fout
  );
}


/**
 * @param array $pargs
 * @param array $vpsettings
 * @param array $its
 * @param array $prev_func_margs
 * @return array{string_dataOptions: string} $config
 * @type string  NAME      [description]
 * @type string string_dataOptions
 * { "optKey": "optValue" ... }
 */
function dzsvg_generatePlaylistSettings($pargs = array(), $itsSettings = array(), $prev_func_margs = array(), $its = array()) {

  $margs = array(
    'extra_classes' => 'search-align-right',
    'call_from' => 'default',
    'playerid' => '12345',
    'enc_margs' => '',
  );

  $string_dataOptions = '';

  if (!is_array($pargs)) {
    $pargs = array();
  }
  $margs = array_merge($margs, $pargs);


  $foutArr = array();


  $playlistId = 'default';
  $playlistSettingsArray = dzsvg_generate_javascript_setting_for_playlist(array_merge($itsSettings, $prev_func_margs));


  if ($prev_func_margs['settings_separation_mode'] == 'scroll' || $prev_func_margs['settings_separation_mode'] == 'button') {
    // todo: we need  || $prev_func_margs['settings_separation_mode'] == 'pages' ?

    $playlistSettingsArray['settings_separation_mode'] = $prev_func_margs['settings_separation_mode'];

    $settings_separation_pages = array();
    for ($i = 1; $i < (ceil(count($its) - 1) / intval($prev_func_margs['settings_separation_pages_number'])); $i++) {


      $newPageUrl = '' . site_url() . '/index.php?dzsvg_action=load_gallery_items_for_pagination&gallery_id=' . $prev_func_margs['id'] . '&dzsvg_settings_separation_paged=' . ($i + 1) . '&settings_separation_pages_number=' . $prev_func_margs['settings_separation_pages_number'] . '';

      array_push($settings_separation_pages, $newPageUrl);
    }


    $playlistSettingsArray['settings_separation_pages'] = $settings_separation_pages;


  }


  // -- only for walls
  if (($itsSettings['displaymode'] == 'videowall' || $itsSettings['displaymode'] == 'wall') && (isset($itsSettings['mode_wall_layout']) && $itsSettings['mode_wall_layout'] && $itsSettings['mode_wall_layout'] != 'none')) {


    if ($itsSettings['mode_wall_layout'] == 'default') {
      $itsSettings['mode_wall_layout'] = 'dzs-layout--3-cols';
    }
    if ($itsSettings['mode_wall_layout'] == 'layout-2-cols-15-margin') {
      $itsSettings['mode_wall_layout'] = 'dzs-layout--2-cols';
    }
    if ($itsSettings['mode_wall_layout'] == 'layout-3-cols-15-margin') {
      $itsSettings['mode_wall_layout'] = 'dzs-layout--3-cols';
    }
    if ($itsSettings['mode_wall_layout'] == 'layout-4-cols-10-margin') {
      $itsSettings['mode_wall_layout'] = 'dzs-layout--4-cols';
    }


    $playlistSettingsArray['extra_class_slider_con'] = $itsSettings['mode_wall_layout'];
    $playlistSettingsArray['nav_type_outer_grid'] = $itsSettings['mode_wall_layout'];

  }


  if (isset($itsSettings['nav_type_outer_max_height']) && $itsSettings['nav_type_outer_max_height']) {


    if (isset($itsSettings['nav_type']) && $itsSettings['nav_type'] == 'outer') {
      $playlistSettingsArray['nav_type_outer_max_height'] = $itsSettings['nav_type_outer_max_height'];

    }


    wp_enqueue_style('dzs.scroller', DZSVG_URL . 'libs/dzsscroller/scroller.css');
    wp_enqueue_script('dzs.scroller', DZSVG_URL . 'libs/dzsscroller/scroller.js');
  }


  global $dzsvg;
  if (isset($itsSettings['enable_search_field']) && $itsSettings['enable_search_field'] == 'on') {
    if ($prev_func_margs['customAttr__show_search_outside']) {
      $playlistSettingsArray['search_field_con'] = ".vg" . $dzsvg->sliders_index . "-search-field  input";
    }
  }


  if (isset($itsSettings['enableunderneathdescription']) && $itsSettings['enableunderneathdescription'] == 'on') {
    $itsSettings['enable_secondcon'] = 'off';
    $playlistSettingsArray['enable_secondcon'] = 'off';
    $playlistSettingsArray['settings_secondCon'] = '#as' . $dzsvg->sliders_index . '-secondcon';
  }


  if (isset($itsSettings['enable_search_field']) && $itsSettings['enable_search_field'] == 'on') {
    $playlistSettingsArray['search_field'] = 'on';
  }


  // -- old legacy code
  $socialFout = '';


  $slug = '';

  if (isset($itsSettings['slug'])) {
    $slug = $itsSettings['slug'];
  }
  if (isset($itsSettings['id'])) {
    $playlistId = $itsSettings['id'];
  }

  // -- gallery
  if (isset($itsSettings['embedbutton']) && $itsSettings['embedbutton'] == 'on') {
    // todo: we changed here..


    $extraCodeEmbed = '&id=' . $slug . '';

    if ($dzsvg->mainoptions['playlists_mode'] == 'legacy') {
      $extraCodeEmbed = '&id=' . $itsSettings['id'] . '&db=' . $dzsvg->currDb . '';
    }
    $socialFout = ClassDzsvgHelpers::generate_embedCode(array(
      'called_from' => 'gallery',
      'type' => 'gallery',
      'player_margs' => 'gallery',
      'extra_code' => $extraCodeEmbed,
    ));
    $playlistSettingsArray['embedCode'] = $socialFout;

  }


  if (isset($itsSettings['sharebutton']) && $itsSettings['sharebutton'] == 'on') {

  }


  if (isset($itsSettings['enable_secondcon']) && $itsSettings['enable_secondcon'] == 'on') {
    $playlistSettingsArray['settings_secondCon'] = ".dzsas-second-con-for-" . $playlistId;
  }
  if (isset($itsSettings['enable_outernav']) && $itsSettings['enable_outernav'] == 'on') {
    $playlistSettingsArray['embedCode'] = $socialFout;
    $playlistSettingsArray['settings_outerNav'] = ".videogallery--navigation-outer-for-" . $playlistId;
  }


  $playlistSettingsArray['videoplayersettings'] = $prev_func_margs['videoplayersettings'];


  $extraFeedOptions = array();
  if (isset($playlistSettingsArray['embedCode']) && $playlistSettingsArray['embedCode']) {

    $extraFeedOptions['feed-dzsvg--embedcode'] = $playlistSettingsArray['embedCode'];
    unset($playlistSettingsArray['embedCode']);
  }


  $string_dataOptions .= json_encode($playlistSettingsArray);


  $string_dataOptions = str_replace('\'', '`', $string_dataOptions);
  return array(
    'string_dataOptions' => $string_dataOptions,
    'extraFeedOptions' => $extraFeedOptions,
  );
}


/**
 * @param array $pargs
 * @param array $vpsettings
 * @param array $its
 * @param array $prev_func_margs
 * @return string a string with { "optKey": "optValue" ... }
 */
function dzsvg_generate_audioplayer_settings($pargs = array(), $vpsettings = array(), $its = array(), $prev_func_margs = array()) {
  // -- @call from shortcode_player

  $margs = array(
    'extra_classes' => 'search-align-right',
    'call_from' => 'default',
    'playerid' => '12345',
    'enc_margs' => '',
  );

  $fout = '';

  if (!is_array($pargs)) {
    $pargs = array();
  }
  $margs = array_merge($margs, $pargs);


  $player_id = $margs['playerid'];


  // -- shortcode
  if ($margs['call_from'] == 'shortcode_player') {


    $fout .= json_encode(dzsvg_generate_javascript_setting_for_player(array_merge($vpsettings['settings'], $prev_func_margs))['foutArr']);


  }

  return $fout;
}