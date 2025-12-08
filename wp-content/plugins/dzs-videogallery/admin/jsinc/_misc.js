exports.extra_skin_hiddenselect = function () {
  for (var i = 0; i < jQuery('.select-hidden-metastyle').length; i++) {
    var _t = jQuery('.select-hidden-metastyle').eq(i);
    if (_t.hasClass('inited')) {
      continue;
    }
    _t.addClass('inited');
    _t.children('select').eq(0).bind('change', change_selecthidden);
    change_selecthidden(null, _t.children('select').eq(0));
    _t.find('.an-option').bind('click', click_anoption);
  }

  function change_selecthidden(e, arg) {
    var _c = jQuery(this);
    if (arg) {
      _c = arg;
    }
    var _con = _c.parent();
    var selind = _c.children().index(_c.children(':selected'));
    var _slidercon = _con.parent().parent();
    _con.find('.an-option').removeClass('active');
    _con.find('.an-option').eq(selind).addClass('active');
    do_changemainsliderclass(_slidercon, selind);
  }

  function click_anoption(e) {
    var _c = jQuery(this);
    var ind = _c.parent().children().index(_c);
    var _con = _c.parent().parent();
    var _slidercon = _con.parent().parent();
    _c.parent().children().removeClass('active');
    _c.addClass('active');
    _con.children('select').eq(0).children().removeAttr('selected');
    _con.children('select').eq(0).children().eq(ind).attr('selected', 'selected');
    do_changemainsliderclass(_slidercon, ind);
  }

  function do_changemainsliderclass(arg, argval) {
    //extra function - handmade

    if (arg.hasClass('select-hidden-con')) {
      arg.removeClass('mode_thumb');
      arg.removeClass('mode_gallery');
      arg.removeClass('mode_audio');
      arg.removeClass('mode_video');
      arg.removeClass('mode_youtube');
      arg.removeClass('mode_vimeo');
      arg.removeClass('mode_link');
      arg.removeClass('mode_testimonial');
      arg.removeClass('mode_link');
      arg.removeClass('mode_twitter');

      arg.addClass('mode_' + arg.find('.mainsetting').eq(0).children().eq(argval).val());

    }
    if (arg.hasClass('item-settings-con')) {
      arg.removeClass('type_youtube');
      arg.removeClass('type_normal');
      arg.removeClass('type_vimeo');
      arg.removeClass('type_audio');
      arg.removeClass('type_image');
      arg.removeClass('type_link');

      if (argval == 0) {
        arg.addClass('mode_youtube')
      }
      if (argval == 1) {
        arg.addClass('mode_normal')
      }
      if (argval == 2) {
        arg.addClass('mode_vimeo')
      }
      if (argval == 3) {
        arg.addClass('mode_audio')
      }
      if (argval == 4) {
        arg.addClass('mode_image')
      }
      if (argval == 5) {
        arg.addClass('mode_link')
      }
    }
  }

}



exports.con_generate_buttons = function (ajaxurl) {
  var $ =jQuery;
  $('#generate-upload-page').bind('click', function () {
    var _t = $(this);

    _t.css('opacity', 0.5);


    var data = {
      action: 'dzsvp_insert_upload_page'
      , postdata: '1'
    };
    $.post(ajaxurl, data, function (response) {
        console.log('Got this from the server: ' + response);

      $('select[name=dzsvp_page_upload]').prepend('<optgroup label="Generated Pages"><option value="' + response + '">Upload</option></optgroup>')

      $('select[name=dzsvp_page_upload]').find('option').eq(0).prop('selected', true);
      $('select[name=dzsvp_page_upload]').trigger('change');

      _t.parent().parent().remove();

    });

    return false;
  })
}