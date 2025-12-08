


function docReady(){



// -- add shortcode buttons to gutenberg classic block
  setInterval(function(){
    if(window.tinyMCE){
      for(var i in window.tinyMCE.editors){


        var _el = window.tinyMCE.editors[i];
        var $_el = _el.$();

        // -- gutenberg ..

        if($_el.hasClass('wp-block-freeform')){

          var _cach = $_el.parent().parent().parent();
          if(_cach.find('.wp-content-media-buttons').length===0){


            _cach.find('.block-library-classic__toolbar .mce-last .mce-btn-group').children().eq(0).append('<div style="display: inline-block;" class="wp-content-media-buttons"></div>');

          }
          window.dzsvg_add_media_buttons();
        }

      }
    }
  },2000);
// -- END add shortcode buttons to gutenberg classic block
}
document.addEventListener('DOMContentLoaded', docReady, false);
