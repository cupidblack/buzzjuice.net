<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_Hooks_Module' ) ) :
	class MWP_Hooks_Module {
		
		public function __construct() {

			add_action( 'mycred_admin_enqueue', array( $this, 'register_admin_assets' ) );
			add_filter( 'mycred_setup_hooks',   array( $this, 'register_hooks' ), 20, 2 );
			//add_action( 'mycred_load_hooks',    array( $this, 'load_hooks' ) );
			$this->load_hooks();
		}

		public function register_admin_assets() {

			wp_register_script( 'mwp-hooks-script', plugins_url( 'assets/js/mwp-hooks-script.js', MYCRED_WOOPLUS_THIS ), array( 'jquery', 'wp-i18n' ), MYCRED_WOOPLUS_VERSION );

		}

		public function register_hooks( $installed ) {
			
			$installed['woocommerce_each_order'] = array(
                'title'       => __( '%plural% for each order (WooCommerce Plus)', 'mycred-woocommerce-plus' ),
                'description' => __( 'Award %plural% for each order.', 'mycred-woocommerce-plus' ),
                'callback'    => array( 'MWP_Hook_Each_Order' )
            );

            $installed['woocommerce_numbers_of_orders'] = array(
                'title'       => __( '%plural% for numbers of order (WooCommerce Plus)', 'mycred-woocommerce-plus' ),
                'description' => __( 'Award %plural% for numbers of order.', 'mycred-woocommerce-plus' ),
                'callback'    => array( 'MWP_Hook_Number_Of_Order' )
            );

            $installed['woocommerce_order_range'] = array(
                'title'       => __( '%plural% for order range (WooCommerce Plus)', 'mycred-woocommerce-plus' ),
                'description' => __( 'Award %plural% for order range.', 'mycred-woocommerce-plus' ),
                'callback'    => array( 'MWP_Hook_Order_Range' )
            );	

			return $installed;

		}

		public function load_hooks() {

			require_once MYCRED_WOOPLUS_HOOKS_DIR . 'reward-for-each-order.php';
            require_once MYCRED_WOOPLUS_HOOKS_DIR . 'reward-for-number-of-order.php';
            require_once MYCRED_WOOPLUS_HOOKS_DIR . 'reward-for-order-range.php';

		}

	}
endif;

function mwp_load_hooks_module() {
	$module = new MWP_Hooks_Module();
}
mwp_load_hooks_module();