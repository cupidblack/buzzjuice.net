<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_Paid_Membership_Subscriptions')):
	#[AllowDynamicProperties]
	final class myCRED_Paid_Membership_Subscriptions
	{

		// Plugin Domain
		public $domain = 'mycred_pms';
		// Plugin Slug
		public $slug = 'mycred-paid-membership-subscriptions';

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
			if (is_plugin_active('mycred/mycred.php') && is_plugin_active('paid-member-subscriptions/index.php')) {
				$this->includes();
				add_action('admin_enqueue_scripts', array($this, 'load_assets'));
				add_filter('mycred_setup_hooks', array($this, 'register_hooks'), 10, 2);
				add_action('mycred_load_hooks', array($this, 'mycred_load_pms_hook'), 10);
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
			$this->define('MYCRED_PMS_SLUG', 'mycred-paid-membership-subscriptions');
			$this->define('MYCRED_PMS', __FILE__);
			$this->define('MYCRED_PMS_ROOT_DIR', plugin_dir_path(MYCRED_PMS));
			$this->define('MYCRED_PMS_ASSETS_DIR_URL', plugin_dir_url(MYCRED_PMS) . 'assets/');
			$this->define('MYCRED_PMS_INCLUDES_DIR', MYCRED_PMS_ROOT_DIR . 'includes/');
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
		public function mycred_load_pms_hook()
		{
			$this->file(MYCRED_PMS_INCLUDES_DIR . 'mycred-paid-membership-subscriptions-purchase-hook.php');
			$this->file(MYCRED_PMS_INCLUDES_DIR . 'mycred-paid-membership-subscriptions-payment-hook.php');
			$this->file(MYCRED_PMS_INCLUDES_DIR . 'mycred-paid-membership-subscriptions-renewal-hook.php');
			$this->file(MYCRED_PMS_INCLUDES_DIR . 'mycred-paid-membership-subscriptions-change-hook.php');
			$this->file(MYCRED_PMS_INCLUDES_DIR . 'mycred-paid-membership-subscriptions-cancel-hook.php');
			$this->file(MYCRED_PMS_INCLUDES_DIR . 'mycred-paid-membership-subscriptions-abandon-hook.php');
		}

		public function load_assets()
		{
			if (is_admin()) {
				wp_enqueue_script(
					'mycred-pms-admin',
					MYCRED_PMS_ASSETS_DIR_URL . 'js/script.js',
					array('jquery'),
					'1.0',
					true
				);
			}
		}

		public function register_hooks($installed)
		{
			$installed['pms_purchase_subscription'] = array(
				'title' => __('Paid Membership Subscriptions - Purchase Subscription', 'myCred_pms'),
				'description' => __('Awards %_plural% when a user purchases a subscription.', 'myCred_pms'),
				'callback' => array('MyCred_PMS_Purchase_Subscription_Hook')
			);
			$installed['pms_payment_subscription'] = array(
				'title' => __('Paid Membership Subscriptions - Payment on Subscription', 'myCred_pms'),
				'description' => __('Awards %_plural% when a user pays for a subscription.', 'myCred_pms'),
				'callback' => array('MyCred_PMS_Payment_Subscription_Hook')
			);
			$installed['pms_renewal_subscription'] = array(
				'title' => __('Paid Membership Subscriptions - Renew Subscription', 'myCred_pms'),
				'description' => __('Awards %_plural% when a user renews a subscription.', 'myCred_pms'),
				'callback' => array('MyCred_PMS_Renewal_Subscription_Hook')
			);
			$installed['pms_change_subscription'] = array(
				'title' => __('Paid Membership Subscriptions - Change Subscription', 'myCred_pms'),
				'description' => __('Awards %_plural% when a user upgrades or downgrades a subscription.', 'myCred_pms'),
				'callback' => array('MyCred_PMS_Change_Subscription_Hook')
			);
			$installed['pms_cancel_subscription'] = array(
				'title' => __('Paid Membership Subscriptions - Cancel Subscription', 'myCred_pms'),
				'description' => __('Awards %_plural% when a user cancels a subscription.', 'myCred_pms'),
				'callback' => array('MyCred_PMS_Cancel_Subscription_Hook')
			);
			$installed['pms_abandon_subscription'] = array(
				'title' => __('Paid Membership Subscriptions - Abandon Subscription', 'myCred_pms'),
				'description' => __('Awards %_plural% when a user abandons a subscription.', 'myCred_pms'),
				'callback' => array('MyCred_PMS_Abandon_Subscription_Hook')
			);
			return $installed;
		}

		public function register_references($list)
		{
			$list['pms_purchase_subscription'] = __('Purchasing a subscription', 'myCred_pms');
			$list['pms_payment_subscription'] = __('Payment on subscription', 'myCred_pms');
			$list['pms_renewal_subscription'] = __('Renewing a subscription', 'myCred_pms');
			$list['pms_change_subscription'] = __('Changing a subscription', 'myCred_pms');
			$list['pms_cancel_subscription'] = __('Cancelling a subscription', 'myCred_pms');
			$list['pms_abandon_subscription'] = __('Abandoning a subscription', 'myCred_pms');
			return $list;
		}

	}
endif;

function myCRED_paid_membership_subscriptions()
{
	return myCRED_Paid_Membership_Subscriptions::instance();
}
myCRED_paid_membership_subscriptions();
