<?php
/**
 * The admin license settings page functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.9.0
 *
 * @package    woocommerce-checkout-field-editor-pro
 * @subpackage woocommerce-checkout-field-editor-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWCFE_Admin_Settings_License')):

class THWCFE_Admin_Settings_License extends THWCFE_Admin_Settings{
	protected static $_instance = null;
	
	public $page_id;

	public function __construct() {
		parent::__construct();
		
		$this->page_id = 'license_settings';
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	} 	
	
	public function render_page(){
		settings_errors();
		$this->render_tabs();
		$this->output_content();
	}

	private function output_content(){
		echo do_shortcode('[th_checkout_field_editor_for_woocommerce_license_form]');
	}
}

endif;