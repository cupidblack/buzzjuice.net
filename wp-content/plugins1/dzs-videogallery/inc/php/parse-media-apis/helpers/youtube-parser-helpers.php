<?php

/**
 * try to get a more relevant channelId
 * @param string $targetfeed
 * @param string $str_youtubeApiKey
 * @param DZSVideoGallery $dzsvg
 * @return null
 */
function dzsvg_parser_youtube_get_channel_id($targetfeed, $str_youtubeApiKey, $dzsvg){

  $channel_id = null;
  // -- search first the channel id
  $targetApiCall = 'https://www.googleapis.com/youtube/v3/search?q=' . $targetfeed . '&key=' . $str_youtubeApiKey . '&type=channel&part=snippet';


  $apiResponseJson = DZSHelpers::get_contents($targetApiCall, array('force_file_get_contents' => $dzsvg->mainoptions['force_file_get_contents']));



  if ($apiResponseJson) {
    $apiResponseObject = json_decode($apiResponseJson);
    if ($apiResponseObject && is_object($apiResponseObject)) {
      if (isset($apiResponseObject->items[0]->id)) {
        $channel_id = $apiResponseObject->items[0]->id;
      } else {
        if (isset($apiResponseObject->items[0]->id->channelId)) {
          $channel_id = $apiResponseObject->items[0]->id->channelId;
        } else {
          try {
            if (isset($apiResponseObject->error)) {
              if ($apiResponseObject->error->errors[0]) {
                array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . $apiResponseObject->error->errors[0]->message . '</div>');
                if (strpos($apiResponseObject->error->errors[0]->message, 'per-IP or per-Referer restriction') !== false) {

                  array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__("Suggestion - go to Video Gallery > Settings and enter your YouTube API Key", DZSVG_ID) . '</div>');
                } else {

                }
              }
            }

          } catch (Exception $err) {

          }
        }
      }
    } else {

      array_push($dzsvg->arr_api_errors, '<div class="dzsvg-error">' . esc_html__('Object is not JSON...', DZSVG_ID) . '</div>');
    }
  }


  if (isset($channel_id->channelId)) {
    $channel_id = $channel_id->channelId;
  }

  return $channel_id;
}