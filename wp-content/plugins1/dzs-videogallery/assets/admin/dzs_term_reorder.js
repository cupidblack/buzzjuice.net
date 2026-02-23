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


function get_query_arg_nr(purl) {

  var nr = 0;
  if (purl.indexOf('=') > -1) {
    var regexS = "[?&]";
    var regex = new RegExp(/[?&]/g);
    var regtest = null;


    var ibreaker = 10;
    while (regtest = regex.exec(purl)) {
      ibreaker--;
      if (ibreaker < 0) {
        break;
      }

      nr++;
    }


  }

  return nr;
}


jQuery(document).ready(function ($) {


  $(document).on('click', '.import-dzscfs-sample, .import-form--head > .option, .colorpicker-con .color-spectrum, .sort-up-down-conglomerate > *', handle_mouse);


  var thumbdisplay_arr = [];


  try {
    thumbdisplay_arr = JSON.parse(window.dzs_term_order.thumbdisplay);
  } catch (err) {
    console.info(err);
  }


  var cat_sort_arr = [];


  try {
    cat_sort_arr = JSON.parse(window.dzs_term_order.cat_sort);
  } catch (err) {
    console.info(err);
  }


  // -- q admin functionality


  var is_thumb_display_page = false;


  for (var lab in thumbdisplay_arr) {
    if (get_query_arg(window.location.href, 'post_type') == thumbdisplay_arr[lab]) {
      is_thumb_display_page = true;
    }
  }


  for (var lab in thumbdisplay_arr) {
    if (thumbdisplay_arr[lab] == 'post') {

      if (window.location.href.indexOf('edit.php') > -1 && get_query_arg_nr(window.location.href) == 0) {

        is_thumb_display_page = true;
      }
    }
  }


  if (is_thumb_display_page) {


    var i3 = 0;


    var id_arr = [];
    $('#the-list > tr').each(function () {
      var _t4 = $(this);

      var id = String(_t4.attr('id')).replace('post-', '');
      id_arr.push(id);


      i3++;

    })


    setTimeout(function () {

      function delay_it(arg) {

        setTimeout(function () {

          arg.addClass('has-image');
        }, 100)
      }

      var data = {
        action: 'dzs_get_all_post_thumb_url'
        , postdata: JSON.stringify(id_arr)
      };
      jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
      jQuery.ajax({
        url: ajaxurl
        , data: data
        , method: "POST"
        , dataType: "html"
        , complete: function (response) {


          if (response && response.responseText) {

            var json_string = response.responseText;
            try {

              var thumb_arr = JSON.parse(json_string);


              for (var i5 in thumb_arr) {
                var cac = thumb_arr[i5];


                if (cac.thumb) {

                  var _c234 = $('#post-' + cac.id).find('.column-title');


                  _c234.prepend('<div class="divimage" style="background-image: url(' + cac.thumb + '); "></div>');

                  delay_it(_c234);
                }
              }
            } catch (err) {
            }
          }
        }
      });
    }, 100);


  }


  function handle_mouse(e) {
    "use strict";


    var _t = $(this);


    if (_t.hasClass('meta-sort-up')) {

      var _con = null;

      if (_t.parent().parent().parent().parent().hasClass('meta-order-tr')) {
        _con = _t.parent().parent().parent().parent();
      }


      if (_con.prev().length) {
        _con.prev().before(_con);

        update_dzs_meta_order();
      }


    }
    if (_t.hasClass('meta-sort-down')) {

      var _con = null;

      if (_t.parent().parent().parent().parent().hasClass('meta-order-tr')) {
        _con = _t.parent().parent().parent().parent();
      }


      if (_con.next().length) {
        _con.next().after(_con);

        update_dzs_meta_order();
      }


    }
  }


  $('.subsubsub').before($('.parent-cats-shower').eq(0))

  setTimeout(function () {
    $('.parent-cats-shower').eq(0).addClass('loaded');
  }, 500);


  for (var lab in cat_sort_arr) {

    if (get_query_arg(window.location.href, 'taxonomy') == cat_sort_arr[lab]) {


      $('.column-posts').each(function () {
        var _t = $(this);

        var hre = _t.children('a').attr('href');
        hre = add_query_arg(hre, 'zoom-term-reorder', 'on');
        _t.children('a').attr('href', hre);
      })

    }

  }


  if (get_query_arg(window.location.href, 'zoom-term-reorder') == 'on') {


    $('.wrap').append($('.dzs-sort-portfolio').eq(0));

    setTimeout(function () {

      $('.dzs-sort-portfolio').eq(0).addClass('loaded');
    }, 100)


    if ($.fn.sortable) {


      $('.the-sortable-list').sortable({
        items: 'tr',
        scrollSensitivity: 50,
        forcePlaceholderSize: true,
        forceHelperSize: false,
        handle: '.fa-arrows'
        , opacity: 0.7
        , placeholder: 'dzs_sort_term_list-placeholder'
        , update: function (event, ui) {


          var _t = $(this);
          var len = $(this).children().length;


          window.update_dzs_meta_order();
        }
      });
    }


    $('.meta-order-new-set').each(function () {
      var _t2 = $(this);

      var ord = Number(_t2.attr('data-meta-order'));


      $('tr[data-meta-order="' + (ord - 1) + '"]').before(_t2);
    })

    if (window.needs_js_reorder) {
      setTimeout(function () {

        window.update_dzs_meta_order();
      }, 500)
    }
  }


});


window.update_dzs_meta_order = function () {
  // -- @arg is the .dzs_item_gallery_list element


  var mainarray = [];


  var iorder = 0;

  var len = jQuery('.meta-order-tr').length

  jQuery('.meta-order-tr').each(function () {
    var _t2 = jQuery(this);

    var neworder = len - iorder;
    _t2.attr('data-meta-order', neworder);

    _t2.find('.column-order').html(neworder)

    iorder++;
  })


  jQuery('.meta-order-tr').each(function () {

    var _t = jQuery(this);
    var aux = {
      'order': _t.attr('data-meta-order')
      , 'id': _t.attr('data-post-id')
    }
    mainarray.push(aux);
  })


  var data = {
    action: 'dzs_update_term_order'
    , postdata: JSON.stringify(mainarray)
    , meta_key: jQuery('.dzs-sort-portfolio').attr('data-meta-key')
  };
  jQuery('.saveconfirmer').html('Options saved.');
  jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
  jQuery.post(ajaxurl, data, function (response) {


  });
}