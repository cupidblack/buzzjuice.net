<?php

class USIN_Woocommerce_Product_Variations_Loader extends USIN_Standard_Report_Loader {
	protected $items_table;
	protected $item_meta_table;

	protected function load_data(){
		global $wpdb;

		$product_id = $this->get_selected_group_filter('product');
		$product = wc_get_product($product_id);

		$this->item_meta_table = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$this->items_table = "{$wpdb->prefix}woocommerce_order_items";

		if(!$product->is_type('variable')){
			return new WP_Error('report_error', __('This report is not applicable to this product', 'usin'), 'notice');
		}

		$orders_join = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_orders_join() : $this->get_legacy_orders_join();

		$query = $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, variation_ids.meta_value AS $this->label_col
			FROM $this->item_meta_table AS variation_ids
			INNER JOIN $this->item_meta_table AS products ON products.order_item_id = variation_ids.order_item_id " . $orders_join . "
			WHERE variation_ids.meta_key = '_variation_id' AND products.meta_key = '_product_id' AND products.meta_value = %d
			GROUP BY variation_ids.meta_value ORDER BY $this->total_col DESC LIMIT $this->max_items",
			$product_id);

		$data = $wpdb->get_results($query);
		return $this->apply_variation_names($data);
	}

	protected function get_orders_join(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt'));

		return $wpdb->prepare(" INNER JOIN $this->items_table AS items ON items.order_item_id = variation_ids.order_item_id 
			INNER JOIN {$wpdb->prefix}wc_orders AS orders ON orders.id = items.order_id
			AND orders.type = %s", USIN_Woocommerce::ORDER_POST_TYPE) . $period_condition;
	}

	protected function get_legacy_orders_join(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, 'orders.post_date');

		return $wpdb->prepare(" INNER JOIN $this->items_table AS items ON items.order_item_id = variation_ids.order_item_id 
			INNER JOIN $wpdb->posts AS orders ON orders.ID = items.order_id
			AND orders.post_type = %s", USIN_Woocommerce::ORDER_POST_TYPE) . $period_condition;
	}

	protected function apply_variation_names($data){
		foreach($data as $row){
			$row->label = $this->get_variation_name($row->label);
		}

		return $data;
	}

	protected function get_variation_name($variation_id){
		$variation = wc_get_product($variation_id);

		if($variation && function_exists('wc_get_formatted_variation')){
			$name = wc_get_formatted_variation($variation, true, true, false);
			if(!empty($name)){
				return $name;
			}
		}

		return "#{$variation_id}";
	}
}