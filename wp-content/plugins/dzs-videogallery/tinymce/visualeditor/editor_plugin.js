(function () {
  tinymce.create('tinymce.plugins.ve_dzs_video', {

    init: function (ed, url) {
      var t = this;

      t.url = url;


      //replace shortcode before editor content set
      ed.onBeforeSetContent.add(function (ed, o) {
        o.content = t.replace_wsi(o.content);
      });

      ed.onExecCommand.add(function (ed, cmd) {
        if (cmd === 'mceInsertContent') {
          tinyMCE.activeEditor.setContent(t.replace_wsi(tinyMCE.activeEditor.getContent()));
        }
      });
      ed.onPostProcess.add(function (ed, o) {
        if (o.get) {
          o.content = t.replace_sho(o.content);
        }
      });
    },

    replace_wsi: function (co) {
      if (co != undefined) {
        return co.replace(/\[dzs_video([^\]]*)\]/g, function (a, b) {
          var aux = '<div class=\'ve_dzs_video mceItem mceNonEditable\' contentEditable="false" data-shortcodecontent=\'dzs_video' + tinymce.DOM.encode(b) + '\' >[ dzs_video' + jQuery('<div/>').text(b).html() + ' ]</div>';


          return aux;

        });
      }
//
      return co;
    },


    replace_sho: function (co) {


      co = co.replace(/<div.*?class="ve_dzs_video.*?<\/div>/g, function (a, b) {

        var aux = (getAttr(a, 'data-shortcodecontent'));
        aux = aux.replace(/&amp;/g, '');
        aux = aux.replace(/&quot;/g, '"');
        return '[' + aux + ']';
      });

      return co;
    }

  });

  //--better idea to have image and :before and / :after tags - with editor buttons
  tinymce.PluginManager.add('ve_dzs_video', tinymce.plugins.ve_dzs_video);
})();

function getAttr(s, n) {
  n = new RegExp(n + '=[\"|\'](.*?)[\"|\']', 'g').exec(s);
  if (n[1]) {
    return n[1];
  } else {
    return null;
  }
};