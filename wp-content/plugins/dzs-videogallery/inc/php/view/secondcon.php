<?php
function dzsvg_view_generateSecondConForUnderneathGallery($slidersIndex, $its){


  $fout = '';

  $fout .= '<div id="as' . $slidersIndex . '-secondcon" class="dzsas-second-con"><div class="dzsas-second-con--clip">';
  foreach ($its as $lab => $val) {
    if ($lab === 'settings') {
      continue;
    }


    $fout .= '<div class="item">';
    if (isset($val['title'])) {
      $fout .= '<h4>' . stripslashes($val['title']) . '</h4>';
    }

    if (isset($val['menuDescription']) && $val['menuDescription'] == 'as_description') {
      $val['menuDescription'] = $val['description'];
    }

    if (isset($val['menuDescription'])) {
      $fout .= '<div class="menudescriptioncon">' . $val['menuDescription'] . '</div>';
    }


    $fout .= '</div>';


  }
  $fout .= '</div></div>';
  wp_enqueue_style('dzsvg_second_con', DZSVG_SCRIPT_URL . 'parts/second-con/second-con.css');

  return $fout;

}