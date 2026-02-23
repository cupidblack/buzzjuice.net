(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
const tinyMceContentReplace = arg => {
  var currentTinymceEditor = null;

  if (jQuery('#wp-content-wrap').hasClass('tmce-active') && window.tinyMCE) {
    currentTinymceEditor = window.tinyMCE.activeEditor;
  } else {
    if (window.tinyMCE && window.tinyMCE.activeEditor) {
      currentTinymceEditor = window.tinyMCE.activeEditor;
    }
  }

  if (currentTinymceEditor) {
    if (window.mceeditor_sel != 'notset') {
      if (typeof window.tinyMCE != 'undefined') {
        if (typeof window.tinyMCE.activeEditor != 'undefined') {}

        if (typeof window.tinyMCE.execInstanceCommand != 'undefined') {
          window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, arg);
        } else {
          if (currentTinymceEditor && currentTinymceEditor.execCommand) {
            currentTinymceEditor.execCommand('mceReplaceContent', false, arg);

            if (window.remember_sel) {
              currentTinymceEditor.dom.remove(window.remember_sel);
              window.remember_sel = null;
            }
          } else {
            window.tinyMCE.execCommand('mceReplaceContent', false, arg);
          }
        }
      }
    } else {
      window.tinyMCE.execCommand('mceReplaceContent', false, arg);
    }
  } else {
    var currentContent = '';

    if (jQuery("#content").length) {
      currentContent = jQuery("#content").val();
    }

    var bigaux = currentContent + arg;

    if (window.htmleditor_sel) {
      bigaux = currentContent.replace(window.htmleditor_sel, arg);
    }

    if (jQuery("#content").length) {
      jQuery("#content").val(bigaux);
    }
  }
};

exports.tinyMceContentReplace = tinyMceContentReplace;

},{}],2:[function(require,module,exports){
function dzsvg_receiver(e){tinyMceFunctions.tinyMceContentReplace(e),close_ultibox()}function close_zoombox2(){window.close_ultibox()}const tinyMceFunctions=require("./jsinc/_replace_tinymce_content");window.close_zoombox=function(){jQuery.fn.zoomBox.close()},window.dzsvg_receiver=dzsvg_receiver;
},{"./jsinc/_replace_tinymce_content":1}]},{},[2])


//# sourceMappingURL=receiver.js.map