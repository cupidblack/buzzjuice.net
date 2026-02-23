<?php

include_once(DZSVG_PATH . 'inc/php/parse-media-apis/helpers/debug-parser.php');
include_once(DZSVG_PATH . 'inc/php/parse-media-apis/helpers/cache-adder.php');
include_once(DZSVG_PATH . 'inc/php/parse-media-apis/helpers/cache-helpers.php');
include_once(DZSVG_PATH . 'inc/php/parse-media-apis/helpers/vimeo-parser.php');
if (!function_exists('dzsvg_parse_vimeo__isApiLoggedIn')) {

  function dzsvg_parse_vimeo__isApiLoggedIn() {

    global $dzsvg;
    if ($dzsvg->mainoptions['vimeo_api_client_id'] != '' && $dzsvg->mainoptions['vimeo_api_client_secret'] != '' && $dzsvg->mainoptions['vimeo_api_access_token'] != '') {
      return true;
    }

    return false;
  }
}
if (!function_exists('dzsvg_parse_vimeo')) {
  function dzsvg_parse_vimeo($initialUrl, $pargs = array(), &$fout = null) {

    // -- vimeo

    global $dzsvg;


    $parseOptions = array(
      'max_videos' => '5',
      'enable_outernav_video_author' => 'off',
      'striptags' => 'off',
      'vimeo_sort' => 'default',
      'type' => 'detect',
      'vimeo_user_id' => '',
    );

    if (!is_array($pargs)) {
      $pargs = array();
    }

    $parseOptions = array_merge($parseOptions, $pargs);

    $its = array();
    $apiRequest = '';


    $type = '';
    $isFromLoggedInApi = false; // -- this will establish if the feed is from the logged in api
    $original_max_videos = '';


    $vimeo_id = $dzsvg->mainoptions['vimeo_api_user_id']; // Get from https://vimeo.com/settings, must be in the form of user123456
    $consumer_key = $dzsvg->mainoptions['vimeo_api_client_id'];
    $consumer_secret = $dzsvg->mainoptions['vimeo_api_client_secret'];
    $token = $dzsvg->mainoptions['vimeo_api_access_token'];


    $feedMainId = '';

    if ($parseOptions['type'] == 'detect') {
      if (strpos($initialUrl, 'vimeo.com/album') !== false) {
        $type = 'album';
      } elseif (strpos($initialUrl, 'vimeo.com/channels') !== false) {

        $type = 'channel';

        // -- albums are now channels
      } elseif (strpos($initialUrl, 'vimeo.com/showcase') !== false) {

        $type = 'album';
        preg_match_all('/showcase\/(.*?)($|&|\/)/', $initialUrl, $output_array);
        if (isset($output_array[1][0])) {
          $feedMainId = $output_array[1][0];
        }
      } elseif (strpos($initialUrl, '/folder/') !== false) {

        $type = 'folder';
      } else {

        $type = 'user';
      }
    } else {
      $type = $parseOptions['type'];
    }


    if ($type == '') {
      $type = 'user';
    }


    $targetUser = '';
    $targetSource = '';
    $q_strings = explode('/', $initialUrl);

    if ($q_strings[count($q_strings) - 1] == '') {
      unset($q_strings[count($q_strings) - 1]);
    }


    if ($type == 'folder') {

      preg_match('/user\/(.*?)\/folder\/(\d*?)$/', $initialUrl, $output_array);

      if (isset($output_array[1]) && $output_array[1]) {
        $targetUser = $output_array[1];
      }
      if (isset($output_array[2]) && $output_array[2]) {
        $targetSource = $output_array[2];
      }
    }
    if ($type == 'album') {

      $targetSource = $q_strings[count($q_strings) - 1];

    }
    if ($type == 'channel') {

      $targetSource = $q_strings[count($q_strings) - 1];

    }
    if ($type == 'user') {

      $targetSource = $q_strings[count($q_strings) - 1];


      if ($targetSource == 'videos') {

        $targetSource = $q_strings[count($q_strings) - 2];
      }
    }


    $max_videos = $parseOptions['max_videos'];


    // --- vimeo album
    if ($type == 'album') {


      $cachedContent = get_option(DZSVG_PARSER_VIMEO_ALBUM_CACHE_NAME);

      $isCached = false;


      if ($cachedContent == false || is_array($cachedContent) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
        $isCached = false;
      } else {


        $ik = -1;
        $i = 0;
        for ($i = 0; $i < count($cachedContent); $i++) {
          if ($cachedContent[$i]['id'] == $feedMainId) {
            if ($_SERVER['REQUEST_TIME'] - $cachedContent[$i]['time'] < intval($dzsvg->mainoptions['cache_time'])) {
              $ik = $i;

              $isCached = true;
              break;
            }
          }
        }


        if ($isCached) {
          foreach ($cachedContent[$ik]['items'] as $cacheItemIndex => $vimeoItem) {
            if ($cacheItemIndex === 'settings') {
              continue;
            }

            $its[$cacheItemIndex] = $vimeoItem;
          }
        }

      }

      // -- finished checking if cached


      $max_videos = $parseOptions['max_videos'];
      $parseOptions['original_max_videos'] = $max_videos;

      if ($max_videos === 'all') {
        $max_videos = 50;
      }


      $breakerCount = 1;
      $vimeo_response = null;
      $nextPageToken = 'start';

      $totalIndex = 0;


      /**
       * album
       */
      if ($isCached == false) {


        while ($breakerCount < 10 && $nextPageToken !== '') {

          $apiResponse = '';
          if ($dzsvg->mainoptions['vimeo_api_client_id'] != '' && $dzsvg->mainoptions['vimeo_api_client_secret'] != '' && $dzsvg->mainoptions['vimeo_api_access_token'] != '') {
            // -- authentificated call
            $isFromLoggedInApi = true;


            if (!class_exists('Vimeo')) {
              require_once(DZSVG_PATH . 'inc/vimeoapi/vimeo.php');
            }


            $sort_call = '';

            $page_call = '';

            if ($parseOptions['vimeo_sort'] && $parseOptions['vimeo_sort'] != 'default') {
              $sort_call .= '&sort=' . $parseOptions['vimeo_sort'];
            }

            if ($nextPageToken && $nextPageToken != 'start') {
              $page_call = '&page=' . $breakerCount;
            }


            // -- album


            if ($max_videos == '') {
              $max_videos = '25';
            }

            // Do an authentication call
            $vimeo = new Vimeo($consumer_key, $consumer_secret);
            $vimeo->setToken($token); // -- $token_secret


            $apiRequest = '/albums/' . $feedMainId . '/videos?per_page=' . $max_videos . $sort_call . $page_call;


            if ($parseOptions['vimeo_user_id']) {
              $apiRequest = '/users/' . $parseOptions['vimeo_user_id'] . $apiRequest;
            }

            $vimeo_response = $vimeo->request($apiRequest);


            if ($vimeo_response['status'] != 200) {
              if (isset($vimeo_response['body']['message'])) {

                error_log('dzsvg.php line 4023: ' . $vimeo_response['body']['message']);
              }
              if (isset($vimeo_response['body']['error'])) {

                error_log('dzsvg.php line 4023: ' . $vimeo_response['body']['error']);
              }

              if (isset($vimeo_response['body']['error'])) {
                array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . $vimeo_response['body']['error'] . '</div>');
              }
            }
            if (isset($vimeo_response['body']['data'])) {
              $apiResponse = $vimeo_response['body']['data'];
            }
          } else {
            // -- unauthentificated call

            $apiRequest = "https://vimeo.com/api/v2/album/" . $feedMainId . "/videos.json";


            $apiResponse = DZSHelpers::get_contents($apiRequest, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));
            $isFromLoggedInApi = false;
          }


          if ($dzsvg->mainoptions['debug_mode'] == 'on') {
            dzsvg_debug_parse_items_request(esc_html__('api response', DZSVG_ID), array(
              array(
                'title' => '$apiRequest - ',
                'content' => preg_replace("/\r|\n/", "", print_r($apiRequest, true)),
              ),
              array(
                'title' => '$apiResponse - ',
                'content' => preg_replace("/\r|\n/", "", print_r($apiResponse, true)),
              ),
            ));
          }


          // -- api response settled.

          if ($isFromLoggedInApi) {
            dzsvg_parser_vimeo_loggedIn($apiResponse, $dzsvg, $parseOptions, $apiRequest, $its, $totalIndex);
          } else {
            // -- simple call
            if (is_string($apiResponse)) {
              $idar = json_decode($apiResponse); // -- vmuser
            } else {
              $idar = $apiResponse;
            }
            dzsvg_parser_vimeo_unAuthentificated($idar, $dzsvg, $apiRequest, $its, $totalIndex);
          }


          // -- vimeo

          if ($parseOptions['max_videos'] === 'all') {
            if ($isFromLoggedInApi) {
              if ($vimeo_response['body']['paging']['next']) {
                $nextPageToken = $vimeo_response['body']['paging']['next'];
              } else {
                $nextPageToken = '';
                break;
              }
            } else {
              $nextPageToken = '';
              break;
            }
          } else {
            $nextPageToken = '';
            break;
          }
          $breakerCount++;
        }
      }


      if ($dzsvg->mainoptions['debug_mode'] == 'on') {
        dzsvg_debug_parse_items_request(esc_html__('finished adding items vimeo album ( $its ) ', DZSVG_ID), array(
          array(
            'title' => '$margs - ',
            'content' => print_rr($parseOptions, array('echo' => false, 'encode_html' => true))
          ),
        ));
      }


      // -- finished adding items


      if ($dzsvg->mainoptions['disable_api_caching'] != 'on') {
        dzsvg_parser_cacheAdder($its, $feedMainId, $isFromLoggedInApi, $original_max_videos, $parseOptions['max_videos'], DZSVG_PARSER_VIMEO_ALBUM_CACHE_NAME);
      }


    }


    // --- END vimeo album


    // --- vimeo album
    if ($type == 'folder') {

      $cachedContent = get_option(DZSVG_PARSER_VIMEO_FOLDER_CACHE_NAME);
      $isCached = false;


      if ($cachedContent == false || is_array($cachedContent) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
        $isCached = false;
      } else {


        $ik = -1;
        $i = 0;
        for ($i = 0; $i < count($cachedContent); $i++) {
          if ($cachedContent[$i]['id'] == $targetSource) {
            if ($_SERVER['REQUEST_TIME'] - $cachedContent[$i]['time'] < intval($dzsvg->mainoptions['cache_time'])) {
              $ik = $i;

              $isCached = true;
              break;
            }
          }
        }


        if ($isCached) {
          foreach ($cachedContent[$ik]['items'] as $cacheItemIndex => $vimeoItem) {
            if ($cacheItemIndex === 'settings') {
              continue;
            }

            $its[$cacheItemIndex] = $vimeoItem;
          }
        }

      }

      // -- finished checking if cached


      $max_videos = $parseOptions['max_videos'];
      $parseOptions['original_max_videos'] = $max_videos;

      if ($max_videos === 'all') {
        $max_videos = 50;
      }

      if ($dzsvg->mainoptions['debug_mode'] == 'on') {


        dzsvg_debug_parse_items_request(esc_html__('starting parse from parse_yt_vimeo', DZSVG_ID), array(
          array(
            'title' => '$isCached - ',
            'content' => $isCached ? 'true' : 'false'
          ),
          array(
            'title' => 'margs - ',
            'content' => print_rr($parseOptions, array('echo' => false, 'encode_html' => true))
          ),
        ));

      }


      $breakerCount = 1;
      $vimeo_response = null;
      $nextPageToken = 'start';

      $totalIndex = 0;


      // -- vimeo folder
      if ($isCached == false) {


        while ($breakerCount < 10 && $nextPageToken !== '') {

          $vimeoApiUnauthentificatedUri = 'https://api.vimeo.com/users/{user_id}/projects/{project_id}/videos';

          $vimeoApiUnauthentificatedUri = str_replace('{user_id}', $targetUser, $vimeoApiUnauthentificatedUri);
          $vimeoApiUnauthentificatedUri = str_replace('{project_id}', $targetSource, $vimeoApiUnauthentificatedUri);


          $apiResponse = '';
          if ($dzsvg->mainoptions['vimeo_api_client_id'] != '' && $dzsvg->mainoptions['vimeo_api_client_secret'] != '' && $dzsvg->mainoptions['vimeo_api_access_token'] != '') {


            if (!class_exists('Vimeo')) {
              require_once(DZSVG_PATH . 'inc/vimeoapi/vimeo.php');
            }


            $sort_call = '';

            $page_call = '';


            if ($parseOptions['vimeo_sort'] && $parseOptions['vimeo_sort'] != 'default') {
              $sort_call .= '&sort=' . $parseOptions['vimeo_sort'];
            }

            if ($nextPageToken && $nextPageToken != 'start') {
              $page_call = '&page=' . $breakerCount;
            }


            // -- album


            if ($max_videos == '') {
              $max_videos = '25';
            }

            // Do an authentication call
            $vimeo = new Vimeo($consumer_key, $consumer_secret);
            $vimeo->setToken($token); // -- $token_secret
            $apiRequest = '/users/' . $targetUser . '/projects/' . $targetSource . '/videos?per_page=' . $max_videos . $sort_call . $page_call;
            $vimeo_response = $vimeo->request($apiRequest);


            if ($dzsvg->mainoptions['debug_mode'] == 'on') {


              dzsvg_debug_parse_items_request(esc_html__('starting parse from parse_yt_vimeo', DZSVG_ID), array(
                array(
                  'title' => '$isCached - ',
                  'content' => ($isCached ? 'true' : 'false')
                ),
                array(
                  'title' => '$request - ',
                  'content' => ($apiRequest)
                ),
                array(
                  'title' => '$vimeo_response - ',
                  'content' => print_rr($vimeo_response, array('echo' => false, 'encode_html' => true))
                ),
              ));

            }


            $errorMessage = '';
            if ($vimeo_response['status'] != 200) {
              if (isset($vimeo_response['body']['error'])) {
                $errorMessage = $vimeo_response['body']['error'];
              }

              if ($errorMessage) {

                array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . $errorMessage . '</div>');
              }
            }
            if (isset($vimeo_response['body']['data'])) {
              $apiResponse = $vimeo_response['body']['data'];
            }
            $isFromLoggedInApi = true;
          } else {
            $apiResponse = DZSHelpers::get_contents($vimeoApiUnauthentificatedUri, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));
            $isFromLoggedInApi = false;
          }


          if ($dzsvg->mainoptions['debug_mode'] == 'on') {
            echo 'debug mode: mode vimeo album target file - ' . $targetSource
              . '<br>cached - ' . $isCached . '<br>vimeo_response is:';
          }


          if ($isFromLoggedInApi) {

            if (!is_array($apiResponse)) {

              $apiResponse = (array)$apiResponse;
            }

            // -- authentificated CALL
            dzsvg_parser_vimeo_loggedIn($apiResponse, $dzsvg, $parseOptions, $apiRequest, $its, $totalIndex);

          } else {

            // -- simple call

            if (!is_object($apiResponse) && !is_array($apiResponse)) {
              $idar = json_decode($apiResponse); // -- vmuser
            } else {
              $idar = $apiResponse;
            }


            dzsvg_parser_vimeo_unAuthentificated($idar, $dzsvg, $vimeoApiUnauthentificatedUri, $its, $totalIndex);

          }


          // -- vimeo

          if ($parseOptions['max_videos'] === 'all') {
            if ($isFromLoggedInApi) {
              if ($vimeo_response['body']['paging']['next']) {
                $nextPageToken = $vimeo_response['body']['paging']['next'];
              } else {
                $nextPageToken = '';
                break;
              }
            } else {

              $nextPageToken = '';
              break;
            }

          } else {
            $nextPageToken = '';
            break;
          }
          $breakerCount++;
        }


      }




      // -- finished adding items


      if ($dzsvg->mainoptions['disable_api_caching'] != 'on') {
        dzsvg_parser_cacheAdder($its, $targetSource, $isFromLoggedInApi, $original_max_videos, $parseOptions['max_videos'], DZSVG_PARSER_VIMEO_FOLDER_CACHE_NAME);
      }


    }


    // -- vimeo CHANNEL
    if ($type == 'channel') {


      $cachedContent = get_option(DZSVG_PARSER_VIMEO_CHANNEL_CACHE_NAME);

      $isCached = false;
      $isFromLoggedInApi = false;

      if ($cachedContent == false || is_array($cachedContent) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
        $isCached = false;
      } else {


        $ik = -1;
        $i = 0;
        for ($i = 0; $i < count($cachedContent); $i++) {


          if ($cachedContent[$i]['id'] == $targetSource) {


            if ((isset($cachedContent[$i]['maxlen']) && $cachedContent[$i]['maxlen'] == $max_videos) || (isset($cachedContent[$i]['maxlen_from_margs']) && $cachedContent[$i]['maxlen_from_margs'] == $parseOptions['max_videos'])) {

              if ($_SERVER['REQUEST_TIME'] - $cachedContent[$i]['time'] < intval($dzsvg->mainoptions['cache_time'])) {
                $ik = $i;

                $isCached = true;
                break;
              }
            }
          }
        }


        if ($isCached) {
          foreach ($cachedContent[$ik]['items'] as $cacheItemIndex => $vimeoItem) {
            if ($cacheItemIndex === 'settings') {
              continue;
            }

            $its[$cacheItemIndex] = $vimeoItem;
          }
        }

      }

      // -- finished checking if cached


      //-- vimeo channel


      $breakerCount = 1;
      $vimeo_response = null;
      $nextPageToken = 'start';

      $totalIndex = 0;


      if ($isCached == false) {

        while ($breakerCount < 10 && $nextPageToken !== '') {
          $vimeoApiUnauthentificatedUri = "https://vimeo.com/api/v2/channel/" . $targetSource . "/videos.json";

          $apiResponse = '';
          if ($dzsvg->mainoptions['vimeo_api_client_id'] != '' && $dzsvg->mainoptions['vimeo_api_client_secret'] != '' && $dzsvg->mainoptions['vimeo_api_access_token'] != '') {

            $isFromLoggedInApi = true;


            if (!class_exists('Vimeo')) {
              require_once(DZSVG_PATH . 'inc/vimeoapi/vimeo.php');
            }


            // -- sanitizing
            if ($max_videos == '') {
              $max_videos = '25';
            }


            $original_max_videos = $max_videos;

            if ($max_videos === 'all') {
              $max_videos = 96;
            }


            $sort_call = '';

            $page_call = '';

            if ($parseOptions['vimeo_sort'] && $parseOptions['vimeo_sort'] != 'default') {
              $sort_call .= '&sort=' . $parseOptions['vimeo_sort'];
            }

            if ($nextPageToken && $nextPageToken != 'start') {
              $page_call = '&page=' . $breakerCount;
            }


            // Do an authentication call
            $vimeo = new Vimeo($consumer_key, $consumer_secret);
            $vimeo->setToken($token); //,$token_secret
            $request = '/channels/' . $targetSource . '/videos?per_page=' . $max_videos . $sort_call . $page_call;
            $vimeo_response = $vimeo->request($request);


            if ($vimeo_response['status'] != 200) {


              try {
                error_log('dzsvg.php line 4023: ' . $vimeo_response['body']['message']);
                if (isset($vimeo_response['body']['error'])) {

                  array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . $vimeo_response['body']['error'] . '</div>');
                }

              } catch (Exception $err) {

                $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . sprintf(__('mode vimeo %s - making autetificated call', 'dzsvg'), $type) . '</div>
<div class="toggle-content">';
                $fout .= 'cached - ( ' . $isCached . ' )  cacher is...<br>';
                $fout .= (print_rr($vimeo_response, array('echo' => false, 'encode_html' => true)));
                $fout .= (print_rr($err, array('echo' => false, 'encode_html' => true)));
                $fout .= '</div></div>';


              }

            }
            if (isset($vimeo_response['body']['data'])) {
              $apiResponse = $vimeo_response['body']['data'];
            }
            $isFromLoggedInApi = true;
          } else {
            $apiResponse = DZSHelpers::get_contents($vimeoApiUnauthentificatedUri, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));
            $isFromLoggedInApi = false;

          }

          $apiRequest = $isFromLoggedInApi ? $apiRequest : $vimeoApiUnauthentificatedUri;


          dzsvg_parse_vimeo_its($its, $isFromLoggedInApi, $apiResponse, $totalIndex, $parseOptions, $apiRequest);


          if ($parseOptions['max_videos'] === 'all') {
            if ($isFromLoggedInApi) {
              if ($vimeo_response['body']['paging']['next']) {
                $nextPageToken = $vimeo_response['body']['paging']['next'];
              } else {
                $nextPageToken = '';
                break;
              }
            } else {

              $nextPageToken = '';
              break;
            }
          } else {
            $nextPageToken = '';
            break;
          }
          $breakerCount++;
        }


      }


      // -- finished adding items
      if ($dzsvg->mainoptions['disable_api_caching'] != 'on') {
        dzsvg_parser_cacheAdder($its, $targetSource, $isFromLoggedInApi, $original_max_videos, $parseOptions['max_videos'], DZSVG_PARSER_VIMEO_CHANNEL_CACHE_NAME);
      }


    }
    // --- END vimeo channel


    // --- start vmuser / start vimeo user


    // -- vimeo user CHANNEL
    if ($type == 'user') {

      $cachedContent = get_option(DZSVG_PARSER_VIMEO_USER_CHANNEL_CACHE_NAME);

      $isCached = false;
      $isFromLoggedInApi = false;


      $maxCacheTime = $dzsvg->mainoptions['cache_time'];
      $isCachedIndex = dzsvg_cache_helpers_isCacheValid($cachedContent, $dzsvg, $targetSource, $max_videos, $parseOptions);

      if ($isCachedIndex !== false) {
        foreach ($cachedContent[$isCachedIndex]['items'] as $cacheItemIndex => $vimeoItem) {
          if ($cacheItemIndex === 'settings') {
            continue;
          }
          $its[$cacheItemIndex] = $vimeoItem;
        }
      }
      // -- finished checking if cached



      //-- vimeo user channel


      $breakerCount = 1;
      $vimeo_response = null;
      $nextPageToken = 'start';

      $totalIndex = 0;


      if (!$isCachedIndex) {
        dzsvg_parse_vimeo__getFromApi($its, $targetSource, $max_videos, $parseOptions, $nextPageToken, $breakerCount, $consumer_key, $consumer_secret, $token, $fout, $isCachedIndex, $type, $apiRequest);
      }


      // -- finished adding items
      if ($dzsvg->mainoptions['disable_api_caching'] != 'on') {

        dzsvg_parser_cacheAdder($its, $targetSource, $isFromLoggedInApi, $original_max_videos, $parseOptions['max_videos'], DZSVG_PARSER_VIMEO_USER_CHANNEL_CACHE_NAME);
      }


    }


    // --- END vimeo user channel


    return $its;
  }

}



