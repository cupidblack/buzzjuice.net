<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Square Gateway
 * Allows point purchases using Square.
 */
if ( ! class_exists( 'ms_myCRED_Square' ) ) :
	class ms_myCRED_Square extends myCRED_Payment_Gateway {
		public $visitors_allowed;
		function __construct( $gateway_prefs ) {
			$types = mycred_get_types();
			$default_exchange = array();
			foreach ( $types as $type => $label ) {
				$default_exchange[ $type ] = 1;
			}

			parent::__construct( array(
				'id'       => 'mycred_square',
				'label'    => 'Mycred Square',
				'defaults' => array(
					'mode'             => 'test',
					'live_public'      => '',
					'live_secret'      => '',
					'test_public'      => '',
					'test_secret'      => '',
					'currency'         => 'USD',
					'exchange'         => $default_exchange,
					'box_logo'         => '',
					'box_title'        => '',
					'box_desc'         => '',
					'box_button'       => '',
					'description'      => '',
					'card_statement'   => '',
					'application_id'   => '',
					'access_token'   => '',
					'location_id'   => '',
					'populate_email'   => 1,
					'require_billing'  => 1,
					'allow_rememberme' => 0
				)
			), $gateway_prefs );

			add_action( 'mycred_pre_process_buycred', array( $this, 'mcs_change_square_button_call' ), 10 );
			add_action( 'mycred_pre_process_buycred', array( $this, 'mcs_define_appilication_constant' ) );
			add_action( 'wp_head', array( $this, 'mcs_register_scripts' ), 10 );
			add_action( 'wp_footer', array( $this, 'mcs_wp_footer' ) );
			add_action('wp_ajax_nopriv_mcs_payment_process', array( $this, 'mcs_payment_process' ));
			add_action('wp_ajax_mcs_payment_process', array( $this, 'mcs_payment_process' ));



			if ( $this->prefs['mode'] == 'test' ) {
				$this->sandbox_mode     = true;
			}

			$this->visitors_allowed = apply_filters( 'mycred_square_visitors_allowed', false, $this );
		}

		public function mcs_admin_application_notice() {

			$class = 'notice notice-warning';

			if ( $this->prefs['mode'] == 'test' ) {
				$message = sprintf(
					// Translators: %s is the url .
					esc_html__( 'Connect your Square account ( sandbox ) before using %s', 'mycred-toolkit' ),
					'<a href="admin.php?page=mycred-gateways">myCred Square</a>'
				);
			} else {
				$message = sprintf(
					// Translators: %s is the url .
					esc_html__( 'Connect your Square account ( live ) before using %s', 'mycred-toolkit' ),
					'<a href="admin.php?page=mycred-gateways">myCred Square</a>'
				);
			}
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
		
		public function mcs_admin_application_wc() {

			$class = 'notice notice-warning';

			$message = esc_html__('To use "<b>WooSquare WooCommerce Square Integration</b>" WooCommerce must be activated!', 'mycred-toolkit');
			
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}


		public function mcs_define_appilication_constant() {

			//sandbox constant defined
			if ( $this->prefs['mode'] == 'test' ) {
				//get from mycred setting

				if (!empty($this->prefs['application_id']) && !empty($this->prefs['location_id']) && !empty($this->prefs['access_token']) ) {

					if (!defined('MS_MYCREDSQUARE_APPLICATION_ID')) {
						define('MS_MYCREDSQUARE_APPLICATION_ID', $this->prefs['application_id']);
					}
					define('MS_MYCREDSQUARE_LOCATION_ID', $this->prefs['location_id']);
					define('MS_MYCREDSQUARE_ACCESS_TOKEN', $this->prefs['access_token']);
					if (!defined('MS_WC_SQUARE_ENABLE_STAGING')) {
						define('MS_WC_SQUARE_ENABLE_STAGING', false);
					}
					define('MS_MYCRED_SQUARE_STAGING_URL', 'squareupsandbox');

				} else {
					//get from woocommerce

					if (!empty(get_option('woocommerce_square_settings'))) {
						$square_settings = get_option('woocommerce_square_settings');
						if (!defined('MS_MYCREDSQUARE_APPLICATION_ID')) {
							define('MS_MYCREDSQUARE_APPLICATION_ID', $square_settings['sandbox_application_id']);
						}
						define('MS_MYCREDSQUARE_LOCATION_ID', $square_settings['sandbox_location_id']);
						define('MS_MYCREDSQUARE_ACCESS_TOKEN', $square_settings['sandbox_access_token']);
					}
					if (!defined('MS_WC_SQUARE_ENABLE_STAGING')) {
						define('MS_WC_SQUARE_ENABLE_STAGING', false);
					}
					define('MS_MYCRED_SQUARE_STAGING_URL', 'squareupsandbox');

				}
			} else {
				//live constant setup

				if ($this->prefs['mode'] == 'live' ) {
					//echo WOOSQU_APPID;
					if (!defined('MS_MYCREDSQUARE_APPLICATION_ID')) {
						if (in_array('woosquare-premium/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))) {
							define('MS_MYCREDSQUARE_APPLICATION_ID', WOOSQU_PLUS_APPID);
						} elseif (in_array('woosquare-pro/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))) {
							define('MS_MYCREDSQUARE_APPLICATION_ID', WOOSQU_PLUS_APPID);
						} elseif (in_array('woosquare/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))) {
							define('MS_MYCREDSQUARE_APPLICATION_ID', WOOSQU_PLUS_APPID);
						}
					}
					define('MS_MYCREDSQUARE_LOCATION_ID', get_option('woo_square_location_id_free'));
					define('MS_MYCREDSQUARE_ACCESS_TOKEN', get_option('woo_square_access_token_free'));


					if (!defined('MS_WC_SQUARE_ENABLE_STAGING')) {
						define('MS_WC_SQUARE_ENABLE_STAGING', true);
					}
					define('MS_MYCRED_SQUARE_STAGING_URL', 'squareup');
				}
			}

			if (!defined('MS_MYCREDSQUARE_LOCATION_ID') || !defined('MS_MYCREDSQUARE_ACCESS_TOKEN')  || !defined('MS_MYCREDSQUARE_APPLICATION_ID') ) {
				add_action ( 'admin_init' , array( $this, 'mcs_admin_application_notice' ), true   );
			}
			
			if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_action ( 'admin_init' , array( $this, 'mcs_admin_application_wc' ), true   );
			}
		}


		/**
		 * Payment Process Methode
		 */

		public function mcs_payment_process() {

			if (isset($_POST['nonce']) && isset($_POST['amount']) && isset($_POST['currency']) && isset($_POST['points']) && isset($_POST['buyerVerification_token'])) {
				$nonce = sanitize_text_field($_POST['nonce']);
				$amount = sanitize_text_field($_POST['amount']);
				$currency = sanitize_text_field($_POST['currency']);
				$points = sanitize_text_field($_POST['points']);
				switch (strtoupper($currency)) {
					// Zero decimal currencies
					case 'BIF':
					case 'CLP':
					case 'DJF':
					case 'GNF':
					case 'JPY':
					case 'KMF':
					case 'KRW':
					case 'MGA':
					case 'PYG':
					case 'RWF':
					case 'VND':
					case 'VUV':
					case 'XAF':
					case 'XOF':
					case 'XPF':
					$amount = absint($amount);
						break;
					default:
						$amount = round($amount, 2) * 100; // In cents
						break;
				}

					$amount = (int) round($amount);
					$buyerVerification_token = sanitize_text_field($_POST['buyerVerification_token']);

				// try {

					$uid = uniqid();
					$woo_square_locations = MS_MYCREDSQUARE_LOCATION_ID;
					$token = MS_MYCREDSQUARE_ACCESS_TOKEN;


					$fields = array(
						'idempotency_key' => $uid,
						'location_id' => $woo_square_locations,
						'amount_money' => array(
							'amount' => $amount,
							'currency' => $currency
						),
						'source_id' => $nonce,
						'verification_token' => $buyerVerification_token,

					);


					//need to add order creation function and get the order id.

					$url = esc_url('https://connect.' . MS_MYCRED_SQUARE_STAGING_URL . '.com/v2/payments');


					$headers = array(
						'Accept' => 'application/json',
						'Authorization' => 'Bearer ' . $token,
						'Content-Type' => 'application/json',
						'Cache-Control' => 'no-cache'
					);


					$result = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
						'method' => 'POST',
						'headers' => $headers,
						'httpversion' => '1.0',
						'sslverify' => false,
						'body' => json_encode($fields)
					)
				)
				)
				);

				if ($result->errors) {
					wp_send_json_error($result->errors);
				}


				if (is_wp_error($result)) {
					echo esc_html__('Error: Unable to complete your transaction with square due to some issue. For now you can try some other payment method or try again later.', 'mycred-toolkit');
				}

				if ('CAPTURED' === $result->payment->card_details->status) {

					$to = 'current';
					$ctype = 'mycred_default';
					if ($to === 'current') {
						$to = get_current_user_id();
					}

					$square_token = $result->payment->id;
					$amount_paid = absint($result->payment->amount_money->amount);
					$point_amount = $points;
					$point_type = $ctype;
					$recipient = absint($to);
					$user_id = get_current_user_id();

					// Check to make sure we paid the correct amount
					$cost = $raw_cost = $this->get_cost($point_amount, $point_type);
					$cost = number_format($cost * 100, 0, '.', '');

					$pending_payment = buycred_get_pending_payment(array(
						'public_id' => '',
						'point_type' => $point_type,
						'amount' => $point_amount,
						'cost' => $raw_cost,
						'currency' => $currency,
						'buyer_id' => $user_id,
						'recipient_id' => $recipient,
						'gateway_id' => $this->id,
						'transaction_id' => ''
					));


					$payout = $this->complete_payment($pending_payment, $result->payment->id);
						
					if ($payout === false) {

						$message = 'Your payment failed.';

					} else {
						// Construct success message for user
						$message = 'Purchase completed';
					}
					echo esc_html($message);
					//wp_send_json($message);

				}

				// } catch (Exception $e) {

					// echo esc_html(esc_html__('Caught exception: ', "\n"));
				// }
			} else {

				echo esc_html__('Your credential not working', 'mycred-toolkit');

			}

				die();
		}



		/**
		 * Script Defined
		 */
		/**
		 * Script and css add
		 */
		public function mcs_register_scripts() {
			$currency   =  $this->prefs['currency'];
			$points_amount  =  $this->prefs['exchange'];
			$points_amount = implode(' ', $points_amount);
			$get_options =  ( get_option('mycred_pref_core') );
			$get_option     =  ( $get_options['buy_creds']['checkout'] );
			$get_thankyou_page       =  $get_options['buy_creds']['thankyou']['custom'];

			if (empty($get_thankyou_page)) {
				$get_thankyou_page       =  $get_options['buy_creds']['thankyou']['page'];
				if ($get_thankyou_page != 0 && $get_thankyou_page != null) {
					$get_thankyou_page = esc_url(get_page_link($get_thankyou_page));
				}
			} else {
				$get_thankyou_page = esc_url($get_thankyou_page);
			}


			$get_cancel_page       =    $get_options['buy_creds']['cancelled']['custom'];
			if (empty($get_cancel_page)) {
				$get_cancel_page       =    $get_options['buy_creds']['cancelled']['page'];
				if ($get_cancel_page != 0 && $get_cancel_page != null) {
					$get_cancel_page = esc_url(get_page_link($get_cancel_page));
				}
			} else {
				$get_cancel_page = esc_url(( $get_cancel_page ));
			}
			if ($this->prefs['mode'] == 'test') {
				$endpoint = 'sandbox.web';
			} else {
				$endpoint = 'web';
			}
			wp_enqueue_script('squareSDK', 'https://' . $endpoint . '.squarecdn.com/v1/square.js', array(), '');
			// wp_enqueue_script("sq-paymentForm", "https://js." . MS_MYCRED_SQUARE_STAGING_URL . ".com/v2/paymentform");
			wp_register_script( 'square-checkout-js', plugins_url( 'assets/js/square.js', MS_MYCRED_SQUARE ) );
			wp_register_style( 'square-checkout-css', plugins_url( 'assets/css/square-frontend-styles.css', MS_MYCRED_SQUARE ) );
			wp_localize_script( 'square-checkout-js', 'myAjax', array(
				'ajaxurl' => admin_url( 'admin-ajax.php'),
				'location_id' => MS_MYCREDSQUARE_LOCATION_ID,
				'application_id' => MS_MYCREDSQUARE_APPLICATION_ID,
				'currency' => $currency,
				'points_amount' => $points_amount,
				'checkoutoption' => $get_option,
				'cancel_page' => $get_cancel_page,
				'thankyou_page'=> $get_thankyou_page
			));
			wp_enqueue_script( 'square-checkout-js' );
			wp_enqueue_style( 'square-checkout-css' );
		}

		/**
		 * Square Button
		 */


		public function mcs_change_square_button_call() {
			@$request_mycred_buy = sanitize_text_field($_REQUEST['mycred_buy']);
			if (isset($request_mycred_buy)) {

				if ($request_mycred_buy == 'mycred_square') {

					add_filter( 'mycred_buycred_checkout_button', array( $this, 'change_square_button' ) );
				}
			}
		}

		/**
		 * Shortcode
		 */

		public function change_square_button() {

			return do_shortcode('[mycred_square_buy amount=' . sanitize_text_field($_REQUEST['amount']) . ']Continue[/mycred_square_buy]');
		}


		public function mcs_wp_footer() {
			if ( ! $this->visitors_allowed && ! is_user_logged_in() ) {
				return;
			}
			//echo ('<div id="buycred-square-payment-cover" style="display: none;top: 0;bottom: 0;left: 0;right: 0;position: fixed;z-index: 9;background: rgba(0,0,0,0.5);text-align: center;"><div style="padding-top: 40%;color: white;font-size: 18px;">' . esc_html(esc_html__( 'processing ...', 'mycred-toolkit' )) . '</div></div>');
		}
		public function process() { }


		/**
		 *   Return a JSON response
		 */

		public function ajax_buy() {

			$response = do_shortcode('[mycred_square_buy amount=' . sanitize_text_field($_REQUEST['amount']) . ']Continue[/mycred_square_buy]');
			$response .= "<script>jQuery('.mycred-square-buy-button').click();jQuery('.mycred_square_close_btn').on( 'click', function(ev) {jQuery('#buycred-checkout-wrapper').removeClass('open');jQuery('#buycred-checkout-form').empty();jQuery('#buycred-checkout-form').append('<div class=\"loading-indicator\"></div>');});</script>";
			$this->send_json($response);
		}

		/**
		 * Add Shortcode
		 */

		public function returning() {

			add_shortcode('mycred_square_buy', array( $this, 'shortcode_buy' ));
		}


		public function buy() {
			wp_die( '<p>Square payments not allowed via the buyCRED Checkout page.</p>' );
		}

		/**
		 * Shortcode
		 */

		public function shortcode_buy( $atts, $content = '' ) {

			ob_start();
			extract( shortcode_atts( array(
				'logo'     => $this->prefs['box_logo'],
				'title'    => $this->prefs['box_title'],
				'desc'     => $this->prefs['box_desc'],
				'label'    => $this->prefs['box_button'],
				'currency'  =>  $this->prefs['currency'],
				'amount'   => 0,
				'to'       => 'current',
				'ctype'    => 'mycred_default',
				'classes'  => 'btn btn-primary btn-lg',
				'id'       => ''
			), $atts ) );

			if ( ! $this->visitors_allowed && ! is_user_logged_in() ) {
				return;
			}

			$user_id = get_current_user_id();
			// Make sure the type we added exists
			if ( function_exists( 'mycred_point_type_exists' ) && ! mycred_point_type_exists( $ctype ) ) {
				$ctype = 'mycred_default';
			}

			// Make sure the type we added is enabled to be used with buyCRED
			if ( ! in_array( $ctype, $this->core->core['buy_creds']['types'] ) ) {
				return 'Invalid point type';
			}

			// Setup myCRED to use our selected point type
			if ( $ctype != 'mycred_default' ) {
				$mycred = mycred( $ctype );
			} else {
				$mycred = $this->core;
			}

			// Check if the current user is excluded from using this point type
			if ( ! $this->visitors_allowed && $mycred->exclude_user( $user_id ) ) {
				return;
			}

			if ( $to === 'current' ) {
				$to = $user_id;
			}

			if ( $to != $user_id && $mycred->exclude_user( $user_id ) ) {
				return 'Recipient is excluded from using this type.';
			}

			// Remove HTML tags from fields that do not support them
			$title = strip_tags( $title );
			$desc  = strip_tags( $desc );
			$label = strip_tags( $label );
			$currency = strip_tags($currency);

			// The button must have classes
			if ( $classes == '' ) {
				$classes = 'btn btn-primary btn-lg';
			}

			// Add trigger class
			$classes .= 'mycred-square-buy-button';

			// Get the cost
			$cost = $this->get_cost( $amount, $ctype );

			// Stripe requires costs to be set in cents
			$cost = number_format( $cost * 100, 0, '.', '' );
			if ( $id != '' ) {
				$id = ' id="' . $id . '"';
			}

			// Genrate unique id
			$unique_id = rand(100, 1000000);
			?><div id="mycred_modal_container">
			<div id="form-container">
				<div id="sq-ccbox">
					<img class="mycred_square_close_btn"   data-id="<?php echo esc_attr($unique_id); ?>"
					src="<?php echo esc_url( plugins_url( 'assets/images/close.png', MS_MYCRED_SQUARE ) ); ?>">
					<center>

						<?php if ($logo) { ?>
							<img class=""  src="<?php echo esc_url($logo); ?>" style="width: 100px;">
						<?php } ?>
					</center>
					<?php if ($title) { ?>
						<h2>  <?php echo esc_attr( $title ); ?></h2>
					<?php } ?>
					<?php if ($desc) { ?>
						<p>  <?php echo esc_attr( $desc ); ?></p>
					<?php } ?>

					<?php if ($amount) { ?>
						<p>  <b> Points </b> : <?php echo esc_attr( $amount ); ?></p>
						<?php
					}

					$get_point_type = 'buycred-setup-' . $ctype;
					$get_max =  ( get_option($get_point_type) );
					$get_max = $get_max['max'];
					$get_min =  ( get_option($get_point_type) );
					$get_min =  $get_min['min'];
					if (( $amount >= ( $get_min ) && $amount <= ( $get_max ) ) || empty($get_min) && empty($get_max)  ||  empty($get_min) && $amount <= ( $get_max ) ||  $amount >= ( $get_min ) && empty($get_max) ) {
						?>
						<div id="nonce-form" >
							<div id="ms-card-container"></div>
							<p class="form-row form-row-wide"><input type="hidden" id="amount" name="amount" value="<?php echo esc_attr($amount); ?>"><input type="hidden" id="card-nonce" name="nonce"><button type="submit" id="sq-creditcard" class="button-credit-card"> 
								<?php
								if ($label) {
									echo esc_attr($label);
								} else {
									esc_html_e( 'Buy Coin', 'mycred-toolkit' );  }
								?>
								</button></p>
								<div id="error"></div>
							</div>
							<?php
					} else {
						echo "<div class='padded error'> The amount must be in between min " . esc_attr( $get_min ) . ' and max ' . esc_attr( $get_max ) . '</div>' ;
					}
					?>
					</div>
				</div>
			</div>
			<?php
			$button = ob_get_contents();
			ob_end_clean();
			return apply_filters( 'mycred_square_buy_button', $button, $atts, $this );
		}


		/**
		 * Dashboard Setting
		 */

		public function preferences( $buy_creds = null ) {

			$prefs = $this->prefs;

			?>
			<style type="text/css">
				.mycred-square .form-control {
					width: 30% !important;
				}
				.mycred-square .mycred-square-li{
					line-height: 50px;
				}
			</style>
			<label class="subheader" for="<?php echo esc_attr($this->field_id( 'mode' )); ?>"><?php esc_html_e( 'Transaction Mode', 'mycred-toolkit' ); ?></label>
			<ol class="mycred-square">
				<li class="mycred-square-li">
					<select name="<?php echo esc_attr($this->field_name( 'mode' )); ?>" id="<?php echo esc_attr($this->field_id( 'mode' )); ?>" class="form-control">
						<?php

						$options = array(
							'live' => esc_html(esc_html__( 'Live - Real transactions', 'mycred-toolkit' )),
							'test' => esc_html(esc_html__( 'Test - Test transactions', 'mycred-toolkit' ))
						);

						foreach ( $options as $value => $label ) {
							echo ( '<option value="' ) . esc_attr($value) . '"';
							if ( $prefs['mode'] == $value ) {
								echo esc_html('selected="selected"');
							}
							echo '>' . esc_attr($label) . '</option>';
						}
						?>

					</select>
				</li>
				<li>
					<span class="description">
					<?php
					printf(
						/* translators: %s: URL to Square documentation for testing credit cards. */
						esc_html__( 
							'Do not use real credit cards in test mode! Instead, use one of the %s.', 
							'mycred-toolkit' 
						),
						sprintf(
							'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
							esc_url( 'https://developer.squareup.com/docs/testing/sandbox' ),
							esc_html__( 'credit card numbers Square provides', 'mycred-toolkit' )
						)
					);
					?>
				</span>
			</li>

			<label class="subheader" for="<?php echo esc_attr($this->field_id( 'currency' )); ?>"><?php esc_html_e( 'Currency', 'mycred-toolkit' ); ?></label>
			<ol>
				<li class="mycred-square-li">
					<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'currency' )); ?>" maxlength="22" id="<?php echo esc_attr($this->field_id( 'currency' )); ?>" class="form-control" value="<?php echo esc_attr( $prefs['currency'] ); ?>" size="20" /></div>

				</li>
			</ol>
			<label class="subheader" for="<?php echo esc_attr($this->field_id( 'exchange' )); ?>"><?php echo esc_attr($this->core->template_tags_general( esc_html(esc_html__( '%plural% Exchange Rate', 'mycred-toolkit' ) ))); ?></label>
			<ol>
				<?php $this->exchange_rate_setup( 'USD' ); ?>
			</ol>
			<label class="subheader" for="<?php echo esc_attr($this->field_id( 'description' )); ?>"><?php esc_html_e( 'Description', 'mycred-toolkit' ); ?></label>
			<ol>
				<li class="mycred-square-li">
					<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'description' )); ?>" id="<?php echo esc_attr($this->field_id( 'description' )); ?>" value="<?php echo esc_attr( $prefs['description'] ); ?>" class="form-control" /></div>
				</li>
			</ol>
			<label class="subheader" for="<?php echo esc_attr($this->field_id( 'card_statement' )); ?>"><?php esc_html_e( 'Credit Card Statement', 'mycred-toolkit' ); ?></label>
			<ol>
				<li class="mycred-square-li">
					<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'card_statement' )); ?>" maxlength="22" id="<?php echo esc_attr($this->field_id( 'card_statement' )); ?>" class="form-control" value="<?php echo esc_attr( $prefs['card_statement'] ); ?>" size="30" /></div>
					<span class="description"><?php esc_html_e( 'The text to request Square inserts into the buyers credit card statement. Maximum 22 characters!', 'mycred-toolkit' ); ?></span>
				</li>
			</ol>
			<label class="subheader" for="<?php echo esc_attr($this->field_id( 'box_logo' )); ?>"><?php esc_html_e( 'Square Checkout Window Details', 'mycred-toolkit' ); ?></label>
			<ol>
				<li>
					<label for="<?php echo esc_attr($this->field_id( 'box_logo' )); ?>"><?php esc_html_e( 'Logo URL', 'mycred-toolkit' ); ?></label>
					<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'box_logo' )); ?>" id="<?php echo esc_attr($this->field_id( 'box_logo' )); ?>" value="<?php echo esc_attr( $prefs['box_logo'] ); ?>" class="form-control" /></div>
					<span class="description"><?php esc_html_e( 'The default logo URL to use for the popup box. You can override this when you setup a purchase button.', 'mycred-toolkit' ); ?></span><br /><br />
					<label for="<?php echo esc_attr($this->field_id( 'box_title' )); ?>"><?php esc_html_e( 'Title', 'mycred-toolkit' ); ?></label>
					<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'box_title' )); ?>" id="<?php echo esc_attr($this->field_id( 'box_title' )); ?>" value="<?php echo esc_attr( $prefs['box_title'] ); ?>" class="form-control" /></div><br />
					<label for="<?php echo esc_attr($this->field_id( 'box_desc' )); ?>"><?php esc_html_e( 'Short Description', 'mycred-toolkit' ); ?></label>
					<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'box_desc' )); ?>" id="<?php echo esc_attr($this->field_id( 'box_desc' )); ?>" value="<?php echo esc_attr( $prefs['box_desc'] ); ?>" class="form-control" /></div>
					<span class="description"><?php esc_html_e( 'Shown under the title. Maximum 32 characters.', 'mycred-toolkit' ); ?></span><br /><br />
					<label for="<?php echo esc_attr($this->field_id( 'box_button' )); ?>"><?php esc_html_e( 'Button Label', 'mycred-toolkit' ); ?></label>
					<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'box_button' )); ?>" id="<?php echo esc_attr($this->field_id( 'box_button' )); ?>" value="<?php echo esc_attr( $prefs['box_button'] ); ?>" class="form-control" /></div>


				</li>

			</ol>
			<div class="sandbox_details">
				<?php
				if (!empty(get_option('woocommerce_square_settings'))) {
					$square_settings = get_option('woocommerce_square_settings');
					if ($square_settings['enable_sandbox'] == 'yes') {
						$application_id = $square_settings['sandbox_application_id'];
						$location_id = $square_settings['sandbox_location_id'];
						$access_token = $square_settings['sandbox_access_token'];
					}
				}
				?>
				<ol>
					<li>
						<h2>Sandbox API credentials </h2>
					</li>
				</ol>
				<ol>
					<li class="sandbox-description" style="padding: 3px 0px 3px 10px;background-color: #0085ba;color: white;font-size: medium;font-weight: 400;margin-bottom: 15px"><p>These settings are required only for sandbox!</p></li>
					<li class="sandbox-description"><p>If you don't have an account, go to <a target="_blank" href="https://squareup.com/signup">https://squareup.com/signup</a> to create one. You need a Square account to register an application with Square.
						Register your application with Square
					</p></li>
					<li>
						<p>
							Then go to <a target="_blank" href="https://connect.squareup.com/apps">https://connect.squareup.com/apps</a> and sign in to your Square account. Then <b>click New Application</b> and give the name for your application to Create App.

							The application dashboard displays your new app's sandbox credentials. Insert below these sandbox credentials.
						</p>
					</li>
				</ol>
				<label class="subheader" for="<?php echo esc_attr($this->field_id( 'application_id' )); ?>"><?php esc_html_e( ' Sandbox application id', 'mycred-toolkit' ); ?></label>
				<ol>
					<li>
						<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'application_id' )); ?>" id="<?php echo esc_attr($this->field_id( 'application_id' )); ?>" value="
							<?php
							if (isset($prefs['application_id'])) {
								echo esc_attr($prefs['application_id']) ;
							} elseif (isset($application_id)) {
								echo esc_attr($application_id); }
							?>
								" size="30"  class="form-control" autocomplete="off"/></div>
							</li>
						</ol>
						<label class="subheader" for="<?php echo esc_attr($this->field_id( 'access_token' )); ?>"><?php esc_html_e( ' Sandbox Access Token', 'mycred-toolkit' ); ?></label>
						<ol>
							<li>
								<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'access_token' )); ?>" id="<?php echo esc_attr($this->field_id( 'access_token' )); ?>" value="
									<?php
									if (isset($prefs['access_token'])) {
										echo esc_attr( $prefs['access_token'] );
									} elseif (isset($access_token)) {
										echo esc_attr($access_token); }
									?>
										" size="30" class="form-control" autocomplete="off"/></div>
									</li>
								</ol>
								<label class="subheader" for="<?php echo esc_attr($this->field_id( 'location_id' )); ?>"><?php esc_html_e( ' Sandbox Location ID', 'mycred-toolkit' ); ?></label>
								<ol>
									<li>
										<div class="h2"><input type="text" name="<?php echo esc_attr($this->field_name( 'location_id' )); ?>" id="<?php echo esc_attr($this->field_id( 'location_id' )); ?>" value="
											<?php
											if (isset($prefs['location_id'])) {
												echo esc_attr( $prefs['location_id'] );
											} elseif (isset($location_id)) {
												echo esc_attr( $location_id ); }
											?>
												" size="30" class="form-control" autocomplete="off"/></div>
											</li>
										</ol>

									</div>
									<script type="text/javascript">
										jQuery(document).ready(function(){
											jQuery("#mycred-gateway-prefs-mycred-square-mode").change(function(){
												if ( jQuery(this).find( ':selected' ).val() == 'live' ) {
													jQuery('.sandbox_details').hide();
												} else{
													jQuery('.sandbox_details').show();
												}
											}).change();
										});
									</script>
									<?php
		}
	}
						endif;

?>