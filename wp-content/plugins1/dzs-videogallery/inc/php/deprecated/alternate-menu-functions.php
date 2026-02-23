<?php


function dzsvg_altenate_menu_output($its){

  $i = 0;
  $k = 0;


  $current_urla = explode("?", dzs_curr_url());
  $current_url = $current_urla[0];

  $fout = '';
  $fout .= '
<style type="text/css">
.submenu{
margin:0;
padding:0;
list-style-type:none;
list-style-position:outside;
position:relative;
z-index:32;
}

.submenu a{
display:block;
padding:5px 15px;
background-color: #28211b;
color:#fff;
text-decoration:none;
}

.submenu li ul a{
display:block;
width:200px;
height:auto;
}

.submenu li{
float:left;
position:static;
width: auto;
position:relative;
}

.submenu ul, .submenu ul ul{
position:absolute;
width:200px;
top:auto;
display:none;
list-style-type:none;
list-style-position:outside;
}
.submenu > li > ul{
position:absolute;
top:auto;
left:0;
margin:0;
}

.submenu a:hover{
background-color:#555;
color:#eee;
}

.submenu li:hover ul, .submenu li li:hover ul{
display:block;
}
</style>';

  $fout .= '<ul class="submenu">';
  if (isset($dzsvg->mainitems)) {
    for ($k = 0; $k < count($dzsvg->mainitems); $k++) {
      if (count($dzsvg->mainitems[$k]) < 2) {
        continue;
      }
      $fout .= '<li><a href="#">' . $dzsvg->mainitems[$k]["settings"]["id"] . '</a>';

      if (isset($dzsvg->mainitems[$k]) && count($dzsvg->mainitems[$k]) > 1) {

        $fout .= '<ul>';
        for ($i = 0; $i < count($dzsvg->mainitems[$k]); $i++) {
          if (isset($dzsvg->mainitems[$k][$i]["thethumb"])) $fout .= '<li><a href="' . $current_url . '?the_source=' . $dzsvg->mainitems[$k][$i]["source"] . '&the_thumb=' . $dzsvg->mainitems[$k][$i]["thethumb"] . '&the_type=' . $dzsvg->mainitems[$k][$i]["type"] . '&the_title=' . $dzsvg->mainitems[$k][$i]["title"] . '">' . $dzsvg->mainitems[$k][$i]["title"] . '</a>';
        }
        $fout .= '</ul>';
      }
      $fout .= '</li>';
    }
  }

  $k = 0;
  $i = 0;
  $fout .= '</ul>
<div class="clearfix"></div>
<br>';

  if (isset($_REQUEST['the_source'])) {

    $the_source = esc_html($_REQUEST['the_source']);
    $the_type = esc_html($_REQUEST['the_type']);
    $the_thumb = esc_html($_REQUEST['the_thumb']);
    $fout .= '<a class="zoombox" data-type="video" data-videotype="' . $the_type . '" data-src="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($the_source) . '"><img class="item-image" src="';
    if ($its[$i]['thethumb'] != '') $fout .= $the_thumb; else {
      if ($its[$i]['type'] == "youtube") {
        $fout .= 'https://img.youtube.com/vi/' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($the_source) . '/0.jpg';
        $its[$i]['thethumb'] = 'https://img.youtube.com/vi/' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($the_source) . '/0.jpg';
      }
    }
    $fout .= '"/></a>';
  }


  wp_enqueue_style('dzsulb', DZSVG_URL . 'libs/ultibox/ultibox.css');
  wp_enqueue_script('dzsulb', DZSVG_URL . 'libs/ultibox/ultibox.js');


  return $fout;
}
