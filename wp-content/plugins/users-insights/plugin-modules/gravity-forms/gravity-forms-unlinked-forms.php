<?php

class USIN_Gravity_Forms_Unlinked_Forms{

	const FORM_KEY_PREFIX = 'gf_form_';
	protected $forms = array();
	protected $form_fields = array();
	protected $module_name;

	public function __construct($module_name){
		$this->module_name = $module_name;
	}

	public function register_fields(){
		$result = array();

		$forms = $this->get_filter_forms();
		foreach ($forms as $form ) {
			$result[]= array(
				'name' => sprintf(__('Has submitted form %s', 'usin'), $form['title']),
				'id' => $this->get_form_filter_id($form['id']),
				'order' => 'DESC',
				'show' => false,
				'hideOnTable' => true,
				'fieldType' => $this->module_name,
				'filter' => array(
					'type' => 'combined',
					'items' => $this->get_filter_items($form),
					'disallow_null' => true
				),
				'module' => $this->module_name
			);
		}
		
		return $result;
	}

	public function get_form_key($form_id){
		return self::FORM_KEY_PREFIX.$form_id;
	}

	public function get_form_filter_prefix(){
		return 'gf_submitted_form_';
	}

	public function get_form_filter_id($form_id){
		return $this->get_form_filter_prefix().$form_id;
	}

	public function get_filter_forms(){
		return $this->get_enabled_forms('enable_filters_for_forms');
	}

	public function get_report_forms(){
		return $this->get_enabled_forms('enable_reports_for_forms');
	}

	protected function get_enabled_forms($option_key){
		$result = array();
		$form_ids = usin_get_module_setting($this->module_name, $option_key);

		foreach ($form_ids as $form_id) {
			$form = $this->get_form($form_id);
			if(!empty($form)){
				$result[]=$form;
			}
		}

		return $result;
	}

	protected function get_form($form_id){
		if(!method_exists('GFAPI', 'get_form')){
			return null;	
		}
		$form_id = intval($form_id);
		if(!isset($this->forms[$form_id])){
			$this->forms[$form_id] = null;
			$form = GFAPI::get_form($form_id);
			if(!empty($form) && is_array($form) && !(isset($form['is_trash']) && $form['is_trash'])){
				$this->forms[$form_id] = $form;
			}
		}
		return $this->forms[$form_id];
	}

	public function get_form_fields($form_id){
		if(isset($this->form_fields[$form_id])){
			return $this->form_fields[$form_id];
		}

		$this->form_fields[$form_id] = array();
		$form = $this->get_form($form_id);

		if(!empty($form) && isset($form['fields'])){
			$gf_fields = $form['fields'];
			foreach ($gf_fields as $gf_field ) {
				$this->form_fields[$form_id][]= new USIN_Gravity_Forms_Field($gf_field);
			}
		}
		return $this->form_fields[$form_id];
	}

	public function get_field($form_id, $field_id){
		$form_fields = $this->get_form_fields($form_id);
		foreach ($form_fields as $field ) {
			if($field->get_id() == $field_id){
				return $field;
			}
		}
	}

	protected function get_filter_items($form){
		$result = array(
			array('id'=> 'submission_date', 'name' => __('Submission date', 'usin'), 'type' => 'date')
		);

		$fields = $this->get_form_fields($form['id']);

		foreach ($fields as $field ) {

			$type = $field->get_subfield_type();
			if(!$type){
				continue;
			}

			$item = array(
				'id' => $field->get_id(),
				'name' => $field->get_name(),
				'type' => $type
			);

			if($type == 'select'){
				$item['options'] = $field->get_field_options();
			}
			
			$result[]= $item;
		}

		return $result;
	}
}