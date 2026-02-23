<?php

function dzsvg_debug_parse_items_request($title, $debugVars){

  if(!headers_sent()){
    ob_start();
  }

    echo '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . $title . '</div>
<div class="toggle-content">';
    foreach ($debugVars as $debugVar){
      echo $debugVar['title'].' -> ';
      echo '<textarea style="width: 100%; max-height: 250px;"  readonly>'.print_r($debugVar['content'],true).'</textarea>';
    }

    echo '</div></div>';

    ClassDzsvgHelpers::enqueueDzsToggle();


  if(!headers_sent()){
    error_log(ob_get_clean());
  }
}