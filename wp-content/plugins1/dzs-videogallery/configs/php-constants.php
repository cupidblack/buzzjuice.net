<?php
const DZSVG_ID                                  = 'dzsvg';
const DZSVG_LOG_ID                              = '[dzsvg]';
const DZSVG_POST_NAME          = 'dzsvideo';
const DZSVG_SHORTCODE_PLAYLIST = 'videogallery';
const DZSVG_POST_NAME__CATEGORY = 'dzsvideo_category';
const DZSVG_POST_NAME__TAGS                     = 'dzsvideo_tags';
const DZSVG_POST_NAME__SLIDERS = 'dzsvg_sliders';

// -- capabilities
const DZSVG_CAPABILITY_ADMIN   = 'manage_options';
const DZSVG_CAP_EDIT_OWN_GALLERIES   = 'video_gallery_edit_own_galleries';
const DZSVG_CAP_EDIT_OTHERS_GALLERIES   = 'video_gallery_edit_others_galleries';



const DZSVG_PAGENAME_LEGACY_SLIDERS = 'dzsvg_menu'; // -- still used for main menu
const DZSVG_PAGENAME_VPCONFIGS = 'dzsvg-vpc';
const DZSVG_PAGENAME_LEGACY_DESIGNER_CENTER     = 'dzsvg-dc';
const DZSVG_PAGENAME_MAINOPTIONS = 'dzsvg-mo';
const DZSVG_PAGENAME_AUTOUPDATER = 'dzsvg-autoupdater';
const DZSVG_PAGENAME_ABOUT = 'dzsvg-about';
const DZSVG_PAGENAME_LAYOUTBUILDER_MENU_ITEMS = 'dzsvg-layoutbuilder-menu-items';
const DZSVG_LAYOUT_BUILDER_AJAX_ACTION = 'dzsvg_layout_builder_save';
const DZSVG_DBKEY_MAINOPTIONS = 'zsvg_options';
const DZSVG_DBKEY_LEGACY_ITEMS = 'zsvg_items';
const DZSVG_DBKEY_LOGS = 'dzsvg_error_logs';
const DZSVG_ASSETS_URL_FONTAWESOME_CDN = 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css';
const DZSVG_PLAYLIST_PAGINATION_QUERY_ARG_SHORT = 'settings_separation_paged';
const DZSVG_PLAYLIST_PAGINATION_QUERY_ARG = 'dzsvg_settings_separation_paged';
const DZSVG_JS_VPCONFIGS_NAME_LEGACY = 'zsvg_vpconfigs';
DEFINE('DZSVG_JS_VPCONFIGS_NAME', 'dzsvg_vpconfigs');
DEFINE('DZSVG_VIEW_PRELOADER_MARKUP', '<div class="dzsvg-preloader"><div class="dzsvg-preloader--spinner"><div class="bc1"></div><div class="bc2"></div><div class="bc3"></div></div></div>');
DEFINE('DZSVG_YOUTUBE_SAMPLE_API_KEY_1', 'AIzaSyD8yUvArWaD1arpEFwNyP3nGbzF3937vXo');
DEFINE('DZSVG_YOUTUBE_SAMPLE_API_KEY_2', 'AIzaSyCtrnD7ll8wyyro5f1LitPggaSKvYFIvU4');
DEFINE('DZSVG_YOUTUBE_SAMPLE_API_KEY_3', 'AIzaSyBIG-cgPLwCDKEaWZL7qXRXPTMMKA7bryQ');
DEFINE('DZSVG_LAYOUTBUILDER_MENU_ITEMS_OPTION_NAME', 'dzsvg_layoutbuilder');
DEFINE('DZSVG_FACEBOOK_LOGIN_REDIRECT_URL', 'admin.php?page=dzsvg-about');
DEFINE('DZSVG_DB_TABLE_NAME_ACTIVITY', 'dzsvg_activity');
const DZSVG_API_QUERY_NO_LEFT_PAGES_KEY = 'none';

const DZSVG_VIEW_ULTIBOX_ITEM_DELEGATED_CLASS = 'ultibox-item-delegated';


const DZSVG_SAMPLE_VIDEO = 'AIzaSyCtrnD7ll8wyyro5f1LitPggaSKvYFIvU4';
const DZSVG_VIEW_ULTIBOX_DZSVG_PLAYER_SKIN = 'skin_reborn';

const DZSVG_YOUTUBE_SAMPLE_API_KEY = array(
  'AIzaSyD8yUvArWaD1arpEFwNyP3nGbzF3937vXo',
  'AIzaSyCtrnD7ll8wyyro5f1LitPggaSKvYFIvU4',
  'AIzaSyBIG-cgPLwCDKEaWZL7qXRXPTMMKA7bryQ',
  'AIzaSyDfDDHWTqJ6iOcASL3wLcpTvPWjmC-NnVk',
  'AIzaSyDUqTcYCpBWsFBLJhPj02P6zs2_atJep5o',
);


const DZSVG_HTML_ALLOWED_TAGS = array(
  'p' => array(
    'class' => array(),
    'style' => array(),
  ),
  'strong' => array(),
  'em' => array(),
  'br' => array(),
  'a' => array(
    'href' => array(),
    'target' => array(),
    'style' => array(),
  ),
);
const DZSVG_PARSER_VIMEO_ALBUM_CACHE_NAME = 'dzsvg_cache_vmalbum';
const DZSVG_PARSER_VIMEO_FOLDER_CACHE_NAME = 'dzsvg_cache_vmfolder';
const DZSVG_PARSER_VIMEO_CHANNEL_CACHE_NAME = 'dzsvg_cache_vmchannel';
const DZSVG_PARSER_VIMEO_USER_CHANNEL_CACHE_NAME = 'dzsvg_cache_vmuserchannel';
DEFINE('DZSVG_PARSER_YOUTUBE_KEYWORDS_CACHE_NAME', 'dzsvg_cache_ytkeywords');
const DZSVG_PARSER_YOUTUBE_PLAYLIST_CACHE_NAME = 'dzsvg_cache_ytplaylist';
DEFINE('DZSVG_PARSER_YOUTUBE_USER_CHANNEL_CACHE_NAME', 'dzsvg_cache_ytuserchannel');

if (defined('DZSVG_DEBUG_LOCAL_SCRIPTS') && DZSVG_DEBUG_LOCAL_SCRIPTS === true) {

  define('DZSVG_SCRIPT_URL', 'http://devsite/html5vg/html5vg_source/videogallery/');
} else {
  define('DZSVG_SCRIPT_URL', DZSVG_URL . "videogallery/");
}

const PLAYLIST_SETTINGS_DEFAULT = array(
  'galleryskin' => 'skin-wave',
  'vpconfig' => 'default',
  'bgcolor' => 'transparent',
  'width' => '',
  'height' => '300',
  'autoplay' => '',
  'autoplaynext' => 'on',
  'autoplay_next' => '',
  'menuposition' => 'bottom',
  'displaymode' => 'normal',
  'feedfrom' => 'normal',
);


const DZSVG_PLAYER_CONFIG_DEFAULT = array(
  'id' => 'default',
  'skin_html5vp' => 'skin_aurora',
  'defaultvolume' => '',
  'youtube_sdquality' => 'small', 'youtube_hdquality' => 'hd720', 'youtube_defaultquality' => 'hd', 'yt_customskin' => 'on', 'vimeo_byline' => '0', 'vimeo_portrait' => '0', 'vimeo_color' => '',
  'enable_info_button' => 'off',
  'settings_video_overlay' => 'off',
);
