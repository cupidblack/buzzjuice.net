<?php

/**
 *
 * @param $dzsvg
 * @param $playlistData
 * @param $margs
 * @return void
 */

function dzsvg_view_displayMode_alternatewall_output($dzsvg, $playlistData, $margs) {

  $fout = '';
  $fout .= '<style>
.dzs-gallery-container .item{ width:23%; margin-right:1%; float:left; position:relative; display:block; margin-bottom:10px; }
.dzs-gallery-container .item-image{ width:100%; }
.dzs-gallery-container h4{  color:#D26; }
.dzs-gallery-container h4:hover{ background: #D26; color:#fff; }
.last { margin-right:0!important; }
.clear { clear:both; }
</style>';
  $fout .= '<div class="dzs-gallery-container">';


  $fout .= $dzsvg->classView->parse_items($playlistData, $margs);


  $fout .= '<div class="clear"></div>';
  $fout .= '</div>';


  if ($margs['settings_separation_mode'] == 'pages') {
    $fout .= '<div class="con-dzsvg-pagination">';


    for ($i = 0; $i < ceil(count($playlistData) / intval($margs['settings_separation_pages_number'])); $i++) {
      $str_active = '';
      if (($i + 1) == $margs[DZSVG_PLAYLIST_PAGINATION_QUERY_ARG_SHORT]) {
        $str_active = ' active';
      }
      $fout .= '<a class="pagination-number ' . $str_active . '" href="' . esc_url(add_query_arg(array(DZSVG_PLAYLIST_PAGINATION_QUERY_ARG => ($i + 1)), dzs_curr_url())) . '">' . ($i + 1) . '</a>';
    }
    $fout .= '</div>';
  }

  $fout .= '<div class="clear"></div>';


  // todo: zoombox to ultibox here
//            $fout .= '<script>jQuery(document).ready(function($){ jQuery(".zoombox").zoomBox(); });</script>';


  ClassDzsvgHelpers::enqueueUltibox();
}
