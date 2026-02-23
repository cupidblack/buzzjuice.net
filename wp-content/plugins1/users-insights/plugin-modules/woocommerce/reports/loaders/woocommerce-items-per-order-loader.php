<?php

class USIN_Woocommerce_Items_Per_Order_Loader extends USIN_Standard_Report_Loader {
	protected $filter;

	protected function load_data(){
		global $wpdb;

		$this->filter = $this->getSelectedFilter();
		$subquery = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_subquery() : $this->get_legacy_subquery();

		$query = "SELECT COUNT(*) AS $this->total_col, item_num AS $this->label_col".
			" FROM ($subquery) AS item_nums GROUP BY item_num";

		$data = $wpdb->get_results( $query );
		return $this->format_labels($data);
	}

	protected function get_subquery(){
		global $wpdb;
		$status_cond = $this->filter == 'all' ? '' : $wpdb->prepare(" AND status = %s", $this->filter);

		return $wpdb->prepare("SELECT SUM(IFNULL(meta.meta_value, 0)) AS item_num FROM {$wpdb->prefix}wc_orders AS orders
			LEFT JOIN ".$wpdb->prefix."woocommerce_order_items AS items ON orders.id = items.order_id
			LEFT JOIN ".$wpdb->prefix."woocommerce_order_itemmeta AS meta ON meta.order_item_id = items.order_item_id AND meta.meta_key = '_qty'
			WHERE orders.type = %s".$status_cond."
			GROUP BY orders.id", USIN_Woocommerce::ORDER_POST_TYPE);
	}

	protected function get_legacy_subquery(){
		global $wpdb;
		$status_cond = $this->filter == 'all' ? '' : $wpdb->prepare(" AND orders.post_status = %s", $this->filter);

		return $wpdb->prepare("SELECT SUM(IFNULL(meta.meta_value, 0)) AS item_num FROM $wpdb->posts AS orders
			LEFT JOIN ".$wpdb->prefix."woocommerce_order_items AS items ON orders.ID = items.order_id
			LEFT JOIN ".$wpdb->prefix."woocommerce_order_itemmeta AS meta ON meta.order_item_id = items.order_item_id AND meta.meta_key = '_qty'
			WHERE orders.post_type = %s".$status_cond."
			GROUP BY orders.ID", USIN_Woocommerce::ORDER_POST_TYPE);
	}

	protected function format_labels($data){

		foreach ($data as &$row ) {
			if($row->label != __('Other', 'usin')){
				$row->label .= ' '. _n( 'item', 'items', intval($row->label), 'usin' );
			}
		}

		return $data;
	}
}