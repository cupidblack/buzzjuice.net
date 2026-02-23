<?php

class USIN_Woocommerce_Coupons_Used_Loader extends USIN_Standard_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_query() : $this->get_legacy_query();

		return $wpdb->get_results( $query );

	}

	protected function get_query(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt'));

		return $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, order_item_name AS $this->label_col".
			" FROM ".$wpdb->prefix."woocommerce_order_items AS items".
			" INNER JOIN {$wpdb->prefix}wc_orders AS orders on items.order_id = orders.id AND orders.type = %s" .
			" WHERE order_item_type = 'coupon'".$period_condition.
			" GROUP BY order_item_name ORDER BY $this->total_col DESC LIMIT $this->max_items", USIN_Woocommerce::ORDER_POST_TYPE);
	}

	protected function get_legacy_query(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, 'orders.post_date');

		return $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, order_item_name AS $this->label_col".
			" FROM ".$wpdb->prefix."woocommerce_order_items AS items".
			" INNER JOIN $wpdb->posts AS orders on items.order_id = orders.ID AND orders.post_type = %s" .
			" WHERE order_item_type = 'coupon'".$period_condition.
			" GROUP BY order_item_name ORDER BY $this->total_col DESC LIMIT $this->max_items", USIN_Woocommerce::ORDER_POST_TYPE);
	}
}