<?php

class USIN_Woocommerce_Order_Meta_Loader extends USIN_Standard_Report_Loader {

	public function load_data_for_key($meta_key, $limit){
		global $wpdb;
		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_query($meta_key, $limit) : $this->get_legacy_query($meta_key, $limit);
		return $wpdb->get_results($query);
	}

	protected function get_query($meta_key, $limit){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt'));

		$query = $wpdb->prepare("SELECT COUNT(*) as $this->total_col, order_meta.meta_value as $this->label_col FROM {$wpdb->prefix}wc_orders AS orders" .
			$this->get_product_query() .
			" INNER JOIN {$wpdb->prefix}wc_orders_meta AS order_meta ON orders.id = order_meta.order_id AND order_meta.meta_key = %s" .
			" WHERE type = %s AND order_meta.meta_value IS NOT NULL AND order_meta.meta_value != '' " . $period_condition .
			" GROUP BY order_meta.meta_value ORDER BY $this->total_col DESC", $meta_key, USIN_Woocommerce::ORDER_POST_TYPE);

		if($limit){
			$query.= " LIMIT $this->max_items";
		}

		return $query;
	}

	protected function get_legacy_query($meta_key, $limit){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, 'orders.post_date');

		$query = $wpdb->prepare("SELECT COUNT(*) as $this->total_col, order_meta.meta_value as $this->label_col FROM $wpdb->posts AS orders" .
			$this->get_product_query() .
			" INNER JOIN $wpdb->postmeta AS order_meta ON orders.ID = order_meta.post_id AND order_meta.meta_key = %s" .
			" WHERE post_type = %s AND order_meta.meta_value IS NOT NULL AND order_meta.meta_value != '' " . $period_condition .
			" GROUP BY order_meta.meta_value ORDER BY $this->total_col DESC", $meta_key, USIN_Woocommerce::ORDER_POST_TYPE);

		if($limit){
			$query.= " LIMIT $this->max_items";
		}

		return $query;
	}

	protected function get_product_query(){
		$product = $this->get_selected_group_filter('product');
		return USIN_Woocommerce_Query::get_product_query($product);
	}
}