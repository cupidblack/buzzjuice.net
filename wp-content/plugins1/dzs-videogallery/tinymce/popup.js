'use strict';
var coll_buffer = 0;
var func_output = '';


function htmlEncode(arg) {
  return jQuery('<div/>').text(arg).html();
}

function htmlDecode(value) {
  return jQuery('<div/>').html(arg).text();
}

function get_shortcode_attr(arg, argtext) {


  var regex_aattr = new RegExp(' ' + arg + '="(.*?)"');


  var aux = regex_aattr.exec(argtext);

  if (arg == 'cat') {

  }
  if (aux) {
    var foutobj = {'full': aux[0], 'val': aux[1]};
    return foutobj;
  }


  return false;
}


function add_query_arg(purl, key, value) {
  key = encodeURIComponent(key);
  value = encodeURIComponent(value);


  var s = purl;
  var pair = key + "=" + value;

  var r = new RegExp("(&|\\?)" + key + "=[^\&]*");


  s = s.replace(r, "$1" + pair);
  var addition = '';
  if (s.indexOf(key + '=') > -1) {


  } else {
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
    var regexS = "[?&]" + key + "(.+?)(?=&|$)";
    var regex = new RegExp(regexS);
    var regtest = regex.exec(purl);


    if (regtest != null) {


      if (regtest[1]) {
        var aux = regtest[1].replace(/=/g, '');
        return aux;
      } else {
        return '';
      }


    }
  }
}


var dzsp_standard_options = [
  'strip_html'
  , 'strip_shortcodes'
  , 'try_repair'
];

jQuery(document).ready(function ($) {


  var fout = '';

  if (typeof (dzsvg_settings) != 'undefined' && dzsvg_settings.startSetup != '') {
    top.dzsvg_startinit = dzsvg_settings.startSetup;
  }


  var coll_buffer = 0;
  fout = '';


  // ---- some custom code for initing the generator ( previous values )
  if (typeof top.dzsvg_startinit != 'undefined' && top.dzsvg_startinit != '') {


    var arr_settings = ['id', 'db'];

    $('.dzsvg-admin').append('<div class="misc-initSetup"><h5>Start Setup</h5></h5><p>' + htmlEncode(top.dzsvg_startinit) + '</p></div>');


    var res;
    var lab = '';
    for (var key in arr_settings) {

      lab = arr_settings[key];
      res = get_shortcode_attr(lab, top.dzsvg_startinit);
      if (res) {
        if (lab == 'id') {
          lab = 'dzsvg_selectid';
        }
        if (lab == 'db') {
          lab = 'dzsvg_selectdb';
        }
        $('*[name="' + lab + '"]').val(res['val']);
        $('*[name="' + lab + '"]').trigger('change');
      }
    }
  }


  var _feedbacker = $('.feedbacker');

  _feedbacker.fadeOut("slow");
  setTimeout(reskin_select, 10);
  $('#insert_tests').unbind('click');
  $('#insert_tests').bind('click', click_insert_tests);

  $(document).delegate('.import-sample', 'click', handle_mouse);
  $(document).delegate('form.import-sample-galleries', 'submit', handle_submit);

  $(document).on('change', '*[name=dzsvg_selectid]', handle_change);
  $(document).on('click', '.insert-sample-library,.lib-item', handle_mouse);

  $('*[name=dzsvg_selectid]').trigger('change');


  function handle_change(e) {
    var _t = $(this);

    if (e.type == 'change') {
      if (_t.attr('name') === 'dzsvg_selectid') {


        var ind = 0;

        _t.children().each(function () {
          var _t2 = $(this);

          if (_t2.prop('selected')) {
            ind = _t2.parent().children().index(_t2);
            return false;
          }
        });

        $('#quick-edit').attr('href', add_query_arg($('#quick-edit').attr('href'), 'currslider', ind));
        $('#quick-edit').attr('href', add_query_arg($('#quick-edit').attr('href'), 'dbname', $('*[name=dzsvg_selectdb]').val()));

      }
    }
  }


  $('select[name=dzsvg_selectdb]').bind('change', change_selectdb);

  function handle_mouse(e) {
    var _t = $(this);

    if (e.type === 'click') {

      if (_t.hasClass('insert-sample-library')) {


        window.open_ultibox(null, {


          type: 'inlinecontent'
          , source: '#import-sample-lib'
          // ,inline_content_move: 'on'
          , scaling: 'fill' // -- this is the under description
          , suggested_width: '95vw' // -- this is the under description
          , suggested_height: '95vh' // -- this is the under description
          , item: null // -- we can pass the items from here too

        });


      }
      if (_t.hasClass('lib-item')) {


        var data = {
          action: 'dzsvg_import_item_lib'
          , demo: _t.attr('data-demo')
        };

        _t.addClass('loading');

        jQuery.ajax({
          type: "POST",
          url: ajaxurl,
          data: data,
          success: function (response) {


            setTimeout(function () {
              "use strict";

              _t.removeClass('loading');
            }, 100);

            if (response.indexOf('"response_type":"error"') > -1) {

              show_notice(response);
            } else {
              var resp = JSON.parse(response);


              tinymce_add_content(resp.settings.final_shortcode);

              close_ultibox();

              setTimeout(function () {
                "use strict";
                top.close_ultibox();
              }, 500);
            }

          },
          error: function (arg) {
          }
        });


      }
      if (_t.hasClass('import-sample')) {

        var fout = '';
        if (_t.hasClass('import-sample-1')) {

          fout = '[dzs_videogallery id="sample_wall" db="main" settings_separation_mode="pages" settings_separation_pages_number="6"]';
        }
        if (_t.hasClass('import-sample-2')) {

          fout = '<div style="float:left; width: 66%;"> [videogallery id="sample_youtube_channel"] </div> <div style="float:left; width: 33%; padding-left: 2%; box-sizing: border-box;"> [dzsvg_secondcon id="sample_youtube_channel" skin="oasis" extraclasses=""] </div> <div style="clear:both;"></div> <div> [dzsvg_outernav id="sample_youtube_channel" skin="oasis" extraclasses=""] </div>';
        }
        if (_t.hasClass('import-sample-3')) {

          fout = '[dzs_videogallery id="sample_ad_before_video" db="main"]';
        }
        if (_t.hasClass('import-sample-4')) {

          fout = '[dzs_videogallery id="sample_balne_setup" db="main"][dzsvg_secondcon id="sample_balne_setup" extraclasses="skin-balne" enable_readmore="on" ] [dzsvg_outernav id="sample_balne_setup" skin="balne" extraclasses="" layout="layout-one-third" thumbs_per_page="9"]';
        }
        if (_t.hasClass('import-sample-5')) {

          fout = '[dzs_videogallery id="sample_vimeo_channel" db="main"]';
        }
        tinymce_add_content(fout);
        return false;
      }
    }
  }

  function handle_submit(e) {
    const _t = $(this);

    if (e.type === 'submit') {


      if (_t.hasClass('import-sample-galleries')) {

        var data = {
          action: 'dzsvg_import_galleries'
          , postdata: _t.serialize()
        };


        jQuery.ajax({
          type: "POST",
          url: ajaxurl,
          data: data,
          success: function (response) {




            show_notice(response);
            if (response.indexOf('"response_type":"error"') > -1) {

            } else {
              var resp = JSON.parse(response);


            }


          },
          error: function (arg) {

          }
        });

        return false;
      }
    }
  }


  function show_notice(response) {

    if (response.indexOf('{') == 0) {


      response = JSON.parse(response);



      if (response.response_type == 'error') {

        _feedbacker.addClass('is-error');
        _feedbacker.html(response.msg);
        _feedbacker.fadeIn('fast');

        _feedbacker.css({
          'z-index': '555556'
        })

        setTimeout(function () {

          _feedbacker.fadeOut('slow');
        }, 1500)
      }


    } else {
      if (response.indexOf('error -') === 0) {
        _feedbacker.addClass('is-error');
        _feedbacker.html(response.substr(7));
        _feedbacker.fadeIn('fast');

        setTimeout(function () {

          _feedbacker.fadeOut('slow');
        }, 1500)
      }
      if (response.indexOf('success -') == 0) {
        _feedbacker.removeClass('is-error');
        _feedbacker.html(response.substr(9));
        _feedbacker.fadeIn('fast');

        setTimeout(function () {

          _feedbacker.fadeOut('slow');
        }, 1500)
      }
    }


  }


  function change_selectdb(e) {
    var _t = jQuery(this);



    jQuery('#save-ajax-loading').css('opacity', '1');
    var mainarray = _t.val();
    var data = {
      action: 'dzsvg_get_db_gals',
      postdata: mainarray
    };
    jQuery('.saveconfirmer').html('Options saved.');
    jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
    jQuery.post(ajaxurl, data, function (response) {

      jQuery('#save-ajax-loading').css('opacity', '0');

      var aux = '';
      var auxa = response.split(';');
      for (let i = 0; i < auxa.length; i++) {
        aux += '<option>' + auxa[i] + '</option>'
      }
      jQuery('select[name=dzsvg_selectid]').html(aux);
      jQuery('select[name=dzsvg_selectid]').trigger('change');

    });

    return false;

  }


  function tinymce_add_content(arg) {
    if (top == window) {
      jQuery('.shortcode-output').text(arg);
    } else {


      if (top.dzsvg_widget_shortcode) {
        top.dzsvg_widget_shortcode.val(arg);

        top.dzsvg_widget_shortcode = null;


        if (top.close_zoombox2) {
          top.close_zoombox2();
        }
      } else {

        if (typeof (top.dzsvg_receiver) == 'function') {
          top.dzsvg_receiver(arg);
        }
      }

    }
  }

  function click_insert_tests(e) {

    prepare_fout();
    tinymce_add_content(fout);
    return false;
  }

  function prepare_fout() {
    var $ = jQuery.noConflict();
    fout = '';
    fout += '[dzs_videogallery';
    var _c,
      _c2
    ;
    _c = $('select[name=dzsvg_selectid]');
    if (_c.val() !== '' && _c.val() !== 'main') {
      fout += ' id="' + _c.val() + '"';
    }


    _c = $('select[name=dzsvg_selectdb]');

    if (_c.length && _c.val()) {
      fout += ' db="' + _c.val() + '"';
    }

    if ($('select[name=dzsvg_settings_separation_mode]').val() != 'normal') {
      _c = $('select[name=dzsvg_settings_separation_mode]');
      if (_c.val() !== '') {
        fout += ' settings_separation_mode="' + _c.val() + '"';
      }
      _c = $('input[name=dzsvg_settings_separation_pages_number]');
      if (_c.val() !== '') {
        fout += ' settings_separation_pages_number="' + _c.val() + '"';
      }
    }

    fout += ']';
  }

  function sc_toggle_change() {
    var $ = jQuery.noConflict();

    var type = 'toggle';
    var params = '?type=' + type;
    for (let i = 0; i < $('.sc-toggle').length; i++) {
      var $cach = $('.sc-toggle').eq(i);
      var val = $cach.val();
      if ($cach.hasClass('color'))
        val = val.substr(1);
      params += '&opt' + (i + 1) + '=' + val;
    }
    $('.sc-toggle-frame').attr('src', window.theme_url + 'tinymce/preview.php' + params);

  }

  function sc_boxes_change() {

    var type = 'box';
    var params = '?type=' + type;
    for (let i = 0; i < $('.sc-box').length; i++) {
      var $cach = $('.sc-box').eq(i);
      var val = $cach.val();
      params += '&opt' + (i + 1) + '=' + val;
    }
    $('.sc-box-frame').attr('src', window.theme_url + 'tinymce/preview.php' + params);

  }


  function reskin_select() {
    for (let i = 0; i < jQuery('select').length; i++) {
      var _cache = jQuery('select').eq(i);

      if (!_cache.hasClass('styleme') || _cache.parent().hasClass('select_wrapper') || _cache.parent().hasClass('select-wrapper')) {
        continue;
      }
      var sel = (_cache.find(':selected'));
      _cache.wrap('<div class="select-wrapper"></div>')
      _cache.parent().prepend('<span>' + sel.text() + '</span>')
    }

    jQuery('.select-wrapper select').unbind('change', change_select);
    jQuery('.select-wrapper select').bind('change', change_select);
  }

  function change_select() {
    var selval = (jQuery(this).find(':selected').text());
    jQuery(this).parent().children('span').text(selval);
  }
});