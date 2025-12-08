<?php

class USIN_Woocommerce_Billing_Address_Loader extends USIN_Standard_Report_Loader {

	public function load_data(){
		return $this->load_post_meta_data('_billing_city', true, USIN_Woocommerce::ORDER_POST_TYPE);
	}

	protected function load_address_data($property){
		if(USIN_Woocommerce::custom_order_tables_enabled()){
			// load COT data
			global $wpdb;
			$query = $wpdb->prepare("SELECT COUNT(*) as $this->total_col, $property as $this->label_col FROM {$wpdb->prefix}wc_order_addresses AS a " .
				"INNER JOIN {$wpdb->prefix}wc_orders AS o ON a.order_id = o.id AND o.type = %s " .
				"WHERE a.address_type = 'billing' AND $property IS NOT NULL AND $property != '' " .
				"GROUP BY $this->label_col ORDER BY $this->total_col DESC LIMIT $this->max_items", USIN_Woocommerce::ORDER_POST_TYPE);
			return $wpdb->get_results($query);
		}else{
			// load legacy data
			return $this->load_post_meta_data("_billing_{$property}", true, USIN_Woocommerce::ORDER_POST_TYPE);
		}
	}
}