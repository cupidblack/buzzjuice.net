
import {is_ios} from '../js_common/_dzs_helpers';
import {PLAYER_DEFAULT_TIMEOUT} from "../configs/Constants";
export function checkForAdAlongTheWay(selfClass, argPerc){

  if (selfClass.ad_array.length) {
    for (let i2 = 0; i2 < selfClass.ad_array.length; i2++) {
      if (selfClass.ad_array[i2].time < argPerc) {
        return Number(selfClass.ad_array[i2].time);
      }
    }
  }

  return null;
}

export function adEnd (selfClass){

  if (selfClass.$adContainer.children().get(0) && selfClass.$adContainer.children().get(0).api_destroy_listeners) {
    selfClass.$adContainer.children().get(0).api_destroy_listeners();
  }

  selfClass.player_user_had_first_interaction();
  selfClass.playMovie();
  selfClass.cthis.addClass('ad-transitioning-out');


  if (selfClass.initOptions.gallery_object) {
    if (selfClass.initOptions.gallery_object.get(0) && selfClass.initOptions.gallery_object.get(0).api_ad_unblock_navigation) {
      selfClass.initOptions.gallery_object.get(0).api_ad_unblock_navigation();
    }
  }

  setTimeout(function () {

    selfClass.cthis.removeClass('ad-playing');
    selfClass.cthis.removeClass('ad-transitioning-out');
    selfClass.$adContainer.children().remove();
    selfClass.isAdPlaying = false;
  }, PLAYER_DEFAULT_TIMEOUT)
}

/**
 *
 * @param {DzsVideoPlayer} selfClass
 * @returns {boolean}
 */
export function check_if_ad_must_be_played (selfClass) {
  if (selfClass.cthis.attr('data-adsource') && selfClass.cthis.data('adplayed') !== 'on') {


    if (is_ios()) {
      setTimeout(function () {

        selfClass.pauseMovie({
          'called_from': 'check_if_ad_must_be_played'
        });

        if (selfClass.dataType === 'youtube') {
          selfClass._videoElement.stopVideo();
        }

        selfClass.seek_to_perc(0);





      }, 1000);
    }

    var o = selfClass.initOptions;

    if (o.gallery_object && o.gallery_object.get(0) && o.gallery_object.get(0).api_setup_ad) {

      o.gallery_object.get(0).api_setup_ad(cthis);

      selfClass.cthis.data('adplayed', 'on');

      return false;
    }

  }

}
