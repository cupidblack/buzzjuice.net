<?php

function dzsvg_view_parseItems_generateItemProperties($dzsvg, $itemInstances, $i, $videoTitle, $videoType, $che){

  if(!isset($itemInstances['settings'])){
    $itemInstances['settings'] = array();
  }

  $fout = '';
  if (isset($itemInstances['settings']['displaymode']) && $itemInstances['settings']['displaymode'] == 'normal' && ((isset($itemInstances['settings']['menu_description_format']) && $itemInstances['settings']['menu_description_format']) || $dzsvg->mainoptions['use_layout_builder_on_navigation'] == 'on')) {
    // -- new format


  } else {
  }


  $fout .= '<div hidden class="feed-menu-number" aria-hidden="true">' . ($i + 1) . '</div>
<div hidden class="feed-menu-title" aria-hidden="true">' . $videoTitle . '</div>';


  $thumbnailSrc = '';
  if (isset($che['thumbnail'])) {
    $thumbnailSrc = ClassDzsvgHelpers::sanitizeCheToThumbnailUrlSource($che['thumbnail'], $che);
  }
  $fout .= '<div hidden class="feed-menu-image" aria-hidden="true">' . $thumbnailSrc . '</div>';

  $fout .= '<div hidden class="feed-menu-desc" aria-hidden="true">' . $che['menuDescription'] . '</div>';

  if (isset($che['total_duration'])) {
    $fout .= '<div hidden class="feed-menu-time" aria-hidden="true">' . $che['total_duration'] . '</div>';
  }


  return $fout;









  $fout .= '<div class="menuDescription from-parse-items">';


  $thumbclass = 'imgblock';


  if (isset($itemInstances['settings']['thumb_extraclass']) && $itemInstances['settings']['thumb_extraclass'] != '') {
    $thumbclass .= ' ' . $itemInstances['settings']['thumb_extraclass'];
  }

  if (isset($itemInstances['settings']['nav_type']) && $itemInstances['settings']['nav_type'] == 'outer') {
    $thumbclass = 'imgfull';
  }

  if (isset($che['thumbnail']) && $che['thumbnail']) {

    if (!(isset($che['thethumb']) && $che['thethumb'])) {
      $che['thethumb'] = $che['thumbnail'];
    }
  }

  if (isset($che['thethumb']) && $che['thethumb']) {

    $fout .= '<div data-imgsrc="' . ClassDzsvgHelpers::sanitize_from_anchor_to_shortcode_attr($che['thethumb']) . '"   class="divimage ' . $thumbclass . '"></div>';
  } else {
    if ($videoType == 'youtube') {
      $fout .= '{ytthumb}';
    }
  }


  $fout .= '<div class="dzs-navigation--item--title-and-meta">';


  $fout .= '';

  if ((isset($itemInstances['settings']['disable_title']) == false || $itemInstances['settings']['disable_title'] != 'on') && $videoTitle) {
    $fout .= '<div class="the-title from-parse-items">' . $videoTitle . '</div>';
  }


  if ($che['menuDescription'] == '<p></p>') {
    $che['menuDescription'] = '';
  }


  $str_menu_description = '';


  if (((isset($itemInstances['settings']['disable_menu_description']) == false) || $itemInstances['settings']['disable_menu_description'] != 'on') && isset($che['menuDescription']) && $che['menuDescription'] && $che['menuDescription'] != '<p></p>') {

    $str_menu_description = '<div class="paragraph from-menu-desc-parse-items">';


    $str_menu_description .= $che['menuDescription'];

    if (strrpos($str_menu_description, '</') === strlen($str_menu_description) - 2) {
      $str_menu_description = substr($str_menu_description, 0, -2);
    }

    $str_menu_description .= '</div>';
  }


  $fout .= $str_menu_description;

  $fout .= '</div><!-- end .title-and-meta -->';

  $fout .= '</div>'; // -- menuDescription END
}