<?php


function dzsvg_shortcode_cats($atts, $content = null) {
  global $dzsvg;
  $fout = '';
  $margs = array('width' => '100', 'height' => 400,);

  $margs = array_merge($margs, $atts);


  // -- some sanitizing
  $str_tw = $margs['width'];
  $str_th = $margs['height'];


  if (strpos($str_tw, "%") === false) {
    $str_tw = $str_tw . 'px';
  }
  if (strpos($str_th, "%") === false && $str_th != 'auto') {
    $str_th = $str_th . 'px';
  }


  $lb = array("\r\n", "\n", "\r", "<br />");
  $content = str_replace($lb, '', $content);


  $aux = do_shortcode($content);;

  $fout .= '<div class="categories-videogallery" id="cats' . (++$dzsvg->cats_index) . '">';
  $fout .= '<div class="the-categories-con"><span class="label-categories">' . esc_html__('categories', DZSVG_ID) . '</span></div>';
  $fout .= $aux;
  $fout .= '</div>';
  $fout .= '<script>jQuery(document).ready(function($){ setup_videogalleryCategories("#cats' . $dzsvg->cats_index . '"); });</script>';

  return $fout;
}