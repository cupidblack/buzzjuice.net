<?php

class USIN_Edd_Orders_By_Status_Loader extends USIN_Period_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$date_select = USIN_Query_Helper::get_gmt_offset_date_select('date_created');
		$group_by = $this->get_period_group_by($this->label_col).', status';

		$query =  $wpdb->prepare("SELECT COUNT(DISTINCT id) AS $this->total_col, $date_select AS $this->label_col, status 
       		FROM {$wpdb->prefix}edd_orders AS orders
			WHERE type = 'sale' AND $date_select >= %s AND $date_select <= %s AND status != 'trash'
			GROUP BY {$group_by}", $this->get_period_start(), $this->get_period_end());

		$results = $wpdb->get_results($query);
		$results = $this->break_down_by_status($results);
		return $this->convert_to_datasets($results);
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
		$colors = USIN_EDD::get_status_colors();

		$statuses = USIN_EDD::get_order_status_options(true);

		$results = $this->reorder_results($results);

		foreach($results as $status => $data){
			$color = isset($colors[$status]) ? $colors[$status] : 'gray';
			$name = isset($statuses[$status]) ? $statuses[$status] : $status;
			$datasets[] = $this->dataset($data, $name, $color);
		}

		return $datasets;
	}

	function reorder_results($results) {
		$order = array('complete', 'processing', 'pending', 'refunded', 'on_hold', 'partially_refunded',
			'failed', 'revoked',  'abandoned');
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
}