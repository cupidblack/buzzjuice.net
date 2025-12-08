<?php

function dzsvg_debug_getDebugInfo($dzsvg, $fout){

  if ($dzsvg->mainoptions['debug_mode'] == 'on') {
    $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('memory usage - ', DZSVG_ID) . '</div>
<div class="toggle-content">';
    $fout .= 'memory usage - ' . memory_get_usage() . "\n <br>memory limit - " . ini_get('memory_limit');
    $fout .= '</div></div>';

  }
}
