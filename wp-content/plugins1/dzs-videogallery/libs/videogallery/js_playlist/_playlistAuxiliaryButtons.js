import {dzsvg_check_multisharer} from "../js_dzsvg/_dzsvg_helpers";
import {svg_embed, svgShareIcon} from "../js_dzsvg/_dzsvg_svgs";
import {ConstantsDzsvg} from "../configs/Constants";


/**
 *
 * @param {DzsVideoGallery} selfClass
 * @param {object} o
 * @param {boolean} isMergeSocialIconsIntoOne
 */
export function dzsvg_playlist_setupEmbedAndShareButtons(selfClass, o, isMergeSocialIconsIntoOne){


  // -- going to merge social code into one
  if (isMergeSocialIconsIntoOne) {
    if (o.embedCode !== '' || selfClass.feed_socialCode) {
      dzsvg_check_multisharer();
      if (o.settings_mode === 'wall') {

        if (selfClass.$sliderMain.find('.gallery-buttons').length === 0) {
          selfClass.$galleryButtons = selfClass.cgallery.find('.gallery-buttons');

        }
        setTimeout(function () {
          selfClass.$sliderMain.before(selfClass.$galleryButtons);
        }, 500);
      }


      selfClass.$galleryButtons.append('<div class="dzs-social-box--invoke-btn embed-button open-in-embed-ultibox"><div class="handle">' + svg_embed + '</div><div hidden aria-hidden="true" class="feed-dzsvg feed-dzsvg--embedcode">' + o.embedCode + '</div></div>');


    }


  } else {

    if (o.embedCode !== '') {
      selfClass.$galleryButtons.append('<div class="embed-button"><div class="handle">' + svg_embed + '</div><div class="contentbox" style="display:none;"><textarea class="thetext">' + o.embedCode + '</textarea></div></div>');
      selfClass.$galleryButtons.find('.embed-button .handle').on('click', click_embedHandle(selfClass))
      selfClass.$galleryButtons.find('.embed-button .contentbox').css({
        'right': 50
      })
    }
    if (selfClass.feed_socialCode) {
      selfClass.$galleryButtons.append('<div class="share-button"><div class="handle">' + svgShareIcon + '</div><div class="contentbox" style="display:none;"><div class="thetext">' + selfClass.feed_socialCode + '</div></div></div>');
      selfClass.$galleryButtons.find('.share-button .handle').on('click', click_sharehandle(selfClass))
      selfClass.$galleryButtons.find('.share-button .contentbox').css({
        'right': 50
      })
    }
  }
}


/**
 *
 * @param {DzsVideoGallery} selfClass
 * @returns {(function(): void)|*}
 */
function click_embedHandle(selfClass) {
  return function(){
    if (selfClass.isEmbedOpened === false) {
      selfClass.$galleryButtons.find('.embed-button .contentbox').css({
        'right': 60
      }, {queue: false, duration: ConstantsDzsvg.ANIMATIONS_DURATION});

      selfClass.$galleryButtons.find('.embed-button .contentbox').addClass('is-visible');
      selfClass.isEmbedOpened = true;
    } else {
      selfClass.$galleryButtons.find('.embed-button .contentbox').css({
        'right': 50
      }, {queue: false, duration: ConstantsDzsvg.ANIMATIONS_DURATION});

      selfClass.$galleryButtons.find('.embed-button .contentbox').removeClass('is-visible');
      selfClass.isEmbedOpened = false;
    }
  }
}


/**
 *
 * @param {DzsVideoGallery} selfClass
 * @returns {(function(): void)|*}
 */
function click_sharehandle(selfClass) {
  return function() {
    if (selfClass.isShareOpened === false) {
      selfClass.$galleryButtons.find('.share-button .contentbox').css({
        'right': 60
      });

      selfClass.$galleryButtons.find('.share-button .contentbox').addClass('is-visible');
      selfClass.isShareOpened = true;
    } else {
      selfClass.$galleryButtons.find('.share-button .contentbox').css({
        'right': 50
      });

      selfClass.$galleryButtons.find('.share-button .contentbox').removeClass('is-visible');
      selfClass.isShareOpened = false;
    }
  }
}