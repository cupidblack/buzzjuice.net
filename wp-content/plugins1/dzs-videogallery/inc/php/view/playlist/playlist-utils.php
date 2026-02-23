<?php

function dzsvg_playlist_getPlayerSettingsFromGallery($dzsvg, $playlistSettings){

  $playerSettingsFromGallery = array();


  if (isset($playlistSettings['set_responsive_ratio_to_detect']) && $playlistSettings['set_responsive_ratio_to_detect'] == 'on') {
    $playerSettingsFromGallery['responsive_ratio'] = 'detect';
  }

  if ($dzsvg->mainoptions['settings_trigger_resize'] == 'on') {
    $playerSettingsFromGallery['settings_trigger_resize'] = 'on';
  };


  if ($dzsvg->mainoptions['youtube_playfrom']) {
    if ($playlistSettings['feedfrom'] == 'ytuserchannel' || $playlistSettings['feedfrom'] == 'ytplaylist' || $playlistSettings['feedfrom'] == 'ytkeywords') {
      $playerSettingsFromGallery['playfrom'] = $dzsvg->mainoptions['youtube_playfrom'];
    }
  }


  if ($playlistSettings['displaymode'] == 'wall') {
    $playerSettingsFromGallery['autoplay'] = $playlistSettings['autoplay'];
  } else {
    $playerSettingsFromGallery['autoplay'] = 'off';
  }


  if ($dzsvg->mainoptions['videoplayer_end_exit_fullscreen'] == 'off') {
    $playerSettingsFromGallery['end_exit_fullscreen'] = $dzsvg->mainoptions['videoplayer_end_exit_fullscreen'];
  }

  return $playerSettingsFromGallery;
}
function dzsvg_view_getNavigationSkin($playlistSettings = array()){

  $string_navigationSkin = 'skin-pro';

  if (isset($playlistSettings['design_skin']) && $playlistSettings['design_skin'] !== 'skin-custom') {
    $string_navigationSkin = $playlistSettings['design_skin'];
  } else {
    if (isset($playlistSettings['skin_html5vg']) && $playlistSettings['skin_html5vg'] !== 'skin-custom') {
      $string_navigationSkin = $playlistSettings['skin_html5vg'];
    }
  }

  if ($string_navigationSkin === 'skin-default') {
    if ($playlistSettings['displaymode'] === 'wall') {

      // -- force skin wall for gallery
      $string_navigationSkin = 'skin-wall';
    }
  }

  return $string_navigationSkin;
}
