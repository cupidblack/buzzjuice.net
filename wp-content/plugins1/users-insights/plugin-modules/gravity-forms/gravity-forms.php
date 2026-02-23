<?php

if(!defined( 'ABSPATH' )){
	exit;
}

/**
 * Gravity Forms Module:
 * - loads the user data saved via the Gravity Forms User Registration Add-on
 * - loads data and provides filters about the completed by the users forms
 */
class USIN_Gravity_Forms extends USIN_Plugin_Module{
	
	const MIN_DB_VERSION = '2.3';
	protected $module_name = 'gravityforms';
	protected $plugin_path = 'gravityforms/gravityforms.php';
	protected $gf_user_reg = null;
	protected $gf_unlinked = null;
	protected $gf_query;

	/**
	 * Initialize the main module functionality.
	 */
	public function init(){
		$gf_user_activity = new USIN_Gravity_Forms_User_Activity();
		$gf_user_activity->init();

		$this->gf_user_reg = new USIN_Gravity_Forms_User_Registration($this->module_name);
		$this->gf_unlinked = new USIN_Gravity_Forms_Unlinked_Forms($this->module_name);

		$this->gf_query = new USIN_Gravity_Forms_Query($this->gf_user_reg, $this->gf_unlinked);
		$this->gf_query->init();
	}

	protected function init_reports(){
		new USIN_Gravity_Forms_Reports($this->gf_user_reg, $this->gf_unlinked);
	}

	/**
	 * Registers the module.
	 */
	public function register_module(){
		add_filter('usin_module_update_settings_'.$this->module_name, array($this, 'check_gf_version_before_saving_settings'));

		return array(
			'id' => $this->module_name,
			'name' => 'Gravity Forms',
			'desc' => __('Provides Gravity Forms related filters and data. Detects and displays the custom user data saved with the Gravity Forms User Registration Add-on.', 'usin'),
			'allow_deactivate' => true,
			'buttons' => array(
				array('text'=> __('Learn More', 'usin'), 'link'=>'https://usersinsights.com/gravity-forms-list-search-filter-user-data/', 'target'=>'_blank')
			),
			'active' => false,
			'settings' => array(
				'enable_filters_for_forms' => array(
					'name' => __('Enable form submission filters in user table for forms', 'usin'),
					'type' => USIN_Settings_Field::TYPE_CHECKBOXES,
					'options' => $this->get_form_options(true),
					'desc' => __('This option allows you to filter users based on the data from their form submissions. This field is available for all forms, including those that are not linked to users via the User Registration add-on.', 'usin')
				),
				'enable_reports_for_forms' => array(
					'name' => __('Enable form submission reports for forms', 'usin'),
					'type' => USIN_Settings_Field::TYPE_CHECKBOXES,
					'options' => $this->get_form_options(true),
					'desc' => __('This option allows you to enable reports for all forms, including non-user linked forms<br/>'
						.' and including those forms that are not submitted by users. For each enabled form there will be a separate corresponding tab in the Reports section.', 'usin')
				)
			)
		);
	}
	
	/**
	 * Registers the Gravity Form user fields
	 * @param  array $fields the default Users Insights fields
	 * @return array         the default Users Insights fields including the 
	 * Gravity Form fields
	 */
	public function register_fields(){
		$fields = array();
			
		$form_options = $this->get_form_options();

		$fields[]=array(
			'name' => __('Has submitted form', 'usin'),
			'id' => 'has_completed_form',
			'order' => 'ASC',
			'show' => false,
			'hideOnTable' => true,
			'fieldType' => $this->module_name,
			'filter' => array(
				'type' => 'select_option',
				'options' => $form_options
			),
			'module' => $this->module_name
		);

		$fields[]=array(
			'name' => __('Has not submitted form', 'usin'),
			'id' => 'has_not_completed_form',
			'order' => 'ASC',
			'show' => false,
			'hideOnTable' => true,
			'fieldType' => $this->module_name,
			'filter' => array(
				'type' => 'select_option',
				'options' => $form_options
			),
			'module' => $this->module_name
		);
		
		if($this->gf_user_reg->is_active()){
			//Gravity form user registration meta fields
			$fields = array_merge($fields, $this->gf_user_reg->register_fields());
		}

		$fields = array_merge($fields, $this->gf_unlinked->register_fields());

		return $fields;
	}
	
	protected function get_form_options($assoc = false){
		if(!(isset($_GET['page']) && in_array($_GET['page'], array('usin_modules', 'users_insights')))){
			// return on non UI pages, as calling GFAPI::get_forms() on the Gravity Forms entries page
			// causes a problem with the GF survey addon not showing the entry value names (but only their IDs)
			return array();	
		}
		$form_options = array();
		if(method_exists('GFAPI', 'get_forms')){
			$forms = GFAPI::get_forms();
			
			if(is_array($forms)){
				foreach ($forms as $form ) {
					if($assoc){
						$form_options[$form['id']] = $form['title'];
					}else{
						$form_options[]=array('key'=>$form['id'], 'val'=>$form['title']);
					}
				}
			}
		}
		
		return $form_options;
	}

	public static function is_db_migrated(){
		$db_version = get_option('gf_db_version');
		if(!$db_version && class_exists('GFForms') &&
			property_exists('GFForms', 'version') && !empty(GFForms::$version)){
				$db_version = GFForms::$version;
		}
		if(!empty($db_version)){
			return version_compare($db_version, self::MIN_DB_VERSION, '>=');
		}
		return false;
	}

	public static function get_entries_db_table_name(){
		global $wpdb;
		if(self::is_db_migrated()){
			return $wpdb->prefix.'gf_entry';
		}else{
			return $wpdb->prefix.'rg_lead';
		}
	}

	public function check_gf_version_before_saving_settings($settings){
		$unlinked_forms_enabled = !empty($settings['enable_filters_for_forms']) || !empty($settings['enable_reports_for_forms']);
		
		if($unlinked_forms_enabled && !self::is_db_migrated()){
			return new WP_Error('unsupported_gf_version', 
			sprintf(__('Error: The form filters and reports features require Gravity Forms version %s or newer.', 'usin'), self::MIN_DB_VERSION));
		}

		return $settings;
	}
	
}

new USIN_Gravity_Forms();