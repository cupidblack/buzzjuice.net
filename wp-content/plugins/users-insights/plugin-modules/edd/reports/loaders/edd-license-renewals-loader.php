<?php

class USIN_Edd_License_Renewals_Loader extends USIN_Period_Report_Loader {


	protected function load_data(){
		global $wpdb;

		$query = USIN_EDD::is_edd_v30() ? $this->get_query() : $this->get_legacy_query();

		return $wpdb->get_results( $query );
	}

	private function get_query(){
		global $wpdb;

		$orders_table = $wpdb->prefix.'edd_orders';
		$meta_table = $wpdb->prefix.'edd_ordermeta';
		$group_by = $this->get_period_group_by($this->label_col);
		$date_selector = USIN_Query_Helper::get_gmt_offset_date_select('date_created');

		return $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, $date_selector AS $this->label_col FROM $orders_table AS orders".
			" INNER JOIN $meta_table AS meta on orders.id = meta.edd_order_id AND meta.meta_key = '_edd_sl_is_renewal' AND meta.meta_value='1'".
			" WHERE type = 'sale' AND $date_selector >= %s AND $date_selector <= %s AND status = 'complete' GROUP BY $group_by",
			$this->get_period_start(), $this->get_period_end());
	}

	private function get_legacy_query(){
		global $wpdb;

		$group_by = $this->get_period_group_by($this->label_col);
		return $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, post_date AS $this->label_col FROM $wpdb->posts AS posts".
			" INNER JOIN $wpdb->postmeta AS meta on posts.ID = meta.post_id AND meta.meta_key = '_edd_sl_is_renewal' AND meta.meta_value='1'".
			" WHERE post_type = %s AND post_date >= %s AND post_date <= %s AND post_status = 'publish'".
			" GROUP BY $group_by",
			USIN_EDD::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

}