<?php

class VideoGalleryAjaxFunctions {
  public static function ajax_import_simple_playlist() {

    $sliderName = sanitize_key($_POST['name']);


    $rel_path = DZSVG_PATH . 'sampledata/sample-slider--' . $sliderName . '.txt';
    $file_cont = file_get_contents($rel_path, true);

    $sw_import = VideoGalleryAjaxFunctions::import_slider($file_cont);

    echo json_encode($sw_import);
    die();
  }

  public static function ajax_legacy_importOrExportDatabase() {
    global $dzsvg;
    //// POST OPTIONS ///

    if (isset($_POST['dzsvg_exportdb'])) {


      // -- setting up the db
      $currDb = '';
      if (isset($_POST['currdb']) && $_POST['currdb'] != '') {
        $dzsvg->currDb = sanitize_key($_POST['currdb']);
        $currDb = $dzsvg->currDb;
      }

      if ($currDb != 'main' && $currDb != '') {
        $dzsvg->dbkey_legacyItems .= '-' . $currDb;
        $dzsvg->mainitems = get_option($dzsvg->dbkey_legacyItems);
      }
      // -- setting up the db END

      header('Content-Type: text/plain');
      header('Content-Disposition: attachment; filename="' . "dzsvg_backup.txt" . '"');
      echo serialize($dzsvg->mainitems);
      die();
    }
    if (isset($_POST['dzsvg_dismiss_limit_notice']) && $_POST['dzsvg_dismiss_limit_notice'] == 'dismiss') {
      $dzsvg->mainoptions['settings_limit_notice_dismissed'] = 'on';


      update_option($dzsvg->dboptionsname, $dzsvg->mainoptions);
    }

    if (isset($_POST['dzsvg_exportslider'])) {


      // -- setting up the db
      $currDb = '';
      if (isset($_POST['currdb']) && $_POST['currdb'] != '') {
        $dzsvg->currDb = sanitize_key($_POST['currdb']);
        $currDb = $dzsvg->currDb;
      }

      if ($currDb != 'main' && $currDb != '') {
        $dzsvg->dbkey_legacyItems .= '-' . $currDb;
        $dzsvg->mainitems = get_option($dzsvg->dbkey_legacyItems);
      }
      // -- setting up the db END

	    $sliderName = sanitize_key($_POST['slidername']);
      header('Content-Type: text/plain');
      header('Content-Disposition: attachment; filename="' . "dzsvg-slider-" . $sliderName . ".txt" . '"');
      echo serialize($dzsvg->mainitems[$_POST['slidernr']]);
      die();
    }
    if (isset($_POST['dzsvg_exportslider_config'])) {


      // -- setting up the db
      $currDb = '';


      // -- setting up the db END

	    $sliderName = sanitize_key($_POST['slidername']);
      header('Content-Type: text/plain');
      header('Content-Disposition: attachment; filename="' . "dzsvg-slider-" . $sliderName . ".txt" . '"');

      error_log("EXPORTING SLIDER CONFIG ( currdb - " . $currDb . " )" . print_rr($dzsvg->mainvpconfigs, array('echo' => false)));
      echo serialize($dzsvg->mainvpconfigs[$_POST['slidernr']]);
      die();
    }


    if (isset($_POST['dzsvg_importdb'])) {

      if (function_exists('wp_verify_nonce') && (!wp_verify_nonce($_REQUEST['dzsvg_importdb_nonce'], 'dzsvg_importdb_nonce'))) {
        die('Security check');
      }

      $file_data = file_get_contents($_FILES['dzsvg_importdbupload']['tmp_name']);
      $dzsvg->mainitems = unserialize($file_data);
      update_option($dzsvg->dbkey_legacyItems, $dzsvg->mainitems);
    }

    if (isset($_POST['dzsvg_importslider'])) {


      // -- import
      if (function_exists('wp_verify_nonce') && (!wp_verify_nonce($_REQUEST['dzsvg_importslider_nonce'], 'dzsvg_importslider_nonce'))) {
        die('Security check');

      }


      $file_data = file_get_contents($_FILES['importsliderupload']['tmp_name']);
      $auxslider = unserialize($file_data);

      $dzsvg->mainitems = get_option($dzsvg->dbkey_legacyItems);

      $countSliders = $dzsvg->mainitems && count($dzsvg->mainitems) ? count($dzsvg->mainitems) : 0;

      $dzsvg->mainitems[$countSliders] = $auxslider;

      update_option($dzsvg->dbkey_legacyItems, $dzsvg->mainitems);
    }


    if (isset($_POST['dzsvg_saveoptions'])) {
      if ($_POST['use_external_uploaddir'] == 'on') {
        copy(dirname(__FILE__) . '/admin/upload.php', dirname(dirname(dirname(__FILE__))) . '/upload.php');
        $mypath = dirname(dirname(dirname(__FILE__))) . '/upload';
        if (is_dir($mypath) === false && file_exists($mypath) === false) {
          mkdir($mypath, 0755);
        }
      }


      update_option($dzsvg->dboptionsname, $dzsvg->mainoptions);
    }
  }

  public static function import_slider($file_cont) {

    global $dzsvg;

    $tax = $dzsvg->taxname_sliders;
    $response_arr = array(
      'status' => 'success',
      'slider_name' => '',
      'slider_slug' => '',
    );

    try {

      $arr = json_decode($file_cont, true);

      $file_cont = str_replace('\\\\"', '\\"', $file_cont);


      if ($arr && is_array($arr)) {

        $type = 'json';
      } else {

        try {

          $arr = unserialize($file_cont);


          error_log('content serial - ' . print_rr($arr, true) . ' - ' . print_rr($file_cont, true));
          $type = 'serial';
        } catch (Exception $e) {

          error_log('failed parsing' . print_rr($file_cont, true));
        }
      }

      if (is_array($arr)) {
        if ($type == 'json') {


          $reference_term_name = $arr['original_term_name'];
          $reference_term_slug = $arr['original_term_slug'];

          $original_name = $reference_term_name;
          $original_slug = $reference_term_slug;


          $new_term_slug = $reference_term_slug;
          $new_term_name = $reference_term_name;


          $ind = 1;
          $breaker = 100;


          $term = term_exists($new_term_name, $tax);
          if ($term !== 0 && $term !== null) {


            $new_term_name = $original_name . '-' . $ind;
            $new_term_slug = $original_slug . '-' . $ind;
            $ind++;


            while (1) {

              $term = term_exists($new_term_name, $tax);
              if ($term !== 0 && $term !== null) {

                $new_term_name = $original_name . '-' . $ind;
                $new_term_slug = $original_slug . '-' . $ind;
              } else {

                error_log("SEEMS THAT TERM DOES NOT EXIST " . $new_term_name . ' ' . $new_term_slug);
                break;
              }
              $ind++;

              $breaker--;

              if ($breaker < 0) {
                break;
              }
            }

          } else {

            error_log("SEEMS THAT TERM DOES NOT EXIST " . $new_term_name . ' ' . $new_term_slug);


          }


          $new_term = wp_insert_term(
            $new_term_name, // the term
            $tax, // the taxonomy
            array(

              'slug' => $new_term_slug,
            )
          );


          $new_term_id = '';


          $response_arr['slider_name'] = $new_term_name;
          $response_arr['slider_slug'] = $new_term_slug;


          if (is_array($new_term)) {

            $new_term_id = $new_term['term_id'];
          } else {
            error_log(' .. ERROR the name is ' . $new_term_name);
            error_log(' .. $tax is ' . $tax);
            error_log(print_r($new_term, true));
          }


          $term_meta = array_merge(array(), $arr['term_meta']);

          unset($term_meta['items']);

          update_option("taxonomy_$new_term_id", $term_meta);


          foreach ($arr['items'] as $po) {

            $args = array_merge(array(), $po);

            $args['term'] = $new_term;
            $args['taxonomy'] = $tax;


            // -- we do not need this

            unset($args['post_name']);


            error_log('args import item - ' . print_r($args, true));
            $dzsvg->classAjax->import_demo_insert_post_complete($args);


          }


        }


        // -- legacy
        if ($type == 'serial') {


          $new_term_id = '';
          $new_term = null;
          $original_slug = '';
          $new_term_slug = '';


          foreach ($arr as $lab => $val) {


            if ($lab === 'settings') {


              // -- settings


              $reference_term_name = $val['id'];
              $reference_term_slug = $val['id'];

              $original_name = $reference_term_name;
              $original_slug = $reference_term_slug;


              $new_term_slug = $reference_term_slug;
              $new_term_name = $reference_term_name;


              $ind = 1;
              $breaker = 100;


              $term = term_exists($new_term_name, $tax);
              if ($term !== 0 && $term !== null) {


                $new_term_name = $original_name . '-' . $ind;
                $new_term_slug = $original_slug . '-' . $ind;
                $ind++;


                while (1) {

                  $term = term_exists($new_term_name, $tax);
                  if ($term !== 0 && $term !== null) {

                    $new_term_name = $original_name . '-' . $ind;
                    $new_term_slug = $original_slug . '-' . $ind;
                  } else {

                    break;
                  }
                  $ind++;

                  $breaker--;

                  if ($breaker < 0) {
                    break;
                  }
                }

              } else {



              }


              $new_term = wp_insert_term(
                $new_term_name, // the term
                $tax, // the taxonomy
                array(

                  'slug' => $new_term_slug,
                )
              );


              if (is_array($new_term)) {

                $new_term_id = $new_term['term_id'];
              } else {
                error_log(' .. the name is ' . $new_term_name);
                error_log(print_r($new_term, true));
              }


              $term_meta = array_merge(array(), $val);

              if ($val['feedfrom'] == 'vmuserchannel' || $val['feedfrom'] == 'vmchannel' || $val['feedfrom'] == 'vmalbum') {
                $term_meta['feed_mode'] = 'vimeo';

                if ($val['feedfrom'] == 'vmuserchannel') {

                  $term_meta['vimeo_source'] = 'https://vimeo.com/' . $val['vimeofeed_user'];
                }
                if ($val['feedfrom'] == 'vmchannel') {

                  $term_meta['vimeo_source'] = 'https://vimeo.com/channels/' . $val['vimeofeed_channel'];
                }
                if ($val['feedfrom'] == 'vmalbum') {

                  $term_meta['vimeo_source'] = 'https://vimeo.com/album/' . $val['vimeofeed_vmalbum'];
                }

              }

              if ($val['feedfrom'] == 'ytkeywords' || $val['feedfrom'] == 'ytplaylist' || $val['feedfrom'] == 'ytuserchannel') {
                $term_meta['feed_mode'] = 'youtube';

                if ($val['feedfrom'] == 'ytkeywords') {

                  $term_meta['youtube_source'] = 'https://youtube.com/results/?search_query=' . $val['ytkeywords_source'];
                }
                if ($val['feedfrom'] == 'ytplaylist') {

                  $term_meta['youtube_source'] = 'https://youtube.com/?list=' . $val['ytplaylist_source'];
                }
                if ($val['feedfrom'] == 'ytuserchannel') {

                  $term_meta['youtube_source'] = 'https://youtube.com/c/' . $val['youtubefeed_user'];
                }

              }

              unset($term_meta['items']);

              update_option("taxonomy_$new_term_id", $term_meta);
            } else {

              $args = array_merge(array(), $val);

              $args['term'] = $new_term;
              $args['taxonomy'] = $tax;
              $args['post_name'] = $new_term_slug . '-' . $lab;
              $args['post_title'] = $original_slug . '-' . $lab;


              $response_arr['slider_name'] = $new_term_slug;
              $response_arr['slider_slug'] = $new_term_slug;

              if (isset($args['title'])) {
                $args['post_title'] = $args['title'];
              }

              foreach ($dzsvg->options_item_meta as $oim) {
                $long_name = $oim['name'];

                $short_name = str_replace('dzsvg_meta_', '', $oim['name']);


                if (isset($args[$short_name])) {

                  $args[$long_name] = $args[$short_name];
                }


              }
              if (isset($args['type'])) {
                $args['dzsvg_meta_item_type'] = $args['type'];

              }
              if (isset($args['source'])) {
                $args['dzsvg_meta_featured_media'] = $args['source'];

              }
              if (isset($args['thethumb'])) {
                $args['dzsvg_meta_thumb'] = $args['thethumb'];

              }
              if (isset($args['description'])) {
                $args['dzsvg_meta_description'] = $args['description'];
                $args['dzsvg_meta_menuDescription'] = $args['description'];
              }
              if (isset($args['menudescription'])) {
                $args['dzsvg_meta_menu_description'] = $args['menudescription'];
              }
              if (isset($args['menuDescription'])) {
                $args['dzsvg_meta_menu_description'] = $args['menuDescription'];
              }


              $args['dzsvg_meta_order_' . $new_term_id] = $lab;


              error_log('args import item - ' . print_r($args, true));

              $dzsvg->classAjax->import_demo_insert_post_complete($args);

            }


          }
        }
      }
    } catch (Exception $err) {
      print_rr($err);
    }

    return $response_arr;

  }


  public static function create_playlist_if_it_does_not_exist() {

    global $dzsvg;


    if (isset($_POST['term_name'])) {


      $new_term_name = sanitize_text_field($_POST['term_name']);
      $new_term_slug = $new_term_name;
      $tax = $dzsvg->taxname_sliders;


      $term = term_exists($new_term_name, $tax);


      if (!(0 !== $term && null !== $term)) {

        // -- import slider
        $new_term = wp_insert_term(
          $new_term_name, // the term
          $tax, // the taxonomy
          array(

            'slug' => $new_term_slug,
          )
        );


        $new_term_id = '';
        if (is_array($new_term)) {

          $new_term_id = $new_term['term_id'];
        } else {
          error_log(' .. ERROR the name is ' . $new_term_name);
          error_log(' .. $tax is ' . $tax);
          error_log(print_r($new_term, true));
        }

        echo '' . $new_term_id;
      } else {

        echo '' . '' . $term['term_id'];
        error_log('.. create_playlist_if_it_does_not_exist term exists' . $new_term_name);


        error_log('.. term exists' . print_r($term, true));
      }


    }
    die();
  }

  public static function shoutcast_get_now_playing($arg) {


    $final_metadata = array();
    $source = $arg;
    $url_vars = parse_url($source);
    $host = $url_vars['host'];
    $path = isset($url_vars['path']) ? $url_vars['path'] : '/';


    $url = $source;
    $ch = curl_init($url);

    $headers = array(
      'GET ' . $path . ' HTTP/1.0',
      'Host: ' . $url_vars['host'] . '',
      'Connection: Close',
      'User-Agent: Winamp',
      'Accept: */*',
      'icy-metadata: 1',
      'icy-prebuffer: 2314',
    );

    $construct_url = $url_vars['scheme'] . '://' . $url_vars['host'] . $path;


    $err_no = '';
    $err_str = '';

    $fp = @fsockopen($url_vars['host'], $url_vars['port'], $err_no, $err_str, 10);



    if ($fp) {


      $headers_str = '';

      foreach ($headers as $key => $val) {
        $headers_str .= $val . '\r\n';
      }



      define('CRLF', "\r\n");


      $headers_str = 'GET ' . $path . ' HTTP/1.0' . CRLF .
        'Host: ' . $url_vars['host'] . CRLF .
        'Connection: Close' . CRLF .
        'User-Agent: Winamp 2.51' . CRLF .
        'Accept: */*' . CRLF .
        'icy-metadata: 1' . CRLF .
        'icy-prebuffer: 65536' . CRLF . CRLF;


      fwrite($fp, $headers_str);

      stream_set_timeout($fp, 2, 0);
      $response = "";

      while (!feof($fp)) {


        $line = fgets($fp, 4096);
        if ('' == trim($line)) {
          break;
        }
        $response .= $line;
      }

      preg_match_all('/(.*?):(.*)[^|$]/', $response, $fout_arr);

      if (isset($fout_arr[1])) {

        $final_arr = array();
        foreach ($fout_arr[1] as $key => $val) {
          $final_arr[$val] = $fout_arr[2][$key];
        }


        // -- snippet from https://stackoverflow.com/questions/15803441/php-script-to-extract-artist-title-from-shoutcast-icecast-stream
        if (!isset($final_arr['icy-metaint'])) {
          $data = '';
          $metainterval = 512;
          while (!feof($fp)) {
            $data .= fgetc($fp);
            if (strlen($data) >= $metainterval) break;
          }

          $matches = array();
          preg_match_all('/([\x00-\xff]{2})\x0\x0([a-z]+)=/i', $data, $matches, PREG_OFFSET_CAPTURE);
          preg_match_all('/([a-z]+)=([a-z0-9\(\)\[\]., ]+)/i', $data, $matches, PREG_SPLIT_NO_EMPTY);




          $title = $artist = '';
          foreach ($matches[0] as $nr => $values) {
            $offset = $values[1];
            $length = ord($values[0][0]) +
              (ord($values[0][0]) * 256) +
              (ord($values[0][0]) * 256 * 256) +
              (ord($values[0][0]) * 256 * 256 * 256);
            $info = substr($data, $offset + 4, $length);
            $seperator = strpos($info, '=');
            $final_metadata[substr($info, 0, $seperator)] = substr($info, $seperator + 1);
            if (substr($info, 0, $seperator) == 'title') $title = substr($info, $seperator + 1);
            if (substr($info, 0, $seperator) == 'artist') $artist = substr($info, $seperator + 1);
          }
          $final_metadata['streamtitle'] = $artist . ' - ' . $title;
        } else {
          $metainterval = $final_arr['icy-metaint'];
          $intervals = 0;
          $metadata = '';
          while (1) {
            $data = '';
            while (!feof($fp)) {
              $data .= fgetc($fp);
              if (strlen($data) >= $metainterval) break;
            }

            $len = join(unpack('c', fgetc($fp))) * 16;
            if ($len > 0) {
              $metadata = str_replace("\0", '', fread($fp, $len));
              break;
            } else {
              $intervals++;
              if ($intervals > 100) break;
            }
          }
          $metarr = explode(';', $metadata);
          foreach ($metarr as $meta) {
            $t = explode('=', $meta);
            if (isset($t[0]) && trim($t[0]) != '') {
              $name = preg_replace('/[^a-z][^a-z0-9]*/i', '', strtolower(trim($t[0])));
              array_shift($t);
              $value = trim(implode('=', $t));
              if (substr($value, 0, 1) == '"' || substr($value, 0, 1) == "'") {
                $value = substr($value, 1);
              }
              if (substr($value, -1) == '"' || substr($value, -1) == "'") {
                $value = substr($value, 0, -1);
              }
              if ($value != '') {
                $final_metadata[$name] = $value;
              }
            }
          }
        }


      }

      fclose($fp);
    }


    if (isset($final_metadata) && isset($final_metadata['streamtitle'])) {
      return $final_metadata['streamtitle'];


    } else {
      return 'Song name not found';
    }


  }

  public static function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
      $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
      if (!is_dir($path)) {
        $results[] = $path;
      }
    }

    return $results;
  }

  public static function import_folder() {

    $ajaxResponse = array(
      'ajax_status' => 'success',
      'ajax_message' => esc_html__("folder imported"),
    );


    $ajaxResponse['type'] = 'success';

    $dir = sanitize_key($_POST['payload']);

    VideoGalleryAjaxFunctions::getDirContents($dir, $files);

    $valid_extensions = array('mp4', 'm4v');

    $results = array();
    foreach ($files as $lab => $val) {

      $sw_continue = false;

      foreach ($valid_extensions as $vlid) {

        if (strpos(strtolower($val), $vlid) !== false) {
          $sw_continue = true;
        }
      }


      if ($sw_continue === false) {
        continue;
      }

      $final_url = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['HTTP_ORIGIN'], $val);
      $arr = explode(DIRECTORY_SEPARATOR, $final_url);
      $name = $arr[count($arr) - 1];


      foreach ($valid_extensions as $vlid) {
        $name = str_replace('.' . $vlid, '', $name);
      }

      $aux = array(
        'name' => $name,
        'url' => $final_url,
        'path' => $val,
      );
      array_push($results, $aux);
    }

    if (is_array($results) && count($results) === 0) {
      $ajaxResponse['ajax_status'] = 'error';
      $ajaxResponse['ajax_message'] = esc_html__("seems like directory is empty or is incorrect");
    }

    $ajaxResponse['files'] = $results;

    echo json_encode($ajaxResponse);

    die();
  }

  static function import_demo_create_term_if_it_does_not_exist($pargs = array()) {


    $margs = array(

      'term_name' => '',
      'slug' => '',
      'taxonomy' => '',
      'description' => '',
      'parent' => '',
    );

    $margs = array_merge($margs, $pargs);

    $term = get_term_by('slug', $margs['slug'], $margs['taxonomy']);


    if ($term) {

    } else {


      $args = array(
        'description' => $margs['description'],
        'slug' => $margs['slug'],


      );

      if ($margs['parent']) {
        $args['parent'] = $margs['parent'];
      }

      $term = wp_insert_term($margs['term_name'], $margs['taxonomy'], $args);

    }
    return $term;

  }


  public static function ajax_import_item_lib() {

		include_once DZSVG_PATH.'inc/php/import-func/import-func.php';

	  dzsvg_ajax_importFunc();
  }


}


function dzsvg_ajax_send_queue_from_sliders_admin() {

  global $dzsvg;

  $response = array(
    'report' => 'success',
    'items' => array(),
  );

  $queue_calls = json_decode(stripslashes($_POST['postdata']), true);

  foreach ($queue_calls as $qc) {

    if ($qc['type'] == 'set_meta_order') {
      foreach ($qc['items'] as $it) {

        update_post_meta($it['id'], 'dzsvg_meta_order_' . $qc['term_id'], $it['order']);
      }
    }
    if ($qc['type'] == 'set_meta') {
      if ($qc['lab'] == 'the_post_title' || $qc['lab'] == 'the_post_content') {

        $aferent_lab = $qc['lab'];

        if ($qc['lab']) {
          $aferent_lab = str_replace('the_', '', $aferent_lab);
        }

        $my_post = array(
          'ID' => $qc['item_id'],
          $aferent_lab => $qc['val'],

        );

// Update the post into the database
        wp_update_post($my_post);
      } else {

        update_post_meta($qc['item_id'], $qc['lab'], $qc['val']);
      }

    }
    if ($qc['type'] == 'delete_item') {


      $post_id = $qc['id'];


      $term_list = wp_get_post_terms($post_id, DZSVG_POST_NAME__SLIDERS, array("fields" => "all"));


      $response['report_type'] = 'delete_item';
      $response['report_message'] = esc_html__("Item deleted", 'dzsvg');


      if (is_array($term_list) && count($term_list) == 1) {
        if (!wp_delete_post($post_id)) {
          $response['report'] = 'error';
        }
      } else {
        wp_remove_object_terms($post_id, $qc['term_slug'], DZSVG_POST_NAME__SLIDERS);
      }
    }
    if ($qc['type'] == 'create_item') {


      // -- here we create the item

      $taxonomy = 'dzsvg_sliders';


      $current_user = wp_get_current_user();
      $new_post_author_id = $current_user->ID;


      $args = array(
        'post_title' => esc_html__("Insert Name", 'dzsvg'),
        'post_content' => 'content here',
        'post_status' => 'publish',
        'post_author' => $new_post_author_id,
        'post_type' => 'dzsvideo',
      );
      if (isset($qc['post_title']) && $qc['post_title']) {
        $args['post_title'] = $qc['post_title'];
      }

      $sample_post_2_id = wp_insert_post($args);


      if (isset($qc['term_slug']) && $qc['term_slug']) {

        wp_set_post_terms($sample_post_2_id, dzs_sanitize_for_post_terms($qc['term_slug']), $taxonomy);
      }


      foreach ($qc as $lab => $val) {
        if (strpos($lab, 'dzsvg_meta') === 0) {
          update_post_meta($sample_post_2_id, $lab, $val);
        }
      }


      // -- generate create_item here

      array_push($response['items'], array(
        'type' => 'create_item',
        'str' => SlidersAdminHelpers::sliders_admin_generate_item(get_post($sample_post_2_id)),
      ));
    }


    if ($qc['type'] == 'duplicate_item') {



      $reference_po_id = ($qc['id']);

      $sample_post_2_id = dzsvg_duplicate_post($reference_po_id);


      // -- generate create_item here for duplicate
      array_push($response['items'], array(
        'type' => 'create_item',
        'original_request' => 'duplicate_item',
        'original_post_id' => $reference_po_id,
        'str' => SlidersAdminHelpers::sliders_admin_generate_item(get_post($sample_post_2_id)),
      ));
    }
  }

  echo json_encode($response);
  die();
}

/**
 * @param int $reference_po_id
 * @param array $pargs
 * @return int|WP_Error
 */
function dzsvg_duplicate_post($reference_po_id, $pargs = array()) {


  $margs = array(
    'new_term_slug' => '',
    'called_from' => 'default',
    'new_tax' => 'dzsvg_sliders',
  );

  $margs = array_merge($margs, $pargs);

  $reference_po = get_post($reference_po_id);


  $current_user = wp_get_current_user();
  $new_post_author_id = $current_user->ID;

  $args = array(
    'post_title' => $reference_po->post_title,
    'post_content' => $reference_po->post_content,
    'post_status' => 'publish',
    'post_author' => $new_post_author_id,
    'post_type' => $reference_po->post_type,
  );


  $sample_post_2_id = wp_insert_post($args);


  /*
   * get all current post terms ad set them to the new post draft
   */
  $taxonomies = get_object_taxonomies($reference_po->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
  foreach ($taxonomies as $taxonomy) {
    if ($margs['new_term_slug']) {
      if ($taxonomy == DZSVG_POST_NAME__SLIDERS) {
        continue;
      }
    }
    $post_terms = wp_get_object_terms($reference_po_id, $taxonomy, array('fields' => 'slugs'));
    wp_set_object_terms($sample_post_2_id, $post_terms, $taxonomy, false);
  }


  // -- for duplicate term
  if ($margs['new_term_slug']) {

    wp_set_object_terms($sample_post_2_id, $margs['new_term_slug'], $margs['new_tax'], false);
  } else {

  }


  /*
   * duplicate all post meta just in two SQL queries
   */
  global $wpdb;
  $sql_query_sel = array();
  $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$reference_po_id");
  if (count($post_meta_infos) != 0) {
    $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
    foreach ($post_meta_infos as $meta_info) {
      $meta_key = $meta_info->meta_key;
      if ($meta_key == '_wp_old_slug') continue;
      $meta_value = addslashes($meta_info->meta_value);
      $sql_query_sel[] = "SELECT $sample_post_2_id, '$meta_key', '$meta_value'";
    }
    $sql_query .= implode(" UNION ALL ", $sql_query_sel);
    $wpdb->query($sql_query);
  }

  return $sample_post_2_id;
}
