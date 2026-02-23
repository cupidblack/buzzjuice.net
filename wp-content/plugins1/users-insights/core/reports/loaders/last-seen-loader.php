<?php

class USIN_Last_Seen_Loader extends USIN_Report_Loader {

	protected function load_data(){
		global $wpdb;
		$data = array();
		$periods = $this->get_periods();

		$today_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}usin_user_data WHERE DATE(last_seen) = %s", current_time('Y-m-d')));
		$data[] = (object)array('total' => $today_total, 'label' => __('Today', 'usin'));

		foreach($periods as $period_key => $period_name){
			$condition = USIN_Period::generate_condition($period_key, 'last_seen');
			$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}usin_user_data WHERE 1=1 $condition");
			$data[] = (object)array('total' => $total, 'label' => $period_name);
		}

		return $data;
	}

	protected function get_periods(){
		$keys = array(USIN_Period::LAST_7_DAYS, USIN_Period::LAST_30_DAYS, USIN_Period::LAST_6_MONTHS, USIN_Period::LAST_12_MONTHS);

		return USIN_Period::get_period_names($keys);
	}
}