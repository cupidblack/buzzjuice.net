<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_Meta_Box')):
	#[AllowDynamicProperties]
	final class myCRED_Meta_Box
	{

		// Plugin Domain
		public $domain = 'mycred_meta_box';
		// Plugin Slug
		public $slug = 'mycred-meta-box';

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
			// Check if myCred and Meta Box are active
			if (is_plugin_active('mycred/mycred.php') && (is_plugin_active('meta-box/meta-box.php') || is_plugin_active('meta-box-lite/meta-box-lite.php'))) {
				$this->includes();
				add_filter('mycred_setup_hooks', array($this, 'register_hooks'), 10, 2);
				add_action('mycred_load_hooks', array($this, 'mycred_load_meta_box_hook'), 10);
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

			$this->define('MYCRED_META_BOX_SLUG', 'mycred-meta-box');
			$this->define('MYCRED_META_BOX', __FILE__);
			$this->define('MYCRED_META_BOX_ROOT_DIR', plugin_dir_path(MYCRED_META_BOX));
			$this->define('MYCRED_META_BOX_INCLUDES_DIR', MYCRED_META_BOX_ROOT_DIR . 'includes/');
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
		public function mycred_load_meta_box_hook()
		{

			$this->file(MYCRED_META_BOX_INCLUDES_DIR . 'mycred-meta-box-post-save-hook.php');
			$this->file(MYCRED_META_BOX_INCLUDES_DIR . 'mycred-meta-box-specific-post-save-hook.php');
			$this->file(MYCRED_META_BOX_INCLUDES_DIR . 'mycred-meta-box-field-post-save-hook.php');

		}

	public function register_hooks($installed)
	{
		$installed['meta_box_save_post'] = array(
			'title' => __('Meta Box - Save Any Meta Box (Post)', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for saving any meta box on a post.', 'mycred-toolkit'),
			'callback' => array('myCRED_Meta_Box_Post_Save_Hook')
		);

		$installed['meta_box_save_specific_post'] = array(
			'title' => __('Meta Box - Save Specific Meta Box (Post)', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for saving a specific meta box on a post.', 'mycred-toolkit'),
			'callback' => array('myCRED_Meta_Box_Specific_Post_Save_Hook')
		);

		$installed['meta_box_save_field_post'] = array(
			'title' => __('Meta Box - Save Specific Field (Post)', 'mycred-toolkit'),
			'description' => __('Awards %_plural% for saving a specific meta field on a post.', 'mycred-toolkit'),
			'callback' => array('myCRED_Meta_Box_Field_Post_Save_Hook')
		);

		return $installed;
	}

	public function register_references($list)
	{
		$list['meta_box_save_post'] = __('Meta Box - Save Any Meta Box (Post)', 'mycred-toolkit');
		$list['meta_box_save_specific_post'] = __('Meta Box - Save Specific Meta Box (Post)', 'mycred-toolkit');
		$list['meta_box_save_field_post'] = __('Meta Box - Save Specific Field (Post)', 'mycred-toolkit');
		return $list;
	}

	}
endif;

function myCRED_meta_box()
{
	return myCRED_Meta_Box::instance();
}
myCRED_meta_box();
