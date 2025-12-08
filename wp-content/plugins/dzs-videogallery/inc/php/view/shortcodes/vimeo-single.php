<?php


/**
 * @param $atts
 * @return string
 */
function dzsvg_shortcode_vimeo_func($atts) {

  global $dzsvg;
  $fout = '';
  $margs = array('id' => '2', 'vimeo_title' => '', 'vimeo_byline' => '0', 'vimeo_portrait' => '0', 'vimeo_color' => '', 'width' => '100%', 'height' => '300', 'config' => '', 'single' => 'on',);

  if ($atts == false) {
    $atts = array();
  }

  $margs = array_merge($margs, $atts);

  $vimeoShortcodeWidth = 400;
  if (isset($margs['width'])) {
    $vimeoShortcodeWidth = $margs['width'];
  }
  $vimeoShortcodeHeight = 300;
  if (isset($margs['height'])) {
    $vimeoShortcodeHeight = $margs['height'];
  }

  $vpsettingsdefault = array();
  $vpsettingsdefault['settings'] = array_merge($dzsvg->vpsettingsdefault, array());
  $i = 0;
  $vpconfig_k = 0;
  $vpsettings = array();


  if ($margs['config'] != '') {
    $vpconfig_id = $margs['config'];

    for ($i = 0; $i < count($dzsvg->mainvpconfigs); $i++) {
      if ((isset($vpconfig_id)) && ($vpconfig_id == $vpconfig_id)) {
        $vpconfig_k = $i;
      }
    }
    $vpsettings = $dzsvg->mainvpconfigs[$vpconfig_k];
  }


  $vpsettings = array_merge($vpsettingsdefault, $vpsettings);


  if (isset($vpsettings['settings']) && isset($vpsettings['settings']['vimeo_byline'])) {
    $margs['vimeo_byline'] = $vpsettings['settings']['vimeo_byline'];
  }
  if (isset($vpsettings['settings']) && isset($vpsettings['settings']['vimeo_title'])) {
    $margs['vimeo_title'] = $vpsettings['settings']['vimeo_title'];
  }
  if (isset($vpsettings['settings']) && isset($vpsettings['settings']['vimeo_color'])) {
    $margs['vimeo_color'] = $vpsettings['settings']['vimeo_color'];
  }
  if (isset($vpsettings['settings']) && isset($vpsettings['settings']['vimeo_portrait'])) {
    $margs['vimeo_portrait'] = $vpsettings['settings']['vimeo_portrait'];
  }



  $str_title = 'title=' . $margs['vimeo_title'];
  $str_byline = '&amp;byline=' . $margs['vimeo_byline'];
  $str_portrait = '&amp;portrait=' . $margs['vimeo_portrait'];
  $str_color = '';
  if ($margs['vimeo_color'] != '') {
    $str_color = '&amp;color=' . $margs['vimeo_color'];
  }

  $margs['config'] = $dzsvg->mainoptions['replace_default_video_embeds'];

  $fout .= '<iframe src="https://player.vimeo.com/video/' . $margs['id'] . '?' . $str_title . $str_byline . $str_portrait . $str_color . '" width="' . $vimeoShortcodeWidth . '" height="' . $vimeoShortcodeHeight . '" style="border: 0;"></iframe>';
  return $fout;
}