<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_ARForms')):
	#[AllowDynamicProperties]
	final class myCRED_ARForms
	{

		// Plugin Domain
		public $domain = 'mycred_arforms';
		// Plugin Slug
		public $slug = 'mycred-arforms';

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
				add_action('mycred_load_hooks', array($this, 'mycred_load_arforms_hook'), 10);
				add_filter('mycred_all_references', array($this, 'register_refrences'));
				add_action('wp_ajax_mycred_arforms_get_form_fields', array($this, 'ajax_get_form_fields'));
			}
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants()
		{

			$this->define('MYCRED_ARFORMS_SLUG', 'mycred-arforms');
			$this->define('MYCRED_ARFORMS', __FILE__);
			$this->define('MYCRED_ARFORMS_ROOT_DIR', plugin_dir_path(MYCRED_ARFORMS));
			$this->define('MYCRED_ARFORMS_ASSETS_DIR_URL', plugin_dir_url(MYCRED_ARFORMS) . 'assets/');
			$this->define('MYCRED_ARFORMS_INCLUDES_DIR', MYCRED_ARFORMS_ROOT_DIR . 'includes/');
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes()
		{
			// No helper functions file needed yet, but keeping structure consistent
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_arforms_hook()
		{

			$this->file(MYCRED_ARFORMS_INCLUDES_DIR . 'mycred-arforms-hook.php');
			$this->file(MYCRED_ARFORMS_INCLUDES_DIR . 'mycred-arforms-field-value-hook.php');

		}

		public function load_assets()
		{
			// Enqueue script only in admin
			if (is_admin()) {
				wp_enqueue_script(
					'mycred-arforms-admin',
					MYCRED_ARFORMS_ASSETS_DIR_URL . 'js/script.js',
					array('jquery'),
					'1.0',
					true
				);

				// Localize script with AJAX URL and nonce
				wp_localize_script(
					'mycred-arforms-admin',
					'mycred_arforms_ajax',
					array(
						'ajax_url' => admin_url('admin-ajax.php'),
						'nonce' => wp_create_nonce('mycred_arforms_nonce')
					)
				);
			}
		}

		public function register_hooks($installed)
		{
			$installed['arforms_successful_submit'] = array(
				'title' => __('Form Submissions (ARForms)', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in ARForms submit form events.', 'mycred-toolkit'),
				'callback' => array('myCRED_ARForms_Hook')
			);

			$installed['arforms_field_value_submit'] = array(
				'title' => __('Field Value Submissions (ARForms)', 'mycred-toolkit'),
				'description' => __('Award points when users submit specific field values on ARForms.', 'mycred-toolkit'),
				'callback' => array('myCRED_ARForms_Field_Value_Hook')
			);

			return $installed;
		}

		public function register_refrences($list)
		{
			$list['arforms_successful_submit'] = __('Successfully submitting an ARForm', 'mycred-toolkit');
			$list['arforms_field_value_submit'] = __('Submitting specific field value in ARForm', 'mycred-toolkit');
			return $list;
		}

		/**
		 * AJAX handler to get form fields
		 */
		public function ajax_get_form_fields()
		{
			// Verify nonce
			if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mycred_arforms_nonce')) {
				wp_send_json_error(array('message' => 'Invalid nonce'));
			}

			$form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

			global $wpdb;
			$table_name = $wpdb->prefix . 'arf_fields';

			$forms_table = $wpdb->prefix . 'arf_forms';
			
			// If form_id is 0 (Any Form), get all fields from published, non-template forms only
			if ($form_id == 0) {
				// Join with forms table to filter only published, non-template forms
				// Check if arf_is_lite_form column exists
				$lite_form_check = $wpdb->get_var("SHOW COLUMNS FROM {$forms_table} LIKE 'arf_is_lite_form'");
				$lite_form_condition = $lite_form_check ? "AND (fr.arf_is_lite_form = 1 OR fr.arf_is_lite_form IS NULL)" : "";
				
				// Exclude system/internal field types that shouldn't be selectable
				$excluded_types = array('captcha', 'divider', 'section', 'break', 'arf_repeater');
				$excluded_types_escaped = array_map('esc_sql', $excluded_types);
				$excluded_types_sql = "'" . implode("', '", $excluded_types_escaped) . "'";
				
				$fields = $wpdb->get_results(
					"SELECT fi.id, fi.name, fi.type, fi.form_id, fr.name as form_name
					FROM {$table_name} fi
					INNER JOIN {$forms_table} fr ON fi.form_id = fr.id
					WHERE fr.is_template = 0 
					AND (fr.status IS NULL OR fr.status = '' OR fr.status = 'published')
					AND fi.type NOT IN ({$excluded_types_sql})
					{$lite_form_condition}
					ORDER BY fr.name ASC, fi.name ASC"
				);
			} else {
				// Get fields for specific form - verify form is published and not a template
				$form_exists = $wpdb->get_var($wpdb->prepare(
					"SELECT id FROM {$forms_table} 
					WHERE id = %d 
					AND is_template = 0 
					AND (status IS NULL OR status = '' OR status = 'published')",
					$form_id
				));
				
				if ($form_exists) {
					// Exclude system/internal field types
					$excluded_types = array('captcha', 'divider', 'section', 'break', 'arf_repeater');
					$excluded_types_escaped = array_map('esc_sql', $excluded_types);
					$excluded_types_sql = "'" . implode("', '", $excluded_types_escaped) . "'";
					
					$fields = $wpdb->get_results($wpdb->prepare(
						"SELECT id, name, type FROM {$table_name} 
						WHERE form_id = %d 
						AND type NOT IN ({$excluded_types_sql})
						ORDER BY name ASC",
						$form_id
					));
				} else {
					$fields = array(); // Form doesn't exist or is not published
				}
			}

			if ($fields) {
				wp_send_json_success(array('fields' => $fields));
			} else {
				wp_send_json_error(array('message' => 'No fields found'));
			}
		}

	}
endif;

function myCRED_arforms()
{
	return myCRED_ARForms::instance();
}
myCRED_arforms();
