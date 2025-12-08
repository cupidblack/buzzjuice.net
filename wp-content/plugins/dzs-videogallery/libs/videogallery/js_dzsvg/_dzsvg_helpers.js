import {ads_decode_ads_array} from "../js_player/_player_setupAd";


import {ConstantsDzsvg, PLAYLIST_SCROLL_TOP_OFFSET} from '../configs/Constants';
import {
  svg_aurora_play_btn,
  svg_pause_simple_skin_aurora,
  svg_play_simple_skin_bigplay_pro,
  svgReplayIcon
} from "./_dzsvg_svgs";
import {get_query_arg, add_query_arg, is_safari, is_mobile, is_ios, is_android} from "../js_common/_dzs_helpers";



export function player_setQualityLevels(selfClass) {

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
    })

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
export function fullscreen_status() {
  if (document.fullscreenElement !== null && typeof document.fullscreenElement !== "undefined") {
    return 1;
  } else if (document.webkitFullscreenElement && typeof document.webkitFullscreenElement !== "undefined") {
    return 1;
  } else if (document.mozFullScreenElement && typeof document.mozFullScreenElement !== "undefined") {
    return 1;
  }
  ;
  return 0
}


function is_chrome() {
  return navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
}
;

export function player_controls_generatePlayCon(o) {

  var structPlayControls = '';
  structPlayControls = '<div class="playSimple dzsvgColorForFills">';
  if (o.design_skin == 'skin_bigplay_pro') {

    structPlayControls += svg_play_simple_skin_bigplay_pro;
  }
  if (o.design_skin == 'skin_aurora' || o.design_skin == 'skin_bigplay' || o.design_skin == 'skin_avanti' || o.design_skin == 'skin_default' || o.design_skin == 'skin_pro' || o.design_skin == 'skin_white') {
    structPlayControls += svg_aurora_play_btn;
  }


  structPlayControls += '</div><div class="pauseSimple dzsvgColorForFills">';
  if (o.design_skin == 'skin_aurora' || o.design_skin == 'skin_pro' || o.design_skin == 'skin_bigplay' || o.design_skin == 'skin_avanti' || o.design_skin == 'skin_default' || o.design_skin == 'skin_white') {

    structPlayControls += svg_pause_simple_skin_aurora;
  }
  structPlayControls += '</div>';


  structPlayControls += '<div class="dzsvg-player--replay-btn dzsvgColorForFills">';
  structPlayControls += svgReplayIcon;

  structPlayControls += '</div>';


  return structPlayControls;
}

export function dzsvg_call_video_when_ready(o, selfClass, init_readyVideo, vimeo_is_ready, inter_videoReadyState) {

  const _videoElement = selfClass._videoElement


  if (o.type === 'youtube' && _videoElement.getPlayerState) {

    init_readyVideo(selfClass);
  }


  if (o.cueVideo != 'on' && (o.type == 'selfHosted' || o.type == 'audio') && Number(_videoElement.readyState) >= 2) {
    init_readyVideo(selfClass,{
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
    if (is_mobile()) {
      if (Number(_videoElement.readyState) >= 1) {

        init_readyVideo(selfClass);
        return false;
      }
    }


    if (Number(_videoElement.readyState) >= 3) {
      clearInterval(inter_videoReadyState);
      init_readyVideo(selfClass,{
        'called_from': 'check_videoReadyState'
      });
      return false;
    }
  }
  if (o.type === 'selfHosted') {


    if (is_ios()) {

      if (Number(_videoElement.readyState) >= 1) {

        init_readyVideo(selfClass);
        return false;
      }
    }

    if (is_android()) {
      if (Number(_videoElement.readyState) >= 2) {

        init_readyVideo(selfClass);
        return false;
      }
    }


    if (Number(_videoElement.readyState) >= 3 || o.preload_method === 'none') {
      clearInterval(inter_videoReadyState);
      init_readyVideo(selfClass,{
        'called_from': 'check_videoReadyState'
      });
      return false;
    }
  }


  // --- WORKAROUND __ for some reason ios default browser would not go over video ready state 1

  if (o.type === 'dash') {

    clearInterval(inter_videoReadyState)
    init_readyVideo(selfClass, {
      'called_from': 'check_videoReadyState'
    });
  }


}


export function dzsvg_check_multisharer() {



}



export function sanitize_to_youtube_id(arg = '') {
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
export function getDataOrAttr(_c, attr) {
  if (_c.data && typeof _c.data(attr) != 'undefined') {
    return _c.data(attr);
  }
  if (_c.attr && typeof _c.attr(attr) != 'undefined') {
    return _c.attr(attr);
  }

  return null;
}

export function detect_videoTypeAndSourceForElement(_el) {

  if (_el.data('originalPlayerAttributes')) {
    return _el.data('originalPlayerAttributes');
  }

  var dataSrc = getDataOrAttr(_el, 'data-sourcevp');

  var forceType = getDataOrAttr(_el, 'data-type') ? getDataOrAttr(_el, 'data-type') : '';

  return detect_video_type_and_source(dataSrc, forceType)
}

/**
 * detect video type and source
 * @param {string} dataSrc
 * @param forceType we might want to force the type if we know it
 * @param cthis
 * @returns {{source: *, playFrom: null, type: string}}
 */
export function detect_video_type_and_source(dataSrc, forceType = null, cthis = null) {


  dataSrc = String(dataSrc);

  var playFrom = null;
  var type = 'selfHosted';
  var source = dataSrc;

  if (dataSrc.indexOf('youtube.com/watch?') > -1 || dataSrc.indexOf('youtube.com/embed') > -1 || dataSrc.indexOf('youtu.be/') > -1) {
    type = 'youtube';

    var aux = /http(?:s?):\/\/(?:www\.)?youtu(?:be\.com\/watch\?v=|\.be\/)([\w\-\_]*)(&(amp;)?‌​[\w\?‌​=]*)?/g.exec(dataSrc);


    if (get_query_arg(dataSrc, 't')) {
      playFrom = get_query_arg(dataSrc, 't');
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

export function sanitizeDataAdArrayStringToArray(aux) {

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

export function is_autoplay_and_muted(autoplay, o) {

  return ((1) && autoplay === 'on' && o.autoplayWithVideoMuted === 'on' && o.user_action === 'noUserActionYet') || (o.defaultvolume === 0 && o.defaultvolume !== '');

}


export function setup_videogalleryCategories(arg) {
  var ccat = jQuery(arg);
  var currCatNr = -1;

  ccat.find('.gallery-precon').each(function () {
    var _t = jQuery(this);

    _t.css({'display': 'none'});
    ccat.find('.the-categories-con').append('<span class="a-category">' + _t.attr('data-category') + '</span>')
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


      auxurl = add_query_arg(auxurl, ccat.attr('id') + '_cat', NaN);


      auxurl = add_query_arg(auxurl, ccat.attr('id') + '_cat', i2);

      _t2.attr('href', auxurl);
    })

    i2++;
  })

  var tempCat = 0;


  if (get_query_arg(window.location.href, ccat.attr('id') + '_cat')) {
    tempCat = Number(get_query_arg(window.location.href, ccat.attr('id') + '_cat'));
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
      })


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

          setTimeout(function () {


          }, 1000);
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

export function youtube_sanitize_url_to_id(arg) {


  if (arg) {

    if (String(arg).indexOf('youtube.com/embed') > -1) {
      var auxa = String(dataSrc).split('youtube.com/embed/');


      if (auxa[1]) {

        return auxa[1];
      }
    }

    if (arg.indexOf('youtube.com') > -1 || arg.indexOf('youtu.be') > -1) {


      if (get_query_arg(arg, 'v')) {
        return get_query_arg(arg, 'v');
      }

      if (arg.indexOf('youtu.be') > -1) {
        var arr = arg.split('/');

        arg = arr[arr.length - 1];
      }
    }
  }


  return arg;
}


export function registerAuxjQueryExtends($) {


  $.fn.appendOnce = function (arg, argfind) {
    var _t = $(this) // It's your element


    if (typeof (argfind) == 'undefined') {
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


    })

  }

  // -- we save the other youtube player ready functions ( maybe conflict with other players )
  if (window.onYouTubePlayerReady && typeof window.onYouTubePlayerReady == 'function' && typeof backup_onYouTubePlayerReady == 'undefined') {
    window.dzsvg_backup_onYouTubePlayerReady = window.onYouTubePlayerReady;
  }
}

export function dzsvgExtraWindowFunctions() {


  window.dzsvg_wp_send_view = function (argcthis, argtitle) {
    var data = {
      video_title: argtitle
      , video_analytics_id: argcthis.attr('data-player-id')
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
      success: function (response) {


      },
      error: function (arg) {
      }
    });


  }


  window.dzsvg_wp_send_contor_60_secs = function (argcthis, argtitle) {

    var data = {
      video_title: argtitle

      , video_analytics_id: argcthis.attr('data-player-id')
      , dzsvg_curr_user: window.dzsvg_curr_user
    };
    var theajaxurl = 'index.php?action=ajax_dzsvg_submit_contor_60_secs';

    if (window.dzsvg_site_url) {

      theajaxurl = dzsvg_settings.dzsvg_site_url + theajaxurl;
    }


    jQuery.ajax({
      type: "POST",
      url: theajaxurl,
      data: data,
      success: function (response) {

      },
      error: function (arg) {
        ;
      }
    });
  }


  window.dzsvg_open_social_link = function (urlTemplate) {
    const currentUrl = encodeURIComponent(window.location.href);
    const finalUrl = urlTemplate.replace(/{{replacewithcurrurl}}/g, currentUrl);

    // Use Web Share API if supported (mobile-friendly)
    if (navigator.share) {
      navigator.share({
        title: document.title,
        url: finalUrl,
      }).catch((err) => {
        console.warn('Web Share failed:', err);
      });
      return;
    }

    // Fallback: open a new window
    const width = 500;
    const height = 500;
    const windowOptions = [
      `width=${width}`,
      `height=${height}`,
      'resizable=yes',
      'scrollbars=yes'
    ].join(',');

    const popup = window.open(finalUrl, '_blank', windowOptions);
    if (!popup) {
      console.warn('Popup blocked. Please allow popups for this site.');
    }
  };



  window.dzsvp_yt_iframe_ready = function () {
    _global_youtubeIframeAPIReady = true;
  }

  window.onYouTubeIframeAPIReady = function () {
    window.dzsvg_yt_ready = true;
    window.dzsvp_yt_iframe_ready();
  }


}


export function extractOptionsFromPlayer($c) {


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


export function convertPluginOptionsToFinalOptions(elThis, defaultOptions, argOptions = null, searchedAttr = 'data-options', searchedDivClass = 'feed-options') {

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

export function player_setupQualitySelector(selfClass, yt_qualCurr, yt_qualArray) {
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

export function playerHandleDeprecatedAttrSrc(cthis) {


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

export function player_assert_autoplay(selfClass) {


  // -- autoplay assert

  var o = selfClass.initOptions;


  if (is_mobile()) {

  }


}

export function configureAudioPlayerOptionsInitial(cthis, o, selfClass) {


  if (o.gallery_object != null) {
    if (typeof (o.gallery_object.get(0)) != 'undefined') {
      selfClass.$parentGallery = o.gallery_object;


      setTimeout(function () {
        if (selfClass.$parentGallery.get(0).api_video_ready) {
          selfClass.$parentGallery.get(0).api_video_ready();
        }
      }, ConstantsDzsvg.DELAY_MINUSCULE);
    }
  }


  if (is_mobile() || (o.first_video_from_gallery === 'on' && (is_safari()))) {
    if (is_mobile()) {
      cthis.addClass('is-mobile');
    }
    if (cthis.attr('data-img')) {
    } else {
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
  if (typeof (cthis.attr('class')) == 'string') {
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


  if (is_mobile() || is_ios()) {
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


  if (o.responsive_ratio === 'default' || (selfClass.dataType === 'youtube' && o.responsive_ratio === 'detect')) {

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
    if (o.gallery_target_index === 0 && !(selfClass.cthis.data('originalPlayerAttributes'))) {
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


export function reinitPlayerOptions(selfClass, o) {
  // -- we need  selfClass.dataType and selfClass.dataSrc beforeHand


  selfClass.hasCustomSkin = true;
  // -- assess custom skin
  if (selfClass.dataType === 'vimeo' && o.vimeo_is_chromeless !== 'on') {
    selfClass.hasCustomSkin = false;
  }
  if (selfClass.dataType === 'youtube' && o.settings_youtube_usecustomskin !== 'on') {
    selfClass.hasCustomSkin = false;
  }
  if (is_ios() && o.settings_ios_usecustomskin !== 'on') {
    selfClass.hasCustomSkin = false;
  }
  if (selfClass.dataType === 'inline') {

    selfClass.hasCustomSkin = false;
  }


  if (selfClass.cthis.attr('data-ad-array')) {
    selfClass.ad_array = sanitizeDataAdArrayStringToArray(selfClass.cthis.attr('data-ad-array'));
  }
  ads_decode_ads_array(selfClass);


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
    if (!is_mobile() && selfClass.$parentGallery && selfClass.$parentGallery.hasClass('user-had-first-interaction')) {
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
      if (is_mobile()) {
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

export function tagsSetupDom(_tagElement) {
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
  _tagElement.css({'left': (_tagElement.attr('data-left') + 'px'), 'top': (_tagElement.attr('data-top') + 'px')});

  _tagElement.append('<div class="tag-box" style="width:' + w + 'px; height:' + h + 'px;">' + acomlink + '</div>');
  _tagElement.append('<span class="tag-content">' + auxhtml + '</span>');
  _tagElement.removeClass('dzstag-tobe').addClass('dzstag');

}

export function pauseDzsapPlayers() {
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

export function init_navigationOuter() {
  jQuery('.videogallery--navigation-outer').each(function () {
    var _t = jQuery(this);


    var xpos = 0;
    _t.find('.videogallery--navigation-outer--bigblock').each(function () {
      var _t = jQuery(this);
      _t.css('left', xpos + '%');
      xpos += 100;
    })


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
      }, 1000)
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
    }, 500)

    _navOuterBlocks.on('click', function (e) {
      const $outerBlock = jQuery(this);
      const ind = _navOuterBlocks.index($outerBlock);


      if ($targetVideoGallery.get(0) && $targetVideoGallery.get(0).api_gotoItem) {


        if ($targetVideoGallery.get(0).SelfPlaylist) {
          const SelfPlaylist = $targetVideoGallery.get(0).SelfPlaylist;
          SelfPlaylist.handleHadFirstInteraction(e);
        }

        if ($targetVideoGallery.get(0).api_gotoItem(ind)) {
        }

        const scrollY = $targetVideoGallery.offset().top - PLAYLIST_SCROLL_TOP_OFFSET;
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

    })

    function gotoPage(arg) {
      var auxl = -(Number(arg) * 100) + '%';

      _navOuterBullets.removeClass('active');
      _navOuterBullets.eq(arg).addClass('active');

      _t.find('.videogallery--navigation-outer--bigblock.active').removeClass('active');
      _t.find('.videogallery--navigation-outer--bigblock').eq(arg).addClass('active');


      _clip.height(_t.find('.videogallery--navigation-outer--bigblock').eq(arg).height());

      _clipmover.css('left', auxl);

    }


  })
}

export function vimeo_do_command(selfClass, vimeo_data, vimeo_url) {

  if (vimeo_url) {

    if (selfClass._videoElement && selfClass._videoElement.contentWindow && vimeo_url) {

      selfClass._videoElement.contentWindow.postMessage(JSON.stringify(vimeo_data), vimeo_url);
    }
  }
}




