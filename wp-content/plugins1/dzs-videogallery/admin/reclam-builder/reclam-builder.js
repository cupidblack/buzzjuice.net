"use strict";
function htmlEntities(str) {
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

jQuery(document).ready(function ($) {


  var dragelement = null;

  var cthis = $('.dzsvg-reclam-builder').eq(0);

  cthis.on('mousedown', '.reclam-marker > .icon', handle_mouse);
  cthis.on('click', '.scrub-bg, .reclam-marker > .icon, .delete-ad-btn, .close-ad-btn', handle_mouse);
  cthis.on('mousemove', handle_mouse);
  cthis.on('submit', handle_submit);
  $(document).on('mouseup', '.reclam-marker', handle_mouse);


  var start_array = [];

  if (window.ad_builder_start_array) {
    // window.ad_builder_start_array = String(window.ad_builder_start_array).replace(/(<iframe.*?src=)(\\".*?)(\\")(.*?<\/iframe>)/g, "$1\\$2\\$3$4");
    window.ad_builder_start_array = String(window.ad_builder_start_array).replace(/(<iframe.*?src=)(".*?)(")(.*?<\/iframe>)/g, "$1\\$2\\$3$4");
    window.ad_builder_start_array = sanitize_decode_for_html_attr(window.ad_builder_start_array);
    try {
      start_array = JSON.parse(window.ad_builder_start_array);
    } catch (err) {

    }
  }

  for (var i2 in start_array) {

    generate_ad_marker(start_array[i2])
  }


  // generate_ad_marker({
  //
  //     source:''
  //     ,type:'image'
  //     ,time:'0.75'
  //     ,ad_link:''
  //     ,skip_delay:''
  // })


  function generate_ad_marker(pargs) {


    var margs = {
      source: ''
      , type: 'detect'
      , time: '0'
      , ad_link: ''
      , skip_delay: ''
    }


    if (pargs) {
      margs = $.extend(margs, pargs);
    }


    margs.time = Number(margs.time);

    var aux = '';

    aux += '<div class="reclam-marker dzstooltip-con" style="left: ' + (margs.time * 100) + '%;"> <div class="icon"></div> <div class="dzstooltip align-center style-rounded color-dark-light talign-center  arrow-top" style="top: 100%; margin-top: 20px; width: 200px; text-align: center;"> <div class="dzstooltip--selector-top"></div> <div class="dzstooltip--inner"> <h6>SOURCE</h6> <input class="dzs-input" type="text" name="source[]" value="' + htmlEntities(margs.source) + '"> <button class="dzsvg-wordpress-uploader button-secondary">Upload</button> <h6>TYPE</h6> <select class="dzs-style-me skin-beige" name="type[]"> <option>detect</option> <option>video</option> <option>youtube</option> <option>vimeo</option> <option>image</option> <option>inline</option> </select> <h6>TIME</h6> <input class="dzs-input" type="text" name="time[]" value="' + margs.time + '">  <h6>Ad Link</h6> <input class="dzs-input" type="text" name="ad_link[]" value="' + margs.ad_link + '">  <h6>Skip Delay</h6> <input class="dzs-input" type="text" name="skip_delay[]" value="' + margs.skip_delay + '"> <br> <br><div class="delete-ad-btn">&#x267B; delete</div> <div class="close-ad-btn"><span class="center-it"> &times;</span></div> </div> </div> </div>';

    cthis.find('.scrubbar-con').append(aux);


    cthis.find('select[name="type[]"]').last().val(margs.type);

    dzssel_init('select.dzs-style-me', {init_each: true});

  }


  function handle_mouse(e) {
    var $t = $(this);

    if (e.type == 'mousedown') {

      dragelement = $t.parent();

    }


    if (e.type == 'mousemove') {

      var mx = e.clientX - cthis.offset().left;

      if (dragelement) {
        var rat = mx / $t.width();

        dragelement.css({
          'left': rat * 100 + '%'
        })

        dragelement.find('input[name="time[]"]').val(Number(rat).toFixed(3));
      }

    }
    if (e.type == 'mouseup') {
      dragelement = null;

    }
    if (e.type == 'click') {


      if ($t.hasClass('icon') || $t.hasClass('close-ad-btn')) {
        var $tooltip = null;
        if($t.next().hasClass('dzstooltip')){
          $tooltip = $t.next();
        }
        if($t.parent().parent().hasClass('dzstooltip')){
          $tooltip = $t.parent().parent();
        }
        if($tooltip){

          $tooltip.toggleClass('active');
        }
      }
      if ($t.hasClass('scrub-bg')) {

        var mx = e.clientX - cthis.offset().left;

        var rat = mx / $t.width();



        generate_ad_marker({
          time: rat
        })

      }

      if ($t.hasClass('delete-ad-btn')) {

        var isConfirm = confirm('delete ? ');
        if(isConfirm){

          $t.parent().parent().parent().remove();
        }
      }

    }
  }


  function handle_submit(e) {
    var _t = $(this);

    if (e.type == 'submit') {

      if (_t.hasClass('dzsvg-reclam-builder')) {


        $('input[name="source[]"]').each(function () {
          var _t3 = $(this);

          _t3.val(String(_t3.val()).replace(/"/g, '{{doublequot_fordzsvgad}}'));
        })
        var mainarray = _t.serialize();


        var data = {
          action: 'dzsvg_ajax_json_encode_ad'
          , postdata: mainarray
        };


        var ajaxurl = '';
        if (window.ajaxurl) {

          ajaxurl = window.ajaxurl;
        } else {

          ajaxurl = "ajax_json_encode_ad.php";
        }

        jQuery.post(ajaxurl, data, function (response) {

          $('.output').text(response);


          /**
           * need you for react - might work only for INPUT
           * @param {HTMLElement} element
           * @param {string} value
           */
          function setNativeValue(element, value) {
            element.value = value;
            const propDescriptor = Object.getOwnPropertyDescriptor(element, 'value');

            if (propDescriptor) {

              const valueSetter = propDescriptor.set;
              const prototype = Object.getPrototypeOf(element);
              const prototypeValueSetter = Object.getOwnPropertyDescriptor(prototype, 'value').set;

              if (valueSetter && valueSetter !== prototypeValueSetter) {
                prototypeValueSetter.call(element, value);
              } else {
                valueSetter.call(element, value);
              }
            }
          }

          if (parent.ad_target_field && parent.ad_target_field.get(0)) {
            // parent.ad_target_field.get(0).value = response;
            // parent.ad_target_field.get(0).setAttribute('value', response);


            setNativeValue(parent.ad_target_field.get(0), response);
            // parent.ad_target_field.trigger('change');

            setTimeout(() => {

              var event = new Event('input', {bubbles: true});
              var eventChange = new Event('change', {bubbles: true});
              parent.ad_target_field.get(0).dispatchEvent(event);
              parent.ad_target_field.get(0).dispatchEvent(eventChange);
            }, 10);
          }

          if (parent.close_ultibox) {
            parent.close_ultibox();
          }
        });


        return false;
      }

    }
  }
});


function sanitize_encode_for_html_attr(arg) {

  var fout = htmlEncode(arg);


  fout = fout.replace(/\"/g, '{{quot}}');
  fout = fout.replace(/\[/g, '{patratstart}');
  fout = fout.replace(/\]/g, '{patratend}');

  return fout;
}

function sanitize_decode_for_html_attr(arg) {

  // var fout = htmlDecode(arg);

  var fout = arg;

  fout = fout.replace(/{{quot}}/g, '"');
  fout = fout.replace(/{patratstart}/g, '[');
  fout = fout.replace(/{patratend}/g, ']');

  return fout;
}