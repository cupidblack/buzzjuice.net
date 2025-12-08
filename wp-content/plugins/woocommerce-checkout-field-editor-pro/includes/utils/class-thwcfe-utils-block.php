<?php
/**
 * The common utility functionalities for the plugin.
 *
 * @link       https://themehigh.com
 * @since      3.6.4
 *
 * @package    woocommerce-checkout-field-editor-pro
 * @subpackage woocommerce-checkout-field-editor-pro/public
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWCFE_Utils_Block')):

class THWCFE_Utils_Block {


	const OPTION_KEY_BLOCK_SECTIONS     = 'thwcfe_block_sections';
	private $core_fields;
	private $fields_locations;

	public static function get_block_sections(){
		//$sections = get_option(self::OPTION_KEY_CUSTOM_SECTIONS);
		//return empty($sections) ? false : $sections;
		
		self::get_default_block_sections();
	}

	public static function get_default_block_fields(){

		$core_fields         = array(

			'contact' => array(
				'email'      => [
					'name'           => 'email',
					'label'          => __( 'Email address', 'woocommerce' ),
					'optionalLabel'  => __(
						'Email address (optional)',
						'woocommerce'
					),
					'required'       => true,
					'hidden'         => false,
					'autocomplete'   => 'email',
					'autocapitalize' => 'none',
					'index'          => 0,
					'type'           => 'email'
				],
			),
			'address'  =>  array(
				
				'country'    => [
					'name'         => 'country',
					'label'         => __( 'Country/Region', 'woocommerce' ),
					'optionalLabel' => __(
						'Country/Region (optional)',
						'woocommerce'
					),
					'required'      => true,
					'hidden'        => false,
					'autocomplete'  => 'country',
					'index'         => 1,
					'type'          => 'country',
				],
				
				'first_name' => [
					'label'          => __( 'First name', 'woocommerce' ),
					'optionalLabel'  => __(
						'First name (optional)',
						'woocommerce'
					),
					'required'       => true,
					'hidden'         => false,
					'autocomplete'   => 'given-name',
					'autocapitalize' => 'sentences',
					'index'          => 10,
				],
				'last_name'  => [
					'label'          => __( 'Last name', 'woocommerce' ),
					'optionalLabel'  => __(
						'Last name (optional)',
						'woocommerce'
					),
					'required'       => true,
					'hidden'         => false,
					'autocomplete'   => 'family-name',
					'autocapitalize' => 'sentences',
					'index'          => 20,
				],
				
				'company'    => [
					'label'          => __( 'Company', 'woocommerce' ),
					'optionalLabel'  => __(
						'Company (optional)',
						'woocommerce'
					),
					'required'       => false,
					'hidden'         => false,
					'autocomplete'   => 'organization',
					'autocapitalize' => 'sentences',
					'index'          => 30,
				],
				'address_1'  => [
					'label'          => __( 'Address', 'woocommerce' ),
					'optionalLabel'  => __(
						'Address (optional)',
						'woocommerce'
					),
					'required'       => true,
					'hidden'         => false,
					'autocomplete'   => 'address-line1',
					'autocapitalize' => 'sentences',
					'index'          => 40,
				],
				'address_2'  => [
					'label'          => __( 'Apartment, suite, etc.', 'woocommerce' ),
					'optionalLabel'  => __(
						'Apartment, suite, etc. (optional)',
						'woocommerce'
					),
					'required'       => false,
					'hidden'         => false,
					'autocomplete'   => 'address-line2',
					'autocapitalize' => 'sentences',
					'index'          => 50,
				],
				'city'       => [
					'label'          => __( 'City', 'woocommerce' ),
					'optionalLabel'  => __(
						'City (optional)',
						'woocommerce'
					),
					'required'       => true,
					'hidden'         => false,
					'autocomplete'   => 'address-level2',
					'autocapitalize' => 'sentences',
					'index'          => 70,
				],
				'state'      => [
					'label'          => __( 'State/County', 'woocommerce' ),
					'optionalLabel'  => __(
						'State/County (optional)',
						'woocommerce'
					),
					'required'       => true,
					'hidden'         => false,
					'autocomplete'   => 'address-level1',
					'autocapitalize' => 'sentences',
					'index'          => 80,
					'type'           => 'state'
				],
				'phone'      => [
					'label'          => __( 'Phone', 'woocommerce' ),
					'optionalLabel'  => __(
						'Phone (optional)',
						'woocommerce'
					),
					'required'       => false,
					'hidden'         => false,
					'type'           => 'tel',
					'autocomplete'   => 'tel',
					'autocapitalize' => 'characters',
					'index'          => 80,
				],
				'postcode'   => [
					'label'          => __( 'Postal code', 'woocommerce' ),
					'optionalLabel'  => __(
						'Postal code (optional)',
						'woocommerce'
					),
					'required'       => true,
					'hidden'         => false,
					'autocomplete'   => 'postal-code',
					'autocapitalize' => 'characters',
					'index'          => 90,
				],
			),

			'order'    => array(
				
			),
		);

			// $fields_locations = [
			// 	// omit email from shipping and billing fields.
			// 	'address' => array_merge( \array_diff_key( array_keys( $core_fields ), array( 'email' ) ) ),
			// 	'contact' => array( 'email' ),
			// 	'order'   => [],
			// ];

		return $core_fields ;
	}
	public static function get_default_block_section_fields($section_name){
		$core_fields = self::get_default_block_fields();
		if(isset($section_name) && !empty($section_name)){
			if(isset($core_fields[$section_name])){
				return $core_fields[$section_name];
			}
		}
		return false;
	}

	public static function get_block_checkout_sections(){
		$sections = get_option(self::OPTION_KEY_BLOCK_SECTIONS);
		return empty($sections) ? self::get_default_block_sections() : $sections;
	}

	public static function get_default_block_sections(){

		$checkout_fields = self::get_default_block_fields();
		$default_sections = array( 'contact' => 'Contact Information', 'address' => 'Address',  'order' => 'Additional order information');
		$default_sections = apply_filters('thwcfe_default_checkout_sections', $default_sections);

		$sections = array();
		$order = -3;
		foreach($checkout_fields as $fieldset => $fields){
			//$fieldset = $fieldset && $fieldset === 'order' ? 'additional' : $fieldset;
			$title = isset($default_sections[$fieldset]) ? $default_sections[$fieldset] : '';

			$section = new WCFE_Checkout_Section();
			$section->set_property('id', $fieldset);
			$section->set_property('name', $fieldset);
			$section->set_property('order', $order);
			$section->set_property('title', $title);
			$section->set_property('custom_section', 0);
			$section->set_property('fields', self::prepare_default_fields($fields));

			$sections[$fieldset] = $section;
			$order++;
		}
		
		return $sections;
	}

	public static function get_block_field_set(){
		
	}

	public static function get_block_checkout_section($section_name, $cart_info=false){
	 	if(isset($section_name) && !empty($section_name)){
			$sections = self::get_block_checkout_sections();
			if(is_array($sections) && isset($sections[$section_name])){
				$section = $sections[$section_name];
				//if(THWCFE_Utils_Section::is_valid_section($section) && THWCFE_Utils_Section::is_show_section($section, $cart_info)){
					return $section;
				//}
			}
		}
		return false;
	}

	public static function prepare_default_fields($fields){
		$field_objects = array();
		$default_fields_id = array(
					'first_name' => array(
						'label'          => 'First name',
					),
					'last_name'  => array(
						'label'          => 'Last name',
					),
					'company'    => array(
						'label'          => 'Company name',
					),
					'country'    => array(
						'label'          =>  'Country / Region',
					),	
					'address_1'  => array(
						'label'          => 'Street address',
						'placeholder'  	 => 'House number and street name',
					),
					'address_2'  => array(
						'label'        => 'Apartment, suite, unit, etc.',
						'placeholder'  => 'Apartment, suite, unit, etc. (optional)',
					),
					'city'       => array(
						'label'        => 'Town / City',
					),
					'state'      => array(
						'label'        => 'State / County',
					),
					'postcode'   => array(
						'label'        => 'Postcode / ZIP',
					),
					'email' => array(
						'label' => 'Email Address',
					)
				);

		if(is_array($fields)){
			foreach($fields as $name => $field){
				if(!empty($name) && !empty($field) && is_array($field)){
					$field['type'] = isset($field['type']) ? $field['type'] : 'text';
					$field_object = THWCFE_Utils_Field::create_field($field['type'], $name, $field); 

					if(array_key_exists($name, $default_fields_id) && is_object($field_object)){
						$field_object->title = $default_fields_id[$name]['label'];
						if($field_object->placeholder != '' && isset($default_fields_id[$name]['placeholder'])){
							$field_object->placeholder = $default_fields_id[$name]['placeholder'];
						}
					}
					if(($name === 'billing_state' || $name === 'shipping_state') && isset($field['country'])){
						$field_object->set_property('country', '');
					}
				
					if($field_object){
						$field_objects[$name] = $field_object;
					}
				}
			}
		}
		return $field_objects;
	}

	public static function get_core_fields() {
		
		return [
			'email'      => [
				'label'          => __( 'Email address', 'woocommerce' ),
				'optionalLabel'  => __(
					'Email address (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'email',
				'autocapitalize' => 'none',
				'type'           => 'email',
				'index'          => 0,
			],
			'country'    => [
				'label'         => __( 'Country/Region', 'woocommerce' ),
				'optionalLabel' => __(
					'Country/Region (optional)',
					'woocommerce'
				),
				'required'      => true,
				'hidden'        => false,
				'autocomplete'  => 'country',
				'index'         => 1,
			],
			'first_name' => [
				'label'          => __( 'First name', 'woocommerce' ),
				'optionalLabel'  => __(
					'First name (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'given-name',
				'autocapitalize' => 'sentences',
				'index'          => 10,
			],
			'last_name'  => [
				'label'          => __( 'Last name', 'woocommerce' ),
				'optionalLabel'  => __(
					'Last name (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'family-name',
				'autocapitalize' => 'sentences',
				'index'          => 20,
			],
			'company'    => [
				'label'          => __( 'Company', 'woocommerce' ),
				'optionalLabel'  => __(
					'Company (optional)',
					'woocommerce'
				),
				'required'       => false,
				'hidden'         => false,
				'autocomplete'   => 'organization',
				'autocapitalize' => 'sentences',
				'index'          => 30,
			],
			'address_1'  => [
				'label'          => __( 'Address', 'woocommerce' ),
				'optionalLabel'  => __(
					'Address (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-line1',
				'autocapitalize' => 'sentences',
				'index'          => 40,
			],
			'address_2'  => [
				'label'          => __( 'Apartment, suite, etc.', 'woocommerce' ),
				'optionalLabel'  => __(
					'Apartment, suite, etc. (optional)',
					'woocommerce'
				),
				'required'       => false,
				'hidden'         => false,
				'autocomplete'   => 'address-line2',
				'autocapitalize' => 'sentences',
				'index'          => 50,
			],
			'city'       => [
				'label'          => __( 'City', 'woocommerce' ),
				'optionalLabel'  => __(
					'City (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-level2',
				'autocapitalize' => 'sentences',
				'index'          => 70,
			],
			'state'      => [
				'label'          => __( 'State/County', 'woocommerce' ),
				'optionalLabel'  => __(
					'State/County (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-level1',
				'autocapitalize' => 'sentences',
				'index'          => 80,
			],
			'postcode'   => [
				'label'          => __( 'Postal code', 'woocommerce' ),
				'optionalLabel'  => __(
					'Postal code (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'postal-code',
				'autocapitalize' => 'characters',
				'index'          => 90,
			],
			'phone'      => [
				'label'          => __( 'Phone', 'woocommerce' ),
				'optionalLabel'  => __(
					'Phone (optional)',
					'woocommerce'
				),
				'required'       => false,
				'hidden'         => false,
				'type'           => 'tel',
				'autocomplete'   => 'tel',
				'autocapitalize' => 'characters',
				'index'          => 100,
			],
		];
		
	}

	public static function get_all_custom_block_checkout_fields(){
		$fields = array();
		$sections = self::get_block_checkout_sections();	
		if($sections){
			foreach($sections as $sname => $section){	
				if($sname === 'address'){
					// Skip the address section as it is already handled by WooCommerce
					continue;
				}
				$temp_fields = THWCFE_Utils_Section::get_fields($section);
				if($temp_fields && is_array($temp_fields)){
					foreach($temp_fields as $key => $field){
						if(THWCFE_Utils_Field::is_custom_field($field) && THWCFE_Utils_Field::is_enabled($field)){
							$fields[$key] = $field;
						}
					}
				}
			}
			return $fields;
		}
	}

	
}

endif;

