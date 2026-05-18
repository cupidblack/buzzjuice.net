<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_Product_Referral_Hook' ) ) :
	class MWP_Product_Referral_Hook extends myCRED_Hook {

		use MWP_Helper_Trait;

		public $module_setting;
		
		function __construct( $hook_prefs, $type ) {

			parent::__construct( array(
				'id'       => 'product_referral',
				'defaults' => array(
					'referrer' => array(
						'creds'    => 10,
						'log'      => '%plural% for referring %product_name%',
						'limit'    => 1,
						'limit_by' => 'total'
					),
					'referee'  => array(
						'creds'    => 10,
						'log'      => '%plural% for product purchase',
						'limit'    => 1,
						'limit_by' => 'total'
					)
				)
			), $hook_prefs, $type );

			$this->populate();

		}

		public function populate() {

			$this->woocommerce_setting = mycred_get_woocommerce_settings();
			$this->module_setting      = $this->woocommerce_setting[ 'mwp_product_referral' ];

		}

		public function run() {

			add_action( 'woocommerce_order_status_changed', array( $this, 'process_referral_reward' ), 10, 4 );

		}

		public function preferences() {

			$this->load_template( 
				'product_referral_hook', 
				'product-referral-module/product-referral-hook.php',
				array( 'prefs' => $this->prefs ) 
			);

		}

		public function sanitise_preferences( $data ) {

			if ( isset( $data['referrer']['limit'] ) && isset( $data['referrer']['limit_by'] ) ) {

				$limit = sanitize_text_field( $data['referrer']['limit'] );
				
				if ( '' == $limit ) {
					$limit = 0;
				}

				$data['referrer']['limit'] = $limit . '/' . $data['referrer']['limit_by'];
				
				unset( $data['referrer']['limit_by'] );

			}

			if ( isset( $data['referee']['limit'] ) && isset( $data['referee']['limit_by'] ) ) {
				
				$limit = sanitize_text_field( $data['referee']['limit'] );
				
				if ( '' == $limit ) {
					$limit = 0;
				}
				
				$data['referee']['limit'] = $limit . '/' . $data['referee']['limit_by'];
				
				unset( $data['referee']['limit_by'] );

			}
			
			return apply_filters( 'mycred_woo_product_referrer_save_pref', $data );

		}

		public function hook_limit_setting_e( $name, $id, $selected ) {

			$allowed_html = array(
				'div' => array(
					'class' => array()
				),
				'input' => array(
					'type'  => array(),
					'size'  => array(),
					'class' => array(),
					'name'  => array(),
					'id'    => array(),
					'value' => array()
				),
				'select' => array(
					'class' => array(),
					'name'  => array(),
					'id'    => array()
				),
				'option' => array(
					'value'    => array(),
					'selected' => array()
				)
			);

			echo wp_kses( $this->hook_limit_setting( $name, $id, $selected ), $allowed_html );

		}

		public function process_referral_reward( $order_id, $old_status, $new_status, $order ) {
			if ( ! isset( $this->woocommerce_setting['reward']['status'] ) || empty( $this->woocommerce_setting['reward']['status'] ) ) {
				$woocommerce_setting_status = array( 0 => 'wc-completed' );
			} else { 
				$woocommerce_setting_status = $this->woocommerce_setting['reward']['status'];
			}

			if ( ! in_array( 'wc-' . $new_status,  $woocommerce_setting_status  ) ) {
				return;
			}

			$referrals  = $order->get_meta( 'mwp_referral_data' );

			$referee_id = $order->get_user_id() ?? 0;
			$referee_ip = $order->get_customer_ip_address();

			if ( ! empty( $referrals ) ) {
				foreach ( $referrals as $product_id => $ref_data ) {
					
					if ( ! empty( $ref_data['referrer_id'] ) && ! empty( $ref_data['data'] ) ) {
						
						$referrer_id = absint( $ref_data['referrer_id'] );
						
						$this->reward_referrer( $referrer_id, $referee_id, $referee_ip, $order_id, $product_id );

						if ( ! empty( $referee_id ) ) {
							$this->reward_referee( $referrer_id, $referee_id, $order_id, $product_id );
						}

					}

				}
			}

		}

		public function reward_referrer( $referrer_id, $referee_id, $referee_ip, $order_id, $product_id ) {

			$data = array(
				'product_id' => $product_id,
				'referee_id' => 0,
				'referee_ip' => $referee_ip
			);
			
			if ( 
				! empty( $this->prefs[ 'referrer' ]['creds'] ) && 
				! $this->core->exclude_user( $referrer_id ) &&
				! $this->over_hook_limit( '', 'product_referral', $referrer_id )
			) {

				//Check for guest user with referee IP address
				$data[ 'referee_id' ] = $referee_id;

					//Check for referee ID and IP address both
				$this->core->add_creds(
					'product_referral',
					$referrer_id,
					$this->prefs[ 'referrer' ]['creds'],
					$this->prefs[ 'referrer' ]['log'],
					$order_id,
					$data,
					$this->mycred_type
				);
			}

		}

		public function reward_referee( $referrer_id, $referee_id, $order_id, $product_id ) {

			$data = array(
				'product_id' => $product_id,
				'referrer_id' => $referrer_id
			);

			if ( 
				! empty( $this->prefs[ 'referrer' ]['creds'] ) &&
				! $this->core->exclude_user( $referee_id ) &&
				! $this->over_hook_limit( '', 'product_referral_referee', $referee_id ) &&
				! $this->has_entry( 'product_referral_referee', NULL, $referee_id, $data, $this->mycred_type )
			) {
				
				$this->core->add_creds(
					'product_referral_referee',
					$referee_id,
					$this->prefs[ 'referee' ]['creds'],
					$this->prefs[ 'referee' ]['log'],
					$order_id,
					$data,
					$this->mycred_type
				);

			}

		}

	}
endif;