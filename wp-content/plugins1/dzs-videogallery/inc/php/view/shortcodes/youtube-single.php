<?php

/**
 * [youtube id="youtubeid"]
 * @param $atts
 * @return string|void
 */
function dzsvg_shortcode_youtube_func($atts) {
  global $dzsvg;

  $fout = '';

  $margs = array(
    'width' => '100%',
    'config' => '',
    'height' => '300',
    'source' => '',
    'mediaid' => '',
    'player' => '',
    'mp4' => '',
    'sourceogg' => '',
    'autoplay' => 'off',
    'cuevideo' => 'on',
    'cover' => '',
    'type' => 'youtube',
    'cssid' => '',
    'single' => 'on',
  );

  $margs = array_merge($margs, $atts);

  if (isset($margs['id']) && $margs['id']) {
    $margs['source'] = $margs['id'];
  }
  $margs['config'] = $dzsvg->mainoptions['replace_default_video_embeds'];

  return dzsvg_shortcode_player($margs);
}


