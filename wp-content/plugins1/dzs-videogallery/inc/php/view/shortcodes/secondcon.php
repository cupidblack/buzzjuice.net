<?php

/**
 * [dzsvg_secondcon id="example-youtube-channel-outer" extraclasses="skin-balne" enable_readmore="on" ]
 * @param $pargs
 * @param null $content
 * @return string
 */
function dzsvg_shortcode_secondcon($pargs, $content = null) {
  global $dzsvg;
  // --

  $fout = '';

  $margs = array(
    'id' => 'default',
    'extraclasses' => '',
    'enable_readmore' => 'off',
  );
  if (is_array($pargs) == false) {
    $pargs = array();
  }
  $margs = array_merge($margs, $pargs);


  wp_enqueue_style('dzs.advancedscroller', DZSVG_URL . 'assets/advancedscroller/plugin.css');
  wp_enqueue_script('dzs.advancedscroller', DZSVG_URL . 'assets/advancedscroller/plugin.js');


  $id_main = $margs['id'];
  $id = $margs['id'];
  $original_id = $id;


  $extra_galleries = array();
  if (strpos($id, ',') !== false) {
    $auxa = explode(",", $id);
    $id = $auxa[0];

    $id_main = $auxa[0];
    unset($auxa[0]);
    $extra_galleries = $auxa;
  }


  $gallery_margs = array(
    'id' => $margs['id'],
    'return_mode' => 'items',
  );

  $its = dzsvg_shortcode_videogallery($gallery_margs);


  foreach ($extra_galleries as $extragal) {
    $args = array(
      'id' => $extragal,
      'return_mode' => 'items',
      'called_from' => 'extra_galleries',

    );


    foreach (dzsvg_shortcode_videogallery($args) as $lab => $it3) {
      if ($lab === 'settings') {
        continue;
      }
      array_push($its, $it3);
    }
  }


  $css_classid = str_replace(' ', '_', $id_main);


  $fout .= '<div class="dzsas-second-con dzsas-second-con-for-' . $css_classid . ' ' . $margs['extraclasses'] . '"  data-vgtarget=".id_' . $css_classid . '" data-original-id="' . $original_id . '">';

  if ($margs['enable_readmore'] == 'on') {
    $fout .= '<div class="read-more-con">';
    $fout .= '<div class="read-more-content">';
  }


  $fout .= '<div class="dzsas-second-con--clip">';
  foreach ($its as $lab => $itemArr) {
    if ($lab === 'settings') {
      continue;
    }

    $desc = '';
    $title = '';
    $itemArr = ClassDzsvgHelpers::sanitizeWpPostToVideoItem($itemArr);


    if (isset($itemArr['description'])) {

      $desc = $itemArr['description'];
    }
    if (isset($itemArr['title'])) {
      $title = $itemArr['title'];
    }

    // -- secondcon

    $maxlen = 100;
    if (isset($its['settings']['maxlen_desc']) && $its['settings']['maxlen_desc']) {
      $maxlen = $its['settings']['maxlen_desc'];
    }
    if (isset($its['settings']['desc_different_settings_for_aside']) && $its['settings']['desc_different_settings_for_aside'] == 'on') {

      if (isset($its['settings']['desc_aside_maxlen_desc']) && $its['settings']['desc_aside_maxlen_desc']) {
        $maxlen = $its['settings']['desc_aside_maxlen_desc'];
      }
    }


    $striptags = false;

    if (isset($its['settings']['striptags']) && $its['settings']['striptags'] === 'on') {
      $striptags = true;
    }

    $try_to_close_unclosed_tags = false;


    if (isset($itemArr['description']) && $itemArr['description']) {
      $desc = '' . wp_kses(dzs_get_excerpt(-1, array('content' => $itemArr['description'], 'maxlen' => $maxlen, 'striptags' => $striptags,)), (DZSVG_HTML_ALLOWED_TAGS));
    }


    $fout .= '<div class="item">
<h4>' . $title . '</h4>
<p>' . $desc . '</p>
</div>';


  }


  if ($margs['enable_readmore'] == 'on') {
    $fout .= '</div>';
    $fout .= '</div>';
    $fout .= '<div class="read-more-label"> <i class="fa fa-angle-down"></i> <span>' . esc_html__("DETAILS") . '</span></div>';


  } else {

  }
  $fout .= '</div></div>';


  wp_enqueue_style('dzsvg_second_con', DZSVG_SCRIPT_URL . 'parts/second-con/second-con.css');

  return $fout;


}
