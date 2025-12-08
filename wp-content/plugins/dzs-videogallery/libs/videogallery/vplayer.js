(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.VIEW_LAYOUT_BUILDER_FEED_CLASS = exports.PLAYLIST_VIEW_FULLSCREEN_CLASS = exports.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON = exports.PLAYLIST_SCROLL_TOP_OFFSET = exports.PLAYLIST_PAGINATION_QUERY_ARG = exports.PLAYLIST_MODE_WALL__ITEM_CLASS = exports.PLAYLIST_HEIGHT_IS_WINDOW_MAX_OFFSET = exports.PLAYLIST_DEFAULT_TIMEOUT = exports.PLAYER_REGEX_SUBTITLE = exports.PLAYER_DEFAULT_TIMEOUT = exports.PLAYER_DEFAULT_RESPONSIVE_RATIO = exports.DEFAULT_MENU_ITEM_STRUCTURE = exports.ConstantsDzsvg = void 0;
const ConstantsDzsvg = exports.ConstantsDzsvg = {
  THREEJS_LIB_URL: 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r73/three.min.js',
  THREEJS_LIB_ORBIT_URL: 'https://s3-us-west-2.amazonaws.com/s.cdpn.io/211120/orbitControls.js',
  YOUTUBE_IFRAME_API: 'https://www.youtube.com/iframe_api',
  VIMEO_IFRAME_API: 'https://player.vimeo.com/api/player.js',
  DEBUG_STYLING: 'background-color: #4422aa;',
  DEBUG_STYLING_2: 'color: #ffdada; background-color: #da3333;',
  ANIMATIONS_DURATION: 303,
  DELAY_MINUSCULE: 3
};
const VIEW_LAYOUT_BUILDER_FEED_CLASS = exports.VIEW_LAYOUT_BUILDER_FEED_CLASS = 'feed-layout-builder--menu-items';
const PLAYER_REGEX_SUBTITLE = exports.PLAYER_REGEX_SUBTITLE = /([0-9](?:[0-9]|:|,| )*)[–|-]*(?:(?:\&gt;)|>) *([0-9](?:[0-9]|:|,| )*)[\n|\r]([\s\S]*?)[\n|\r]/g;

/**
 * used if we don't have VIEW_LAYOUT_BUILDER_FEED_CLASS
 * @type {string}
 */
const DEFAULT_MENU_ITEM_STRUCTURE = exports.DEFAULT_MENU_ITEM_STRUCTURE = `<div class="layout-builder--structure layout-builder--menu-items--layout-default layout-builder--main-con " style="display: flex; gap: 10px; padding: 10px; align-items: center;">
  <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container" style="flex: 0 0 60px;">
  <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container" style="padding-top: 100%; position:relative;">
    <div class="layout-builder--item layout-builder--item--2312321 layout-builder--item--type-thumbnail navigation-type-image divimage" style="position:absolute;top:0; left:0; width: 100%; height: 100%; background-image: url({{layout-builder.replace-thumbnail-url}})"></div>
    </div>
  </div>
  <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container" style="flex: 100; white-space: normal; min-width: 150px;">
    <div class="layout-builder--item layout-builder--item--3321321 layout-builder--item--type-title" style="font-weight: bold; margin-bottom: 5px; padding-right: 10px;;">{{layout-builder.replace-title}}</div>
    <div class="layout-builder--item layout-builder--item--21312321 layout-builder--item--type-menu-description" style="font-weight: 400; line-height: 1.5; padding-right: 10px;">{{layout-builder.replace-menu-description}}</div>
  </div>
</div>`;
const PLAYER_DEFAULT_RESPONSIVE_RATIO = exports.PLAYER_DEFAULT_RESPONSIVE_RATIO = 0.5625;
const PLAYER_DEFAULT_TIMEOUT = exports.PLAYER_DEFAULT_TIMEOUT = 304;
const PLAYLIST_HEIGHT_IS_WINDOW_MAX_OFFSET = exports.PLAYLIST_HEIGHT_IS_WINDOW_MAX_OFFSET = 310;
const PLAYLIST_DEFAULT_TIMEOUT = exports.PLAYLIST_DEFAULT_TIMEOUT = 305;
const PLAYLIST_SCROLL_TOP_OFFSET = exports.PLAYLIST_SCROLL_TOP_OFFSET = 120;
const PLAYLIST_MODE_WALL__ITEM_CLASS = exports.PLAYLIST_MODE_WALL__ITEM_CLASS = 'vgwall-item';
const PLAYLIST_PAGINATION_QUERY_ARG = exports.PLAYLIST_PAGINATION_QUERY_ARG = 'dzsvgpage';
const PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON = exports.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON = 'dzsvg-btn-pagination--load-more';
const PLAYLIST_VIEW_FULLSCREEN_CLASS = exports.PLAYLIST_VIEW_FULLSCREEN_CLASS = 'is_fullscreen';

},{}],2:[function(require,module,exports){
"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.defaultPlayerSettings=exports.VIDEO_TYPES=exports.PLAYER_STATES=void 0;const PLAYER_STATES=exports.PLAYER_STATES={INITIALIZED:"dzsvp-inited",LOADED:"dzsvp-loaded",TO_BE_INITIALIZED:"vplayer-tobe"},VIDEO_TYPES=exports.VIDEO_TYPES={YOUTUBE:"youtube",VIMEO:"vimeo",SELF_HOSTED:"selfHosted"},defaultPlayerSettings=exports.defaultPlayerSettings={type:"detect",init_on:"init",autoplay:"off",autoplayWithVideoMuted:"auto",user_action:"noUserActionYet",first_video_from_gallery:"off",old_curr_nr:-1,gallery_object:null,parent_player:null,design_skin:"skin_default",design_background_offsetw:0,defaultvolume:"last",settings_youtube_usecustomskin:"on",settings_ios_usecustomskin:"on",settings_ios_playinline:"on",cueVideo:"on",preload_method:"metadata",settings_disableControls:"off",settings_hideControls:"off",vimeo_color:"ffffff",vimeo_title:"1",vimeo_avatar:"1",vimeo_badge:"1",vimeo_byline:"1",mode_normal_video_mode:"one",vimeo_is_chromeless:"off",is_ad:"off",ad_link:"",ad_show_markers:"off",ads_player_mode:"differentPlayer",settings_suggestedQuality:"hd720",settings_currQuality:"HD",settings_enableTags:"on",design_enableProgScrubBox:"default",settings_disableVideoArray:"off",settings_makeFunctional:!1,settings_video_overlay:"on",settings_big_play_btn:"off",video_description_style:"none",htmlContent:"",extra_controls:"",settings_disable_mouse_out:"off",settings_disable_mouse_out_for_fullscreen:"off",controls_fscanvas_bg:"#aaa",controls_fscanvas_hover_bg:"#ddd",touch_play_inline:"on",google_analytics_send_play_event:"off",settings_video_end_reset_time:"on",settings_trigger_resize:"0",settings_mouse_out_delay:100,settings_mouse_out_delay_for_fullscreen:1100,playfrom:"default",settings_subtitle_file:"",responsive_ratio:"default",action_video_play:null,action_video_view:null,action_video_end:null,action_video_contor_5secs:null,action_video_contor_10secs:null,action_video_contor_60secs:null,try_to_pause_zoomsounds_players:"off",end_exit_fullscreen:"on",extra_classes:"",embed_code:"",default_playbackrate:"1"};
},{}],3:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.add_query_arg = add_query_arg;
exports.can_history_api = can_history_api;
exports.can_translate = can_translate;
exports.formatTime = formatTime;
exports.format_to_seconds = format_to_seconds;
exports.get_query_arg = get_query_arg;
exports.is_android = is_android;
exports.is_ios = is_ios;
exports.is_mobile = is_mobile;
exports.is_safari = is_safari;
exports.is_touch_device = is_touch_device;
exports.loadScriptIfItDoesNotExist = void 0;
exports.sanitizeToCssPx = sanitizeToCssPx;
exports.stringUtilGetSkinFromClass = stringUtilGetSkinFromClass;
/**
 * formats the time
 * @param {number} arg
 * @returns {string}
 */
function formatTime(arg) {
  var s = Math.round(arg);
  var m = 0;
  if (s > 0) {
    while (s > 59) {
      m++;
      s -= 60;
    }
    return String((m < 10 ? "0" : "") + m + ":" + (s < 10 ? "0" : "") + s);
  } else {
    return "00:00";
  }
}

/**
 *
 * @param {string} stringUri
 * @param {string} key
 * @returns {string}
 */
function get_query_arg(stringUri, key) {
  if (stringUri.indexOf(key + '=') > -1) {
    var regexS = "[?&]" + key + "=.+";
    var regex = new RegExp(regexS);
    var regtest = regex.exec(stringUri);
    if (regtest != null) {
      var splitterS = regtest[0];
      if (splitterS.indexOf('&') > -1) {
        var aux = splitterS.split('&');
        splitterS = aux[1];
      }
      var splitter = splitterS.split('=');
      return splitter[1];
    }
  }
}

/**
 *
 * @param {string|number} arg
 * @returns {string|*}
 */
function sanitizeToCssPx(arg) {
  if (String(arg).indexOf('%') > -1 || String(arg).indexOf('em') > -1 || String(arg).indexOf('px') > -1 || String(arg).indexOf('auto') > -1) {
    return arg;
  }
  return arg + 'px';
}
function format_to_seconds(arg) {
  var argsplit = String(arg).split(':');
  argsplit.reverse();
  var secs = 0;
  if (argsplit[0]) {
    argsplit[0] = String(argsplit[0]).replace(',', '.');
    secs += Number(argsplit[0]);
  }
  if (argsplit[1]) {
    secs += Number(argsplit[1]) * 60;
  }
  if (argsplit[2]) {
    secs += Number(argsplit[2]) * 60;
  }
  return secs;
}
function add_query_arg(purl, key, value) {
  key = encodeURIComponent(key);
  value = encodeURIComponent(value);
  var s = purl;
  var pair = key + "=" + value;
  var r = new RegExp("(&|\\?)" + key + "=[^\&]*");
  s = s.replace(r, "$1" + pair);
  if (s.indexOf(key + '=') > -1) {} else {
    if (s.indexOf('?') > -1) {
      s += '&' + pair;
    } else {
      s += '?' + pair;
    }
  }
  if (value === 'NaN') {
    var regex_attr = new RegExp('[\?|\&]' + key + '=' + value);
    s = s.replace(regex_attr, '');
  }
  return s;
}
function is_touch_device() {
  return !!('ontouchstart' in window);
}
function can_history_api() {
  return !!(window.history && history.pushState);
}

/**
 * *deprecated
 * @returns {*|boolean}
 */
function can_translate() {
  return is_chrome() || is_safari();
}
function is_safari() {
  return navigator.userAgent.toLowerCase().indexOf('safari') > -1;
}
;
function is_mobile() {
  return is_ios() || is_android();
}
function is_android() {
  var ua = navigator.userAgent.toLowerCase();
  return ua.indexOf("android") > -1;
}
function is_ios() {
  return navigator.platform.indexOf("iPhone") !== -1 || navigator.platform.indexOf("iPod") !== -1 || navigator.platform.indexOf("iPad") !== -1 || navigator.platform.indexOf("MacIntel") !== -1 && is_touch_device();
}

/**
 *
 * @param {string} scriptSrc if no script src - it will just look for var
 * @param {string} checkForVar must be on window property
 * @returns {Promise<any>}
 */
const loadScriptIfItDoesNotExist = (scriptSrc, checkForVar) => {
  const CHECK_INTERVAL = 50;
  const TIMEOUT_MAX = 5000;
  let checkInterval = 0;
  const loadScript = (scriptSrc, resolve, reject) => {
    var script = document.createElement('script');
    script.onload = function () {
      resolve('loadfromload');
    };
    script.onerror = function () {
      reject();
    };
    script.src = scriptSrc;
    document.head.appendChild(script);
  };
  return new Promise((resolve, reject) => {
    let isAlreadyLoaded = false;
    let isGoingToLoadScript = false;
    function checkIfVarExists() {
      if (window[checkForVar]) {
        clearInterval(checkInterval);
        resolve('loadfromvar');
        return true;
      }
      return false;
    }
    isAlreadyLoaded = checkIfVarExists();
    if (!isAlreadyLoaded) {
      isGoingToLoadScript = true;
      checkInterval = setInterval(checkIfVarExists, CHECK_INTERVAL);
      setTimeout(() => {
        clearInterval(checkInterval);
        reject('timeout');
      }, TIMEOUT_MAX);
    }
    if (!checkForVar) {
      isGoingToLoadScript = true;
    }
    if (!scriptSrc) {
      isGoingToLoadScript = false;
    }
    if (isGoingToLoadScript) {
      clearInterval(checkInterval);
      loadScript(scriptSrc, resolve, reject);
    }
  });
};
exports.loadScriptIfItDoesNotExist = loadScriptIfItDoesNotExist;
function stringUtilGetSkinFromClass(cclass) {
  var arr = /(skin.*?)( |$)/.exec(cclass);
  if (arr && arr[1]) {
    return arr[1];
  }
  return '';
}

},{}],4:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.configureAudioPlayerOptionsInitial = configureAudioPlayerOptionsInitial;
exports.convertPluginOptionsToFinalOptions = convertPluginOptionsToFinalOptions;
exports.detect_videoTypeAndSourceForElement = detect_videoTypeAndSourceForElement;
exports.detect_video_type_and_source = detect_video_type_and_source;
exports.dzsvgExtraWindowFunctions = dzsvgExtraWindowFunctions;
exports.dzsvg_call_video_when_ready = dzsvg_call_video_when_ready;
exports.dzsvg_check_multisharer = dzsvg_check_multisharer;
exports.extractOptionsFromPlayer = extractOptionsFromPlayer;
exports.fullscreen_status = fullscreen_status;
exports.getDataOrAttr = getDataOrAttr;
exports.init_navigationOuter = init_navigationOuter;
exports.is_autoplay_and_muted = is_autoplay_and_muted;
exports.pauseDzsapPlayers = pauseDzsapPlayers;
exports.playerHandleDeprecatedAttrSrc = playerHandleDeprecatedAttrSrc;
exports.player_assert_autoplay = player_assert_autoplay;
exports.player_controls_generatePlayCon = player_controls_generatePlayCon;
exports.player_setQualityLevels = player_setQualityLevels;
exports.player_setupQualitySelector = player_setupQualitySelector;
exports.registerAuxjQueryExtends = registerAuxjQueryExtends;
exports.reinitPlayerOptions = reinitPlayerOptions;
exports.sanitizeDataAdArrayStringToArray = sanitizeDataAdArrayStringToArray;
exports.sanitize_to_youtube_id = sanitize_to_youtube_id;
exports.setup_videogalleryCategories = setup_videogalleryCategories;
exports.tagsSetupDom = tagsSetupDom;
exports.vimeo_do_command = vimeo_do_command;
exports.youtube_sanitize_url_to_id = youtube_sanitize_url_to_id;
var _player_setupAd = require("../js_player/_player_setupAd");
var _Constants = require("../configs/Constants");
var _dzsvg_svgs = require("./_dzsvg_svgs");
var _dzs_helpers = require("../js_common/_dzs_helpers");
function player_setQualityLevels(selfClass) {
  var $temp_qualitiesFromFeed = selfClass.cthis.find('.dzsvg-feed-quality');
  if ($temp_qualitiesFromFeed.length) {
    selfClass.cthis.addClass('has-multiple-quality-levels');
    var $qualitySelector = selfClass.cthis.find('.quality-selector');
    var str_qualitiesTooltip = $qualitySelector.find('.dzsvg-tooltip').html();
    let struct_qualityOptions = '';
    var added = false;
    var curr_qual_added = false;
    $temp_qualitiesFromFeed.each(function () {
      var _t2 = $(this);
      struct_qualityOptions += '<div class="quality-option';
      if (_t2.attr('data-source') === selfClass.dataSrc) {
        struct_qualityOptions += ' active';
        added = true;
      }
      struct_qualityOptions += '" data-val="' + _t2.attr('data-label') + '" data-source="' + selfClass.dataSrc + '">' + _t2.attr('data-label') + '</div>';
    });
    if (added === false) {
      struct_qualityOptions += '<div class="quality-option active ';
      struct_qualityOptions += '" data-val="' + selfClass.initOptions.settings_currQuality + '" data-source="' + selfClass.dataSrc + '">' + selfClass.initOptions.settings_currQuality + '</div>';
    }
    if (str_qualitiesTooltip) {
      str_qualitiesTooltip = str_qualitiesTooltip.replace('{{quality-options}}', struct_qualityOptions);
      $qualitySelector.find('.dzsvg-tooltip').html(str_qualitiesTooltip);
    } else {
      console.warn('no aux ? ', str_qualitiesTooltip, $qualitySelector);
    }
  }
}

/**
 *
 * @returns {number}
 */
function fullscreen_status() {
  if (document.fullscreenElement !== null && typeof document.fullscreenElement !== "undefined") {
    return 1;
  } else if (document.webkitFullscreenElement && typeof document.webkitFullscreenElement !== "undefined") {
    return 1;
  } else if (document.mozFullScreenElement && typeof document.mozFullScreenElement !== "undefined") {
    return 1;
  }
  ;
  return 0;
}
function is_chrome() {
  return navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
}
;
function player_controls_generatePlayCon(o) {
  var structPlayControls = '';
  structPlayControls = '<div class="playSimple dzsvgColorForFills">';
  if (o.design_skin == 'skin_bigplay_pro') {
    structPlayControls += _dzsvg_svgs.svg_play_simple_skin_bigplay_pro;
  }
  if (o.design_skin == 'skin_aurora' || o.design_skin == 'skin_bigplay' || o.design_skin == 'skin_avanti' || o.design_skin == 'skin_default' || o.design_skin == 'skin_pro' || o.design_skin == 'skin_white') {
    structPlayControls += _dzsvg_svgs.svg_aurora_play_btn;
  }
  structPlayControls += '</div><div class="pauseSimple dzsvgColorForFills">';
  if (o.design_skin == 'skin_aurora' || o.design_skin == 'skin_pro' || o.design_skin == 'skin_bigplay' || o.design_skin == 'skin_avanti' || o.design_skin == 'skin_default' || o.design_skin == 'skin_white') {
    structPlayControls += _dzsvg_svgs.svg_pause_simple_skin_aurora;
  }
  structPlayControls += '</div>';
  structPlayControls += '<div class="dzsvg-player--replay-btn dzsvgColorForFills">';
  structPlayControls += _dzsvg_svgs.svgReplayIcon;
  structPlayControls += '</div>';
  return structPlayControls;
}
function dzsvg_call_video_when_ready(o, selfClass, init_readyVideo, vimeo_is_ready, inter_videoReadyState) {
  const _videoElement = selfClass._videoElement;
  if (o.type === 'youtube' && _videoElement.getPlayerState) {
    init_readyVideo(selfClass);
  }
  if (o.cueVideo != 'on' && (o.type == 'selfHosted' || o.type == 'audio') && Number(_videoElement.readyState) >= 2) {
    init_readyVideo(selfClass, {
      'called_from': 'check_videoReadyState'
    });
    return false;
  }
  if (o.type == 'vimeo' && o.vimeo_is_chromeless == 'on') {
    if (vimeo_is_ready) {
      init_readyVideo(selfClass);
      return false;
    }
  }
  if (o.type == 'audio') {
    if ((0, _dzs_helpers.is_mobile)()) {
      if (Number(_videoElement.readyState) >= 1) {
        init_readyVideo(selfClass);
        return false;
      }
    }
    if (Number(_videoElement.readyState) >= 3) {
      clearInterval(inter_videoReadyState);
      init_readyVideo(selfClass, {
        'called_from': 'check_videoReadyState'
      });
      return false;
    }
  }
  if (o.type === 'selfHosted') {
    if ((0, _dzs_helpers.is_ios)()) {
      if (Number(_videoElement.readyState) >= 1) {
        init_readyVideo(selfClass);
        return false;
      }
    }
    if ((0, _dzs_helpers.is_android)()) {
      if (Number(_videoElement.readyState) >= 2) {
        init_readyVideo(selfClass);
        return false;
      }
    }
    if (Number(_videoElement.readyState) >= 3 || o.preload_method === 'none') {
      clearInterval(inter_videoReadyState);
      init_readyVideo(selfClass, {
        'called_from': 'check_videoReadyState'
      });
      return false;
    }
  }

  // --- WORKAROUND __ for some reason ios default browser would not go over video ready state 1

  if (o.type === 'dash') {
    clearInterval(inter_videoReadyState);
    init_readyVideo(selfClass, {
      'called_from': 'check_videoReadyState'
    });
  }
}
function dzsvg_check_multisharer() {}
function sanitize_to_youtube_id(arg = '') {
  var fourArr = null;
  if (arg) {
    arg = detect_video_type_and_source(arg).source;
  }
  return arg;
}

/**
 *
 * @param _c the video player element
 * @param attr attribute
 * @returns {null|jQuery|undefined|*}
 */
function getDataOrAttr(_c, attr) {
  if (_c.data && typeof _c.data(attr) != 'undefined') {
    return _c.data(attr);
  }
  if (_c.attr && typeof _c.attr(attr) != 'undefined') {
    return _c.attr(attr);
  }
  return null;
}
function detect_videoTypeAndSourceForElement(_el) {
  if (_el.data('originalPlayerAttributes')) {
    return _el.data('originalPlayerAttributes');
  }
  var dataSrc = getDataOrAttr(_el, 'data-sourcevp');
  var forceType = getDataOrAttr(_el, 'data-type') ? getDataOrAttr(_el, 'data-type') : '';
  return detect_video_type_and_source(dataSrc, forceType);
}

/**
 * detect video type and source
 * @param {string} dataSrc
 * @param forceType we might want to force the type if we know it
 * @param cthis
 * @returns {{source: *, playFrom: null, type: string}}
 */
function detect_video_type_and_source(dataSrc, forceType = null, cthis = null) {
  dataSrc = String(dataSrc);
  var playFrom = null;
  var type = 'selfHosted';
  var source = dataSrc;
  if (dataSrc.indexOf('youtube.com/watch?') > -1 || dataSrc.indexOf('youtube.com/embed') > -1 || dataSrc.indexOf('youtu.be/') > -1) {
    type = 'youtube';
    var aux = /http(?:s?):\/\/(?:www\.)?youtu(?:be\.com\/watch\?v=|\.be\/)([\w\-\_]*)(&(amp;)?‌​[\w\?‌​=]*)?/g.exec(dataSrc);
    if ((0, _dzs_helpers.get_query_arg)(dataSrc, 't')) {
      playFrom = (0, _dzs_helpers.get_query_arg)(dataSrc, 't');
    }
    if (aux && aux[1]) {
      source = aux[1];
    } else {
      // -- let us try youtube embed
      source = dataSrc.replace(/http(?:s?):\/\/(?:www\.)?youtu(?:be\.com\/watch\?v=|\.be\/|be\.com)\/embed\//g, '');
    }
  }
  if (dataSrc.indexOf('<iframe') > -1) {
    type = 'inline';
  }
  // todo: php turn into content
  if (cthis && cthis.find('.feed-dzsvg--inline-content').length && cthis.find('.feed-dzsvg--inline-content').eq(0).html().indexOf('<iframe') > -1) {
    type = 'inline';
  }
  if (dataSrc.indexOf('vimeo.com/') > -1) {
    type = 'vimeo';
    var aux = /(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:[a-zA-Z0-9_\-]+)?/g.exec(dataSrc);
    if (aux && aux[1]) {
      source = aux[1];
    }
  }
  if (dataSrc.indexOf('.mp4') > -1) {
    type = 'selfHosted';
  }
  if (dataSrc && dataSrc.indexOf('.mpd') > dataSrc.length - 5) {
    type = 'dash';
  }
  if (forceType && forceType !== 'detect') {
    type = forceType;
  }
  if (!playFrom) {
    if (cthis && cthis.attr('data-play_from')) {
      playFrom = cthis.attr('data-play_from');
    }
  }
  return {
    type,
    source,
    playFrom
  };
}
function sanitizeDataAdArrayStringToArray(aux) {
  var ad_array = null;
  try {
    // temp - try to remove slashes manually
    aux = aux.replace(/{\\"/g, '{"');
    aux = aux.replace(/\\":/g, '":');
    aux = aux.replace(/:\\"/g, ':"');
    aux = aux.replace(/\\",/g, '",');
    aux = aux.replace(/\\"}/g, '"}');
    aux = aux.replace(/,\\"/g, ',"');
    ad_array = JSON.parse(aux);
  } catch (err) {
    console.log('ad array parse error', aux);
  }
  return ad_array;
}
function is_autoplay_and_muted(autoplay, o) {
  return 1 && autoplay === 'on' && o.autoplayWithVideoMuted === 'on' && o.user_action === 'noUserActionYet' || o.defaultvolume === 0 && o.defaultvolume !== '';
}
function setup_videogalleryCategories(arg) {
  var ccat = jQuery(arg);
  var currCatNr = -1;
  ccat.find('.gallery-precon').each(function () {
    var _t = jQuery(this);
    _t.css({
      'display': 'none'
    });
    ccat.find('.the-categories-con').append('<span class="a-category">' + _t.attr('data-category') + '</span>');
  });
  ccat.find('.the-categories-con').find('.a-category').eq(0).addClass('active');
  ccat.find('.the-categories-con').find('.a-category').on('click', click_category);
  function click_category() {
    var _t = jQuery(this);
    var ind = _t.parent().children('.a-category').index(_t);
    gotoCategory(ind);
    setTimeout(function () {
      jQuery(window).trigger('resize');
    }, 100);
  }
  var i2 = 0;
  ccat.find('.gallery-precon').each(function () {
    var _t = jQuery(this);
    _t.find('.pagination-number').each(function () {
      var _t2 = jQuery(this);
      var auxurl = _t2.attr('href');
      auxurl = (0, _dzs_helpers.add_query_arg)(auxurl, ccat.attr('id') + '_cat', NaN);
      auxurl = (0, _dzs_helpers.add_query_arg)(auxurl, ccat.attr('id') + '_cat', i2);
      _t2.attr('href', auxurl);
    });
    i2++;
  });
  var tempCat = 0;
  if ((0, _dzs_helpers.get_query_arg)(window.location.href, ccat.attr('id') + '_cat')) {
    tempCat = Number((0, _dzs_helpers.get_query_arg)(window.location.href, ccat.attr('id') + '_cat'));
  }
  ccat.get(0).api_goto_category = gotoCategory;
  gotoCategory(tempCat, {
    'called_from': 'init'
  });
  function gotoCategory(arg, pargs) {
    var margs = {
      'called_from': 'default'
    };
    if (pargs) {
      margs = jQuery.extend(margs, pargs);
    }
    if (currCatNr > -1 && ccat.find('.gallery-precon').eq(currCatNr).find('.videogallery').eq(0).get(0) != undefined && ccat.find('.gallery-precon').eq(currCatNr).find('.videogallery').eq(0).get(0).external_handle_stopCurrVideo != undefined) {
      var ind = 0;
      ccat.find('.gallery-precon').each(function () {
        if (ind != arg) {
          jQuery(this).find('.videogallery').eq(0).get(0).external_handle_stopCurrVideo();
        }
        ind++;
      });
    }
    ccat.find('.gallery-precon').removeClass('curr-gallery');
    ccat.find('.the-categories-con').find('.a-category').removeClass('active');
    ccat.find('.the-categories-con').find('.a-category').eq(arg).addClass('active');
    ccat.find('.gallery-precon').addClass('disabled');
    ccat.find('.gallery-precon').eq(arg).css('display', '').removeClass('disabled');
    var _cach = ccat.find('.gallery-precon').eq(arg);
    var _cachg = _cach.find('.videogallery').eq(0);
    if (_cachg.get(0) && _cachg.get(0).init_settings) {
      if (_cachg.get(0).init_settings.autoplay == 'on') {
        setTimeout(function () {
          _cachg.get(0).api_play_currVideo();
        }, 10);
        if (margs.called_from == 'deeplink' || margs.called_from == 'init') {
          setTimeout(function () {}, 1000);
          setTimeout(function () {
            _cachg.get(0).api_play_currVideo();
          }, 1500);
        }
      }
    }
    setTimeout(function () {
      ccat.children('.dzsas-second-con').hide();
      ccat.children('.dzsas-second-con').eq(arg).show();
      ccat.find('.gallery-precon').eq(arg).addClass('curr-gallery');
      currCatNr = arg;
      if (typeof ccat.find('.gallery-precon').eq(arg).find('.videogallery').eq(0).get(0) != 'undefined' && typeof ccat.find('.gallery-precon').eq(arg).find('.videogallery').eq(0).get(0).api_handleResize != 'undefined') {
        ccat.find('.gallery-precon').eq(arg).find('.videogallery').eq(0).get(0).api_handleResize();
        ccat.find('.gallery-precon').eq(arg).find('.videogallery').eq(0).get(0).api_handleResize_currVideo();
      }
      setTimeout(function () {
        jQuery(window).trigger('resize');
      }, 1500);
    }, 50);
  }
}
function youtube_sanitize_url_to_id(arg) {
  if (arg) {
    if (String(arg).indexOf('youtube.com/embed') > -1) {
      var auxa = String(dataSrc).split('youtube.com/embed/');
      if (auxa[1]) {
        return auxa[1];
      }
    }
    if (arg.indexOf('youtube.com') > -1 || arg.indexOf('youtu.be') > -1) {
      if ((0, _dzs_helpers.get_query_arg)(arg, 'v')) {
        return (0, _dzs_helpers.get_query_arg)(arg, 'v');
      }
      if (arg.indexOf('youtu.be') > -1) {
        var arr = arg.split('/');
        arg = arr[arr.length - 1];
      }
    }
  }
  return arg;
}
function registerAuxjQueryExtends($) {
  $.fn.appendOnce = function (arg, argfind) {
    var _t = $(this); // It's your element

    if (typeof argfind == 'undefined') {
      var regex = new RegExp('class="(.*?)"');
      var auxarr = regex.exec(arg);
      if (typeof auxarr[1] != 'undefined') {
        argfind = '.' + auxarr[1];
      }
    }
    if (_t.children(argfind).length < 1) {
      _t.append(arg);
      return true;
    }
    return false;
  };
  var d = new Date();
  window.dzsvg_time_started = d.getTime();
  var inter_check_treat = 0;
  clearTimeout(inter_check_treat);
  inter_check_treat = setTimeout(workaround_treatuntretreadItems, 2000);
  function workaround_treatuntretreadItems() {
    jQuery('.js-api-player:not(.treated)').each(function () {
      var _t = jQuery(this);
      var $ytApiPlayer_ = _t.get(0);
      var playerId = _t.attr('id');
      var aux = playerId.substr(8);
      var aux2 = _t.attr('data-suggestedquality');
      if (typeof $ytApiPlayer_.loadVideoById != 'undefined') {
        $ytApiPlayer_.loadVideoById(aux, 0, aux2);
        $ytApiPlayer_.pauseVideo();
      } else {
        inter_check_treat = setTimeout(workaround_treatuntretreadItems, 2000);
      }
    });
  }

  // -- we save the other youtube player ready functions ( maybe conflict with other players )
  if (window.onYouTubePlayerReady && typeof window.onYouTubePlayerReady == 'function' && typeof backup_onYouTubePlayerReady == 'undefined') {
    window.dzsvg_backup_onYouTubePlayerReady = window.onYouTubePlayerReady;
  }
}
function dzsvgExtraWindowFunctions() {
  window.dzsvg_wp_send_view = function (argcthis, argtitle) {
    var data = {
      video_title: argtitle,
      video_analytics_id: argcthis.attr('data-player-id')
    };
    if (window.dzsvg_curr_user) {
      data.dzsvg_curr_user = window.dzsvg_curr_user;
    }
    var theajaxurl = 'index.php?action=ajax_dzsvg_submit_view';
    if (window.dzsvg_site_url) {
      theajaxurl = dzsvg_settings.dzsvg_site_url + theajaxurl;
    }
    jQuery.ajax({
      type: "POST",
      url: theajaxurl,
      data: data,
      success: function (response) {},
      error: function (arg) {}
    });
  };
  window.dzsvg_wp_send_contor_60_secs = function (argcthis, argtitle) {
    var data = {
      video_title: argtitle,
      video_analytics_id: argcthis.attr('data-player-id'),
      dzsvg_curr_user: window.dzsvg_curr_user
    };
    var theajaxurl = 'index.php?action=ajax_dzsvg_submit_contor_60_secs';
    if (window.dzsvg_site_url) {
      theajaxurl = dzsvg_settings.dzsvg_site_url + theajaxurl;
    }
    jQuery.ajax({
      type: "POST",
      url: theajaxurl,
      data: data,
      success: function (response) {},
      error: function (arg) {
        ;
      }
    });
  };
  window.dzsvg_open_social_link = function (urlTemplate) {
    const currentUrl = encodeURIComponent(window.location.href);
    const finalUrl = urlTemplate.replace(/{{replacewithcurrurl}}/g, currentUrl);

    // Use Web Share API if supported (mobile-friendly)
    if (navigator.share) {
      navigator.share({
        title: document.title,
        url: finalUrl
      }).catch(err => {
        console.warn('Web Share failed:', err);
      });
      return;
    }

    // Fallback: open a new window
    const width = 500;
    const height = 500;
    const windowOptions = [`width=${width}`, `height=${height}`, 'resizable=yes', 'scrollbars=yes'].join(',');
    const popup = window.open(finalUrl, '_blank', windowOptions);
    if (!popup) {
      console.warn('Popup blocked. Please allow popups for this site.');
    }
  };
  window.dzsvp_yt_iframe_ready = function () {
    _global_youtubeIframeAPIReady = true;
  };
  window.onYouTubeIframeAPIReady = function () {
    window.dzsvg_yt_ready = true;
    window.dzsvp_yt_iframe_ready();
  };
}
function extractOptionsFromPlayer($c) {
  if ($c.data('originalPlayerAttributes')) {
    return $c.data('originalPlayerAttributes');
  }
  var finalOptions = {};
  if (getDataOrAttr($c, 'data-sourcevp')) {
    finalOptions.source = getDataOrAttr($c, 'data-sourcevp');
  }
  if ($c.attr('data-type')) {
    finalOptions.type = $c.attr('data-type');
  }
  return finalOptions;
}
function convertPluginOptionsToFinalOptions(elThis, defaultOptions, argOptions = null, searchedAttr = 'data-options', searchedDivClass = 'feed-options') {
  var finalOptions = null;
  var tempOptions = {};
  var sw_setFromJson = false;
  var _elThis = jQuery(elThis);
  if (argOptions && typeof argOptions == 'object' && Object.keys(argOptions).length) {
    tempOptions = argOptions;
  } else {
    if (_elThis.attr(searchedAttr)) {
      try {
        tempOptions = JSON.parse(_elThis.attr(searchedAttr));
        sw_setFromJson = true;
      } catch (err) {
        console.log('json parse error searched attr err - ', err, _elThis.attr(searchedAttr));
      }
    } else {
      if (_elThis.find('.feed-options').length) {
        try {
          tempOptions = JSON.parse(_elThis.find('.feed-options').html());
          sw_setFromJson = true;
        } catch (err) {
          console.log('json parse error feed-options err - ', err, _elThis.find('.feed-options').html());
        }
      }
    }
    if (!sw_setFromJson) {
      // -- *deprecated
      if (typeof argOptions == 'undefined' || !argOptions) {
        // if (typeof _elThis.attr(searchedAttr) != 'undefined' && _elThis.attr(searchedAttr) !== '') {
        // var aux = _elThis.attr(searchedAttr);
        // aux = 'var aux_opts = ' + aux;
        // eval(aux);
        // tempOptions = Object.assign({}, aux_opts);
        // }
        console.log('[dzsvg] no options');
      }
    }
  }
  finalOptions = Object.assign(defaultOptions, tempOptions);
  return finalOptions;
}
function player_setupQualitySelector(selfClass, yt_qualCurr, yt_qualArray) {
  var _qualitySelector = selfClass.cthis.find('.quality-selector');
  if (_qualitySelector.find('.dzsvg-tooltip').length) {
    var aux = _qualitySelector.find('.dzsvg-tooltip').html();
    var aux_opts = '';
    for (var i2 in yt_qualArray) {
      aux_opts += '<div class="quality-option';
      if (yt_qualCurr === yt_qualArray[i2]) {
        aux_opts += ' active';
      }
      aux_opts += '" data-val="' + yt_qualArray[i2] + '">' + yt_qualArray[i2] + '</div>';
    }
    aux = aux.replace('{{quality-options}}', aux_opts);
    _qualitySelector.find('.dzsvg-tooltip').html(aux);
  }
}
function playerHandleDeprecatedAttrSrc(cthis) {
  if (!cthis.attr('data-sourcevp')) {
    if (cthis.attr('data-source')) {
      cthis.attr('data-sourcevp', cthis.attr('data-source'));
    } else {
      if (cthis.attr('data-src')) {
        cthis.attr('data-sourcevp', cthis.attr('data-src'));
      }
    }
  }
}
function player_assert_autoplay(selfClass) {
  // -- autoplay assert

  var o = selfClass.initOptions;
  if ((0, _dzs_helpers.is_mobile)()) {}
}
function configureAudioPlayerOptionsInitial(cthis, o, selfClass) {
  if (o.gallery_object != null) {
    if (typeof o.gallery_object.get(0) != 'undefined') {
      selfClass.$parentGallery = o.gallery_object;
      setTimeout(function () {
        if (selfClass.$parentGallery.get(0).api_video_ready) {
          selfClass.$parentGallery.get(0).api_video_ready();
        }
      }, _Constants.ConstantsDzsvg.DELAY_MINUSCULE);
    }
  }
  if ((0, _dzs_helpers.is_mobile)() || o.first_video_from_gallery === 'on' && (0, _dzs_helpers.is_safari)()) {
    if ((0, _dzs_helpers.is_mobile)()) {
      cthis.addClass('is-mobile');
    }
    if (cthis.attr('data-img')) {} else {
      cthis.removeClass('hide-on-paused');
    }
  }
  if (o.playfrom === 'default') {
    if (typeof selfClass.cthis.attr('data-playfrom') != 'undefined' && selfClass.cthis.attr('data-playfrom') != '') {
      o.playfrom = selfClass.cthis.attr('data-playfrom');
    }
  }
  if (isNaN(Number(o.playfrom)) == false) {
    o.playfrom = Number(o.playfrom);
  }
  if (isNaN(Number(o.sliderAreaHeight)) == false) {
    o.sliderAreaHeight = Number(o.sliderAreaHeight);
  }
  cthis.data('embed_code', o.embed_code);
  selfClass.videoWidth = cthis.width();
  selfClass.videoHeight = cthis.height();
  if (o.autoplay === 'on') {
    selfClass.autoplayVideo = 'on';
  }
  if (!selfClass.dataSrc) {
    console.log('[dzsvg] missing source', selfClass.cthis);
  }
  var mainClass = '';
  if (typeof cthis.attr('class') == 'string') {
    mainClass = cthis.attr('class');
  } else {
    mainClass = cthis.get(0).className;
  }
  if (mainClass.indexOf('skin_') == -1) {
    cthis.addClass(o.design_skin);
    mainClass += ' ' + o.design_skin;
  }
  cthis.addClass(o.extra_classes);

  //-setting skin specific vars
  if (mainClass.indexOf('skin_aurora') > -1) {
    o.design_skin = 'skin_aurora';
    selfClass.bufferedWidthOffset = -2;
    selfClass.volumeWidthOffset = -2;
    if (o.design_enableProgScrubBox == 'default') {
      o.design_enableProgScrubBox = 'on';
    }
  }
  if (mainClass.indexOf('skin_pro') > -1) {
    o.design_skin = 'skin_pro';
    selfClass.volumeWidthOffset = -2;
    if (o.design_enableProgScrubBox == 'default') {
      o.design_enableProgScrubBox = 'off';
    }
  }
  if (mainClass.indexOf('skin_bigplay') > -1) {
    o.design_skin = 'skin_bigplay';
  }
  if (mainClass.indexOf('skin_nocontrols') > -1) {
    o.design_skin = 'skin_nocontrols';
  }
  if (mainClass.indexOf('skin_bigplay_pro') > -1) {
    o.design_skin = 'skin_bigplay_pro';
  }
  if (mainClass.indexOf('skin_bluescrubtop') > -1) {
    o.design_skin = 'skin_bluescrubtop';
  }
  if (mainClass.indexOf('skin_avanti') > -1) {
    o.design_skin = 'skin_avanti';
  }
  if (mainClass.indexOf('skin_noskin') > -1) {
    o.design_skin = 'skin_noskin';
  }
  if (cthis.hasClass('skin_white')) {
    o.design_skin = 'skin_white';
  }
  if (cthis.hasClass('skin_reborn')) {
    o.design_skin = 'skin_reborn';
  }
  if (o.design_enableProgScrubBox == 'default') {
    o.design_enableProgScrubBox = 'off';
  }
  if ((0, _dzs_helpers.is_mobile)() || (0, _dzs_helpers.is_ios)()) {
    cthis.addClass('disable-volume');
  }
  if (o.gallery_object) {
    if (o.gallery_object.get(0)) {
      cthis.get(0).gallery_object = o.gallery_object.get(0);
    }
  }
  if (o.extra_controls) {
    cthis.append(o.extra_controls);
  }
  if (o.responsive_ratio === 'default' || selfClass.dataType === 'youtube' && o.responsive_ratio === 'detect') {
    if (cthis.attr('data-responsive_ratio')) {
      o.responsive_ratio = cthis.attr('data-responsive_ratio');
    }
  }
  if (o.gallery_object !== null) {
    selfClass.isPartOfAnGallery = true;
  }
  if (selfClass.isPartOfAnGallery) {
    selfClass.isGalleryHasOneVideoPlayerMode = o.gallery_object.data('vg_settings') && o.gallery_object.data('vg_settings').mode_normal_video_mode === 'one';
  }

  // -- we cache this for the one
  if (selfClass.isGalleryHasOneVideoPlayerMode) {
    if (o.gallery_target_index === 0 && !selfClass.cthis.data('originalPlayerAttributes')) {
      selfClass.cthis.data('originalPlayerAttributes', detect_videoTypeAndSourceForElement(selfClass.cthis));
    }
  }
  if (o.action_video_view === 'wpdefault') {
    o.action_video_view = window.dzsvg_wp_send_view;
  }
  if (o.action_video_contor_60secs === 'wpdefault') {
    o.action_video_contor_60secs = window.dzsvg_wp_send_contor_60_secs;
  }
  reinitPlayerOptions(selfClass, o);
}
function reinitPlayerOptions(selfClass, o) {
  // -- we need  selfClass.dataType and selfClass.dataSrc beforeHand

  selfClass.hasCustomSkin = true;
  // -- assess custom skin
  if (selfClass.dataType === 'vimeo' && o.vimeo_is_chromeless !== 'on') {
    selfClass.hasCustomSkin = false;
  }
  if (selfClass.dataType === 'youtube' && o.settings_youtube_usecustomskin !== 'on') {
    selfClass.hasCustomSkin = false;
  }
  if ((0, _dzs_helpers.is_ios)() && o.settings_ios_usecustomskin !== 'on') {
    selfClass.hasCustomSkin = false;
  }
  if (selfClass.dataType === 'inline') {
    selfClass.hasCustomSkin = false;
  }
  if (selfClass.cthis.attr('data-ad-array')) {
    selfClass.ad_array = sanitizeDataAdArrayStringToArray(selfClass.cthis.attr('data-ad-array'));
  }
  (0, _player_setupAd.ads_decode_ads_array)(selfClass);
  player_assert_autoplay(selfClass);
  if (o.is_ad === 'on') {
    selfClass.isAd = true;
  }
  player_checkIfItShouldStartMuted(selfClass, o);
  if (selfClass.isAd && o.ad_link) {
    selfClass.ad_link = o.ad_link;
  }

  // -- assess custom skin END
}

function player_checkIfItShouldStartMuted(selfClass, o) {
  const isPlayerOrGalleryHadFirstInteraction = () => {
    if (selfClass.isHadFirstInteraction) return true;
    if (!(0, _dzs_helpers.is_mobile)() && selfClass.$parentGallery && selfClass.$parentGallery.hasClass('user-had-first-interaction')) {
      return true;
    }
    return false;
  };

  // -- should start muted
  if (selfClass.cthis.hasClass('start-muted')) {
    selfClass.initOptions.autoplayWithVideoMuted = 'always'; // -- warning: override
  }

  // -- detect
  if (selfClass.initOptions.autoplay === 'off') {
    selfClass.shouldStartMuted = false;
  }
  if (selfClass.initOptions.autoplay === 'on') {
    if (isPlayerOrGalleryHadFirstInteraction()) {
      selfClass.shouldStartMuted = false;
    } else {
      if ((0, _dzs_helpers.is_mobile)()) {
        // -- mobile
        selfClass.shouldStartMuted = selfClass.initOptions.autoplay === 'on' && selfClass.initOptions.autoplayWithVideoMuted === 'auto';
      } else {
        // -- desktop
        if (o.autoplayWithVideoMuted === 'auto') {
          selfClass.shouldStartMuted = true;
        }
      }
    }
  }
  // -- should start muted

  if (o.autoplayWithVideoMuted === 'always') {
    selfClass.shouldStartMuted = true;
  }
}
function tagsSetupDom(_tagElement) {
  var auxhtml = _tagElement.html();
  var w = 100;
  var h = 100;
  var acomlink = '';
  if (_tagElement.attr('data-width') != undefined) {
    w = _tagElement.attr('data-width');
  }
  if (_tagElement.attr('data-height') != undefined) {
    h = _tagElement.attr('data-height');
  }
  if (_tagElement.attr('data-link') != undefined) {
    acomlink = '<a href="' + _tagElement.attr('data-link') + '"></a>';
  }
  _tagElement.html('');
  _tagElement.css({
    'left': _tagElement.attr('data-left') + 'px',
    'top': _tagElement.attr('data-top') + 'px'
  });
  _tagElement.append('<div class="tag-box" style="width:' + w + 'px; height:' + h + 'px;">' + acomlink + '</div>');
  _tagElement.append('<span class="tag-content">' + auxhtml + '</span>');
  _tagElement.removeClass('dzstag-tobe').addClass('dzstag');
}
function pauseDzsapPlayers() {
  if (window.dzsap_list) {
    for (var i = 0; i < dzsap_list.length; i++) {
      if (typeof dzsap_list[i].get(0) != "undefined" && typeof dzsap_list[i].get(0).api_pause_media != "undefined" && dzsap_list[i].get(0) != cthis.get(0)) {
        if (dzsap_list[i].data('type_audio_stop_buffer_on_unfocus') && dzsap_list[i].data('type_audio_stop_buffer_on_unfocus') == 'on') {
          dzsap_list[i].get(0).api_destroy_for_rebuffer();
        } else {
          dzsap_list[i].get(0).api_pause_media({
            'audioapi_setlasttime': false
          });
        }
        window.dzsap_player_interrupted_by_dzsvg = dzsap_list[i].get(0);
      }
    }
  }
}
function init_navigationOuter() {
  jQuery('.videogallery--navigation-outer').each(function () {
    var _t = jQuery(this);
    var xpos = 0;
    _t.find('.videogallery--navigation-outer--bigblock').each(function () {
      var _t = jQuery(this);
      _t.css('left', xpos + '%');
      xpos += 100;
    });

    // -- we will use first gallery if id is auto
    if (_t.attr('data-vgtarget') === '.id_auto') {
      var _cach = jQuery('.videogallery,.videogallery-tobe').eq(0);
      var cclass = /id_(.*?) /.exec(_cach.attr('class'));
      if (cclass && cclass[1]) {
        _t.attr('data-vgtarget', '.id_' + cclass[1]);
      }
      if (_cach.get(0) && _cach.get(0).api_set_outerNav) {
        _cach.get(0).api_set_outerNav(_t);
      }
      setTimeout(function () {
        if (_cach.get(0) && _cach.get(0).api_set_outerNav) {
          _cach.get(0).api_set_outerNav(_t);
        }
      }, 1000);
    }
    var $targetVideoGallery = jQuery(_t.attr('data-vgtarget')).eq(0);
    var _clip = _t.find('.videogallery--navigation-outer--clip').eq(0);
    var _clipmover = _t.find('.videogallery--navigation-outer--clipmover').eq(0);
    var currPage = 0;
    var _block_active = _t.find('.videogallery--navigation-outer--bigblock.active').eq(0);
    var _navOuterBullets = _t.find('.navigation-outer--bullet');
    var _navOuterBlocks = _t.find('.videogallery--navigation-outer--block');
    setTimeout(function () {
      _t.addClass('active');
      _block_active = _t.find('.videogallery--navigation-outer--bigblock.active').eq(0);
      _clip.height(_block_active.height());
    }, 500);
    _navOuterBlocks.on('click', function (e) {
      const $outerBlock = jQuery(this);
      const ind = _navOuterBlocks.index($outerBlock);
      if ($targetVideoGallery.get(0) && $targetVideoGallery.get(0).api_gotoItem) {
        if ($targetVideoGallery.get(0).SelfPlaylist) {
          const SelfPlaylist = $targetVideoGallery.get(0).SelfPlaylist;
          SelfPlaylist.handleHadFirstInteraction(e);
        }
        if ($targetVideoGallery.get(0).api_gotoItem(ind)) {}
        const scrollY = $targetVideoGallery.offset().top - _Constants.PLAYLIST_SCROLL_TOP_OFFSET;
        window.scrollTo({
          top: scrollY,
          left: 0,
          behavior: 'smooth'
        });
      }
    });
    _navOuterBullets.on('click', function () {
      var _t2 = jQuery(this);
      var ind = _navOuterBullets.index(_t2);
      gotoPage(ind);
    });
    function gotoPage(arg) {
      var auxl = -(Number(arg) * 100) + '%';
      _navOuterBullets.removeClass('active');
      _navOuterBullets.eq(arg).addClass('active');
      _t.find('.videogallery--navigation-outer--bigblock.active').removeClass('active');
      _t.find('.videogallery--navigation-outer--bigblock').eq(arg).addClass('active');
      _clip.height(_t.find('.videogallery--navigation-outer--bigblock').eq(arg).height());
      _clipmover.css('left', auxl);
    }
  });
}
function vimeo_do_command(selfClass, vimeo_data, vimeo_url) {
  if (vimeo_url) {
    if (selfClass._videoElement && selfClass._videoElement.contentWindow && vimeo_url) {
      selfClass._videoElement.contentWindow.postMessage(JSON.stringify(vimeo_data), vimeo_url);
    }
  }
}

},{"../configs/Constants":1,"../js_common/_dzs_helpers":3,"../js_player/_player_setupAd":13,"./_dzsvg_svgs":5}],5:[function(require,module,exports){
"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.svg_volume_icon=exports.svg_volume_active_skin_default=exports.svg_quality_icon=exports.svg_play_simple_skin_bigplay_pro=exports.svg_pause_simple_skin_aurora=exports.svg_mute_icon=exports.svg_mute_btn=exports.svg_full_icon=exports.svg_embed=exports.svg_default_volume_static=exports.svg_aurora_play_btn=exports.svgShareIcon=exports.svgSearchIcon=exports.svgReplayIcon=exports.svgForwardButton=exports.svgForSkin_boxyRounded2=exports.svgForSkin_boxyRounded=exports.svgBackButton=void 0;const svg_quality_icon=exports.svg_quality_icon='<svg class="the-icon" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="896.025px" height="896.025px" viewBox="0 0 896.025 896.025" style="enable-background:new 0 0 896.025 896.025;" xml:space="preserve"> <g> <path id="settings_1_" d="M863.24,382.771l-88.759-14.807c-6.451-26.374-15.857-51.585-28.107-75.099l56.821-70.452 c12.085-14.889,11.536-36.312-1.205-50.682l-35.301-39.729c-12.796-14.355-34.016-17.391-50.202-7.165l-75.906,47.716 c-33.386-23.326-71.204-40.551-112-50.546l-14.85-89.235c-3.116-18.895-19.467-32.759-38.661-32.759h-53.198 c-19.155,0-35.561,13.864-38.608,32.759l-14.931,89.263c-33.729,8.258-65.353,21.588-94.213,39.144l-72.188-51.518 c-15.558-11.115-36.927-9.377-50.504,4.171l-37.583,37.61c-13.548,13.577-15.286,34.946-4.142,50.504l51.638,72.326 c-17.391,28.642-30.584,60.086-38.841,93.515l-89.743,14.985C13.891,385.888,0,402.24,0,421.435v53.156 c0,19.193,13.891,35.547,32.757,38.663l89.743,14.985c6.781,27.508,16.625,53.784,29.709,78.147L95.647,676.44 c-12.044,14.875-11.538,36.312,1.203,50.669l35.274,39.73c12.797,14.382,34.028,17.363,50.216,7.163l77-48.37 c32.581,22.285,69.44,38.664,108.993,48.37l14.931,89.25c3.048,18.896,19.453,32.76,38.608,32.76h53.198 c19.194,0,35.545-13.863,38.661-32.759l14.875-89.25c33.308-8.147,64.531-21.245,93.134-38.5l75.196,53.705 c15.53,11.155,36.915,9.405,50.478-4.186l37.598-37.597c13.532-13.536,15.365-34.893,4.127-50.479l-53.536-75.059 c17.441-28.738,30.704-60.238,38.909-93.816l88.758-14.82c18.921-3.116,32.756-19.469,32.756-38.663v-53.156 C895.998,402.24,882.163,385.888,863.24,382.771z M449.42,616.013c-92.764,0-168-75.25-168-168c0-92.764,75.236-168,168-168 c92.748,0,167.998,75.236,167.998,168C617.418,540.763,542.168,616.013,449.42,616.013z"/> </g> </svg>',svg_default_volume_static=exports.svg_default_volume_static='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="14px" viewBox="0 5 24 14" enable-background="new 0 5 24 14" xml:space="preserve"> <path d="M0,19h24V5L0,19z M22,17L5,17.625l12-6.227l5-2.917V17z"/> </svg>',svg_volume_icon=exports.svg_volume_icon='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="10px" height="12px" viewBox="0 0 10 12" enable-background="new 0 0 10 12" xml:space="preserve"> <path fill-rule="evenodd" clip-rule="evenodd" fill="#200C34" d="M8.475,0H7.876L5.323,1.959c0,0-0.399,0.667-1.157,0.667H1.454 c0,0-1.237,0.083-1.237,1.334v3.962c0,0-0.159,1.334,1.277,1.334h2.553c0,0,0.877,0.167,1.316,0.667l2.513,1.959l0.638,0.083 c0,0,0.678,0,0.678-0.667V0.667C9.193,0.667,9.153,0,8.475,0z"/> </svg>',svg_aurora_play_btn=exports.svg_aurora_play_btn='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="100%" height="100%" viewBox="0 0 13.75 12.982" enable-background="new 0 0 13.75 12.982" xml:space="preserve"> <path d="M11.889,5.71L3.491,0.108C3.389,0.041,3.284,0,3.163,0C2.834,0,2.565,0.304,2.565,0.676H2.562v11.63h0.003 c0,0.372,0.269,0.676,0.597,0.676c0.124,0,0.227-0.047,0.338-0.115l8.389-5.595c0.199-0.186,0.326-0.467,0.326-0.781 S12.088,5.899,11.889,5.71z"/> </svg>',svg_embed=exports.svg_embed='<svg width="32.00199890136719" height="32" viewBox="0 0 32.00199890136719 32" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g><path d="M 23.586,9.444c 0.88,0.666, 1.972,1.064, 3.16,1.064C 29.648,10.508, 32,8.156, 32,5.254 C 32,2.352, 29.648,0, 26.746,0c-2.9,0-5.254,2.352-5.254,5.254c0,0.002,0,0.004,0,0.004L 8.524,11.528 C 7.626,10.812, 6.49,10.38, 5.254,10.38C 2.352,10.38,0,12.734,0,15.634s 2.352,5.254, 5.254,5.254c 1.048,0, 2.024-0.312, 2.844-0.84 l 13.396,6.476c0,0.002,0,0.004,0,0.004c0,2.902, 2.352,5.254, 5.254,5.254c 2.902,0, 5.254-2.352, 5.254-5.254 c0-2.902-2.352-5.254-5.254-5.254c-1.188,0-2.28,0.398-3.16,1.064L 10.488,16.006c 0.006-0.080, 0.010-0.158, 0.012-0.238L 23.586,9.444z"></path></g></svg>',svg_mute_btn=exports.svg_mute_btn='<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="72.786px" height="72.786px" viewBox="0 0 72.786 72.786" enable-background="new 0 0 72.786 72.786" xml:space="preserve"> <g id="Capa_1"> <g> <g id="Volume_Off"> <g> <path d="M38.479,4.216c-1.273-0.661-2.819-0.594-4.026,0.188L13.858,17.718h-2.084C5.28,17.718,0,22.84,0,29.135v14.592 c0,6.296,5.28,11.418,11.774,11.418h2.088L34.46,68.39c0.654,0.421,1.41,0.632,2.17,0.632c0.636,0,1.274-0.148,1.854-0.449 c1.274-0.662,2.067-1.949,2.067-3.355V7.572C40.551,6.172,39.758,4.878,38.479,4.216z"/> </g> </g> </g> </g> <g id="only-if-mute"> <path d="M67.17,35.735l4.469-4.334c1.529-1.48,1.529-3.896-0.004-5.377c-1.529-1.489-4.018-1.489-5.553,0l-4.461,4.328 l-4.045-3.923c-1.535-1.489-4.021-1.489-5.552,0c-1.534,1.489-1.534,3.896,0,5.378l4.048,3.926l-3.63,3.521 c-1.53,1.488-1.53,3.896,0,5.386c0.767,0.737,1.771,1.112,2.774,1.112c1.005,0,2.009-0.375,2.775-1.112l3.629-3.521l4.043,3.92 c0.769,0.744,1.771,1.121,2.775,1.121c1.004,0,2.008-0.377,2.773-1.121c1.533-1.48,1.533-3.89,0-5.377L67.17,35.735z"/> </g> </svg> ',svg_mute_icon=exports.svg_mute_icon='<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px"  y="0px" viewBox="0 0 196.78 196.78" style="enable-background:new 0 0 196.78 196.78;" xml:space="preserve" width="14px" height="14px"> <g > <path style="fill-rule:evenodd;clip-rule:evenodd;" d="M144.447,3.547L80.521,53.672H53.674c-13.227,0-17.898,4.826-17.898,17.898 v26.4v27.295c0,13.072,4.951,17.898,17.898,17.898h26.848l63.926,50.068c7.668,4.948,16.558,6.505,16.558-7.365V10.914 C161.005-2.956,152.115-1.4,144.447,3.547z" fill="#494b4d"/> </g> </svg> ',svg_play_simple_skin_bigplay_pro=exports.svg_play_simple_skin_bigplay_pro='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="120px" height="120px" viewBox="0 0 120 120" enable-background="new 0 0 120 120" xml:space="preserve"> <path fill-rule="evenodd" clip-rule="evenodd" fill="#D0ECF3" d="M79.295,56.914c2.45,1.705,2.45,4.468,0,6.172l-24.58,17.103 c-2.45,1.704-4.436,0.667-4.436-2.317V42.129c0-2.984,1.986-4.022,4.436-2.318L79.295,56.914z M0.199,54.604 c-0.265,2.971-0.265,7.821,0,10.792c2.57,28.854,25.551,51.835,54.405,54.405c2.971,0.265,7.821,0.265,10.792,0 c28.854-2.57,51.835-25.551,54.405-54.405c0.265-2.971,0.265-7.821,0-10.792C117.231,25.75,94.25,2.769,65.396,0.198 c-2.971-0.265-7.821-0.265-10.792,0C25.75,2.769,2.769,25.75,0.199,54.604z M8.816,65.394c-0.309-2.967-0.309-7.82,0-10.787 c2.512-24.115,21.675-43.279,45.79-45.791c2.967-0.309,7.821-0.309,10.788,0c24.115,2.512,43.278,21.675,45.79,45.79 c0.309,2.967,0.309,7.821,0,10.788c-2.512,24.115-21.675,43.279-45.79,45.791c-2.967,0.309-7.821,0.309-10.788,0 C30.491,108.672,11.328,89.508,8.816,65.394z"/> </svg>',svg_pause_simple_skin_aurora=exports.svg_pause_simple_skin_aurora='<svg version="1.1" id="Layer_2" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="13.75px" height="12.982px" viewBox="0 0 13.75 12.982" enable-background="new 0 0 13.75 12.982" xml:space="preserve"> <g> <path d="M5.208,11.982c0,0.55-0.45,1-1,1H3c-0.55,0-1-0.45-1-1V1c0-0.55,0.45-1,1-1h1.208c0.55,0,1,0.45,1,1V11.982z"/> </g> <g> <path d="M12.208,11.982c0,0.55-0.45,1-1,1H10c-0.55,0-1-0.45-1-1V1c0-0.55,0.45-1,1-1h1.208c0.55,0,1,0.45,1,1V11.982z"/> </g> </svg> ',svg_volume_active_skin_default=exports.svg_volume_active_skin_default='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="14px" viewBox="0 5 24 14" enable-background="new 0 5 24 14" xml:space="preserve"> <path d="M0,19h24V5L0,19z M22,17L22,17V8.875V8.481V17z"/> </svg>',svg_full_icon=exports.svg_full_icon='<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="16px" height="16px" viewBox="0 0 16 16" enable-background="new 0 0 16 16" xml:space="preserve"> <g id="Layer_3"> <polygon fill="#FFFFFF" points="2.404,2.404 0.057,4.809 0.057,0 4.751,0 "/> <polygon fill="#FFFFFF" points="13.435,2.404 11.03,0.057 15.839,0.057 15.839,4.751 "/> <polygon fill="#FFFFFF" points="2.404,13.446 4.809,15.794 0,15.794 0,11.1 "/> <polygon fill="#FFFFFF" points="13.435,13.446 15.781,11.042 15.781,15.851 11.087,15.851 "/> </g> <g id="Layer_2"> <rect x="4.255" y="4.274" fill="#FFFFFF" width="7.366" height="7.442"/> </g> </svg>',svgReplayIcon=exports.svgReplayIcon='<?xml version="1.0" encoding="iso-8859-1"?> <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 17.12 17.12" style="enable-background:new 0 0 17.12 17.12;" xml:space="preserve"> <path style="" d="M8.661,0.001c0.006,0,0.01,0,0.01,0c0.007,0,0.007,0,0.011,0c0.002,0,0.007,0,0.009,0 c0,0,0,0,0.004,0c0.019-0.002,0.027,0,0.039,0c2.213,0,4.367,0.876,5.955,2.42l1.758-1.776c0.081-0.084,0.209-0.11,0.314-0.065 c0.109,0.044,0.186,0.152,0.186,0.271l-0.294,6.066h-5.699c-0.003,0-0.011,0-0.016,0c-0.158,0-0.291-0.131-0.291-0.296 c0-0.106,0.059-0.201,0.146-0.252l1.73-1.751c-1.026-0.988-2.36-1.529-3.832-1.529c-2.993,0.017-5.433,2.47-5.433,5.51 c0.023,2.978,2.457,5.4,5.481,5.422c1.972-0.106,3.83-1.278,4.719-3.221l2.803,1.293l-0.019,0.039 c-1.92,3.713-4.946,5.277-8.192,4.944c-4.375-0.348-7.848-4.013-7.878-8.52C0.171,3.876,3.976,0.042,8.661,0.001z"/></svg> ',svgBackButton=exports.svgBackButton='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"                width="32px" height="32px" viewBox="0 0 32 32" enable-background="new 0 0 32 32" xml:space="preserve"><path fill="#515151" d="M7.927,17.729l9.619,9.619c0.881,0.881,2.325,0.881,3.206,0l0.803-0.804c0.881-0.88,0.881-2.323,0-3.204l-7.339-7.342l7.34-7.34c0.881-0.882,0.881-2.325,0-3.205l-0.803-0.803c-0.881-0.882-2.325-0.882-3.206,0l-9.619,9.619                C7.454,14.744,7.243,15.378,7.278,16C7.243,16.621,7.452,17.256,7.927,17.729z"/></svg>',svgForwardButton=exports.svgForwardButton='<svg enable-background="new 0 0 32 32" height="32px" id="Layer_1" version="1.1" viewBox="0 0 32 32" width="32px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M24.291,14.276L14.705,4.69c-0.878-0.878-2.317-0.878-3.195,0l-0.8,0.8c-0.878,0.877-0.878,2.316,0,3.194  L18.024,16l-7.315,7.315c-0.878,0.878-0.878,2.317,0,3.194l0.8,0.8c0.878,0.879,2.317,0.879,3.195,0l9.586-9.587  c0.472-0.471,0.682-1.103,0.647-1.723C24.973,15.38,24.763,14.748,24.291,14.276z" fill="#515151"/></svg>',svgForSkin_boxyRounded=exports.svgForSkin_boxyRounded=' <svg class="svg_rounded" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="1px" height="1px" viewBox="0 0 1 1" enable-background="new 0 0 1 1" xml:space="preserve"> <g id="Layer_1"> </g> <g id="Layer_2"> <g> <defs> <path id="SVGID_1_" d="M1,0.99C1,0.996,0.996,1,0.99,1H0.01C0.004,1,0,0.996,0,0.99V0.01C0,0.004,0.004,0,0.01,0h0.98 C0.996,0,1,0.004,1,0.01V0.99z"/> </defs> <clipPath id="SVGID_2_"  clipPathUnits="objectBoundingBox"> <use xlink:href="#SVGID_1_" overflow="visible"/> </clipPath> <path clip-path="url(#SVGID_2_)" fill="#2A2F3F" d="M3,1.967C3,1.985,2.984,2,2.965,2h-3.93C-0.984,2-1,1.985-1,1.967v-2.934 C-1-0.985-0.984-1-0.965-1h3.93C2.984-1,3-0.985,3-0.967V1.967z"/> </g> </g> </svg>  ',svgForSkin_boxyRounded2=exports.svgForSkin_boxyRounded2=' <svg class="svg_rounded" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="1px" height="1px" viewBox="0 0 1 1" enable-background="new 0 0 1 1" xml:space="preserve"> <g id="Layer_1"> </g> <g id="Layer_2"> <g> <defs> <path id="SVGID_1_" d="M1,0.99C1,0.996,0.996,1,0.99,1H0.01C0.004,1,0,0.996,0,0.99V0.01C0,0.004,0.004,0,0.01,0h0.98 C0.996,0,1,0.004,1,0.01V0.99z"/> </defs> <clipPath id="SVGID_2_"  clipPathUnits="objectBoundingBox"> <use xlink:href="#SVGID_1_" overflow="visible"/> </clipPath> <path clip-path="url(#SVGID_2_)" fill="#2A2F3F" d="M3,1.967C3,1.985,2.984,2,2.965,2h-3.93C-0.984,2-1,1.985-1,1.967v-2.934 C-1-0.985-0.984-1-0.965-1h3.93C2.984-1,3-0.985,3-0.967V1.967z"/> </g> </g> </svg>  ',svgSearchIcon=exports.svgSearchIcon=' <svg class="search-icon" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="15px" height="15px" viewBox="230.042 230.042 15 15" enable-background="new 230.042 230.042 15 15" xml:space="preserve"> <g> <path fill="#898383" d="M244.708,243.077l-3.092-3.092c0.746-1.076,1.118-2.275,1.118-3.597c0-0.859-0.167-1.681-0.501-2.465 c-0.333-0.784-0.783-1.46-1.352-2.028s-1.244-1.019-2.027-1.352c-0.785-0.333-1.607-0.5-2.466-0.5s-1.681,0.167-2.465,0.5 s-1.46,0.784-2.028,1.352s-1.019,1.244-1.352,2.028s-0.5,1.606-0.5,2.465s0.167,1.681,0.5,2.465s0.784,1.46,1.352,2.028 s1.244,1.019,2.028,1.352c0.784,0.334,1.606,0.501,2.465,0.501c1.322,0,2.521-0.373,3.597-1.118l3.092,3.083 c0.217,0.229,0.486,0.343,0.811,0.343c0.312,0,0.584-0.114,0.812-0.343c0.228-0.228,0.342-0.499,0.342-0.812 C245.042,243.569,244.931,243.3,244.708,243.077z M239.241,239.241c-0.79,0.79-1.741,1.186-2.853,1.186s-2.062-0.396-2.853-1.186 c-0.79-0.791-1.186-1.741-1.186-2.853s0.396-2.063,1.186-2.853c0.79-0.791,1.741-1.186,2.853-1.186s2.062,0.396,2.853,1.186 s1.186,1.741,1.186,2.853S240.032,238.45,239.241,239.241z"/> </g> </svg>  ',svgShareIcon=exports.svgShareIcon=' <svg width="32" height="33.762001037597656" viewBox="0 0 32 33.762001037597656" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g><path d="M 22,6c0-3.212-2.788-6-6-6S 10,2.788, 10,6c0,3.212, 2.788,6, 6,6S 22,9.212, 22,6zM 16,14c-5.256,0-10,5.67-10,12.716s 20,7.046, 20,0S 21.256,14, 16,14z"></path></g></svg>  ';
},{}],6:[function(require,module,exports){
"use strict";function init_windowVars(){window._global_vimeoIframeAPIReady=!1,window._global_vimeoIframeAPILoading=!1,window._global_youtubeIframeAPIReady=0,window.dzsvg_fullscreen_counter=0,window.dzsvg_fullscreen_curr_video=null,window.backup_onYouTubePlayerReady=null,window.dzsvg_self_options={},window.dzsvp_self_options={},window.dzsvg_time_started=0,window.dzsvg_had_user_action=!1,window.dzsvg_default_settings||(window.dzsvg_default_settings={})}Object.defineProperty(exports,"__esModule",{value:!0}),exports.init_windowVars=init_windowVars;
},{}],7:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.adEnd = adEnd;
exports.checkForAdAlongTheWay = checkForAdAlongTheWay;
exports.check_if_ad_must_be_played = check_if_ad_must_be_played;
var _dzs_helpers = require("../js_common/_dzs_helpers");
var _Constants = require("../configs/Constants");
function checkForAdAlongTheWay(selfClass, argPerc) {
  if (selfClass.ad_array.length) {
    for (let i2 = 0; i2 < selfClass.ad_array.length; i2++) {
      if (selfClass.ad_array[i2].time < argPerc) {
        return Number(selfClass.ad_array[i2].time);
      }
    }
  }
  return null;
}
function adEnd(selfClass) {
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
  }, _Constants.PLAYER_DEFAULT_TIMEOUT);
}

/**
 *
 * @param {DzsVideoPlayer} selfClass
 * @returns {boolean}
 */
function check_if_ad_must_be_played(selfClass) {
  if (selfClass.cthis.attr('data-adsource') && selfClass.cthis.data('adplayed') !== 'on') {
    if ((0, _dzs_helpers.is_ios)()) {
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

},{"../configs/Constants":1,"../js_common/_dzs_helpers":3}],8:[function(require,module,exports){
"use strict";function video_play(e,t){var d={called_from:"default"};t&&(d=Object.assign(d,t));if("selfHosted"===e.dataType||"audio"===e.dataType||"dash"===e.dataType){var o=null;e._videoElement&&(o=e._videoElement.play()),void 0!==o&&null!==o&&o.then(function(){}).catch(function(t){if(console.log("[dzsvg] [player] fallback . autoplay muted"),e.cthis.addClass("autoplay-fallback--started-muted"),"retry_muted"===d.called_from)throw console.log("error when autoplaying - ",t,e._videoElement,e._videoElement.muted),new Error("retry not working even muted...");"auto"===e.initOptions.autoplayWithVideoMuted?(video_mute(e,{called_from:"play_video__retry_muted"}),video_play(e,Object.assign(d,{called_from:"retry_muted"}))):e.pauseMovie()}),e.cthis.hasClass("pattern-video")&&e.cthis.find(".the-video").each(function(){var e=jQuery(this);"play_from_loop"===d.called_from&&(e.get(0).currentTime=0),e.get(0).play()})}if("vimeo"===e.dataType){var a={method:"play"};(0,_dzsvg_helpers.vimeo_do_command)(e,a,e.vimeo_url)}"youtube"===e.dataType&&e._videoElement.playVideo&&e._videoElement.getPlayerState&&1!=e._videoElement.getPlayerState&&e._videoElement.playVideo()}function video_mute(e,t){"selfHosted"!==e.dataType&&"audio"!==e.dataType&&"dash"!==e.dataType||e._videoElement&&e._videoElement.setAttribute&&(e._videoElement.muted=!0,e._videoElement.setAttribute("muted",!0),e.cthis.addClass("is-muted")),"youtube"===e.dataType&&e._videoElement&&(e._videoElement.mute?(e._videoElement.mute(),e.cthis.addClass("is-muted")):console.log("[dzsvg] [warning] [youtube] video mute failed"))}Object.defineProperty(exports,"__esModule",{value:!0}),exports.video_mute=video_mute,exports.video_play=video_play;var _dzsvg_helpers=require("./_dzsvg_helpers");
},{"./_dzsvg_helpers":4}],9:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.VolumeControls = void 0;
var _dzsvg_svgs = require("../_dzsvg_svgs");
var _dzsvg_helpers = require("../_dzsvg_helpers");
class VolumeControls {
  /**
   *
   * @param {DzsVideoPlayer} selfClass
   */
  constructor(selfClass) {
    this.selfClass = selfClass;
  }
  constructVolumeInPlayer() {
    var selfClass = this.selfClass;
    var o = selfClass.initOptions;
    var struct_volume = '<div class="volumecontrols"></div>';
    if (selfClass._controlsRight) {
      selfClass._controlsRight.append(struct_volume);
    } else {
      selfClass._controlsDiv.append(struct_volume);
    }
    selfClass._volumeControls = selfClass.cthis.find('.volumecontrols');
    selfClass._volumeControls_real = selfClass.cthis.find('.volumecontrols');
    var str_volumeControls_struct = '<div class="volumeicon">';
    if (o.design_skin === 'skin_aurora' || o.design_skin === 'skin_default' || o.design_skin === 'skin_white') {
      str_volumeControls_struct += _dzsvg_svgs.svg_volume_icon;
    }
    str_volumeControls_struct += '</div><div class="volume_static">';
    if (o.design_skin === 'skin_default') {
      str_volumeControls_struct += _dzsvg_svgs.svg_default_volume_static;
    }
    if (o.design_skin === 'skin_reborn' || o.design_skin === 'skin_white') {
      for (var i2 = 0; i2 < 10; i2++) {
        str_volumeControls_struct += '<div class="volbar"></div>';
      }
    }
    str_volumeControls_struct += '</div><div class="volume_active">';
    if (o.design_skin === 'skin_default') {
      str_volumeControls_struct += _dzsvg_svgs.svg_volume_active_skin_default;
    }
    if (o.design_skin === 'skin_aurora') {
      ;
    }
    str_volumeControls_struct += '</div><div class="volume_cut"></div>';
    if (o.design_skin === 'skin_reborn') {
      str_volumeControls_struct += '<div class="volume-tooltip">VOLUME: 100</div>';
    }
    selfClass._volumeControls.append(str_volumeControls_struct);
  }
  set_volume_adjustVolumeBar(volumeAmount) {
    var selfClass = this.selfClass;
    var o = selfClass.initOptions;
    var volumeX = volumeAmount;
    if (o.design_skin === 'skin_reborn') {
      volumeX *= 10;
      volumeX = Math.round(volumeX);
      volumeX /= 10;
    }
    if (volumeX > 1) {
      volumeX = 1;
    }
    var volumeControl = selfClass.cthis.find('.volumecontrols').children();
    var aux = volumeX * (volumeControl.eq(1).width() + selfClass.volumeWidthOffset);
    if (o.design_skin === 'skin_reborn' || o.design_skin === 'skin_white') {
      if (selfClass._volumeControls_real) {
        var aux2 = volumeX * 10;
        selfClass._volumeControls_real.children('.volume_static').children().removeClass('active');
        for (var i = 0; i < aux2; i++) {
          selfClass._volumeControls_real.children('.volume_static').children().eq(i).addClass('active');
        }
        selfClass._volumeControls_real.children('.volume-tooltip').css({
          'right': 100 - aux2 * 10 + '%'
        });
        selfClass._volumeControls_real.children('.volume-tooltip').html('VOLUME: ' + aux2 * 10);
      }
    } else {
      volumeControl.eq(2).width(aux);
    }
  }
  set_volume(volumeAmount) {
    var selfClass = this.selfClass;
    if (volumeAmount >= 0) {
      if (selfClass._videoElement) {
        if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio') {
          selfClass._videoElement.volume = volumeAmount;
        }
        if (selfClass.dataType === 'youtube') {
          selfClass._videoElement.setVolume(volumeAmount * 100);
        }
      }
      if (selfClass.dataType === 'vimeo') {
        var vimeo_data = {
          "method": "setVolume",
          "value": volumeAmount
        };
        if (selfClass.vimeo_url) {
          (0, _dzsvg_helpers.vimeo_do_command)(selfClass, vimeo_data, selfClass.vimeo_url);
        }
      }
    }
    this.set_volume_adjustVolumeBar(volumeAmount);
    try {
      if (localStorage != null) {
        localStorage.setItem('volumeIndex', volumeAmount);
      }
    } catch (e) {}
  }

  /**
   * apply only once per video
   * @returns {boolean}
   */
  volume_setInitial() {
    var selfClass = this.selfClass;
    var o = selfClass.initOptions;
    if (selfClass.cthis.data('isVolumeAlreadySetInitial') || selfClass.hasCustomSkin === false) {
      return false;
    }
    selfClass.cthis.data('isVolumeAlreadySetInitial', true);
    if (o.defaultvolume === '') {
      o.defaultvolume = 'last';
    }
    if (isNaN(Number(o.defaultvolume))) {
      if (o.defaultvolume === 'last') {
        selfClass.volumeDefault = 1;
        try {
          if (localStorage != null) {
            if (localStorage.getItem('volumeIndex') !== undefined) {
              selfClass.volumeDefault = localStorage.getItem('volumeIndex');
            }
          }
        } catch (e) {}
      }
    } else {
      o.defaultvolume = Number(o.defaultvolume);
      selfClass.volumeDefault = o.defaultvolume;
    }
    selfClass.volumeDefault = Number(selfClass.volumeDefault);
    if (selfClass.volumeDefault > -0.1 || !isNaN(Number(selfClass.volumeDefault))) {
      // -- all well
    } else {
      selfClass.volumeDefault = 1;
    }
    if (!selfClass.shouldStartMuted) {
      selfClass.setupVolume(selfClass.volumeDefault, {
        'called_from': 'init, selfClass.volumeDefault'
      });
    } else {
      selfClass.volume_mute();
    }
  }
  volume_getVolume() {
    var selfClass = this.selfClass;
    if (selfClass._videoElement) {
      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
        return selfClass._videoElement.volume;
      }
      if (selfClass.dataType === 'youtube') {
        try {
          return Number(selfClass._videoElement.getVolume()) / 100;
        } catch (e) {
          console.log('[warn] e - ', e);
        }
      }
    }
    return 0;
  }
  player_volumeUnmute() {
    var selfClass = this.selfClass;
    var o = selfClass.initOptions;
    window.dzsvg_had_user_action = true;
    o.user_action = 'yet';
    if (selfClass._videoElement && selfClass._videoElement.removeAttribute) {
      selfClass._videoElement.muted = false;
      selfClass._videoElement.removeAttribute('muted');
    }
    if (selfClass._videoElement) {
      if (selfClass._videoElement.unMute) {
        selfClass._videoElement.unMute(); // -- youtube
      }
    }

    if (selfClass.is_muted_for_autoplay) {
      selfClass._videoElement.muted = false;
    }
    if (this.volume_getVolume() === 0) {
      if (!selfClass.volumeLast) {
        selfClass.volumeLast = 1;
      }
      selfClass.setupVolume(selfClass.volumeLast, {
        'called_from': 'volume_unmute()'
      });
    }
  }
}
exports.VolumeControls = VolumeControls;

},{"../_dzsvg_helpers":4,"../_dzsvg_svgs":5}],10:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.dash_setupPlayer = dash_setupPlayer;
exports.exitFullscreen = exitFullscreen;
exports.player_getResponsiveRatio = player_getResponsiveRatio;
exports.requestFullscreen = requestFullscreen;
var _dzs_helpers = require("../js_common/_dzs_helpers");
var _Constants = require("../configs/Constants");
function dash_setupPlayer(selfClass) {
  var dash_player = null,
    dash_context = null;
  function setup_dash() {
    dash_context = new Webm.di.WebmContext();
    dash_player = new MediaPlayer(dash_context);
    dash_player.startup();
    dash_player.attachView(video);
    if (selfClass.autoplayVideo === 'on') {
      dash_player.setAutoPlay(true);
    } else {
      dash_player.setAutoPlay(false);
    }
    dash_player.attachSource(selfClass.dataSrc);
  }
  if (!(selfClass && selfClass.dataSrc)) {
    console.log('[dzsvg][error] no selfclass .. no src ?? ');
    return false;
  }
  const baseUrl = window.dzsvg_settings && window.dzsvg_settings['libsUri'] ? window.dzsvg_settings.libsUri : '';
  (0, _dzs_helpers.loadScriptIfItDoesNotExist)(baseUrl + 'parts/player/dash.js', 'Webm').then(r => {
    setup_dash();
  });
}

/**
 *
 * @param {DzsVideoPlayer} selfClass
 * @param {object} pargs
 */
function player_getResponsiveRatio(selfClass, pargs) {
  var $ = jQuery;
  var o = selfClass.initOptions;
  var margs = {
    'reset_responsive_ratio': false,
    'called_from': 'default'
  };
  if (pargs) {
    margs = $.extend(margs, pargs);
  }
  if (margs.reset_responsive_ratio) {
    o.responsive_ratio = 'default';
  }
  if (o.responsive_ratio === 'detect') {
    if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'dash') {
      if (selfClass._videoElement && selfClass._videoElement.videoHeight) {
        o.responsive_ratio = selfClass._videoElement.videoHeight / selfClass._videoElement.videoWidth;
      } else {
        o.responsive_ratio = _Constants.PLAYER_DEFAULT_RESPONSIVE_RATIO;
      }
      if (selfClass._videoElement && selfClass._videoElement.addEventListener) {
        selfClass._videoElement.addEventListener('loadedmetadata', function () {
          o.responsive_ratio = selfClass._videoElement.videoHeight / selfClass._videoElement.videoWidth;
          selfClass.handleResize();
        });
      }
      if (selfClass.dataType === 'dash') {
        selfClass.dash_inter_check_sizes = setInterval(function () {
          if (selfClass._videoElement && selfClass._videoElement.videoHeight) {
            if (selfClass._videoElement.videoWidth > 0) {
              o.responsive_ratio = selfClass._videoElement.videoHeight / selfClass._videoElement.videoWidth;
              selfClass.handleResize();
              clearInterval(selfClass.dash_inter_check_sizes);
            }
          }
        }, 1000);
      }
    }
    if (selfClass.dataType === 'audio') {
      if (selfClass.cthis.find('.div-full-image').length) {
        var _cach = selfClass.cthis.find('.div-full-image').eq(0);
        var aux = _cach.css('background-image');
        aux = aux.replace(/"/g, '');
        aux = aux.replace("url(", '');
        aux = aux.replace(")", '');
        var img = new Image();
        img.onload = function () {
          o.responsive_ratio = this.naturalHeight / this.naturalWidth;
          selfClass.handleResize();
        };
        img.src = aux;
      }
    }
    if (selfClass.dataType === 'youtube') {
      o.responsive_ratio = _Constants.PLAYER_DEFAULT_RESPONSIVE_RATIO;
    }
    if (selfClass.dataType === 'vimeo') {
      o.responsive_ratio = _Constants.PLAYER_DEFAULT_RESPONSIVE_RATIO;
    }
    if (selfClass.dataType === 'inline') {
      o.responsive_ratio = _Constants.PLAYER_DEFAULT_RESPONSIVE_RATIO;
    }
  }
  o.responsive_ratio = Number(o.responsive_ratio);
  if (selfClass.cthis.hasClass('vp-con-laptop')) {
    o.responsive_ratio = '';
  }
}
function exitFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.mozCancelFullScreen) {
    document.mozCancelFullScreen();
  } else if (document.webkitExitFullscreen) {
    document.webkitExitFullscreen();
  }
  return null;
}
function requestFullscreen($elem_) {
  if ($elem_) {
    if ($elem_.requestFullScreen) {
      return $elem_.requestFullScreen();
    } else if ($elem_.webkitRequestFullScreen) {
      return $elem_.webkitRequestFullScreen();
    } else if ($elem_.mozRequestFullScreen) {
      return $elem_.mozRequestFullScreen();
    }
  }
  return null;
}

},{"../configs/Constants":1,"../js_common/_dzs_helpers":3}],11:[function(require,module,exports){
"use strict";function player_lifeCycle_setupMobile(e,r){const l=e.cthis,i=e.argOptions,s=l;if("selfHosted"===e.dataType&&(r.usePlayInline=!0,r.useCrossOrigin=!0),"vimeo"===e.dataType){s.children().remove();const a=e.dataSrc;s.append('<iframe width="100%" height="100%" src="//player.vimeo.com/video/'+a+'" frameborder="0"  allowFullScreen allow="autoplay;fullscreen" style=""></iframe>')}"default"===i.responsive_ratio&&((0,_player_helpers.player_getResponsiveRatio)(e,{called_from:"init_readyControls .. ios"}),i.responsive_ratio=.5625),l.addClass(_playerSettings.PLAYER_STATES.LOADED)}Object.defineProperty(exports,"__esModule",{value:!0}),exports.player_lifeCycle_setupMobile=player_lifeCycle_setupMobile;var _player_helpers=require("./_player_helpers"),_playerSettings=require("../configs/_playerSettings");
},{"../configs/_playerSettings":2,"./_player_helpers":10}],12:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.init_readyControls = init_readyControls;
exports.init_readyVideo = init_readyVideo;
exports.vplayerLifecycleInit = vplayerLifecycleInit;
exports.vplayerLifecycleReinit = vplayerLifecycleReinit;
var _dzsvg_helpers = require("../js_dzsvg/_dzsvg_helpers");
var _dzs_helpers = require("../js_common/_dzs_helpers");
var _Constants = require("../configs/Constants");
var _player_viewDraw = require("./view/_player_viewDraw");
var playerAdFunctions = _interopRequireWildcard(require("../js_dzsvg/_player_ad_functions"));
var _dzsvg_svgs = require("../js_dzsvg/_dzsvg_svgs");
var _playerSettings = require("../configs/_playerSettings");
var _viewFunctions = require("../shared/_viewFunctions");
var _player_helpers = require("./_player_helpers");
var _player_setupMedia = require("./_player_setupMedia");
var _player_setupAd = require("./_player_setupAd");
var _player_lifeCycle_setupMobile = require("./_player_lifeCycle_setupMobile");
function _getRequireWildcardCache(e) {
  if ("function" != typeof WeakMap) return null;
  var r = new WeakMap(),
    t = new WeakMap();
  return (_getRequireWildcardCache = function (e) {
    return e ? t : r;
  })(e);
}
function _interopRequireWildcard(e, r) {
  if (!r && e && e.__esModule) return e;
  if (null === e || "object" != typeof e && "function" != typeof e) return {
    default: e
  };
  var t = _getRequireWildcardCache(r);
  if (t && t.has(e)) return t.get(e);
  var n = {
      __proto__: null
    },
    a = Object.defineProperty && Object.getOwnPropertyDescriptor;
  for (var u in e) if ("default" !== u && {}.hasOwnProperty.call(e, u)) {
    var i = a ? Object.getOwnPropertyDescriptor(e, u) : null;
    i && (i.get || i.set) ? Object.defineProperty(n, u, i) : n[u] = e[u];
  }
  return n.default = e, t && t.set(e, n), n;
}
/**
 * The function is responsible for initializing a video player instance. Here's a breakdown of its main functionality: `vplayerLifecycleInit`
 * @param {DzsVideoPlayer} selfClass
 */
function vplayerLifecycleInit(selfClass) {
  const $ = jQuery;
  const o = selfClass.argOptions;
  const cthis = selfClass.cthis;
  cthis.removeClass('vplayer-tobe');
  cthis.addClass('vplayer dzsvp-inited');
  if (o.settings_disableVideoArray !== 'on') {
    selfClass.dzsvp_players_arr.push(cthis);
  }
  selfClass.isInited = true;
  $(window).off('scroll.' + selfClass.currentPlayerId);

  // -- get from attr
  (0, _dzsvg_helpers.playerHandleDeprecatedAttrSrc)(cthis);
  if ((0, _dzsvg_helpers.getDataOrAttr)(selfClass.cthis, 'data-sourcevp')) {
    selfClass.dataSrc = (0, _dzsvg_helpers.getDataOrAttr)(selfClass.cthis, 'data-sourcevp');
    selfClass.dataOriginalSrc = selfClass.cthis.attr('data-sourcevp');
  }
  if (cthis.attr('data-type')) {
    selfClass.dataType = cthis.attr('data-type');
    selfClass.dataOriginalType = cthis.attr('data-type');
  } else {
    if (o.type) {
      selfClass.dataOriginalType = o.type;
      selfClass.dataType = o.type;
    }
  }
  // -- get from attr END

  if (selfClass.dataType === 'normal' || selfClass.dataType === 'video') {
    selfClass.dataType = 'selfHosted';
  }
  const videoTypeAndSource = (0, _dzsvg_helpers.detect_video_type_and_source)(selfClass.dataSrc, null, selfClass.cthis);
  if (selfClass.dataOriginalType === '' || selfClass.dataOriginalType === 'detect') {
    cthis.attr('data-type', videoTypeAndSource.type);
    selfClass.dataType = videoTypeAndSource.type;
    if (o.playfrom === 'default') {
      if (videoTypeAndSource.playFrom) {
        o.playfrom = videoTypeAndSource.playFrom;
      }
    }
  }
  (0, _dzsvg_helpers.configureAudioPlayerOptionsInitial)(cthis, o, selfClass);
  if (o.action_video_end) {
    selfClass.action_video_end = o.action_video_end;
  }
  if (o.action_video_view) {
    selfClass.action_video_view = o.action_video_view;
  }
  if (selfClass.hasCustomSkin) {
    selfClass.cthis.addClass('has-custom-controls');
  }

  // -- we do not need the attr anymore.. hide it
  cthis.attr('data-sourcevp', '');
  cthis.data('data-sourcevp', selfClass.dataSrc);
  cthis.data('data-sourcevp-original', selfClass.dataOriginalSrc);
  if (selfClass.dataType === 'vimeo' || selfClass.dataType === 'youtube' || selfClass.dataType === 'dash') {
    selfClass.dataSrc = videoTypeAndSource.source;
  }
  o.type = selfClass.dataType;
  if (cthis.attr('data-is-360') === 'on') {
    selfClass.is360 = true;
  }
  if (selfClass.dataType === 'audio') {
    // -- on no circumstance audio can play
    if ((0, _dzs_helpers.is_mobile)()) {
      selfClass.autoplayVideo = 'off';
      o.autoplay = 'off';
    }
  }
  cthis.addClass('type-' + selfClass.dataType);
  selfClass.lastVideoType = selfClass.dataType;
  if (selfClass.dataType === 'vimeo') {
    if (o.vimeo_is_chromeless === 'on') {
      cthis.addClass('vimeo-chromeless');
    }
  }
  if (selfClass.dataType !== 'selfHosted' && selfClass.dataType !== 'video') {
    selfClass.is360 = false;
  }
  if (cthis.attr('data-adlink')) {
    selfClass.ad_link = cthis.attr('data-adlink');
  }

  // -- para nada?
  selfClass._rparent = cthis.parent();
  if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
    if (!window._global_youtubeIframeAPIReady && window.dzsvp_yt_iframe_settoload === false) {
      (0, _dzs_helpers.loadScriptIfItDoesNotExist)(_Constants.ConstantsDzsvg.YOUTUBE_IFRAME_API, '').then(r => {
        console.log('r - ', r);
      });
      window.dzsvp_yt_iframe_settoload = true;
    }
  }
  if (selfClass.dataType === 'inline') {
    if (o.htmlContent !== '') {
      cthis.html(o.htmlContent);
    }
    if (cthis.children().length > 0) {
      const _cach = cthis.children().eq(0);
      if (_cach.get(0)) {
        if (_cach.get(0).nodeName === 'IFRAME') {
          _cach.attr('width', '100%');
          _cach.attr('height', '100%');
        }
      }
    }
  }
  if (selfClass.isAd) {
    if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE && (0, _dzs_helpers.is_touch_device)() && $(window).width() < 700) {
      cthis.addClass('is-touch-device type-youtube');
    }
    o.settings_video_overlay = 'on';
  }
  selfClass.view_setupBasicStructure();
  if (cthis.get(0)) {
    cthis.get(0).fn_change_color_highlight = selfClass.classMisc.fn_change_color_highlight;
    cthis.get(0).api_handleResize = selfClass.handleResize;
    cthis.get(0).api_seek_to_perc = selfClass.seek_to_perc;
    cthis.get(0).api_currVideo_refresh_fsbutton = arg => {
      (0, _player_viewDraw.player_controls_drawFullscreenBarsOnCanvas)(selfClass, selfClass._controls_fs_canvas, arg);
    };
    cthis.get(0).api_reinit_cover_image = selfClass.classMisc.reinit_cover_image;
    cthis.get(0).api_restart_video = selfClass.restart_video;
    cthis.get(0).api_change_media = selfClass.change_media;
    cthis.get(0).api_ad_end = () => {
      playerAdFunctions.adEnd(selfClass);
    };
    cthis.get(0).api_action_set_video_end = function (arg) {
      selfClass.action_video_end = arg;
    };
    cthis.get(0).api_action_set_video_view = function (arg) {
      selfClass.action_video_view = arg;
    };
    cthis.get(0).api_action_set_video_play = function (arg) {
      selfClass.action_video_play = arg;
    };
    cthis.get(0).api_action_set_video_pause = function (arg) {
      selfClass.action_video_pause = arg;
    };
  }
  if (o.settings_big_play_btn === 'on') {
    let string_structureBigPlayBtn = (0, _player_viewDraw.player_controls_drawBigPlayBtn)();
    if (cthis.find('.controls').length) {
      cthis.find('.controls').before(string_structureBigPlayBtn);
    } else {
      cthis.append(string_structureBigPlayBtn);
    }
    cthis.find('.big-play-btn').on('click', selfClass.handleClickVideoOverlay);
    cthis.addClass('has-big-play-btn');
  } else {
    cthis.addClass('not-has-big-play-btn');
  }
  if (o.cueVideo === 'on' || (!(0, _dzs_helpers.is_ios)() || o.settings_ios_usecustomskin === 'on') && (selfClass.dataType === 'selfHosted' || selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE || selfClass.dataType === 'vimeo')) {
    if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
      selfClass.inter_checkYtIframeReady = setInterval(selfClass.classMisc.youtube_checkIfIframeIsReady, 100);
    } else {
      selfClass.init_readyControls(selfClass, null, {
        'called_from': 'init.. cue video'
      });
    }
  } else {
    selfClass.resizePlayer(selfClass.videoWidth, selfClass.videoHeight);
    cthis.on('click', init_readyControls);
    cthis.addClass('dzsvp-loaded');
  }
  setInterval(selfClass.classMisc.check_one_sec_for_adsOrTags, 1000);
  selfClass.classMisc.check_one_sec_for_adsOrTags();
}
function init_readyControls(selfClass, e, pargs) {
  (0, _viewFunctions.promise_allDependenciesMet)(selfClass, () => {
    if (selfClass.is360) {
      window.dzsvp_player_init360(selfClass);
    }
    init_readyControls_dependenciesMet(selfClass, e, pargs);
  });
}
function init_readyControls_dependenciesMet(selfClass, e, pargs) {
  const cthis = selfClass.cthis;
  const $ = jQuery;
  const o = selfClass.argOptions;
  let margs = {
    'reset_responsive_ratio': false,
    'check_source': true,
    'called_from': 'default'
  };
  if (pargs) {
    margs = Object.assign(margs, pargs);
  }
  const _c = cthis;
  _c.off();
  if (_c.attr('data-type') === _playerSettings.VIDEO_TYPES.YOUTUBE) {
    selfClass.dataSrc = (0, _dzsvg_helpers.youtube_sanitize_url_to_id)(selfClass.dataSrc);
  }
  let argsForVideoSetup = {};

  // -- ios video setup

  if (o.settings_ios_usecustomskin !== 'on' && (0, _dzs_helpers.is_ios)()) {
    // -- our job on the iphone / ipad has been done, we exit the function.
    (0, _player_lifeCycle_setupMobile.player_lifeCycle_setupMobile)(selfClass, argsForVideoSetup);
  }
  // -- end ios setup

  // -- selfHosted
  if (!(0, _dzs_helpers.is_ios)() || o.settings_ios_usecustomskin === 'on') {
    // -- selfHosted video on modern browsers
    if (o.settings_enableTags === 'on') {
      cthis.find('.dzstag-tobe').each(function () {
        var _tagElement = $(this);
        (0, _dzsvg_helpers.tagsSetupDom)(_tagElement);
      });
      selfClass.arrTags = cthis.find('.dzstag');
    }
    let aux = '';
    if (selfClass.dataType === 'audio') {
      if (selfClass.cthis.attr('data-audioimg') !== undefined) {
        aux = '<div style="background-image:url(' + selfClass.cthis.attr('data-audioimg') + ')" class="div-full-image from-type-audio"/>';
        selfClass._vpInner.prepend(aux);
      }
    }
    if (selfClass.dataType === 'selfHosted') {
      if (o.cueVideo !== 'on') {
        selfClass.autoplayVideo = 'off';
        argsForVideoSetup = {
          'preload': 'metadata',
          'called_from': 'init_readyControls .. cueVideo off'
        };
      } else {
        if (o.preload_method) {
          argsForVideoSetup.preload = o.preload_method;
          argsForVideoSetup.called_from = 'init_readyControls .. cueVideo on';
        }
      }
    }

    // --- type youtube
    if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
      // -- youtube
      argsForVideoSetup.youtube_useDefaultSkin = o.settings_youtube_usecustomskin !== 'on' || o.settings_ios_usecustomskin !== 'on' && (0, _dzs_helpers.is_ios)();
    }
    if (selfClass.dataType === 'dash') {
      (0, _player_helpers.dash_setupPlayer)();
    }
    if (margs.called_from === 'change_media') {
      argsForVideoSetup.isGoingToChangeMedia = true;
    }
    if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE && argsForVideoSetup.youtube_useDefaultSkin === false) {
      cthis.find('#the-media-' + selfClass.currentPlayerId).on('mousemove', selfClass.handle_mousemove);
    }
  }
  (0, _player_setupMedia.generatePlayerMarkupAndSource)(selfClass, argsForVideoSetup);

  // -- setup remainder

  if (selfClass.dataType === 'vimeo') {
    if (window.addEventListener) {
      window.addEventListener('message', selfClass.vimeo_windowMessage, false);
    }
  }
  if (selfClass.autoplayVideo === 'on') {
    selfClass.wasPlaying = true;
  }
  selfClass.handleResize();
  (0, _player_helpers.player_getResponsiveRatio)(selfClass, {
    'called_from': 'init .. readyControls'
  });
  ;
  if (margs.called_from !== 'change_media' && String(margs.called_from).indexOf('retry') === -1) {
    init_final(selfClass);
  }
}
function init_final(selfClass) {
  const cthis = selfClass.cthis;
  const o = selfClass.argOptions;
  const $ = jQuery;
  if (cthis.get(0)) {
    if (!cthis.get(0).externalPauseMovie) {
      cthis.get(0).externalPauseMovie = selfClass.pauseMovie;
      cthis.get(0).externalPlayMovie = selfClass.playMovie;
      cthis.get(0).api_pauseMovie = selfClass.pauseMovie;
      cthis.get(0).api_playMovie = selfClass.playMovie;
      cthis.get(0).api_get_responsive_ratio = (pargs = {}) => {
        (0, _player_helpers.player_getResponsiveRatio)(selfClass, pargs);
      };
    }
  }
  cthis.on('click', '.cover-image:not(.from-type-image), .div-full-image.from-type-audio', selfClass.handleClickCoverImage);
  cthis.addClass('dzsvp-loaded');
  selfClass.inter_videoReadyState = setInterval(selfClass.check_videoReadyState, 50);
  if (selfClass.is360) {
    $(selfClass._videoElement).on('click', function (e) {
      if (selfClass.isInitialPlayed === false) {
        selfClass.playMovie();
        selfClass.mouse_is_over();
      }
    });
    window.dzsvp_player_360_eventFunctionsInit(selfClass);
  }
  const _scrubbar = cthis.find('.scrubbar').eq(0);
  _scrubbar.on('touchstart', function (e) {
    selfClass.scrubbar_moving = true;
  });
  if (o.ad_show_markers === 'on') {
    (0, _player_setupAd.ads_view_setupMarkersOnScrub)(selfClass);
  }
  $(document).on('touchmove', function (e) {
    if (selfClass.scrubbar_moving) {
      let scrubbar_moving_x = e.originalEvent.touches[0].pageX;
      var aux3 = scrubbar_moving_x - _scrubbar.offset().left;
      if (aux3 < 0) {
        aux3 = 0;
      }
      if (aux3 > _scrubbar.width()) {
        aux3 = _scrubbar.width();
      }
      selfClass.seek_to_perc(aux3 / _scrubbar.width());
    }
  });
  $(document).on('touchend', function (e) {
    selfClass.scrubbar_moving = false;
  });
  $(window).on('resize', selfClass.handleResize);
  o.settings_trigger_resize = parseInt(o.settings_trigger_resize, 10);
  if (o.settings_trigger_resize > 0) {
    setInterval(function () {
      selfClass.handleResize(null, {
        'called_from': 'recheck_sizes'
      });
    }, o.settings_trigger_resize);
  }
  ;
  if ((0, _dzs_helpers.is_touch_device)()) {
    cthis.addClass('is-touch');
  }
}

/**
 * should init after setup controls
 */
function vplayerLifecycleReinit(selfClass) {
  // console.log('reinit');

  const cthis = selfClass.cthis;
  const o = selfClass.argOptions;
  if (cthis.attr('data-loop') === 'on') {
    selfClass.isLoop = true;
  }
  (0, _dzsvg_helpers.reinitPlayerOptions)(selfClass, o);
  selfClass.classMisc.reinit_cover_image();
  let extraFeedBeforeRightControls = '';
  const $extraFeedBeforeRightControls = selfClass.cthis.find('.dzsvg-feed--extra-html-before-right-controls').eq(0);
  if ($extraFeedBeforeRightControls.length) {
    extraFeedBeforeRightControls = $extraFeedBeforeRightControls.html();
  }
  if (extraFeedBeforeRightControls) {
    extraFeedBeforeRightControls = String(extraFeedBeforeRightControls).replace('{{svg_quality_icon}}', _dzsvg_svgs.svg_quality_icon);
    if (selfClass._controlsRight) {
      selfClass._controlsRight.prepend(extraFeedBeforeRightControls);
    } else {
      if (selfClass._timetext) {
        selfClass._timetext.after(extraFeedBeforeRightControls);
      }
    }
  }
  (0, _dzsvg_helpers.player_setQualityLevels)(selfClass);
}

/**
 * this function will assign listeners to the player and selfClass.autoplayVideo if the selfClass.autoplayVideo is set to on
 * @param {DzsVideoPlayer} selfClass selfClass
 * @param {object} pargs parameters
 */
function init_readyVideo(selfClass, pargs) {
  const cthis = selfClass.cthis;
  const o = selfClass.argOptions;
  const $ = jQuery;
  var margs = {
    'called_from': 'default'
  };
  if (pargs) {
    margs = Object.assign(margs, pargs);
  }
  selfClass.isInitedReadyVideo = true;
  clearInterval(selfClass.inter_videoReadyState);
  selfClass.volumeClass.volume_setInitial();
  if (selfClass.videoWidth === 0) {
    selfClass.videoWidth = cthis.width();
    selfClass.videoHeight = cthis.height();
  }
  cthis.addClass('dzsvp-loaded');
  if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
    selfClass.qualities_youtubeCurrentQuality = selfClass._videoElement.getPlaybackQuality();
  }
  if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'selfHosted') {
    if (o.default_playbackrate && o.default_playbackrate !== '1') {
      setTimeout(function () {}, 1000);
      if (selfClass._videoElement && selfClass._videoElement.playbackRate) {
        selfClass._videoElement.playbackRate = Number(o.default_playbackrate);
      }
    }
  }
  selfClass.videoWidth = cthis.outerWidth();
  selfClass.videoHeight = cthis.outerHeight();
  selfClass.resizePlayer(selfClass.videoWidth, selfClass.videoHeight);
  var checkInter = setInterval(selfClass.handleEnterFrame, 100);
  if (selfClass.autoplayVideo === 'on') {
    if (selfClass.dataType !== 'vimeo') {
      selfClass.playMovie({
        'called_from': 'autoplay - on'
      });
    }
  }
  if (o.playfrom !== 'default') {
    if (o.playfrom === 'last' && selfClass.id_player !== '') {
      if (typeof Storage != 'undefined') {
        try {
          if (typeof localStorage['dzsvp_' + selfClass.id_player + '_lastpos'] != 'undefined') {
            if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio') {
              selfClass._videoElement.currentTime = Number(localStorage['dzsvp_' + selfClass.id_player + '_lastpos']);
            }
            if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
              selfClass._videoElement.seekTo(Number(localStorage['dzsvp_' + selfClass.id_player + '_lastpos']));
              if (!selfClass.wasPlaying) {
                selfClass.pauseMovie({
                  'called_from': '_init_readyVideo()'
                });
              }
            }
          }
        } catch (e) {}
      }
    }
    if (isNaN(Number(o.playfrom)) === false) {
      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio') {
        selfClass._videoElement.currentTime = o.playfrom;
      }
    }
  }
  selfClass.handleEnterFrame({
    skin_play_check: true
  });

  // -- we include this for both ads and selfHosted videos
  cthis.on('mouseleave', selfClass.handleMouseout);
  cthis.on('mouseover', selfClass.handleMouseover);
  cthis.on('click', '.mute-indicator', selfClass.handle_mouse);
  selfClass._vpInner.on('click', '.controls .playcontrols', selfClass.handleClickPlayPause);
  if (o.settings_disableControls !== 'on') {
    // -- only for selfHosted videos

    cthis.find('.controls').eq(0).on('mouseover', selfClass.handle_mouse);
    cthis.find('.controls').eq(0).on('mouseout', selfClass.handle_mouse);
    cthis.on('mousemove', selfClass.handle_mousemove);
    $(document).on('keyup.dzsvgp', selfClass.handleKeyPress);
    cthis.on('click', '.quality-option', selfClass.handle_mouse);
    if (selfClass.$fullscreenControl) {
      selfClass.$fullscreenControl.off('click', selfClass.fullscreenToggle);
      selfClass.$fullscreenControl.on('click', selfClass.fullscreenToggle);
    }
    if (selfClass.scrubbar) {
      selfClass.scrubbar.on('click', selfClass.handleScrub);
      selfClass.scrubbar.on('mousedown', selfClass.handle_mouse);
      selfClass.scrubbar.on('mousemove', selfClass.handleScrubMouse);
      selfClass.scrubbar.on('mouseout', selfClass.handleScrubMouse);
    }
    cthis.on('mouseleave', selfClass.handleScrubMouse);
    selfClass._vpInner.find('.touch-play-btn').on('click touchstart', selfClass.handleClickPlayPause);
    selfClass._vpInner.find('.mutecontrols-con').on('click', selfClass.volume_handleClickMuteIcon);
    document.addEventListener('fullscreenchange', selfClass.handleFullscreenChange, false);
    document.addEventListener('webkitfullscreenchange', selfClass.handleFullscreenChange, false);
    document.addEventListener('webkitendfullscreen', selfClass.handleFullscreenChange, false);
    selfClass._videoElement.addEventListener('webkitendfullscreen', selfClass.handleFullscreenEnd);
    selfClass._videoElement.addEventListener('endfullscreen', selfClass.handleFullscreenEnd);
    if ((0, _dzs_helpers.is_mobile)()) {
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        if (o.settings_video_overlay === 'on') {
          cthis.find('.controls').eq(0).css('pointer-events', 'none');
          cthis.find('.controls .playcontrols').eq(0).css('pointer-events', 'auto');
        }
      }
    } else {
      if (selfClass.is360) {
        o.settings_video_overlay = 'off';
      }
    }
  } else {
    // -- if we disableControls ( for ad for example )
    // -- disable controls except volume / probably because its a advertisment

    if (selfClass.isAd && selfClass.autoplayVideo === 'off') {}
    if ((0, _dzs_helpers.is_ios)() || (0, _dzs_helpers.is_android)()) {
      selfClass.$playcontrols.css({
        'opacity': 0.9
      });
      selfClass.$playcontrols.on('click', selfClass.handleClickPlayPause);
      o.settings_hideControls = 'off';
      cthis.removeClass('hide-on-paused');
      cthis.removeClass('hide-on-mouse-out');
      if (selfClass.isAd) {
        // -- if this is an ad

        selfClass.autoplayVideo = 'on';
        o.autoplay = 'on';
        o.cue = 'on';
        cthis.find('.video-overlay').append('<div class="warning-mobile-ad">' + 'You need to click here for the ad for to start' + '</div>');
      }
    }
  }
  $(selfClass._videoElement).on('play', selfClass.handleVideoEvent);
  if ((0, _dzs_helpers.is_ios)() && o.settings_ios_usecustomskin === 'off') {
    o.settings_video_overlay = 'off';
  }
  if (o.settings_video_overlay === 'on') {
    let str_video_overlay = '<div class="video-overlay"></div>';
    cthis.find('.dzsvg-video-container').eq(0).after(str_video_overlay);
    cthis.on('click', '.video-overlay', selfClass.handleClickVideoOverlay);
    cthis.on('dblclick', '.video-overlay', selfClass.fullscreenToggle);
  }
  if (o.video_description_style === 'gradient') {
    let aux3 = '<div class="video-description video-description-style-' + o.video_description_style + '"><div>';
    aux3 += selfClass.dataVideoDesc;
    aux3 += '</div></div>';
    if (cthis.find('.big-play-btn').length) {
      cthis.find('.big-play-btn').eq(0).before(aux3);
    } else {
      if (cthis.find('.video-overlay').length) {
        cthis.find('.video-overlay').eq(0).after(aux3);
      } else {
        cthis.find('.controls').before(aux3);
      }
    }
  }
  window.dzsvg_handle_mouse = selfClass.handle_mouse;
  if (selfClass._volumeControls_real) {
    selfClass._volumeControls_real.on('mousedown', selfClass.handle_mouse);
    selfClass._volumeControls_real.on('click', selfClass.handleMouseOnVolume);
  }
  $(document).on('mouseup.dzsvg', window.dzsvg_handle_mouse);
  if (o.settings_hideControls === 'on') {
    selfClass._controlsDiv.hide();
  }
  if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio') {
    selfClass._videoElement.addEventListener('ended', selfClass.handleVideoEnd, false);
    if ((0, _dzs_helpers.is_ios)() && video && selfClass.isAd) {
      selfClass._videoElement.addEventListener('webkitendfullscreen', function () {
        if (selfClass._videoElement.currentTime > selfClass._videoElement.duration * 0.75) {
          selfClass.handleVideoEnd();
        }
      }, false);
    }
  }
  if (cthis.children('.subtitles-con-input').length || o.settings_subtitle_file) {
    selfClass.classMisc.setup_subtitle();
  }
  setTimeout(selfClass.handleResize, 500);
  cthis.get(0).api_destroy_listeners = destroy_listeners(selfClass);
}

/**
 *
 * @param {DzsVideoPlayer} selfClass
 * @returns {(function(): void)|*}
 */
function destroy_listeners(selfClass) {
  const cthis = selfClass.cthis;
  const $ = jQuery;
  return function () {
    cthis.off('mouseout', selfClass.handleMouseout);
    cthis.off('mouseover', selfClass.handleMouseover);
    cthis.find('.controls').eq(0).off('mouseover', selfClass.handle_mouse);
    cthis.find('.controls').eq(0).off('mouseout', selfClass.handle_mouse);
    cthis.off('mousemove', selfClass.handle_mousemove);
    cthis.off('keydown', selfClass.handleKeyPress);
    selfClass.$fullscreenControl.off('click', selfClass.fullscreenToggle);
    selfClass.scrubbar.off('click', selfClass.handleScrub);
    selfClass.scrubbar.off('mousedown', selfClass.handle_mouse);
    selfClass.scrubbar.off('mousemove', selfClass.handleScrubMouse);
    selfClass.scrubbar.off('mouseout', selfClass.handleScrubMouse);
    cthis.off('mouseleave', selfClass.handleScrubMouse);
    cthis.off('click');
    cthis.find('.mutecontrols-con').off('click', selfClass.volume_handleClickMuteIcon);
    document.removeEventListener('fullscreenchange', selfClass.handleFullscreenChange, false);
    if (selfClass.$parentGallery == null) {
      $(window).off('resize', selfClass.handleResize);
    }
    selfClass._videoElement.removeEventListener('ended', selfClass.handleVideoEnd, false);
  };
}

},{"../configs/Constants":1,"../configs/_playerSettings":2,"../js_common/_dzs_helpers":3,"../js_dzsvg/_dzsvg_helpers":4,"../js_dzsvg/_dzsvg_svgs":5,"../js_dzsvg/_player_ad_functions":7,"../shared/_viewFunctions":17,"./_player_helpers":10,"./_player_lifeCycle_setupMobile":11,"./_player_setupAd":13,"./_player_setupMedia":14,"./view/_player_viewDraw":16}],13:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.player_setup_skipad = exports.player_setupAd = exports.ads_view_setupMarkersOnScrub = exports.ads_decode_ads_array = void 0;
var _dzs_helpers = require("../js_common/_dzs_helpers");
/**
 *
 * @param {string} initialVal
 */
const ads_sanitizeForSource = initialVal => {
  let fout = initialVal;
  fout = fout.replace(/{{doublequot_fordzsvgad}}/g, '"');
  fout = fout.replace(/&lt;/g, '<');
  fout = fout.replace(/&gt;/g, '>');
  return fout;
};
const ads_view_setupMarkersOnScrub = selfClass => {
  for (var i2 = 0; i2 < selfClass.ad_array.length; i2++) {
    selfClass.cthis.find('.scrubbar .scrub').eq(0).before('<span class="reclam-marker" style="left: ' + selfClass.ad_array[i2].time * 100 + '%"></span>');
  }
};
exports.ads_view_setupMarkersOnScrub = ads_view_setupMarkersOnScrub;
const ads_decode_ads_array = selfClass => {
  // -- decode after parsing json
  for (var lab in selfClass.ad_array) {
    selfClass.ad_array[lab].source = ads_sanitizeForSource(selfClass.ad_array[lab].source);
  }
};
exports.ads_decode_ads_array = ads_decode_ads_array;
const player_setupAd = (selfClass, arg, pargs) => {
  var margs = {
    'called_from': 'default'
  };
  if (pargs) {
    margs = Object.assign(margs, pargs);
  }
  var o = selfClass.initOptions;
  const {
    source,
    type,
    skip_delay,
    ad_link
  } = selfClass.ad_array[arg];
  var ad_time = selfClass.ad_array[arg].time;
  selfClass.ad_array.splice(arg, 1);
  selfClass.cthis.appendOnce('<div class="ad-container"></div>');
  selfClass.$adContainer = selfClass.cthis.find('.ad-container').eq(0);
  var stringVplayerAdStructure = '<div class="vplayer-tobe"';
  if (type !== 'inline') {
    stringVplayerAdStructure += ' data-sourcevp="' + source + '"';
  }
  if (type) {
    stringVplayerAdStructure += ' data-type="' + type + '"';
  }
  if (ad_link) {
    stringVplayerAdStructure += ' data-adlink="' + ad_link + '"';
  }
  if (skip_delay) {
    stringVplayerAdStructure += ' data-adskip_delay="' + skip_delay + '"';
  }
  stringVplayerAdStructure += '>';
  if (type === 'inline') {
    stringVplayerAdStructure += '<div class="feed-dzsvg--inline-content">' + source + '</div>';
  }
  stringVplayerAdStructure += '</div>';
  selfClass.$adContainer.show();
  selfClass.$adContainer.append(stringVplayerAdStructure);
  var argsForVideoPlayerAd = {};
  argsForVideoPlayerAd.design_skin = o.design_skin;
  argsForVideoPlayerAd.cueVideo = 'on';
  argsForVideoPlayerAd.is_ad = 'on';
  argsForVideoPlayerAd.parent_player = selfClass.cthis;
  argsForVideoPlayerAd.user_action = o.user_action;
  selfClass.isAdPlaying = true;
  argsForVideoPlayerAd.autoplay = 'on';
  if (ad_time < 0.1 && (0, _dzs_helpers.is_mobile)()) {
    // this is invisible

    selfClass.$adContainer.children('.vplayer-tobe').addClass('mobile-pretime-ad');
    selfClass.cthis.addClass('pretime-ad-setuped');
    if (o.gallery_object) {
      o.gallery_object.addClass('pretime-ad-setuped');
    }
  }
  selfClass.$adContainer.children('.vplayer-tobe').addClass('dzsvg-recla');
  selfClass.$adContainer.children('.vplayer-tobe').vPlayer(argsForVideoPlayerAd);
  if (o.gallery_object) {
    if (o.gallery_object.get(0) && o.gallery_object.get(0).api_ad_block_navigation) {
      o.gallery_object.get(0).api_ad_block_navigation();
    }
  }
  setTimeout(function () {
    selfClass.cthis.addClass('ad-playing');
  }, 100);
  selfClass.isAdPlaying = true;
  selfClass.pauseMovie({
    'called_from': 'ad_setup'
  });
};
exports.player_setupAd = player_setupAd;
const player_setup_skipad = selfClass => () => {
  var translate_skipad = 'Skip Ad';
  var dzsvg_translate_youcanskipto = 'you can skip to video in ';
  if (window.dzsvg_translate_youcanskipto) {
    dzsvg_translate_youcanskipto = window.dzsvg_translate_youcanskipto;
  }
  if (window.dzsvg_translate_skipad) {
    translate_skipad = window.dzsvg_translate_skipad;
  }
  let inter_time_counter_skipad = null;
  let time_counter_skipad = null;
  let $ = jQuery;
  if (selfClass.ad_status === 'first_played') {
    return false;
  }
  if (selfClass.isAd) {
    let skipad_timer = 0;
    if (selfClass.dataType === 'image' || selfClass.dataType === 'inline') {
      skipad_timer = 0;
    }
    if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'youtube') {
      skipad_timer = 1001;
    }
    selfClass.cthis.appendOnce('<div class="skipad-con"></div>', '.skipad-con');
    selfClass.$adSkipCon = selfClass.cthis.find('.skipad-con').eq(0);
    if ((0, _dzs_helpers.is_mobile)() && selfClass.cthis.attr('data-adskip_delay')) {
      // -- TBC - skip ad notice on mobile
      selfClass.$adSkipCon.html("Play the ad for the skip ad counter to appear");
      selfClass.$adSkipCon.attr('data-ad-status', 'waiting_for_play');
    }
    if (typeof selfClass.cthis.attr('data-adskip_delay') != 'undefined') {
      skipad_timer = Number(selfClass.cthis.attr('data-adskip_delay'));
    }
    time_counter_skipad = skipad_timer;
    if (skipad_timer !== 1001) {
      setTimeout(function () {
        time_counter_skipad = 0;
        selfClass.$adSkipCon.html(translate_skipad);
        selfClass.$adSkipCon.on('click', function () {
          if ($(this).attr('data-ad-status') === 'can_be_clicked_for_end_ad') {
            selfClass.handleVideoEnd();
          }
        });
        selfClass.$adSkipCon.attr('data-ad-status', 'can_be_clicked_for_end_ad');
      }, skipad_timer * 1000);
      if (skipad_timer > 0) {
        inter_time_counter_skipad = setInterval(tick_counter_skipad, 1000);
      }
    }
  }
  selfClass.ad_status = 'first_played';
  function tick_counter_skipad() {
    if (time_counter_skipad > 0) {
      time_counter_skipad = time_counter_skipad - 1;
      if (selfClass.$adSkipCon) {
        selfClass.$adSkipCon.html(dzsvg_translate_youcanskipto + time_counter_skipad);
        selfClass.$adSkipCon.attr('data-ad-status', 'ad_status_is_ticking');
      }
    } else {
      clearInterval(inter_time_counter_skipad);
    }
  }
};
exports.player_setup_skipad = player_setup_skipad;

},{"../js_common/_dzs_helpers":3}],14:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.generatePlayerMarkupAndSource = generatePlayerMarkupAndSource;
var _dzsvg_helpers = require("../js_dzsvg/_dzsvg_helpers");
var _Constants = require("../configs/Constants");
var _dzs_helpers = require("../js_common/_dzs_helpers");
/**
 * setup video player here .. setup_video
 * @param {DzsVideoPlayer} selfClass
 * @param pargsForVideoSetup
 * @returns {boolean}
 */

function generatePlayerMarkupAndSource(selfClass, pargsForVideoSetup) {
  var argsForVideoSetup = {
    'preload': 'auto',
    'called_from': 'default',
    'is_dash': false,
    'useCrossOrigin': false,
    'usePlayInline': true,
    'useAudioDimensions': true,
    isGoingToChangeMedia: false,
    is360: false,
    Dzsvg360: null // -- add the 360 class here if need be
  };

  if (pargsForVideoSetup) {
    argsForVideoSetup = Object.assign(argsForVideoSetup, pargsForVideoSetup);
  }
  var o = selfClass.initOptions;
  var struct_video_element = '';
  var posterSourceStr = '';
  var str_crossorigin = ' crossorigin="anonymous"';
  if (selfClass.cthis.attr('data-img')) {
    posterSourceStr = ' poster="' + selfClass.cthis.attr('data-img') + '"';
  }
  var shouldAutoplay = false;
  if (selfClass.autoplayVideo === 'on' && o.settings_mode !== 'videowall') {
    shouldAutoplay = true;
  }

  // -- if we have user action on page it's enough for autoplay with sound
  if (o.old_curr_nr > -1) {
    if (o.autoplayWithVideoMuted === 'on') {
      if (1) {
        if (o.autoplay === 'on') {
          o.autoplayWithVideoMuted = 'off';
        }
      }
    }
  }
  if (argsForVideoSetup.isGoingToChangeMedia) {
    if (selfClass._vpInner) {
      selfClass._vpInner.find('.fullscreen-video-element').remove();
      selfClass._vpInner.find('.video-element').remove();
    }
  }
  if (selfClass.dataType === 'selfHosted') {
    if (o.is_ad === 'on' && (0, _dzs_helpers.is_mobile)()) {
      argsForVideoSetup.preload = 'metadata';
    }
    if ((0, _dzs_helpers.is_ios)() && o.settings_ios_usecustomskin === 'off') {
      argsForVideoSetup.preload = 'auto';
    }
    struct_video_element = '<video class="the-video video-element" preload="' + argsForVideoSetup.preload + '" ';
    if (selfClass.shouldStartMuted) {
      struct_video_element += ' muted';
    }
    if (o.touch_play_inline === 'on' || argsForVideoSetup.usePlayInline) {
      struct_video_element += ' webkit-playsinline playsinline';
    }
    if (!selfClass.hasCustomSkin) {
      struct_video_element += ' controls="true"';
    }
    if (argsForVideoSetup.useCrossOrigin) {
      struct_video_element += str_crossorigin;
    }
    if (selfClass.videoWidth !== 0) {
      struct_video_element += ' width="100%"';
      struct_video_element += ' height="100%"';
    }
    struct_video_element += '></video>';
  }
  if (selfClass.dataType === 'audio') {
    struct_video_element = '<audio class="the-video video-element" preload="' + argsForVideoSetup.preload + '" ';
    if (argsForVideoSetup.useAudioDimensions) {
      struct_video_element += ' width="100%"';
      struct_video_element += ' height="100%"';
    }
    struct_video_element += '>';
    if (selfClass.dataSrc) {
      struct_video_element += '<source src="' + selfClass.dataSrc + '" type="audio/mp3"/>';
    }
    struct_video_element += '</audio>';
  }
  if (selfClass.dataType === 'youtube') {
    var youtubeAPIArgs = {};
    selfClass._vpInner.children('.cmedia-con').remove();
    struct_video_element = '<span class="cmedia-con youtube-player-con"><span class="video-element" id="the-media-' + selfClass.currentPlayerId + '"></span></span>';
    if (o.cueVideo === 'off') {
      selfClass.autoplayVideo = 'off';
    }
    var playfrom = '';
    if (o.playfrom !== 'default') {
      // -- setup last position youtube
      if (o.playfrom === 'last' && selfClass.id_player !== '') {
        try {
          if (typeof Storage != 'undefined') {
            if (typeof localStorage['dzsvp_' + selfClass.id_player + '_lastpos'] != 'undefined') {
              playfrom = Number(localStorage['dzsvp_' + selfClass.id_player + '_lastpos']);
            }
          }
        } catch (e) {}
      }
      if (isNaN(Number(o.playfrom)) === false) {
        playfrom = Number(o.playfrom);
      }
    }

    // -- custom controls
    // -- youtube no controls

    var param_autoplay = 0;
    if (shouldAutoplay) {
      param_autoplay = 1;
    }
    var playerVars = {
      'autoplay': param_autoplay,
      controls: 0,
      'showinfo': 0,
      'playsinline': 1,
      rel: 0,
      autohide: 1,
      start: playfrom,
      wmode: 'transparent',
      iv_load_policy: 3,
      modestbranding: 1,
      enablejsapi: 1,
      disablekb: 1
    };

    // -- youtube

    if (selfClass.shouldStartMuted) {
      playerVars.mute = 1;
    }
    youtubeAPIArgs = {
      height: '100%',
      width: '100%',
      playerVars: playerVars,
      videoId: selfClass.dataSrc,
      suggestedQuality: o.settings_suggestedQuality,
      events: {
        'onReady': youtube_onPlayerReady,
        'onStateChange': youtube_onPlayerStateChange,
        'onPlaybackQualityChange': onPlayerPlaybackQualityChange
      }
    };
    if (argsForVideoSetup.youtube_useDefaultSkin) {
      // -- init ready controls, not custom skin ( default skin )
      selfClass._vpInner.children(':not(.cover-image)').remove();
      youtubeAPIArgs.playerVars.controls = 1;
    }
  }

  /**
   * vimeo
   */
  if (selfClass.dataType === 'vimeo') {
    var src = selfClass.dataSrc;
    var str_autoplay = '';
    if (shouldAutoplay) {
      str_autoplay = '&autoplay=1';
      selfClass.cthis.find('.cover-image').removeClass('is-visible');
    }
    if (selfClass._vpInner && o.vimeo_is_chromeless !== 'on') {
      selfClass._vpInner.children('.controls').remove();
    }
    var str_allowFullscreen = 'webkitAllowFullScreen mozallowfullscreen allowFullScreen';
    var str_controls_disable_param = '';
    if (selfClass.hasCustomSkin) {
      str_controls_disable_param = '&controls=0';
    }
    var str_source = 'https:' + '//player.vimeo.com/video/' + src + '?api=1&color=' + o.vimeo_color + '&title=' + o.vimeo_title + str_controls_disable_param + '&byline=' + o.vimeo_byline + '&portrait=' + o.vimeo_portrait + '&badge=' + o.vimeo_badge + '&player_id=vimeoplayer' + src + str_autoplay;
    struct_video_element = '<div class="fullscreen-video-element"><iframe allow="fullscreen;autoplay" class="vimeo-iframe video-element from-simple" scrolling="no" src="' + str_source + '" width="100%" height="100%"   ' + str_allowFullscreen + ' style=""></iframe></div>';
  }
  selfClass._fullscreenVideoElement = null;
  selfClass._vpInner.prepend('<div class="dzsvg-video-container">' + struct_video_element + '</div>');
  if (selfClass.dataType === 'inline') {
    selfClass._vpInner.find('.dzsvg-video-container').append(selfClass.cthis.find('.feed-dzsvg--inline-content'));
    selfClass.cthis.find('.feed-dzsvg--inline-content').removeClass('feed-dzsvg');
  }
  if (selfClass.dataType === 'vimeo') {
    selfClass._videoElement = selfClass._vpInner.find('.vimeo-iframe').get(0);
    selfClass._fullscreenVideoElement = selfClass._vpInner.find('.fullscreen-video-element').get(0);
  }
  if (selfClass.dataType === 'youtube') {
    selfClass._videoElement = new window.YT.Player('the-media-' + selfClass.currentPlayerId, youtubeAPIArgs);
  }
  if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
    selfClass._videoElement = selfClass.cthis.find('.video-element').get(0);
  }
  if (!selfClass._fullscreenVideoElement) {
    selfClass._fullscreenVideoElement = selfClass._videoElement;
  }
  if (argsForVideoSetup.is_dash) {
    return false;
  }
  if (selfClass.dataType === 'audio') {
    if (selfClass.cthis.attr('data-sourceogg')) {
      jQuery(selfClass._videoElement).eq(0).append('<source src="' + selfClass.cthis.attr('data-sourceogg') + '" type="audio/ogg"/>');
    }
    if (selfClass.cthis.attr('data-sourcewav')) {
      jQuery(selfClass._videoElement).eq(0).append('<source src="' + selfClass.cthis.attr('data-sourcewav') + '" type="audio/wav"/>');
    }
  }
  if (selfClass.dataType === 'vimeo') {
    const handleVimeoFullscreen = e => {
      if (!(0, _dzsvg_helpers.fullscreen_status)()) {
        viewRemoveFullscreenClass(jQuery(e.target));
      }
    };
    selfClass._videoElement.addEventListener("webkitfullscreenchange", handleVimeoFullscreen, true);
    selfClass._videoElement.addEventListener("fullscreenchange", handleVimeoFullscreen, true);
    selfClass._videoElement.addEventListener("webkitendfullscreen", handleVimeoFullscreen, true);
    selfClass._videoElement.addEventListener("resize", handleVimeoFullscreen, true);
    function vimeo_addListeners() {
      if (window.Vimeo) {
        if (window._global_vimeoIframeAPILoading_inter) {
          clearInterval(window._global_vimeoIframeAPILoading_inter);
        }
        window._global_vimeoIframeAPIReady = true;
        if (selfClass._videoElement) {
          // -- vimeo events

          var player = new Vimeo.Player(selfClass._videoElement);
          player.on('play', function () {
            selfClass.playMovie_visual();
          });
          player.on('pause', function () {
            selfClass.pauseMovie_visual();
          });
          selfClass._videoElement.addEventListener('fullscreenchange', selfClass.fullscreenHandleChange, false);
          selfClass._videoElement.addEventListener('webkitfullscreenchange', selfClass.fullscreenHandleChange, false);
        }
      }
    }
    if (window.Vimeo) {
      vimeo_addListeners();
    } else {
      window._global_vimeoIframeAPILoading = true;
      (0, _dzs_helpers.loadScriptIfItDoesNotExist)(_Constants.ConstantsDzsvg.VIMEO_IFRAME_API, 'Vimeo').then(r => {
        vimeo_addListeners();
      });
    }
  }
  if (selfClass.dataType === 'selfHosted') {
    if (selfClass.dataSrc && argsForVideoSetup.is_dash === false) {
      if (selfClass.dataSrc && (selfClass.dataSrc.indexOf('.ogg') > -1 || selfClass.dataSrc.indexOf('.ogv') > -1)) {
        selfClass.cthis.attr('data-sourceogg', selfClass.dataSrc);
      }
    }
    if (selfClass.dataSrc && argsForVideoSetup.is_dash === false) {
      let stringTheVideo = '<source src="' + selfClass.dataSrc + '"';
      if ((0, _dzs_helpers.is_safari)()) {
        stringTheVideo += '  type=\'video/mp4\'';
      }
      stringTheVideo += '/>';
      selfClass.cthis.find('.the-video').eq(0).append(stringTheVideo);
    }
    if (selfClass.cthis.attr('data-sourceogg')) {
      selfClass.cthis.find('.the-video').eq(0).append('<source src="' + selfClass.cthis.attr('data-sourceogg') + '" type="video/ogg"/>');
    }
    if (selfClass.cthis.attr('data-sourcewebm')) {
      selfClass.cthis.find('.the-video').eq(0).append('<source src="' + selfClass.cthis.attr('data-sourcewebm') + '" type="video/webm"/>');
    }
  }
  if (argsForVideoSetup.is360) {
    argsForVideoSetup.Dzsvg360.initPlayer(selfClass);
  }
  function youtube_onPlayerReady(e) {
    if (selfClass._videoElement && selfClass._videoElement.getPlaybackQuality) {
      selfClass._videoElement.setPlaybackQuality(o.settings_suggestedQuality);
    }
    if (selfClass.shouldStartMuted) {
      selfClass.volume_mute();
      selfClass.is_muted_for_autoplay = true;
    }
    if (o.playfrom === 'last') {
      // TODO: dunno why we need this

      if (selfClass.autoplayVideo === 'off') {
        setTimeout(function () {
          selfClass.pauseMovie({
            'called_from': 'playfrom last'
          });
        }, 1000);
      }
    }
    return false;
  }
  function youtube_onPlayerStateChange(e) {
    /*
               -1 – unstarted
               0 – ended
               1 – playing
               2 – paused
               3 – buffering
               5 – video cued
               */

    if (e.data === 1) {
      // -- playing
      // -play

      if (selfClass.queue_goto_perc) {
        selfClass.seek_to_perc(selfClass.queue_goto_perc);
        selfClass.queue_goto_perc = '';
      }
      if (selfClass.ad_status === 'waiting_for_play') {
        selfClass.setup_skipad();
      }
      selfClass.check_if_ad_must_be_played();
      selfClass.check_if_hd_available();
      if (selfClass.youtube_queue_change_quality) {
        selfClass._videoElement.setPlaybackQuality(o.settings_suggestedQuality);
        selfClass.youtube_queue_change_quality = '';
      }
      selfClass.playMovie_visual();
      selfClass.paused = false;
      selfClass.wasPlaying = true;
      selfClass.isInitialPlayed = true;
      if ((0, _dzs_helpers.is_mobile)()) {
        selfClass.cthis.find('.controls').eq(0).css('pointer-events', 'auto');
      }
    }
    if (e.data === 2) {
      if (selfClass.suspendStateForLoop === false) {
        selfClass.pauseMovie({
          'called_from': 'state_2_for_youtube'
        });
      }
      selfClass.paused = true;
      selfClass.wasPlaying = false;
    }
    if (selfClass._videoElement && selfClass._videoElement.getPlaybackQuality) {}
    if (e.data === 3) {
      // -- on player play, set the volume again
      selfClass.volumeClass.volume_setInitial();
    }
    if (e.data === 5) {}
    if (e.data === 0) {
      // -- handlevideo end
      selfClass.handleVideoEnd();
    }
  }
  function onPlayerPlaybackQualityChange(e) {}
  function viewRemoveFullscreenClass($player) {
    if ($player.hasClass('vplayer')) {} else {
      if ($player.parent().parent().parent().parent().hasClass('vplayer')) {
        $player = $player.parent().parent().parent().parent();
      }
    }
    $player.removeClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
  }
}

},{"../configs/Constants":1,"../js_common/_dzs_helpers":3,"../js_dzsvg/_dzsvg_helpers":4}],15:[function(require,module,exports){
"use strict";function vimeoPlayerCommand(e,o,t){o||(o="pause");const a={method:o};if(void 0!==t&&(a.value=t),e.vimeo_url)try{e._videoElement.contentWindow.postMessage(JSON.stringify(a),e.vimeo_url),("pause"===o||"seekTo"===o&&"0"==t)&&(e.wasPlaying=!1,e.paused=!0)}catch(e){}}Object.defineProperty(exports,"__esModule",{value:!0}),exports.vimeoPlayerCommand=vimeoPlayerCommand;
},{}],16:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.player_controls_drawBigPlayBtn = player_controls_drawBigPlayBtn;
exports.player_controls_drawFullscreenBarsOnCanvas = player_controls_drawFullscreenBarsOnCanvas;
exports.player_controls_stringScrubbar = player_controls_stringScrubbar;
var _dzsvg_svgs = require("../../js_dzsvg/_dzsvg_svgs");
/**
 * draw fullscreen bars
 * @param selfClass
 * @param _controls_fs_canvas
 * @param argColor
 */
function player_controls_drawFullscreenBarsOnCanvas(selfClass, _controls_fs_canvas, argColor) {
  if (selfClass.initOptions.design_skin !== 'skin_pro') {
    return;
  }
  var ctx = _controls_fs_canvas.getContext("2d");
  var ctx_w = _controls_fs_canvas.width;
  var ctx_pw = ctx_w / 100;
  var ctx_ph = ctx_w / 100;
  ctx.fillStyle = argColor;
  var borderw = 30;
  ctx.fillRect(25 * ctx_pw, 25 * ctx_ph, 50 * ctx_pw, 50 * ctx_ph);
  ctx.beginPath();
  ctx.moveTo(0, 0);
  ctx.lineTo(0, borderw * ctx_ph);
  ctx.lineTo(borderw * ctx_pw, 0);
  ctx.fill();
  ctx.moveTo(0, 100 * ctx_ph);
  ctx.lineTo(0, (100 - borderw) * ctx_ph);
  ctx.lineTo(borderw * ctx_pw, 100 * ctx_ph);
  ctx.fill();
  ctx.moveTo(100 * ctx_pw, 100 * ctx_ph);
  ctx.lineTo((100 - borderw) * ctx_pw, 100 * ctx_ph);
  ctx.lineTo(100 * ctx_pw, (100 - borderw) * ctx_ph);
  ctx.fill();
  ctx.moveTo(100 * ctx_pw, 0 * ctx_ph);
  ctx.lineTo((100 - borderw) * ctx_pw, 0 * ctx_ph);
  ctx.lineTo(100 * ctx_pw, borderw * ctx_ph);
  ctx.fill();
}
function player_controls_stringScrubbar() {
  var str_scrubbar = '<div class="scrubbar">';
  str_scrubbar += '<div class="scrub-bg"></div><div class="scrub-buffer"></div><div class="scrub">';
  str_scrubbar += '</div><div class="scrubBox"></div><div class="scrubBox-prog"></div>';
  str_scrubbar += '</div>';
  return str_scrubbar;
}
function player_controls_drawBigPlayBtn() {
  let string_structureBigPlayBtn = '<div class="big-play-btn">';
  string_structureBigPlayBtn += _dzsvg_svgs.svg_aurora_play_btn;
  string_structureBigPlayBtn += '</div>';
  return string_structureBigPlayBtn;
}

},{"../../js_dzsvg/_dzsvg_svgs":5}],17:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.promise_allDependenciesMet = promise_allDependenciesMet;
exports.view_cssConvertForPx = view_cssConvertForPx;
exports.view_setCssPropsForElement = view_setCssPropsForElement;
var _dzs_helpers = require("../js_common/_dzs_helpers");
var _playerSettings = require("../configs/_playerSettings");
var _Constants = require("../configs/Constants");
/**
 *
 * @param {jQuery} $elements
 * @param {object} cssProps
 */
function view_setCssPropsForElement($elements, cssProps) {
  $elements.css(cssProps);
}

/**
 *
 * @param cssVal
 * @return {string}
 */
function view_cssConvertForPx(cssVal) {
  if (cssVal === '') {
    return cssVal;
  }
  if (['auto', 'px', '%', ''].indexOf(cssVal) === -1) {
    return cssVal + 'px';
  }
  return cssVal;
}
function promise_allDependenciesMet(selfClass, completeFn) {
  const baseUrl = window.dzsvg_settings && window.dzsvg_settings.libsUri ? window.dzsvg_settings.libsUri : '';
  if (selfClass.is360) {
    (0, _dzs_helpers.loadScriptIfItDoesNotExist)(baseUrl + 'parts/player/player-360.js', 'dzsvp_player_init360').then(r => {
      completeFn();
    });
  }
  if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
    (0, _dzs_helpers.loadScriptIfItDoesNotExist)(_Constants.ConstantsDzsvg.YOUTUBE_IFRAME_API, 'dzsvg_yt_ready').then(r => {
      completeFn();
    });
  }
  if (!selfClass.is360 && selfClass.dataType !== _playerSettings.VIDEO_TYPES.YOUTUBE) {
    completeFn();
  }
}

},{"../configs/Constants":1,"../configs/_playerSettings":2,"../js_common/_dzs_helpers":3}],18:[function(require,module,exports){
"use strict";

/**
 * Author: Digital Zoom Studio
 * Website: https://digitalzoomstudio.net/
 * Portfolio: https://codecanyon.net/user/ZoomIt/portfolio?ref=ZoomIt
 * This is not free software.
 * Video Gallery
 * Version: 10.76
 */
var _dzsvg_helpers = require("./js_dzsvg/_dzsvg_helpers");
var _dzs_helpers = require("./js_common/_dzs_helpers");
var playerAdFunctions = _interopRequireWildcard(require("./js_dzsvg/_player_ad_functions"));
var _player_setupAd = require("./js_player/_player_setupAd");
var _player_setupMedia = require("./js_player/_player_setupMedia");
var _dzsvg_window_vars = require("./js_dzsvg/_dzsvg_window_vars");
var _videoElementFunctions = require("./js_dzsvg/_video-element-functions");
var _Constants = require("./configs/Constants");
var _dzsvg_svgs = require("./js_dzsvg/_dzsvg_svgs");
var _volume = require("./js_dzsvg/components/_volume");
var _playerSettings = require("./configs/_playerSettings");
var _player_helpers = require("./js_player/_player_helpers");
var _vimeoPlayerCommands = require("./js_player/_vimeoPlayerCommands");
var _player_viewDraw = require("./js_player/view/_player_viewDraw");
var _player_lifecycle = require("./js_player/_player_lifecycle");
var _viewFunctions = require("./shared/_viewFunctions");
function _getRequireWildcardCache(e) {
  if ("function" != typeof WeakMap) return null;
  var r = new WeakMap(),
    t = new WeakMap();
  return (_getRequireWildcardCache = function (e) {
    return e ? t : r;
  })(e);
}
function _interopRequireWildcard(e, r) {
  if (!r && e && e.__esModule) return e;
  if (null === e || "object" != typeof e && "function" != typeof e) return {
    default: e
  };
  var t = _getRequireWildcardCache(r);
  if (t && t.has(e)) return t.get(e);
  var n = {
      __proto__: null
    },
    a = Object.defineProperty && Object.getOwnPropertyDescriptor;
  for (var u in e) if ("default" !== u && {}.hasOwnProperty.call(e, u)) {
    var i = a ? Object.getOwnPropertyDescriptor(e, u) : null;
    i && (i.get || i.set) ? Object.defineProperty(n, u, i) : n[u] = e[u];
  }
  return n.default = e, t && t.set(e, n), n;
}
(0, _dzsvg_window_vars.init_windowVars)();
window.dzsvp_yt_iframe_settoload = false;
window.dzsvp_players_arr = [];
setTimeout(function () {
  if (window.dzsvg_settings) {}
  if (Object.hasOwnProperty('assign')) {
    window.dzsvg_settings = Object.assign(dzsvg_default_settings, window.dzsvg_settings);
  }
}, 2);
class DzsVideoPlayer {
  constructor(argThis, argOptions, $) {
    this.argThis = argThis;
    this.argOptions = argOptions;
    this.$ = $;
    this.dzsvp_players_arr = dzsvp_players_arr;
    this.cthis = null;
    this.initOptions = {};
    this.$parentGallery = null; // -- parent dzsvg

    this.id_player = ''; // -- this is set only if the player actually has an id

    this._videoElement = null;
    this._vpInner = null;
    this._fullscreenVideoElement = null; // -- the video element might be different then the video element ( ie. vimeo )
    this._controlsRight = null;
    this._volumeControls = null;
    this._volumeControls_real = null;
    this.$adContainer = null;

    // -- dimensions
    this.bufferedWidthOffset = 0;
    this.volumeWidthOffset = 0;
    this.totalWidth = 0;
    this.totalHeight = 0;
    this.videoWidth = 0;
    this.videoHeight = 0;
    this.currentPlayerId = '';
    this.dataSrc = '';
    this.dataType = ''; // -- type

    this.paused = true;
    this.suspendStateForLoop = false;
    this.wasPlaying = false;
    this.isInitialPlayed = false;
    this.is_muted_for_autoplay = false; // -- is muted only for autoplay purposes
    this.shouldStartMuted = false;
    this.isPartOfAnGallery = false;
    this.isGalleryHasOneVideoPlayerMode = false;
    this.isAd = false;
    this.isHadFirstInteraction = false;
    this.hasCustomSkin = true; // -- has custom skin or the iframe skin

    this.queue_goto_perc = 0; // -- seek to

    this.autoplayVideo = 'off';
    this.youtube_queue_change_quality = 'off';
    this.isLoop = false;

    // -- ads
    this.$adSkipCon = null;
    this.ad_status = 'undefined';
    this.ad_link = null;
    this.ad_array = [];
    this.isAdPlaying = false;
    this.vimeo_url = '';
    this.volumeClass = new _volume.VolumeControls(this);
    this.volumeLast = null;
    this.volumeDefault = null;
    this.classMisc = null;
    this.playerOptions = {}; // -- player options

    this.isInited = false;
    this.dataOriginalSrc = '';
    this.dataOriginalType = '';
    this.is360 = false;
    this.lastVideoType = '';
    this.dataVideoDesc = '';
    this.action_video_end = null;
    this.action_video_play = null;
    this.action_video_pause = null;
    this.action_video_view = null;
    this.arrTags = [];
    this.inter_checkYtIframeReady = 0;
    this.inter_clear_playpause_mistake = 0;
    this.inter_videoReadyState = 0;
    this.isInitedReadyVideo = false;
    this.$playcontrols = null;
    this.$fullscreenControl = null;
    this._timetext = null;
    this.scrubbar = null;
    this._controls_fs_canvas = null;
    this.scrubbar_moving = false;
    this.qualities_youtubeCurrentQuality = null;
    this.classInit();
  }
  classInit() {
    const selfClass = this;
    selfClass.init_readyControls = _player_lifecycle.init_readyControls;
    selfClass.view_setupBasicStructure = view_setupBasicStructure;
    selfClass.resizePlayer = resizePlayer;
    selfClass.handleEnterFrame = handleEnterFrame;
    selfClass.playMovie = playMovie;
    selfClass.mouse_is_over = mouse_is_over;
    selfClass.pauseMovie = pauseMovie;
    selfClass.handleMouseout = handleMouseout;
    selfClass.handleMouseover = handleMouseover;
    selfClass.handle_mouse = handle_mouse;
    selfClass.handleClickPlayPause = handleClickPlayPause;
    selfClass.handle_mousemove = handle_mousemove;
    selfClass.handleScrubMouse = handleScrubMouse;
    selfClass.handleKeyPress = handleKeyPress;
    selfClass.handleScrub = handleScrub;
    selfClass.restart_video = restart_video;
    selfClass.change_media = change_media;
    selfClass.handleClickVideoOverlay = handleClickVideoOverlay;
    selfClass.fullscreenToggle = fullscreenToggle;
    selfClass.volume_handleClickMuteIcon = volume_handleClickMuteIcon;
    selfClass.handleFullscreenChange = handleFullscreenChange;
    selfClass.handleFullscreenEnd = handleFullscreenEnd;
    selfClass.handleMouseOnVolume = handleMouseOnVolume;
    selfClass.handleResize = handleResize;
    selfClass.seek_to_perc = seek_to_perc;
    selfClass.handleVideoEnd = handleVideoEnd;
    selfClass.handleVideoEvent = handleVideoEvent;
    selfClass.vimeo_windowMessage = vimeo_windowMessage;
    var cthis;
    var natural_videow = 0,
      natural_videoh = 0,
      last_videoWidth = 0,
      last_videoHeight = 0;
    var video;
    var aux = 0;
    var isFullscreen = 0;
    let inter_removeFsControls // interval to remove fullscreen controls when no action is detected
      ,
      inter_mousedownscrubbing = 0 // interval to apply mouse down scrubbing on the video
    ;

    var info,
      infotext,
      _scrubBg,
      _btnhd,
      _controlsBackground = null,
      _muteControls = null;
    let videoIsPlayed = false,
      isMouseover = false // -- the mouse is over the vplayer
      ,
      google_analytics_sent_play_event = false,
      volume_mouse_down = false,
      scrub_mouse_down = false,
      controls_are_hovered = false,
      isViewSent = false,
      isFullscreenJustPressed = false,
      isPlayCommited = false // -- this will apply play later on
      ,
      vimeo_is_ready = false;
    var totalDuration = 0;
    var time_curr = 0;
    var video_title = '';

    //responsive vars
    var ww, wh;
    var qualities_youtubeVideoQualitiesArray = [],
      hasHD = false;
    var bufferedLength = -1,
      scrubbg_width = 0;
    var isBusyPlayPauseMistake = false;
    let vimeo_data;
    var inter_10_secs_contor = 0,
      inter_5_secs_contor = 0,
      inter_60_secs_contor = 0;
    const $ = this.$;
    var o = this.argOptions;
    cthis = $(this.argThis);
    selfClass.cthis = cthis;
    selfClass.initOptions = o;
    if (cthis.attr('id')) {
      selfClass.id_player = cthis.attr('id');
    } else {
      if (cthis.attr('data-player-id')) {
        selfClass.id_player = cthis.attr('data-player-id');
      }
    }
    if (typeof cthis.attr('id') != 'undefined' && cthis.attr('id')) {
      selfClass.currentPlayerId = cthis.attr('id');
    } else {
      selfClass.currentPlayerId = 'dzsvp' + parseInt(Math.random() * 10000, 10);
    }
    if (cthis.parent().parent().parent().parent().hasClass('videogallery')) {
      selfClass.$parentGallery = cthis.parent().parent().parent().parent();
    }

    // -- determine autoplay
    selfClass.autoplayVideo = o.autoplay;
    if (o.init_on === 'init') {
      init();
    }
    if (o.init_on === 'scroll') {
      $(window).on('scroll.' + selfClass.currentPlayerId, handle_scroll);
      handle_scroll();
    }
    function init() {
      // -- @order - first function

      classMiscInit();
      // -- external function calls
      selfClass.get_responsive_ratio = (pargs = {}) => {
        (0, _player_helpers.player_getResponsiveRatio)(selfClass, pargs);
      };
      selfClass.player_user_had_first_interaction = player_user_had_first_interaction;
      selfClass.pauseMovie = pauseMovie;
      selfClass.handleResize = handleResize;
      selfClass.setup_skipad = (0, _player_setupAd.player_setup_skipad)(selfClass);
      selfClass.seek_to_perc = seek_to_perc;
      selfClass.check_videoReadyState = check_videoReadyState;
      selfClass.handleClickCoverImage = handleClickCoverImage;
      selfClass.playMovie_visual = playMovie_visual;
      selfClass.pauseMovie_visual = pauseMovie_visual;
      selfClass.check_if_ad_must_be_played = () => {
        playerAdFunctions.check_if_ad_must_be_played(selfClass);
      };
      selfClass.check_if_hd_available = selfClass.classMisc.check_if_hd_available;
      selfClass.handleVideoEnd = handleVideoEnd;
      selfClass.volume_setInitial = selfClass.volumeClass.volume_setInitial;
      selfClass.volume_mute = volume_playerMute;
      selfClass.fullscreenHandleChange = handleFullscreenChange;
      selfClass.setupVolume = volume_setupVolumePerc;
      if (cthis.hasClass('vplayer-tobe') || !cthis.hasClass(_playerSettings.PLAYER_STATES.INITIALIZED)) {
        (0, _player_lifecycle.vplayerLifecycleInit)(selfClass);
      }
      if (!inter_10_secs_contor && o.action_video_contor_10secs) {
        inter_10_secs_contor = setInterval(selfClass.classMisc.count_10secs, 10000);
      }
      if (!inter_60_secs_contor && o.action_video_contor_60secs) {
        inter_60_secs_contor = setInterval(selfClass.classMisc.count_60secs, 30000);
      }
      if (!inter_5_secs_contor && o.action_video_contor_5secs) {
        inter_5_secs_contor = setInterval(selfClass.classMisc.count_5secs, 3000);
        setTimeout(function () {
          selfClass.classMisc.count_5secs();
        }, 500);
      }
    }
    function view_setupBasicStructure() {
      // console.log('view_setupBasicStructure()');

      // -- setup vp-inner
      if (!cthis.children('.vp-inner').length) {
        if (selfClass.dataType !== 'inline') {
          if (selfClass.dataType === 'vimeo') {
            if (o.settings_big_play_btn === 'on') {
              cthis.append('<div class="vp-inner ' + o.design_skin + '"></div>');
            } else {
              cthis.prepend('<div class="vp-inner ' + o.design_skin + '"></div>');
            }
          } else {
            cthis.append('<div class="vp-inner ' + o.design_skin + '"></div>');
          }
          selfClass._vpInner = cthis.children('.vp-inner').eq(0);
        } else {
          selfClass._vpInner = selfClass.cthis;
        }
      }
      if (selfClass.hasCustomSkin) {
        setup_customControls();
      }
      if (selfClass.isAd) {
        (0, _player_setupAd.player_setup_skipad)(selfClass)();
      }
      (0, _player_lifecycle.vplayerLifecycleReinit)(selfClass);
    }
    function setup_customControls() {
      var str_scrubbar = (0, _player_viewDraw.player_controls_stringScrubbar)();
      if (o.design_skin === 'skin_pro') {
        if (!(selfClass.dataType === 'vimeo' && o.vimeo_is_chromeless !== 'on')) {
          if (selfClass._vpInner) {
            selfClass._vpInner.append(str_scrubbar);
          }
        }
      }
      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        if (selfClass._vpInner) {
          selfClass._vpInner.prepend('<div class="mute-indicator"><i class="the-icon">' + _dzsvg_svgs.svg_mute_icon + '</i> <span class="the-label">' + 'muted' + '</span></div>');
        }
      }
      var str_controls = '<div class="controls"></div>';
      if (cthis.find('.cover-image').length > 0) {
        cthis.find('.cover-image').eq(0).before(str_controls);
      } else {
        if (selfClass._vpInner) {
          selfClass._vpInner.append(str_controls);
        }
      }
      setTimeout(function () {
        cthis.addClass('cover-image-loaded');
      }, 600);
      selfClass._controlsDiv = cthis.find('.controls');
      selfClass.totalWidth = selfClass.videoWidth;
      selfClass.totalHeight = selfClass.videoHeight;
      if (o.design_skin === 'skin_pro' || o.design_skin === 'skin_aurora') {
        selfClass._controlsDiv.append('<div class="controls-right"></div>');
      }
      if (selfClass._controlsDiv.find('.controls-right').length) {
        selfClass._controlsRight = selfClass._controlsDiv.find('.controls-right');
      }
      if ((selfClass.dataType !== 'vimeo' || o.vimeo_is_chromeless === 'on') && selfClass.dataType !== 'image' && selfClass.dataType !== 'inline') {
        var aux34 = '<div class=""></div>';
        var struct_bg = '<div class="videoPlayer-controls--background"></div>';
        var struct_playcontrols = '<div class="playcontrols-con"><div class="playcontrols"></div></div>';
        var struct_timetext = '<div class="timetext"><span class="curr-timetext"></span><span class="total-timetext"></span></div>';
        var struct_fscreen = '<div class="fscreencontrols"></div>';
        aux34 += '';
        selfClass._controlsDiv.append(struct_bg);
        selfClass._controlsDiv.append(struct_playcontrols);
        if (o.design_skin !== 'skin_pro') {
          selfClass._controlsDiv.append(str_scrubbar);
        }
        selfClass._controlsDiv.append(struct_timetext);
        if (selfClass._controlsRight) {
          selfClass._controlsRight.append(struct_fscreen);
        } else {
          selfClass._controlsDiv.append(struct_fscreen);
        }
        selfClass.volumeClass.constructVolumeInPlayer();
        if (o.design_skin === 'skin_avanti') {
          selfClass._controlsDiv.append('<div class="mutecontrols-con"><div class="btn-mute">' + _dzsvg_svgs.svg_mute_btn + '</div></div>');
          _muteControls = selfClass._controlsDiv.find('.mutecontrols-con').eq(0);
        }
      }
      if (selfClass._controlsRight) {
        selfClass._controlsDiv.append(selfClass._controlsRight);
      }
      selfClass._timetext = cthis.find('.timetext').eq(0);
      _controlsBackground = selfClass._controlsDiv.find('.videoPlayer-controls--background').eq(0);
      if (selfClass.dataType === 'image') {
        cthis.attr('data-img', selfClass.dataSrc);
      }
      if (cthis.children('.vplayer-logo')) {
        cthis.append(cthis.children('.vplayer-logo'));
      }
      if (cthis.children('.extra-controls')) {
        if (o.design_skin === 'skin_aurora') {
          cthis.children('.extra-controls').children().each(function () {
            var _t = $(this);
            if (_t.html().indexOf('{{')) {
              _t.html(String(_t.html()).replace('{{svg_embed_icon}}', _dzsvg_svgs.svg_embed));
            }
            if (_t.get(0).outerHTML.indexOf('dzsvg-multisharer-but') > -1) {
              (0, _dzsvg_helpers.dzsvg_check_multisharer)();
            }
            cthis.find('.timetext').eq(0).after(_t);
          });
        }
      }
      if (cthis.attr('data-img')) {
        selfClass._vpInner.prepend('<div class="cover-image from-type-' + selfClass.dataType + '"><div class="the-div-image" style="background-image:url(' + cthis.attr('data-img') + ');"/></div>');
      }
      if (selfClass.dataType === 'image') {
        cthis.addClass(_playerSettings.PLAYER_STATES.LOADED);
        if (selfClass.ad_link) {
          selfClass.cthis.children().eq(0).css({
            'cursor': 'pointer'
          });
          selfClass.cthis.children().eq(0).on('click', function () {
            if (selfClass.cthis.find('.controls').eq(0).css('pointer-events') !== 'none') {
              window.open(selfClass.ad_link);
              selfClass.ad_link = null;
            }
          });
        }
        return;
      }
      if (selfClass.dataType === 'inline') {
        cthis.find('.cover-image').on('click', function () {
          $(this).removeClass('is-visible');
        });
        cthis.addClass(_playerSettings.PLAYER_STATES.LOADED);
        setTimeout(function () {
          cthis.addClass('dzsvp-really-loaded');
        }, 2000);
        (0, _player_helpers.player_getResponsiveRatio)(selfClass, {
          'called_from': 'init .. inline'
        });
        handleResize();
        setTimeout(function () {
          handleResize();
        }, 1000);
        $(window).on('resize', handleResize);
        return;
      }
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        (0, _player_helpers.player_getResponsiveRatio)(selfClass, {
          'called_from': 'init .. youtube'
        });
      }
      if (selfClass.dataType === 'selfHosted') {
        if (o.settings_disableControls === 'on') {
          // -- for youtube ads we force enable the custom skin because we need to know when the video ended
          o.cueVideo = 'on';
          o.settings_youtube_usecustomskin = 'on';
          if ((0, _dzs_helpers.is_mobile)()) {
            selfClass.autoplayVideo = 'off';
          }
        }
      }
      if (selfClass.dataType === 'vimeo') {}
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        if (o.settings_disableControls === 'on') {
          // -- for youtube ads we force enable the custom skin because we need to know when the video ended
          o.cueVideo = 'on';
          o.settings_youtube_usecustomskin = 'on';
          if ((0, _dzs_helpers.is_mobile)()) {
            selfClass.autoplayVideo = 'off';
          }
        }
      }
      info = cthis.find('.info');
      infotext = cthis.find('.infoText');
      var structPlayControls = '';
      selfClass.$playcontrols = cthis.find('.playcontrols');
      structPlayControls = (0, _dzsvg_helpers.player_controls_generatePlayCon)(o);
      selfClass.$playcontrols.append(structPlayControls);
      selfClass.scrubbar = cthis.find('.scrubbar');
      _scrubBg = selfClass.scrubbar.children('.scrub-bg');
      selfClass.$fullscreenControl = cthis.find('.fscreencontrols');
      aux = '<div class="full">';
      if (o.design_skin === 'skin_aurora' || o.design_skin === 'skin_default' || o.design_skin === 'skin_white') {
        aux += _dzsvg_svgs.svg_full_icon;
      }
      aux += '</div><div class="fullHover"></div>';
      if (o.design_skin === 'skin_reborn') {
        aux += '<div class="full-tooltip">FULLSCREEN</div>';
      }
      selfClass.$fullscreenControl.append(aux);
      if (o.design_skin === 'skin_pro' || o.design_skin === 'skin_bigplay') {
        selfClass.$playcontrols.find('.pauseSimple').eq(0).append('<div class="pause-part-1"></div><div class="pause-part-2"></div>');
        selfClass.$fullscreenControl.find('.full').eq(0).append('<canvas width="15" height="15" class="fullscreen-button"></canvas>');
        selfClass._controls_fs_canvas = selfClass.$fullscreenControl.find('.full').eq(0).find('canvas.fullscreen-button').eq(0)[0];
        if (selfClass._controls_fs_canvas) {
          (0, _player_viewDraw.player_controls_drawFullscreenBarsOnCanvas)(selfClass, selfClass._controls_fs_canvas, o.controls_fscanvas_bg);
          $(selfClass._controls_fs_canvas).on('mouseover', handleMouseover);
          $(selfClass._controls_fs_canvas).on('mouseout', handleMouseout);
        }
      }
      if (selfClass.cthis.children('.videoDescription').length > 0) {
        selfClass.dataVideoDesc = selfClass.cthis.children('.videoDescription').html();
        selfClass.cthis.children('.videoDescription').remove();
      }
      if (cthis.attr('data-videoTitle')) {
        if (selfClass._vpInner) {
          selfClass._vpInner.append('<div class="video-description"></div>');
        }
        cthis.find('.video-description').eq(0).append('<div class="video-title">' + cthis.attr('data-videoTitle') + '</div>');
        if (o.video_description_style === 'show-description' && selfClass.dataVideoDesc) {
          cthis.find('.video-description').eq(0).append('<div class="video-subdescription">' + selfClass.dataVideoDesc + '</div>');
        }
        video_title = cthis.attr('data-videoTitle');
      }
    }
    function check_videoReadyState() {
      if (!selfClass._videoElement) {
        return;
      }
      (0, _dzsvg_helpers.dzsvg_call_video_when_ready)(o, selfClass, _player_lifecycle.init_readyVideo, vimeo_is_ready, selfClass.inter_videoReadyState);
      setTimeout(() => {
        if (o.cue === 'on') {
          if (!selfClass.isInitedReadyVideo) {
            (0, _player_lifecycle.init_readyVideo)(selfClass, {
              'called_from': 'timeout .. readyvideo'
            });
          }
        }
      }, 10000);
    }
    function handle_scroll() {
      if (!selfClass.isInited) {
        var st = $(window).scrollTop();
        var cthis_ot = cthis.offset().top;
        var wh = window.innerHeight;
        if (cthis_ot < st + wh + 150) {
          init();
        }
        return;
      } else {}
    }

    /**
     * change the media of the player
     * @param {string} argmedia
     * @param {object} pargs
     */
    function change_media(argmedia, pargs) {
      // -- @change media

      var margs = {
        'called_from': 'default',
        'type': 'selfHosted',
        'autoplay': 'off'
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      selfClass.lastVideoType = selfClass.dataType;

      // -- update with new types
      selfClass.dataSrc = argmedia;
      selfClass.dataType = margs.type;
      if (margs.autoplay) {
        selfClass.autoplayVideo = margs.autoplay;
      }
      (0, _viewFunctions.promise_allDependenciesMet)(selfClass, () => {
        (0, _player_lifecycle.vplayerLifecycleReinit)(selfClass);
        if (selfClass.lastVideoType === margs.type) {
          // -- same type
          if (selfClass.lastVideoType === 'selfHosted') {
            $(selfClass._videoElement).attr('src', argmedia);
            $(selfClass._videoElement).children('source').attr('src', argmedia);
          }
          if (selfClass.lastVideoType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
            if (selfClass.hasCustomSkin) {
              selfClass._videoElement.loadVideoById((0, _dzsvg_helpers.youtube_sanitize_url_to_id)(argmedia));
            } else {
              if (selfClass._videoElement.loadVideoById) {
                selfClass._videoElement.loadVideoById((0, _dzsvg_helpers.youtube_sanitize_url_to_id)(argmedia));
              } else {
                selfClass.dataSrc = (0, _dzsvg_helpers.youtube_sanitize_url_to_id)(argmedia);
                cthis.find('iframe').eq(0).attr('src', '//www.youtube.com/embed/' + selfClass.dataSrc + '?rel=0&showinfo=0');
              }
            }
          }
          if (selfClass.lastVideoType === 'vimeo') {
            if (selfClass.hasCustomSkin) {
              var argsForVideoSetup = {
                called_from: 'change_media'
              };
              (0, _player_setupMedia.generatePlayerMarkupAndSource)(selfClass, argsForVideoSetup);
            } else {
              var str_source = 'https:' + '//player.vimeo.com/video/' + selfClass.dataSrc + '?api=1&color=' + o.vimeo_color + '&title=' + o.vimeo_title + '&byline=' + o.vimeo_byline + '&portrait=' + o.vimeo_portrait + '&badge=' + o.vimeo_badge + '&player_id=vimeoplayer' + selfClass.dataSrc + (selfClass.autoplayVideo == 'on' ? '&autoplay=1' : '');
              selfClass._vpInner.find('.vimeo-iframe').eq(0).attr('src', str_source);
            }
          }
        } else {
          // -- different types..

          // -- update types
          selfClass.dataType = margs.type;
          cthis.find('video').each(function () {
            var _t2 = $(this);
            var errag = null;
            try {
              errag = this.pause();
              ;
            } catch (err) {
              console.log('cannot pause .. ', errag, err);
            }
            _t2.remove();
          });
          cthis.find('.the-video').remove();
          cthis.attr('data-sourcevp', argmedia);
          cthis.attr('data-type', margs.type);
          (0, _player_lifecycle.init_readyControls)(null, {
            'called_from': 'change_media'
          });
          selfClass.dataSrc = argmedia;
        }
        selfClass.lastVideoType = margs.type;
        if (selfClass.hasCustomSkin) {
          if (selfClass._vpInner.find('.controls').length === 0) {
            setup_customControls();
          }
        }
        if (margs.autoplay === 'on') {
          setTimeout(function () {
            playMovie({
              'called_from': 'change_media'
            });
          }, _Constants.PLAYER_DEFAULT_TIMEOUT);
        }
      });
    }
    function restart_video() {
      if (selfClass.dataType === 'selfHosted') {
        seek_to_perc(0);
      }
      if (selfClass.dataType === 'vimeo') {
        seek_to_perc(0);
      }
      (0, _player_lifecycle.vplayerLifecycleReinit)(selfClass);
    }
    function handle_mouse(e) {
      var _t = $(this);
      if (e.type === 'mouseover') {
        if (_t.hasClass('controls')) {
          controls_are_hovered = true;
        }
      }
      if (e.type === 'mouseout') {
        if (_t.hasClass('controls')) {
          controls_are_hovered = false;
        }
      }
      if (e.type === 'mousedown') {
        if (_t.hasClass('volumecontrols')) {
          volume_mouse_down = true;
        }
        if (_t.hasClass('scrubbar')) {
          clearTimeout(inter_mousedownscrubbing);
          inter_mousedownscrubbing = setTimeout(() => {
            scrub_mouse_down = true;
          }, 100);
        }
      }
      if (e.type === 'click') {
        player_user_had_first_interaction();
        if (_t.hasClass('mute-indicator')) {
          selfClass.volumeClass.player_volumeUnmute();
        }
        if (_t.hasClass('quality-option')) {
          if (_t.hasClass('active')) {
            return false;
          }
          selfClass.queue_goto_perc = time_curr / totalDuration;
          if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
            selfClass._videoElement.setPlaybackQuality(_t.attr('data-val'));
            selfClass._videoElement.stopVideo();
            selfClass._videoElement.setPlaybackQuality(_t.attr('data-val'));
            selfClass._videoElement.playVideo();
            setTimeout(function () {
              selfClass.qualities_youtubeCurrentQuality = selfClass._videoElement.getPlaybackQuality();
            }, 2000);
          }
          if (selfClass.dataType === 'selfHosted') {
            var newsource = selfClass.dataSrc;
            var _c = $(selfClass._videoElement).eq(0);
            cthis.find('.the-video').addClass('transitioning-out');
            _c.after(_c.clone());
            var _c2 = _c.next();
            _c2.removeClass('transitioning-out transitioning-in');
            _c2.addClass('preparing-transitioning-in js-transitioning-in');
            _c2.html('<source src="' + newsource + '">');
            var aux_wasPlaying = selfClass.wasPlaying;
            _c2.on('loadeddata', function () {
              _c2.off('loadeddata');
              selfClass._videoElement = _c2.get(0);
              if (selfClass.queue_goto_perc) {
                seek_to_perc(selfClass.queue_goto_perc);
                selfClass.queue_goto_perc = '';
              }
              if (selfClass.is360) {
                window.dzsvp_player_360_eventAfterQualityChange(selfClass);
              }
              setTimeout(function () {
                pauseMovie();
                if (cthis.find('.transitioning-out').get(0).pause) {
                  cthis.find('.transitioning-out').get(0).pause();
                }
                cthis.find('.transitioning-out').remove();
                cthis.find('.the-video.js-transitioning-in').addClass('transitioning-in');
                if (aux_wasPlaying) {
                  playMovie();
                }
              }, 500);
            });
            setTimeout(function () {}, 100);
          }
          _t.parent().children().removeClass('active');
          _t.addClass('active');
        }
      }
      if (e.type === 'mouseup') {
        clearTimeout(inter_mousedownscrubbing);
        volume_mouse_down = false;
        scrub_mouse_down = false;
      }
    }
    function handle_mousemove(e) {
      cthis.removeClass('mouse-is-out');
      isMouseover = true;
      if (volume_mouse_down) {
        handleMouseOnVolume(e);
      }
      if (scrub_mouse_down) {
        const argperc = (e.pageX - selfClass.scrubbar.offset().left) / selfClass.scrubbar.children().eq(0).width();
        seek_to_perc(argperc);
      }
      if (isFullscreen) {
        if (o.settings_disable_mouse_out !== 'on' && o.settings_disable_mouse_out_for_fullscreen !== 'on') {
          clearTimeout(inter_removeFsControls);
          inter_removeFsControls = setTimeout(controls_mouse_is_out, o.settings_mouse_out_delay_for_fullscreen);
        }
        if (e.pageX > ww - 10) {
          controls_are_hovered = false;
        }
      }
    }
    function controls_mouse_is_out() {
      if (!selfClass.paused && (!controls_are_hovered || (0, _dzs_helpers.is_android)())) {
        cthis.removeClass('mouse-is-over');
        cthis.addClass('mouse-is-out');
      }
      isMouseover = false;
    }
    function handleVideoEvent(e) {
      if (e.type === 'play') {
        videoIsPlayed = true;
        if ((0, _dzs_helpers.is_ios)() || (0, _dzs_helpers.is_android)()) {
          cthis.find('.controls').eq(0).css('pointer-events', 'auto');
        }
      }
    }
    function handleClickCoverImage(e) {
      if (e) {
        player_user_had_first_interaction();
      }
      if (selfClass.dataType !== 'image') {
        if (!selfClass.wasPlaying) {
          playMovie({
            'called_from': 'click coverImage'
          });
        } else {
          pauseMovie({
            'called_from': 'click coverImage'
          });
        }
      }
    }
    function player_user_had_first_interaction() {
      if (selfClass.cthis.data('userHadFirstInteraction')) {
        return false;
      }
      selfClass.isHadFirstInteraction = true;
      setTimeout(() => {
        // -- eliminate any concurrent events
        selfClass.cthis.addClass('user-had-first-interaction');
        if (selfClass.$parentGallery) {
          selfClass.$parentGallery.addClass('user-had-first-interaction');
        }
      }, 100);

      // -- unmute
      selfClass.volumeClass.player_volumeUnmute();
      selfClass.cthis.removeClass('autoplay-fallback--started-muted');
      selfClass.cthis.removeClass('is-muted');
      selfClass.cthis.data('userHadFirstInteraction', 'on');
      selfClass.is_muted_for_autoplay = false;
    }
    function handleClickVideoOverlay(e) {
      // -- check if user event

      const wasMutedForAutoplayBeforeClick = selfClass.is_muted_for_autoplay;
      if (e) {
        player_user_had_first_interaction();
      }
      if (selfClass.is360) {
        window.dzsvp_player_360_funcEnableControls(selfClass);
      }
      if (selfClass.isAd) {
        if (!selfClass.cthis.hasClass('user-had-first-interaction')) {
          // -- no previous interaction
          handleClickPlayPause();
          if (cthis.hasClass('mobile-pretime-ad') && !cthis.hasClass('first-played')) {
            return false;
          }
        } else {
          // -- previous interaction, now open link
          if (selfClass.ad_link) {
            window.open(selfClass.ad_link);
            selfClass.ad_link = null;
            return false;
          } else {
            return false;
          }
        }
        if (selfClass._videoElement && selfClass._videoElement.paused) {
          playMovie({
            'called_from': 'click_videoOverlay'
          });
        }
        if (e) {
          e.stopPropagation();
        }
      } else {
        // -- is not an AD

        if (selfClass.wasPlaying && wasMutedForAutoplayBeforeClick) {
          // -- just unmute
          selfClass.is_muted_for_autoplay = false;
        } else {
          if (selfClass.wasPlaying === false) {
            playMovie({
              'called_from': '_click_videoOverlay()'
            });
          } else {
            pauseMovie({
              'called_from': '_click_videoOverlay()'
            });
          }
        }
      }
    }
    function handleClickHdButton() {
      var _t = $(this);
      if (_t.hasClass('active')) {
        _t.removeClass('active');
        if ($.inArray('large', qualities_youtubeVideoQualitiesArray) > -1) {
          selfClass._videoElement.setPlaybackQuality('large');
        } else {
          if ($.inArray('medium', qualities_youtubeVideoQualitiesArray) > -1) {
            selfClass._videoElement.setPlaybackQuality('medium');
          } else {
            if ($.inArray('small', qualities_youtubeVideoQualitiesArray) > -1) {
              selfClass._videoElement.setPlaybackQuality('small');
            }
          }
        }
      } else {
        _t.addClass('active');
        if ($.inArray('hd1080', qualities_youtubeVideoQualitiesArray) > -1) {
          selfClass._videoElement.setPlaybackQuality('hd1080');
        } else {
          if ($.inArray('hd720', qualities_youtubeVideoQualitiesArray) > -1) {
            selfClass._videoElement.setPlaybackQuality('hd720');
          }
        }
      }
    }
    function mouse_is_over() {
      if (selfClass.is360) {
        window.dzsvp_player_360_funcEnableControls(selfClass);
      }
      clearTimeout(inter_removeFsControls);
      cthis.removeClass('mouse-is-out');
      cthis.addClass('mouse-is-over');
    }
    function handleMouseover(e) {
      if ($(e.currentTarget).hasClass('vplayer')) {
        if (o.settings_disable_mouse_out !== 'on') {
          if (!isFullscreenJustPressed) {
            mouse_is_over();
          }
        }
      }
      if ($(e.currentTarget).hasClass('fullscreen-button')) {
        (0, _player_viewDraw.player_controls_drawFullscreenBarsOnCanvas)(selfClass, selfClass._controls_fs_canvas, o.controls_fscanvas_hover_bg);
      }
    }
    function handleMouseout(e) {
      if (selfClass.is360) {
        window.dzsvp_player_360_funcEnableControls(selfClass);
      }
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE && isFullscreen) {
        isFullscreenJustPressed = true;
        setTimeout(function () {
          isFullscreenJustPressed = false;
        }, 500);
      }
      if ($(e.currentTarget).hasClass('vplayer')) {
        if (o.settings_disable_mouse_out !== 'on') {
          clearTimeout(inter_removeFsControls);
          inter_removeFsControls = setTimeout(controls_mouse_is_out, o.settings_mouse_out_delay);
        }
      }
      if ($(e.currentTarget).hasClass('fullscreen-button')) {
        (0, _player_viewDraw.player_controls_drawFullscreenBarsOnCanvas)(selfClass, selfClass._controls_fs_canvas, o.controls_fscanvas_bg);
      }
    }
    function handleScrubMouse(e) {
      if (!selfClass.scrubbar) {
        return false;
      }
      var _t = selfClass.scrubbar;
      if (e.type === 'mousemove') {
        var mouseX = e.pageX - $(this).offset().left;
        var aux = mouseX / scrubbg_width * totalDuration;
        if (!(isNaN(aux) || aux === Infinity)) {
          _t.children('.scrubBox').html((0, _dzs_helpers.formatTime)(aux));
        }
        _t.children('.scrubBox').css({
          'visibility': 'visible',
          'left': mouseX - 16
        });
      }
      if (e.type === 'mouseout') {
        _t.children('.scrubBox').css({
          'visibility': 'hidden'
        });
      }
      if (e.type === 'mouseleave') {
        _t.children('.scrubBox').css({
          'visibility': 'hidden'
        });
      }
    }
    function handleScrub(e) {
      player_user_had_first_interaction();
      var argperc = (e.pageX - selfClass.scrubbar.offset().left) / selfClass.scrubbar.children().eq(0).width();
      seek_to_perc(argperc);
    }
    function seek_to_perc(argperc) {
      var argperccheckads = playerAdFunctions.checkForAdAlongTheWay(selfClass, argperc);
      if (argperccheckads) {
        argperc = argperccheckads;
      }
      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
        totalDuration = selfClass._videoElement.duration;
        if (isNaN(totalDuration)) {
          return false;
        }
        selfClass._videoElement.currentTime = argperc * totalDuration;
      }
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        if (selfClass._videoElement && selfClass._videoElement.getDuration) {
          totalDuration = selfClass._videoElement.getDuration();
        } else {
          console.info('vplayer warning, youtube type - youtube api not ready .. ? ');
          totalDuration = 0;
        }

        // -- no need for seek to perct if video has not started.
        if (isNaN(totalDuration) || time_curr === 0 && argperc === 0) {
          return false;
        }
        selfClass._videoElement.seekTo(argperc * totalDuration);
        if (!selfClass.wasPlaying) {
          pauseMovie({
            'called_from': '_seek_to_perc()'
          });
        }
      }
      if (selfClass.dataType === 'vimeo') {
        if (argperc === 0 && selfClass.isInitialPlayed) {
          (0, _vimeoPlayerCommands.vimeoPlayerCommand)(selfClass, 'seekTo', '0');
        } else {
          if (o.vimeo_is_chromeless === 'on') {
            (0, _vimeoPlayerCommands.vimeoPlayerCommand)(selfClass, 'seekTo', argperc * totalDuration);
          }
        }
      }
    }
    function handleEnterFrame(pargs) {
      // -- enterFrame function

      var margs = {
        skin_play_check: false
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
        totalDuration = selfClass._videoElement.duration;
        time_curr = selfClass._videoElement.currentTime;
        if (selfClass.scrubbar && selfClass._videoElement && selfClass._videoElement.buffered && selfClass._videoElement.readyState > 1 && selfClass._videoElement.buffered && selfClass._videoElement.buffered.length) {
          bufferedLength = 0;
          try {
            bufferedLength = selfClass._videoElement.buffered.end(0) / selfClass._videoElement.duration * (selfClass.scrubbar.children().eq(0).width() + selfClass.bufferedWidthOffset);
          } catch (err) {
            console.log(err);
          }
        }
      }
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        if (!selfClass._videoElement.getVideoLoadedFraction) {
          return false;
        }
        if (selfClass._videoElement.getDuration !== undefined) {
          totalDuration = selfClass._videoElement.getDuration();
          time_curr = selfClass._videoElement.getCurrentTime();
        }
        if (_scrubBg) {
          bufferedLength = selfClass._videoElement.getVideoLoadedFraction() * (_scrubBg.width() + selfClass.bufferedWidthOffset);
        }
        aux = 0;
        if (selfClass.scrubbar) {
          selfClass.scrubbar.children('.scrub-buffer').css('left', aux);
        }
      }
      aux = time_curr / totalDuration * scrubbg_width;
      if (aux > scrubbg_width) {
        aux = scrubbg_width;
      }
      aux = parseInt(aux, 10);
      if (o.vimeo_is_chromeless === 'on') {
        if (selfClass.scrubbar) {
          selfClass.scrubbar.children('.scrub').css({
            'width': aux
          }, {});
        }
      } else {
        if (selfClass.scrubbar) {
          selfClass.scrubbar.children('.scrub').css({
            'width': aux
          });
        }
      }
      if (bufferedLength > -1) {
        if (bufferedLength > scrubbg_width + selfClass.bufferedWidthOffset) {
          bufferedLength = scrubbg_width + selfClass.bufferedWidthOffset;
        }
        if (selfClass.scrubbar) {
          selfClass.scrubbar.children('.scrub-buffer').width(bufferedLength);
        }
      }
      if (selfClass._timetext && selfClass._timetext.css('display') !== 'none' && (selfClass.wasPlaying || margs.skin_play_check) || selfClass.dataType === 'vimeo' && o.vimeo_is_chromeless === 'on') {
        var aux35 = (0, _dzs_helpers.formatTime)(totalDuration);
        if (o.design_skin !== 'skin_reborn') {
          aux35 = ' / ' + aux35;
        }
        selfClass._timetext.children(".curr-timetext").html((0, _dzs_helpers.formatTime)(time_curr));
        selfClass._timetext.children(".total-timetext").html(aux35);
      }
      if (o.design_enableProgScrubBox === 'on') {
        if (selfClass.scrubbar) {
          selfClass.scrubbar.children('.scrubBox-prog').html((0, _dzs_helpers.formatTime)(time_curr));
          selfClass.scrubbar.children('.scrubBox-prog').css({
            'left': aux - 16
          });
        }
      }
      if (o.playfrom === 'last') {
        try {
          if (typeof Storage != 'undefined') {
            localStorage['dzsvp_' + selfClass.id_player + '_lastpos'] = time_curr;
          }
        } catch (e) {}
      }
    }
    function volume_handleClickMuteIcon(e) {
      var _t = $(this);
      _t.toggleClass('active');
      if (_t.hasClass('active')) {
        selfClass.volumeLast = selfClass.volumeClass.volume_getVolume();
        volume_playerMute();
      } else {
        volume_setupVolumePerc(selfClass.volumeLast, {
          'called_from': 'volume_unmute'
        });
      }
    }
    function volume_playerMute() {
      (0, _videoElementFunctions.video_mute)(selfClass);
    }
    function handleMouseOnVolume(e) {
      // -- from user action

      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        // -- we can remove muted on user action
        player_user_had_first_interaction();
      }
      const _volumeReferenceTarget = selfClass._volumeControls.eq(1).length ? selfClass._volumeControls.eq(1) : selfClass._volumeControls.eq(0);
      const mousePositionRelativeToVolumeControls = e.pageX - _volumeReferenceTarget.offset().left;
      selfClass._volumeControls = cthis.find('.volumecontrols').children();
      if (mousePositionRelativeToVolumeControls >= 0) {
        aux = e.pageX - _volumeReferenceTarget.offset().left;
        selfClass._volumeControls.eq(2).css('visibility', 'visible');
        selfClass._volumeControls.eq(3).css('visibility', 'hidden');
        volume_setupVolumePerc(aux / _volumeReferenceTarget.width(), {
          'called_from': 'handleMouseOnVolume'
        });
      } else {
        // -- set volume to 0  when x < 0

        if (selfClass._volumeControls.eq(3).css('visibility') === 'hidden') {
          selfClass.volumeLast = selfClass.volumeClass.volume_getVolume();
          volume_setupVolumePerc(0);
          if (selfClass.dataType === 'vimeo') {
            vimeo_data = {
              "method": "setVolume",
              "value": "0"
            };
            if (selfClass.vimeo_url) {
              (0, _dzsvg_helpers.vimeo_do_command)(selfClass, vimeo_data, selfClass.vimeo_url);
            }
          }
          selfClass._volumeControls.eq(3).css('visibility', 'visible');
          selfClass._volumeControls.eq(2).css('visibility', 'hidden');
        } else {
          volume_setupVolumePerc(selfClass.volumeLast);
          selfClass._volumeControls.eq(3).css('visibility', 'hidden');
          selfClass._volumeControls.eq(2).css('visibility', 'visible');
        }
      }
    }

    /**
     *
     * @param {number} argumentVolumePerc 0-1
     * @param pargs
     */
    function volume_setupVolumePerc(argumentVolumePerc, pargs) {
      var margs = {
        'called_from': 'default'
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      if (argumentVolumePerc > 1) {
        argumentVolumePerc = 1;
      }
      selfClass.volumeClass.set_volume(argumentVolumePerc);
    }
    function handleVideoEnd() {
      if (selfClass.dataType === 'vimeo') {
        if (o.end_exit_fullscreen === 'on') {
          if ((0, _dzsvg_helpers.fullscreen_status)() === 1) {
            (0, _player_helpers.exitFullscreen)();
          }
        }
      }
      if ((0, _dzsvg_helpers.fullscreen_status)() === 1) {
        if (o.end_exit_fullscreen === 'on') {
          fullscreenToggle(null, {
            'called_from': 'handleVideoEnd .. forced o.end_exit_fullscreen',
            'force_exit_fullscreen': true
          }); // -- we exit fullscreen if video has ended on fullscreen
        }

        setTimeout(function () {
          handleResize();
        }, 100);
      }
      selfClass.cthis.addClass('is-video-end-screen');
      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
        if (selfClass.isLoop) {
          seek_to_perc(0);
          playMovie({
            'called_from': 'play_from_loop'
          });
          selfClass.cthis.removeClass('is-video-end-screen');
          return false;
        }
        if (selfClass._videoElement) {
          if (o.settings_video_end_reset_time === 'on') {
            selfClass._videoElement.currentTime = 0;
            if (selfClass.isLoop) {
              pauseMovie({
                'called_from': 'end_video()'
              });
              cthis.find('.cover-image').addClass('is-visible');
            } else {}
          }
        }
      }
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        if (selfClass.isLoop) {
          seek_to_perc(0);
          setTimeout(function () {
            playMovie({
              'called_from': 'play_from_loop'
            });
          }, 1000);
          selfClass.suspendStateForLoop = true;
          setTimeout(function () {
            selfClass.suspendStateForLoop = false;
          }, 1500);
          selfClass.cthis.removeClass('is-video-end-screen');
          return false;
        }
        if (selfClass._videoElement) {
          if (selfClass._videoElement && selfClass._videoElement.pauseVideo) {
            selfClass.wasPlaying = false;
          }
        }
      }
      if (selfClass.$parentGallery) {
        if (typeof selfClass.$parentGallery.get(0) !== 'undefined') {
          selfClass.$parentGallery.get(0).videoEnd();
        }
      }
      if (o.parent_player) {
        if (o.parent_player.get(0)) {
          o.parent_player.get(0).api_ad_end();
        }
      }
      if (selfClass.action_video_end) {
        selfClass.action_video_end(cthis);
      }
    }
    function handleResize(e, pargs) {
      var margs = {
        'force_resize_gallery': false,
        'called_from': 'default'
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      selfClass.videoWidth = cthis.width();
      selfClass.videoHeight = cthis.height();
      if (margs.called_from === 'recheck_sizes') {
        if (Math.abs(last_videoHeight - selfClass.videoHeight) < 4 && Math.abs(last_videoWidth - selfClass.videoWidth) < 4) {
          return false;
        }
      }
      last_videoWidth = selfClass.videoWidth;
      last_videoHeight = selfClass.videoHeight;
      if (!isNaN(o.responsive_ratio) && o.responsive_ratio > 0) {
        var auxh = o.responsive_ratio * selfClass.videoWidth;
        if (selfClass.$parentGallery && (cthis.hasClass('currItem') && !selfClass.isAd || margs.force_resize_gallery)) {
          if (selfClass.$parentGallery.get(0) && selfClass.$parentGallery.get(0).api_responsive_ratio_resize_h) {
            selfClass.$parentGallery.addClass('responsive-ratio-smooth');
            selfClass.$parentGallery.get(0).api_responsive_ratio_resize_h(auxh, {
              caller: cthis
            });
          }
        } else {
          if (!selfClass.isAd) {
            cthis.height(o.responsive_ratio * cthis.width());
          }
        }
      }
      if (cthis.hasClass('vp-con-laptop')) {
        if (selfClass.$parentGallery.get(0) && selfClass.$parentGallery.get(0).api_responsive_ratio_resize_h) {
          selfClass.$parentGallery.addClass('responsive-ratio-smooth');
          selfClass.$parentGallery.get(0).api_responsive_ratio_resize_h(selfClass.videoWidth * 0.5466, {
            caller: cthis
          });
        }
      }
      if (selfClass.videoWidth < 600) {
        cthis.addClass('under-600');
        if (selfClass.videoWidth < 421) {
          cthis.addClass('under-420');
        } else {
          cthis.removeClass('under-420');
        }
      } else {
        cthis.removeClass('under-600');
      }
      if ((0, _dzsvg_helpers.fullscreen_status)() === 1) {
        ww = $(window).width();
        wh = window.innerHeight;
        resizePlayer(ww, wh);
        cthis.css('transform', '');
      } else {
        resizePlayer(selfClass.videoWidth, selfClass.videoHeight);
      }
    }
    function handleKeyPress(e) {
      //-check if space is pressed for pause

      if (isMouseover) {
        if (e.charCode === 27 || e.keyCode === 27) {
          setTimeout(function () {
            handleResize(null, {
              'called_from': 'esc_key'
            });
          }, _Constants.PLAYER_DEFAULT_TIMEOUT);
        }
        if (e.charCode === 32 || e.keyCode === 32) {
          handleClickPlayPause();
          e.stopPropagation();
          e.preventDefault();
          return false;
        }
      }
    }
    function vimeo_windowMessage(e) {
      // -- we receive iframe messages from vimeo here
      var data, method;
      if (e.origin !== 'https://player.vimeo.com' && e.origin !== 'http://player.vimeo.com') {
        return;
      }
      if (!selfClass._videoElement) {
        console.log('[dzsvg][log] video element does not exist for a reason ..', selfClass, e);
        return false;
      }
      selfClass.vimeo_url = '';
      if ($(selfClass._videoElement).attr('src')) {
        selfClass.vimeo_url = $(selfClass._videoElement).attr('src').split('?')[0];
      }
      vimeo_is_ready = true;
      if (String(selfClass.vimeo_url).indexOf('http') !== 0) {
        selfClass.vimeo_url = 'https:' + selfClass.vimeo_url;
      }
      try {
        data = JSON.parse(e.data);
        if (data.data.duration) {
          time_curr = data.data.seconds;
          totalDuration = data.data.duration;
        }
      } catch (err) {
        //fail silently... like a ninja!
      }
      if (e && typeof e.data == 'object') {
        data = e.data;
      }
      if (data && data.player_id && selfClass.dataSrc !== data.player_id.substr(11)) {
        return;
      }
      if (data) {
        if (data.event === 'pause') {
          pauseMovie_visual();
        }
        if (data.event === 'ready') {
          if (selfClass.autoplayVideo === 'on') {
            // -- we don't force play Movie because we already set autoplay to 1 on the iframe
          }
          vimeo_data = {
            "method": "addEventListener",
            "value": "finish"
          };
          (0, _dzsvg_helpers.vimeo_do_command)(selfClass, vimeo_data, selfClass.vimeo_url);
          vimeo_data = {
            "method": "addEventListener",
            "value": "pause"
          };
          (0, _dzsvg_helpers.vimeo_do_command)(selfClass, vimeo_data, selfClass.vimeo_url);
          vimeo_data = {
            "method": "addEventListener",
            "value": "playProgress"
          };
          (0, _dzsvg_helpers.vimeo_do_command)(selfClass, vimeo_data, selfClass.vimeo_url);
          cthis.addClass(_playerSettings.PLAYER_STATES.LOADED);
          if (selfClass.$parentGallery != null) {
            if (typeof selfClass.$parentGallery.get(0) != 'undefined') {
              selfClass.$parentGallery.get(0).api_video_ready();
            }
          }
        }
        if (data.event === 'playProgress') {
          selfClass.isInitialPlayed = true;
          if (selfClass.paused === true) {
            playMovie_visual();
          }
        }
        if (data.event === 'finish' || data.event === 'ended') {
          handleVideoEnd();
        }
      }
    }
    function handleClickPlayPause(e) {
      const _t = $(this);
      if (this && e) {
        if ($(e.currentTarget).hasClass('playcontrols')) {
          if (_t.parent().parent().parent().hasClass('vplayer') || _t.parent().parent().parent().parent().hasClass('vplayer')) {

            // -- check for HD / ad reasons
          } else {
            return false;
          }
        }
      }
      if (isBusyPlayPauseMistake) {
        return false;
      }
      isBusyPlayPauseMistake = true;
      if (selfClass.inter_clear_playpause_mistake) {
        clearTimeout(selfClass.inter_clear_playpause_mistake);
      }
      selfClass.inter_clear_playpause_mistake = setTimeout(function () {
        isBusyPlayPauseMistake = false;
      }, _Constants.PLAYER_DEFAULT_TIMEOUT);
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE && selfClass._videoElement.getPlayerState && (selfClass._videoElement.getPlayerState() === 2 || selfClass._videoElement.getPlayerState() === -1)) {
        selfClass.paused = true;
      }
      if (e) {
        player_user_had_first_interaction();
      }
      if (selfClass.cthis.hasClass('is-video-end-screen')) {
        seek_to_perc(0);
        setTimeout(() => {
          playMovie({
            'called_from': 'handleClickPlayPause'
          });
        }, 100);
        return false;
      }
      if (selfClass.paused) {
        playMovie({
          'called_from': 'handleClickPlayPause'
        });
        return;
      }
      pauseMovie({
        'called_from': 'handleClickPlayPause'
      });
    }
    function handleFullscreenEnd(event) {}
    function handleFullscreenChange(e) {
      isFullscreen = !!((0, _dzsvg_helpers.fullscreen_status)() === 1);
      if (isFullscreen) {
        // -- we have something fullscreen
        selfClass.cthis.addClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
        if (selfClass.dataType === 'vimeo') {
          selfClass._vpInner.get(0).addEventListener('click', () => {}, false);
        }
      }
      if (o.touch_play_inline === 'on') {
        if ((0, _dzs_helpers.is_ios)()) {
          pauseMovie({
            'called_from': '_touch_play_inline_ios()'
          });
        }
      }
      if (!isFullscreen) {
        fullscreen_offActions();
      }
    }
    function classMiscInit() {
      class classMisc {
        check_one_sec_for_adsOrTags() {
          if (selfClass.isAdPlaying === false && selfClass.paused === false) {
            if (typeof selfClass.ad_array == 'object' && selfClass.ad_array.length > 0) {
              for (let i2 in selfClass.ad_array) {
                var cach = selfClass.ad_array[i2];
                var cach_time = 0;
                if (cach.time) {
                  cach_time = cach.time;
                }
                if (cach.source && totalDuration && time_curr >= cach_time * totalDuration) {
                  (0, _player_setupAd.player_setupAd)(selfClass, i2, {
                    'called_from': 'check_one_sec_for_adsOrTags'
                  });
                }
              }
            }
          }
          if (o.settings_enableTags === 'on') {
            selfClass.classMisc.tags_check();
          }
        }
        reinit_cover_image() {
          selfClass.cthis.find('.cover-image').addClass('is-visible');
        }
        setup_subtitle() {
          let subtitle_input = '';
          var self = this;
          if (cthis.children('.subtitles-con-input').length > 0) {
            subtitle_input = cthis.children('.subtitles-con-input').eq(0).html();
            this.parse_subtitle(subtitle_input);
          } else {
            if (o.settings_subtitle_file) {
              $.ajax({
                url: o.settings_subtitle_file,
                success: function (response) {
                  subtitle_input = response;
                  self.parse_subtitle(subtitle_input);
                }
              });
            }
          }
        }
        parse_subtitle(arg) {
          const regex_subtitle = _Constants.PLAYER_REGEX_SUBTITLE;
          var arr_subtitle = [];
          cthis.append('<div class="subtitles-con"></div>');
          while (arr_subtitle = regex_subtitle.exec(arg)) {
            let startTime = '';
            if (arr_subtitle[1]) {
              startTime = (0, _dzs_helpers.format_to_seconds)(arr_subtitle[1]);
            }
            let endtime = '';
            if (arr_subtitle[2]) {
              arr_subtitle[2] = String(arr_subtitle[2]).replace('gt;', '');
              endtime = (0, _dzs_helpers.format_to_seconds)(arr_subtitle[2]);
            }
            let cnt = '';
            if (arr_subtitle[3]) {
              cnt = arr_subtitle[3];
            }
            cthis.children('.subtitles-con').append('<div class="dzstag subtitle-tag" data-starttime="' + startTime + '" data-endtime="' + endtime + '">' + cnt + '</div>');
          }
          selfClass.arrTags = cthis.find('.dzstag');
        }
        youtube_checkIfIframeIsReady() {
          if (window.YT && window.YT.Player || window._global_youtubeIframeAPIReady) {
            (0, _player_lifecycle.init_readyControls)(selfClass, null, {
              'called_from': 'check_if_yt_iframe_ready'
            });
            clearInterval(selfClass.inter_checkYtIframeReady);
          }
        }
        fn_change_color_highlight(arg) {
          cthis.find('.scrub').eq(0).css({
            'background': arg
          });
          cthis.find('.volume_active').eq(0).css({
            'background': arg
          });
          cthis.find('.hdbutton-hover').eq(0).css({
            'color': arg
          });
        }
        tags_check() {
          var roundTime = Number(time_curr);
          if (selfClass.arrTags.length === 0) {
            return;
          }
          selfClass.arrTags.removeClass('active');
          selfClass.arrTags.each(function () {
            var _t = $(this);
            if (Number(_t.attr('data-starttime')) <= roundTime && Number(_t.attr('data-endtime')) >= roundTime) {
              _t.addClass('active');
            }
          });
        }
        check_if_hd_available() {
          if (qualities_youtubeVideoQualitiesArray.length > 0) {
            return false;
          }
          selfClass.qualities_youtubeCurrentQuality = selfClass._videoElement.getPlaybackQuality();
          qualities_youtubeVideoQualitiesArray = selfClass._videoElement.getAvailableQualityLevels();
          if ($.inArray('hd720', qualities_youtubeVideoQualitiesArray) > -1) {
            hasHD = true;
          }
          if (qualities_youtubeVideoQualitiesArray.length > 1) {
            cthis.addClass('has-multiple-quality-levels');
          }
          if (selfClass._controlsDiv) {
            var _qualitySelector = selfClass.cthis.find('.quality-selector');
            if (_qualitySelector.length === 0) {
              if (hasHD === true) {
                if (selfClass._controlsDiv.children('.hdbutton-con').length === 0) {
                  if (o.settings_suggestedQuality !== 'default') {
                    if (selfClass.qualities_youtubeCurrentQuality !== o.settings_suggestedQuality) {
                      selfClass._videoElement.setPlaybackQuality(o.settings_suggestedQuality);
                    }
                  }
                  if (o.design_skin === 'skin_pro') {
                    selfClass._controlsDiv.find('.timetext').after('<div class="hdbutton-con"><div class="hdbutton-normal">HD</div></div>');
                  } else {
                    selfClass._controlsDiv.append('<div class="hdbutton-con"><div class="hdbutton-normal">HD</div></div>');
                  }
                  _btnhd = selfClass._controlsDiv.children('.hdbutton-con');
                  if (selfClass.qualities_youtubeCurrentQuality === 'hd720' || selfClass.qualities_youtubeCurrentQuality === 'hd1080') {
                    _btnhd.addClass('active');
                  }
                  _btnhd.on('click', handleClickHdButton);
                  resizePlayer(selfClass.videoWidth, selfClass.videoHeight);
                }
              }
            } else {
              // no-quality selector

              (0, _dzsvg_helpers.player_setupQualitySelector)(selfClass, selfClass.qualities_youtubeCurrentQuality, qualities_youtubeVideoQualitiesArray);
            }
          }
        }
        count_10secs() {
          if (o.action_video_contor_10secs && cthis.hasClass('is-playing')) {
            o.action_video_contor_10secs(cthis, video_title);
          }
        }
        count_60secs() {
          if (o.action_video_contor_60secs && cthis.hasClass('is-playing')) {
            o.action_video_contor_60secs(cthis, video_title);
          }
        }
        count_5secs() {
          if (o.action_video_contor_5secs) {
            o.action_video_contor_5secs(cthis, video_title);
          }
        }
      }
      selfClass.classMisc = new classMisc();
    }

    /**
     *
     * @param event
     * @param pargs
     * @returns {boolean}
     */
    function fullscreenToggle(event, pargs) {
      var margs = {
        'called_from': 'event',
        force_exit_fullscreen: false
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      let $elemRequestFull = cthis.get(0);
      if (!(0, _dzs_helpers.is_safari)() && cthis.parent().parent().hasClass('sliderMain')) {
        $elemRequestFull = cthis.parent().parent().get(0);
      }
      if (cthis.hasClass('dzsvg-recla')) {
        if (event && event.currentTarget) {
          if (event.currentTarget.className.indexOf('video-overlay') > -1) {
            return false;
          }
        }
      }
      selfClass.videoWidth = cthis.outerWidth();
      selfClass.videoHeight = cthis.outerHeight();
      if ((0, _dzs_helpers.is_ios)() && o.touch_play_inline === 'off') {
        playMovie({
          'called_from': 'fullscreenToggle ios'
        });
        return false;
      }

      // -- we force fullscreen status to 1 if we are forcing a exit
      const fullscreenStatus = margs.force_exit_fullscreen ? 1 : (0, _dzsvg_helpers.fullscreen_status)();

      // -- this was forced fullscreen so we exit it..
      if (fullscreenStatus === 0 && cthis.hasClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS)) {
        fullscreen_offActions();
        isFullscreen = 0;
        return false;
      }
      if (fullscreenStatus === 0) {
        isFullscreen = 1;
        cthis.addClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
        if (selfClass.is360 && (0, _dzs_helpers.is_ios)()) {
          setTimeout(function () {
            handleResize(null, {
              'called_from': 'fullscreen 360'
            });
          }, _Constants.PLAYER_DEFAULT_TIMEOUT);
        } else {
          if ((0, _dzs_helpers.is_ios)() && selfClass._videoElement.webkitEnterFullscreen) {
            selfClass._videoElement.webkitEnterFullscreen();
            return false;
          }
          if ((0, _player_helpers.requestFullscreen)($elemRequestFull) === null) {
            if (selfClass.$parentGallery) {
              selfClass.$parentGallery.find('.gallery-buttons').hide();
            }
          }
          selfClass.totalWidth = window.screen.width;
          selfClass.totalHeight = window.screen.height;
          resizePlayer(selfClass.totalWidth, selfClass.totalHeight);
          if (o.design_skin === 'skin_reborn') {
            cthis.find('.full-tooltip').eq(0).html('EXIT FULLSCREEN');
          }
          isFullscreenJustPressed = true;
          setTimeout(function () {
            isFullscreenJustPressed = false;
          }, 700);
          if (o.settings_disable_mouse_out !== 'on' && o.settings_disable_mouse_out_for_fullscreen !== 'on') {
            clearTimeout(inter_removeFsControls);
            inter_removeFsControls = setTimeout(controls_mouse_is_out, o.settings_mouse_out_delay_for_fullscreen);
          }
        }
      } else {
        // -- disable fullscreen

        isFullscreen = 0;
        fullscreen_offActions();
        fullscreen_cancel_on_document();
      }
    }
    function fullscreen_offActions() {
      cthis.addClass('remove_fullscreen');
      cthis.removeClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
      cthis.find('.vplayer.' + _Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS).removeClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
      cthis.removeClass('is-fullscreen');
      if (o.design_skin === 'skin_reborn') {
        cthis.find('.full-tooltip').eq(0).html('FULLSCREEN');
      }
      handleResize();
      setTimeout(handleResize, 800);
    }
    function fullscreen_cancel_on_document() {
      var elem = document;
      if ((0, _dzsvg_helpers.fullscreen_status)() === 1) {
        if (elem.cancelFullScreen) {
          elem.cancelFullScreen();
        } else if (elem.exitFullscreen) {
          try {
            elem.exitFullscreen();
          } catch (err) {
            console.info('error at exit fullscreen ', err);
          }
        } else if (elem.mozCancelFullScreen) {
          elem.mozCancelFullScreen();
        } else if (elem.webkitCancelFullScreen) {
          elem.webkitCancelFullScreen();
        } else if (elem.msExitFullscreen) {
          elem.msExitFullscreen();
        }
      }
    }
    function resizePlayer(playerWidth, playerHeight) {
      calculateDims(playerWidth, playerHeight);
      if (_scrubBg) {
        scrubbg_width = _scrubBg.width();
      }
      if (selfClass.is360) {
        window.dzsvp_player_360_funcResizeControls(playerWidth, playerHeight);
      }
    }
    function calculateDims(warg, harg) {
      if (selfClass.dataType === 'selfHosted') {
        if (selfClass._videoElement) {
          if (selfClass._videoElement.videoWidth) {
            natural_videow = selfClass._videoElement.videoWidth;
          }
          if (selfClass._videoElement.videoHeight) {
            natural_videoh = selfClass._videoElement.videoHeight;
          }
        } else {
          console.info('video not found ? problem');
        }
      }
      if (cthis.hasClass('pattern-video')) {
        if (selfClass.dataType === 'selfHosted') {
          if (natural_videow) {
            var nr_w = Math.ceil(selfClass.totalWidth / natural_videow);
            var nr_h = Math.ceil(selfClass.videoHeight / natural_videoh);
            for (var i = 0; i < nr_w; i++) {
              for (var j = 0; j < nr_h; j++) {
                if (i === 0 && j === 0 || cthis.find('video[data-dzsvgindex="' + i + '' + j + '"]').length) {
                  continue;
                }
                $(selfClass._videoElement).after($(selfClass._videoElement).clone());
                $(selfClass._videoElement).next().attr('data-dzsvgindex', String(i) + String(j));
                $(selfClass._videoElement).next().get(0).play();
                $(selfClass._videoElement).next().css({
                  'left': i * natural_videow,
                  'top': j * natural_videoh
                });
              }
            }
            if (nr_w) {
              for (var i = 0; i < nr_w; i++) {}
            }
          }
        }
      }
    }
    function playMovie(pargs) {
      var margs = {
        'called_from': 'default'
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      if ((0, _dzs_helpers.is_mobile)()) {
        var d = new Date();
        if (selfClass.isHadFirstInteraction === false && o.autoplayWithVideoMuted === 'off' && margs.called_from.indexOf('autoplayNext') > -1 && Number(d) - window.dzsvg_time_started < 1500) {
          // -- no user action
          return false;
        }
      }
      isPlayCommited = true;
      if (!cthis.hasClass(_playerSettings.PLAYER_STATES.LOADED) && selfClass.dataType !== 'vimeo') {
        setTimeout(function () {
          // -- check if play still commited

          if (isPlayCommited) {
            margs.called_from = margs.called_from + ' recommit';
            playMovie(margs);
          }
        }, 500);
        return false;
      }
      if (margs.called_from === 'play_only_on_desktop') {
        if ((0, _dzs_helpers.is_mobile)()) {
          return false;
        }
      }
      cthis.find('.cover-image').removeClass('is-visible');
      if (o.settings_disableVideoArray !== 'on') {
        for (var i = 0; i < selfClass.dzsvp_players_arr.length; i++) {
          if (selfClass.dzsvp_players_arr[i].get(0) && selfClass.dzsvp_players_arr[i].get(0) != cthis.get(0) && selfClass.dzsvp_players_arr[i].get(0).externalPauseMovie) {
            selfClass.dzsvp_players_arr[i].get(0).externalPauseMovie({
              'called_from': 'external_pauseMovie()'
            });
          }
        }
      }
      if (o.try_to_pause_zoomsounds_players === 'on') {
        (0, _dzsvg_helpers.pauseDzsapPlayers)();
      }
      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'vimeo' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
        try {
          (0, _videoElementFunctions.video_play)(selfClass);
        } catch (err) {
          console.info('[dzsvg] vg - ', err);
        }
      }
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        if (!selfClass.paused) {
          return false;
        }
        (0, _videoElementFunctions.video_play)(selfClass);
      }
      selfClass.wasPlaying = true;
      selfClass.paused = false;
      selfClass.isInitialPlayed = true;
      if (!selfClass.isHadFirstInteraction) {
        selfClass.is_muted_for_autoplay = true;
      }
      cthis.trigger('videoPlay');
      playMovie_visual();
      selfClass.classMisc.check_one_sec_for_adsOrTags();
      if (selfClass.action_video_view) {
        if (!isViewSent) {
          selfClass.action_video_view(cthis, video_title);
          isViewSent = true;
        }
      }
    }
    function playMovie_visual() {
      setTimeout(() => {
        cthis.addClass('first-played');
      }, 1000);
      if (selfClass.isAd) {
        o.parent_player.removeClass('pretime-ad-setuped');
        if (o.parent_player.get(0) && o.parent_player.get(0).gallery_object) {
          $(o.parent_player.get(0).gallery_object).removeClass('pretime-ad-setuped');
        }
      }
      if (o.google_analytics_send_play_event === 'on' && window._gaq && !google_analytics_sent_play_event) {
        window._gaq.push(['_trackEvent', 'Video Gallery Play', 'Play', 'video gallery play - ' + selfClass.dataSrc]);
        google_analytics_sent_play_event = true;
      }
      if (o.settings_disable_mouse_out !== 'on') {
        if ((0, _dzs_helpers.is_mobile)()) {
          clearTimeout(inter_removeFsControls);
          inter_removeFsControls = setTimeout(controls_mouse_is_out, o.settings_mouse_out_delay_for_fullscreen);
        }
      }
      cthis.addClass('is-playing');
      cthis.removeClass('is-video-end-screen');
      if (selfClass.$parentGallery && selfClass.$parentGallery.get(0) && selfClass.$parentGallery.get(0).api_played_video) {
        selfClass.$parentGallery.get(0).api_played_video();
      }
      selfClass.paused = false;
      selfClass.wasPlaying = true;
      selfClass.isInitialPlayed = true;
      if (selfClass.action_video_play) {
        selfClass.action_video_play(cthis, video_title);
      }
    }
    function pauseMovie_visual() {
      selfClass.wasPlaying = false;
      selfClass.paused = true;
      cthis.removeClass('is-playing');
      if (selfClass.action_video_pause) {
        selfClass.action_video_pause(cthis, video_title);
      }
    }
    function pauseMovie(pargs) {
      var margs = {
        'called_from': 'default'
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      isPlayCommited = false;
      if (o.try_to_pause_zoomsounds_players === 'on') {
        if (window.dzsap_player_interrupted_by_dzsvg) {
          window.dzsap_player_interrupted_by_dzsvg.api_play_media({
            'audioapi_setlasttime': false
          });
          window.dzsap_player_interrupted_by_dzsvg = null;
        }
      }
      if (!selfClass.isInitialPlayed) {
        return false;
      }
      selfClass.suspendStateForLoop = true;
      setTimeout(function () {
        selfClass.suspendStateForLoop = false;
      }, 1500);
      pauseMovie_visual();
      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
        if (selfClass._videoElement) {
          selfClass._videoElement.pause();
        } else {
          console.info('[vplayer] warning: video undefined');
        }
      }
      if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE) {
        if (selfClass._videoElement && selfClass._videoElement.pauseVideo) {
          try {
            selfClass._videoElement.pauseVideo();
          } catch (err) {
            console.log(err);
          }
        }
      }
      if (selfClass.dataType === 'vimeo') {
        (0, _vimeoPlayerCommands.vimeoPlayerCommand)(selfClass, 'pause');
      }
      selfClass.wasPlaying = false;
      selfClass.paused = true;
      mouse_is_over();
      cthis.removeClass('is-playing');
    }
    try {
      cthis.get(0).checkYoutubeState = function () {
        if (selfClass.dataType === _playerSettings.VIDEO_TYPES.YOUTUBE && selfClass._videoElement.getPlayerState) {
          if (selfClass._videoElement.getPlayerState && selfClass._videoElement.getPlayerState() === 0) {
            handleVideoEnd();
          }
        }
      };
    } catch (err) {}
  }
}
function dzsvp_handleInitedjQuery() {
  //-------VIDEO PLAYER
  (function ($) {
    $.fn.vPlayer = function (argOptions) {
      var finalOptions = {};
      var defaultOptions = Object.assign({}, _playerSettings.defaultPlayerSettings);
      finalOptions = (0, _dzsvg_helpers.convertPluginOptionsToFinalOptions)(this, defaultOptions, argOptions);
      this.each(function () {
        var _vg = new DzsVideoPlayer(this, finalOptions, $);
        return this;
      }); // end each
    };

    window.dzsvp_init = function (selector, settings) {
      if (typeof settings != "undefined" && typeof settings.init_each != "undefined" && settings.init_each === true) {
        var element_count = 0;
        for (var e in settings) {
          element_count++;
        }
        if (element_count === 1) {
          settings = undefined;
        }
        $(selector).each(function () {
          var _t = $(this);
          _t.vPlayer(settings);
        });
      } else {
        $(selector).vPlayer(settings);
      }
    };
  })(jQuery);
  window.dzsvp_isLoaded = true;
  jQuery(document).ready(function ($) {
    dzsvp_init('.vplayer-tobe.auto-init', {
      init_each: true
    });
    (0, _dzsvg_helpers.registerAuxjQueryExtends)($);
  });
}
(0, _dzs_helpers.loadScriptIfItDoesNotExist)('', 'jQuery').then(() => {
  dzsvp_handleInitedjQuery();
});
(0, _dzsvg_helpers.dzsvgExtraWindowFunctions)();
window.dzsvg_curr_embed_code = '';

},{"./configs/Constants":1,"./configs/_playerSettings":2,"./js_common/_dzs_helpers":3,"./js_dzsvg/_dzsvg_helpers":4,"./js_dzsvg/_dzsvg_svgs":5,"./js_dzsvg/_dzsvg_window_vars":6,"./js_dzsvg/_player_ad_functions":7,"./js_dzsvg/_video-element-functions":8,"./js_dzsvg/components/_volume":9,"./js_player/_player_helpers":10,"./js_player/_player_lifecycle":12,"./js_player/_player_setupAd":13,"./js_player/_player_setupMedia":14,"./js_player/_vimeoPlayerCommands":15,"./js_player/view/_player_viewDraw":16,"./shared/_viewFunctions":17}]},{},[18])


//# sourceMappingURL=vplayer.js.map