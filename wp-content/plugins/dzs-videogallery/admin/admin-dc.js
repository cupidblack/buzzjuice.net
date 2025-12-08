"use strict";
jQuery(document).ready(function($){

    $('.toggle-title').bind('click', function(){
        var $t = $(this);
        if($t.hasClass('opened')){
            ($t.parent().find('.toggle-content').slideUp('fast'));
            $t.removeClass('opened');
        }else{
            ($t.parent().find('.toggle-content').slideDown('fast'));
            $t.addClass('opened');
        }
    })


    var okaytochangehref = false;

    setTimeout(function(){
        "use strict";
        okaytochangehref = true;
    },1500);



    $('.save-button').bind('click', function(){


        jQuery('#save-ajax-loading').css('opacity', '1');
        var mainarray = jQuery('.settings-html5vg').serialize();
        var data = {
            action: 'dzsvg_ajax_options_dc',
            postdata: mainarray
        };

        if($('.settings-html5vg').hasClass('for-aurora')){
            data.action = 'dzsvg_ajax_options_dc_aurora';
        }
        jQuery('.saveconfirmer').html('Options saved.');
        jQuery('.saveconfirmer').fadeIn('fast').delay(2000).fadeOut('fast');
        jQuery.post(ajaxurl, data, function(response) {
            jQuery('#save-ajax-loading').css('opacity', '0');
        });

        return false;

    })

    $('select[name="vp_skin"]').bind('change', function(){
        "use strict";


        var _t = $(this);




        if(okaytochangehref){

            window.location.href = add_query_arg(window.location.href,'skin',_t.val());
        }



    })
    $('.dc-input').bind('change', function(){
        var aux = '';

        var sname ='';
        sname = 'background';

        if($('input[name="'+sname+'"]').val()!=''){

            if(get_query_arg(window.location.href, 'skin')=='aurora'){

                aux+='#html5vp-preview{ background-color: '+$('input[name="'+sname+'"]').val()+'; } ';
            }else{

                aux+='#html5vg-preview.videogallery{ background-color: '+$('input[name="'+sname+'"]').val()+'; } #html5vg-preview.videogallery .navMain{ background-color: '+$('input[name="'+sname+'"]').val()+'; }';
            }
        }
        sname = 'controls_background';
        if($('input[name="'+sname+'"]').val()!=''){

            aux+='#html5vp-preview.skin_aurora .background{ background-color: '+$('input[name="'+sname+'"]').val()+'; }';
            aux+='#html5vg-preview.videogallery .background{ background-color: '+$('input[name="'+sname+'"]').val()+'; }';
            aux+='#html5vg-preview .vplayer.skin_white .background{ background: linear-gradient(to bottom, transparent 0%, rgba(30, 30, 30, 0.5) 100%); }';
        }
        sname = 'scrub_background';
        if($('input[name="'+sname+'"]').val()!=''){
            if(get_query_arg(window.location.href, 'skin')=='aurora'){

                aux+='#html5vp-preview.skin_aurora .scrub-bg{ background-color: '+$('input[name="'+sname+'"]').val()+'; }';
            }else{
                aux+='#html5vg-preview.videogallery .scrub-bg{ background-color: '+$('input[name="'+sname+'"]').val()+'; }';
            }

        }
        sname = 'scrub_buffer';
        if($('input[name="'+sname+'"]').val()!=''){
            if(get_query_arg(window.location.href, 'skin')=='aurora'){

                aux+='#html5vp-preview.skin_aurora .scrub-buffer{ background-color: '+$('input[name="'+sname+'"]').val()+'; }';
            }else{
                aux+='#html5vg-preview.videogallery .scrub-buffer{ background-color: '+$('input[name="'+sname+'"]').val()+'; }';
            }
        }
        sname = 'scrub_progress';
        if($('input[name="'+sname+'"]').val()!=''){
            if(get_query_arg(window.location.href, 'skin')=='aurora'){

                aux+=' ';
            }else{
                aux+='#html5vg-preview.videogallery .scrub-buffer{ background-color: '+$('input[name="'+sname+'"]').val()+'; }';
            }
        }
        sname = 'controls_color';
        if($('input[name="'+sname+'"]').val()!=''){


                aux+='#html5vg-preview.videogallery .playSimple svg path,#html5vg-preview.videogallery .pauseSimple svg path,#html5vg-preview .vplayer .fscreencontrols svg path,#html5vg-preview .vplayer .volumeicon svg path,#html5vg-preview .vplayer .fscreencontrols svg rect,#html5vg-preview .vplayer .fscreencontrols svg polygon{ fill: '+$('input[name="'+sname+'"]').val()+'; }  #html5vg-preview.videogallery .volumeicon:before{ border-right-color: '+$('input[name="'+sname+'"]').val()+'; }#html5vg-preview.videogallery .hdbutton-con .hdbutton-normal{ color: '+$('input[name="'+sname+'"]').val()+'; } #html5vg-preview.videogallery .total-timetext{ color: '+$('input[name="'+sname+'"]').val()+'; } ';



                aux+='#html5vg-preview .vplayer.skin_pro .volumeicon{ background: '+$('input[name="'+sname+'"]').val()+'; } '
                aux+='#html5vg-preview .vplayer.skin_pro .volume_static{ background: '+$('input[name="'+sname+'"]').val()+'; } '


        }
        sname = 'controls_hover_color';
        if($('input[name="'+sname+'"]').val()!=''){
                aux += '#html5vg-preview.videogallery .playSimple:hover svg path,#html5vg-preview.videogallery .pauseSimple:hover svg path,#html5vg-preview .vplayer .volumecontrols:hover svg path,#html5vg-preview .vplayer .fscreencontrols:hover svg path,#html5vg-preview .vplayer .fscreencontrols:hover svg rect,#html5vg-preview .vplayer .fscreencontrols:hover svg polygon{ fill: ' + $('input[name="' + sname + '"]').val() + '; }   ';


                aux+=' #html5vg-preview .vplayer.skin_pro .volumeicon:hover{ background: ' + $('input[name="' + sname + '"]').val() + '; } #html5vg-preview .vplayer.skin_pro .volumeicon:hover:before{ border-right-color: ' + $('input[name="' + sname + '"]').val() + '; }';

        }
        sname = 'controls_highlight_color';
        if($('input[name="'+sname+'"]').val()!=''){
            aux+='#html5vg-preview.videogallery .scrub{ background-color: '+$('input[name="'+sname+'"]').val()+'!important; } #html5vg-preview.videogallery .hdbutton-con .hdbutton-hover{ color: '+$('input[name="'+sname+'"]').val()+'; } ';


            aux+='#html5vg-preview .vplayer.skin_aurora .volume_active{ background-color: '+$('input[name="'+sname+'"]').val()+'; } ';
        }
        sname = 'timetext_curr_color';
        if($('input[name="'+sname+'"]').val()!=''){
            aux+='#html5vg-preview.videogallery .curr-timetext{ color: '+$('input[name="'+sname+'"]').val()+'; } ';
        }
        sname = 'thumbs_bg';
        if($('input[name="'+sname+'"]').val()!=''){
            aux+='#html5vg-preview.videogallery .navigationThumb{ background-color: '+$('input[name="'+sname+'"]').val()+'; } ';
        }
        sname = 'thumbs_active_bg';
        if($('input[name="'+sname+'"]').val()!=''){
            aux+='#html5vg-preview.videogallery .navigationThumb.active{ background-color: '+$('input[name="'+sname+'"]').val()+'; } ';
        }
        sname = 'thumbs_text_color';
        if($('input[name="'+sname+'"]').val()!=''){
            aux+='#html5vg-preview.videogallery .navigationThumb{ color: '+$('input[name="'+sname+'"]').val()+'; } #html5vg-preview.videogallery .navigationThumb .the-title{ color: '+$('input[name="'+sname+'"]').val()+'; } ';
        }


        sname = 'thumbnail_image_width';
        if($('input[name="'+sname+'"]').val()!=''){
            aux+='#html5vg-preview.videogallery .imgblock{ width: '+$('input[name="'+sname+'"]').val()+'px;; }  ';
        }
        sname = 'thumbnail_image_height';
        if($('input[name="'+sname+'"]').val()!=''){
            aux+='#html5vg-preview.videogallery .imgblock{ height: '+$('input[name="'+sname+'"]').val()+'px; }  ';
        }
        $('#html5vg-preview-style').html(aux);
    });

    $('.dc-input').trigger('change');



});