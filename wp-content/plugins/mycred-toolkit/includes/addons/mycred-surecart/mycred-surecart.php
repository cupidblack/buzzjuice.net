<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (! class_exists( 'myCRED_Toolkit_SureCart')) :
	class myCRED_Toolkit_SureCart {

		// Instnace
		protected static $_instance = null;

		/**
		 * Setup Instance
		 * @since 1.1.2
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __clone() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.5'); }

		/**
		 * Not allowed
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __wakeup() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.5'); }

		/**
		 * Define
		 * @since 1.1.2
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if (! defined($name)) {
				define($name, $value);
			} elseif (! $definable && defined($name)) {
				_doing_it_wrong('myCRED_Toolkit_SureCart->define()', 'Could not define: ' . esc_html($name) . ' as it is already defined somewhere else!', '1.5');
			}
		}

		/**
		 * Require File
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();
			$this->includes();
			add_filter('mycred_setup_hooks', array( $this, 'register_hooks' ));
			add_filter('mycred_all_references', array( $this, 'setup_references' ));
			
		}

		
		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define('MYCRED_SURECART_SLUG', 'mycred-surecart');
			$this->define('MYCRED_SURECART', __FILE__);
			$this->define('MYCRED_SURECART_ROOT', plugin_dir_path( MYCRED_SURECART));
			$this->define('MYCRED_SURECART_INC_DIR', MYCRED_SURECART_ROOT . 'includes/');
			
		}

		/**
		 * Include Plugin Files
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() {
			
			$this->file(MYCRED_SURECART_INC_DIR . 'surecart-reward-for-each-order.php');
			$this->file(MYCRED_SURECART_INC_DIR . 'surecart-reward-for-first-order.php');
			$this->file(MYCRED_SURECART_INC_DIR . 'surecart-reward-for-order-range.php');
			$this->file(MYCRED_SURECART_INC_DIR . 'surecart-reward-for-number-of-order.php');	
		}

		public function register_hooks( $installed ) {

			$installed['surecart_each_order'] = array(
                'title'       => __( '%plural% for each order (SureCart)', 'mycred-toolkit' ),
                'description' => __( 'Award %plural% for each order.', 'mycred-toolkit' ),
                'callback'    => array( 'SureCart_Hook_Each_Order' )
            );
             $installed['surecart_first_order'] = array(
                'title'       => __('%plural% for first order (SureCart)', 'mycred-toolkit'),
                'description' => __('Award %plural% for first order.', 'mycred-toolkit'),
                'callback'    => array('SureCart_Hook_First_Order')
            );
             $installed['surecart_order_range'] = array(
                'title'       => __('%plural% for order range (SureCart)', 'mycred-toolkit'),
                'description' => __('Award %plural% for order range.', 'mycred-toolkit'),
                'callback'    => array('SureCart_Hook_Order_Range')
            );
             $installed['surecart_numbers_of_orders'] = array(
                'title'       => __('%plural% for numbers of order (SureCart)', 'mycred-toolkit'),
                'description' => __('Award %plural% for numbers of order.', 'mycred'),
                'callback'    => array('SureCart_Hook_Number_Of_Order')
            );
			return $installed;
		}

		/**
		 * Setup references so they show on the Edit Points log screen
		 *
		 * @param $references
		 *
		 * @return mixed
		 */
		function setup_references( $references ) {

			$references['surecart_each_order'] = __( 'Points for each order', 'mycred-toolkit' );
			$references['surecart_first_order'] = __( 'Reward for first order', 'mycred-toolkit' );
			$references['surecart_order_range'] = __( 'Reward for order range', 'mycred-toolkit' );
			$references['surecart_numbers_of_orders'] = __( 'Reward for number of order', 'mycred-toolkit' );
			return $references;
		}
		
	}
endif;

function mycred_surecart_plugin() {

	return myCRED_Toolkit_SureCart::instance();
}
add_action('mycred_init', 'mycred_surecart_plugin');
