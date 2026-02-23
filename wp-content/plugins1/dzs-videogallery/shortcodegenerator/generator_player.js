'use strict';
var coll_buffer = 0;
var func_output = '';
var fout = '';


function sanitize_encode_for_html_attr(arg) {

  var fout = htmlEncode(arg);


  fout = fout.replace(/\"/g, '{{quot}}');
  fout = fout.replace(/\[/g, '{patratstart}');
  fout = fout.replace(/\]/g, '{patratend}');

  return fout;
}

function sanitize_decode_for_html_attr(arg) {

  var fout = htmlDecode(arg);


  fout = fout.replace(/{patratstart}/g, '[');
  fout = fout.replace(/{patratend}/g, ']');

  return fout;
}


function htmlEncode(arg) {
  return jQuery('<div/>').text(arg).html();
}

function get_shortcode_attr(arg, argtext) {


  var regex_aattr = null;


  if (argtext.indexOf(arg + '=\'') > -1) {

    regex_aattr = new RegExp(' ' + arg + '=\'(.*?)\'');
  } else {

    regex_aattr = new RegExp(' ' + arg + '="(.*?)"');
  }


  var aux = regex_aattr.exec(argtext);

  if (arg == 'cat') {

  }
  if (aux) {
    var foutobj = {'full': aux[0], 'val': aux[1]};
    return foutobj;
  }


  return false;
}


jQuery(document).ready(function ($) {


  if (window.dzsvg_standard_options) {

  } else {

    window.dzsvg_standard_options = [];
  }


  $('.shortcode-field').each(function () {
    var _t = $(this);

    window.dzsvg_standard_options.push(_t.attr('name'));
  })


  var startinit = '';

  if (window.dzsvg_startinit) {
    startinit = window.dzsvg_startinit;
  }


  if (startinit) {


    $('.dzsvg-admin').append('<div class="misc-initSetup"><h5>Start Setup</h5></h5><p>' + htmlEncode(startinit) + '</p></div>');


    var res;
    var lab = '';

    for (var key in window.dzsvg_standard_options) {
      lab = window.dzsvg_standard_options[key];
      res = get_shortcode_attr(lab, startinit);
      if (res) {
        if (lab == 'id') {
          lab = 'dzsvg_selectid';
        }
        if (lab == 'db') {
          lab = 'dzsvg_selectdb';
        }
        if (lab == 'cat') {
          let res_arr = String(res['val']).split(',');


          $('*[name="' + lab + '[]"').each(function () {
            var _t2 = $(this);

            for (var ij in res_arr) {


              if (_t2.val() == res_arr[ij]) {
                _t2.prop('checked', true);
                _t2.trigger('change');
              }
            }
            _t2.parent().attr('data-init_categories', res['val']);
          })


        } else {

          if (lab == 'type') {
          }

          $('*[name="' + lab + '"]').val(res['val']);
          $('*[name="' + lab + '"]').trigger('change');
        }
      }
    }
  }


  setTimeout(reskin_select, 10);
  $('.submit-shortcode').bind('click', click_insert_tests);
  $(document).delegate('.insert-sample-tracks,.remove-sample-tracks, button.sg-1, button.sg-2, button.sg-3', 'click', handle_mouse);
  $('#insert_single_player').bind('click', click_insert_single_player);


  function handle_mouse(e) {
    var _t = $(this);

    if (e.type == 'click') {
      if (_t.hasClass('insert-sample-tracks')) {


        var data = {
          action: 'ajax_dzsvg_insert_sample_tracks'
        };


        $.ajax({
          type: "POST",
          url: ajaxurl,
          data: data,
          success: function (response) {
            window.location.reload();

          },
          error: function (arg) {
          }
        });

        return false;
      }
      if (_t.hasClass('remove-sample-tracks')) {


        var data = {
          action: 'ajax_dzsvg_remove_sample_tracks'
        };


        $.ajax({
          type: "POST",
          url: ajaxurl,
          data: data,
          success: function (response) {
            window.location.reload();

          },
          error: function (arg) {
          }
        });

        return false;
      }


      if (_t.hasClass('sg-1')) {


        fout = window.sg1_shortcode;

        tinymce_add_content(fout);

      }
      if (_t.hasClass('sg-3')) {


        fout = window.sg3_shortcode;

        tinymce_add_content(fout);

      }


      if (_t.hasClass('sg-2')) {


        fout = window.sg2_shortcode;

        if (parent.dzsvg_prepare_footer_player) {
          parent.dzsvg_prepare_footer_player();
        }

        tinymce_add_content(fout);

      }
    }
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

  function click_insert_tests() {
    prepare_fout_single();
    tinymce_add_content(fout);
    return false;
  }


  function reskin_select() {
    for (let i = 0; i < jQuery('select').length; i++) {
      var _cache = jQuery('select').eq(i);

      if (_cache.hasClass('styleme') == false || _cache.parent().hasClass('select_wrapper') || _cache.parent().hasClass('select-wrapper')) {
        continue;
      }
      var sel = (_cache.find(':selected'));
      _cache.wrap('<div class="select-wrapper"></div>')
      _cache.parent().prepend('<span>' + sel.text() + '</span>')
    }
    jQuery('.select-wrapper select').unbind();
    jQuery('.select-wrapper select').on('change', change_select);
  }

  function change_select() {
    var selval = (jQuery(this).find(':selected').text());
    jQuery(this).parent().children('span').text(selval);
  }

  /**
   * [zoomsounds_player source="http://localhost/wordpress/wp-content/uploads/2013/11/song.mp3" configs="skinwavewithcomments" playerid="4306" waveformbg="http://localhost/wordpress/wp-content/plugins/dzs-zoomsounds/waves/scrubbg_songmp3.png" waveformprog="http://localhost/wordpress/wp-content/plugins/dzs-zoomsounds/waves/scrubprog_songmp3.png" thumb="http://localhost/wordpress/wp-content/uploads/2013/03/1185428_13454282.jpeg" autoplay="on" cue="on" enable_likes="off" enable_views="off" playfrom="10"]
   */
  function prepare_fout_single() {
    let fout = '';



    fout += '[dzs_video';


    for (var i2 in dzsvg_standard_options) {

      fout += add_attribute_to_shortcode(dzsvg_standard_options[i2]);
    }




    fout += ']';


    if (add_attribute_to_shortcode('content', {attribute_type: 'content'})) {
      fout += add_attribute_to_shortcode('content', {
        attribute_type: 'content'
      });


      fout += '[/dzs_video]'

    }
  }


  function add_attribute_to_shortcode(lab, pargs) {


    var margs = {
      'called_from': 'default'
      , 'attribute_type': 'attr'
    }

    if (pargs) {
      margs = $.extend(margs, pargs);
    }

    var _c = $('*[name=' + lab + ']');
    var _par = null;


    if (_c.parent().hasClass('setting')) {
      _par = _c.parent();

    }
    if (_c.parent().parent().hasClass('setting')) {
      _par = _c.parent().parent();

    }
    if (_c.parent().parent().parent().hasClass('setting')) {
      _par = _c.parent().parent().parent();

    }
    if (_c.parent().parent().parent().parent().hasClass('setting')) {
      _par = _c.parent().parent().parent().parent();

    }

    if (_par) {
      if (_par.css('display') === 'none') {
        return '';
      }
    }


    var fout2 = '';
    if (margs.attribute_type == 'attr') {


      var val = '';

      if (_c.val()) {
        val = _c.val();
      }



      val = sanitize_encode_for_html_attr(val);
      if (val) {


        fout2 += ' ' + lab + '="' + val + '"';
      }
    }
    if (margs.attribute_type === 'content') {


      var ed = null;
      if (window.tinymce) {
        ed = window.tinymce.get('content');


      }


      if (ed) {

        if (ed) {
          fout2 += (ed.getContent({format: 'raw'}));
        }
      } else {

        if (_c.val()) {
          fout2 += '' + _c.val() + '"';
        }
      }
    }

    return fout2;
  }

  function click_insert_single_player() {

    prepare_fout_single();
    tinymce_add_content(fout);
    return false;
  }

});
