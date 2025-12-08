"use strict";

jQuery(document).ready(function($){




    function mo_saveall(){
        jQuery('#save-ajax-loading').css('visibility', 'visible');
        var mainarray = jQuery('.mainsettings').serialize();
        var data = {
            action: 'dzsvg_ajax_mo',
            postdata: mainarray
        };
        jQuery('.saveconfirmer').html('Options saved.');
        jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
        jQuery.post(ajaxurl, data, function(response) {
            if(window.console !=undefined ){
                console.log('Got this from the server: ' + response);
            }
            jQuery('#save-ajax-loading').css('visibility', 'hidden');
        });

        return false;
    }


    $('.saveconfirmer').fadeOut('slow');
    $('.dzsvg-mo-save-mainoptions').bind('click', mo_saveall);
    $('*[name="enable_developer_options"]').on('change', function(){
        "use strict";


        var _t = $(this);

        if(_t.prop('checked')){


            var r = confirm("Are you sure you want to do this ? Options that will appear are only for developers");

            if(r){
                $('.dzsvg-mo-save-mainoptions').trigger('click');

                setTimeout(function(){
                    window.location.href = window.location.href;
                },3000);
            }else{
                _t.prop('checked',false);
            }
        }
    });

});