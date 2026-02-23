'use strict';
import {getDefaultPlaylistSettings, VIDEO_GALLERY_MODES} from './configs/_playlistSettings';
import {
  assertVideoFromGalleryAutoplayStatus,
  detect_startItemBasedOnQueryAddress,
  navigation_initScroller,
  playlist_inDzsTabsHandle,
  playlist_initialConfig,
  playlist_initSetupInitial,
  playlist_navigation_getPreviewImg,
  playlistGotoItemHistoryChangeForLinks,
  playlistGotoItemHistoryChangeForNonLinks
} from "./js_dzsvg/playlist/_playlistHelpers";
import {
  convertPluginOptionsToFinalOptions,
  detect_videoTypeAndSourceForElement,
  dzsvgExtraWindowFunctions,
  fullscreen_status,
  init_navigationOuter,
  setup_videogalleryCategories,
} from "./js_dzsvg/_dzsvg_helpers";

import {get_query_arg, is_ios, is_touch_device, loadScriptIfItDoesNotExist,} from "./js_common/_dzs_helpers";
import {secondCon_initFunctions} from "./js_dzsvg/components/_second_con";
import {buildPlaylist} from "./js_dzsvg/playlist/_playlistBuilderFunctions";
import {dzsvg_mode_wall_reinitWallStructure} from "./js_playlist/mode/_mode-wall";
import {dzsvg_playlist_setupEmbedAndShareButtons,} from "./js_playlist/_playlistAuxiliaryButtons";
import {
  PLAYLIST_DEFAULT_TIMEOUT,
  PLAYLIST_HEIGHT_IS_WINDOW_MAX_OFFSET,
  PLAYLIST_VIEW_FULLSCREEN_CLASS
} from "./configs/Constants";
import {view_cssConvertForPx, view_setCssPropsForElement} from "./shared/_viewFunctions";
import {view_structureGenerateNavigation} from "./js_playlist/_viewPlaylistStructure";
import {playlist_calculateDims_totals} from "./js_playlist/view/_calculateDims";
import {playlist_pagination_scrollSetup} from "./js_playlist/view/_pagination";


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
    this.argOptions = {...argOptions};
    this.viewOptions = {
      enableVideoArea: true,
    }
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


    var cgallery = null
    ;
    var nrChildren = 0;
    //gallery dimensions
    var
      navWidth = 0 // the _navCon width
      , ww
      , heightWindow
      , last_height_for_videoheight = 0 // -- last responsive_ratio height known
    ;

    var isMenuMovementLocked = false;

    var inter_start_the_transition = null;


    let isMergeSocialIconsIntoOne = false; // -- merge all socials into one


    var currNr = -1
      , currNr_curr = -1 // current transitioning
      , nextNr = -1
      , prevNr = -1
      , last_arg = 0
    ;
    var $currVideoPlayer;


    var $galleryParent
      , $galleryCon
      , heightInitial = -1
    ;
    var conw = 0;


    var isBusyTransition = false
      , isTransitionStarted = false
    ;
    var isFirstPlayed = false
      , isMouseOver = false
      , isFirstTransition = false // -- first transition made
    ;


    var i = 0;


    var menuitem_width = 0
      , menuitem_height = 0


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

    playlist_initSetupInitial(selfClass, o);


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
        caller: null
        , 'called_from': 'default'
      }


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
        playlist_inDzsTabsHandle(selfClass, margs);
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


      if (is_touch_device()) {
        cgallery.addClass('is-touch');
      }


      if (o.settings_mode === VIDEO_GALLERY_MODES.WALL || o.settings_mode === VIDEO_GALLERY_MODES.VIDEOWALL) {
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


      playlist_initialConfig(selfClass, o);


      $('html').addClass('supports-translate');


      view_structureGenerateNavigation(selfClass);


      if (o.settings_mode === VIDEO_GALLERY_MODES.NORMAL || o.settings_mode === VIDEO_GALLERY_MODES.SLIDER) {
        reinit();
      }


      // -- wall END


      if (o.settings_mode === VIDEO_GALLERY_MODES.VIDEOWALL) {


        if (cgallery.parent().hasClass('videogallery-con')) {
          view_setCssPropsForElement(cgallery.parent(), {'width': 'auto', 'height': 'auto'})
        }
        view_setCssPropsForElement(cgallery, {'width': 'auto', 'height': 'auto'})


      }


      if (o.settings_mode === VIDEO_GALLERY_MODES.WALL || o.settings_mode === VIDEO_GALLERY_MODES.VIDEOWALL || o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR || o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR3D) {
        reinit({
          'called_from': 'init'
        });
      }


      if (window.dzsvg_settings && window.dzsvg_settings.merge_social_into_one === 'on') {
        isMergeSocialIconsIntoOne = true;
      }

      dzsvg_playlist_setupEmbedAndShareButtons(selfClass, o, isMergeSocialIconsIntoOne);


      if (o.nav_type === 'outer') {
        selfClass.$navigationItemsContainer.addClass(o.nav_type_outer_grid);
        selfClass.$navigationItemsContainer.children().addClass('dzs-layout-item');


        if (o.menuitem_width) {
          o.menuitem_width = '';
        }


        if (o.nav_type_outer_max_height) {
          const nto_mh = Number(o.nav_type_outer_max_height);


          view_setCssPropsForElement(selfClass.$navigationClippedContainer, {'max-height': view_cssConvertForPx(nto_mh)})
          selfClass.$navigationClippedContainer.addClass('scroller-con skin_apple inner-relative');
          selfClass.$navigationItemsContainer.addClass('inner');

          view_setCssPropsForElement(selfClass.$navigationClippedContainer, {
            'height': 'auto'
          })

          try_to_init_scroller();
        }
      }


      calculateDims({


        'called_from': 'init'
      });


      if (o.nav_type === 'scroller') {
        selfClass.$navigationClippedContainer.addClass('scroller-con skin_apple');
        selfClass.$navigationItemsContainer.addClass('inner');


        view_setCssPropsForElement(selfClass.$navigationClippedContainer, {
          'height': '100%'
        })

        // -- try scroller
        if ($.fn.scroller) {
          navigation_initScroller(selfClass.$navigationClippedContainer);
        } else {
          setTimeout(() => {
            navigation_initScroller(selfClass.$navigationClippedContainer);
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
        })
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
      if (o.settings_mode !== VIDEO_GALLERY_MODES.WALL && o.settings_mode !== VIDEO_GALLERY_MODES.VIDEOWALL) {

        selfClass.isGalleryLoaded = true;


        if (get_query_arg(window.location.href, 'dzsvg_startitem_' + selfClass.galleryComputedId)) {
          o.startItem = Number(get_query_arg(window.location.href, 'dzsvg_startitem_' + selfClass.galleryComputedId));
        }


        var tempStartItem = detect_startItemBasedOnQueryAddress(selfClass.deeplinkGotoItemQueryParam, selfClass.galleryComputedId);
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


      playlist_pagination_scrollSetup(selfClass);

      function call_init_readyForInitingVideos() {
        init_readyForInitingVideos();
      }

      if (o.settings_mode === VIDEO_GALLERY_MODES.WALL) {
        call_init_readyForInitingVideos();
      } else {
        loadScriptIfItDoesNotExist('', 'dzsvp_isLoaded').then(() => {
          call_init_readyForInitingVideos();
        });
      }
    }

    function init_readyForInitingVideos() {

      // -- first item

      if (selfClass._sliderCon.children().eq(o.startItem).attr('data-type') === 'link') {
        // -- only for link
        gotoItem(o.startItem, {donotopenlink: "on", 'called_from': 'init'});

      } else {
        // -- first item
        // -- normal
        if (o.settings_mode !== VIDEO_GALLERY_MODES.WALL) {
          gotoItem(o.startItem, {'called_from': 'init'});
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
          $('.' + PLAYLIST_VIEW_FULLSCREEN_CLASS).removeClass(PLAYLIST_VIEW_FULLSCREEN_CLASS);
          setTimeout(function () {
            $('.' + PLAYLIST_VIEW_FULLSCREEN_CLASS).removeClass(PLAYLIST_VIEW_FULLSCREEN_CLASS);
          }, 999);
          cgallery.find('.' + PLAYLIST_VIEW_FULLSCREEN_CLASS).removeClass(PLAYLIST_VIEW_FULLSCREEN_CLASS);
          setTimeout(function () {
            calculateDims();
          }, 100);
        }
      }


    }

    function try_to_init_scroller() {

      const baseUrl = window.dzsvg_settings && window.dzsvg_settings.libsUri ? window.dzsvg_settings.libsUri : '';

      loadScriptIfItDoesNotExist(baseUrl + 'dzsscroller/scroller.js', 'dzsscr_init').then(r => {
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


        view_setCssPropsForElement(selfClass.cgallery, {
          'min-height': '100px'
        })
      }, 100);


      if (o.init_on === 'scroll' && cgallery.hasClass('transition-slidein')) {
        setTimeout(function () {

          cgallery.addClass('dzsvg-loaded');

          if (cgallery.parent().hasClass('videogallery-con')) {
            cgallery.parent().addClass('dzsvg-loaded');
          }
        }, PLAYLIST_DEFAULT_TIMEOUT);
      } else {

        cgallery.addClass('dzsvg-loaded');
        if (cgallery.parent().hasClass('videogallery-con')) {
          cgallery.parent().addClass('dzsvg-loaded');
        }
      }
    }

    function setup_navigation_items() {
      if (o.settings_mode === VIDEO_GALLERY_MODES.NORMAL || o.settings_mode === VIDEO_GALLERY_MODES.WALL) {
        buildPlaylist(selfClass);
      }
    }

    /**
     * transfer from feed con to slider con
     */
    function setup_transfer_items_to_sliderCon() {


      if (o.settings_mode !== VIDEO_GALLERY_MODES.WALL) {
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


          view_setCssPropsForElement(_t, {
            'width': selfClass.videoAreaWidth, 'height': selfClass.videoAreaHeight
          })
          _t.append('<div class="previewImg" style="background-image:url(' + playlist_navigation_getPreviewImg(_t) + ');"></div>');
          _t.children('.previewImg').on('click', rotator3d_handleClickOnPreviewImg);

        })
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


      if (o.settings_mode === VIDEO_GALLERY_MODES.WALL) {
        dzsvg_mode_wall_reinitWallStructure(selfClass)
      }
      if (o.settings_mode === VIDEO_GALLERY_MODES.NORMAL) {
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
        caller: null
        , 'called_from': 'default'
      }, pargs ?? {})


      if (margs.caller == null || cgallery.parent().hasClass('skin-laptop')) {
        return false;
      }


      if (heightInitial === -1) {
        heightInitial = selfClass.$sliderMain.height();
      }


      $currVideoPlayer.height(resizeHeightDimension);


      view_setCssPropsForElement(selfClass.$sliderMain, {
        'height': resizeHeightDimension,
        'min-height': resizeHeightDimension
      })


      if (!cgallery.hasClass('ultra-responsive') && (selfClass.nav_position === 'left' || selfClass.nav_position === 'right' || selfClass.nav_position === 'none')) {
        selfClass.totalHeight = resizeHeightDimension;
        selfClass.videoAreaHeight = resizeHeightDimension;


        if (o.settings_mode !== VIDEO_GALLERY_MODES.SLIDER) {
          selfClass._mainNavigation.height(resizeHeightDimension);
        }
        selfClass.videoAreaHeight = resizeHeightDimension;

        setTimeout(() => {

          selfClass.Navigation.calculateDims({forceMainAreaHeight: resizeHeightDimension});
        })

      } else {
        // -- responsive ratio


        view_setCssPropsForElement(selfClass.cgallery, {
          'height': 'auto'
        })


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
        }, 100)
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


      playlist_calculateDims_totals(selfClass, margs);


      selfClass.videoAreaWidth = selfClass.totalWidth;
      selfClass.videoAreaHeight = selfClass.totalHeight;


      menuitem_width = o.menuitem_width;
      menuitem_height = o.menuitem_height;


      if ((selfClass.nav_position === 'right' || selfClass.nav_position === 'left') && nrChildren > 1) {
        selfClass.videoAreaWidth -= (menuitem_width);
      }


      if (o.nav_type !== 'outer' && (selfClass.nav_position === 'bottom' || selfClass.nav_position === 'top') && nrChildren > 1 && cgallery.get(0).style && cgallery.get(0).style.height && cgallery.get(0).style.height !== 'auto') {
        selfClass.videoAreaHeight -= (menuitem_height);
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

      if (o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR3D) {
        selfClass.videoAreaWidth = selfClass.totalWidth / 2;
        selfClass.videoAreaHeight = selfClass.totalHeight * 0.8;
        menuitem_width = 0;
        menuitem_height = 0;
      }


      cgallery.addClass('media-area--transition-' + o.transition_type)


      // === if there is only one video we hide the nav
      if (nrChildren === 1) {
        selfClass._mainNavigation.hide();
      }


      if ($currVideoPlayer) {

      }
      ;

      if (o.settings_mode !== VIDEO_GALLERY_MODES.WALL && o.settings_mode !== VIDEO_GALLERY_MODES.VIDEOWALL) {


        view_setCssPropsForElement(selfClass.$sliderMain, {
          'width': selfClass.videoAreaWidth
        })


        if ((selfClass.nav_position === 'left' || selfClass.nav_position === 'right') && nrChildren > 1) {


          view_setCssPropsForElement(selfClass.$sliderMain, {
            'width': 'auto'
          })
        }


        view_setCssPropsForElement(selfClass.$sliderMain, {
          'height': selfClass.videoAreaHeight
        })

      }

      if (o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR3D) {
        view_setCssPropsForElement(selfClass.$sliderMain, {
          'width': selfClass.totalWidth,
          'height': selfClass.totalHeight
        })
        view_setCssPropsForElement(selfClass._sliderCon.children(), {
          'width': selfClass.videoAreaWidth,
          'height': selfClass.videoAreaHeight
        })
      }


      // -- END calculate dims for navigation / mode-normal


      if (o.settings_mode === VIDEO_GALLERY_MODES.NORMAL) {

        const $dzsNavItems = selfClass.$navigationItemsContainer.find('.dzs-navigation--item');
        if (menuitem_width) {
          view_setCssPropsForElement($dzsNavItems, {
            'width': menuitem_width,
          })
        }
        if (menuitem_height) {

          view_setCssPropsForElement($dzsNavItems, {
            'height': menuitem_height
          })
        }

        if (menuitem_height === 0) {
          view_setCssPropsForElement($dzsNavItems, {
            'height': ''
          })
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
        view_setCssPropsForElement(cgallery, {
          'width': ww + 'px'
        })

        view_setCssPropsForElement(cgallery, {
          'margin-left': '0'
        })


        if (cgallery.offset().left > 0) {
          view_setCssPropsForElement(cgallery, {
            'margin-left': '-' + cgallery.offset().left + 'px'
          })
        }
      }


      if (cgallery.hasClass('try-height-as-window-minus-offset')) {

        let windowMinusGalleryOffset = heightWindow - cgallery.offset().top;
        if (windowMinusGalleryOffset < PLAYLIST_HEIGHT_IS_WINDOW_MAX_OFFSET) {
          view_setCssPropsForElement(cgallery, {
            'height': '90vh'
          })
        } else {
          view_setCssPropsForElement(cgallery, {
            'height': windowMinusGalleryOffset + 'px'
          })
        }

      }


      calculateDims();


      if ($currVideoPlayer) {
        handleResize_currVideo();
      }

    }

    function handleResize_currVideo(e, pargs) {


      var margs = {
        'force_resize_gallery': true
        , 'called_from': 'default'
      };

      if (pargs) {
        margs = $.extend(margs, pargs);
      }


      margs.called_from += '_handleResize_currVideo';

      if (($currVideoPlayer) && $currVideoPlayer.get(0) && ($currVideoPlayer.get(0).api_handleResize)) {


        $currVideoPlayer.get(0).api_handleResize(null, margs);
      }
    }

    function pause_currVideo(e, pargs) {


      var margs = {
        'force_resize_gallery': true
        , 'called_from': 'default'
      };

      if (pargs) {
        margs = $.extend(margs, pargs);
      }


      margs.called_from += '_pause_currVideo';

      if (($currVideoPlayer) && ($currVideoPlayer.get(0).api_pauseMovie)) {


        $currVideoPlayer.get(0).api_pauseMovie(margs);
      }
    }


    function api_currVideo_refresh_fsbutton(argcol) {
      if (typeof ($currVideoPlayer) != "undefined" && typeof ($currVideoPlayer.get(0)) != "undefined" && typeof ($currVideoPlayer.get(0).api_currVideo_refresh_fsbutton) != "undefined") {
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
        if ($currVideoPlayer && $currVideoPlayer.get(0) && typeof ($currVideoPlayer.get(0).api_pauseMovie) != "undefined") {
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

        'ignore_arg_currNr_check': false
        , 'ignore_linking': false // -- does not change the link if set to true
        , donotopenlink: "off"
        , called_from: "default"
      }

      if (pargs) {
        gotoItemOptions = $.extend(gotoItemOptions, pargs);
      }


      if (!(o.settings_mode === VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {

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


      var shouldVideoAutoplay = assertVideoFromGalleryAutoplayStatus(currNr, o, cgallery);
      argsForVideoPlayer['autoplay'] = shouldVideoAutoplay ? 'on' : 'off';


      currNr_curr = arg;


      if (o.settings_enable_linking === 'on') {

        if (_currentTargetPlayer.attr('data-type') === 'link' && (gotoItemOptions.donotopenlink !== 'on')) {
          playlistGotoItemHistoryChangeForLinks(selfClass.ind_ajaxPage, o, cgallery, _currentTargetPlayer, selfClass.deeplinkGotoItemQueryParam);
          return false;
        }
        if (_currentTargetPlayer.attr('data-type') !== 'link') {
          playlistGotoItemHistoryChangeForNonLinks(gotoItemOptions, o, selfClass.galleryComputedId, arg, selfClass.deeplinkGotoItemQueryParam);
        }
      }

      if (o.settings_mode === VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one') {
        _currentTargetPlayer = selfClass._sliderCon.children().eq(0);
        _currentTargetPlayer.addClass('playlist-mode-video-one--main-player')
        $currVideoPlayer = _currentTargetPlayer;

        var _targetPlayer = selfClass._sliderCon.children().eq(arg);
        var optionsForChange = detect_videoTypeAndSourceForElement(_targetPlayer);
        // -- one
        if ($currVideoPlayer.hasClass('vplayer')) {

          pause_currVideo();


          $currVideoPlayer.get(0).api_change_media(
            optionsForChange.source, {
              'type': optionsForChange.type,
              autoplay: shouldVideoAutoplay ? 'on' : 'off'
            })

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
      if (!(o.settings_mode === VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {
        if (currNr > -1) {
          var _c2 = selfClass._sliderCon.children().eq(currNr);

          // --- if on iPad or iPhone, we disable the video as it had runed in a iframe and it wont pause otherwise
          _c2.addClass('transitioning-out');
          if ((is_ios() || _c2.attr('data-type') === 'inline' || (_c2.attr('data-type') === 'youtube' && o.videoplayersettings['settings_youtube_usecustomskin'] !== 'on'))) {
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


      if (!(o.settings_mode === VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {
        _currentTargetPlayer.addClass('transitioning-in');
      }


      if (_currentTargetPlayer.hasClass('type-inline') && _currentTargetPlayer.data('original-iframe')) {
        if (_currentTargetPlayer.html() === '') {
          _currentTargetPlayer.html(_currentTargetPlayer.data('original-iframe'));
        }
      }

      // -- not one
      if (!(o.settings_mode === VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {
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
              cgallery.find('.vplayer.currItem').removeClass(PLAYLIST_VIEW_FULLSCREEN_CLASS)
            }

            // -- next video has fullscreen status
            if (fullscreen_status() === 1) {
              argsForVideoPlayer.extra_classes = argsForVideoPlayer.extra_classes ? argsForVideoPlayer.extra_classes + ' ' + PLAYLIST_VIEW_FULLSCREEN_CLASS : ' ' + PLAYLIST_VIEW_FULLSCREEN_CLASS;
            }

            setTimeout(function () {

            }, 500);
          }

          if (o.settings_disableVideo === 'on') {
          } else {
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


            if (fullscreen_status() === 1) {
              _currentTargetPlayer.addClass(PLAYLIST_VIEW_FULLSCREEN_CLASS);
            }
          }

          // -- we force a resize on the player just in case it has an responsive ratio


          setTimeout(function () {
            if (typeof _currentTargetPlayer.get(0) != 'undefined' && _currentTargetPlayer.get(0).api_handleResize) {

              _currentTargetPlayer.get(0).api_handleResize(null, {
                force_resize_gallery: true
              })
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


      if (o.settings_mode === VIDEO_GALLERY_MODES.NORMAL) {
        view_setCssPropsForElement(_currentTargetPlayer, {
          'display': ''
        })
      }
      if (o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR3D) {
        selfClass._sliderCon.children().removeClass('nextItem currItem hide-preview-img').removeClass('prevItem');
        selfClass._sliderCon.children().eq(nextNr).addClass('nextItem');
        selfClass._sliderCon.children().eq(prevNr).addClass('prevItem');
      }
      if (o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR) {

        if (currNr > -1) {

        }
        var _descCon = selfClass.$navigationClippedContainer.children('.descriptionsCon');
        _descCon.children('.currDesc').removeClass('currDesc').addClass('pastDesc');
        _descCon.append('<div class="desc">' + _currentTargetPlayer.find('.feed-menu-desc').html() + '</div>');
        setTimeout(function () {
          _descCon.children('.desc').addClass('currDesc');
        }, 20)

      }


      last_arg = arg;


      if (!(o.settings_mode === VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one')) {

        if (currNr === -1 || isTransformed) {
          galleryTransition();
          if (o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR3D) {
            selfClass._sliderCon.children().eq(arg).addClass('hide-preview-img');
          }
        } else {
          cgallery.parent().children('.preloader').addClass('is-visible');

          let delay = 500;

          if (o.settings_mode === VIDEO_GALLERY_MODES.ROTATOR3D) {
            delay = 10;
            selfClass._sliderCon.children().eq(arg).addClass('currItem');
            setTimeout(function () {

              selfClass._sliderCon.children().eq(arg).addClass('hide-preview-img');
            }, PLAYLIST_DEFAULT_TIMEOUT);
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
        })
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


      return !(o.settings_mode === VIDEO_GALLERY_MODES.NORMAL && o.mode_normal_video_mode === 'one');


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
          selfClass._sliderCon.children().eq(currNr).removeClass('no-transition')
        })
      } else {

        if (currNr !== arg) {

          selfClass._sliderCon.children().eq(currNr).addClass('transition-slideup-gotoTop')
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

      if (is_ios() && currNr > -1) {
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

          view_setCssPropsForElement($innerSecondCon, {
            'height': _c.find('.item').eq(arg).outerHeight(false),
            'left': -(arg * 100) + '%'
          })
        }


      }
    }

    function view_hideAllVideosButCurrentVideo() {
      if (o.settings_mode === VIDEO_GALLERY_MODES.NORMAL) {

        selfClass._sliderCon.children().each(function () {
          const $videoItem = $(this);

          if (!$videoItem.hasClass('currItem')) {
            $videoItem.hide();
          }
        })
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
      }

      if (pargs) {
        margs = $.extend(margs, pargs);
      }

      if (!(is_ios()) && currNr > -1 && o.mode_normal_video_mode !== 'one') {
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


export function apply_videogallery_plugin($) {
  $.fn.vGallery = function (argOptions) {
    this.each(function () {
      var finalOptions = {};
      let overwriteSettings = {...argOptions};

      if(argOptions && Object.keys(argOptions).length === 1 && argOptions.init_each){
        overwriteSettings = null;
      }
      finalOptions = convertPluginOptionsToFinalOptions(this, getDefaultPlaylistSettings(), overwriteSettings);

      return new DzsVideoGallery(this, finalOptions, $);
    }); // end each

  }


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
    window.dzsvg_settings = {...{}};
  }
}
window.setup_videogalleryCategories = setup_videogalleryCategories;


function dzsvg_handleInitedjQuery() {


  (function ($) {
    apply_videogallery_plugin($);
  })(jQuery);


  const dzsvg_reinit = () => {

    secondCon_initFunctions();
    init_navigationOuter();
  }


  jQuery(document).ready(function () {
    dzsvg_init('.videogallery.auto-init');
    dzsvg_reinit();
  })
  dzsvgExtraWindowFunctions();


  window.dzsvg_reinit = dzsvg_reinit;
}

loadScriptIfItDoesNotExist('', 'jQuery').then(() => {
  dzsvg_handleInitedjQuery();
})