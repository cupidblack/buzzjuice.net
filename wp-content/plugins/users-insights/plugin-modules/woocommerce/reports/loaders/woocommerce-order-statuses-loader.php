<?php

class USIN_Woocommerce_Order_Statuses_Loader extends USIN_Standard_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_query() : $this->get_legacy_query();
		$data = $wpdb->get_results( $query );
		$data = $this->apply_colors($data);
		return $this->apply_status_names($data);
	}

	protected function get_query(){
		global $wpdb;
		return "SELECT COUNT(DISTINCT orders.id) AS $this->total_col, status AS $this->label_col FROM {$wpdb->prefix}wc_orders AS orders".$this->get_product_query().
			" WHERE type = '".USIN_Woocommerce::ORDER_POST_TYPE."'".$this->get_status_query().$this->get_period_condition()." GROUP BY $this->label_col";
	}

	protected function get_legacy_query(){
		global $wpdb;
		return "SELECT COUNT(DISTINCT orders.ID) AS $this->total_col, post_status AS $this->label_col FROM $wpdb->posts AS orders".$this->get_product_query().
			" WHERE post_type = '".USIN_Woocommerce::ORDER_POST_TYPE."'".$this->get_status_query().$this->get_period_condition()." GROUP BY $this->label_col";
	}

	protected function get_product_query(){
		$product = $this->get_selected_group_filter('product');
		$category = $this->get_selected_group_filter('category');
		return USIN_Woocommerce_Query::get_product_query($product, $category);
	}

	protected function get_period_condition(){
		$filter = $this->getSelectedFilter();
		if(USIN_Woocommerce::custom_order_tables_enabled()){
			return USIN_Standard_Report_With_Period_Filter::generate_condition($filter, USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt'));
		}else{
			return  USIN_Standard_Report_With_Period_Filter::generate_condition($filter, 'orders.post_date');
		}
	}

	protected function get_status_query(){
		if($wc_statuses = $this->get_statuses()){
			$status_column = USIN_Woocommerce::custom_order_tables_enabled() ? 'status' : 'post_status';
			return " AND $status_column IN (".USIN_Helper::array_to_sql_string(array_keys($wc_statuses)).")";
		}
		return '';
	}

	protected function apply_status_names($data){
		if($wc_statuses = $this->get_statuses()){

			foreach ($data as $row ) {
				if(isset($wc_statuses[$row->label])){
					$row->label = $wc_statuses[$row->label];
				}
			}
		}
		return $data;
	}

	protected function apply_colors($data){
		$colors = USIN_WooCommerce_Reports::get_status_colors();

		foreach($data as &$item){
			$item->color = isset($colors[$item->label]) ? USIN_Report_Colors::get($colors[$item->label]) : 'gray';
		}

		return $data;
	}

	protected function get_statuses(){
		if(function_exists('wc_get_order_statuses')){
			return wc_get_order_statuses();
		}
	}

}