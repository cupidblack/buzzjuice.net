<?php

class USIN_Woocommerce_Abandoned_Carts_Loader extends USIN_Period_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$date_select = USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('activity.meta_value');
		$empty_cart_value = 'a:1:{s:4:"cart";a:0:{}}';
		$today = current_time('Y-m-d');

		$query = $wpdb->prepare("SELECT COUNT(*) AS $this->total_col, $date_select AS $this->label_col FROM $wpdb->usermeta AS carts" .
			" INNER JOIN $wpdb->usermeta AS activity ON activity.user_id = carts.user_id AND activity.meta_key='wc_last_active'" .
			" WHERE carts.meta_key = %s AND carts.meta_value != '{$empty_cart_value}' AND carts.meta_value != '' AND carts.meta_value IS NOT NULL" .
			" AND $date_select >= %s AND $date_select <= %s AND DATE($date_select) != %s GROUP BY {$this->get_group_by()}",
			USIN_Woocommerce::get_persistent_cart_key(), $this->get_period_start(), $this->get_period_end(), $today);
		
		return $wpdb->get_results($query);
	}
}
