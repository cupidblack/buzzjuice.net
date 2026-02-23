<?php

function dzsvg_view_playlist_getIdForClass($playlistSettings, $margs) {

  $idForClass = '';

  if (isset($playlistSettings['id']) && $playlistSettings['id']) {
    $idForClass = $playlistSettings['id'];
  } else {
    if (isset($margs['id']) && $margs['id']) {
      $idForClass = $margs['id'];
    }
  }
  if (isset($playlistSettings['slug']) && $playlistSettings['slug']) {
    $idForClass = $playlistSettings['slug'];
  }

  return $idForClass;
}

function dzsvg_view_getPaginationOutput($playlistItems, $margs, $post) {

  $fout = '';
  $fout .= '<div class="con-dzsvg-pagination">';


  $numberOfPages = ceil(count($playlistItems) / intval($margs['settings_separation_pages_number']));

  if ($numberOfPages > 1) {
    for ($i = 0; $i < $numberOfPages; $i++) {
      $str_active = '';
      if (($i + 1) == $margs[DZSVG_PLAYLIST_PAGINATION_QUERY_ARG_SHORT]) {
        $str_active = ' active';
      };

      $curr_url = dzs_curr_url();
      if ($post) {
        if (get_permalink($post->ID)) {

          $curr_url = get_permalink($post->ID);
        }
      }

      $auxurl = add_query_arg(array(DZSVG_PLAYLIST_PAGINATION_QUERY_ARG => ($i + 1)), $curr_url);


      $fout .= '<a class="pagination-number ' . $str_active . '" href="' . esc_url($auxurl) . '">' . ($i + 1) . '</a>';
    }

  }
  $fout .= '</div>';

  return $fout;
}

function dzsvg_view_modeWallPrepare($dzsvg, $sanitizedApConfigId) {


  $dzsvg->script_footer_root .= 'window.ultibox_videoplayersettings = "' . $sanitizedApConfigId . '";';
  $dzsvg->script_footer_root .= 'window.ultibox_options_init = { videoplayer_settings : "' . $sanitizedApConfigId . '"} ;';


  // in wall we need dzsap
  wp_enqueue_style('dzsap', DZSVG_URL . 'libs/audioplayer/audioplayer.css');
  wp_enqueue_script('dzsap', DZSVG_URL . 'libs/audioplayer/audioplayer.js');


  wp_enqueue_script('jquery.masonry', DZSVG_URL . "assets/masonry/jquery.masonry.min.js");


  ClassDzsvgHelpers::enqueueUltibox();
}

function dzsvg_view_playlistGenerateScriptForNext($playlistSettings, $viewGalleryId = '') {
  $script_final = '
                        setTimeout(function(){ function gotonext_' . ClassDzsvgHelpers::sanitize_forHtmlClass($playlistSettings['id']) . '(arg){

                                ' . stripslashes($playlistSettings['action_playlist_end']) . '
                            }
console.info("$(\'.id_' . $viewGalleryId . '\') -> ",$(\'.id_' . $viewGalleryId . '\'));
                            $(\'.id_' . $viewGalleryId . '\').get(0).api_set_action_playlist_end(gotonext_' . ClassDzsvgHelpers::sanitize_forHtmlClass($playlistSettings['id']) . ');
                        },1000);';

  return $script_final;
}

/**
 * generate videogallery con
 * @param array $margs
 * @param DZSVideoGallery $dzsvg
 * @param array $playlistSettings
 * @param $transition
 * @param $viewGalleryId
 * @param $string_navigationSkin
 * @param $totalHeightForCss
 * @param $playlistInitOptions
 * @return string
 */
function dzsvg_view_playlistGenerateHtmlConStart($margs, $dzsvg, $playlistSettings, $transition, $viewGalleryId, $string_navigationSkin, $totalHeightForCss, $playlistInitOptions) {
  $fout = '';


  $fout .= '<div class="vg' . $dzsvg->sliders_index . ' transition-' . $transition . ' dzsvg-videogallery videogallery videogallery-' . $margs['css_number_id'] . ' auto-init id_' . $viewGalleryId . ' ' . $string_navigationSkin . ' navigation-skin--' . $string_navigationSkin;


  if (isset($playlistSettings['shadow']) && $playlistSettings['shadow'] == 'on') {
    $fout .= ' with-bottom-shadow';
  }

  if (isset($playlistSettings['extra_classes']) && $playlistSettings['extra_classes'] != '') {
    $fout .= ' ' . $playlistSettings['extra_classes'] . '';
  }


  if ($margs['fullscreen'] == 'on') {
    $fout .= ' gallery-is-fullscreen';
  }

  if ($margs['init_from'] == 'ultibox') {
    $fout .= ' auto-init-from-ultibox';
  }
  if ($totalHeightForCss && $totalHeightForCss !== 'auto') {

    $fout .= ' height-is-fixed';
  }

  $fout .= '" ';
  $fout .= ' id="' . ($viewGalleryId) . '" ';
  $fout .= ' data-dzsvg-gallery-id="' . ($viewGalleryId) . '" ';

  $fout .= ' data-options=\'' . $playlistInitOptions . '\' ';


  $fout .= '';


  $fout .= '   style=\'';


  if (isset($playlistSettings['skin_html5vg']) && $playlistSettings['skin_html5vg'] != 'skin-custom') {
    $fout .= 'background-color:' . $playlistSettings['bgcolor'] . ';';
  }

  if ($totalHeightForCss && $totalHeightForCss !== 'auto') {

    $fout .= '  height:' . $totalHeightForCss . ';';
  }
  $fout .= '\'';
  $fout .= '>';

  if ($dzsvg->mainoptions['use_layout_builder_on_navigation'] == 'on') {
    if ($dzsvg->classLayoutBuilder_menuItems) {
      $fout .= $dzsvg->classLayoutBuilder_menuItems->get_frontend_struct($playlistSettings, $string_navigationSkin);
      $fout .= '<style>' . $dzsvg->classLayoutBuilder_menuItems->get_frontend_css() . '</style>';
    }
  }


  $fout .= '<div class="items">';

  return $fout;
}

function dzsvg_view_getOutsideSearchField($playlistSettings, $viewPlaylistMenuPosition, $dzsvg) {


  $fout = '';
  if (!(isset($playlistSettings['search_field_location']) && $playlistSettings['search_field_location'] == 'inside' && $playlistSettings['displaymode'] == 'normal' && ($playlistSettings['nav_type'] == 'thumbs' || $playlistSettings['nav_type'] == 'scroller') && ($viewPlaylistMenuPosition == 'left' || $viewPlaylistMenuPosition == 'right'))) {
    $fout .= '<div class="vg' . $dzsvg->sliders_index . '-search-field dzsvg-search-field outer"><input type="text" placeholder="' . esc_html__('Search', DZSVG_ID) . '..."/>' . DZSVG_VIEW_SVG_SEARCH_ICON . '</div>';
  }

  return $fout;
}

function dzsvg_view_playlistGenerateHtmlContainerStart($margs, $dzsvg, $playlistSettings, $totalWidth) {


  $fout = '';
  $fout .= '<div class="gallery-precon gp' . $dzsvg->sliders_index . '';
  if ($margs['fullscreen'] == 'on') {
    $fout .= ' gallery-is-fullscreen';
  }

  if ($margs['init_from'] == 'ultibox') {
    $fout .= ' show-only-in-ultibox cancel-inlinecontent-padding';
    $fout .= ' gallery-precon' . $margs['css_number_id'];
  }


  $totalHeight = 'auto';
  if ($margs['fullscreen'] == 'on') {
    $totalHeight = '100vh';
  }
  if (isset($playlistSettings['forcevideoheight']) && $playlistSettings['forcevideoheight']) {
  }
  if ($margs['fullscreen'] == 'on') {
    $totalWidth = '100vw';
  }

  $fout .= '" style="width:' . $totalWidth . ';height:' . $totalHeight . ';';


  if (isset($playlistSettings['max_width']) && $playlistSettings['max_width']) {

    $fout .= ' max-width: ' . $playlistSettings['max_width'] . 'px; margin: 0 auto; ';
  }

  if ($margs['fullscreen'] == 'on') {
    $fout .= ' position:' . 'fixed' . '; z-index:50005; top:0; left:0;';
  }
  if ($margs['category'] != '') {
    $fout .= '"';
    $fout .= '  data-category="' . $margs['category'] . '';
  }
  $fout .= '"';
  $fout .= '>';

  return $fout;
}

/**
 * @param array $playlistData mutates
 * @param array $extra_galleries
 * @return void
 */
function dzsvg_view_playlistAddExtraGalleries(&$playlistData, $extra_galleries) {

  foreach ($extra_galleries as $extragal) {
    $playlistArgs = array(
      'id' => $extragal,
      'return_mode' => 'items',
      'called_from' => 'extra_galleries',

    );


    foreach (dzsvg_shortcode_videogallery($playlistArgs) as $lab => $it3) {
      if ($lab === 'settings') {
        continue;
      }
      array_push($playlistData, $it3);
    }


  }
}

/**
 * @param array $playlistData mutates
 * @return void
 */
function dzsvg_view_playlistItems_randomize(&$playlistData) {


  $backup_its = $playlistData;
  shuffle($playlistData);

  for ($i = 0; $i < count($playlistData); $i++) {
    if (isset($playlistData[$i]['feedfrom'])) {

      unset($playlistData[$i]);
    }
  }
  $playlistData = array_reverse($playlistData);
  $playlistSettings = $backup_its['settings'];
  $playlistData['playerConfigSettings'] = $backup_its['playerConfigSettings'];

}

/**
 * @param array $playlistData mutates
 * @return void
 */
function dzsvg_view_playlistItems_parseDescription(&$playlistData) {


  foreach ($playlistData as $lab => $playlistDatum) {

    if ($lab == 'playerConfigSettings' || $lab == 'videoPlayerConfig' || $lab == 'settings') {
      continue;
    }

    // -- lets parse links

    if (isset($playlistDatum['description'])) {
      $playlistData[$lab]['description'] = ClassDzsvgHelpers::sanitize_anchorsTextToHtml($playlistDatum['description']);
    }
    if (isset($playlistDatum['menuDescription'])) {
      $playlistData[$lab]['menuDescription'] = ClassDzsvgHelpers::sanitize_anchorsTextToHtml($playlistDatum['menuDescription']);
    }

  }
}

function dzsvg_view_getPlaylistDataSettings($playlistData, $selected_term_id, $playlistMode, $term_meta, $reference_term_slug) {


  $playlistSettingsDefault = PLAYLIST_SETTINGS_DEFAULT;
  if ($playlistMode == 'normal') {
    $playlistSettingsDefault['id'] = $selected_term_id;
  }

  $playlistDataSettings = array();

  if (isset($playlistData['settings']) && is_array($playlistData['settings'])) {
    $playlistDataSettings = array_merge($playlistSettingsDefault, $playlistData['settings']);
  } else {
    $playlistDataSettings = array_merge($playlistSettingsDefault, array());
  }
  if (!is_array($playlistDataSettings)) {
    $playlistDataSettings = array();
  }


  if ($playlistMode == 'normal') {
    if ($term_meta && is_array($term_meta)) {
      foreach ($term_meta as $lab => $val) {
        if ($lab == 'autoplay_next') {
          $lab = 'autoplaynext';
        }
        $playlistDataSettings[$lab] = $val;
      }
    }


    // -- if we have reference term slug..
    $playlistDataSettings['slug'] = $reference_term_slug;
  }

  return $playlistDataSettings;
}

function dzsvg_view_getPlaylistData($margs) {
  global $dzsvg;

  $itsForPlaylist = null;

  if ($margs['overwrite_only_its'] && is_array($margs['overwrite_only_its'])) {
    $playlistData = $margs['overwrite_only_its'];
  } else {

    if ($margs['its'] && is_array($margs['its'])) {
      $playlistData = $margs['its'];
    } else {


      // -- playlists mode normal
      if ($dzsvg->mainoptions['playlists_mode'] == 'normal') {
        $itsForPlaylist = DzsvgView::getItsForPlaylist($margs['id']);
        $playlistData = $itsForPlaylist['its'];
      } else {
        include_once(DZSVG_PATH . 'inc/php/parse-media-apis/legacy--get-its.php');
        $playlistData = dzsvg_legacy_getItsForPlaylist($dzsvg, $margs['id']);
      }
    }
  }

  return array(
    'playlistData' => $playlistData,
    'itsForPlaylist' => $itsForPlaylist,
  );
}

function dzsvg_view_legacy_set_current_items($margs) {

  global $dzsvg;

  // -- setting up the db
  $currDb = '';
  if (isset($margs['db']) && $margs['db'] != '') {
    $dzsvg->currDb = $margs['db'];
    $currDb = $dzsvg->currDb;
  }
  $dzsvg->dbs = get_option($dzsvg->dbdbsname);


  // -- *deprecated - legacy
  if ($currDb != 'main' && $currDb != '') {
    $dbitemsname = $dzsvg->dbkey_legacyItems . '-' . $currDb;
    $dzsvg->mainitems = get_option($dbitemsname);
  }
  //--setting up the db END
}

function dzsvg_view_playlist_get_id($margs, $dzsvg) {


  $playlistId = $margs['id'];

  if ($margs['slider'] && $margs['slider'] != 'default') {

    $playlistId = $margs['slider'];
  }

  if ($margs['id'] == 'auto') {


    if (isset($_GET['dzsvg_gallery_slug']) && $_GET['dzsvg_gallery_slug']) {

      $playlistId = sanitize_text_field($_GET['dzsvg_gallery_slug']);
    } else {

      $terms = get_terms($dzsvg->taxname_sliders, array(
        'hide_empty' => false,
      ));


      if (is_array($terms) && isset($terms[0])) {
        $playlistId = $terms[0]->slug;
      }
    }
  }


  return $playlistId;

}
