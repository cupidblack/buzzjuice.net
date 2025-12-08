(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
exports._feedbacker=null,exports.timeoutClearFeedbackId=null,exports.feedbacker_initSetup=function(){this._feedbacker=jQuery(".dzs-feedbacker").eq(0),this._feedbacker.fadeOut("fast")},exports.show_feedback=function(e,t){var r={extra_class:""};t&&(r=jQuery.extend(r,t));var a="",s="dzs-feedbacker";"object"==typeof e?("error"===e.ajax_status&&(s+=" is-error"),a=e.ajax_message):(0===e.indexOf("success - ")&&(a=e.substr(10)),0===e.indexOf("error - ")&&(a=e.substr(8),s+=" is-error")),r.extra_class&&(s+=" "+r.extra_class),this._feedbacker&&(this._feedbacker.attr("class",s),this._feedbacker.html(a),this._feedbacker.fadeIn("fast"));var c=this;clearTimeout(this.timeoutClearFeedbackId),this.timeoutClearFeedbackId=setTimeout(function(){c._feedbacker&&c._feedbacker.fadeOut("slow")},2e3)};
},{}],2:[function(require,module,exports){
exports.get_query_arg = function (purl, key) {
  if (purl.indexOf(key + '=') > -1) {
    var regexS = "[?&]" + key + "(.+?)(?=&|$)";
    var regex = new RegExp(regexS);
    var regtest = regex.exec(purl);

    if (regtest) {
      if (regtest[1]) {
        var aux = regtest[1].replace(/=/g, '');
        return aux;
      } else {
        return '';
      }
    }
  }
};

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
};
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
};

},{}],3:[function(require,module,exports){
exports.detectParentSliderItemCon=function(e){var a=null;return e.parent().parent().parent().parent().hasClass("slider-item")&&(a=e.parent().parent().parent().parent()),e.parent().parent().parent().parent().parent().hasClass("slider-item")&&(a=e.parent().parent().parent().parent().parent()),e.parent().parent().parent().parent().parent().parent().hasClass("slider-item")&&(a=e.parent().parent().parent().parent().parent().parent()),e.parent().parent().parent().parent().parent().parent().parent().hasClass("slider-item")&&(a=e.parent().parent().parent().parent().parent().parent().parent()),e.parent().parent().parent().parent().parent().parent().parent().parent().hasClass("slider-item")&&(a=e.parent().parent().parent().parent().parent().parent().parent().parent()),e.parent().parent().parent().parent().parent().parent().parent().parent().parent().hasClass("slider-item")&&(a=e.parent().parent().parent().parent().parent().parent().parent().parent().parent()),e.parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().hasClass("slider-item")&&(a=e.parent().parent().parent().parent().parent().parent().parent().parent().parent().parent()),a};
},{}],4:[function(require,module,exports){
exports.realtimeValidatorsInit = function ($) {
  // -- custom validators
  $(document).on('keyup', '*[data-aux-name="vimeo_source"]', e => {
    var isValid = true;
    var $t = $(e.currentTarget);
    var val = e.currentTarget.value;

    if (val.indexOf('manage/folder') > -1) {
      isValid = false;
    }

    if (isValid) {
      $t.removeClass('is-not-valid');
    } else {
      $t.addClass('is-not-valid');
    }
  });
};

},{}],5:[function(require,module,exports){
"use strict";function dzsvg_slidersAdmin_specifics_init(){function i(){var i=e(this);if(console.log(i),"vimeo_source"==i.attr("data-aux-name")){i.val().indexOf("showcase")>-1?e("body").addClass("slidersAdmin--visible-for--dzsvg--sliders-admin--vimeo-api-requires-user-id"):e("body").removeClass("slidersAdmin--visible-for--dzsvg--sliders-admin--vimeo-api-requires-user-id")}}var e=jQuery;e('*[data-aux-name="vimeo_source"]').on("change",i),e('*[data-aux-name="vimeo_source"]').trigger("change")}Object.defineProperty(exports,"__esModule",{value:!0}),exports.dzsvg_slidersAdmin_specifics_init=dzsvg_slidersAdmin_specifics_init;
},{}],6:[function(require,module,exports){
'use strict';

var _dzsvg_specifics = require("./slidersAdmin/dzsvg/_dzsvg_specifics");

var dzsHelpers = require('./js_common/_dzs_helpers');

var dzsFeedbacker = require('./js_common/_dzs_feedbacker');

var slidersAdminHelper = require('./slidersAdmin/_slidersAdmin-helper');

var slidersAdminValidators = require('./slidersAdmin/_validators');

jQuery(document).ready(function ($) {
  // -- we ll create queue calls so that we send ajax only once
  var inter_queued_calls = 0;
  var ajax_queue = [];
  var inter_send_to_ajax = 0;
  var term_id = 0;
  let isSaving = false;

  const _sliderItems = $('.dzsvg-slider-items').eq(0);

  let _slidersCon = $('.dzsvg-sliders-con').eq(0);

  const SLIDER_PAGE_TYPE = {
    'SINGLE': 'slider_single',
    'MULTIPLE': 'slider_multiple'
  };
  let currentPageType = SLIDER_PAGE_TYPE.SINGLE;

  if (dzsHelpers.get_query_arg(window.location.href, 'taxonomy') === 'dzsvg_sliders' && dzsHelpers.get_query_arg(window.location.href, 'post_type') === 'dzsvideo' && (typeof dzsHelpers.get_query_arg(window.location.href, 'tag_ID') == 'undefined' || typeof dzsHelpers.get_query_arg(window.location.href, 'tag_ID') == '')) {
    currentPageType = SLIDER_PAGE_TYPE.MULTIPLE;
  }

  dzsFeedbacker.feedbacker_initSetup();
  slidersAdminValidators.realtimeValidatorsInit($);

  function handleChangeInTargetFields() {
    let slug = '';
    slug = jQuery('#slug').val();
    var sampleShortcode = '[videogallery id="{{theslug}}"]';
    sampleShortcode = sampleShortcode.replace(/{{theslug}}/g, slug);
    jQuery('.dzssa--sample-shortcode-area--readonly').text(sampleShortcode);
  }

  jQuery('#slug').on('change', handleChangeInTargetFields);
  handleChangeInTargetFields();
  var slider_term_id = 0;
  var slider_term_slug = '';
  const $dzsvgSlidersCon = $('.dzsvg-sliders-con');

  if (currentPageType === SLIDER_PAGE_TYPE.SINGLE) {
    slider_term_id = $dzsvgSlidersCon.eq(0).attr('data-term_id');
    slider_term_slug = $dzsvgSlidersCon.eq(0).attr('data-term-slug');
  }

  setTimeout(function () {
    $('body').addClass('sliders-loaded');
  }, 600);
  const $wrapH1 = $('.wrap > h1');
  $wrapH1.wrapInner('<span class="label"></span>');
  let slugText = window.dzsvg_settings.version;

  if (currentPageType === SLIDER_PAGE_TYPE.SINGLE) {
    slugText = $('h3.slider-label .the-gallery-slugger').html();
  }

  $wrapH1.append('<span class="the-gallery-slug">( ' + slugText + ' )</span>');
  setTimeout(function () {
    if ($.fn.spectrum) {
      $(".color-with-spectrum").spectrum({
        showAlpha: true,
        allowEmpty: true
      });
    }
  }, 50);

  if (currentPageType === SLIDER_PAGE_TYPE.MULTIPLE) {
    $('body').addClass('page-slider-multiple');

    var _colContainer = $('#col-container');

    _colContainer.before('<div class="sliders-con"></div>');

    _colContainer.after('<div class="add-slider-con"></div>');

    _slidersCon = _colContainer.prev();

    var addSliderCon = _colContainer.next();

    _slidersCon.append(_colContainer.find('#col-right').eq(0));

    $('#footer-thankyou').hide();
    $dzsvgSlidersCon.hide();

    _slidersCon.find('.row-actions > .edit > a').css('margin-right', '15px');

    _slidersCon.find('.row-actions > .edit > a').wrapInner('<span class="the-text"></span>');

    _slidersCon.find('.row-actions > .edit > a').addClass('dzs-button btn-style-default skinvariation-border-radius-more btn-padding-medium text-strong color-normal-highlight color-over-dark font-size-small');

    $('#screen-meta-links').prepend('<div id="import-options-link-wrap" class="hide-if-no-js screen-meta-toggle">\n' + '\t\t\t<button type="button" id="show-settings-link" class="button show-settings" aria-controls="screen-options-wrap" aria-expanded="false">Import</button>\n' + '\t\t\t</div>'); // -- end slider multiple

    $('#screen-options-wrap').after($('.import-slider-form'));
  }

  if (currentPageType == SLIDER_PAGE_TYPE.SINGLE) {
    $('body').addClass('page-slider-single');
    $('.dzsvg-sliders').before($('#edittag').eq(0));
    $('#edittag').prepend($('.tag-options-con').eq(0));
    $('.form-table:not(.custom-form-table)').addClass('sa-category-main');

    var _sa_categoryMain = $('.sa-category-main').eq(0);

    _sa_categoryMain.find('tr').eq(1).after('<div class="clear"></div>');

    _sa_categoryMain.find('.term-description-wrap').eq(0).after('<div class="clear"></div>');

    $('.tab-content-cat-main').append(_sa_categoryMain);
    dzstaa_init('#tabs-box');
    dzstaa_init('.dzs-tabs-meta-item', {
      init_each: true
    });
    (0, _dzsvg_specifics.dzsvg_slidersAdmin_specifics_init)();
  }

  setTimeout(function () {
    $('.slider-status').removeClass('empty');
  }, 300);
  setTimeout(function () {
    $('.slider-status').removeClass('loading');
  }, 500);
  setTimeout(function () {
    // -- we place this here so that it won't fire with no reason ;)
    $(document).on('change', 'input.setting-field,select.setting-field,textarea.setting-field,*[name=dzsvg_meta_featured_media]', handle_change);
    $(document).on('keyup', 'input.setting-field,select.setting-field,textarea.setting-field', handle_change);
    $('.slider-status').addClass('empty');
  }, 1000);
  $(document).on('change.sliders_admin', '*[name=the_post_title]', handle_change);
  $(document).on('click.sliders_admin', '.slider-item, .slider-item > .divimage, .add-btn-new, .add-btn-existing-media, .delete-btn,.clone-item-btn, #import-options-link-wrap, .button-primary, .btn-import-folder', handle_mouse);
  $(document).on('click', '#addtag input[type="submit"]', handle_submitAddPlaylist);
  $(document).on('submit', 'form#addtag', handle_submitAddPlaylist);

  function handle_submitAddPlaylist(e) {
    action_onSubmittedPlaylist();
  }

  function action_onSubmittedPlaylist() {
    window.scrollTo(0, 0);
  }

  window.onbeforeunload = function () {
    if (isSaving) {
      return "Are you sure you want to navigate away?";
    }
  };

  setTimeout(function () {
    if (currentPageType === SLIDER_PAGE_TYPE.SINGLE && dzsHelpers.get_query_arg(window.location.href, 'taxonomy') === 'dzsvg_sliders') {
      $('.wrap').eq(0).append($('.dzsvg-sliders-con').eq(0));
    }

    _sliderItems.sortable({
      placeholder: "ui-state-highlight",
      items: ".slider-item",
      stop: function (event, ui) {
        var arr_order = [];
        var i = 1;

        _sliderItems.children().each(function () {
          var _t = $(this);

          var aux = {
            'id': _t.attr('data-id'),
            'order': i++
          };
          arr_order.push(aux);
        });

        var queue_call = {
          'type': 'set_meta_order',
          'items': arr_order,
          'term_id': slider_term_id
        };
        ajax_queue.push(queue_call);
        prepare_send_queue_calls();
      }
    });
  }, 500);

  function handle_change(e) {
    var _t = $(this);

    var $conSliderItem = null;

    if (e.type === 'change' || e.type === 'keyup') {
      $conSliderItem = slidersAdminHelper.detectParentSliderItemCon(_t);

      if (_t.attr('name') === 'dzsvg_meta_featured_media') {
        var _thumbinp = $conSliderItem.find('*[name=dzsvg_meta_thumb]');

        if (_thumbinp.val() === '') {
          $conSliderItem.find('.refresh-main-thumb').trigger('click');
        }
      }

      if (_t.attr('name') == 'the_post_title') {
        $conSliderItem.find('.slider-item--title').html(_t.val());
      } // -- change the thumbnail


      if (String(_t.attr('name')).indexOf('meta_thumb') > -1) {
        $conSliderItem.find('.divimage').eq(0).css({
          'background-image': 'url(' + _t.val() + ')'
        });
      }

      if ($conSliderItem) {
        var id = $conSliderItem.attr('data-id');
        var queue_call = {
          'type': 'set_meta',
          'item_id': id,
          'lab': _t.attr('name'),
          'val': _t.val()
        };
        var sw_found_and_set = false;

        for (var lab in ajax_queue) {
          var val = ajax_queue[lab];

          if (val.type == 'set_meta') {
            if (val.item_id == id) {
              if (val.lab == _t.attr('name')) {
                ajax_queue[lab].val = _t.val();
                sw_found_and_set = true;
              }
            }
          }
        }

        if (sw_found_and_set == false) {
          ajax_queue.push(queue_call);
        }

        prepare_send_queue_calls();
      }
    }
  }

  function handle_mouse(e) {
    var _t = $(this);

    if (e.type == 'click') {
      if (_t.attr('id') == 'import-options-link-wrap') {
        var _c = $('#screen-options-wrap');

        if (_t.hasClass('active') == false) {
          $('.import-slider-form').show();
          $('#screen-meta').slideDown('fast');
          $('#screen-options-link-wrap').fadeOut('fast');

          _t.addClass('active');
        } else {
          $('#screen-meta').slideUp('fast');
          $('.import-slider-form').fadeOut('fast');
          $('#screen-options-link-wrap').fadeIn('fast');

          _t.removeClass('active');
        }
      }

      if (_t.hasClass('btn-import-folder')) {
        var data = {
          action: 'dzsvg_import_folder',
          payload: $('*[data-aux-name="folder_location"]').val()
        };
        $('*[data-aux-name="folder_location"], .btn-import-folder').prop('disabled', true);
        jQuery.ajax({
          type: "POST",
          url: window.ajaxurl,
          data: data,
          success: function (response) {
            response = parse_response(response);

            if (typeof response == 'object' && response.ajax_status) {
              if (window) {
                dzsFeedbacker.show_feedback(response);
              }
            }

            $('*[data-aux-name="folder_location"], .btn-import-folder').prop('disabled', false);

            if (response.files) {
              for (var i in response.files) {
                var cach = response.files[i];
                $('.for-feed_mode-import-folder').addClass('loading');
                var sw_continue = true;
                $('.slider-item').each(function () {
                  var _t4 = $(this);

                  if (_t4.find('input[name="dzsvg_meta_featured_media"]').val() === cach.url) {
                    sw_continue = false;
                  }
                });

                if (sw_continue) {
                  var queue_call = {
                    'type': 'create_item',
                    'term_id': $('.dzsvg-sliders-con').eq(0).attr('data-term_id'),
                    'term_slug': $('.dzsvg-sliders-con').eq(0).attr('data-term-slug'),
                    'dzsvg_meta_item_type': 'video',
                    'post_title': cach.name,
                    'dzsvg_meta_featured_media': cach.url,
                    'dzsvg_meta_item_path': cach.path
                  };
                  queue_call['dzsvg_meta_order_' + slider_term_id] = 1 + _sliderItems.children().length + 0;
                  ajax_queue.push(queue_call);
                  prepare_send_queue_calls(10);
                  setTimeout(function () {
                    dzstaa_init('.dzs-tabs-meta-item', {
                      init_each: true
                    });
                    dzssel_init('select.dzs-style-me', {
                      init_each: true
                    });
                    $('*[data-aux-name="feed_mode"]').parent().find('.bigoption').eq(0).trigger('click');
                  }, 1000);
                }

                setTimeout(function () {
                  $('.for-feed_mode-import-folder').removeClass('loading');
                }, 1000);
              }
            }
          },
          error: function (arg) {
            console.warn('Got this from the server / error: ' + arg);
          }
        });
        return false;
      }

      if (_t.hasClass('button-primary')) {
        if (ajax_queue.length) {
          prepare_send_queue_calls(10);
          setTimeout(function () {
            $('.button-primary').trigger('click');
          }, 1000);
          return false;
        }
      }

      if (_t.hasClass('delete-btn')) {
        var queue_call = {
          'type': 'delete_item',
          'id': _t.parent().attr('data-id'),
          'term_slug': slider_term_slug
        };
        ajax_queue.push(queue_call);
        prepare_send_queue_calls(10);

        _t.parent().remove();

        return false;
      }

      if (_t.hasClass('clone-item-btn')) {
        var queue_call = {
          'type': 'duplicate_item',
          'id': _t.parent().attr('data-id'),
          'term_slug': slider_term_slug
        };
        ajax_queue.push(queue_call);
        prepare_send_queue_calls(10);
        return false;
      }

      if (_t.hasClass('add-btn--icon')) {
        var queue_call = {
          'type': 'create_item',
          'term_id': $('.dzsvg-sliders-con').eq(0).attr('data-term_id'),
          'term_slug': $('.dzsvg-sliders-con').eq(0).attr('data-term-slug')
        };
        queue_call['dzsvg_meta_order_' + slider_term_id] = 1 + _sliderItems.children().length + 0;
        ajax_queue.push(queue_call);
        prepare_send_queue_calls(10);
      }

      if (_t.hasClass('add-btn-new')) {
        var queue_call = {
          'type': 'create_item',
          'term_id': $('.dzsvg-sliders-con').eq(0).attr('data-term_id'),
          'term_slug': $('.dzsvg-sliders-con').eq(0).attr('data-term-slug')
        };
        queue_call['dzsvg_meta_order_' + slider_term_id] = 1 + _sliderItems.children().length + 0;
        ajax_queue.push(queue_call);
        prepare_send_queue_calls(10);
      }

      if (_t.hasClass('add-btn-existing-media')) {
        var _t = $(this);

        var _targetInput = _t.prev();

        var searched_type = '';

        if (_t.hasClass('upload-type-audio') || _targetInput.hasClass('upload-type-audio')) {
          searched_type = 'audio';
        }

        if (_targetInput.hasClass('upload-type-video')) {
          searched_type = 'video';
        }

        if (_targetInput.hasClass('upload-type-image')) {
          searched_type = 'image';
        }

        var frame = wp.media.frames.dzsp_addimage = wp.media({
          title: "Insert Media",
          multiple: true,
          library: {
            type: searched_type
          },
          // Customize the submit button.
          button: {
            // Set the text of the button.
            text: "Insert Media",
            close: true
          }
        }); // When an image is selected, run a callback.

        frame.on('select', function (arg1, arg2) {
          // Grab the selected attachment.
          // TODO: add code here
          var selection = frame.state().get('selection');
          var i_sel = 0;
          selection.map(function (attachment) {
            attachment = attachment.toJSON(); //...one commented line, that was to add files into HTML structure - works     perfect, but only once

            var queue_call = {
              'type': 'create_item',
              'term_id': $('.dzsvg-sliders-con').eq(0).attr('data-term_id'),
              'term_slug': $('.dzsvg-sliders-con').eq(0).attr('data-term-slug'),
              'post_title': attachment.title // ,'dzsvg_meta_item_source':attachment.url
              ,
              'dzsvg_meta_featured_media': attachment.url
            };
            queue_call['dzsvg_meta_order_' + slider_term_id] = 1 + _sliderItems.children().length + i_sel;
            ajax_queue.push(queue_call);
            i_sel++;
          });
          prepare_send_queue_calls(10);
        }); // Finally, open the modal.

        frame.open();
        e.stopPropagation();
        e.preventDefault();
        return false;
      }

      if (_t.hasClass('slider-item')) {
        if (_t.hasClass('tooltip-open')) {} else {
          $('.slider-item').removeClass('tooltip-open').find('.dzstooltip').removeClass('active');

          _t.addClass('tooltip-open');

          _t.find('.dzstooltip').addClass('active');
        }
      }

      if (_t.hasClass('divimage')) {
        if (_t.parent().hasClass('slider-item')) {
          var _par = _t.parent();

          if (_par.hasClass('tooltip-open')) {
            _par.removeClass('tooltip-open');

            _par.find('.dzstooltip').removeClass('active');

            return false;
          }
        }
      }
    }
  }

  function send_queue_calls() {
    $('.slider-status').removeClass('empty');
    var arg = JSON.stringify(ajax_queue);
    var data = {
      action: 'dzsvg_send_queue_from_sliders_admin',
      the_term_id: _slidersCon.attr('data-term-id'),
      postdata: arg
    };
    jQuery.ajax({
      type: "POST",
      url: window.ajaxurl,
      data: data,
      success: function (response) {
        response = parse_response(response);

        if (response.report_message) {
          if (window) {
            dzsFeedbacker.show_feedback(response.report_message);
          }
        }

        if (response.items) {
          for (var i in response.items) {
            var cach = response.items[i];

            if (cach.type === 'create_item') {
              if (cach.original_request === 'duplicate_item') {
                $('.slider-item[data-id="' + cach.original_post_id + '"]').after(cach.str);
              } else {
                _sliderItems.append(cach.str);
              }

              dzstaa_init('.dzs-tabs-meta-item', {
                init_each: true
              });
              dzssel_init('select.dzs-style-me', {
                init_each: true
              });
            }
          }
        } // -- end sending calls


        $('.slider-status').addClass('empty');
        isSaving = false;
        ajax_queue = [];
      },
      error: function (arg) {}
    });
  }

  function parse_response(response) {
    var arg = {};

    try {
      arg = JSON.parse(response);
    } catch (err) {}

    return arg;
  }

  function prepare_send_queue_calls(customdelay) {
    let delay = 2000;

    if (typeof customdelay == 'undefined') {
      delay = 2000;
    } else {
      delay = customdelay;
    }

    isSaving = true;
    clearTimeout(inter_send_to_ajax);
    inter_send_to_ajax = setTimeout(send_queue_calls, delay);
  }

  $(document).on('change.dzsvg_sliders_admin', '*[data-aux-name]', function () {
    var _t = $(this);

    if (_t.attr('data-aux-name') === 'feed_mode') {
      // -- we adjust via css
      _t.parent().parent().parent().attr('data-feed_mode', _t.val());

      if (_t.val() === 'manual') {
        $('.dzs-tabs-meta-item').each(function () {
          var _t = $(this);

          if (_t.get(0) && _t.get(0).api_handle_resize) {
            setTimeout(function (_t2) {
              _t2.get(0).api_handle_resize();
            }, 500, _t);
          }
        });
      }
    }

    let _slidersAdminActualInputForFeedField = $('*[name="term_meta[' + _t.attr('data-aux-name') + ']"]');

    let val = _t.val();

    if (val === 'import-folder') {
      val = 'manual';
    }

    _slidersAdminActualInputForFeedField.val(val);
  });
});

},{"./js_common/_dzs_feedbacker":1,"./js_common/_dzs_helpers":2,"./slidersAdmin/_slidersAdmin-helper":3,"./slidersAdmin/_validators":4,"./slidersAdmin/dzsvg/_dzsvg_specifics":5}]},{},[6])


//# sourceMappingURL=sliders_admin.js.map