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

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
if(!class_exists('THWCFE_Block_Integration')):

class THWCFE_Block_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {

		return 'thwcfe-block-integration';
	}
	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {

		$this->register_contact_info_block_frontend_scripts();
		$this->register_contact_info_block_editor_scripts();
		$this->register_additional_sections_editor_scripts();
		$this->register_additional_sections_frontend_scripts();
		
		//$this->register_main_integration();
		add_action('wp_enqueue_scripts', array($this, 'register_styles'));
		add_action('admin_enqueue_scripts', array($this, 'register_styles'));
	}

	public function register_styles(){

		if (function_exists('has_block') && has_block('woocommerce/checkout')) {
			//$this->register_contact_info_block_frontend_styles();
			//$this->register_contact_info_block_editor_styles();
			//$this->register_additional_sections_frontend_styles();
			//$this->register_additional_sections_editor_styles();
			$this->register_block_styles();
		}
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {

		//$script_handles = ['thwcfe-block-integration', 'thwcfe-contact-info-section-frontend', 'thwcfe-additional-sections-frontend'];
		$script_handles = ['thwcfe-additional-sections-frontend', 'thwcfe-contact-info-section-frontend'];
        return $script_handles;
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		$script_edit_handles = ['thwcfe-additional-sections-editor', 'thwcfe-contact-info-section-editor'];	
		return $script_edit_handles;
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {

		$advanced_settings = THWCFE_Utils::get_advanced_settings();
		$custom_validators = $advanced_settings['custom_validators'] ?? [];
		$confirm_validators = $advanced_settings['confirm_validators'] ?? [];
		$all_sections = THWCFE_Utils_Block::get_block_checkout_sections();
		/**  Translation helper method caller **/
		if (apply_filters('thwcfe_translate_block_field_dynamic_strings', false)) {
			$this->translate_block_field_dynamic_strings($all_sections);
		}
		$default_keys = ['contact', 'address', 'order'];
		$additional_sections = array_diff_key($all_sections, array_flip($default_keys));

		$cart_items_category_data = [];
        if ( function_exists( 'WC' ) &&  WC()->cart ) {
            $cart_items = WC()->cart->get_cart();
            
            foreach (WC()->cart->get_cart() as $cart_item_id => $cart_item) {
                $product_id = $cart_item['product_id'];
                $variation_id = $cart_item['variation_id'] ?? 0;
                $category_ids = [];
				$category_slugs = [];
                $terms = get_the_terms($product_id, 'product_cat');
                if ($terms && ! is_wp_error($terms)) {
                    $category_slugs = wp_list_pluck($terms, 'slug');
                }
                $cart_items_category_data[$cart_item_id] = [
                    'product_id'   => $product_id,
                    'variation_id' => $variation_id,
                    'category_slugs' => $category_slugs,
                ];
            }
            
        }

		$data = [
			'additionalSections' => $additional_sections,
			'allSections' => $all_sections,
			'customValidators' => $custom_validators,
			'confirmValidators' => $confirm_validators,
			'cartItemsCategoryData' => $cart_items_category_data,
		];
		return $data;
	}

     /**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return THWCFE_VERSION;
	}

	/**
     * Returns an array of style handles to enqueue in the frontend context.
     *
     * @return string[]
     */
	/*public function register_contact_info_block_editor_styles(){

		$style_path = '/assets/dist/style-contact-info-section.css';
		$style_url = plugins_url( $style_path, __FILE__ );
		$absolutepath = dirname( __FILE__ ) . '/assets/dist/style-contact-info-section.css';
		wp_enqueue_style(
			'thwcfe-contact-info-section-editor',
			$style_url,
			[],
			$this->get_file_version( $absolutepath )
		);
	}*/

	public function register_contact_info_block_editor_scripts() {

		$script_path       = '/assets/dist/contact-info-section.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/assets/dist/contact-info-section.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'thwcfe-contact-info-section-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'thwcfe-contact-info-section-editor',
			'woocommerce-checkout-field-editor-pro',
			dirname( __FILE__ ) . '/languages'
		);
	}

	
	// public function register_contact_info_block_frontend_styles(){
	// 	$style_path = '/assets/dist/style-contact-info-section-frontend.css';
	// 	$style_url = plugins_url( $style_path, __FILE__ );
	// 	$absolutepath = dirname( __FILE__ ) . '/assets/dist/style-contact-info-section-frontend.css';
	// 	wp_enqueue_style(
	// 		'thwcfe-contact-info-section-frontend',
	// 		$style_url,
	// 		[],
	// 		$this->get_file_version( $absolutepath )
	// 	);
	// }

	public function register_contact_info_block_frontend_scripts() {

		$script_path       = '/assets/dist/contact-info-section-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/assets/dist/contact-info-section-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'thwcfe-contact-info-section-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'thwcfe-contact-info-section-frontend',
			'woocommerce-checkout-field-editor-pro',
			dirname( __FILE__ ) . '/languages'
		);
	}

	public function register_additional_sections_editor_scripts(){

		$script_path       = '/assets/dist/additional-sections.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/assets/dist/additional-sections.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'thwcfe-additional-sections-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'thwcfe-additional-sections-editor',
			'woocommerce-checkout-field-editor-pro',
			dirname( __FILE__ ) . '/languages'
		);
	}

	// public function register_additional_sections_editor_styles(){

	// 	$style_path = '/assets/dist/style-additional-sections.css';
	// 	$style_url = plugins_url( $style_path, __FILE__ );
	// 	$absolutepath = dirname( __FILE__ ) . '/assets/dist/style-additional-sections.css';
	// 	wp_enqueue_style(
	// 		'thwcfe-additional-sections-editor',
	// 		$style_url,
	// 		[],
	// 		$this->get_file_version( $absolutepath )
	// 	);
	// }
	public function register_additional_sections_frontend_scripts(){

		$script_path       = '/assets/dist/additional-sections-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/assets/dist/additional-sections-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'thwcfe-additional-sections-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'thwcfe-additional-sections-frontend',
			'woocommerce-checkout-field-editor-pro',
			dirname( __FILE__ ) . '/languages'
		);
	}

	// public function register_additional_sections_frontend_styles(){
		
	// 	$style_path = '/assets/dist/style-additional-sections-frontend.css';
	// 	$style_url = plugins_url( $style_path, __FILE__ );
	// 	$absolutepath = dirname( __FILE__ ) . '/assets/dist/style-additional-sections-frontend.css';
	// 	wp_enqueue_style(
	// 		'thwcfe-additional-sections-frontend',
	// 		$style_url,
	// 		[],
	// 		$this->get_file_version( $absolutepath )
	// 	);
	// }

	public function register_block_styles(){
		$style_path = '/assets/dist/style.css';
		$style_url = plugins_url( $style_path, __FILE__ );
		$absolutepath = dirname( __FILE__ ) . '/assets/dist/style.css';
		wp_enqueue_style(
			'thwcfe-block',
			$style_url,
			[],
			$this->get_file_version( $absolutepath )
		);
	}

   /* private function register_main_integration() {

		$script_path = '/assets/dist/index.js';
		$style_path  = '/assets/dist/style-index.css';

		$script_url = plugins_url( $script_path, __FILE__ );
		$style_url  = plugins_url( $style_path, __FILE__ );

		$script_asset_path = dirname( __FILE__ ) . '/assets/dist/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => ['thwcfe-additional-sections-frontend'],
				'version'      => $this->get_file_version( $script_path ),
			];
			$script_asset      =  [
				'dependencies' => ['thwcfe-additional-sections-frontend'],
				'version'      => $this->get_file_version( $script_path ),
			];

		wp_enqueue_style(
			'thwdtp-block-integration',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);


		wp_register_script(
			'thwcfe-block-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		// wp_set_script_translations(
		// 	'thwcfe-block-integration',
		// 	' order-delivery-date-and-time',
		// 	dirname( __FILE__ ) . '/languages'
		// );
	}*/

	/**
	 * 
	 * ---  Temporary function to translate block dynamic strings (gettext, WPML..) ---
	 * 	
	 * 							--- Start ---
	 * 
	 */

	private function translate_block_field_dynamic_strings($all_sections) {
	    if (empty($all_sections)) {
	        return;
	    }

	    foreach ($all_sections as $section) {
	        // Translate Section Title & Subtitle
	        if (!empty($section->title)) {
	            $section->title = __($section->title, 'woocommerce-checkout-field-editor-pro');
	        }
	        if (!empty($section->subtitle)) {
	            $section->subtitle = __($section->subtitle, 'woocommerce-checkout-field-editor-pro');
	        }

	        // Skip if no fields in this section
	        if (empty($section->fields)) {
	            continue;
	        }

	        foreach ($section->fields as $field) {
	            // Translate Field Properties
	            $this->translate_field_properties($field);

	            // Translate Field Options (for select, radio, etc.)
	            $this->translate_field_options($field);
	        }
	    }
	}

	/**
	 * Translate field properties like title, subtitle, placeholder, etc.
	 */
	private function translate_field_properties($field) {
	    $properties_to_translate = [
	        'title',
	        'subtitle',
	        'placeholder',
	        'description'
	    ];

	    foreach ($properties_to_translate as $prop) {
	        if (!empty($field->{$prop})) {
	            $field->{$prop} = __($field->{$prop}, 'woocommerce-checkout-field-editor-pro');
	        }
	    }
	}

	/**
	 * Translate field options (for select, radio, checkbox groups).
	 */
	private function translate_field_options($field) {
	    if (empty($field->options)) {
	        return;
	    }

	    // Handle both array and object-style options
	    $options = is_array($field->options) ? $field->options : (array) $field->options;

	    foreach ($options as $key => $option) {
	        if (is_array($option)) {
	            // Translate 'text' in option arrays (e.g., select fields)
	            if (!empty($option['text'])) {
	                $field->options[$key]['text'] = __($option['text'], 'woocommerce-checkout-field-editor-pro');
	            }
	        } elseif (is_string($option)) {
	            // Translate direct string options (e.g., simple key-value pairs)
	            $field->options[$key] = __($option, 'woocommerce-checkout-field-editor-pro');
	        }
	    }
	}

	/**
	 * 
	 * --- Temporary function to translate block dynamic strings (gettext, WPML..) ---
	 * 
	 * 							--- End --- 
	 */
}
endif;
