import {documentReady, $es} from "../shared/esjquery/js/_esjquery";

export function htmlEntities(str) {
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
/**
 *
 * todo: move to separate part
 // -- this is used for video gallery
 // -- @triggered on clicking multisharer button in dzsvg
 */
export function dzsvg_click_open_embed_ultibox() {


  const $socialDzsvgBox = jQuery('.dzs-social-box--main-con').eq(0);


  var _t = jQuery(this);
  var $targetGallery = null
    , $targetPlayer = null
  ;


  $socialDzsvgBox.removeAttr('hidden');
  var _par_par_par = _t.parent().parent().parent();
  var _par_par_par_par = _t.parent().parent().parent().parent();
  var _par_par_par_par_par = _t.parent().parent().parent().parent().parent();
  // -- if this is a button in video player ?
  if (_par_par_par.hasClass('vplayer')) {
    $targetPlayer = _par_par_par;
  } else {
    if (_par_par_par_par.hasClass('vplayer')) {
      $targetPlayer = _par_par_par_par;
    }
  }
  // -- if this is a button in video gallery ?
  if (_par_par_par_par_par.hasClass('videogallery')) {
    $targetGallery = _par_par_par_par_par;
  }

  let stringEmbedCodeForSocialNetworks = '';

  if (window.dzsvg_social_feed_for_social_networks) {
    stringEmbedCodeForSocialNetworks = window.dzsvg_social_feed_for_social_networks;
  }


  stringEmbedCodeForSocialNetworks = stringEmbedCodeForSocialNetworks.replace(/&quot;/g, '\'');
  stringEmbedCodeForSocialNetworks = stringEmbedCodeForSocialNetworks.replace('onclick=""', '');

  $socialDzsvgBox.find('.social-networks-con').html(stringEmbedCodeForSocialNetworks);


  let stringEmbedCodeForShareLink = '';


  if (window.dzsvg_social_feed_for_share_link) {
    stringEmbedCodeForShareLink = window.dzsvg_social_feed_for_share_link;
  }


  if (stringEmbedCodeForShareLink) {

    stringEmbedCodeForShareLink = stringEmbedCodeForShareLink.replace('{{replacewithcurrurl}}', window.location.href);
    $socialDzsvgBox.find('.share-link-con').html(stringEmbedCodeForShareLink);
  }

  let stringEmbedCodeForEmbedLink = '';
  if (window.dzsvg_social_feed_for_embed_link) {
    stringEmbedCodeForEmbedLink = window.dzsvg_social_feed_for_embed_link;
  }


  // -- for single video player
  if (($targetPlayer || $targetGallery) && stringEmbedCodeForEmbedLink) {

    if ($targetPlayer && $targetPlayer.data('embed_code')) {
      jQuery('.embed-link-con').show();
      stringEmbedCodeForEmbedLink = stringEmbedCodeForEmbedLink.replace('{{replacewithembedcode}}', htmlEntities($targetPlayer.data('embed_code')));


      $socialDzsvgBox.find('.embed-link-con').html(stringEmbedCodeForEmbedLink);
    } else {


      if ($targetGallery && stringEmbedCodeForEmbedLink) {

        var iframe_code = '<div style="width: 100%; padding-top: 67.5%; position: relative;"><iframe src=\'' + dzsvg_settings.dzsvg_site_url + '?action=embed_dzsvg&type=' + 'gallery' + '&id=' + $targetGallery.attr('data-dzsvg-gallery-id') + '\'  width="100%" style="position:absolute; top:0; left:0; width: 100%; height: 100%;" scrolling="no" frameborder="0" allowfullscreen allow></iframe>';

        var encodedStr = String(iframe_code).replace(/[\u00A0-\u9999<>\&]/gim, function (i) {
          return '&#' + i.charCodeAt(0) + ';';
        });
        stringEmbedCodeForEmbedLink = stringEmbedCodeForEmbedLink.replace('{{replacewithembedcode}}', encodedStr);

        // -- inspired from php @generate_embed_code
        $socialDzsvgBox.find('.embed-link-con').html(stringEmbedCodeForEmbedLink);


        setTimeout(function () {
          $socialDzsvgBox.addClass('loaded-item');
        }, 200);
      } else {

        // -- hide embed link if we do not have embed_code
        jQuery('.embed-link-con').hide();
      }
    }
  }

  setTimeout(function () {

    $socialDzsvgBox.addClass('loading-item');
  }, 100);

  setTimeout(function () {
    $socialDzsvgBox.addClass('loaded-item');
  }, 200);
}

const dzsvg_multiSharer_init = () => {

  $es(document).on('click', '.dzs-social-box--invoke-btn .handle', dzsvg_click_open_embed_ultibox)
}


documentReady(()=>{
  dzsvg_multiSharer_init();
})