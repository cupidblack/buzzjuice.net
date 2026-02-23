<?php

class USIN_Woocommerce_Product_Attributes_Loader extends USIN_Standard_Report_Loader {
	protected $items_table;
	protected $item_meta_table;

	protected function load_data(){
		global $wpdb;

		$product_id = $this->get_selected_group_filter('product');
		$product = wc_get_product($product_id);

		if(!$product->is_type('variable')){
			return new WP_Error('report_error', __('This report is not applicable to this product', 'usin'), 'notice');
		}

		$attributes = $product->get_attributes();

		if(empty($attributes)){
			return array();
		}

		$this->item_meta_table = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$this->items_table = "{$wpdb->prefix}woocommerce_order_items";
		$orders_join = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_orders_join() : $this->get_legacy_orders_join();
		$attributes_sql = USIN_Helper::array_to_sql_string(array_keys($attributes));

		$query = $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, attributes.meta_key AS 'attribute_key', attributes.meta_value AS attribute_value
			FROM $this->item_meta_table AS attributes
			INNER JOIN $this->item_meta_table AS products ON products.order_item_id = attributes.order_item_id ".$orders_join."
			WHERE attributes.meta_key IN ($attributes_sql) AND products.meta_key = '_product_id' AND products.meta_value = %d 
			GROUP BY attributes.meta_key, attributes.meta_value ORDER BY $this->total_col DESC LIMIT $this->max_items",
			$product_id);

		$data = $wpdb->get_results($query);
		return $this->apply_attributes_names($data, $attributes, $product);
	}

	protected function get_orders_join(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt'));

		return $wpdb->prepare(" INNER JOIN $this->items_table AS items ON items.order_item_id = products.order_item_id 
			INNER JOIN {$wpdb->prefix}wc_orders AS orders ON orders.id = items.order_id
			AND orders.type = %s", USIN_Woocommerce::ORDER_POST_TYPE) . $period_condition;
	}

	protected function get_legacy_orders_join(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, 'orders.post_date');

		return $wpdb->prepare(" INNER JOIN $this->items_table AS items ON items.order_item_id = products.order_item_id 
			INNER JOIN $wpdb->posts AS orders ON orders.ID = items.order_id
			AND orders.post_type = %s", USIN_Woocommerce::ORDER_POST_TYPE) . $period_condition;
	}

	protected function apply_attributes_names($data, $attributes, $product){
		foreach($data as $row){
			$attr_value = $row->attribute_value;
			$attr_name = $attributes[$row->attribute_key];

			if(isset($attributes[$row->attribute_key])){
				$attribute = $attributes[$row->attribute_key];
				$attr_name = wc_attribute_label($row->attribute_key, $product);

				if($attribute->is_taxonomy()){
					$attr_value = $this->get_global_attribute_display_value($row->attribute_key, $row->attribute_value);
				}
			}

			$row->label = "$attr_value ($attr_name)";

			// these are not needed anymore
			unset($row->attribute_key);
			unset($row->attribute_value);
		}
		return $data;
	}

	protected function get_global_attribute_display_value($key, $value){
		if(taxonomy_exists($key)){
			$term = get_term_by('slug', $value, $key);
			if(!is_wp_error($term) && is_object($term) && $term->name){
				return $term->name;
			}
			return $value;
		}
	}
}