<?php

function dzsvg_view_parse_items_showcase($its, $pargs) {
  global $post, $dzsvg;
  $fout = '';

  $margs = $pargs;
  $dzsvg->sliders_index++;


  $slider_index = $dzsvg->sliders_index;


  $skin_vp = 'skin_aurora';
  $vpsettings = array();


  if ($margs['vpconfig']) {


    if (isset($its) == false || is_array($its) == false) {
      $its = array();
    }
    if (isset($its['settings']) == false || is_array($its['settings']) == false) {
      $its['settings'] = array();
    }
    if (isset($its['settings']['vpconfig']) == false) {
      $its['settings']['vpconfig'] = 'default';
    }


    $vpsettings = ClassDzsvgHelpers::view_getVpConfig($its['settings']['vpconfig']);


    if (is_array($its['settings']) == false) {
      $its['settings'] = array();
    }


  }


  if ($margs['mode'] == 'ullist') {
    $fout .= '<ul class="dzsvp-showcase type-' . $margs['type'] . ' mode-' . $margs['mode'] . '">';
  }

  if ($margs['mode'] == 'list') {
    $fout .= '<div class="dzsvp-showcase type-' . $margs['type'] . ' mode-' . $margs['mode'] . '">';
  }
  if ($margs['mode'] == 'scroller') {

    wp_enqueue_style('dzs.advancedscroller', DZSVG_URL . 'assets/advancedscroller/plugin.css');
    wp_enqueue_script('dzs.advancedscroller', DZSVG_URL . 'assets/advancedscroller/plugin.js');

    $fout .= '<div id="dzsvpas' . $slider_index . '" class="advancedscroller auto-height item-padding-20 skin-black dzsvp-showcase type-' . $margs['type'] . ' mode-' . $margs['mode'] . '">';
    $fout .= '<ul class="items">';
  }
  if ($margs['mode'] == 'scrollmenu') {

    wp_enqueue_style('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.css');
    wp_enqueue_script('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.js');

    $fout .= '<div  class="dzs_slideshow_' . $slider_index . ' scroller-con skin_royale scrollbars-inset  dzsvp-showcase type-' . $margs['type'] . ' mode-' . $margs['mode'] . '"  style="width: 100%;	height: ' . $margs['mode_scrollmenu_height'] . 'px;" data-options="">';
    $fout .= '<div class="inner" style=""><div class="gallery-items skin-viva">';
  }
  if ($margs['mode'] == 'featured') {

    wp_enqueue_style('dzs.advancedscroller', DZSVG_URL . 'assets/advancedscroller/plugin.css');
    wp_enqueue_script('dzs.advancedscroller', DZSVG_URL . 'assets/advancedscroller/plugin.js');


    $fout .= '<div class="real-showcase-featured dzsvp-showcase type-' . $margs['type'] . ' mode-' . $margs['mode'] . '">';
    $fout .= '<div class=" dzspb_lay_con">';
    $fout .= '<div class="dzspb_layb_two_third" style="    float: none;    display: inline-block;
    vertical-align: middle;">';
    $fout .= '<div id="dzsvpas' . $slider_index . '" class="advancedscroller skin-inset auto-height" >';
    $fout .= '<ul class="items">';
  }
  if ($margs['mode'] == 'layouter') {

    wp_enqueue_style('dzs.layouter', DZSVG_URL . 'assets/dzslayouter/dzslayouter.css');
    wp_enqueue_script('dzs.layouter', DZSVG_URL . 'assets/dzslayouter/dzslayouter.js');
    wp_enqueue_script('masonry', DZSVG_URL . 'assets/dzslayouter/masonry.pkgd.min.js');


    $fout .= '<div class="dzslayouter auto-init skin-loading-grey transition-fade hover-arcana" style="" data-options="{prefferedclass: \'wides\', settings_overwrite_margin: \'0\', settings_lazyload: \'on\'}"><ul class="the-items-feed">';
  }
  $taxonomy = DZSVG_POST_NAME__CATEGORY;
  if ($margs['mode'] == 'zfolio') {


    wp_enqueue_style('zfolio', DZSVG_URL . 'libs/zfolio/zfolio.css');
    wp_enqueue_script('zfolio', DZSVG_URL . 'libs/zfolio/zfolio.js');

    ClassDzsvgHelpers::enqueueUltibox();
    wp_enqueue_script('zfolio.isotope', DZSVG_URL . 'libs/zfolio/jquery.isotope.min.js');


    $fout .= '<div class="zfolio zfolio' . $slider_index . ' ' . $margs['mode_zfolio_skin'] . '  delay-effects  ';

    $fout .= '"';

    if ($margs['mode_zfolio_gap'] == '1px') {
      $fout .= ' data-margin="1"';
    }

    $fout .= ' data-options=\'\'>
 
 ';


    if ($margs['mode_zfolio_show_filters'] == 'on') {

      $cas = array();

      if (isset($margs['cat']) && $margs['cat']) {

        $cas = explode(',', $margs['cat']);

      } else {
        if ($margs['mode_zfolio_show_filters'] == 'on') {

          $cas = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
          ));


        }
      }


      foreach ($cas as $ca) {


        if (is_object($ca)) {
          $cat = $ca;
        } else {

          $ca = ClassDzsvgHelpers::sanitize_termSlugToId($ca);
          $cat = get_term($ca, $taxonomy);
        }


        if (isset($cat->term_id)) {

          // -- set terms here
          $fout .= '<div class="feed-zfolio-zfolio-term" data-termid="' . $cat->term_id . '">' . $cat->name . '</div>';
        }
      }

    }


    $fout .= ' <div class="items ';


    if ($margs['mode_zfolio_layout'] == '5columns') {
      $fout .= ' dzs-layout--5-cols';
    }
    if ($margs['mode_zfolio_layout'] == '4columns') {
      $fout .= ' dzs-layout--4-cols';
    }
    if ($margs['mode_zfolio_layout'] == '3columns') {
      $fout .= ' dzs-layout--3-cols';
    }
    if ($margs['mode_zfolio_layout'] == '2columns') {
      $fout .= ' dzs-layout--2-cols';
    }
    if ($margs['mode_zfolio_layout'] == '1column') {
      $fout .= ' dzs-layout--1-cols';
    }

    $fout .= '">';
  }

  $ii = 0;

  foreach ($its as $lab => $itval) {


    $it_default = array(

      'thumbnail' => '',
      'author_display_name' => '',
      'type' => 'video',
      'permalink' => '',
      'permalink_selected' => 'default',
      'permalink_to_post' => '',

      'title' => '', 'description' => '', 'extra_classes' => '', 'source' => '', // -- the mp4 link, image source, vimeo id or youtube id ( should already be parsed )
    );

    if ($lab === 'settings') {
      continue;
    }


    $its[$lab] = array_merge($it_default, $itval);

    $it = $its[$lab];

    $str_featuredimage = '';

    // -- start SANITIZING
    if (!(isset($it['id']) && ($it['id']))) {
      if (isset($it['ID']) && ($it['ID'])) {
        $it['id'] = $it['ID'];
      }
    }


    if ($margs['linking_type'] == 'zoombox') {

      if ($its[$lab]['permalink']) {

      } else {

        if ($its[$lab]['source']) {
          $its[$lab]['permalink'] = $its[$lab]['source'];
        }
      }
    }

    if ($its[$lab]['permalink']) {

    } else {

      if ($its[$lab]['permalink_to_post']) {
        $its[$lab]['permalink'] = $its[$lab]['permalink_to_post'];
      }
    }

    if ($its[$lab]['type'] == 'vimeo') {

      if ($its[$lab]['permalink_to_post']) {
      } else {

        $its[$lab]['permalink_to_post'] = $its[$lab]['permalink'];
      }
    }


    if ($its[$lab]['permalink_selected'] == 'default') {
      $its[$lab]['permalink_selected'] = $its[$lab]['permalink_to_post'];
    }


    // -- try to figure out thumbnail START
    if ($its[$lab]['thumbnail']) {
    } else {

      if ($its[$lab]['type'] == 'youtube') {

        $yt_id = $its[$lab]['source'];;


        if (strpos($yt_id, 'youtube.com/') !== false) {
          $yt_id = DZSHelpers::get_query_arg($yt_id, 'v');
        }

        $its[$lab]['thumbnail'] = 'https://img.youtube.com/vi/' . $yt_id . '/0.jpg';


        if ($margs['type'] == 'video_items') {

          update_post_meta($its[$lab]['id'], 'dzsvp_thumb', 'https://img.youtube.com/vi/' . $yt_id . '/0.jpg');


        }
      }
      if ($its[$lab]['type'] == 'vimeo') {

        $yt_id = $its[$lab]['source'];


        if (strpos($yt_id, 'vimeo.com/') !== false) {
          $yt_id = DZSHelpers::get_query_arg($yt_id, 'v');
        }


        $hash = unserialize(DZSHelpers::get_contents("https://vimeo.com/api/v2/video/$yt_id.php"));

        $its[$lab]['thumbnail'] = $hash[0]['thumbnail_medium'];

        if ($margs['type'] == 'video_items') {

          update_post_meta($its[$lab]['id'], 'dzsvp_thumb', $hash[0]['thumbnail_medium']);


        }
      }
    }

    // -- try to figure out thumbnail END


    if ($margs['desc_readmore_markup'] == 'default') {
      if ($margs['mode'] == 'scrollmenu') {
        $margs['desc_readmore_markup'] = ' <span style="opacity:0.75;">[...]</span>';
      }
    }

    if ($margs['desc_readmore_markup'] == 'default') {
      $margs['desc_readmore_markup'] = '';
    }


    $desc = $its[$lab]['description'];


    $maxlen = $margs['desc_count'];

    $desc = DZSHelpers::wp_get_excerpt(-1, array(
      'content' => $desc,
      'maxlen' => $maxlen,
      'aftercutcontent_html' => ' [ ... ] ',

    ));


    $extra_attr = ''; // -- extra attr for the blank container elements
    $extra_attr_for_zoombox = ''; // -- extra attr for zoombox ( data-biggallery )
    $extra_classes_for_zoombox = ''; // -- apply zoombox class


    $thumb_url_sanitized = dzs_sanitize_to_url($its[$lab]['thumbnail']);


    if ($margs['linking_type'] == 'zoombox') {
      $extra_classes_for_zoombox .= ' ' . DZSVG_VIEW_ULTIBOX_ITEM_DELEGATED_CLASS;
      $extra_attr_for_zoombox .= ' data-type="' . $its[$lab]['type'] . '"  data-biggallery="ullist' . $slider_index . '"  data-biggallerythumbnail="' . $thumb_url_sanitized . '"';


      ClassDzsvgHelpers::enqueueDzsVpPlayer();
      ClassDzsvgHelpers::enqueueDzsVgPlaylist();

    }


    // -- start modes
    // -----
    if ($margs['mode'] == 'ullist') {


      if ($margs['linking_type'] == 'zoombox') {
        $extra_classes_for_zoombox .= ' ' . DZSVG_VIEW_ULTIBOX_ITEM_DELEGATED_CLASS;
        $extra_attr_for_zoombox .= ' data-type="' . $its[$lab]['type'] . '"  data-biggallery="ullist' . $slider_index . '"  data-biggallerythumbnail="' . $thumb_url_sanitized . '"';


        ClassDzsvgHelpers::enqueueDzsVpPlayer();
        ClassDzsvgHelpers::enqueueDzsVgPlaylist();


      }

      $fout .= '<li><a class="' . $extra_classes_for_zoombox . '" href="' . $its[$lab]['permalink'] . '"' . $extra_attr_for_zoombox . '>' . $its[$lab]['title'] . '</a></li>';
    }
    // -- ullist END


    if ($margs['mode'] == 'list') {
      $fout .= '<div class="dzsvp-item" data-id="' . $its[$lab]['id'] . '">';
      $fout .= '<div class="dzspb_lay_con">';
      if ($its[$lab]['thumbnail']) {

        $fout .= '<div class="dzspb_layb_one_fourth">';
        $fout .= '<a class="' . $extra_classes_for_zoombox . '" href="' . $its[$lab]['permalink'] . '"' . $extra_attr_for_zoombox . '>';
        $fout .= '<img width="100%" src="' . $its[$lab]['thumbnail'] . '" style="width:100%;"/>';
        $fout .= '</a>';
        $fout .= '</div>';
        $fout .= '<div class="dzspb_layb_three_fourth">';
        $fout .= '<h4 style="margin-top:2px; margin-bottom: 5px;"><a class="' . $extra_classes_for_zoombox . '" href="' . $its[$lab]['permalink'] . '"' . $extra_attr . '>' . $its[$lab]['title'] . '</a></h4>';
        if ($its[$lab]['author_display_name']) {

          $fout .= '<p>by <em>' . $its[$lab]['author_display_name'] . '</em></p>';
        }


        if ($margs['mode_list_enable_view_count'] == 'on') {

          wp_enqueue_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
          $fout .= '<div class="clear"></div><div class="item-meta-list">';
          $fout .= '<div class="counter-hits">';
          $fout .= '<i class="fa fa-play"></i> <span class="the-label">';
          $fout .= DzsvgAjax::mysql_get_views($its[$lab]['id']) . esc_html__(" views", 'dzsvg');
          $fout .= '</span>';
          $fout .= '</div>';
          $fout .= '</div>';
        }

        $fout .= '<div class="paragraph">' . $desc . '</div>';
        $fout .= '</div>';
      } else {

        $fout .= '<div class="dzspb_layb_one_full">';
        $fout .= '<h4 style="margin-top:2px; margin-bottom: 5px;"><a class="' . $extra_classes_for_zoombox . '" href="' . $it['permalink'] . '"' . $extra_attr_for_zoombox . '>' . $its[$lab]['title'] . '</a></h4>';


        if ($its[$lab]['author_display_name']) {

          $fout .= '<p>by <em>' . $its[$lab]['author_display_name'] . '</em></p>';
        }
        $fout .= '<div class="paragraph">' . $desc . '</div>';
        $fout .= '</div>';
      }
      $fout .= '</div>';
      $fout .= '</div>';
    }


    if ($margs['mode'] == 'list-2') {
      $fout .= '<div class="dzsvp-item">';
      $fout .= '<div class="dzspb_lay_con">';

      $fout .= '<div class="dzspb_layb_one_full">';
      $fout .= '<a class="' . $extra_classes_for_zoombox . '" href="' . $it['permalink'] . '"' . $extra_attr_for_zoombox . '>';
      $fout .= '<img width="100%" src="' . $it['thumbnail'] . '" class="fullwidth" style="width:100%;"/>';
      $fout .= '</a>';
      $fout .= '<h4 style="margin-top:2px; margin-bottom: 5px; text-align: center; "><a class="' . $extra_classes_for_zoombox . '" href="' . $it['permalink'] . '"' . $extra_attr . '>' . $it['title'] . '</a></h4>';
      $fout .= '</div>';

      $fout .= '</div>';
      $fout .= '</div>';
    }


    if ($margs['linking_type'] == 'zoombox') {

      ClassDzsvgHelpers::enqueueUltibox();


    }


    if ($margs['mode'] == 'zfolio') {
      // -- showcase zfolio

      $src = $it['source'];

      if ($it['type'] == 'vimeo') {

        $src = 'https://vimeo.com/' . $src;
      }


      $extra_classes_for_zoombox = '';

      if ($margs['linking_type'] === 'zoombox') {
        $extra_classes_for_zoombox = ' zoombox';
        $extra_classes_for_zoombox .= ' ' . DZSVG_VIEW_ULTIBOX_ITEM_DELEGATED_CLASS;
      }


      $fout .= '<div class="zfolio-item';


      if ($margs['mode_zfolio_enable_special_layout'] == 'on') {


        switch ($ii % 5) {
          case 0:
            $fout .= ' layout-tall';
            break;
          case 1:
            $fout .= ' layout-big';
            break;
          case 2:
            $fout .= ' layout-wide';
            break;
          default:
            $fout .= ' ';
            break;
        }
      }


      if ($margs['mode_zfolio_show_filters'] == 'on') {
        if (isset($it['cats'])) {

          $cas = explode(',', $it['cats']);


          foreach ($cas as $ca) {


            $cat = get_term($ca, $taxonomy);


            if (isset($cat->term_id)) {

              $fout .= ' termid-' . $cat->term_id . '';
            }
          }
        }
      }


      $thumb = $it['thumbnail'];


      if ($thumb == '') {
        if ($its[$lab] && isset($its[$lab]['id'])) {
          $thumb = ClassDzsvgHelpers::get_post_thumb_src($its[$lab]['id']);

        } else {
          if ($thumb_url_sanitized) {

            $thumb = $thumb_url_sanitized;
          }
        }
      }


      $permalink = $it['permalink'];


      if ($permalink == '') {
        if ($extra_classes_for_zoombox) {
          $permalink = $it['source'];
        }
      }

      $fout .= '" data-dzsvgindex="' . $ii . '"   data-overlay_extra_class="" style="" >
                                <div class="zfolio-item--inner">
                                <div class="zfolio-item--inner--inner">
                                <div class="zfolio-item--inner--inner--inner">
                                    <a href="' . $permalink . '" data-type="' . $it['type'] . '" class="the-feature-con ' . $extra_classes_for_zoombox . '" style="" data-biggallery="zfolio' . $slider_index . '" data-biggallerythumbnail="' . $thumb_url_sanitized . '"><div class="the-feature" style="background-image: url(' . $thumb . ');"></div><div class="the-overlay"></div></a>
                                    <div class="item-meta">';


      $fout .= '<div class="the-title items-showcase--item--title">';


      if ($margs['mode_zfolio_title_links_to'] == 'direct_link' || $margs['mode_zfolio_title_links_to'] == 'zoombox') {

        $fout .= '<span class="';


        if ($margs['mode_zfolio_title_links_to'] == 'zoombox') {
          $fout .= ' zoombox';
        }


        $fout .= '" href="';

        if ($margs['mode_zfolio_title_links_to'] == 'zoombox') {

          $fout .= $it['source'];
        } else {
          if (isset($it['id'])) {
            $fout .= get_permalink($it['id']);
          }
        }


        $fout .= '">';
      }
      if ($margs['mode_zfolio_title_links_to'] == 'direct_link_a') {

        $fout .= '<a class="title--mode-zfolio-links-to-direct_link_a" href="' . get_permalink($it['id']) . '">';
      }


      $fout .= $it['title'];

      if ($margs['mode_zfolio_title_links_to'] == 'direct_link') {

        $fout .= '</span>';
      }
      if ($margs['mode_zfolio_title_links_to'] == 'direct_link_a') {

        $fout .= '</a>';
      }

      $fout .= '</div>
                                        <div class="the-desc">' . $it['description'] . '</div>
                                    </div>
                                    <div class="item-meta-secondary">';
      if ($it['author_display_name']) {

        $fout .= '<div class="s-item-meta"><span class="strong">' . esc_html__("Uploader") . ':</span> ' . $it['author_display_name'] . '</div>';
      }
      if (isset($it['upload_date']) && $it['upload_date']) {


        $d2 = new DateTime($it['upload_date'], new DateTimeZone('Europe/Rome'));
        $t2 = $d2->getTimestamp();


        $str_date = human_time_diff($t2, current_time('timestamp')) . ' ago';
        $fout .= '<div class="s-item-meta"><span class="strong">Published:</span> ' . $str_date . '</div>';
      }
      $fout .= '</div><!-- END .item-meta -->
                </div><!-- END .zfolio-item--inner--inner--inner-->
                                </div>
                                </div>



                            </div><!-- END .zfolio-item -->';
    }


    if ($margs['mode'] == 'scrollmenu') {


      $fout .= '<a href="' . $it['permalink'] . '" class="dzsscr-gallery-item';


      $fout .= ' ' . $it['extra_classes'];

      $fout .= '">';


      if ($it['thumbnail']) {
        $fout .= '<div class="the-thumb" style="background-image:url(' . $it['thumbnail'] . '); "></div>';
      }


      $fout .= '
                        <div class="the-meta">
                            <div class="the-title">' . $it['title'] . '</div>
                            <div class="the-desc">' . $desc . '</div>
                        </div>
                    </a>';

    }


    if ($margs['mode'] == 'scroller') {
      if ($it['thumbnail']) {
        $fout .= '<li class="item-tobe">';

        $fout .= '<a target="' . $margs['links_target'] . '" class="' . $extra_classes_for_zoombox . '" href="' . $its[$lab]['permalink'] . '"' . $extra_attr_for_zoombox . '><img width="100%" class="fullwidth" src="' . $its[$lab]['thumbnail'] . '"/></a>';
        $fout .= '<h5 class="name"><a href="' . $its[$lab]['permalink'] . '">' . $its[$lab]['title'] . '</a></h5>';


        if (isset($its[$lab]['author_display_name']) && $its[$lab]['author_display_name']) {
          $fout .= '<span class="block-extra">' . esc_html__('by ', 'dzsvg') . '<strong>' . $it['author_display_name'] . '</strong>' . '</span>';
        }
        $fout .= '</li>';
      }
    }
    if ($margs['mode'] == 'layouter') {

      $fout .= '<li data-link="' . $it['permalink'] . '" data-src="' . $str_featuredimage . '" ><div class="feed-title">' . $it->post_title . '</div></li>';
    }


    if ($margs['mode'] == 'featured') {
      $fout .= '<li class="item-tobe';
      if ($ii == 0) {
        $fout .= ' needs-loading';
      }
      $fout .= '">';
      if ($it['thumbnail']) {

        $fout .= '<a class=" featured-thumb-a ' . $extra_classes_for_zoombox . '" href="' . $it['permalink'] . '"><img width="100%" class="fullwidth" src="' . $it['thumbnail'] . '"' . $extra_attr_for_zoombox . '/></a>';
      }
      $fout .= '</li>';
    }


    $ii++;
  }
  // --- END item parse


  if ($margs['mode'] == 'layouter') {
    $fout .= '</ul></div>';
  }

  if ($margs['mode'] == 'ullist') {
    $fout .= '</ul>';
  }
  if ($margs['mode'] == 'list') {
    $fout .= '</div>';
  }
  if ($margs['mode'] == 'scrollmenu') {
    $fout .= '</div>';
    $fout .= '</div>';
    $fout .= '</div>';
    $fout .= '<script>
jQuery(document).ready(function($){
if(window.dzsscr_init){
dzsscr_init(".dzs_slideshow_' . $slider_index . '",{
    settings_skin:\'skin_slider\'
    ,enable_easing:\'on\'
});
}
});</script>';
  }
  if ($margs['mode'] == 'scroller') {
    $fout .= '</ul>';
    $fout .= '</div>';


    $dzsvg->script_footer .= 'dzsas_init("#dzsvpas' . $slider_index . '",{
    settings_swipe: "on"
    ,design_arrowsize: "0"
    ,design_itemwidth: "25%"
});';

  }
  if ($margs['mode'] == 'zfolio') {
    $fout .= '</div><div class="zfolio-preloader-circle-con zfolio-preloader-con">
<div class="zfolio-preloader-circle"></div>
</div>
</div>';


    $item_thumb_height = '0.6';
    if ($margs['mode_zfolio_enable_special_layout'] == 'on') {
      $item_thumb_height = '1';
    }

    $fout .= '<script>
jQuery(document).ready(function($){
dzszfl_init(".zfolio' . $slider_index . '",{ design_item_thumb_height:"' . $item_thumb_height . '"
,item_extra_class:""
,selector_con_skin:"selector-con-for-skin-melbourne"
,excerpt_con_transition: "wipe"';


    if ($dzsvg->mainoptions['translate_all'] && $dzsvg->mainoptions['translate_all'] != 'none') {

      $fout .= ',settings_categories_strall:"' . $dzsvg->mainoptions['translate_all'] . '"';
    } else {


      $fout .= ',settings_categories_strall:"' . esc_html__("All") . '"';

    }
    if ($margs['mode_zfolio_default_cat'] && $margs['mode_zfolio_default_cat'] != 'none') {

      $fout .= ',settings_defaultCat:"' . $margs['mode_zfolio_default_cat'] . '"';
    } else {


    }


    if ($margs['mode_zfolio_categories_are_links'] && $margs['mode_zfolio_categories_are_links'] == 'on') {

      $fout .= ',settings_useLinksForCategories:"' . $margs['mode_zfolio_categories_are_links'] . '"';
    }

    if ($margs['mode_zfolio_categories_are_links_ajax'] && $margs['mode_zfolio_categories_are_links_ajax'] == 'on') {

      $fout .= ',settings_useLinksForCategories_enableHistoryApi:"' . $margs['mode_zfolio_categories_are_links_ajax'] . '"';
    }


    $fout .= '
});
});</script>';
  }


  if ($margs['mode'] == 'featured') {
    $fout .= '</ul>';
    $fout .= '</div>';
    $fout .= '</div>';


    $fout .= '<div id="dzsvpas' . $slider_index . '-secondcon" class="dzspb_layb_one_third dzsas-second-con" style="    float: none;
    display: inline-block;
    vertical-align: middle;">';

    // -- showcase
    $fout .= '<div class="dzsas-second-con--clip">';


    foreach ($its as $it) {
      $fout .= '<div class="item">';
      if (isset($it['title'])) {


        $fout .= '<h4><a class="featured--link" href="' . $it['permalink_selected'] . '">' . $it['title'] . '</a></h4>';
      }
      if (isset($it['description'])) {
        $fout .= '<p>' . $it['description'] . '</p>';
      }
      $fout .= '</div>';
    }

    $fout .= '</div>';
    $fout .= '</div>';

    $fout .= '</div>';

    $fout .= '</div>';


    $dzsvg->script_footer .= 'dzsas_init("#dzsvpas' . $slider_index . '",{
settings_mode: "onlyoneitem",
design_arrowsize: "0",
settings_swipe: "on"
,settings_swipeOnDesktopsToo: "on"
,settings_slideshow: "on"
,settings_slideshowTime: "300"
,settings_autoHeight:"on"
,settings_transition:"fade"
,settings_secondCon: "#dzsvpas' . $slider_index . '-secondcon"
,design_bulletspos:"none"
});';

  }


  if ($margs['mode'] == 'gallery_view') {


    foreach ($margs as $lab => $val) {
      if (strpos($lab, 'mode_gallery_view_') === 0) {
        $newlab = str_replace('mode_gallery_view_', '', $lab);

        $its['settings'][$newlab] = $val;
      }
    }

    if (isset($margs['desc_count']) && $margs['desc_count']) {
      $its['settings']['maxlen_desc'] = $margs['desc_count'];
    }


    return dzsvg_shortcode_videogallery(array(
      'id' => 'gallery_view'

    , 'its' => $its  // -- force $its array

    ));


  }


  return $fout;
}

