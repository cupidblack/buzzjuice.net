<?php

if ( ! defined('ABSPATH') ) {
    exit;
}

if ( ! class_exists( 'MyCRED_Woocommerce_Plus_Multi_Point_Type' ) ) {

	class MyCRED_Woocommerce_Plus_Multi_Point_Type {

		public function __construct() {
			
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'mycred_woo_available_gateways' ) );
			add_filter( 'woocommerce_currencies', array( $this, 'mycred_woo_add_currency' ) );
			add_filter( 'woocommerce_currency_symbols', array( $this, 'mycred_woo_currency_symbol' ) );
			add_filter( 'mycred_parse_log_entry_woocommerce_payment', array( $this, 'mycred_woo_log_entry_payment' ), 90, 2 );
			add_filter( 'mycred_parse_log_entry_woocommerce_refund', array( $this, 'mycred_woo_log_entry_refunds' ), 90, 2 );
			add_filter( 'mycred_parse_log_entry_store_sale_refund', array( $this, 'mycred_woo_log_entry_profit_refund' ), 90, 2 );
			add_filter( 'mycred_email_before_send', array( $this, 'mycred_woo_parse_email'), 10 );
			add_action( 'woocommerce_review_order_after_order_total', array( $this, 'mycred_woo_after_order_total' ) );
			add_action( 'woocommerce_cart_totals_after_order_total',  array( $this, 'mycred_woo_after_order_total' ) );
		}

		public function mycred_woo_available_gateways( $gateways ) {
			if ( ! isset( $gateways['mycred'] ) ) return $gateways;
			if ( defined( 'SHOW_MYCRED_IN_WOOCOMMERCE' ) && SHOW_MYCRED_IN_WOOCOMMERCE ) return $gateways;
			if ( ! is_user_logged_in() ) {
				unset( $gateways['mycred'] );
				return $gateways;
			}
			
			$point_types = mycred_get_types(true);
			foreach ($point_types as $point_type => $type_key) {

				if ( ! mycred_point_type_exists( $point_type ) )
				$point_type = MYCRED_DEFAULT_TYPE_KEY;

				$mycred     = mycred( $point_type );
				$user_id    = get_current_user_id();

				if ( $mycred->exclude_user( $user_id ) ) {

					unset( $gateways['mycred'] );
					return $gateways;
				}

				global $woocommerce;

				$currency = get_woocommerce_currency();

				if( ! is_object( $woocommerce ) || empty( $woocommerce->cart ) )
				{
					unset( $gateways['mycred'] );

					return $gateways;
				}

				$cost = $woocommerce->cart->total;
				if ( ! mycred_point_type_exists( $currency ) && $currency != 'MYC' )
				
				$exchange_rate = ! empty( $gateways['mycred']->get_option( 'exchange_rate' ) ) ? $gateways['mycred']->get_option( 'exchange_rate' ):1 ;
				$cost = $mycred->number( ( $woocommerce->cart->total / $exchange_rate ) );
				$cost = apply_filters( 'mycred_woo_order_cost', $cost, $woocommerce->cart, true, $mycred );

				if ( $mycred->get_users_balance( $user_id, $point_type ) < $cost ) {
					$gateways['mycred']->enabled = 'no';
				}

				return $gateways;
			}
		}

		public function mycred_woo_add_currency( $currencies ) {

			$point_types = mycred_get_types();

			if ( ! empty( $point_types ) ) {
				foreach ( $point_types as $type_id => $label ) {

					if ( $type_id == MYCRED_DEFAULT_TYPE_KEY )
						$type_id = 'MYC';

					$currencies[ $type_id ] = $label;
				}
			}

			return $currencies;
		
		}

		public function mycred_woo_currency_symbol( $currency_symbols ) {

			$point_types = mycred_get_types();
			if ( ! empty( $point_types ) ) {
				foreach ( $point_types as $type_id => $label ) {

					$mycred = mycred( $type_id );
					$symbol = '';
					if ( ! empty( $mycred->after ) )
						$symbol = $mycred->after;
					elseif ( ! empty( $mycred->before ) )
						$symbol = $mycred->before;

					if ( $type_id == MYCRED_DEFAULT_TYPE_KEY )
						$type_id = 'MYC';

					$currency_symbols[ $type_id ] = $symbol;

				}
			}

			return $currency_symbols;

		}

		public function mycred_woo_log_entry_payment( $content, $log_entry ) {
			
			$order_id   = absint( $log_entry->ref_id );
			$order_link = '#' . $order_id;

			if ( function_exists( 'wc_get_order' ) ) {

				$order = wc_get_order( $order_id );

				if ( $order !== false && is_object( $order ) )
					$order_link = '<a href="' . esc_url( $order->get_view_order_url() ) . '">#' . $order_id . '</a>';

			}

			$content   = str_replace( '%order_id%',   $order_id, $content );
			$content   = str_replace( '%order_link%', $order_link, $content );

			return $content;

		}

		public function mycred_woo_log_entry_refunds( $content, $log_entry ) {

			$content = mycred_woo_log_entry_payment( $content, $log_entry );

			$data    = maybe_unserialize( $log_entry->data );
			$reason  = '-';
			if ( isset( $data['reason'] ) && $data['reason'] != '' )
				$reason = $data['reason'];

			$content = str_replace( '%reason%', $reason, $content );

			return $content;

		}

		public function mycred_woo_log_entry_profit_refund( $content, $log_entry ) {

			$data     = maybe_unserialize( $log_entry->data );
			$order_id = '';
			if ( isset( $data['order_id'] ) && $data['order_id'] != '' )
				$order_id = '#' . $data['order_id'];

			$content  = str_replace( '%order_id%', $order_id, $content );

			$reason   = '-';
			if ( isset( $data['reason'] ) && $data['reason'] != '' )
				$reason = $data['reason'];

			$content  = str_replace( '%reason%', $reason, $content );

			return $content;

		}
		
		public function mycred_woo_parse_email( $email ) {

			if ( $email['request']['ref'] == 'woocommerce_payment' && function_exists( 'woocommerce_get_page_id' ) ) {

				if ( function_exists( 'wc_get_order' ) )
					$order = wc_get_order( (int) $email['request']['ref_id'] );
				else
					$order = new WC_Order( (int) $email['request']['ref_id'] );

				if ( isset( $order->id ) ) {

					$url     = esc_url( add_query_arg( 'order', $order->id, mycred_get_permalink( woocommerce_get_page_id( 'view_order' ) ) ) );
					$content = str_replace( '%order_id%', $order->id, $email['request']['entry'] );

					$email['request']['entry'] = str_replace( '%order_link%', '<a href="' . esc_url( $url ) . '">#' . $order->id . '</a>', $content );

				}

			}

			return $email;

		}

		public function mycred_woo_after_order_total() {
			if ( ! is_user_logged_in() ) return;
			if ( ! is_checkout() ) return;

			global $woocommerce;

			$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
			$point_types = mycred_get_types();
			$user_id     = get_current_user_id();
			
			if ( empty( $point_types ) ) return;

			$options_by_gateway = [];

			foreach ( $point_types as $point_type_key => $label ) {

				$available_gateways[$point_type_key] = new MyCRED_WooCommerce_Plus_Gateway( $point_type_key, $label );
				$gateways_id = $available_gateways[$point_type_key]->id;

				if ( isset( $available_gateways[$gateways_id] ) ) {
					$options_by_gateway[$point_type_key] = [
						'show_total'    => $available_gateways[$gateways_id]->get_option( 'show_total' ),
						'balance_label' => $available_gateways[$gateways_id]->get_option( 'balance_format' ),
						'total_label'   => $available_gateways[$gateways_id]->get_option( 'total_label' ),
						'exchange_rate' => $available_gateways[$gateways_id]->get_option( 'exchange_rate' )
					];
				}
			}
			
			?>
			<script type="text/javascript">
			jQuery(function($) {
				function updateBalanceAndCost() {
					var selectedGateway = $('input[name="payment_method"]:checked').val();
					$('.mycred-balance-cost').each(function() {
						var gateway = $(this).data('gateway');
						
						if (selectedGateway === gateway) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});
				}

				updateBalanceAndCost();
				$('input[name="payment_method"]').on('change', function() {
					updateBalanceAndCost();
				});
			});
			</script>
			<?php

			foreach ( $point_types as $point_type => $label ) {
				if ( ! mycred_point_type_exists( $point_type ) ) 
					$point_type = MYCRED_DEFAULT_TYPE_KEY;

				$mycred = mycred( $point_type );

				if ( $mycred->exclude_user( $user_id ) ) continue;

				$show_balance = true;
				$balance = $mycred->get_users_balance( $user_id, $point_type );
				$currency = get_woocommerce_currency();
				$cost = 0;

				if ( ! mycred_point_type_exists( $currency ) && $currency != 'MYC' ) {

					if( empty( $available_gateways[$gateways_id] ) ){
						return;
					}

					$gateways_id = $available_gateways[$gateways_id]->id;
					
					$exchange_rate = ! empty( $options_by_gateway[$point_type]['exchange_rate'] ) ? $options_by_gateway[$point_type]['exchange_rate'] :1 ;
					
					$cost = $mycred->number( ( $woocommerce->cart->total / $exchange_rate ) );
					
					$cost = apply_filters( 'mycred_woo_order_cost', $cost, $woocommerce->cart, true, $mycred );
				
				}
			
				if ( isset( $options_by_gateway[$point_type]['total_label'] ) ) {
					$total_label = $options_by_gateway[$point_type]['total_label'];
					$exchange_rate = $options_by_gateway[$point_type]['exchange_rate'];
					?>
					<tr class="total mycred-balance-cost" data-gateway="mycred_<?php echo esc_attr( $point_type ); ?>">
						<th><strong><?php echo esc_html( $total_label ); ?></strong></th>
						<td>
							<div class="order-total-in-points">
								<strong class="<?php echo $balance < $cost ? 'mycred-low-funds' : 'mycred-funds'; ?>"<?php echo $balance < $cost ? ' style="color:red;"' : ''; ?>>
									<?php echo esc_html( $mycred->format_creds( $cost ) ); ?>
								</strong>
							</div>
						</td>
					</tr>
					<?php
				}

				if ( ! empty( $options_by_gateway[$point_type]['balance_label'] ) ) {
					$balance_label = $options_by_gateway[$point_type]['balance_label'];
					?>
					<tr class="total mycred-balance-cost" data-gateway="mycred_<?php echo esc_attr( $point_type ); ?>">
						<th><strong class="mycred-label"><?php echo esc_html( $balance_label ); ?></strong></th>
						<td>
							<div class="current-balance">
								<strong class="<?php echo $balance < $cost ? 'mycred-low-funds' : 'mycred-funds'; ?>"<?php echo $balance < $cost ? ' style="color:red;"' : ''; ?>>
									<?php echo esc_html( $mycred->format_creds( $balance ) ); ?>
								</strong>
							</div>
						</td>
					</tr>
					<?php
				}
			}
		}	
	}

	new MyCRED_Woocommerce_Plus_Multi_Point_Type();
}