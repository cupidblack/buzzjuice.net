<?php

class USIN_Woocommerce_Product_Sales_Total_Loader extends USIN_Period_Report_Loader {
	protected $items_table;
	protected $item_meta_table;
	protected $statuses;
	protected $product;

	protected function load_data(){
		global $wpdb;

		$this->product = $this->get_selected_group_filter('product');
		$this->statuses = USIN_Woocommerce_Query::get_sales_statuses(true);
		$this->item_meta_table = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$this->items_table = "{$wpdb->prefix}woocommerce_order_items";

		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_query() : $this->get_legacy_query();

		return $wpdb->get_results( $query );
	}

	protected function get_query(){
		global $wpdb;

		$date_select = USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt');
		$product_condition = $this->get_product_condition();

		return $wpdb->prepare("SELECT SUM(line_total.meta_value) AS $this->total_col, $date_select AS $this->label_col 
			FROM {$wpdb->prefix}wc_orders AS orders
			INNER JOIN $this->items_table AS items on items.order_id = orders.id
			LEFT JOIN $this->item_meta_table AS products on products.order_item_id = items.order_item_id and products.meta_key = '_product_id'
			INNER JOIN $this->item_meta_table AS line_total on line_total.order_item_id = items.order_item_id and line_total.meta_key = '_line_total'
			WHERE orders.type = %s AND $product_condition AND orders.status IN ($this->statuses)
			  AND $date_select >= %s AND $date_select <= %s GROUP BY ".$this->get_group_by(),
			USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_legacy_query(){
		global $wpdb;
		$product_condition = $this->get_product_condition();

		return $wpdb->prepare("SELECT SUM(line_total.meta_value) AS $this->total_col, post_date AS $this->label_col 
			FROM $wpdb->posts AS orders
			INNER JOIN $this->items_table AS items on items.order_id = orders.ID
			LEFT JOIN $this->item_meta_table AS products on products.order_item_id = items.order_item_id and products.meta_key = '_product_id'
			INNER JOIN $this->item_meta_table AS line_total on line_total.order_item_id = items.order_item_id and line_total.meta_key = '_line_total'
			WHERE orders.post_type = %s AND $product_condition AND post_status IN ($this->statuses)
			  AND post_date >= %s AND post_date <= %s GROUP BY ".$this->get_group_by(),
			USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_product_condition(){
		global $wpdb;
		$product_id = $this->get_selected_group_filter('product');
		$category_id = $this->get_selected_group_filter('category');

		if($product_id){
			return $wpdb->prepare("products.meta_value = %d", intval($product_id));
		}else{
			$category_subquery = USIN_Woocommerce_Query::get_select_product_ids_in_category_query(intval($category_id));
			return "products.meta_value IN ($category_subquery)";
		}
	}
}