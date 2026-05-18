<?php

if ( ! class_exists( 'MyCRED_WooCommerce_Partial' ) ) {
	final class MyCRED_WooCommerce_Partial {

		// Plugin Version
		public $version = MYCRED_WOOPLUS_VERSION;


		public $slug = 'mycredpartwoo';

		// Instnace
		protected static $_instance = null;

		// Current session
		public $session = null;


		/**
		 * Setup Instance
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Construct
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function __construct() {

			if ( ! is_admin() && null !== get_option( 'mycred_partial_payment_switch' ) && 'disable' == get_option( 'mycred_partial_payment_switch' ) ) {
				return;
			}

			$this->define_constants();
			$this->includes();
			$this->mycred();
			$this->woocommerce();
		}

		/**
		 * Define Constants
		 * First, we start with defining all requires constants if they are not defined already.
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		private function define_constants() {

			$this->define( 'MYCRED_PARTWOO_THIS', __FILE__ );
			$this->define( 'MYCRED_PARTWOO_ROOT_DIR', plugin_dir_path( MYCRED_PARTWOO_THIS ) );
			$this->define( 'MYCRED_PARTWOO_INCLUDES_DIR', MYCRED_PARTWOO_ROOT_DIR . 'includes/partial-payment/' );
			$this->define( 'MYCRED_PARTWOO_TEMPLATES_DIR', MYCRED_PARTWOO_ROOT_DIR . 'templates/' );

		}

		/**
		 * Define
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			} elseif ( ! $definable && defined( $name ) ) {
				_doing_it_wrong( 'MyCRED_WooCommerce_Partial->define()', 'Could not define: ' . esc_attr($name) . ' as it is already defined somewhere else!', esc_attr($this->version) );
			}
		}

		/**
		 * Include Plugin Files
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function includes() {

			include_once MYCRED_PARTWOO_INCLUDES_DIR . 'mycred-partial-woo-functions.php';
			include_once MYCRED_PARTWOO_INCLUDES_DIR . 'mycred-partial-woo-checkout.php';
			include_once MYCRED_PARTWOO_INCLUDES_DIR . 'mycred-partial-woo-orders.php';
			include_once MYCRED_PARTWOO_INCLUDES_DIR . 'mycred-partial-woo-myaccount.php';
			include_once MYCRED_PARTWOO_INCLUDES_DIR . 'mycred-partial-payment-ajax.php';

		}

		/**
		 * MyCRED
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function mycred() {

			add_action( 'mycred_init', array( $this, 'start_up' ) );
			add_action( 'mycred_front_enqueue', array( $this, 'enqueue_scripts' ) );
			add_filter( 'mycred_run_this', array( $this, 'reward_adjustment' ) );
			add_filter( 'woocommerce_update_cart_action_cart_updated', array( $this, 'woo_cart_page_cart_updated'), 20 );
			add_action( 'woocommerce_cart_item_removed', array( $this, 'refund_partial_payment_on_cart_item_removed' ) );
			add_action( 'woocommerce_add_to_cart', array( $this, 'refund_partial_payment_on_cart_item_added' ) );
			add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'refund_partial_payment_on_cart_item_added' ) );
			add_filter( 'mycred_all_references', array( $this, 'register_partial_references' ) );
		
		}
		
		public function woo_cart_page_cart_updated( $cart_updated ) {

			$settings = mycred_part_woo_settings();

			$max = ! empty( $settings['max'] ) ? floatval( $settings['max'] ) : 100;

			if ( true == $cart_updated && $max < 100 ) {

				mpp_remove_coupon_and_refund( $settings );

			}

			return $cart_updated;

		}

		public function refund_partial_payment_on_cart_item_removed( $cart_item_key ) {

			$settings = mycred_part_woo_settings();

			$max = ! empty( $settings['max'] ) ? floatval( $settings['max'] ) : 100;

			if ( WC()->cart->is_empty() || floatval( $max ) < 100 ) {
				
				mpp_remove_coupon_and_refund();

			}

		}

		public function refund_partial_payment_on_cart_item_added( $id ) {

			$settings = mycred_part_woo_settings();

			$max = ! empty( $settings['max'] ) ? floatval( $settings['max'] ) : 100;

			if ( $max < 100 ) {

				mpp_remove_coupon_and_refund( $settings );

			}

		}
	

		/**
		 * Start
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function start_up() {

			// Bail if WooCommerce is not installed
			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			global $mycred_partial_payment, $mycred_remove_partial_payment;

			$mycred_partial_payment        = mycred_part_woo_settings();
			$mycred_remove_partial_payment = false;

			mycred_woo_partial_setup_my_account();

		}

		/**
		 * Enqueue Scripts
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function enqueue_scripts() {

			if ( ! is_user_logged_in() ) {
			  return;
			}

			global $mycred_partial_payment;

			wp_register_script(
				'mycred-partial-payment-woo',
				plugins_url( 'assets/js/mycred-partial-payment.js', MYCRED_PARTWOO_THIS ),
				array( 'jquery' ),
				MYCRED_WOOPLUS_VERSION,
				true
			);

			if ( function_exists( 'is_checkout' ) && is_checkout() || function_exists( 'is_cart' ) && is_cart() || isset( $GLOBALS['wp_scripts']->registered[ 'wc-add-to-cart' ] ) ) {

				$user_id = get_current_user_id();
				$mycred  = mycred( $mycred_partial_payment['point_type'] );
				
				if ( $mycred->exclude_user( $user_id ) ) {
				  return;
				}

				$total   = mycred_part_woo_get_total();
				$balance = $mycred->get_users_balance( $user_id );
				$max     = $mycred->number( $total / $mycred_partial_payment['exchange'] );

				if ( $balance < $max ) {
					$max = $balance;
				}

				$min     = ( ( $mycred_partial_payment['min'] > 0 ) ? $mycred_partial_payment['min'] : 0 );
				$format  = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), 'COST' );

				wp_localize_script(
					'mycred-partial-payment-woo',
					'myCREDPartial',
					array(
						'ajaxurl'  => admin_url( 'admin-ajax.php' ),
						'token'    => wp_create_nonce( 'mycred-partial-payment-new' ),
						'reload'   => wp_create_nonce( 'mycred-partial-payment-reload' ),
						'rate'     => $mycred_partial_payment['exchange'],
						'max'      => $max,
						'min'      => $min,
						'total'    => $total,
						'step'     => $mycred->number( $mycred_partial_payment['step'] ),
						'decimals' => $mycred->format['decimals'],
						'format'   => $format,
					)
				);

				wp_enqueue_script( 'mycred-partial-payment-woo', '', '', MYCRED_WOOPLUS_VERSION );

			}

		}

		/**
		 * Reward Adjustments
		 * When you make a partial payment in points AND you set your store to reward
		 * store purchases using points, this partial payment can in certain setups cause
		 * a user to get their points back or get more back due to rewards.
		 * This filter will deduct the amount of points a user made as a partial payment (if they made one)
		 * and deduct this amount from the reward amount to prevent the user to ever gaining more than they paid.
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function reward_adjustment( $run_this ) {

			// We need WooCommerce for this
			if ( ! function_exists( 'wc_get_order' ) ) {
				return $run_this;
			}

			extract( $run_this );

			$prefs = mycred_part_woo_settings();

			if ( ! array_key_exists( 'rewards', $prefs ) || 2 != $prefs['rewards'] ) {
				return $run_this;
			}

			/**
			* Filter mycred_woo_reward_reference
			* Filter mycred_woo_reward_mycred_payment
			* 
			* @since 1.0
			**/
			if ( apply_filters( 'mycred_woo_reward_reference', 'reward', 0, $type ) == $ref && apply_filters( 'mycred_woo_reward_mycred_payment', false, 0 ) === false ) {

				$order_id = absint( $ref_id );
				$order    = wc_get_order( $order_id );

				$discount = $order->get_discount_total();

				// No discount used = nothing for us to do
				if ( $discount <= 0 ) {
					return $run_this;
				}

				if ( 1 != $prefs['exchange'] ) {
					$discount = $discount / $prefs['exchange'];
				}

				// Stop transaction if the user is getting more than they
				if ( ( $amount - $discount ) <= 0 ) {
					$run_this['amount'] = null;
					$run_this['entry']  = '';
				} else {
					// Deduct the amount the user paid from the reward
					$run_this['amount'] = ( $amount - $discount );
				}
			}

			return $run_this;

		}

		/**
		 * WooCommerce
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function woocommerce() {

			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_gateways' ) );
			add_filter( 'woocommerce_locate_template', array( $this, 'locate_templates' ), 10, 3 );
			add_action( 'woocommerce_cart_collaterals', array( $this, 'mycred_woocommerce_cart_collaterals' ), 10, 2 );
			add_action( 'woocommerce_checkout_order_review', array( $this, 'mycred_woocommerce_checkout_before_customer_details' ), 9 );

		}

		/**
		 * Available Gateways
		 * Remove the myCRED gateway if it exists as we will replace it
		 * with our own.
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function available_gateways( $gateways ) {

			if ( ! isset( $gateways['mycred'] ) ) {
				return $gateways;
			}
			unset( $gateways['mycred'] );

			return $gateways;
		}

		/**
		 * Locate Template
		 * Since we are using WooCommerce functions to locate template files,
		 * we need to make sure we always provide our own default template.
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function locate_templates( $template, $template_name, $template_path ) { 

			if ( str_replace( 'woocommerce/templates/', '', $template ) !== $template && 'checkout/mycred-partial-payments.php' == $template_name ) {

				$default = MYCRED_PARTWOO_TEMPLATES_DIR . 'mycred-partial-payments.php';

				// Check if the theme has a file we should be using instead
				$_template = locate_template( array( $this->slug . '/mycred-partial-payments.php' ) );
				if ( ! $_template && file_exists( $default ) ) {
					$_template = $default;
				}

				return $_template;

			}

			return $template;

		}

		public function mycred_woocommerce_cart_collaterals() {

			if ( ! is_user_logged_in() ) {
				return __( 'You must be logged in to use partial payemnts/store coupons.', 'mycredpartwoo' );
			}

			if ( ! WC()->cart->needs_payment() ) {
				return;
			}

			$mycred_partial_payment = mycred_part_woo_settings();
			$toggle_module			= get_option( 'mycred_partial_payment_switch' );
			$user_id 				= get_current_user_id();
			$type					= $mycred_partial_payment['point_type'];
			$mycred  				= mycred( $type );

			if ( $mycred->exclude_user( $user_id ) ) {
				return;
			}

			$balance			= $mycred->get_users_balance( $user_id );
			$change_position	= $mycred_partial_payment['change_position'];
			$min				= isset( $mycred_partial_payment['min'] ) && ! empty( $mycred_partial_payment['min'] ) ? $mycred_partial_payment['min'] : 0;
			
			if ( ( 'both' == $change_position ) || ( 'cart' == $change_position && is_cart() ) || ( 'checkout' == $change_position && is_checkout() ) ) {

				if ( 'enable_coupons' == $toggle_module ) {
					
					include_once MYCRED_WOOPLUS_TEMPLATES_DIR . 'mycred-coupon.php';

				} else if ( 'enable_partial_payment' == $toggle_module ) {

					if ( ! mycred_partial_payment_possible() ) {
						return;
					}
					include_once MYCRED_WOOPLUS_TEMPLATES_DIR . 'mycred-partial-payments.php';
				}
			}

		}

		public function mycred_woocommerce_checkout_before_customer_details() {

			if ( ! is_user_logged_in() ) {
				return __( 'You must be logged in to use partial payemnts/store coupons.', 'mycredpartwoo' );
			}

			if ( ! WC()->cart->needs_payment() ) {
				return;
			}

			$mycred_partial_payment = mycred_part_woo_settings();
			$toggle_module			= get_option( 'mycred_partial_payment_switch' );
			$user_id 				= get_current_user_id();
			$type					= $mycred_partial_payment['point_type'];
			$mycred  				= mycred( $type );

			if ( $mycred->exclude_user( $user_id ) ) {
				return;
			}

			$balance			= $mycred->get_users_balance( $user_id );
			$change_position	= $mycred_partial_payment['change_position'];
			$min				= isset( $mycred_partial_payment['min'] ) && ! empty( $mycred_partial_payment['min'] ) ? $mycred_partial_payment['min'] : 0;
			
			if ( ( 'both' == $change_position ) || ( 'cart' == $change_position && is_cart() ) || ( 'checkout' == $change_position && is_checkout() ) ) {

				if ( 'enable_coupons' == $toggle_module ) {
					
					include_once MYCRED_WOOPLUS_TEMPLATES_DIR . 'mycred-coupon.php';

				}
			}
		}

		public function register_partial_references( $references ) {

			$references['partial_payment'] = __( 'Partial Payment', 'mycredpartwoo' );
			$references['points_to_coupon'] = __( 'Points to Coupon', 'mycredpartwoo' );
			$references['partial_payment_refund'] = __( 'Partial Payment Refund', 'mycredpartwoo' );

			return $references;

		}

	}
}

if ( ! function_exists( 'mycred_woo_partial_payments' ) ) {
	function mycred_woo_partial_payments() {
		return MyCRED_WooCommerce_Partial::instance();
	}
	mycred_woo_partial_payments();
}

