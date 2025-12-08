<?php

class USIN_Woocommerce_Billing_Countries_Loader extends USIN_Woocommerce_Billing_Address_Loader {
	protected $countries = null;
	
	public function load_data(){
		$data = $this->load_address_data('country');

		foreach($data as &$row){
			$row->label = USIN_Woocommerce::get_wc_country_name_by_code($row->label);
		}

		return $data;
	}
}