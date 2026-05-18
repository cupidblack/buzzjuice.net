<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook for each order
 * @since 1.8
 * @version 1.0
 */
if ( ! class_exists( 'MWP_Hook_Each_Order' ) ) :
	class MWP_Hook_Each_Order extends myCRED_Hook {

		use MWP_Helper_Trait;

		public $module_setting;

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'woocommerce_each_order',
				'defaults' => array(
					'reward_on_each' => 'fixed_rate',
					'creds'   => 10,
					'log'     => '%plural% for each order'
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'woocommerce_order_status_changed', array( $this, 'assign_reward' ), 10, 4 );

		}

		/**
		 * Add points
		 * @since 1.0
		 * @version 1.0
		 */
		public function assign_reward( $order_id, $old_status, $new_status, $_this ) {

			$woocommerce_setting = mycred_get_woocommerce_settings();
			$reward_setting      = $woocommerce_setting[ 'reward' ];

			// status other than givenin reward-setting. return here
			if( ! in_array( 'wc-'.$new_status, $reward_setting['status'] ) ) return;

			$order   = wc_get_order( $order_id );

			// Make sure user is not excluded
			$user_id = $order->get_user_id();
			$total   = $order->get_total();
			
			if ( $this->core->exclude_user( $user_id ) ) return;

			$reference = apply_filters( 'mycred_woocommerce_each_order', 'woocommerce_each_order', $order );
			$data      = array( 'ref_type' => 'post' );
			
			// Make sure this is unique
			if ( $this->core->has_entry( $reference, $order_id, $user_id, $data, $this->mycred_type ) ) return;

			$creds = $this->prefs['creds'];

			if ( $this->prefs['reward_on_each'] == 'percentage' ) {
				$creds = ( $total / 100 ) * floatval( $this->prefs['creds'] );
			}
			elseif ( $this->prefs['reward_on_each'] == 'exchange_rate' ) {
				$creds = floatval( $this->prefs['creds'] ) * $total;
			}

			// Execute
			$this->core->add_creds(
				$reference,
				$user_id,
				$creds,
				$this->prefs['log'],
				$order_id,
				$data,
				$this->mycred_type
			);
			
		}

		/**
		 * Preferences
		 * @since 1.0
		 * @version 1.0
		 */
		public function preferences() {

			wp_enqueue_script( 'mwp-hooks-script' );

			$this->load_template( 
				'product_referral_hook', 
				'hooks-module/reward-for-each-order-hook.php',
				array(
					'obj'   => $this, 
					'prefs' => $this->prefs 
				) 
			);

		}

	  	/**
	     * Sanitize Preferences
	     */
		public function sanitise_preferences( $data ) {

			$new_data = array();
			$new_data['reward_on_each'] = ! empty( $data['reward_on_each'] ) ? sanitize_text_field( $data['reward_on_each'] ) : 'fixed_rate';
			$new_data['creds'] = ! empty( $data['creds'] ) ? (float) $data['creds'] : 0;
			$new_data['log'] = ! empty( $data['log'] ) ? sanitize_text_field( $data['log'] ) : '%plural% for each order';

			return $new_data;

		}

	}
endif;
