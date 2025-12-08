<?php
/**
 * The file that defines the core plugin class.
 *
 * @link       https://themehigh.com
 * @since      3.6.4
 *
 * @package    woocommerce-checkout-field-editor-pro
 * @subpackage woocommerce-checkout-field-editor-pro/block
 */
if(!defined('WPINC')){	die; }

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

if(!class_exists('THWCFE_Block')):

class THWCFE_Block {

    public $field_sets = array();

    public function init(){
        add_action('woocommerce_init', array($this, 'load_address_blocks'));
        add_action('woocommerce_blocks_loaded', array($this, 'define_block_hooks'));

        $order_data = new THWCFE_Block_Order_Data();
        $order_data->init();
    }

    /**************************************************
	******** Address Section Functionality - START ******
	***************************************************/
    public function load_address_blocks(){

       if(version_compare(THWCFE_Utils::get_wc_version(), '8.9.0', "<")){
            return;
       }
        $this->register_additional_address_fields();
        //add_filter('woocommerce_shared_settings', array($this, 'update_default_fields_data'), 999);
        add_action('woocommerce_blocks_checkout_block_registration', array($this, 'update_default_fields_data_with_block'), 999);
        add_action('woocommerce_validate_additional_field', array($this, 'validate_additional_field'), 10, 3);
        add_action('woocommerce_blocks_validate_location_address_fields', array($this, 'confirm_validator_fields'), 10, 3);
        add_filter('woocommerce_get_country_locale', array($this, 'update_address_fields_data'), 999);
        if($this->has_block_checkout()){
            add_filter('woocommerce_default_address_fields', array($this, 'update_default_fields_data'), 999);
        }
        
    }
    private function has_block_checkout() {
        $checkout_page_id = wc_get_page_id( 'checkout' );
        $has_block_checkout = $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id );
        return $has_block_checkout || apply_filters( 'thwcfe_woocommerce_blocks_has_block_checkout', false );
	}

    public function register_additional_address_fields(){

        if (!function_exists('woocommerce_register_additional_checkout_field')) {
            return;
        }
        $fieldset = $this->get_section_field_set('address');
        $default_address_fields = THWCFE_Utils_Block::get_default_block_section_fields('address');
        if (!is_array($fieldset) || !is_array($default_address_fields)) {
            return;
        }
        $remove_optional = apply_filters('thwcfe_remove_optional_label', false);
        $additional_fields = array_diff_key($fieldset, $default_address_fields);
        foreach ($additional_fields as $field_data) {
            // WooCommerce < 9.8.0 does not support required checkboxes
            if((version_compare(THWCFE_Utils::get_wc_version(), '9.8.0', "<")) && $field_data['type'] === 'checkbox') {
                $field_data['required'] = false;
            }
            // If the field is already registered, skip it     
			woocommerce_register_additional_checkout_field(
				array(
					'id'          => 'thwcfe-block/'.$field_data['name'],
					'label'       => $field_data['label'],
                    'optionalLabel' =>  $remove_optional ? $field_data['label'] : sprintf(
                        /* translators: %s Field label. */
                        __( '%s (optional)', 'woocommerce' ),
                        $field_data['label']
				    ),
					'placeholder' => $field_data['placeholder'],
					'location'    => 'address',
					'type'        => $field_data['type'],
					'required'    => $field_data['required'],
					'index'      => $field_data['priority'],
					'options'     =>  isset($field_data['options']) ? $this->get_field_options($field_data['options_object']) 
						: array()
					,
				)

			);

		} 
    }

    public function get_field_options($options){
		$field_options = array();
		foreach ($options as $option) {
			$field_options[] = array(
				'label' => $option['text'],
				'value' => $option['key'],
			);
		}
		return $field_options;
	}

    public function update_address_fields_data($locale){
 
       if(! function_exists('has_block') || ! has_block( 'woocommerce/checkout' )) {
            return $locale;
        }
        $change_default_address_fields = apply_filters('thwcfe_change_default_block_address_fields', true);
        if (!$change_default_address_fields) {
            return $locale;
        }
        $field_set = $this->get_section_field_set('address');
        $address_field_keys = array('address_1', 'postcode', 'city', 'state');
        $address_fields = array_intersect_key($field_set, array_flip($address_field_keys));

        foreach ($locale as $key => $value) {
            $this->update_locale_field($locale, $key, 'address_1', $address_fields);
            $this->update_locale_field($locale, $key, 'postcode', $address_fields);
            $this->update_locale_field($locale, $key, 'city', $address_fields);
            $this->update_locale_field($locale, $key, 'state', $address_fields);
        }
 
        return $locale;
    }

    private function update_locale_field(&$locale, $key, $field_name, $address_fields) {
        if (isset($address_fields[$field_name])) {
            $locale[$key][$field_name] = [
                'required' => $address_fields[$field_name]['required'] ?? true,
                'hidden'   => false,
            ];
        } else {
            $locale[$key][$field_name] = [
                'required' => false,
                'hidden'   => true,
            ];
        }
    }

    public function update_default_fields_data_with_block() {

		if (!class_exists('Automattic\WooCommerce\Blocks\Package') || !class_exists('Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry')|| !class_exists('Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields') ) {
            error_log('WooCommerce Blocks classes not found. Please ensure WooCommerce Blocks is installed and activated.');
            return;
        }

        $change_default_address_fields = apply_filters('thwcfe_change_default_block_address_fields', true);
        if (!$change_default_address_fields) {
            return;
        }

        $checkout_fields     = Package::container()->get( CheckoutFields::class );
		$asset_data_registry = Package::container()->get(AssetDataRegistry::class);
        $default_address_fields = THWCFE_Utils_Block::get_core_fields();
        $field_set = $this->get_section_field_set('address');
        $remove_optional = apply_filters('thwcfe_remove_optional_label', false);
        foreach( $default_address_fields as $key => &$field){
            if($key === 'email'){
                continue;
            }
            if (isset($field_set[$key])) {
                $field['index'] = $field_set[$key]['priority'] ?? $field['index'];
                $field['label'] = $field_set[$key]['label']?? $field['label'];
                if($remove_optional){
                    $field['optionalLabel'] = $field_set[$key]['label']?? $field['optionalLabel'];
                }else{
                    $field['optionalLabel'] = $field_set[$key]['label']? $field_set[$key]['label'].' (optional)' : $field['optionalLabel'];
                }
                $field['required'] = $field_set[$key]['required'] ?? $field['required'];
            } else {
                $field['hidden'] = true;
            }
        }
        unset($field);
        $asset_data_registry->add( 'defaultFields', array_merge($default_address_fields, $checkout_fields->get_additional_fields() ) );

    }

    public function update_default_fields_data($fields){

        $change_default_address_fields = apply_filters('thwcfe_change_default_block_address_fields', true);
        if (!$change_default_address_fields) {
            return;
        }

        $field_set = $this->get_section_field_set('address');
        foreach( $fields as $key => &$field){
            if($key === 'email'){
                continue;
            }
            if (isset($field_set[$key])) {
                $field['index'] = $field_set[$key]['priority'] ?? $field['index'];
                $field['label'] = $field_set[$key]['label']?? $field['label'];
                $field['required'] = $field_set[$key]['required'] ?? $field['required'];
            } else {
                $field['hidden'] = true;
                $field['required'] = false;
            }
        }
        unset($field);
        return $fields;
    }

    public function validate_additional_field(WP_Error $errors, $field_key, $field_value) {

        $field_set = $this->get_section_field_set('address');
        $key_parts = explode('thwcfe-block/', $field_key);
        $actual_field_key = isset($key_parts[1]) ? $key_parts[1] : null;
    
        if (empty($actual_field_key) || !isset($field_set[$actual_field_key])) {
            return $errors;
        }

        $field_properties = $field_set[$actual_field_key];

        if (empty($field_properties['validate'])) {
            return $errors;
        }

        $advanced_settings = THWCFE_Utils::get_advanced_settings();
		$custom_validators = $advanced_settings['custom_validators'] ?? [];

        foreach ((array)$field_properties['validate'] as $rule) {
            switch ($rule) {
                case 'email':
                    if (!empty($field_value) && !is_email($field_value)) {
                        $errors->add(
                            'invalid_email_field',
                            sprintf(
                                __('The provided %s is not a valid email address.', 'woocommerce'),
                                esc_html($field_properties['title'] ?? 'value')
                            )
                        );
                    }
                    break;
    
                case 'phone':
                    if (!empty($field_value) && !\WC_Validation::is_phone($field_value)) {
                        $errors->add(
                            'invalid_phone_field',
                            sprintf(
                                __('The provided %s is not a valid phone number.', 'woocommerce'),
                                esc_html($field_properties['title'] ?? 'value')
                            )
                        );
                    }
                    break;
    
                case 'postcode':
                    if (!empty($field_value) && !\WC_Validation::is_postcode($field_value)) {
                        $errors->add(
                            'invalid_postcode',
                            sprintf(
                                __('The provided %s is not a valid postcode.', 'woocommerce'),
                                esc_html($field_properties['title'] ?? 'value')
                            )
                        );
                    }
                    break;
    
                default:
                    if (isset($custom_validators[$rule])) {
                        $validator = $custom_validators[$rule];
                        if (!$this->validate_pattern($field_value, $validator)) {
                            $errors->add(
                                'invalid_format',
                                $validator['message'] ?? sprintf(
                                    __('The provided %s is not valid format.', 'woocommerce'),
                                    esc_html($field_properties['title'] ?? 'value')
                                )
                                
                            );
                        }
                    }

                    break;
            }
        }
        return $errors;
    }

    public function confirm_validator_fields(\WP_Error $errors, $fields, $group){
       
        $advanced_settings = THWCFE_Utils::get_advanced_settings();
        $confirm_validators = $advanced_settings['confirm_validators'] ?? [];
        if (empty($confirm_validators)) {
            return;
        }

        $field_set = $this->get_section_field_set('address');
        if (!is_array($field_set)) {
            return;
        }

            
        foreach( $field_set as $key => $field){
            $field_properties = $field_set[$key]? $field_set[$key] : array();
            if (empty($field_properties['validate'])) {
                continue;
            }
                    
            foreach ((array)$field_properties['validate'] as $rule) {
                        
                if (isset($confirm_validators[$rule])) {
                    $validator = $confirm_validators[$rule];
                    $match_field = $validator['pattern']? $validator['pattern'] : null;
                    if($match_field && isset($fields[$match_field]) && isset($fields['thwcfe-block/'.$key])){
                        $match_field_value = $fields[$match_field];
                        if($match_field_value !== $fields['thwcfe-block/'.$key]){
                            $errors->add(
                                'field_mismatch',
                                $validator['message'] ?? sprintf(
                                    __('The provided %s does not match the %s.', 'woocommerce'),
                                    esc_html($field_properties['title'] ?? 'value'),
                                    esc_html($field_set[$match_field]['title'] ?? 'value')
                                )
                            );
                        }
                    }
                }

            }

        }  
    }

    private function validate_pattern($field_value, array $validator): bool {
        if (empty($validator['pattern'])) {
            return false;
        }

        $pattern = $validator['pattern'];
        // Ensure pattern has delimiters
        if (substr($pattern, 0, 1) !== '#') {
            $pattern = '#' . $pattern . '#';
        }

        try {
            $result = preg_match($pattern, $field_value);
            return $result;
        } catch (Exception $e) {
            error_log('Validation pattern error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function get_section_field_set($section_name){

        $section = THWCFE_Utils_Block::get_block_checkout_section($section_name);
      	$fieldset = THWCFE_Utils_Section::get_fieldset($section);
		return $fieldset;
    }

    /**************************************************
	******** Address Section Functionality - END ******
	***************************************************/

    /**************************************************
	******** Additional Sections- START ******
	***************************************************/

    public function define_block_hooks(){

        if(version_compare(THWCFE_Utils::get_wc_version(), '8.9.0', "<")){
            return;
        }

        add_action('woocommerce_blocks_checkout_block_registration' , array($this, 'register_block_integration'));
        THWCFE_Block_Extend_Store_Endpoint::init();
        add_action('woocommerce_store_api_checkout_update_order_from_request', array($this, 'store_api_checkout_update_order_from_request'),10,2);
       
        // Register the callback for the custom cart fee  Fee calculation
       woocommerce_store_api_register_update_callback([
            'namespace' => 'thwcfe-custom-cart-fee',
            'callback'  => [$this, 'process_additional_cart_fee']
        ]);

        add_action('woocommerce_cart_calculate_fees', [$this, 'add_custom_fee']);
        add_action('woocommerce_checkout_order_processed', [$this, 'cleanup_fee_session']);
        add_action('woocommerce_cart_emptied', [$this, 'cleanup_fee_session']);
        //cleanup session on checkout reload
        add_action('template_redirect', [$this, 'cleanup_fee_session_on_checkout_reload']);
    }

    public function register_block_integration($integration_registry){
       
		$integration_registry->register( new THWCFE_Block_Integration() );
	}

    public function store_api_checkout_update_order_from_request( \WC_Order $order, \WP_REST_Request $request ){
 
		$request_data  = $request['extensions']['thwcfe-additional-fields'] ?? array();
        
        if (empty($request_data)) {
            return;
        }
        
        $sections = THWCFE_Utils_Block::get_block_checkout_sections();
        if (empty($sections)) {
            return;
        }

        $order_meta_updates = array();
        $user_meta_updates = array();
		foreach ($request_data as $section_key => $section_fields) {
            if (!isset($sections[$section_key]) || empty($section_fields)) {
                continue;
            }
            $section = $sections[$section_key];
            
            
            if (empty($section->fields)) {
                continue;
            }
            $order_meta_fields = array();
            foreach($section_fields as $field_key => $field_value){
                if (!isset($section->fields[$field_key])) {
                    continue;
                }
                $field = $section->fields[$field_key];
                if($field->type && $field->type === 'datepicker' && $field_value){
                    $field_value = $this->convert_to_wp_timezone($field_value);
                }

                if(is_array($field_value)){
                    $field_value = implode(', ', $field_value);
                }
                if (($field->property_set['order_meta'])) {
                    $order_meta_updates[$field_key] = $field_value;
                    $order_meta_fields[$field_key] = $field_value;
                }
                if (($field->property_set['user_meta'])) {
                    $user_meta_updates[$field_key] = $field_value;
                }
                
            }
            $order_meta_updates[$section_key] = $order_meta_fields;
		}
        if (!empty($order_meta_updates)) {
            foreach ($order_meta_updates as $key => $value) {
                $order->update_meta_data($key, $value);
            }
            $order->save();
        }
        if (!empty($user_meta_updates)) {
            $user_id = $order->get_user_id();
            foreach ($order_meta_updates as $key => $value) {
                update_user_meta($user_id, $key, $value );
            }
        }
	}

    /**** Fee Calculation started */

    public function process_additional_cart_fee($data) {
       
        // Make sure WC session is available
        if (!WC()->session) {
            return rest_ensure_response(['success' => false, 'message' => 'WC Session not available']);
        }

        $fees = WC()->session->get('thwcfe_fees') ?: [];
        
        // Add or update this specific fee only if value exists and isn't null
        $field_id = $data['priceFieldProps']['name'];
        if (isset($data['priceFieldProps']['value']) && $data['priceFieldProps']['value']) {
            $fees[$field_id] = $data['priceFieldProps'];
        } else {
            if (isset($fees[$field_id])) {
                unset($fees[$field_id]);
            }
        }

        WC()->session->set('thwcfe_fees', $fees);
        // Force cart recalculation
        WC()->cart->calculate_totals();
        return rest_ensure_response(['success' => true]);        
    }
    
    public function add_custom_fee($cart) {

        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!WC()->session) {
            return;
        }
        if(is_cart() || has_block('woocommerce/cart')){
            WC()->session->__unset('thwcfe_fees');
            return; 
        }
        
        $fees = WC()->session->get('thwcfe_fees');
        //$extra_fees = $this->get_extra_cost_data($fees, $cart);
        $this->calculate_fees($fees, $cart);
        
        // if ($fees && is_array($fees)) {
        //     // Loop through and add each fee
        //     foreach ($fees as $field_id => $fee_data) {
        //         if ($fee_data['amount'] > 0) {
        //             $cart->add_fee(__($fee_data['label'], 'woocommerce'), $fee_data['amount']);
        //         }
        //     }
        // }
    }
   
    public function calculate_fees($extra_cost, $cart) {

        if (empty($extra_cost)) {
            return;
        }
        
        foreach($extra_cost as $name => $price_info){
            $taxable = isset($price_info['taxable']) && $price_info['taxable'] === 'yes' ? true : false;
			$tax_class = isset($price_info['tax_class']) && !empty($price_info['tax_class']) ? trim($price_info['tax_class']) : '';
            $fee = $this->calculate_extra_cost($price_info, $cart);

            if($fee != 0 || apply_filters('thwcfe_show_zero_fee', false, $fee)){

                //Need to add for woocommerce subscriptions
                /*if(!empty($cart->recurring_cart_key) && apply_filters('thwcfe_wc_subscriptions_recurring_fee', true, $name, $price_info)){
                    $cart->add_fee($price_info['label'], $fee, $taxable, $tax_class);
                }else{
                    WC()->cart->add_fee($price_info['label'], $fee, $taxable, $tax_class);
                }*/
                $cart->add_fee(__($price_info['label'],'woocommerce_checkout_field_editor' ), $fee, $taxable, $tax_class);
            }

        }
    }

    public function calculate_extra_cost($price_info, $cart){
        $fprice = 0;
        $price_type = isset($price_info['price_type']) ? $price_info['price_type'] : '';
        $price 		= isset($price_info['price']) ? $price_info['price'] : 0;
        $multiple   = isset($price_info['multiple']) ? $price_info['multiple'] : 0;
        $name 		= isset($price_info['name']) && !empty($price_info['name']) ? $price_info['name'] : false;
        $value 		= isset($price_info['value']) ? $price_info['value'] : false;

        if($name){
          $price = apply_filters('thwcfe_checkout_field_extra_price_'.$name, $price, $value);
        }
        $cart_total = $cart->get_cart_contents_total();
        if($price_type === 'percentage_subtotal'){
                $cart_total = $cart->subtotal;
                //$cart_total = $cart->get_subtotal_tax()+$cart->subtotal();
        }else if($price_type === 'percentage_subtotal_ex_tax'){
          $cart_total = $cart->subtotal_ex_tax;
        }
    
        /***Add multiple option when multiselect field introduced ***/
		// if($multiple == 1){
		// 	$price_arr = explode(",", $price);
		// 	$price_type_arr = explode(",", $price_type);

		// 	foreach($price_arr as $index => $oprice){
		// 		$oprice_type = isset($price_type_arr[$index]) ? $price_type_arr[$index] : 'normal';

		// 		if($oprice_type === 'percentage' || $oprice_type === 'percentage_subtotal' || $oprice_type === 'percentage_subtotal_ex_tax'){
		// 			if(is_numeric($oprice) && is_numeric($cart_total)){
		// 				$fprice = $fprice + ($oprice/100)*$cart_total;
		// 			}
		// 		}else{
		// 			if(is_numeric($oprice)){
		// 				$fprice = $fprice + $oprice;
		// 			}
		// 		}
		// 	}
		// }else{
		//}

        if($price_type === 'percentage' || $price_type === 'percentage_subtotal' || $price_type === 'percentage_subtotal_ex_tax'){
            if(is_numeric($price) && is_numeric($cart_total)){
                $fprice = ($price/100)*$cart_total;
            }
        }else if($price_type === 'dynamic'){
            $price_unit = isset($price_info['price_unit']) ? $price_info['price_unit'] : false;

            $qty   = isset($price_info['qty_field']) ? $price_info['qty_field'] : false;
            $qty   = apply_filters('thwcfe_dynamic_price_quantity', $qty, $name);
            $value = !empty($qty) && is_numeric($qty) ? $qty : $value;

            if(is_numeric($price) && is_numeric($value) && is_numeric($price_unit) && $price_unit > 0){
                $fprice = $price*($value/$price_unit);
            }
        }else if($price_type === 'custom'){
            if(is_numeric($value)){
                $fprice = $value;
            }
        }else{
            if(is_numeric($price)){
                $fprice = $price;
            }
        }

		if($name){
			$fprice = apply_filters('thwcfe_checkout_field_extra_cost_'.$name, $fprice, $value);
			$fprice = apply_filters('thwcfe_checkout_field_extra_cost', $fprice, $name, $value);
		}

		return $fprice;
	}

    public function cleanup_fee_session() {
        // Make sure WC session is available
        if (WC()->session) {
            WC()->session->__unset('thwcfe_fees');
        }
    }

    public function cleanup_fee_session_on_checkout_reload() {

        if (is_checkout() && has_block('woocommerce/checkout')) {
            $this->cleanup_fee_session();
        }
    }

    private function convert_to_wp_timezone($utc_time_str){
        $datetime = new DateTime($utc_time_str, new DateTimeZone('UTC'));
        $site_timezone = wp_timezone();
        $datetime->setTimezone($site_timezone);
        $site_time_str = wp_date('Y-m-d H:i:s', $datetime->getTimestamp());
        return $site_time_str;
    }

}
endif;