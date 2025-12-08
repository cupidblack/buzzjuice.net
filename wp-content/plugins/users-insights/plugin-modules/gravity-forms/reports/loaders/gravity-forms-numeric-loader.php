<?php

class USIN_Gravity_Forms_Numeric_Loader extends USIN_Numeric_Field_Loader{

	protected function get_default_data(){
		global $wpdb;

		$table_name = $wpdb->prefix.'gf_entry_meta';
		$form_id = $this->report->options['form_id'];
		$field_id = $this->report->options['field_id'];
		
		$query = $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, meta_value AS $this->label_col ".
			" FROM $table_name WHERE form_id = %d AND meta_key = %s AND meta_value != '' AND meta_value IS NOT NULL".
			" GROUP BY meta_value ORDER BY $this->total_col DESC", $form_id, $field_id);
		$data = $wpdb->get_results( $query );
		return $data;
	}

	protected function get_data_in_ranges($chunk_size){
		global $wpdb;

		$select = $this->get_select('meta_value', $chunk_size);
		$group_by = $this->get_group_by('meta_value', $chunk_size);

		$table_name = $wpdb->prefix.'gf_entry_meta';
		$form_id = $this->report->options['form_id'];
		$field_id = $this->report->options['field_id'];

		$query = $wpdb->prepare("$select FROM $table_name WHERE form_id = %s AND meta_key = %s $group_by",
			$form_id, $field_id);

		return $wpdb->get_results( $query );
	}
}