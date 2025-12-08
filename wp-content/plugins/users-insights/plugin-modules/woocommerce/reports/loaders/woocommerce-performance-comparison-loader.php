<?php

class USIN_Woocommerce_Performance_Comparison_Loader extends USIN_Report_Loader {
	protected $filter;
	protected $sales_statuses;

	protected function load_data(){
		$this->filter = $this->getSelectedFilter();
		$this->sales_statuses = USIN_Woocommerce_Query::get_sales_statuses(true);

		$current_name = USIN_Period::get_period_name($this->filter);
		$current = $this->dataset($this->get_items(true), $current_name, 'green');
		$previous_name = USIN_Period::get_previous_period_name($this->filter);
		$previous = $this->dataset($this->get_items(false), $previous_name, 'dark_purple');

		return array($current, $previous);
	}

	protected function get_items($current){
		global $wpdb;
		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_query($current) : $this->get_legacy_query($current);

		$result = $wpdb->get_row($query);
		return $this->result_to_dataset_items($result);
	}

	protected function get_query($current){
		global $wpdb;

		$date_col = USIN_Query_Helper::get_gmt_offset_date_select('date_created_gmt');
		$period_condition = USIN_Standard_Report_With_Period_Comparison_Filter::generate_condition_by_period_type($this->filter, $current, $date_col);
		return $wpdb->prepare("SELECT COUNT(*) AS all_orders,
    			IFNULL(SUM(CASE WHEN status IN ($this->sales_statuses) THEN 1 ELSE 0 END), 0) AS sales,
    			IFNULL(SUM(CASE WHEN status = 'wc-refunded' THEN 1 ELSE 0 END), 0) AS refunds
				FROM {$wpdb->prefix}wc_orders AS orders". $this->get_product_query() ."
				WHERE type = %s" . $period_condition . $this->get_status_query(), USIN_Woocommerce::ORDER_POST_TYPE);
	}

	protected function get_legacy_query($current){
		global $wpdb;

		$period_condition = USIN_Standard_Report_With_Period_Comparison_Filter::generate_condition_by_period_type($this->filter, $current, 'post_date');

		return $wpdb->prepare("SELECT COUNT(*) AS all_orders,
    			IFNULL(SUM(CASE WHEN post_status IN ($this->sales_statuses) THEN 1 ELSE 0 END), 0) AS sales,
    			IFNULL(SUM(CASE WHEN post_status = 'wc-refunded' THEN 1 ELSE 0 END), 0) AS refunds
				FROM $wpdb->posts AS orders". $this->get_product_query() ."
				WHERE post_type = %s" . $period_condition . $this->get_status_query(), USIN_Woocommerce::ORDER_POST_TYPE);

	}

	protected function get_product_query(){
		$product = $this->get_selected_group_filter('product');
		return USIN_Woocommerce_Query::get_product_query($product);
	}

	protected function result_to_dataset_items($result){
		return array(
			(object)array('label' => __('Orders', 'usin'), 'total' => $result->all_orders),
			(object)array('label' => __('Sales', 'usin'), 'total' => $result->sales),
			(object)array('label' => __('Refunds', 'usin'), 'total' => $result->refunds),
		);
	}

	protected function get_status_query(){
		if($wc_statuses = $this->get_statuses()){
			$status_column = USIN_Woocommerce::custom_order_tables_enabled() ? 'status' : 'post_status';
			return " AND $status_column IN (".USIN_Helper::array_to_sql_string(array_keys($wc_statuses)).")";
		}
		return '';
	}

	protected function get_statuses(){
		if(function_exists('wc_get_order_statuses')){
			return wc_get_order_statuses();
		}
	}
}