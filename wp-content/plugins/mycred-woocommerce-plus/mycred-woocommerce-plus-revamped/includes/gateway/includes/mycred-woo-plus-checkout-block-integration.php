<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class myCred_WooCommerce_Plus_Checkout_Blocks_Integration implements IntegrationInterface {

	public function get_name() {
		return 'mycred_plus';
	}

	public function initialize() {
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
	}

	public function get_script_handles() {
		return array( 'mycred-woo-plus-checkout-block' );
	}

	public function get_editor_script_handles() {
		return array( 'mycred-woo-plus-editor-checkout-block' );
	}

	public function get_script_data() {
		global $woocommerce;
	
		$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
		$point_types = mycred_get_types();
		$data = array();
	
		foreach ( $point_types as $point_type_key => $label ) {
			$available_gateways[$point_type_key] = new MyCRED_WooCommerce_Plus_Gateway( $point_type_key, $label );
	
			if ( $point_type_key === MYCRED_DEFAULT_TYPE_KEY ) {
				$core_setting_module = new myCRED_Settings_Module();
				$attachment_id = mycred_get_default_point_image_id();
	
				if ( property_exists( $core_setting_module->core, 'attachment_id' ) ) {
					$point_image = $core_setting_module->get_point_image( $core_setting_module->core->attachment_id, $core_setting_module->field_name( 'attachment_id' ) );
	
					if ( ! empty( $point_image ) ) {

						if ( preg_match('/src=["\']([^"\']+)["\']/i', $point_image, $matches) ) {
							$image_url = $matches[1];
						} else {
							$image_url = $point_image;
						}
					} else {
						$image_url = wp_get_attachment_url( $attachment_id );
					}
				} else {
					$image_url = wp_get_attachment_url( $attachment_id );
				}
			} else {
				$attachment_id = mycred_get_custom_point_image_id( $point_type_key );
				$image_url = wp_get_attachment_url( $attachment_id );
			}
	
			if ( isset( $available_gateways[$point_type_key] ) ) {
				$data[ $point_type_key ] = array(
					'title'           => $available_gateways[$point_type_key]->get_option( 'title' ),
					'balance_format'  => $available_gateways[$point_type_key]->get_option( 'balance_format' ),
					'total_label'     => $available_gateways[$point_type_key]->get_option( 'total_label' ),
					'description'     => $available_gateways[$point_type_key]->get_option( 'description' ),
					'show_total'      => $available_gateways[$point_type_key]->get_option( 'show_total' ),
					'icon'            => $image_url,
				);
			}
		}
	
		return $data;
	}

	public function register_block_editor_scripts() {
        $script_asset_path = plugins_url( 'includes/gateway/build/checkout/checkout-block.asset.php', MYCRED_WOOPLUS_THIS );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

        $script_url = plugins_url( 'includes/gateway/build/checkout/checkout-block.js', MYCRED_WOOPLUS_THIS );

		wp_register_script(
			'mycred-woo-plus-editor-checkout-block',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
       
	}

	public function register_block_frontend_scripts() {

        $script_asset_path  = plugins_url( 'includes/gateway/build/checkout/checkout-block.asset.php', MYCRED_WOOPLUS_THIS );
		$script_asset       = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);
        
        $script_url = plugins_url( 'includes/gateway/build/checkout/checkout-block.js', MYCRED_WOOPLUS_THIS );
		wp_register_script(
			'mycred-woo-plus-checkout-block',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return MYCRED_WOOPLUS_THIS;
	}
}