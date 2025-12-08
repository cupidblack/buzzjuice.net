"use strict";
const tinyMceFunctions = require('./jsinc/_replace_tinymce_content');

function dzsvg_receiver(arg) {
  var aux = '';
  var bigaux = '';


  tinyMceFunctions.tinyMceContentReplace(arg);

  close_ultibox();
}

window.close_zoombox = function () {
  jQuery.fn.zoomBox.close();

}
window.dzsvg_receiver = dzsvg_receiver;

function close_zoombox2() {
  window.close_ultibox();
}