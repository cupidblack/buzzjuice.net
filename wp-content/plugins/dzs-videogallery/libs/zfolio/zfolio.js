(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.ZFOLIO_MODE=exports.VIEW_ISOTOPE_ITEM_CLASS=exports.VIEW_ALLEGED_16_9_RATIO=void 0;const VIEW_ISOTOPE_ITEM_CLASS=exports.VIEW_ISOTOPE_ITEM_CLASS="isotope-item",VIEW_ALLEGED_16_9_RATIO=exports.VIEW_ALLEGED_16_9_RATIO=.5625,ZFOLIO_MODE=exports.ZFOLIO_MODE={SCROLLER:"scroller"};
},{}],2:[function(require,module,exports){
"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.zfolioDefaultSettings=void 0;var _constants=require("./_constants");const zfolioDefaultSettings=exports.zfolioDefaultSettings={design_item_thumb_just_use_img:"off",settings_autoHeight:"on",settings_skin:"skin-default",settings_mode:"isotope",settings_disableCats:"off",settings_clickaction:"none",title:"",design_total_height_full:"off",pagination_method:"normal",design_item_width:"0",design_item_height:"0",design_item_height_same_as_width:"off",design_sizecontain_forfeatureimage:"off",design_thumbw:"",init_on:"init",design_item_thumb_height:"",design_categories_pos:"top",design_categories_align:"auto",design_specialgrid_chooser_align:"auto",design_pageContent_pos:"top",design_categories_style:"normal",design_waitalittleforallloaded:"off",use_scroll_lazyloading_for_images:"off",settings_ajax_method:"off",settings_ajax_method_curritems_per_page:"5",settings_ajax_method_curritems_per_page_initial:"",settings_ajax_pagination_method:"scroll",settings_ajax_pages:[],settings_lightboxlibrary:"zoombox",item_inner_addid:"",settings_preloadall:"off",settings_add_loaded_on_images:"off",settings_useLinksForCategories:"off",settings_useLinksForCategories_enableHistoryApi:"off",item_link_thumb_con_to:"link",item_link_whole_to:"none",disable_itemmeta:"off",filters_enable:"on",disable_cats:"off",wall_settings:{},settings_enableHistory:"off",audioplayer_swflocation:"ap.swf",videoplayer_swflocation:"preview.swf",settings_makeFunctional:!0,settings_defaultCat:"",settings_forceCats:[],settings_categories_strall:"All",settings_categories_strselectcategory:"Select Category",settings_set_forced_width:"off",settings_isotope_settings:{getSortData:{sorter:function(e){var t=$(e).attr("data-sort");return t?parseInt(t.replace(/[\(\)]/g,"")):0}},itemSelector:"."+_constants.VIEW_ISOTOPE_ITEM_CLASS,sortBy:"sorter",percentPosition:!0,columnWidth:".grid-sizer",gutter:".gutter-sizer",layoutMode:"packery",masonry:{columnWidth:".grid-sizer",percentPosition:!0},packery:{columnWidth:".grid-sizer",percentPosition:!0}},scroller_settings:{},zoombox_settings:{},item_extra_class:"",responsive_fallback_tablet:"",responsive_fallback_mobile:"",excerpt_con_transition:"zoom",excerpt_con_resize_videos:"off",excerpt_con_responsive_ratio:"810",selector_con_skin:"default",selector_con_generate_categories:"auto",outer_con_selector_con:null,outer_con_search_con:null,pagination_selector:null};
},{"./_constants":1}],3:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.add_query_arg = add_query_arg;
exports.formatTime = formatTime;
exports.format_to_seconds = format_to_seconds;
exports.get_query_arg = get_query_arg;
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
function add_query_arg(purl, key, value) {
  key = encodeURIComponent(key);
  value = encodeURIComponent(value);
  var s = purl;
  var pair = key + "=" + value;
  var r = new RegExp("(&|\\?)" + key + "=[^\&]*");
  s = s.replace(r, "$1" + pair);
  var addition = '';
  if (s.indexOf(key + '=') > -1) {} else {
    if (s.indexOf('?') > -1) {
      addition = '&' + pair;
    } else {
      addition = '?' + pair;
    }
    s += addition;
  }

  //if value NaN we remove this field from the url
  if (value === 'NaN') {
    var regex_attr = new RegExp('[\?|\&]' + key + '=' + value);
    s = s.replace(regex_attr, '');
  }
  return s;
}
function get_query_arg(purl, key) {
  if (purl.indexOf(key + '=') > -1) {
    var regexS = "[?&]" + key + "=.+";
    var regex = new RegExp(regexS);
    var regtest = regex.exec(purl);
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

},{}],4:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.viewFilterCategory = viewFilterCategory;
exports.zfolioCategoriesGenerate = exports.zfolioCategoriesCalculateCatsArray = void 0;
var _dzs_helpers = require("../js_common/_dzs_helpers");
const zfolioCategoriesCalculateCatsArray = (catsString, $feedItem, arr_cats) => {
  let catsArray = catsString.split(';');
  for (let j = 0; j < catsArray.length; j++) {
    let the_cat = catsArray[j];
    let theCategoryUnsanitized = catsArray[j];
    if (the_cat) {
      the_cat = the_cat.replace(/ /gi, '-');
      $feedItem.addClass('cat-' + the_cat);
    }
    let isAlreadyAdded = false;
    for (let k = 0; k < arr_cats.length; k++) {
      if (arr_cats[k] === theCategoryUnsanitized) {
        isAlreadyAdded = true;
      }
    }
    if (!isAlreadyAdded) {
      arr_cats.push(theCategoryUnsanitized);
    }
  }
};
exports.zfolioCategoriesCalculateCatsArray = zfolioCategoriesCalculateCatsArray;
const zfolioCategoriesGenerate = ($categoriesParent, o, _selectorCon, arr_cats, cthis, arr_cats_type, cid) => {
  if ($categoriesParent.length) {
    $categoriesParent.html('');
  }
  if (o.settings_useLinksForCategories === 'on') {
    $categoriesParent.append('<a class="a-category category-all-selector active" href="' + (0, _dzs_helpers.add_query_arg)(window.location.href, 'dzsp_defCategory_' + cid, 0) + '">' + o.settings_categories_strall + '</a>');
  } else {
    $categoriesParent.append('<div class="a-category category-all-selector  active">' + o.settings_categories_strall + '</div>');
  }
  for (let i = 0; i < arr_cats.length; i++) {
    let label = cthis.find('.feed-zfolio-zfolio-term[data-termid="' + arr_cats[i] + '"]').eq(0).html();
    if (o.settings_useLinksForCategories === 'on') {
      if (arr_cats_type === 'dataterm') {
        if (label) {
          $categoriesParent.append('<a class="a-category"  href="' + (0, _dzs_helpers.add_query_arg)(window.location.href, 'dzsp_defCategory_' + cid, i + 1) + '" data-termid="' + arr_cats[i] + '">' + label + '</a>');
        }
      } else {
        $categoriesParent.append('<a class="a-category"  href="' + (0, _dzs_helpers.add_query_arg)(window.location.href, 'dzsp_defCategory_' + cid, i + 1) + '" >' + arr_cats[i] + '</a>');
      }
    } else {
      if (arr_cats_type === 'dataterm') {
        if (label) {
          $categoriesParent.append('<div class="a-category" data-termid="' + arr_cats[i] + '">' + label + '</div>');
        }
      } else {
        $categoriesParent.append('<div class="a-category" >' + arr_cats[i] + '</div>');
      }
    }
  }
  _selectorCon.removeClass('empty-categories');
};
exports.zfolioCategoriesGenerate = zfolioCategoriesGenerate;
function viewFilterCategory(_t, cthis, $itemsCon, _selectorCon, o, goto_category) {
  let isBreakFunction = false;
  if (!cthis.hasClass('dzszfl-ready-for-transitions')) {
    let isotopeFilterArgs = {};
    isotopeFilterArgs = jQuery.extend(isotopeFilterArgs, o.settings_isotope_settings);
    isotopeFilterArgs.transitionDuration = '0s';
    if (cthis.hasClass('dzs-layout--3-cols')) {
      isotopeFilterArgs.percent_amount = 33.3333;
    }
    if (cthis.hasClass('dzs-layout--4-cols')) {
      isotopeFilterArgs.percent_amount = 25;
    }
    if (cthis.hasClass('dzs-layout--6-cols')) {
      isotopeFilterArgs.percent_amount = 16.6666;
    }
    isotopeFilterArgs.transitionDuration = '0.3s';
    isotopeFilterArgs.transitionDuration = '0.4s';
    $itemsCon.isotope(isotopeFilterArgs);
    cthis.addClass('dzszfl-ready-for-transitions');
    clearTimeout(inter_set_transition_duration);
  }
  if (_t.hasClass('active')) {
    _selectorCon.toggleClass('is-opened');
    isBreakFunction = true;
  }
  var ind = _t.parent().children().index(_t);
  var cat = _t.html();
  if (_t.attr('data-termid')) {
    cat = _t.attr('data-termid');
    goto_category(cat, {
      'class_name': 'termid'
    });
  } else {
    if (o.settings_useLinksForCategories !== 'on' || o.settings_useLinksForCategories_enableHistoryApi === 'on') {
      goto_category(cat);
    }
  }
  _selectorCon.removeClass('is-opened');
  if (o.settings_useLinksForCategories === 'on' && o.settings_useLinksForCategories_enableHistoryApi === 'on') {
    const stateObj = {
      foo: "bar"
    };
    history.pushState(stateObj, "ZoomFolio Category " + ind, (0, _dzs_helpers.add_query_arg)(window.location.href, 'dzsp_defCategory_' + cid, ind));
  }
  if (o.settings_useLinksForCategories_enableHistoryApi === 'on') {
    isBreakFunction = true;
  }
  return isBreakFunction;
}

},{"../js_common/_dzs_helpers":3}],5:[function(require,module,exports){
"use strict";function zfolioInitialSetup(s,e){s.hasClass("skin-qucreative")&&(e.design_skin="skin-qucreative"),s.hasClass("skin-material")&&(e.design_skin="skin-material"),s.hasClass("skin-forwall")&&(e.design_skin="skin-forwall","default"===e.selector_con_skin&&(e.selector_con_skin="selector-con-for-skin-forwall")),s.hasClass("skin-melbourne")&&(e.design_skin="skin-melbourne","default"===e.selector_con_skin&&(e.selector_con_skin="selector-con-for-skin-melbourne")),s.hasClass("skin-silver")&&(e.design_skin="skin-silver","default"===e.selector_con_skin&&(e.selector_con_skin="selector-con-for-skin-silver")),s.hasClass("skin-gazelia")&&(e.design_skin="skin-gazelia"),s.hasClass("skin-lazarus")&&(e.design_skin="skin-lazarus"),s.hasClass("skin-alba")&&(e.design_skin="skin-alba"),s.hasClass("skin-woo")&&(e.design_skin="skin-woo"),s.hasClass("skin-noskin")&&(e.design_skin="skin-noskin"),"default"===e.selector_con_skin&&(e.selector_con_skin="selector-con-for-skin-melbourne"),s.addClass("mode-"+e.settings_mode),"auto"===e.settings_ajax_method_curritems_per_page&&(s.hasClass("dzs-layout--6-cols")&&(e.settings_ajax_method_curritems_per_page="6"),s.hasClass("dzs-layout--5-cols")&&(e.settings_ajax_method_curritems_per_page="5"),s.hasClass("dzs-layout--4-cols")&&(e.settings_ajax_method_curritems_per_page="4"),s.hasClass("dzs-layout--3-cols")&&(e.settings_ajax_method_curritems_per_page="3"),s.hasClass("dzs-layout--2-cols")&&(e.settings_ajax_method_curritems_per_page="2")),"auto"===e.settings_ajax_method_curritems_per_page&&(e.settings_ajax_method_curritems_per_page="4")}function zfolioGetContentCon(s){return s.parent().parent().parent().parent().parent().parent().parent().parent().hasClass("the-content-con")?s.parent().parent().parent().parent().parent().parent().parent().parent():s.parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().hasClass("the-content-con")?s.parent().parent().parent().parent().parent().parent().parent().parent().parent().parent():null}Object.defineProperty(exports,"__esModule",{value:!0}),exports.zfolioGetContentCon=zfolioGetContentCon,exports.zfolioInitialSetup=zfolioInitialSetup;
},{}],6:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.structureGenerateOverlay = void 0;
const structureGenerateOverlay = (isInsideAnchor, overlay_extra_class, dataLink, zfolioSkin, $feedItem) => {
  let isLink = false;
  let stringOverlayStruct = '<div class="the-overlay' + overlay_extra_class + '" ';
  if (dataLink) {
    if (zfolioSkin === 'skin-silver' || zfolioSkin === 'skin-qucreative') {
      if (isInsideAnchor) {
        stringOverlayStruct = '<div href="' + dataLink + '" class="the-overlay ' + overlay_extra_class + '" ';
      } else {
        stringOverlayStruct = '<a href="' + dataLink + '" class="the-overlay ' + overlay_extra_class + '" ';
      }
      isLink = true;
    }
    if (zfolioSkin === 'skin-gazelia') {
      isLink = false;
      stringOverlayStruct = '<div class="the-overlay" ';
      stringOverlayStruct += '>';
      if (isInsideAnchor) {
        stringOverlayStruct += '<div href="' + dataLink + '" class="the-overlay-anchor ' + overlay_extra_class + '"';
      } else {
        stringOverlayStruct += '<a href="' + dataLink + '" class="the-overlay-anchor ' + overlay_extra_class + '"';
      }
      if ($feedItem.attr('data-overlay_anchor_extra_attr')) {
        stringOverlayStruct += $feedItem.attr('data-overlay_anchor_extra_attr');
      }
      stringOverlayStruct += '>';
      if ($feedItem.children('.overlay-anchor-extra-html').length > 0) {
        stringOverlayStruct += $feedItem.children('.overlay-anchor-extra-html').eq(0).html();
      }
      if (isInsideAnchor) {
        stringOverlayStruct += '</div';
      } else {
        stringOverlayStruct += '</a';
      }
    }
    if (zfolioSkin === 'skin-lazarus') {
      isLink = false;
      stringOverlayStruct = '<div class="the-overlay" ';
      stringOverlayStruct += '>';
      if (isInsideAnchor) {
        stringOverlayStruct += '<div href="' + dataLink + '" class="the-overlay-anchor ' + overlay_extra_class + '"';
      } else {
        stringOverlayStruct += '<a href="' + dataLink + '" class="the-overlay-anchor ' + overlay_extra_class + '"';
      }
      if ($feedItem.attr('data-overlay_anchor_extra_attr')) {
        stringOverlayStruct += $feedItem.attr('data-overlay_anchor_extra_attr');
      }
      stringOverlayStruct += '>';
      if ($feedItem.children('.overlay-anchor-extra-html').length > 0) {
        stringOverlayStruct += $feedItem.children('.overlay-anchor-extra-html').eq(0).html();
      }
      if (isInsideAnchor) {
        stringOverlayStruct += '</div';
      } else {
        stringOverlayStruct += '</a';
      }
    }
  }
  if ($feedItem.attr('data-overlay_extra_attr')) {
    stringOverlayStruct += $feedItem.attr('data-overlay_extra_attr');
  }
  stringOverlayStruct += '>';
  if (isLink) {
    stringOverlayStruct += '</a>';
  } else {
    stringOverlayStruct += '</div>';
  }
  return stringOverlayStruct;
};
exports.structureGenerateOverlay = structureGenerateOverlay;

},{}],7:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.structureItemGenerateThumbCon = void 0;
const structureItemGenerateThumbCon = (item_link_thumb_con_to, $feedItem) => {
  let stringStructTheFeatureCon = '';
  if (item_link_thumb_con_to === 'link') {
    stringStructTheFeatureCon += ' <a class="the-feature-con  custom-a';
  } else {
    stringStructTheFeatureCon += ' <div class="the-feature-con';
  }
  if (item_link_thumb_con_to === 'ultibox') {
    if ($feedItem.attr('data-bigimage')) {
      stringStructTheFeatureCon += ' ultibox-item';
    }
  }
  stringStructTheFeatureCon += '"';
  if (item_link_thumb_con_to === 'ultibox') {
    if ($feedItem.attr('data-bigimage')) {
      stringStructTheFeatureCon += ' data-source="' + $feedItem.attr('data-bigimage') + '"';
    }
    if ($feedItem.attr('data-biggallery')) {
      stringStructTheFeatureCon += ' data-biggallery="' + $feedItem.attr('data-biggallery') + '"';
    }
  }
  if (item_link_thumb_con_to === 'link') {
    if ($feedItem.attr('data-link')) {
      stringStructTheFeatureCon += ' href="' + $feedItem.attr('data-link') + '"';
      if ($feedItem.attr('data-link-target')) {
        stringStructTheFeatureCon += ' target="' + $feedItem.attr('data-link-target') + '"';
      }
    }
  }
  stringStructTheFeatureCon += '>';
  if (item_link_thumb_con_to === 'link') {
    stringStructTheFeatureCon += ' </a>';
  } else {
    stringStructTheFeatureCon += '</div>';
  }
  return stringStructTheFeatureCon;
};
exports.structureItemGenerateThumbCon = structureItemGenerateThumbCon;

},{}],8:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.viewGenerateStructureItemMeta = void 0;
const viewGenerateStructureItemMeta = $feedItem => {
  let aux_struct_item_meta = '';
  aux_struct_item_meta += ' <div class="item-meta">';
  if ($feedItem.find('.feed-zfolio-the-title').length) {
    aux_struct_item_meta += ' <div class="the-title">' + $feedItem.find('.feed-zfolio-the-title').eq(0).html() + '</div>';
  }
  if ($feedItem.find('.feed-zfolio-the-desc').length) {
    aux_struct_item_meta += ' <div class="the-desc">' + $feedItem.find('.feed-zfolio-the-desc').eq(0).html() + '</div>';
  }
  aux_struct_item_meta += '</div>';
  return aux_struct_item_meta;
};
exports.viewGenerateStructureItemMeta = viewGenerateStructureItemMeta;

},{}],9:[function(require,module,exports){
/*
 * Author: Digital Zoom Studio
 * Website: http://digitalzoomstudio.net/
 * Portfolio: http://codecanyon.net/user/ZoomIt/portfolio
 *
 * Version: 0.9901
 */

"use strict";

var _constants = require("./config/_constants");
var _zfolioHelpers = require("./js_zfolio/_zfolioHelpers");
var _dzs_helpers = require("./js_common/_dzs_helpers");
var _zfolioSettings = require("./config/_zfolioSettings");
var _view_structureGenerateOverlay = require("./js_zfolio/structure/_view_structureGenerateOverlay");
var _view_structureItemGenerateThumbCon = require("./js_zfolio/structure/_view_structureItemGenerateThumbCon");
var _view_structureItemMeta = require("./js_zfolio/structure/_view_structureItemMeta");
var _categoryHelpers = require("./js_zfolio/_categoryHelpers");
window.dzszfl_self_options = {};
Math.easeIn = function (t, b, c, d) {
  return -c * (t /= d) * (t - 2) + b;
};
(function ($) {
  $.fn.prependOnce = function (arg, argfind) {
    var _t = $(this);
    if (typeof argfind == 'undefined') {
      var regex = new RegExp('class="(.*?)"');
      var auxarr = regex.exec(arg);
      if (typeof auxarr[1] != 'undefined') {
        argfind = '.' + auxarr[1];
      }
    }
    if (_t.children(argfind).length < 1) {
      _t.prepend(arg);
      return true;
    } else {
      return false;
    }
  };
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
    } else {
      return false;
    }
  };
  $.fn.zfolio = function (o) {
    var scroller_default_settings = {
      "settings_direction": "horizontal",
      "settings_onlyone": "off",
      "settings_autoHeight": "on",
      "per_row": "default",
      "design_bulletspos": "none"
    };
    if (!o) {
      if ($(this).attr('data-options')) {
        const aux = $(this).attr('data-options');
        try {
          o = $.extend({}, JSON.parse(aux));
        } catch (err) {
          o = JSON.parse(JSON.stringify(_zfolioSettings.zfolioDefaultSettings));
        }
      }
    }
    o = $.extend(JSON.parse(JSON.stringify(_zfolioSettings.zfolioDefaultSettings)), o);
    o.scroller_settings = $.extend(scroller_default_settings, o.scroller_settings);
    this.each(function () {
      var cthis = $(this);
      var cid = '',
        images;
      var ww, wh, tw, th;
      var $itemsCon = null,
        _selectorCon = null,
        _paginationCon = null,
        _contentScroller = null,
        _contentScrollerItems = null;
      var isDestroyed = false,
        view_isZfolioFaded = false,
        canLoadNextImagesFromScroll = false,
        view_canLoadImagesFromPrevLoaded = false,
        view_isFirstTimeLoadingImages = true // first time we allow
      ;

      var isAlreadyAdded = false;
      var inter_calculate_dims = 0,
        inter_relayout = 0;
      var mode_cols_nr_of_cols = 0,
        nr_per_page = 5,
        class_all_temp_cols = ' temp-dzs-layout--1-cols temp-dzs-layout--2-cols temp-dzs-layout--3-cols temp-dzs-layout--4-cols temp-dzs-layout--5-cols temp-dzs-layout--6-cols';
      var modeNumberOfColumns = 0,
        i_dzscol_ind = 0;

      // -- thumbsize
      var design_item_thumb_height = 200,
        grid_unit_px = 200,
        design_item_padding = 30,
        isDesignHeightDynamic = false,
        layout_margin = 0,
        inter_relayout_allow = false,
        initial_cols_before_fallback = '';
      var inter_set_transition_duration = 0;
      var _excerptContentCon = null // - the excerpt con
        ,
        _excerptContent = null,
        _excerptContent_initialPortItem = null // -- the initial port item that has been clicked to trigger the excerpt
        ,
        _tcon_content = null;
      var arr_cats = [] // -- categories
        ,
        arr_cats_type = 'datacategory' // -- categories
        ,
        cat_curr = '*' // -- categories
      ;

      var categories_parent;

      // -- loading vars
      var images_toBeLoaded = [],
        $itemsArray = null;
      cid = cthis.attr('id');
      if (typeof cid == 'undefined' || cid === '') {
        var auxnr = 0;
        var temps = 'zoomfolio' + auxnr;
        while ($('#' + temps).length > 0) {
          auxnr++;
          temps = 'zoomfolio' + auxnr;
        }
        cid = temps;
        cthis.attr('id', cid);
      }
      function handle_scroll(e, pargs) {
        var st = $(window).scrollTop();
        var cthis_ot = cthis.offset().top;
        var wh = $(window).height();
        if (cthis_ot < st + wh + 50) {
          init();
        }
      }
      if (o.init_on === 'init') {
        init();
      }
      if (o.init_on === 'scroll') {
        $(window).on('scroll.dzszfl_init_' + cid, handle_scroll);
        handle_scroll();
      }
      function init() {
        $(window).off('scroll.dzszfl_init_' + cid);
        if (cthis.hasClass('dzszfl-inited')) {
          return false;
        }
        (0, _zfolioHelpers.zfolioInitialSetup)(cthis, o);
        $itemsCon = cthis.find('.items').eq(0);
        if (!isNaN(Number(o.design_item_thumb_height))) {
          design_item_thumb_height = Number(o.design_item_thumb_height);
          isDesignHeightDynamic = design_item_thumb_height <= 3 && design_item_thumb_height > 0;
        } else {}
        if (!isNaN(Number(cthis.attr('data-margin')))) {
          layout_margin = Number(cthis.attr('data-margin'));
        }
        if (o.design_item_thumb_height === '') {
          o.design_item_thumb_height = 'proportional';
        }
        if (o.design_item_thumb_height === 'proportional') {
          cthis.addClass('wait-until-item-loaded-then-visible');
          cthis.addClass('items-depend-on-thumb-height');
        }
        if (o.outer_con_selector_con) {
          if (o.outer_con_selector_con.off) {} else {
            o.outer_con_selector_con = $(o.outer_con_selector_con);
          }
          _selectorCon = o.outer_con_selector_con;
        }
        nr_per_page = Number(o.settings_ajax_method_curritems_per_page);
        if (o.settings_ajax_method_curritems_per_page_initial) {
          nr_per_page = Number(o.settings_ajax_method_curritems_per_page_initial);
        }
        if (o.settings_mode === _constants.ZFOLIO_MODE.SCROLLER) {
          cthis.append('<div  class="contentscroller auto-init2 bullets-right animate-height " data-margin="30">  <div class="items"></div></div>');
          _contentScroller = cthis.find('.contentscroller');
          _contentScrollerItems = _contentScroller.find('.items');
        }
        cthis.addClass('dzszfl-inited');
        reinit({
          'call_from': 'init'
        });
        init_ready();
        var $theContentCon = (0, _zfolioHelpers.zfolioGetContentCon)(cthis);
        if ($theContentCon) {
          if ($theContentCon.hasClass('fullit')) {
            var gap = parseInt($theContentCon.find('.the-content').eq(0).attr('data-portfolio-gap'), 10);
            if (cthis.hasClass('skin-melbourne') || cthis.hasClass('skin-gazelia skin-gazelia--transparent')) {
              if (gap < 10) {
                gap = 10;
              }
            }
            if (isNaN(gap) === false) {
              $theContentCon.find('.translucent-layer').css({
                'padding': gap + 'px'
              });
              $theContentCon.find('.categories').eq(0).css({});
            }
          }
          $theContentCon.attr('data-filters-position', cthis.attr('data-filters-position'));
        }
        $(window).on('scroll.dzszfl_' + cid, handleScrollInner);
      }
      function handleScrollInner(e, pargs) {
        var st = $(window).scrollTop();
        var cthis_ot = cthis.offset().top;
        th = cthis.height();
        if (o.pagination_method === 'scroll') {
          if (st + wh > cthis_ot + th - 20) {
            canLoadNextImagesFromScroll = true;
            loadBatchImages({
              call_scroll: false,
              'call_from': 'handle_scroll_inner'
            });
          } else {
            canLoadNextImagesFromScroll = false;
          }
        }
      }
      function reinit(pargs) {
        var margs = {
          'call_from': 'default'
        };
        if (pargs) {
          margs = $.extend(margs, pargs);
        }
        if (margs.call_from === 'ajax_append_new_items') {
          view_canLoadImagesFromPrevLoaded = true;
          canLoadNextImagesFromScroll = true;
        }
        var i = 1;
        i_dzscol_ind = 0;
        modeNumberOfColumns = 0;
        arr_cats = [];
        $itemsCon.children('.zfolio-item:not(.inited)').each(function () {
          var $feedItem = $(this);
          $feedItem.addClass('dzs-layout-item');
          if ($feedItem.children().eq(0).hasClass('zfolio-item--inner') || $feedItem.children().eq(1).hasClass('zfolio-item--inner')) {} else {
            if ($feedItem.attr('data-link') && o.design_skin === 'skin-melbourne') {
              // -- for skin-melbourne

              let stringForMelbourne = '<a href="' + $feedItem.attr('data-link') + '" class="zfolio-item--inner ';
              if ($feedItem.attr('data-overlay_extra_class') && $feedItem.attr('data-overlay_extra_class').indexOf('content-opener') > -1) {
                stringForMelbourne += ' for-content-opener';
              }
              stringForMelbourne += 'custom-a "></a>';
              $feedItem.wrapInner(stringForMelbourne);
            } else {
              $feedItem.wrapInner('<div class="zfolio-item--inner"><div class="zfolio-item--inner--inner"><div class="zfolio-item--inner--inner--inner"></div></div></div>');
            }
          }
          if ($feedItem.attr('data-thumb')) {
            if (!$feedItem.attr('data-thumbnail')) {
              $feedItem.attr('data-thumbnail', $feedItem.attr('data-thumb'));
            }
          }
          var $itemInner = $feedItem.children('.zfolio-item--inner');
          var $itemInnerInner = $feedItem.find('.zfolio-item--inner--inner');
          var $itemInnerInnerInner = $feedItem.find('.zfolio-item--inner--inner--inner');
          if (o.item_inner_addid) {
            let stringId = '';
            if (cthis.attr('id')) {
              stringId += cthis.attr('id') + '-';
            }
            stringId += o.item_inner_addid + String(i);
            if (stringId) {
              $itemInner.attr('id', stringId);
            }
          }
          var stringStructTheFeatureCon = '';
          var isInsideAnchor = false;
          var item_link_thumb_con_to = o.item_link_thumb_con_to;
          if ($itemInner.get(0) && $itemInner.get(0).nodeName === 'A') {
            // -- todo: why ?
            if (item_link_thumb_con_to !== 'ultibox') {
              item_link_thumb_con_to = '';
            }
          }
          if (item_link_thumb_con_to === 'link') {
            isInsideAnchor = true;
          }
          stringStructTheFeatureCon = (0, _view_structureItemGenerateThumbCon.structureItemGenerateThumbCon)(item_link_thumb_con_to, $feedItem);
          if ($itemInner.find('.the-feature-con').length) {} else {
            $itemInnerInnerInner.prepend(stringStructTheFeatureCon);
          }
          if ($itemInnerInnerInner.find('.item-meta').length) {} else {
            var aux_struct_item_meta = '';
            if ($feedItem.find('.feed-zfolio-the-title').length || $feedItem.find('.feed-zfolio-the-desc').length) {
              aux_struct_item_meta = (0, _view_structureItemMeta.viewGenerateStructureItemMeta)($feedItem);
            }
            if (aux_struct_item_meta) {
              $itemInnerInnerInner.append(aux_struct_item_meta);
            }
          }
          const $thumbCon = $itemInner.find('.the-feature-con').eq(0);
          if (item_link_thumb_con_to === 'ultibox') {
            if ($itemInner.find('.feed-rst').length) {
              $thumbCon.append('<div class="feed-ultibox feed-ultibox-desc"><div class="rst-big-desc">' + $itemInner.find('.feed-rst').eq(0).html() + '</div></div>');
            }
          }
          if (item_link_thumb_con_to === 'ultibox') {}
          if (o.design_skin === 'skin-alba') {
            $itemInnerInner.append('<figure class="line1"></figure><figure class="line1"></figure><figure class="line1"></figure><figure class="line1"></figure>');
          }
          if (o.design_item_thumb_just_use_img === 'on') {
            let stringTheFeatureImage = '<img class="the-feature';
            if (o.use_scroll_lazyloading_for_images === 'on') {
              stringTheFeatureImage += ' lazyloading-transition-fade set-height-auto-after-load" data-src="' + $feedItem.attr('data-thumbnail') + '"';
              stringTheFeatureImage += '" style="height:300px; display:block;"';
            } else {
              stringTheFeatureImage += '" src="' + $feedItem.attr('data-thumbnail') + '"';
            }
            stringTheFeatureImage += '/>';
            $thumbCon.prependOnce(stringTheFeatureImage);
            $thumbCon.addClass('auto-height');
          } else {
            $thumbCon.prependOnce('<div class="the-feature the-feature-div" style="background-image: url(' + $feedItem.attr('data-thumbnail') + ');"></div>');
          }
          if (!$feedItem.attr('data-thumbnail')) {
            $thumbCon.addClass('no-preset-thumbnail');
          }
          if ($itemInner.find('.feed-zfolio-append-feature-con').length) {
            $thumbCon.append($itemInner.find('.feed-zfolio-append-feature-con').eq(0));
          }
          $itemInner.find('.the-feature-con');
          let overlay_extra_class = '';
          if ($feedItem.attr('data-overlay_extra_class')) {
            overlay_extra_class += ' ' + $feedItem.attr('data-overlay_extra_class');
          }
          let stringOverlayStruct = (0, _view_structureGenerateOverlay.structureGenerateOverlay)(isInsideAnchor, overlay_extra_class, $feedItem.attr('data-link'), o.design_skin, $feedItem);
          $itemInner.find('.the-feature-con').eq(0).appendOnce(stringOverlayStruct);
          if (o.design_skin === 'skin-gazelia') {
            if ($feedItem.children('.the-overlay-anchor').length > 0) {
              $feedItem.find('.the-overlay').eq(0).append($feedItem.children('.the-overlay-anchor').eq(0));
            }
          }
          if (o.design_skin === 'skin-lazarus') {
            if ($feedItem.children('.the-overlay-anchor').length > 0) {
              $feedItem.find('.the-overlay').eq(0).append($feedItem.children('.the-overlay-anchor').eq(0));
            }
          }
          if (o.design_skin === 'skin-material') {
            let $itemMeta = $feedItem.find('.item-meta').eq(0);
            let $theFeatureCon = $feedItem.find('.the-feature-con').eq(0);
            if ($itemMeta.length) {
              $theFeatureCon.before($itemMeta.find('.the-title'));
            }
          }
          if (!isDesignHeightDynamic && design_item_thumb_height > 0) {}
          $feedItem.addClass(o.item_extra_class);
          $feedItem.addClass('inited');
          $feedItem.attr('data-sort', $itemsCon.children('.zfolio-item').index($feedItem) * 10);

          // -- deprecated

          let catsString = $feedItem.attr('data-category');
          if (catsString) {
            (0, _categoryHelpers.zfolioCategoriesCalculateCatsArray)(catsString, $feedItem, arr_cats);
          }
          // -- deprecated END

          var the_cats_regex = /termid-(\d+)/ig;
          var aux = null;
          while (aux = the_cats_regex.exec($feedItem.attr('class'))) {
            var the_cat = aux[1];
            isAlreadyAdded = false;
            for (let k = 0; k < arr_cats.length; k++) {
              if (arr_cats[k] === the_cat) {
                isAlreadyAdded = true;
              }
            }
            if (!isAlreadyAdded) {
              arr_cats.push(the_cat);
            }
            arr_cats_type = 'dataterm';
          }
          i++;
          if (o.design_item_thumb_height === 'proportional') {
            let imageToBeLoaded = {
              'image': $feedItem.attr('data-thumbnail'),
              'item': $feedItem,
              'loaded': "off"
            };
            images_toBeLoaded.push(imageToBeLoaded);
          }
          if (o.design_item_thumb_height === 1) {
            let imageToBeLoaded = {
              'image': $feedItem.attr('data-thumbnail'),
              'item': $feedItem,
              'loaded': "off"
            };
            images_toBeLoaded.push(imageToBeLoaded);
          }
          if (o.settings_mode === _constants.ZFOLIO_MODE.SCROLLER) {
            $feedItem.addClass('csc-item');
            _contentScrollerItems.append($feedItem);
          }
        });
        if (o.settings_mode === _constants.ZFOLIO_MODE.SCROLLER) {
          if (window.dzscsc_init) {
            if (o.scroller_settings.per_row === 'default') {
              if (cthis.hasClass('dzs-layout--5-cols')) {
                o.scroller_settings.per_row = '5';
              }
              if (cthis.hasClass('dzs-layout--3-cols')) {
                o.scroller_settings.per_row = '3';
              }
              if (cthis.hasClass('dzs-layout--1-cols')) {
                o.scroller_settings.per_row = '1';
              }
            }
            if (o.scroller_settings.per_row === 'default') {
              o.scroller_settings.per_row = '4';
            }
            if (o.scroller_settings.per_row === '1') {
              o.scroller_settings.settings_onlyone = 'on';
            }
            dzscsc_init(_contentScroller, o.scroller_settings);
          }
        }

        // -- still reinit()

        if (o.outer_con_selector_con) {
          if (o.outer_con_selector_con.off) {} else {
            o.outer_con_selector_con = $(o.outer_con_selector_con);
          }
          _selectorCon = o.outer_con_selector_con;
        }
        if (arr_cats.length > 1 && cthis.find('.selector-con').length === 0) {
          var aux = '<div class="selector-con ' + o.selector_con_skin + '"><div class="categories">';
          aux += '</div></div>';
          if (o.outer_con_selector_con) {
            _selectorCon = o.outer_con_selector_con;
          } else {
            $itemsCon.before(aux);
            _selectorCon = cthis.find('.selector-con').eq(0);
          }
        } else {
          if (o.outer_con_selector_con) {
            _selectorCon = $(o.outer_con_selector_con);
            _selectorCon.addClass('empty-categories');
          }
        }
        if (_selectorCon && _selectorCon.children) {
          categories_parent = _selectorCon.children('.categories');
          if (categories_parent.find('.a-category').length) {
            if (o.selector_con_generate_categories === 'auto') {
              o.selector_con_generate_categories = 'off';
            }
          }
          if (o.selector_con_generate_categories === 'auto') {
            o.selector_con_generate_categories = 'on';
          }
          if (o.filters_enable === 'off') {
            _selectorCon.hide();
          }
          if (cthis.find('.feed-zfolio-zfolio-term').length) {
            arr_cats = [];
            cthis.find('.feed-zfolio-zfolio-term').each(function () {
              const _t = $(this);
              arr_cats.push(_t.attr('data-termid'));
              cthis.addClass('has-filters');
            });
          }
          if (o.selector_con_generate_categories === 'off') {} else {
            (0, _categoryHelpers.zfolioCategoriesGenerate)(categories_parent, o, _selectorCon, arr_cats, cthis, arr_cats_type, cid);
          }
        }
        images = $itemsCon.children();
        if (o.design_item_thumb_height === 'proportional') {
          loadBatchImages({
            call_scroll: false,
            'call_from': 'reinit'
          });
        }
      }
      function init_ready() {
        if (o.settings_mode === 'isotope') {
          if (o.design_item_thumb_height !== 'proportional') {
            $itemsCon.children('*:not(.grid-sizer):not(.gutter-sizer)').addClass(_constants.VIEW_ISOTOPE_ITEM_CLASS);
          }
          $itemsCon.prepend('<div class="grid-sizer"></div>');
          if (cthis.hasClass('dzs-layout--5-cols')) {}
          checkIfIsotopeIsReady().then(init_isotope);
        }
        if (o.pagination_selector) {
          o.pagination_selector = $(o.pagination_selector);
          _paginationCon = o.pagination_selector;
        }
        if (_selectorCon) {
          _selectorCon.off('click', '.a-category');
          _selectorCon.on('click', '.a-category', handle_mouse);
        }
        if (o.outer_con_search_con) {
          var _c = o.outer_con_search_con;
          _c.on('keyup', handle_key);
        }
        if (_paginationCon) {
          _paginationCon.find('a').addClass('dzszfl-pagination-a custom-a');
          _paginationCon.on('click', 'a', handle_mouse);
        }
        cthis.on('click', '.content-opener', click_contentOpener);
        cthis.get(0).api_destroy = destroy;
        cthis.get(0).api_handle_resize = handle_resize;
        cthis.get(0).api_destroy_listeners = destroy_listeners;
        cthis.get(0).api_relayout_isotope = function () {
          if (inter_relayout_allow) {
            clearTimeout(inter_relayout);
            inter_relayout = setTimeout(calculate_dims_only_relayout, 500);
          }
        };
        setTimeout(function () {
          inter_relayout_allow = true;
        }, 2500);
        handle_resize();
        $(window).on('resize.dzszfl_' + cid, handle_resize);
        if (o.settings_defaultCat === '') {
          if ((0, _dzs_helpers.get_query_arg)(window.location.href, 'dzsp_defCategory_' + cid)) {
            const ind = (0, _dzs_helpers.get_query_arg)(window.location.href, 'dzsp_defCategory_' + cid);
            if (categories_parent) {
              categories_parent.children().eq(ind).trigger('click');
            }
          }
        } else {
          goto_category(o.settings_defaultCat, {
            'class_name': 'termid'
          });
        }
        setTimeout(function () {
          const calculateDimsArgs = {
            'parse_items': false,
            'relayout_isotope': true,
            'disable_easing_on_isotope_transiton': false
          };
          calculate_dims(calculateDimsArgs);
        }, 2500);
        if (o.design_item_height === 0) {
          setTimeout(function () {
            init_allready();
          }, 1200);
        } else {
          setTimeout(function () {
            init_allready();
          }, 1200);
        }
        if (o.settings_mode === _constants.ZFOLIO_MODE.SCROLLER) {
          setTimeout(function () {
            cthis.addClass('inited-scroller-ready');
          }, 1000);
        }
      }
      function checkIfIsotopeIsReady() {
        return new Promise((resolve, reject) => {
          let checkInterval = setInterval(() => {
            let isIsotopeReady = false;
            let isPackeryReady = true;
            if (o.settings_isotope_settings.layoutMode === 'packery') {
              isPackeryReady = !!$.fn.packery;
            }
            if ($.fn.isotope) {
              isIsotopeReady = true;
            }
            if (isIsotopeReady && isPackeryReady) {
              clearInterval(checkInterval);
              resolve();
            }
          }, 30);
        });
      }
      function init_isotope() {
        let isotopeSettings = {};
        isotopeSettings = $.extend(isotopeSettings, o.settings_isotope_settings);
        isotopeSettings.transitionDuration = '0s';
        if (cthis.hasClass('dzs-layout--3-cols')) {
          isotopeSettings.percent_amount = 33.3333;
        }
        if (cthis.hasClass('dzs-layout--4-cols')) {
          isotopeSettings.percent_amount = 25;
        }
        if (cthis.hasClass('dzs-layout--6-cols')) {
          isotopeSettings.percent_amount = 16.6666;
        }
        $itemsCon.isotope(isotopeSettings);
        $itemsCon.addClass('isotoped');
        $itemsCon.children('.' + _constants.VIEW_ISOTOPE_ITEM_CLASS).addClass('isotoped-item');
        inter_set_transition_duration = setTimeout(function () {
          isotopeSettings.transitionDuration = '0.3s';
          isotopeSettings.transitionDuration = '0.4s';
          $itemsCon.isotope(isotopeSettings);
          cthis.addClass('dzszfl-ready-for-transitions');
        }, 4000);
      }

      /**
       *
       */
      function init_allready() {
        if ($itemsCon.css('opacity') === '0') {}
        cthis.removeClass('set-height-when-final');
        cthis.addClass('dzszfl-ready');
        $itemsCon.on('removeComplete', function (event, removedItems) {
          console.log('Removed ' + removedItems.length + ' items');
        });
      }
      function categoriesHandleClickAllCategories(e) {
        const $t = $(this);
        console.log($t);
      }
      function handleImageLoaded(e) {
        var $img_ = this;
        if (e.type === 'error') {
          images_toBeLoaded[$img_.indexinarr].loaded = "error";
        } else {
          images_toBeLoaded[$img_.indexinarr].loaded = "on";
        }
        const $zfolioItem = $img_.item;
        const nw = $img_.naturalWidth;
        const nh = $img_.naturalHeight;
        const perc = Number(nw / nh).toFixed(3);
        $zfolioItem.find('.the-feature').attr('naturalwidth', nw);
        $zfolioItem.find('.the-feature').attr('naturalheight', nh);
        $zfolioItem.find('.the-feature').attr('n_perc', perc);
        $zfolioItem.find('.the-feature').css('padding-top', 1 / perc * 100 + '%');
        $zfolioItem.addClass('padding-set');
        view_checkIfItemsCanShow({
          'call_from': 'image_onload',
          'call_scroll': false
        });
      }
      function loadBatchImages(pargs) {
        // -- lets see first if it needs loading :D

        var margs = {
          'call_scroll': true,
          'call_from': 'default'
        };
        if (pargs) {
          margs = $.extend(margs, pargs);
        }
        if (!(view_isFirstTimeLoadingImages || o.pagination_method === 'normal' && view_canLoadImagesFromPrevLoaded || o.pagination_method === 'scroll' && canLoadNextImagesFromScroll)) {
          return;
        }
        if (images_toBeLoaded && images_toBeLoaded.length) {
          let bufferPerPage = 0;
          for (let i in images_toBeLoaded) {
            if (images_toBeLoaded[i].loaded === "on" || images_toBeLoaded[i].loaded === "set" || images_toBeLoaded[i].loaded === "error") {
              continue;
            }
            const newImage = new Image();
            newImage.src = images_toBeLoaded[i].image;
            newImage.item = images_toBeLoaded[i].item;
            newImage.indexinarr = i;
            if (margs.call_from !== 'handle_scroll_inner') {}
            newImage.onload = handleImageLoaded;
            newImage.onerror = handleImageLoaded;
            images_toBeLoaded[i].loaded = "loading";
            bufferPerPage++;
            if (bufferPerPage >= nr_per_page && !cthis.hasClass('pagination-method-off')) {
              break;
            }
          }
          view_canLoadImagesFromPrevLoaded = false;
          canLoadNextImagesFromScroll = false;
          if (view_isFirstTimeLoadingImages) {
            nr_per_page = Number(o.settings_ajax_method_curritems_per_page);
          }
          view_isFirstTimeLoadingImages = false;
          setTimeout(function () {
            // -- it s not from here

            if (margs.call_scroll) {
              handleScrollInner(null, {});
            }
          }, 100);
        }
      }

      /**
       * call from image_onload
       * @param pargs
       */
      function view_checkIfItemsCanShow(pargs) {
        let margs = {
          'call_scroll': true,
          'call_from': 'default'
        };
        if (pargs) {
          margs = $.extend(margs, pargs);
        }

        // -- deprecated

        let isGoingToNextItems = true;
        for (let i in images_toBeLoaded) {
          if (images_toBeLoaded[i].loaded === "loading") {
            isGoingToNextItems = false;
          }
        }
        if (isGoingToNextItems) {
          $itemsCon.children().removeClass('new-item');
          for (let i in images_toBeLoaded) {
            if (images_toBeLoaded[i].loaded === "on" || cthis.hasClass('pagination-method-off')) {
              var $zfolioItem = images_toBeLoaded[i].item;
              $zfolioItem.addClass(_constants.VIEW_ISOTOPE_ITEM_CLASS);
              if (!$zfolioItem.hasClass('isotoped-item')) {
                $zfolioItem.addClass('new-item');
              }
              images_toBeLoaded[i].loaded = 'set';
              setTimeout(function ($zfolioItem$) {
                $zfolioItem$.addClass('loaded');
              }, 1000, $zfolioItem);
            }
          }
          let $newItems = $itemsCon.children('.new-item');
          checkIfIsotopeIsReady().then(() => {
            isotope_appendNewItems($newItems);
          });
          setTimeout(function () {
            $itemsCon.children().removeClass('new-item');
          }, 1000);
          calculate_dims_the_feature_div();
          view_canLoadImagesFromPrevLoaded = true;
          loadBatchImages({
            'call_from': 'check_if_items_can_show'
          });
          setTimeout(function () {
            calculate_dims_only_relayout();
          }, 500);
        }
      }
      function isotope_appendNewItems($newItems) {
        $itemsCon.isotope('appended', $newItems);
      }
      function fadeout_and_destroy_items() {
        view_isZfolioFaded = true;

        // -- new page

        const aux_scroll_pos = cthis.offset().top - 100;
        const $scrollerScrollTop = $('.scroller-con.type-scrollTop');
        if ($scrollerScrollTop.get(0) && $scrollerScrollTop.get(0).api_scrolly_to) {
          $scrollerScrollTop.get(0).api_scrolly_to(aux_scroll_pos);
        } else {
          $('html, body').css({
            scrollTop: aux_scroll_pos
          }, 300);
        }
        $itemsCon.css({
          'opacity': 0
        }, {
          queue: false,
          duration: 300,
          complete: function () {
            var _t = $(this);
            goto_category("*", {
              'call_from': 'fadeout_and_destroy_items'
            });
            destroy_items();
            view_isZfolioFaded = false;
          }
        });
        if (_selectorCon) {}
        if (_paginationCon) {}
      }
      function destroy_items() {
        if (o.settings_mode === 'isotope') {}
      }
      function ajax_append_new_items(arg, pargs) {
        var margs = {
          'call_from': "default"
        };
        if (pargs) {
          margs = $.extend(margs, pargs);
        }
        if (view_isZfolioFaded) {
          setTimeout(function () {
            ajax_append_new_items(arg);
          }, 500);
          return false;
        }
        $itemsCon.children('.zfolio-item').addClass('old-item');
        if (o.settings_mode === 'isotope') {
          $itemsCon.children('.zfolio-item.old-item').each(function () {
            var _t3 = $(this);
            $itemsCon.isotope('remove', _t3);
          });
          setTimeout(function () {}, 10);
          setTimeout(function () {}, 300);
        }
        setTimeout(function () {
          $itemsCon.append(arg);
          if (o.settings_mode === 'isotope') {
            $itemsCon.children('*:not(.grid-sizer):not(.gutter-sizer)').addClass(_constants.VIEW_ISOTOPE_ITEM_CLASS);
            var _newitems = $itemsCon.children('*:not(.grid-sizer):not(.gutter-sizer):not(.old-item)');
            if (cthis.hasClass('thumbnail-height-mode-normal')) {
              $itemsCon.isotope('appended', _newitems);
            }
            setTimeout(function () {
              calculate_dims({
                'parse_items': true,
                'relayout_isotope': true,
                'disable_easing_on_isotope_transiton': false
              });
              $itemsCon.isotope('layout');
            }, 500);
            setTimeout(function () {}, 1500);
          }
          reinit({
            'call_from': 'ajax_append_new_items'
          });
          setTimeout(function () {
            if (cthis.hasClass('thumbnail-height-mode-normal')) {}
            calculate_dims();
          });
          setTimeout(function () {
            $itemsCon.css({
              'opacity': 1
            }, {
              queue: false,
              duration: 300,
              complete: function () {}
            });
            if (_selectorCon) {
              _selectorCon.css({
                'opacity': 1
              }, {
                queue: false,
                duration: 300
              });
            }
            if (_paginationCon) {
              _paginationCon.css({
                'opacity': 1
              }, {
                queue: false,
                duration: 300
              });
            }
          }, 1000);
        }, 300);
      }
      function destroy_listeners() {
        if (o.settings_mode === 'isotope' && $.fn.isotope) {
          try {
            $itemsCon.isotope('destroy');
          } catch (err) {}
        }
        cthis.off('click');
        $(window).off('resize.dzszfl_' + cid);
        $(window).off('scroll.dzszfl_' + cid);
        isDestroyed = true;
      }
      function destroy() {
        if (o.settings_mode === 'isotope' && $.fn.isotope) {
          try {} catch (err) {}
        }
        isDestroyed = true;
      }
      function handle_key(e) {
        var _t = $(this);
        if (e.type === 'keyup') {
          if (o.settings_mode === 'isotope') {
            $itemsCon.isotope({
              filter: function () {
                var name = $(this).find('.item-meta').eq(0).text();
                var ok = false;
                var regex = new RegExp('.*' + _t.val() + '.*');
                if (name.match(regex)) {
                  ok = true;
                }
                return ok;
              }
            });
          }
          if (o.settings_mode === 'simple') {
            $itemsCon.children().fadeOut('fast');
            $itemsCon.children(value).fadeIn('fast');
          }
        }
      }
      function handle_mouse(e) {
        var _t = $(this);
        if (e.type === 'click') {
          if (_t.hasClass('a-category')) {
            var isReturningFalse = (0, _categoryHelpers.viewFilterCategory)(_t, cthis, $itemsCon, _selectorCon, o, goto_category);
            if (_t.hasClass('category-all-selector')) {}
            if (isReturningFalse) {
              if (_selectorCon.hasClass("selector-responsive-mode")) {
                _selectorCon.toggleClass('selector-responsive-mode--is-expanded');
              }
              return false;
            }
          }
          if (_t.hasClass('dzszfl-pagination-a')) {
            if (_t.hasClass('active') || _t.parent().hasClass('active') || _t.attr('href') === '#') {
              return false;
            }
            fadeout_and_destroy_items();
            $.ajax({
              url: _t.attr('href'),
              context: document.body
            }).done(function (response) {
              ajax_append_new_items(response, {
                'call_from': 'ajax-pagination'
              });
            });
            _t.parent().parent().find('.active').removeClass('active');
            _t.parent().addClass('active');
            return false;
          }
        }
      }
      function handle_resize(e, pargs) {
        var margs = {
          calculate_dims_init: true,
          calculate_excerpt_con: true,
          excerpt_con_noanimation: true
        };
        if (pargs) {
          margs = $.extend(margs, pargs);
        }
        ww = window.innerWidth;
        wh = window.innerHeight;
        tw = cthis.width();
        if (ww <= 520) {
          cthis.addClass('under-520');
        } else {
          cthis.removeClass('under-520');
        }
        if (ww <= 720) {
          cthis.addClass('under-720');
          if (_selectorCon) {
            _selectorCon.addClass('selector-responsive-mode');
          }
          if (o.outer_con_selector_con) {
            o.outer_con_selector_con.addClass('under-720');
          }
        } else {
          cthis.removeClass('under-720');
          if (_selectorCon) {
            _selectorCon.removeClass('selector-responsive-mode');
          }
          if (o.outer_con_selector_con) {
            o.outer_con_selector_con.removeClass('under-720');
          }
        }
        if (ww <= 1000) {
          cthis.addClass('under-1000');
          if (o.outer_con_selector_con) {
            o.outer_con_selector_con.addClass('under-1000');
          }
        } else {
          cthis.removeClass('under-1000');
          if (o.outer_con_selector_con) {
            o.outer_con_selector_con.removeClass('under-1000');
          }
        }
        if (margs.calculate_excerpt_con) {
          if (_excerptContent_initialPortItem) {
            if (o.excerpt_con_resize_videos === 'on') {
              excerpt_content_resize_vplayer();
            }
          }
          setTimeout(function () {
            if (_excerptContent_initialPortItem) {
              _tcon_content.css({
                'padding': _excerptContent.css('padding-top'),
                'width': cthis.outerWidth()
              });
              var auxh = 0;
              if (o.excerpt_con_transition === 'zoom') {
                _excerptContentCon.css({});
                auxh = _tcon_content.outerHeight();
                if (_excerptContent.find('.advancedscroller').length > 0) {
                  auxh -= 1;
                }
                _excerptContent.css({
                  'height': auxh
                });
              } else {
                auxh = _tcon_content.outerHeight();
                if (margs.excerpt_con_noanimation) {
                  auxh = _excerptContent.children('.dzs-colcontainer').outerHeight();
                  if (_excerptContent.find('.advancedscroller').length > 0) {
                    auxh -= 1;
                  }
                  _excerptContent.css({
                    'height': auxh
                  });
                  _excerptContentCon.css({
                    'height': ''
                  });
                }
              }
            }
          }, 500);
        }
        if (margs.calculate_dims_init) {
          if (inter_calculate_dims) {
            clearTimeout(inter_calculate_dims);
          }
          inter_calculate_dims = setTimeout(calculate_dims, 300);
        }
      }
      function calculate_dims_the_feature_div() {}
      function excerpt_content_resize_vplayer() {
        if (_excerptContent && _excerptContent.find('.vplayer').length > 0) {
          var _c = _excerptContent.find('.vplayer').eq(0);
          var auxr = Number(o.excerpt_con_responsive_ratio);
          var excerpt_width = _excerptContent.width();
          if (_c.parent().hasClass('dzs-col-8')) {
            auxr *= 2 / 3;
            if (o.excerpt_con_responsive_ratio === 810) {
              auxr = 580;
              if (ww <= 1000) {}
            }
          }
          var auxih = 0;
          if (_c.data('initial-height-for-excerpt-content')) {
            auxih = Number(_c.data('initial-height-for-excerpt-content'));
          } else {
            auxih = _constants.VIEW_ALLEGED_16_9_RATIO * _excerptContent.width();
            if (_c.parent().hasClass('dzs-col-8')) {
              auxih = _constants.VIEW_ALLEGED_16_9_RATIO * _c.parent().width();
            }
            _c.data('initial-height-for-excerpt-content', auxih);
          }
          var aux_ratio = auxih / auxr;
          _c.height(auxih);
        }
      }
      function calculate_dims(pargs) {
        var margs = {
          'parse_items': true,
          'relayout_isotope': true,
          'disable_easing_on_isotope_transiton': false
        };
        var registered_heights = [];
        var registered_end_pos = [];
        var sw_recheck_at_end = false; // -- recheck all item so that they align with the others nicely

        if (pargs) {
          margs = $.extend(margs, pargs);
        }
        if (isDestroyed) {
          return;
        }
        th = cthis.height();
        wh = window.innerHeight;
        var breaker = 20;
        if (margs.parse_items) {
          i_dzscol_ind = 0;
          modeNumberOfColumns = 0;
          $itemsArray = $itemsCon.children('.zfolio-item');
          if (o.settings_mode === _constants.ZFOLIO_MODE.SCROLLER) {
            $itemsArray = _contentScroller.find('.thumbsCon').children('.zfolio-item');
          }
          $itemsArray.each(function () {
            var _t = $(this);
            var aux_iw = -1;
            var aux_tw = cthis.width();
            if ($itemsCon.css('margin-left') === '0px') {
              design_item_padding = 0;
            }
            if ($itemsCon.css('margin-left') === '-1px') {
              design_item_padding = 2;
            }
            if ($itemsCon.css('margin-left') === '-10px') {
              design_item_padding = 20;
            }
            if ($itemsCon.css('margin-left') === '-15px') {
              design_item_padding = 30;
            }
            var temp_layout = '';
            cthis.removeClass(class_all_temp_cols);
            var aux_grid_response = {};
            aux_grid_response = generate_grid_response(cthis.attr('class'));
            aux_iw = aux_grid_response.aux_iw;
            grid_unit_px = aux_grid_response.grid_unit_px;
            initial_cols_before_fallback = aux_grid_response.nr_cols;
            cthis.removeClass('temp-dzs-layout--2-cols');
            if (cthis.hasClass('dzs-layout--6-cols')) {
              cthis.attr('data-nr-cols', '6');
              if (cthis.hasClass('under-1000')) {}
              if (cthis.hasClass('under-720')) {
                aux_iw = Number(100 / 2).toFixed(3);
                grid_unit_px = tw / 2;
                temp_layout = 'temp-dzs-layout--2-cols';
                cthis.attr('data-nr-cols', '2');
              } else {}
              if (cthis.hasClass('under-520')) {
                aux_iw = Number(100 / 1).toFixed(3);
                grid_unit_px = tw;
              }
            }
            if (cthis.hasClass('dzs-layout--5-cols')) {
              cthis.attr('data-nr-cols', '5');
              if (cthis.hasClass('under-1000')) {}
              if (cthis.hasClass('under-720')) {
                aux_iw = Number(100 / 2).toFixed(3);
                grid_unit_px = tw / 2;
                temp_layout = 'temp-dzs-layout--2-cols';
                cthis.attr('data-nr-cols', '2');
              } else {}
              if (cthis.hasClass('under-520')) {
                aux_iw = Number(100 / 1).toFixed(3);
                grid_unit_px = tw;
                cthis.attr('data-nr-cols', '1');
              }
            }
            if (cthis.hasClass('dzs-layout--4-cols')) {
              cthis.attr('data-nr-cols', '4');
              if (ww < 720) {
                aux_iw = Number(100 / 2).toFixed(3);
                grid_unit_px = tw / 2;
                temp_layout = 'temp-dzs-layout--2-cols';
                cthis.attr('data-nr-cols', '2');
              } else {}
              if (ww < 520) {
                aux_iw = Number(100 / 1).toFixed(3);
                grid_unit_px = tw / 1;
                temp_layout = 'temp-dzs-layout--1-cols';
                cthis.attr('data-nr-cols', '1');
              }
            }
            if (cthis.hasClass('dzs-layout--3-cols')) {
              cthis.attr('data-nr-cols', '3');
              if (cthis.hasClass('under-720')) {
                aux_iw = Number(100 / 2).toFixed(3);
                grid_unit_px = tw / 2;
                temp_layout = 'temp-dzs-layout--2-cols';
                cthis.attr('data-nr-cols', '2');
              } else {}
              if (cthis.hasClass('under-520')) {
                aux_iw = parseInt(aux_tw, 10);
                aux_iw = Number(100 / 1).toFixed(3);
                grid_unit_px = tw / 1;
                temp_layout = 'temp-dzs-layout--1-cols';
                cthis.attr('data-nr-cols', '1');
              }
            }
            if (cthis.hasClass('dzs-layout--2-cols')) {
              if (cthis.hasClass('under-720')) {}
              if (cthis.hasClass('under-520')) {
                aux_iw = parseInt(aux_tw, 10);
                aux_iw = Number(100 / 1).toFixed(3);
                grid_unit_px = tw;
                temp_layout = 'temp-dzs-layout--1-cols';
                cthis.attr('data-nr-cols', '1');
              }
            }
            if (ww <= 920) {
              if (o.responsive_fallback_tablet) {
                aux_grid_response = generate_grid_response(' ' + o.responsive_fallback_tablet);
                aux_iw = aux_grid_response.aux_iw;
                grid_unit_px = aux_grid_response.grid_unit_px;
                temp_layout = 'temp-' + o.responsive_fallback_tablet;
                var aux = /dzs-layout--(\d)-cols/g.exec(o.responsive_fallback_tablet);
                if (aux && aux[1]) {
                  cthis.attr('data-nr-cols', aux[1]);
                }
              }
              if (ww <= 620) {
                if (o.responsive_fallback_mobile) {
                  aux_grid_response = generate_grid_response(' ' + o.responsive_fallback_mobile);
                  aux_iw = aux_grid_response.aux_iw;
                  grid_unit_px = aux_grid_response.grid_unit_px;
                  temp_layout = 'temp-' + o.responsive_fallback_mobile;
                  var aux = /dzs-layout--(\d)-cols/g.exec(o.responsive_fallback_mobile);
                  if (aux && aux[1]) {
                    cthis.attr('data-nr-cols', aux[1]);
                  }
                }
              }
            } else {
              if (o.responsive_fallback_tablet || o.responsive_fallback_mobile) {
                aux_grid_response = generate_grid_response(cthis.attr('class'));
                aux_iw = aux_grid_response.aux_iw;
                grid_unit_px = aux_grid_response.grid_unit_px;
              }
            }
            cthis.addClass(temp_layout);
            grid_unit_px = parseInt(grid_unit_px, 10);
            if (o.settings_mode !== _constants.ZFOLIO_MODE.SCROLLER) {
              _t.css('width', '');
            }
            if (o.settings_set_forced_width === 'on') {
              if (o.settings_mode !== _constants.ZFOLIO_MODE.SCROLLER) {
                _t.outerWidth(aux_iw);
              }
            }

            // -- dynamic SCALING

            if (isDesignHeightDynamic) {
              sw_recheck_at_end = true;
              if (design_item_thumb_height <= 2) {
                var wexpand = 1;
                var hexpand = 1;
                if (_t.attr('data-hexpand')) {
                  hexpand = Number(_t.attr('data-hexpand'));
                }
                if (_t.attr('data-wexpand')) {
                  wexpand = Number(_t.attr('data-wexpand'));
                }
                var orig_hexpand = hexpand;
                hexpand = hexpand / wexpand;
                var finalw = wexpand * aux_iw;
                if (Number(finalw) > 99) {
                  finalw = 100;
                }
                if (aux_iw > 0) {
                  if (o.settings_mode !== _constants.ZFOLIO_MODE.SCROLLER) {
                    _t.css('width', finalw + '%');
                  }
                }
                if (_t.width() < 300) {
                  _t.addClass('under-300');
                } else {
                  _t.removeClass('under-300');
                }
                var auxh = 0;
                auxh = hexpand * design_item_thumb_height * _t.width() + (hexpand - 1) * design_item_padding;
                for (let i2 in registered_heights) {
                  var ncach = registered_heights[i2];
                  if (auxh === ncach - 1 || auxh === ncach + 1) {
                    auxh = ncach;
                  }
                }
                if ($.inArray(auxh, registered_heights) > -1) {} else {
                  registered_heights.push(auxh);
                }

                // TODO: we dont need this anymore

                var view_isCustomLayout = false;
                if (cthis.hasClass('custom-layout')) {
                  view_isCustomLayout = true;
                }
                if (view_isCustomLayout) {
                  _t.find('.zfolio-item--inner').css({
                    'padding-top': hexpand * 100 + '%'
                  });
                  _t.addClass('padding-set');
                } else {
                  // -- this is it
                  _t.find('.the-feature-con').eq(0).css({
                    'padding-top': design_item_thumb_height * 100 + '%'
                  });
                  _t.addClass('padding-set');
                  if (o.design_skin === 'skin-silver') {}
                }
              }
            } else {}
          });
          if (sw_recheck_at_end) {}
          if (modeNumberOfColumns) {
            mode_cols_nr_of_cols = modeNumberOfColumns;
          }
        }
        if (margs.relayout_isotope) {
          if (margs.disable_easing_on_isotope_transiton) {
            var args = {};
            args = $.extend(args, o.settings_isotope_settings);
            args.transitionDuration = '0s';
            $itemsCon.isotope(args);
            $itemsCon.children('.' + _constants.VIEW_ISOTOPE_ITEM_CLASS).addClass('isotoped-item');
            setTimeout(function () {}, 500);
          } else {
            if (o.settings_mode === 'isotope') {
              if ($itemsCon && !isDestroyed) {
                $itemsCon.isotope('layout');
              }
            }
          }
          setTimeout(function () {
            if (o.settings_mode === 'isotope') {
              if ($itemsCon && !isDestroyed) {
                $itemsCon.isotope('layout');
              }
            }
          }, 500);
        }
        if (window.dzs_check_lazyloading_images) {
          window.dzs_check_lazyloading_images();
        }
      }
      function generate_grid_response(arg) {
        var regex = / dzs-layout--(.*?)-cols/g;
        var oout = {};
        var aux = regex.exec(arg);
        if (aux) {
          var nr_cols = parseInt(aux[1], 10);
          var aux_iw = Number(100 / nr_cols).toFixed(4);
          var grid_unit_px = tw / nr_cols;
          oout.nr_cols = nr_cols;
          oout.aux_iw = aux_iw;
          oout.grid_unit_px = grid_unit_px;
        }
        return oout;
      }
      function calculate_dims_only_relayout() {
        var args = {
          'parse_items': false,
          'relayout_isotope': true,
          'disable_easing_on_isotope_transiton': false
        };
        calculate_dims(args);
      }
      function goto_category(arg, args) {
        var margs = {
          'call_from': 'default',
          'class_name': 'cat'
        };
        if (args) {
          margs = $.extend(margs, args);
        }
        var options = {};
        var key = "filter";
        var value = '.' + margs.class_name + '-' + arg;
        if (!arg || arg === "*" || arg === o.settings_categories_strall) {
          value = "*";
        }
        if (cat_curr === value) {
          return false;
        }
        cat_curr = value;
        if (categories_parent) {
          categories_parent.children().removeClass('active');
          categories_parent.children().each(function () {
            var _t = $(this);
            if (_t.text() === arg) {
              _t.addClass('active');
            }
            if (margs.class_name === 'termid') {
              if (_t.attr('data-termid') === arg) {
                _t.addClass('active');
              }
            }
          });
        }
        value = value === "false" ? false : value;
        value = value.replace(/ /gi, '-');
        if (o.settings_mode === _constants.ZFOLIO_MODE.SCROLLER) {
          if ($itemsArray) {
            $itemsArray.each(function () {
              var _t = $(this);
              if (value === '*') {
                _t.removeClass('filtered-out');
              } else {
                if (_t.hasClass(value.replace('.', ''))) {
                  _t.removeClass('filtered-out');
                } else {
                  _t.addClass('filtered-out');
                }
              }
            });
          }
        }
        if (o.settings_mode === 'isotope') {
          o.settings_isotope_settings[key] = value;
          $itemsCon.isotope(o.settings_isotope_settings);
          setTimeout(function () {
            $itemsCon.isotope(o.settings_isotope_settings);
          }, 500);
        }
        if (o.settings_mode === 'simple') {
          $itemsCon.children().fadeOut('fast');
          $itemsCon.children(value).fadeIn('fast');
        }
      }
      function click_contentOpener(e) {
        var _t = $(this);
        var ind = -1;
        _excerptContent_initialPortItem = null;

        // --trial and error
        if (_t.parent().hasClass('zfolio-item')) {
          _excerptContent_initialPortItem = _t.parent();
        } else {
          if (_t.parent().parent().hasClass('zfolio-item')) {
            _excerptContent_initialPortItem = _t.parent().parent();
          } else {
            if (_t.parent().parent().parent().hasClass('zfolio-item')) {
              _excerptContent_initialPortItem = _t.parent().parent().parent();
            } else {
              if (_t.parent().parent().parent().parent().hasClass('zfolio-item')) {
                _excerptContent_initialPortItem = _t.parent().parent().parent().parent();
              } else {
                if (_t.parent().parent().parent().parent().parent().hasClass('zfolio-item')) {
                  _excerptContent_initialPortItem = _t.parent().parent().parent().parent().parent();
                }
              }
            }
          }
        }

        // -- no point in continuing if tcon is not found

        if (_excerptContent_initialPortItem == null) {
          return false;
        }
        if (_excerptContent_initialPortItem.hasClass('active')) {
          contentOpener_close();
          return false;
        } else {
          if (_excerptContentCon) {
            contentOpener_close();
            _excerptContent_initialPortItem.parent().children().removeClass('active');
            setTimeout(function () {
              _t.click();
            }, 750);
            return false;
          }
        }
        var tcon_y = _excerptContent_initialPortItem.offset().top;
        var sw = false;
        var _tcon_next = null;
        while (!sw) {
          if (_tcon_next) {
            _tcon_next = _tcon_next.next();
            ;
          } else {
            _tcon_next = _excerptContent_initialPortItem.next();
          }
          if (_tcon_next.hasClass('zfolio-item')) {
            if (_tcon_next.offset().top !== tcon_y) {
              sw = true;
              ind = _tcon_next.parent().children('.' + _constants.VIEW_ISOTOPE_ITEM_CLASS).index(_tcon_next);
            }
          } else {
            sw = true;
          }
        }
        var excerptContent_extraClasses = '';
        var portclass = _excerptContent_initialPortItem.attr('class');
        portclass += ' ';
        var aux_regex = /cat-\w+/gi;
        var aux_regex_a;
        while (aux_regex_a = aux_regex.exec(portclass)) {
          if (aux_regex_a) {
            excerptContent_extraClasses += ' ' + aux_regex_a[0];
          }
        }
        _tcon_content = _excerptContent_initialPortItem.find('.the-content');
        let aux_excerpt_content_con = '<div class="' + _constants.VIEW_ISOTOPE_ITEM_CLASS + ' excerpt-content-con' + excerptContent_extraClasses + ' transition-' + o.excerpt_con_transition + ' "';
        if (_excerptContent_initialPortItem) {
          let ind3 = Number(_excerptContent_initialPortItem.attr('data-sort'));
          aux_excerpt_content_con += ' data-sort="' + (ind3 + 1) + '"';
        }
        aux_excerpt_content_con += '><div class="' + _tcon_content.attr('class') + '" style="">' + _tcon_content.html() + '<div class="close-btn">x</div></div></div>';
        if (_tcon_next.length > 0) {
          // -- even if the-content div is display: none, the height can still be calculated

          _tcon_next.before(aux_excerpt_content_con);
        } else {
          $itemsCon.append(aux_excerpt_content_con);
        }
        _excerptContentCon = cthis.find('.excerpt-content-con').eq(0);
        _excerptContent = _excerptContentCon.find('.excerpt-content').eq(0);
        setTimeout(function () {
          if (_excerptContent.removeClass) {
            _excerptContent.removeClass('transitioning-in');
          }
        }, 800);
        window.dzszfl_execute_target = _excerptContent;
        _excerptContent.find('.toexecute').each(function () {
          var _t2 = $(this);
          var aux = _t2.html();
          if (!_t2.hasClass('executed')) {
            try {
              if (aux.indexOf('{') === 0) {
                var arr = JSON.parse(aux);
                if (arr.type === 'transform_slider_con') {
                  window.dzsas_init(window.dzszfl_execute_target.find(".slider-con .advancedscroller"), arr);
                }
                if (arr.type === 'item_excerpt_setup') {
                  window.dzsas_init(window.dzszfl_execute_target.find(".advancedscroller"), arr);
                }
              } else {
                eval(aux);
              }
            } catch (err) {
              console.info("ERROR PARSING", err, aux);
            }
            _t2.addClass('executed');
          }
        });
        if (_excerptContent.find('.advancedscroller').length === 0) {
          actually_open_it();
        } else {
          var inter_aux = setInterval(function () {
            if (_excerptContent) {
              if (_excerptContent && _excerptContent.find('.advancedscroller').eq(0).hasClass('loaded')) {
                actually_open_it();
                clearInterval(inter_aux);
              } else {}
            } else {}
          }, 100);
        }
        function actually_open_it() {
          var args = {
            calculate_dims_init: false,
            calculate_excerpt_con: true,
            excerpt_con_noanimation: false
          };
          _excerptContent.css({
            'height': 0
          });
          var delaytime = 100;
          if (o.excerpt_con_transition === 'wipe') {
            delaytime = 100;
          }
          handle_resize(null, {
            calculate_dims_init: true,
            calculate_excerpt_con: true,
            excerpt_con_noanimation: true
          });
          setTimeout(function () {
            if (_excerptContent.find('.advancedscroller').length > 0) {
              _excerptContent.find('.advancedscroller').each(function () {
                var _t = $(this);
                if (_t.get(0) && _t.get(0).api_force_resize) {
                  var args = {};
                  if (_t.attr('data-defaultheight')) {
                    args.calculate_auto_height_default_h = Number(_t.attr('data-defaultheight'));
                  }
                  _t.get(0).api_force_resize(null, args);
                }
              });
            }
            var auxh = _excerptContent.children('.dzs-colcontainer').outerHeight();
            var delaytime2 = 500;
            if (_excerptContent.find('.advancedscroller').length > 0) {
              auxh -= 2;
            }
            if (_tcon_content.hasClass('skin-qucreative')) {
              _excerptContent.css({
                'height': auxh
              });
              _excerptContentCon.css({
                'height': auxh
              });
              if (o.excerpt_con_transition === 'zoom') {
                _excerptContentCon.css({
                  'height': auxh
                });
                _excerptContent.css({
                  'height': auxh
                });
              } else {
                _excerptContentCon.css({
                  'height': auxh
                });
                _excerptContent.css({
                  'height': auxh
                });
                setTimeout(function () {
                  const $scrollerScrollTop = $('.scroller-con.type-scrollTop');
                  var aux = _excerptContent.offset().top - 100;
                  if ($scrollerScrollTop.get(0) && $scrollerScrollTop.get(0).api_scrolly_to) {
                    $scrollerScrollTop.get(0).api_scrolly_to(aux);
                  } else {
                    $('html, body').css({
                      scrollTop: aux
                    }, 300);
                  }
                }, 700);
              }
              excerpt_content_resize_vplayer();
            } else {
              _excerptContent.css({
                'height': _excerptContent.children('.dzs-colcontainer').outerHeight() + 40 * 2
              });
              _excerptContentCon.css({
                'height': _excerptContent.children('.dzs-colcontainer').outerHeight() + 40 * 2
              });
            }
            setTimeout(function () {
              _excerptContentCon.addClass('placed');
            }, delaytime2);
          }, delaytime);
          setTimeout(function () {
            var args = {
              calculate_dims_init: true,
              calculate_excerpt_con: true
            };
            handle_resize(null, args);
          }, 1000);
          _excerptContent.prepend('<style>#' + cid + '.dzsportfolio .excerpt-content:before{ left:' + (_excerptContent_initialPortItem.offset().left - cthis.offset().left + _excerptContent_initialPortItem.outerWidth() / 2 - 8) + 'px; }</style>');
          _excerptContent_initialPortItem.addClass('active');
          _excerptContent.find('.vplayer-tobe.auto-init-from-q').each(function () {
            var _t2 = $(this);
            if (window.dzsvp_init) {
              var args = {
                settings_youtube_usecustomskin: "off",
                init_each: true,
                controls_out_opacity: "1",
                controls_normal_opacity: "1",
                cueVideo: "off"
              };
              if (window.qucreative_options && window.qucreative_options.video_player_settings) {
                args = $.extend(args, window.qucreative_options.video_player_settings);
              }
              window.dzsvp_init(_t2, args);
            }
          });
          _excerptContent.find('.close-btn').bind('click', contentOpener_close);
          if (o.settings_mode === 'isotope' && $.fn.isotope) {
            delaytime = 300;
            if (o.excerpt_con_transition === 'wipe') {
              delaytime = 101;
            }
            setTimeout(function () {
              _excerptContentCon.addClass('isotoped');
              $itemsCon.isotope('insert', _excerptContentCon);
              $itemsCon.isotope('layout');
            }, delaytime);
          }
          delaytime = 700;
          if (o.excerpt_con_transition === 'wipe') {
            delaytime = 100;
          }
          setTimeout(function () {
            _excerptContent.addClass('placed');
          }, delaytime);
          setTimeout(function () {
            const $mainContainer = $('.main-container');
            // -- scroller resize
            if ($mainContainer.eq(0).get(0) && $mainContainer.eq(0).get(0).api_toggle_resize) {
              $mainContainer.eq(0).get(0).api_toggle_resize();
            }
          }, 300);
        }
        return false;
      }
      function contentOpener_close() {
        if (_excerptContentCon || _excerptContent) {
          _excerptContent.removeClass('placed');
          _excerptContent_initialPortItem.removeClass('active');
          if (o.excerpt_con_transition === 'wipe') {
            setTimeout(function () {
              _excerptContentCon.css({
                'height': 0,
                'margin-bottom': 0
              });
              $itemsCon.isotope('layout');
            }, 100);
            _excerptContent.css({
              'height': 0
            });
          }
          var delaytime = 700;
          if (o.excerpt_con_transition === 'wipe') {
            delaytime = 400;
          }
          setTimeout(function () {
            if (o.settings_mode === 'isotope' && $.fn.isotope) {
              $itemsCon.isotope('remove', _excerptContentCon);
            }
            if (_excerptContentCon) {
              _excerptContentCon.remove();
              _excerptContentCon = null;
            }
            _excerptContent_initialPortItem = null;
            _excerptContent = null;
            handle_resize();
          }, delaytime);
        }
        setTimeout(function () {
          const $mainContainer = $('.main-container');
          if ($mainContainer.eq(0).get(0) && $mainContainer.eq(0).get(0).api_toggle_resize) {
            $mainContainer.eq(0).get(0).api_toggle_resize();
          }
        }, 300);
      }
      return this;
    });
  };
  window.dzszfl_init = function (selector, settings) {
    $(selector).zfolio(settings);
  };
})(jQuery);
jQuery(document).ready(function ($) {
  dzszfl_init('.zfolio.auto-init');
});

},{"./config/_constants":1,"./config/_zfolioSettings":2,"./js_common/_dzs_helpers":3,"./js_zfolio/_categoryHelpers":4,"./js_zfolio/_zfolioHelpers":5,"./js_zfolio/structure/_view_structureGenerateOverlay":6,"./js_zfolio/structure/_view_structureItemGenerateThumbCon":7,"./js_zfolio/structure/_view_structureItemMeta":8}]},{},[9])


//# sourceMappingURL=zfolio.js.map