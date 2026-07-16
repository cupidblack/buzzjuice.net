<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_WP_Ulike')):
	#[AllowDynamicProperties]
	final class myCRED_WP_Ulike
	{

		// Plugin Domain
		public $domain = 'mycred_wp_ulike';
		// Plugin Slug
		public $slug = 'mycred-wp-ulike';

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
				add_filter('mycred_setup_hooks', array($this, 'register_hooks'), 10, 2);
				add_action('mycred_load_hooks', array($this, 'mycred_load_wp_ulike_hook'), 10);
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

			$this->define('MYCRED_WP_ULIKE_SLUG', 'mycred-wp-ulike');
			$this->define('MYCRED_WP_ULIKE', __FILE__);
			$this->define('MYCRED_WP_ULIKE_ROOT_DIR', plugin_dir_path(MYCRED_WP_ULIKE));
			$this->define('MYCRED_WP_ULIKE_INCLUDES_DIR', MYCRED_WP_ULIKE_ROOT_DIR . 'includes/');
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
		public function mycred_load_wp_ulike_hook()
		{

			$this->file(MYCRED_WP_ULIKE_INCLUDES_DIR . 'mycred-wp-ulike-hook.php');
			$this->file(MYCRED_WP_ULIKE_INCLUDES_DIR . 'mycred-wp-ulike-post-hook.php');

		}

	public function register_hooks($installed)
	{
		$installed['wp_ulike_like'] = array(
			'title' => __('WP Ulike - Like Anything', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for liking content via WP Ulike plugin.', 'mycred-toolkit'),
			'callback' => array('myCRED_WP_Ulike_Hook')
		);

		$installed['wp_ulike_post_like'] = array(
			'title' => __('WP Ulike - Like Post', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for liking posts via WP Ulike plugin. Authors do not receive points.', 'mycred-toolkit'),
			'callback' => array('myCRED_WP_Ulike_Post_Hook')
		);

		return $installed;
	}

	public function register_references($list)
	{
		$list['wp_ulike_like'] = __('WP Ulike - Like Content', 'mycred-toolkit');
		$list['wp_ulike_post_like'] = __('WP Ulike - Like Post', 'mycred-toolkit');
		return $list;
	}

	}
endif;

function myCRED_wp_ulike()
{
	return myCRED_WP_Ulike::instance();
}
myCRED_wp_ulike();

