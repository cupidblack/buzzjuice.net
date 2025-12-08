import {comments_selector_event} from "../components/_comments";


/**
 *
 * @param {DzsAudioPlayer} selfClass
 * @param {jQuery} $
 * @param {jQuery} cthis
 */
export const player_commentsSelectorInit = (selfClass, $, cthis, o) => {

  selfClass.$commentsSelector = $(o.skinwave_comments_mode_outer_selector);

  if (selfClass.$commentsSelector.data) {

    selfClass.$commentsSelector.data('parent', cthis);

    if (window.dzsap_settings.comments_username) {
      selfClass.$commentsSelector.find('.comment_email,*[name=comment_user]').remove();
    }

    selfClass.$commentsSelector.on('click', '.dzstooltip--close,.comments-btn-submit', comments_selector_event);
    selfClass.$commentsSelector.on('focusin', 'input', comments_selector_event);
    selfClass.$commentsSelector.on('focusout', 'input', comments_selector_event);

  } else {
    console.log('%c, data not available .. ', 'color: #990000;', $(o.skinwave_comments_mode_outer_selector));
  }
}
