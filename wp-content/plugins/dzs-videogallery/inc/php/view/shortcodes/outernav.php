<?php


/**
 * [dzsvg_outernav id="theidofthegallery" skin="oasis" extraclasses="" thumbs_per_page="12" layout="layout-one-third"]
 * @param $pargs
 * @param null $content
 * @return string
 */
function dzsvg_shortcode_outernav($pargs, $content = null) {
  global $dzsvg;

  $fout = '';

  $margs = array(
    'id' => 'default',
    'skin' => 'oasis',
    'extraclasses' => '',
    'layout' => 'layout-one-fourth', // -- layout-one-fourth   layout-one-third   layout-width-370

    'thumbs_per_page' => '8',
  );
  if (is_array($pargs) == false) {
    $pargs = array();
  }
  $margs = array_merge($margs, $pargs);


  $id = $margs['id'];
  $original_id = $id;

  //---- extra galleries code

  $extra_galleries = array();
  if (strpos($id, ',') !== false) {
    $auxa = explode(",", $id);
    $id = $auxa[0];

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


  // -- outernavigation


  $css_classid = str_replace(' ', '_', $margs['id']);
  $fout .= '<div class="videogallery--navigation-outer ' . $margs['layout'] . ' videogallery--navigation-outer-for-' . $id . ' videogallery--navigation-outer-for-' . $css_classid . ' skin-' . $margs['skin'] . ' ' . $margs['extraclasses'] . '" data-vgtarget=".id_' . $css_classid . '" data-original-id="' . $original_id . '">';
  $fout .= '<div class="videogallery--navigation-outer--clip"><div class="videogallery--navigation-outer--clipmover">';

  $ix = 0;
  $maxblocksperrow = intval($margs['thumbs_per_page']);
  $nr_pages = 0;


  if ($maxblocksperrow == 0) {
    $maxblocksperrow = 1;
  }

  foreach ($its as $lab => $val) {
    if ($lab === 'settings') {
      continue;
    }
    if (!isset($val['source']) || $val['source'] == '') {
      continue;
    }


    if ($ix % $maxblocksperrow === 0) {
      $fout .= '<div class="videogallery--navigation-outer--bigblock';
      if ($ix === 0) {
        $fout .= ' active';
      }

      $fout .= '">';
    }


    $thumb = '';
    if (isset($val['thethumb'])) {
      $thumb = $val['thethumb'];
    }
    if ($thumb == '') {
      if (isset($val['thumb'])) {
        $thumb = $val['thumb'];
      }
    }


    if ($thumb == '') {
      if ($val['type'] == 'youtube') {
        $thumb = "https://img.youtube.com/vi/" . $val['source'] . "/0.jpg";
      }
      if ($val['type'] == 'vimeo') {
        $id = $val['source'];

        $target_file = "https://vimeo.com/api/v2/video/$id.php";
        $cache = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));

        $apiresp = $cache;
        $imga = unserialize($apiresp);


        $thumb = $imga[0]['thumbnail_medium'];

        if ($dzsvg->mainoptions['vimeo_thumb_quality'] == 'high') {

          $thumb = $imga[0]['thumbnail_large'];
        }
        if ($dzsvg->mainoptions['vimeo_thumb_quality'] == 'low') {

          $thumb = $imga[0]['thumbnail_small'];
        }


      }
    }


    $fout .= '<span class="videogallery--navigation-outer--block">';
    if ($margs['skin'] == 'oasis') {
      $fout .= '
<span class="block-thumb" style="background-image: url(' . $thumb . ');"></span>';
    }
    if ($margs['skin'] == 'balne') {
      $fout .= '
<span class="image-con"><span class="hover-rect"></span><img width="100%" height="100%" class="fullwidth" src="' . $thumb . '" data-global-responsive-ratio="0.562"/></span>';
    }
    $fout .= '<span class="block-title">' . $val['title'] . '</span>';

    if ((isset($its['settings']['enable_outernav_video_author']) && $its['settings']['enable_outernav_video_author'] == 'on') && isset($val['uploader']) && $val['uploader'] != '') {
      $fout .= '<span class="block-extra">' . esc_html__('by ', DZSVG_ID) . '<strong>' . $val['uploader'] . '</strong>' . '</span>';
    } else {
      if ((isset($its['settings']['enable_outernav_video_author']) && $its['settings']['enable_outernav_video_author'] == 'on') && isset($val['author_display_name']) && $val['author_display_name'] != '') {
        $fout .= '<span class="block-extra">' . esc_html__('by ', DZSVG_ID) . '<strong>' . $val['author_display_name'] . '</strong>' . '</span>';
      }
    }


    if ((isset($its['settings']['enable_outernav_video_date']) && $its['settings']['enable_outernav_video_date'] == 'on') && isset($val['upload_date']) && $val['upload_date']) {
      $fout .= '<span class="block-extra">' . esc_html__('on ', DZSVG_ID) . '<strong>' . date("d-m-Y", strtotime($val['upload_date'])) . '</strong>' . '</span>';
    }

    $fout .= '</span>';


    if ($ix % $maxblocksperrow === ($maxblocksperrow - 1)) {
      $fout .= '</div>';
      $nr_pages++;
    }


    $ix++;

  }

  // -- hier
  if ($ix % $maxblocksperrow <= ($maxblocksperrow - 1) && $ix % $maxblocksperrow > 0) {
    $fout .= '</div>';
    $nr_pages++;
  }
  $fout .= '</div></div>';

  if ($nr_pages > 1) {
    $fout .= '<div class="videogallery--navigation-outer--bullets-con">';
    for ($i = 0; $i < $nr_pages; ++$i) {
      $fout .= '<span class="navigation-outer--bullet';
      if ($i == 0) {
        $fout .= ' active';
      }
      $fout .= '"></span>';
    }
    $fout .= '</div>';
  }


  $fout .= '</div>';


  wp_enqueue_style('dzsvg_outer_nav', DZSVG_SCRIPT_URL . 'parts/navigation-outer/navigation-outer.css');

  return $fout;
}
