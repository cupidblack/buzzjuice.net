<?php

/**
 * @param array $itemInstances
 * @param array $pargs
 * @param DZSVideoGallery $dzsvg
 * @param DzsvgView $dzsvgView
 * @return string
 */
function dzsvg_view_parseItems($itemInstances, $pargs, $dzsvg, &$dzsvgView, $playlistSettings, $playerConfigSettings) {

  $margs = array(
    'settings_separation_mode' => 'normal',
    DZSVG_PLAYLIST_PAGINATION_QUERY_ARG_SHORT => '0',
    'settings_separation_pages_number' => '5',
    'single' => 'off',
    'auto_init_player' => 'off',
    'auto_init_player_options' => '',
    'video_post' => null,
    'called_from' => 'default',
    'striptags' => 'on',
    'extra_classes_player' => '',
  );

  if (is_array($pargs) == false) {
    $pargs = $margs;
  }


  $margs = array_merge($margs, $pargs);


  $fout = '';
  $start_nr = 0; // -- the i start nr
  $end_nr = count($itemInstances); // -- the i start nr

  $pagination_totalNumberOfItems = count($itemInstances);
  $pagination_pageNumber = intval($margs[DZSVG_PLAYLIST_PAGINATION_QUERY_ARG_SHORT]);

  if ($pagination_pageNumber == 0) {
    $pagination_pageNumber = 1;
  }

  if ($margs['settings_separation_mode'] != 'normal') {
    $nr_per_page = intval($margs['settings_separation_pages_number']);

    if ($nr_per_page * $pagination_pageNumber < $pagination_totalNumberOfItems) {

      $start_nr = $nr_per_page * ($pagination_pageNumber - 1);
      $end_nr = $start_nr + $nr_per_page;
    } else {

      $start_nr = $nr_per_page * ($pagination_pageNumber - 1);
      $end_nr = $pagination_totalNumberOfItems;
    }

  }

  if (isset($playlistSettings['displaymode']) && $playlistSettings['displaymode'] == 'alternatewall') {
    return ClassDzsvgHelpers::playlist_parseItemsForAlternateWall($start_nr, $end_nr);
  }


  DzsvgView::enqueuePlayerPartScripts($playerConfigSettings);
  for ($i = $start_nr; $i < $end_nr; $i++) {
    if (!isset($itemInstances[$i])) {
      continue;
    }


    $che = $itemInstances[$i];
    $dzsvgView->index_players++;

    $video_post = null;

    $videoTitle = '';

    if (isset($che['title'])) {
      $videoTitle = stripslashes($che['title']);
    }


    if (!(isset($che['video_post']) && $che['video_post'])) {


      if (isset($che['mediaid']) && $che['mediaid']) {
        $auxpo = get_post($che['mediaid']);
        if ($auxpo) {
          $video_post = $auxpo;
        }

        $post_id = $che['mediaid'];

        if ($auxpo->post_type == 'attachment') {

          if ($che['source'] == '') {
            $che['source'] = $auxpo->guid;
          }
        }


        if ($auxpo->post_type == 'product' || $auxpo->post_type == DZSVG_POST_NAME) {

          if (get_post_meta($post_id, 'dzsvp_featured_media', true)) {

            if ($che['source'] == '') {

              $che['source'] = get_post_meta($post_id, 'dzsvp_featured_media', true);
            }
          }


          if ($video_post->post_content) {

            if ($che['description'] == 'default') {
              $che['description'] = $video_post->post_content;
            }
          }
        }

      }
    }


    if ($video_post) {
      $che['video_post'] = $video_post;
    }

    if (!isset($che['source']) || $che['source'] === '' || $che['source'] === ' ') {
      continue;
    }


    $str_id = '';
    $vp_id = 'vp' . $dzsvgView->index_players;
    if (isset($che['cssid']) && $che['cssid'] != '') {
      $vp_id = $che['cssid'];
    }


    $vp_id = ClassDzsvgHelpers::sanitize_forKey($vp_id);
    if (isset($playlistSettings['ids_point_to_source']) && $playlistSettings['ids_point_to_source'] == 'on') {
      $vp_id = 'vg' . $dzsvg->sliders_index . '_' . 'vp' . ClassDzsvgHelpers::sanitize_forKey($che['source']);
      $str_id = ' id="' . $vp_id . '"';
    }


    ClassDzsvgHelpers::sanitizeArgsForParseItem($che);


    $videoPlayerClasses = '' . $vp_id . ' vplayer-tobe ' . $margs['extra_classes_player'] . '';


    if (isset($playlistSettings['laptop_container']) && $playlistSettings['laptop_container'] == 'on') {
      $videoPlayerClasses .= ' vp-con-laptop';
    }
    if (isset($playlistSettings['enable_mute_icon']) && $playlistSettings['enable_mute_icon'] == 'on') {
      $videoPlayerClasses .= ' show-muted-btn';
    }

    if (isset($playlistSettings['hide_on_mouse_out']) && $playlistSettings['hide_on_mouse_out'] == 'on') {
      $videoPlayerClasses .= ' hide-on-mouse-out';
    }
    if (isset($playerConfigSettings['id']) && $playerConfigSettings['id']) {

      $videoPlayerClasses .= ' vpconfig-' . ClassDzsvgHelpers::sanitizeToPhpId($playerConfigSettings['id']);
    }
    if (isset($playlistSettings['hide_on_paused']) && $playlistSettings['hide_on_paused'] == 'on') {
      $videoPlayerClasses .= ' hide-on-paused';
    }
    if ($margs['auto_init_player'] == 'on') {
      $videoPlayerClasses .= ' auto-init';
    }


    // -------------
    // -- parse here
    $fout .= '<div  ' . $str_id . ' class="' . $videoPlayerClasses . '"';


    if ($margs['auto_init_player_options']) {
      $fout .= ' data-options=\'' . $margs['auto_init_player_options'] . '\'';
    }

    if (isset($che['playerid']) && $che['playerid']) {
      $fout .= ' data-player-id="' . dzs_clean_string($che['playerid']) . '"';
    } else {
      if (is_numeric($che['source'])) {
        $fout .= ' data-player-id="' . ($che['source']) . '"';
      } else {
        $fout .= ' data-player-id="' . intval(ClassDzsvgHelpers::encode_toNumber($che['source'])) . '"';
      }
    }


    if (isset($playlistSettings['coverImage']) && $playlistSettings['coverImage']) {
      $fout .= '  data-img="' . ClassDzsvgHelpers::sanitize_idToSource($playlistSettings['coverImage']) . '"';
    }


    if (!(isset($playlistSettings['disable_video_title']) && $playlistSettings['disable_video_title'] == 'on') && $videoTitle) {
      $fout .= ' data-videoTitle="' . htmlentities($videoTitle) . '"';
    }
    if (isset($che['loop']) && $che['loop'] == 'on') {
      $fout .= ' data-loop="' . $che['loop'] . '"';

    }


    if (isset($che['is_360']) && $che['is_360'] == 'on') {
      $fout .= ' data-is-360="' . $che['is_360'] . '"';

      wp_enqueue_script('dzsvg-part-360', DZSVG_SCRIPT_URL . 'parts/player/player-360.js');
    }


    $videoSource = ClassDzsvgHelpers::detectVideoSourceFromChe($che);
    $videoType = ClassDzsvgHelpers::detectVideoType($videoSource, $che['type']);


    if (isset($videoType) && $videoType == 'video') {
      $fout .= ' data-sourcevp="' . $videoSource . '"';

      if (isset($che['html5sourceogg']) && $che['html5sourceogg'] != '') {

        if (strpos($che['html5sourceogg'], '.webm') === false) {
          $fout .= ' data-sourceogg="' . $che['html5sourceogg'] . '"';
        } else {
          $fout .= ' data-sourcewebm="' . $che['html5sourceogg'] . '"';
        }
      }
    }


    if (isset($playlistSettings['displaymode']) && $playlistSettings['displaymode'] == 'rotator3d') {

      if (isset($che['audioimage']) == false || $che['audioimage'] == '') {

        if (isset($che['thethumb']) && $che['thethumb']) {
          $che['audioimage'] = $che['thethumb'];
        }
      }

    }


    $preview_img = '';


    if (isset($che['audioimage']) && $che['audioimage']) {

      $preview_img = $che['audioimage'];
    }


    if (isset($playlistSettings['displaymode']) && $playlistSettings['displaymode'] == 'rotator3d') {

      $preview_img = $che['thumbnail'];
    }


    if (isset($playlistSettings['displaymode']) && $playlistSettings['displaymode'] == 'wall' && isset($che['thethumb']) && $che['thethumb'] != '') {
      $preview_img = $che['thethumb'];
    }

    if ($preview_img) {

      $fout .= ' data-previewimg="' . $preview_img . '"';
    }


    if (isset($che['audioimage']) && $che['audioimage']) {
      $fout .= '  data-img="' . $che['audioimage'] . '"';
    }

    if (isset($videoType) && $videoType == 'audio') {
      $fout .= ' data-sourcevp="' . $videoSource . '"';
      $fout .= ' data-sourcemp3="' . $videoSource . '"';
      if (isset($che['html5sourceogg']) && $che['html5sourceogg'] != '') {
        $fout .= ' data-sourceogg="' . $che['html5sourceogg'] . '"';
      }
      if (isset($che['audioimage']) && $che['audioimage'] != '') {
        $fout .= ' data-audioimg="' . $che['audioimage'] . '"';
      }
      $fout .= ' data-type="audio"';
    }


    if (isset($videoType) && $videoType == 'youtube') {
      $fout .= ' data-type="youtube"';
      $fout .= ' data-sourcevp="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($videoSource) . '"';
    }
    if (isset($videoType) && $videoType == 'vimeo') {
      $fout .= ' data-type="vimeo"';
      $fout .= ' data-sourcevp="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($videoSource) . '"';
    }
    if (isset($videoType) && $videoType == 'image') {
      $fout .= ' data-type="image"';
      $fout .= ' data-sourcevp="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($videoSource) . '"';
    }
    if (isset($videoType) && $videoType == 'dash') {
      $fout .= ' data-type="dash"';
      $fout .= ' data-sourcevp="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($videoSource) . '"';
    }
    if (isset($videoType) && $videoType == 'facebook') {
      include_once DZSVG_PATH . 'inc/php/facebook/facebook-functions.php';
      dzsvg_facebook_parseItems_facebookVideo($dzsvg, $videoSource);
    }
    if (isset($videoType) && $videoType == 'link') {
      $fout .= ' data-type="link"';
      $fout .= ' data-sourcevp="' . $videoSource . '"';
      if (isset($videoType) && $videoType == 'link' && isset($che['link_target'])) {
        $fout .= ' data-target="' . $che['link_target'] . '"';
      }
    }


    if (isset($videoType) && $videoType == 'inline') {
      $fout .= ' data-type="inline"';
    }


    $aux = 'adarray';


    if (isset($che[$aux]) && $che[$aux]) {
      $che[$aux] = str_replace('{{openbrace}}', '[', $che[$aux]);
      $che[$aux] = str_replace('{{closebrace}}', ']', $che[$aux]);


      $fout .= ' data-ad-array' . '' . '=\'' . ($che[$aux]) . '\'';
      remove_filter('the_content', 'wptexturize');

    }


    $aux = 'adsource';
    if (isset($che[$aux]) && $che[$aux] != '') {
      if (isset($che['adtype']) && $che['adtype'] != 'inline') {
        $fout .= ' data-' . $aux . '="' . $che[$aux] . '"';
      }
    }
    $aux = 'adtype';
    if (isset($che[$aux]) && $che[$aux] != '') {
      $fout .= ' data-' . $aux . '="' . $che[$aux] . '"';
    }
    $aux = 'adlink';
    if (isset($che[$aux]) && $che[$aux] != '') {
      $fout .= ' data-' . $aux . '="' . $che[$aux] . '"';
    }
    $aux = 'adskip_delay';
    if (isset($che[$aux]) && $che[$aux] != '') {
      $fout .= ' data-' . $aux . '="' . $che[$aux] . '"';
    }
    //-- deprecated END


    $aux = 'play_from';

    if (isset($che[$aux]) && $che[$aux] != '') {
      $fout .= ' data-' . $aux . '="' . $che[$aux] . '"';
    }

    $aux = 'responsive_ratio';
    if (isset($che[$aux]) && $che[$aux] != '') {
      $fout .= ' data-' . $aux . '="' . $che[$aux] . '"';
    }

    // -- if the video player is single shortcode then we can alter width height
    if ($margs['single'] == 'on') {

      // --  some sanitizing
      $tw = $margs['width'];
      $th = $margs['height'];
      $str_tw = '';
      $str_th = '';


      if ($tw != '') {
        if (strpos($tw, "%") === false && $tw != 'auto') {
          $str_tw = ' width: ' . $tw . 'px;';
        } else {
          $str_tw = ' width: ' . $tw . ';';
        }
      }


      if ($th != '') {
        if (strpos($th, "%") === false && $th != 'auto') {
          $str_th = ' height: ' . $th . 'px;';
        } else {
          $str_th = ' height: ' . $th . ';';
        }
      }


      $fout .= ' style="' . $str_tw . $str_th . '"';
    }


    $fout .= '>';

    // -- starting from tag


    $maxlen = 350;
    if (isset($playlistSettings['maxlen_desc']) && $playlistSettings['maxlen_desc']) {
      $maxlen = $playlistSettings['maxlen_desc'];
    }


    $aux = 'qualities';


    if (isset($che[$aux]) && $che[$aux]) {
      try {
        $che[$aux] = str_replace('{{quot}}', '"', $che[$aux]);
        $che[$aux] = str_replace('{{patend}}', ']', $che[$aux]);
        $qual_arr = json_decode($che[$aux]);


        if (is_array($qual_arr)) {

          foreach ($qual_arr as $it) {


            $att = get_post($it->source);


            $source = '';

            if (is_numeric($it->source)) {

              if ($att->post_type == 'attachment') {
                $source = wp_get_attachment_url($it->source);
              }
            } else {
              $source = $it->source;
            }

            $fout .= ' <div class="dzsvg-feed dzsvg-feed-quality" data-label="' . $it->label . '" data-sourcevp="' . $source . '"></div>';
          }
        }
      } catch (Exception $err) {

      }
    }

    if (isset($playerConfigSettings) && isset($playerConfigSettings['enable_quality_changer_button']) && $playerConfigSettings['enable_quality_changer_button'] === 'on') {


      $fout .= '<div class="dzsvg-feed dzsvg-feed--extra-html-before-right-controls">';
      $fout .= '<div class="dzsvg-player-button quality-selector show-only-when-multiple-qualities">{{svg_quality_icon}}<div class="dzsvg-tooltip">{{quality-options}}</div></div>';

      $fout .= '</div>';
    }

    $striptags = true;

    $try_to_close_unclosed_tags = true;


    if (isset($playlistSettings['striptags'])) {
      if ($playlistSettings['striptags'] === 'on') {

        $try_to_close_unclosed_tags = false;
      }


      if ($playlistSettings['striptags'] === 'off') {
        $striptags = false;

      }
    }
    if ((isset($che['striptags']) && $che['striptags'] == 'off')) {
      $striptags = false;

    }
    if (isset($playlistSettings['try_to_close_unclosed_tags']) && $playlistSettings['try_to_close_unclosed_tags'] === 'off') {
      $try_to_close_unclosed_tags = false;
    }


    $aux24 = '';

    $readmore_markup = '';
    if (isset($playlistSettings['readmore_markup'])) {
      $readmore_markup = $playlistSettings['readmore_markup'];
    }


    $readmore = 'auto';

    if ($videoType == 'youtube') {
      $readmore_markup = str_replace('{{postlink}}', 'https://www.youtube.com/watch?v=' . $videoSource, $readmore_markup);
    } else {

      // if no post link
      if (strpos($readmore_markup, '{{postlink}}') !== false) {

        $readmore_markup = '';
        $readmore = 'off';
      }
    }


    $description = '';


    if (isset($che['description'])) {
      $description = $che['description'];
    }


    $args = array(
      'content' => $description,
      'maxlen' => $maxlen,
      'try_to_close_unclosed_tags' => $try_to_close_unclosed_tags,
      'striptags' => $striptags, 'readmore_markup' => $readmore_markup, 'readmore' => $readmore, 'called_from' => 'simple_description',);


    $che['description'] = wp_kses(dzs_get_excerpt(-1, $args), (DZSVG_HTML_ALLOWED_TAGS));


    dzs_swap_val($che['menuDescription'], $che['menu_description']);

    if (isset($che['menuDescription']) && $che['menuDescription'] == 'as_description') {
      $che['menuDescription'] = $che['description'];
    }


    if (isset($che['menuDescription'])) {
      // -- TODO: remove striptags for now

      $che['menuDescription'] = wp_kses(dzs_get_excerpt(-1,
        array(
          'content' => ($che['menuDescription']),
          'maxlen' => $maxlen,
          'try_to_close_unclosed_tags' => $try_to_close_unclosed_tags,
          'striptags' => false,
          'readmore_markup' => $readmore_markup,
          'readmore' => $readmore,
          'called_from' => 'simple_menudescription',
        )
      ), (DZSVG_HTML_ALLOWED_TAGS));
    } else {
      $che['menuDescription'] = '';
    }


    if (isset($che['description']) && $che['description']) {
      $aux24 = '<div hidden class="videoDescription feed-dzsvg description-from-parse_items">' . $che['description'] . '</div>';
    }


    if (ClassDzsvgHelpers::galleryHasExtraControls($itemInstances)) {
      $fout .= '<div class="extra-controls">';
      $fout .= $dzsvgView->player_viewGenerateExtraControls($che, $itemInstances);
      $fout .= '</div>';
    }


    $aux24 = str_replace('</</div>', '</div>', $aux24);

    $fout .= $aux24;


    if (isset($che['logo']) && $che['logo']) {
      $fout .= '<div class="vplayer-logo">';


      if (isset($che['logo_link']) && $che['logo_link']) {

        $fout .= '<a href="' . $che['logo_link'] . '">';
      }
      $fout .= '<div class="divimage" style="background-image: url(' . ClassDzsvgHelpers::sanitize_idToSource($che['logo']) . ');"></div>';


      if (isset($che['logo_link']) && $che['logo_link']) {

        $fout .= '</a>';
      }
      $fout .= '</div>';
    }


    // -- subtitle decode here
    $aux = 'subtitle_file';
    if (isset($che[$aux]) && $che[$aux] != '') {
      $fil = DZSHelpers::get_contents($che[$aux]);
      $fout .= '<div class="subtitles-con-input">' . $fil . '</div>';
    } else {
      $aux = 'subtitle';
      if (isset($che[$aux]) && $che[$aux] != '') {
        $fil = DZSHelpers::get_contents($che[$aux]);
        $fout .= '<div class="subtitles-con-input">' . $fil . '</div>';
      }
    }


    $fout .= dzsvg_view_parseItems_generateItemProperties($dzsvg, $itemInstances, $i, $videoTitle, $videoType, $che);

    if (isset($che['tags']) && $che['tags']) {
      /** @var array $arrSeparatorTags */
      $arrSeparatorTags = explode('$$;', $che['tags']);
      foreach ($arrSeparatorTags as $separatorTag) {

        if ($separatorTag != '') {
          $arr_septagprop = explode('$$', $separatorTag);
          $fout .= '<div class="dzstag-tobe" data-starttime="' . $arr_septagprop[0] . '" data-endtime="' . $arr_septagprop[1] . '" data-left="' . $arr_septagprop[2] . '" data-top="' . $arr_septagprop[3] . '" data-width="' . $arr_septagprop[4] . '" data-height="' . $arr_septagprop[5] . '" data-link="' . $arr_septagprop[6] . '">' . $arr_septagprop[7] . '</div>';
        }
      }
    }

    if (isset($videoType) && $videoType == 'inline') {
      // -- inline content
      $fout .= '<div class="feed-dzsvg feed-dzsvg--inline-content">' . ClassDzsvgHelpers::sanitize_forInlineContent(stripslashes($videoSource)) . '</div>';
    }


    if (isset($che['adtype']) && $che['adtype'] == 'inline') {
      $fout .= '<div class="adSource">' . $che['adsource'] . '</div>';
    }

    $fout .= '</div>';
  }
  return $fout;
}
