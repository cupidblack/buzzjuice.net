"use strict";
jQuery(document).ready(function ($) {



  $(document).on('click', '.edit-tag-actions', () => {

    parent.dzsvg_gutenberg_update_current_playlist({
      'label':$('input[name="name"]').eq(0).val(),
      'value':$('input[name="slug"]').eq(0).val(),
      'term_id':$('input[name="tag_ID"]').eq(0).val(),
    })
  })
})