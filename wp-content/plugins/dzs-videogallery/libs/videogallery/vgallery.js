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
exports.getDefaultPlaylistSettings = exports.VIDEO_GALLERY_MODES = void 0;
const getDefaultPlaylistSettings = () => {
  return {
    init_on: "init",
    randomise: "off",
    sliderAreaHeight: '300',
    // -- "300" is default, overwritten by responsive_ratio

    // -- video play options
    autoplay: "off",
    // -- autoplay ( deprecated )
    autoplayFirstVideo: undefined,
    // -- autoplay ( deprecated )
    autoplayNext: "on",
    // -- play the next video when one finishes
    cueFirstVideo: 'on',
    // -- load first video

    // -- playlist playing options
    startItem: 'default',
    playorder: "normal",
    // -- normal or reverse
    loop_playlist: "on",
    // -- loop the playlist from the beginning when the end has been reached

    // -- navigation params
    menu_position: 'right',
    menuitem_width: "default",
    // -- *deprecated
    menuitem_height: "default",
    // -- *deprecated

    navigation_isUltibox: false,
    navigation_gridClassItemsContainer: "default",
    // -- only for some modes
    navigation_direction: "auto",
    // -- "auto" -> "vertical" / "horizontal"
    navigation_maxHeight: "auto",
    // -- only for navigation_direction:"vertical" AND menu_position:"top"|"bottom"
    navigation_viewAnimationDuration: null,
    // -- number
    navigation_mainDimensionItemWidth: '',
    navigation_mainDimensionItemHeight: '',
    navigation_mainDimensionSpace: '',
    // -- space between main container and
    nav_type_outer_max_height: '',
    // -- enable a scroller if menu height bigger then max_height *deprecated todo: replace with navigation_maxHeight
    nav_type: "thumbs",
    // -- "thumbs" or "thumbsandarrows" or "scroller"
    // -- navigation params END
    nav_type_outer_grid: 'dzs-layout--4-cols',
    // -- four-per-row --- only for navPosition: "top"|"bottom" and navigation_direction: "vertical"
    nav_type_auto_scroll: "off",
    // -- auto scroll nav
    design_navigationUseEasing: 'off',
    settings_secondCon: null,
    settings_outerNav: null,
    extra_class_slider_con: '',
    // -- lightbox suggested params
    ultibox_suggestedWidth: '800',
    // -- the mode wall video ( when opened ) dimensions
    ultibox_suggestedHeight: '500',
    // -- the mode wall video ( when opened ) dimensions

    easing_speed: "",
    transition_type: "slideup",
    // --
    design_skin: '',
    // -- *deprecated -> use class
    videoplayersettings: {},
    // -- array or string from "window.dzsvg_vpconfigs"
    embedCode: '',
    php_media_data_retriever: '',
    // -- this can help get the video meta data for youtube and vimeo
    settings_enable_linking: 'off',
    // -- enable deeplinking on video gallery items
    settings_mode: 'normal',
    /// -- normal / wall / rotator / rotator3d / slider / stack
    mode_normal_video_mode: 'auto',
    // -- auto or "one" ( only one video player )
    settings_disableVideo: 'off',
    // -- disable the video area
    settings_enableHistory: 'off',
    // -- html5 history api for link type items
    settings_enableHistory_fornormalitems: 'off',
    // html5 history api for normal items
    settings_ajax_extraDivs: '',
    // extra divs to pull in the ajax request
    settings_separation_mode: 'normal',
    // -- normal ( no pagination ) or pages or scroll or button
    settings_separation_pages: [],
    settings_separation_pages_number: '5',
    //=== the number of items per 'page'
    settings_menu_overlay: 'off',
    // -- an overlay to appear over the menu
    search_field: 'off',
    // -- an overlay to appear over the menu
    search_field_con: null,
    // -- an overlay to appear over the menu
    settings_trigger_resize: '0',
    // -- a force trigger resize every x ms
    settings_go_to_next_after_inactivity: '0',
    // -- go to next track if no action
    init_all_players_at_init: 'off',
    menu_description_format: '',
    // -- (*deprecated) use the new layout builder-- use something like "{{number}}{{menuimage}}{{title}}{{desc}}"
    masonry_options: {}
  };
};
exports.getDefaultPlaylistSettings = getDefaultPlaylistSettings;
const VIDEO_GALLERY_MODES = exports.VIDEO_GALLERY_MODES = {
  NORMAL: 'normal',
  WALL: 'wall',
  VIDEOWALL: 'videowall',
  ROTATOR: 'rotator',
  ROTATOR3D: 'rotator3d',
  SLIDER: 'slider'
};

},{}],4:[function(require,module,exports){
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

},{}],5:[function(require,module,exports){
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

},{"../configs/Constants":1,"../js_common/_dzs_helpers":4,"../js_player/_player_setupAd":15,"./_dzsvg_svgs":6}],6:[function(require,module,exports){
"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.svg_volume_icon=exports.svg_volume_active_skin_default=exports.svg_quality_icon=exports.svg_play_simple_skin_bigplay_pro=exports.svg_pause_simple_skin_aurora=exports.svg_mute_icon=exports.svg_mute_btn=exports.svg_full_icon=exports.svg_embed=exports.svg_default_volume_static=exports.svg_aurora_play_btn=exports.svgShareIcon=exports.svgSearchIcon=exports.svgReplayIcon=exports.svgForwardButton=exports.svgForSkin_boxyRounded2=exports.svgForSkin_boxyRounded=exports.svgBackButton=void 0;const svg_quality_icon=exports.svg_quality_icon='<svg class="the-icon" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="896.025px" height="896.025px" viewBox="0 0 896.025 896.025" style="enable-background:new 0 0 896.025 896.025;" xml:space="preserve"> <g> <path id="settings_1_" d="M863.24,382.771l-88.759-14.807c-6.451-26.374-15.857-51.585-28.107-75.099l56.821-70.452 c12.085-14.889,11.536-36.312-1.205-50.682l-35.301-39.729c-12.796-14.355-34.016-17.391-50.202-7.165l-75.906,47.716 c-33.386-23.326-71.204-40.551-112-50.546l-14.85-89.235c-3.116-18.895-19.467-32.759-38.661-32.759h-53.198 c-19.155,0-35.561,13.864-38.608,32.759l-14.931,89.263c-33.729,8.258-65.353,21.588-94.213,39.144l-72.188-51.518 c-15.558-11.115-36.927-9.377-50.504,4.171l-37.583,37.61c-13.548,13.577-15.286,34.946-4.142,50.504l51.638,72.326 c-17.391,28.642-30.584,60.086-38.841,93.515l-89.743,14.985C13.891,385.888,0,402.24,0,421.435v53.156 c0,19.193,13.891,35.547,32.757,38.663l89.743,14.985c6.781,27.508,16.625,53.784,29.709,78.147L95.647,676.44 c-12.044,14.875-11.538,36.312,1.203,50.669l35.274,39.73c12.797,14.382,34.028,17.363,50.216,7.163l77-48.37 c32.581,22.285,69.44,38.664,108.993,48.37l14.931,89.25c3.048,18.896,19.453,32.76,38.608,32.76h53.198 c19.194,0,35.545-13.863,38.661-32.759l14.875-89.25c33.308-8.147,64.531-21.245,93.134-38.5l75.196,53.705 c15.53,11.155,36.915,9.405,50.478-4.186l37.598-37.597c13.532-13.536,15.365-34.893,4.127-50.479l-53.536-75.059 c17.441-28.738,30.704-60.238,38.909-93.816l88.758-14.82c18.921-3.116,32.756-19.469,32.756-38.663v-53.156 C895.998,402.24,882.163,385.888,863.24,382.771z M449.42,616.013c-92.764,0-168-75.25-168-168c0-92.764,75.236-168,168-168 c92.748,0,167.998,75.236,167.998,168C617.418,540.763,542.168,616.013,449.42,616.013z"/> </g> </svg>',svg_default_volume_static=exports.svg_default_volume_static='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="14px" viewBox="0 5 24 14" enable-background="new 0 5 24 14" xml:space="preserve"> <path d="M0,19h24V5L0,19z M22,17L5,17.625l12-6.227l5-2.917V17z"/> </svg>',svg_volume_icon=exports.svg_volume_icon='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="10px" height="12px" viewBox="0 0 10 12" enable-background="new 0 0 10 12" xml:space="preserve"> <path fill-rule="evenodd" clip-rule="evenodd" fill="#200C34" d="M8.475,0H7.876L5.323,1.959c0,0-0.399,0.667-1.157,0.667H1.454 c0,0-1.237,0.083-1.237,1.334v3.962c0,0-0.159,1.334,1.277,1.334h2.553c0,0,0.877,0.167,1.316,0.667l2.513,1.959l0.638,0.083 c0,0,0.678,0,0.678-0.667V0.667C9.193,0.667,9.153,0,8.475,0z"/> </svg>',svg_aurora_play_btn=exports.svg_aurora_play_btn='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="100%" height="100%" viewBox="0 0 13.75 12.982" enable-background="new 0 0 13.75 12.982" xml:space="preserve"> <path d="M11.889,5.71L3.491,0.108C3.389,0.041,3.284,0,3.163,0C2.834,0,2.565,0.304,2.565,0.676H2.562v11.63h0.003 c0,0.372,0.269,0.676,0.597,0.676c0.124,0,0.227-0.047,0.338-0.115l8.389-5.595c0.199-0.186,0.326-0.467,0.326-0.781 S12.088,5.899,11.889,5.71z"/> </svg>',svg_embed=exports.svg_embed='<svg width="32.00199890136719" height="32" viewBox="0 0 32.00199890136719 32" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g><path d="M 23.586,9.444c 0.88,0.666, 1.972,1.064, 3.16,1.064C 29.648,10.508, 32,8.156, 32,5.254 C 32,2.352, 29.648,0, 26.746,0c-2.9,0-5.254,2.352-5.254,5.254c0,0.002,0,0.004,0,0.004L 8.524,11.528 C 7.626,10.812, 6.49,10.38, 5.254,10.38C 2.352,10.38,0,12.734,0,15.634s 2.352,5.254, 5.254,5.254c 1.048,0, 2.024-0.312, 2.844-0.84 l 13.396,6.476c0,0.002,0,0.004,0,0.004c0,2.902, 2.352,5.254, 5.254,5.254c 2.902,0, 5.254-2.352, 5.254-5.254 c0-2.902-2.352-5.254-5.254-5.254c-1.188,0-2.28,0.398-3.16,1.064L 10.488,16.006c 0.006-0.080, 0.010-0.158, 0.012-0.238L 23.586,9.444z"></path></g></svg>',svg_mute_btn=exports.svg_mute_btn='<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="72.786px" height="72.786px" viewBox="0 0 72.786 72.786" enable-background="new 0 0 72.786 72.786" xml:space="preserve"> <g id="Capa_1"> <g> <g id="Volume_Off"> <g> <path d="M38.479,4.216c-1.273-0.661-2.819-0.594-4.026,0.188L13.858,17.718h-2.084C5.28,17.718,0,22.84,0,29.135v14.592 c0,6.296,5.28,11.418,11.774,11.418h2.088L34.46,68.39c0.654,0.421,1.41,0.632,2.17,0.632c0.636,0,1.274-0.148,1.854-0.449 c1.274-0.662,2.067-1.949,2.067-3.355V7.572C40.551,6.172,39.758,4.878,38.479,4.216z"/> </g> </g> </g> </g> <g id="only-if-mute"> <path d="M67.17,35.735l4.469-4.334c1.529-1.48,1.529-3.896-0.004-5.377c-1.529-1.489-4.018-1.489-5.553,0l-4.461,4.328 l-4.045-3.923c-1.535-1.489-4.021-1.489-5.552,0c-1.534,1.489-1.534,3.896,0,5.378l4.048,3.926l-3.63,3.521 c-1.53,1.488-1.53,3.896,0,5.386c0.767,0.737,1.771,1.112,2.774,1.112c1.005,0,2.009-0.375,2.775-1.112l3.629-3.521l4.043,3.92 c0.769,0.744,1.771,1.121,2.775,1.121c1.004,0,2.008-0.377,2.773-1.121c1.533-1.48,1.533-3.89,0-5.377L67.17,35.735z"/> </g> </svg> ',svg_mute_icon=exports.svg_mute_icon='<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px"  y="0px" viewBox="0 0 196.78 196.78" style="enable-background:new 0 0 196.78 196.78;" xml:space="preserve" width="14px" height="14px"> <g > <path style="fill-rule:evenodd;clip-rule:evenodd;" d="M144.447,3.547L80.521,53.672H53.674c-13.227,0-17.898,4.826-17.898,17.898 v26.4v27.295c0,13.072,4.951,17.898,17.898,17.898h26.848l63.926,50.068c7.668,4.948,16.558,6.505,16.558-7.365V10.914 C161.005-2.956,152.115-1.4,144.447,3.547z" fill="#494b4d"/> </g> </svg> ',svg_play_simple_skin_bigplay_pro=exports.svg_play_simple_skin_bigplay_pro='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="120px" height="120px" viewBox="0 0 120 120" enable-background="new 0 0 120 120" xml:space="preserve"> <path fill-rule="evenodd" clip-rule="evenodd" fill="#D0ECF3" d="M79.295,56.914c2.45,1.705,2.45,4.468,0,6.172l-24.58,17.103 c-2.45,1.704-4.436,0.667-4.436-2.317V42.129c0-2.984,1.986-4.022,4.436-2.318L79.295,56.914z M0.199,54.604 c-0.265,2.971-0.265,7.821,0,10.792c2.57,28.854,25.551,51.835,54.405,54.405c2.971,0.265,7.821,0.265,10.792,0 c28.854-2.57,51.835-25.551,54.405-54.405c0.265-2.971,0.265-7.821,0-10.792C117.231,25.75,94.25,2.769,65.396,0.198 c-2.971-0.265-7.821-0.265-10.792,0C25.75,2.769,2.769,25.75,0.199,54.604z M8.816,65.394c-0.309-2.967-0.309-7.82,0-10.787 c2.512-24.115,21.675-43.279,45.79-45.791c2.967-0.309,7.821-0.309,10.788,0c24.115,2.512,43.278,21.675,45.79,45.79 c0.309,2.967,0.309,7.821,0,10.788c-2.512,24.115-21.675,43.279-45.79,45.791c-2.967,0.309-7.821,0.309-10.788,0 C30.491,108.672,11.328,89.508,8.816,65.394z"/> </svg>',svg_pause_simple_skin_aurora=exports.svg_pause_simple_skin_aurora='<svg version="1.1" id="Layer_2" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="13.75px" height="12.982px" viewBox="0 0 13.75 12.982" enable-background="new 0 0 13.75 12.982" xml:space="preserve"> <g> <path d="M5.208,11.982c0,0.55-0.45,1-1,1H3c-0.55,0-1-0.45-1-1V1c0-0.55,0.45-1,1-1h1.208c0.55,0,1,0.45,1,1V11.982z"/> </g> <g> <path d="M12.208,11.982c0,0.55-0.45,1-1,1H10c-0.55,0-1-0.45-1-1V1c0-0.55,0.45-1,1-1h1.208c0.55,0,1,0.45,1,1V11.982z"/> </g> </svg> ',svg_volume_active_skin_default=exports.svg_volume_active_skin_default='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="14px" viewBox="0 5 24 14" enable-background="new 0 5 24 14" xml:space="preserve"> <path d="M0,19h24V5L0,19z M22,17L22,17V8.875V8.481V17z"/> </svg>',svg_full_icon=exports.svg_full_icon='<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="16px" height="16px" viewBox="0 0 16 16" enable-background="new 0 0 16 16" xml:space="preserve"> <g id="Layer_3"> <polygon fill="#FFFFFF" points="2.404,2.404 0.057,4.809 0.057,0 4.751,0 "/> <polygon fill="#FFFFFF" points="13.435,2.404 11.03,0.057 15.839,0.057 15.839,4.751 "/> <polygon fill="#FFFFFF" points="2.404,13.446 4.809,15.794 0,15.794 0,11.1 "/> <polygon fill="#FFFFFF" points="13.435,13.446 15.781,11.042 15.781,15.851 11.087,15.851 "/> </g> <g id="Layer_2"> <rect x="4.255" y="4.274" fill="#FFFFFF" width="7.366" height="7.442"/> </g> </svg>',svgReplayIcon=exports.svgReplayIcon='<?xml version="1.0" encoding="iso-8859-1"?> <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 17.12 17.12" style="enable-background:new 0 0 17.12 17.12;" xml:space="preserve"> <path style="" d="M8.661,0.001c0.006,0,0.01,0,0.01,0c0.007,0,0.007,0,0.011,0c0.002,0,0.007,0,0.009,0 c0,0,0,0,0.004,0c0.019-0.002,0.027,0,0.039,0c2.213,0,4.367,0.876,5.955,2.42l1.758-1.776c0.081-0.084,0.209-0.11,0.314-0.065 c0.109,0.044,0.186,0.152,0.186,0.271l-0.294,6.066h-5.699c-0.003,0-0.011,0-0.016,0c-0.158,0-0.291-0.131-0.291-0.296 c0-0.106,0.059-0.201,0.146-0.252l1.73-1.751c-1.026-0.988-2.36-1.529-3.832-1.529c-2.993,0.017-5.433,2.47-5.433,5.51 c0.023,2.978,2.457,5.4,5.481,5.422c1.972-0.106,3.83-1.278,4.719-3.221l2.803,1.293l-0.019,0.039 c-1.92,3.713-4.946,5.277-8.192,4.944c-4.375-0.348-7.848-4.013-7.878-8.52C0.171,3.876,3.976,0.042,8.661,0.001z"/></svg> ',svgBackButton=exports.svgBackButton='<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"                width="32px" height="32px" viewBox="0 0 32 32" enable-background="new 0 0 32 32" xml:space="preserve"><path fill="#515151" d="M7.927,17.729l9.619,9.619c0.881,0.881,2.325,0.881,3.206,0l0.803-0.804c0.881-0.88,0.881-2.323,0-3.204l-7.339-7.342l7.34-7.34c0.881-0.882,0.881-2.325,0-3.205l-0.803-0.803c-0.881-0.882-2.325-0.882-3.206,0l-9.619,9.619                C7.454,14.744,7.243,15.378,7.278,16C7.243,16.621,7.452,17.256,7.927,17.729z"/></svg>',svgForwardButton=exports.svgForwardButton='<svg enable-background="new 0 0 32 32" height="32px" id="Layer_1" version="1.1" viewBox="0 0 32 32" width="32px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M24.291,14.276L14.705,4.69c-0.878-0.878-2.317-0.878-3.195,0l-0.8,0.8c-0.878,0.877-0.878,2.316,0,3.194  L18.024,16l-7.315,7.315c-0.878,0.878-0.878,2.317,0,3.194l0.8,0.8c0.878,0.879,2.317,0.879,3.195,0l9.586-9.587  c0.472-0.471,0.682-1.103,0.647-1.723C24.973,15.38,24.763,14.748,24.291,14.276z" fill="#515151"/></svg>',svgForSkin_boxyRounded=exports.svgForSkin_boxyRounded=' <svg class="svg_rounded" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="1px" height="1px" viewBox="0 0 1 1" enable-background="new 0 0 1 1" xml:space="preserve"> <g id="Layer_1"> </g> <g id="Layer_2"> <g> <defs> <path id="SVGID_1_" d="M1,0.99C1,0.996,0.996,1,0.99,1H0.01C0.004,1,0,0.996,0,0.99V0.01C0,0.004,0.004,0,0.01,0h0.98 C0.996,0,1,0.004,1,0.01V0.99z"/> </defs> <clipPath id="SVGID_2_"  clipPathUnits="objectBoundingBox"> <use xlink:href="#SVGID_1_" overflow="visible"/> </clipPath> <path clip-path="url(#SVGID_2_)" fill="#2A2F3F" d="M3,1.967C3,1.985,2.984,2,2.965,2h-3.93C-0.984,2-1,1.985-1,1.967v-2.934 C-1-0.985-0.984-1-0.965-1h3.93C2.984-1,3-0.985,3-0.967V1.967z"/> </g> </g> </svg>  ',svgForSkin_boxyRounded2=exports.svgForSkin_boxyRounded2=' <svg class="svg_rounded" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="1px" height="1px" viewBox="0 0 1 1" enable-background="new 0 0 1 1" xml:space="preserve"> <g id="Layer_1"> </g> <g id="Layer_2"> <g> <defs> <path id="SVGID_1_" d="M1,0.99C1,0.996,0.996,1,0.99,1H0.01C0.004,1,0,0.996,0,0.99V0.01C0,0.004,0.004,0,0.01,0h0.98 C0.996,0,1,0.004,1,0.01V0.99z"/> </defs> <clipPath id="SVGID_2_"  clipPathUnits="objectBoundingBox"> <use xlink:href="#SVGID_1_" overflow="visible"/> </clipPath> <path clip-path="url(#SVGID_2_)" fill="#2A2F3F" d="M3,1.967C3,1.985,2.984,2,2.965,2h-3.93C-0.984,2-1,1.985-1,1.967v-2.934 C-1-0.985-0.984-1-0.965-1h3.93C2.984-1,3-0.985,3-0.967V1.967z"/> </g> </g> </svg>  ',svgSearchIcon=exports.svgSearchIcon=' <svg class="search-icon" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="15px" height="15px" viewBox="230.042 230.042 15 15" enable-background="new 230.042 230.042 15 15" xml:space="preserve"> <g> <path fill="#898383" d="M244.708,243.077l-3.092-3.092c0.746-1.076,1.118-2.275,1.118-3.597c0-0.859-0.167-1.681-0.501-2.465 c-0.333-0.784-0.783-1.46-1.352-2.028s-1.244-1.019-2.027-1.352c-0.785-0.333-1.607-0.5-2.466-0.5s-1.681,0.167-2.465,0.5 s-1.46,0.784-2.028,1.352s-1.019,1.244-1.352,2.028s-0.5,1.606-0.5,2.465s0.167,1.681,0.5,2.465s0.784,1.46,1.352,2.028 s1.244,1.019,2.028,1.352c0.784,0.334,1.606,0.501,2.465,0.501c1.322,0,2.521-0.373,3.597-1.118l3.092,3.083 c0.217,0.229,0.486,0.343,0.811,0.343c0.312,0,0.584-0.114,0.812-0.343c0.228-0.228,0.342-0.499,0.342-0.812 C245.042,243.569,244.931,243.3,244.708,243.077z M239.241,239.241c-0.79,0.79-1.741,1.186-2.853,1.186s-2.062-0.396-2.853-1.186 c-0.79-0.791-1.186-1.741-1.186-2.853s0.396-2.063,1.186-2.853c0.79-0.791,1.741-1.186,2.853-1.186s2.062,0.396,2.853,1.186 s1.186,1.741,1.186,2.853S240.032,238.45,239.241,239.241z"/> </g> </svg>  ',svgShareIcon=exports.svgShareIcon=' <svg width="32" height="33.762001037597656" viewBox="0 0 32 33.762001037597656" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g><path d="M 22,6c0-3.212-2.788-6-6-6S 10,2.788, 10,6c0,3.212, 2.788,6, 6,6S 22,9.212, 22,6zM 16,14c-5.256,0-10,5.67-10,12.716s 20,7.046, 20,0S 21.256,14, 16,14z"></path></g></svg>  ';
},{}],7:[function(require,module,exports){
"use strict";function secondCon_initFunctions(){var e=jQuery;e(document).off("click",".dzsas-second-con .read-more-label"),e(document).on("click",".dzsas-second-con .read-more-label",function(t){var n=e(this),s=n.parent(),o=s.children(".read-more-content").eq(0);if(s.hasClass("active"))o.css({height:0},{queue:!1,duration:_Constants.ConstantsDzsvg.ANIMATIONS_DURATION,complete:function(e){}}),s.removeClass("active");else{o.css("height","auto");var i=o.outerHeight();o.css("height",0),o.css({height:i},{queue:!1,duration:_Constants.ConstantsDzsvg.ANIMATIONS_DURATION,complete:function(t){e(this).css("height","auto")}}),s.addClass("active")}return!1}),jQuery(".dzsas-second-con").each(function(){var e=jQuery(this),t=e;t.find(".item").eq(1).children(".menudescriptioncon").html()||t.find(".item").eq(2).children(".menudescriptioncon").html()&&t.find(".item").eq(1).remove();var n=0;e.find(".videogallery--navigation-outer--bigblock").each(function(){jQuery(this).css("left",n+"%"),n+=100});var n=0;if(e.find(".item").each(function(){jQuery(this).css("left",n+"%"),n+=100}),".id_auto"===e.attr("data-vgtarget")){var s=jQuery(".videogallery,.videogallery-tobe").eq(0),o=/id_(.*?) /.exec(s.attr("class"));o&&o[1]&&e.attr("data-vgtarget",".id_"+o[1]),s.get(0)&&s.get(0).api_set_secondCon&&s.get(0).api_set_secondCon(e),setTimeout(function(){s.get(0)&&s.get(0).api_set_secondCon&&s.get(0).api_set_secondCon(e)},1e3)}})}Object.defineProperty(exports,"__esModule",{value:!0}),exports.secondCon_initFunctions=secondCon_initFunctions;var _Constants=require("../../configs/Constants");
},{"../../configs/Constants":1}],8:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.playlist_navigationGenerateStructure = playlist_navigationGenerateStructure;
exports.playlist_navigationStructureAssignVars = playlist_navigationStructureAssignVars;
/**
 * we need selfClass.navigation_customStructure
 * @param {DzsVideoGallery} selfClass
 * @return {string}
 */
function playlist_navigationGenerateStructure(selfClass) {
  let desc = selfClass.navigation_customStructure;
  if (!desc) {
    desc = '';
  }
  return desc;
}

/**
 *
 * @param {jQuery} $currentVideoPlayer
 * @param {string} structureMenuItemContentInner
 * @returns {string}
 */
function playlist_navigationStructureAssignVars($currentVideoPlayer, structureMenuItemContentInner) {
  /**
   *
   * @param {string} currentStructure
   * @param {string} placeholderText
   * @param {string} argInStructure
   */
  function replaceInNav(currentStructure, placeholderText, argInStructure) {
    let feedValue = '';
    if ($currentVideoPlayer.find(argInStructure).length) {
      feedValue = $currentVideoPlayer.find(argInStructure).eq(0).html();
    }
    return currentStructure.replace(placeholderText, feedValue);
  }
  structureMenuItemContentInner = replaceInNav(structureMenuItemContentInner, '{{layout-builder.replace-title}}', '.feed-menu-title');
  structureMenuItemContentInner = replaceInNav(structureMenuItemContentInner, '{{layout-builder.replace-menu-description}}', '.feed-menu-desc');
  structureMenuItemContentInner = replaceInNav(structureMenuItemContentInner, '{{layout-builder.replace-thumbnail-url}}', '.feed-menu-image');
  return structureMenuItemContentInner;
}

},{}],9:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.DzsNavigation = void 0;
var _navigationSettings = require("./configs/_navigationSettings");
var _dzs_helpers = require("../../js_common/_dzs_helpers");
var _viewFunctions = require("../../shared/_viewFunctions");
var _navigationView = require("./inc/_navigation-view");
Math.easeIn = function (t, b, c, d) {
  return -c * (t /= d) * (t - 2) + b;
};
Math.easeOut = function (t, b, c, d) {
  t /= d;
  return -c * t * (t - 2) + b;
};

/**
 *
 *
 */
class DzsNavigation {
  /**
   *
   * @param {DzsVideoGallery|any} parentClass
   * @param argOptions
   * @param $
   *  @property {number}  navigation_mainDimensionNavSize for left / right /// height for top / bottom
   */
  constructor(parentClass, argOptions, $) {
    this.$ = $;
    this.parentClass = parentClass;
    this.initOptions = null;
    this.navAttributes = null;
    this.$mainArea = null;
    /** */
    this.$mainNavigation = null;
    this.$mainNavigationClipped = null;
    this.$mainNavigationItemsContainer = null;
    this.$containerComponent = null;
    this.ultraResponsive = false;
    this.menuPosition = null;
    this.configObj = argOptions;
    this.viewOptions = {
      isSyncMainAreaAndNavigationAreas: true
    };

    // -- dimensions
    this.totalItemsWidth = 0;
    this.totalItemsHeight = 0;
    this.totalAreaWidth = 0;
    this.totalAreaHeight = 0;
    this.mainAreaHeight = 0;

    // -- thumbsAndArrows
    this.currPage = 0;
    this.nav_max_pages = 0;
    this.navigation_extraFixedElementsSize = 0;
    this.navigation_mainDimensionTotalSize = 0;
    this.navigation_mainDimensionClipSize = 0;
    this.navigation_mainDimensionItemSize = 0;
    this.navigation_mainDimensionNavSize = 0;
    this.navigation_customStructure = '';
    this.initClass();
  }

  /**
   *
   * @param {object} navAttributes
   * @returns {object}
   */
  computeNavAttributes(navAttributes) {
    if (navAttributes.menuPosition === 'right' || navAttributes.menuPosition === 'left') {
      navAttributes.navigation_direction = 'vertical';
    }
    if (navAttributes.menuPosition === 'bottom' || navAttributes.menuPosition === 'top') {
      if (navAttributes.navigation_direction === 'auto') {
        navAttributes.navigation_direction = 'horizontal';
      }
    }
    if (navAttributes.navigationType === 'simple') {
      navAttributes.navigation_direction = 'none';
    }
    return navAttributes;
  }
  computeInstanceProps() {}

  /**
   *
   * @param {jQuery} $feedItemsContainer
   */
  addNavigationItems($feedItemsContainer) {
    const itemsLength = $feedItemsContainer.find(this.configObj.feedItemNotInitedClass).length;
    for (let i = 0; i < itemsLength; i++) {
      let $currentItemFeed = $feedItemsContainer.find(this.configObj.feedItemNotInitedClass).eq(i);
      let structureMenuItemContentInner = this.navigation_customStructure;
      const final_structureMenuItemContent = (0, _navigationView.view_navigation_generateNavigationItem)(structureMenuItemContentInner, $currentItemFeed, this.configObj, this.navAttributes, this.configObj.viewNavigationIsUltibox);
      const $currentItemFeed_ = $currentItemFeed.get(0);
      $currentItemFeed.addClass('nav-treated');
      this.$mainNavigationItemsContainer.append(final_structureMenuItemContent);
      const $justAdded = this.$mainNavigationItemsContainer.children().last();
      for (let i = 0, atts = $currentItemFeed_.attributes, n = atts.length; i < n; i++) {
        if (atts[i].nodeName && atts[i].value) {
          $justAdded.data(atts[i].nodeName, atts[i].value);
        }
      }
    }
  }
  initClass() {
    const selfInstance = this;
    const parentClass = this.parentClass;
    this.configObj = Object.assign(Object.assign(_navigationSettings.defaultSettings, {}), this.configObj);
    const newOptions = Object.assign({}, this.configObj);
    this.initOptions = {
      ...newOptions
    };
    this.navAttributes = {
      ...newOptions
    };
    this.menuPosition = this.navAttributes.menuPosition;
    this.navigation_customStructure = this.navAttributes.navigationStructureHtml;
    const navAttributes = this.computeNavAttributes(this.navAttributes);
    let isMenuMoveLocked = false,
      navMain_mousex = 0,
      navMain_mousey = 0;
    let target_viy = 0,
      target_vix = 0,
      begin_viy = 0,
      begin_vix = 0,
      finish_viy = 0,
      finish_vix = 0,
      change_viy = 0,
      change_vix = 0;
    const OFFSET_BUFFER = 25;
    let DURATION_EASING = 20;
    init();
    function init() {
      if (parentClass) {
        if (parentClass.cgallery) {
          selfInstance.containerComponent = selfInstance.parentClass.cgallery;
        } else {
          if (parentClass.cthis) {
            selfInstance.containerComponent = selfInstance.parentClass.cthis;
          }
        }
      }
      sanitizeInitValues();
      setupStructure();
      if ((0, _dzs_helpers.is_touch_device)()) {
        selfInstance.$mainNavigation.parent().addClass('is-touch');
      }
      if (navAttributes.gridClassItemsContainer) {
        selfInstance.$mainNavigationItemsContainer.addClass(navAttributes.gridClassItemsContainer);
      }
      selfInstance.nav_thumbsandarrows_gotoPage = nav_thumbsandarrows_gotoPage;
      selfInstance.handleMouse = handleMouse;
      setTimeout(init_navIsReady, 100);
    }
    function sanitizeInitValues() {
      if (navAttributes.menuPosition === 'left' || navAttributes.menuPosition === 'right') {
        if (isNaN(Number(selfInstance.initOptions.menuItemWidth)) || selfInstance.initOptions.menuItemWidth === '' || selfInstance.initOptions.menuItemWidth === 'default') {
          navAttributes.menuItemWidth = _navigationSettings.NAVIGATION_VIEW_MENU_VERTICAL_DEFAULT_ITEM_WIDTH;
        }
      }
      if (!navAttributes.viewEnableMediaArea) {
        selfInstance.viewOptions.isSyncMainAreaAndNavigationAreas = false;
      }
    }
    if (selfInstance.initOptions.viewAnimationDuration !== null) {
      DURATION_EASING = selfInstance.initOptions.viewAnimationDuration;
    }
    function init_navIsReady() {
      if ((0, _dzs_helpers.is_touch_device)()) {
        navAttributes.isUseEasing = false;
      }
      if (navAttributes.navigationType === 'hover') {
        handleEnterFrame();
      }
      if (navAttributes.isAutoScrollToCurrent) {
        if (navAttributes.navigationType === 'hover') {
          setTimeout(function () {
            animate_to_curr_thumb();
          }, 20);
        }
      }
    }
    function setupStructure() {
      let structure_baseLayout = '<div class="main-navigation dzs-navigation--type-' + selfInstance.initOptions.navigationType + '"><div class="navMain videogallery--navigation--clipped-container navigation--clipped-container"><div class="videogallery--navigation-container navigation--total-container">';
      structure_baseLayout += '</div></div></div>';
      parentClass.$navigationAndMainArea.addClass(`navPosition-${navAttributes.menuPosition} navType-${navAttributes.navigationType}`);
      parentClass.$navigationAndMainArea.append('<div class="sliderMain media--main-area"><div class="sliderCon"></div></div>');
      parentClass.$navigationAndMainArea.append(structure_baseLayout);
      selfInstance.$mainArea = parentClass.$navigationAndMainArea.find('.media--main-area');
      selfInstance.$mainNavigation = parentClass.$navigationAndMainArea.find('.main-navigation');
      selfInstance.$mainNavigationClipped = selfInstance.$mainNavigation.find('.navigation--clipped-container');
      selfInstance.$mainNavigationItemsContainer = selfInstance.$mainNavigation.find('.navigation--total-container');
      if (navAttributes.menuItemWidth === 'default') {
        navAttributes.menuItemWidth = '';
      }
      if (navAttributes.menuItemHeight === 'default') {
        navAttributes.menuItemHeight = '';
      }
      if (navAttributes.menuPosition === 'top' || navAttributes.menuPosition === 'bottom') {}
      if (navAttributes.menuPosition === 'top') {
        selfInstance.$mainArea.before(selfInstance.$mainNavigation);
      }
      if (navAttributes.navigationSpace) {
        parentClass.$navigationAndMainArea.css({
          gap: (0, _viewFunctions.view_cssConvertForPx)(navAttributes.navigationSpace)
        });
      }
      if (navAttributes.navigationType === 'scroller') {
        if (navAttributes.navigation_direction === 'horizontal') {
          (0, _viewFunctions.view_setCssPropsForElement)(selfInstance.$mainNavigation, {
            'minHeight': navAttributes.menuItemHeight + 'px'
          });
        }
      }
      if (navAttributes.navigationType === 'thumbsAndArrows') {
        selfInstance.$mainNavigation.prepend('<div class="nav--thumbsAndArrows--arrow thumbs-arrow-left arrow-is-inactive"></div>');
        selfInstance.$mainNavigation.append('<div class="nav--thumbsAndArrows--arrow thumbs-arrow-right"></div>');
        selfInstance.$mainNavigation.find('.thumbs-arrow-left,.thumbs-arrow-right').on('click', handleClick_navigationArrow);
      }
      if (!selfInstance.configObj.viewEnableMediaArea) {
        parentClass.$navigationAndMainArea.addClass('view--disable-video-area');
      }
      parentClass.$navigationAndMainArea.addClass(`layout-builder--menu-items--${navAttributes.navigationSkin}`);
    }
    function handleClick_navigationArrow() {
      var $t = jQuery(this);
      if ($t.hasClass('thumbs-arrow-left')) {
        gotoPrevPage();
      }
      if ($t.hasClass('thumbs-arrow-right')) {
        gotoNextPage();
      }
    }
    function gotoNextPage() {
      var tempPage = selfInstance.currPage;
      tempPage++;
      nav_thumbsandarrows_gotoPage(tempPage);
    }
    function gotoPrevPage() {
      if (selfInstance.currPage === 0) {
        return;
      }
      selfInstance.currPage--;
      nav_thumbsandarrows_gotoPage(selfInstance.currPage);
    }

    /**
     * called only from thumbsandarrows
     * @param {number} targetPageNr
     */
    function nav_thumbsandarrows_gotoPage(targetPageNr) {
      if (targetPageNr > selfInstance.nav_max_pages || navAttributes.navigationType !== 'thumbsAndArrows') {
        return;
      }
      selfInstance.$mainNavigation.find('.nav--thumbsAndArrows--arrow').removeClass('arrow-is-inactive');
      if (targetPageNr === 0) {
        selfInstance.$mainNavigation.find('.thumbs-arrow-left').addClass('arrow-is-inactive');
      }
      if (targetPageNr >= selfInstance.nav_max_pages) {
        selfInstance.$mainNavigation.find('.thumbs-arrow-right').addClass('arrow-is-inactive');
      }
      if (targetPageNr >= selfInstance.nav_max_pages) {
        if (navAttributes.navigation_direction === "vertical") {
          const targetTop = -(selfInstance.navigation_mainDimensionTotalSize - selfInstance.navigation_mainDimensionClipSize);
          (0, _viewFunctions.view_setCssPropsForElement)(selfInstance.$mainNavigationItemsContainer, {
            'top': targetTop,
            'left': 0
          });
        }
        if (navAttributes.navigation_direction === "horizontal") {
          (0, _viewFunctions.view_setCssPropsForElement)(selfInstance.$mainNavigationItemsContainer, {
            'left': -(selfInstance.navigation_mainDimensionTotalSize - selfInstance.navigation_mainDimensionClipSize),
            'top': 0
          });
        }
      } else {
        if (navAttributes.navigation_direction === "vertical") {
          let firstItemInSightWidth = selfInstance.$mainNavigationItemsContainer.children().eq(selfInstance.currPage).height();
          selfInstance.$mainNavigationItemsContainer.css({
            'top': firstItemInSightWidth * -targetPageNr,
            'left': 0
          });
        }
        if (navAttributes.navigation_direction === "horizontal") {
          let firstItemInSightWidth = selfInstance.$mainNavigationItemsContainer.children().eq(selfInstance.currPage).width();
          selfInstance.$mainNavigationItemsContainer.css({
            'left': firstItemInSightWidth * -targetPageNr,
            'top': 0
          });
        }
      }
      selfInstance.currPage = targetPageNr;
    }

    /**
     * handle mouse for the parentClass.$navigationItemsContainer element
     * @param e
     * @returns {boolean}
     */
    function handleMouse(e) {
      navMain_mousey = e.pageY - selfInstance.$mainNavigationClipped.offset().top;
      navMain_mousex = e.pageX - selfInstance.$mainNavigationClipped.offset().left;
      if (!(0, _dzs_helpers.is_ios)() && !(0, _dzs_helpers.is_android)()) {
        if (isMenuMoveLocked) {
          return false;
        }
        if (navAttributes.navigation_direction === "vertical") {
          navigation_prepareAnimateMenuY(navMain_mousey, {
            called_from: "handleMouse"
          });
        }
        if (navAttributes.navigation_direction === "horizontal") {
          navigation_prepareAnimateMenuX(navMain_mousex, {
            called_from: "handleMouse"
          });
        }
      } else {
        return false;
      }
    }

    /**
     * only for navType: "hover"
     * @returns {boolean}
     */
    function handleEnterFrame() {
      if (isNaN(target_viy)) {
        target_viy = 0;
      }
      if (DURATION_EASING === 0) {
        window.requestAnimationFrame(handleEnterFrame);
        return false;
      }
      if (navAttributes.navigation_direction === 'vertical') {
        begin_viy = target_viy;
        change_viy = finish_viy - begin_viy;
        target_viy = Number(Math.easeIn(1, begin_viy, change_viy, DURATION_EASING).toFixed(4));
        if (!(0, _dzs_helpers.is_ios)() && !(0, _dzs_helpers.is_android)()) {
          (0, _viewFunctions.view_setCssPropsForElement)(parentClass.$navigationItemsContainer, {
            'transform': 'translate3d(0,' + target_viy + 'px,0)'
          });
        }
      }
      if (navAttributes.navigation_direction === 'horizontal') {
        begin_vix = target_vix;
        change_vix = finish_vix - begin_vix;
        target_vix = Number(Math.easeIn(1, begin_vix, change_vix, DURATION_EASING).toFixed(4));
        if (!(0, _dzs_helpers.is_ios)() && !(0, _dzs_helpers.is_android)()) {
          (0, _viewFunctions.view_setCssPropsForElement)(parentClass.$navigationItemsContainer, {
            'transform': 'translate3d(' + target_vix + 'px,0,0)'
          });
        }
      }
      window.requestAnimationFrame(handleEnterFrame);
    }
    function navigation_getNavPosition(navMain_mouse) {
      const clipSize = selfInstance.navigation_mainDimensionClipSize;
      let viewMax = selfInstance.navigation_mainDimensionTotalSize - clipSize;
      const viewRatio = navMain_mouse / clipSize;
      let finish_viewIndex = (navMain_mouse + viewRatio * OFFSET_BUFFER + 2 - OFFSET_BUFFER) / clipSize * -selfInstance.navigation_mainDimensionTotalSize;
      if (finish_viewIndex > 0) {
        finish_viewIndex = 0;
      }
      if (finish_viewIndex < -viewMax) {
        finish_viewIndex = -viewMax;
      }
      return finish_viewIndex;
    }
    function navigation_prepareAnimateMenuX(navMain_mousex) {
      finish_vix = navigation_getNavPosition(navMain_mousex);
      if (navAttributes.isUseEasing) {} else {
        animate_menu_x(finish_vix);
      }
    }
    function navigation_prepareAnimateMenuY(navMain_mousey) {
      finish_viy = navigation_getNavPosition(navMain_mousey);
      if (navAttributes.isUseEasing) {} else {
        view_animateMenuVertical(finish_viy);
      }
    }
    selfInstance.animate_to_curr_thumb = animate_to_curr_thumb;
    function animate_to_curr_thumb() {
      if ((0, _dzs_helpers.is_touch_device)()) {}
      if (navAttributes.navigationType === 'hover') {
        var $activeNavItem = parentClass.$navigationItemsContainer.find('.dzs-navigation--item').eq(0);
        if (parentClass.$navigationItemsContainer.find('.dzs-navigation--item.active').length) {
          $activeNavItem = parentClass.$navigationItemsContainer.find('.dzs-navigation--item.active').eq(0);
        }
        var rat = ($activeNavItem.offset().top - parentClass.$navigationItemsContainer.offset().top) / (parentClass.$navigationItemsContainer.outerHeight() - selfInstance.$mainNavigationClipped.parent().outerHeight());
        if (navAttributes.navigation_direction === 'vertical') {
          if (parentClass.$navigationItemsContainer.outerHeight() > selfInstance.$mainNavigationClipped.parent().outerHeight()) {
            view_animateMenuVertical(rat * (parentClass.$navigationItemsContainer.outerHeight() - selfInstance.$mainNavigationClipped.parent().outerHeight()), {
              'called_from': 'animate_to_curr_thumb'
            });
          }
        } else {
          if (navAttributes.navigation_direction === 'horizontal') {
            rat = ($activeNavItem.offset().left - parentClass.$navigationItemsContainer.offset().left) / (parentClass.$navigationItemsContainer.outerWidth() - selfInstance.$mainNavigationClipped.outerWidth());
            navigation_prepareAnimateMenuX(rat * selfInstance.$mainNavigationClipped.outerWidth());
          }
        }
      }
      if (navAttributes.navigationType === 'scroller') {
        var coordinateToActiveItem = 0;
        if (parentClass.$navigationItemsContainer.find('.dzs-navigation--item.active').length) {
          coordinateToActiveItem = parentClass.$navigationItemsContainer.find('.dzs-navigation--item.active').offset().top - parentClass.$navigationItemsContainer.eq(0).offset().top;
          setTimeout(function () {
            if (selfInstance.$mainNavigationClipped.get(0).api_scrolly_to) {
              selfInstance.$mainNavigationClipped.get(0).api_scrolly_to(coordinateToActiveItem);
            }
          }, _navigationSettings.NAVIGATION_DEFAULT_TIMEOUT);
        }
      }
    }
    function animate_menu_x() {
      if (!(0, _dzs_helpers.is_ios)() && !(0, _dzs_helpers.is_android)()) {
        if (navAttributes.isUseEasing) {
          (0, _viewFunctions.view_setCssPropsForElement)(parentClass.$navigationItemsContainer, {
            'transform': 'translate3d(' + finish_vix + 'px, ' + 0 + 'px, 0)'
          });
        }
      }
    }
    function view_animateMenuVertical(viewIndex, pargs) {
      // -- positive number viewIndexX
      var margs = {
        called_from: "default"
      };
      if (pargs) {
        margs = jQuery.extend(margs, pargs);
      }
      if (!(0, _dzs_helpers.is_touch_device)()) {
        if (!navAttributes.isUseEasing) {
          (0, _viewFunctions.view_setCssPropsForElement)(parentClass.$navigationItemsContainer, {
            'transform': 'translate3d(0, ' + finish_viy + 'px, 0)'
          });
        } else {
          if (-finish_viy < selfInstance.navigation_mainDimensionTotalSize - selfInstance.$mainNavigation.outerHeight()) {
            finish_viy = -(selfInstance.navigation_mainDimensionTotalSize - selfInstance.$mainNavigation.outerHeight());
          }
          finish_viy = -viewIndex;
        }
      } else {
        if (margs.called_from === 'animate_to_curr_thumb') {
          setTimeout(function () {
            selfInstance.$mainNavigation.scrollTop(viewIndex);
          }, 1500);
        }
      }
    }
  }
  calculateDims(pargs = {}) {
    const selfInstance = this;
    (0, _navigationView.view_navigation_calculateDims)(selfInstance, pargs);
  }
}
exports.DzsNavigation = DzsNavigation;

},{"../../js_common/_dzs_helpers":4,"../../shared/_viewFunctions":22,"./configs/_navigationSettings":10,"./inc/_navigation-view":11}],10:[function(require,module,exports){
"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.defaultSettings=exports.NAVIGATION_VIEW_MENU_VERTICAL_DEFAULT_ITEM_WIDTH=exports.NAVIGATION_DEFAULT_TIMEOUT=void 0;const defaultSettings=exports.defaultSettings={navigationType:"hover",navigationSkin:"skin-default",menuPosition:"bottom",navigation_direction:"auto",menuItemWidth:"default",menuItemHeight:"default",navigationSpace:void 0,feedItemNotInitedClass:".vplayer-tobe",isAutoScrollToCurrent:!1,isSyncMainAreaAndNavigationAreas:!0,viewEnableMediaArea:!0,viewNavigationIsUltibox:!1,isUseEasing:!0,parentSkin:"",filter_structureMenuItemContent:null,viewAnimationDuration:null,navigationStructureHtml:"",gridClassItemsContainer:""},NAVIGATION_DEFAULT_TIMEOUT=exports.NAVIGATION_DEFAULT_TIMEOUT=320,NAVIGATION_VIEW_MENU_VERTICAL_DEFAULT_ITEM_WIDTH=exports.NAVIGATION_VIEW_MENU_VERTICAL_DEFAULT_ITEM_WIDTH=254;
},{}],11:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.view_navigation_calculateDims = view_navigation_calculateDims;
exports.view_navigation_generateNavigationItem = view_navigation_generateNavigationItem;
var _navigationHelpers = require("../_navigation-helpers");
var _viewFunctions = require("../../../shared/_viewFunctions");
var _dzs_helpers = require("../../../js_common/_dzs_helpers");
/**
 *
 * @param {DzsNavigation} selfInstance
 * @param pargs
 * @returns {{navigation_mainDimensionNavSize: number, navigation_mainDimensionTotalSize: *}}
 */
function view_navigation_calculateDims(selfInstance, pargs = {}) {
  const calculateDimsArgs = Object.assign({
    forceMainAreaHeight: null
  }, pargs);
  const parentClass = selfInstance.parentClass;
  const navAttributes = selfInstance.navAttributes;
  selfInstance.mainAreaHeight = calculateDimsArgs.forceMainAreaHeight ? calculateDimsArgs.forceMainAreaHeight : selfInstance.$mainArea.outerHeight();
  let totalAreaHeightPixels = 0;
  selfInstance.totalAreaWidth = parentClass.$navigationAndMainArea.outerWidth();
  selfInstance.totalAreaHeight = parentClass.$navigationAndMainArea.outerHeight();
  let mainNavigationDesiredWidth = navAttributes.menuItemWidth;
  if (navAttributes.navigation_direction === 'vertical') {
    selfInstance.navigation_mainDimensionTotalSize = selfInstance.$mainNavigationItemsContainer.height();
    selfInstance.navigation_mainDimensionClipSize = selfInstance.$mainNavigationClipped.height();
    selfInstance.navigation_mainDimensionItemSize = selfInstance.$mainNavigationItemsContainer.children().eq(0).height();
  }
  if (navAttributes.navigation_direction === 'horizontal') {
    selfInstance.navigation_mainDimensionTotalSize = selfInstance.$mainNavigationItemsContainer.width();
    selfInstance.navigation_mainDimensionClipSize = selfInstance.$mainNavigationClipped.width();
    selfInstance.navigation_mainDimensionItemSize = selfInstance.$mainNavigationItemsContainer.children().eq(0).width();
  }
  selfInstance.nav_max_pages = Math.ceil(selfInstance.navigation_mainDimensionTotalSize / selfInstance.navigation_mainDimensionClipSize);
  parentClass.$navigationAndMainArea.children().each(function () {
    const $navigationChild = selfInstance.$(selfInstance);
    totalAreaHeightPixels += $navigationChild.get(0).scrollHeight;
  });

  // -- ultra-responsive
  // todo: remove dependency on parentClass
  if (selfInstance.configObj.viewEnableMediaArea && (navAttributes.menuPosition === 'right' || navAttributes.menuPosition === 'left')) {
    selfInstance.navigation_mainDimensionNavSize = navAttributes.menuItemWidth;
    if (selfInstance.totalAreaWidth - mainNavigationDesiredWidth < mainNavigationDesiredWidth) {
      if (selfInstance.containerComponent) {
        selfInstance.containerComponent.addClass('ultra-responsive');
      }
      parentClass.$navigationAndMainArea.addClass('nav-is-ultra-responsive');
      selfInstance.ultraResponsive = true;
    } else {
      parentClass.$navigationAndMainArea.removeClass('nav-is-ultra-responsive');
      selfInstance.ultraResponsive = false;
    }
  }
  if (navAttributes.menuPosition === 'top' || navAttributes.menuPosition === 'bottom') {}
  if (navAttributes.menuPosition === 'right' || navAttributes.menuPosition === 'left') {
    if (!selfInstance.ultraResponsive) {
      if (navAttributes.menuItemWidth) {
        (0, _viewFunctions.view_setCssPropsForElement)(selfInstance.$mainNavigation, {
          'flex-basis': `${(0, _dzs_helpers.sanitizeToCssPx)(navAttributes.menuItemWidth)}`
        });
      }
      if (selfInstance.viewOptions.isSyncMainAreaAndNavigationAreas) {
        (0, _viewFunctions.view_setCssPropsForElement)(selfInstance.$mainNavigation, {
          'height': `${selfInstance.mainAreaHeight}`
        });
      }
      if (navAttributes.navigation_mainDimensionSpace) {
        (0, _viewFunctions.view_setCssPropsForElement)(parentClass.$navigationAndMainArea, {
          'grid-gap': `${(0, _dzs_helpers.sanitizeToCssPx)(navAttributes.navigation_mainDimensionSpace)}`
        });
      }
    } else {}
  }
  let navWidth = 0;
  selfInstance.totalItemsWidth = parentClass.$navigationItemsContainer.outerWidth();
  selfInstance.totalItemsHeight = parentClass.$navigationItemsContainer.outerHeight();

  // -- hover
  if (navAttributes.navigationType === 'hover' || navAttributes.navigationType === 'thumbsAndArrows') {
    if (navAttributes.navigation_direction === 'horizontal') {
      navWidth = 0;
      parentClass.$navigationItemsContainer.children().each(function () {
        const $t = selfInstance.$(this);
        navWidth += $t.outerWidth();
      });
      if (navWidth > selfInstance.totalAreaWidth) {
        selfInstance.$mainNavigation.off('mousemove', selfInstance.handleMouse);
        selfInstance.$mainNavigation.on('mousemove', selfInstance.handleMouse);
        selfInstance.containerComponent.removeClass('navWidth-bigger-then-totalWidth');
      } else {
        selfInstance.containerComponent.addClass('navWidth-bigger-then-totalWidth');
        (0, _viewFunctions.view_setCssPropsForElement)(parentClass.$navigationItemsContainer, {
          'left': ''
        });
        selfInstance.$mainNavigation.off('mousemove', selfInstance.handleMouse);
      }
    }
    if (navAttributes.navigation_direction === 'vertical') {
      if (selfInstance.totalItemsHeight > selfInstance.totalAreaHeight) {
        selfInstance.$mainNavigation.off('mousemove', selfInstance.handleMouse);
        selfInstance.$mainNavigation.on('mousemove', selfInstance.handleMouse);
      } else {
        (0, _viewFunctions.view_setCssPropsForElement)(parentClass.$navigationItemsContainer, {
          'top': ''
        });
        selfInstance.$mainNavigation.off('mousemove', selfInstance.handleMouse);
      }
    }
  }
  return {
    navigation_mainDimensionTotalSize: selfInstance.navigation_mainDimensionTotalSize,
    navigation_mainDimensionNavSize: selfInstance.navigation_mainDimensionNavSize
  };
}

/**
 *
 * @param {string} structureMenuItemContentInner
 * @param {jQuery} $currentItemFeed
 * @param {object} configObj
 * @param {object} navAttributes
 * @param {boolean} isUltiboxItem
 * @returns string
 */
function view_navigation_generateNavigationItem(structureMenuItemContentInner, $currentItemFeed, configObj, navAttributes, isUltiboxItem = false) {
  let final_structureMenuItemContent = '';
  if (structureMenuItemContentInner) {
    structureMenuItemContentInner = (0, _navigationHelpers.playlist_navigationStructureAssignVars)($currentItemFeed, structureMenuItemContentInner);
    // -- add parent default skin todo: we will have navigation skin
    structureMenuItemContentInner = structureMenuItemContentInner.replace('<div class="layout-builder--structure', '<div class="layout-builder--structure layout-builder--parent-style-' + navAttributes.parentSkin);
  }
  let navigationItemDomTag = 'div';
  let navigationItemExtraAttr = ' ';
  let navigationItemExtraClasses = ' ';
  if ($currentItemFeed.data('dzsvg-curatedtype-from-gallery') === 'link') {
    navigationItemDomTag = 'a';
    if ($currentItemFeed.attr('data-source')) {
      navigationItemExtraAttr += ' href="' + $currentItemFeed.attr('data-source') + '"';
    }
    if ($currentItemFeed.attr('data-target')) {
      navigationItemExtraAttr += ' target="' + $currentItemFeed.attr('data-target') + '"';
    }
  }
  if (isUltiboxItem) {
    navigationItemExtraClasses += ' ultibox-item-delegated';
    if ($currentItemFeed.hasClass('vplayer-tobe')) {
      navigationItemExtraAttr += ' data-type="video"';
      if ($currentItemFeed.attr('data-type')) {
        navigationItemExtraAttr += ` data-video-type="${$currentItemFeed.attr('data-type')}"`;
      }
      if ($currentItemFeed.attr('data-sourcevp')) {
        navigationItemExtraAttr += ` data-source="${$currentItemFeed.attr('data-sourcevp')}"`;
      }
    }
  }

  // -- generating final_structureMenuItemContent

  final_structureMenuItemContent += '<' + navigationItemDomTag + ' class=" dzs-navigation--item ';
  final_structureMenuItemContent += navigationItemExtraClasses;
  final_structureMenuItemContent += '"';
  final_structureMenuItemContent += navigationItemExtraAttr;
  final_structureMenuItemContent += '>';
  final_structureMenuItemContent += `<div class=" dzs-navigation--item-content">`;
  final_structureMenuItemContent += `${structureMenuItemContentInner}</div>`;
  final_structureMenuItemContent += '</' + navigationItemDomTag + '>';

  // -- function
  if (configObj.filter_structureMenuItemContent) {
    final_structureMenuItemContent = configObj.filter_structureMenuItemContent(final_structureMenuItemContent, $currentItemFeed);
  }
  return final_structureMenuItemContent;
}

},{"../../../js_common/_dzs_helpers":4,"../../../shared/_viewFunctions":22,"../_navigation-helpers":8}],12:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.buildPlaylist = buildPlaylist;
var _playlistHelpers = require("./_playlistHelpers");
var _dzsvg_helpers = require("../_dzsvg_helpers");
var _Constants = require("../../configs/Constants");
/**
 *
 * transfer feed items
 * @param {DzsVideoGallery} selfClass
 */
function buildPlaylist(selfClass) {
  let itemsLength = selfClass.$feedItemsContainer.find('.vplayer-tobe').length;
  let o = selfClass.initOptions;
  selfClass.Navigation.addNavigationItems(selfClass.$feedItemsContainer);
  for (let i = 0; i < itemsLength; i++) {
    var $currentItemFeed = selfClass.$feedItemsContainer.find('.vplayer-tobe').eq(i);
    var vpRealSrc = (0, _dzsvg_helpers.getDataOrAttr)($currentItemFeed, 'data-sourcevp');
    var sourceAndType = (0, _dzsvg_helpers.detect_video_type_and_source)(vpRealSrc);
    vpRealSrc = sourceAndType.source;
    $currentItemFeed.data('dzsvg-curatedtype-from-gallery', sourceAndType.type);
    if (sourceAndType.type === 'youtube') {
      if (sourceAndType.source) {
        $currentItemFeed.data('dzsvg-curatedid-from-gallery', sourceAndType.source);
      }
    }
    vpRealSrc = (0, _dzsvg_helpers.getDataOrAttr)($currentItemFeed, 'data-sourcevp');
    sourceAndType = (0, _dzsvg_helpers.detect_video_type_and_source)(vpRealSrc);
    vpRealSrc = sourceAndType.source;
    const curatedTypeFromGallery = sourceAndType.type;
    $currentItemFeed.data('dzsvg-curatedtype-from-gallery', curatedTypeFromGallery);
    $currentItemFeed.data('dzsvg-curatedid-from-gallery', sourceAndType.source);

    // -- this is inside video gallery
    if ((curatedTypeFromGallery === 'youtube' || curatedTypeFromGallery === 'vimeo' || curatedTypeFromGallery === 'facebook' || curatedTypeFromGallery === 'inline') && o.videoplayersettings.responsive_ratio === 'detect' && !$currentItemFeed.attr('data-responsive_ratio')) {
      if (!$currentItemFeed.attr('data-responsive_ratio') || $currentItemFeed.attr('data-responsive_ratio') === 'detect') {
        $currentItemFeed.attr('data-responsive_ratio', String(_Constants.PLAYER_DEFAULT_RESPONSIVE_RATIO));
      }
      if (curatedTypeFromGallery === 'inline') {
        setTimeout(function () {
          selfClass.apiResponsiveRationResize(_Constants.PLAYER_DEFAULT_RESPONSIVE_RATIO * selfClass.videoAreaWidth);
        }, 3003);
      }
      $currentItemFeed.attr('data-responsive_ratio-not-known-for-sure', 'on'); // -- we set this until we know the responsive ratio for sure , 0.562 is just 16/9 ratio so should fit to most videos

      if (o.php_media_data_retriever) {
        (0, _playlistHelpers.playlist_get_real_responsive_ratio)(i, selfClass);
      }
    }
    var $cacheMenuItem = selfClass.$navigationItemsContainer.children().last();
    if (o.settings_mode === 'normal') {
      if (o.mode_normal_video_mode === 'one') {
        (0, _playlistHelpers.playlist_navigation_mode_one__set_players_data)($cacheMenuItem);
      }
    }
  }
}

},{"../../configs/Constants":1,"../_dzsvg_helpers":5,"./_playlistHelpers":13}],13:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.assertVideoFromGalleryAutoplayStatus = assertVideoFromGalleryAutoplayStatus;
exports.detect_startItemBasedOnQueryAddress = detect_startItemBasedOnQueryAddress;
exports.navigation_detectClassesForPosition = navigation_detectClassesForPosition;
exports.navigation_initScroller = navigation_initScroller;
exports.playlistGotoItemHistoryChangeForLinks = playlistGotoItemHistoryChangeForLinks;
exports.playlistGotoItemHistoryChangeForNonLinks = playlistGotoItemHistoryChangeForNonLinks;
exports.playlist_get_real_responsive_ratio = playlist_get_real_responsive_ratio;
exports.playlist_inDzsTabsHandle = playlist_inDzsTabsHandle;
exports.playlist_initSetupInitial = playlist_initSetupInitial;
exports.playlist_initialConfig = playlist_initialConfig;
exports.playlist_navigation_getPreviewImg = playlist_navigation_getPreviewImg;
exports.playlist_navigation_mode_one__set_players_data = playlist_navigation_mode_one__set_players_data;
var _dzsvg_helpers = require("../_dzsvg_helpers");
var _dzs_helpers = require("../../js_common/_dzs_helpers");
var _Constants = require("../../configs/Constants");
var _modeWall = require("../../js_playlist/mode/_mode-wall");
var _playlistSettings = require("../../configs/_playlistSettings");
function playlistGotoItemHistoryChangeForNonLinks(margs, o, cid, arg, deeplinkGotoItemQueryParam = 'the-video') {
  var $ = jQuery;
  var deeplink_str = String(deeplinkGotoItemQueryParam).replace('{{galleryid}}', cid);
  if (!margs.ignore_linking && margs.called_from !== 'init') {
    var stateObj = {
      foo: "bar"
    };
    if ($('.videogallery').length === 1) {
      history.pushState(stateObj, null, (0, _dzs_helpers.add_query_arg)(window.location.href, deeplink_str, Number(arg) + 1));
    } else {
      history.pushState(stateObj, null, (0, _dzs_helpers.add_query_arg)(window.location.href, deeplink_str + '-' + cid, Number(arg) + 1));
    }
  }
}

/**
 * sanitize all options
 * @param selfClass
 * @param o
 */
function playlist_initSetupInitial(selfClass, o) {
  if (!o.autoplayFirstVideo) {
    o.autoplayFirstVideo = o.autoplay;
  }
  if (o.nav_type === 'outer') {
    if (o.forceVideoHeight === '') {
      o.forceVideoHeight = '300';
    }
  }
  if (o.settings_mode === 'slider') {
    o.menu_position = 'none';
    o.nav_type = 'none';
  }
  if (o.settings_mode === 'wall') {
    o.nav_type = 'thumbs';
  }
  if ((0, _dzs_helpers.is_mobile)() && o.autoplayNext === 'on') {
    if (o.mode_normal_video_mode !== 'one') {
      o.autoplayNext = 'off';
    }
  }
  selfClass.cgallery.data('vg_autoplayNext', o.autoplayNext);
  selfClass.cgallery.data('vg_settings', o);
  if (isNaN(parseInt(o.menuitem_width, 10)) === false && String(o.menuitem_width).indexOf('%') === -1) {
    o.menuitem_width = parseInt(o.menuitem_width, 10);
  } else {
    o.menuitem_width = '';
  }
  if (isNaN(Number(o.menuitem_height)) === false && o.menuitem_height > 0) {
    o.menuitem_height = Number(o.menuitem_height);
  } else {
    o.menuitem_height = '';
  }
  o.settings_go_to_next_after_inactivity = parseInt(o.settings_go_to_next_after_inactivity, 10);
  if (o.startItem !== 'default') {
    o.startItem = parseInt(o.startItem, 10);
  }
  o.settings_separation_pages_number = parseInt(o.settings_separation_pages_number, 10);
  o.settings_trigger_resize = parseInt(o.settings_trigger_resize, 10);
  selfClass.$feedItemsContainer = selfClass.cgallery;
  if (selfClass.cgallery.children('.items').length) {
    selfClass.$feedItemsContainer = selfClass.cgallery.children('.items');
  }
  const masonry_options_default = {
    columnWidth: 1,
    containerStyle: {
      position: 'relative'
    },
    isFitWidth: false,
    isAnimated: true
  };
  o.masonry_options = Object.assign(masonry_options_default, o.masonry_options);
  if (!(0, _dzs_helpers.can_history_api)()) {
    o.settings_enable_linking = 'off';
  }
  const $feedLayoutBuilderItems = selfClass.cgallery.children('.' + _Constants.VIEW_LAYOUT_BUILDER_FEED_CLASS);
  if ($feedLayoutBuilderItems.length) {
    selfClass.navigation_customStructure = $feedLayoutBuilderItems.eq(0).html();
  } else {
    selfClass.navigation_customStructure = _Constants.DEFAULT_MENU_ITEM_STRUCTURE;
  }
  if (!selfClass.navigation_customStructure) {
    if (!o.design_skin) {
      o.design_skin = 'skin-default';
    }
  }
  if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR3D) {
    o.menu_position = 'none';
    o.nav_type = 'none';
    o.transition_type = 'rotator3d';
  }
  if (typeof o.videoplayersettings == 'string' && window.dzsvg_vpconfigs) {
    if (typeof window.dzsvg_vpconfigs[o.videoplayersettings] === 'object') {
      o.videoplayersettings = {
        ...window.dzsvg_vpconfigs[o.videoplayersettings]
      };
    }
  }
  if (selfClass.cgallery.find('.feed-dzsvg--embedcode').length) {
    o.embedCode = selfClass.cgallery.find('.feed-dzsvg--embedcode').eq(0).html();
  }
  if (selfClass.cgallery.hasClass('view--disable-video-area')) {
    selfClass.viewOptions.enableVideoArea = false;
  }
}
function playlist_initialConfig(selfClass, o) {
  selfClass.galleryComputedId = selfClass.cgallery.attr('id');
  if (!selfClass.galleryComputedId) {
    var auxnr = 0;
    var temps = 'vgallery' + auxnr;
    while (jQuery('#' + temps).length > 0) {
      auxnr++;
      temps = 'vgallery' + auxnr;
    }
    selfClass.galleryComputedId = temps;
    selfClass.cgallery.attr('id', selfClass.galleryComputedId);
  }
  selfClass.deeplinkGotoItemQueryParam = window.dzsvg_settings && window.dzsvg_settings.deeplink_str ? String(window.dzsvg_settings.deeplink_str).replace('{{galleryid}}', selfClass.galleryComputedId) : 'the-video';
  if ((0, _dzs_helpers.is_touch_device)()) {
    if (o.nav_type === 'scroller') {
      o.nav_type = 'thumbs';
    }
  }
  selfClass.cgallery.addClass('mode-' + o.settings_mode);
  selfClass.cgallery.addClass('nav-' + o.nav_type);
  var mainClass = '';
  if (typeof selfClass.cgallery.attr('class') == 'string') {
    mainClass = selfClass.cgallery.attr('class');
  } else {
    mainClass = selfClass.cgallery.get(0).className;
  }
  if (mainClass.indexOf('skin-') === -1) {
    selfClass.cgallery.addClass(o.design_skin);
  } else {
    o.design_skin = (0, _dzs_helpers.stringUtilGetSkinFromClass)(mainClass);
  }
  if (o.nav_type === 'scroller') {
    if (o.menu_position === 'bottom' || o.menu_position === 'top') {
      if (o.menuitem_height === 'default' || o.menuitem_height === '') {
        o.menuitem_height = '100';
      }
    }
  }
  if (o.settings_mode === 'wall') {
    if (o.design_skin === 'skin-default') {
      o.design_skin = 'skin-wall';
    }
    (0, _modeWall.dzsvg_mode_wall_init)(selfClass);
  }
}
function playlist_inDzsTabsHandle(selfClass, margs) {
  // -- tabs
  var _con = selfClass.cgallery.parent().parent().parent();
  if (selfClass.initOptions.autoplayFirstVideo === 'on') {
    if (margs.called_from !== 'init_restart_in_tabs') {
      setTimeout(function () {
        margs.called_from = 'init_restart_in_tabs';
        selfClass.init(margs);
      }, 50);
      return false;
    }
    if (_con.hasClass('active') || _con.hasClass('will-be-start-item')) {} else {
      selfClass.initOptions.autoplayFirstVideo = 'off';
    }
  }
}

/**
 * return .previewImg
 * @param _t
 * @returns {null|jQuery|undefined|*}
 */
function playlist_navigation_getPreviewImg(_t) {
  let stringPreviewImg = '';
  if (_t.attr('data-previewimg')) {
    stringPreviewImg = _t.attr('data-previewimg');
  } else if (_t.attr('data-audioimg')) {
    stringPreviewImg = _t.attr('data-audioimg');
  } else if (_t.attr('data-thumb')) {
    stringPreviewImg = _t.attr('data-thumb');
  }
  return stringPreviewImg;
}
function playlist_get_real_responsive_ratio(i, selfClass) {
  var $ = jQuery;
  var o = selfClass.initOptions;
  setTimeout(function (targetIndex) {
    var _cach = selfClass._sliderCon.children().eq(targetIndex);
    var src = _cach.data('dzsvg-curatedid-from-gallery');
    $.get(o.php_media_data_retriever + "?action=dzsvg_action_get_responsive_ratio&type=" + _cach.data('dzsvg-curatedtype-from-gallery') + "&source=" + src, function (data) {
      try {
        var json = JSON.parse(data);
        var rr = _Constants.PLAYER_DEFAULT_RESPONSIVE_RATIO;
        if (json.height && json.width) {
          rr = json.height / json.width;
        }
        if (rr.toFixed(3) !== '0.563') {
          _cach.attr('data-responsive_ratio', rr.toFixed(3));
        }
        _cach.attr('data-responsive_ratio-not-known-for-sure', 'off');
        if (_cach.get(0) && _cach.get(0).api_get_responsive_ratio) {
          _cach.get(0).api_get_responsive_ratio({
            'reset_responsive_ratio': true,
            'called_from': 'php_media_data_retriever'
          });
          setTimeout(function () {
            selfClass.handleResize_currVideo();
          }, 100);
        }
      } catch (err) {
        console.info('json parse error - ', data);
      }
    });
  }, 100, i);
}

/**
 * set player data
 * @param _cachmenuitem
 */
function playlist_navigation_mode_one__set_players_data(_cachmenuitem) {
  var attr_arr = ['data-loop', 'data-sourcevp', 'data-source', 'data-videotitle', 'data-type'];
  var maxlen = attr_arr.length;
  var ci = 0;
  for (var i5 in attr_arr) {
    var lab4 = attr_arr[i5];
    var val = '';
    val = (0, _dzsvg_helpers.getDataOrAttr)(_cachmenuitem, lab4);
    if (val) {
      var lab_sanitized_for_data = lab4.replace('data-', 'vp_');
      _cachmenuitem.data(lab_sanitized_for_data, val);
    }
    if (ci > maxlen || ci > 10) {
      break;
    }
    ci++;
  }
}
function playlistGotoItemHistoryChangeForLinks(ind_ajaxPage, o, cgallery, _currentTargetPlayer) {
  var $ = jQuery;
  // --- history API ajax cool stuff
  if (o.settings_enableHistory === 'on' && (0, _dzs_helpers.can_history_api)()) {
    var stateObj = {
      foo: "bar"
    };
    history.pushState(stateObj, "Gallery Video", (0, _dzsvg_helpers.getDataOrAttr)(_currentTargetPlayer, 'data-sourcevp'));
    $.ajax({
      url: (0, _dzsvg_helpers.getDataOrAttr)(_currentTargetPlayer, 'data-sourcevp'),
      success: function (response) {
        setTimeout(function () {
          $('.history-video-element').eq(0).html($(response).find('.history-video-element').eq(0).html());

          // $('.toexecute').each(function () {
          //   var _t = $(this);
          //   if (!_t.hasClass('executed')) {
          //     eval(_t.text());
          //     _t.addClass('executed');
          //   }
          // });

          if (o.settings_ajax_extraDivs !== '') {
            var extradivs = String(o.settings_ajax_extraDivs).split(',');
            for (let i = 0; i < extradivs.length; i++) {
              $(extradivs[i]).eq(0).html(jQuery(response).find(extradivs[i]).eq(0).html());
            }
          }
        }, 100);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        console.error('not found ' + ajaxOptions);
        ind_ajaxPage++;
        cgallery.children('.preloader').removeClass('is-visible');
      }
    });
  }
}
function detect_startItemBasedOnQueryAddress(deeplinkGotoItemQueryParam = '', cid = '') {
  if ((0, _dzs_helpers.get_query_arg)(window.location.href, deeplinkGotoItemQueryParam) && jQuery('.videogallery').length === 1) {
    return Number((0, _dzs_helpers.get_query_arg)(window.location.href, deeplinkGotoItemQueryParam)) - 1;
  }
  if ((0, _dzs_helpers.get_query_arg)(window.location.href, deeplinkGotoItemQueryParam + '-' + cid)) {
    return Number((0, _dzs_helpers.get_query_arg)(window.location.href, deeplinkGotoItemQueryParam + '-' + cid)) - 1;
  }
  return null;
}
function navigation_detectClassesForPosition(menu_position, _mainNavigation, cgallery) {
  const classMenuMovement = menu_position === 'right' || menu_position === 'left' ? 'menu-moves-vertically' : 'menu-moves-horizontally';
  const classesClearMenuLocations = 'menu-top menu-bottom menu-right menu-left';
  const classesNewMenuLocation = 'menu-' + menu_position + ' ' + classMenuMovement;
  _mainNavigation.removeClass(classesClearMenuLocations);
  _mainNavigation.addClass(classesNewMenuLocation);
  cgallery.removeClass(classesClearMenuLocations);
  cgallery.addClass(classesNewMenuLocation);
}
function navigation_initScroller(_navMain) {
  var $ = jQuery;
  if ($ && $.fn && $.fn.scroller) {
    _navMain.scroller({
      'enable_easing': 'on'
    });
  }
}
function assertVideoFromGalleryAutoplayStatus(currNr, o, cgallery) {
  var shouldVideoAutoplay = false;
  if (currNr === -1) {
    if (o.autoplayFirstVideo === 'on') {
      if (cgallery.parent().parent().parent().hasClass('categories-videogallery') || !!(cgallery.parent().parent().parent().hasClass('categories-videogallery') && !cgallery.parent().parent().hasClass('gallery-precon')) || !!(cgallery.parent().parent().parent().hasClass('categories-videogallery') && cgallery.parent().parent().hasClass('gallery-precon') && cgallery.parent().parent().hasClass('curr-gallery'))) {
        shouldVideoAutoplay = false;
      } else {
        shouldVideoAutoplay = true;
      }
    }
  }
  //-- if it's not the first video
  if (currNr > -1) {
    if (o.autoplayNext === 'on') {
      shouldVideoAutoplay = true;
      // -- todo: sideeffect - maybe move
      o.videoplayersettings['cueVideo'] = 'on';
    }
  }
  return shouldVideoAutoplay;
}

},{"../../configs/Constants":1,"../../configs/_playlistSettings":3,"../../js_common/_dzs_helpers":4,"../../js_playlist/mode/_mode-wall":19,"../_dzsvg_helpers":5}],14:[function(require,module,exports){
"use strict";function handleSearchFieldChange(e,t,n){return function(){var i=jQuery,a=i(this),o=e.initOptions;"wall"===o.settings_mode&&e._sliderCon.children().each(function(){var e=i(this);""===a.val()||String(String(e.find(".menuDescription").eq(0).html()).toLowerCase()).indexOf(a.val().toLowerCase())>-1?e.show():e.hide()}),"scroller"===o.nav_type&&(void 0!==t.get(0).api_scrolly_to&&t.get(0).api_scrolly_to(0),setTimeout(function(){e.$navigationItemsContainer.css("top","0")},100)),e.$navigationItemsContainer.children().each(function(){var e=i(this);""===a.val()||String(String(e.find(".dzs-navigation--item-content").eq(0).html()).toLowerCase()).indexOf(a.val().toLowerCase())>-1?e.show():!1===e.hasClass("dzsvg-search-field")&&e.hide()}),n()}}Object.defineProperty(exports,"__esModule",{value:!0}),exports.handleSearchFieldChange=handleSearchFieldChange;
},{}],15:[function(require,module,exports){
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

},{"../js_common/_dzs_helpers":4}],16:[function(require,module,exports){
"use strict";function dzsvg_playlist_setupEmbedAndShareButtons(e,s,t){t?(""!==s.embedCode||e.feed_socialCode)&&((0,_dzsvg_helpers.dzsvg_check_multisharer)(),"wall"===s.settings_mode&&(0===e.$sliderMain.find(".gallery-buttons").length&&(e.$galleryButtons=e.cgallery.find(".gallery-buttons")),setTimeout(function(){e.$sliderMain.before(e.$galleryButtons)},500)),e.$galleryButtons.append('<div class="dzs-social-box--invoke-btn embed-button open-in-embed-ultibox"><div class="handle">'+_dzsvg_svgs.svg_embed+'</div><div hidden aria-hidden="true" class="feed-dzsvg feed-dzsvg--embedcode">'+s.embedCode+"</div></div>")):(""!==s.embedCode&&(e.$galleryButtons.append('<div class="embed-button"><div class="handle">'+_dzsvg_svgs.svg_embed+'</div><div class="contentbox" style="display:none;"><textarea class="thetext">'+s.embedCode+"</textarea></div></div>"),e.$galleryButtons.find(".embed-button .handle").on("click",click_embedHandle(e)),e.$galleryButtons.find(".embed-button .contentbox").css({right:50})),e.feed_socialCode&&(e.$galleryButtons.append('<div class="share-button"><div class="handle">'+_dzsvg_svgs.svgShareIcon+'</div><div class="contentbox" style="display:none;"><div class="thetext">'+e.feed_socialCode+"</div></div></div>"),e.$galleryButtons.find(".share-button .handle").on("click",click_sharehandle(e)),e.$galleryButtons.find(".share-button .contentbox").css({right:50})))}function click_embedHandle(e){return function(){!1===e.isEmbedOpened?(e.$galleryButtons.find(".embed-button .contentbox").css({right:60},{queue:!1,duration:_Constants.ConstantsDzsvg.ANIMATIONS_DURATION}),e.$galleryButtons.find(".embed-button .contentbox").addClass("is-visible"),e.isEmbedOpened=!0):(e.$galleryButtons.find(".embed-button .contentbox").css({right:50},{queue:!1,duration:_Constants.ConstantsDzsvg.ANIMATIONS_DURATION}),e.$galleryButtons.find(".embed-button .contentbox").removeClass("is-visible"),e.isEmbedOpened=!1)}}function click_sharehandle(e){return function(){!1===e.isShareOpened?(e.$galleryButtons.find(".share-button .contentbox").css({right:60}),e.$galleryButtons.find(".share-button .contentbox").addClass("is-visible"),e.isShareOpened=!0):(e.$galleryButtons.find(".share-button .contentbox").css({right:50}),e.$galleryButtons.find(".share-button .contentbox").removeClass("is-visible"),e.isShareOpened=!1)}}Object.defineProperty(exports,"__esModule",{value:!0}),exports.dzsvg_playlist_setupEmbedAndShareButtons=dzsvg_playlist_setupEmbedAndShareButtons;var _dzsvg_helpers=require("../js_dzsvg/_dzsvg_helpers"),_dzsvg_svgs=require("../js_dzsvg/_dzsvg_svgs"),_Constants=require("../configs/Constants");
},{"../configs/Constants":1,"../js_dzsvg/_dzsvg_helpers":5,"../js_dzsvg/_dzsvg_svgs":6}],17:[function(require,module,exports){
"use strict";function dzsvg_playlist_initSearchField(e,a){a.search_field_con?e.$searchFieldCon=jQuery(a.search_field_con):dzsvg_playlist_addSearchField(e),e.$searchFieldCon.on("keyup",(0,_searchPlaylist.handleSearchFieldChange)(e,e.$navigationItemsContainer.parent(),e.handleResize))}function dzsvg_playlist_addSearchField(e){var a="";a='<div class="dzsvg-search-field"><input type="text" placeholder="search..."/>'+_dzsvg_svgs.svgSearchIcon+"</div>",e._mainNavigation.hasClass("menu-moves-vertically")?e._mainNavigation.prepend(a):e.$navigationItemsContainer.prepend(a),e.$searchFieldCon=e.cgallery.find(".dzsvg-search-field > input")}Object.defineProperty(exports,"__esModule",{value:!0}),exports.dzsvg_playlist_addSearchField=dzsvg_playlist_addSearchField,exports.dzsvg_playlist_initSearchField=dzsvg_playlist_initSearchField;var _dzsvg_svgs=require("../js_dzsvg/_dzsvg_svgs"),_searchPlaylist=require("../js_dzsvg/playlist/_searchPlaylist");
},{"../js_dzsvg/_dzsvg_svgs":6,"../js_dzsvg/playlist/_searchPlaylist":14}],18:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.view_structureGenerateNavigation = view_structureGenerateNavigation;
var _navigation = require("../js_dzsvg/navigation/_navigation");
var _dzsvg_svgs = require("../js_dzsvg/_dzsvg_svgs");
var _playlistHelpers = require("../js_dzsvg/playlist/_playlistHelpers");
var _searchFunctions = require("./_searchFunctions");
/**
 *
 * called on init
 * @param {DzsVideoGallery} selfClass
 */
function view_structureGenerateNavigation(selfClass) {
  let structNavigationAndMainArea = '<div class="navigation-and-main-area"></div>';
  const cgallery = selfClass.cgallery;
  const o = selfClass.initOptions;
  if (o.design_shadow === 'on') {
    cgallery.prepend('<div class="shadow"></div>');
  }
  selfClass.cgallery.append(structNavigationAndMainArea);
  selfClass.$navigationAndMainArea = selfClass.cgallery.find('.navigation-and-main-area').eq(0);
  selfClass.$navigationAndMainArea.css('background-color', selfClass.cgallery.css('background-color'));
  const navOptions = {
    navigationType: o.nav_type === 'thumbs' ? 'hover' : o.nav_type === 'thumbsandarrows' ? 'thumbsAndArrows' : o.nav_type === 'outer' ? 'simple' : o.nav_type,
    menuPosition: o.menu_position,
    menuItemWidth: o.menuitem_width,
    menuItemHeight: o.menuitem_height,
    navigation_mainDimensionSpace: o.navigation_mainDimensionSpace,
    parentSkin: o.design_skin,
    viewNavigationIsUltibox: o.navigation_isUltibox,
    viewEnableMediaArea: selfClass.viewOptions.enableVideoArea,
    viewAnimationDuration: o.navigation_viewAnimationDuration,
    navigationStructureHtml: selfClass.navigation_customStructure
  };
  Object.keys(o).forEach(playlistOptionKey => {
    if (playlistOptionKey.indexOf('navigation_') === 0) {
      const newKeyForNav = playlistOptionKey.replace('navigation_', '');
      navOptions[newKeyForNav] = o[playlistOptionKey];
    }
  });
  if (o.settings_mode === 'wall') {
    navOptions.gridClassItemsContainer = o.navigation_gridClassItemsContainer;
    navOptions.navigationType = 'simple';
    navOptions.filter_structureMenuItemContent = (final_structureMenuItemContent, $currentItemFeed) => {
      if ($currentItemFeed.attr('data-type')) {
        final_structureMenuItemContent = final_structureMenuItemContent.replace('dzs-navigation--item"', 'dzs-navigation--item" ' + ' data-video-type="' + $currentItemFeed.attr('data-type') + '"');
      }
      return final_structureMenuItemContent;
    };
  }
  selfClass.Navigation = new _navigation.DzsNavigation(selfClass, navOptions, jQuery);
  selfClass.$sliderMain = cgallery.find('.sliderMain');
  selfClass._sliderCon = cgallery.find('.sliderCon');
  selfClass._mainNavigation = cgallery.find('.main-navigation');
  selfClass._sliderCon.addClass(o.extra_class_slider_con);
  if (o.settings_mode === 'slider') {
    selfClass.$sliderMain.after(selfClass._mainNavigation);
  }
  selfClass.$sliderMain.append('<div class="gallery-buttons"></div>');
  selfClass.$navigationClippedContainer = selfClass.cgallery.find('.navMain');
  selfClass.$navigationItemsContainer = selfClass.cgallery.find('.videogallery--navigation-container').eq(0);
  if (o.settings_mode === 'slider') {
    selfClass.$navigationClippedContainer.append('<div class="rotator-btn-gotoNext">' + _dzsvg_svgs.svgForwardButton + '</div><div class="rotator-btn-gotoPrev">' + _dzsvg_svgs.svgBackButton + '</div>');
  }
  if (o.settings_mode === 'rotator') {
    selfClass.$navigationClippedContainer.append('<div class="rotator-btn-gotoNext"></div><div class="rotator-btn-gotoPrev"></div>');
    selfClass.$navigationClippedContainer.append('<div class="descriptionsCon"></div>');
  }
  selfClass.$galleryButtons = selfClass.$sliderMain.children('.gallery-buttons');
  (0, _playlistHelpers.navigation_detectClassesForPosition)(o.menu_position, selfClass._mainNavigation, cgallery);
  if (o.search_field === 'on') {
    (0, _searchFunctions.dzsvg_playlist_initSearchField)(selfClass, o);
  }
}

},{"../js_dzsvg/_dzsvg_svgs":6,"../js_dzsvg/navigation/_navigation":9,"../js_dzsvg/playlist/_playlistHelpers":13,"./_searchFunctions":17}],19:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.dzsvg_mode_wall_init = dzsvg_mode_wall_init;
exports.dzsvg_mode_wall_reinitWallStructure = dzsvg_mode_wall_reinitWallStructure;
var _dzsvg_helpers = require("../../js_dzsvg/_dzsvg_helpers");
var _Constants = require("../../configs/Constants");
function dzsvg_mode_wall_init(selfClass) {
  const o = selfClass.initOptions;
  o.menu_position = 'all';
  if (o.navigation_gridClassItemsContainer === 'default') {
    o.navigation_gridClassItemsContainer = 'dzs-layout--3-cols';
  }
}
function dzsvg_mode_wall_reinitWallStructure(selfClass) {
  // -- wall

  const o = selfClass.initOptions;
  selfClass.$navigationItemsContainer.children().each(function () {
    // -- each item
    const $t = jQuery(this);
    if (!$t.hasClass('wall-treated')) {
      $t.addClass(_Constants.PLAYLIST_MODE_WALL__ITEM_CLASS).addClass('  dzs-layout-item ultibox-item-delegated');
      $t.css({});
      $t.attr('data-suggested-width', o.ultibox_suggestedWidth);
      $t.attr('data-suggested-height', o.ultibox_suggestedHeight);
      $t.attr('data-biggallery', 'vgwall');
      if ($t.attr('data-previewimg')) {
        $t.attr('data-thumb-for-gallery', $t.attr('data-previewimg'));
      } else {
        if ($t.data('thumbForGallery')) {
          $t.attr('data-thumb-for-gallery', $t.data('thumbForGallery'));
        }
      }
      let uriThumb = $t.attr('data-thumb');
      let thumb_imgblock = null;
      if ($t.find('.layout-builder--item--type-thumbnail').length) {
        thumb_imgblock = $t.find('.layout-builder--item--type-thumbnail');
      }
      if (!uriThumb) {
        if (thumb_imgblock) {
          if (thumb_imgblock.attr('data-imgsrc')) {} else {
            if (thumb_imgblock.attr('src')) {
              uriThumb = $t.find('.imgblock').attr('src');
            } else {
              uriThumb = thumb_imgblock.css('background-image');
            }
          }
        }
      }
      if (uriThumb) {
        uriThumb = uriThumb.replace('url(', '');
        uriThumb = uriThumb.replace(')', '');
        uriThumb = uriThumb.replace(/"/g, '');
        $t.attr('data-thumb-for-gallery', uriThumb);
      }
      // -- setup wall
      if (!$t.attr('data-source')) {
        $t.attr('data-source', (0, _dzsvg_helpers.getDataOrAttr)($t, 'data-sourcevp'));
      }
      $t.attr('data-type', 'video');
      if ($t.data('dataType')) {
        $t.attr('data-video-type', $t.data('dataType'));
      }
      $t.addClass('wall-treated');
    }
  });
  setTimeout(function () {
    setTimeout(selfClass.handleResize, 1000);
    selfClass.isGalleryLoaded = true;
  }, 1500);
}

},{"../../configs/Constants":1,"../../js_dzsvg/_dzsvg_helpers":5}],20:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.playlist_calculateDims = playlist_calculateDims;
exports.playlist_calculateDims_totals = playlist_calculateDims_totals;
/**
 *
 * @param {DzsVideoGallery} selfClass
 * @param {object} margs
 */
function playlist_calculateDims_totals(selfClass, margs) {
  const o = selfClass.initOptions;
  var {
    cgallery
  } = selfClass;
  selfClass.totalWidth = selfClass.cgallery.outerWidth();
  selfClass.totalHeight = selfClass.cgallery.height();
  if (selfClass.cgallery.height() === 0) {
    if (o.forceVideoHeight) {
      if (selfClass.nav_position === 'top' || selfClass.nav_position === 'bottom') {
        selfClass.totalHeight = o.forceVideoHeight + o.design_menuitem_height;
      } else {
        selfClass.totalHeight = o.forceVideoHeight;
      }
    }
  }
  if (margs.called_from === 'recheck_sizes') {
    if (Math.abs(selfClass.last_totalWidth - selfClass.totalWidth) < 4 && Math.abs(selfClass.last_totalHeight - selfClass.totalHeight) < 4) {
      return false;
    }
  }
  selfClass.last_totalWidth = selfClass.totalWidth;
  selfClass.last_totalHeight = selfClass.totalHeight;
  if (selfClass.totalWidth <= 720) {
    cgallery.addClass('under-720');
  } else {
    cgallery.removeClass('under-720');
  }
  if (selfClass.totalWidth <= 600) {
    cgallery.addClass('under-600');
  } else {
    cgallery.removeClass('under-600');
  }
  if (String(cgallery.get(0).style.height).indexOf('%') > -1) {
    selfClass.totalHeight = cgallery.height();
  } else {
    if (cgallery.data('init-height')) {
      selfClass.totalHeight = cgallery.data('init-height');
    } else {
      selfClass.totalHeight = cgallery.height();
      setTimeout(function () {});
    }
  }
}
function playlist_calculateDims(pargs) {}

},{}],21:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.playlist_pagination_scrollSetup = playlist_pagination_scrollSetup;
var _Constants = require("../../configs/Constants");
// import {add_query_arg} from "../../js_common/_dzs_helpers";
// import {PLAYLIST_PAGINATION_QUERY_ARG} from "../../configs/Constants";

/**
 *
 * @param {DzsVideoGallery} selfClass
 */
function playlist_pagination_scrollSetup(selfClass) {
  var $ = jQuery;
  var cgallery = selfClass.cgallery;
  var o = selfClass.initOptions;
  if (o.settings_separation_mode === 'button') {
    selfClass.cgallery.append(`<div class="${_Constants.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON} btn_ajax_loadmore">Load More</div>`);
    selfClass.cgallery.on('click', `.${_Constants.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`, handleClickBtnAjaxLoadMore);
    if (o.settings_separation_pages.length === 0) {
      selfClass.cgallery.find(`.${_Constants.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`).hide();
    }
  }
  if (o.settings_separation_mode === 'scroll') {
    $(window).on('scroll', handleScroll);
  }

  // -- functions hoisting

  function handleClickBtnAjaxLoadMore(e) {
    if (selfClass.isBusyAjax === true || selfClass.ind_ajaxPage >= o.settings_separation_pages.length) {
      return;
    }
    selfClass.cgallery.find(`.${_Constants.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`).addClass('disabled');
    ajax_load_nextpage();
  }
  function ajax_load_nextpage() {
    selfClass.cgallery.parent().children('.preloader').addClass('is-visible');
    $.ajax({
      url: o.settings_separation_pages[selfClass.ind_ajaxPage],
      success: function (response) {
        setTimeout(function () {
          selfClass.$feedItemsContainer.append(response);
          selfClass.reinit({
            'called_from': 'ajax_load_nextpage'
          });
          cgallery.find(`.${_Constants.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`).removeClass('disabled');
          if (selfClass.ind_ajaxPage >= o.settings_separation_pages.length) {
            selfClass.cgallery.find(`.${_Constants.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`).addClass(_Constants.PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON + '--is-hidden');
          }
          setTimeout(function () {
            selfClass.isBusyAjax = false;
            selfClass.cgallery.parent().children('.preloader').removeClass('is-visible');
            selfClass.ind_ajaxPage++;
          }, 10);
        }, 10);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        selfClass.ind_ajaxPage++;
        selfClass.cgallery.parent().children('.preloader').removeClass('is-visible');
      }
    });
    selfClass.isBusyAjax = true;
  }
  function handleScroll() {
    var _t = $(this); //==window
    let wh = $(window).height();
    if (selfClass.isBusyAjax === true || selfClass.ind_ajaxPage >= selfClass.initOptions.settings_separation_pages.length) {
      return;
    }
    if (_t.scrollTop() + wh > cgallery.offset().top + cgallery.height() - 10) {
      ajax_load_nextpage();
    }
  }
}

// -- this was on init

// if (o.settings_separation_mode === 'pages') {
//
//   let dzsvg_page = get_query_arg(window.location.href, PLAYLIST_PAGINATION_QUERY_ARG);
//
//
//   if (typeof dzsvg_page == "undefined") {
//     dzsvg_page = 1;
//   }
//   dzsvg_page = parseInt(dzsvg_page, 10);
//
//
//   if (dzsvg_page === 0 || isNaN(dzsvg_page)) {
//     dzsvg_page = 1;
//   }
//
//   if (dzsvg_page > 0 && o.settings_separation_pages_number < nrChildren) {
//
//     // if (o.settings_separation_pages_number * dzsvg_page <= nrChildren) {
//     //   for (elimi = o.settings_separation_pages_number * dzsvg_page - 1; elimi >= o.settings_separation_pages_number * (dzsvg_page - 1); elimi--) {
//     //     cgallery.children().eq(elimi).addClass('from-pagination-do-not-eliminate');
//     //   }
//     // } else {
//     //   for (elimi = nrChildren - 1; elimi >= nrChildren - o.settings_separation_pages_number; elimi--) {
//     //     cgallery.children().eq(elimi).addClass('from-pagination-do-not-eliminate');
//     //   }
//     // }
//     //
//     // cgallery.children().each(function () {
//     //   const $videoItem = $(this);
//     //   if (!$videoItem.hasClass('from-pagination-do-not-eliminate')) {
//     //     $videoItem.remove();
//     //   }
//     // })
//
//
//     // const str_pagination = view_playlist_buildPagination(selfClass, dzsvg_page);
//     // cgallery.after(str_pagination);
//
//   }
// }
// /**
//  *
//  * @param {DzsVideoGallery} selfClass
//  * @param dzsvg_page
//  * @returns {string}
//  */
// export function view_playlist_buildPagination(selfClass, dzsvg_page) {
//   var settings_separation_nr_pages = 0;
//
//   var nrChildren = selfClass.cgallery.children().length;
//   let str_pagination = '<div class="con-dzsvg-pagination">';
//   settings_separation_nr_pages = Math.ceil(nrChildren / selfClass.initOptions.settings_separation_pages_number);
//
//
//   for (let i = 0; i < settings_separation_nr_pages; i++) {
//     let str_active = '';
//     if ((i + 1) === dzsvg_page) {
//       str_active = ' active';
//     }
//     str_pagination += '<a class="pagination-number ' + str_active + '" href="' + add_query_arg(window.location.href, PLAYLIST_PAGINATION_QUERY_ARG, (i + 1)) + '">' + (i + 1) + '</a>'
//   }
//
//   str_pagination += '</div>';
//
//   return str_pagination;
// }

},{"../../configs/Constants":1}],22:[function(require,module,exports){
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

},{"../configs/Constants":1,"../configs/_playerSettings":2,"../js_common/_dzs_helpers":4}],23:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.apply_videogallery_plugin = apply_videogallery_plugin;
var _playlistSettings = require("./configs/_playlistSettings");
var _playlistHelpers = require("./js_dzsvg/playlist/_playlistHelpers");
var _dzsvg_helpers = require("./js_dzsvg/_dzsvg_helpers");
var _dzs_helpers = require("./js_common/_dzs_helpers");
var _second_con = require("./js_dzsvg/components/_second_con");
var _playlistBuilderFunctions = require("./js_dzsvg/playlist/_playlistBuilderFunctions");
var _modeWall = require("./js_playlist/mode/_mode-wall");
var _playlistAuxiliaryButtons = require("./js_playlist/_playlistAuxiliaryButtons");
var _Constants = require("./configs/Constants");
var _viewFunctions = require("./shared/_viewFunctions");
var _viewPlaylistStructure = require("./js_playlist/_viewPlaylistStructure");
var _calculateDims = require("./js_playlist/view/_calculateDims");
var _pagination = require("./js_playlist/view/_pagination");
/**
 * @property {jQuery} _sliderCon
 * @property $feedItemsContainer
 * @property {boolean} isEmbedOpened
 * @property navigation_customStructure
 * @property {jQuery} $searchFieldCon
 * @property {jQuery} _mainNavigation
 * @property {boolean} isEmbedOpened
 */
class DzsVideoGallery {
  constructor(argThis, argOptions, $) {
    this.argThis = argThis;
    this.argOptions = {
      ...argOptions
    };
    this.viewOptions = {
      enableVideoArea: true
    };
    this.$ = $;
    this._sliderCon = null;
    this.$sliderMain = null;
    this.$navigationAndMainArea = null;
    this._mainNavigation = null;
    this.$navigationClippedContainer = null;
    this.$feedItemsContainer = null;
    this.navigation_customStructure = '';
    this.galleryComputedId = '';
    this.deeplinkGotoItemQueryParam = '';
    this.$galleryButtons = null;
    this.$navigationItemsContainer = null;
    /** videogallery--navigation-container */
    this.$searchFieldCon = null;
    this.videoAreaWidth = 0;
    this.ind_ajaxPage = 0;
    this.feed_socialCode = '';
    this.Navigation = null;
    this.nav_position = 'right';
    this.videoAreaHeight = null;
    this.totalWidth = null;
    this.totalHeight = null;
    this.isBusyAjax = false;
    this.isEmbedOpened = false;
    this.isShareOpened = false;
    this.isGalleryLoaded = false; // -- gallery loaded sw, when dimensions are set, will take a while if wall
    this.classInit();
  }
  classInit() {
    var cgallery = null;
    var nrChildren = 0;
    //gallery dimensions
    var navWidth = 0 // the _navCon width
      ,
      ww,
      heightWindow,
      last_height_for_videoheight = 0 // -- last responsive_ratio height known
    ;

    var isMenuMovementLocked = false;
    var inter_start_the_transition = null;
    let isMergeSocialIconsIntoOne = false; // -- merge all socials into one

    var currNr = -1,
      currNr_curr = -1 // current transitioning
      ,
      nextNr = -1,
      prevNr = -1,
      last_arg = 0;
    var $currVideoPlayer;
    var $galleryParent,
      $galleryCon,
      heightInitial = -1;
    var conw = 0;
    var isBusyTransition = false,
      isTransitionStarted = false;
    var isFirstPlayed = false,
      isMouseOver = false,
      isFirstTransition = false // -- first transition made
    ;

    var i = 0;
    var menuitem_width = 0,
      menuitem_height = 0;
    var init_settings = {};
    var action_playlist_end = null;
    var $ = this.$;
    var o = this.argOptions;
    cgallery = $(this.argThis);
    var selfClass = this;
    selfClass.init = init;
    selfClass.cgallery = cgallery;
    selfClass.initOptions = o;
    init_settings = $.extend({}, o);
    (0, _playlistHelpers.playlist_initSetupInitial)(selfClass, o);
    selfClass.nav_position = o.menu_position;
    nrChildren = selfClass.$feedItemsContainer.children('.vplayer,.vplayer-tobe').length;
    if (o.init_on === 'init') {
      init({
        'called_from': 'init'
      });
    }
    if (o.init_on === 'scroll') {
      $(window).on('scroll', handleScroll);
      handleScroll();
    }
    function init(pargs) {
      var margs = {
        caller: null,
        'called_from': 'default'
      };
      if (selfClass.cgallery.hasClass('dzsvg-inited')) {
        return false;
      }
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      selfClass.handleResize = handleResize;
      selfClass.reinit = reinit;
      selfClass.handleResize_currVideo = handleResize_currVideo;
      selfClass.apiResponsiveRationResize = apiResponsiveRationResize;
      if (cgallery.parent().parent().parent().hasClass('tab-content')) {
        // -- tabs
        (0, _playlistHelpers.playlist_inDzsTabsHandle)(selfClass, margs);
      }
      selfClass.cgallery.addClass('dzsvg-inited');
      $galleryCon = cgallery.parent();
      $galleryParent = cgallery.parent();
      if ($galleryParent.parent().hasClass('gallery-is-fullscreen')) {
        if (o.videoplayersettings.responsive_ratio === 'detect') {
          o.videoplayersettings.responsive_ratio = 'default';
        }
      }

      // -- separation - PAGES
      let elimi = 0;
      if ((0, _dzs_helpers.is_touch_device)()) {
        cgallery.addClass('is-touch');
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.WALL || o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.VIDEOWALL) {
        o.design_shadow = 'off';
      }
      selfClass.totalWidth = cgallery.width();
      selfClass.totalHeight = cgallery.height();
      if (isNaN(selfClass.totalWidth)) {
        selfClass.totalWidth = 800;
      }
      if (isNaN(selfClass.totalHeight)) {
        selfClass.totalHeight = 400;
      }
      (0, _playlistHelpers.playlist_initialConfig)(selfClass, o);
      $('html').addClass('supports-translate');
      (0, _viewPlaylistStructure.view_structureGenerateNavigation)(selfClass);
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL || o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.SLIDER) {
        reinit();
      }

      // -- wall END

      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.VIDEOWALL) {
        if (cgallery.parent().hasClass('videogallery-con')) {
          (0, _viewFunctions.view_setCssPropsForElement)(cgallery.parent(), {
            'width': 'auto',
            'height': 'auto'
          });
        }
        (0, _viewFunctions.view_setCssPropsForElement)(cgallery, {
          'width': 'auto',
          'height': 'auto'
        });
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.WALL || o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.VIDEOWALL || o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR || o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR3D) {
        reinit({
          'called_from': 'init'
        });
      }
      if (window.dzsvg_settings && window.dzsvg_settings.merge_social_into_one === 'on') {
        isMergeSocialIconsIntoOne = true;
      }
      (0, _playlistAuxiliaryButtons.dzsvg_playlist_setupEmbedAndShareButtons)(selfClass, o, isMergeSocialIconsIntoOne);
      if (o.nav_type === 'outer') {
        selfClass.$navigationItemsContainer.addClass(o.nav_type_outer_grid);
        selfClass.$navigationItemsContainer.children().addClass('dzs-layout-item');
        if (o.menuitem_width) {
          o.menuitem_width = '';
        }
        if (o.nav_type_outer_max_height) {
          const nto_mh = Number(o.nav_type_outer_max_height);
          (0, _viewFunctions.view_setCssPropsForElement)(selfClass.$navigationClippedContainer, {
            'max-height': (0, _viewFunctions.view_cssConvertForPx)(nto_mh)
          });
          selfClass.$navigationClippedContainer.addClass('scroller-con skin_apple inner-relative');
          selfClass.$navigationItemsContainer.addClass('inner');
          (0, _viewFunctions.view_setCssPropsForElement)(selfClass.$navigationClippedContainer, {
            'height': 'auto'
          });
          try_to_init_scroller();
        }
      }
      calculateDims({
        'called_from': 'init'
      });
      if (o.nav_type === 'scroller') {
        selfClass.$navigationClippedContainer.addClass('scroller-con skin_apple');
        selfClass.$navigationItemsContainer.addClass('inner');
        (0, _viewFunctions.view_setCssPropsForElement)(selfClass.$navigationClippedContainer, {
          'height': '100%'
        });

        // -- try scroller
        if ($.fn.scroller) {
          (0, _playlistHelpers.navigation_initScroller)(selfClass.$navigationClippedContainer);
        } else {
          setTimeout(() => {
            (0, _playlistHelpers.navigation_initScroller)(selfClass.$navigationClippedContainer);
          }, 2000);
        }
        setTimeout(function () {
          if ($('html').eq(0).attr('dir') === 'rtl') {
            selfClass.$navigationClippedContainer.get(0).fn_scrollx_to(1);
          }
        }, 100);
      }
      // -- scroller END

      // -- NO FUNCTION HIER

      cgallery.on('click', '.rotator-btn-gotoNext,.rotator-btn-gotoPrev', handle_mouse);
      $(document).on('keyup.dzsvgg', handleKeyPress);
      window.addEventListener("orientationchange", view_handleOrientationChange);
      $(window).on('resize', handleResize);
      handleResize();
      setTimeout(function () {
        calculateDims({
          'called_from': 'first_timeout'
        });
      }, 3000);
      setTimeout(init_playlistIsReady, 100);
      if (o.settings_trigger_resize > 0) {
        setInterval(function () {
          calculateDims({
            'called_from': 'recheck_sizes'
          });
        }, o.settings_trigger_resize);
      }
      setup_apiFunctions();
      if (o.startItem === 'default') {
        o.startItem = 0;
        if (o.playorder === 'reverse') {
          o.startItem = nrChildren - 1;
        }
      }

      // --- gotoItem
      if (o.settings_mode !== _playlistSettings.VIDEO_GALLERY_MODES.WALL && o.settings_mode !== _playlistSettings.VIDEO_GALLERY_MODES.VIDEOWALL) {
        selfClass.isGalleryLoaded = true;
        if ((0, _dzs_helpers.get_query_arg)(window.location.href, 'dzsvg_startitem_' + selfClass.galleryComputedId)) {
          o.startItem = Number((0, _dzs_helpers.get_query_arg)(window.location.href, 'dzsvg_startitem_' + selfClass.galleryComputedId));
        }
        var tempStartItem = (0, _playlistHelpers.detect_startItemBasedOnQueryAddress)(selfClass.deeplinkGotoItemQueryParam, selfClass.galleryComputedId);
        if (tempStartItem !== null) {
          o.startItem = tempStartItem;
          if (cgallery.parent().parent().parent().hasClass('categories-videogallery')) {
            const $categoriesVideoGallery = cgallery.parent().parent().parent();
            const ind = $categoriesVideoGallery.find('.videogallery').index(cgallery);
            if (ind) {
              setTimeout(function () {
                $categoriesVideoGallery.get(0).api_goto_category(ind, {
                  'called_from': 'deeplink'
                });
              }, 100);
            }
          }
        }
        if (isNaN(o.startItem)) {
          o.startItem = 0;
        }
        if (o.nav_type === 'scroller') {
          // todo: import from _navigation.js
        }
        if (o.settings_go_to_next_after_inactivity) {
          setInterval(function () {
            if (!isFirstPlayed) {
              gotoNext();
            }
          }, o.settings_go_to_next_after_inactivity * 1000);
        }
      }
      cgallery.on('mouseleave', handleMouseout);
      cgallery.on('mouseover', handleMouseover);
      (0, _pagination.playlist_pagination_scrollSetup)(selfClass);
      function call_init_readyForInitingVideos() {
        init_readyForInitingVideos();
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.WALL) {
        call_init_readyForInitingVideos();
      } else {
        (0, _dzs_helpers.loadScriptIfItDoesNotExist)('', 'dzsvp_isLoaded').then(() => {
          call_init_readyForInitingVideos();
        });
      }
    }
    function init_readyForInitingVideos() {
      // -- first item

      if (selfClass._sliderCon.children().eq(o.startItem).attr('data-type') === 'link') {
        // -- only for link
        gotoItem(o.startItem, {
          donotopenlink: "on",
          'called_from': 'init'
        });
      } else {
        // -- first item
        // -- normal
        if (o.settings_mode !== _playlistSettings.VIDEO_GALLERY_MODES.WALL) {
          gotoItem(o.startItem, {
            'called_from': 'init'
          });
        }
      }
    }
    function setup_apiFunctions() {
      cgallery.get(0).SelfPlaylist = selfClass;
      // --- go to video 0 <<<< the start of the gallery
      cgallery.get(0).videoEnd = handleVideoEnd;
      cgallery.get(0).init_settings = init_settings;
      cgallery.get(0).api_play_currVideo = play_currVideo;
      cgallery.get(0).external_handle_stopCurrVideo = video_stopCurrentVideo;
      cgallery.get(0).api_gotoNext = gotoNext;
      cgallery.get(0).api_gotoPrev = gotoPrev;
      cgallery.get(0).api_gotoItem = gotoItem;
      cgallery.get(0).api_responsive_ratio_resize_h = apiResponsiveRationResize;

      // -- ad functions
      cgallery.get(0).api_ad_block_navigation = ad_block_navigation;
      cgallery.get(0).api_ad_unblock_navigation = ad_unblock_navigation;
      cgallery.get(0).api_handleResize = handleResize;
      cgallery.get(0).api_gotoItem = gotoItem;
      cgallery.get(0).api_handleResize_currVideo = handleResize_currVideo;
      cgallery.get(0).api_play_currVideo = play_currVideo;
      cgallery.get(0).api_pause_currVideo = pause_currVideo;
      cgallery.get(0).api_currVideo_refresh_fsbutton = api_currVideo_refresh_fsbutton;
      cgallery.get(0).api_video_ready = galleryTransition;
      cgallery.get(0).api_set_outerNav = function (arg) {
        o.settings_outerNav = arg;
      };
      cgallery.get(0).api_set_secondCon = function (arg) {
        o.settings_secondCon = arg;
      };
      cgallery.get(0).api_set_action_playlist_end = function (arg) {
        action_playlist_end = arg;
      };
      cgallery.get(0).api_played_video = function () {
        isFirstPlayed = true;
      };
    }
    function handleMouseover(e) {
      isMouseOver = true;
    }

    /**
     * handle actions for mouse over
     * @param e
     */
    function handleMouseout(e) {
      isMouseOver = false;
      if (o.nav_type_auto_scroll === 'on') {
        if (o.nav_type === 'thumbs' || o.nav_type === 'scroller') {
          setTimeout(function () {
            if (!isMouseOver) {
              // todo: import from navigation.js
            } else {
              handleMouseout();
            }
          }, 2000);
        }
      }
    }
    function handleKeyPress(e) {
      if (e.type === 'keyup') {
        if (e.keyCode === 27) {
          $('.' + _Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS).removeClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
          setTimeout(function () {
            $('.' + _Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS).removeClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
          }, 999);
          cgallery.find('.' + _Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS).removeClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
          setTimeout(function () {
            calculateDims();
          }, 100);
        }
      }
    }
    function try_to_init_scroller() {
      const baseUrl = window.dzsvg_settings && window.dzsvg_settings.libsUri ? window.dzsvg_settings.libsUri : '';
      (0, _dzs_helpers.loadScriptIfItDoesNotExist)(baseUrl + 'dzsscroller/scroller.js', 'dzsscr_init').then(r => {
        window.dzsscr_init(selfClass.$navigationClippedContainer, {
          'enable_easing': 'on',
          'settings_skin': 'skin_apple'
        });
      });
      $('head').append('<link rel="stylesheet" type="text/css" href="' + baseUrl + 'dzsscroller/scroller.css">');
    }
    function ad_block_navigation() {
      cgallery.addClass('ad-blocked-navigation');
    }
    function ad_unblock_navigation() {
      cgallery.removeClass('ad-blocked-navigation');
    }
    function init_playlistIsReady() {
      init_showPlaylist();
      if (o.settings_secondCon) {
        // -- moving this to bottom
      }
      if (o.settings_outerNav) {

        // -- we moved setup to bottom
      }
      handleResize();
      selfClass.cgallery.addClass('inited');
    }
    function handle_mouse(e) {
      var _t = $(this);
      if (_t.hasClass('rotator-btn-gotoNext')) {
        gotoNext();
      }
      if (_t.hasClass('rotator-btn-gotoPrev')) {
        gotoPrev();
      }
    }
    function init_showPlaylist() {
      cgallery.parent().children('.preloader').removeClass('is-visible');
      cgallery.parent().children('.css-preloader').removeClass('is-visible');
      setTimeout(() => {
        (0, _viewFunctions.view_setCssPropsForElement)(selfClass.cgallery, {
          'min-height': '100px'
        });
      }, 100);
      if (o.init_on === 'scroll' && cgallery.hasClass('transition-slidein')) {
        setTimeout(function () {
          cgallery.addClass('dzsvg-loaded');
          if (cgallery.parent().hasClass('videogallery-con')) {
            cgallery.parent().addClass('dzsvg-loaded');
          }
        }, _Constants.PLAYLIST_DEFAULT_TIMEOUT);
      } else {
        cgallery.addClass('dzsvg-loaded');
        if (cgallery.parent().hasClass('videogallery-con')) {
          cgallery.parent().addClass('dzsvg-loaded');
        }
      }
    }
    function setup_navigation_items() {
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL || o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.WALL) {
        (0, _playlistBuilderFunctions.buildPlaylist)(selfClass);
      }
    }

    /**
     * transfer from feed con to slider con
     */
    function setup_transfer_items_to_sliderCon() {
      if (o.settings_mode !== _playlistSettings.VIDEO_GALLERY_MODES.WALL) {
        let len = selfClass.$feedItemsContainer.find('.vplayer-tobe').length;
        for (i = 0; i < len; i++) {
          let _t = selfClass.$feedItemsContainer.children('.vplayer-tobe').eq(0);
          selfClass._sliderCon.append(_t);
        }
      }
    }
    function reinit() {
      setup_navigation_items();
      setup_transfer_items_to_sliderCon();
      if (o.settings_mode === 'videowall') {
        selfClass._sliderCon.children().each(function () {
          // --each item
          var _t = $(this);
          _t.wrap('<div class="dzs-layout-item"></div>');
          o.videoplayersettings.responsive_ratio = 'detect';
          o.videoplayersettings.autoplay = 'off';
          o.videoplayersettings.preload_method = 'metadata';
          o.init_all_players_at_init = 'on';
        });
      }
      if (o.settings_mode === 'rotator3d') {
        selfClass.nav_position = 'none';
        selfClass._sliderCon.children().each(function () {
          const _t = $(this);
          _t.addClass('rotator3d-item');
          (0, _viewFunctions.view_setCssPropsForElement)(_t, {
            'width': selfClass.videoAreaWidth,
            'height': selfClass.videoAreaHeight
          });
          _t.append('<div class="previewImg" style="background-image:url(' + (0, _playlistHelpers.playlist_navigation_getPreviewImg)(_t) + ');"></div>');
          _t.children('.previewImg').on('click', rotator3d_handleClickOnPreviewImg);
        });
      }
      if (o.init_all_players_at_init === 'on') {
        // -- init all players
        selfClass._sliderCon.find('.vplayer-tobe').each(function () {
          // -- each item
          const _t = $(this);
          o.videoplayersettings.autoplay = 'off';
          o.videoplayersettings.preload_method = 'metadata';
          o.videoplayersettings.gallery_object = cgallery;
          _t.vPlayer(o.videoplayersettings);
        });
      }
      nrChildren = selfClass._sliderCon.children().length;
      if (selfClass.cgallery.find('.feed-dzsvg--socialCode').length) {
        selfClass.feed_socialCode = selfClass.cgallery.find('.feed-dzsvg--socialCode').html();
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.WALL) {
        (0, _modeWall.dzsvg_mode_wall_reinitWallStructure)(selfClass);
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL) {
        selfClass.$navigationItemsContainer.children('.dzs-navigation--item').off('click', handleClickOnNavigationContainer);
        selfClass.$navigationItemsContainer.children('.dzs-navigation--item').on('click', handleClickOnNavigationContainer);
      }
    }

    /**
     * called from outside
     * @param resizeHeightDimension
     * @param pargs
     * @returns {boolean}
     */
    function apiResponsiveRationResize(resizeHeightDimension, pargs) {
      var margs = Object.assign({
        caller: null,
        'called_from': 'default'
      }, pargs ?? {});
      if (margs.caller == null || cgallery.parent().hasClass('skin-laptop')) {
        return false;
      }
      if (heightInitial === -1) {
        heightInitial = selfClass.$sliderMain.height();
      }
      $currVideoPlayer.height(resizeHeightDimension);
      (0, _viewFunctions.view_setCssPropsForElement)(selfClass.$sliderMain, {
        'height': resizeHeightDimension,
        'min-height': resizeHeightDimension
      });
      if (!cgallery.hasClass('ultra-responsive') && (selfClass.nav_position === 'left' || selfClass.nav_position === 'right' || selfClass.nav_position === 'none')) {
        selfClass.totalHeight = resizeHeightDimension;
        selfClass.videoAreaHeight = resizeHeightDimension;
        if (o.settings_mode !== _playlistSettings.VIDEO_GALLERY_MODES.SLIDER) {
          selfClass._mainNavigation.height(resizeHeightDimension);
        }
        selfClass.videoAreaHeight = resizeHeightDimension;
        setTimeout(() => {
          selfClass.Navigation.calculateDims({
            forceMainAreaHeight: resizeHeightDimension
          });
        });
      } else {
        // -- responsive ratio

        (0, _viewFunctions.view_setCssPropsForElement)(selfClass.cgallery, {
          'height': 'auto'
        });
        selfClass.videoAreaHeight = resizeHeightDimension;
      }
      if (margs.caller) {
        margs.caller.data('height_for_videoheight', resizeHeightDimension);
        calculateDims({
          called_from: 'height_for_videoheight'
        });
      }
      if (o.nav_type === 'scroller') {
        setTimeout(function () {
          if (selfClass.$navigationClippedContainer.get(0) && selfClass.$navigationClippedContainer.get(0).api_toggle_resize) {
            selfClass.$navigationClippedContainer.get(0).api_toggle_resize();
          }
        }, 100);
      }
    }

    /**
     * calculate dimensions
     * @param pargs
     * @returns {boolean}
     */
    function calculateDims(pargs) {
      const margs = $.extend({
        'called_from': 'default'
      }, pargs ?? {});
      (0, _calculateDims.playlist_calculateDims_totals)(selfClass, margs);
      selfClass.videoAreaWidth = selfClass.totalWidth;
      selfClass.videoAreaHeight = selfClass.totalHeight;
      menuitem_width = o.menuitem_width;
      menuitem_height = o.menuitem_height;
      if ((selfClass.nav_position === 'right' || selfClass.nav_position === 'left') && nrChildren > 1) {
        selfClass.videoAreaWidth -= menuitem_width;
      }
      if (o.nav_type !== 'outer' && (selfClass.nav_position === 'bottom' || selfClass.nav_position === 'top') && nrChildren > 1 && cgallery.get(0).style && cgallery.get(0).style.height && cgallery.get(0).style.height !== 'auto') {
        selfClass.videoAreaHeight -= menuitem_height;
      } else {
        selfClass.videoAreaHeight = o.sliderAreaHeight;
      }
      if ($currVideoPlayer && $currVideoPlayer.data('height_for_videoheight')) {
        selfClass.videoAreaHeight = $currVideoPlayer.data('height_for_videoheight');
        last_height_for_videoheight = selfClass.videoAreaHeight;
      } else {
        // -- lets try to get the last value known for responsive ratio if the height of the current video is now currently known
        if (o.videoplayersettings.responsive_ratio && o.videoplayersettings.responsive_ratio === 'detect') {
          if (last_height_for_videoheight) {
            selfClass.videoAreaHeight = last_height_for_videoheight;
          }
        } else {
          if (selfClass.nav_position === 'left' || selfClass.nav_position === 'right') {
            selfClass.videoAreaHeight = o.sliderAreaHeight;
          }
        }
      }
      if (o.forceVideoHeight !== '' && (!o.videoplayersettings || o.videoplayersettings.responsive_ratio !== 'detect')) {
        selfClass.videoAreaHeight = o.forceVideoHeight;
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR3D) {
        selfClass.videoAreaWidth = selfClass.totalWidth / 2;
        selfClass.videoAreaHeight = selfClass.totalHeight * 0.8;
        menuitem_width = 0;
        menuitem_height = 0;
      }
      cgallery.addClass('media-area--transition-' + o.transition_type);

      // === if there is only one video we hide the nav
      if (nrChildren === 1) {
        selfClass._mainNavigation.hide();
      }
      if ($currVideoPlayer) {}
      ;
      if (o.settings_mode !== _playlistSettings.VIDEO_GALLERY_MODES.WALL && o.settings_mode !== _playlistSettings.VIDEO_GALLERY_MODES.VIDEOWALL) {
        (0, _viewFunctions.view_setCssPropsForElement)(selfClass.$sliderMain, {
          'width': selfClass.videoAreaWidth
        });
        if ((selfClass.nav_position === 'left' || selfClass.nav_position === 'right') && nrChildren > 1) {
          (0, _viewFunctions.view_setCssPropsForElement)(selfClass.$sliderMain, {
            'width': 'auto'
          });
        }
        (0, _viewFunctions.view_setCssPropsForElement)(selfClass.$sliderMain, {
          'height': selfClass.videoAreaHeight
        });
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR3D) {
        (0, _viewFunctions.view_setCssPropsForElement)(selfClass.$sliderMain, {
          'width': selfClass.totalWidth,
          'height': selfClass.totalHeight
        });
        (0, _viewFunctions.view_setCssPropsForElement)(selfClass._sliderCon.children(), {
          'width': selfClass.videoAreaWidth,
          'height': selfClass.videoAreaHeight
        });
      }

      // -- END calculate dims for navigation / mode-normal

      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL) {
        const $dzsNavItems = selfClass.$navigationItemsContainer.find('.dzs-navigation--item');
        if (menuitem_width) {
          (0, _viewFunctions.view_setCssPropsForElement)($dzsNavItems, {
            'width': menuitem_width
          });
        }
        if (menuitem_height) {
          (0, _viewFunctions.view_setCssPropsForElement)($dzsNavItems, {
            'height': menuitem_height
          });
        }
        if (menuitem_height === 0) {
          (0, _viewFunctions.view_setCssPropsForElement)($dzsNavItems, {
            'height': ''
          });
        }
      }
      if (o.nav_type === 'scroller') {
        if (selfClass.nav_position === 'top' || selfClass.nav_position === 'bottom') {
          navWidth = 0;
          selfClass.$navigationItemsContainer.children().each(function () {
            const _t = $(this);
            navWidth += _t.outerWidth(true);
          });
          selfClass.$navigationItemsContainer.width(navWidth);
        }
      }
      calculateDims_secondCon(currNr_curr);
      selfClass.Navigation.calculateDims();
      // -- calculateDims() END
    }

    function view_handleOrientationChange() {
      setTimeout(function () {
        handleResize();
      }, 1000);
    }
    function handleResize(e, pargs) {
      ww = $(window).width();
      heightWindow = $(window).height();
      conw = $galleryParent.width();
      if (cgallery.hasClass('try-breakout')) {
        (0, _viewFunctions.view_setCssPropsForElement)(cgallery, {
          'width': ww + 'px'
        });
        (0, _viewFunctions.view_setCssPropsForElement)(cgallery, {
          'margin-left': '0'
        });
        if (cgallery.offset().left > 0) {
          (0, _viewFunctions.view_setCssPropsForElement)(cgallery, {
            'margin-left': '-' + cgallery.offset().left + 'px'
          });
        }
      }
      if (cgallery.hasClass('try-height-as-window-minus-offset')) {
        let windowMinusGalleryOffset = heightWindow - cgallery.offset().top;
        if (windowMinusGalleryOffset < _Constants.PLAYLIST_HEIGHT_IS_WINDOW_MAX_OFFSET) {
          (0, _viewFunctions.view_setCssPropsForElement)(cgallery, {
            'height': '90vh'
          });
        } else {
          (0, _viewFunctions.view_setCssPropsForElement)(cgallery, {
            'height': windowMinusGalleryOffset + 'px'
          });
        }
      }
      calculateDims();
      if ($currVideoPlayer) {
        handleResize_currVideo();
      }
    }
    function handleResize_currVideo(e, pargs) {
      var margs = {
        'force_resize_gallery': true,
        'called_from': 'default'
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      margs.called_from += '_handleResize_currVideo';
      if ($currVideoPlayer && $currVideoPlayer.get(0) && $currVideoPlayer.get(0).api_handleResize) {
        $currVideoPlayer.get(0).api_handleResize(null, margs);
      }
    }
    function pause_currVideo(e, pargs) {
      var margs = {
        'force_resize_gallery': true,
        'called_from': 'default'
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      margs.called_from += '_pause_currVideo';
      if ($currVideoPlayer && $currVideoPlayer.get(0).api_pauseMovie) {
        $currVideoPlayer.get(0).api_pauseMovie(margs);
      }
    }
    function api_currVideo_refresh_fsbutton(argcol) {
      if (typeof $currVideoPlayer != "undefined" && typeof $currVideoPlayer.get(0) != "undefined" && typeof $currVideoPlayer.get(0).api_currVideo_refresh_fsbutton != "undefined") {
        $currVideoPlayer.get(0).api_currVideo_refresh_fsbutton(argcol);
      }
    }
    function handleClickOnNavigationContainer(e) {
      var _t = $(this);
      let classForNavigationItem = '';
      if (_t.hasClass('dzs-navigation--item')) {
        classForNavigationItem = '.dzs-navigation--item';
      }
      if (e) {
        selfClass.handleHadFirstInteraction(e);
      }
      if (_t.get(0) && _t.get(0).nodeName !== "A") {
        gotoItem(selfClass.$navigationItemsContainer.children(classForNavigationItem).index(_t));
        if (o.nav_type_auto_scroll === 'on') {
          if (o.nav_type === 'thumbs' || o.nav_type === 'scroller') {
            isMenuMovementLocked = true;
            setTimeout(function () {

              // todo: get form _navigation.js
            }, 100);
            setTimeout(function () {
              isMenuMovementLocked = false;
            }, 2000);
          }
        }
      } else {
        if ($currVideoPlayer && $currVideoPlayer.get(0) && typeof $currVideoPlayer.get(0).api_pauseMovie != "undefined") {
          $currVideoPlayer.get(0).api_pauseMovie({
            'called_from': 'handleClickOnNavigationContainer()'
          });
        }
      }
    }
    function handleScroll() {
      if (!selfClass.isGalleryLoaded) {
        // -- try init

        var st = $(window).scrollTop();
        var cthisOffsetTop = cgallery.offset().top;
        var wh = window.innerHeight;
        if (cthisOffsetTop < st + wh + 50) {
          init();
        }
      }
    }
    function gotoItem(arg, pargs) {
      var gotoItemOptions = {
        'ignore_arg_currNr_check': false,
        'ignore_linking': false // -- does not change the link if set to true
        ,

        donotopenlink: "off",
        called_from: "default"
      };
      if (pargs) {
        gotoItemOptions = $.extend(gotoItemOptions, pargs);
      }
      if (!(o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {
        if (currNr === arg || isBusyTransition) {
          return false;
        }
      }
      let isTransformed = false; // -- if the video is already transformed there is no need to wait
      var _currentTargetPlayer = selfClass._sliderCon.children().eq(arg);
      var argsForVideoPlayer = $.extend({}, o.videoplayersettings);
      $currVideoPlayer = _currentTargetPlayer;
      argsForVideoPlayer.gallery_object = cgallery;
      argsForVideoPlayer.gallery_last_curr_nr = currNr;
      if (gotoItemOptions.called_from === 'init') {
        argsForVideoPlayer.first_video_from_gallery = 'on';
      }
      argsForVideoPlayer['gallery_target_index'] = arg;
      var shouldVideoAutoplay = (0, _playlistHelpers.assertVideoFromGalleryAutoplayStatus)(currNr, o, cgallery);
      argsForVideoPlayer['autoplay'] = shouldVideoAutoplay ? 'on' : 'off';
      currNr_curr = arg;
      if (o.settings_enable_linking === 'on') {
        if (_currentTargetPlayer.attr('data-type') === 'link' && gotoItemOptions.donotopenlink !== 'on') {
          (0, _playlistHelpers.playlistGotoItemHistoryChangeForLinks)(selfClass.ind_ajaxPage, o, cgallery, _currentTargetPlayer, selfClass.deeplinkGotoItemQueryParam);
          return false;
        }
        if (_currentTargetPlayer.attr('data-type') !== 'link') {
          (0, _playlistHelpers.playlistGotoItemHistoryChangeForNonLinks)(gotoItemOptions, o, selfClass.galleryComputedId, arg, selfClass.deeplinkGotoItemQueryParam);
        }
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one') {
        _currentTargetPlayer = selfClass._sliderCon.children().eq(0);
        _currentTargetPlayer.addClass('playlist-mode-video-one--main-player');
        $currVideoPlayer = _currentTargetPlayer;
        var _targetPlayer = selfClass._sliderCon.children().eq(arg);
        var optionsForChange = (0, _dzsvg_helpers.detect_videoTypeAndSourceForElement)(_targetPlayer);
        // -- one
        if ($currVideoPlayer.hasClass('vplayer')) {
          pause_currVideo();
          $currVideoPlayer.get(0).api_change_media(optionsForChange.source, {
            'type': optionsForChange.type,
            autoplay: shouldVideoAutoplay ? 'on' : 'off'
          });
        } else {
          // -- one video_mode .. vplayer-tobe
          // -- first item
          $currVideoPlayer.vPlayer(argsForVideoPlayer);
          $currVideoPlayer.addClass('active');
          $currVideoPlayer.addClass('currItem');
        }
        selfClass.$navigationItemsContainer.children('.dzs-navigation--item').removeClass('active');
        selfClass.$navigationItemsContainer.children('.dzs-navigation--item').eq(arg).addClass('active');
      }

      // -- not one
      if (!(o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {
        if (currNr > -1) {
          var _c2 = selfClass._sliderCon.children().eq(currNr);

          // --- if on iPad or iPhone, we disable the video as it had runed in a iframe and it wont pause otherwise
          _c2.addClass('transitioning-out');
          if ((0, _dzs_helpers.is_ios)() || _c2.attr('data-type') === 'inline' || _c2.attr('data-type') === 'youtube' && o.videoplayersettings['settings_youtube_usecustomskin'] !== 'on') {
            setTimeout(function () {
              _c2.find('.video-description').remove();
              _c2.data('original-iframe', _c2.html());

              // -- we will delete inline content here
              _c2.html('');
              _c2.removeClass('vplayer');
              _c2.addClass('vplayer-tobe');
            }, 1000);
          }
          ;
        }
      }
      if (o.autoplay_ad === 'on') {
        _currentTargetPlayer.data('adplayed', 'on');
      } else {
        _currentTargetPlayer.data('adplayed', 'off');
      }
      if (_currentTargetPlayer.hasClass('vplayer')) {
        isTransformed = true;
      }
      if (!(o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {
        _currentTargetPlayer.addClass('transitioning-in');
      }
      if (_currentTargetPlayer.hasClass('type-inline') && _currentTargetPlayer.data('original-iframe')) {
        if (_currentTargetPlayer.html() === '') {
          _currentTargetPlayer.html(_currentTargetPlayer.data('original-iframe'));
        }
      }

      // -- not one
      if (!(o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {
        if (_currentTargetPlayer.hasClass('vplayer-tobe')) {
          // -- if not inited

          _currentTargetPlayer.addClass('in-vgallery');
          argsForVideoPlayer['videoWidth'] = selfClass.videoAreaWidth;
          argsForVideoPlayer['videoHeight'] = '';
          argsForVideoPlayer['old_curr_nr'] = currNr;
          if (currNr === -1 && o.cueFirstVideo === 'off') {
            argsForVideoPlayer.cueVideo = 'off';
          } else {
            argsForVideoPlayer.cueVideo = 'on';
          }
          argsForVideoPlayer['settings_disableControls'] = 'off';
          argsForVideoPlayer.htmlContent = '';
          argsForVideoPlayer.gallery_object = cgallery;
          if (argsForVideoPlayer.end_exit_fullscreen === 'off') {
            // -- exit fullscreen on video end

            if (cgallery.find('.vplayer.currItem').hasClass('type-vimeo')) {
              cgallery.find('.vplayer.currItem').removeClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
            }

            // -- next video has fullscreen status
            if ((0, _dzsvg_helpers.fullscreen_status)() === 1) {
              argsForVideoPlayer.extra_classes = argsForVideoPlayer.extra_classes ? argsForVideoPlayer.extra_classes + ' ' + _Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS : ' ' + _Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS;
            }
            setTimeout(function () {}, 500);
          }
          if (o.settings_disableVideo === 'on') {} else {
            // -- NOT ONE MODE o.mode_normal_video_mode
            _currentTargetPlayer.vPlayer(argsForVideoPlayer);
          }
        } else {
          // -- NOT (ONE) if already setuped

          if (!(o.init_all_players_at_init === 'on' && currNr === -1)) {
            if (shouldVideoAutoplay) {
              if (typeof _currentTargetPlayer.get(0) != 'undefined' && typeof _currentTargetPlayer.get(0).externalPlayMovie != 'undefined') {
                _currentTargetPlayer.get(0).externalPlayMovie({
                  'called_from': 'autoplayNext'
                });
              }
            }
          }
          if (o.videoplayersettings.end_exit_fullscreen === 'off') {
            if ((0, _dzsvg_helpers.fullscreen_status)() === 1) {
              _currentTargetPlayer.addClass(_Constants.PLAYLIST_VIEW_FULLSCREEN_CLASS);
            }
          }

          // -- we force a resize on the player just in case it has an responsive ratio

          setTimeout(function () {
            if (typeof _currentTargetPlayer.get(0) != 'undefined' && _currentTargetPlayer.get(0).api_handleResize) {
              _currentTargetPlayer.get(0).api_handleResize(null, {
                force_resize_gallery: true
              });
            }
          }, 250);
        }
      }
      prevNr = arg - 1;
      if (prevNr < 0) {
        prevNr = selfClass._sliderCon.children().length - 1;
      }
      nextNr = arg + 1;
      if (nextNr > selfClass._sliderCon.children().length - 1) {
        nextNr = 0;
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL) {
        (0, _viewFunctions.view_setCssPropsForElement)(_currentTargetPlayer, {
          'display': ''
        });
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR3D) {
        selfClass._sliderCon.children().removeClass('nextItem currItem hide-preview-img').removeClass('prevItem');
        selfClass._sliderCon.children().eq(nextNr).addClass('nextItem');
        selfClass._sliderCon.children().eq(prevNr).addClass('prevItem');
      }
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR) {
        if (currNr > -1) {}
        var _descCon = selfClass.$navigationClippedContainer.children('.descriptionsCon');
        _descCon.children('.currDesc').removeClass('currDesc').addClass('pastDesc');
        _descCon.append('<div class="desc">' + _currentTargetPlayer.find('.feed-menu-desc').html() + '</div>');
        setTimeout(function () {
          _descCon.children('.desc').addClass('currDesc');
        }, 20);
      }
      last_arg = arg;
      if (!(o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {
        if (currNr === -1 || isTransformed) {
          galleryTransition();
          if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR3D) {
            selfClass._sliderCon.children().eq(arg).addClass('hide-preview-img');
          }
        } else {
          cgallery.parent().children('.preloader').addClass('is-visible');
          let delay = 500;
          if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.ROTATOR3D) {
            delay = 10;
            selfClass._sliderCon.children().eq(arg).addClass('currItem');
            setTimeout(function () {
              selfClass._sliderCon.children().eq(arg).addClass('hide-preview-img');
            }, _Constants.PLAYLIST_DEFAULT_TIMEOUT);
          }
          inter_start_the_transition = setTimeout(galleryTransition, delay, arg);
        }
      } else {
        selfClass.isBusyAjax = false;
        isBusyTransition = false;
        currNr = arg;
      }
      calculateDims_secondCon(arg);
      if (o.settings_outerNav) {
        var _c_outerNav = $(o.settings_outerNav);
        _c_outerNav.find('.videogallery--navigation-outer--block ').removeClass('active');
        _c_outerNav.find('.videogallery--navigation-outer--block ').eq(arg).addClass('active');
        _c_outerNav.find('*[data-global-responsive-ratio]').each(function () {
          var _t4 = $(this);
          var rat = Number(_t4.attr('data-global-responsive-ratio'));
          _t4.height(rat * _t4.width());
        });
      }
      if (cgallery.hasClass('responsive-ratio-smooth')) {
        if (!_currentTargetPlayer.attr('data-responsive_ratio')) {
          apiResponsiveRationResize(heightInitial, {});
        } else {
          $(window).trigger('resize');
        }
      }
      cgallery.removeClass('hide-players-not-visible-on-screen');
      setTimeout(function () {
        cgallery.addClass('hide-players-not-visible-on-screen');
        selfClass._sliderCon.find('.transitioning-in').removeClass('transitioning-in');
        selfClass._sliderCon.find('.transitioning-out').removeClass('transitioning-out');
        var _extraBtns = null;
        if (cgallery.parent().parent().next().hasClass('extra-btns-con')) {
          _extraBtns = cgallery.parent().parent().next();
        }
        if (cgallery.parent().parent().next().next().hasClass('extra-btns-con')) {
          _extraBtns = cgallery.parent().parent().next().next();
        }
        if (_extraBtns) {
          _extraBtns.find('.stats-btn').attr('data-playerid', $currVideoPlayer.attr('data-player-id'));
        }
      }, 400);
      isBusyTransition = true;
      return !(o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one');
    }
    function galleryTransition() {
      if (isTransitionStarted) {
        return;
      }
      const arg = last_arg;
      const _c = selfClass._sliderCon.children().eq(arg);
      isTransitionStarted = true;
      clearTimeout(inter_start_the_transition);
      cgallery.parent().children('.preloader').removeClass('is-visible');
      selfClass._sliderCon.children().removeClass('currItem');
      if (currNr === -1) {
        _c.addClass('currItem');
        _c.addClass('no-transition');
        setTimeout(function () {
          selfClass._sliderCon.children().eq(currNr).removeClass('no-transition');
        });
      } else {
        if (currNr !== arg) {
          selfClass._sliderCon.children().eq(currNr).addClass('transition-slideup-gotoTop');
        } else {
          selfClass._sliderCon.children().eq(currNr).addClass('currItem');
        }
      }
      setTimeout(setCurrVideoClass, 100);
      selfClass.$navigationItemsContainer.children('.dzs-navigation--item').removeClass('active');
      selfClass.$navigationItemsContainer.children('.dzs-navigation--item').eq(arg).addClass('active');
      setTimeout(function () {
        $('window').trigger('resize');
        selfClass._sliderCon.children().removeClass('transition-slideup-gotoTop');
      }, 1000);
      if ((0, _dzs_helpers.is_ios)() && currNr > -1) {
        if (selfClass._sliderCon.children().eq(currNr).children().eq(0).length > 0 && selfClass._sliderCon.children().eq(currNr).children().eq(0)[0] !== undefined) {
          if (selfClass._sliderCon.children().eq(currNr).children().eq(0)[0].tagName === 'VIDEO') {
            selfClass._sliderCon.children().eq(currNr).children().eq(0).get(0).pause();
          }
        }
      }
      if (isFirstTransition) {
        video_stopCurrentVideo({
          'called_from': 'the_transition'
        });
      }
      if (currNr > -1) {
        isFirstTransition = true;
      }
      currNr = arg;
      setTimeout(function () {
        isBusyTransition = false;
        isTransitionStarted = false;
        view_hideAllVideosButCurrentVideo();
      }, 400);
    }

    // -- end the_transition()

    function calculateDims_secondCon(arg) {
      if (o.settings_secondCon) {
        var _c = $(o.settings_secondCon);
        _c.find('.item').removeClass('active');
        _c.find('.item').eq(arg).addClass('active');
        const $innerSecondCon = _c.find('.dzsas-second-con--clip');
        if ($innerSecondCon.length) {
          (0, _viewFunctions.view_setCssPropsForElement)($innerSecondCon, {
            'height': _c.find('.item').eq(arg).outerHeight(false),
            'left': -(arg * 100) + '%'
          });
        }
      }
    }
    function view_hideAllVideosButCurrentVideo() {
      if (o.settings_mode === _playlistSettings.VIDEO_GALLERY_MODES.NORMAL) {
        selfClass._sliderCon.children().each(function () {
          const $videoItem = $(this);
          if (!$videoItem.hasClass('currItem')) {
            $videoItem.hide();
          }
        });
      }
    }
    function setCurrVideoClass() {
      if ($currVideoPlayer) {
        $currVideoPlayer.addClass('currItem');
      }
    }
    function play_currVideo() {
      if (selfClass._sliderCon.children().eq(currNr).get(0) && selfClass._sliderCon.children().eq(currNr).get(0).externalPauseMovie) {
        selfClass._sliderCon.children().eq(currNr).get(0).api_playMovie({
          'called_from': 'api_playMovie'
        });
      }
    }
    function video_stopCurrentVideo(pargs) {
      var margs = {
        'called_from': 'default'
      };
      if (pargs) {
        margs = $.extend(margs, pargs);
      }
      if (!(0, _dzs_helpers.is_ios)() && currNr > -1 && o.mode_normal_video_mode !== 'one') {
        if (selfClass._sliderCon.children().eq(currNr).get(0) && selfClass._sliderCon.children().eq(currNr).get(0).externalPauseMovie) {
          selfClass._sliderCon.children().eq(currNr).get(0).externalPauseMovie({
            'called_from': 'external_handle_stopCurrVideo() - ' + margs.called_from
          });
        }
      }
    }
    function gotoPrev() {
      if (o.playorder === 'reverse') {
        gotoNext();
        return;
      }
      var tempNr = currNr - 1;
      if (tempNr < 0) {
        tempNr = selfClass._sliderCon.children().length - 1;
      }
      gotoItem(tempNr);
    }
    function gotoNext() {
      if (o.playorder === 'reverse') {
        gotoPrev();
        return;
      }
      var isGoingToGoNext = true;
      var tempNr = currNr + 1;
      if (tempNr >= selfClass._sliderCon.children().length) {
        tempNr = 0;
        if (o.loop_playlist !== 'on') {
          isGoingToGoNext = false;
        }
        if (action_playlist_end) {
          action_playlist_end(cgallery);
        }
      }
      if (isGoingToGoNext) {
        // -- we will go forward with next movie
        gotoItem(tempNr);
      }
      if (o.nav_type_auto_scroll === 'on') {
        if (o.nav_type === 'thumbs' || o.nav_type === 'scroller') {
          setTimeout(function () {
            selfClass.Navigation.animate_to_curr_thumb();
          }, 20);
        }
      }
    }
    function handleVideoEnd() {
      // -- cgallery
      if (o.autoplayNext === 'on') {
        gotoNext();
      }
    }
    function rotator3d_handleClickOnPreviewImg(e) {
      var _t = $(this);
      var selectedIndex = _t.parent().parent().children().index(_t.parent());
      if (e) {
        selfClass.handleHadFirstInteraction(e);
      }
      gotoItem(selectedIndex);
    }
  }

  /**
   *
   * @param {Event} e
   * @returns {boolean}
   */
  handleHadFirstInteraction(e) {
    if (this.cgallery.data('user-had-first-interaction')) {
      return false;
    }
    this.isHadFirstInteraction = true;
    this.cgallery.data('user-had-first-interaction', 'yes');
    this.cgallery.addClass('user-had-first-interaction');
  }
}
function apply_videogallery_plugin($) {
  $.fn.vGallery = function (argOptions) {
    this.each(function () {
      var finalOptions = {};
      let overwriteSettings = {
        ...argOptions
      };
      if (argOptions && Object.keys(argOptions).length === 1 && argOptions.init_each) {
        overwriteSettings = null;
      }
      finalOptions = (0, _dzsvg_helpers.convertPluginOptionsToFinalOptions)(this, (0, _playlistSettings.getDefaultPlaylistSettings)(), overwriteSettings);
      return new DzsVideoGallery(this, finalOptions, $);
    }); // end each
  };

  window.dzsvg_init = function (selector, settings) {
    $(selector).vGallery(settings);
  };
  // -- deprecated
  window.zsvg_init = function (selector, settings) {
    $(selector).vGallery(settings);
  };
}
if (!window.dzsvg_settings) {
  if (window.dzsvg_default_settings) {
    window.dzsvg_settings = {
      ...{}
    };
  }
}
window.setup_videogalleryCategories = _dzsvg_helpers.setup_videogalleryCategories;
function dzsvg_handleInitedjQuery() {
  (function ($) {
    apply_videogallery_plugin($);
  })(jQuery);
  const dzsvg_reinit = () => {
    (0, _second_con.secondCon_initFunctions)();
    (0, _dzsvg_helpers.init_navigationOuter)();
  };
  jQuery(document).ready(function () {
    dzsvg_init('.videogallery.auto-init');
    dzsvg_reinit();
  });
  (0, _dzsvg_helpers.dzsvgExtraWindowFunctions)();
  window.dzsvg_reinit = dzsvg_reinit;
}
(0, _dzs_helpers.loadScriptIfItDoesNotExist)('', 'jQuery').then(() => {
  dzsvg_handleInitedjQuery();
});

},{"./configs/Constants":1,"./configs/_playlistSettings":3,"./js_common/_dzs_helpers":4,"./js_dzsvg/_dzsvg_helpers":5,"./js_dzsvg/components/_second_con":7,"./js_dzsvg/playlist/_playlistBuilderFunctions":12,"./js_dzsvg/playlist/_playlistHelpers":13,"./js_playlist/_playlistAuxiliaryButtons":16,"./js_playlist/_viewPlaylistStructure":18,"./js_playlist/mode/_mode-wall":19,"./js_playlist/view/_calculateDims":20,"./js_playlist/view/_pagination":21,"./shared/_viewFunctions":22}]},{},[23])


//# sourceMappingURL=vgallery.js.map