<?php


/**
 * @param $dzsvg
 * @param $targetPlaylist
 * @return mixed
 */
function dzsvg_legacy_getItsForPlaylist($dzsvg, $targetPlaylist){
  $its = array();




  if (isset($targetPlaylist)) {
    $id = $targetPlaylist;
  }



  if (strpos($id, ',') !== false) {
    $auxa = explode(",", $id);
    $id = $auxa[0];

    unset($auxa[0]);
    $extra_galleries = $auxa;
  }

  //echo 'ceva' . $id;
  for ($i = 0; $i < count($dzsvg->mainitems); $i++) {
    if ((isset($id)) && isset($dzsvg->mainitems[$i]['settings']) && ($id == $dzsvg->mainitems[$i]['settings']['id'])) {
      $k = $i;
    }
  }

  $its = $dzsvg->mainitems[$k];


  if (isset($dzsvg->mainitems['settings'])) {

    $its = $dzsvg->mainitems;
  } else {

    // todo: verify
    for ($i = 0; $i < count($dzsvg->mainitems); $i++) {
      if ((isset($id)) && ($id == $dzsvg->mainitems[$i]['settings']['id'])) {
        $k = $i;
      }
    }
    $its = $dzsvg->mainitems[$k];
  }




  // -----
  // -- ---- ---- YouTube user channel feed ---
  // -----
  if (($its['settings']['feedfrom'] == 'ytuserchannel') && $its['settings']['youtubefeed_user'] != '') {


    include_once DZSVG_PATH . "inc/php/parse_yt_vimeo.php";


    $len = count($its) - 1;
    for ($i = 0; $i < $len; $i++) {
      unset($its[$i]);
    }

    $args = array(
      'type' => 'user_channel',
      'subtype' => 'user_channel',
      'max_videos' => $its['settings']['youtubefeed_maxvideos'],
      'enable_outernav_video_author' => $its['settings']['enable_outernav_video_author'],
    );

    if (isset($its['settings']['enable_outernav_video_date'])) {
      $args['enable_outernav_video_date'] = $its['settings']['enable_outernav_video_date'];
    }


    // -- max len description
    if (intval($its['settings']['maxlen_desc']) && intval($its['settings']['maxlen_desc']) > 150) {

      $args['get_full_description'] = 'on';
    }

    $its2 = dzsvg_parse_yt($its['settings']['youtubefeed_user'], $args, $fout);

    $its = array_merge($its, $its2);

  }
  // -- END YT USER CHANNEL


  // -- START youtube playlist -----------------------
  if (($its['settings']['feedfrom'] == 'ytplaylist') && $its['settings']['ytplaylist_source'] != '') {


    $len = count($its) - 1;
    for ($i = 0; $i < $len; $i++) {
      unset($its[$i]);
    }


    $targetfeed = $its['settings']['ytplaylist_source'];
    $targetfeed = str_replace('https://www.youtube.com/playlist?list=', '', $targetfeed);


    $cacher = get_option('dzsvg_cache_ytplaylist');

    $cached = false;
    $found_for_cache = false;


    if ($cacher == false || is_array($cacher) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
      $cached = false;
    } else {


      if ($dzsvg->mainoptions['debug_mode'] == 'on') {
        if (isset($_GET['show_cacher']) && $_GET['show_cacher'] == 'on') {
          print_r($cacher);
        };
      }


      $ik = -1;
      $i = 0;
      for ($i = 0; $i < count($cacher); $i++) {
        if ($cacher[$i]['id'] == $targetfeed) {
          if (isset($cacher[$i]['maxlen']) && $cacher[$i]['maxlen'] == $its['settings']['youtubefeed_maxvideos']) {
            if ($_SERVER['REQUEST_TIME'] - $cacher[$i]['time'] < 3600) {
              $ik = $i;

              $cached = true;
              break;
            }
          }

        }
      }


      if ($cached) {

        foreach ($cacher[$ik]['items'] as $lab => $item) {
          if ($lab === 'settings') {
            continue;
          }

          $its[$lab] = $item;

        }

      }
    }


    if ($dzsvg->mainoptions['debug_mode'] == 'on') {


      $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('youtube playlist statistics', 'dzsvg') . '</div>
<div class="toggle-content">';
      $fout .= 'memory usage - ' . memory_get_usage() . "\n <br>memory limit - " . ini_get('memory_limit');
      $fout .= '<br>';
      $fout .= 'feed - to be determied';
      $fout .= '</div></div>';
    }


    if (!$cached) {
      if (isset($its['settings']['youtubefeed_maxvideos']) == false || $its['settings']['youtubefeed_maxvideos'] == '') {
        $its['settings']['youtubefeed_maxvideos'] = 50;
      }
      $yf_maxi = $its['settings']['youtubefeed_maxvideos'];

      if ($its['settings']['youtubefeed_maxvideos'] == 'all') {
        $yf_maxi = 50;
      }


      $breaker = 0;

      $i_for_its = 0;
      $nextPageToken = DZSVG_API_QUERY_NO_LEFT_PAGES_KEY;

      while ($breaker < 10 || $nextPageToken !== '') {


        $str_nextPageToken = '';

        if ($nextPageToken && $nextPageToken != DZSVG_API_QUERY_NO_LEFT_PAGES_KEY) {
          $str_nextPageToken = '&pageToken=' . $nextPageToken;
        }


        if ($dzsvg->mainoptions['youtube_api_key'] == '') {
          $dzsvg->mainoptions['youtube_api_key'] = DZSVG_YOUTUBE_SAMPLE_API_KEY[1];
        }


        $target_file = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=' . $targetfeed . '&key=' . $dzsvg->mainoptions['youtube_api_key'] . '' . $str_nextPageToken . '&maxResults=' . $yf_maxi;



        if ($dzsvg->mainoptions['debug_mode'] == 'on') {


          $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('youtube playlist target file', 'dzsvg') . '</div>
<div class="toggle-content">';
          $fout .= 'target file ' . $target_file;
          $fout .= '</div></div>';
        }


        $ida = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));



        if ($ida) {

          $obj = json_decode($ida);






          if ($obj && is_object($obj)) {


            // -- still ytplaylist


            if (isset($obj->items[0]->snippet->resourceId->videoId)) {


              foreach ($obj->items as $ytitem) {


                if ($dzsvg->mainoptions['debug_mode'] == 'on') {
                  if (isset($_GET['show_item']) && $_GET['show_item'] == 'on') {
                  };
                }
                if (isset($ytitem->snippet->resourceId->videoId) == false) {
                  echo 'this does not have id ? ';
                  continue;
                }
                $its[$i_for_its]['source'] = $ytitem->snippet->resourceId->videoId;

                if ($ytitem->snippet->thumbnails) {

                  $its[$i_for_its]['thethumb'] = $ytitem->snippet->thumbnails->medium->url;
                }
                $its[$i_for_its]['type'] = "youtube";

                $aux = $ytitem->snippet->title;
                $lb = array(
                  '"',
                  "\r\n",
                  "\n",
                  "\r",
                  "&",
                  "-",
                  "`",
                  '???',
                  "'",
                  '-'
                );
                $aux = str_replace($lb, ' ', $aux);
                $its[$i_for_its]['title'] = $aux;

                $aux = $ytitem->snippet->description;
                $lb = array("\r\n", "\n", "\r");
                $aux = str_replace($lb, '<br>', $aux);
                $lb = array('"');
                $aux = str_replace($lb, '&quot;', $aux);
                $lb = array("'");
                $aux = str_replace($lb, '&#39;', $aux);


                $auxcontent = '<p>' . str_replace(array(
                    "\r\n",
                    "\n",
                    "\r"
                  ), '</p><p>', $aux) . '</p>';

                $its[$i_for_its]['description'] = $auxcontent;
                $its[$i_for_its]['menuDescription'] = $auxcontent;

                if ($its['settings']['enable_outernav_video_author'] == 'on') {
                  $its[$i_for_its]['uploader'] = $ytitem->snippet->channelTitle;
                }

                $i_for_its++;


              }

              $found_for_cache = true;


            } else {

              array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No youtube playlist videos to be found - maybe API key not set ? This is the feed - ' . $target_file) . '</div>');

              try {

                if (isset($obj->error)) {
                  if ($obj->error->errors[0]) {


                    array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . $obj->error->errors[0]->message . '</div>');
                    if (strpos($obj->error->errors[0]->message, 'per-IP or per-Referer restriction') !== false) {

                      array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__("Suggestion - go to Video Gallery > Settings and enter your YouTube API Key") . '</div>');
                    } else {

                    }
                  }
                }


              } catch (Exception $err) {

              }
            }

          }


          if ($its['settings']['youtubefeed_maxvideos'] === 'all') {

            if (isset($obj->nextPageToken) && $obj->nextPageToken) {
              $nextPageToken = $obj->nextPageToken;
            } else {

              $nextPageToken = '';
              break;
            }

          } else {
            $nextPageToken = '';
            break;
          }


        }
        $breaker++;
      }


      if ($found_for_cache) {

        $sw34 = false;
        $auxa34 = array(
          'id' => $targetfeed,
          'items' => $its,
          'time' => $_SERVER['REQUEST_TIME'],
          'maxlen' => $its['settings']['youtubefeed_maxvideos']

        );

        if (!is_array($cacher)) {
          $cacher = array();
        } else {


          foreach ($cacher as $lab => $cach) {
            if ($cach['id'] == $targetfeed) {
              $sw34 = true;

              $cacher[$lab] = $auxa34;

              update_option('dzsvg_cache_ytplaylist', $cacher);

              break;
            }
          }


        }

        if ($sw34 == false) {

          array_push($cacher, $auxa34);


          update_option('dzsvg_cache_ytplaylist', $cacher);
        }
      }
    }


  }
  // -- END youtube playlist
  //
  //


  // -- youtube keywords
  if (($its['settings']['feedfrom'] == 'ytkeywords') && $its['settings']['ytkeywords_source'] != '') {


    include_once DZSVG_PATH . "inc/php/parse_yt_vimeo.php";


    $len = count($its) - 1;
    for ($i = 0; $i < $len; $i++) {
      unset($its[$i]);
    }


    $args = array(
      'type' => 'user_channel',
      'subtype' => 'search',

      'max_videos' => $its['settings']['youtubefeed_maxvideos'],
      'enable_outernav_video_author' => $its['settings']['enable_outernav_video_author'],
    );

    if (isset($its['settings']['enable_outernav_video_date'])) {
      $args['enable_outernav_video_date'] = $its['settings']['enable_outernav_video_date'];
    }

    if (intval($its['settings']['maxlen_desc']) && intval($its['settings']['maxlen_desc']) > 150) {

      $args['get_full_description'] = 'on';
    }

    $its2 = dzsvg_parse_yt($its['settings']['ytkeywords_source'], $args, $fout);

    $its = array_merge($its, $its2);


  }
  //=======END youtube keywords
  //
  //


  if ($dzsvg->mainoptions['debug_mode'] == 'on') {
    wp_enqueue_style('dzstoggle', DZSVG_URL . 'dzstoggle/dzstoggle.css');
    wp_enqueue_script('dzstoggle', DZSVG_URL . 'dzstoggle/dzstoggle.js');
  }
  // -- start vimeo user channel //https://vimeo.com/api/v2/blakewhitman/videos.json
  if (isset($its['settings']['feedfrom']) && ($its['settings']['feedfrom'] == 'vmuserchannel') && $its['settings']['vimeofeed_user']) {


    include_once DZSVG_PATH . "inc/php/parse_yt_vimeo.php";


    $len = count($its) - 1;
    for ($i = 0; $i < $len; $i++) {
      unset($its[$i]);
    }

    $args = array(
      'type' => 'user',
      'max_videos' => $its['settings']['vimeo_maxvideos'],
    );

    if (isset($its['settings']['vimeo_sort'])) {
      $args['vimeo_sort'] = $its['settings']['vimeo_sort'];
    }
    if (intval($its['settings']['maxlen_desc']) && intval($its['settings']['maxlen_desc']) > 150) {

      $args['get_full_description'] = 'on';
    }

    $its2 = dzsvg_parse_vimeo($its['settings']['vimeofeed_user'], $args, $fout);

    $its = array_merge($its, $its2);


  }




  //------start vmchannel //https://vimeo.com/api/v2/blakewhitman/videos.json
  // -- VIMEO CHANNEL
  if (($its['settings']['feedfrom'] == 'vmchannel') && $its['settings']['vimeofeed_channel'] != '') {


    include_once DZSVG_PATH . "inc/php/parse_yt_vimeo.php";


    $len = count($its) - 1;
    for ($i = 0; $i < $len; $i++) {
      unset($its[$i]);
    }

    $args = array(
      'type' => 'channel',
      'max_videos' => $its['settings']['vimeo_maxvideos'],
    );

    if (isset($its['settings']['vimeo_sort'])) {
      $args['vimeo_sort'] = $its['settings']['vimeo_sort'];
    }
    if (intval($its['settings']['maxlen_desc']) && intval($its['settings']['maxlen_desc']) > 150) {

      $args['get_full_description'] = 'on';
    }

    $its2 = dzsvg_parse_vimeo($its['settings']['vimeofeed_channel'], $args, $fout);

    $its = array_merge($its, $its2);
  }
  // -- end vmchannel


  //------start vmalbum //https://vimeo.com/api/v2/blakewhitman/videos.json
  if (($its['settings']['feedfrom'] == 'vmalbum') && $its['settings']['vimeofeed_vmalbum'] != '') {


    include_once DZSVG_PATH . "inc/php/parse_yt_vimeo.php";


    $len = count($its) - 1;
    for ($i = 0; $i < $len; $i++) {
      unset($its[$i]);
    }

    $args = array(
      'type' => 'album',
      'max_videos' => $its['settings']['vimeo_maxvideos'],
    );

    if (isset($its['settings']['vimeo_sort'])) {
      $args['vimeo_sort'] = $its['settings']['vimeo_sort'];
    }
    if (intval($its['settings']['maxlen_desc']) && intval($its['settings']['maxlen_desc']) > 150) {

      $args['get_full_description'] = 'on';
    }


    $its2 = dzsvg_parse_vimeo($its['settings']['vimeofeed_vmalbum'], $args, $fout);

    $its = array_merge($its, $its2);
  }
  //------start facebook //https://vimeo.com/api/v2/blakewhitman/videos.json
  if (($its['settings']['feedfrom'] == 'facebook') && $its['settings']['facebook_url'] != '') {


    include_once DZSVG_PATH . "inc/php/parse_yt_vimeo.php";


    $len = count($its) - 1;
    for ($i = 0; $i < $len; $i++) {
      unset($its[$i]);
    }

    $args = array(
      'type' => 'album',
      'max_videos' => $its['settings']['vimeo_maxvideos'],
    );

    if (isset($its['settings']['vimeo_sort'])) {
      $args['vimeo_sort'] = $its['settings']['vimeo_sort'];
    }
    if (intval($its['settings']['maxlen_desc']) && intval($its['settings']['maxlen_desc']) > 150) {

      $args['get_full_description'] = 'on';
    }


    $its2 = dzsvg_parse_facebook($its['settings']['facebook_url'], $args, $fout);

    $its = array_merge($its, $its2);
  }

  return $its;
}
