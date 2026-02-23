<?php

class USIN_Buddypress_Active_Users_Loader extends USIN_Period_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$group_by = $this->get_period_group_by($this->label_col);
		$prefix = USIN_BuddyPress_Query::get_prefix();
		$activity_date_select = USIN_Query_Helper::get_gmt_offset_date_select('date_recorded');

		$subquery = "SELECT user_id, $activity_date_select AS activity_date FROM {$prefix}bp_activity WHERE `type` != 'last_activity'";

		$query = $wpdb->prepare("SELECT COUNT(DISTINCT(user_id)) AS $this->total_col, activity_date AS $this->label_col" .
			" FROM ($subquery) AS activities WHERE activity_date >= %s AND activity_date <= %s".
			" GROUP BY $group_by",
			$this->get_period_start(), $this->get_period_end());

		return $wpdb->get_results($query);
	}
}