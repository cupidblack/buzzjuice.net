<?php

/**
 * @param array $its
 * @param string $targetSource
 * @param boolean $isFromLoggedInApi
 * @param $original_max_videos
 * @param $maxlen_from_margs
 * @param string $cacheDbOptionName
 */
function dzsvg_parser_cacheAdder($its, $targetSource, $isFromLoggedInApi, $original_max_videos, $maxlen_from_margs, $cacheDbOptionName) {


  if (is_array($its) && count($its)) {

    $cacheMainObj = get_option($cacheDbOptionName);
    if(!is_array($cacheMainObj)){
      $cacheMainObj = array();

    }else{
      foreach ($cacheMainObj as $lab => $cacheObj){
        $mins = intval(abs($cacheObj['time'] - time()) / 60);
        if($mins>(60*24*21)){
          unset($cacheMainObj[$lab]);
        }

        $cacheMainObj = array_values($cacheMainObj);
      }
    }
    $cacheObject = array(
      'items' => $its,
      'id' => $targetSource,
      'time' => $_SERVER['REQUEST_TIME'],
      'from_logged_in_api' => $isFromLoggedInApi,
      'maxlen' => $original_max_videos,
      'maxlen_from_margs' => $maxlen_from_margs
    );
    array_push($cacheMainObj, $cacheObject);
    update_option($cacheDbOptionName, $cacheMainObj);
  }

}