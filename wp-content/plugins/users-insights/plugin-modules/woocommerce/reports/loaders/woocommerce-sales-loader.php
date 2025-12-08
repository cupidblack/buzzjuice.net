<?php

class USIN_Woocommerce_Sales_Loader extends USIN_Period_Report_Loader {
	protected $statuses;

	protected function load_data(){
		global $wpdb;
		$this->statuses = USIN_Woocommerce_Query::get_sales_statuses(true);
		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_query() : $this->get_legacy_query();

		return $wpdb->get_results($query);
	}

	protected function get_query(){
		global $wpdb;
		$date_select = USIN_Query_Helper::get_gmt_offset_date_select('date_created_gmt');

		return $wpdb->prepare("SELECT COUNT(DISTINCT orders.id) AS $this->total_col, $date_select AS $this->label_col FROM {$wpdb->prefix}wc_orders AS orders" . $this->get_product_query() .
			" WHERE type = %s AND status IN ($this->statuses) AND $date_select >= %s AND $date_select <= %s GROUP BY {$this->get_group_by()}",
			USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_legacy_query(){
		global $wpdb;

		return $wpdb->prepare("SELECT COUNT(DISTINCT orders.ID) AS $this->total_col, post_date AS $this->label_col FROM $wpdb->posts AS orders" . $this->get_product_query() .
			" WHERE post_type = %s AND post_status IN ($this->statuses) AND post_date >= %s AND post_date <= %s GROUP BY {$this->get_group_by()}",
			USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_product_query(){
		$product = $this->get_selected_group_filter('product');
		$category = $this->get_selected_group_filter('category');
		return USIN_Woocommerce_Query::get_product_query($product, $category);
	}
}