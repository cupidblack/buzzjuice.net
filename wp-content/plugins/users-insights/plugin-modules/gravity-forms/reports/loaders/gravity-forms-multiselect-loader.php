<?php

class USIN_Gravity_Forms_Multiselect_Loader extends USIN_Multioption_Field_Loader{

	protected function load_data(){
		global $wpdb;

		$table_name = $wpdb->prefix.'gf_entry_meta';
		$form_id = $this->report->options['form_id'];
		$field_id = $this->report->options['field_id'];
		
		$query = $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, meta_value AS $this->label_col ".
			" FROM $table_name WHERE form_id = %d AND meta_key = %s AND meta_value != '' AND meta_value IS NOT NULL".
			" GROUP BY meta_value ORDER BY $this->total_col DESC", $form_id, $field_id);

		$data = $wpdb->get_results( $query );
		return $this->unify_fields($data);
	}

	protected function value_to_array($value){
		$storage_type = $this->report->options['storage_type'];
		if($storage_type == 'json'){
			return json_decode($value);
		}else{
			return array_map('trim', explode(',', $value));
		}
		
	}
}