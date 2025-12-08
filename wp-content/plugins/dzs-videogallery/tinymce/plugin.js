"use strict";
(function(){
    tinymce.create('tinymce.plugins.abraxSc', {
 
        init : function(ed, url){
            ed.addButton('abraxPHP', {
                title: 'Add Shortcode',
                image: window.theme_url + 'tinymce/img/shortcodes.png',
				onclick: function(){
					window.tinymce_cursor = tinyMCE.activeEditor.selection.getBookmark();
                	tb_show('Ammon Shortcodes', ammonsetings.themeurl + 'tinymce/popup.php?width=630&height=800');
            	}
            }
            )
        }
    });
    tinymce.PluginManager.add('abraxsyntax', tinymce.plugins.abraxSc);
})();

jQuery(document).ready(function($){
	$('#wp-content-media-buttons').append('<a class="thickbox shortcode_opener" style="cursor:pointer;"><img src="'+themesettings.themeurl+'tinymce/img/shortcodes-small.png"/></a>');
	$('.shortcode_opener').last().bind('click', function(){
    	tb_show('Ammon Shortcodes', themesettings.themeurl + 'tinymce/popup.php?width=630&height=800');
	})
})
