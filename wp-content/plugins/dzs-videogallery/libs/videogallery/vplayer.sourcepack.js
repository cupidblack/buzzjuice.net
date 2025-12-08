"use strict";
/**
 * Author: Digital Zoom Studio
 * Website: https://digitalzoomstudio.net/
 * Portfolio: https://codecanyon.net/user/ZoomIt/portfolio?ref=ZoomIt
 * This is not free software.
 * Video Gallery
 * Version: 10.76
 */

import {
  convertPluginOptionsToFinalOptions,
  dzsvg_call_video_when_ready,
  dzsvg_check_multisharer,
  dzsvgExtraWindowFunctions,
  fullscreen_status,
  pauseDzsapPlayers,
  player_controls_generatePlayCon,
  player_setupQualitySelector,
  registerAuxjQueryExtends,
  vimeo_do_command,
  youtube_sanitize_url_to_id
} from "./js_dzsvg/_dzsvg_helpers";

import {
  format_to_seconds, 
  formatTime,
  is_android,
  is_ios,
  is_mobile,
  is_safari,
  loadScriptIfItDoesNotExist,
} from './js_common/_dzs_helpers';
import * as playerAdFunctions from './js_dzsvg/_player_ad_functions';

import { player_setup_skipad, player_setupAd} from "./js_player/_player_setupAd";
import {generatePlayerMarkupAndSource} from "./js_player/_player_setupMedia";
import {init_windowVars} from "./js_dzsvg/_dzsvg_window_vars";
import {video_mute, video_play} from './js_dzsvg/_video-element-functions';


import {
  ConstantsDzsvg,
  PLAYER_DEFAULT_TIMEOUT,
  PLAYER_REGEX_SUBTITLE,
  PLAYLIST_VIEW_FULLSCREEN_CLASS
} from './configs/Constants';
import {svg_embed, svg_full_icon, svg_mute_btn, svg_mute_icon, svg_quality_icon} from "./js_dzsvg/_dzsvg_svgs";

import {VolumeControls} from "./js_dzsvg/components/_volume";
import {defaultPlayerSettings, PLAYER_STATES, VIDEO_TYPES} from "./configs/_playerSettings";
import {
  dash_setupPlayer,
  exitFullscreen,
  player_getResponsiveRatio,
  requestFullscreen,
} from "./js_player/_player_helpers";
import {vimeoPlayerCommand} from "./js_player/_vimeoPlayerCommands";
import {
  player_controls_drawBigPlayBtn,
  player_controls_drawFullscreenBarsOnCanvas, player_controls_stringScrubbar
} from "./js_player/view/_player_viewDraw";
import {
  init_readyControls,
  init_readyVideo,
  vplayerLifecycleInit,
  vplayerLifecycleReinit
} from "./js_player/_player_lifecycle";
import {promise_allDependenciesMet} from "./shared/_viewFunctions";

init_windowVars();

window.dzsvp_yt_iframe_settoload = false;
window.dzsvp_players_arr = [];
setTimeout(function () {

  if (window.dzsvg_settings) {

  }
  if (Object.hasOwnProperty('assign')) {

    window.dzsvg_settings = Object.assign(dzsvg_default_settings, window.dzsvg_settings)
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
    this.$adContainer = null

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
    this.isLoop = false

    // -- ads
    this.$adSkipCon = null;
    this.ad_status = 'undefined'
    this.ad_link = null;
    this.ad_array = [];
    this.isAdPlaying = false


    this.vimeo_url = '';

    this.volumeClass = new VolumeControls(this);
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

    selfClass.init_readyControls = init_readyControls;
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


    var cthis
    ;
    var natural_videow = 0
      , natural_videoh = 0
      , last_videoWidth = 0
      , last_videoHeight = 0;
    var video;
    var aux = 0;
    var isFullscreen = 0;
    let inter_removeFsControls // interval to remove fullscreen controls when no action is detected
      , inter_mousedownscrubbing = 0 // interval to apply mouse down scrubbing on the video
    ;

    var  info
      , infotext
      , _scrubBg
      , _btnhd
      , _controlsBackground = null
      , _muteControls = null

    ;
    let videoIsPlayed = false
      , isMouseover = false // -- the mouse is over the vplayer
      , google_analytics_sent_play_event = false
      , volume_mouse_down = false
      , scrub_mouse_down = false
      , controls_are_hovered = false
      , isViewSent = false
      , isFullscreenJustPressed = false
      , isPlayCommited = false // -- this will apply play later on
      , vimeo_is_ready = false
    ;


    var totalDuration = 0;
    var time_curr = 0;

    var video_title = '';

    //responsive vars
    var ww
      , wh
    ;
    var qualities_youtubeVideoQualitiesArray = []

      , hasHD = false
    ;




    var bufferedLength = -1,
      scrubbg_width = 0
    ;




    var isBusyPlayPauseMistake = false
    ;


    let vimeo_data;


    var inter_10_secs_contor = 0
      , inter_5_secs_contor = 0
      , inter_60_secs_contor = 0
    ;


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
      selfClass.currentPlayerId = 'dzsvp' + parseInt(Math.random() * 10000, 10)
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
        player_getResponsiveRatio(selfClass, pargs)
      };
      selfClass.player_user_had_first_interaction = player_user_had_first_interaction;
      selfClass.pauseMovie = pauseMovie;
      selfClass.handleResize = handleResize;
      selfClass.setup_skipad = player_setup_skipad(selfClass);
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


      if (cthis.hasClass('vplayer-tobe') || !cthis.hasClass(PLAYER_STATES.INITIALIZED)) {

        vplayerLifecycleInit(selfClass);

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
        }, 500)
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

        player_setup_skipad(selfClass)()
      }


      vplayerLifecycleReinit(selfClass);
    }

    function setup_customControls() {


      var str_scrubbar = player_controls_stringScrubbar();


      if (o.design_skin === 'skin_pro') {

        if (!(selfClass.dataType === 'vimeo' && o.vimeo_is_chromeless !== 'on')) {

          if (selfClass._vpInner) {
            selfClass._vpInner.append(str_scrubbar);
          }
        }

      }


      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
        if (selfClass._vpInner) {
          selfClass._vpInner.prepend('<div class="mute-indicator"><i class="the-icon">' + svg_mute_icon + '</i> <span class="the-label">' + 'muted' + '</span></div>')
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
          selfClass._controlsDiv.append('<div class="mutecontrols-con"><div class="btn-mute">' + svg_mute_btn + '</div></div>');

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
              _t.html(String(_t.html()).replace('{{svg_embed_icon}}', svg_embed));
            }
            if (_t.get(0).outerHTML.indexOf('dzsvg-multisharer-but') > -1) {
              dzsvg_check_multisharer();
            }

            cthis.find('.timetext').eq(0).after(_t);
          });

        }
      }


      if (cthis.attr('data-img')) {
        selfClass._vpInner.prepend('<div class="cover-image from-type-' + selfClass.dataType + '"><div class="the-div-image" style="background-image:url(' + cthis.attr('data-img') + ');"/></div>');
      }


      if (selfClass.dataType === 'image') {

        cthis.addClass(PLAYER_STATES.LOADED);

        if (selfClass.ad_link) {
          selfClass.cthis.children().eq(0).css({'cursor': 'pointer'})
          selfClass.cthis.children().eq(0).on('click', function () {
            if (selfClass.cthis.find('.controls').eq(0).css('pointer-events') !== 'none') {
              window.open(selfClass.ad_link);
              selfClass.ad_link = null;
            }
          })
        }
        return;
      }


      if (selfClass.dataType === 'inline') {
        cthis.find('.cover-image').on('click', function () {
          $(this).removeClass('is-visible');
        });

        cthis.addClass(PLAYER_STATES.LOADED);

        setTimeout(function () {

          cthis.addClass('dzsvp-really-loaded');


        }, 2000);


        player_getResponsiveRatio(selfClass, {
          'called_from': 'init .. inline'
        });
        handleResize();
        setTimeout(function () {

          handleResize();
        }, 1000);
        $(window).on('resize', handleResize);

        return;
      }


      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
        player_getResponsiveRatio(selfClass, {
          'called_from': 'init .. youtube'
        });
      }
      if (selfClass.dataType === 'selfHosted') {

        if (o.settings_disableControls === 'on') {
          // -- for youtube ads we force enable the custom skin because we need to know when the video ended
          o.cueVideo = 'on';
          o.settings_youtube_usecustomskin = 'on';

          if (is_mobile()) {

            selfClass.autoplayVideo = 'off';
          }
        }

      }

      if (selfClass.dataType === 'vimeo') {

      }
      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
        if (o.settings_disableControls === 'on') {
          // -- for youtube ads we force enable the custom skin because we need to know when the video ended
          o.cueVideo = 'on';
          o.settings_youtube_usecustomskin = 'on';
          if (is_mobile()) {
            selfClass.autoplayVideo = 'off';
          }
        }

      }
      info = cthis.find('.info');
      infotext = cthis.find('.infoText');


      var structPlayControls = '';
      selfClass.$playcontrols = cthis.find('.playcontrols');


      structPlayControls = player_controls_generatePlayCon(o);

      selfClass.$playcontrols.append(structPlayControls);


      selfClass.scrubbar = cthis.find('.scrubbar');

      _scrubBg = selfClass.scrubbar.children('.scrub-bg');


      selfClass.$fullscreenControl = cthis.find('.fscreencontrols');

      aux = '<div class="full">';


      if (o.design_skin === 'skin_aurora' || o.design_skin === 'skin_default' || o.design_skin === 'skin_white') {
        aux += svg_full_icon;
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
          player_controls_drawFullscreenBarsOnCanvas(selfClass, selfClass._controls_fs_canvas, o.controls_fscanvas_bg);
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
          selfClass._vpInner.append('<div class="video-description"></div>')

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

      dzsvg_call_video_when_ready(o, selfClass, init_readyVideo, vimeo_is_ready, selfClass.inter_videoReadyState);
      setTimeout(() => {
        if (o.cue === 'on') {
          if (!selfClass.isInitedReadyVideo) {
            init_readyVideo(selfClass,{
              'called_from': 'timeout .. readyvideo'
            })
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
      } else {


      }

    }



    /**
     * change the media of the player
     * @param {string} argmedia
     * @param {object} pargs
     */
    function change_media(argmedia, pargs) {
      // -- @change media


      var margs = {
        'called_from': 'default'
        , 'type': 'selfHosted'
        , 'autoplay': 'off'
      }

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


      promise_allDependenciesMet(selfClass,() => {

        vplayerLifecycleReinit(selfClass);

        if (selfClass.lastVideoType === margs.type) {

          // -- same type
          if (selfClass.lastVideoType === 'selfHosted') {
            $(selfClass._videoElement).attr('src', argmedia);
            $(selfClass._videoElement).children('source').attr('src', argmedia);
          }
          if (selfClass.lastVideoType === VIDEO_TYPES.YOUTUBE) {
            if (selfClass.hasCustomSkin) {
              selfClass._videoElement.loadVideoById(youtube_sanitize_url_to_id(argmedia));
            } else {
              if (selfClass._videoElement.loadVideoById) {
                selfClass._videoElement.loadVideoById(youtube_sanitize_url_to_id(argmedia));
              } else {

                selfClass.dataSrc = youtube_sanitize_url_to_id(argmedia);
                cthis.find('iframe').eq(0).attr('src', '//www.youtube.com/embed/' + selfClass.dataSrc + '?rel=0&showinfo=0')
              }
            }
          }
          if (selfClass.lastVideoType === 'vimeo') {
            if (selfClass.hasCustomSkin) {
              var argsForVideoSetup = {
                called_from: 'change_media'
              }
              generatePlayerMarkupAndSource(selfClass, argsForVideoSetup);
            } else {

              var str_source = 'https:' + '//player.vimeo.com/video/' + selfClass.dataSrc + '?api=1&color=' + o.vimeo_color + '&title=' + o.vimeo_title + '&byline=' + o.vimeo_byline + '&portrait=' + o.vimeo_portrait + '&badge=' + o.vimeo_badge + '&player_id=vimeoplayer' + selfClass.dataSrc + (selfClass.autoplayVideo == 'on' ? '&autoplay=1' : '');

              selfClass._vpInner.find('.vimeo-iframe').eq(0).attr('src', str_source)
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
          init_readyControls(null, {
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
          }, PLAYER_DEFAULT_TIMEOUT);
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


      vplayerLifecycleReinit(selfClass);
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
          if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {

            selfClass._videoElement.setPlaybackQuality(_t.attr('data-val'));


            selfClass._videoElement.stopVideo();
            selfClass._videoElement.setPlaybackQuality(_t.attr('data-val'));
            selfClass._videoElement.playVideo();


            setTimeout(function () {

              selfClass.qualities_youtubeCurrentQuality = selfClass._videoElement.getPlaybackQuality();


            }, 2000)
          }


          if (selfClass.dataType === 'selfHosted') {


            var newsource = selfClass.dataSrc;


            var _c = $(selfClass._videoElement).eq(0);

            cthis.find('.the-video').addClass('transitioning-out');

            _c.after(_c.clone());

            var _c2 = _c.next();

            _c2.removeClass('transitioning-out transitioning-in');
            _c2.addClass('preparing-transitioning-in js-transitioning-in');
            _c2.html('<source src="' + newsource + '">')


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
              }, 500)

            })

            setTimeout(function () {

            }, 100);
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


        const argperc = (e.pageX - (selfClass.scrubbar.offset().left)) / (selfClass.scrubbar.children().eq(0).width());
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

      if (!selfClass.paused && (!controls_are_hovered || is_android())) {

        cthis.removeClass('mouse-is-over');
        cthis.addClass('mouse-is-out');
      }
      isMouseover = false;
    }

    function handleVideoEvent(e) {


      if (e.type === 'play') {

        videoIsPlayed = true;

        if (is_ios() || is_android()) {
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

        player_controls_drawFullscreenBarsOnCanvas(selfClass, selfClass._controls_fs_canvas, o.controls_fscanvas_hover_bg);
      }


    }

    function handleMouseout(e) {

      if (selfClass.is360) {

        window.dzsvp_player_360_funcEnableControls(selfClass);
      }

      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE && isFullscreen) {
        isFullscreenJustPressed = true;

        setTimeout(function () {
          isFullscreenJustPressed = false;
        }, 500)
      }
      if ($(e.currentTarget).hasClass('vplayer')) {


        if (o.settings_disable_mouse_out !== 'on') {


          clearTimeout(inter_removeFsControls);

          inter_removeFsControls = setTimeout(controls_mouse_is_out, o.settings_mouse_out_delay);
        }
      }
      if ($(e.currentTarget).hasClass('fullscreen-button')) {
        player_controls_drawFullscreenBarsOnCanvas(selfClass, selfClass._controls_fs_canvas, o.controls_fscanvas_bg);
      }

    }

    function handleScrubMouse(e) {
      if (!selfClass.scrubbar) {
        return false;
      }
      var _t = selfClass.scrubbar;


      if (e.type === 'mousemove') {
        var mouseX = (e.pageX - $(this).offset().left);
        var aux = (mouseX / scrubbg_width) * totalDuration;
        if (!(isNaN(aux) || aux === Infinity)) {

          _t.children('.scrubBox').html(formatTime(aux));
        }
        _t.children('.scrubBox').css({'visibility': 'visible', 'left': (mouseX - 16)});
      }
      if (e.type === 'mouseout') {
        _t.children('.scrubBox').css({'visibility': 'hidden'});

      }
      if (e.type === 'mouseleave') {
        _t.children('.scrubBox').css({'visibility': 'hidden'});
      }
    }


    function handleScrub(e) {
      player_user_had_first_interaction();

      var argperc = (e.pageX - (selfClass.scrubbar.offset().left)) / (selfClass.scrubbar.children().eq(0).width());
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
        selfClass._videoElement.currentTime = (argperc) * totalDuration;
      }
      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {


        if (selfClass._videoElement && selfClass._videoElement.getDuration) {

          totalDuration = selfClass._videoElement.getDuration();
        } else {
          console.info('vplayer warning, youtube type - youtube api not ready .. ? ');
          totalDuration = 0;
        }

        // -- no need for seek to perct if video has not started.
        if (isNaN(totalDuration) || (time_curr === 0 && argperc === 0)) {
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

          vimeoPlayerCommand(selfClass, 'seekTo', '0');
        } else {

          if (o.vimeo_is_chromeless === 'on') {
            vimeoPlayerCommand(selfClass, 'seekTo', (argperc) * totalDuration);
          }

        }
      }

    }

    function handleEnterFrame(pargs) {
      // -- enterFrame function

      var margs = {
        skin_play_check: false
      }

      if (pargs) {
        margs = $.extend(margs, pargs);
      }

      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
        totalDuration = selfClass._videoElement.duration;
        time_curr = selfClass._videoElement.currentTime;


        if (selfClass.scrubbar && selfClass._videoElement && selfClass._videoElement.buffered && selfClass._videoElement.readyState > 1 && selfClass._videoElement.buffered && selfClass._videoElement.buffered.length) {
          bufferedLength = 0;
          try {

            bufferedLength = (selfClass._videoElement.buffered.end(0) / selfClass._videoElement.duration) * (selfClass.scrubbar.children().eq(0).width() + selfClass.bufferedWidthOffset);
          } catch (err) {
            console.log(err);
          }
        }


      }
      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
        if (!selfClass._videoElement.getVideoLoadedFraction) {
          return false;
        }
        if (selfClass._videoElement.getDuration !== undefined) {
          totalDuration = selfClass._videoElement.getDuration();
          time_curr = selfClass._videoElement.getCurrentTime();
        }
        if (_scrubBg) {
          bufferedLength = (selfClass._videoElement.getVideoLoadedFraction()) * (_scrubBg.width() + selfClass.bufferedWidthOffset);
        }

        aux = 0;
        if (selfClass.scrubbar) {

          selfClass.scrubbar.children('.scrub-buffer').css('left', aux);
        }


      }
      aux = ((time_curr / totalDuration) * (scrubbg_width));

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
          selfClass.scrubbar.children('.scrub-buffer').width(bufferedLength)
        }
      }
      if (selfClass._timetext && selfClass._timetext.css('display') !== 'none' && (selfClass.wasPlaying || margs.skin_play_check) || (selfClass.dataType === 'vimeo' && o.vimeo_is_chromeless === 'on')) {

        var aux35 = formatTime(totalDuration);


        if (o.design_skin !== 'skin_reborn') {
          aux35 = ' / ' + aux35;
        }


        selfClass._timetext.children(".curr-timetext").html(formatTime(time_curr));
        selfClass._timetext.children(".total-timetext").html(aux35);

      }
      if (o.design_enableProgScrubBox === 'on') {

        if (selfClass.scrubbar) {
          selfClass.scrubbar.children('.scrubBox-prog').html(formatTime(time_curr));

          selfClass.scrubbar.children('.scrubBox-prog').css({
            'left': aux - 16
          })
        }
      }
      if (o.playfrom === 'last') {
        try {
          if (typeof Storage != 'undefined') {
            localStorage['dzsvp_' + selfClass.id_player + '_lastpos'] = time_curr;
          }
        } catch (e) {

        }
      }

    }


    function volume_handleClickMuteIcon(e) {
      var _t = $(this);
      _t.toggleClass('active');

      if (_t.hasClass('active')) {
        selfClass.volumeLast = selfClass.volumeClass.volume_getVolume();
        volume_playerMute();
      } else {

        volume_setupVolumePerc(selfClass.volumeLast, {'called_from': 'volume_unmute'});
      }
    }


    function volume_playerMute() {

      video_mute(selfClass);
    }


    function handleMouseOnVolume(e) {


      // -- from user action


      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'audio' || selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
        // -- we can remove muted on user action
        player_user_had_first_interaction();
      }

      const _volumeReferenceTarget = selfClass._volumeControls.eq(1).length ? selfClass._volumeControls.eq(1) : selfClass._volumeControls.eq(0);
      const mousePositionRelativeToVolumeControls = (e.pageX - (_volumeReferenceTarget.offset().left));

      selfClass._volumeControls = cthis.find('.volumecontrols').children();
      if (mousePositionRelativeToVolumeControls >= 0) {


        aux = (e.pageX - (_volumeReferenceTarget.offset().left));

        selfClass._volumeControls.eq(2).css('visibility', 'visible')
        selfClass._volumeControls.eq(3).css('visibility', 'hidden')

        volume_setupVolumePerc(aux / _volumeReferenceTarget.width(), {'called_from': 'handleMouseOnVolume'});
      } else {

        // -- set volume to 0  when x < 0

        if (selfClass._volumeControls.eq(3).css('visibility') === 'hidden') {
          selfClass.volumeLast = selfClass.volumeClass.volume_getVolume();
          volume_setupVolumePerc(0)


          if (selfClass.dataType === 'vimeo') {
            vimeo_data = {
              "method": "setVolume"
              , "value": "0"
            };

            if (selfClass.vimeo_url) {
              vimeo_do_command(selfClass, vimeo_data, selfClass.vimeo_url);
            }
          }
          selfClass._volumeControls.eq(3).css('visibility', 'visible')
          selfClass._volumeControls.eq(2).css('visibility', 'hidden')
        } else {
          volume_setupVolumePerc(selfClass.volumeLast)


          selfClass._volumeControls.eq(3).css('visibility', 'hidden')
          selfClass._volumeControls.eq(2).css('visibility', 'visible')
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
      }
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
          if (fullscreen_status() === 1) {
            exitFullscreen();
          }
        }

      }


      if (fullscreen_status() === 1) {
        if (o.end_exit_fullscreen === 'on') {
          fullscreenToggle(null, {
            'called_from': 'handleVideoEnd .. forced o.end_exit_fullscreen',
            'force_exit_fullscreen': true,
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
            } else {

            }


          }
        }
      }
      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
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
        if (typeof (selfClass.$parentGallery.get(0)) !== 'undefined') {
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
        'force_resize_gallery': false
        , 'called_from': 'default'
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


        if (selfClass.$parentGallery && ((cthis.hasClass('currItem') && !selfClass.isAd) || margs.force_resize_gallery)) {
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


      if (fullscreen_status() === 1) {
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
          }, PLAYER_DEFAULT_TIMEOUT);
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

      if (e && typeof (e.data) == 'object') {
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
          vimeo_do_command(selfClass, vimeo_data, selfClass.vimeo_url);

          vimeo_data = {
            "method": "addEventListener",
            "value": "pause"
          };
          vimeo_do_command(selfClass, vimeo_data, selfClass.vimeo_url);

          vimeo_data = {
            "method": "addEventListener",
            "value": "playProgress"
          };
          vimeo_do_command(selfClass, vimeo_data, selfClass.vimeo_url);


          cthis.addClass(PLAYER_STATES.LOADED);
          if (selfClass.$parentGallery != null) {
            if (typeof (selfClass.$parentGallery.get(0)) != 'undefined') {
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
      }, PLAYER_DEFAULT_TIMEOUT);

      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE && selfClass._videoElement.getPlayerState && (selfClass._videoElement.getPlayerState() === 2 || selfClass._videoElement.getPlayerState() === -1)) {
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


    function handleFullscreenEnd(event) {

    }


    function handleFullscreenChange(e) {

      isFullscreen = !!(fullscreen_status() === 1);

      if (isFullscreen) {
        // -- we have something fullscreen
        selfClass.cthis.addClass(PLAYLIST_VIEW_FULLSCREEN_CLASS);
        if (selfClass.dataType === 'vimeo') {
          selfClass._vpInner.get(0).addEventListener('click', () => {
          }, false)
        }
      }


      if (o.touch_play_inline === 'on') {

        if (is_ios()) {
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

          if (selfClass.isAdPlaying === false && (selfClass.paused === false)) {

            if (typeof selfClass.ad_array == 'object' && selfClass.ad_array.length > 0) {

              for (let i2 in selfClass.ad_array) {


                var cach = selfClass.ad_array[i2];

                var cach_time = 0;


                if (cach.time) {
                  cach_time = cach.time;
                }
                if (cach.source && totalDuration && time_curr >= cach_time * totalDuration) {

                  player_setupAd(selfClass, i2, {'called_from': 'check_one_sec_for_adsOrTags'});

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
          const regex_subtitle = PLAYER_REGEX_SUBTITLE;
          var arr_subtitle = [];
          cthis.append('<div class="subtitles-con"></div>')


          while (arr_subtitle = regex_subtitle.exec(arg)) {

            let startTime = '';
            if (arr_subtitle[1]) {
              startTime = format_to_seconds(arr_subtitle[1]);
            }
            let endtime = '';
            if (arr_subtitle[2]) {
              arr_subtitle[2] = String(arr_subtitle[2]).replace('gt;', '');
              endtime = format_to_seconds(arr_subtitle[2]);
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

          if ((window.YT && window.YT.Player) || window._global_youtubeIframeAPIReady) {
            init_readyControls(selfClass,null, {
              'called_from': 'check_if_yt_iframe_ready'
            });
            clearInterval(selfClass.inter_checkYtIframeReady);
          }
        }

        fn_change_color_highlight(arg) {
          cthis.find('.scrub').eq(0).css({
            'background': arg
          })
          cthis.find('.volume_active').eq(0).css({
            'background': arg
          })
          cthis.find('.hdbutton-hover').eq(0).css({
            'color': arg
          })
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
          })
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

              player_setupQualitySelector(selfClass, selfClass.qualities_youtubeCurrentQuality, qualities_youtubeVideoQualitiesArray);

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


      if (!is_safari() && cthis.parent().parent().hasClass('sliderMain')) {
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


      if (is_ios() && o.touch_play_inline === 'off') {
        playMovie({
          'called_from': 'fullscreenToggle ios'
        });
        return false;
      }

      // -- we force fullscreen status to 1 if we are forcing a exit
      const fullscreenStatus = margs.force_exit_fullscreen ? 1 : fullscreen_status();


      // -- this was forced fullscreen so we exit it..
      if (fullscreenStatus === 0 && cthis.hasClass(PLAYLIST_VIEW_FULLSCREEN_CLASS)) {
        fullscreen_offActions();
        isFullscreen = 0;
        return false;
      }

      if (fullscreenStatus === 0) {
        isFullscreen = 1;

        cthis.addClass(PLAYLIST_VIEW_FULLSCREEN_CLASS);


        if (selfClass.is360 && is_ios()) {


          setTimeout(function () {

            handleResize(null, {
              'called_from': 'fullscreen 360'
            });
          }, PLAYER_DEFAULT_TIMEOUT)

        } else {

          if (is_ios() && selfClass._videoElement.webkitEnterFullscreen) {
            selfClass._videoElement.webkitEnterFullscreen();
            return false;
          }


          if (requestFullscreen($elemRequestFull) === null) {

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
          }, 700)

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
      cthis.removeClass(PLAYLIST_VIEW_FULLSCREEN_CLASS);
      cthis.find('.vplayer.' + PLAYLIST_VIEW_FULLSCREEN_CLASS).removeClass(PLAYLIST_VIEW_FULLSCREEN_CLASS);
      cthis.removeClass('is-fullscreen');

      if (o.design_skin === 'skin_reborn') {
        cthis.find('.full-tooltip').eq(0).html('FULLSCREEN');
      }


      handleResize();
      setTimeout(handleResize, 800);
    }

    function fullscreen_cancel_on_document() {

      var elem = document;

      if (fullscreen_status() === 1) {

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


                if ((i === 0 && j === 0) || (cthis.find('video[data-dzsvgindex="' + i + '' + j + '"]').length)) {
                  continue;
                }
                $(selfClass._videoElement).after($(selfClass._videoElement).clone());
                $(selfClass._videoElement).next().attr('data-dzsvgindex', String(i) + String(j));
                $(selfClass._videoElement).next().get(0).play();
                $(selfClass._videoElement).next().css({
                  'left': (i * natural_videow)
                  , 'top': (j * natural_videoh)
                })

              }
            }


            if (nr_w) {
              for (var i = 0; i < nr_w; i++) {

              }
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


      if (is_mobile()) {
        var d = new Date();

        if (selfClass.isHadFirstInteraction === false && o.autoplayWithVideoMuted === 'off' && margs.called_from.indexOf('autoplayNext') > -1 && (Number(d) - window.dzsvg_time_started < 1500)) {
          // -- no user action
          return false;
        }
      }
      isPlayCommited = true;
      if (!cthis.hasClass(PLAYER_STATES.LOADED) && selfClass.dataType !== 'vimeo') {
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
        if (is_mobile()) {
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

        pauseDzsapPlayers();
      }


      if (selfClass.dataType === 'selfHosted' || selfClass.dataType === 'vimeo' || selfClass.dataType === 'audio' || selfClass.dataType === 'dash') {
        try {
          video_play(selfClass);
        } catch (err) {
          console.info('[dzsvg] vg - ', err);
        }

      }
      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {
        if (!selfClass.paused) {
          return false;
        }
        video_play(selfClass);
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
          $(o.parent_player.get(0).gallery_object).removeClass('pretime-ad-setuped')
        }

      }


      if (o.google_analytics_send_play_event === 'on' && window._gaq && !google_analytics_sent_play_event) {
        window._gaq.push(['_trackEvent', 'Video Gallery Play', 'Play', 'video gallery play - ' + selfClass.dataSrc]);
        google_analytics_sent_play_event = true;
      }


      if (o.settings_disable_mouse_out !== 'on') {


        if (is_mobile()) {
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
          console.info('[vplayer] warning: video undefined')
        }
      }
      if (selfClass.dataType === VIDEO_TYPES.YOUTUBE) {

        if (selfClass._videoElement && selfClass._videoElement.pauseVideo) {

          try {
            selfClass._videoElement.pauseVideo();
          } catch (err) {
            console.log(err);

          }
        }
      }

      if (selfClass.dataType === 'vimeo') {
        vimeoPlayerCommand(selfClass, 'pause');
      }


      selfClass.wasPlaying = false;
      selfClass.paused = true;

      mouse_is_over();

      cthis.removeClass('is-playing');
    }


    try {
      cthis.get(0).checkYoutubeState = function () {
        if (selfClass.dataType === VIDEO_TYPES.YOUTUBE && selfClass._videoElement.getPlayerState) {
          if (selfClass._videoElement.getPlayerState && selfClass._videoElement.getPlayerState() === 0) {
            handleVideoEnd();
          }
        }
      }

    } catch (err) {
    }

  }
}


function dzsvp_handleInitedjQuery() {


//-------VIDEO PLAYER
  (function ($) {
    $.fn.vPlayer = function (argOptions) {

      var finalOptions = {};
      var defaultOptions = Object.assign({}, defaultPlayerSettings);
      finalOptions = convertPluginOptionsToFinalOptions(this, defaultOptions, argOptions);
      this.each(function () {

        var _vg = new DzsVideoPlayer(this, finalOptions, $);
        return this;
      }); // end each

    }


    window.dzsvp_init = function (selector, settings) {


      if (typeof (settings) != "undefined" && typeof (settings.init_each) != "undefined" && settings.init_each === true) {
        var element_count = 0;
        for (var e in settings) {
          element_count++;
        }
        if (element_count === 1) {
          settings = undefined;
        }

        $(selector).each(function () {
          var _t = $(this);
          _t.vPlayer(settings)
        });
      } else {
        $(selector).vPlayer(settings);
      }


    };
  })(jQuery);

  window.dzsvp_isLoaded = true;

  jQuery(document).ready(function ($) {
    dzsvp_init('.vplayer-tobe.auto-init', {init_each: true});


    registerAuxjQueryExtends($);
  });
}

loadScriptIfItDoesNotExist('', 'jQuery').then(() => {
  dzsvp_handleInitedjQuery();
})


dzsvgExtraWindowFunctions();

window.dzsvg_curr_embed_code = '';

