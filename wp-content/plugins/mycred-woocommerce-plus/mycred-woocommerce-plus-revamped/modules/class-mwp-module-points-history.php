<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_Points_History_Module' ) ) :
	class MWP_Points_History_Module extends MWP_Module {

		// Point Types
		public $types = array();
		
		public function __construct() {

			parent::__construct( 'MWP_Points_History_Module', array(
				'module_name' => 'mwp_points_history',
				'defaults'    => array(
					'enable' => 0
				),
				'add_tab'     => true,
				'title'       => __('Points History', 'mycred-woocommerce-plus'),
				'icon'        => 'dashicons-editor-table',
				'tab_pos'     => 12
			) );

			$this->types = mycred_get_types();
			
		}

		public function module_init() {

			// if ( ! $this->prefs['enable'] ) {
			// 	return;
			// }

			add_action( 'init',                           array( $this, 'url_rewrite_for_history_tabs' ) );
			add_filter( 'query_vars',                     array( $this, 'history_tabs_query_vars' ) );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'add_history_tabs' ) );

		}
		
		public function admin_settings( $core ) {

			$settings = $core->woocommerce[ $this->module_name ];

			$this->load_template( 
				'points_history_settings', 
				'points-history-module/admin-settings.php',
				array( 
					'settings' => $settings
				) 
			);
			
		}

		public function sanitize_settings( $new_data, $data, $core ) {
			$new_data[ $this->module_name ]['enable'] = isset( $data[ $this->module_name ]['enable'] ) ? 1 : 0;

			$point_types = mycred_get_types();
			foreach ($point_types as $point_type => $value) {
				$is_enabled = isset($data[$this->module_name]['point_history']['types'][$point_type]) ? 1 : 0;
				$new_data[$this->module_name]['point_history']['types'][$point_type] = $is_enabled;
				$new_data[$this->module_name]['point_history']['tabs'][$point_type] = isset($data[$this->module_name]['point_history']['tabs'][$point_type]) ? sanitize_text_field($data[$this->module_name]['point_history']['tabs'][$point_type]) : '';
			}

			return $new_data;
		}



		public function url_rewrite_for_history_tabs() {

			foreach ( $this->types as $slug => $label ) {
				
				add_action( 'woocommerce_account_' . $slug . '_endpoint', array( $this, 'render_history' ), 100 );

				add_rewrite_endpoint( $slug, EP_ROOT | EP_PAGES );

			}

		}

		public function history_tabs_query_vars( $query_vars ) {

			foreach ( $this->types as $slug => $label ) {
				$query_vars[] = $slug;
			}

			return $query_vars;

		}
		
		public function add_history_tabs( $items ) {

			$new_items = array();
			foreach ($this->prefs['point_history']['types'] as $slug => $value) {
			    if ( $value == 1 && isset($this->types[$slug] ) ) {
			        $new_items[$slug] = ! empty( $this->prefs['point_history']['tabs'][$slug] ) ? $this->prefs['point_history']['tabs'][$slug] : $this->types[$slug] ;
			    }
			}
			return $this->add_menu_after($items, $new_items, 'edit-account');

		}
		
		public function history_tabs_endpoint_title( $title, $id ) {

			if ( is_account_page() ) {

				$title = $this->get_history_page_title();

				if ( ! empty( $title ) ) {
					
					return sprintf( __( '%s History', 'mycred-woocommerce-plus' ), $title );

				}

			}

			return $title;

		}
		
		public function render_history( $current_page ) {

			$type_key = $this->get_point_type_from_query();

			if ( ! empty( $type_key ) ) {

				add_filter( 'mycred_log_table_classes', array( $this, 'add_history_table_classes' ) );

				wp_enqueue_style( 'mwp-style' );

				$current_page = empty( $current_page ) ? 1 : absint( $current_page );
				$big_number   = 999999999;

				$args = array( 
					'ctype'   => $type_key,
					'user_id' => get_current_user_id(),
					'number'  => 10,
					'paged'   => $current_page
				);

				$logs = new myCRED_Query_Log( $args );

				$this->load_template( 
					'front_history_tab', 
					'points-history-module/frontend-history-tab.php',
					array( 
						'logs'         => $logs,
						'type_key'     => $type_key,
						'current_page' => $current_page,
						'big_number'   => $big_number
					) 
				);

			}

		}
		
		public function get_history_page_title() {

			$title = '';

			$type_key = $this->get_point_type_from_query();

			if ( ! empty( $type_key ) ) {

				$title = $this->types[ $type_key ];
				
			}

			return $title;

		}

		public function get_point_type_from_query() {
			
			global $wp_query;

			$type_key = '';

			$types = array_intersect_key( $wp_query->query_vars, $this->types );

			if ( ! empty( $types ) ) {

				$type_key = array_keys( $types )[0];
				
			}

			return $type_key;

		}

		public function add_history_table_classes( $classes ) {

			return 'woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table';

		}

	}
endif;

function mwp_load_points_history_module() {

	$module = new MWP_Points_History_Module();
	$module->load();

}
mwp_load_points_history_module();