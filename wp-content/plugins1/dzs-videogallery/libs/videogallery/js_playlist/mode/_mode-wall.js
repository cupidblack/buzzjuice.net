import {getDataOrAttr,} from "../../js_dzsvg/_dzsvg_helpers";
import {PLAYLIST_MODE_WALL__ITEM_CLASS} from "../../configs/Constants";


export function dzsvg_mode_wall_init(selfClass) {
  const o = selfClass.initOptions;


  o.menu_position = 'all';

  if (o.navigation_gridClassItemsContainer === 'default') {
    o.navigation_gridClassItemsContainer = 'dzs-layout--3-cols';
  }
}

export function dzsvg_mode_wall_reinitWallStructure(selfClass) {
  // -- wall

  const o = selfClass.initOptions;

  selfClass.$navigationItemsContainer.children().each(function () {
    // -- each item
    const $t = jQuery(this);


    if (!$t.hasClass('wall-treated')) {
      $t.addClass(PLAYLIST_MODE_WALL__ITEM_CLASS).addClass('  dzs-layout-item ultibox-item-delegated');
      $t.css({});
      $t.attr('data-suggested-width', o.ultibox_suggestedWidth);
      $t.attr('data-suggested-height', o.ultibox_suggestedHeight);
      $t.attr('data-biggallery', 'vgwall');

      if ($t.attr('data-previewimg')) {
        $t.attr('data-thumb-for-gallery', $t.attr('data-previewimg'));
      } else {
        if ($t.data('thumbForGallery')) {
          $t.attr('data-thumb-for-gallery', $t.data('thumbForGallery'));
        }
      }


      let uriThumb = $t.attr('data-thumb');
      let thumb_imgblock = null;

      if ($t.find('.layout-builder--item--type-thumbnail').length) {
        thumb_imgblock = $t.find('.layout-builder--item--type-thumbnail');
      }


      if (!uriThumb) {

        if (thumb_imgblock) {
          if (thumb_imgblock.attr('data-imgsrc')) {
          } else {
            if (thumb_imgblock.attr('src')) {
              uriThumb = $t.find('.imgblock').attr('src');
            } else {
              uriThumb = thumb_imgblock.css('background-image');
            }
          }
        }

      }


      if (uriThumb) {

        uriThumb = uriThumb.replace('url(', '');
        uriThumb = uriThumb.replace(')', '');
        uriThumb = uriThumb.replace(/"/g, '');
        $t.attr('data-thumb-for-gallery', uriThumb);
      }
      // -- setup wall
      if (!$t.attr('data-source')) {
        $t.attr('data-source', getDataOrAttr($t, 'data-sourcevp'));
      }
      $t.attr('data-type', 'video');

      if ($t.data('dataType')) {
        $t.attr('data-video-type', $t.data('dataType'));
      }

      $t.addClass('wall-treated')
    }


  });


  setTimeout(function () {

    setTimeout(selfClass.handleResize, 1000);
    selfClass.isGalleryLoaded = true;
  }, 1500);

}