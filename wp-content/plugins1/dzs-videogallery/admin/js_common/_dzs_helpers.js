exports.get_query_arg = function (purl, key) {
  if (purl.indexOf(key + '=') > -1) {
    var regexS = "[?&]" + key + "(.+?)(?=&|$)";
    var regex = new RegExp(regexS);
    var regtest = regex.exec(purl);


    if (regtest ) {


      if (regtest[1]) {
        var aux = regtest[1].replace(/=/g, '');
        return aux;
      } else {
        return '';
      }


    }
  }
}


exports.sanitize_to_youtube_id = function (arg) {

  if (String(arg).indexOf('youtube.com/watch')) {

    var dataSrc = arg;
    var auxa = String(dataSrc).split('youtube.com/watch?v=');
    if (auxa[1]) {

      dataSrc = auxa[1];
      if (auxa[1].indexOf('&') > -1) {
        var auxb = String(auxa[1]).split('&');
        dataSrc = auxb[0];
      }


      return dataSrc;
    }
  }

  return arg;
}


/**
 * detect video type and source
 * @param dataSrc
 * @param forceType we might want to force the type if we know it
 * @returns {{source: *, playFrom: null, type: string}}
 */
exports.detect_video_type_and_source = function (dataSrc, forceType = null, cthis = null) {

  var playFrom = null;
  var type = 'selfHosted';
  var source = dataSrc;
  if (String(dataSrc).indexOf('youtube.com/watch?') > -1 || String(dataSrc).indexOf('youtube.com/embed') > -1 || String(dataSrc).indexOf('youtu.be/') > -1) {
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

  if (String(dataSrc).indexOf('vimeo.com/') > -1) {
    type = 'vimeo';

    var aux = /(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:[a-zA-Z0-9_\-]+)?/g.exec(dataSrc);


    if (aux && aux[1]) {
      source = aux[1];
    }
  }

  if (String(dataSrc).indexOf('.mp4') > -1) {
    type = 'selfHosted';

  }
  if (String(dataSrc).indexOf('.mpd') > String(dataSrc).length - 5) {
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