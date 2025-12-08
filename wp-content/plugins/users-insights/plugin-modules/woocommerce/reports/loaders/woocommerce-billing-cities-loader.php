<?php

class USIN_Woocommerce_Billing_Cities_Loader extends USIN_Woocommerce_Billing_Address_Loader {
	public function load_data(){
		return $this->load_address_data('city');
	}
}