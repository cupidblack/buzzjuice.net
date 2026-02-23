<?php


function dzsvg_shortcode_video_playlist_title($pargs, $content = null) {

  global $dzsvg;

  $margs = array(
    'id' => 'auto', // -- default or round-white-bg
    'extra_style' => '', // -- default or round-white-bg
  );


  if (is_array($pargs)) {
    $margs = array_merge($margs, $pargs);
  }

  $fout = '';
  $fout .= '<div class="dzsvg-slider-title" style="' . $margs['extra_style'] . '">';



  if ($margs['id'] == 'auto') {


    if (isset($_GET['dzsvg_gallery_slug']) && $_GET['dzsvg_gallery_slug']) {

      $margs['id'] = sanitize_text_field($_GET['dzsvg_gallery_slug']);
    } else {

      $terms = get_terms($dzsvg->taxname_sliders, array(
        'hide_empty' => false,
      ));


      if (is_array($terms) && isset($terms[0])) {
        $margs['id'] = $terms[0]->slug;
      }
    }

    $term = get_term_by('slug', $margs['id'], DZSVG_POST_NAME__SLIDERS);


    if ($term) {

      $fout .= $term->name;
    }
  }
  $fout .= '</div>';


  return $fout;


}


