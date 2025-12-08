<?php

class USIN_Woocommerce_Orders_Loader extends USIN_Period_Report_Loader {
	protected $statuses;

	protected function load_data(){
		global $wpdb;
		$this->statuses = USIN_Woocommerce_Query::get_sales_statuses(true);
		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_query() : $this->get_legacy_query();

		$results = $wpdb->get_results($query);
		$results = $this->break_down_by_status($results);
		return $this->convert_to_datasets($results);
	}

	protected function get_query(){
		global $wpdb;
		$date_select = USIN_Query_Helper::get_gmt_offset_date_select('date_created_gmt');
		$group_by = $this->get_group_by().', orders.status';

		return $wpdb->prepare("SELECT COUNT(DISTINCT orders.id) AS $this->total_col, $date_select AS $this->label_col, orders.status FROM {$wpdb->prefix}wc_orders AS orders" . $this->get_product_query() .
			" WHERE type = %s AND $date_select >= %s AND $date_select <= %s".$this->get_status_query().
			" GROUP BY {$group_by}",
			USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_legacy_query(){
		global $wpdb;

		$group_by = $this->get_group_by().', orders.post_status';

		return $wpdb->prepare("SELECT COUNT(DISTINCT orders.ID) AS $this->total_col, post_date AS $this->label_col, orders.post_status AS status FROM $wpdb->posts AS orders" . $this->get_product_query() .
			" WHERE post_type = %s AND post_date >= %s AND post_date <= %s".$this->get_status_query().
			" GROUP BY {$group_by}",
			USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_product_query(){
		$product = $this->get_selected_group_filter('product');
		$category = $this->get_selected_group_filter('category');
		return USIN_Woocommerce_Query::get_product_query($product, $category);
	}

	protected function get_status_query(){
		if($wc_statuses = $this->get_statuses()){
			$status_column = USIN_Woocommerce::custom_order_tables_enabled() ? 'status' : 'post_status';
			return " AND $status_column IN (".USIN_Helper::array_to_sql_string(array_keys($wc_statuses)).")";
		}
		return '';
	}

	protected function break_down_by_status($results){
		$breakdown = array();

		foreach($results as $result){
			$status = $result->status;

			if(!isset($breakdown[$status])){
				$breakdown[$status] = array();
			}

			$breakdown[$status][]=$result;
		}

		return $breakdown;
	}

	protected function convert_to_datasets($results){
		$datasets = array();
		$colors = USIN_WooCommerce_Reports::get_status_colors();

		$statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array();

		$results = $this->reorder_results($results);

		foreach($results as $status => $data){
			$color = isset($colors[$status]) ? $colors[$status] : 'gray';
			$name = isset($statuses[$status]) ? $statuses[$status] : $status;
			$datasets[] = $this->dataset($data, $name, $color);
		}

		return $datasets;
	}

	function reorder_results($results) {
		$order = array('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending', 'wc-refunded', 'wc-cancelled', 'wc-failed');
		$reordered_results = array();

		foreach ($order as $key) {
			if (array_key_exists($key, $results)) {
				$reordered_results[$key] = $results[$key];
				unset($results[$key]);
			}
		}

		// Add any other statuses that might exist
		foreach ($results as $key => $value) {
			$reordered_results[$key] = $value;
		}

		return $reordered_results;
	}

	protected function get_statuses(){
		if(function_exists('wc_get_order_statuses')){
			return wc_get_order_statuses();
		}
	}
}