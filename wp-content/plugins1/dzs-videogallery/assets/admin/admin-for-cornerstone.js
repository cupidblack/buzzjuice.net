jQuery(document).ready(function($){






    setTimeout(function(){


        if(window.cs){

            cs.listenTo( cs.events, 'inspect:element', function(e,e2,e3){

                if(e.attributes._type=='dzsvg') {
                    $('.cs-control[data-name="source"]').each(function () {
                        var _t = $(this);

                        _t.find('input[type="text"]').addClass('input-big-image upload-target-prev upload-type-video ');

                        if (_t.find('.upload-for-target').length == 0) {
                            _t.find('input[type="text"]').after('<a href="#" class="button-secondary dzsvg-wordpress-uploader">Upload</a>');
                        }
                    })
                }
            } );
        }
    },10);




});

