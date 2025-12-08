import {player_getResponsiveRatio} from "./_player_helpers";
import {PLAYER_STATES} from "../configs/_playerSettings";

export function player_lifeCycle_setupMobile(selfClass, argsForVideoSetup) {

  const cthis = selfClass.cthis;
  const o = selfClass.argOptions;
  const _c = cthis;

  if (selfClass.dataType === 'selfHosted') {

    argsForVideoSetup.usePlayInline = true;
    argsForVideoSetup.useCrossOrigin = true;
  }
  if (selfClass.dataType === 'vimeo') {
    _c.children().remove();
    const src = selfClass.dataSrc;
    _c.append('<iframe width="100%" height="100%" src="//player.vimeo.com/video/' + src + '" frameborder="0"  allowFullScreen allow="autoplay;fullscreen" style=""></iframe>');

  }


  if (o.responsive_ratio === 'default') {
    player_getResponsiveRatio(selfClass, {
      'called_from': 'init_readyControls .. ios'
    });
    ;
    o.responsive_ratio = 0.5625;
  }

  cthis.addClass(PLAYER_STATES.LOADED);


}