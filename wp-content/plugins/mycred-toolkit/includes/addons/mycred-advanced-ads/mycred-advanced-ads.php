<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_Advanced_Ads')):
	#[AllowDynamicProperties]
	final class myCRED_Advanced_Ads
	{

		// Plugin Domain
		public $domain = 'mycred_advanced_ads';
		// Plugin Slug
		public $slug = 'mycred-advanced-ads';

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
				add_action('mycred_load_hooks', array($this, 'mycred_load_advanced_ads_hook'), 10);
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

			$this->define('MYCRED_ADVANCED_ADS_SLUG', 'mycred-advanced-ads');
			$this->define('MYCRED_ADVANCED_ADS', __FILE__);
			$this->define('MYCRED_ADVANCED_ADS_ROOT_DIR', plugin_dir_path(MYCRED_ADVANCED_ADS));
			$this->define('MYCRED_ADVANCED_ADS_INCLUDES_DIR', MYCRED_ADVANCED_ADS_ROOT_DIR . 'includes/');
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
		public function mycred_load_advanced_ads_hook()
		{

			$this->file(MYCRED_ADVANCED_ADS_INCLUDES_DIR . 'mycred-advanced-ads-hook.php');
			$this->file(MYCRED_ADVANCED_ADS_INCLUDES_DIR . 'mycred-advanced-ads-unpublish-hook.php');
			$this->file(MYCRED_ADVANCED_ADS_INCLUDES_DIR . 'mycred-advanced-ads-expired-hook.php');

		}

		public function register_hooks($installed)
		{
			$installed['advanced_ads_publication'] = array(
				'title' => __('Advanced Ads Publication', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for publishing ads via Advanced Ads.', 'mycred-toolkit'),
				'callback' => array('myCRED_Advanced_Ads_Hook')
			);

			$installed['advanced_ads_unpublication'] = array(
				'title' => __('Advanced Ads Unpublication', 'mycred-toolkit'),
				'description' => __('Awards or deducts %_plural% when an ad is unpublished in Advanced Ads.', 'mycred-toolkit'),
				'callback' => array('myCRED_Advanced_Ads_Unpublish_Hook')
			);

			$installed['advanced_ads_expiration'] = array(
				'title' => __('Advanced Ads Expiration', 'mycred-toolkit'),
				'description' => __('Awards or deducts %_plural% when an ad expires in Advanced Ads.', 'mycred-toolkit'),
				'callback' => array('myCRED_Advanced_Ads_Expired_Hook')
			);

			return $installed;
		}

		public function register_references($list)
		{
			$list['advanced_ads_publication'] = __('Advanced Ads Publication', 'mycred-toolkit');
			$list['advanced_ads_unpublication'] = __('Advanced Ads Unpublication', 'mycred-toolkit');
			$list['advanced_ads_expiration'] = __('Advanced Ads Expiration', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_advanced_ads()
{
	return myCRED_Advanced_Ads::instance();
}
myCRED_advanced_ads();
