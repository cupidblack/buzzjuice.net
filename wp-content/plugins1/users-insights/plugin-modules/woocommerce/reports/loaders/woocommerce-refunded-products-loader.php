<?php

class USIN_Woocommerce_Refunded_Products_Loader extends USIN_Woocommerce_Ordered_Products_Loader {

	protected function load_data() {
		return $this->get_results('wc-refunded');
	}

}