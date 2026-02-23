<?php


/**
 * [dzs_video source="https://localhost/wordpress/wp-content/uploads/2015/03/test.m4v" configs="minimalplayer" height="" type="video"]
 * [video source="pathto.mp4"]
 * @param array $atts
 * @param string $content
 * @return string|void
 */
function dzsvg_shortcode_replace_video_embed($atts = array(), $content = '') {
  global $dzsvg;
  $dzsvg->slider_index++;

  $fout = '';


  $dzsvg->front_scripts();

  $margs = array(
    'width' => '100%',            // -- the width , leave 100% for responsive
    'height' => '300',// -- force a height

  );

  $margs = array_merge($margs, $atts);

  $margs['source'] = $margs['mp4'];
  $margs['config'] = $dzsvg->mainoptions['replace_default_video_embeds'];

  return dzsvg_shortcode_player($margs);
}
