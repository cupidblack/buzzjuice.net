<?php

/**
 * Gravity Forms module query functionality.
 */
class USIN_Gravity_Forms_Query{
	
	protected $gf_user_reg;
	protected $gf_unlinked;
	protected $ref_count = 0;

	public function __construct($gf_user_reg, $gf_unlinked){
		$this->gf_user_reg = $gf_user_reg;
		$this->gf_unlinked = $gf_unlinked;
	}

	/**
	 * Initializes the main functionality.
	 */
	public function init(){
		add_filter('usin_db_map', array($this, 'filter_db_map'));
		add_filter('usin_custom_query_filter', array($this, 'apply_custom_query_filters'), 10, 2);
		$this->init_meta_query();
	}
	
	public function filter_db_map($db_map){
		$db_map['has_completed_form'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['has_not_completed_form'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);

		foreach ($this->gf_unlinked->get_filter_forms() as $form) {
			$filter_id = $this->gf_unlinked->get_form_filter_id($form['id']);
			$db_map[$filter_id] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		}

		return $db_map;
	}

	public function apply_custom_query_filters($custom_query_data, $filter){
		
		if($filter->by == 'has_completed_form' || $filter->by == 'has_not_completed_form'){
			$custom_query_data = $this->apply_form_completed_clauses($custom_query_data, $filter);
		}elseif(strpos($filter->by, $this->gf_unlinked->get_form_filter_prefix()) === 0){
			$custom_query_data = $this->apply_form_combined_clauses($custom_query_data, $filter);
		}
	
		return $custom_query_data;
	}

	protected function apply_form_completed_clauses($custom_query_data, $filter){
		global $wpdb;
		$ref = $this->get_unique_ref('rgl');
		$table_name = USIN_Gravity_Forms::get_entries_db_table_name();

		$custom_query_data['joins'] .= $wpdb->prepare(" LEFT JOIN ".
			"(SELECT form_id, created_by FROM $table_name WHERE form_id = %d GROUP BY created_by) AS $ref ON ".
			"$wpdb->users.ID = $ref.created_by", $filter->condition);
			
		$operator = $filter->by == 'has_completed_form' ? 'IS NOT NULL' : 'IS NULL';
		$custom_query_data['where'] = " AND $ref.form_id $operator";
		return $custom_query_data;
	}

	protected function apply_form_combined_clauses($custom_query_data, $filter){
		global $wpdb;

		$form_id = intval(str_replace($this->gf_unlinked->get_form_filter_prefix(), '', $filter->by));

		$entries_table = $wpdb->prefix.'gf_entry';
		$entries_meta_table = $wpdb->prefix.'gf_entry_meta';
		$base_ref = $this->get_unique_ref($form_id);
		$entries_ref = "gf_entries_$base_ref";
		
		$custom_query_data['joins'] .= $wpdb->prepare(" INNER JOIN $entries_table AS $entries_ref ON ".
			"$wpdb->users.ID = $entries_ref.created_by AND $entries_ref.form_id = %d", $form_id);

		foreach ($filter->condition as $condition ) {
			if($condition->id == 'submission_date'){
				//filter by date submitted
				$cond_builder = new USIN_Combined_Filter_Condition_Builder();
				$date_created = USIN_Query_Helper::get_gmt_offset_date_select("$entries_ref.date_created");
				$cond_builder->add_date_range_condition($date_created, $condition->val);
				$custom_query_data['where'] .= $cond_builder->build(true);
			}elseif(!empty($condition->id)){
				//filter by field value
				$field_id = intval($condition->id);
				$field = $this->gf_unlinked->get_field($form_id, $field_id );

				$meta_ref = "gf_entry_meta_".$base_ref."_".$field_id;

				$column = "$meta_ref.meta_value";
				$cond_builder = new USIN_Combined_Filter_Condition_Builder();

				switch ($field->get_subfield_type()) {
					case 'text':
						$cond_builder->add_text_contains_condition($column, $condition->val);
						break;
					case 'select':
						$cond_builder->add_text_condition($column, $condition->val);
						break;
					case 'number':
						$cond_builder->add_number_range_condition($column, $condition->val, true);
					break;
					case 'date':
						$cond_builder->add_date_range_condition($column, $condition->val);
					break;
				}

				//use FLOOR(meta_key) so we can search checkbox fields that store a meta entry for each selected value
				//where the meta_key is in the format [field_id].[index of option]
				//use meta_key*1 = meta_key to ensure we are filtering entries with numeric keys only. Some meta keys contain texts
				//so if it is an entry with meta_key 5_something, FLOOR(meta_key) will be 5 and this will be detected as a 
				//record for field with ID 5
				$custom_query_data['joins'] .= $wpdb->prepare(" INNER JOIN $entries_meta_table AS $meta_ref ON ".
				"$entries_ref.id = $meta_ref.entry_id AND FLOOR($meta_ref.meta_key) = %d AND $meta_ref.meta_key*1 = $meta_ref.meta_key".
				$cond_builder->build(true), $field_id);
			}
		}

		return $custom_query_data;
	}
	
	/**
	 * Initializes the meta query for the Gravity Forms fields.
 	 */
	public function init_meta_query(){
		if(is_admin() && $this->gf_user_reg->is_active()){
			$fields = $this->gf_user_reg->get_meta_fields();
			foreach ($fields as $field ) {
				$meta_query = new USIN_Meta_Query($field->get_id(), $field->get_type(), $field->get_prefix());
				$meta_query->init();
			}
		}
	}

	protected function get_unique_ref($prefix){
		return $prefix.'_'.++$this->ref_count;
	}
}