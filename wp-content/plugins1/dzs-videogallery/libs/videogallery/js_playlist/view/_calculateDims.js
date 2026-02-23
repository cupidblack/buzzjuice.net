/**
 *
 * @param {DzsVideoGallery} selfClass
 * @param {object} margs
 */
export function playlist_calculateDims_totals(selfClass, margs) {

  const o = selfClass.initOptions;
  var {cgallery} = selfClass;

  selfClass.totalWidth = selfClass.cgallery.outerWidth();
  selfClass.totalHeight = selfClass.cgallery.height();

  if (selfClass.cgallery.height() === 0) {
    if (o.forceVideoHeight) {
      if (selfClass.nav_position === 'top' || selfClass.nav_position === 'bottom') {
        selfClass.totalHeight = o.forceVideoHeight + o.design_menuitem_height;
      } else {
        selfClass.totalHeight = o.forceVideoHeight;
      }
    }
  }



  if (margs.called_from === 'recheck_sizes') {

    if (Math.abs(selfClass.last_totalWidth - selfClass.totalWidth) < 4 && Math.abs(selfClass.last_totalHeight - selfClass.totalHeight) < 4) {


      return false;
    }

  }


  selfClass.last_totalWidth = selfClass.totalWidth;
  selfClass.last_totalHeight = selfClass.totalHeight;






  if (selfClass.totalWidth <= 720) {
    cgallery.addClass('under-720');
  } else {
    cgallery.removeClass('under-720');
  }


  if (selfClass.totalWidth <= 600) {
    cgallery.addClass('under-600');
  } else {
    cgallery.removeClass('under-600');
  }


  if (String(cgallery.get(0).style.height).indexOf('%') > -1) {

    selfClass.totalHeight = cgallery.height();
  } else {

    if (cgallery.data('init-height')) {

      selfClass.totalHeight = cgallery.data('init-height');
    } else {

      selfClass.totalHeight = cgallery.height();

      setTimeout(function () {
      })

    }
  }

}
export function playlist_calculateDims(pargs) {

}