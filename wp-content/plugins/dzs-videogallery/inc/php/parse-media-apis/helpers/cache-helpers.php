<?php


/**
 * mutates
 * @param array $its
 */
function dzsvg_cache_purgeItsItems(&$its) {

  $len = count($its) - 1;
  for ($i = 0; $i < $len; $i++) {
    unset($its[$i]);
  }
}

/**
 * @param $cachedContent
 * @param $dzsvg
 * @param $targetSource
 * @param $max_videos
 * @param $parseOptions
 * @return int|bool
 */
function dzsvg_cache_helpers_isCacheValid($cachedContent, $dzsvg, $targetSource, $max_videos, $parseOptions, $MAX_CACHE_TIME = 7200) {

  if ($MAX_CACHE_TIME < 1000) {
    $MAX_CACHE_TIME = 1000;
  }


  if (!($cachedContent == false || is_array($cachedContent) == false || $dzsvg->mainoptions['disable_api_caching'] == 'on')) {
    for ($i = 0; $i < count($cachedContent); $i++) {

      $cachedMaxLen = null;
      $cachedMaxLenFromMars = null;

      if (isset($cachedContent[$i]['maxlen']) && $cachedContent[$i]['maxlen']) {
        $cachedMaxLen = $cachedContent[$i]['maxlen'];
      }
      if (isset($cachedContent[$i]['maxlen_from_margs']) && $cachedContent[$i]['maxlen_from_margs']) {
        $cachedMaxLenFromMars = $cachedContent[$i]['maxlen_from_margs'];
      }


      if ($cachedContent[$i]['id'] == $targetSource) {
        if (($cachedMaxLen == $max_videos) || ($cachedMaxLenFromMars == $parseOptions['max_videos'])) {
          if (abs(intval($_SERVER['REQUEST_TIME']) - intval($cachedContent[$i]['time'])) < intval($MAX_CACHE_TIME)) {
            if (isset($cachedContent[$i]['items']) && count($cachedContent[$i]['items'])) {
              return $i;
            }
          }
        }
      }
    }
  }

  return false;

}