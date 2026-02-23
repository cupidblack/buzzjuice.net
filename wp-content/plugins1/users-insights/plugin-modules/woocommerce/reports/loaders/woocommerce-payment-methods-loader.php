<?php

class USIN_Woocommerce_Payment_Methods_Loader extends USIN_Standard_Report_Loader {

	public function load_data(){
		return USIN_Woocommerce::custom_order_tables_enabled() ? $this->load_cot_data() : $this->load_legacy_data();
	}

	protected function load_cot_data(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, USIN_Query_Helper::get_gmt_offset_date_select('orders.date_created_gmt'));

		$query = $wpdb->prepare("SELECT COUNT(*) as $this->total_col, payment_method as $this->label_col FROM {$wpdb->prefix}wc_orders AS orders" .
			" WHERE type = %s AND payment_method IS NOT NULL AND payment_method != '' " .$period_condition.
			" GROUP BY payment_method ORDER BY $this->total_col DESC", USIN_Woocommerce::ORDER_POST_TYPE);

		$data = $wpdb->get_results($query);
		return $this->apply_gateway_names($data);
	}

	protected function apply_gateway_names($data){
		$gateways = WC()->payment_gateways->payment_gateways();

		if(empty($gateways)){
			return $data;
		}

		foreach($data as $row){
			if(isset($gateways[$row->label])){
				$row->label = $gateways[$row->label]->get_title();
			}
		}

		return $data;
	}

	protected function load_legacy_data(){
		global $wpdb;

		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter, 'orders.post_date');

		$query = $wpdb->prepare("SELECT COUNT(*) as $this->total_col, meta_value as $this->label_col FROM $wpdb->postmeta AS methods" .
			" INNER JOIN $wpdb->posts AS orders ON orders.ID = methods.post_id AND orders.post_type = %s" .
			" WHERE meta_key = '_payment_method_title' AND meta_value != ''".$period_condition.
			" GROUP BY meta_value ORDER BY $this->total_col DESC",
			USIN_Woocommerce::ORDER_POST_TYPE);

		return $wpdb->get_results($query);
	}
}