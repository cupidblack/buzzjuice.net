import {loadScriptIfItDoesNotExist} from "../js_common/_dzs_helpers";
import {PLAYER_DEFAULT_RESPONSIVE_RATIO} from "../configs/Constants";

export function dash_setupPlayer(selfClass) {

  var dash_player = null
    , dash_context = null
  ;


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
  loadScriptIfItDoesNotExist(baseUrl + 'parts/player/dash.js', 'Webm').then(r => {
    setup_dash();
  });
}


/**
 *
 * @param {DzsVideoPlayer} selfClass
 * @param {object} pargs
 */
export function player_getResponsiveRatio(selfClass, pargs) {

  var $ = jQuery;
  var o = selfClass.initOptions;

  var margs = {
    'reset_responsive_ratio': false
    , 'called_from': 'default'
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
        o.responsive_ratio = PLAYER_DEFAULT_RESPONSIVE_RATIO;
      }

      if (selfClass._videoElement && selfClass._videoElement.addEventListener) {
        selfClass._videoElement.addEventListener('loadedmetadata', function () {
          o.responsive_ratio = selfClass._videoElement.videoHeight / selfClass._videoElement.videoWidth;
          selfClass.handleResize();
        })
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
      o.responsive_ratio = PLAYER_DEFAULT_RESPONSIVE_RATIO;
    }
    if (selfClass.dataType === 'vimeo') {
      o.responsive_ratio = PLAYER_DEFAULT_RESPONSIVE_RATIO;
    }
    if (selfClass.dataType === 'inline') {
      o.responsive_ratio = PLAYER_DEFAULT_RESPONSIVE_RATIO;
    }

  }
  o.responsive_ratio = Number(o.responsive_ratio);

  if (selfClass.cthis.hasClass('vp-con-laptop')) {
    o.responsive_ratio = '';
  }
}



export function exitFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.mozCancelFullScreen) {
    document.mozCancelFullScreen();
  } else if (document.webkitExitFullscreen) {
    document.webkitExitFullscreen();
  }

  return null;
}

export function requestFullscreen($elem_) {
  if ($elem_) {

    if ($elem_.requestFullScreen) {
      return $elem_.requestFullScreen();
    } else if ($elem_.webkitRequestFullScreen) {
      return $elem_.webkitRequestFullScreen()
    } else if ($elem_.mozRequestFullScreen) {
      return $elem_.mozRequestFullScreen()
    }
  }

  return null;
}