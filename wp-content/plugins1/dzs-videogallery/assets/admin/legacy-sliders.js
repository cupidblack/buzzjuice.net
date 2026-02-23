'use strict';
var sliderIndex = 0;
var itemIndex = [0];
var currSlider_nr = -1;
var currSlider;
var targetInput;
var global_items = 0;

function sliders_ready($) {
  dzsvg_settings.currSlider = parseInt(dzsvg_settings.currSlider, 10);

  jQuery('.saveconfirmer').fadeOut('slow');
  jQuery('.add-slider').bind('click', sliders_click_addslider);

  jQuery('.master-save').bind('click', sliders_saveall);
  jQuery('.slider-save').bind('click', sliders_saveslider);
  jQuery('.master-save-vpc').bind('click', sliders_saveall_vpc);
  jQuery('.slider-save-vpc').bind('click', sliders_saveslider_vpc);
  jQuery(document).delegate(".main-id", "change", sliders_change_mainid);
  jQuery(document).delegate(".main-source", "change", sliders_change_mainsource);
  jQuery(document).delegate("input[name*='desc_different_settings_for_aside']", "change", dzsvg_admin_change_desc_different_settings_for_aside);
  jQuery(document).delegate(".main-thumb", "change", sliders_change_mainthumb);
  jQuery(document).delegate(".slider-edit", "click", sliders_click_slideredit);
  jQuery(document).delegate(".slider-duplicate", "click", sliders_click_sliderduplicate);
  jQuery(document).delegate(".slider-delete", "click", sliders_click_sliderdelete);
  jQuery(document).delegate(".slider-sliderexport", "click", sliders_click_sliderexport);
  jQuery(document).delegate(".slider-sliderexport-configs", "click", sliders_click_sliderexport_config);
  jQuery(document).delegate(".slider-embed", "click", sliders_click_sliderembed);
  jQuery(document).delegate(".item-preview", "click", item_open);


  $(document).on('click', '.quick-edit-vp', handle_mouse);


  jQuery(document).delegate(".refresh-main-thumb", "click", sliders_click_refreshThumbnail);


  jQuery(document).delegate(".item-delete", "click", sliders_click_itemdelete);
  jQuery(document).delegate(".item-duplicate", "click", sliders_click_itemduplicate);

  jQuery(document).undelegate(".upload_file", "click");
  jQuery(document).delegate(".upload_file", "click", sliders_wpupload);
  jQuery(document).delegate(".item-type", "change", sliders_itemchangetype);
  jQuery('.item-type').trigger('change');

  jQuery('#importytplaylist').bind('click', extra_importytplaylist)
  jQuery('#importytuser').bind('click', extra_importytuser);
  jQuery('#importvimeouser').bind('click', extra_importvimeouser);


  jQuery(document).on('mouseover', '.setting', function () {
    "use strict";
    var $ = jQuery;
    var _t = $(this);

    if (_t.children('.extra-desc').length) {
      var _c = _t.children('.extra-desc').eq(0);

      _c.find('.divimage:not(.loading)').each(function () {
        var _t2 = $(this);


        _t2.css({
          'background-image': 'url(' + _t2.attr('data-src') + ')'
        });

      })
    }
  })

  jQuery(document).on('change', '.setting select', function () {
    "use strict";
    var $ = jQuery;
    var _t = $(this);


    if (_t.hasClass('has-extra-desc')) {
      var _c2 = _t.parent().parent().find('.extra-desc');

      var indsel = 0;

      _t.children().each(function () {
        var _t2 = $(this);

        if (_t2.prop('selected')) {
          indsel = _t.children().index(_t2);
        }

      })


      _c2.find('.bigoption').removeClass('active');
      _c2.find('.bigoption').eq(indsel).addClass('active');
    }
  })

  jQuery(document).on('click', '.extra-desc .bigoption', function () {
    "use strict";
    var $ = jQuery;
    var _t = $(this);


    var indsel = _t.parent().children().index(_t);


    var _c = _t.parent().parent().find('select').eq(0);

    _c.children().prop('selected', false);
    _c.children().eq(indsel).prop('selected', true);

    _c.trigger('change');
  })


  jQuery('.import-export-db-con .the-toggle').click(function () {
    var _t = jQuery(this);
    var _cont = _t.parent().children('.the-content-mask');
    /*
     if(_cont.css('display')=='none')
     _cont.slideDown('slow');
     else
     _cont.slideUp('slow');
     */
    var cont_h = _cont.children('.the-content').height() + 50 + 19;
    if (_cont.css('height') == '0px')
      _cont.stop().animate({
        'height': cont_h
      }, 200);
    else
      _cont.stop().animate({
        'height': 0
      }, 200);

  });
  dzsvg_setupDbSelect();
  setTimeout(sliders_addlisteners, 1000);
  jQuery('.import-export-db-con .the-content-mask').css({
    'height': 0
  })


  function handle_mouse(e) {


    var _t = $(this);
    if (_t.hasClass('quick-edit-vp')) {
      window.open_ultibox(null, {

        type: 'iframe'
        , source: _t.attr('href')
        , scaling: 'fill' // -- this is the under description
        , suggested_width: '400' // -- this is the under description
        , suggested_height: '95vh' // -- this is the under description
        , item: null // -- we can pass the items from here too

      });

      return false;
    }

  }


  // -- dzsvg functions
  function dzsvg_admin_change_desc_different_settings_for_aside() {

    var _t = jQuery(this);


    if (_t.prop('checked')) {
      jQuery('.appear-only-when-is-on-desc_different_settings_for_aside').show();
    } else {

      jQuery('.appear-only-when-is-on-desc_different_settings_for_aside').hide();
    }
  }

  // -- dzsvg functions END


};

// -- end sliders_ready

function sliders_click_refreshThumbnail() {

  var _t = jQuery(this);
  var _con = _t.parent().parent().parent().parent();


  if (_con.hasClass('item-settings-con')) {
    _con.find('.main-thumb').eq(0).val('');
    _con.find('.main-source').eq(0).trigger('change');
  } else {
  }

  return false;
}


function sliders_change_mainsource() {
  var _t = jQuery(this);
  var _con = _t.parent().parent();


  if (_con.hasClass('item-settings-con')) {
    if (_con.hasClass('type_youtube')) {
      if (_con.find('.main-thumb').eq(0).val() == '') {
        _con.find('.main-thumb').eq(0).val('https://img.youtube.com/vi/' + _t.val() + '/0.jpg');
        _con.find('.main-thumb').eq(0).trigger('change');
      }
    }
    if (_con.hasClass('type_vimeo')) {
      if (_con.find('.main-thumb').eq(0).val() == '') {


        var aux = _t.val();

        if (aux.indexOf('vimeo.com/') > -1) {

          var aux_regex = /vimeo.com\/(.*?)(?:$|\?)/g;
          var match = aux_regex.exec(aux);


          if (match && match[1]) {
            aux = match[1];
          }
        }

        var data = {
          action: 'dzsvg_vimeo_get_vimeothumb',
          postdata: aux
        };

        jQuery.post(ajaxurl, data, function (response) {
          if (response.substr(0, 6) == 'error:') {
            jQuery('.import-error').html(response.substr(7));
            jQuery('.import-error').fadeIn('fast').delay(5000).fadeOut('slow');
            return false;
          }
          if (response.indexOf('h') == 0) {

            _con.find('.main-thumb').eq(0).val(response);
            _con.find('.main-thumb').eq(0).trigger('change');
          }
        });
      }
    }
  }
}

function dzsvg_setupDbSelect() {
  var _c = jQuery('.db-select.dzsvg');
  _c.append('<div class="db-select-nicecon"><div id="db-select-scroller-con" class="scroller-con scrollbars-inset" style="width: 100%; height: 180px;"><div class="inner"></div></div></div>');
  _c.find('.main-select').children().each(function () {
    var _t = jQuery(this);
    _c.find('.inner').append('<div class="a-db-option">select database <span class="strong">' + _t.text() + '</span><a href="' + _t.attr('data-newurl') + '" class="todb">&raquo;</a></div>');
  })
  _c.find('.inner').append('<div class="a-db-option">create database <input class="newdb"/><a href=" " class="todb createdb">&raquo;</a></div>');

  if (jQuery.fn.scroller) {
    jQuery('#db-select-scroller-con').scroller({
      settings_skin: 'skin_apple'
      , enable_easing: "on"
    });
  }
  _c.find('.todb.createdb').eq(0).bind('click', function () {
    var _t = jQuery(this);
    if (_t.prev().val() == '') {
      _t.prev().addClass('attention');
      setTimeout(function () {
        _t.prev().removeClass('attention');

      }, 1000)
      return false;

    } else {
      var aux = _c.find('.replaceurlhelper').eq(0).text();
      aux = aux.replace('replaceurlhere', _t.prev().val());
      _t.attr('href', aux);
    }
  })
  jQuery('.dzsvg .btn-show-dbs').bind('click', function () {
    var _t = jQuery(this).parent();
    if (_t.find('.db-select-nicecon').eq(0).hasClass('active')) {
      _t.find('.db-select-nicecon').eq(0).removeClass('active')
    } else {
      _t.find('.db-select-nicecon').eq(0).addClass('active')
    }
  })
}


function sliders_click_sliderexport() {
  var _t = jQuery(this);
  var par = _t.parent().parent().parent();
  var ind = par.parent().children().index(par);
  var sname = par.children('td').eq(0).html()
  tb_show('Slide Editor', dzsvg_settings.wpurl + '?dzsvg_show_generator_export_slider=on&KeepThis=true&width=400&height=200&slidernr=' + ind + '&slidername=' + sname + '&currdb=' + window.dzsvg_settings.currdb + '&TB_iframe=true');
  return false;
}

function sliders_click_sliderexport_config() {
  var _t = jQuery(this);
  var par = _t.parent().parent().parent();
  var ind = par.parent().children().index(par);
  var sname = par.children('td').eq(0).html()
  tb_show('Slide Editor', dzsvg_settings.wpurl + '?dzsvg_show_generator_export_slider_config=on&KeepThis=true&width=400&height=200&slidernr=' + ind + '&slidername=' + sname + '&TB_iframe=true');
  return false;
}

function sliders_click_sliderembed() {
  var _t = jQuery(this);
  var par = _t.parent().parent().parent();
  var ind = par.parent().children().index(par);
  var sname = par.children('td').eq(0).html()


  var aux = 'use this shortcode for embedding: [videogallery id="' + sname + '"';

  if (window.dzsvg_settings.currdb != '') {
    aux += ' db="' + window.dzsvg_settings.currdb + '"';
  }

  aux += ']';

  jQuery('.saveconfirmer').html(aux);
  jQuery('.saveconfirmer').stop().fadeIn('fast').delay(4000).fadeOut('fast');
  return false;
}

function extra_importytplaylist() {
  var aux = jQuery('#import_inputtext').val();
  var i2 = 0;
  var data = {
    action: 'dzsvg_import_ytplaylist',
    postdata: aux
  };

  jQuery.post(ajaxurl, data, function (response) {
    if (response.substr(0, 6) == 'error:') {
      jQuery('.import-error').html(response.substr(7));
      jQuery('.import-error').fadeIn('fast').delay(5000).fadeOut('slow');
      return false;
    }
    var obj = jQuery.parseJSON(response);
    if (obj.length > 0) {
      var aux = itemIndex[currSlider_nr];
      for (i2 = 0; i2 < aux; i2++) {
        sliders_delete_item(0);
      }
      for (i2 = 0; i2 < obj.length; i2++) {
        var arg3 = {
          title: obj[i2].title
          , type: obj[i2].type
          , thumb: response[i2].thethumb
        }
        sliders_additem(currSlider_nr, obj[i2].source, arg3);
      }
    }


    setTimeout(sliders_addlisteners, 100);
  });


  return false;
}

function extra_importytuser() {
  var aux = jQuery('#import_inputtext').val();
  var i2 = 0;
  var data = {
    action: 'dzsvg_import_ytuser',
    postdata: aux
  };

  jQuery.post(ajaxurl, data, function (response) {
    if (typeof (response) == 'string' && response.substr(0, 6) == 'error:') {
      jQuery('.import-error').html(response.substr(7));
      jQuery('.import-error').fadeIn('fast').delay(5000).fadeOut('slow');
      return false;
    }
    var obj;
    if (typeof (response) == 'string') {
      obj = jQuery.parseJSON(response);
    } else {
      obj = response;
    }

    if (obj != null && obj.length > 0) {
      var aux = itemIndex[currSlider_nr];
      for (i2 = 0; i2 < aux; i2++) {
        sliders_delete_item(0);
      }
      for (i2 = 0; i2 < obj.length; i2++) {
        var arg3 = {
          title: obj[i2].title
          , type: obj[i2].type
          , thumb: obj[i2].thethumb
        }
        sliders_additem(currSlider_nr, obj[i2].source, arg3);
      }
    } else {

    }


    setTimeout(sliders_addlisteners, 100);
  }, "json");


  return false;
}


function extra_importvimeouser() {
  var aux = jQuery('#import_inputtext').val();
  var i2 = 0;
  var data = {
    action: 'dzsvg_import_vimeouser',
    postdata: aux
  };

  jQuery.post(ajaxurl, data, function (response) {
    if (response.substr(0, 6) == 'error:') {
      jQuery('.import-error').html(response.substr(7));
      jQuery('.import-error').fadeIn('fast').delay(5000).fadeOut('slow');
      return false;
    }
    var obj = jQuery.parseJSON(response);
    if (obj.length > 0) {
      var aux = itemIndex[currSlider_nr];
      for (i2 = 0; i2 < aux; i2++) {
        sliders_delete_item(0);
      }
      for (i2 = 0; i2 < obj.length; i2++) {
        var arg3 = {
          title: obj[i2].title
          , type: obj[i2].type
          , thumb: obj[i2].thethumb
        }
        sliders_additem(currSlider_nr, obj[i2].source, arg3);
      }
    }

    setTimeout(sliders_addlisteners, 100);
  });
  return false;
}


function sliders_reinit() {
  window.farbtastic_reinit();

}

function sliders_itemchangetype() {
  var _t = jQuery(this);
  var selval = _t.find(':selected').val();
  var target = _t.parent().parent().parent().find('.main-source');




  if (selval == 'inline') {
    target.css({
      'height': 80,
      'resize': 'vertical'
    });
  } else {
    target.css({
      'height': 29,
      'resize': 'none'
    });
  }

  target.removeClass('upload-type-video upload-type-off upload-type-audio upload-type-image');
  target.addClass('upload-type-' + selval);

}

var uploader_frame;

function sliders_wpupload() {


  var _t = jQuery(this);
  var _targetInput = _t.prev();

  var searched_type = '';

  if (_targetInput.hasClass('upload-type-audio')) {
    searched_type = 'audio';
  }
  if (_targetInput.hasClass('upload-type-video')) {
    searched_type = 'video';
  }
  if (_targetInput.hasClass('upload-type-image')) {
    searched_type = 'image';
  }



  if (typeof wp != 'undefined' && typeof wp.media != 'undefined') {
    uploader_frame = wp.media.frames.dzsvg_addplayer = wp.media({
      // Set the title of the modal.
      title: "Insert Media Modal",
      multiple: true,
      // Tell the modal to show only images.
      library: {
        type: searched_type
      },

      // Customize the submit button.
      button: {
        // Set the text of the button.
        text: "Insert Media",
        // Tell the button not to close the modal, since we're
        // going to refresh the page when the image is selected.
        close: false
      }
    });

    // When an image is selected, run a callback.
    uploader_frame.on('select', function () {
      var attachment = uploader_frame.state().get('selection').first();

      if (_targetInput.hasClass('upload-prop-id')) {
        _targetInput.val(attachment.attributes.id);
      } else {
        _targetInput.val(attachment.attributes.url);

      }


      _targetInput.trigger('change');
      uploader_frame.close();
    });

    // Finally, open the modal.
    uploader_frame.open();
  } else {
    tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true&amp;post_id=1&amp;width=640&amp;height=105');
    var backup_send_to_editor = window.send_to_editor;
    var intval = window.setInterval(function () {
      if (jQuery('#TB_iframeContent').attr('src') != undefined && jQuery('#TB_iframeContent').attr('src').indexOf("&field_id=") !== -1) {
        jQuery('#TB_iframeContent').contents().find('#tab-type_url').hide();
      }
      jQuery('#TB_iframeContent').contents().find('.savesend .button').val("Upload to Video Gallery");
    }, 50);
    window.send_to_editor = function (arg) {
      var fullpath = arg
        , fullpathArray = fullpath.split('>');


      var aux3 = jQuery(fullpath).attr('href');


      _targetInput.val(aux3);
      _targetInput.trigger('change');
      tb_remove();
      window.clearInterval(intval);
      window.send_to_editor = backup_send_to_editor;
    };
  }


  return false;
}

function sliders_click_slideredit() {

  if (dzsvg_settings.is_safebinding == 'on') {

  } else {
    var index = jQuery('.slider-edit').index(jQuery(this));
    sliders_showslider(index);
    return false;
  }
}

function sliders_addslider(args) {
  var sliderslen = jQuery('.main_sliders').children('tbody').children().length;
  var auxurl = (dzsvg_settings.urlcurrslider).replace('_currslider_', sliderslen);
  var auxdelurl = (dzsvg_settings.urldelslider).replace('_currslider_', sliderslen);
  var auxname = 'default';


  if (args != undefined && args.name !== undefined) {
    auxname = args.name;
  }


  var auxs = '<tr class="slider-in-table"><td>' + auxname + '</td><td class="button_view"><strong><a href="' + auxurl + '" class="slider-action slider-edit">Edit</a></strong></td><td class="button_view"><strong><a href="#" class="slider-action slider-embed">Embed</a></strong></td><td class="button_view"><strong><a href="#" class="slider-action slider-sliderexport';


  if (get_query_arg(window.location.href, 'page') === 'dzsvg-vpc') {
    auxs += '-configs';
  }

  auxs += '">Export</a></strong></td>';

  if (dzsvg_settings.is_safebinding == 'on') {
    auxs += '<td class="button_view"><form method="POST" class="slider-duplicate-form"><input type="hidden" name="dzsvg_duplicateslider" value="' + sliderslen + '"/><input class="button-secondary" type="submit" value="Duplicate"/></form></td>';
  } else {
    auxs += '<td class="button_view"><strong><a href="#" class="slider-action slider-duplicate">Duplicate</a></strong></td>';
  }
  auxs += '<td class="button_view"><form method="POST" class="slider-delete"><input type="hidden" name="deleteslider" value="' + sliderslen + '"/><input class="button-secondary" type="submit" value="Delete"/></form></td></tr>';

  jQuery('.main_sliders').children('tbody').append(auxs);



  if (dzsvg_settings.is_safebinding === 'on') {
    if (dzsvg_settings.currSlider >= sliderslen) {
      if (jQuery('.master-settings').hasClass("mode_vpconfigs")) {
        jQuery('.master-settings').append(videoplayerconfig);
      } else {
        jQuery('.master-settings').append(sliderstructure);
      }

    }
  } else {
    if (jQuery('.master-settings').hasClass("mode_vpconfigs")) {
      jQuery('.master-settings').append(videoplayerconfig);
    } else {
      jQuery('.master-settings').append(sliderstructure);
    }
  }
  for (let i = 0; i < jQuery('.slider-con').eq(sliderIndex).find('.textinput').length; i++) {
    var _cache = jQuery('.slider-con').eq(sliderIndex).find('.textinput').eq(i);
    sliders_rename(_cache, sliderIndex, 'settings')
  }
  sliders_addlisteners();
  itemIndex[sliderIndex] = 0;
  ++sliderIndex;
  sliders_reinit();
  return false;

}


function sliders_click_sliderduplicate() {
  var index = jQuery('.slider-duplicate').index(jQuery(this));

  var sliderslen = jQuery('.main_sliders').children('tbody').children().length;
  var auxname = jQuery('.slider-con').eq(index).find('.main-id').eq(0).val();
  var auxurl = (dzsvg_settings.urlcurrslider).replace('_currslider_', sliderslen);


  var auxs = '<tr class="slider-in-table"><td>' + auxname + '</td><td class="button_view"><strong><a href="' + auxurl + '" class="slider-action slider-edit">Edit</a></strong></td><td class="button_view"><strong><a href="#" class="slider-action slider-embed">Embed</a></strong></td><td class="button_view"><strong><a href="#" class="slider-action slider-sliderexport">Export</a></strong></td><td class="button_view"><strong><a href="#" class="slider-action slider-duplicate">Duplicate</a></strong></td><td class="button_view"><form method="POST" class="slider-delete"><input type="hidden" name="deleteslider" value="' + sliderslen + '"/><input class="button-secondary" type="submit" value="Delete"/></form></td></tr>';

  jQuery('.main_sliders').children('tbody').append(auxs);


  jQuery('.master-settings').append(jQuery('.slider-con').eq(index).clone());
  for (i = 0; i < jQuery('.slider-con').eq(sliderIndex).find('.textinput').length; i++) {
    var _cache = jQuery('.slider-con').eq(sliderIndex).find('.textinput').eq(i);
    sliders_rename(_cache, sliderIndex, 'same')
  }


  for (i = 0; i < jQuery('.slider-con').eq(index).find('textarea').length; i++) {
    var _c = jQuery('.slider-con').last().find('textarea').eq(i);
    _c.val(jQuery('.slider-con').eq(index).find('textarea').eq(i).val());
  }

  sliders_addlisteners();
  itemIndex[sliderIndex] = 0;
  ++sliderIndex;


  return false;
}

function sliders_click_itemdelete() {
  var index = currSlider.find('.item-delete').index(jQuery(this));

  var arg = index;
  sliders_delete_item(arg);
  return false;
}

function sliders_delete_item(arg) {
  currSlider.find('.item-con').eq(arg).remove();
  if (arg < itemIndex[currSlider_nr] - 1) {
    for (i = arg; i < itemIndex[currSlider_nr] - 1; i++) {
      var _c = currSlider.find('.item-con').eq(i);
      for (j = 0; j < _c.find('.textinput').length; j++) {
        sliders_rename(_c.find('.textinput').eq(j), currSlider_nr, i);
      }
    }
  }
  itemIndex[currSlider_nr]--;
  return false;
}

function sliders_click_itemduplicate() {
  var index = currSlider.find('.item-duplicate').index(jQuery(this));
  var _cache = currSlider.find('.items-con').eq(0);
  _cache.append(jQuery(this).parent().clone());


  //curr item
  var _c = _cache.children().last();

  var i = 0;
  for (i = 0; i < _cache.children().last().find('.textinput').length; i++) {
    sliders_rename(_cache.children().last().find('.textinput').eq(i), currSlider_nr, itemIndex[currSlider_nr]);
  }
  for (i = 0; i < _cache.children().last().find('textarea').length; i++) {
    var _c2 = _cache.children().last().find('textarea').eq(i);
    _c2.val(_cache.children().eq(index).find('textarea').eq(i).val());
  }




  _c.find('.inited').removeClass('inited');
  setTimeout(dzstoggle_initalltoggles, 1000);
  itemIndex[currSlider_nr]++;

  return false;
  //sliders_showslider(index);

}

function sliders_click_sliderdelete() {

  var r = confirm("are you sure you want to delete ?");
  if (r == true) {
  } else {
    return false;
  }

  if (dzsvg_settings.is_safebinding == 'on') {

  } else {
    var index = jQuery('.slider-delete').index(jQuery(this));
    sliders_deleteslider(index);
    return false;
  }

}

function sliders_deleteslider(arg) {
  jQuery('.main_sliders').children('tbody').children().eq(arg).remove();
  jQuery('.slider-con').eq(arg).remove();
  let _cache = null;
  if (arg < sliderIndex - 1) {
    for (i = arg; i < sliderIndex - 1; i++) {
      _cache = jQuery('.slider-con').eq(i);
      for (j = 0; j < _cache.find('.textinput').length; j++) {
        var _c2 = _cache.find('.textinput').eq(j);
        sliders_rename(_c2, i, 'same')
      }
    }
  }

  sliderIndex--;
  if (arg == currSlider_nr) {
    currSlider_nr = -1;
    sliders_showslider(arg);
  }
}

var extra_targettagseditor;
var extra_targetplaylistseditor;

function sliders_addlisteners() {
  jQuery('.add-item').unbind();
  jQuery('.add-item').bind('click', click_additem);
  jQuery('.items-con').sortable({
    placeholder: "ui-state-highlight",
    update: item_onsorted
  });
  if (jQuery.fn.singleUploader) {
    jQuery('.dzs-upload').singleUploader();
  }
  if (window.dzstoggle_initalltoggles) {
    dzstoggle_initalltoggles();
  } else {
  }


  jQuery('.btn-tageditor').each(function () {
    var _t = jQuery(this);
    if (_t.hasClass('inited')) {

    } else {
      _t.addClass('inited');
      _t.bind('click', function (e) {
        var _tt = jQuery(this);

        extra_targettagseditor = _tt.prev();
        e.preventDefault();
        jQuery.fn.zoomBox.open(dzsvg_settings.base_pluginPath + 'admin/tagseditor/popup.php?initer=' + extra_targettagseditor.val(), 'iframe', {
          width: 400,
          height: 800
        });
        return false;
      });
    }
  });

  jQuery('.btn-playlistseditor').each(function () {
    var _t = jQuery(this);
    if (_t.hasClass('inited')) {

    } else {
      _t.addClass('inited');
      _t.bind('click', function (e) {
        var _tt = jQuery(this);

        extra_targetplaylistseditor = _tt.prev();
        e.preventDefault();
        jQuery.fn.zoomBox.open(dzsvg_settings.base_pluginPath + 'admin/playlistseditor/popup.php?initer=' + extra_targetplaylistseditor.val(), 'iframe', {
          width: 400,
          height: 800
        });
        return false;
      });
    }
  });
  extra_skin_hiddenselect();


  function extra_skin_hiddenselect() {


    jQuery(document).undelegate('.select-hidden-metastyle .an-option', 'click');
    jQuery(document).delegate('.select-hidden-metastyle .an-option', 'click', click_anoption);


    jQuery(document).undelegate('.select-hidden-metastyle select', 'change');
    jQuery(document).delegate('.select-hidden-metastyle select', 'change', change_selecthidden);


    for (i = 0; i < jQuery('.select-hidden-metastyle').length; i++) {
      var _t = jQuery('.select-hidden-metastyle').eq(i);
      if (_t.hasClass('inited')) {
        continue;
      }
      _t.addClass('inited');
      change_selecthidden(null, _t.children('select').eq(0));
    }

    function change_selecthidden(e, arg) {
      var _c = jQuery(this);
      if (arg != undefined) {
        _c = arg;
      }
      var _con = _c.parent();
      var selind = _c.children().index(_c.children(':selected'));
      var _slidercon = _con.parent().parent();
      _con.find('.an-option').removeClass('active');
      _con.find('.an-option').eq(selind).addClass('active');

      var optval = _c.val();
      do_changemainsliderclass(_slidercon, selind, optval);
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
      _con.children('select').eq(0).trigger('change');
    }

    function do_changemainsliderclass(arg, argval, optval) {
      //extra function - handmade
      if (arg.hasClass('slider-con')) {
        arg.removeClass('mode_normal mode_ytuserchannel mode_ytplaylist mode_vmuserchannel mode_vmchannel mode_ytkeywords mode_vmalbum');
        if (typeof optval != "undefined") {
          arg.addClass("mode_" + optval)
        }

      } else {
        if (arg.hasClass('item-settings-con')) {
          arg.removeClass('type_youtube type_normal type_vimeo type_audio type_image type_rtmp type_inline type_link');

          if (argval == 0) {
            arg.addClass('type_youtube')
          }
          if (argval == 1) {
            arg.addClass('type_normal')
          }
          if (argval == 2) {
            arg.addClass('type_vimeo')
          }
          if (argval == 3) {
            arg.addClass('type_audio')
          }
          if (argval == 4) {
            arg.addClass('type_image')
          }
          if (argval == 5) {
            arg.addClass('type_link')
          }
          if (argval == 6) {
            arg.addClass('type_rtmp')
          }
          if (argval == 7) {
            arg.addClass('type_inline')
          }
        } else {
        }

      }


    }

  }
}

function extra_receiver_tagsreceived(arg) {
  extra_targettagseditor.val(arg);
  jQuery.fn.zoomBox.close();
}

function extra_receiver_playlistsreceived(arg) {
  extra_targetplaylistseditor.val(arg);
  jQuery.fn.zoomBox.close();
}

function sliders_click_addslider() {

  if (dzsvg_settings.is_safebinding == 'on') {

  } else {
    sliders_addslider();
    return false;
  }
}

function sliders_additem(arg1, arg2, arg3) {
  var j = 0;


  //====arg1 the slider index
  var _cache = jQuery('.items-con').eq(arg1);
  _cache.append(itemstructure);
  for (let i = 0; i < _cache.children().last().find('.textinput').length; i++) {
    sliders_rename(_cache.children().last().find('.textinput').eq(i), arg1, itemIndex[arg1]);
  }
  if (arg2 != undefined) {
    _cache.children().last().find('.textinput[data-label=source]').eq(0).val(arg2)
    _cache.children().last().find('.textinput[data-label=source]').eq(0).trigger('change');
  }
  if (arg3 != undefined) {
    if (arg3.title != undefined) {
      _cache.children().last().find('.textinput[name*="-title"]').eq(0).val(arg3.title)
      _cache.children().last().find('.textinput[name*="-title"]').eq(0).trigger('change');
    }
    if (arg3.thumb != undefined) {
      _cache.children().last().find('.textinput[name*="-thethumb"]').eq(0).val(arg3.thumb)
      _cache.children().last().find('.textinput[name*="-thethumb"]').eq(0).trigger('change');
    }
    if (arg3.type != undefined) {
      var _c = _cache.children().last().find('.textinput[data-label=type]').eq(0);
      _c.find(':selected').attr('selected', '');

      for (j = 0; j < _c.children().length; j++) {
        if (_c.children().eq(j).text() == arg3.type)
          _c.children().eq(j).attr('selected', 'selected');
      }
      _c.trigger('change');
    }
  }

  itemIndex[arg1]++;
  global_items++;

  check_global_items();

  return false;
}

function check_global_items() {
  var limit = 15;

  if (dzsvg_settings.is_safebinding == 'on') {
    limit = 60;
  }
  if (global_items > limit && jQuery('.notes').find('.limit-notice').length == 0 && dzsvg_settings.settings_limit_notice_dismissed != 'on') {
    if (dzsvg_settings.is_safebinding == 'on') {
      jQuery('.notes').append('<div class="warning limit-notice"><strong>Warning</strong> - you have many items in this gallery. max_input_vars is defaulted to 1000. What this means is if you have more then ' + limit + ' saving might not work. and there are three possible solutions to this:</p>        <ol>  <li>increase max_input_vars via php.ini or .htaccess file       <li>OR distrubute your videos accross multiple galleries.   </ol>        <p>Also remember to backup regularly via the Export option from the Gear menu</p>' +
        '<form method="POST"><input type="submit" class="button-secondary" name="dzsvg_dismiss_limit_notice" value="dismiss"/></form></div>');
    } else {
      jQuery('.notes').append('<div class="warning limit-notice"><strong>Warning</strong> - you have many items in this database. max_input_vars is defaulted to 1000. What this means is if you have more then ' + limit + ' items across all the galleries in this database, saving via the <strong>Save All Sliders</strong> option might not work. and there are three possible solutions to this:</p>        <ol>  <li>( recommended ) distribute your galleries accross multiple databases - <a href="https://digitalzoomstudio.net/docs/wpvideogallery/#explaination_dbs">see how</a>           <li>OR increase max_input_vars via php.ini or .htaccess file       <li>OR use the <strong>save slider</strong> ( single ) option - you can only save the current slider you are editing with this                </ol><p>Also remember to backup regularly via the Export option from the Gear menu</p></div>');
    }

  }
}

function sliders_showslider(arg1) {
  if (arg1 == currSlider_nr) {
    return;
  }
  jQuery('.slider-con').eq(currSlider_nr).fadeOut('fast');
  jQuery('.slider-con').eq(arg1).fadeIn('fast');
  currSlider_nr = arg1;
  currSlider = jQuery('.slider-con').eq(currSlider_nr);
  jQuery('.slider-con').removeClass('currSlider');
  currSlider.addClass('currSlider');
}

function click_additem() {


  sliders_additem(currSlider_nr)
  sliders_addlisteners();

  return false;
}

function sliders_change_mainid() {
  var _t = jQuery(this);
  var index = jQuery('.main-id').index(_t);
  if (dzsvg_settings.is_safebinding != 'on') {
  } else {
    index = (dzsvg_settings.currSlider);
  }


  jQuery('.main_sliders tbody').children().eq(index).children().eq(0).text(_t.val());
}

function sliders_change_mainthumb() {
  var _t = jQuery(this);
  var _con = _t.parent().parent().parent().parent();
  if (_t.val() == '' && _con.find('select[data-label=type]').eq(0).val() == 'youtube') {
    _t.val('https://img.youtube.com/vi/' + _con.find('textarea[data-label=source]').eq(0).val() + '/0.jpg');
  }
  _con.parent().find('.item-preview').css('background-image', "url(" + _t.val() + ")");
}

function sliders_change(arg1, arg2, arg3, arg4) {
  //select the main slider
  var _cache = jQuery('.slider-con').eq(arg1);


  var i = 0;


  arg4 = arg4.replace(/{{endscript}}/g, '</script>');
  if (arg2 == "settings") {
    for (i = 0; i < _cache.find('.mainsetting').length; i++) {

      var _c2 = _cache.find('.mainsetting').eq(i);
      var aux = arg1 + "-" + arg2 + "-" + arg3;

      if (_c2.attr('name') == aux) {
        _c2.val(arg4);
        if (_c2[0].nodeName == 'SELECT') {
          for (j = 0; j < _c2.children().length; j++) {
            var auxval = _c2.children().eq(j).text();
            if (_c2.children().eq(j).attr('value') != '' && _c2.children().eq(j).attr('value') != undefined) {
              auxval = _c2.children().eq(j).attr('value');
            }
            if (auxval == arg4)
              _c2.children().eq(j).attr('selected', 'selected');
          }
        }
        if (_c2[0].nodeName == 'INPUT' && _c2.attr('type') == 'checkbox') {
          if (arg4 == 'on') {
            _c2.attr('checked', 'checked');
            _c2.prop('checked', true);
          }
        }
        _c2.trigger('change');
      }
    }
  } else {
    var _c2 = _cache.find('.item-con').eq(arg2);
    for (i = 0; i < _c2.find('.textinput').length; i++) {
      var _c3 = _c2.find('.textinput').eq(i);
      var aux = arg1 + "-" + arg2 + "-" + arg3;
      if (_c3.attr('name') == aux) {
        _c3.val(arg4);
        if (_c3[0].nodeName == 'SELECT') {
          for (j = 0; j < _c3.children().length; j++) {
            if (_c3.children().eq(j).text() == arg4)
              _c3.children().eq(j).attr('selected', 'selected');
          }
        }
        _c3.change();

      }
    }

  }
}

function sliders_rename(arg1, arg2, arg3, arg4) {
  var name = arg1.attr('name');
  var aname = name.split('-');

  if (arg2 != 'same') {
    if (arg2 == undefined) {
      aname[0] = currSlider_nr;
    } else {
      aname[0] = arg2;
    }
  }
  if (arg3 != 'same') {
    if (arg3 == undefined) {
      aname[1] = itemIndex[currSlider_nr];
    } else {
      aname[1] = arg3;
    }
  }
  var str = aname[0] + '-' + aname[1] + '-' + aname[2];
  arg1.attr('name', str);

}

function item_onsorted() {
  for (let i = 0; i < currSlider.find('.item-con').length; i++) {
    var _cache = currSlider.find('.item-con').eq(i);
    for (let j = 0; j < _cache.find('.textinput').length; j++) {
      var _cache2 = _cache.find('.textinput').eq(j);
      sliders_rename(_cache2, undefined, i);
    }
  }
}

function item_open() {
  var _t = jQuery(this);
  var _itemcon = _t.parent();
  if (dzsvg_settings.admin_close_otheritems == 'on') {
    jQuery('.item-con').each(function () {
      var _t2 = jQuery(this);
      if (_t2[0] != _itemcon[0] && _t2.hasClass('active')) {
        _t2.removeClass('active');
      }
    });
  }


  if (_itemcon.hasClass('active')) {
    _itemcon.removeClass('active');
  } else {
    _itemcon.addClass('active');
  }
}

function sliders_saveslider() {
  jQuery('#save-ajax-loading').css('visibility', 'visible');
  var mainarray = currSlider.serializeAnything();


  var auxslidernr = currSlider_nr;

  if (dzsvg_settings.is_safebinding == 'on') {
    auxslidernr = dzsvg_settings.currSlider;
  }

  var data = {
    action: 'dzsvg_ajax_saveSlider'
    , postdata: mainarray
    , sliderid: auxslidernr
    , currdb: dzsvg_settings.currdb
  };
  jQuery.post(ajaxurl, data, function (response) {
    jQuery('#save-ajax-loading').css('visibility', 'hidden');
    if (response.indexOf('success') > -1) {
      jQuery('.saveconfirmer').html('Options saved.');
    } else {
      jQuery('.saveconfirmer').html('There seemed to be a problem ? Please check if options were actually saved.');
    }
    jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
  });
  return false;
}

function sliders_saveall() {
  jQuery('#save-ajax-loading').css('visibility', 'visible');
  var mainarray = jQuery('.master-settings').serialize();
  var data = {
    action: 'dzsvg_ajax_saveSliders'
    , postdata: mainarray
    , currdb: dzsvg_settings.currdb
  };
  jQuery('.saveconfirmer').html('Options saved.');
  jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
  jQuery.post(ajaxurl, data, function (response) {
    jQuery('#save-ajax-loading').css('visibility', 'hidden');
  });

  return false;
}

function sliders_allready() {


  jQuery('table.main_sliders').find('.slider-in-table').eq(dzsvg_settings.currSlider).addClass('active');

  setTimeout(function () {
    jQuery.get("https://zoomthe.me/cronjobs/cache/dzsvg_get_version.static.html", function (data) {

      var newvrs = Number(data);
      if (newvrs > Number(jQuery('.version-number .now-version').html())) {
        jQuery('.version-number').append('<span class="new-version info-con" style="width: auto;"> <span class="new-version-text">/ new version ' + data + '</span><div class="sidenote">Download the new version by going to your CodeCanyon accound and accessing the Downloads tab.</div></div> </span>')
      }
    });
  }, 2000);

}


function sliders_saveslider_vpc() {
  jQuery('#save-ajax-loading').css('visibility', 'visible');
  var mainarray = currSlider.serializeAnything();


  var auxslidernr = currSlider_nr;

  if (dzsvg_settings.is_safebinding == 'on') {
    auxslidernr = dzsvg_settings.currSlider;
  }

  var data = {
    action: 'dzsvg_save_vpc'
    , postdata: mainarray
    , sliderid: auxslidernr
    , currdb: dzsvg_settings.currdb
  };
  jQuery.post(ajaxurl, data, function (response) {
    jQuery('#save-ajax-loading').css('visibility', 'hidden');
    if (response.indexOf('success') > -1) {
      jQuery('.saveconfirmer').html('Options saved.');
    } else {
      jQuery('.saveconfirmer').html('There seemed to be a problem ? Please check if options were actually saved.');
    }
    jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
  });
  return false;
}

function sliders_saveall_vpc() {
  jQuery('#save-ajax-loading').css('visibility', 'visible');
  var mainarray = jQuery('.master-settings').serialize();
  var data = {
    action: 'dzsvg_save_vpc'
    , postdata: mainarray
    , currdb: dzsvg_settings.currdb
  };
  jQuery('.saveconfirmer').html('Options saved.');
  jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
  jQuery.post(ajaxurl, data, function (response) {
    jQuery('#save-ajax-loading').css('visibility', 'hidden');
  });

  return false;
}


function global_dzsmultiupload(arg) {
  sliders_additem(currSlider_nr, window.dzs_upload_path + arg);
}

function sliders_resize() {
  jQuery('.master-settings').height(currSlider.height() + 250)
}
