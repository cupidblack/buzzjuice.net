"use strict";
const dzsDep = require('./js_common/_dzs_dependency.js');
const miscFuncs = require('./jsinc/_misc');
var dzsHelpers = require('./js_common/_dzs_helpers');
var adminHelpers = require('./jsinc/_adminHelpers');
var mainoptions = require('./jsinc/_mainoptions');
require('./jsinc/_check_block_editors.js');
const {dzs_admin_addWarningForUpdate} = require("./jsinc/admin/_view-admin");
jQuery(document).ready(function ($) {
  // Create the media frame.


  setTimeout(reskin_select, 10);
  $(document).undelegate(".select-wrapper select", "change");
  $(document).delegate(".select-wrapper select", "change", change_select);


  $(document).on('click', '.quick-edit-adarray, .quick-edit-qualityarray', handle_mouse);
  $(document).on('change', '.wpb-input[name="db"]', handle_input);
  $(document).on('submit', '.delete-all-settings', handle_input);

  mainoptions.mainoptions_init();


  dzsDep.dep_init();


  function handle_mouse(e) {
    var _t = $(this);


    if (e.type === 'click') {
      if (_t.hasClass('quick-edit-adarray')) {
        var url3 = dzsvg_settings.ad_builder_url;


        window.ad_target_field = _t.prev();
        if (_t.parent().parent().hasClass('zoomsounds-inspector-setting')) {
          window.ad_target_field = _t.parent().parent().find('input').eq(0);
        }
        if (window.ad_target_field.val()) {
          url3 += '&adstart=' + encodeURIComponent(window.ad_target_field.val());
        }


        window.open_ultibox(null, {
          type: 'iframe'
          , source: url3
          , scaling: 'fill' // -- this is the under description
          , suggested_width: '800' // -- this is the under description
          , suggested_height: '700' // -- this is the under description
          , item: null // -- we can pass the items from here too

        });


        return false;
      }

      if (_t.hasClass('quick-edit-qualityarray')) {
        var url3 = dzsvg_settings.quality_builder_url;


        window.quality_target_field = _t.prev();
        if (_t.prev().val()) {
          url3 += '&qualitystart=' + encodeURIComponent(_t.prev().val());
        }

        window.open_ultibox(null, {


          type: 'iframe'
          , source: url3
          , scaling: 'fill' // -- this is the under description
          , suggested_width: '800' // -- this is the under description
          , suggested_height: '700' // -- this is the under description
          , item: null // -- we can pass the items from here too

        });


        return false;
      }
    }
  }

  function handle_input(e) {
    var _t = $(this);


    if (e.type === 'change') {
      if (_t.hasClass('wpb-input')) {

        // -- vico get db

        var mainarray = _t.val();


        if (window.dzsvg_settings && dzsvg_settings.playlists_mode !== 'normal') {

          var data = {
            action: 'dzsvg_get_db_gals',
            postdata: mainarray
          };
          jQuery.post(ajaxurl, data, function (response) {
            jQuery('#save-ajax-loading').css('opacity', '0');

            var aux = '';
            var auxa = response.split(';');
            for (let i = 0; i < auxa.length; i++) {
              aux += '<option>' + auxa[i] + '</option>'
            }
            jQuery('.wpb-input[name=id]').html(aux);
            jQuery('.wpb-input[name=id]').trigger('change');
            jQuery('.wpb-input[name=slider]').html(aux);
            jQuery('.wpb-input[name=slider]').trigger('change');

          });
        }
      }
    }
    if (e.type === 'submit') {
      if (_t.hasClass('delete-all-settings')) {


        var r = confirm("Are you sure you want to delete all video gallery data ? ");

        if (r) {

        } else {
          return false;
        }
      }
    }
  }


  $(document).off('click.dzswup', '.dzs-wordpress-uploader');
  $(document).on('click.dzswup', '.dzs-wordpress-uploader', function (e) {
    var _t = $(this);
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


    var frame = wp.media.frames.dzsp_addimage = wp.media({
      title: "Insert Media",
      library: {
        type: searched_type
      },

      // Customize the submit button.
      button: {
        // Set the text of the button.
        text: "Insert Media",
        close: true
      }
    });

    // When an image is selected, run a callback.
    frame.on('select', function () {
      // Grab the selected attachment.
      var attachment = frame.state().get('selection').first();

      var arg = attachment.attributes.url;

      if (_t.hasClass('insert-id')) {
        arg = attachment.attributes.id;
      }

      _targetInput.val(arg);
      _targetInput.trigger('change');
    });

    // Finally, open the modal.
    frame.open();

    e.stopPropagation();
    e.preventDefault();
    return false;
  });


  $(document).off('click', '.dzsvg-wordpress-uploader');
  $(document).on('click', '.dzsvg-wordpress-uploader', function (e) {
    var _t = $(this);
    var _targetInput = _t.prev();
    var _targetInputTitle = null;


    var _con = _t.parent();


    if (_con.find('.upload-target-prev').length) {
      _targetInput = _con.find('.upload-target-prev').eq(0);
    }
    if (_con.find('.upload-target-title').length) {
      _targetInputTitle = _con.find('.upload-target-title').eq(0);
    }

    var searched_type = '';

    if (_targetInput.hasClass('upload-type-audio')) {
      searched_type = 'audio';
    }
    if (_targetInput.hasClass('upload-type-image')) {
      searched_type = 'image';
    }
    if (_targetInput.hasClass('upload-type-video')) {
      searched_type = 'video';
    }


    if (typeof wp != 'undefined' && typeof wp.media != 'undefined') {
      var uploader_frame = wp.media.frames.dzsvg_addplayer = wp.media({
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


        if (_targetInputTitle) {
          _targetInputTitle.val(attachment.attributes.title);
        }


        _targetInput.trigger('change');
        uploader_frame.close();
      });

      // Finally, open the modal.
      uploader_frame.open();
    }

    return false;
  });


  $(document).off('click', '.dzs-btn-add-media-att');
  $(document).on('click', '.dzs-btn-add-media-att', function () {
    var _t = $(this);

    var args = {
      title: 'Add Item',
      button: {
        text: 'Select'
      },
      multiple: false
    };

    if (_t.attr('data-library_type')) {
      args.library = {
        'type': _t.attr('data-library_type')
      }
    }


    var item_gallery_frame = wp.media.frames.downloadable_file = wp.media(args);

    item_gallery_frame.on('select', function () {

      var selection = item_gallery_frame.state().get('selection');
      selection = selection.toJSON();

      var ik = 0;
      for (ik = 0; ik < selection.length; ik++) {

        var _c = selection[ik];
        if (_c.id == undefined) {
          continue;
        }

        if (_t.hasClass('button-setting-input-url')) {

          _t.parent().parent().find('input').eq(0).val(_c.url);
        } else {

          _t.parent().parent().find('input').eq(0).val(_c.id);
        }


        _t.parent().parent().find('input').eq(0).trigger('change');

      }
    });


    // Finally, open the modal.
    item_gallery_frame.open();

    return false;
  });


  function change_select() {
    var selval = ($(this).find(':selected').text());
    $(this).parent().children('span').text(selval);
  }

  function reskin_select() {
    for (let i = 0; i < jQuery('select').length; i++) {
      var _cache = jQuery('select').eq(i);

      if (_cache.hasClass('styleme') == false || _cache.parent().hasClass('select_wrapper') || _cache.parent().hasClass('dzs--select-wrapper')) {
        continue;
      }
      var sel = (_cache.find(':selected'));
      _cache.wrap('<div class="dzs--select-wrapper"></div>')
      _cache.parent().prepend('<span>' + sel.text() + '</span>')
    }
    jQuery(document).off("change.dzsselectwrap");
    jQuery(document).on("change.dzsselectwrap", ".dzs--select-wrapper select", change_select);


    function change_select() {
      var selval = (jQuery(this).find(':selected').text());
      jQuery(this).parent().children('span').text(selval);
    }

  }


  var aux = window.location.href;


  if (aux.indexOf('plugins.php') > -1 || (aux.indexOf('index.php') > -1 && aux.indexOf('?') == -1)) {


    setTimeout(function () {
      jQuery.get("https://zoomthe.me/cronjobs/cache/dzsvg_get_version.static.html", function (data) {

        const newVersion = Number(data);
        console.log(newVersion, Number(dzsvg_settings.version));
        if (newVersion > Number(dzsvg_settings.version)) {


          dzs_admin_addWarningForUpdate();

        }
      });
    }, 300);
  }

  if (aux.indexOf('&dzsvg_purchase_remove_binded=on') > -1) {

    aux = aux.replace('&dzsvg_purchase_remove_binded=on', '');
    var stateObj = {foo: "bar"};
    if (history) {

      history.pushState(stateObj, null, aux);
    }
  }


  $(document).on('click', '.refresh-main-thumb', function () {

    adminHelpers.generateThumbnailForField(this);


    return false;
  })


  miscFuncs.con_generate_buttons(ajaxurl);

  miscFuncs.extra_skin_hiddenselect()
});


