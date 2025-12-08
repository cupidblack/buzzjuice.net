<?php

class USIN_Registered_Users_Loader extends USIN_Period_Report_Loader {


	protected function load_data(){
		global $wpdb;

		$registered_select = USIN_Query_Helper::get_gmt_offset_date_select('user_registered');

		$subquery ="SELECT ID, $registered_select AS $this->label_col".
			" FROM $wpdb->users u";

		if(is_multisite()){
			//load only the users for the current site
			$blog_id = $GLOBALS['blog_id'];
			if($blog_id){
				$key = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';
				$subquery .= $wpdb->prepare(" INNER JOIN $wpdb->usermeta m ON".
					" u.ID = m.user_id AND m.meta_key = %s", $key);
			}
		}

		$group_by = $this->get_period_group_by($this->label_col);
		$query =  $wpdb->prepare( "SELECT COUNT(*) AS $this->total_col, $this->label_col FROM ($subquery) AS registrations".
			" WHERE $this->label_col >= %s AND $this->label_col <= %s GROUP BY $group_by",
			$this->get_period_start(), $this->get_period_end());

		return $wpdb->get_results( $query );
	}

}