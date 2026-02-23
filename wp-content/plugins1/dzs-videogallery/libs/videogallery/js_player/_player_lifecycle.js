import {
  configureAudioPlayerOptionsInitial,
  detect_video_type_and_source,
  getDataOrAttr, player_setQualityLevels,
  playerHandleDeprecatedAttrSrc, reinitPlayerOptions, tagsSetupDom, youtube_sanitize_url_to_id
} from "../js_dzsvg/_dzsvg_helpers";
import {is_android, is_ios, is_mobile, is_touch_device, loadScriptIfItDoesNotExist} from "../js_common/_dzs_helpers";
import {ConstantsDzsvg} from "../configs/Constants";
import {player_controls_drawBigPlayBtn, player_controls_drawFullscreenBarsOnCanvas} from "./view/_player_viewDraw";
import * as playerAdFunctions from "../js_dzsvg/_player_ad_functions";
import {svg_quality_icon} from "../js_dzsvg/_dzsvg_svgs";
import {VIDEO_TYPES} from "../configs/_playerSettings";
import {promise_allDependenciesMet} from "../shared/_viewFunctions";
import {dash_setupPlayer, player_getResponsiveRatio} from "./_player_helpers";
import {generatePlayerMarkupAndSource} from "./_player_setupMedia";
import {ads_view_setupMarkersOnScrub} from "./_player_setupAd";
import {player_lifeCycle_setupMobile} from "./_player_lifeCycle_setupMobile";


/**
 * The function is responsible for initializing a video player instance. Here's a breakdown of its main functionality: `vplayerLifecycleInit`
 * @param {DzsVideoPlayer} selfClass
 */
export function vplayerLifecycleInit(selfClass) {

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
  playerHandleDeprecatedAttrSrc(cthis);
  if (getDataOrAttr(selfClass.cthis, 'data-sourcevp')) {
    selfClass.dataSrc = getDataOrAttr(selfClass.cthis, 'data-sourcevp');
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


  const videoTypeAndSource = detect_video_type_and_source(selfClass.dataSrc, null, selfClass.cthis);

  if (selfClass.dataOriginalType === '' || selfClass.dataOriginalType === 'detect') {
    cthis.attr('data-type', videoTypeAndSource.type);
    selfClass.dataType = videoTypeAndSource.type;
    if (o.playfrom === 'default') {
      if (videoTypeAndSource.playFrom) {
        o.playfrom = videoTypeAndSource.playFrom;
      }
    }
  }

  configureAudioPlayerOptionsInitial(cthis, o, selfClass);

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
    if (is_mobile()) {
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


  if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {

    if (!window._global_youtubeIframeAPIReady && window.dzsvp_yt_iframe_settoload === false) {
      loadScriptIfItDoesNotExist(ConstantsDzsvg.YOUTUBE_IFRAME_API, '').then(r => {
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

    if (selfClass.dataType === VIDEO_TYPES.YOUTUBE && is_touch_device() && $(window).width() < 700) {
      cthis.addClass('is-touch-device type-youtube');

    }
    o.settings_video_overlay = 'on';

  }


  selfClass.view_setupBasicStructure();


  if (cthis.get(0)) {
    cthis.get(0).fn_change_color_highlight = selfClass.classMisc.fn_change_color_highlight;

    cthis.get(0).api_handleResize = selfClass.handleResize;
    cthis.get(0).api_seek_to_perc = selfClass.seek_to_perc;

    cthis.get(0).api_currVideo_refresh_fsbutton = (arg) => {
      player_controls_drawFullscreenBarsOnCanvas(selfClass, selfClass._controls_fs_canvas, arg);
    }
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
    let string_structureBigPlayBtn = player_controls_drawBigPlayBtn()

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


  if (o.cueVideo === 'on' || ((!is_ios() || o.settings_ios_usecustomskin === 'on') && (selfClass.dataType === 'selfHosted' || selfClass.dataType === VIDEO_TYPES.YOUTUBE || selfClass.dataType === 'vimeo'))) {

    if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
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




export function init_readyControls(selfClass, e, pargs) {


  promise_allDependenciesMet(selfClass,() => {
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

  if (_c.attr('data-type') === VIDEO_TYPES.YOUTUBE) {

    selfClass.dataSrc = youtube_sanitize_url_to_id(selfClass.dataSrc);
  }


  let argsForVideoSetup = {}


  // -- ios video setup

  if (o.settings_ios_usecustomskin !== 'on' && is_ios()) {

    // -- our job on the iphone / ipad has been done, we exit the function.
    player_lifeCycle_setupMobile(selfClass, argsForVideoSetup)
  }
  // -- end ios setup


  // -- selfHosted
  if ((!is_ios() || o.settings_ios_usecustomskin === 'on')) {

    // -- selfHosted video on modern browsers
    if (o.settings_enableTags === 'on') {
      cthis.find('.dzstag-tobe').each(function () {
        var _tagElement = $(this);
        tagsSetupDom(_tagElement);
      })
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
    if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
      // -- youtube
      argsForVideoSetup.youtube_useDefaultSkin = (o.settings_youtube_usecustomskin !== 'on' || (o.settings_ios_usecustomskin !== 'on' && is_ios()));


    }


    if (selfClass.dataType === 'dash') {
      dash_setupPlayer();
    }


    if (margs.called_from === 'change_media') {
      argsForVideoSetup.isGoingToChangeMedia = true;
    }

    if (selfClass.dataType === VIDEO_TYPES.YOUTUBE && argsForVideoSetup.youtube_useDefaultSkin === false) {
      cthis.find('#the-media-' + selfClass.currentPlayerId).on('mousemove', selfClass.handle_mousemove);
    }

  }
  generatePlayerMarkupAndSource(selfClass, argsForVideoSetup);


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
  player_getResponsiveRatio(selfClass, {
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
    if (!(cthis.get(0).externalPauseMovie)) {

      cthis.get(0).externalPauseMovie = selfClass.pauseMovie;
      cthis.get(0).externalPlayMovie = selfClass.playMovie;
      cthis.get(0).api_pauseMovie = selfClass.pauseMovie;
      cthis.get(0).api_playMovie = selfClass.playMovie;
      cthis.get(0).api_get_responsive_ratio = (pargs = {}) => {
        player_getResponsiveRatio(selfClass, pargs);
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
    })


    window.dzsvp_player_360_eventFunctionsInit(selfClass);
  }


  const _scrubbar = cthis.find('.scrubbar').eq(0);

  _scrubbar.on('touchstart', function (e) {
    selfClass.scrubbar_moving = true;
  })

  if (o.ad_show_markers === 'on') {
    ads_view_setupMarkersOnScrub(selfClass);
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
  })


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

  if (is_touch_device()) {
    cthis.addClass('is-touch');
  }
}



/**
 * should init after setup controls
 */
export function vplayerLifecycleReinit(selfClass) {
  // console.log('reinit');

  const cthis = selfClass.cthis;
  const o = selfClass.argOptions;

  if (cthis.attr('data-loop') === 'on') {
    selfClass.isLoop = true;
  }

  reinitPlayerOptions(selfClass, o);

  selfClass.classMisc.reinit_cover_image();


  let extraFeedBeforeRightControls = '';
  const $extraFeedBeforeRightControls = selfClass.cthis.find('.dzsvg-feed--extra-html-before-right-controls').eq(0);
  if ($extraFeedBeforeRightControls.length) {
    extraFeedBeforeRightControls = $extraFeedBeforeRightControls.html();
  }

  if (extraFeedBeforeRightControls) {

    extraFeedBeforeRightControls = String(extraFeedBeforeRightControls).replace('{{svg_quality_icon}}', svg_quality_icon);

    if (selfClass._controlsRight) {

      selfClass._controlsRight.prepend(extraFeedBeforeRightControls);
    } else {

      if (selfClass._timetext) {

        selfClass._timetext.after(extraFeedBeforeRightControls);
      }
    }
  }

  player_setQualityLevels(selfClass);


}


/**
 * this function will assign listeners to the player and selfClass.autoplayVideo if the selfClass.autoplayVideo is set to on
 * @param {DzsVideoPlayer} selfClass selfClass
 * @param {object} pargs parameters
 */
export function init_readyVideo(selfClass, pargs) {

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

  if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
    selfClass.qualities_youtubeCurrentQuality = selfClass._videoElement.getPlaybackQuality();

  }


  if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'selfHosted') {
    if (o.default_playbackrate && o.default_playbackrate !== '1') {

      setTimeout(function () {

      }, 1000);
      if (selfClass._videoElement && selfClass._videoElement.playbackRate) {

        selfClass._videoElement.playbackRate = Number(o.default_playbackrate);
      }
    }
  }


  selfClass.videoWidth = cthis.outerWidth();
  selfClass.videoHeight = cthis.outerHeight();


  selfClass.resizePlayer(selfClass.videoWidth, selfClass.videoHeight)


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
            if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
              selfClass._videoElement.seekTo(Number(localStorage['dzsvp_' + selfClass.id_player + '_lastpos']));
              if (!selfClass.wasPlaying) {
                selfClass.pauseMovie({
                  'called_from': '_init_readyVideo()'
                });
              }
            }
          }
        } catch (e) {

        }
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


    if (is_mobile()) {
      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
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


    if (selfClass.isAd && selfClass.autoplayVideo === 'off') {

    }

    if (is_ios() || is_android()) {

      selfClass.$playcontrols.css({'opacity': 0.9});
      selfClass.$playcontrols.on('click', selfClass.handleClickPlayPause);

      o.settings_hideControls = 'off';

      cthis.removeClass('hide-on-paused');
      cthis.removeClass('hide-on-mouse-out');


      if (selfClass.isAd) {
        // -- if this is an ad

        selfClass.autoplayVideo = 'on';
        o.autoplay = 'on';
        o.cue = 'on';


        cthis.find('.video-overlay').append('<div class="warning-mobile-ad">' + 'You need to click here for the ad for to start' + '</div>')

      }

    }
  }

  $(selfClass._videoElement).on('play', selfClass.handleVideoEvent);


  if (is_ios() && o.settings_ios_usecustomskin === 'off') {
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
    if (is_ios() && video && selfClass.isAd) {
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
function destroy_listeners(selfClass){
  const cthis = selfClass.cthis;
  const $ = jQuery;

  return function (){


    cthis.off('mouseout', selfClass.handleMouseout);
    cthis.off('mouseover', selfClass.handleMouseover);
    cthis.find('.controls').eq(0).off('mouseover', selfClass.handle_mouse);
    cthis.find('.controls').eq(0).off('mouseout', selfClass.handle_mouse);
    cthis.off('mousemove', selfClass.handle_mousemove);
    cthis.off('keydown', selfClass.handleKeyPress);
    selfClass.$fullscreenControl.off('click', selfClass.fullscreenToggle)
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



  }
}