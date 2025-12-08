<?php

class USIN_Gravity_Forms_Submissions_Loader extends USIN_Period_Report_Loader{

	protected function load_data(){
		global $wpdb;

		$table_name = USIN_Gravity_Forms::get_entries_db_table_name();
		$form_id = $this->report->options['form_id'];
		$group_by = $this->get_period_group_by($this->label_col);

		$date_created = USIN_Query_Helper::get_gmt_offset_date_select('date_created');
		$subquery = $wpdb->prepare("SELECT $date_created AS submission_date FROM $table_name WHERE form_id = %d ", $form_id);

		$query = $wpdb->prepare("SELECT submission_date AS $this->label_col, COUNT(*) AS $this->total_col".
			" FROM ($subquery) AS submissions WHERE submission_date >= %s AND submission_date <= %s GROUP BY $group_by",
			$this->get_period_start(), $this->get_period_end());

		return $wpdb->get_results( $query );
	}
}