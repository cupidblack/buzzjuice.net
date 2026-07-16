<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_Favorites')):
	#[AllowDynamicProperties]
	final class myCRED_Favorites
	{

		// Plugin Domain
		public $domain = 'mycred_favorites';
		// Plugin Slug
		public $slug = 'mycred-favorites';

		// Instance
		protected static $_instance = NULL;

		/**
		 * Setup Instance
		 * @version 1.0
		 * 
		 */
		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Define
		 * @version 1.0
		 * 
		 */
		private function define($name, $value)
		{
			if (!defined($name))
				define($name, $value);
		}

		/**
		 * Require File
		 * @version 1.0
		 * 
		 */
		public function file($required_file)
		{
			if (file_exists($required_file))
				require_once $required_file;
		}

		/**
		 * Construct
		 * @version 1.0
		 * 
		 */
		public function __construct()
		{
			$this->define_constants();
			$this->init();
			$this->plugin = plugin_basename(__FILE__);

		}

		/**
		 * Initialize
		 * @version 1.0
		 * 
		 */
		private function init()
		{
			$this->file(ABSPATH . 'wp-admin/includes/plugin.php');
			if (is_plugin_active('mycred/mycred.php')) {
				$this->includes();
				add_action('admin_enqueue_scripts', array($this, 'load_assets'));
				add_filter('mycred_setup_hooks', array($this, 'register_hooks'), 10, 2);
				add_action('mycred_load_hooks', array($this, 'mycred_load_favorites_hook'), 10);
				add_filter('mycred_all_references', array($this, 'register_refrences'));
			}
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants()
		{

			$this->define('MYCRED_FAVORITES_SLUG', 'mycred-favorites');
			$this->define('MYCRED_FAVORITES', __FILE__);
			$this->define('MYCRED_FAVORITES_ROOT_DIR', plugin_dir_path(MYCRED_FAVORITES));
			$this->define('MYCRED_FAVORITES_ASSETS_DIR_URL', plugin_dir_url(MYCRED_FAVORITES) . 'assets/');
			$this->define('MYCRED_FAVORITES_INCLUDES_DIR', MYCRED_FAVORITES_ROOT_DIR . 'includes/');
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes()
		{
			// No helper functions file needed yet
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_favorites_hook()
		{

			$this->file(MYCRED_FAVORITES_INCLUDES_DIR . 'mycred-favorites-hook.php');
			$this->file(MYCRED_FAVORITES_INCLUDES_DIR . 'mycred-favorites-author-hook.php');
			$this->file(MYCRED_FAVORITES_INCLUDES_DIR . 'mycred-favorites-unfavorite-hook.php');
			$this->file(MYCRED_FAVORITES_INCLUDES_DIR . 'mycred-favorites-author-unfavorite-hook.php');

		}

		public function load_assets()
		{
			if (is_admin()) {
				wp_enqueue_script(
					'mycred-favorites-admin',
					MYCRED_FAVORITES_ASSETS_DIR_URL . 'js/script.js',
					array('jquery'),
					'1.0',
					true
				);
			}
		}

		public function register_hooks($installed)
		{
			$installed['favorites_favorite'] = array(
				'title' => __('Favorites - User Favorites', 'mycred-toolkit'),
				'description' => __('Awards %_plural% to users for favoriting posts via the Favorites plugin.', 'mycred-toolkit'),
				'callback' => array('myCRED_Favorites_Hook')
			);

			$installed['favorites_author'] = array(
				'title' => __('Favorites - Author Receives', 'mycred-toolkit'),
				'description' => __('Awards %_plural% to post authors when their posts receive favorites.', 'mycred-toolkit'),
				'callback' => array('myCRED_Favorites_Author_Hook')
			);

			$installed['favorites_unfavorite'] = array(
				'title' => __('Favorites - User Unfavorites', 'mycred-toolkit'),
				'description' => __('Awards or deducts %_plural% when users unfavorite posts.', 'mycred-toolkit'),
				'callback' => array('myCRED_Favorites_Unfavorite_Hook')
			);

			$installed['favorites_author_unfavorite'] = array(
				'title' => __('Favorites - Author Receives Unfavorite', 'mycred-toolkit'),
				'description' => __('Awards or deducts %_plural% to post authors when their posts are unfavorited.', 'mycred-toolkit'),
				'callback' => array('myCRED_Favorites_Author_Unfavorite_Hook')
			);

			return $installed;
		}

		public function register_refrences($list)
		{
			$list['favorites_favorite'] = __('Favoriting a post via Favorites plugin', 'mycred-toolkit');
			$list['favorites_author'] = __('Author receives a favorite on post', 'mycred-toolkit');
			$list['favorites_unfavorite'] = __('Unfavoriting a post via Favorites plugin', 'mycred-toolkit');
			$list['favorites_author_unfavorite'] = __('Author receives an unfavorite on post', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_favorites()
{
	return myCRED_Favorites::instance();
}
myCRED_favorites();
