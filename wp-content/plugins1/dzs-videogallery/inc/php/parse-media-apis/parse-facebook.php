<?php

if (!function_exists('dzsvg_parse_facebook')) {
  function dzsvg_parse_facebook($link, $pargs = array(), &$fout = null) {
    /**
     * @var DZSVideoGallery
     */
    global $dzsvg;


    $margs = array(
      'maxlen' => '50',
      'enable_outernav_video_author' => 'off',
      'enable_outernav_video_date' => 'off',
      'striptags' => 'off',
      'get_full_description' => 'off',
      'type' => 'detect',
      'subtype' => 'detect',
      'query' => '',
    );

    if (!is_array($pargs)) {
      $pargs = array();
    }

    $margs = array_merge($margs, $pargs);

    $its = array();

    $link = str_replace('/videos', '', $link);

    $subtype = '';


    if ($margs['maxlen'] == '') {
      $margs['maxlen'] = '30';
    }


    $max_videos = $margs['maxlen'];
    $original_max_videos = $max_videos;

    $id = 'raywilliamjohnson';

    $isCachedTimeValid = false;


    $id = $margs['facebook_source'];
//		$id = '100000085825669';


    if ($link) {
      $id = $link;
    }


    $id_arr = explode('/', $id);


    if ($id_arr[count($id_arr) - 1]) {
      $id = $id_arr[count($id_arr) - 1];
    } else {
      $id = $id_arr[count($id_arr) - 2];
    }

    $validCacheObject = null;

    $cacheObjects = get_option('dzsvg_cache_facebook_' . ClassDzsvgHelpers::sanitize_forKey($id));

    if ($cacheObjects == false || is_array($cacheObjects) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
      $isCachedTimeValid = false;
    } else {


      $ik = -1;
      $i = 0;
      if ((isset($cacheObjects[$max_videos]['maxlen']) && $cacheObjects[$max_videos]['maxlen'] == $margs['maxlen'])) {
        $validCacheObject = $cacheObjects[$max_videos];
        if ($_SERVER['REQUEST_TIME'] - $validCacheObject['time'] < intval($dzsvg->mainoptions['cache_time'])) {
          $ik = $i;
          if (count($validCacheObject['items'])) {
            $isCachedTimeValid = true;
          }
        }
      }


      if ($isCachedTimeValid) {
        foreach ($validCacheObject['items'] as $lab => $item) {
          if ($lab === 'settings') {
            continue;
          }

          $its[$lab] = $item;
        }
      }

    }


    if ($isCachedTimeValid == false) {
      $app_id = $dzsvg->mainoptions['facebook_app_id'];
      $app_secret = $dzsvg->mainoptions['facebook_app_secret'];


      $posts = null;
      $response = null;

      if ($app_id && $app_secret) {


        if (file_exists(DZSVG_PATH . 'class_parts/src/Facebook/autoload.php')) {

          require_once DZSVG_PATH . 'class_parts/src/Facebook/autoload.php'; // change path as needed


          $fb = new \Facebook\Facebook([
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'default_graph_version' => 'v6.0',
            //'default_access_token' => '{access-token}', // optional
          ]);

// Use one of the helper classes to get a Facebook\Authentication\AccessToken entity.





          $accessToken = $dzsvg->mainoptions['facebook_access_token'];
          $helper = $fb->getRedirectLoginHelper();




          // pictures - https://graph.facebook.com/10155310281108386/thumbnails?access_token=EAAVcLm1RBJ0BAEbWGcoKhkPMMa6ZCKJvz5nnRn1fd2NnoxPIP2AjTQ35OahjZBWBCiqxS3MPCu04cDNApFywZC8koVPgZB8mglpuMzKqIPgBMTRf4FcVC3TldZBrgNZC4BVZBoO8ZBZB2cDZBbVgRMOQgbDdN6AZAzh1gQEcEGQofs9OAZDZD

          if (isset($_GET['state'])) {

            $_SESSION['FBRLH_state'] = sanitize_key($_GET['state']);
          }


          if ($accessToken) {

          } else {


            // here we will try to save access toekn
            try {
              $accessToken = $helper->getAccessToken();
            } catch (Facebook\Exceptions\FacebookResponseException $e) {
              // When Graph returns an error
              echo 'Graph [tried getting access token] returned an error: ' . $e->getMessage();

            } catch (Facebook\Exceptions\FacebookSDKException $e) {
              // When validation fails or other local issues
              echo 'redirect-from-facebook.php - Facebook 26 SDK returned an error: ' . $e->getMessage() . '...' . print_rr($e, true);
              print_rr("__GET__" . print_rr($_GET, true) . "__SESSION__" . print_rr($_SESSION, true));
              print_rr($helper->getPersistentDataHandler());
              print_rr($helper->getError());
            }
          }


          if ($accessToken) {


            if (!isset($accessToken)) {
              if ($helper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Error: " . $helper->getError() . "\n";
                echo "Error Code: " . $helper->getErrorCode() . "\n";
                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                echo "Error Description: " . $helper->getErrorDescription() . "\n";
              } else {
//				header('HTTP/1.0 400 Bad Request');
                echo 'Bad request';
              }
//			exit;
            }


            $from_logged_in_api = true;

            $apiCall = '/' . $id . '/videos?fields=title,picture,description,source,embeddable';
            try {
              // Returns a `Facebook\FacebookResponse` object

              // TODO: we don't need thumbnails for now thumbnails,


              // ?fields=title,picture,description,source,embeddable,embed_html

              $response = $fb->get(
                $apiCall,
                $accessToken
              );
            } catch (Facebook\Exceptions\FacebookResponseException $e) {


              if ($validCacheObject) {
                foreach ($validCacheObject['items'] as $lab => $item) {
                  if ($lab === 'settings') {
                    continue;
                  }

                  $its[$lab] = $item;
                }
              } else {

                echo 'Graph [tried getting videos] returned an error ( id - ' . $id . ' ): ' . $e->getMessage();
                echo '<br>attempted API call was - ( <small>' . $apiCall . '</small> )';
                print_rr($response);
              }



            } catch (Facebook\Exceptions\FacebookSDKException $e) {
              echo 'Facebook SDK returned an error: ' . $e->getMessage();

            }


// Or if you have the latest dev version of the official SDK

            if ($dzsvg->mainoptions['debug_mode'] == 'on') {
              // -- debug call

              $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . sprintf(__('facebook response 3', 'dzsvg')) . '</div>
<div class="toggle-content">';

              $fout .= '<br>$response is..->' . (print_rr($response, array('echo' => false, 'encode_html' => true)));
              $fout .= '</div></div>';
              ClassDzsvgHelpers::enqueueDzsToggle();
            }


            try {


              if ($response) {

                $graphEdge = $response->getGraphEdge();

                $posts = $graphEdge->asArray();


              }



            } catch (Exception $e) {


              try {


                $graphNode = $response->getGraphNode();


                print_rr($graphNode);



              } catch (Exception $e) {
                error_log("facebook api error" . print_r($e, true));
              }


            }



            if ($dzsvg->mainoptions['debug_mode'] == 'on') {


              // -- debug call


              $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . sprintf(__('facebook $posts', 'dzsvg')) . '</div>
<div class="toggle-content">';

              $fout .= '<br>id is..->' . (print_rr($id, array('echo' => false, 'encode_html' => true)));
              $fout .= '<br>$posts is..->' . (print_rr($posts, array('echo' => false, 'encode_html' => true)));
              $fout .= '</div></div>';
              ClassDzsvgHelpers::enqueueDzsToggle();
            }


            if ($posts) {
              $breaker = 1;
              $vimeo_response = null;
              $nextPageToken = 'start';

              $i_for_its = 0;


              while ($breaker < 10 && $nextPageToken !== '') {


                $ida = '';
                $from_logged_in_api = true;


                // -- sanitizing
                if ($max_videos == '') {
                  $max_videos = '25';
                }


                if ($max_videos === 'all') {
                  $max_videos = 96;
                }


                $sort_call = '';

                $page_call = '';


                if (isset($vimeo_response['body']['data'])) {
                  $ida = $vimeo_response['body']['data'];
                }
                $from_logged_in_api = true;


                if ($dzsvg->mainoptions['debug_mode'] == 'on') {
                  echo 'debug mode: mode vimeo album target file - ' . $id
                    . '<br>cached - ' . $isCachedTimeValid . '<br>vimeo_response is:';
                }


                $idar = $posts;
                if (is_array($idar) && count($idar)) {

                  foreach ($idar as $item) {


                    if (is_object($item)) {
                      $item = (array)$item;
                    }

                    $auxa = array();
                    if (isset($item['uri'])) {
                      $auxa = explode('/', $item['uri']);
                    }
                    if (isset($item['url'])) {
                      $auxa = explode('/', $item['url']);
                    }
                    $its[$i_for_its]['source'] = $item['source'];



                    $its[$i_for_its]['thumbnail'] = $item['picture'];
                    $its[$i_for_its]['thethumb'] = $item['picture'];


                    $its[$i_for_its]['type'] = "video";

                    if (isset($item['description'])) {
                      $its[$i_for_its]['description'] = $item['description'];
                    }


                    $aux = '';
                    if (isset($item['title'])) {
                      $aux = $item['title'];
                    } else {
                      $aux = 'title';
                    }


                    $lb = array('"', "\r\n", "\n", "\r", "&", "`", '???', "'");
                    $aux = str_replace($lb, ' ', $aux);
                    $its[$i_for_its]['title'] = $aux;


                    // -- description

                    $aux = 'description';
                    if (isset($item['description'])) {
                      $aux = $item['description'];
                    } else {
                      $aux = 'description';
                    }
                    if ($margs['striptags'] == 'on') {
                      $aux = strip_tags($aux);


                      $lb = array("\r\n", "\n", "\r");
                      $aux = str_replace($lb, '<br>', $aux);
                      $lb = array('"');
                      $aux = str_replace($lb, '&quot;', $aux);
                      $lb = array("'");
                      $aux = str_replace($lb, '&#39;', $aux);
                      $its[$i_for_its]['description'] = $aux;
                      $its[$i_for_its]['menuDescription'] = $aux;
                    }
                    $i_for_its++;
                  }
                } else {


                }


                $jida = $ida;
                //        if (is_array($ida)) {
                //            $jida = json_encode($ida);
                //        }


                $nextPageToken = '';


                $breaker++;
              }


            }


            // -- finished adding items
            if ($dzsvg->mainoptions['disable_api_caching'] != 'on') {
              $cache_mainaux = array();

              if (count($its)) {

                $cache_aux = array(
                  'items' => $its
                , 'time' => $_SERVER['REQUEST_TIME']
                , 'from_logged_in_api' => $from_logged_in_api
                , 'maxlen' => $original_max_videos
                , 'maxlen_from_margs' => $margs['max_videos']
                );
                $cache_mainaux[$original_max_videos] = $cache_aux;
                update_option('dzsvg_cache_facebook_' . ClassDzsvgHelpers::sanitize_forKey($id), $cache_mainaux);
              }
            }




          } else {

            $helper = $fb->getRedirectLoginHelper();

            $permissions = ['email']; // Optional permissions
            $loginUrl = $helper->getLoginUrl(dzs_curr_url(), $permissions);


            if (isset($_SESSION) && is_array($_SESSION)) {

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

            echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
          }


        }

      } else {
        echo '<div class="warning">' . esc_html__("You need to set up your facebook api in Video Gallery > Settings > Facebook", 'dzsvg') . '</div>';
      }


    }






    return $its;

    $targetfeed = '';

    if (strpos($link, '/') !== false) {
      $q_strings = explode('/', $link);


      if ($subtype == 'user_channel') {

        $targetfeed = $q_strings[count($q_strings) - 1];


      }
      if ($subtype == 'playlist') {

        $targetfeed = DZSHelpers::get_query_arg($link, 'list');


      }
      if ($subtype == 'search') {

        $targetfeed = DZSHelpers::get_query_arg($link, 'search_query');


      }

    } else {
      $targetfeed = $link;
    }

    if ($margs['query']) {
      if ($targetfeed == '') {
        $targetfeed = $margs['query'];
      }
    }





    $max_videos = $margs['max_videos'];


    if ($max_videos == 'all') {
      $max_videos = 50;
    }


    // --- user channel
    if ($subtype == 'user_channel') {


      $cacheObjects = get_option('dzsvg_cache_ytuserchannel');

      $isCachedTimeValid = false;


      if ($cacheObjects == false || is_array($cacheObjects) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
        $isCachedTimeValid = false;
      } else {




        $ik = -1;
        $i = 0;
        for ($i = 0; $i < count($cacheObjects); $i++) {
          if ($cacheObjects[$i]['id'] == $targetfeed) {
            if ($_SERVER['REQUEST_TIME'] - $cacheObjects[$i]['time'] < $dzsvg->mainoptions['cache_time']) {
              $ik = $i;


              $isCachedTimeValid = true;
              break;
            }
          }
        }


        if ($isCachedTimeValid) {
          foreach ($cacheObjects[$ik]['items'] as $lab => $item) {
            if ($lab === 'settings') {
              continue;
            }

            $its[$lab] = $item;
          }

          return $its;
        }

      }


      // -- use sort method for search
      $target_file = 'https://www.googleapis.com/youtube/v3/search?q=' . $targetfeed . '&key=' . $dzsvg->mainoptions['youtube_api_key'] . '&type=channel&part=snippet' . $str_arg_order;


      $ida = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));


      if ($dzsvg->mainoptions['debug_mode'] == 'on') {
        if ($fout != null) {

        }
        $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('first search for channel id', 'dzsvg') . '</div>
<div class="toggle-content">';
        $fout .= 'debug mode: target file ( ' . $target_file . ' )  ida is is...<br>';
        $fout .= '</div></div>';
        ClassDzsvgHelpers::enqueueDzsToggle();
      }


      $i = 0;

      if ($ida) {

        $obj = json_decode($ida);


        if ($dzsvg->mainoptions['debug_mode'] == 'on') {

        }

        if ($obj && is_object($obj)) {


          if (isset($obj->items[0]->id->channelId)) {


            $channel_id = $obj->items[0]->id->channelId;


            $breaker = 0;
            $nextPageToken = DZSVG_API_QUERY_NO_LEFT_PAGES_KEY;

            while ($breaker < 10 || $nextPageToken !== '') {


              $str_nextPageToken = '';

              if ($nextPageToken && $nextPageToken != DZSVG_API_QUERY_NO_LEFT_PAGES_KEY) {
                $str_nextPageToken = '&pageToken=' . $nextPageToken;
              }


              if ($dzsvg->mainoptions['youtube_api_key'] == '') {
                $dzsvg->mainoptions['youtube_api_key'] = DZSVG_YOUTUBE_SAMPLE_API_KEY[1];
              }

              // -- inside parse facebook
              $target_file = 'https://www.googleapis.com/youtube/v3/search?key=' . $dzsvg->mainoptions['youtube_api_key'] . '&channelId=' . $channel_id . '&part=snippet&type=video' . $str_nextPageToken . '&maxResults=' . $max_videos . $str_arg_order;



              $ida = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));


              if ($ida) {

                $obj = json_decode($ida);



                if ($dzsvg->mainoptions['debug_mode'] == 'on') {
                  $fout .= 'fout on';
                  $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('then we know the real query  ', 'dzsvg') . '</div>
<div class="toggle-content">';
                  $fout .= ' youtube user channel - let us see the actual channel id targetfile - ' . $target_file . ' <br>';
                  $fout .= ' channelId - <strong>' . $channel_id . '</strong> <br>';
                  $fout .= 'debug mode: $obj - ' . print_rr($obj, array('echo' => false, 'encode_html' => true)) . '<br>';
                  $fout .= '</div></div>';
                }


                if ($obj && is_object($obj) && isset($obj->error) && isset($obj->error->message)) {

                  echo '<div class="error">' . $obj->error->message . '</div>';
                }

                if ($obj && is_object($obj)) {


                  if (isset($obj->items[0]->id->videoId)) {


                    foreach ($obj->items as $ytitem) {


                      if (isset($ytitem->id->videoId) == false) {
                        echo 'this does not have id ? ';
                        continue;
                      }
                      $its[$i]['source'] = $ytitem->id->videoId;
                      $its[$i]['thumbnail'] = $ytitem->snippet->thumbnails->medium->url;
                      $its[$i]['type'] = "youtube";
                      $its[$i]['permalink'] = "https://www.youtube.com/watch?v=" . $its[$i]['source'];

                      $aux = $ytitem->snippet->title;
                      $lb = array('"', "\r\n", "\n", "\r", "&", "", "`", '???', "'", '');
                      $aux = str_replace($lb, ' ', $aux);
                      $its[$i]['title'] = $aux;

                      $aux = $ytitem->snippet->description;
                      $lb = array("\r\n", "\n", "\r");
                      $aux = str_replace($lb, '<br>', $aux);
                      $lb = array('"');
                      $aux = str_replace($lb, '&quot;', $aux);
                      $lb = array("'");
                      $aux = str_replace($lb, '&#39;', $aux);


                      $auxcontent = '<p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', $aux) . '</p>';

                      $its[$i]['description'] = $auxcontent;
                      $its[$i]['menuDescription'] = $auxcontent;

                      if ($margs['enable_outernav_video_author'] == 'on') {
//                        echo 'ceva';
                      }
                      if ($margs['enable_outernav_video_date'] == 'on') {
//                        echo 'ceva';
                      }
                      $its[$i]['uploader'] = $ytitem->snippet->channelTitle;
                      $its[$i]['upload_date'] = $ytitem->snippet->publishedAt;


                      if ($margs['get_full_description'] == 'on') {
                        $arr = dzsvg_parse_youtube_video($its[$i]['source'], $margs, $fout);


                        if (is_array($arr)) {
                          $its[$i] = array_merge($its[$i], $arr);
                        }
                      }

                      $i++;

//                                            if ($i > $max_videos + 1){ break; }

                    }


                  } else {

                    array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('( user channel ) No videos to be found - ') . $target_file . '</div>');
                  }
                } else {

                  array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('Object channel is not JSON...') . '</div>');
                }
              } else {

                array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('Cannot get info from YouTube API about channel - ') . $target_file . '</div>');
              }


              if ($max_videos === 'all') {

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

              $breaker++;
            }


            $sw34 = false; // -- true if added to cache
            $auxa34 = array('id' => $targetfeed,
              'items' => $its,
              'time' => $_SERVER['REQUEST_TIME']
            , 'maxlen' => $max_videos

            );


            $cacheObjects = false;
            if (!is_array($cacheObjects)) {
              $cacheObjects = array();
            } else {


              foreach ($cacheObjects as $lab => $cach) {
                if ($cach['id'] == $targetfeed) {
                  $sw34 = true;

                  $cacheObjects[$lab] = $auxa34;

                  update_option('dzsvg_cache_ytuserchannel', $cacheObjects);

                  break;
                }
              }


            }

            if ($sw34 == false) {

              array_push($cacheObjects, $auxa34);


              update_option('dzsvg_cache_ytuserchannel', $cacheObjects);
            }


          } else {

            array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('Cannot access channel ID, this is feed - ') . $target_file . '</div>');
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
        } else {

          array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('Object is not JSON...') . '</div>');
        }
      }

    }
    // --- END user channel


    // --- youtube playlist
    if ($subtype == 'playlist') {


      $len = count($its) - 1;
      for ($i = 0; $i < $len; $i++) {
        unset($its[$i]);
      }


      $cacheObjects = get_option('dzsvg_cache_ytplaylist');

      $isCachedTimeValid = false;
      $found_for_cache = false;


      if ($cacheObjects == false || is_array($cacheObjects) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
        $isCachedTimeValid = false;
      } else {



        $ik = -1;
        $i = 0;
        for ($i = 0; $i < count($cacheObjects); $i++) {
          if ($cacheObjects[$i]['id'] == $targetfeed) {
            if (isset($cacheObjects[$i]['maxlen']) && $cacheObjects[$i]['maxlen'] == $max_videos) {
              if ($_SERVER['REQUEST_TIME'] - $cacheObjects[$i]['time'] < intval($dzsvg->mainoptions['cache_time'])) {
                $ik = $i;

//                                echo 'yabebe';
                $isCachedTimeValid = true;
                break;
              }
            }

          }
        }


        if ($isCachedTimeValid) {

          foreach ($cacheObjects[$ik]['items'] as $lab => $item) {
            if ($lab === 'settings') {
              continue;
            }

            $its[$lab] = $item;

//                        echo 'from cache';
          }

        }
      }


      if ($dzsvg->mainoptions['debug_mode'] == 'on') {
        echo 'is cached - ' . $isCachedTimeValid . ' | ';
      }


      // -- youtube playlist
      if (!$isCachedTimeValid) {
        if (isset($max_videos) == false || $max_videos == '') {
          $max_videos = 50;
        }
        $yf_maxi = $max_videos;

        if ($max_videos == 'all') {
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
            $dzsvg->mainoptions['youtube_api_key'] = 'AIzaSyCtrnD7ll8wyyro5f1LitPggaSKvYFIvU4';
          }


          $target_file = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=' . $targetfeed . '&key=' . $dzsvg->mainoptions['youtube_api_key'] . '' . $str_nextPageToken . '&maxResults=' . $yf_maxi;



          if ($dzsvg->mainoptions['debug_mode'] == 'on') {
            echo 'target file - ' . $target_file;
          }


          $ida = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));


          if ($ida) {

            $obj = json_decode($ida);


            if ($obj && is_object($obj)) {


              if ($obj && is_object($obj)) {

                if (isset($obj->items[0]->snippet->resourceId->videoId)) {


                  foreach ($obj->items as $ytitem) {


                    if (isset($ytitem->snippet->resourceId->videoId) == false) {
                      echo 'this does not have id ? ';
                      continue;
                    }


                    $its[$i_for_its]['source'] = $ytitem->snippet->resourceId->videoId;

                    if (isset($ytitem->snippet->thumbnails)) {

                      $its[$i_for_its]['thumbnail'] = $ytitem->snippet->thumbnails->medium->url;
                    }
                    $its[$i_for_its]['type'] = "youtube";
                    $its[$i_for_its]['permalink'] = "https://www.youtube.com/watch?v=" . $its[$i_for_its]['source'];

                    $aux = $ytitem->snippet->title;
                    $lb = array('"', "\r\n", "\n", "\r", "&", "", "`", '???', "'", '');
                    $aux = str_replace($lb, ' ', $aux);
                    $its[$i_for_its]['title'] = $aux;

                    $aux = $ytitem->snippet->description;
                    $lb = array("\r\n", "\n", "\r");
                    $aux = str_replace($lb, '<br>', $aux);
                    $lb = array('"');
                    $aux = str_replace($lb, '&quot;', $aux);
                    $lb = array("'");
                    $aux = str_replace($lb, '&#39;', $aux);


                    $auxcontent = '<p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', $aux) . '</p>';

                    $its[$i_for_its]['description'] = $auxcontent;
                    $its[$i_for_its]['menuDescription'] = $auxcontent;

                    if ($margs['enable_outernav_video_author'] == 'on') {
//                        echo 'ceva';
                    }
                    if ($margs['enable_outernav_video_date'] == 'on') {
//                        echo 'ceva';
                    }
                    $its[$i_for_its]['upload_date'] = $ytitem->snippet->publishedAt;
                    $its[$i_for_its]['uploader'] = $ytitem->snippet->channelTitle;

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
            }


            if ($max_videos === 'all') {

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
            'id' => $targetfeed
          , 'items' => $its
          , 'time' => $_SERVER['REQUEST_TIME']
          , 'maxlen' => $max_videos

          );

          if (!is_array($cacheObjects)) {
            $cacheObjects = array();
          } else {


            foreach ($cacheObjects as $lab => $cach) {
              if ($cach['id'] == $targetfeed) {
                $sw34 = true;

                $cacheObjects[$lab] = $auxa34;

                update_option('dzsvg_cache_ytplaylist', $cacheObjects);

                break;
              }
            }


          }

          if ($sw34 == false) {

            array_push($cacheObjects, $auxa34);


            update_option('dzsvg_cache_ytplaylist', $cacheObjects);
          }
        }
      }


    }
    // --- END youtube playlist


    // --- youtube search query
    if ($subtype == 'search') {


      $len = count($its) - 1;
      for ($i = 0; $i < $len; $i++) {
        unset($its[$i]);
      }


      $cacheObjects = get_option('dzsvg_facebook_cache_search');

      $isCachedTimeValid = false;
      $found_for_cache = false;


      if ($cacheObjects == false || is_array($cacheObjects) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on') {
        $isCachedTimeValid = false;
      } else {



        $ik = -1;
        $i = 0;
        for ($i = 0; $i < count($cacheObjects); $i++) {
          if ($cacheObjects[$i]['id'] == $targetfeed) {
            if ($_SERVER['REQUEST_TIME'] - $cacheObjects[$i]['time'] < 3600) {
              $ik = $i;

//                                echo 'yabebe';
              $isCachedTimeValid = true;
              break;
            }
          }
        }


        if ($isCachedTimeValid) {

          foreach ($cacheObjects[$ik]['items'] as $lab => $item) {
            if ($lab === 'settings') {
              continue;
            }

            $its[$lab] = $item;

          }

        }
      }


      //-- youtube search
      if (!$isCachedTimeValid) {
        if (isset($max_videos) == false || $max_videos == '') {
          $max_videos = 50;
        }
        $yf_maxi = $max_videos;

        if ($max_videos == 'all') {
          $yf_maxi = 50;
        }


        $breaker = 0;

        $i_for_its = 0;
        $nextPageToken = DZSVG_API_QUERY_NO_LEFT_PAGES_KEY;

        while ($breaker < 5 || $nextPageToken !== '') {


          $str_nextPageToken = '';

          if ($nextPageToken && $nextPageToken != DZSVG_API_QUERY_NO_LEFT_PAGES_KEY) {
            $str_nextPageToken = '&pageToken=' . $nextPageToken;
          }

//                echo '$breaker is '.$breaker;


          $targetfeed = str_replace(' ', '+', $targetfeed);


          if ($dzsvg->mainoptions['youtube_api_key'] == '') {
            $dzsvg->mainoptions['youtube_api_key'] = 'AIzaSyCtrnD7ll8wyyro5f1LitPggaSKvYFIvU4';
          }

          $target_file = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=' . $targetfeed . '&type=video&key=' . $dzsvg->mainoptions['youtube_api_key'] . $str_nextPageToken . '&videoEmbeddable=true&maxResults=' . $yf_maxi . $str_arg_order;


          $ida = DZSHelpers::get_contents($target_file, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));

//            echo 'ceva'.$ida;

          if ($ida) {

            $obj = json_decode($ida);


            if ($obj && is_object($obj)) {


              if (isset($obj->items[0]->id->videoId)) {


                foreach ($obj->items as $ytitem) {


                  if (isset($ytitem->id->videoId) == false) {
                    echo 'this does not have id ? ';
                    continue;
                  }
                  $its[$i_for_its]['source'] = $ytitem->id->videoId;
                  $its[$i_for_its]['thethumb'] = $ytitem->snippet->thumbnails->medium->url;
                  $its[$i_for_its]['type'] = "youtube";
                  $its[$i_for_its]['permalink'] = "https://www.youtube.com/watch?v=" . $its[$i_for_its]['source'];

                  $aux = $ytitem->snippet->title;
                  $lb = array('"', "\r\n", "\n", "\r", "&", "", "`", '???', "'", '');
                  $aux = str_replace($lb, ' ', $aux);
                  $its[$i_for_its]['title'] = $aux;

                  $aux = $ytitem->snippet->description;
                  $lb = array("\r\n", "\n", "\r");
                  $aux = str_replace($lb, '<br>', $aux);
                  $lb = array('"');
                  $aux = str_replace($lb, '&quot;', $aux);
                  $lb = array("'");
                  $aux = str_replace($lb, '&#39;', $aux);


                  $auxcontent = '<p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', $aux) . '</p>';

                  $its[$i_for_its]['description'] = $auxcontent;
                  $its[$i_for_its]['menuDescription'] = $auxcontent;

                  if ($margs['enable_outernav_video_author'] == 'on') {
//                        echo 'ceva';
                  }
                  $its[$i_for_its]['uploader'] = $ytitem->snippet->channelTitle;
                  $its[$i_for_its]['upload_date'] = $ytitem->snippet->publishedAt;
                  if ($margs['enable_outernav_video_date'] == 'on') {
//                        echo 'ceva';
                  }

                  $i_for_its++;

                  $found_for_cache = true;

                }


              } else {

                array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No youtube keyboard videos to be found') . '</div>');
              }

            }


            if ($max_videos === 'all') {

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


          } else {

            array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No youtube keyboards ida found ' . $target_file) . '</div>');
          }
          $breaker++;
        }


        if ($found_for_cache) {

          $sw34 = false;
          $auxa34 = array(
            'id' => $targetfeed
          , 'items' => $its
          , 'time' => $_SERVER['REQUEST_TIME']
          , 'maxlen' => $max_videos

          );

          if (!is_array($cacheObjects)) {
            $cacheObjects = array();
          } else {


            foreach ($cacheObjects as $lab => $cach) {
              if ($cach['id'] == $targetfeed) {
                $sw34 = true;
                $cacheObjects[$lab] = $auxa34;
                update_option('dzsvg_cache_ytkeywords', $cacheObjects);
                break;
              }
            }


          }


          if ($sw34 == false) {

            array_push($cacheObjects, $auxa34);


            update_option('dzsvg_facebook_cache_search', $cacheObjects);
          }
        }


      }
      // -- end not cached


      if ($dzsvg->mainoptions['debug_mode'] == 'on') {
//		                $fout.= 'fout on';
        $fout .= '<div class="dzstoggle toggle1" rel="">
<div class="toggle-title" style="">' . esc_html__('search results query', 'dzsvg') . '</div>
<div class="toggle-content">';
        $fout .= ' youtube user channel - let us see the actual channel id targetfile - ' . $target_file . ' <br>';
        $fout .= ' cached - (' . $isCachedTimeValid . ')<br>  - ';
        $fout .= ' $found_for_cache - (' . $found_for_cache . ')<br>  - ';
        $fout .= 'debug mode: $its - ' . print_rr($its, array('echo' => false, 'encode_html' => true)) . '<br>';
        $fout .= '</div></div>';
        ClassDzsvgHelpers::enqueueDzsToggle();
      }


    }
    // --- END youtube search query


    return $its;
  }

}



