"use strict";
jQuery(document).ready(function($){


  setInterval(function(){

    $('.videogallery:not(.inited)').each(function(){
      var _t2 = $(this);
      dzsvg_init(_t2,{})
    })
  },2000);
})