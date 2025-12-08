<?php
include_once(DZSVG_PATH . 'inc/php/parse-media-apis/helpers/youtube-parser-helpers.php');
if (!function_exists('dzsvg_parse_yt')) {
  function dzsvg_parse_yt_normalizeIts($its) {


    return $its;
  }
}
if (!function_exists('dzsvg_parse_yt')) {
  function dzsvg_parse_yt($youtubeLink, $pargs = array(), &$fout = null) {


    /** @var DZSVideoGallery $dzsvg */
    global $dzsvg;


    $parseOptions = array(
      'max_videos' => '5',
      'enable_outernav_video_author' => 'off',
      'enable_outernav_video_date' => 'off',
      'striptags' => 'off',
      'get_full_description' => 'off',
      'is_id' => 'on',
      'type' => 'detect',
      'subtype' => 'detect',
      'query' => '',
      'youtube_order' => '',
    );

    if (!is_array($pargs)) {
      $pargs = array();
    }

    $parseOptions = array_merge($parseOptions, $pargs);

    $its = array();


    $youtubeApiType = '';


    $youtubeLink = str_replace('&amp;', '&', $youtubeLink);

    if ($parseOptions['subtype'] != 'detect') {
      $youtubeApiType = $parseOptions['subtype'];
    }
    if (strpos($youtubeLink, 'youtube.com/c/') !== false) {
      $youtubeApiType = 'user_channel';
    }
    if (strpos($youtubeLink, 'youtube.com/channel/') !== false) {
      $youtubeApiType = 'user_channel';
      $parseOptions['is_id'] = 'on';
    }
    if (strpos($youtubeLink, 'youtube.com/user/') !== false) {
      $youtubeApiType = 'user_channel';

      $parseOptions['is_id'] = 'off';
    }


    if (strpos($youtubeLink, 'youtube.com/playlist') !== false || strpos($youtubeLink, 'list=') !== false) {
      $youtubeApiType = 'playlist';
    }
    if (strpos($youtubeLink, 'youtube.com/results') !== false) {
      $youtubeApiType = 'search';
    }

    $parseOptions['original_max_videos'] = $parseOptions['max_videos'];
    if ($parseOptions['max_videos'] == '') {
      $parseOptions['max_videos'] = '30';
    }


    if ($parseOptions['youtube_order'] == '' || $parseOptions['youtube_order'] == 'default') {
      $parseOptions['youtube_order'] = 'date';
    }

    $targetfeed = '';

    if (strpos($youtubeLink, '/') !== false) {
      $q_strings = explode('/', $youtubeLink);


      if ($youtubeApiType == 'user_channel') {

        $targetfeed = $q_strings[count($q_strings) - 1];

        if ($targetfeed == '' || $targetfeed == 'videos') {

          $targetfeed = $q_strings[count($q_strings) - 2];
        }

        if (strpos($targetfeed, '?') !== false) {
          $targetfeed = strtok($targetfeed, '?');
        }
      }
      if ($youtubeApiType == 'playlist') {

        $targetfeed = DZSHelpers::get_query_arg($youtubeLink, 'list');
      }
      if ($youtubeApiType == 'search') {

        $targetfeed = DZSHelpers::get_query_arg($youtubeLink, 'search_query');

      }

    } else {
      $targetfeed = $youtubeLink;
    }

    if ($parseOptions['query']) {
      if ($targetfeed == '') {
        $targetfeed = $parseOptions['query'];
      }
    }


    $max_videos = $parseOptions['max_videos'];
    $original_max_videos = $parseOptions['max_videos'];


    if (isset($max_videos) == false || $max_videos == '') {
      $max_videos = 50;
    }
    if ($max_videos == 'all') {
      $max_videos = 50;
    }
    $per_page = $max_videos;

    // todo: temp


    if ($youtubeApiType == '') {
      array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error dzsvg--youtube-parse-error error">' . esc_html__('not a compatible youtube link', DZSVG_ID) . ' ( ' . $youtubeLink . ' )</div>');
    }


    $str_youtubeApiKey = $dzsvg->mainoptions['youtube_api_key'];
    if ($str_youtubeApiKey == '') {
      $str_youtubeApiKey = DZSVG_YOUTUBE_SAMPLE_API_KEY[1];
    }

    /** user channel
     *
     * */
    if ($youtubeApiType == 'user_channel') {


      // -- start


      dzsvg_cache_purgeItsItems($its);


      $cachedContent = get_option(DZSVG_PARSER_YOUTUBE_USER_CHANNEL_CACHE_NAME);

      $isCached = false;
      $isGoingToCache = false;


      $targetSource = $targetfeed;

      $maxCacheTime = $dzsvg->mainoptions['cache_time'];
      $isCachedIndex = dzsvg_cache_helpers_isCacheValid($cachedContent, $dzsvg, $targetSource, $original_max_videos, array(
        'max_videos' => $original_max_videos
      ), $maxCacheTime);

      if ($isCachedIndex !== false) {
        foreach ($cachedContent[$isCachedIndex]['items'] as $lab => $item) {
          if ($lab === 'settings') {
            continue;
          }
          $its[$lab] = $item;
        }
      }


      // -- end caching


      if ($isCachedIndex === false) {


        $str_nextPageToken = '';
        $theChannelId = $targetfeed;


        $stringParameterOrder = '';

        if ($parseOptions['youtube_order'] && $parseOptions['youtube_order'] != 'default') {
          $stringParameterOrder = '&order=' . $parseOptions['youtube_order'];
        }

        $i = 0;

        $tryToGetChannelId = dzsvg_parser_youtube_get_channel_id($targetfeed, $str_youtubeApiKey, $dzsvg);

        if ($tryToGetChannelId) {
          $theChannelId = $tryToGetChannelId;
        }

        $breakerIndex = 0;
        $nextPageToken = DZSVG_API_QUERY_NO_LEFT_PAGES_KEY;

        while ($breakerIndex < 10 || $nextPageToken !== '') {
          $str_nextPageToken = '';
          if ($nextPageToken && $nextPageToken != DZSVG_API_QUERY_NO_LEFT_PAGES_KEY) {
            $str_nextPageToken = '&pageToken=' . $nextPageToken;
          }
          $targetApiCall = 'https://www.googleapis.com/youtube/v3/search?key=' . $str_youtubeApiKey . '&channelId=' . $theChannelId . '&part=snippet&type=video' . $str_nextPageToken . '&maxResults=' . $max_videos . $stringParameterOrder;


          $apiResponseJson = DZSHelpers::get_contents($targetApiCall, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));


          if ($apiResponseJson) {

            $apiResponseObject = json_decode($apiResponseJson);


            if ($apiResponseObject && is_object($apiResponseObject) && isset($apiResponseObject->error) && isset($apiResponseObject->error->message)) {
              $fout .= '<div class="dzsvg-error dzsvg--youtube-parse-error error">' . $apiResponseObject->error->message . '</div>';
            }


            // -- still channel
            if ($apiResponseObject && is_object($apiResponseObject)) {


              if (isset($apiResponseObject->items[0]->id->videoId)) {


                foreach ($apiResponseObject->items as $ytitem) {


                  if (isset($ytitem->id->videoId) == false) {
                    echo 'this does not have id ? ';
                    continue;
                  }

                  $vid = $ytitem->id->videoId;


                  $video_details_arr = dzsvg_view_parseApi_youtubeGetMoreDetails($vid);

                  if ($dzsvg->mainoptions['youtube_hide_non_embeddable'] == 'on' && $video_details_arr && isset($video_details_arr['items']) && $video_details_arr['items'][0]['status']['embeddable'] != 1) {

                    continue;
                  }


                  $its[$i]['source'] = $vid;
                  $its[$i]['thumbnail'] = $ytitem->snippet->thumbnails->medium->url;
                  $its[$i]['type'] = "youtube";
                  $its[$i]['permalink'] = "https://www.youtube.com/watch?v=" . $its[$i]['source'];

                  $aux = $ytitem->snippet->title;
                  $lb = array('"', "\r\n", "\n", "\r", "", "`", '???', '');
                  $aux = str_replace($lb, ' ', $aux);
                  $its[$i]['title'] = $aux;

                  $aux = $ytitem->snippet->description;
                  $lb = array("\r\n", "\n", "\r");
                  $aux = str_replace($lb, '<br>', $aux);
                  $lb = array('"');
                  $aux = str_replace($lb, '&quot;', $aux);


                  $sanitizedDescription = '<p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', $aux) . '</p>';

                  $its[$i]['description'] = $sanitizedDescription;
                  $its[$i]['menuDescription'] = $sanitizedDescription;


                  if ($video_details_arr && isset($video_details_arr['items']) && $video_details_arr['items'][0]['snippet']['description']) {

                    $its[$i]['description'] = $video_details_arr['items'][0]['snippet']['description'];
                    $its[$i]['menuDescription'] = $video_details_arr['items'][0]['snippet']['description'];
                  }

                  if ($parseOptions['enable_outernav_video_author'] == 'on') {
                  }
                  if ($parseOptions['enable_outernav_video_date'] == 'on') {
                  }
                  $its[$i]['uploader'] = $ytitem->snippet->channelTitle;
                  $its[$i]['upload_date'] = $ytitem->snippet->publishedAt;


                  if ($parseOptions['get_full_description'] == 'on') {
                    $arr = dzsvg_parse_youtube_video($its[$i]['source'], $parseOptions, $fout, $str_youtubeApiKey);


                    if (is_array($arr)) {
                      $its[$i] = array_merge($its[$i], $arr);
                    }
                  }

                  $i++;


                }


                $isGoingToCache = true;
              } else {

                array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No videos to be found - ') . $targetApiCall . '</div>');
              }
            } else {

              array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('Object channel is not JSON...') . '</div>');
            }
          } else {

            array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('Cannot get info from YouTube API about channel - ') . $targetApiCall . '</div>');
          }


          if ($parseOptions['original_max_videos'] === 'all') {

            if (isset($apiResponseObject->nextPageToken) && $apiResponseObject->nextPageToken) {
              $nextPageToken = $apiResponseObject->nextPageToken;
            } else {

              $nextPageToken = '';
              break;
            }

          } else {
            $nextPageToken = '';
            break;
          }

          $breakerIndex++;
        }


        if ($isGoingToCache) {
          dzsvg_parser_cacheAdder($its, $targetfeed, true, $original_max_videos, $original_max_videos, DZSVG_PARSER_YOUTUBE_USER_CHANNEL_CACHE_NAME);
        }
      }


    }
    // --- END user channel


    // --- youtube playlist
    if ($youtubeApiType == 'playlist') {


      $len = count($its) - 1;
      for ($i = 0; $i < $len; $i++) {
        unset($its[$i]);
      }


      $cachedContent = get_option(DZSVG_PARSER_YOUTUBE_PLAYLIST_CACHE_NAME);

      $isGoingToCache = false;


      $targetSource = $targetfeed;
      $maxCacheTime = $dzsvg->mainoptions['cache_time'];
      $isCachedIndex = dzsvg_cache_helpers_isCacheValid($cachedContent, $dzsvg, $targetSource, $original_max_videos, array(
        'max_videos' => $original_max_videos
      ), $maxCacheTime);

      if ($isCachedIndex !== false) {
        foreach ($cachedContent[$isCachedIndex]['items'] as $lab => $item) {
          if ($lab === 'settings') {
            continue;
          }
          $its[$lab] = $item;
        }
      }


      // -- youtube playlist
      if ($isCachedIndex === false) {


        $breakerIndex = 0;

        $i_for_its = 0;
        $nextPageToken = DZSVG_API_QUERY_NO_LEFT_PAGES_KEY; // -- this will see if we have next page

        while ($breakerIndex < 10 || $nextPageToken !== '') {


          // -- use sort method for search

          $targetApiCall = dzsvg_view_parseApi_youtubeConstructApiCall('playlist', $nextPageToken, $dzsvg, $parseOptions, $targetfeed, $per_page, $str_youtubeApiKey);


          $apiResponseJson = DZSHelpers::get_contents($targetApiCall, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));


          if ($apiResponseJson) {

            $apiResponseObject = json_decode($apiResponseJson);


            if ($apiResponseObject && is_object($apiResponseObject)) {
              dzsvg_view_parseApi_youtubeParseItems($its, $i_for_its, $apiResponseObject, $targetApiCall, $dzsvg, $parseOptions, $isGoingToCache);
            }


            if ($parseOptions['original_max_videos'] === 'all') {
              if (isset($apiResponseObject->nextPageToken) && $apiResponseObject->nextPageToken) {
                $nextPageToken = $apiResponseObject->nextPageToken;
              } else {
                $nextPageToken = '';
                break;
              }
            } else {
              $nextPageToken = '';
              break;
            }
          }
          $breakerIndex++;
        }


        if ($isGoingToCache) {
          dzsvg_parser_cacheAdder($its, $targetfeed, true, $original_max_videos, $original_max_videos, DZSVG_PARSER_YOUTUBE_PLAYLIST_CACHE_NAME);
        }
      }
    }
    // --- END youtube playlist


    // --- youtube search query
    if ($youtubeApiType == 'search') {


      $len = count($its) - 1;
      for ($i = 0; $i < $len; $i++) {
        unset($its[$i]);
      }


      $cachedContent = get_option(DZSVG_PARSER_YOUTUBE_KEYWORDS_CACHE_NAME);

      $isCached = false;
      $isGoingToCache = false;


      $targetSource = $targetfeed;

      $maxCacheTime = $dzsvg->mainoptions['cache_time'];
      $isCachedIndex = dzsvg_cache_helpers_isCacheValid($cachedContent, $dzsvg, $targetSource, $original_max_videos, array(
        'max_videos' => $original_max_videos
      ), $maxCacheTime);

      if ($isCachedIndex !== false) {
        foreach ($cachedContent[$isCachedIndex]['items'] as $lab => $item) {
          if ($lab === 'settings') {
            continue;
          }
          $its[$lab] = $item;
        }
      }


      //-- youtube search
      if ($isCachedIndex === false) {
        if (isset($max_videos) == false || $max_videos == '') {
          $per_page = 50;
        }
        $per_page = $max_videos;

        if ($parseOptions['original_max_videos'] == 'all') {
          $per_page = 50;
        }


        $breakerIndex = 0;

        $i_for_its = 0;
        $nextPageToken = DZSVG_API_QUERY_NO_LEFT_PAGES_KEY;
        $targetfeed = str_replace(' ', '+', $targetfeed);


        while ($breakerIndex < 5 || $nextPageToken !== '') {


          $targetApiCall = dzsvg_view_parseApi_youtubeConstructApiCall('search', $nextPageToken, $dzsvg, $parseOptions, $targetfeed, $per_page);


          $apiResponseJson = DZSHelpers::get_contents($targetApiCall, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));


          if ($apiResponseJson) {

            $apiResponseObject = json_decode($apiResponseJson);


            if ($apiResponseObject && is_object($apiResponseObject)) {


              dzsvg_view_parseApi_youtubeParseItems($its, $i_for_its, $apiResponseObject, $targetApiCall, $dzsvg, $parseOptions, $isGoingToCache);

              //todo: continue from here - get them one by one
              if (isset($apiResponseObject->items[0]->id->videoId)) {
                foreach ($apiResponseObject->items as $ytitem) {
                }
              } else {
                array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No youtube keyboard videos to be found') . '</div>');
              }
            }
            if ($parseOptions['original_max_videos'] === 'all') {

              if (isset($apiResponseObject->nextPageToken) && $apiResponseObject->nextPageToken) {
                $nextPageToken = $apiResponseObject->nextPageToken;
              } else {
                break;
              }
            } else {
              break;
            }
          } else {

            array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No youtube keyboards ida found ' . $targetApiCall) . '</div>');
          }
          $breakerIndex++;
        }

        if ($isGoingToCache) {
          dzsvg_parser_cacheAdder($its, $targetfeed, true, $original_max_videos, $original_max_videos, DZSVG_PARSER_YOUTUBE_KEYWORDS_CACHE_NAME);
        }
      }
      // -- end not cached


    }
    // --- END youtube search query


    return $its;
  }

}


if (!function_exists('dzsvg_parse_youtube_video')) {
  function dzsvg_parse_youtube_video($id, $pargs = array(), &$fout = null, $youtubeApiKey = '') {

    global $dzsvg;

    $margs = array(
      'max_videos' => '5',
      'enable_outernav_video_author' => 'off',
      'striptags' => 'off',
      'get_full_description' => 'off',
      'type' => 'detect',
    );


    $response = '';


    $lab_cacher = 'dzsvg_cache_ytvideos';
    $cacher = get_option($lab_cacher);

    $cached = false;


    $foutarr = array();


    if ($cacher == false || is_array($cacher) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
      $cached = false;
    } else {


      $ik = -1;
      $i = 0;
      for ($i = 0; $i < count($cacher); $i++) {
        if ($cacher[$i]['id'] == $id) {
          if ($_SERVER['REQUEST_TIME'] - $cacher[$i]['time'] < 144000) {
            $ik = $i;

            $cached = true;
            break;
          }
        }
      }


      if ($cached) {
        $response = $cacher[$ik]['response'];
      }

    }


    if ($response == '') {

      $target_file = 'https://www.googleapis.com/youtube/v3/videos?part=snippet%2Cstatistics&id=' . $id . '&key=' . $youtubeApiKey . '&type=channel&part=snippet';


      $response = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));


      $auxa34 = array('id' => $id, 'response' => $response, 'time' => $_SERVER['REQUEST_TIME']
      );

      $found_cached = false;

      if (!is_array($cacher)) {
        $cacher = array();
      } else {


        foreach ($cacher as $lab => $cach) {
          if ($cach['id'] == $id) {
            $found_cached = true;

            $cacher[$lab] = $auxa34;

            update_option($lab_cacher, $cacher);

            break;
          }
        }


      }

      if ($found_cached == false) {

        array_push($cacher, $auxa34);


        update_option($lab_cacher, $cacher);
      }


    }


    $obj = json_decode($response);


    if ($obj && is_object($obj)) {

      if (isset($obj->items) && isset($obj->items[0]) && isset($obj->items[0]->snippet) && isset($obj->items[0]->snippet->description)) {

        $foutarr['description'] = $obj->items[0]->snippet->description;
        $foutarr['menuDescription'] = $obj->items[0]->snippet->description;
      }

      if (isset($obj->items) && isset($obj->items[0]) && isset($obj->items[0]->statistics) && isset($obj->items[0]->statistics->viewCount)) {

        $foutarr['views'] = $obj->items[0]->statistics->viewCount;
      }
    }


    return $foutarr;


  }

}


if (function_exists('dzsvg_view_parseApi_youtubeParseItems') === false) {
  function dzsvg_view_parseApi_youtubeParseItems(&$its, &$i_for_its, $apiResponseObject, $targetApiCall, $dzsvg, $parseOptions, &$isGoingToCache) {


    if (isset($apiResponseObject->error) && isset($apiResponseObject->error->errors)) {

      echo '<pre class="error dzsvg-error dzsvg-object-error" style="white-space: pre-line; word-break: break-all;">';

      echo $apiResponseObject->error->errors[0]->message;
      echo '<br>' . (esc_html__("Original request", DZSVG_ID)) . ' - ' . $targetApiCall;
      echo '</pre>';
    }


    if (isset($apiResponseObject->items[0]->id->videoId) || isset($apiResponseObject->items[0]->snippet->resourceId->videoId)) {


      foreach ($apiResponseObject->items as $youtubeItemObject) {


        $videoId = '';

        if (isset($youtubeItemObject->id->videoId)) {

          $videoId = $youtubeItemObject->id->videoId;
        } else {

          if (isset($youtubeItemObject->snippet->resourceId->videoId)) {

            $videoId = $youtubeItemObject->snippet->resourceId->videoId;
          }
        }

        if ($videoId == '') {

          error_log('this does not have id ? ' . print_r($youtubeItemObject, true));
          continue;
        }

        if ($dzsvg->mainoptions['youtube_hide_non_embeddable'] == 'on') {

          $videoDetailsObject = dzsvg_view_parseApi_youtubeGetMoreDetails($videoId);


          if ($videoDetailsObject && isset($videoDetailsObject['items']) && isset($videoDetailsObject['items'][0]) && $videoDetailsObject['items'][0]['status']['embeddable'] != 1) {

            continue;
          }
        }


        // -- still playlist


        $its[$i_for_its]['source'] = $videoId;

        if (isset($youtubeItemObject->snippet->thumbnails)) {
          if (isset($youtubeItemObject->snippet->thumbnails->medium)) {
            $its[$i_for_its]['thumbnail'] = $youtubeItemObject->snippet->thumbnails->medium->url;
          }

        }
        $its[$i_for_its]['type'] = "youtube";
        $its[$i_for_its]['permalink'] = "https://www.youtube.com/watch?v=" . $videoId;

        $sanitizedTitle = $youtubeItemObject->snippet->title;
        $lb = array('"', "\r\n", "\n", "\r", "", "`", '???', '');
        $sanitizedTitle = str_replace($lb, ' ', $sanitizedTitle);
        $its[$i_for_its]['title'] = $sanitizedTitle;

        $sanitizedDescription = $youtubeItemObject->snippet->description;
        $lb = array("\r\n", "\n", "\r");
        $sanitizedDescription = str_replace($lb, '<br>', $sanitizedDescription);
        $lb = array('"');
        $sanitizedDescription = str_replace($lb, '&quot;', $sanitizedDescription);


        $sanitizedDescription = '<p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', $sanitizedDescription) . '</p>';

        $its[$i_for_its]['description'] = $sanitizedDescription;
        $its[$i_for_its]['menuDescription'] = $sanitizedDescription;

        if ($parseOptions['enable_outernav_video_author'] == 'on') {
        }
        if ($parseOptions['enable_outernav_video_date'] == 'on') {
        }
        $its[$i_for_its]['upload_date'] = $youtubeItemObject->snippet->publishedAt;
        $its[$i_for_its]['uploader'] = $youtubeItemObject->snippet->channelTitle;


        $i_for_its++;


        // --


        $isGoingToCache = true;

      }

      $isGoingToCache = true;


    } else {

      array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No youtube playlist videos to be found - maybe API key not set ? This is the feed - ' . $targetApiCall) . '</div>');

      try {

        if (isset($apiResponseObject->error)) {
          if ($apiResponseObject->error->errors[0]) {


            array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . $apiResponseObject->error->errors[0]->message . '</div>');
            if (strpos($apiResponseObject->error->errors[0]->message, 'per-IP or per-Referer restriction') !== false) {

              array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__("Suggestion - go to Video Gallery > Settings and enter your YouTube API Key") . '</div>');
            } else {

            }
          }
        }

      } catch (Exception $err) {

      }
    }
  }
}

if (function_exists('dzsvg_view_parseApi_youtubeConstructApiCall') === false) {
  function dzsvg_view_parseApi_youtubeConstructApiCall($apiCallType, $nextPageToken, $dzsvg, $parseOptions = array(), $targetId = '', $per_page = '10', $youtubeApiKey = '') {

    $str_nextPageToken = '';

    if ($nextPageToken && $nextPageToken != DZSVG_API_QUERY_NO_LEFT_PAGES_KEY) {
      $str_nextPageToken = '&pageToken=' . $nextPageToken;
    }


    // -- make the call here
    // -- we can call search as well


    $str_arg_order = '';

    if ($parseOptions['youtube_order'] && $parseOptions['youtube_order'] != 'default') {
      $str_arg_order = '&order=' . $parseOptions['youtube_order'];
    }


    $targetApiCall = '';
    if ($apiCallType == '' || $apiCallType == 'playlist') {

      $targetApiCall .= 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&type=video&videoEmbeddable=true&playlistId=' . $targetId;
    }

    if ($apiCallType == 'search') {

      // -- use sort method for search
      $targetApiCall .= 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=' . '{{targetFeed}}' . '&type=video&videoEmbeddable=true';
    }

    $targetApiCall = str_replace('{{targetFeed}}', $targetId, $targetApiCall);


    $targetApiCall .= '&key=' . $youtubeApiKey . '' . $str_nextPageToken . '&maxResults=' . $per_page . $str_arg_order;

    return $targetApiCall;
  }
}
if (function_exists('dzsvg_view_parseApi_youtubeGetMoreDetails') === false) {
  function dzsvg_view_parseApi_youtubeGetMoreDetails($vid) {

    global $dzsvg;
    if ($dzsvg->mainoptions['youtube_hide_non_embeddable'] == 'on') {

      $video_details_arr = array();


      $option_name_for_vid = 'dzsvg_youtube_video_details_' . $vid;

      $auxcachvid = get_option($option_name_for_vid);
      if ($auxcachvid && $dzsvg->mainoptions['disable_api_caching'] != 'on') {


        try {
          $auxcachvid_ar = json_decode($auxcachvid, true);
          $video_details_arr = $auxcachvid_ar['status'];

        } catch (Exception $e) {

        }
      } else {

        $target_file = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,status&id=' . $vid . '&key=' . $dzsvg->mainoptions['youtube_api_key'];
        $idas = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));

        try {
          $idas_arr = json_decode($idas, true);


          $auxa34 = array(
            'status' => $idas_arr,
            'time' => $_SERVER['REQUEST_TIME'],
          );

          $video_details_arr = $idas_arr;
          update_option($option_name_for_vid, json_encode($auxa34));

        } catch (Exception $e) {

        }


      }


      return $video_details_arr;


    }
    return false;

  }
}
