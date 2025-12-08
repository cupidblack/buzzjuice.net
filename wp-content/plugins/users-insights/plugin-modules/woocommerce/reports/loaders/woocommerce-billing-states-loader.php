<?php

class USIN_Woocommerce_Billing_States_Loader extends USIN_Woocommerce_Billing_Address_Loader {

	public function load_data(){
		return $this->load_address_data('state');
	}
}