<?php

class USIN_Woocommerce_Ordered_Products_Loader extends USIN_Standard_Report_Loader {

	protected function load_data() {
		return $this->get_results();
	}

	protected function get_results($order_status = null) {
		global $wpdb;

		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_query($order_status) : $this->get_legacy_query($order_status);
		return $wpdb->get_results( $query );
	}

	protected function get_query($order_status){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt'));
		$status_condition = $order_status ? $wpdb->prepare(' AND orders.status = %s', $order_status) : '';
		$category_condition = $this->get_category_condition();

		return $wpdb->prepare("SELECT COUNT(DISTINCT orders.id) AS $this->total_col, products.post_title AS $this->label_col" .
			" FROM " . $wpdb->prefix . "woocommerce_order_itemmeta AS meta" .
			" INNER JOIN $wpdb->posts AS products ON meta.meta_value = products.ID" .
			" INNER JOIN {$wpdb->prefix}woocommerce_order_items AS items ON meta.order_item_id = items.order_item_id" .
			" INNER JOIN {$wpdb->prefix}wc_orders AS orders on items.order_id = orders.id AND orders.type = %s" .
			" WHERE meta_key = '_product_id'" . $period_condition . $status_condition . $category_condition .
			" GROUP BY meta_value ORDER BY $this->total_col DESC LIMIT $this->max_items", USIN_Woocommerce::ORDER_POST_TYPE);
	}

	protected function get_legacy_query($order_status){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, 'orders.post_date');
		$status_condition = $order_status ? $wpdb->prepare(' AND orders.post_status = %s', $order_status) : '';
		$category_condition = $this->get_category_condition();

		return $wpdb->prepare("SELECT COUNT(DISTINCT orders.ID) AS $this->total_col, products.post_title AS $this->label_col" .
			" FROM " . $wpdb->prefix . "woocommerce_order_itemmeta AS meta" .
			" INNER JOIN $wpdb->posts AS products ON meta.meta_value = products.ID" .
			" INNER JOIN {$wpdb->prefix}woocommerce_order_items AS items ON meta.order_item_id = items.order_item_id" .
			" INNER JOIN $wpdb->posts AS orders on items.order_id = orders.ID AND orders.post_type = %s" .
			" WHERE meta_key = '_product_id'" . $period_condition . $status_condition . $category_condition .
			" GROUP BY meta_value ORDER BY $this->total_col DESC LIMIT $this->max_items", USIN_Woocommerce::ORDER_POST_TYPE);
	}

	protected function get_category_condition(){
		$category_id = $this->get_selected_group_filter('category');
		if(empty($category_id)){
			return '';
		}

		$category_subquery = USIN_Woocommerce_Query::get_select_product_ids_in_category_query(intval($category_id));
		return " AND meta_value IN ($category_subquery)";
	}
}