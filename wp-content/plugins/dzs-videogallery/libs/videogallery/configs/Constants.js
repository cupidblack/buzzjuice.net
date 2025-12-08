export const ConstantsDzsvg = {
  THREEJS_LIB_URL: 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r73/three.min.js',
  THREEJS_LIB_ORBIT_URL: 'https://s3-us-west-2.amazonaws.com/s.cdpn.io/211120/orbitControls.js',
  YOUTUBE_IFRAME_API: 'https://www.youtube.com/iframe_api',
  VIMEO_IFRAME_API: 'https://player.vimeo.com/api/player.js',
  DEBUG_STYLING: 'background-color: #4422aa;',
  DEBUG_STYLING_2: 'color: #ffdada; background-color: #da3333;',
  ANIMATIONS_DURATION: 303,
  DELAY_MINUSCULE: 3,
}

export const VIEW_LAYOUT_BUILDER_FEED_CLASS = 'feed-layout-builder--menu-items';
export const PLAYER_REGEX_SUBTITLE = /([0-9](?:[0-9]|:|,| )*)[–|-]*(?:(?:\&gt;)|>) *([0-9](?:[0-9]|:|,| )*)[\n|\r]([\s\S]*?)[\n|\r]/g

/**
 * used if we don't have VIEW_LAYOUT_BUILDER_FEED_CLASS
 * @type {string}
 */
export const DEFAULT_MENU_ITEM_STRUCTURE = `<div class="layout-builder--structure layout-builder--menu-items--layout-default layout-builder--main-con " style="display: flex; gap: 10px; padding: 10px; align-items: center;">
  <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container" style="flex: 0 0 60px;">
  <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container" style="padding-top: 100%; position:relative;">
    <div class="layout-builder--item layout-builder--item--2312321 layout-builder--item--type-thumbnail navigation-type-image divimage" style="position:absolute;top:0; left:0; width: 100%; height: 100%; background-image: url({{layout-builder.replace-thumbnail-url}})"></div>
    </div>
  </div>
  <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container" style="flex: 100; white-space: normal; min-width: 150px;">
    <div class="layout-builder--item layout-builder--item--3321321 layout-builder--item--type-title" style="font-weight: bold; margin-bottom: 5px; padding-right: 10px;;">{{layout-builder.replace-title}}</div>
    <div class="layout-builder--item layout-builder--item--21312321 layout-builder--item--type-menu-description" style="font-weight: 400; line-height: 1.5; padding-right: 10px;">{{layout-builder.replace-menu-description}}</div>
  </div>
</div>`;
export const PLAYER_DEFAULT_RESPONSIVE_RATIO = 0.5625;
export const PLAYER_DEFAULT_TIMEOUT = 304;
export const PLAYLIST_HEIGHT_IS_WINDOW_MAX_OFFSET = 310;
export const PLAYLIST_DEFAULT_TIMEOUT = 305;
export const PLAYLIST_SCROLL_TOP_OFFSET = 120;
export const PLAYLIST_MODE_WALL__ITEM_CLASS = 'vgwall-item';
export const PLAYLIST_PAGINATION_QUERY_ARG = 'dzsvgpage';
export const PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON = 'dzsvg-btn-pagination--load-more';
export const PLAYLIST_VIEW_FULLSCREEN_CLASS = 'is_fullscreen';