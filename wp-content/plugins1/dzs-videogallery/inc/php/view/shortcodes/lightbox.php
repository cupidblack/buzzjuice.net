<?php

/**
 * example: [videogallerylightbox id="already_self_host_videos"]launch[/videogallerylightbox]
 *
 * @return mixed output shortcode
 */
function dzsvg_shortcode_lightbox($atts, $content = null) {
  global $dzsvg;

  $fout = '';

  $dzsvg->front_scripts();

  ClassDzsvgHelpers::enqueueUltibox();


  $margs = array(
    'id' => 'default',
    'db' => '',
    'category' => '',
    'width' => '',
    'height' => '',
    'gallerywidth' => '800',
    'galleryheight' => '500'
  );
  $margs = array_merge($margs, $atts);
  $fout .= '<div class="' . DZSVG_VIEW_ULTIBOX_ITEM_DELEGATED_CLASS . '"';

  $css_number_id = rand(0, 999999);


  $fout .= '   data-source=".gallery-precon' . $css_number_id . '" data-type="inlinecontent" data-box-bg="#FFFFFF"   data-biggallery="ulti2" data-inline-move="on" >' . $content . '</div>';


  $fout .= dzsvg_shortcode_videogallery(array(
    'called_from' => 'show_shortcode_lightbox',
    'init_from' => 'ultibox',
    'id' => $margs['id'],
    'css_number_id' => $css_number_id,
  ));


//        todo: zoombox to ultibox


  return $fout;
}