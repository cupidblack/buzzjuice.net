<?php

/**
 * [videogallerylinks ids="2,3" height="300" source="pathtomp4.mp4" type="normal"]
 * @param $atts
 * @param null $content
 * @return string
 */
function dzsvg_shortcode_links($atts, $content = null) {

  global $dzsvg;
  global $post;
  $fout = '';

  $dzsvg->front_scripts();

  $args = array('ids' => '', 'width' => 400, 'height' => 300, 'source' => '', 'sourceogg' => '', 'type' => 'normal', 'autoplay' => 'on', 'design_skin' => 'skin_aurora', 'gallery_nav_type' => 'thumbs', 'menuitem_width' => '275', 'menuitem_height' => '75', 'menuitem_space' => '1', 'settings_ajax_extradivs' => '',);
  $args = array_merge($args, $atts);
  if ($args['gallery_nav_type'] == 'scroller') {
    wp_enqueue_style('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.css');
    wp_enqueue_script('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.js');
  }
  $its = array();
  $ind_post = 0;
  $array_ids = explode(',', $args['ids']);

  foreach ($array_ids as $id) {
    $po = get_post($id);
    array_push($its, $po);
  }

  $dzsvg->sliders_index++;

  $fout .= '<div class="videogallery-with-links">';

  $fout .= '<div class="videogallery-con currGallery" style="width:' . $args['menuitem_width'] . 'px; height:' . $args['height'] . 'px; float:right; padding-top: 0; padding-bottom: 0;">';
  $fout .= '<div class="vg' . $dzsvg->sliders_index . ' videogallery skin_default" >';

  $i = 0;
  foreach ($its as $it) {


    $the_src = wp_get_attachment_image_src(get_post_thumbnail_id($it->ID), 'full');
    $fout .= '<div class="vplayer-tobe" data-videoTitle="' . $it->post_title . '" data-type="link" data-src="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces(get_permalink($it->ID)) . '">
<div class="menuDescription from-vg-with-links"><img src="' . $the_src[0] . '" class="imgblock"/>
<div class="the-title from-show-shortcode-links">' . $it->post_title . '</div><div class="paragraph">' . $it->post_excerpt . '</div></div>
</div>';
    if ($it->ID == $post->ID) {
      $ind_post = $i;
    }
    $i++;
  }

  $fout .= '</div>'; // -- end vg
  $fout .= '<div class="dzsvg-preloader"></div>';
  $fout .= '</div>'; // -- end vg-con
  $fout .= '';

  // -- shortcode links
  $fout .= '<div class="history-video-element" style="overflow: hidden;">
<div class="vphistory vplayer-tobe" data-videoTitle="" data-img="" data-type="' . $args['type'] . '" data-src="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($args['source']) . '"';
  if ($args['sourceogg'] != '') {
    if (strpos($args['sourceogg'], '.webm') === false) {
      $fout .= ' data-sourceogg="' . $args['sourceogg'] . '"';
    } else {
      $fout .= ' data-sourcewebm="' . $args['sourceogg'] . '"';
    }
  }
  $fout .= '>
</div>
<div class="nest-script">
<div class="toexecute" style="display:none">
jQuery(document).ready(function($){
    var videoplayersettings = {
        autoplay : "' . $args['autoplay'] . '"
        ,settings_hideControls : "off"
        ,design_skin: "skin_aurora"
        
    };
    $(".vphistory").vPlayer(videoplayersettings);
})
</div>
</div>
</div>';

  $fout .= '<script>
jQuery(".toexecute").each(function(){
    var _t = jQuery(this);
    if(_t.hasClass("executed")==false){
        eval(_t.text());
        _t.addClass("executed");
    }
})
jQuery(document).ready(function($){
dzsvg_init(".vg' . $dzsvg->sliders_index . '", {
    totalWidth:"' . $args['menuitem_width'] . '"
    ,settings_mode:"normal"
    ,menuSpace:0
    ,randomise:"off"
    ,autoplay :"' . $args['autoplay'] . '"
    ,cueFirstVideo: "off"
    ,autoplayNext : "on"
    ,nav_type: "' . $args['gallery_nav_type'] . '"
    ,menuitem_width:"' . $args['menuitem_width'] . '"
    ,menuitem_height:"' . $args['menuitem_height'] . '"
    ,menuitem_space:"' . $args['menuitem_space'] . '"
    ,menu_position:"right"
    ,transition_type:"fade"
    ,design_skin: "skin_navtransparent"
    ,embedCode:""
    ,shareCode:""
    ,logo: ""
    ,design_shadow:"off"
    ,settings_disableVideo:"on"
    ,startItem: "' . $ind_post . '"
    ,settings_enableHistory: "on"
        ,settings_ajax_extraDivs : "' . $args['settings_ajax_extradivs'] . '"
});
});
</script>';
  $fout .= '</div>';

  return $fout;
}