<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_GeoDirectory')):
	#[AllowDynamicProperties]
	final class myCRED_GeoDirectory
	{

		// Plugin Domain
		public $domain = 'mycred_geodirectory';
		// Plugin Slug
		public $slug = 'mycred-geodirectory';

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
				add_action('mycred_load_hooks', array($this, 'mycred_load_geodirectory_hook'), 10);
				add_filter('mycred_all_references', array($this, 'register_references'));
			}
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants()
		{

			$this->define('MYCRED_GEODIRECTORY_SLUG', 'mycred-geodirectory');
			$this->define('MYCRED_GEODIRECTORY', __FILE__);
			$this->define('MYCRED_GEODIRECTORY_ROOT_DIR', plugin_dir_path(MYCRED_GEODIRECTORY));
			$this->define('MYCRED_GEODIRECTORY_ASSETS_DIR_URL', plugin_dir_url(MYCRED_GEODIRECTORY) . 'assets/');
			$this->define('MYCRED_GEODIRECTORY_INCLUDES_DIR', MYCRED_GEODIRECTORY_ROOT_DIR . 'includes/');
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
		public function mycred_load_geodirectory_hook()
		{

			$this->file(MYCRED_GEODIRECTORY_INCLUDES_DIR . 'mycred-geodirectory-hook.php');
			$this->file(MYCRED_GEODIRECTORY_INCLUDES_DIR . 'mycred-geodirectory-category-hook.php');
			$this->file(MYCRED_GEODIRECTORY_INCLUDES_DIR . 'mycred-geodirectory-review-hook.php');

		}

		public function load_assets()
		{
			if (is_admin()) {
				wp_enqueue_script(
					'mycred-geodirectory-admin',
					MYCRED_GEODIRECTORY_ASSETS_DIR_URL . 'js/script.js',
					array('jquery'),
					'1.0',
					true
				);
			}
		}

		public function register_hooks($installed)
		{
			$installed['geodirectory_place_added'] = array(
				'title' => __('GeoDirectory Place Added', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for adding a new place via GeoDirectory.', 'mycred-toolkit'),
				'callback' => array('myCRED_GeoDirectory_Hook')
			);

			$installed['geodirectory_category_added'] = array(
				'title' => __('GeoDirectory Category Added', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for adding a new category via GeoDirectory.', 'mycred-toolkit'),
				'callback' => array('myCRED_GeoDirectory_Category_Hook')
			);

			$installed['geodirectory_review_posted'] = array(
				'title' => __('GeoDirectory Review Posted', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for submitting a review on a place via GeoDirectory.', 'mycred-toolkit'),
				'callback' => array('myCRED_GeoDirectory_Review_Hook')
			);

			return $installed;
		}

		public function register_references($list)
		{
			$list['geodirectory_place_added'] = __('GeoDirectory Place Added', 'mycred-toolkit');
			$list['geodirectory_category_added'] = __('GeoDirectory Category Added', 'mycred-toolkit');
			$list['geodirectory_review_posted'] = __('GeoDirectory Review Posted', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_geodirectory()
{
	return myCRED_GeoDirectory::instance();
}
myCRED_geodirectory();

