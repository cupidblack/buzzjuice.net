<?php


class DzsvgAjax {

	public DZSVideoGallery $dzsvg;
  /**
   * DzsvgAjax constructor.
   * @param DZSVideoGallery $dzsvg
   */
  function __construct($dzsvg) {
    $this->dzsvg = $dzsvg;

    add_action('plugins_loaded', array($this, 'handle_plugins_loaded'), 5);
    add_action('init', array($this, 'handle_init_end'), 9999);
    add_action('wp_ajax_dzsvg_vimeo_get_vimeothumb', array($this, 'ajax_get_vimeothumb'));

    add_action('wp_ajax_dzsvg_ajax_saveSlider', array($this, 'post_save_slider'));

    add_action('wp_ajax_dzsvg_ajax_json_encode_ad', array($this, 'ajax_insert_ads'));
    add_action('wp_ajax_dzsvg_ajax_json_encode_quality', array($this, 'ajax_insert_quality'));
    add_action('wp_ajax_dzsvg_import_ytplaylist', array($this, 'post_importytplaylist'));
    add_action('wp_ajax_dzsvg_import_ytuser', array($this, 'post_importytuser'));
    add_action('wp_ajax_dzsvg_import_vimeouser', array($this, 'post_importvimeouser'));
    add_action('wp_ajax_dzsvg_get_db_gals', array($this, 'post_get_db_gals'));

    add_action('wp_ajax_dzsvg_import_galleries', array($this, 'ajax_import_galleries'));
    add_action('wp_ajax_dzsvg_import_sample_items', array($this, 'ajax_import_sample_items'));
    add_action('wp_ajax_dzsvg_remove_sample_items', array($this, 'ajax_remove_sample_items'));


    add_action('wp_ajax_dzsvp_submit_view', array($this, 'ajax_submit_view'));
    add_action('wp_ajax_nopriv_dzsvp_submit_view', array($this, 'ajax_submit_view'));

    add_action('wp_ajax_dzsvg_activate', array($this, 'ajax_activate_license'));
    add_action('wp_ajax_dzsvg_deactivate', array($this, 'ajax_deactivate_license'));

    add_action('wp_ajax_dzsvg_delete_notice', array($this, 'ajax_delete_notice'));

    add_action('wp_ajax_dzsvg_send_queue_from_sliders_admin', 'dzsvg_ajax_send_queue_from_sliders_admin');

    add_action('wp_ajax_dzsvg_import_folder', 'VideoGalleryAjaxFunctions::import_folder');
    add_action('wp_ajax_dzsvg_import_item_lib', 'VideoGalleryAjaxFunctions::ajax_import_item_lib');
    add_action('wp_ajax_dzsvg_import_simple_playlist', 'VideoGalleryAjaxFunctions::ajax_import_simple_playlist');


    add_action('wp_ajax_dzsvg_ajax_mo', array($this, 'post_save_mo'));
    add_action('wp_ajax_dzsvg_save_vpc', array($this, 'post_save_vpc'));
    add_action('wp_ajax_dzs_update_term_order', array($this, 'post_dzs_update_term_order'));

    if (defined('DOING_AJAX') && DOING_AJAX) {
      $dzsvg->init_layoutBuilder($dzsvg);
    }
  }

  function post_dzs_update_term_order() {

    $auxarray = array();
    //parsing post data
    $arr = json_decode(stripslashes($_POST['postdata']), true);


    foreach ($arr as $po) {
      update_post_meta($po['id'], $_POST['meta_key'], $po['order']);
    }
    die();
  }


  function post_save_mo() {
    $dzsvg = $this->dzsvg;

    $auxarray_defs = array('disable_api_caching' => 'off', 'disable_fontawesome' => 'off', 'tinymce_enable_preview_shortcodes' => 'off', 'force_file_get_contents' => 'off', 'debug_mode' => 'off', 'settings_trigger_resize' => 'off', 'replace_wpvideo' => 'off',
      'usewordpressuploader' => 'on',
      'dzsvp_enable_visitorupload' => 'off',
      'dzsvp_enable_ratings' => 'off',
      'dzsvp_enable_ratingscount' => 'off',
    );
    $auxarray = array();
    //parsing post data
    parse_str($_POST['postdata'], $auxarray);


    $auxarray = array_merge($auxarray_defs, $auxarray);


    $lab = 'dzsvp_enable_user_upload_capability';

    if (isset($auxarray[$lab]) && $dzsvg->mainoptions[$lab] == 'on') {

      $role = get_role('subscriber');

      // This only works, because it accesses the class instance.
      // would allow the author to edit others' posts for current theme only
      $role->add_cap('upload_files');

    }


    if (
      (isset($auxarray['track_views']) && $auxarray['track_views'] == 'on' && (isset($dzsvg->mainoptions['track_views']) == false || $dzsvg->mainoptions['track_views'] == 'off'))
      || ($auxarray['videopage_show_views'] == 'on' && $dzsvg->mainoptions['videopage_show_views'] == 'off')
      || (isset($auxarray['analytics_enable']) && $auxarray['analytics_enable'] == 'on')
    ) {


      if ($dzsvg->mainoptions['analytics_table_created'] == 'off') {

        $dzsvg->classAjax->analytics_table_create();
      }

    }


    $auxarray = array_merge($dzsvg->mainoptions, $auxarray);

    update_option($dzsvg->dboptionsname, $auxarray);
    die();
  }


  function import_demo_create_portfolio_item($pargs = array()) {


    $margs = array(

      'post_title' => '',
      'post_content' => '',
      'post_status' => '',
      'post_type' => 'dzsvcs_port_items',
    );

    $margs = array_merge($margs, $pargs);


    $args = array(
      'post_type' => $margs['post_type'],
      'post_title' => $margs['post_title'],
      'post_content' => $margs['post_content'],
      'post_status' => $margs['post_status'],


      /*other default parameters you want to set*/
    );


    $post_id = wp_insert_post($args);

    return $post_id;


  }


  function import_demo_insert_post_complete($pargs = array()) {


    $margs = array(

      'post_title' => '',

      'post_content' => '',
      'post_type' => DZSVG_POST_NAME,
      'post_status' => 'publish',
      'post_name' => '',
      'img_url' => '',
      'img_path' => '',
      'term' => '',
      'taxonomy' => '',
      'attach_id' => '',
      'dzsvp_thumb' => '',
      'dzsvp_item_type' => 'detect',
      'dzsvp_featured_media' => '',
      'dzsvg_meta_featured_media' => '',
      'q_meta_port_optional_info_2' => '',
      'q_meta_port_subtitle' => '',
      'q_meta_port_website' => '',
      'q_meta_video_cover_image' => '',
      'q_meta_image_gallery_in_meta' => '',

    );

    $margs = array_merge($margs, $pargs);


    if ($margs['post_name']) {


      $the_slug = $margs['post_name'];
      $args = array(
        'name' => $the_slug,
        'post_type' => $margs['post_type'],
        'post_status' => 'publish',
        'numberposts' => 1
      );
      $my_posts = get_posts($args);


      if ($my_posts) {

        if ($margs['term']) {

          if (is_object($margs['term']) && isset($margs['term']->term_id)) {
            $term = $margs['term']->term_id;
          } else {

            if (is_array($margs['term']) && isset($margs['term']['term_id'])) {
              $term = $margs['term']['term_id'];
            }
          }
          wp_set_post_terms($my_posts[0]->ID, $term, $margs['taxonomy']);
        }
        return $my_posts[0];


      }
    }

    $args = array(
      'post_type' => $margs['post_type'],
      'post_title' => $margs['post_title'],

      'post_content' => $margs['post_content'],
      'post_status' => $margs['post_status'],


      /*other default parameters you want to set*/
    );

    if ($margs['post_name']) {

      $args['post_name'] = $margs['post_name'];
    }


    if ($margs['term']) {

      $term = $margs['term'];
    }
    $taxonomy = $margs['taxonomy'];

    if ($margs['img_url']) {

      $img_url = $margs['img_url'];
    }
    $img_path = $margs['img_path'];


    $port_id = $this->import_demo_create_portfolio_item($args);

    if ($margs['term']) {
      $term = $margs['term'];


      if (is_object($margs['term']) && isset($margs['term']->term_id)) {
        $term = $margs['term']->term_id;
      }
      wp_set_post_terms($port_id, $term, $taxonomy);
    }


    foreach ($margs as $lab => $val) {
      if (strpos($lab, 'dzsvg_meta') === 0) {

        update_post_meta($port_id, $lab, $val);
      }
    }




    if ($margs['attach_id']) {

      set_post_thumbnail($port_id, $margs['attach_id']);
    } else {

      if ($margs['img_url']) {
        $attach_id = $this->import_demo_create_attachment($img_url, $port_id, $img_path);
        set_post_thumbnail($port_id, $attach_id);

        $this->import_demo_last_attach_id = $attach_id;
      }

    }


    return $port_id;


  }


  function import_demo_create_attachment($img_url, $port_id, $img_path) {


    $attachment = array(
      'guid' => $img_url,
      'post_mime_type' => 'image/jpeg',
      'post_title' => preg_replace('/\.[^.]+$/', '', basename($img_url)),
      'post_content' => '',
      'post_status' => 'inherit'
    );

// Insert the attachment.
    $attach_id = wp_insert_attachment($attachment, $img_url, $port_id);


    require_once(ABSPATH . 'wp-admin/includes/image.php');

// Generate the metadata for the attachment, and update the database record.
    $attach_data = wp_generate_attachment_metadata($attach_id, $img_path);

    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
  }


  function post_save_vpc() {
    $dzsvg = $this->dzsvg;
    // ---this is the main save function which saves item
    $auxarray = array();
    $mainarray = array();

    //parsing post data
    parse_str($_POST['postdata'], $auxarray);


    if (isset($_POST['currdb'])) {
      $dzsvg->currDb = sanitize_key($_POST['currdb']);
    }

    if (isset($_POST['sliderid'])) {

      $mainarray = get_option(DZSVG_JS_VPCONFIGS_NAME_LEGACY);
      foreach ($auxarray as $label => $value) {
        $aux = explode('-', $label);
        $tempmainarray[$aux[1]][$aux[2]] = sanitize_text_field($auxarray[$label]);
      }
      $mainarray[$_POST['sliderid']] = $tempmainarray;
    } else {
      foreach ($auxarray as $label => $value) {

        $aux = explode('-', $label);
        $mainarray[$aux[0]][$aux[1]][$aux[2]] = sanitize_text_field($auxarray[$label]);
      }
    }


    print_r($mainarray);
    update_option(DZSVG_JS_VPCONFIGS_NAME_LEGACY, $mainarray);
    echo 'success';
    die();
  }


  function ajax_deactivate_license() {
    $dzsvg = $this->dzsvg;

    $dzsvg->mainoptions['dzsvg_purchase_code'] = '';
    $dzsvg->mainoptions['dzsvg_purchase_code_binded'] = 'off';
    update_option($dzsvg->dboptionsname, $dzsvg->mainoptions);
    die();
  }

  function ajax_activate_license() {

    $dzsvg = $this->dzsvg;


    $purchaseCode = sanitize_key($_POST['postdata']);
    $dzsvg->mainoptions['dzsvg_purchase_code'] = $purchaseCode;
    $dzsvg->mainoptions['dzsvg_purchase_code_binded'] = 'on';
    update_option($dzsvg->dboptionsname, $dzsvg->mainoptions);
    die();

  }


  function ajax_delete_notice() {
    update_option($_POST['postdata'], 'seen');
    die();
  }


  function misc_get_ip() {

    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    $ip = filter_var($ip, FILTER_VALIDATE_IP);
    $ip = ($ip === false) ? '0.0.0.0' : $ip;


    return $ip;
  }


  function ajax_submit_view() {


    $dzsvg = $this->dzsvg;

    $playerid = 1;
    if (isset($_POST['playerid'])) {
      $playerid = sanitize_key($_POST['playerid']);
      $playerid = str_replace('ap', '', $playerid);
    }


    if (isset($_COOKIE["dzsvp_viewsubmitted-" . $playerid]) && $_COOKIE["dzsvp_viewsubmitted-" . $playerid] == '1') {

    } else {

      $currip = $this->misc_get_ip();
      $date = date('Y-m-d H:i:s');


      $postData = sanitize_key($_POST['postdata']);
      setcookie("dzsvp_viewsubmitted-" . $playerid, $postData, time() + 36000, COOKIEPATH);

      global $wpdb;
      $table_name = $wpdb->prefix . DZSVG_DB_TABLE_NAME_ACTIVITY;

      $user_id = get_current_user_id();


      $wpdb->insert(
        $table_name,
        array(
          'ip' => $currip,
          'type' => 'view',
          'id_user' => $user_id,
          'id_video' => $playerid,
          'date' => $date,
        )
      );


      echo 'success';
    }


    die();


  }


  function ajax_remove_sample_items() {

    $demo_data = get_option('dzsvg_demo_data');


    $taxonomy = DZSVG_POST_NAME__CATEGORY;


    foreach ($demo_data['posts'] as $pid) {
      wp_delete_post($pid);
    };

    foreach ($demo_data['cats'] as $categ_ID) {
      wp_delete_term($categ_ID, $taxonomy);
    };

    delete_option('dzsvg_demo_data');


    die();
  }


  function ajax_import_sample_items() {


    if (get_option('dzsvg_demo_data') == '') {
      include_once(DZSVG_PATH . 'class_parts/install_sample_data.php');
    }

    die();
  }


  function ajax_import_galleries() {

    $dzsvg = $this->dzsvg;

    if ($dzsvg->mainitems == '') {
      $dzsvg->mainitems = array();
    }


    $mainitems_default_ser = file_get_contents(DZSVG_PATH . '/sampledata/sample_items.txt');
    $aux = unserialize($mainitems_default_ser);

    foreach ($aux as $lab => $val) {

      $seekid = $val['settings']['id'];


      $sw = false;
      foreach ($dzsvg->mainitems as $lab2 => $val2) {

        if ($seekid === $val2['settings']['id']) {

          $sw = true;
          break;
        }

      }

      if ($sw) {
        unset($aux[$lab]);
      }
    }

    $dzsvg->mainitems = array_merge($dzsvg->mainitems, $aux);
    update_option($dzsvg->dbkey_legacyItems, $dzsvg->mainitems);


    echo 'success - ' . esc_html__('galleries imported for sample data use');
    die();
  }


  function post_get_db_gals() {

    $dzsvg = $this->dzsvg;
    if (isset($_POST['postdata'])) {
      $dzsvg->currDb = sanitize_text_field($_POST['postdata']);
    }


    if ($dzsvg->currDb != 'main' && $dzsvg->currDb != '') {
      $dzsvg->dbkey_legacyItems .= '-' . $dzsvg->currDb;
    }


    $mainarray = get_option($dzsvg->dbkey_legacyItems);

    $i = 0;
    foreach ($mainarray as $gal) {
      if ($i > 0) {
        echo ';';
      }

      echo $gal['settings']['id'];

      $i++;
    }


    die();
  }


  function post_importytplaylist() {

    $dzsvg = $this->dzsvg;
    $pd = sanitize_text_field($_POST['postdata']);

    $yf_maxi = 100;
    $i = 0;
    $its = array();

    $str_apikey = '';

    if ($dzsvg->mainoptions['youtube_api_key'] != '') {
      $str_apikey = '&key=' . $dzsvg->mainoptions['youtube_api_key'];
    }

    $target_file = $dzsvg->httpprotocol . "://gdata.youtube.com/feeds/api/playlists/" . $pd . "?alt=json&start-index=1&max-results=40" . $str_apikey;
    $ida = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));
    $idar = json_decode($ida);
    if ($idar == false) {
      echo 'error: ' . 'check the id';
    } else {
      foreach ($idar->feed->entry as $ytitem) {
        $cache = $ytitem;
        $aux = array();
        $auxtitle = '';
        $auxcontent = '';
        foreach ($cache->title as $hmm) {
          $auxtitle = $hmm;
          break;
        }
        foreach ($cache->content as $hmm) {
          $auxcontent = $hmm;
          break;
        }
        parse_str($ytitem->link[0]->href, $aux);

        $its[$i]['source'] = $aux[$dzsvg->httpprotocol . '://www_youtube_com/watch?v'];
        $its[$i]['thethumb'] = "";
        $its[$i]['type'] = "youtube";
        $its[$i]['title'] = $auxtitle;
        $its[$i]['menuDescription'] = $auxcontent;
        $its[$i]['description'] = $auxcontent;

        $aux2 = get_object_vars($ytitem->title);
        $aux = ($aux2['$t']);
        $lb = array("\r\n", "\n", "\r", "&", "-", "`", '???', "'", '-');
        $aux = str_replace($lb, ' ', $aux);


        $i++;
        if ($i > $yf_maxi) break;
      }
    }

    if (count($its) == 0) {
      echo 'error: ' . '<a href="' . $target_file . '">this</a> is what the feed returned ' . $ida;
      die();
    }
    for ($i = 0; $i < count($its); $i++) {

    }
    $sits = json_encode($its);
    echo $sits;


    die();
  }

  function post_importytuser() {
    global $dzsvg;

    $userName = sanitize_text_field($_POST['postdata']);
    $yf_maxi = 100;
    $i = 0;
    $its = array();



    $sw = false;
    $i = 0;
    $yf_maxi = 100;




    $target_file = $dzsvg->httpprotocol . "://gdata.youtube.com/feeds/api/users/" . $userName . "/uploads?v=2&alt=jsonc";
    $ida = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));
    $idar = json_decode($ida);

    if ($ida == 'yt:quotatoo_many_recent_calls') {
      echo 'error: too many recent calls - YouTube rejected the call';
      $sw = true;
    }

    if ($idar == false) {
      echo 'error: ' . 'check the id ';
      print_r($ida);
      die();
    } else {

      foreach ($idar->data->items as $ytitem) {
        $its[$i]['source'] = $ytitem->id;
        $its[$i]['thethumb'] = "";
        $its[$i]['type'] = "youtube";

        $aux = $ytitem->title;
        $lb = array('"', "\r\n", "\n", "\r", "&", "-", "`", '???', "'", '-');
        $aux = str_replace($lb, ' ', $aux);
        $its[$i]['title'] = $aux;

        $aux = $ytitem->description;
        $lb = array("\r\n", "\n", "\r", "&", '???');
        $aux = str_replace($lb, ' ', $aux);
        $lb = array('"');
        $aux = str_replace($lb, '&quot;', $aux);
        $lb = array("'");
        $aux = str_replace($lb, '&#39;', $aux);
        $its[$i]['description'] = $aux;

        $i++;
        if ($i > $yf_maxi + 1) break;
      }
    }
    if (count($its) == 0) {
      echo 'error: ' . 'this is what the feed returned ' . $ida;
      die();
    }
    $sits = json_encode($its);
    echo $sits;


    die();
  }


  /**
   * this is the main save function which saves gallery
   */
  function ajax_insert_quality() {


    $ad_arr_str = '';


    $postdata = array();


    parse_str($_POST['postdata'], $postdata);


    $ad_arr = array();

    foreach ($postdata['source'] as $lab => $val) {


      if (!$postdata['source'][$lab]) {

        continue;
      }

      $theSource = sanitize_text_field($postdata['source'][$lab]);
      $theLabel = sanitize_text_field($postdata['source'][$lab]);
      $aux_arr = array(
        'source' => $theSource,
        'label' => $theLabel,
      );


      array_push($ad_arr, $aux_arr);
    }


    $ad_arr_str = json_encode($ad_arr);
    echo($ad_arr_str);

    die();
  }


  /**
   * this is the main save function which saves gallery
   */
  function ajax_insert_ads() {



    $ad_arr_str = '';


    $postdata = array();


    parse_str($_POST['postdata'], $postdata);


    $ad_arr = array();

    foreach ($postdata['source'] as $lab => $val) {




      if ($postdata['source'][$lab]) {

      } else {
        continue;
      }
      $aux_arr = array(
        'source' => $postdata['source'][$lab],
      );


      $adlab = 'time';

      if (isset($postdata[$adlab][$lab]) && $postdata[$adlab][$lab]) {
        $aux_arr[$adlab] = $postdata[$adlab][$lab];
      }


      $adlab = 'type';

      if (isset($postdata[$adlab][$lab]) && $postdata[$adlab][$lab]) {
        $aux_arr[$adlab] = $postdata[$adlab][$lab];
      }


      $adlab = 'ad_link';

      if (isset($postdata[$adlab][$lab]) && $postdata[$adlab][$lab]) {
        $aux_arr[$adlab] = $postdata[$adlab][$lab];
      }


      $adlab = 'skip_delay';

      if (isset($postdata[$adlab][$lab]) && $postdata[$adlab][$lab]) {
        $aux_arr[$adlab] = $postdata[$adlab][$lab];
      }

      array_push($ad_arr, $aux_arr);
    }


    $ad_arr_str = json_encode($ad_arr);
    print_r($ad_arr_str);

    die();
  }


  function post_importvimeouser() {
    global $dzsvg;

    $userName = sanitize_key($_POST['postdata']);
    $yf_maxi = 100;
    $i = 0;
    $its = array();

    $target_file = "https://vimeo.com/api/v2/" . $userName . "/videos.json";
    $ida = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));
    $idar = json_decode($ida);
    $i = 0;
    if ($idar == false) {
      echo 'error: ' . 'check the id ';
      print_r($ida);
      die();
    } else {
      foreach ($idar as $item) {
        $its[$i]['source'] = $item->id;
        $its[$i]['thethumb'] = $item->thumbnail_small;


        $its[$i]['type'] = "vimeo";

        $aux = $item->title;
        $lb = array('"', "\r\n", "\n", "\r", "&", "-", "`", '???', "'", '-');
        $aux = str_replace($lb, ' ', $aux);
        $its[$i]['title'] = $aux;

        $aux = $item->description;
        $lb = array("\r\n", "\n", "\r", "&", '???');
        $aux = str_replace($lb, ' ', $aux);
        $lb = array('"');
        $aux = str_replace($lb, '&quot;', $aux);
        $lb = array("'");
        $aux = str_replace($lb, '&#39;', $aux);
        $its[$i]['description'] = $aux;
        $i++;
      }
    }
    if (count($its) == 0) {
      echo 'error: ' . 'this is what the feed returned ' . $ida;
      die();
    }

    $sits = json_encode($its);
    echo $sits;


    die();
  }


  /**
   * *deprecated
   */
  function post_save_slider() {

    $dzsvg = $this->dzsvg;
    // --- this is the main save function which saves gallery
    $postDataArray = array();
    $arrayItems = array();

    //parsing post data
    parse_str($_POST['postdata'], $postDataArray);


    if (isset($_POST['currdb'])) {
      $dzsvg->currDb = sanitize_key($_POST['currdb']);
    }


    // -- save gallery


    if ($dzsvg->currDb != 'main' && $dzsvg->currDb != '') {
      $dzsvg->dbkey_legacyItems = DZSVG_DBKEY_LEGACY_ITEMS . '-' . $dzsvg->currDb;
    }


    if (isset($_POST['sliderid'])) {
      $arrayItems = get_option($dzsvg->dbkey_legacyItems);
      foreach ($postDataArray as $label => $value) {
        $aux = explode('-', $label);
        $newPostDataArray[$aux[1]][$aux[2]] = $postDataArray[$label];
      }
      $arrayItems[$_POST['sliderid']] = $newPostDataArray;
    } else {
      foreach ($postDataArray as $label => $value) {
        $aux = explode('-', $label);
        $arrayItems[$aux[0]][$aux[1]][$aux[2]] = $postDataArray[$label];
      }
    }

    foreach ($arrayItems as $key => $item) {
      if (!($arrayItems[$key]) || $arrayItems[$key] && (isset($arrayItems[$key]['settings']) === false)) {
        unset($arrayItems[$key]);
      }
    }
    $arrayItems = array_values($arrayItems);
    update_option($dzsvg->dbkey_legacyItems, $arrayItems);
    echo 'success';
    die();
  }


  function handle_plugins_loaded() {


    if (is_admin()) {

      if (isset($_GET['dzsvg_action'])) {

        if ($_GET['dzsvg_action'] == 'report_download') {


          $dzsvg = $this->dzsvg;
          header('Content-Description: File Transfer');
          header('Content-type: text/plain');

          header('Expires: 0');
          header('Cache-Control: must-revalidate');
          header('Pragma: public');
          header('Content-Disposition: attachment; filename="dzsvg-report.html"');
          include DZSVG_PATH . 'class_parts/settings-page/report-generator--html.php';
          die();
        }
      }
    }
  }

  function handle_init_end() {

    if (!is_admin()) {
      if (isset($_GET['dzsvg_action'])) {
        if ($_GET['dzsvg_action'] == 'load_gallery_items_for_pagination') {
          echo do_shortcode('[videogallery id="' . $_GET['gallery_id'] . '" settings_separation_mode="scroll"  settings_separation_paged="' . $_GET[DZSVG_PLAYLIST_PAGINATION_QUERY_ARG] . '"  settings_separation_pages_number="' . $_GET['settings_separation_pages_number'] . '" return_mode="parsed items" call_script="off" called_from="ajax_pagination" ]');
          die();
        }


      }

    } else {
    }
    $this->check_posts();


  }

  function check_posts() {

    $dzsvg = $this->dzsvg;


    // -- legacy ADMIN
    if (isset($_POST['deleteslider']) && $_POST['deleteslider']) {


      // -- deleteslider
      if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_LEGACY_SLIDERS) {
        unset($dzsvg->mainitems[$_POST['deleteslider']]);
        $dzsvg->mainitems = array_values($dzsvg->mainitems);
        $dzsvg->currSlider = 0;

        update_option($dzsvg->dbkey_legacyItems, $dzsvg->mainitems);
      }


      if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_VPCONFIGS) {
        unset($dzsvg->mainvpconfigs[$_POST['deleteslider']]);
        $dzsvg->mainvpconfigs = array_values($dzsvg->mainvpconfigs);
        $dzsvg->currSlider = 0;

        update_option(DZSVG_JS_VPCONFIGS_NAME_LEGACY, $dzsvg->mainvpconfigs);
      }
    }

    if (isset($_POST['dzsvg_duplicateslider'])) {

      // -- duplicate slider
      if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_LEGACY_SLIDERS) {
        $aux = ($dzsvg->mainitems[$_POST['dzsvg_duplicateslider']]);
        array_push($dzsvg->mainitems, $aux);
        $dzsvg->mainitems = array_values($dzsvg->mainitems);
        $dzsvg->currSlider = count($dzsvg->mainitems) - 1;
        update_option($dzsvg->dbkey_legacyItems, $dzsvg->mainitems);
      }
    }
    // -- legacy ADMIN END


    // --- check posts
    if (isset($_GET['dzsvg_action'])) {


      if ($_GET['dzsvg_action']) {

        if ($_GET['dzsvg_action'] == 'get_vimeo_thumb') {


          $hash = unserialize(DZSHelpers::get_contents("https://vimeo.com/api/v2/video/" . $_GET['vimeo_id'] . ".php"));


          $str_featuredimage = $hash[0]['thumbnail_medium'];


          die($str_featuredimage);

        }


      }
    }
    if (isset($_GET['dzsvg_shortcode_builder']) && $_GET['dzsvg_shortcode_builder'] == 'on') {

      include_once(DZSVG_PATH . '/tinymce/popupiframe.php');
      define('DONOTCACHEPAGE', true);
      define('DONOTMINIFY', true);

    }
    if (isset($_GET['dzsvg_shortcode_showcase_builder']) && $_GET['dzsvg_shortcode_showcase_builder'] == 'on') {

      include_once(DZSVG_PATH . '/tinymce/popupiframe_showcase.php');
      define('DONOTCACHEPAGE', true);
      define('DONOTMINIFY', true);
    }
    if (isset($_GET['dzsvg_reclam_builder']) && $_GET['dzsvg_reclam_builder'] == 'on') {

      include_once(DZSVG_PATH . '/tinymce/ad_builder.php');
      define('DONOTCACHEPAGE', true);
      define('DONOTMINIFY', true);

    }
    if (isset($_GET['dzsvg_quality_builder']) && $_GET['dzsvg_quality_builder'] == 'on') {

      include_once(DZSVG_PATH . '/tinymce/quality_builder.php');
      define('DONOTCACHEPAGE', true);
      define('DONOTMINIFY', true);

    }


  }


  function ajax_get_vimeothumb() {

    $dzsvg = $this->dzsvg;
    $id = sanitize_key($_POST['postdata']);


    $id = ClassDzsvgHelpers::vimeo_detectIdFromUrl($id);



    if ($dzsvg->mainoptions['vimeo_api_client_id'] != '' && $dzsvg->mainoptions['vimeo_api_client_secret'] != '' && $dzsvg->mainoptions['vimeo_api_access_token'] != '') {


      if (!class_exists('VimeoAPIException')) {
        require_once(DZSVG_PATH . '/inc/vimeoapi/vimeo.php');
      }


      $vimeo_id = $dzsvg->mainoptions['vimeo_api_user_id']; // Get from https://vimeo.com/settings, must be in the form of user123456
      $consumer_key = $dzsvg->mainoptions['vimeo_api_client_id'];
      $consumer_secret = $dzsvg->mainoptions['vimeo_api_client_secret'];
      $token = $dzsvg->mainoptions['vimeo_api_access_token'];

      // Do an authentication call
      $vimeo = new Vimeo($consumer_key, $consumer_secret);
      $vimeo->setToken($token); //,$token_secret


      $vimeo_response = $vimeo->request('/videos/' . $id . '/pictures');

      if ($vimeo_response['status'] != 200) {
        echo 'error - vimeo error';
        print_r($vimeo_response);
      }

      $ida = '';
      if (isset($vimeo_response['body']['data'])) {
        $ida = $vimeo_response['body']['data'];
      }

      if ($ida && $ida[0]) {
        $vimeo_quality_ind = 2;
        if ($dzsvg->mainoptions['vimeo_thumb_quality'] == 'medium') {
          $vimeo_quality_ind = 3;
        }
        if ($dzsvg->mainoptions['vimeo_thumb_quality'] == 'high') {
          $vimeo_quality_ind = 4;
        }

        if (isset($ida[0]['sizes'][$vimeo_quality_ind]['link'])) {
          echo $ida[0]['sizes'][$vimeo_quality_ind]['link'];
        } else {
          if (isset($ida[0]['sizes'][(--$vimeo_quality_ind)]['link'])) {
            echo $ida[0]['sizes'][$vimeo_quality_ind]['link'];
          } else {
            if (isset($ida[0]['sizes'][(--$vimeo_quality_ind)]['link'])) {
              echo $ida[0]['sizes'][$vimeo_quality_ind]['link'];
            }
          }
        }


      }

    } else {
      die('error: you need to input credentials');
    }


    die();
  }


  function analytics_table_create() {

    global $wpdb;
    $dzsvg = $this->dzsvg;


    $table_name = $wpdb->prefix . DZSVG_DB_TABLE_NAME_ACTIVITY;
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      //table not in database. Create new table
      $charset_collate = $wpdb->get_charset_collate();

      $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          type varchar(100) NOT NULL,
          country varchar(100) NULL,
          id_user int(10) NOT NULL,
          val int(255) NOT NULL,
          ip varchar(255) NOT NULL,
          id_video int(10) NOT NULL,
          date datetime NOT NULL,
          UNIQUE KEY id (id)
     ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);

      $dzsvg->mainoptions['analytics_table_created'] = 'on';

      $auxarray['analytics_table_created'] = 'on';;

    } else {
    }

  }


  public function checkAjaxGetFunctions() {

    $dzsvg = $this->dzsvg;
    if (isset($_GET['action'])) {
      if ($_GET['action'] == 'ajax_analytics_table_create') {
        $this->analytics_table_create();
      }
      if ($_GET['action'] == 'ajax_dzsvg_submit_files') {


	      /**
								 * DZS Upload
								 * version: 1.0
								 * author: digitalzoomstudio
								 * website: https://digitalzoomstudio.net
								 *
								 * Dual licensed under the MIT and GPL licenses:
								 *   https://www.opensource.org/licenses/mit-license.php
								 *   https://www.gnu.org/licenses/gpl.html
								 */


        $isAllowed = false;


        $current_user = wp_get_current_user();

        if (isset($_GET['from']) && $_GET['from'] == 'dzsvp_portal') {

          if ($dzsvg->mainoptions['dzsvp_enable_visitorupload'] == 'on' || $dzsvg->mainoptions['dzsvp_enable_user_upload_capability'] == 'on') {

            if ($current_user->ID) {
              $isAllowed = true;
            }
          }
        }


        if (current_user_can('upload_files')) {
          $isAllowed = true;
        }


        if ($isAllowed) {

        } else {
          die('no permission');
        }

        $allowed_filetypes = array('.jpg', '.jpeg', '.png', '.gif', '.tiff', '.txt', '.mp4', '.m4v', '.mov', '.ogg', '.ogv', '.webm', '.sql', '.mp3');
        $upload_dir = DZSVG_PATH . '/upload';


        if (isset($_FILES['file_field']['tmp_name'])) {
          $file_name = $_FILES['file_field']['name'];
          $file_name = str_replace(" ", "_", $file_name); // strip spaces
          $path = $upload_dir . "/" . $file_name;


          $sw = true;


          foreach ($allowed_filetypes as $dft) {

            $pos = strpos(strtolower($file_name), $dft);


            if ($pos > strlen($file_name) - 6) {
              $sw = false;
            }
          }

          if ($sw == true) {
            $arr = array(
              'type' => 'upload_notice',
              'report' => 'error',
              'text' => esc_html__('extension not allowed') . '( dir - ' . $upload_dir . ' )',
            );
            die(json_encode($arr));
          }
          if (!is_writable($upload_dir)) {
            $arr = array(
              'type' => 'upload_notice',
              'report' => 'error',
              'text' => esc_html__('dir not writable - check permissions') . '( dir - ' . $upload_dir . ' )',
            );
            die(json_encode($arr));
          }


          if (copy($_FILES['file_field']['tmp_name'], $path)) {
            echo '<div class="success">file uploaded</div><script>top.hideFeedbacksCall();</script>';
          } else {
            echo '<div class="error">file could not be uploaded</div><script>window.hideFeedbacksCall()</script>';
          }


        } else {
          $headers = $_SERVER;
          if (isset($headers['HTTP_X_FILE_NAME'])) {
            $file_name = $headers['HTTP_X_FILE_NAME'];
            $file_name = str_replace(" ", "_", $file_name); // strip spaces


            if (isset($headers['HTTP_X_FILE_UPLOAD_DIR']) && $headers['HTTP_X_FILE_UPLOAD_DIR']) {
              $upload_dir = $headers['HTTP_X_FILE_UPLOAD_DIR'];

            }


            if (isset($_POST['upload_path']) && $_POST['upload_path']) {
              $upload_dir = sanitize_text_field($_POST['upload_path']);

            }


            $target = $upload_dir . "/" . $file_name;


            // --  checking for disallowed file types
            $sw = true;

            foreach ($allowed_filetypes as $dft) {
              $pos = strpos(strtolower($file_name), $dft);


              if ($pos > strlen($file_name) - 6) {
                $sw = false;
              }
            }


            if ($sw == true) {
              $arr = array(
                'type' => 'upload_notice',
                'report' => 'error',
                'text' => esc_html__('extension not allowed') . '( dir - ' . $upload_dir . ' )',
              );
              die(json_encode($arr));
            }

            if (!is_writable($upload_dir)) {


              $arr = array(
                'type' => 'upload_notice',
                'report' => 'error',
                'text' => esc_html__('dir not writable - check permissions') . '( dir - ' . $upload_dir . ' )',
              );
              die(json_encode($arr));
            }

            $auxindex = 0;
            $auxname = $file_name;
            $auxpath = $target;
            if (file_exists($target)) {


              $part1_target = $target;
              $part2_target = '';


              $part1_name = $auxname;
              $part2_name = '';

              if (strpos($target, '.png') !== false || strpos($target, '.jpg') !== false || strpos($target, '.mp4') !== false || strpos($target, '.m4v') !== false
                || strpos($target, '.ogg') !== false || strpos($target, '.ogv') !== false || strpos($target, '.gif') !== false || strpos($target, '.mp3') !== false
                || strpos($target, '.gif') !== false
              ) {
                $part1_target = substr($target, 0, -4);
                $part2_target = substr($target, -4);
              }


              if (strpos($auxname, '.png') !== false || strpos($auxname, '.jpg') !== false || strpos($auxname, '.mp4') !== false || strpos($auxname, '.m4v') !== false
                || strpos($auxname, '.ogg') !== false || strpos($auxname, '.ogv') !== false || strpos($auxname, '.gif') !== false || strpos($auxname, '.mp3') !== false
                || strpos($auxname, '.gif') !== false
              ) {
                $part1_name = substr($auxname, 0, -4);
                $part2_name = substr($auxname, -4);
              }

              if (strpos($target, '.jpeg') !== false) {
                $part1_target = substr($target, 0, -5);
                $part2_target = substr($target, -5);
              }


              if (strpos($auxname, '.jpeg') !== false) {
                $part1_name = substr($auxname, 0, -5);
                $part2_name = substr($auxname, -5);
              }

              while (file_exists($auxpath) === true) {
                $auxindex++;

                $auxpath = $part1_target . '_' . $auxindex . $part2_target;
                $auxname = $part1_name . '_' . $auxindex . $part2_name;
              }
            }

            $target = $auxpath;


            if (move_uploaded_file($_FILES['myfile']['tmp_name'], $target)) {

              echo 'success - file written {{filename-' . $auxname . '}}';
            } else {

              $arr = array(
                'type' => 'upload_notice',
                'report' => 'error',
                'text' => esc_html__("error at ") . 'move_uploaded_file',
              );
              die(json_encode($arr));
            }


          } else {
            die('not for direct access');
          }
        }

        die();

      }


// -- submit view
      if ($_GET['action'] == 'ajax_dzsvg_submit_view') {
        $date = date('Y-m-d H:i:s');


        $id = sanitize_key($_POST['video_analytics_id']);
        $country = '0';

        if ($dzsvg->mainoptions['analytics_enable_location'] == 'on') {


          if ($_SERVER['REMOTE_ADDR']) {


            $request = wp_remote_get('https://ipinfo.io/' . $_SERVER['REMOTE_ADDR'] . '/json');
            $response = wp_remote_retrieve_body($request);
            $aux_arr = json_decode($response);


            if ($aux_arr) {
              $country = $aux_arr->country;
            }
          }
        }


        $userid = '';
        $userid = get_current_user_id();
        if ($dzsvg->mainoptions['analytics_enable_user_track'] == 'on') {

          if ($_POST['dzsvg_curr_user']) {
            $userid = sanitize_text_field($_POST['dzsvg_curr_user']);
          }
        }


        $playerid = $id;


        if (isset($_COOKIE["dzsvg_viewsubmitted-" . $playerid]) && $_COOKIE["dzsvg_viewsubmitted-" . $playerid] == '1') {

        } else {


          $nr_views = get_post_meta($id, 'dzsvg_nr_views', true);

          $nr_views = intval($nr_views);
          update_post_meta($id, 'dzsvg_nr_views', ++$nr_views);


          $currip = $this->misc_get_ip();


          setcookie("dzsvg_viewsubmitted-" . $playerid, 1, time() + 36000, COOKIEPATH);


          global $wpdb;


          $table_name = $wpdb->prefix . DZSVG_DB_TABLE_NAME_ACTIVITY;


          if ($dzsvg->mainoptions['analytics_enable_user_track'] == 'on') {

            // -- date precise
            $date = date('Y-m-d H:i:s');
            $wpdb->insert(
              $table_name,
              array(
                'ip' => $currip,
                'country' => $country,
                'type' => 'view',
                'val' => 1,
                'id_user' => $userid,
                'id_video' => $playerid,
                'date' => $date,
              )
            );
          } else {


            // -- date more generic for select matches
            $date = date('Y-m-d');


            // -- submit to total plays for today

            $query = 'SELECT * FROM ' . $table_name . ' WHERE id_user = \'0\' AND date=\'' . $date . '\'  AND type=\'' . 'view' . '\' AND id_video=\'' . ($playerid) . '\'';
            if ($dzsvg->mainoptions['analytics_enable_location'] == 'on' && $country) {
              $query .= ' AND country=\'' . $country . '\'';
            }
            $results = $wpdb->get_results($query, OBJECT);


            if (is_array($results) && count($results) > 0) {


              $val = intval($results[0]->val);
              $newval = $val + 1;

              $wpdb->update(
                $table_name,
                array(
                  'val' => $val + 1,
                ),
                array('ID' => $results[0]->id),
                array(
                  '%s',    // value1
                ),
                array('%d')
              );


            } else {

              $wpdb->insert(
                $table_name,
                array(
                  'ip' => 0,
                  'type' => 'view',
                  'id_user' => 0,
                  'id_video' => $playerid,
                  'date' => $date,
                  'val' => 1,
                  'country' => $country,
                )
              );
            }

          }


          echo $nr_views;


          $query = 'SELECT * FROM ' . $table_name . ' WHERE id_user = \'0\' AND date=\'' . $date . '\'  AND type=\'' . 'view' . '\' AND id_video=\'' . (0) . '\'';
          if ($dzsvg->mainoptions['analytics_enable_location'] == 'on' && $country) {
            $query .= ' AND country=\'' . $country . '\'';
          }
          $results = $wpdb->get_results($query, OBJECT);


          if (is_array($results) && count($results) > 0) {


            $val = intval($results[0]->val);
            $newval = $val + 1;

            $wpdb->update(
              $table_name,
              array(
                'val' => $val + 1,
              ),
              array('ID' => $results[0]->id),
              array(
                '%s',    // value1
              ),
              array('%d')
            );


          } else {

            $wpdb->insert(
              $table_name,
              array(
                'ip' => 0,
                'type' => 'view',
                'id_user' => 0,
                'id_video' => 0,
                'date' => $date,
                'val' => 1,
                'country' => $country,
              )
            );
          }


          die();

        }


        die();


      }


      if (isset($_GET['action']) && $_GET['action'] == 'ajax_dzsvg_submit_contor_60_secs') {

        $date = date('Y-m-d');


        $country = '0';
        $id = sanitize_key($_POST['video_analytics_id']);

        if ($dzsvg->mainoptions['analytics_enable_location'] == 'on') {


          if ($_SERVER['REMOTE_ADDR']) {


            $request = wp_remote_get('https://ipinfo.io/' . $_SERVER['REMOTE_ADDR'] . '/json');
            $response = wp_remote_retrieve_body($request);
            $aux_arr = json_decode($response);


            if ($aux_arr) {
              $country = $aux_arr->country;
            }
          }
        }


        $userid = '';
        $userid = get_current_user_id();
        if ($dzsvg->mainoptions['analytics_enable_user_track'] == 'on') {

          if ($_POST['dzsvg_curr_user']) {
            $userid = sanitize_text_field($_POST['dzsvg_curr_user']);
          }
        }


        $playerid = $id;

        global $wpdb;
        $table_name = $wpdb->prefix . DZSVG_DB_TABLE_NAME_ACTIVITY;


        $results = $GLOBALS['wpdb']->get_results('SELECT * FROM ' . $table_name . ' WHERE id_user = \'' . $userid . '\' AND date=\'' . $date . '\'  AND type=\'' . 'timewatched' . '\' AND id_video=\'' . $playerid . '\'', OBJECT);


        if (is_array($results) && count($results) > 0) {


          $val = intval($results[0]->val);

          $newval = $val + 60;

          $wpdb->update(
            $table_name,
            array(
              'val' => $val + 60,
            ),
            array('ID' => $results[0]->id),
            array(
              '%s',
            ),
            array('%d')
          );




        } else {
          $currip = $this->misc_get_ip();


          $wpdb->insert(
            $table_name,
            array(
              'ip' => $currip,
              'type' => 'timewatched',
              'id_user' => $userid,
              'id_video' => $playerid,
              'date' => $date,
              'val' => 60,
              'country' => $country,
            )
          );
        }


        // -- global table

        $query = 'SELECT * FROM ' . $table_name . ' WHERE id_user = \'0\' AND date=\'' . $date . '\'  AND type=\'' . 'timewatched' . '\' AND id_video=\'' . (0) . '\'';
        if ($dzsvg->mainoptions['analytics_enable_location'] == 'on' && $country) {
          $query .= ' AND country=\'' . $country . '\'';
        }
        $results = $GLOBALS['wpdb']->get_results($query, OBJECT);


        if (is_array($results) && count($results) > 0) {


          $val = intval($results[0]->val);
          $newval = $val + 60;

          $wpdb->update(
            $table_name,
            array(
              'val' => $val + 60,
            ),
            array('ID' => $results[0]->id),
            array(
              '%s',    // value1
            ),
            array('%d')
          );


        } else {

          $wpdb->insert(
            $table_name,
            array(
              'ip' => 0,
              'type' => 'timewatched',
              'id_user' => 0,
              'id_video' => 0,
              'date' => $date,
              'country' => $country,
              'val' => 60,
            )
          );
        }


        die();


      }


    }


    if (isset($_GET['dzsvg_action'])) {


      if ($_GET['dzsvg_action'] == 'dzsvp_submit_like') {
        $this->ajax_submit_like();
      }

      if ($_GET['dzsvg_action'] == 'showinzoombox') {

        echo do_shortcode('[videogallery id="' . esc_html($_GET['id']) . '"]');

        die();


      }

      if ($_GET['dzsvg_action'] == 'dzsvp_retract_like') {

        $this->ajax_retract_like();
      }
      if ($_GET['dzsvg_action'] == 'savescreenshot') {
        if (current_user_can('upload_files')) {
          $isAllowed = true;


          $upload_dir = wp_upload_dir();


          $realpath = $upload_dir['path'];
          $realpath = str_replace('\\', '/', $realpath);


          $name = str_replace('.', '', ClassDzsvgHelpers::sanitize_forHtmlClass($_GET['name']));

          $target_path = $realpath . '/' . $name . '.png';
          $target_url = $upload_dir['url'] . '/' . $name . '.png';


          $data = sanitize_text_field($_POST['imgData']);


          $data = str_replace('data:image/png;base64,', '', $data);
          $data = str_replace(' ', '+', $data);


          file_put_contents($target_path, base64_decode($data));

          echo $target_url;

        }

        die();

      }


      if ($_GET['dzsvg_action'] == 'load_charts_html') {
        $yesterday = date("d M", time() - 60 * 60 * 24);
        $days_2 = date("d M", time() - 60 * 60 * 24 * 2);
        $days_3 = date("d M", time() - 60 * 60 * 24 * 3);


        // -- chart

        $trackid = sanitize_key($_POST['postdata']);
        $arr = array(
          'labels' => array(esc_html__('Track'), esc_html__('Views'), esc_html__('Likes')),
          'lastdays' => array(
            array(

              $days_3,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '4',
                'day_end' => '3',
                'type' => 'view',
                'get_count' => 'off',
              )),
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '4',
                'day_end' => '3',
                'type' => 'like',
                'get_count' => 'off',
              )),
            ),
            array(

              $days_2,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '3',
                'day_end' => '2',
                'type' => 'view',
              )),
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '3',
                'day_end' => '2',
                'type' => 'like',
              )),
            ),

            array(

              $yesterday,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '2',
                'day_end' => '1',
                'type' => 'view',
              )),
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '2',
                'day_end' => '1',
                'type' => 'like',
              )),
            ),
            array(

	            esc_html__("Today"),
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '1',
                'day_end' => '0',
                'type' => 'view',
              )),
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '1',
                'day_end' => '0',
                'type' => 'like',
              )),

            ),
          ),

        );


        ?>
        <div class="hidden-data"><?php echo json_encode($arr); ?></div>


        <?php


        $last_month = date("M", time() - 60 * 60 * 31);
        $month_2 = date("M", time() - 60 * 60 * 24 * 62);
        $month_3 = date("M", time() - 60 * 60 * 24 * 93);


        $trackid = sanitize_key($_POST['postdata']);
        $arr = array(
          'labels' => array(esc_html__('Track'), esc_html__('Minutes watched')),
          'lastdays' => array(
            array(

              $month_3,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '120',
                'day_end' => '90',
                'type' => 'timewatched',
                'get_count' => 'off',
                'id_user' => '0',
              )),
            ),
            array(

              $month_2,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '90',
                'day_end' => '60',
                'type' => 'timewatched',
                'get_count' => 'off',
                'id_user' => '0',
              )),
            ),
            array(

              $last_month,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '60',
                'day_end' => '30',
                'type' => 'timewatched',
                'get_count' => 'off',
                'id_user' => '0',
              )),
            ),

            array(

              "This month",
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '30',
                'day_end' => '0',
                'type' => 'timewatched',
                'get_count' => 'off',
                'id_user' => '0',
              )),
            ),
          ),

        );


        ?>
        <div class="hidden-data-time-watched"><?php echo json_encode($arr); ?></div>
        <?php

        $last_month = date("M", time() - 60 * 60 * 31);
        $month_2 = date("M", time() - 60 * 60 * 24 * 62);
        $month_3 = date("M", time() - 60 * 60 * 24 * 93);


        // -- time watched
        $trackid = sanitize_text_field($_POST['postdata']);
        $arr = array(
          'labels' => array(esc_html__('Track'), esc_html__('Number of plays')),
          'lastdays' => array(
            array(

              $month_3,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '120',
                'day_end' => '90',
                'type' => 'view',
                'get_count' => 'off',
                'id_user' => '0',
              )),
            ),
            array(

              $month_2,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '90',
                'day_end' => '60',
                'type' => 'view',
                'get_count' => 'off',
                'id_user' => '0',
              )),
            ),
            array(

              $last_month,
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '60',
                'day_end' => '30',
                'type' => 'view',
                'get_count' => 'off',
                'id_user' => '0',
              )),
            ),

            array(

              "This month",
              $this->mysql_get_track_activity($trackid, array(
                'get_last' => 'day',
                'day_start' => '30',
                'day_end' => '0',
                'type' => 'view',
                'get_count' => 'off',
                'called_from' => 'debug',
                'id_user' => '0',
              )),
            ),
          ),

        );

        ?>
        <div class="hidden-data-month-viewed"><?php echo json_encode($arr); ?></div>

        <div class="dzs-row">
          <div class="dzs-col-md-8">
            <div class="trackchart">

            </div>
          </div>
          <div class="dzs-col-md-4">
            <div class="dzs-row">

              <div class="dzs-col-md-6">
                <h6><?php echo esc_html__("Likes Today"); ?></h6>
                <div><span class="the-number"><?php


                    $aux = $this->mysql_get_track_activity($trackid, array(
                      'get_last' => 'on',
                      'interval' => '24',
                      'type' => 'like',
                    ));

                    echo $aux;

                    ?></span> <span class="the-label"><?php ?></span></div>
              </div>
              <div class="dzs-col-md-6">
                <h6><?php echo esc_html__("Plays Today"); ?></h6>
                <div><span class="the-number"><?php


                    $aux = $this->mysql_get_track_activity($trackid, array(
                      'get_last' => 'on',
                      'interval' => '24',
                      'type' => 'view',
                    ));

                    echo $aux;

                    ?></span> <span class="the-label"><?php ?></span></div>
              </div>
            </div>

            <div class="dzs-row">
              <div class="dzs-col-md-6">


                <h6><?php echo esc_html__("Likes This Week"); ?></h6>
                <div><span class="the-number"><?php


                    $aux = $this->mysql_get_track_activity($trackid, array(
                      'get_last' => 'on',
                      'interval' => '144',
                      'type' => 'like',
                    ));

                    echo $aux;

                    ?></span> <span class="the-label"><?php ?></span></div>
              </div>

              <div class="dzs-col-md-6">
                <h6><?php echo esc_html__("Plays This Week"); ?></h6>
                <div><span class="the-number"><?php


                    $aux = $this->mysql_get_track_activity($trackid, array(
                      'get_last' => 'on',
                      'interval' => '144',
                      'type' => 'view',
                    ));

                    echo $aux;

                    ?></span> <span class="the-label"><?php ?></span></div>
              </div>
            </div>
            <div class="dzs-row">

              <div class="dzs-col-md-6">
                <h6><?php echo esc_html__("Likes this month"); ?></h6>
                <div><span class="the-number"><?php


                    $aux = $this->mysql_get_track_activity($trackid, array(
                      'get_last' => 'on',
                      'interval' => '720',
                      'type' => 'like',
                    ));

                    echo $aux;

                    ?></span> <span class="the-label"><?php ?></span></div>
              </div>
              <div class="dzs-col-md-6">
                <h6><?php echo esc_html__("Plays this month"); ?></h6>
                <div><span class="the-number"><?php


                    $aux = $this->mysql_get_track_activity($trackid, array(
                      'get_last' => 'on',
                      'interval' => '720',
                      'type' => 'view',
                    ));

                    echo $aux;

                    ?></span> <span class="the-label"><?php ?></span></div>
              </div>
            </div>

          </div>
        </div>
        <div class="dzs-row">

          <div class="dzs-col-md-6">
            <div class="trackchart-time-watched">

            </div>
          </div>

          <div class="dzs-col-md-6">
            <div class="trackchart-month-viewed">

            </div>
          </div>
        </div>
        <?php

        die();

      }

    }


    if (isset($_POST['dzsvg_action'])) {


      if ($_POST['dzsvg_action'] == 'submit_video') {


        $isAllowed = false;

        $isAllowed = $this->can_submit_video();


        if ($isAllowed) {
          global $current_user;

          $args = array(
            'post_title' => $_POST['title'],
            'post_content' => $_POST['description'],
            'post_status' => 'publish',
            'post_author' => $current_user->data->ID,
            'post_type' => 'dzsvideo',
          );


          if ($_POST['thumbnail']) {

          } else {
            if ($dzsvg->mainoptions['dzsvp_use_default_image'] == 'on') {
              $_POST['thumbnail'] = $dzsvg->mainoptions['dzsvp_upload_image_default'];
            }
          }

          $sample_post_id = wp_insert_post($args);
          update_post_meta($sample_post_id, 'dzsvg_meta_featured_media', $_POST['source']);
          update_post_meta($sample_post_id, 'dzsvg_meta_item_type', $_POST['type']);
          update_post_meta($sample_post_id, 'dzsvg_meta_thumb', $_POST['thumbnail']);


          $aux = array(
            'type' => 'upload',
            'text' => sprintf(__('video uploaded %shere%s'), '<a href="' . get_permalink($sample_post_id) . '">', '</a>'),
          );

          array_push($dzsvg->notifications, $aux);


          if (isset($_POST['video_category']) && $_POST['video_category']) {

            wp_set_object_terms($sample_post_id, array(intval($_POST['video_category'])), DZSVG_POST_NAME__CATEGORY);
          }
          if (isset($_POST['tags']) && $_POST['tags']) {

            $arr_tags = explode(',', $_POST['tags']);
            wp_set_object_terms($sample_post_id, $arr_tags, 'dzsvideo_tags');
          }


          $_POST = array();
          header("Location: " . get_permalink($sample_post_id));
        } else {
          die("Not allowed - " . sprintf(esc_html__("Make sure %s", 'dzsvg'), 'Allow visitor submit is allowed'));
        }


      }
    }

  }

  static function mysql_get_views($id, $pargs = array()) {


    $margs = array();

    $margs = array_merge($margs, $pargs);


    global $wpdb;


    $table_name = $wpdb->prefix . DZSVG_DB_TABLE_NAME_ACTIVITY;

    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name  WHERE id_video='$id'");


    if ($count) {

    } else {
      $count = '0';
    }

    return $count;


  }

  function mysql_get_track_activity($track_id, $pargs = array()) {


    // -- get last ON for interval training

    $margs = array(
      'get_last' => 'off',
      'called_from' => 'default',
      'interval' => '24',
      'type' => 'view',
      'table' => 'detect',
      'day_start' => '3',
      'day_end' => '2',
      'get_count' => 'off',
    );

    if ($pargs) {
      $margs = array_merge($margs, $pargs);
    }


    global $wpdb;
    $table_name = $wpdb->prefix . DZSVG_DB_TABLE_NAME_ACTIVITY;


    $format_track_id = 'id_video';


    $margs['table'] = $table_name;

    $query = "SELECT ";


    if ($margs['get_count'] == 'on') {

      $query .= 'COUNT(*)';
    } else {

      $query .= '*';
    }

    $query .= " FROM `" . $margs['table'] . "` WHERE `" . $format_track_id . "` = '" . $track_id;


    if (strpos($margs['type'], '%') !== false) {

      $query .= "' AND type LIKE '" . $margs['type'] . "'";
    } else {

      $query .= "' AND type='" . $margs['type'] . "'";
    }


    if ($margs['get_last'] == 'on') {
      $query .= ' AND date > DATE_SUB(NOW(), INTERVAL ' . $margs['interval'] . ' HOUR)';
    }

    if ($margs['get_last'] == 'day') {
      $query .= ' AND date BETWEEN DATE_SUB(NOW(), INTERVAL ' . $margs['day_start'] . ' DAY)
    AND DATE_SUB(NOW(), INTERVAL  ' . $margs['day_end'] . ' DAY)';

    }

    // -- interval start / end




    if (isset($margs['id_user'])) {
      $query .= ' AND id_user=\'' . $margs['id_user'] . '\'';
    }


    $results = $GLOBALS['wpdb']->get_results($query, OBJECT);


    $finalval = 0;
    if (is_array($results) && count($results) > 0) {


      if ($margs['get_count'] == 'on') {


        if (isset($results[0])) {
          $results[0] = (array)$results[0];

          return $results[0]['COUNT(*)'];

        }
      } else {

        if ($margs['called_from'] == 'debug') {

        }
        foreach ($results as $lab => $aux2) {
          $results[$lab] = (array)$results[$lab];

          $finalval += $results[$lab]['val'];
        }
      }


    }


    return $finalval;


  }


  function can_submit_video() {

    $dzsvg = $this->dzsvg;

    global $current_user;


    $isAllowed = false;

    if (isset($_GET['from']) && $_GET['from'] == 'dzsvp_portal') {

      if ($dzsvg->mainoptions['dzsvp_enable_visitorupload'] == 'on' || $dzsvg->mainoptions['dzsvp_enable_user_upload_capability'] == 'on') {

        if ($current_user->data->ID) {
          $isAllowed = true;
        }

      }
    }

    if (current_user_can('upload_files')) {
    } else {
      $isAllowed = false;
    }

    if (current_user_can('video_gallery_portal_submit_videos') || current_user_can('manage_options')) {
      $isAllowed = true;
    }

    return $isAllowed;

  }


  function ajax_submit_like() {
    global $current_user;


    $user_id = -1;
    if ($current_user->ID) {
      $user_id = $current_user->ID;
    }


    $aux_likes = 0;
    $playerid = '';

    if (isset($_POST['playerid'])) {
      $playerid = sanitize_key($_POST['playerid']);
      $playerid = str_replace('ap', '', $playerid);
    }

    if (get_post_meta($playerid, '_dzsvp_likes', true) != '') {
      $aux_likes = intval(get_post_meta($playerid, '_dzsvp_likes', true));
    }

    $aux_likes = $aux_likes + 1;

    update_post_meta($playerid, '_dzsvp_likes', $aux_likes);

    setcookie("dzsvp_likesubmitted-" . $playerid, '1', time() + 36000, COOKIEPATH);


    if ($user_id > 0) {
      $aux_likes_arr = array();
      $aux_likes_arr_test = get_user_meta($user_id, '_dzsvp_likes');
      if (is_array($aux_likes_arr_test)) {
        $aux_likes_arr = $aux_likes_arr_test;
      };

      if (!in_array($playerid, $aux_likes_arr)) {
        array_push($aux_likes_arr, $playerid);

        update_user_meta($user_id, '_dzsvp_likes', $aux_likes_arr);
      }
    };


    echo 'success';
    die();
  }

  function ajax_retract_like() {


    $aux_likes = 1;
    $playerid = '';

    if (isset($_POST['playerid'])) {
      $playerid = sanitize_key($_POST['playerid']);
      $playerid = str_replace('ap', '', $playerid);


	    if (get_post_meta($playerid, '_dzsvp_likes', true) != '') {

		    $playerid = sanitize_key($_POST['playerid']);
		    $aux_likes = intval(get_post_meta($_POST['playerid'], '_dzsvp_likes', true));
	    }
    }



    $aux_likes = $aux_likes - 1;

    update_post_meta($playerid, '_dzsvp_likes', $aux_likes);

    setcookie("dzsvp_likesubmitted-" . $playerid, '', time() - 36000, COOKIEPATH);

    echo 'success';
    die();
  }


}
