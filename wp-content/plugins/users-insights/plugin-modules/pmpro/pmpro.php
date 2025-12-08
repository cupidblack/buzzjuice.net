<?php

if(!defined( 'ABSPATH' )){
	exit;
}

class USIN_Pmpro extends USIN_Plugin_Module{
	
	protected $module_name = 'pmpro';
	protected $plugin_path = 'paid-memberships-pro/paid-memberships-pro.php';
	

	public function init(){
		$query = new USIN_Pmpro_Query();
		$query->init();

		$user_activity = new USIN_Pmpro_User_Activity();
		$user_activity->init();
	}

	protected function init_reports(){
		new USIN_Pmpro_Reports();
	}

	public function register_module(){
		return array(
			'id' => $this->module_name,
			'name' => 'Paid Memberships Pro',
			'desc' => __('Detects the Paid Memberships Pro user data and makes it available in the user table, filters and reports.', 'usin'),
			'allow_deactivate' => true,
			'buttons' => array(
				array('text'=> __('Learn More', 'usin'), 'link'=>'https://usersinsights.com/paid-memberships-pro-search-filters', 'target'=>'_blank')
			),
			'active' => false
		);
	}

	public function register_fields(){
		$fields = array();
		$levels = self::get_levels();
		$statuses = self::get_statuses();

		if($this->enable_single_membership_fields()){
			$fields[] = array(
				'name' => $this->get_legacy_field_name(__('Level', 'usin')),
				'id' => 'pmpro_level',
				'order' => false,
				'show' => true,
				'fieldType' => 'general',
				'filter' => array(
					'type' => 'select',
					'options' => $levels
				),
				'module' => $this->module_name
			);

			$fields[] = array(
				'name' => $this->get_legacy_field_name(__('Start date', 'usin')),
				'id' => 'pmpro_start_date',
				'order' => 'DESC',
				'show' => true,
				'fieldType' => $this->module_name,
				'filter' => array(
					'type' => 'date'
				),
				'module' => $this->module_name
			);

			$fields[] = array(
				'name' => $this->get_legacy_field_name(__('End date', 'usin')),
				'id' => 'pmpro_end_date',
				'order' => 'DESC',
				'show' => true,
				'fieldType' => $this->module_name,
				'filter' => array(
					'type' => 'date'
				),
				'module' => $this->module_name
			);


			$fields[] = array(
				'name' => $this->get_legacy_field_name(__('Status', 'usin')),
				'id' => 'pmpro_status',
				'order' => 'ASC',
				'show' => true,
				'fieldType' => $this->module_name,
				'filter' => array(
					'type' => 'select',
					'options' => $statuses
				),
				'module' => $this->module_name
			);
		}

		if(self::is_v3_or_greater()){
			$fields[] = array(
				'name' => __('Member status', 'usin'),
				'id' => 'pmpro_member_status',
				'order' => 'ASC',
				'show' => true,
				'fieldType' => $this->module_name,
				'allowHtml' => true,
				'filter' => array(
					'type' => 'select_option',
					'options' => array(
						array('key' => 'active', 'val' => __('is active', 'usin')),
						array('key' => 'inactive', 'val' => __('is inactive', 'usin'))
					)
				),
				'module' => $this->module_name
			);

			$fields[]= array(
				'name' => __('Memberships', 'usin'),
				'id' => 'pmpro_membership_num',
				'order' => 'DESC',
				'show' => true,
				'fieldType' => $this->module_name,
				'filter' => array(
					'type' => 'number',
					'disallow_null' => true
				),
				'module' => $this->module_name
			);

			$fields[]=array(
				'name' => __('Active memberships', 'usin'),
				'id' => 'pmpro_active_memberships',
				'order' => false,
				'show' => false,
				'fieldType' => $this->module_name,
				'filter' => false,
				'module' => $this->module_name
			);

			$fields[]= array(
				'name' => __('Has a membership', 'usin'),
				'id' => 'pmpro_has_membership',
				'order' => false,
				'show' => false,
				'hideOnTable' => true,
				'fieldType' => $this->module_name,
				'filter' => array(
					'type' => 'combined',
					'items' => array(
						array('name' => __('Level', 'usin'), 'id' => 'level', 'type' => 'select', 'options' => $levels),
						array('name' => __('Status', 'usin'), 'id' => 'status', 'type' => 'select', 'options' => $statuses),
						array('name' => __('Start date', 'usin'), 'id' => 'start_date', 'type' => 'date'),
						array('name' => __('End date', 'usin'), 'id' => 'end_date', 'type' => 'date')
					),
					'disallow_null' => true
				),
				'module' => $this->module_name
			);
		}

		$fields[]=array(
			'name' => __('Member since', 'usin'),
			'id' => 'pmpro_member_since',
			'order' => 'DESC',
			'show' => true,
			'fieldType' => $this->module_name,
			'filter' => array(
				'type' => 'date'
			),
			'module' => $this->module_name
		);

		$fields[]=array(
			'name' => __('Lifetime value', 'usin'),
			'id' => 'pmpro_ltv',
			'order' => 'DESC',
			'show' => true,
			'fieldType' => 'general',
			'filter' => array(
				'type' => 'number',
				'disallow_null' => true
			),
			'module' => $this->module_name
		);

		$fields[]=array(
			'name' => __('Payments', 'usin'),
			'id' => 'pmpro_payment_count',
			'order' => 'DESC',
			'show' => true,
			'fieldType' => $this->module_name,
			'filter' => array(
				'type' => 'number',
				'disallow_null' => true
			),
			'module' => $this->module_name
		);

		$fields[]=array(
			'name' => __('Last payment', 'usin'),
			'id' => 'pmpro_last_payment',
			'order' => 'DESC',
			'show' => true,
			'fieldType' => $this->module_name,
			'filter' => array(
				'type' => 'date'
			),
			'module' => $this->module_name
		);

		$fields[]=array(
			'name' => __('Has used discount code', 'usin'),
			'id' => 'pmpro_has_used_discount_code',
			'show' => false,
			'hideOnTable' => true,
			'fieldType' => $this->module_name,
			'filter' => array(
				'type' => 'select_option',
				'options' => self::get_discount_codes()
			),
			'module' => $this->module_name
		);

		$fields[]=array(
			'name' => __('Billing country', 'usin'),
			'id' => 'pmpro_bcountry',
			'show' => false,
			'fieldType' => 'general',
			'order' => 'ASC',
			'filter' => array(
				'type' => 'select',
				'options' => self::get_countries()
			),
			'module' => $this->module_name
		);

		$fields[]=array(
			'name' => __('Billing state', 'usin'),
			'id' => 'pmpro_bstate',
			'show' => false,
			'order' => 'ASC',
			'fieldType' => 'general',
			'filter' => array(
				'type' => 'text'
			),
			'module' => $this->module_name
		);

		$fields[]=array(
			'name' => __('Billing city', 'usin'),
			'id' => 'pmpro_bcity',
			'show' => false,
			'order' => 'ASC',
			'fieldType' => 'general',
			'filter' => array(
				'type' => 'text'
			),
			'module' => $this->module_name
		);

		return $fields;
	}

	public static function is_v3_or_greater(){
		return defined('PMPRO_VERSION') && version_compare(PMPRO_VERSION, '3.0', '>=');
	}

	public static function get_levels($assoc_res = false){
		global $wpdb;
		$levels = array();

		if(isset($wpdb->pmpro_membership_levels)){
			$res = $wpdb->get_results( "SELECT id, name FROM $wpdb->pmpro_membership_levels" );
			if(!empty($res)){
				foreach ($res as $row ) {
					if($assoc_res){
						$levels[$row->id] = $row->name;
					}else{
						$levels[]= array('key'=>$row->id, 'val'=>$row->name);
					}
				}
			}

		}

		return $levels;
	}

	public static function get_statuses($assoc_res = false){
		global $wpdb;
		$statuses = array();

		if(isset($wpdb->pmpro_memberships_users)){
			$res = $wpdb->get_col( "SELECT DISTINCT status FROM $wpdb->pmpro_memberships_users" );
			
			if(!empty($res)){
				foreach ($res as $status ) {
					if($assoc_res){
						$statuses[$status] = $status;
					}else{
						$statuses[]= array('key'=>$status, 'val'=>$status);
					}
				}
			}

		}

		return $statuses;
	}

	public static function get_discount_codes($assoc_res = false){
		global $wpdb;
		$codes = array();

		if(isset($wpdb->pmpro_discount_codes)){
			$res = $wpdb->get_results( "SELECT id, code FROM $wpdb->pmpro_discount_codes" );
			if(!empty($res)){
				foreach ($res as $row ) {
					if($assoc_res){
						$codes[$row->id] = $row->code;
					}else{
						$codes[]= array('key'=>$row->id, 'val'=>$row->code);
					}
				}
			}

		}

		return $codes;
	}

	public static function get_countries($assoc_res = false){
		global $pmpro_countries;
	
		if(empty($pmpro_countries) || !is_array($pmpro_countries)){
			return array();
		}

		if($assoc_res){
			return $pmpro_countries;
		}

		$countries = array();
		foreach ($pmpro_countries as $code => $name) {
			$countries[]=array('key'=>$code, 'val'=>$name);
		}

		return $countries;

	}

	protected function enable_single_membership_fields(){
		$should_enable = !self::is_v3_or_greater();
		return apply_filters('usin_pmpro_enable_single_membership_fields', $should_enable);
	}

	protected function get_legacy_field_name($name){
		return self::is_v3_or_greater() ? sprintf('%s %s', __('Last', 'usin'), $name) : $name;
	}
}

new USIN_Pmpro();