<?php
include_once(DZSVG_PATH . 'inc/php/view/playlist/playlist-functions.php');
include_once(DZSVG_PATH . 'inc/php/view/playlist/playlist-utils.php');


function dzsvg_shortcode_videogallery__getOutput($margs = array(), $content = null, $dzsvg = null, $opts = array()) {


  $mainOptions = array_merge(array(
    'extra_styling' => '',
    'totalWidth' => '',
    'totalWidthForCss' => '',
    'totalHeightForCss' => '',
    'playlistSettings' => array(),
    'vpsettings' => array(),
    'playlistData' => array(),
  ), $opts);

  $extra_styling = $mainOptions['extra_styling'];
  $vpsettings = $mainOptions['vpsettings'];
  $playlistSettings = $mainOptions['playlistSettings'];
  $totalWidth = $mainOptions['totalWidth'];
  $totalWidthForCss = $mainOptions['totalWidthForCss'];
  $totalHeightForCss = $mainOptions['totalHeightForCss'];
  $playlistData = $mainOptions['playlistData'];

  $fout = '';
  $iout = ''; //items parse

  if ($extra_styling) {
    $fout .= '<style class="dzsvg-skin-custom-styling">' . esc_html($extra_styling);
    $fout .= '</style>';
  }


  if ($vpsettings['settings']['skin_html5vp'] == 'skin_custom' || $vpsettings['settings']['skin_html5vp'] == 'skin_custom_aurora' || (isset($vpsettings['settings']['use_custom_colors']) && $vpsettings['settings']['use_custom_colors'] == 'on')) {
    $fout .= ClassDzsvgHelpers::style_player('.vg' . $dzsvg->sliders_index, $vpsettings);
  }


  // -- .precon start
  if ($margs['output_container'] == 'on') {
    $fout .= dzsvg_view_playlistGenerateHtmlContainerStart($margs, $dzsvg, $playlistSettings, $totalWidth);
  }


  $idForClass = dzsvg_view_playlist_getIdForClass($playlistSettings, $margs);


  $viewGalleryId = DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($idForClass);


  $errorsFout = implode('', $dzsvg->arr_api_errors);

  if ($errorsFout) {
    $fout .= $errorsFout;
  }
  $viewPlaylistMenuPosition = 'right';

  if (isset($playlistSettings['menuposition'])) {
    $viewPlaylistMenuPosition = $playlistSettings['menuposition'];
  }
  $isViewSearchOutside = false;


  // -- search field
  if (isset($playlistSettings['enable_search_field']) && $playlistSettings['enable_search_field'] == 'on') {
    $searchFieldOutput = dzsvg_view_getOutsideSearchField($playlistSettings, $viewPlaylistMenuPosition, $dzsvg);

    if ($searchFieldOutput) {
      $fout .= $searchFieldOutput;
      $isViewSearchOutside = true;
    }
  }
  // -- search field END


  $sanitizedApConfigId = ClassDzsvgHelpers::sanitizeToPhpId($playlistData['playerConfigSettings']['vpconfig_id']);


  $transition = 'fade';

  if (isset($playlistSettings['transition']) && $playlistSettings['transition']) {
    $transition = $playlistSettings['transition'];
  }

  $string_navigationSkin = dzsvg_view_getNavigationSkin($playlistSettings);


  $playerSettingsFromGallery = dzsvg_playlist_getPlayerSettingsFromGallery($dzsvg, $playlistSettings);

  $convertedMargs = $margs;
  $convertedMargs['randomise'] = 'off';
  $convertedMargs['settings_menu_overlay'] = 'on';
  $convertedMargs['customAttr__show_search_outside'] = $isViewSearchOutside;
  $convertedMargs['customAttr__css_classid'] = $viewGalleryId;

  if ($dzsvg->mainoptions['settings_trigger_resize'] == 'on') {
    $convertedMargs['settings_trigger_resize'] = '1000';

  };

  if ($dzsvg->mainoptions['loop_playlist'] == 'off') {
    $convertedMargs['loop_playlist'] = $dzsvg->mainoptions['loop_playlist'];

  }
  $convertedMargs['videoplayersettings'] = $sanitizedApConfigId;


  $dataOptionsArr = dzsvg_generatePlaylistSettings(array(), $playlistSettings, $convertedMargs, $playlistData);
  $playlistInitOptions = $dataOptionsArr['string_dataOptions'];


  $audioplayerSettingsMerged = array_merge($playlistData['playerConfigSettings'], $playerSettingsFromGallery);
  $playlistItems = $playlistData;

  unset($playlistItems['playerConfigSettings']);
  unset($playlistItems['videoPlayerConfig']);
  unset($playlistItems['settings']);


  $dzsvg->vpConfigsFrontend[$sanitizedApConfigId] = dzsvg_generate_javascript_setting_for_player($audioplayerSettingsMerged)['foutArr'];


  // -- start output content
  if ($margs['output_container'] == 'on') {

    $fout .= dzsvg_view_playlistGenerateHtmlConStart($margs, $dzsvg, $playlistSettings, $transition, $viewGalleryId, $string_navigationSkin, $totalHeightForCss, $playlistInitOptions);
  }


  $iout .= $dzsvg->classView->parse_items($playlistData, $margs);

  $fout .= $iout;


	$warningsArray = array();
  if ($iout == '') {
    $warningsArray[] = array(
      'warning_type' => 'warning',
      'warning_content' => esc_html__("Gallery", DZSVG_ID) . ' <strong>' . $margs['id'] . '</strong> ' . esc_html__("does not seem to have any videos.", DZSVG_ID),
    );
  }

  if ($content) {

    // -- sanitize..
    $content = str_replace('&#8221;', '"', $content);
    $content = str_replace('&#8217;', '"', $content);
    $content = str_replace('&#8243;', '"', $content);
    $fout .= do_shortcode(($content));

  }


  if ($margs['output_container'] == 'on') {
    $fout .= '</div><!-- end .items-->'; // -- end .items
  }


  foreach ($dataOptionsArr['extraFeedOptions'] as $key => $val) {
    $fout .= '<script type="text/html" hidden class=" feed-dzsvg ' . $key . '">' . $val . '</script>';
  }

  $socialCode = '';

  if (isset($playlistSettings['sharebutton'])) {
    $socialCode = $dzsvg->classView->generateShareCode($playlistSettings['sharebutton'], $playlistSettings);
  }

  if ($socialCode) {
    $fout .= '<div hidden class="feed-dzsvg feed-dzsvg--socialCode">' . $socialCode . '</div>';
  }

  if ($margs['output_container'] == 'on') {
    $fout .= '</div> <!-- end .videogallery -->';// --
  }


  $script_final = '';
  if ($margs['call_script'] == 'on') {
    $dzsvg->script_footer_root .= '';


    // -- end custom settings


    $dzsvg->script_footer_root .= '';


    if ($playlistSettings['displaymode'] == 'wall') {
      dzsvg_view_modeWallPrepare($dzsvg, $sanitizedApConfigId);
    }


    // --- here we had a jq ready call


    // -- end vpsettings


    $options_array_string = '';


    if (isset($playlistSettings['action_playlist_end']) && $playlistSettings['action_playlist_end']) {
      $script_final .= dzsvg_view_playlistGenerateScriptForNext($playlistSettings, $viewGalleryId);
    }


    if ($margs['init_from'] == '' || $margs['init_from'] == 'normal') {
      $dzsvg->script_footer .= $script_final;
    }
    if ($margs['init_from'] == 'ultibox') {
      $fout .= '<div class="dzs--to-execute toexecute to-execute--from-ultibox">' . $dzsvg->script_footer_root . $script_final . '</div>';
    }
  }

  $fout .= '<div class="clear"></div>';

  if ($margs['settings_separation_mode'] == 'pages') {
    global $post;
    $fout .= dzsvg_view_getPaginationOutput($playlistItems, $margs, $post);
  }

  $fout .= '</div>';
  // --------
  // -- END gallery-precon


  if (isset($playlistSettings['enableunderneathdescription']) && $playlistSettings['enableunderneathdescription'] == 'on') {

    $fout .= dzsvg_view_generateSecondConForUnderneathGallery($dzsvg->sliders_index, $playlistData);
  }


  if ($playlistSettings['displaymode'] == 'wall') {
    wp_enqueue_script('jquery.masonry', DZSVG_URL . "assets/masonry/jquery.masonry.min.js");


    ClassDzsvgHelpers::enqueueUltibox();
  }


  // -- alternatewall
  // ---- mode alternatewall


  if ($playlistSettings['displaymode'] == 'alternatewall') {
    include_once DZSVG_PATH . 'inc/php/view/playlist/modes/displaymode-alternatewall.php';
    dzsvg_view_displayMode_alternatewall_output($dzsvg, $playlistData, $margs);
    return $fout;
  }


  // -- alternate menu
  if ($playlistSettings['displaymode'] == 'alternatemenu') {
    include_once(DZSVG_PATH . 'inc/php/deprecated/alternate-menu-functions.php');
    $fout = dzsvg_altenate_menu_output($playlistData);
  }

  dzsvg_debug_getDebugInfo($dzsvg, $fout);


  if ($dzsvg->mainoptions['analytics_enable'] == 'on') {
    $fout .= ClassDzsvgHelpers::addAnalyticsButtonPlaylist();
  }

  ClassDzsvgHelpers::enqueueDzsVgPlaylist();

  $fout .= dzs_parseWarningsArrayToHtml($warningsArray);

  if ($margs['return_mode'] == 'parsed items') {
    $iout = str_replace('https://img.youtube.com', '//img.youtube.com', $iout);
    return $iout;
  }

  $fout = str_replace('https://img.youtube.com', '//img.youtube.com', $fout);
  return $fout;

}

/**
 * Main gallery output from playlist admin
 *
 * @param array $pargs gallery options
 * @return mixed output shortcode
 */
function dzsvg_shortcode_videogallery($pargs = array(), $content = null) {

  // -- main shortcode
  global $post;
  global $dzsvg;


  $margs = array(
    'id' => 'default',
    'css_number_id' => 'default',
    'original_id' => 'default',
    'slider' => 'default',
    'extra_classes' => ' ',
    'db' => '',
    'called_from' => 'default',
    'category' => '',
    'fullscreen' => 'off',
    'output_container' => 'on',
    'call_script' => 'on',
    'init_from' => 'normal',
    'its' => '',   // -- force $its array
    'overwrite_only_its' => '',  // -- force $its array
    'settings_separation_mode' => 'normal',  // -- normal ( no pagination ) or "pages" or "scroll" or "button"
    'settings_separation_pages_number' => '5',//-- the number of items per 'page'
    'settings_separation_paged' => '0',//-- the page number
    'return_mode' => 'normal' // -- "normal" returns the whole gallery, "items" returns the items array, "parsed items" returns the parsed items ( for pagination for example )
  );

  if ($pargs == '') {
    $pargs = array();
  }

  $fout = '';
  $margs = array_merge($margs, $pargs);


  if ($margs['called_from'] == 'gutenberg_playlist_render()') {

  }

  $margs['original_id'] = $margs['id'];

  $margs['id'] = dzsvg_view_playlist_get_id($margs, $dzsvg);

  if (isset($_GET[DZSVG_PLAYLIST_PAGINATION_QUERY_ARG])) {
    $margs[DZSVG_PLAYLIST_PAGINATION_QUERY_ARG_SHORT] = sanitize_text_field($_GET[DZSVG_PLAYLIST_PAGINATION_QUERY_ARG]);
  }

  $extra_galleries = array();


  if ($dzsvg->mainoptions['playlists_mode'] == 'legacy') {
    dzsvg_view_legacy_set_current_items($margs);
  }


  $dzsvg->front_scripts();


  if ($margs['return_mode'] == 'normal') {
    $dzsvg->sliders_index++;
  }


  if ($margs['css_number_id'] == '' || $margs['css_number_id'] == 'default') {
    $margs['css_number_id'] = $dzsvg->sliders_index;
  }

  $i = 0;
  $k = 0;
  $id = 'default';
  if (isset($margs['id'])) {
    $id = $margs['id'];
  }


  $playlistData = array();
  // ---- extra galleries code


  $selected_term_id = '';

  $term_meta = array();

  // -- if we have its forced on us we don't need to search them again

  $reference_term_slug = '';


  $plData = dzsvg_view_getPlaylistData($margs);
  $itsForPlaylist = $plData['itsForPlaylist'];
  $playlistData = $plData['playlistData'];

  if ($dzsvg->mainoptions['playlists_mode'] == 'normal' && $itsForPlaylist) {
    $term_meta = $itsForPlaylist['term_meta'];
  }

  if ($margs['id'] == 'default_video_playlist') {
    $ids_arr = explode(',', $margs['ids']);

    foreach ($playlistData as $lab => $val) {
      if ($lab !== 'settings') {
        unset($playlistData[$lab]);
      }
    }

    foreach ($ids_arr as $ida) {

      $po = get_post($ida);
      $por = ClassDzsvgHelpers::sanitize_to_gallery_item($po);
      array_push($playlistData, $por);
    }
  }


  $playlistData['settings'] = dzsvg_view_getPlaylistDataSettings($playlistData, $selected_term_id, $dzsvg->mainoptions['playlists_mode'], $term_meta, $reference_term_slug);

  $playlistSettings = $playlistData['settings'];


  $vpsettings = array();


  $vpconfig_name = 'default';

  if (isset($playlistSettings) && isset($playlistSettings['vpconfig'])) {
    $vpconfig_name = $playlistSettings['vpconfig'];
  }


  $vpsettings = ClassDzsvgHelpers::view_getVpConfig($vpconfig_name);
  $playlistData['playerConfigSettings'] = $vpsettings['settings'];


  $playlistData['videoPlayerConfig'] = array_merge(array(), $vpsettings['settings']);
  $playlistSettings = array_merge($playlistSettings, $vpsettings['settings']);


  $playlistSettings = DzsvgView::gallerySanitizeInitialOptions($playlistSettings);


  if ($post && $dzsvg->sliders_index == 1) {
    if (get_post_meta($post->ID, 'dzsvg_preview', true) == 'on') {
      if (!is_admin()) {
        include_once(DZSVG_PATH . "class_parts/preview_page_customizer.php");
      }
    }
  }// ----dzsvg preview END


  if ($margs['category']) {

  }


  if ($dzsvg->mainoptions['playlists_mode'] != 'normal') {
  }

  dzsvg_view_playlistItems_parseDescription($playlistData);


  if (isset($playlistSettings['randomize']) && $playlistSettings['randomize'] == 'on' && is_array($playlistData)) {
    dzsvg_view_playlistItems_randomize($playlistData);
  }


  // -- if order is descending
  if (isset($playlistSettings['order']) && $playlistSettings['order'] == 'DESC') {
    $playlistData = array_reverse($playlistData);
  }

  // --- items settled

  if ($margs['return_mode'] == 'items') {
    return $playlistData;
  }


  if (is_array($extra_galleries) && count($extra_galleries)) {
    dzsvg_view_playlistAddExtraGalleries($playlistData, $extra_galleries);
  }


  // ------- some sanitizing
  $totalWidth = $playlistSettings['width'];

  $totalHeightForCss = '';


  if (isset($playlistSettings['force_height']) && $playlistSettings['force_height']) {

    $totalHeightForCss = $playlistSettings['force_height'];
  }


  if ($totalHeightForCss == '') {
    $totalHeightForCss = 'auto';
  }


  if (strpos($totalWidth, "%") === false) {
    $totalWidth = $totalWidth . 'px';
  }

  if (strpos($totalHeightForCss, "%") === false && $totalHeightForCss != 'auto') {
    $totalHeightForCss = $totalHeightForCss . 'px';
  }

  if (isset($playlistSettings['facebooklink']) && strpos($playlistSettings['facebooklink'], "{currurl}") !== false) {
    $playlistSettings['facebooklink'] = str_replace('{currurl}', urlencode(dzs_curr_url()), $playlistSettings['facebooklink']);
  }


  if ($margs['fullscreen'] == 'on') {
    $totalWidth = '100%';
    $totalHeightForCss = '100%';
  }


  $dzsvg->call_index--;

  if ($dzsvg->call_index < 0) {
    return $fout;
  }

  $extra_styling = '';

  $gallery_css_identifier = '.vg' . $dzsvg->sliders_index;

  if (isset($playlistSettings['skin_html5vg']) && $playlistSettings['skin_html5vg'] == 'skin-custom') {
    $extra_styling .= '<style class="dzsvg-skin-custom-styling">';
    $extra_styling .= $gallery_css_identifier . '.videogallery:not(.a):not(.a) { background:' . $dzsvg->mainoptions_dc['background'] . ';} ';
    $extra_styling .= $gallery_css_identifier . '.videogallery .navigationThumb:not(.a):not(.a){ background: ' . $dzsvg->mainoptions_dc['thumbs_bg'] . '; } ';
    $extra_styling .= $gallery_css_identifier . '.videogallery .navigationThumb.active:not(.a):not(.a),.vg' . $dzsvg->sliders_index . '.videogallery .navigationThumb:not(.a):not(.a):hover{ background-color: ' . $dzsvg->mainoptions_dc['thumbs_active_bg'] . '; } ';
    $extra_styling .= $gallery_css_identifier . '.videogallery .navigationThumb:not(.a):not(.a){ color: ' . $dzsvg->mainoptions_dc['thumbs_text_color'] . '; } .vg' . $dzsvg->sliders_index . '.videogallery .navigationThumb .the-title:not(.a):not(.a){ color: ' . $dzsvg->mainoptions_dc['thumbs_text_color'] . '; } ';

    if ($dzsvg->mainoptions_dc['thumbnail_image_width'] != '') {
      $extra_styling .= $gallery_css_identifier . '.videogallery .imgblock:not(.a):not(.a){ width: ' . $dzsvg->mainoptions_dc['thumbnail_image_width'] . 'px; } ';
    }

    if ($dzsvg->mainoptions_dc['thumbnail_image_height'] != '') {
      $extra_styling .= $gallery_css_identifier . '.videogallery .imgblock:not(.a):not(.a){ height: ' . $dzsvg->mainoptions_dc['thumbnail_image_height'] . 'px; } ';
    }

  }


  if (isset($playlistSettings) && isset($playlistSettings['extra_styling'])) {

    $extra_styling .= str_replace('{{gallery}}', $gallery_css_identifier, $playlistSettings['extra_styling']);
  }


  // ----
  // -- start output
  // ----

  ClassDzsvgHelpers::navigationPrepareOptions($playlistData);
  return dzsvg_shortcode_videogallery__getOutput($margs, $content, $dzsvg, array(
    'extra_styling' => $extra_styling,
    'playlistSettings' => $playlistSettings,
    'vpsettings' => $vpsettings,
    'totalWidth' => $totalWidth,
    'playlistData' => $playlistData,
    'totalHeightForCss' => $totalHeightForCss,
  ));

}

