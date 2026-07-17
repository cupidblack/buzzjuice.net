<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_Fluent_Support')):
	#[AllowDynamicProperties]
	final class myCRED_Fluent_Support
	{

		// Plugin Domain
		public $domain = 'mycred_fluent_support';
		// Plugin Slug
		public $slug = 'mycred-fluent-support';

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
			if (is_plugin_active('mycred/mycred.php') && is_plugin_active('fluent-support/fluent-support.php')) {
				$this->includes();
				add_filter('mycred_setup_hooks', array($this, 'register_hooks'), 10, 2);
				add_action('mycred_load_hooks', array($this, 'mycred_load_fluent_support_hook'), 10);
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

			$this->define('MYCRED_FLUENT_SUPPORT_SLUG', 'mycred-fluent-support');
			$this->define('MYCRED_FLUENT_SUPPORT', __FILE__);
			$this->define('MYCRED_FLUENT_SUPPORT_ROOT_DIR', plugin_dir_path(MYCRED_FLUENT_SUPPORT));
			$this->define('MYCRED_FLUENT_SUPPORT_INCLUDES_DIR', MYCRED_FLUENT_SUPPORT_ROOT_DIR . 'includes/');
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
		public function mycred_load_fluent_support_hook()
		{

			$this->file(MYCRED_FLUENT_SUPPORT_INCLUDES_DIR . 'mycred-fluent-support-hook.php');
			$this->file(MYCRED_FLUENT_SUPPORT_INCLUDES_DIR . 'mycred-fluent-support-reply-hook.php');
			$this->file(MYCRED_FLUENT_SUPPORT_INCLUDES_DIR . 'mycred-fluent-support-close-hook.php');
			$this->file(MYCRED_FLUENT_SUPPORT_INCLUDES_DIR . 'mycred-fluent-support-customer-hook.php');
			$this->file(MYCRED_FLUENT_SUPPORT_INCLUDES_DIR . 'mycred-fluent-support-customer-reply-hook.php');
			$this->file(MYCRED_FLUENT_SUPPORT_INCLUDES_DIR . 'mycred-fluent-support-customer-close-hook.php');

		}

	public function register_hooks($installed)
	{
		$installed['fluent_support_open_ticket'] = array(
			'title' => __('Fluent Support - Agents Opens or Gets Assigned to a New Ticket', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for opening or getting assigned to a new ticket as an agent.', 'mycred-toolkit'),
			'callback' => array('myCRED_Fluent_Support_Hook')
		);

		$installed['fluent_support_agent_reply'] = array(
			'title' => __('Fluent Support - Agent Reply', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for replying to a ticket as an agent.', 'mycred-toolkit'),
			'callback' => array('myCRED_Fluent_Support_Reply_Hook')
		);

		$installed['fluent_support_agent_close_ticket'] = array(
			'title' => __('Fluent Support - Agent\'s Ticket Gets Closed', 'mycred-toolkit'),
			'description' => __('Awards %_plural% to the assigned agent when their ticket gets closed (by anyone).', 'mycred-toolkit'),
			'callback' => array('myCRED_Fluent_Support_Close_Hook')
		);

		$installed['fluent_support_customer_open_ticket'] = array(
			'title' => __('Fluent Support - Customer Opens New Ticket', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for opening a new ticket as a customer.', 'mycred-toolkit'),
			'callback' => array('myCRED_Fluent_Support_Customer_Hook')
		);

		$installed['fluent_support_customer_reply'] = array(
			'title' => __('Fluent Support - Customer Reply', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for replying to a ticket as a customer.', 'mycred-toolkit'),
			'callback' => array('myCRED_Fluent_Support_Customer_Reply_Hook')
		);

		$installed['fluent_support_customer_close_ticket'] = array(
			'title' => __('Fluent Support - Customer\'s Ticket Gets Closed', 'mycred-toolkit'),
			'description' => __('Awards %_plural% to the customer when their ticket gets closed (by anyone).', 'mycred-toolkit'),
			'callback' => array('myCRED_Fluent_Support_Customer_Close_Hook')
		);

		return $installed;
	}

	public function register_references($list)
	{
		$list['fluent_support_agent_open_ticket'] = __('Fluent Support - Agents Opens or Gets Assigned to a New Ticket', 'mycred-toolkit');
		$list['fluent_support_agent_reply'] = __('Fluent Support - Agent Reply', 'mycred-toolkit');
		$list['fluent_support_agent_close_ticket'] = __('Fluent Support - Agent\'s Ticket Gets Closed', 'mycred-toolkit');
		$list['fluent_support_customer_open_ticket'] = __('Fluent Support - Customer Opens New Ticket', 'mycred-toolkit');
		$list['fluent_support_customer_reply'] = __('Fluent Support - Customer Reply', 'mycred-toolkit');
		$list['fluent_support_customer_close_ticket'] = __('Fluent Support - Customer\'s Ticket Gets Closed', 'mycred-toolkit');
		return $list;
	}

	}
endif;

function myCRED_fluent_support()
{
	return myCRED_Fluent_Support::instance();
}
myCRED_fluent_support();
