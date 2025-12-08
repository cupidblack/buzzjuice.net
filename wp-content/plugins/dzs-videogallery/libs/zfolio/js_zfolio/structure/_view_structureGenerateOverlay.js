export const structureGenerateOverlay = (isInsideAnchor, overlay_extra_class, dataLink, zfolioSkin, $feedItem) => {

  let isLink = false;
  let stringOverlayStruct = '<div class="the-overlay' + overlay_extra_class + '" ';


  if (dataLink) {

    if (zfolioSkin === 'skin-silver' || zfolioSkin === 'skin-qucreative') {


      if (isInsideAnchor) {

        stringOverlayStruct = '<div href="' + dataLink + '" class="the-overlay ' + overlay_extra_class + '" ';
      } else {

        stringOverlayStruct = '<a href="' + dataLink + '" class="the-overlay ' + overlay_extra_class + '" ';
      }


      isLink = true;
    }


    if (zfolioSkin === 'skin-gazelia') {

      isLink = false;
      stringOverlayStruct = '<div class="the-overlay" ';
      stringOverlayStruct += '>';
      if (isInsideAnchor) {
        stringOverlayStruct += '<div href="' + dataLink + '" class="the-overlay-anchor ' + overlay_extra_class + '"';

      } else {

        stringOverlayStruct += '<a href="' + dataLink + '" class="the-overlay-anchor ' + overlay_extra_class + '"';
      }

      if ($feedItem.attr('data-overlay_anchor_extra_attr')) {
        stringOverlayStruct += $feedItem.attr('data-overlay_anchor_extra_attr');
      }


      stringOverlayStruct += '>';


      if ($feedItem.children('.overlay-anchor-extra-html').length > 0) {
        stringOverlayStruct += $feedItem.children('.overlay-anchor-extra-html').eq(0).html();
      }

      if (isInsideAnchor) {

        stringOverlayStruct += '</div';
      } else {

        stringOverlayStruct += '</a';
      }
    }


    if (zfolioSkin === 'skin-lazarus') {

      isLink = false;
      stringOverlayStruct = '<div class="the-overlay" ';
      stringOverlayStruct += '>';


      if (isInsideAnchor) {

        stringOverlayStruct += '<div href="' + dataLink + '" class="the-overlay-anchor ' + overlay_extra_class + '"';

      } else {

        stringOverlayStruct += '<a href="' + dataLink + '" class="the-overlay-anchor ' + overlay_extra_class + '"';

      }

      if ($feedItem.attr('data-overlay_anchor_extra_attr')) {
        stringOverlayStruct += $feedItem.attr('data-overlay_anchor_extra_attr');
      }


      stringOverlayStruct += '>';


      if ($feedItem.children('.overlay-anchor-extra-html').length > 0) {
        stringOverlayStruct += $feedItem.children('.overlay-anchor-extra-html').eq(0).html();
      }


      if (isInsideAnchor) {

        stringOverlayStruct += '</div';
      } else {

        stringOverlayStruct += '</a';
      }

    }
  }

  if ($feedItem.attr('data-overlay_extra_attr')) {
    stringOverlayStruct += $feedItem.attr('data-overlay_extra_attr');
  }

  stringOverlayStruct += '>';


  if (isLink) {
    stringOverlayStruct += '</a>';
  } else {

    stringOverlayStruct += '</div>';
  }

  return stringOverlayStruct;
}