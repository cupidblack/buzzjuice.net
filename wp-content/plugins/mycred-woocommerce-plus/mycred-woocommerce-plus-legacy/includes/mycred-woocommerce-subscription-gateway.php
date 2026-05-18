<?php
// No dirrect access
if ( ! defined( 'MYCRED_WOOPLUS_VERSION' ) ) {
	exit;
}

if ( ! function_exists( 'mycred_init_woo_subscription_gateway' ) ) :

	function mycred_init_woo_subscription_gateway() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) || class_exists( 'MyCRED_WooCommerce_Subscription_Gateway' ) ) {
			return;
		}
		#[\AllowDynamicProperties]
		class MyCRED_WooCommerce_Subscription_Gateway extends WC_Payment_Gateway {

			public $mycred;
			public $mycred_type;
			public $log_template;
			public $log_template_refund;
			public $exchange_rate;

			public function __construct() {

				$this->id = 'mycred';

				if ( ! $this->use_exchange() ) {

					$exchange_rate     = 1;
					$this->mycred_type = get_woocommerce_currency();

				} else {

					$exchange_rate     = (float) $this->get_option( 'exchange_rate' );
					$this->mycred_type = $this->get_option( 'point_type' );

					if ( ! mycred_point_type_exists( $this->mycred_type ) ) {
						$this->mycred_type = MYCRED_DEFAULT_TYPE_KEY;
					}
				}

				if ( ! is_numeric( $exchange_rate ) ) {
					$exchange_rate = 1;
				}

				$this->mycred              = mycred( $this->mycred_type );
				$this->log_template        = $this->get_option( 'log_template' );
				$this->log_template_refund = $this->get_option( 'log_template_refund' );
				$this->exchange_rate       = $exchange_rate;

				add_filter( 'mycred_woocommerce_gateway_supports', array( $this, 'add_subscription_support' ) );
				add_action( 'woocommerce_scheduled_subscription_payment_mycred', array( $this, 'process_subscription_payment' ), 10, 2 );
				add_filter( 'mycred_parse_log_entry_recurring_payment_woocommerce', 'mycred_woo_log_entry_payment', 90, 2 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );
				add_action( 'mycred_refunded_for_woo', array( $this, 'mycred_subscription_refund' ), 10, 4 );

			}

			public function add_subscription_support( $supports ) {

				$subscription_support = array(
					'subscriptions',
					'subscription_cancellation',
					'subscription_suspension',
					'subscription_reactivation',
					'subscription_amount_changes',
					'subscription_date_changes',
					'subscription_payment_method_change',
					'subscription_payment_method_change_customer',
					'subscription_payment_method_change_admin',
					'multiple_subscriptions',
				);

				return array_merge( $supports, $subscription_support );
			}

			public function process_subscription_payment( $amount, $order ) {

				$parent_order_id = wcs_get_objects_property( $order, 'id' );

				$user_id = wcs_get_objects_property( $order, 'user_id' );

				$parent_order = wc_get_order( $parent_order_id );

				// Cost
				$cost = $amount;
				if ( $this->use_exchange() ) {
					$cost = $this->mycred->number( ( $amount / $this->exchange_rate ) );
				}

				// Check funds
				if ( $this->mycred->get_users_balance( $user_id, $this->mycred_type ) < $cost ) {
					wc_add_notice( 'Insufficient funds', 'error' );
					return;
				}

				// Charge
				$this->mycred->add_creds(
					'recurring_payment_woocommerce',
					$user_id,
					0 - $cost,
					$this->log_template,
					$parent_order_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);

				$parent_order->payment_complete();

			}

			public function use_exchange() {

				$currency = get_woocommerce_currency();
				if ( mycred_point_type_exists( $currency ) || 'MYC' == $currency ) {
					return false;
				}
				return true;

			}

			public function register_refrences( $list ) {

				$list['recurring_payment_woocommerce'] = __( 'Recurring Payment (WooCommerce Subscription)', 'mycredpartwoo' );

				return $list;

			}

			public function mycred_subscription_refund( $order, $amount, $reason, $obj ) {

				if ( ! wcs_order_contains_subscription( $order ) ) {
					return;
				}

				WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );

			}

		}

		$mycred_woo_subscription_init = new MyCRED_WooCommerce_Subscription_Gateway();
	}
endif;

add_action( 'after_setup_theme', 'mycred_init_woo_subscription_gateway', 999 );
