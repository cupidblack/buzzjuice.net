<?php

class USIN_Woocommerce_Origin_Types_Loader extends USIN_Woocommerce_Order_Meta_Loader {

	public function load_data(){
		return $this->load_data_for_key('_wc_order_attribution_source_type', true);
	}
}