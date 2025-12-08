<?php

function dzsvg_shortcode_div_clear($pargs, $content = null) {
  global $dzsvg;
  $fout = '';
  $fout .= '<div style="clear: both; "></div>';

  return $fout;
}

function dzsvg_shortcode_div($atts, $content = null) {

  $fout = '';
  global $dzsvg;


  $margs = array(
    'style' => '',
  );

  if ($atts) {

    $margs = array_merge($margs, $atts);
  }

  $fout .= ' <div class="dzsvg-div" style=\'';

  $fout .= $margs['style'];


  $fout .= '\'>';
  $fout .= do_shortcode($content);
  $fout .= '</div>';

  return $fout;


}

function dzsvg_shortcode_player_button($atts, $content = null) {

  $fout = '';
  include(DZSVG_PATH . "class_parts/shortcode_player_button.php");

  return $fout;

}



function dzsvg_shortcode_default_video_playlist($atts, $content = null) {

  global $dzsvg;
  $margs = array(
    'ids' => '',
  );

  if ($atts) {

    $margs = array_merge($margs, $atts);
  }


  $fout = '';


  $args = array(
    'id' => 'default_video_playlist',
    'ids' => $margs['ids'],
  );

  $fout .= dzsvg_shortcode_videogallery($args);

  return $fout;
}