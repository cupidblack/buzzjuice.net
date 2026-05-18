<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_My_Account_Module' ) ) :
	class MWP_My_Account_Module extends MWP_Module {
		
		public function __construct() {

			parent::__construct( 'MWP_My_Account_Module', array(
                'module_name' => 'mwp_my_account',
				'defaults'    => array(
					'enable'    => 0,
					'tab_label' => 'myCred',
					'badges'    => 0,
					'ranks'     => 0,
					'balances'  => 0,
					'level'     => 0
				),
				'add_tab'     => true,
				'title'       => __('My Account', 'mycred-woocommerce-plus'),
				'icon'        => 'dashicons-performance',
				'tab_pos'     => 16
            ) );
			
		}

		public function module_init() {
			
			if ( 1 !==  $this->prefs['enable'] ) {
				return;
			}

			$slug = str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );

			add_filter( 'woocommerce_account_menu_items', 		      array( $this, 'add_my_account_tab' ) );
			add_action( 'init',                           		      array( $this, 'url_rewrite_for_my_account_tab' ) );
			add_filter( 'query_vars',                     		      array( $this, 'my_account_tab_query_vars' ), 0 );
			add_action( 'woocommerce_account_' . $slug . '_endpoint', array( $this, 'render_my_account_tab' ) );

		}
		
		public function admin_settings( $core ) {

			$settings = $core->woocommerce[ $this->module_name ];

			$this->load_template( 
				'my_account_settings', 
				'my-account-module/admin-settings.php',
				array( 'settings' => $settings ) 
			);
			
		}

		public function sanitize_settings( $new_data, $data, $core ) {

			$new_data[ $this->module_name ]['enable'] = isset( $data[ $this->module_name ]['enable'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['tab_label'] = ! empty( $data[ $this->module_name ]['tab_label'] ) ? sanitize_text_field( $data[ $this->module_name ]['tab_label'] ) : 'myCred Account';
			$new_data[ $this->module_name ]['badges'] = isset( $data[ $this->module_name ]['badges'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['ranks'] = isset( $data[ $this->module_name ]['ranks'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['balances'] = isset( $data[ $this->module_name ]['balances'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['level'] = isset( $data[ $this->module_name ]['level'] ) ? 1 : 0;

			return $new_data;

		}

		public function add_my_account_tab( $items ) {

			$slug 			= str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );
			$items[ $slug ] = $this->prefs['tab_label'];
			flush_rewrite_rules();
			return $items;

		}

		public function url_rewrite_for_my_account_tab() {

			$slug = str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );
			
			add_rewrite_endpoint( $slug, EP_ROOT | EP_PAGES );

		}

		public function my_account_tab_query_vars( $vars ) {

			$vars[] = str_replace( ' ', '-', strtolower( $this->prefs['tab_label'] ) );

			return $vars;
		
		}

		public function render_my_account_tab() {

			$settings    = $this->prefs;
			$point_types = ( $settings['balances'] ? mycred_get_types() : array() );

			wp_enqueue_style( 'mwp-style' );

			$this->load_template( 
				'front_my_account_tab',
				'my-account-module/frontend-my-account-tab.php',
				array(
					'settings'    => $settings,
					'point_types' => $point_types 
				)
			);
		
		}

	}
endif;

function mwp_load_my_account_module() {

	$module = new MWP_My_Account_Module();
	$module->load();

}
mwp_load_my_account_module();