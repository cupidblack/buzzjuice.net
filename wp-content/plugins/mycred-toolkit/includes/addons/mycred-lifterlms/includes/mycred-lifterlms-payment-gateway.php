<?php

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! class_exists( 'LLMS_Payment_Gateway_Mycred' ) ) :
	#[AllowDynamicProperties]
	class LLMS_Payment_Gateway_Mycred extends LLMS_Payment_Gateway {

		protected $checkbox_option = '';
		protected $select_option   = '';
		protected $test_api_key    = '';

		public function __construct() {

			$types            = mycred_get_types();
			$default_exchange = array();

			foreach ( $types as $type => $label ) {
				$default_exchange[ $type ] = 1;
			}
			
			$this->configure_variables();

			add_filter( 'llms_get_gateway_settings_fields', array( $this, 'mycred_add_settings_fields' ), 10, 2 );
			add_action( 'lifterlms_before_view_order_table', array( $this, 'mycred_before_view_order_table' ) );
			add_filter( 'lifterlms_register_order_post_statuses', array( $this, 'mycred_add_new_llms_order_status' ) );
			add_action( 'llms_checkout_footer_before', array( $this, 'llms_checkout_footer_before' ), 20 );
			
			$this->profit_sharing_percent = get_option( 'llms_gateway_mycred_lifterlms_profit_sharing' );
			$this->profit_sharing_log     = get_option( 'llms_gateway_mycred_lifterlms_log_template' );
			$this->refund_log_template    = get_option( 'llms_gateway_mycred_lifterlms_refund_log_template' );
			$this->exchange_rate          = get_option( 'llms_gateway_mycred_lifterlms_exchange_rate' );
		}

		public function get_payment_instructions() {
			$opt = get_option('payment_instructions');
			if ($opt) {
				$fields = '<div class="llms-notice llms-info"><h3>' . esc_html__('Payment Instructions', 'mycred-toolkit') . '</h3>' . wpautop(wptexturize(wp_kses_post($opt))) . '</div>';
			} else {
				$fields = '';
			}
			return apply_filters('llms_get_payment_instructions', $fields, $this->id);
		}

		public function get_price( $key, $price_args = array(), $format = 'html', $coupon = null ) {

			$price = $this->get( $key );

			if ( $price > 0 ) {


				$ret = parent::get_price( $key, $price_args, $format );

			}

			return apply_filters( 'llms_mycred_plan_get_price', $ret, $key, $price_args, $format, $this );
		}

		public function llms_checkout_footer_before() {
			
			global $mycred, $mycred_types, $post;

			$user_id = get_current_user_id();
			$mycred_types = mycred_get_types();
			$point_type = get_option('llms_gateway_mycred_lifterlms_point_type');
			$mycred = mycred($point_type);
			$total_label = get_option('llms_gateway_mycred_lifterlms_balance_label');
			$balance = $mycred->get_users_balance( $user_id, $point_type );
			$show_total_checkout = get_option('llms_gateway_mycred_lifterlms_show_total');
			$lifterlms_total_label = get_option('llms_gateway_mycred_lifterlms_total_label');
			$plan_id = isset( $_GET['plan'] ) ? absint( wp_unslash( $_GET['plan'] ) ) : '';
			$gateway = mycred_lifterlms_plugin()->get_gateway();
			$order_amount = get_post_meta($post->ID, '_llms_mycred_installment_amount_paid', true);
			$payment_instructions = get_option('llms_gateway_mycred_lifterlms_payment_instructions');
			$retrieved_total = get_option('my_total_option');
			$order = new LLMS_Order( $post->ID );
			$plan = new LLMS_Access_Plan( $plan_id );
			$total = $plan->price;
			$exchange_rate = $mycred->number(get_option('llms_gateway_mycred_lifterlms_exchange_rate'));
			$balance = $mycred->get_users_balance(get_current_user_id(), $point_type);
			$cost = $mycred->number($total/$exchange_rate);

			if ($show_total_checkout == 'checkout') {

				if (!empty($plan->price)) {
					?>

												   <tr class="total">
		<th><strong><?php echo esc_html( $mycred->template_tags_general( $lifterlms_total_label ) . ': '  ); ?></strong></th>
		<td>
			<div class="current-balance-lifterlms order-total-in-points-lifterlms">
				<strong class="
				<?php
					if ( $balance < $cost ) {
	echo 'mycred-low-funds';
					} else {
	echo 'mycred-funds';
					}
					?>
				"
				<?php
					if ( $balance < $cost ) {
									echo ' style="color:red;"';}
					?>
><?php echo esc_html( $mycred->number( $cost ) ); ?></strong> 
			</div>
		</td>
	</tr>
				<?php
				} 
												  
			}
			
			?>

			<?php

			if ( ! empty( $total_label ) && $show_total_checkout == 'checkout' ) {
				?>
			<tr class="total-lifterlms">
				<th><strong><?php echo esc_html($mycred->template_tags_general( $total_label) . ': ' ); ?></strong></th>
				<td>
					<div class="current-balance-lifterlms">
					<?php echo esc_html( $mycred->number( $balance ) ); ?>
					</div>
				</td>
			</tr>
	<?php
			}

			if ( ! empty( $payment_instructions ) && $show_total_checkout == 'checkout' ) {
				?>
			<tr class="lifterlms-payment-instructions">
				<th><strong><?php echo esc_html('Payment Instructions :' ); ?></strong></th>
				<td>
					<div class="lifterlms-payment-instructions-text">
						<?php echo esc_html( $payment_instructions ); ?>
					</div>
				</td>
			</tr>
	<?php
			}
		}

		public function mycred_before_view_order_table() {
			global $wp;
			if (!empty($wp->query_vars['orders'])) {
				$order = new LLMS_Order(intval( $wp->query_vars['orders']));
				if ('mycred' === $order->get( 'payment_gateway') && in_array($order->get('status'), array( 'llms-pending', 'llms-on-hold' ))) {
					echo esc_html($this->get_payment_instructions());
				}
			}
		}

		public function mycred_add_new_llms_order_status( $order_statuses ) {
			$order_statuses['llms-partial'] = array(
				'label'       => _x('Partially Paid', 'Order status', 'mycred-toolkit'),
				// Translators: %s is the count of partially paid items.
				'label_count' => _n_noop(
					'Partially Paid <span class="count">(%s)</span>',
					'Partially Paid <span class="count">(%s)</span>',
					'mycred-toolkit'
				),
			);
			return $order_statuses;  
		}

		protected function configure_variables() {
			$this->id = 'mycred';
			// Translators: This is the title displayed for the "Pay with myCRED" payment option.
			$this->title = _x( 'Pay with myCRED', 'Payment method title', 'mycred-toolkit' );
			$this->description = __('Deduct the amount from your points balance', 'mycred-toolkit');
			$this->admin_title = _x('myCred ', 'Gateway admin title', 'mycred-toolkit');
			$this->admin_description = __('myCred payment gateway used to document requirements for building a LifterLMS payment gateway.', 'mycred-toolkit');

			
			$this->test_mode_description = sprintf(
				// Translators: %1$s and %2$s are HTML anchor tags for a "Learn More" link.
				__( 'Sandbox Mode can be used to process test transactions. %1$sLearn More.%2$s', 'mycred-toolkit' ),
				'<a href="#">',
				'</a>'
			);

			$this->supports = array(
				'checkout_fields'    => true,
				'refunds'            => true,
				'single_payments'    => true,
				'recurring_payments' => true,
				'recurring_retry'    => true,
				'test_mode'          => false,
			);
			$this->admin_order_fields = wp_parse_args(
				array(
					'customer'     => true,
					'source'       => true,
					'subscription' => false,
				),
				$this->admin_order_fields
			);
		}

		public function settings_gateway_llms() {

			global $mycred, $mycred_types;
			$mycred_types = mycred_get_types();
			$gateway = mycred_lifterlms_plugin()->get_gateway();
			
			$currency = get_lifterlms_currency();
			$name = '';
			$mycred = new myCRED_Settings();

			if ( ! isset( $mycred->format['decimals'] ) ) {
					$decimals = $mycred->core['format']['decimals'];
			} else {
					$decimals = $mycred->format['decimals'];
			}   

			$currencies = get_lifterlms_currencies();
			if ( isset( $currencies[ $currency ] ) ) {
				$name = $currencies[ $currency ];
			}
			
			$fields = array();
			$fields[] = array(
				'id'      => $gateway->get_option_name('lifterlms_point_type'),
				'title'   => __('Point Type(s)', 'mycred-toolkit'),
				'type'    => 'select',
				'desc' => '',
				'options' => $mycred_types,
				'default'     => MYCRED_DEFAULT_TYPE_KEY
			);
			$fields[] = array(
				'id'            => $gateway->get_option_name('lifterlms_exchange_rate'),
				'title'         => __('Exchange Rate', 'mycred-toolkit'),
				'type'          => 'text',
				'desc' => sprintf(
					// Translators: %1$s is the name, %2$s is the currency symbol, and %3$s is the singular form of the currency.
					__( 'Enter the %1$s (%2$s) equivalent value of 1 %3$s based on your local currency', 'mycred-toolkit' ),
					$name,
					get_lifterlms_currency_symbol(),
					strtolower( $mycred->singular() )
				),
				'default'       => 1,
				'class' => 'regular-text',
				'min'       => '0',
				'step'       => 'any',
				'required'    => true,
				'desc_tip'    => true   
			);
			$fields[] = array(
				'id'      => $gateway->get_option_name('lifterlms_show_total'),
				'title'   => __('Show Total', 'mycred-toolkit'),
				'type'    => 'select',
				'desc' => '',
				'options' => array(
					'hide'   => __('Do Not Show', 'mycred-toolkit'),
					'checkout'   => __('Checkout', 'mycred-toolkit')
				)
			);
			$fields[] = array(
				'id'            => $gateway->get_option_name('lifterlms_total_label'),
				'title'         => __('Label', 'mycred-toolkit'),
				'type'          => 'text',
				'desc' => '',
				'default'       => __('Order total in %_plural%', 'mycred-toolkit')
				
			);
			$fields[] = array(
				'id'            => $gateway->get_option_name('lifterlms_balance_label'),
				'title'         => __('Balance Label', 'mycred-toolkit'),
				'type'          => 'text',
				'desc' => '',
				'default'       => 'Your Balance'
				
			);
			$fields[] = array(
				'id'            => $gateway->get_option_name('lifterlms_profit_sharing'),
				'title'         => __('Profit Sharing', 'mycred-toolkit'),
				'type'          => 'text',
				'desc' => __( 'Percentage of the paid amount to share with the product owner. Use zero to disable.', 'mycred-toolkit' ),
				'default'       => 0,
				'required'    => true,
				'min'       => '0',
				'step'       => '1'
				
			); 
			$fields[] = array(
				'id'            => $gateway->get_option_name('lifterlms_log_template'),
				'title'         => __('Log Template', 'mycred-toolkit'),
				'type'          => 'text',
				'default'       => 'Sale of order'          
			);
			$fields[] = array(
				'id'            => $gateway->get_option_name('lifterlms_refund_log_template'),
				'title'         => __('Refund Log Template', 'mycred-toolkit'),
				'type'          => 'text',
				'default'       => 'Refund for order'
			);
			$fields[] = array(
				'id'    => $gateway->get_option_name('lifterlms_payment_instructions'),
				'desc'  => '<br>' . __('Displayed to the user when this gateway is selected during checkout. Add information here instructing the student on how to send payment.', 'mycred-toolkit'),
				'title' => __('Payment Instructions', 'mycred-toolkit'),
				'type'  => 'textarea',
			);



			return $fields;
		}

		public function mycred_add_settings_fields( $default_fields, $gateway_id ) {

			if ( $this->id !== $gateway_id ) {
				return $default_fields;
			}

			if ($this->id === $gateway_id) {
				$fields = $this->settings_gateway_llms();
				$default_fields = array_merge($default_fields, $fields);
			}

			return $default_fields;
		}

		protected function record_transaction( $order, $gateway_txn_result, $type = 'initial' ) {
			$payment_type = 'single';
			if ($order->is_recurring()) {
				$payment_type = ( $order->has_trial() && 'initial' === $type ) ? 'trial' : 'recurring';
			}
			$args = array(
				'amount'       => $gateway_txn_result['amount'],
				'customer_id'  => $order->get('gateway_customer_id'),
				'status'       => sprintf('llms-txn-%s', 'success' === $gateway_txn_result['status'] ? 'succeeded' : 'failed'),
				'payment_type' => $payment_type,
			);
			$args['completed_date']     = gmdate('Y-m-d H:i:s', $gateway_txn_result['created']);
			$args['source_id']          = $gateway_txn_result['source_id'];
			$args['source_description'] = 'Visa ending in 4242'; 
			$args['transaction_id']     = $gateway_txn_result['id'];

			if ('succeeded' === $gateway_txn_result['status']) {
				$order->add_note(
					sprintf(
						// Translators: %1$s is the name, %2$s is the currency symbol, and %3$s is the singular form of the currency.
						__( 'Charge attempt for %1$s payment succeeded! [Charge ID: %2$s]', 'mycred-toolkit' ),
						$payment_type,
						$gateway_txn_result['id']
					)
				);
			} else {
				$order->add_note(
					sprintf(
						// Translators: %1$s is the name, %2$s is the currency symbol, and %3$s is the singular form of the currency.
						__('Charge attempt for %1$s failed. [Charge ID: %2$s]', 'mycred-toolkit'),
						$payment_type,
						$gateway_txn_result['id']
					)
				);
			}
			return $order->record_transaction($args);
		}

		public function handle_payment_source_switch( $order, $form_data = array() ) {

			$previous_gateway = $order->get('payment_gateway');

			if ($this->get_id() === $previous_gateway) {
				return;
			}
			
			$order->set('payment_gateway', $this->get_id());
			$order->set('gateway_customer_id', '');
			$order->set('gateway_source_id', '');
			$order->set('gateway_subscription_id', '');

			$order->add_note(sprintf(
				// Translators: %1$s is the name, %2$s is the currency symbol, and %3$s is the singular form of the currency.
			 __('Payment method switched from "%1$s" to "%2$s"', 'mycred-toolkit'), $previous_gateway, $this->get_admin_title()));
		}

		public function complete_transaction( $order, $deprecated = null ) {

			$this->log($this->get_admin_title() . ' `complete_transaction()` started', $order);
			$redirect = $this->get_complete_transaction_redirect_url($order);
			$this->log($this->get_admin_title() . ' `complete_transaction()` finished', $redirect, $order);

			// Execute a redirect.
			llms_redirect_and_exit(
				$redirect,
				array(
					'safe' => false,
				)
			);
		}

		
		public function get( $key, $raw = false ) {

			if ( $raw ) {
				return $this->___get( $key, $raw );
			}

			return $this->$key;
		}

		public function handle_pending_order( $order, $plan, $student, $coupon = false ) {

			$total    = $order->get_price('total', array(), 'float');
			$currency = $order->get('currency');
			
			$total_label = get_option('llms_gateway_mycred_lifterlms_show_total');
			$point_type = get_option('llms_gateway_mycred_lifterlms_point_type');
			$mycred = mycred($point_type);
			$exchange_rate = $mycred->number(get_option('llms_gateway_mycred_lifterlms_exchange_rate'));
			$balance = $mycred->get_users_balance($student->get('id'), $point_type);
			$cost = $mycred->number($total/$exchange_rate);
			$profit_sharing = $mycred->number(get_option('llms_gateway_mycred_lifterlms_profit_sharing'));
			$refund_log_template = get_option('llms_gateway_mycred_lifterlms_refund_log_template');
			$log_template_profit_sharing = get_option('llms_gateway_mycred_lifterlms_log_template');
			$post_id = $order->get('id');
			$post = get_post( $post_id );
			$final_price = $mycred->number( ( $total / $exchange_rate ) );
			$product_id = $order->get( 'product_id' );
			$plan_price = $plan->get_price('total', array(), 'float');
			$plan_id = isset( $_GET['plan'] ) ? absint( wp_unslash( $_GET['plan'] ) ) : '';
			$plan = new LLMS_Access_Plan( $plan_id );
			$student_id = $student->get('id');

			if ( ! isset( $mycred->format['decimals'] ) ) {
								$decimals = $mycred->core['format']['decimals'];
			} else {
						   $decimals = $mycred->format['decimals'];
			}

			update_option('my_total_option', $total);

			update_post_meta($product_id, '_llms_mycred_installment_amount_paid', $cost);

			update_option($total . '_llms_mycred_installment_amount_paid', true);

			if ($balance < $cost) {
				llms_add_notice('Insufficient funds', 'error');
				wp_redirect(llms_get_page_url('checkout', array( 'plan' => $order->plan_id )));
				die();
			}

			if ($profit_sharing > 0) {
				$percentage = apply_filters('mycred_lifterms_profit_share', $profit_sharing, $order->get('total'), $plan->get_product(), $profit_sharing);

				$ratio = $percentage / 100;
				$share = ( $final_price / 100 ) * $profit_sharing;
				$mycred->add_creds('lifterlms_plan_sale', $post->post_author, $mycred->number($share), $log_template_profit_sharing, $order->get('id'), array( 'ref_type' => 'post' ), $point_type);
			}

			if ($balance >= $cost) {
				$mycred->add_creds('lifterlms_payment_pts', $student_id, 0 - $final_price, 'lifterlms Payment By Pts', '', '', $point_type);
				$order->set('status', 'llms-completed');
			}

			$total    = $order->get_price('total', array(), 'float');
			$currency = $order->get('currency');

			if ($order->get_price('total', array(), 'float') > 0) {

					$product = $plan->get_product();

					$orderData = array(
						'currency' => get_lifterlms_currency(),
						'price' => $order->get('total'),
						'orderId' => $order->get('id'),
						'transactionSpeed' => 'high', //high, medium, low
						'redirectURL' => $order->get_view_link(),
						'buyer' => array(
							'email' => $order->get('billing_email')
						),
						'token' => $this->get_option('token')
					);

					$order->record_transaction(
								array(
									'amount'             => $order->get('total'),
									'source_description' => __('myCred', 'mycred-toolkit'),
									'transaction_id'     => uniqid(),
									'status'             => 'llms-txn-succeeded',
									'payment_gateway'    => 'mycred',
									'payment_type'       => 'single'
								)
								);

					llms_redirect_and_exit($order->get_view_link());

			}
		}


		public function process_refund( $transaction, $amount = 0, $note = '' ) {

			global $post;

		
			$order = llms_get_post( $post->ID );
			$student_id = $order->get( 'user_id' );
			$point_type = get_option('llms_gateway_mycred_lifterlms_point_type');
			$mycred = mycred($point_type);
			$order         = $transaction->get_order();
			$plan_id = isset( $_GET['plan'] ) ? absint( wp_unslash( $_GET['plan'] ) ) : '';
			$plan = new LLMS_Access_Plan( $plan_id );
			$refund      = $amount;
			$exchange_rate =   $mycred->number(get_option('llms_gateway_mycred_lifterlms_exchange_rate'));
			$profit_sharing = $mycred->number(get_option('llms_gateway_mycred_lifterlms_profit_sharing'));
			$total    = $order->get_price('total', array(), 'float');
			$refund = $mycred->number( ( $refund / $exchange_rate ) );
			$percentage = apply_filters('mycred_lifterms_profit_share', $profit_sharing, $order->get('total'), $order->get_product(), $profit_sharing);
			$ratio = $percentage / 100;
			$share = ( $profit_sharing / 100 ) * $refund;
			$final_price = $mycred->number( ( $total / $exchange_rate ) );

			if ( ! isset( $mycred->format['decimals'] ) ) {
						$decimals = $mycred->core['format']['decimals'];
			} else {
						$decimals = $mycred->format['decimals'];
			}

			$this->log('myCred gateway `process_refund()` started', $transaction, $final_price, $note);
				
			$share = ( $profit_sharing / 100 ) * $refund;
			$refunded_amount = $mycred->number( ( $amount / $exchange_rate ) );

			if ($profit_sharing > 0) {

				$mycred->add_creds('lifterlms_payment_refund', $student_id , $refunded_amount , 'lifterlms Refund By Pts', '', '', $point_type);

		 
				$mycred->add_creds('lifterlms_payment_refund', $post->post_author, 0 - $mycred->number($share), 'lifterlms Refund By Pts', '', '', $point_type);

			} else {
				$mycred->add_creds('lifterlms_payment_refund', $student_id , $refunded_amount , 'lifterlms Refund By Pts', '', '', $point_type);
			}

			return (string) uniqid();
		}

		public function record_refund( $refund, $note = '' ) {

			$refund = wp_parse_args(
				$refund,
				array(
					'amount' => 0.00,
					'id'     => '',
					'method' => '',
					'date'   => llms_current_time('mysql'),
				)
			);

			// Record the note.
			$this->record_refund_note($note, $refund['amount'], $refund['id'], $refund['method']);

			// Update the refunded amount.
			$refund_amount = $this->get('refund_amount');
			$new_amount    = ! $refund_amount ? $refund['amount'] : $refund_amount + $refund['amount'];
			$this->set('refund_amount', $new_amount);

			// Record refund metadata.
			$refund_data = $this->get_refunds();

			$refund_data[ $refund['id'] ] = apply_filters('llms_transaction_refund_data', $refund, $this, $refund['amount'], $refund['method']);
			$this->set('refund_data', $refund_data);

			// Update status.
			$this->set('status', 'llms-txn-refunded');
		}

		public function is_enabled() {
				return ( 'yes' === $this->get_enabled() ) ? true : false;
		}
	}
endif;