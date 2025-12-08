<?php

include_once DZSVG_PATH . 'inc/php/parse-items/parse-items-functions.php';
include_once DZSVG_PATH . 'inc/php/view/parse-items.php';

class DzsvgView {


	public DZSVideoGallery $dzsvg;
  static function gallerySanitizeInitialOptions($itsSettings) {

    $newItsSettings = $itsSettings;

    if (isset($itsSettings['nav_type']) && $itsSettings['nav_type'] == 'scroller') {
      wp_enqueue_style('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.css');
      wp_enqueue_script('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.js');
    }


    // --- if display mode is wall, it cannot be shown on a laptop, and height needs to be set to auto
    if ($newItsSettings['displaymode'] == 'wall' || $newItsSettings['displaymode'] == 'videowall' || ($newItsSettings['displaymode'] == 'normal' && (isset($newItsSettings['nav_type']) && $newItsSettings['nav_type'] == 'outer'))) {
      $newItsSettings['laptopskin'] = 'off';
      $newItsSettings['height'] = 'auto';
      // -- height will be auto depending on a certain amount of factors
    }


    if ($newItsSettings['displaymode'] == 'wall' || $newItsSettings['displaymode'] == 'videowall' || $newItsSettings['displaymode'] == 'rotator' || $newItsSettings['displaymode'] == 'rotator3d' || $newItsSettings['displaymode'] == 'slider') {
      wp_enqueue_style('dzsvg-part-mode-' . $newItsSettings['displaymode'], DZSVG_SCRIPT_URL . 'parts/playlist/mode/mode-' . $newItsSettings['displaymode'] . '.css');
    }

    if (isset($newItsSettings['laptopskin']) && $newItsSettings['laptopskin'] == 'on') {
      $newItsSettings['set_responsive_ratio_to_detect'] = 'off';
      $newItsSettings['extra_classes'] = $newItsSettings['extra_classes'] . ' view--is-height-dependent-on-parent';
    }


    return $newItsSettings;
  }

  public $index_players = 0;

  /**
   * @param DZSVideoGallery $dzsvg
   */
  function __construct($dzsvg) {
    $this->dzsvg = $dzsvg;


    add_action('wp_footer', array($this, 'handle_footer'), 3);
    add_action('wp_head', array($this, 'handle_wp_head'), 555);
    add_action('login_enqueue_scripts', array($this, 'login_enqueue_scripts'));

    add_shortcode('video_playlists_display', 'dzsvg_shortcode_sliders_display');
  }

  function login_enqueue_scripts() {
    if (defined('DZSVG_PREVIEW') && DZSVG_PREVIEW == "YES") {
      ?>
      <script>
        (function () {
          setTimeout(function () {

            document.getElementById('user_login').value = 'demouser';
            document.getElementById('user_pass').value = 'demouser';
          }, 1000);
        })();
      </script>
      <?php

    }
  }


  /**
   * fronend
   */
  function handle_wp_head() {
    $dzsvg = $this->dzsvg;
    global $post;
    echo '<script>';
    echo 'window.dzsvg_settings= {dzsvg_site_url: "' . site_url() . '/",version: "' . DZSVG_VERSION . '",ajax_url: "' . admin_url('admin-ajax.php') . '",deeplink_str: "' . $dzsvg->mainoptions['deeplink_str'] . '", debug_mode:"' . $dzsvg->mainoptions['debug_mode'] . '", merge_social_into_one:"' . $dzsvg->mainoptions['merge_social_into_one'] . '"}; window.dzsvg_site_url="' . site_url() . '";';
    echo 'window.dzsvg_plugin_url="' . DZSVG_URL . '";';
    if (defined('DZSVP_VERSION')) {
      global $dzsvp;
      echo 'window.dzsvp_plugin_url = "' . $dzsvp->base_url . '";';
      echo 'window.dzsvp_try_to_generate_image = "' . $dzsvg->mainoptions['dzsvp_try_to_generate_image'] . '";';
    }
    if (isset($dzsvg->mainoptions['translate_skipad']) && $dzsvg->mainoptions['translate_skipad'] != 'Skip Ad') {
      echo 'window.dzsvg_translate_skipad = "' . $dzsvg->mainoptions['translate_skipad'] . '";';
    }
    if (isset($dzsvg->mainoptions['analytics_enable_user_track']) && $dzsvg->mainoptions['analytics_enable_user_track'] == 'on') {
      echo 'window.dzsvg_curr_user = "' . get_current_user_id() . '";';
    }
    echo '</script>';

    if ($dzsvg->mainoptions['extra_css']) {


      if ($dzsvg->mainoptions['extra_css_in_stylesheet'] == 'on') {

        echo '<link rel="stylesheet" href="' . site_url() . '?dzsvg_extra_css=on' . '"  type=\'text/css\' media=\'all\'/>';
      } else {

        echo '<style class="dzsvg-extra-css">';
        echo $dzsvg->mainoptions['extra_css'];


        echo '</style>';
      }

    }


    if ($post) {
      if ($post->post_type == DZSVG_POST_NAME) {


        $image = '';
        if (get_post_meta($post->ID, 'dzsvp_thumb', true)) {
          $image = get_post_meta($post->ID, 'dzsvp_thumb', true);
        } else {

          if (get_post_meta($post->ID, 'dzsvg_meta_thumb', true)) {
            $image = get_post_meta($post->ID, 'dzsvg_meta_thumb', true);
          } else {

            $image = ClassDzsvgHelpers::sanitize_idToSource(get_post_thumbnail_id($post->ID));
          }

        }


        echo '<meta property="og:title" content="' . $post->post_title . '" />';

        echo '<meta property="og:description" content="' . strip_tags($post->post_excerpt) . '" />';

        if ($image) {

          echo '<meta property="og:image" content="' . $image . '" />';
        }


      }
    }


    if (DZSVideoGalleryHelper::page_has_fs_gallery()) {
      $dzsvg->front_scripts();
    }


    $start_video_lab = '';


    foreach ($_GET as $lab => $geti) {
      if (strpos($lab, 'the-video-') === 0) {
        $start_video_lab = $lab;
      }
    }

    if ((isset($_GET['dzsvg_startitem_dzs-video0']) && ($_GET['dzsvg_startitem_dzs-video0'] || $_GET['dzsvg_startitem_dzs-video0'] === '0'))) {
      $start_video_lab = 'dzsvg_startitem_dzs-video0';
    }

    if ((isset($_GET['the-video']) && ($_GET['the-video'] || $_GET['the-video'] === '0'))) {
      $start_video_lab = 'the-video';
    }


    if ($start_video_lab) {
      $po_co = $post->post_content;

      $output_array = array();
      preg_match("/\[(?:dzs_){0,1}videogallery.*?id=\"(.*?)\"/sm", $po_co, $output_array);


      if (count($output_array) > 0) {

        if (isset($output_array[1])) {
          $its = dzsvg_shortcode_videogallery(array(
            'id' => $output_array[1],
            'return_mode' => 'items',
            'called_from' => 'check_for_graph',
          ));


          if (isset($its[$_GET[$start_video_lab]])) {
            $it = $its[$_GET[$start_video_lab]];

            if (isset($it['title'])) {
              echo '<meta property="og:url" content="' . get_permalink($post->ID) . '?' . $start_video_lab . '=' . $_GET[$start_video_lab] . '" />';
            }

            if (isset($it['title'])) {

              echo '<meta property="og:title" content="' . $it['title'] . '" />';
            }
            if (isset($it['description'])) {

              echo '<meta property="og:description" content="' . strip_tags($it['description']) . '" />';
            }

            if (isset($it['thethumb'])) {
              echo '<meta property="og:image" content="' . $it['thethumb'] . '" />';
              echo '<meta property="twitter:image" content="' . $it['thethumb'] . '" />';
            } else {
              if (isset($it['thumbnail'])) {
                echo '<meta property="og:image" content="' . $it['thumbnail'] . '" />';
                echo '<meta property="og:image:width" content="' . 300 . '" />';
                echo '<meta property="og:image:height" content="' . 300 . '" />';
                echo '<meta property="twitter:image" content="' . $it['thumbnail'] . '" />';
              }
            }
          }
        }
      }

    }
  }


  function handle_footer() {

    global $post;

    $dzsvg = $this->dzsvg;


    if (DZSVideoGalleryHelper::page_has_fs_gallery()) {
      include_once(DZSVG_PATH.'inc/php/view/misc/playlist-fullscreen.php');
      dzsvg_view_playlistFullscreenGenerate();
    }


    if ($dzsvg->script_footer) {
      ?>
      <script class="dzsvg-footer-script">
        if (window.jQuery) {
          jQuery(document).ready(function ($) {
            call_dzsvg_footer($);
          })
        } else {
          window.inter_call_dzsvg_footer = setInterval(function () {
            if (window.jQuery) {
              call_dzsvg_footer(jQuery);
              clearInterval(window.inter_call_dzsvg_footer);
            }
          }, 1000);
        }
        <?php echo $dzsvg->script_footer_root; ?>
        function call_dzsvg_footer($) {
          <?php echo $dzsvg->script_footer; ?>
        }
      </script><?php
    }


    $vpsettingsdefault = array();
    $vpsettingsdefault['settings'] = array_merge($dzsvg->vpsettingsdefault, array());

    $vpconfig_k = 0;
    $vpsettings = array();


    if ($dzsvg->mainoptions['zoombox_video_config']) {
      $ultiboxVideoPlayerConfigKey = $dzsvg->mainoptions['zoombox_video_config'];


      for ($i3 = 0; $i3 < count($dzsvg->mainvpconfigs); $i3++) {

        if (isset($dzsvg->mainvpconfigs[$i3]['settings']['id'])) {

          if ((isset($ultiboxVideoPlayerConfigKey)) && ($ultiboxVideoPlayerConfigKey == $dzsvg->mainvpconfigs[$i3]['settings']['id'])) {
            $vpconfig_k = $i3;
          }
        }
      }
      $vpsettings = $dzsvg->mainvpconfigs[$vpconfig_k];
    }


    if (is_array($vpsettings) == false) {
      $vpsettings = array();
    }

    $vpsettings = array_merge($vpsettingsdefault, $vpsettings);


    if (count($dzsvg->vpConfigsFrontend) > 0) {


      ?>
      <script>
        var <?php echo DZSVG_JS_VPCONFIGS_NAME; ?> = <?php echo json_encode($dzsvg->vpConfigsFrontend); ?>;
      </script>
      <?php
    }

    if($dzsvg->view_isMultisharerOnPage){
      include_once(DZSVG_PATH . "inc/php/view/multisharer.php");
      dzsvg_view_multisharer_output($vpsettings);

      wp_enqueue_script('dzs-part-mode-' . 'multisharer', DZSVG_SCRIPT_URL . 'parts/multisharer/multisharer.js', array(), DZSVG_VERSION);
      wp_enqueue_script('dzsvg-part-mode-' . 'multisharer', DZSVG_SCRIPT_URL . 'parts/multisharer/dzsvg-multisharer.js', array(), DZSVG_VERSION);
      wp_enqueue_style('dzsvg-part-mode-' . 'multisharer', DZSVG_SCRIPT_URL . 'parts/multisharer/multisharer.css', array(), DZSVG_VERSION);
    }


    include_once(DZSVG_PATH . "inc/php/view/ultibox.php");
    dzsvg_view_generateUltiboxSettings($vpsettings);

    echo '<style class="dzsvg-footer-css-init">.videogallery:not(.dzsvg-loaded) { opacity:0; }</style>';
    if ($dzsvg->str_footer_css) {
      echo '<style class="dzsvg-footer-css">' . $dzsvg->str_footer_css . '</style>';
    }
  }

  static function enqueuePlayerPartScripts($playerConfigSettings) {

    if (isset($playerConfigSettings['video_description_style']) && $playerConfigSettings['video_description_style']) {
      $ENUM_CANDIDATS = array('gradient');
      if (in_array($playerConfigSettings['video_description_style'], $ENUM_CANDIDATS)) {
        wp_enqueue_style('dzsvp_skin_' . $playerConfigSettings['video_description_style'], DZSVG_URL . 'videogallery/parts/player/player-video-description--style-' . $playerConfigSettings['video_description_style'] . '.css', null, DZSVG_VERSION);
      }
    }
    if (isset($playerConfigSettings['skin_html5vp']) && $playerConfigSettings['skin_html5vp']) {
      $ENUM_PLAYER_SKINS_LOAD_OUTSIDE = array('skin_aurora', 'skin_avanti', 'skin_bigplay', 'skin_bluescrubtop', 'skin_pro', 'skin_reborn', 'skin_white');
      if (in_array($playerConfigSettings['skin_html5vp'], $ENUM_PLAYER_SKINS_LOAD_OUTSIDE)) {
        ClassDzsvgHelpers::enqueuePlayerSkin($playerConfigSettings['skin_html5vp']);
      }
    }
  }

  function generateShareCode($isShareButton, $itsSettings) {
    $socialFout = '';
    if (isset($isShareButton) && $isShareButton == 'on') {
      if ($itsSettings['facebooklink']) {
        if ($itsSettings['facebooklink'] == '{{share}}') {
          $socialFout .= '<a class="dzsvg-social-icon"  href="#"  onclick=\'window.dzsvg_open_social_link("https://www.facebook.com/sharer.php?u={{replacewithcurrurl}}"); return false;\'>';
        } else {
          $socialFout .= '<a class="dzsvg-social-icon" target="_blank" href="' . stripslashes($itsSettings['facebooklink']) . '">';
        }
        $socialFout .= '<i class="fa fa-facebook"></i></a>';
      }
      if ($itsSettings['twitterlink']) {
        $socialFout .= '<a class="dzsvg-social-icon" target="_blank"  href="' . stripslashes($itsSettings['twitterlink']) . '"><i class="fa fa-twitter"></i></a>';
      }
      if ($itsSettings['googlepluslink']) {
        $socialFout .= '<a class="dzsvg-social-icon" target="_blank"  href="' . stripslashes($itsSettings['googlepluslink']) . '"><i class="fa fa-google-plus-official" aria-hidden="true"></i></a>';
      }
      if (isset($itsSettings['social_extracode']) && $itsSettings['social_extracode'] != '') {
        $socialFout .= $itsSettings['social_extracode'];
      }

      if ($this->dzsvg->mainoptions['merge_social_into_one'] == 'on') {
        $this->dzsvg->view_isMultisharerOnPage = true;
      }
    }

    return $socialFout;
  }

  /**
   * return term_meta and its
   * @param string $targetPlaylist - slug
   * @return array
   */
  static function getItsForPlaylist($targetPlaylist) {


    $its = array();
    $term_meta = null;
    $taxonomyName = DZSVG_POST_NAME__SLIDERS;

    $reference_term = get_term_by('slug', $targetPlaylist, $taxonomyName);


    if ($reference_term) {

      $selected_term_id = $reference_term->term_id;


      $term_meta = get_option("taxonomy_$selected_term_id");


      if (!isset($term_meta['feed_mode']) || $term_meta['feed_mode'] == '' || $term_meta['feed_mode'] == 'manual') {


        if ($selected_term_id) {

          $args = array(
            'post_type' => DZSVG_POST_NAME,
            'numberposts' => -1,
            'posts_per_page' => -1,

            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_query' => array(
              'relation' => 'OR',
              array(
                'key' => 'dzsvg_meta_order_' . $selected_term_id,
                'compare' => 'EXISTS',
              ),
              array(
                'key' => 'dzsvg_meta_order_' . $selected_term_id,
                'compare' => 'NOT EXISTS'
              )
            ),
            'tax_query' => array(
              array(
                'taxonomy' => $taxonomyName,
                'field' => 'id',
                'terms' => $selected_term_id
              )
            ),
          );

          $my_query = new WP_Query($args);


          foreach ($my_query->posts as $po) {
            $por = ClassDzsvgHelpers::sanitize_to_gallery_item($po);

            array_push($its, $por);

          }
        }
      } else {

        // -- parse yt or vimeo

        include_once DZSVG_PATH . "inc/php/parse_yt_vimeo.php";


        if ($term_meta['feed_mode'] == 'youtube') {

          $maxlen = 50;

          if (isset($term_meta['youtube_maxlen']) && $term_meta['youtube_maxlen']) {
            $maxlen = $term_meta['youtube_maxlen'];
          }


          $args = array(
            'type' => 'detect',
            'max_videos' => $maxlen,
            'enable_outernav_video_author' => 'off',
          );

          $lab = 'youtube_order';
          if (isset($term_meta[$lab])) {
            $args[$lab] = $term_meta[$lab];
          }

          if (isset($its['settings']['enable_outernav_video_date'])) {
            $args['enable_outernav_video_date'] = $its['settings']['enable_outernav_video_date'];
          }

          $its2 = dzsvg_parse_yt($term_meta['youtube_source'], $args, $fout);
          $its = array_merge($its, $its2);
        }

        if ($term_meta['feed_mode'] == 'vimeo') {

          $vimeo_sort = 'default';

          if (isset($term_meta['vimeo_sort'])) {
            $vimeo_sort = $term_meta['vimeo_sort'];
          }


          $maxlen = 50;

          if (isset($term_meta['vimeo_maxlen']) && $term_meta['vimeo_maxlen']) {
            $maxlen = $term_meta['vimeo_maxlen'];
          }

          $args = array(
            'type' => 'detect',
            'max_videos' => $maxlen,
            'enable_outernav_video_author' => 'off',
            'vimeo_sort' => $vimeo_sort,
          );

          if (isset($its['settings']['enable_outernav_video_date'])) {
            $args['enable_outernav_video_date'] = $its['settings']['enable_outernav_video_date'];
          }


          if (isset($term_meta['vimeo_user_id'])) {
            $args['vimeo_user_id'] = $term_meta['vimeo_user_id'];
          }

          $its2 = dzsvg_parse_vimeo($term_meta['vimeo_source'], $args, $fout);

          $its = array_merge($its, $its2);
        }
        if ($term_meta['feed_mode'] == 'facebook') {

          // -- facebook


          $args = array(
            'type' => 'facebook',
            'max_videos' => '50',
            'enable_outernav_video_author' => 'off',
            'facebook_source' => $term_meta['facebook_source'],
          );

          if (isset($its['settings']['enable_outernav_video_date'])) {
            $args['enable_outernav_video_date'] = $its['settings']['enable_outernav_video_date'];
          }


          $its2 = dzsvg_parse_facebook($term_meta['facebook_source'], $args, $fout);

          $its = array_merge($its, $its2);
        }
      }

    }

    return array('its' => $its, 'term_meta' => $term_meta);

  }

  static function generate_embedMargs($margs, $default_margs, &$embed_margs) {

    foreach ($margs as $lab => $arg) {
      if (isset($margs[$lab])) {
        if (isset($default_margs[$lab]) == false || $margs[$lab] !== $default_margs[$lab]) {
          $embed_margs[$lab] = $margs[$lab];
        }
      }
    }
    if (isset($embed_margs['cat_feed_data'])) {
      unset($embed_margs['cat_feed_data']);
    }

// -- sanitizing
    if (!(isset($margs['thumbnail']) && $margs['thumbnail'])) {
      if ((isset($margs['thumb']) && $margs['thumb'])) {
        $margs['thumbnail'] = $margs['thumb'];
      }
    }
  }

  function convert_cptsForParseItems($argits, $pargs = array()) {

    // -- used in showcase.. video_items
    // -- to transform from video_items to video gallery it

    global $post;
    $margs = array(
      'type' => 'video_items',
      'mode' => 'posts',
    );

    if (!is_array($pargs)) {
      $pargs = array();
    }
    $margs = array_merge($margs, $pargs);


    $its = array();


    foreach ($argits as $it) {


      $che_for_showcase = array();

      $che_for_showcase['extra_classes'] = '';


      if ($margs['type'] == 'video_items') {
        $it_id = $it->ID;
        $imgsrc = wp_get_attachment_image_src(get_post_thumbnail_id($it_id), "full");


        if ($imgsrc) {

          if (is_array($imgsrc)) {
            $che_for_showcase['thumbnail'] = $imgsrc[0];
          } else {
            $che_for_showcase['thumbnail'] = $imgsrc;
          }

        } else {
          if (get_post_meta($it_id, 'dzsvp_thumb', true)) {
            $che_for_showcase['thumbnail'] = get_post_meta($it_id, 'dzsvp_thumb', true);
          } else {
            if (get_post_meta($it_id, 'dzsvg_meta_thumb', true)) {
              $che_for_showcase['thumbnail'] = get_post_meta($it_id, 'dzsvg_meta_thumb', true);
            }
          }
        }


        $arr_metas_we_are_after = array('adarray');
        $meta_all = get_post_meta($it_id);

        foreach ($arr_metas_we_are_after as $lab => $vallab) {
          if (isset($meta_all[$vallab]) && $meta_all[$vallab] && $meta_all[$vallab][0]) {
            $che_for_showcase[$vallab] = $meta_all[$vallab][0];
          }
        }


        $che_for_showcase['type'] = get_post_meta($it_id, 'dzsvp_item_type', true);
        $che_for_showcase['date'] = $it->post_date;


        if (isset($margs['orderby'])) {

          if ($margs['orderby'] == 'views') {

            $che_for_showcase['views'] = DzsvgAjax::mysql_get_views($it_id);
          }
        }


        $aux = get_post_meta($it_id, 'dzsvg_meta_featured_media', true);
        $che_for_showcase['source'] = $aux;

        if ($che_for_showcase['type'] == 'youtube') {

          if (strpos($aux, 'youtube.com') !== false) {


            $aux = DZSHelpers::get_query_arg($aux, 'v');


            $che_for_showcase['source'] = $aux;

          }
        }

        $che_for_showcase['title'] = $it->post_title;
        $che_for_showcase['id'] = $it_id;


        $che_for_showcase['permalink'] = get_permalink($it_id);
        $che_for_showcase['permalink_to_post'] = get_permalink($it_id);


        if ($margs['linking_type'] == 'zoombox') {
          $che_for_showcase['permalink'] = $che_for_showcase['source'];
        }

        // -- video_items is old portal code ?
        if ($margs['type'] == 'video_items') {

          $args = array('orderby' => 'name', 'order' => 'ASC', 'fields' => 'all');

          $terms = wp_get_post_terms($it_id, DZSVG_POST_NAME__CATEGORY, $args);


          $str_cats = '';
          foreach ($terms as $term) {
            if ($str_cats) {
              $str_cats .= ',';
            }
            $str_cats .= $term->term_id;
          }


          $che_for_showcase['cats'] = $str_cats;
        }


        $maxlen = $margs['desc_count'];


        if ($maxlen == 'default') {

          if ($margs['mode'] == 'scrollmenu') {
            $maxlen = 50;
          }
        }
        if ($maxlen == 'default') {
          $maxlen = 100;
        }


        if ($margs['desc_readmore_markup'] == 'default') {
          if ($margs['mode'] == 'scrollmenu') {
            $margs['desc_readmore_markup'] = ' <span style="opacity:0.75;">[...]</span>';
          }
        }
        if ($margs['desc_readmore_markup'] == 'default') {
          $margs['desc_readmore_markup'] = '';
        }


        $che_for_showcase['description'] = wp_kses(ClassDzsvgHelpers::sanitize_description($it->post_content, array('desc_count' => intval($maxlen), 'striptags' => 'off', 'try_to_close_unclosed_tags' => 'off', 'desc_readmore_markup' => $margs['desc_readmore_markup'],)), (DZSVG_HTML_ALLOWED_TAGS));


        if ($post && $post->ID === $it_id) {
          $che_for_showcase['extra_classes'] .= ' active';
        }

        array_push($its, $che_for_showcase);
      }


    }


    return $its;

  }


  /**
   * @param $itemInstances
   * @param $pargs
   * @return string
   */
  function parse_items($itemInstances, $pargs) {

    $dzsvg = $this->dzsvg;

    $fout = '';
    $playlistSettings = $itemInstances['settings'];

    $playerConfigSettings = array();
    if (isset($itemInstances['playerConfigSettings'])) {
      $playerConfigSettings = $itemInstances['playerConfigSettings'];
    }
    $fout .= dzsvg_view_parseItems($itemInstances, $pargs, $dzsvg, $this, $playlistSettings, $playerConfigSettings);

    return $fout;
  }


  function player_viewGenerateExtraControls($che = null, $its = null) {

    $dzsvg = $this->dzsvg;
    $fout = '';
    if (isset($its['settings']['enable_info_button']) && $its['settings']['enable_info_button'] == 'on') {

      if ($che && isset($che['description']) && $che['description']) {


        $aux = $che['description'];

        $aux = preg_replace("/<a.*?>.*?<\/a>/", "", $aux);

        $fout .= '<a class="dzsvg-control dzsvg-info">
<i class="fa fa-info-circle"></i>
<div class="info-content align-right" style="width: 300px;">
' . $aux . '
</div>
</a>';
      }
    }

    if (isset($its['settings']['enable_link_button']) && $its['settings']['enable_link_button'] == 'on') {

      if ($che && isset($che['link']) && $che['link']) {

        $fout .= '<a class="dzsvg-control dzsvg-link" href="' . $che['link'] . '">
                            <i class="fa fa-link"></i>
                            <div class="info-content " style=";">
' . $che['link_label'] . '
                            </div>
                        </a>';
      }
    }


    if (isset($its['settings']['enable_cart_button']) && $its['settings']['enable_cart_button'] == 'on') {

      if ($che && isset($che['video_post']) && $che['video_post']) {
        $video_post = $che['video_post'];


        if ($video_post->post_type == 'product') {


          $buy_link = DZSHelpers::add_query_arg(dzs_curr_url(), 'add-to-cart', $video_post->ID);


          if (function_exists('wc_get_product')) {

            $product_id = $video_post->ID;
            $_product = wc_get_product($product_id);
            if ($_product->is_type('simple')) {

            } else {
              $buy_link = get_permalink($video_post->ID);
            }

          }
          $fout .= '<div class="dzsvg-control dzsvg-add-to-cart">
                            <a href="' . $buy_link . '">
                            <i class="fa fa-shopping-cart"></i>
                            </a>

                            <div class="info-content ">
' . esc_html__('Add to Cart') . '
                            </div>
                        </div>';
        }
      }
    }


    // -- for player
    if (isset($its['settings']['enable_multisharer_button']) && $its['settings']['enable_multisharer_button'] == 'on') {

      $dzsvg->view_isMultisharerOnPage = true;


      $str_share = esc_html__('Share', DZSVG_ID);


      if ($dzsvg->mainoptions['translate_share']) {
        $str_share = $dzsvg->mainoptions['translate_share'];
      }


      $fout .= '<div class="dzsvg-control dzsvg-multisharer-but">
                           
                            <i class="the-icon">{{svg_embed_icon}}</i>
                            

                            <div class="info-content ">
' . $str_share . '
                            </div>
                        </div>';

    }

    return $fout;
  }

}
