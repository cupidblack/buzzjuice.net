import {
  playlist_get_real_responsive_ratio, playlist_navigation_mode_one__set_players_data,

} from "./_playlistHelpers";
import {detect_video_type_and_source, getDataOrAttr} from "../_dzsvg_helpers";
import {PLAYER_DEFAULT_RESPONSIVE_RATIO} from "../../configs/Constants";

/**
 *
 * transfer feed items
 * @param {DzsVideoGallery} selfClass
 */
export function buildPlaylist(selfClass) {


  let itemsLength = selfClass.$feedItemsContainer.find('.vplayer-tobe').length;
  let o = selfClass.initOptions;

  selfClass.Navigation.addNavigationItems(selfClass.$feedItemsContainer);

  for (let i = 0; i < itemsLength; i++) {
    var $currentItemFeed = selfClass.$feedItemsContainer.find('.vplayer-tobe').eq(i);

    var vpRealSrc = getDataOrAttr($currentItemFeed, 'data-sourcevp');
    var sourceAndType = detect_video_type_and_source(vpRealSrc);
    vpRealSrc = sourceAndType.source;
    $currentItemFeed.data('dzsvg-curatedtype-from-gallery', sourceAndType.type);
    if (sourceAndType.type === 'youtube') {
      if (sourceAndType.source) {
        $currentItemFeed.data('dzsvg-curatedid-from-gallery', sourceAndType.source);
      }
    }


    vpRealSrc = getDataOrAttr($currentItemFeed, 'data-sourcevp');
    sourceAndType = detect_video_type_and_source(vpRealSrc);
    vpRealSrc = sourceAndType.source;
    const curatedTypeFromGallery = sourceAndType.type;

    $currentItemFeed.data('dzsvg-curatedtype-from-gallery', curatedTypeFromGallery);
    $currentItemFeed.data('dzsvg-curatedid-from-gallery', sourceAndType.source);


    // -- this is inside video gallery
    if ((curatedTypeFromGallery === 'youtube' || curatedTypeFromGallery === 'vimeo' || curatedTypeFromGallery === 'facebook' || curatedTypeFromGallery === 'inline')
      && o.videoplayersettings.responsive_ratio === 'detect' && !($currentItemFeed.attr('data-responsive_ratio'))) {
      if (!$currentItemFeed.attr('data-responsive_ratio') || $currentItemFeed.attr('data-responsive_ratio') === 'detect') {
        $currentItemFeed.attr('data-responsive_ratio', String(PLAYER_DEFAULT_RESPONSIVE_RATIO));
      }
      if (curatedTypeFromGallery === 'inline') {
        setTimeout(function () {
          selfClass.apiResponsiveRationResize(PLAYER_DEFAULT_RESPONSIVE_RATIO * selfClass.videoAreaWidth);
        }, 3003);
      }
      $currentItemFeed.attr('data-responsive_ratio-not-known-for-sure', 'on');  // -- we set this until we know the responsive ratio for sure , 0.562 is just 16/9 ratio so should fit to most videos

      if (o.php_media_data_retriever) {
        playlist_get_real_responsive_ratio(i, selfClass);
      }
    }


    var $cacheMenuItem = selfClass.$navigationItemsContainer.children().last();

    if (o.settings_mode === 'normal') {
      if (o.mode_normal_video_mode === 'one') {
        playlist_navigation_mode_one__set_players_data($cacheMenuItem);
      }
    }

  }



}