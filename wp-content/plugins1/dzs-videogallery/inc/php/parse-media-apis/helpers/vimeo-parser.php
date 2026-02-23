<?php

function dzsvg_parser_vimeo_getThumbnailUri($vimeoItem) {

  global $dzsvg;

  $vimeo_quality_ind = 2;

  if ($dzsvg->mainoptions['vimeo_thumb_quality'] == 'medium') {
    $vimeo_quality_ind = 3;
  }
  if ($dzsvg->mainoptions['vimeo_thumb_quality'] == 'high') {
    $vimeo_quality_ind = 4;
  }

  $theThumbUri = '';
  if (isset($vimeoItem['pictures']) && is_object($vimeoItem['pictures'])) {
    $vimeoItem['pictures'] = (array)$vimeoItem['pictures'];
    if (is_object($vimeoItem['pictures']['sizes'])) {
      $vimeoItem['pictures']['sizes'] = (array)$vimeoItem['pictures']['sizes'];
    }

    if (is_object($vimeoItem['pictures']['sizes'][$vimeo_quality_ind])) {
      $vimeoItem['pictures']['sizes'][$vimeo_quality_ind] = (array)$vimeoItem['pictures']['sizes'][$vimeo_quality_ind];
    }
    $theThumbUri = $vimeoItem['pictures']['sizes'][$vimeo_quality_ind]['link'];
  } else {


    if (isset($vimeoItem['thumbnail_large'])) {

      $theThumbUri = $vimeoItem['thumbnail_large'];
    }
    if (isset($vimeoItem['pictures']) && isset($vimeoItem['pictures']['sizes'])) {


      if (isset($vimeoItem['pictures']['sizes'][$vimeo_quality_ind]) && isset($vimeoItem['pictures']['sizes'][$vimeo_quality_ind]['link'])) {

        $theThumbUri = $vimeoItem['pictures']['sizes'][$vimeo_quality_ind]['link'];
      } else {
        if (isset($vimeoItem['pictures']['sizes'][$vimeo_quality_ind - 1]) && isset($vimeoItem['pictures']['sizes'][$vimeo_quality_ind - 1]['link'])) {

          $theThumbUri = $vimeoItem['pictures']['sizes'][$vimeo_quality_ind - 1]['link'];
        } else {
          if (isset($vimeoItem['pictures']['sizes'][$vimeo_quality_ind - 2]) && isset($vimeoItem['pictures']['sizes'][$vimeo_quality_ind - 2]['link'])) {

            $theThumbUri = $vimeoItem['pictures']['sizes'][$vimeo_quality_ind - 2]['link'];
          }
        }
      }

//                        echo $its[$i]['thethumb'];


    }
  }

  return $theThumbUri;

}

/**
 * parses vimeo api response to dzsvg items
 * @param array $apiResponse
 * @param DZSVideoGallery $dzsvg
 * @param array $parseOptions
 * @param string $vimeoApiCall
 * @return void
 */
function dzsvg_parser_vimeo_loggedIn($apiResponse, $dzsvg, $parseOptions, $vimeoApiCall, &$dzsvgItems, &$totalIndex) {


  if (!is_array($apiResponse)) {
    $apiResponse = (array)$apiResponse;
  }


  $apiResponseArray = array_merge(array(), $apiResponse);


  // -- authentificated CALL


  if (is_array($apiResponseArray) && count($apiResponseArray)) {
    foreach ($apiResponseArray as $vimeoItem) {
      if (is_object($vimeoItem)) {
        $vimeoItem = (array)$vimeoItem;
      }

      if (!isset($vimeoItem['uri'])) {
        continue;
      }


      if ($dzsvg->mainoptions['vimeo_show_only_public_videos'] == 'on') {
        if (isset($vimeoItem['privacy']) && isset($vimeoItem['privacy']['embed']) && $vimeoItem['privacy']['embed'] == 'private') {

          continue;
        }
      }

      $urlExplodeSlashArray = array();
      if (isset($vimeoItem['uri'])) {
        $urlExplodeSlashArray = explode('/', $vimeoItem['uri']);
      }
      if (isset($vimeoItem['url'])) {
        $urlExplodeSlashArray = explode('/', $vimeoItem['url']);
      }

      $dzsvgItems[$totalIndex]['source'] = $urlExplodeSlashArray[count($urlExplodeSlashArray) - 1];


      $dzsvgItems[$totalIndex]['type'] = "vimeo";
      $dzsvgItems[$totalIndex]['thumbnail'] = dzsvg_parser_vimeo_getThumbnailUri($vimeoItem);
      $dzsvgItems[$totalIndex]['thethumb'] = $dzsvgItems[$totalIndex]['thumbnail'];
      $dzsvgItems[$totalIndex]['permalink'] = "https://vimeo.com/" . $dzsvgItems[$totalIndex]['source'];


      if (isset($vimeoItem['name'])) {
        $aux = $vimeoItem['name'];

      }
      if (isset($vimeoItem['title'])) {
        $aux = $vimeoItem['title'];
      }


      $lb = array('"', "\r\n", "\n", "\r", "&", "`", '???', "'");
      $aux = str_replace($lb, ' ', $aux);
      $dzsvgItems[$totalIndex]['title'] = $aux;


      $dzsvgItems[$totalIndex]['all_description'] = $vimeoItem['description'];

      $description = '';

      if (isset($vimeoItem['description'])) {
        $description = $vimeoItem['description'];
      }

      if ($parseOptions['striptags'] == 'on') {
        $description = strip_tags($description);
      }


      $description = wp_kses($description, (DZSVG_HTML_ALLOWED_TAGS));

      if (isset($margs['desc_count']) && $margs['desc_count'] && $margs['desc_count'] != 'all') {
        $description = DZSHelpers::wp_get_excerpt(-1, array(
          'maxlen' => $margs['desc_count'],
          'content' => $description,
          'aftercutcontent_html' => ' [ ... ] ',
        ));
      }


      $description = ClassDzsvgHelpers::sanitizeApiDescriptionToHtml($description);
      $dzsvgItems[$totalIndex]['description'] = $description;
      $dzsvgItems[$totalIndex]['menuDescription'] = $description;
      $totalIndex++;

    }

  } else {

    array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No items found ? This is the feed - ' . $vimeoApiCall) . '</div>');

  }


}

/**
 * @param $apiResponse
 * @param DZSVideoGallery $dzsvg
 * @param $vimeoApiCall
 * @return void
 */
function dzsvg_parser_vimeo_unAuthentificated($apiResponse, $dzsvg, $vimeoApiCall, &$its, &$totalIndex) {

  $dzsvgItems = array();

  if (is_array($apiResponse) && count($apiResponse)) {

    $totalIndex = 0;
    foreach ($apiResponse as $vimeoItem) {


      $its[$totalIndex]['source'] = $vimeoItem->id;
      $its[$totalIndex]['thumbnail'] = $vimeoItem->thumbnail_medium;


      if ($dzsvg->mainoptions['vimeo_thumb_quality'] == 'high') {
        $its[$totalIndex]['thumbnail'] = $vimeoItem->thumbnail_large;
      }


      $its[$totalIndex]['type'] = "vimeo";

      $aux = $vimeoItem->title;
      $lb = array('"', "\r\n", "\n", "\r", "&", "`", '???', "'");
      $aux = str_replace($lb, ' ', $aux);
      $its[$totalIndex]['title'] = $aux;

      $aux = $vimeoItem->description;
      $lb = array("\r\n", "\n", "\r", "&", '???');
      $aux = str_replace($lb, ' ', $aux);
      $lb = array('"');
      $aux = str_replace($lb, '&quot;', $aux);
      $lb = array("'");
      $aux = str_replace($lb, '&#39;', $aux);
      $its[$totalIndex]['menuDescription'] = $aux;


      $totalIndex++;
    }


  } else {

    array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('No items found ? This is the feed - ' . $vimeoApiCall) . '</div>');

  }


}


if (!function_exists('dzsvg_parse_vimeo_its')) {

  function dzsvg_parse_vimeo__getFromApi(&$its, $targetSource, $maxVideos, $parseOptions, $nextPageToken, $breakerCount, $api_customerKey, $api_customerSecret, $api_token, &$fout, $isCached, $type, $apiRequest) {

    global $dzsvg;


    $isFromLoggedInApi = dzsvg_parse_vimeo__isApiLoggedIn();


    $breakerCount = 1;


    $vimeoApiUnauthentificatedUri = "https://vimeo.com/api/v2/" . $targetSource . "/videos.json";

    $apiResponse = '';
    if ($isFromLoggedInApi) {
      // -- authentificated call


      if (!class_exists('Vimeo')) {
        require_once(DZSVG_PATH . 'inc/vimeoapi/vimeo.php');
      }

      $query_maxVideos = $maxVideos;

      // -- sanitizing
      if ($query_maxVideos == '') {
        $query_maxVideos = '25';
      }


      $original_max_videos = $query_maxVideos;

      if ($query_maxVideos === 'all') {
        $query_maxVideos = 96;
      }


      $query_sortCall = '';

      $query_pageCall = '';

      if ($parseOptions['vimeo_sort'] && $parseOptions['vimeo_sort'] != 'default') {
        $query_sortCall .= '&sort=' . $parseOptions['vimeo_sort'];
      }

      if ($nextPageToken && $nextPageToken != 'start') {
        $query_pageCall = '&page=' . $breakerCount;
      }


      // -- Do an authentication call
      $vimeo = new Vimeo($api_customerKey, $api_customerSecret);
      $vimeo->setToken($api_token);

      $request = '/users/' . $targetSource . '/videos?per_page=' . $query_maxVideos . $query_sortCall . $query_pageCall;
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


    $totalIndex = 0;


    $apiRequest = $isFromLoggedInApi ? $apiRequest : $vimeoApiUnauthentificatedUri;
    dzsvg_parse_vimeo_its($its, $isFromLoggedInApi, $apiResponse, $totalIndex, $parseOptions, $apiRequest);


  }
}


if (!function_exists('dzsvg_parse_vimeo_its')) {
  /**
   * @param array $its
   * @param boolean $isFromLoggedInApi
   * @param $apiResponse
   * @param $totalIndex
   * @param $margs
   * @param $vimeoApiCall
   */
  function dzsvg_parse_vimeo_its(&$its, $isFromLoggedInApi, $apiResponse, &$totalIndex, $margs, $vimeoApiCall) {


    global $dzsvg;


    if ($isFromLoggedInApi) {

      if (!is_array($apiResponse)) {
        $apiResponse = (array)$apiResponse;
      }
      $apiResponser = array_merge(array(), $apiResponse);

      // -- authentificated CALL


      dzsvg_parser_vimeo_loggedIn($apiResponse, $dzsvg, $margs, $vimeoApiCall, $its, $totalIndex);


    } else {

      // -- simple call

      if (!is_object($apiResponse) && !is_array($apiResponse)) {
        $idar = json_decode($apiResponse); // -- vmuser
      } else {
        $idar = $apiResponse;
      }


      dzsvg_parser_vimeo_unAuthentificated($idar, $dzsvg, $vimeoApiCall, $its, $totalIndex);
    }

  }

}
