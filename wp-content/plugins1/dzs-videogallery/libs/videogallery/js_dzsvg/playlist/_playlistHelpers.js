import {
  getDataOrAttr,
} from "../_dzsvg_helpers";

import {get_query_arg, add_query_arg, is_mobile, is_touch_device, can_history_api} from '../../js_common/_dzs_helpers';

import {
  DEFAULT_MENU_ITEM_STRUCTURE,
  PLAYER_DEFAULT_RESPONSIVE_RATIO,
  VIEW_LAYOUT_BUILDER_FEED_CLASS
} from '../../configs/Constants';
import {stringUtilGetSkinFromClass} from "../../js_common/_dzs_helpers";
import {dzsvg_mode_wall_init} from "../../js_playlist/mode/_mode-wall";
import {VIDEO_GALLERY_MODES} from "../../configs/_playlistSettings";


export function playlistGotoItemHistoryChangeForNonLinks(margs, o, cid, arg, deeplinkGotoItemQueryParam = 'the-video') {

  var $ = jQuery;

  var deeplink_str = String(deeplinkGotoItemQueryParam).replace('{{galleryid}}', cid);
  if (!margs.ignore_linking && margs.called_from !== 'init') {
    var stateObj = {foo: "bar"};

    if ($('.videogallery').length === 1) {
      history.pushState(stateObj, null, add_query_arg(window.location.href, deeplink_str, (Number(arg) + 1)));
    } else {
      history.pushState(stateObj, null, add_query_arg(window.location.href, deeplink_str + '-' + cid, (Number(arg) + 1)));
    }
  }
}

/**
 * sanitize all options
 * @param selfClass
 * @param o
 */
export function playlist_initSetupInitial(selfClass, o) {


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

  if (is_mobile() && o.autoplayNext === 'on') {
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
    columnWidth: 1
    , containerStyle: {position: 'relative'}
    , isFitWidth: false
    , isAnimated: true
  };


  o.masonry_options = Object.assign(masonry_options_default, o.masonry_options);


  if (!can_history_api()) {
    o.settings_enable_linking = 'off';
  }

  const $feedLayoutBuilderItems = selfClass.cgallery.children('.'+VIEW_LAYOUT_BUILDER_FEED_CLASS);
  if ($feedLayoutBuilderItems.length) {
    selfClass.navigation_customStructure = $feedLayoutBuilderItems.eq(0).html();
  } else {
    selfClass.navigation_customStructure = DEFAULT_MENU_ITEM_STRUCTURE;
  }


  if (!selfClass.navigation_customStructure) {
    if (!o.design_skin) {
      o.design_skin = 'skin-default';
    }
  }


  if (o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR3D) {
    o.menu_position = 'none';
    o.nav_type = 'none';
    o.transition_type = 'rotator3d';
  }


  if (typeof o.videoplayersettings == 'string' && window.dzsvg_vpconfigs) {
    if (typeof window.dzsvg_vpconfigs[o.videoplayersettings] === 'object') {
      o.videoplayersettings = {...window.dzsvg_vpconfigs[o.videoplayersettings]};
    }
  }


  if (selfClass.cgallery.find('.feed-dzsvg--embedcode').length) {
    o.embedCode = selfClass.cgallery.find('.feed-dzsvg--embedcode').eq(0).html();
  }

  if(selfClass.cgallery.hasClass('view--disable-video-area')){
    selfClass.viewOptions.enableVideoArea = false;
  }



}

export function playlist_initialConfig(selfClass, o) {

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


  selfClass.deeplinkGotoItemQueryParam = (window.dzsvg_settings && (window.dzsvg_settings.deeplink_str)) ? String(window.dzsvg_settings.deeplink_str).replace('{{galleryid}}', selfClass.galleryComputedId) : 'the-video';


  if (is_touch_device()) {
    if (o.nav_type === 'scroller') {
      o.nav_type = 'thumbs';
    }
  }

  selfClass.cgallery.addClass('mode-' + o.settings_mode);
  selfClass.cgallery.addClass('nav-' + o.nav_type);

  var mainClass = '';

  if (typeof (selfClass.cgallery.attr('class')) == 'string') {
    mainClass = selfClass.cgallery.attr('class');
  } else {
    mainClass = selfClass.cgallery.get(0).className;
  }
  if (mainClass.indexOf('skin-') === -1) {
    selfClass.cgallery.addClass(o.design_skin);
  } else {
    o.design_skin = stringUtilGetSkinFromClass(mainClass);
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
    dzsvg_mode_wall_init(selfClass);
  }

}


export function playlist_inDzsTabsHandle(selfClass, margs) {
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
    if (_con.hasClass('active') || _con.hasClass('will-be-start-item')) {

    } else {
      selfClass.initOptions.autoplayFirstVideo = 'off';
    }
  }

}


/**
 * return .previewImg
 * @param _t
 * @returns {null|jQuery|undefined|*}
 */
export function playlist_navigation_getPreviewImg(_t) {

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

export function playlist_get_real_responsive_ratio(i, selfClass) {
  var $ = jQuery;
  var o = selfClass.initOptions;
  setTimeout(function (targetIndex) {
    var _cach = selfClass._sliderCon.children().eq(targetIndex);

    var src = _cach.data('dzsvg-curatedid-from-gallery');

    $.get(o.php_media_data_retriever + "?action=dzsvg_action_get_responsive_ratio&type=" + _cach.data('dzsvg-curatedtype-from-gallery') + "&source=" + src, function (data) {


      try {
        var json = JSON.parse(data);

        var rr = PLAYER_DEFAULT_RESPONSIVE_RATIO;

        if (json.height && json.width) {

          rr = json.height / json.width;
        }

        if (rr.toFixed(3) !== '0.563') {
          _cach.attr('data-responsive_ratio', rr.toFixed(3));
        }
        _cach.attr('data-responsive_ratio-not-known-for-sure', 'off');


        if (_cach.get(0) && _cach.get(0).api_get_responsive_ratio) {
          _cach.get(0).api_get_responsive_ratio({
            'reset_responsive_ratio': true
            , 'called_from': 'php_media_data_retriever'
          })

          setTimeout(function () {
            selfClass.handleResize_currVideo();
          }, 100);
        }
      } catch (err) {
        console.info('json parse error - ', data);
      }


    });
  }, 100, i)
}

/**
 * set player data
 * @param _cachmenuitem
 */
export function playlist_navigation_mode_one__set_players_data(_cachmenuitem) {

  var attr_arr = ['data-loop', 'data-sourcevp', 'data-source', 'data-videotitle', 'data-type'];

  var maxlen = attr_arr.length;
  var ci = 0;
  for (var i5 in attr_arr) {
    var lab4 = attr_arr[i5];

    var val = '';


    val = getDataOrAttr(_cachmenuitem, lab4)
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

export function playlistGotoItemHistoryChangeForLinks(ind_ajaxPage, o, cgallery, _currentTargetPlayer) {


  var $ = jQuery;
  // --- history API ajax cool stuff
  if (o.settings_enableHistory === 'on' && can_history_api()) {
    var stateObj = {foo: "bar"};
    history.pushState(stateObj, "Gallery Video", getDataOrAttr(_currentTargetPlayer, 'data-sourcevp'));

    $.ajax({
      url: getDataOrAttr(_currentTargetPlayer, 'data-sourcevp'),
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


export function detect_startItemBasedOnQueryAddress(deeplinkGotoItemQueryParam = '', cid = '') {

  if (get_query_arg(window.location.href, deeplinkGotoItemQueryParam) && jQuery('.videogallery').length === 1) {
    return Number(get_query_arg(window.location.href, deeplinkGotoItemQueryParam)) - 1;
  }
  if (get_query_arg(window.location.href, deeplinkGotoItemQueryParam + '-' + cid)) {
    return Number(get_query_arg(window.location.href, deeplinkGotoItemQueryParam + '-' + cid)) - 1;

  }

  return null;
}


export function navigation_detectClassesForPosition(menu_position, _mainNavigation, cgallery) {
  const classMenuMovement = (menu_position === 'right' || menu_position === 'left') ? 'menu-moves-vertically' : 'menu-moves-horizontally';
  const classesClearMenuLocations = 'menu-top menu-bottom menu-right menu-left';
  const classesNewMenuLocation = 'menu-' + menu_position + ' ' + classMenuMovement;

  _mainNavigation.removeClass(classesClearMenuLocations);
  _mainNavigation.addClass(classesNewMenuLocation);

  cgallery.removeClass(classesClearMenuLocations);
  cgallery.addClass(classesNewMenuLocation);
}

export function navigation_initScroller(_navMain) {

  var $ = jQuery;
  if ($ && $.fn && $.fn.scroller) {
    _navMain.scroller({
      'enable_easing': 'on'
    });
  }
}

export function assertVideoFromGalleryAutoplayStatus(currNr, o, cgallery) {
  var shouldVideoAutoplay = false;
  if (currNr === -1) {
    if (o.autoplayFirstVideo === 'on') {
      if ((cgallery.parent().parent().parent().hasClass('categories-videogallery')) || !!(cgallery.parent().parent().parent().hasClass('categories-videogallery') && !cgallery.parent().parent().hasClass('gallery-precon')) || !!(cgallery.parent().parent().parent().hasClass('categories-videogallery') && cgallery.parent().parent().hasClass('gallery-precon') && cgallery.parent().parent().hasClass('curr-gallery'))) {
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