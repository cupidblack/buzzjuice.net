<?php

class USIN_Woocommerce_Bought_Together_Loader extends USIN_Standard_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$product = $this->get_selected_group_filter('product');
		if(empty($product)){
			return array();
		}

		$item_meta_table = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$items_table = "{$wpdb->prefix}woocommerce_order_items";
		$orders_join = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_orders_join() : $this->get_legacy_orders_join();
		$orders_id_col = USIN_Woocommerce::custom_order_tables_enabled() ? 'orders.id' : 'orders.ID';
		$count = "COUNT(DISTINCT $orders_id_col)";

		$query = $wpdb->prepare("SELECT products.post_title AS $this->label_col, $count AS $this->total_col
			FROM $items_table AS items
			INNER JOIN $item_meta_table AS meta ON items.order_item_id = meta.order_item_id" . $orders_join . "
			INNER JOIN (
				SELECT order_id
				FROM $items_table AS items
				INNER JOIN $item_meta_table AS meta ON items.order_item_id = meta.order_item_id AND meta.meta_key ='_product_id' AND meta.meta_value = %d
			) AS focus_orders ON items.order_id = focus_orders.order_id
			INNER JOIN $wpdb->posts AS products ON products.ID = meta.meta_value AND products.post_type = 'product'                                                                 
			WHERE meta.meta_value != %d AND meta.meta_key ='_product_id'
			GROUP BY meta.meta_value
			HAVING $count > 1
			ORDER BY $this->total_col DESC LIMIT $this->max_items", $product, $product);

		return $wpdb->get_results($query);
	}

	private function get_orders_join(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt'));

		return $wpdb->prepare(" INNER JOIN {$wpdb->prefix}wc_orders AS orders ON orders.id = items.order_id".
			" AND orders.type = %s", USIN_Woocommerce::ORDER_POST_TYPE) . $period_condition;
	}

	private function get_legacy_orders_join(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, 'orders.post_date');

		return $wpdb->prepare(" INNER JOIN $wpdb->posts AS orders ON orders.ID = items.order_id".
			" AND orders.post_type = %s", USIN_Woocommerce::ORDER_POST_TYPE) . $period_condition;
	}
}