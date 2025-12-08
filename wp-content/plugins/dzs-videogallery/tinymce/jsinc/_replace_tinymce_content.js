const tinyMceContentReplace = (arg) => {


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
        if (typeof window.tinyMCE.activeEditor != 'undefined') {
        }

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

    if(jQuery("#content").length){
      currentContent = jQuery("#content").val();
    }


    var bigaux = currentContent + arg;
    if (window.htmleditor_sel) {
      bigaux = currentContent.replace(window.htmleditor_sel, arg);
    }
    if(jQuery("#content").length) {
      jQuery("#content").val(bigaux);
    }
  }
};

exports.tinyMceContentReplace = tinyMceContentReplace;