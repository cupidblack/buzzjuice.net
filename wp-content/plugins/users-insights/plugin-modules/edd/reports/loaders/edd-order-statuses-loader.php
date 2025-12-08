<?php

class USIN_Edd_Order_Statuses_Loader extends USIN_Standard_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$query = USIN_EDD::is_edd_v30() ? $this->get_query() : $this->get_legacy_query();

		$data = $wpdb->get_results( $query );
		$data = $this->apply_colors($data);

		$statuses = USIN_EDD::get_order_status_options(true);
		return $this->match_ids_to_names($data, $statuses, true);
	}

	private function get_query(){
		global $wpdb;

		$orders_table = $wpdb->prefix.'edd_orders';
		return "SELECT COUNT(*) AS $this->total_col, status AS $this->label_col".
			" FROM $orders_table WHERE type = 'sale' GROUP BY status";
	}

	private function get_legacy_query(){
		global $wpdb;

		return $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, post_status AS $this->label_col".
			" FROM $wpdb->posts WHERE post_type = %s GROUP BY post_status",
			USIN_EDD::ORDER_POST_TYPE);
	}

	protected function apply_colors($data){
		$colors = USIN_EDD::get_status_colors();

		foreach($data as &$item){
			$item->color = isset($colors[$item->label]) ? USIN_Report_Colors::get($colors[$item->label]) : 'gray';
		}

		return $data;
	}
}