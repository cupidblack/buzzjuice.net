<?php
/*
  Plugin Name: DZS Video Gallery
  Plugin URI: https://digitalzoomstudio.net/
  Description: Creates and manages cool video galleries. Has a admin panel and tons of options and skins.
  Version: 12.29
  Author: Digital Zoom Studio
  Author URI: https://digitalzoomstudio.net/
 */


const DZSVG_VERSION = '12.29';

define('DZSVG_PATH', dirname(__FILE__) . '/');
if (function_exists('plugin_dir_url')) {
  define('DZSVG_URL', plugin_dir_url(__FILE__));
}
include_once(DZSVG_PATH . 'configs/php-constants.php');


include_once(DZSVG_PATH . 'dzs_functions.php');
if (!class_exists('DZSVideoGallery')) {
  include_once(DZSVG_PATH . 'class-dzsvg.php');
}

$dzsvg = new DZSVideoGallery();


include_once(DZSVG_PATH.'previewdata/preview.php');


if (defined('DZSVG_PREVIEW') && DZSVG_PREVIEW == "YES") {
  add_action('wp_login', 'dzsvg_handle_activated_plugin', 10, 2);
}


add_action('activated_plugin', 'dzsvg_handle_activated_plugin');
register_activation_hook(__FILE__, array($dzsvg, 'handle_plugin_activate'));
register_deactivation_hook(__FILE__, array($dzsvg, 'handle_plugin_deactivate'));



