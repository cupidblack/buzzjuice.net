
var dzsHelpers = require('../js_common/_dzs_helpers');
exports.generateThumbnailForVideo = function(videoSource, videoType, _con){



  var $ = jQuery;





  return new Promise((resolve,reject)=>{


    if (videoType == 'video') {


      // -- try to generate image
      if (window.dzsvg_settings && dzsvg_settings.admin_try_to_generate_thumb_for_self_hosted_videos == 'on') {


        $('.dzs-single-upload-preview-img').addClass('generating-thumb');


        var source = _con.find('*[name=dzsvg_meta_featured_media]').eq(0).val();


        var aux43 = '<div class="screenshot-canvas-con';


        if (window.dzsvg_settings && window.dzsvg_settings.debug_mode == 'on') {
          aux43 += ' debug-mode';
        }


        aux43 += '"><video crossOrigin="Anonymous" class="temp-screenshot-video" width="600" height="400" src="' + source + '" style="opacity: 0;"></video><canvas crossOrigin="Anonymous" width="600" height="400" class="temp-screenshot-canvas" style="opacity: 0;"></canvas></div>';


        _con.after(aux43);



        var _c = _con.next();
        var $canvas_ = _c.find('canvas').get(0);
        var $canvasContext_ = $canvas_.getContext('2d');
        var $videoElement_ = _c.find('video').get(0);

        _c.find('video').get(0).currentTime = 5;


        $videoElement_.addEventListener('error', (err)=>{

          reject('');
        })

        setTimeout(function () {

          $canvasContext_.drawImage($videoElement_, 0, 0);


          var xhr = null;

          var canvasData = $canvas_.toDataURL("image/png");
          var xmlHttpReq = false;
          if (window.XMLHttpRequest) {
            xhr = new XMLHttpRequest();
          }

          xhr.open('POST', dzsvg_settings.dzsvg_site_url + '/?dzsvg_action=savescreenshot&name=' + encodeURIComponent(source), true);
          xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
          xhr.onreadystatechange = function () {
            var response = xhr.responseText;

            if (xhr.readyState == 4 && xhr.status == 200) {

              _con.find('*[name=dzsvg_meta_thumb]').trigger('change');
              $('.dzs-single-upload-preview-img').removeClass('generating-thumb');

              $('.screenshot-canvas-con:not(.debug-mode)').remove();

              resolve(response);

            }
          }
          xhr.send("imgData=" + canvasData);

        }, 4500);


      }

    }
    if (videoType === 'vimeo') {

      var _thumbfield = _con.find('*[name=dzsvg_meta_thumb]').eq(0);

      if (_thumbfield.val() === '') {


        var data = {
          action: 'dzsvg_vimeo_get_vimeothumb',
          postdata: _con.find('*[name=dzsvg_meta_featured_media]').eq(0).val()
        };

        jQuery.post(ajaxurl, data, function (response) {
          if (response.substr(0, 6) == 'error:') {

            alert(response.substr(7));
            jQuery('.import-error').fadeIn('fast').delay(5000).fadeOut('slow');
            return false;
          }
          resolve(response);

        });
      }
    }

    if (videoType === 'youtube') {


      resolve('https://img.youtube.com/vi/' + dzsHelpers.sanitize_to_youtube_id(videoSource) + '/0.jpg');
    }
  })


}


exports.generateThumbnailForField = function($autoGenerateButton_){

  // -- refresh main thumbnail

  var $ = jQuery;
  var _t = $($autoGenerateButton_);
  var $item_tabContent = null;
  var adminHelpers = this;




  if (_t.parent().parent().hasClass('select-hidden-con')) {
    $item_tabContent = _t.parent().parent();
  }
  if (_t.parent().parent().parent().hasClass('select-hidden-con')) {
    $item_tabContent = _t.parent().parent().parent();
  }
  if (_t.parent().parent().parent().hasClass('tab-content')) {
    $item_tabContent = _t.parent().parent().parent();
  }


  if ($item_tabContent) {

    const videoSource = $item_tabContent.find('*[name="dzsvg_meta_featured_media"]').eq(0).val();
    let videoType = $item_tabContent.find('select[name="dzsvg_meta_item_type"]').eq(0).val();
    var $itemThumbnailField = $item_tabContent.find('*[name=dzsvg_meta_thumb]').eq(0);


    if (videoType === 'detect' || videoType === '') {
      videoType = dzsHelpers.detect_video_type_and_source(videoSource).type;
    }

    if (videoType === 'detect' || videoType === '') {
      videoType = 'video';
    }
    const videoThumbComputed = adminHelpers.generateThumbnailForVideo(videoSource, videoType, $item_tabContent).then(result => {


      $itemThumbnailField.val(result).trigger('change');



    }).catch(err => {
      console.log(err);

    });




  } else {
  }
}