import {loadScriptIfItDoesNotExist} from "../js_common/_dzs_helpers";
import {VIDEO_TYPES} from "../configs/_playerSettings";
import {ConstantsDzsvg} from "../configs/Constants";

/**
 *
 * @param {jQuery} $elements
 * @param {object} cssProps
 */
export function view_setCssPropsForElement($elements, cssProps){
  $elements.css(cssProps);
}

/**
 *
 * @param cssVal
 * @return {string}
 */
export function view_cssConvertForPx(cssVal){
  if(cssVal===''){
    return cssVal;
  }
  if(['auto','px','%',''].indexOf(cssVal)===-1){
    return cssVal + 'px';
  }

  return cssVal;
}





export function promise_allDependenciesMet(selfClass, completeFn) {

  const baseUrl = window.dzsvg_settings && window.dzsvg_settings.libsUri ? window.dzsvg_settings.libsUri : '';


  if (selfClass.is360) {

    loadScriptIfItDoesNotExist(baseUrl + 'parts/player/player-360.js', 'dzsvp_player_init360').then(r => {
      completeFn();
    })
  }

  if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
    loadScriptIfItDoesNotExist(ConstantsDzsvg.YOUTUBE_IFRAME_API, 'dzsvg_yt_ready').then(r => {
      completeFn();
    });
  }
  if (!selfClass.is360 && selfClass.dataType !== VIDEO_TYPES.YOUTUBE) {

    completeFn();
  }
}