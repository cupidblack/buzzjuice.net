<?php

class USIN_Edd_Sales_Loader extends USIN_Period_Report_Loader {

	protected function load_data(){
		global $wpdb;
		
		$query = USIN_EDD::is_edd_v30() ? $this->get_query() : $this->get_legacy_query();

		return $wpdb->get_results( $query );
	}

	private function get_query(){
		global $wpdb;

		$group_by = $this->get_period_group_by($this->label_col);
		$orders_table = $wpdb->prefix.'edd_orders';
		$date_selector = USIN_Query_Helper::get_gmt_offset_date_select('date_created');
		return $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, $date_selector AS $this->label_col FROM $orders_table".
			" WHERE type = 'sale' AND $date_selector >= %s AND $date_selector <= %s AND status IN ('complete', 'revoked', 'partially_refunded')".
			" GROUP BY $group_by", $this->get_period_start(), $this->get_period_end());
	}

	private function get_legacy_query(){
		global $wpdb;

		$group_by = $this->get_period_group_by($this->label_col);
		return $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, post_date AS $this->label_col FROM $wpdb->posts".
			" WHERE post_type = %s AND post_date >= %s AND post_date <= %s AND post_status IN ('publish', 'revoked')".
			" GROUP BY $group_by",
			USIN_EDD::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}
}