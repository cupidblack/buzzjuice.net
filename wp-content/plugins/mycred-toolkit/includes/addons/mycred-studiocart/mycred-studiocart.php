<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_StudioCart')):
	#[AllowDynamicProperties]
	final class myCRED_StudioCart
	{

		// Plugin Domain
		public $domain = 'mycred_studiocart';
		// Plugin Slug
		public $slug = 'mycred-studiocart';

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
				add_action('mycred_load_hooks', array($this, 'mycred_load_studiocart_hook'), 10);
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

			$this->define('MYCRED_STUDIOCART_SLUG', 'mycred-studiocart');
			$this->define('MYCRED_STUDIOCART', __FILE__);
			$this->define('MYCRED_STUDIOCART_ROOT_DIR', plugin_dir_path(MYCRED_STUDIOCART));
			$this->define('MYCRED_STUDIOCART_ASSETS_DIR_URL', plugin_dir_url(MYCRED_STUDIOCART) . 'assets/');
			$this->define('MYCRED_STUDIOCART_INCLUDES_DIR', MYCRED_STUDIOCART_ROOT_DIR . 'includes/');
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
		public function mycred_load_studiocart_hook()
		{

			$this->file(MYCRED_STUDIOCART_INCLUDES_DIR . 'studiocart-hook.php');
			$this->file(MYCRED_STUDIOCART_INCLUDES_DIR . 'studiocart-order-completed-hook.php');
			$this->file(MYCRED_STUDIOCART_INCLUDES_DIR . 'studiocart-refund-hook.php');
			$this->file(MYCRED_STUDIOCART_INCLUDES_DIR . 'studiocart-subscription-canceled-hook.php');

		}

		public function load_assets()
		{
			if (is_admin()) {
				wp_enqueue_script(
					'mycred-studiocart-admin',
					MYCRED_STUDIOCART_ASSETS_DIR_URL . 'js/script.js',
					array('jquery'),
					'1.0',
					true
				);
			}
		}

		public function register_hooks($installed)
		{
			$installed['studiocart_purchase'] = array(
				'title' => __('StudioCart Purchase', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for purchases made via StudioCart.', 'mycred-toolkit'),
				'callback' => array('myCRED_StudioCart_Hook')
			);

			$installed['studiocart_order_completion'] = array(
				'title' => __('StudioCart Order Completed', 'mycred-toolkit'),
				'description' => __('Awards %_plural% when a StudioCart order is completed.', 'mycred-toolkit'),
				'callback' => array('myCRED_StudioCart_Order_Completion_Hook')
			);

			$installed['studiocart_refund'] = array(
				'title' => __('StudioCart Refund', 'mycred-toolkit'),
				'description' => __('Awards or deducts %_plural% when a StudioCart order is refunded.', 'mycred-toolkit'),
				'callback' => array('myCRED_StudioCart_Refund_Hook')
			);

			$installed['studiocart_subscription_canceled'] = array(
				'title' => __('StudioCart Subscription Canceled', 'mycred-toolkit'),
				'description' => __('Awards or deducts %_plural% when a StudioCart subscription is canceled.', 'mycred-toolkit'),
				'callback' => array('myCRED_StudioCart_Subscription_Canceled_Hook')
			);

			return $installed;
		}

		public function register_references($list)
		{
			$list['studiocart_purchase'] = __('Purchasing a product via StudioCart', 'mycred-toolkit');
			$list['studiocart_order_completion'] = __('Completing an order via StudioCart', 'mycred-toolkit');
			$list['studiocart_refund'] = __('StudioCart Refund', 'mycred-toolkit');
			$list['studiocart_subscription_canceled'] = __('StudioCart Subscription Canceled', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_studiocart()
{
	return myCRED_StudioCart::instance();
}
myCRED_studiocart();
