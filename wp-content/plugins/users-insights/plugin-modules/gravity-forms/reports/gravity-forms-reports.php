<?php

class USIN_Gravity_Forms_Reports extends USIN_Module_Reports{

	protected $group = 'gravity_forms';
	protected $gf_user_reg;
	protected $gf_unlinked;
	protected $visibility_counts = array();

	public function __construct($gf_user_reg, $gf_unlinked){
		parent::__construct();
		$this->gf_user_reg = $gf_user_reg;
		$this->gf_unlinked = $gf_unlinked;
	}

	public function get_group(){
		$groups = array();
		
		$main_group = array(
			'id' => $this->group,
			'name' => 'Gravity Forms '.__('users', 'usin')
		);

		if($this->gf_user_reg->is_active()){
			$main_group['info'] = __('All of the field reports in this section reflect the user data that is stored as user meta via the User Registration add-on', 'usin');
		}

		$groups[]= $main_group;

		$unlinked_forms = $this->gf_unlinked->get_report_forms();
		foreach ($unlinked_forms as $form ) {
			$groups[]= array(
				'id' => $this->gf_unlinked->get_form_key($form['id']),
				'name' => $form['title'],
				'info' => sprintf(__('* All of the reports in this section are based on all %s form entries, including non-user submissions', 'usin'), $form['title'])
			);
		}

		return $groups;
	}

	protected function get_default_report_visibility_by_group($group_id){
		if(!isset($this->visibility_counts[$group_id])){
			$this->visibility_counts[$group_id] = 0;
		}
		$this->visibility_counts[$group_id] += 1;
		return $this->visibility_counts[$group_id] <= $this->max_cf_reports;
	}

	public function get_reports(){
		$reports = array();

		$reports[]= new USIN_Standard_Report(
			'gravity_forms_submissions', 
			__('Top user submitted forms', 'usin'), 
			array(
				'group' => $this->group,
				'type' => USIN_Report::BAR,
				'loader_class' => 'USIN_Gravity_Forms_User_Submissions_Loader'
			)
		);

		if($this->gf_user_reg->is_active()){
			$reports = array_merge($reports, $this->get_user_reg_reports());
		}
		
		$reports = array_merge($reports, $this->get_unlinked_forms_reports());

		return $reports;

	}

	protected function get_user_reg_reports(){
		$reports = array();

		$gf_user_reg_fields = $this->gf_user_reg->get_meta_fields();
		foreach ($gf_user_reg_fields as $field) {

			switch ($field->get_type()) {
				case 'select':
					$reports[]= new USIN_Standard_Report(
						$field->get_id(), 
						$field->get_name(), 
						array(
							'group' => $this->group,
							'visible' => $this->get_default_report_visibility_by_group($this->group), 
							'loader_class' => 'USIN_Meta_Field_Loader'
						)
					);
				break;
				case 'multioption_text':
				$reports[]= new USIN_Standard_Report(
					$field->get_id(), 
					$field->get_name(), 
					array(
						'group' => $this->group,
						'visible' => $this->get_default_report_visibility_by_group($this->group), 
						'loader_class' => 'USIN_Gravity_Forms_Multioption_Meta_Loader',
						'options' => array('is_json'=>$field->is_multiselect_json_field()),
						'type' => USIN_Report::BAR
					)
				);
				break;
				case 'number':
				$reports[]= new USIN_Standard_Report(
					$field->get_id(), 
					$field->get_name(), 
					array(
						'group' => $this->group,
						'visible' => $this->get_default_report_visibility_by_group($this->group), 
						'loader_class' => 'USIN_Numeric_Meta_Field_Loader',
						'type' => USIN_Report::BAR
					)
				);
				break;
			}
		}

		return $reports;
	}

	protected function get_unlinked_forms_reports(){
		$reports = array();

		$unlinked_forms = $this->gf_unlinked->get_report_forms();
		foreach ($unlinked_forms as $form ) {
			$group = $this->gf_unlinked->get_form_key($form['id']);
			$fields = $this->gf_unlinked->get_form_fields($form['id']);

			$reports[]= new USIN_Period_Report(
				$group.'_submissions', 
				__('Submissions', 'usin'), 
				array(
					'group' => $group,
					'loader_class' => 'USIN_Gravity_Forms_Submissions_Loader',
					'options' => array('form_id'=>$form['id']),
					'info' => __('Includes both user and non-user submissions', 'usin')
				)
			);

			foreach ($fields as $field ) {
				$report_id = $group.'_'.$field->get_id();

				if ($field->get_subfield_type() == 'select') {
					$type = $field->get_gf_type() == 'checkbox' ? USIN_Report::BAR : USIN_Report::PIE;
					$reports[]= new USIN_Standard_Report(
						$report_id, 
						$field->get_name(), 
						array(
							'group' => $group,
							'visible' => $this->get_default_report_visibility_by_group($group), 
							'loader_class' => 'USIN_Gravity_Forms_Field_Loader',
							'options' => array('field_id'=>$field->get_id(), 'form_id'=>$form['id']),
							'type' => $type
						)
					);
					
				}elseif ($field->get_gf_type() == 'multiselect') {
					$storage_type = !empty($field->gf_field->storageType) ? $field->gf_field->storageType : null;

					$reports[]= new USIN_Standard_Report(
						$report_id, 
						$field->get_name(), 
						array(
							'group' => $group,
							'visible' => $this->get_default_report_visibility_by_group($group), 
							'loader_class' => 'USIN_Gravity_Forms_Multiselect_Loader',
							'options' => array('field_id'=>$field->get_id(), 'form_id'=>$form['id'], 'storage_type' => $storage_type),
							'type' => USIN_Report::BAR
						)
					);
					
				}elseif ($field->get_type() == 'number') {
					$reports[]= new USIN_Standard_Report(
						$report_id, 
						$field->get_name(), 
						array(
							'group' => $group,
							'visible' => $this->get_default_report_visibility_by_group($group), 
							'loader_class' => 'USIN_Gravity_Forms_Numeric_Loader',
							'options' => array('field_id'=>$field->get_id(), 'form_id'=>$form['id']),
							'type' => USIN_Report::BAR
						)
					);
					
				}
			}
		}

		return $reports;
	}
}