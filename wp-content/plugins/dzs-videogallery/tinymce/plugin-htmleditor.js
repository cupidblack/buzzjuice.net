'use strict';

window.htmleditor_sel = 'notset';
window.mceeditor_sel = 'notset';
window.dzsvg_widget_shortcode = null;


window.dzsvg_add_media_buttons = function(){


  jQuery('#wp-content-media-buttons,.wp-content-media-buttons').each(function(){
    var _t3 = jQuery(this);
    if(_t3.find('.dzsvg_shortcode').length===0){




      _t3.append('<button type="button"  class="shortcode-opener dzs-shortcode-button dzsvg_shortcode button " data-editor="content"><span class="the-icon"><svg id="Layer_1" style="enable-background:new 0 0 30 30;" version="1.1" viewBox="0 0 30 30" xml:space="preserve" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink"><path fill="#555" d="M27,26H8c-1.105,0-2-0.895-2-2V11c0-1.105,0.895-2,2-2h19c1.105,0,2,0.895,2,2v13C29,25.105,28.105,26,27,26z"/><path fill="#555" d="M6,7h18V6c0-1.105-0.895-2-2-2H3C1.895,4,1,4.895,1,6v14c0,1.105,0.895,2,2,2h1V9C4,7.895,4.895,7,6,7z"/></svg></span> <span class="the-label"> '+dzsvg_settings.translate_add_videogallery+'</span></button>');



      _t3.append('<button type="button"  class="shortcode-opener dzs-shortcode-button dzsvg_shortcode_addvideoshowcase button " data-editor="content"><span class="the-icon"><svg xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 426.667 426.667" style="enable-background:new 0 0 426.667 426.667;" xml:space="preserve" width="512px" height="512px"><path d="M42.667,85.333H0V384c0,23.573,19.093,42.667,42.667,42.667h298.667V384H42.667V85.333z" fill="#5e5e5e"/><path d="M384,0H128c-23.573,0-42.667,19.093-42.667,42.667v256c0,23.573,19.093,42.667,42.667,42.667h256     c23.573,0,42.667-19.093,42.667-42.667v-256C426.667,19.093,407.573,0,384,0z M128,298.667l64-85.333l43.307,57.813L298.667,192     L384,298.667H128z" fill="#5e5e5e"/></svg></span> <span class="the-label"> '+dzsvg_settings.translate_add_videoshowcase+'</span></button>');



      _t3.append('<button type="button" id="dzsvg-shortcode-generator-player" class="shortcode-opener dzs-shortcode-button dzsvg-shortcode-generator-player button " data-editor="content"><span class="the-icon"><svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px" width="100px" height="100px" viewBox="-50 -49 100 100" enable-background="new -50 -49 100 100" xml:space="preserve"> <g> <path d="M0.919-46.588c-9.584,0-18.294,2.895-26.031,7.576l-8.591,7.014l-5.725,7.043l8.51-9.707 C-40.988-25.8-47.44-13.066-47.44,1.448c0,2.417,0.324,4.188,0.324,6.606l-0.286-5.194l1.333,9.228l0,0 c5.081,21.92,24.098,37.558,46.988,37.558c26.601,0,48.207-21.759,48.207-48.197C49.286-24.992,27.521-46.588,0.919-46.588z M0.919,45.458c-20.311,0-37.634-14.19-42.554-33.046c-0.324-1.617-0.485-2.74-0.809-4.197c-0.162-2.255-0.324-4.187-0.324-6.605 c0-14.989,7.585-28.21,18.949-36.271c2.656-1.609,5.32-3.226,8.052-4.188c5.159-2.265,10.963-3.226,16.685-3.226 c24.267,0,43.771,19.664,43.771,43.846C44.69,25.947,25.187,45.458,0.919,45.458z"/> <path d="M19.137-0.168L-6.171-15.637c-0.647-0.323-1.447-0.323-2.095,0c-0.562,0.315-1.047,1.286-1.047,1.933v22.08v4.036v4.835 c0,0.962,0.485,1.607,1.208,2.256c0.324,0,0.486,0,0.971,0c0.478,0,0.799,0,1.123,0L19.298,3.38 c0.484-0.323,0.808-0.809,0.808-1.932C20.105,0.64,19.782,0.153,19.137-0.168z M0.603,9.672l-4.433,2.74l-1.294,0.962v-0.962V8.055 v-18.056L13.661,1.771L0.603,9.672z"/> </g> </svg></span> <span class="the-label"> '+dzsvg_settings.translate_add_player+'</span></button>');




    }
  })
};


jQuery(document).ready(function($){
  if(typeof(dzsvg_settings)=='undefined'){
    return;
  }




  window.dzsvg_add_media_buttons();

  $(document).on('click','.dzsvg_shortcode', function(){

    var shortcode_iframe_url = dzsvg_settings.shortcode_generator_url;

    var ed = null;

    if(jQuery('#wp-content-wrap').hasClass('tmce-active') && window.tinyMCE ){

      ed = window.tinyMCE.activeEditor;
    }else{
      if(window.tinyMCE && window.tinyMCE.activeEditor){
        ed = window.tinyMCE.activeEditor;
      }
    }



    var parsel = '';
    if(ed ){

      var sel=ed.selection.getContent({format: 'html'});

      if(sel!=''){
        parsel+='&sel=' + encodeURIComponent(sel);
        window.mceeditor_sel = sel;
      }else{
        window.mceeditor_sel = '';
      }


      window.htmleditor_sel = 'notset';


    }else{




      var textarea = document.getElementById("content");
      var start = textarea.selectionStart;
      var end = textarea.selectionEnd;
      var sel = textarea.value.substring(start, end);

      if(sel!=''){
        parsel+='&sel=' + encodeURIComponent(sel);
        window.htmleditor_sel = sel;
      }else{
        window.htmleditor_sel = '';
      }

      window.mceeditor_sel = 'notset';
    }


    shortcode_iframe_url+=parsel;

    window.open_ultibox(null, {suggested_width: 1200, suggested_height: 700,forcenodeeplink: 'on', dims_scaling: 'fill', source: shortcode_iframe_url, type: 'iframe'});
  });


  $(document).on('click','.dzsvg_shortcode_addvideoshowcase', function(){


    var ed = null;

    if(jQuery('#wp-content-wrap').hasClass('tmce-active') && window.tinyMCE ){

      ed = window.tinyMCE.activeEditor;
    }else{
      if(window.tinyMCE && window.tinyMCE.activeEditor){
        ed = window.tinyMCE.activeEditor;
      }
    }

    var parsel = '';



    if(ed ){

      var sel=ed.selection.getContent({format: 'html'});

      if(sel!=''){
        parsel+='&sel=' + encodeURIComponent(sel);
        window.mceeditor_sel = sel;
      }else{
        window.mceeditor_sel = '';
      }


      window.htmleditor_sel = 'notset';


    }else{




      var textarea = document.getElementById("content");
      var start = textarea.selectionStart;
      var end = textarea.selectionEnd;
      var sel = textarea.value.substring(start, end);

      if(sel!==''){
        parsel+='&sel=' + encodeURIComponent(sel);
        window.htmleditor_sel = sel;
      }else{
        window.htmleditor_sel = '';
      }

      window.mceeditor_sel = 'notset';
    }






    window.open_ultibox(null, {suggested_width: 1200, suggested_height: 700,forcenodeeplink: 'on', dims_scaling: 'fill', source:dzsvg_settings.shortcode_showcase_generator_url+parsel, type: 'iframe'});
  });


  $(document).on('click','.dzsvg_shortcode_addvideoplayer', function(){

    var frame = wp.media.frames.dzsvg_addplayer = wp.media({
      // Set the title of the modal.
      title: "Insert Video Player",

      // Tell the modal to show only images.
      library: {
        type: 'video'
      },

      // Customize the submit button.
      button: {
        // Set the text of the button.
        text: "Insert Video",
        // Tell the button not to close the modal, since we're
        // going to refresh the page when the image is selected.
        close: false
      }
    });

    // When an image is selected, run a callback.
    frame.on( 'select', function() {
      // Grab the selected attachment.
      var attachment = frame.state().get('selection').first();

      var arg = '[dzs_video source="'+attachment.attributes.url+'" configs="'+$('*[name*="video-player-configs"]').val()+'" height="'+$('*[name*="video-player-height"]').val()+'" responsive_ratio="off"]';
      if(typeof(top.dzsvg_receiver)=='function'){
        top.dzsvg_receiver(arg);
      }
      frame.close();
    });

    // Finally, open the modal.
    frame.open();
  });



  $(document).delegate('.btn-shortcode-generator-dzsvg-showcase','click', function(){
    var _t = $(this);
    var parsel = '';


    if(_t.prev().hasClass('shortcode-generator-target')){

      window.dzsvg_widget_shortcode = _t.prev();
      parsel+='&sel=' + encodeURIComponent(_t.prev().val());
    }



    window.open_ultibox(null, {suggested_width: 1200, suggested_height: 700,forcenodeeplink: 'on', dims_scaling: 'fill', source:dzsvg_settings.shortcode_showcase_generator_url+parsel, type: 'iframe'});

    return false;
  })








  $(document).on('click','.dzsvg-shortcode-generator-player', function(){


    var ed = null;

    if(jQuery('#wp-content-wrap').hasClass('tmce-active') && window.tinyMCE ){

      ed = window.tinyMCE.activeEditor;
    }else{
      if(window.tinyMCE && window.tinyMCE.activeEditor){
        ed = window.tinyMCE.activeEditor;
      }
    }


    var parsel = '';
    if(ed ){

      var ed = window.tinyMCE.activeEditor;
      var sel=ed.selection.getContent({format: 'html'});

      if(sel!=''){
        parsel+='&sel=' + encodeURIComponent(sel);
        window.mceeditor_sel = sel;
      }else{
        window.mceeditor_sel = '';
      }


      window.htmleditor_sel = 'notset';


    }else{




      var textarea = document.getElementById("content");
      var start = textarea.selectionStart;
      var end = textarea.selectionEnd;
      var sel = textarea.value.substring(start, end);

      if(sel!=''){
        parsel+='&sel=' + encodeURIComponent(sel);
        window.htmleditor_sel = sel;
      }else{
        window.htmleditor_sel = '';
      }

      window.mceeditor_sel = 'notset';
    }

    window.open_ultibox(null,{

      type: 'iframe'
      ,source: dzsvg_settings.shortcode_generator_player_url + parsel
      ,scaling: 'fill' // -- this is the under description
      ,suggested_width: 800 // -- this is the under description
      ,suggested_height: 600 // -- this is the under description
      ,item: null // -- we can pass the items from here too

    })

    return false;
  })


})