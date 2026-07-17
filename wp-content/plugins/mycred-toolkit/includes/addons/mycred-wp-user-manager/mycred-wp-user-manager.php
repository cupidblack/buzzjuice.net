<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_WP_User_Manager')):
	#[AllowDynamicProperties]
	final class myCRED_WP_User_Manager
	{

		// Plugin Domain
		public $domain = 'mycred_wp_user_manager';
		// Plugin Slug
		public $slug = 'mycred-wp-user-manager';

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
				add_action('mycred_load_hooks', array($this, 'mycred_load_wp_user_manager_hook'), 10);
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

			$this->define('MYCRED_WP_USER_MANAGER_SLUG', 'mycred-wp-user-manager');
			$this->define('MYCRED_WP_USER_MANAGER', __FILE__);
			$this->define('MYCRED_WP_USER_MANAGER_ROOT_DIR', plugin_dir_path(MYCRED_WP_USER_MANAGER));
			$this->define('MYCRED_WP_USER_MANAGER_INCLUDES_DIR', MYCRED_WP_USER_MANAGER_ROOT_DIR . 'includes/');
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
		public function mycred_load_wp_user_manager_hook()
		{

			$this->file(MYCRED_WP_USER_MANAGER_INCLUDES_DIR . 'mycred-wp-user-manager-avatar-hook.php');
			$this->file(MYCRED_WP_USER_MANAGER_INCLUDES_DIR . 'mycred-wp-user-manager-remove-avatar-hook.php');
			$this->file(MYCRED_WP_USER_MANAGER_INCLUDES_DIR . 'mycred-wp-user-manager-change-cover-hook.php');
			$this->file(MYCRED_WP_USER_MANAGER_INCLUDES_DIR . 'mycred-wp-user-manager-change-description-hook.php');

		}

		public function register_hooks($installed)
		{
			$installed['wpum_avatar_change'] = array(
				'title' => __('WP User Manager: Change Avatar', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for changing profile avatar via WP User Manager.', 'mycred-toolkit'),
				'callback' => array('myCRED_WP_User_Manager_Avatar_Hook')
			);

			$installed['wpum_avatar_remove'] = array(
				'title' => __('WP User Manager: Remove Avatar', 'mycred-toolkit'),
				'description' => __('Awards or deducts %_plural% for removing profile avatar via WP User Manager.', 'mycred-toolkit'),
				'callback' => array('myCRED_WP_User_Manager_Remove_Avatar_Hook')
			);

			$installed['wpum_cover_change'] = array(
				'title' => __('WP User Manager: Change Cover', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for changing profile cover via WP User Manager.', 'mycred-toolkit'),
				'callback' => array('myCRED_WP_User_Manager_Change_Cover_Hook')
			);

			$installed['wpum_update_bio'] = array(
				'title' => __('WP User Manager: Update Bio', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for updating profile description via WP User Manager.', 'mycred-toolkit'),
				'callback' => array('myCRED_WP_User_Manager_Change_Description_Hook')
			);

			return $installed;
		}

		public function register_references($list)
		{
			$list['wpum_avatar_change'] = __('WP User Manager: Change Avatar', 'mycred-toolkit');
			$list['wpum_avatar_remove'] = __('WP User Manager: Remove Avatar', 'mycred-toolkit');
			$list['wpum_cover_change'] = __('WP User Manager: Change Cover', 'mycred-toolkit');
			$list['wpum_update_bio'] = __('WP User Manager: Update Bio', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_wp_user_manager()
{
	return myCRED_WP_User_Manager::instance();
}
myCRED_wp_user_manager();
