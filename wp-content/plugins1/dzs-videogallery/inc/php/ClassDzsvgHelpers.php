<?php


class ClassDzsvgHelpers {
  public static function sanitizeApiDescriptionToHtml($apiDescription) {


    $lb = array("\r\n", "\n", "\r");
    $apiDescription = str_replace($lb, '<br>', $apiDescription);
    $lb = array('"');
    $apiDescription = str_replace($lb, '&quot;', $apiDescription);
    $lb = array("'");
    $apiDescription = str_replace($lb, '&#39;', $apiDescription);

    $apiDescription = preg_replace('/https:\/\/.*\.(\w|\/)*/', '<a href="$0" target="_blank">$0</a>', $apiDescription);

    return $apiDescription;
  }

  public static function sanitizeWpPostToVideoItem($wpPost) {


    $lab_deprecated = 'post_content';
    $lab_correct = 'description';

    if (isset($wpPost[$lab_deprecated]) && $wpPost[$lab_deprecated]) {
      if (!(isset($wpPost[$lab_correct]) && $wpPost[$lab_correct])) {

        $wpPost[$lab_correct] = $wpPost[$lab_deprecated];
      }
    }
    $lab_deprecated = 'post_title';
    $lab_correct = 'title';

    if (isset($wpPost[$lab_deprecated]) && $wpPost[$lab_deprecated]) {
      if (!(isset($wpPost[$lab_correct]) && $wpPost[$lab_correct])) {

        $wpPost[$lab_correct] = $wpPost[$lab_deprecated];
      }
    }

    return $wpPost;
  }

  public static function detectVideoSourceFromChe($che) {

    $videoSource = '';
    if (isset($che['source']) && $che['source']) {
      $videoSource = $che['source'];
    } else {

      if (isset($che['featured_media']) && $che['featured_media']) {
        $videoSource = $che['featured_media'];
      }
    }

    return $videoSource;
  }

  public static function detectVideoType($source, $itemType) {


    if (isset($itemType) && ($itemType == '' || $itemType == 'normal')) {
      $itemType = 'video';
    }

    if ($itemType == 'detect') {

      if (strpos($source, 'vimeo.com/') !== false) {
        $itemType = 'vimeo';
      }


      if (strpos($source, '<iframe') !== false) {

        $itemType = 'inline';
      }
      if (strpos($source, 'youtube.com/') !== false) {
        $itemType = 'youtube';
      }
    }
    if ($itemType == 'detect') {

      $itemType = 'video';
    }

    if (!$itemType) {
      $itemType = '';
    }

    return $itemType;


  }


  function sanitize_forHtmlClass($arg, $pargs = array()) {


    $margs = array(
      'type' => 'image',
    );

    $margs = array_merge($margs, $pargs);


    $arg = str_replace(array(' ', '/', '\\', ':'), '', $arg);
    return $arg;

  }

  static function sanitize_idToSource($arg, $pargs = array()) {


    $margs = array(
      'type' => 'image',
    );

    $margs = array_merge($margs, $pargs);

    if (is_numeric($arg)) {

      if ($margs['type'] == 'image') {

        $imgsrc = wp_get_attachment_image_src($arg, 'full');
        return $imgsrc[0];
      }
      if ($margs['type'] == 'video') {

        $imgsrc = wp_get_attachment_url($arg);
        print_r($imgsrc);
      }


    } else {
      return $arg;
    }


  }


  static function encode_toNumber($string) {
    return substr(sprintf("%u", crc32($string)), 0, 8);
    $ans = array();
    $string = str_split($string);
    #go through every character, changing it to its ASCII value
    for ($i = 0; $i < count($string); $i++) {

      #ord turns a character into its ASCII values
      $ascii = (string)ord($string[$i]);

      #make sure it's 3 characters long
      if (strlen($ascii) < 3)
        $ascii = '0' . $ascii;
      $ans[] = $ascii;
    }

    #turn it into a string
    return implode('', $ans);
  }

  static function sanitize_forInlineContent($arg) {

    $arg = str_replace('<div', '<span', $arg);
    $arg = str_replace('</div>', '</span>', $arg);
    return $arg;
  }

  static function initRegisterPermalinksAndCpt() {

    global $dzsvg;


    $labels = array(
      'name' => esc_html__('Video galleries', 'dzsvg'),
      'singular_name' => esc_html__('Video gallery', 'dzsvg'),
      'search_items' => esc_html__('Search galleries', 'dzsvg'),
      'all_items' => esc_html__('All galleries', 'dzsvg'),
      'parent_item' => esc_html__('Parent gallery', 'dzsvg'),
      'parent_item_colon' => esc_html__('Parent gallery', 'dzsvg'),
      'edit_item' => esc_html__('Edit gallery', 'dzsvg'),
      'update_item' => esc_html__('Update gallery', 'dzsvg'),
      'add_new_item' => esc_html__('Add playlist', 'dzsvg'),
      'new_item_name' => esc_html__('New gallery name', 'dzsvg'),
      'menu_name' => esc_html__('Galleries', 'dzsvg'),
    );


    register_taxonomy(DZSVG_POST_NAME__SLIDERS, DZSVG_POST_NAME, array(

      'label' => esc_html__('Playlists', DZSVG_ID),
      'labels' => $labels,
      'query_var' => true,
      'show_ui' => true,
      'hierarchical' => false,
      'rewrite' => array('slug' => $dzsvg->mainoptions['dzsvg_sliders_rewrite']),
      'show_in_menu' => true,
    ));


    register_taxonomy(DZSVG_POST_NAME__CATEGORY, DZSVG_POST_NAME, array('label' => esc_html__('Video Categories', DZSVG_ID), 'query_var' => true, 'show_ui' => true, 'hierarchical' => true, 'rewrite' => array('slug' => $dzsvg->mainoptions['dzsvp_categories_rewrite']),));
    register_taxonomy(DZSVG_POST_NAME__TAGS, DZSVG_POST_NAME, array('label' => esc_html__('Video Tags', DZSVG_ID), 'query_var' => true, 'show_ui' => true, 'hierarchical' => false, 'rewrite' => array('slug' => $dzsvg->mainoptions['dzsvp_tags_rewrite']),));


    $labels = array('name' => $dzsvg->mainoptions['dzsvp_post_name'], 'singular_name' => $dzsvg->mainoptions['dzsvp_post_name_singular'],);

    $permalinks = get_option('dzsvp_permalinks');

    $item_slug_permalink = empty($permalinks['item_base']) ? _x('video', 'slug', 'dzsvp') : $permalinks['item_base'];


    $args = array(
      'labels' => $labels,
      'public' => true,
      'has_archive' => true,
      'hierarchical' => false,
      'supports' => array(
        'title',
        'editor',
        'author',
        'thumbnail',
        'post-thumbnail',
        'comments',
        'custom-fields',
        'excerpt'
      ),
      'rewrite' => array(
        'slug' => $item_slug_permalink,
      ),
      'yarpp_support' => true,
      'capabilities' => array(),
    );

    if ($dzsvg->mainoptions['post_is_public'] == 'off') {
      $args['public'] = false;
    }
    if ($dzsvg->mainoptions['post_show_in_nav_menus'] == 'off') {
      $args['show_in_nav_menus'] = false;
    }

    register_post_type(DZSVG_POST_NAME, $args);
  }


  public static function admin_enqueueAssetsBasedOnPage() {
    global $dzsvg;

    wp_enqueue_style('dzsvg_admin_global', DZSVG_URL . 'admin/admin_global.css');
    wp_enqueue_script('dzsvg_admin_global', DZSVG_URL . 'admin/admin_global.js');


    if ($dzsvg->mainoptions['analytics_enable'] == 'on') {

      wp_enqueue_script('google.charts', 'https://www.gstatic.com/charts/loader.js');

      if ($dzsvg->mainoptions['analytics_enable_location'] == 'on') {

        wp_enqueue_script('google.maps', 'https://www.google.com/jsapi');
      }
    }

    if (isset($_GET['page']) && ($_GET['page'] == DZSVG_PAGENAME_LEGACY_SLIDERS || $_GET['page'] == DZSVG_PAGENAME_VPCONFIGS)) {
      if ((current_user_can($dzsvg->capability_admin) || $dzsvg->mainoptions['admin_enable_for_users'] == 'on') && function_exists('wp_enqueue_media')) {
        wp_enqueue_media();
      }

      $dzsvg->classAdmin->admin_scripts();


      wp_enqueue_style('dzs.uploader', DZSVG_URL . 'admin/dzsuploader/upload.css');
      wp_enqueue_script('dzs.uploader', DZSVG_URL . "admin/dzsuploader/upload.js");
    }
    if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_LEGACY_DESIGNER_CENTER) {
      wp_enqueue_script('dzs.farbtastic', DZSVG_URL . "admin/colorpicker/farbtastic.js");
      wp_enqueue_style('dzs.farbtastic', DZSVG_URL . 'admin/colorpicker/farbtastic.css');
      wp_enqueue_script('dzsvg-dc.admin', DZSVG_URL . 'admin/admin-dc.js');
      ClassDzsvgHelpers::enqueueDzsVpPlayer();
      ClassDzsvgHelpers::enqueueDzsVgPlaylist();
    }


    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] == $dzsvg->taxname_sliders) {
      wp_enqueue_script('jquery-ui-sortable');
      $url = 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css';


      wp_enqueue_style('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.css');
      wp_enqueue_script('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.js');


      wp_enqueue_style('fontawesome', $url);
      wp_enqueue_style('dzs.tooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.css');




      wp_enqueue_media();
    }


    if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_ABOUT) {

      ClassDzsvgHelpers::enqueueDzsVpPlayer();
      ClassDzsvgHelpers::enqueueDzsVgPlaylist();
    }
    if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_MAINOPTIONS) {
      wp_enqueue_style('dzsvg_admin', DZSVG_URL . 'admin/admin.css');
      wp_enqueue_script('dzsvg_admin', DZSVG_URL . "admin/admin-mo.js");
      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-sortable');


      wp_enqueue_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');


      wp_enqueue_style('dzstabsandaccordions', DZSVG_URL . 'libs/dzstabsandaccordions/dzstabsandaccordions.css');
      wp_enqueue_script('dzstabsandaccordions', DZSVG_URL . "libs/dzstabsandaccordions/dzstabsandaccordions.js", array('jquery'));


      wp_enqueue_style('dzs.dzscheckbox', DZSVG_URL . 'assets/dzscheckbox/dzscheckbox.css');


      ClassDzsvgHelpers::enqueueDzsToggle();


      wp_enqueue_style('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.css');
      wp_enqueue_script('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.js');


      if (isset($_GET['dzsvg_shortcode_player_builder']) && $_GET['dzsvg_shortcode_player_builder'] == 'on') {


        wp_enqueue_style('dzsvg_shortcode_builder_style', DZSVG_URL . 'tinymce/popup.css');
        wp_enqueue_style('dzsvg_shortcode_player_builder_style', DZSVG_URL . 'shortcodegenerator/generator_player.css');
        wp_enqueue_script('dzsvg_shortcode_player_builder', DZSVG_URL . 'shortcodegenerator/generator_player.js');

        wp_enqueue_media();


        wp_enqueue_style('dzsulb', DZSVG_URL . 'libs/ultibox/ultibox.css');
        wp_enqueue_script('dzsulb', DZSVG_URL . 'libs/ultibox/ultibox.js');


        include_once(DZSVG_PATH . 'shortcodegenerator/generator_player.php');
        define('DONOTCACHEPAGE', true);
        define('DONOTMINIFY', true);
      }


      if (isset($_GET['dzsvg_shortcode_builder']) && $_GET['dzsvg_shortcode_builder'] == 'on') {

        wp_enqueue_style('dzsvg_shortcode_builder_style', DZSVG_URL . 'tinymce/popup.css');
        wp_enqueue_script('dzsvg_shortcode_builder', DZSVG_URL . 'tinymce/popup.js');


        ClassDzsvgHelpers::enqueueUltibox();


        wp_enqueue_media();
      }


      if (isset($_GET['dzsvg_shortcode_showcase_builder']) && $_GET['dzsvg_shortcode_showcase_builder'] == 'on') {

        wp_enqueue_style('dzsvg_shortcode_builder_style', DZSVG_URL . 'tinymce/popup.css');
        wp_enqueue_script('dzsvg_shortcode_builder', DZSVG_URL . 'tinymce/popup_showcase.js');


        wp_enqueue_style('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.css');
        wp_enqueue_script('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.js');


        wp_enqueue_style('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.css');
        wp_enqueue_script('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.js');


        wp_enqueue_style('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.css');
        wp_enqueue_script('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.js');


        wp_enqueue_media();
      }

      if (isset($_GET['dzsvg_reclam_builder']) && $_GET['dzsvg_reclam_builder'] == 'on') {

        wp_enqueue_style('dzsvg_shortcode_builder_style', DZSVG_URL . 'tinymce/popup.css');
        wp_enqueue_style('reclambuilder', DZSVG_URL . 'admin/reclam-builder/reclam-builder.css');
        wp_enqueue_script('reclambuilder', DZSVG_URL . 'admin/reclam-builder/reclam-builder.js');


        wp_enqueue_style('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.css');
        wp_enqueue_script('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.js');


        wp_enqueue_style('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.css');
        wp_enqueue_script('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.js');
        wp_enqueue_media();


      }

      if (isset($_GET['dzsvg_quality_builder']) && $_GET['dzsvg_quality_builder'] == 'on') {

        wp_enqueue_style('dzsvg_shortcode_builder_style', DZSVG_URL . 'tinymce/popup.css');
        wp_enqueue_style('qualitybuilder', DZSVG_URL . 'admin/quality-builder/quality-builder.css');
        wp_enqueue_script('qualitybuilder', DZSVG_URL . 'admin/quality-builder/quality-builder.js');


        wp_enqueue_style('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.css');
        wp_enqueue_script('dzsselector', DZSVG_URL . 'libs/dzsselector/dzsselector.js');


        wp_enqueue_style('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.css');
        wp_enqueue_script('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.js');
        wp_enqueue_media();


      }


    }

    if (current_user_can(DZSVG_CAP_EDIT_OWN_GALLERIES) || current_user_can(DZSVG_CAP_EDIT_OTHERS_GALLERIES) || current_user_can('manage_options')) {

      wp_enqueue_script('dzsvg_htmleditor', DZSVG_URL . 'tinymce/plugin-htmleditor.js', array('jquery'), DZSVG_VERSION);
      wp_enqueue_script('dzsvg_configreceiver', DZSVG_URL . 'tinymce/receiver.js');
    }
  }

  public static function sanitizeToPhpId($s) {
    return preg_replace("/[^a-zA-Z0-9]+/", "", $s);
  }


  public static function assertIfPageCanHaveGutenbergBlocks() {
    global $post;

    $current_screen = dzs_get_current_screen();
    if (isset($post) &&
      (
        ($current_screen['base'] == 'post' && (isset($post->post_content) && strpos($post->post_content, 'wp:') !== false)) ||
        ($current_screen['base'] == 'post' && ($current_screen['action'] == 'new' || $current_screen['action'] == 'add'))
        || ($current_screen['base'] == 'post' && $current_screen['action'] != 'new' && (function_exists('has_blocks') && has_blocks($post->ID)) || (isset($post->post_content) && $post->post_content === ''))
      )
    ) {

      return true;
    }

    return false;
  }

  public static function navigationPrepareOptions(&$its) {

    global $dzsvg;
    if (isset($its['settings']['html5designmiw']) && $its['settings']['html5designmiw'] == 'default') {
      if ($dzsvg->mainoptions['use_layout_builder_on_navigation'] == 'on') {
        $its['settings']['html5designmiw'] = '';
      } else {
        $its['settings']['html5designmiw'] = '275';
      }
    }
    if (isset($its['settings']['html5designmih']) && $its['settings']['html5designmih'] == 'default') {
      if ($dzsvg->mainoptions['use_layout_builder_on_navigation'] == 'on') {
        $its['settings']['html5designmih'] = '';
      } else {
        $its['settings']['html5designmih'] = '100';
      }
    }
    if (isset($its['settings']['view_navigation_space']) && $its['settings']['view_navigation_space'] == 'default') {
      if ($dzsvg->mainoptions['use_layout_builder_on_navigation'] == 'on') {
        $its['settings']['view_navigation_space'] = '';
      } else {
        $its['settings']['view_navigation_space'] = '10';
      }
    }

  }

  /**
   * @param string $thumbSrc
   * @param array $che
   * @return string|null
   */
  public static function sanitizeCheToThumbnailUrlSource($thumbSrc = '', $che = array()) {


    if (isset($thumbSrc) && $thumbSrc) {


    } else {

      if (isset($che['type']) && $che['type'] == 'youtube') {
        $thumbSrc = '{ytthumb}';
      }
    }
    if ($thumbSrc == '{ytthumb}') {

      $thumbSrc = 'http://i3.ytimg.com/vi/' . ClassDzsvgHelpers::youtube_getSource($che['source']) . '/hqdefault.jpg';;
    }
    return $thumbSrc;
  }

  public static function youtube_getSource($url) {
    parse_str(parse_url($url, PHP_URL_QUERY), $my_array_of_vars);
    if (isset($my_array_of_vars['v'])) {

      return $my_array_of_vars['v'];
    }

    return $url;
  }

  public static function autoupdaterUpdate($zipUrl = '', $zipTargetPath = ''): bool {


    global $dzsvg;
    if (!$zipUrl) {
      $zipUrl = 'https://zoomthe.me/updater_dzsvg/servezip.php?purchase_code=' . $dzsvg->mainoptions['dzsvg_purchase_code'] . '&site_url=' . site_url();
    }
    if (!$zipTargetPath || $zipTargetPath == '') {
      $zipTargetPath = DZSVG_PATH . 'update.zip';
    }

    $res = DZSHelpers::get_contents($zipUrl);

    if ($res === false) {
      echo 'server offline';
    } else {
      if (strpos($res, '<div class="error') === 0) {
        echo $res;


        if (strpos($res, '<div class="error">error: in progress') === 0) {

          $dzsvg->mainoptions['dzsvg_purchase_code_binded'] = 'on';
          update_option(DZSVG_DBKEY_MAINOPTIONS, $dzsvg->mainoptions);
        }
        return false;
      } else {

        file_put_contents($zipTargetPath, $res);
        if (class_exists('ZipArchive')) {
          $zip = new ZipArchive;
          $zipOpenResp = $zip->open($zipTargetPath);
          if ($zipOpenResp === TRUE) {
            $zip->extractTo(DZSVG_PATH);
            $zip->close();


            $dzsvg->mainoptions['dzsvg_purchase_code_binded'] = 'on';
            update_option(DZSVG_DBKEY_MAINOPTIONS, $dzsvg->mainoptions);


            echo esc_html__('Update succesful.', DZSVG_ID);
            return true;
          } else {
            echo 'failed, code:' . $res;
          }
        } else {

          echo esc_html__('ZipArchive class not found.');
        }

      }
    }
    return false;
  }


  static function sanitize_description($desc, $pargs = array()) {

    $fout = $desc;

    $margs = array('desc_count' => 'default', 'striptags' => 'on', 'try_to_close_unclosed_tags' => 'on', 'desc_readmore_markup' => '',);
    if (is_array($pargs) == false) {
      $pargs = array();
    }
    $margs = array_merge($margs, $pargs);


    $maxlen = 100;
    if ($margs['desc_count']) {
      $maxlen = $margs['desc_count'];
    }



    $striptags = false;

    if ($margs['striptags'] == 'on') {
      $striptags = true;
    }

    $try_to_close_unclosed_tags = true;


    if ($striptags) {
      $try_to_close_unclosed_tags = false;
    }
    if ($margs['try_to_close_unclosed_tags'] == 'on') {
      $try_to_close_unclosed_tags = false;
    }
    $try_to_close_unclosed_tags = false;


    if ($desc) {
      $fout = '' . dzs_get_excerpt(-1, array('content' => $desc, 'maxlen' => $maxlen, 'try_to_close_unclosed_tags' => $try_to_close_unclosed_tags, 'striptags' => $striptags, 'readmore' => 'auto', 'readmore_markup' => $margs['desc_readmore_markup'],));
    }

    return $fout;
  }


  static function sanitize_anchorsTextToHtml($arg) {

    $fout = '';


    $fout = $arg;

    $fout = preg_replace("/(?<![\"|'])(http[s]*:\/\/.*?)(?= |$|<br>|\<)/mi", ' <a href="$0" target="_blank">$0</a>', $arg);


    return $fout;
  }


  /**
   * @param $argId string vpsetting id
   * @return mixed
   */
  public static function view_getVpConfig($argId) {



    global $dzsvg;

    $i = 0;
    $vpconfig_k = 0;
    $vpconfig_id = $argId;
    for ($i = 0; $i < count($dzsvg->mainvpconfigs); $i++) {
      if ((isset($vpconfig_id)) && ($vpconfig_id == $dzsvg->mainvpconfigs[$i]['settings']['id'])) {
        $vpconfig_k = $i;
      }
    }
    $vpsettings = $dzsvg->mainvpconfigs[$vpconfig_k];

    if (!isset($vpsettings['settings']) || $vpsettings['settings'] == '') {
      $vpsettings['settings'] = array();
    }

    $vpsettings['settings'] = array_merge($dzsvg->vpsettingsdefault, $vpsettings['settings']);

    unset($vpsettings['settings']['id']);

    $vpsettings['settings']['vpconfig_id'] = $vpconfig_id;
    return $vpsettings;
  }

  public static function enqueueUltibox() {

    wp_enqueue_style('dzsulb', DZSVG_URL . 'libs/ultibox/ultibox.css');
    wp_enqueue_script('dzsulb', DZSVG_URL . 'libs/ultibox/ultibox.js', array(), DZSVG_VERSION);


    $playerSkin = DZSVG_VIEW_ULTIBOX_DZSVG_PLAYER_SKIN;
    wp_enqueue_style('dzsvp_skin_' . $playerSkin, DZSVG_URL . 'libs/videogallery/parts/player-skins/player-skin--' . $playerSkin . '.css', null, DZSVG_VERSION);
  }

  public static function enqueueDzsVgPlaylist() {
    global $dzsvg;

    $js_url = DZSVG_SCRIPT_URL . "vgallery.js";
    $css_url = DZSVG_SCRIPT_URL . "vgallery.css";


    if ($dzsvg->mainoptions['enable_ie11_compatibility'] === 'on') {
      $js_url = DZSVG_SCRIPT_URL . "deprecated/vgallery.ie11.js";
    }

    wp_enqueue_style('dzsvg_playlist', $css_url, array(), DZSVG_VERSION);
    wp_enqueue_script('dzsvg_playlist', $js_url, array('jquery'), DZSVG_VERSION,
      array(
        'in_footer' => true,
        'strategy'  => 'async',
      ));

  }




  public static function enqueueDzsVpPlayer() {
    $js_url = DZSVG_SCRIPT_URL . "vplayer.js";
    $css_url = DZSVG_SCRIPT_URL . "vplayer.css";


    wp_enqueue_style('dzs-video-player', $css_url, array(), DZSVG_VERSION);
    wp_enqueue_script('dzs-video-player', $js_url, array('jquery'), DZSVG_VERSION,
      array(
        'in_footer' => true,
        'strategy'  => 'async',
      ));
    wp_add_inline_style('dzs-video-player', '.vplayer-tobe{ min-height: 150px; }');
  }

  public static function addAnalyticsButtonPlaylist() {

    $fout = '';
    if (current_user_can('manage_options')) {

      $fout .= '<div class="extra-btns-con">';
      $fout .= '<span class="btn-zoomsounds stats-btn" data-playerid="' . '' . '"><span class="the-icon"><i class="fa fa-tachometer" aria-hidden="true"></i></span><span class="btn-label">' . esc_html__('Stats', 'dzsvg') . '</span></span>';
      $fout .= '</div>';


      ClassDzsvgHelpers::enqueueDzsVgShowcase();
    }

    return $fout;

  }


  public static function enqueueDzsVgShowcase() {

    wp_enqueue_style('dzsvg_showcase', DZSVG_URL . 'libs/video-portal/front-dzsvp.css');
    wp_enqueue_script('dzsvg_showcase', DZSVG_URL . 'libs/video-portal/front-dzsvp.js');
  }

  public static function enqueuePlayerSkin($playerSkin = '') {

    wp_enqueue_style('dzsvp_skin_' . $playerSkin, DZSVG_URL . 'libs/videogallery/parts/player-skins/player-skin--' . $playerSkin . '.css', null, DZSVG_VERSION);
  }

  public static function enqueueDzsToggle() {

    wp_enqueue_style('dzstoggle', DZSVG_URL . 'libs/dzstoggle/dzstoggle.css', null, DZSVG_VERSION);
    wp_enqueue_script('dzstoggle', DZSVG_URL . 'libs/dzstoggle/dzstoggle.js', null, DZSVG_VERSION);
  }

  public static function assets_getUrlForHelperImage($helperImageName = '') {

    $prefix = 'https://www.dropbox.com/s/t451p4x5km2yiad/';
    $suffix = '?dl=1';


    return $prefix . $helperImageName . $suffix;

  }

  /**
   * [deprecated]
   * @param $start_nr
   * @param $end_nr
   * @return string
   */
  public static function playlist_parseItemsForAlternateWall($start_nr, $end_nr) {
    $fout = '';

    for ($i = $start_nr; $i < $end_nr; $i++) {
      if (!isset($its[$i]['type'])) {
        continue;
      }
      $islastonrow = false;
      if ($i % 4 == 3) {
        $islastonrow = true;
      }
      $itemclass = 'item';
      if ($islastonrow == true) {
        $itemclass .= ' last';
      }
      $fout .= '<div class="' . $itemclass . '">';

      $fout .= '<a class="zoombox" data-type="video" data-videotype="' . $its[$i]['type'] . '" data-src="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($its[$i]['source']) . '"><img width="100%" height="100%" class="item-image" src="';
      if ($its[$i]['thethumb'] != '') $fout .= $its[$i]['thethumb']; else {
        if ($its[$i]['type'] == "youtube") {
          $fout .= 'https://img.youtube.com/vi/' . $its[$i]['source'] . '/0.jpg';
          $its[$i]['thethumb'] = 'https://img.youtube.com/vi/' . $its[$i]['source'] . '/0.jpg';
        }
      }
      $fout .= '"/></a>';
      $fout .= '<h4>' . $its[$i]['title'] . '</h4>';
      $fout .= '</div>';
      if ($islastonrow) {
        $fout .= '<div class="clear"></div>';
      }
    }

    return $fout;

  }

  public static function vimeo_detectIdFromUrl($arg) {

    $fout = $arg;

    if (strpos($arg, '/') !== false) {
      $argarr = explode('/', $arg);
      $fout = $argarr[count($argarr) - 1];
    }
    return $fout;
  }

  /**
   * @param array $argarr
   * @param array $addrArray
   * @return array
   */
  public static function sanitize_config_to_gutenberg_register_block_type($argarr, &$addrArray) {

    $foutarr = array();
    foreach ($argarr as $key => $arr) {

      $finalkey = $key;

      $default = '';

      if (isset($arr['default'])) {
        $default = $arr['default'];
      }


      if ($addrArray) {
        $addrArray[$finalkey] = array(
          'type' => 'string',
          'default' => $default,
        );
      } else {

        $foutarr[$finalkey] = array(
          'type' => 'string',
          'default' => $default,
        );
      }
    }
    return $foutarr;

  }

  /**
   * file name is enough, the folder will be auto selected based on dzsvg assets library path
   * @param $fileUrl
   * @return string
   */
  static function admin_documentationGetAsset($fileUrl) {
    if (strpos($fileUrl, 'https://') !== false) {
      return $fileUrl;
    }
    return '';
  }

  static function admin_generateTooltip($tooltip) {

    // -- tooltip
    ob_start();
    ?> <span class="dzstooltip-con js for-hover for-click "><span class="tooltip-indicator"><span
        class="tooltip-info-indicator"><span
          class="tooltip-info-indicator--i">i</span></span></span><span
      class="dzstooltip  talign-center arrow-bottom style-rounded color-dark-light  dims-set transition-slidedown "
      style="width: 280px;">

                  <span class="dzstooltip--inner"><?php
                    if (isset($tooltip['image_url']) && $tooltip['image_url']) {
                      ?>
                      <span class="divimage negative-margin-top"
                            style="padding-top: 52.625%; background-image: url(<?php echo $tooltip['image_url']; ?>); "></span>
                      <?php
                    }
                    ?>
            <span class="paragraph"><?php echo $tooltip['description'] ?></span>

            </span> </span></span><?php

    return ob_get_clean();
  }

  /**
   * @param array $config_main_options
   * @param string $category
   * @param null $mainOptions
   * @return string
   */
  static function generateOptionsFromConfigForMainOptions($config_main_options, $category = 'main', $mainOptions = null) {


    $fout = '';
    foreach ($config_main_options as $key => $main_option) {
      if ($main_option['category'] !== $category) {
        continue;
      }

      $lab = $key;


      $val = '';

      if (isset($main_option['default']) && $main_option['default']) {
        $val = $main_option['default'];
      }

      if (isset($mainOptions[$lab]) && $mainOptions[$lab]) {
        $val = $mainOptions[$lab];
      }


      $fout .= '<div class="setting">';
      $fout .= '<div class="setting-label"><div class="setting-label--text">' . $main_option['title'] . '</div>';


      if (isset($main_option['tooltip']) && $main_option['tooltip']) {
        // todo: move in helper
        $fout .= ClassDzsvgHelpers::admin_generateTooltip($main_option['tooltip']);
      }

      $fout .= '</div>';

      $argsForInput = array(
        'id' => $lab,
        'val' => '',
        'class' => ' ',
        'seekval' => $val,
      );
      if (isset($main_option['extraAttr'])) {
        $argsForInput['extraattr'] = $main_option['extraAttr'];

      }

      if ($main_option['type'] == 'textarea') {
        $fout .= DZSHelpers::generate_input_textarea($lab, $argsForInput);
      }
      if ($main_option['type'] == 'text') {
        $fout .= DZSHelpers::generate_input_text($lab, $argsForInput);
      }
      if ($main_option['type'] == 'select') {
        $argsForInput['class'] = 'dzs-style-me skin-beige';
        $argsForInput['options'] = $main_option['choices'];
        $fout .= DZSHelpers::generate_select($lab, $argsForInput);
      }
      if ($main_option['type'] == 'checkbox') {

        $fout .= DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
        $fout .= '<div class="dzscheckbox skin-nova">';
        $fout .= DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $val));
        $fout .= '<label for="' . $lab . '"></label>';
        $fout .= '</div>';
      }
      if (isset($main_option['sidenote']) && $main_option['sidenote']) {
        $fout .= '<div class="sidenote">' . $main_option['sidenote'] . '</div>';
      }

      $fout .= '</div>';


    }
    return $fout;

  }


  public static function generate_embedCode($pargs = array()) {

    $margs = array(
      'extra_classes' => 'search-align-right',
      'called_from' => 'default',
      'type' => 'player', // -- only player for now
      'player_margs' => '', // -- non encoded
      'extra_code' => '', // -- non encoded
      'enc_margs' => '', // -- encoded
      'extra_embed_code' => '', // -- extra code to be added at the end of the embed code
    );

    if (!is_array($pargs)) {
      $pargs = array();
    }
    $margs = array_merge($margs, $pargs);
    $embed_code = '';


    if ($margs['enc_margs'] == '' && $margs['player_margs']) {
      $margs['enc_margs'] = base64_encode(json_encode($margs['player_margs']));
    }


    $extra_code = '';
    $embed_code = '<div style="width: 100%; padding-top: 67.5%; position: relative;"><iframe src=\'' . site_url() . '?action=embed_dzsvg&type=' . $margs['type'] . '&margs=' . urlencode($margs['enc_margs']) . $margs['extra_code'] . '\'  width="100%" style="position:absolute; top:0; left:0; width: 100%; height: 100%;" scrolling="no" frameborder="0" allowfullscreen allow></iframe></div>';
    $embed_code = str_replace("'", '"', $embed_code);

    return $embed_code;

  }

  public static function facebook_maybeStartSession() {
    global $dzsvg;

    $app_id = $dzsvg->mainoptions['facebook_app_id'];
    $app_secret = $dzsvg->mainoptions['facebook_app_secret'];


    if ($app_id && $app_secret) {


      if (isset($_SESSION)) {
        // -- set cookies for session for some reason
        foreach ($_SESSION as $k => $v) {
          if (strpos($k, "FBRLH_") !== FALSE) {
            if (!setcookie($k, $v)) {
              //what??
            } else {
              $_COOKIE[$k] = $v;
            }
          }
        }
      }


    }
  }

  public static function generateSingleVideoPagePlayer($targetPost, $pargs = array()) {

    // -- for single custom post type dzsvideo

    global $dzsvg, $current_user;
    $targetPost_id = $targetPost->ID;

    $fout = '';


    $margs = array('disable_meta' => 'auto',
      'called_from' => 'default',
    );


    $margs = array_merge($margs, $pargs);


    $dzsvg->sliders_index++;
    $dzsvg->front_scripts();

    $target_playlist = '';
    $target_playlist_startnr = 0;

    //---playlist setup

    if (isset($_GET['dzsvp_user']) && isset($_GET['dzsvp_playlist'])) {
      $target_user_id = sanitize_key($_GET['dzsvp_user']);

      $target_playlists = get_user_meta($target_user_id, 'dzsvp_playlists', true);
      if (is_array($target_playlists)) {
        $target_playlists = json_encode($target_playlists);
      }
      $target_playlists = json_decode($target_playlists, true);


      foreach ($target_playlists as $pl) {
        if ($pl['name'] == sanitize_key($_GET['dzsvp_playlist'])) {
          $target_playlist = $pl;
          break;
        }
      }
    }


    if ($margs['disable_meta'] != 'on') {
      if ($dzsvg->mainoptions['dzsvp_tab_share_content'] != 'on' || $dzsvg->mainoptions['dzsvp_enable_tab_playlist'] == 'on') {
      }
    }


    wp_enqueue_style('dzstabsandaccordions', DZSVG_URL . 'libs/dzstabsandaccordions/dzstabsandaccordions.css');
    wp_enqueue_script('dzstabsandaccordions', DZSVG_URL . "libs/dzstabsandaccordions/dzstabsandaccordions.js", array('jquery'));


    wp_enqueue_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');


    $featured_media = get_post_meta($targetPost_id, 'dzsvg_meta_featured_media', true);


    if ($featured_media == '') {

      // -- deprecated
      $featured_media = get_post_meta($targetPost_id, 'dzsvg_meta_featured_media', true);
    }


    $type = 'video';

    if (get_post_meta($targetPost_id, 'dzsvg_meta_item_type', true) != '') {
      $type = get_post_meta($targetPost_id, 'dzsvg_meta_item_type', true);
    }

    if ($type == '') {

      // -- deprecated
      $featured_media = get_post_meta($targetPost_id, 'dzsvp_item_type', true);
    }

    $i = 0;
    $vpconfig_k = 0;
    $vpconfig_id = '';


    $vpsettingsdefault = array('id' => 'default', 'skin_html5vp' => 'skin_aurora',
      'defaultvolume' => '',
      'youtube_sdquality' => 'small',
      'youtube_hdquality' => 'hd720',
      'youtube_defaultquality' => 'hd',
      'yt_customskin' => 'on',
      'vimeo_byline' => '0',
      'vimeo_portrait' => '0',
      'vimeo_color' => '',
      'settings_video_overlay' => 'off',
      'settings_disable_mouse_out' => 'off',
    );
    $vpsettings = array();


    $vpconfig_id = $dzsvg->mainoptions['dzsvp_video_config'];

    if ($vpconfig_id != '') {
      for ($i = 0; $i < count($dzsvg->mainvpconfigs); $i++) {
        if ((isset($vpconfig_id)) && ($vpconfig_id == $dzsvg->mainvpconfigs[$i]['settings']['id'])) $vpconfig_k = $i;
      }
      $vpsettings = $dzsvg->mainvpconfigs[$vpconfig_k];


      if (!isset($vpsettings['settings']) || $vpsettings['settings'] == '') {
        $vpsettings['settings'] = array();
      }
    }

    if (!isset($vpsettings['settings']) || (isset($vpsettings['settings']) && !is_array($vpsettings['settings']))) {
      $vpsettings['settings'] = array();
    }

    $vpsettings['settings'] = array_merge($vpsettingsdefault, $vpsettings['settings']);


    $skin_vp = 'skin_aurora';
    if ($vpsettings['settings']['skin_html5vp'] == 'skin_custom') {
      $skin_vp = 'skin_pro';
    } else {

      if ($vpsettings['settings']['skin_html5vp'] == 'skin_custom_aurora') {
        $skin_vp = 'skin_aurora';

      } else {

        $skin_vp = $vpsettings['settings']['skin_html5vp'];
      }
    }


    if ($vpsettings['settings']['skin_html5vp'] == 'skin_custom') {


      $selector = '#mainvpfromvp' . $dzsvg->sliders_index;


      $dzsvg->str_footer_css .= '#mainvpfromvp' . $dzsvg->sliders_index . ' { background-color:' . $dzsvg->mainoptions_dc['background'] . ';} ';
      $dzsvg->str_footer_css .= $selector . ' .cover-image > .the-div-image { background-color:' . $dzsvg->mainoptions_dc['background'] . ';} ';
      $dzsvg->str_footer_css .= '#mainvpfromvp' . $dzsvg->sliders_index . ' .background{ background-color:' . $dzsvg->mainoptions_dc['controls_background'] . ';} ';
      $dzsvg->str_footer_css .= '#mainvpfromvp' . $dzsvg->sliders_index . ' .scrub-bg{ background-color:' . $dzsvg->mainoptions_dc['scrub_background'] . ';} ';
      $dzsvg->str_footer_css .= '#mainvpfromvp' . $dzsvg->sliders_index . ' .scrub-buffer{ background-color:' . $dzsvg->mainoptions_dc['scrub_buffer'] . ';} ';
      $dzsvg->str_footer_css .= '#mainvpfromvp' . $dzsvg->sliders_index . ' .playSimple{ border-left-color:' . $dzsvg->mainoptions_dc['controls_color'] . ';} #mainvpfromvp' . $dzsvg->sliders_index . ' .stopSimple .pause-part-1{ background-color: ' . $dzsvg->mainoptions_dc['controls_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .stopSimple .pause-part-2{ background-color: ' . $dzsvg->mainoptions_dc['controls_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .volumeicon{ background: ' . $dzsvg->mainoptions_dc['controls_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .volumeicon:before{ border-right-color: ' . $dzsvg->mainoptions_dc['controls_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .volume_static{ background: ' . $dzsvg->mainoptions_dc['controls_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .hdbutton-con .hdbutton-normal{ color: ' . $dzsvg->mainoptions_dc['controls_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .total-timetext{ color: ' . $dzsvg->mainoptions_dc['controls_color'] . '; } ';
      $dzsvg->str_footer_css .= '#mainvpfromvp' . $dzsvg->sliders_index . ' .playSimple:hover{ border-left-color: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .stopSimple:hover .pause-part-1{ background-color: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .stopSimple:hover .pause-part-2{ background-color: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .volumeicon:hover{ background: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .volumeicon:hover:before{ border-right-color: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '; } ';
      $dzsvg->str_footer_css .= '#mainvpfromvp' . $dzsvg->sliders_index . ' .volume_active{ background-color: ' . $dzsvg->mainoptions_dc['controls_highlight_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .scrub{ background-color: ' . $dzsvg->mainoptions_dc['controls_highlight_color'] . '; } #mainvpfromvp' . $dzsvg->sliders_index . ' .hdbutton-con .hdbutton-hover{ color: ' . $dzsvg->mainoptions_dc['controls_highlight_color'] . '; } ';
      $dzsvg->str_footer_css .= '#mainvpfromvp' . $dzsvg->sliders_index . ' .curr-timetext{ color: ' . $dzsvg->mainoptions_dc['timetext_curr_color'] . '; } ';

    }


    $target_playlist = '';
    $target_playlist_startnr = 0;

    //---playlist setup

    if (isset($_GET['dzsvp_user']) && isset($_GET['dzsvp_playlist'])) {
      $target_user_id = sanitize_text_field($_GET['dzsvp_user']);

      $target_playlists = get_user_meta($target_user_id, 'dzsvp_playlists', true);
      if (is_array($target_playlists)) {
        $target_playlists = json_encode($target_playlists);
      }
      $target_playlists = json_decode($target_playlists, true);


      foreach ($target_playlists as $pl) {
        if ($pl['name'] == $_GET['dzsvp_playlist']) {
          $target_playlist = $pl;
          break;
        }
      }
    }


    $fout .= '<div class="mainvp-con dzsvg--single-video-page--con">';


    if ($target_playlist) {


      wp_enqueue_style('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.css');
      wp_enqueue_script('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.js');

      $fout .= '<div class="videogallery-con currGallery" style="width:275px; height:300px; float:right; padding-top: 0; padding-bottom: 0;">
<div class="dzsvg-preloader"></div>
<div class="vg-playlist videogallery skin_default" style="width:275px; height:300px;">';


      $i5 = 0;

      global $post;
      foreach ($target_playlist['items'] as $targetPlaylistItemId) {
        $postItem = get_post($targetPlaylistItemId);

        $playlistLink = get_permalink($targetPlaylistItemId);

        $playlistLink = add_query_arg('dzsvp_user', $_GET['dzsvp_user'], $playlistLink);
        $playlistLink = add_query_arg('dzsvp_playlist', $_GET['dzsvp_playlist'], $playlistLink);


        if ($post->ID == $targetPlaylistItemId) {
          $target_playlist_startnr = $i5;
        }

        $stringFeaturedImage = '';

        $imgsrc = wp_get_attachment_image_src(get_post_thumbnail_id($targetPlaylistItemId), "full");
        if ($imgsrc) {

          if (is_array($imgsrc)) {
            $imgsrc = $imgsrc[0];
          }
        } else {
          if (get_post_meta($targetPost_id, 'dzsvg_meta_thumb', true)) {
            $imgsrc = get_post_meta($targetPlaylistItemId, 'dzsvg_meta_thumb', true);
          } else {
            if (get_post_meta($targetPost_id, 'dzsvp_thumb', true)) {
              $imgsrc = get_post_meta($targetPlaylistItemId, 'dzsvp_thumb', true);
            }
          }
        }


        if (get_post_meta($targetPlaylistItemId, 'dzsvp_featured_media', true)) {

          update_post_meta($targetPlaylistItemId, 'dzsvg_meta_featured_media', get_post_meta($targetPlaylistItemId, 'dzsvp_featured_media', true));
          update_post_meta($targetPlaylistItemId, 'dzsvp_featured_media', '');
        }
        if (get_post_meta($targetPlaylistItemId, 'dzsvp_item_type', true)) {

          update_post_meta($targetPlaylistItemId, 'dzsvg_meta_item_type', get_post_meta($targetPlaylistItemId, 'dzsvp_item_type', true));
          update_post_meta($targetPlaylistItemId, 'dzsvp_item_type', '');
        }

        if ($imgsrc) {
          $stringFeaturedImage = '<img data-imgsrc="' . $imgsrc . '" class="imgblock"  alt="' . $postItem->post_title . '""/>';
        } else {

          if (get_post_meta($targetPlaylistItemId, 'dzsvg_meta_item_type', true) == 'youtube') {
            $stringFeaturedImage = '<img data-imgsrc="https://img.youtube.com/vi/' . get_post_meta($targetPlaylistItemId, 'dzsvg_meta_featured_media', true) . '/0.jpg" class="imgblock"/>';
          }
        }


        $fout .= '<div class="vplayer-tobe" data-videoTitle="' . $postItem->post_title . '" data-type="link" data-sourcevp="' . $playlistLink . '" data-postid="' . $targetPlaylistItemId . '" data-player-id="' . $targetPlaylistItemId . '"';

        if ($dzsvg->mainoptions['videopage_resize_proportional'] == 'on') {
          $fout .= ' data-responsive_ratio="detect"';
        }

        $fout .= '>
<div class="menuDescription">' . $stringFeaturedImage . '
    <div class="the-title">' . $postItem->post_title . '</div> ' . $postItem->post_content . '
</div>
</div>';
        $i5++;
      }


      $fout .= '</div></div>';
      $fout .= '<div class="history-video-element" style="overflow:hidden;">';
    }

    // -- single audio player page
    $fout .= '<div id="mainvpfromvp' . $dzsvg->sliders_index . '"   data-player-id="' . $targetPost->ID . '" data-postid="' . $targetPost->ID . '" class="vplayer-tobe from-parse-videoitem" data-videoTitle="' . $targetPost->post_title . '" data-type="' . $type . '" data-src="' . DZSVideoGalleryHelper::sanitize_for_html_attribute_value_no_spaces($featured_media) . '"';


    $aux = 'dzsvg_meta_ad_array';
    if (get_post_meta($targetPost->ID, $aux, true)) {
      $fout .= ' data-ad-array' . '' . '=\'' . (get_post_meta($targetPost->ID, $aux, true)) . '\'';

    }


    $aux = 'dzsvg_meta_play_from';
    if (get_post_meta($targetPost->ID, $aux, true)) {
      $fout .= ' data-playfrom' . '' . '=\'' . (get_post_meta($targetPost->ID, $aux, true)) . '\'';

    }


    $fout .= '>';


    $aux = 'dzsvg_meta_subtitle';

    if (get_post_meta($targetPost->ID, $aux, true)) {
      $fil = DZSHelpers::get_contents(get_post_meta($targetPost->ID, $aux, true));
      $fout .= '<div class="subtitles-con-input">' . $fil . '</div>';
    }


    $fout .= '</div>';


    if ($target_playlist) {

      $fout .= '</div>'; // end .history-video-element
    }


    if ($dzsvg->mainoptions['analytics_enable'] == 'on') {

      if (current_user_can('manage_options')) {

        $fout .= '<div class="extra-btns-con">';
        $fout .= '<span class="btn-zoomsounds stats-btn" data-playerid="' . $targetPost_id . '"><span class="the-icon"><i class="fa fa-tachometer" aria-hidden="true"></i></span><span class="btn-label">' . esc_html__('Stats', 'dzsvg') . '</span></span>';
        $fout .= '</div>';


        ClassDzsvgHelpers::enqueueDzsVgShowcase();
      }


    }


    if ($margs['disable_meta'] != 'on') {
      include_once(DZSVG_PATH.'configs/svg-assets.php');
      if ($dzsvg->mainoptions['dzsvp_enable_likes'] == 'on' || $dzsvg->mainoptions['dzsvp_enable_ratings'] == 'on' || $dzsvg->mainoptions['dzsvp_enable_viewcount'] == 'on' || $dzsvg->mainoptions['dzsvp_enable_likescount'] == 'on' || $dzsvg->mainoptions['dzsvp_enable_ratingscount'] == 'on') {

        $nr_views = 0;
        $fout .= '<div class="extra-html extra-html--videoitem">';
        if ($dzsvg->mainoptions['dzsvp_enable_likes'] == 'on') {


          $fout .= '<span class=" btn-zoomsounds btn-like';

          if (isset($_COOKIE['dzsvp_likesubmitted-' . $targetPost_id]) && $_COOKIE['dzsvp_likesubmitted-' . $targetPost_id] == '1') {
            $fout .= ' active';
          }

          $fout .= '"><span class="the-icon"><svg xmlns:svg="https://www.w3.org/2000/svg" xmlns="https://www.w3.org/2000/svg" version="1.0" width="15" height="15" viewBox="0 0 645 700" id="svg2"> <defs id="defs4"></defs> <g id="layer1"> <path d="M 297.29747,550.86823 C 283.52243,535.43191 249.1268,505.33855 220.86277,483.99412 C 137.11867,420.75228 125.72108,411.5999 91.719238,380.29088 C 29.03471,322.57071 2.413622,264.58086 2.5048478,185.95124 C 2.5493594,147.56739 5.1656152,132.77929 15.914734,110.15398 C 34.151433,71.768267 61.014996,43.244667 95.360052,25.799457 C 119.68545,13.443675 131.6827,7.9542046 172.30448,7.7296236 C 214.79777,7.4947896 223.74311,12.449347 248.73919,26.181459 C 279.1637,42.895777 310.47909,78.617167 316.95242,103.99205 L 320.95052,119.66445 L 330.81015,98.079942 C 386.52632,-23.892986 564.40851,-22.06811 626.31244,101.11153 C 645.95011,140.18758 648.10608,223.6247 630.69256,270.6244 C 607.97729,331.93377 565.31255,378.67493 466.68622,450.30098 C 402.0054,497.27462 328.80148,568.34684 323.70555,578.32901 C 317.79007,589.91654 323.42339,580.14491 297.29747,550.86823 z" id="path2417" style=""></path> <g transform="translate(129.28571,-64.285714)" id="g2221"></g> </g> </svg> </span><span class="the-label hide-on-active">' . esc_html__("Like") . '</span><span class="the-label show-on-active">' . esc_html__("Liked") . '</span></span>';

        }
        if ($dzsvg->mainoptions['dzsvp_enable_ratings'] == 'on') {

          $playerid = '';


          $aux = get_post_meta($targetPost->ID, '_dzsvp_rate_index', true);

          // -- 1 to 5
          if ($aux == '') {
            $aux = 0;
          } else {
            $aux = floatval($aux) / 5;
          }
          if ($aux > 5) {
            $aux = 5;
          }

          $perc = floatval(($aux) * 100);


          $fout .= '<div class="star-rating-con" data-initial-rating-index="' . $aux . '">';


          $arte_stars = '<span class="rating-bg"><span class="rating-inner">{{starssvg}}</span></span>
 <span class="rating-prog" style="width: ' . $perc . '%;"><span class="rating-inner">{{starssvg}}</span></span>';


          $arte_stars = str_replace('{{starssvg}}', DZSVG_SVG_STAR . DZSVG_SVG_STAR . DZSVG_SVG_STAR . DZSVG_SVG_STAR . DZSVG_SVG_STAR, $arte_stars);

          $fout .= $arte_stars;
          $fout .= '</div>';
        }
        if ($dzsvg->mainoptions['videopage_show_views'] == 'on') {
          $nr_views = DzsvgAjax::mysql_get_views($targetPost_id);


          $fout .= '<div class="counter-hits"><i class="fa fa-eye"></i>  <span class="the-label"> <span class="the-number">' . $nr_views . '</span> <span class="the-label-text">' . esc_html__("views") . '</span></span></div>';

        }
        if ($dzsvg->mainoptions['dzsvp_enable_likescount'] == 'on') {
          $nr_likes = '';
          if (get_post_meta($targetPost_id, '_dzsvp_likes', true) == '') {
            $nr_likes .= '0';
          } else {
            $nr_likes .= get_post_meta($targetPost_id, '_dzsvp_likes', true);
          }


          $fout .= '<div class="counter-likes"><i class="fa fa-heart"></i>  <span class="the-label"> <span class="the-number">' . $nr_likes . '</span> <span class="the-label-text">' . esc_html__("likes") . '</span></span></div>';
        }
        if ($dzsvg->mainoptions['dzsvp_enable_ratingscount'] == 'on') {
          $fout .= '<div class="counter-rates"><span class="the-number">';

          $nr_rates = 0;


          if (get_post_meta($targetPost_id, '_dzsvp_rate_nr', true)) {
            $nr_rates = intval(get_post_meta($targetPost_id, '_dzsvp_rate_nr', true));
          }

          $fout .= $nr_rates . '</span> ' . esc_html__('ratings', 'dzsvp') . '</div>';
        }
        $fout .= '</div>';

      }

    }


    $fout .= '<script>';


    $dzsvg->mainoptions['advanced_videopage_custom_action_contor_10_secs'] = str_replace('{{postid}}', $targetPost_id, $dzsvg->mainoptions['advanced_videopage_custom_action_contor_10_secs']);
    if (isset($current_user->data) && isset($current_user->data->ID)) {

      $dzsvg->mainoptions['advanced_videopage_custom_action_contor_10_secs'] = str_replace('{{userid}}', $current_user->data->ID, $dzsvg->mainoptions['advanced_videopage_custom_action_contor_10_secs']);
    }


    // TODO: for custom action


    if ($dzsvg->mainoptions['advanced_videopage_custom_action_contor_10_secs']) {
      $fout .= 'window.custom_action_contor_10_secs = function(arg1,arg2){
            ' . $dzsvg->mainoptions['advanced_videopage_custom_action_contor_10_secs'] . '
}; ';
    }


    if ($dzsvg->mainoptions['videopage_autoplay_next'] == 'on') {
      $fout .= '
window.video_page_action_video_end = function(arg){ console.info("video end - ", arg);';


      $args = array('post_type' => 'dzsvideo', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC',);


      $query = new WP_Query($args);


      $ind = 0;
      foreach ($query->posts as $por) {


        if ($por->ID == $targetPost_id) {
          $curr_index = $ind;
        }
        $ind++;
      }


      $target_post_id = 0;

      if (isset($query->posts[$curr_index + 1])) {
        $target_post_id = $query->posts[$curr_index + 1];
      }

      if ($dzsvg->mainoptions['videopage_autoplay_next_direction'] == 'reverse') {
        if (isset($query->posts[$curr_index - 1])) {
          $target_post_id = $query->posts[$curr_index - 1];
        } else {
          $target_post_id = 0;
        }
      }


      if ($target_post_id) {
        $fout .= ' window.location.href = "' . get_permalink($target_post_id) . '";';
      }
      $fout .= '};  ';
    }

    if ($margs['disable_meta'] != 'on') {
      if ($dzsvg->mainoptions['dzsvp_enable_ratings'] == 'on') {
        if (isset($_COOKIE['dzsvp_ratesubmitted-' . $targetPost_id])) {
          $fout .= 'window.starrating_alreadyrated="' . $_COOKIE['dzsvp_ratesubmitted-' . $targetPost_id] . '";';
        }
      };
    }

    $fout .= 'jQuery(document).ready(function($){ var videoplayersettings = {
autoplay : "' . $dzsvg->mainoptions['videopage_autoplay'] . '",
cueVideo : "on",
settings_hideControls : "off"
,settings_video_overlay : "' . $vpsettings['settings']['settings_video_overlay'] . '"
,settings_disable_mouse_out : "' . $vpsettings['settings']['settings_disable_mouse_out'] . '"
,design_skin: "' . $skin_vp . '"';


    if ($dzsvg->mainoptions['videopage_resize_proportional'] == 'on') {

      $fout .= '
,responsive_ratio: "detect"';
    }
    if ($dzsvg->mainoptions['videopage_autoplay_next'] == 'on') {

      $fout .= '
,action_video_end: window.video_page_action_video_end';
    }
    if ($dzsvg->mainoptions['advanced_videopage_custom_action_contor_10_secs']) {

      $fout .= '
,action_video_contor_60secs: window.dzsvg_wp_send_contor_60_secs ';
    }

    ClassDzsvgHelpers::enqueuePlayerSkin($skin_vp);


    $fout .= '};';


    if ($dzsvg->mainoptions['analytics_enable'] == 'on') {


      $player_index = ''; // -- (only one)
      $fout .= 'videoplayersettings' . $player_index . '.action_video_view = window.dzsvg_wp_send_view;';

      $fout .= 'videoplayersettings' . $player_index . '.action_video_contor_60secs = window.dzsvg_wp_send_contor_60_secs;';

    }

    $fout .= 'dzsvp_init("#mainvpfromvp' . $dzsvg->sliders_index . '",videoplayersettings);';


    if ($dzsvg->mainoptions['track_views'] == 'on' || $dzsvg->mainoptions['videopage_show_views'] == 'on') {
      if (!isset($_COOKIE['dzsvp_viewsubmitted-' . $targetPost_id])) {
        $fout .= 'var data = {
    action: "dzsvp_submit_view",
    postdata: "1",
    playerid: "' . $targetPost_id . '"
};
setTimeout(function(){
$.ajax({
type: "POST",
url: dzsvg_settings.ajax_url,
data: data,
success: function(response) {
},
error:function(arg){
}
});
},1500); ';
      };
    }


    if ($margs['disable_meta'] != 'on') {
      ;
    }


    if ($target_playlist) {
      $fout .= 'dzsvg_init(".vg-playlist",{
totalWidth:275
,settings_mode:"normal"
,menuSpace:0
,randomise:"off"
,autoplay :"off"
,cueFirstVideo: "off"
,autoplayNext : "on"
,nav_type: "scroller"
,menuitem_width:275
,menuitem_height:75
,menuitem_space:1
,menu_position:"right"
,transition_type:"fade"
,design_skin: "skin_navtransparent"
,embedCode:""
,shareCode:""
,logo: ""
,responsive: "on"
,design_shadow:"off"
,settings_disableVideo:"on"
,startItem: "' . $target_playlist_startnr . '"
,settings_enableHistory: "off"
,settings_ajax_extraDivs: ""
});';
    }


    $fout .= '});</script>'; // end document ready


    $fout .= '<div class="clearboth"></div>';
    $fout .= '</div><!-- end .mainvp-con -->'; // end mainvp-con


    if ($margs['disable_meta'] != 'on') {
      if (($dzsvg->mainoptions['dzsvp_tab_share_content'] == 'on' || $dzsvg->mainoptions['dzsvp_enable_tab_playlist'] == 'on' || $dzsvg->mainoptions['dzsvp_enable_tab_playlist'] == 'on') && !is_post_type_archive('dzsvideo')) {

        $fout .= '<div class="clearboth"></div>';
        $fout .= '<div class=""></div>
                    <div class="dzs-tabs auto-init dzs-tabs-dzsvp-page skin-default" data-options="{ \'design_tabsposition\' : \'top\'
                ,design_transition: \'slide\'
                ,design_tabswidth: \'default\'
                ,toggle_breakpoint : \'' . $dzsvg->mainoptions['dzsvp_tabs_breakpoint'] . '\'
                 ,toggle_type: \'accordion\'}">';


        $fout .= '';
        $fout .= '<div class="dzs-tab-tobe">
                <div class="tab-menu"><i class="fa fa-info"></i> ' . esc_html__('About', 'dzsvp') . '</div>';
        $fout .= '<div class="tab-content">';


        $fout .= do_shortcode($targetPost->post_content);
        $dzsvg->sw_content_added = true;

        $fout .= '</div>';
        $fout .= '</div>'; // -- close .dzs-tab-tobe


        $fout .= '';


        if ($dzsvg->mainoptions['dzsvp_tab_share_content'] != '' || $dzsvg->mainoptions['dzsvp_enable_tab_playlist'] == 'on') {


          // -- we are in video item page

          if ($dzsvg->mainoptions['dzsvp_tab_share_content'] != '') {


            $aux_cont = $dzsvg->mainoptions['dzsvp_tab_share_content'];
            $aux_cont = str_replace('{{currurl}}', urlencode(dzs_curr_url()), $aux_cont);


            // todo: find other solution
            $auxembed = '<iframe src="' . DZSVG_URL . 'bridge.php?action=view&dzsvideo=' . $targetPost_id . '" style="width:100%; height:300px; overflow:hidden;" scrolling="no" frameborder="0"></iframe>';

            $aux_cont = str_replace('{{embedcode}}', htmlentities($auxembed), $aux_cont);


            $fout .= '<div class="dzs-tab-tobe">
                            <div class="tab-menu with-tooltip">
                                <i class="fa fa-share"></i> ' . esc_html__('Share', 'dzsvp') . '
                            </div>
                            <div class="tab-content">
                                <br>' . $aux_cont . '
                            </div>
                        </div>';


          }

          ob_start();
          do_action('dzsvg_extra_tabs_videoitem');

          $fout .= ob_get_contents();

          /* perform what you need on $str with str_replace */

          ob_end_clean();

          $fout .= '</div><!-- end .dzs-tabs -->'; //close .dzs-tabs

          $fout .= '<script>
jQuery(document).ready(function($){
dzstaa_init(".dzs-tabs-dzsvp-page",{ \'design_tabsposition\' : \'top\'
                ,design_transition: \'slide\'
                ,design_tabswidth: \'default\'
                ,toggle_breakpoint : \'' . $dzsvg->mainoptions['dzsvp_tabs_breakpoint'] . '\'
                 ,toggle_type: \'accordion\'});
});</script>';


        }
      }
    }


    return $fout;
  }


  /**
   * used in show_shortcode
   * @param $argumentItemOptions
   * @return array
   */
  public static function sanitize_to_gallery_item($argumentItemOptions) {
    // --
    global $dzsvg;


    $itemOptions = (array)$argumentItemOptions;

    $po_id = $itemOptions['ID'];

    if (!isset($itemOptions['post_type'])) {
      error_log('what is wrong with che ? (sanitize_to_gallery_item)' . print_r($itemOptions, true));
      return array();
    }

    if ($itemOptions['post_type'] == 'attachment') {

      $temp = get_attached_media('video', $po_id);

      if (is_array($temp) && count($temp)) {
        $itemOptions['source'] = $temp[0];
      } else {


        $feat_image_url = wp_get_attachment_url($po_id);

        if ($feat_image_url) {


          $itemOptions['source'] = $feat_image_url;
        } else {

          $itemOptions['source'] = $itemOptions['guid'];
        }
      }
    }


    foreach ($dzsvg->options_item_meta as $oim) {


      if ($oim['name'] === 'post_content' || $oim['name'] === 'the_post_content' || $oim['name'] === 'the_post_title') {
        continue;
      }

      $long_name = $oim['name'];

      $short_name = str_replace('dzsvg_meta_', '', $oim['name']);


      if (isset($oim['default']) && $oim['default']) {

        $itemOptions[$oim['name']] = $oim['default'];
        $itemOptions[$short_name] = $oim['default'];


        $aux = get_post_meta($itemOptions['ID'], $long_name);
        if (get_post_meta($itemOptions['ID'], $long_name)) {

          if (isset($aux[0])) {
            $itemOptions[$long_name] = $aux[0];
            $itemOptions[$short_name] = $aux[0];
          }
        }
      } else {


        $itemOptions[$oim['name']] = get_post_meta($po_id, $oim['name'], true);
        $itemOptions[$short_name] = get_post_meta($po_id, $long_name, true);
      }


    }


    if (get_post_meta($po_id, 'dzsvg_meta_replace_artistname', true)) {
      $itemOptions['artistname'] = get_post_meta($po_id, 'dzsvg_meta_replace_artistname', true);
    }

    if (get_post_meta($po_id, 'dzsvg_meta_replace_menu_artistname', true)) {
      $itemOptions['menu_artistname'] = get_post_meta($po_id, 'dzsvg_meta_replace_menu_artistname', true);
    }

    if (get_post_meta($po_id, 'dzsvg_meta_replace_menu_songname', true)) {
      $itemOptions['menu_songname'] = get_post_meta($po_id, 'dzsvg_meta_replace_menu_songname', true);
    }


    $itemOptions['sourceogg'] = '';


    if (get_post_meta($po_id, 'dzsvg_meta_item_thumb', true)) {
      $itemOptions['thumb'] = get_post_meta($po_id, 'dzsvg_meta_item_thumb', true);
    } else {

      $itemOptions['thumb'] = get_post_meta($po_id, 'dzsvg_meta_thumb', true);
    }

    $itemOptions['type'] = get_post_meta($po_id, 'dzsvg_meta_type', true);
    $itemOptions['playerid'] = $po_id;


    $arr_metas_we_are_after = array('adarray');
    $meta_all = get_post_meta($po_id);

    foreach ($arr_metas_we_are_after as $lab => $vallab) {
      if (isset($meta_all[$vallab]) && $meta_all[$vallab] && $meta_all[$vallab][0]) {
        $itemOptions[$vallab] = $meta_all[$vallab][0];
      }
    }


    $itemOptions['title'] = '';
    $itemOptions['description'] = '';

    if (isset($itemOptions['post_title'])) {

      $itemOptions['title'] = $itemOptions['post_title'];
    }

    if (isset($itemOptions['post_content'])) {

      $itemOptions['description'] = $itemOptions['post_content'];
    }
    if (get_post_meta($po_id, 'dzsvg_meta_featured_media', true)) {

      $itemOptions['source'] = get_post_meta($po_id, 'dzsvg_meta_featured_media', true);
    }
    $itemOptions['type'] = get_post_meta($po_id, 'dzsvg_meta_item_type', true);
    $itemOptions['loop'] = get_post_meta($po_id, 'dzsvg_meta_loop', true);
    $itemOptions['is_360'] = get_post_meta($po_id, 'dzsvg_meta_is_360', true);
    if (get_post_meta($po_id, 'dzsvg_meta_ad_array', true)) {
      $itemOptions['adarray'] = get_post_meta($po_id, 'dzsvg_meta_ad_array', true);
    }
    if (get_post_meta($po_id, 'dzsvg_meta_subtitle', true)) {
      $itemOptions['subtitle'] = get_post_meta($po_id, 'dzsvg_meta_subtitle', true);
    }
    $itemOptions['audioimage'] = get_post_meta($po_id, 'dzsvg_meta_audioimage', true);
    $itemOptions['menuDescription'] = $itemOptions['menu_description'];
    $itemOptions['thumbnail'] = ClassDzsvgHelpers::get_post_thumb_src($po_id);

    // -- final sanitize
    if (isset($itemOptions['play_from']) && $itemOptions['play_from']) {
      $itemOptions['playfrom'] = $itemOptions['play_from'];
    }


    return $itemOptions;
  }


  static function style_player($selector, $vpsettings, $pargs = array()) {

    global $dzsvg;

    $fout = '';


    $margs = array(

      'gallery' => ''
    );


    $margs = array_merge($margs, $pargs);


    if ($selector) {

    } else {
      $selector = '.vp' . '0';
    }


    // -- we move this into footer for theme excerpt purpose
    $dzsvg->str_footer_css .= $selector . ' { background-color:' . $dzsvg->mainoptions_dc['background'] . ';} ';
    $dzsvg->str_footer_css .= $selector . ' .cover-image > .the-div-image { background-color:' . $dzsvg->mainoptions_dc['background'] . ';} ';
    $dzsvg->str_footer_css .= $selector . ' .background { background-color:' . $dzsvg->mainoptions_dc['controls_background'] . '!important;} ';
    $dzsvg->str_footer_css .= $selector . ' .scrub-bg{ background-color:' . $dzsvg->mainoptions_dc['scrub_background'] . '!important;} ';
    $dzsvg->str_footer_css .= $selector . ' .scrub-buffer{ background-color:' . $dzsvg->mainoptions_dc['scrub_buffer'] . '!important;} ';

    $dzsvg->str_footer_css .= $selector . ' .playcontrols .playSimple path,' . $selector . ' .playcontrols .pauseSimple  path{ fill:' . $dzsvg->mainoptions_dc['controls_color'] . '!important;}  ' . $selector . ' .dzsvg-control,  ' . $selector . ' .dzsvg-control a >i{ color: ' . $dzsvg->mainoptions_dc['controls_color'] . '!important; }  ' . $selector . ' .volumeicon path{ fill: ' . $dzsvg->mainoptions_dc['controls_color'] . '!important; }  ' . $selector . ' .fscreencontrols rect, ' . $selector . ' .fscreencontrols polygon { fill: ' . $dzsvg->mainoptions_dc['controls_color'] . '!important; } ' . $selector . ' .hdbutton-con .hdbutton-normal{ color: ' . $dzsvg->mainoptions_dc['controls_color'] . '!important; }   ';

    $dzsvg->str_footer_css .= $selector . ' .playSimple{ border-left-color:' . $dzsvg->mainoptions_dc['controls_color'] . '!important;  } .vplayer.skin_reborn .pauseSimple:before, .vplayer.skin_reborn .pauseSimple:after{ background-color:' . $dzsvg->mainoptions_dc['controls_color'] . '!important;  } ' . $selector . ' .skin_reborn .playcontrols{ background-color:' . $dzsvg->mainoptions_dc['controls_background'] . '!important; } ' . $selector . ' .skin_reborn .volume_static .volbar{ background-color:' . $dzsvg->mainoptions_dc['controls_background'] . '!important; } ' . $selector . ' .skin_reborn .volume_static .volbar.active{ background-color:' . $dzsvg->mainoptions_dc['controls_highlight_color'] . '!important; } ';

    $dzsvg->str_footer_css .= $selector . ' .playcontrols  .playSimple:hover path{ fill: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '!important; } ' . $selector . ' .playcontrols  .pauseSimple:hover path{ fill: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '!important; }  ' . $selector . ' .volumeicon:hover path{ fill: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '!important; }  .hdbutton-con:hover .hdbutton-normal{ color: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '!important; }      ' . $selector . ' .fscreencontrols:hover rect, ' . $selector . ' .fscreencontrols:hover polygon { fill: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '!important; }    ' . $selector . ' .dzsvg-control:hover, ' . $selector . ' .dzsvg-control:hover a > i{ color: ' . $dzsvg->mainoptions_dc['controls_hover_color'] . '!important; }     ';


    $dzsvg->str_footer_css .= $selector . ':not(.skin_white) .volume_active{ background-color: ' . $dzsvg->mainoptions_dc['controls_highlight_color'] . '!important; } ' . $selector . ' .scrub{ background-color: ' . $dzsvg->mainoptions_dc['controls_highlight_color'] . '!important; } ' . $selector . ' .hdbutton-con .hdbutton-hover{ color: ' . $dzsvg->mainoptions_dc['controls_highlight_color'] . '!important; } ';

    $dzsvg->str_footer_css .= ' .vplayer.skin_reborn .volume_active{ background-color: transparent!important; }';
    $dzsvg->str_footer_css .= $selector . ' .curr-timetext{ color: ' . $dzsvg->mainoptions_dc['timetext_curr_color'] . '; } ';


    return $fout;
  }

  public static function sanitizeShortcodeAttrToWithoutLink($input_line) {

    $fout = $input_line;

    if (preg_match('/href=\"(.*?)\"/', $input_line, $output_array)) {
      if (isset($output_array[1]) && $output_array[1]) {
        $fout = $output_array[1];
      }
    }

    return $fout;

  }

  public static function sanitizeArgsForParseItem(&$che) {

    $aux = 'overwrite_responsive_ratio';
    if (isset($che[$aux]) && $che[$aux] != '') {
      $che['responsive_ratio'] = $che[$aux];
    }
    $aux = 'the_post_content';
    if (isset($che[$aux]) && $che[$aux]) {
      if (!(isset($che['description']) && $che['description'])) {
        $che['description'] = $che[$aux];
      }
    }

    $fieldTemp = 'thethumb';
    $fieldReal = 'thumbnail';
    if (isset($che[$fieldTemp]) && $che[$fieldTemp]) {
      if (!(isset($che[$fieldReal]) && $che[$fieldReal])) {
        $che[$fieldReal] = $che[$fieldTemp];
      }
    }

  }

  public static function galleryHasExtraControls($its) {
    return (isset($its['settings']['enable_info_button']) && $its['settings']['enable_info_button'] == 'on') || (isset($its['settings']['enable_link_button']) && $its['settings']['enable_link_button'] == 'on') || (isset($its['settings']['enable_cart_button']) && $its['settings']['enable_cart_button'] == 'on') || (isset($its['settings']['enable_quality_changer_button']) && $its['settings']['enable_quality_changer_button'] == 'on') || (isset($its['settings']['enable_multisharer_button']) && $its['settings']['enable_multisharer_button'] == 'on');
  }


  static function sanitize_forUrl($arg) {
    $arg = str_replace('"', '', $arg);
    $arg = str_replace('&#8221;', '', $arg);
    $arg = str_replace('&#8243;', '', $arg);
    return $arg;
  }

  static function sanitize_forJsSnippet($arg) {

    $lb = array("\r\n", "\n", "\r");
    $arg = str_replace($lb, '', $arg);


    // -- we will prepare for ' wrap
    $arg = str_replace(array("'"), '&quot;', $arg);
    $arg = str_replace(array("{squote}"), '\\\'', $arg);

    // -- wordfence workaround..
    $arg = str_replace(array("{onclick}"), 'onclick', $arg);

    return $arg;
  }


  static function sanitize_encodeForSlidersChange($subvalue) {
    // -- used in LEGACY mode

    $subvalue = (string)$subvalue;
    $subvalue = stripslashes($subvalue);
    $subvalue = str_replace(array("\r", "\r\n", "\n", '\\', "\\"), '', $subvalue);
    $subvalue = str_replace(array("'"), '"', $subvalue);
    $subvalue = str_replace(array("</script>"), '{{endscript}}', $subvalue);


    return $subvalue;
  }

  static function sanitize_fromShortcodeAttr($arg, $che = array()) {

    $arg = str_replace('{{lsqb}}', '[', $arg);
    $arg = str_replace('{{rsqb}}', ']', $arg);
    $arg = str_replace('&#8221;', '', $arg);
    $arg = str_replace('&#8217;', '', $arg);


    $arg = str_replace('{{linkedid}}', '', $arg);
    $lab = 'itunes_link';
    if (isset($che[$lab])) {
      $arg = str_replace('{{' . $lab . '}}', $che[$lab], $arg);
    } else {

      $arg = str_replace('{{' . $lab . '}}', '', $arg);
    }


    return $arg;
  }


  static function sanitize_youtubeUrlToId($arg) {

    if (strpos($arg, 'youtube.com/embed') !== false) {
      $auxa = explode('/', 'youtube.com/embed/');

      if ($auxa[1]) {

        return $auxa[1];
      }
    }


    if (strpos($arg, 'youtube.com') !== false || strpos($arg, 'youtu.be') !== false) {


      if (DZSHelpers::get_query_arg($arg, 'v')) {
        return DZSHelpers::get_query_arg($arg, 'v');
      }

      if (strpos($arg, 'youtu.be') !== false) {
        $auxa = explode('/', 'youtube.com/embed/');

        $arg = $auxa[count($auxa) - 1];
      }
    }


    return $arg;
  }


  static function sanitize_termSlugToId($arg, $taxonomy_name = DZSVG_POST_NAME__CATEGORY) {


    if (is_numeric($arg)) {

    } else {


      if (is_int($arg) || is_string($arg)) {

        $term = get_term_by('slug', $arg, $taxonomy_name);

        if ($term) {
          $arg = $term->term_id;
        }
      } else {
        return $arg;
      }

    }


    return $arg;
  }


  public static function sanitize_for_javascript_double_quote_value($arg) {

    $arg = str_replace('"', '', $arg);

    if ($arg == '/') {
      $arg = '';
    }

    return $arg;

  }

  public static function player_fromMargsShortcodeToIts($margs, &$its) {

    global $dzsvg;
    if ($margs['sourceogg'] != '') {
      $its[0]['html5sourceogg'] = $margs['sourceogg'];
    }
    if ($margs['cover'] != '') {
      $its['settings']['coverImage'] = $margs['cover'];
    }
    if ($margs['title']) {
      $its[0]['title'] = $margs['title'];
    }
    if ($margs['loop']) {
      $its[0]['loop'] = $margs['loop'];
    }
    if ($margs['logo']) {
      $its[0]['logo'] = $margs['logo'];
    }
    if ($margs['logo_link']) {
      $its[0]['logo_link'] = $margs['logo_link'];
    }
    if ($margs['description']) {
      $its[0]['description'] = $margs['description'];
    }
    if ($margs['link']) {
      $its[0]['link'] = $margs['link'];
    }
    if ($margs['link_label']) {
      $its[0]['link_label'] = $margs['link_label'];
    }
    if (isset($margs['adarray']) && $margs['adarray']) {
      $its[0]['adarray'] = ClassDzsvgHelpers::sanitize_decodeForHtmlAttributes($margs['adarray']);
    }
    if ($margs['is_360']) {
      $its[0]['is_360'] = $margs['is_360'];
    }
  }


  static function sanitize_from_anchor_to_shortcode_attr($arg) {
    $input_lines = $arg;

    $input_lines = str_replace('&#8221;', '', $input_lines);
    $input_lines = str_replace('<wbr />', '', $input_lines);
    preg_match_all("/href=\"(.*?)\"/", $input_lines, $output_array);

    if (isset($output_array[1]) && isset($output_array[1][0])) {


      return $output_array[1][0];
    }

    return $input_lines;
  }

  static function sanitize_forKey($string) {
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    $string = str_replace('?', '-', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
  }

  static function sanitize_decodeForHtmlAttributes($arg) {

    $fout = html_entity_decode($arg);

    $fout = str_replace('{{quot}}', '"', $fout);
    $fout = str_replace('{patratstart}', '[', $fout);
    $fout = str_replace('{patratend}', ']', $fout);


    return $fout;
  }

  public static function getVideoPlayerConfig($margs) {

    global $dzsvg;


    $vpconfig_k = 0;
    $vpconfig_id = '';

    $vpsettingsdefault = array_merge($dzsvg->vpsettingsdefault, array());
    $vpsettings = array(
      'settings' => array()
    );


    if ($margs['config'] != '') {
      $vpconfig_id = $margs['config'];
    }

    if ($vpconfig_id != '') {
      for ($i = 0; $i < count($dzsvg->mainvpconfigs); $i++) {
        if ((isset($vpconfig_id)) && ($vpconfig_id == $dzsvg->mainvpconfigs[$i]['settings']['id'])) $vpconfig_k = $i;
      }
      $vpsettings = $dzsvg->mainvpconfigs[$vpconfig_k];
      if (!isset($vpsettings['settings']) || $vpsettings['settings'] == '') {
        $vpsettings['settings'] = array();
      }
    }

    if (!isset($vpsettings['settings']) || (isset($vpsettings['settings']) && !is_array($vpsettings['settings']))) {
      $vpsettings['settings'] = array();
    }
    $vpsettings['settings'] = array_merge($vpsettingsdefault, $vpsettings['settings']);
    $vpsettings['settings']['vpconfig_id'] = $vpconfig_id;
    return $vpsettings;

  }

  public static function legacyGenerateHtmlExportDatabaseForVideoConfig() {

    ?>Please note that this feature uses the last saved data. Unsaved changes will not be exported.
    <form action="<?php echo site_url() . '/wp-admin/options-general.php?page=dzsvg_menu'; ?>" method="POST">
      <input type="hidden" class="hidden" name="slidernr" value="<?php echo $_GET['slidernr']; ?>"/>
      <input type="hidden" class="hidden" name="slidername" value="<?php echo $_GET['slidername']; ?>"/>
      <input type="hidden" class="hidden" name="currdb" value="<?php echo $_GET['currdb']; ?>"/>
      <input class="button-secondary" type="submit" name="dzsvg_exportslider_config" value="Export"/>
    </form>
    <?php
  }

  public static function legacyGenerateHtmlExportDatabaseForSlider() {

    ?>Please note that this feature uses the last saved data. Unsaved changes will not be exported.
    <form action="<?php echo site_url() . '/wp-admin/options-general.php?page=dzsvg_menu'; ?>" method="POST">
      <input type="hidden" class="hidden" name="slidernr" value="<?php echo $_GET['slidernr']; ?>"/>
      <input type="hidden" class="hidden" name="slidername" value="<?php echo $_GET['slidername']; ?>"/>
      <input type="hidden" class="hidden" name="currdb" value="<?php echo $_GET['currdb']; ?>"/>
      <input class="button-secondary" type="submit" name="dzsvg_exportslider" value="Export"/>
    </form>
    <?php
  }

  public static function legacyExportDatabaseController() {

    if (isset($_GET['dzsvg_show_generator_export_slider']) && $_GET['dzsvg_show_generator_export_slider'] == 'on') {
      ClassDzsvgHelpers::legacyGenerateHtmlExportDatabaseForSlider();
      die();
    }


    if (isset($_GET['dzsvg_show_generator_export_slider_config']) && $_GET['dzsvg_show_generator_export_slider_config'] == 'on') {
      ClassDzsvgHelpers::legacyGenerateHtmlExportDatabaseForVideoConfig();
      die();
    }


  }

  public static function get_post_thumb_src($it_id) {

    if (get_post_meta($it_id, 'dzsvg_meta_thumb', true)) {
      $imgsrc = get_post_meta($it_id, 'dzsvg_meta_thumb', true);

      return $imgsrc;
    } else {

      $imgsrc = wp_get_attachment_image_src(get_post_thumbnail_id($it_id), "full");

      if (isset($imgsrc[0])) {

        return $imgsrc[0];
      }
    }

    return null;

  }


}
