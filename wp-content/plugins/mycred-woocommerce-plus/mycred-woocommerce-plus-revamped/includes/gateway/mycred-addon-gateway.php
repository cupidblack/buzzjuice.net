<?php
if (!defined('ABSPATH')) {
    exit;
}
#[AllowDynamicProperties]
class MyCRED_WooCommerce_Plus_Gateway extends WC_Payment_Gateway {

    public function __construct( $point_type, $point_label ) {

        $this->id                 = 'mycred_' . $point_type;
        $this->point_type         = $point_type;
		$this->has_fields         	= true;
        $this->method_title       = sprintf(__('%s', 'mycred'), $point_label);
        $this->method_description = sprintf(__('Let users pay using %s.', 'mycred'), $point_label);
        $this->title              = sprintf(__('Pay with %s', 'mycred'), $point_label);

		$gateway_suppots = array(
			'products',
			'refunds'
		);
		
		$this->supports = apply_filters( 'mycred_woocommerce_gateway_supports', $gateway_suppots );

		if ( ! $this->use_exchange() )
			$this->mycred_type = get_woocommerce_currency();

		else {
			$this->mycred_type = $this->get_option( 'point_type' );
			if ( ! mycred_point_type_exists( $this->mycred_type ) )
				$this->mycred_type = MYCRED_DEFAULT_TYPE_KEY;
		}
		
		$this->mycred = mycred( $this->mycred_type );

		if ( is_admin() && isset($_GET['page']) && $_GET['page'] === 'wc-settings' ) {
		   $this->init_form_fields();
		}
		$this->init_settings();

		$this->title                     = $this->get_option( 'title' );
		$this->description               = $this->get_option( 'description' );

		if ( $this->use_exchange() )
			$exchange_rate = (float) $this->get_option( 'exchange_rate' );
		else
			$exchange_rate = 1;

		if ( ! is_numeric( $exchange_rate ) )
			$exchange_rate = 1;

		$this->exchange_rate             = $exchange_rate;
		$this->log_template              = $this->get_option( 'log_template' );
		$this->log_template_refund       = $this->get_option( 'log_template_refund' );
		$this->profit_sharing_refund_log = $this->get_option( 'profit_sharing_refund_log' );

		$this->show_total                = $this->get_option( 'show_total' );
		$this->total_label               = $this->get_option( 'total_label' );
		$this->balance_format            = $this->get_option( 'balance_format' );

		$this->profit_sharing_percent    = $this->get_option( 'profit_sharing_percent' );
		$this->profit_sharing_log        = $this->get_option( 'profit_sharing_log' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );  
		add_action('admin_enqueue_scripts', array ($this, 'dequeue_mycred_select2'), 9999);
	}

    public static function instance() {

		$point_types = mycred_get_types(true); 

		foreach ($point_types as $type_key => $type_label) {

			add_filter('woocommerce_payment_gateways', function ($gateways) use ($type_key, $type_label) {

				$gateway = new MyCRED_WooCommerce_Plus_Gateway($type_key, $type_label);

				if ( $type_key === MYCRED_DEFAULT_TYPE_KEY ) {

					$core_setting_module = new myCRED_Settings_Module();
					$attachment_id = mycred_get_default_point_image_id();
				
					if ( property_exists( $core_setting_module->core, 'attachment_id' ) ) {

						$point_image = $core_setting_module->get_point_image( $core_setting_module->core->attachment_id, $core_setting_module->field_name( 'attachment_id' ) );
				
						if ( ! empty( $point_image ) ) {
							if ( preg_match('/src=["\']([^"\']+)["\']/i', $point_image, $matches) ) {
								$image_url = $matches[1];
							} else {
								$image_url = $point_image;
							}
						} else {
							$image_url = wp_get_attachment_url($attachment_id);
						}
					} else {
						$image_url = wp_get_attachment_url($attachment_id);
					}
				} else {
					$attachment_id = mycred_get_custom_point_image_id( $type_key );
					$image_url = wp_get_attachment_url($attachment_id);
				}
						
				if ($image_url) {
					$gateway->icon = $image_url;
				}
	
				$gateways[] = $gateway;
				
				return $gateways;
			});
	
			add_action('wp_head', function() use ($type_key) {
				?>
				<style>
					.payment_method_mycred_<?= esc_attr($type_key); ?> img {
						width: 25px; /* Set your desired width */
					}
				</style>
				<?php
			});
		}
	}	
	
	public function init_form_fields() {

		$products = wc_get_products(array('limit' => -1, 'status' => 'publish', 'type'    => ['simple', 'variable', 'variation'],));
		$categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
		$tags = get_terms(array('taxonomy' => 'product_tag', 'hide_empty' => false));
	
		$product_options = [];
		foreach ($products as $product) {
			$product_options[$product->get_id()] = $product->get_name();
		}
	
		$category_options = [];
		foreach ($categories as $category) {
			$category_options[$category->term_id] = $category->name;
		}
	
		$tag_options = [];
		foreach ($tags as $tag) {
			$tag_options[$tag->term_id] = $tag->name;
		}

		$fields['enabled']           = array(
			'title'   => __( 'Enable/Disable', 'mycred' ),
			'type'    => 'checkbox',
			'label'   => sprintf( __( 'Enable %s Payment', 'mycred' ), $this->method_title ),
			'default' => 'no',
			'description' => __( 'Users who are not logged in or excluded from using myCred will not have access to this gateway!', 'mycred' )
		);

		$fields['title']               = array(
			'title'       => __( 'Title', 'mycred' ),
			'type'        => 'text',
			'description' => __( 'Title to show for this payment option.', 'mycred' ),
			'default'   => sprintf( __( 'Pay with %s', 'mycred' ), $this->method_title ),
			'desc_tip'    => true
		);

		$fields['description']         = array(
			'title'       => __( 'Customer Message', 'mycred' ),
			'type'        => 'textarea',
			'default'     => $this->mycred->template_tags_general( 'Deduct the amount from your '. $this->method_title .' balance.' )
		);

		$fields['log_template']        = array(
			'title'       => __( 'Log Template', 'mycred' ),
			'type'        => 'text',
			'description' => $this->mycred->available_template_tags( array( 'general' ), '%order_id%, %order_link%' ),
			'default'     => 'Payment for Order: #%order_id%'
		);

		$fields['log_template_refund'] = array(
			'title'       => __( 'Refund Log Template', 'mycred' ),
			'type'        => 'text',
			'description' => $this->mycred->available_template_tags( array( 'general' ), '%order_id%, %reason%' ),
			'default'     => 'Payment refund for order #%order_id% Reason: %reason%'
		);

		if ( $this->use_exchange() ) {

			$exchange_desc = $this->mycred->template_tags_general( __( 'How much is 1 %_singular% worth in %currency%?', 'mycred' ) );
			$exchange_desc = str_replace( '%currency%', get_woocommerce_currency(), $exchange_desc );

			$fields['exchange_rate']   = array(
				'title'       => __( 'Exchange Rate', 'mycred' ),
				'type'        => 'text',
				'description' => $exchange_desc,
				'default'     => 1,
				'desc_tip'    => true
			);

			$fields['show_total']      = array(
				'title'       => __( 'Show Total', 'mycred' ),
				'type'        => 'select',
				'label'       => $this->mycred->template_tags_general( __( 'Show the final price in %_plural% .', 'mycred' ) ),
				'options'     => array(
					''           => __( 'Do not show', 'mycred' ),
					'checkout'   => __( 'Show on Checkout Page', 'mycred' ),
				),
				'default'     => ''
			);

			$fields['total_label']     = array(
				'title'       => __( 'Label', 'mycred' ),
				'type'        => 'text',
				'default'     => $this->mycred->template_tags_general( __( 'Order Total in '. $this->method_title .'', 'mycred' ) ),
				'desc_tip'    => true
			);
		}

		$fields['balance_format']            = array(
			'title'       => __( 'Balance Label', 'mycred' ),
			'type'        => 'text',
			'description' => __( 'The label to use when presenting a user their balance on the checkout / cart pages. Leave empty to hide.', 'mycred' ),
			'default'     => 'Your Balance',
			'desc_tip'    => false
		);

		$fields['profit_sharing_percent']    = array(
			'title'       => __( 'Profit Sharing', 'mycred' ),
			'type'        => 'text',
			'description' => __( 'Option to share sales with the product owner. Use zero to disable.', 'mycred' ),
			'default'     => 0,
			'desc_tip'    => true
		);

		$fields['profit_sharing_log']        = array(
			'title'       => __( 'Log Template', 'mycred' ),
			'type'        => 'text',
			'description' => __( 'Log entry template for profit sharing.', 'mycred' ) . ' ' . $this->mycred->available_template_tags( array( 'general', 'post' ) ),
			'default'     => 'Sale of %post_title%'
		);

		$fields['profit_sharing_refund_log'] = array(
			'title'       => __( 'Refund Log Template', 'mycred' ),
			'type'        => 'text',
			'description' => __( 'Log entry template for refunds of profit shares.', 'mycred' ) . ' ' . $this->mycred->available_template_tags( array( 'general', 'post' ) ),
			'default'     => 'Refund for order #%order_id%'
		);

		$fields['product_restriction'] = array(
				'title' => __('Product Restriction', 'mycred'),
				'type' => 'multiselect',
				'class' => 'wc-product-search',
				'description' => __('Restrict specific products for this gateway.', 'mycred'),
				'options' => $product_options,
		);

		$fields['category_restriction'] = array(
				'title' => __('Category Restriction', 'mycred'),
				'type' => 'multiselect',
				'class' => 'wc-enhanced-select',
				'description' => __('Restrict specific categories for this gateway.', 'mycred'),
				'options' => $category_options,
		);

		$fields['tag_restriction'] = array(
				'title' => __('Tag Restriction', 'mycred'),
				'type' => 'multiselect',
				'class' => 'wc-enhanced-select',
				'description' => __('Restrict specific tags for this gateway.', 'mycred'),
				'options' => $tag_options,
		);
		
		$this->form_fields = apply_filters( 'mycred_woo_fields', $fields, $this );
	}

	public function dequeue_mycred_select2() {

		// Check if the current page is the WooCommerce Checkout tab
		if ( isset($_GET['page'], $_GET['tab']) && $_GET['page'] === 'wc-settings' && $_GET['tab'] === 'checkout' ) {
			wp_dequeue_style('mycred-select2-style');
		}
	}

	public function is_available() {
		if (!parent::is_available()) {
			return false;
		}
	
		$restricted_products = array_map('intval', (array) $this->get_option('product_restriction', []));
		$restricted_categories = array_map('intval', (array) $this->get_option('category_restriction', []));
		$restricted_tags = array_map('intval', (array) $this->get_option('tag_restriction', []));
	
		if (did_action('wp_loaded') && WC()->cart) {
			foreach (WC()->cart->get_cart() as $cart_item) {

				$product_id = $cart_item['product_id'];
				$variation_id = $cart_item['variation_id'] ?? 0; 
				$parent_id = wp_get_post_parent_id($product_id); 
	
				if ( in_array($product_id, $restricted_products, true) || in_array($variation_id, $restricted_products, true) || in_array($parent_id, $restricted_products, true )) {
					return false;
				}
	
				$product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
				if (array_intersect($product_categories, $restricted_categories)) {
					return false;
				}
	
				$product_tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'ids']);
				if (array_intersect($product_tags, $restricted_tags)) {
					return false;
				}
			}
		}
	
		return true;
	}

	public function generate_text_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data     = wp_parse_args( $data, $defaults );
		$currency = get_woocommerce_currency();

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo wp_kses_post( $this->get_tooltip_html( $data ) ); ?>
			</th>
			<td class="forminp">
				<?php if ( $data['type'] == 'currency' ) : $mycred = mycred( $currency ); ?>
				<input type="hidden" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
				<p><?php echo esc_html( $mycred->plural() ); ?></p>
				<?php else : ?>
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo esc_attr( $this->get_custom_attribute_html( $data ) ); ?> />
					<?php echo wp_kses_post( $this->get_description_html( $data ) ); ?>
				</fieldset>
				<?php endif; ?>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	public function use_exchange() {
		$currency = get_woocommerce_currency();
		if ( mycred_point_type_exists( $currency ) || $currency == 'MYC' ) return false;
		return true;
	}

	public function admin_options() {
		?>
			<h3><?php printf( esc_html__( '%s Payment', 'mycred' ), esc_html( mycred_label() ) ); ?></h3>
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
		<?php
	}

    public function process_payment($order_id) {
		global $woocommerce;
	
		if (!is_user_logged_in()) {
			$message = $this->mycred->template_tags_general(__('You must be logged in to pay with %_plural%', 'mycred'));
			throw new \Exception( $message );
			return array();
		}
	
		$user_id = apply_filters('mycred_woo_gateway_user_id', get_current_user_id(), $order_id);
	
		if ($this->mycred->exclude_user($user_id)) {
			$message = $this->mycred->template_tags_general(__('You cannot use this gateway. Please try a different payment option.', 'mycred'));
			throw new \Exception( $message );
			return array();
		}
	
		$order = wc_get_order($order_id);
	
		$order_total = version_compare($woocommerce->version, '3.0', '>=') ? $order->get_total() : $order->order_total;
	
		$cost = $order_total;
		if ($this->use_exchange()) {
			$cost = $this->mycred->number(($order_total / $this->exchange_rate));
		}
	
		$cost = apply_filters('mycred_woo_order_cost', $cost, $order, false, $this);
	
		$balance = $this->mycred->get_users_balance($user_id, $this->point_type);
		
		if ($balance < $cost) {
			$message = apply_filters( 'mycred_woo_error_insufficient_funds', __( 'Insufficient funds.', 'mycred' ) );
			throw new \Exception( $message );
			return array();
		}
	
		$this->mycred->add_creds(
			'woocommerce_payment',
			$user_id,
			-$cost,
			$this->log_template,
			$order_id,
			array('ref_type' => 'post', 'point_type' => $this->point_type),
			$this->point_type
		);
	
		$order->payment_complete();
		$order->add_order_note(sprintf(__('Payment completed using %s points.', 'mycred'), $this->point_type));
	
		$order->reduce_order_stock();
	
		$woocommerce->cart->empty_cart();
	
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url($order)
		);
	}
	
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order       = wc_get_order( $order_id );
		$order_total = ( version_compare( $woocommerce->version, '3.0', '>=' ) ) ? $order->get_total() : $order->order_total;
		$user_id     = ( version_compare( $woocommerce->version, '3.0', '>=' ) ) ? $order->get_user_id() : $order->user_id;

		if ( $amount === NULL )
			$amount = $order_total;

		$refund      = $amount;
		if ( $this->use_exchange() )
			$refund = $this->mycred->number( ( $refund / $this->exchange_rate ) );

		$this->mycred->add_creds(
			'woocommerce_refund',
			$user_id,
			$refund,
			$this->log_template_refund,
			$order_id,
			array( 'ref_type' => 'post', 'reason' => $reason ),
			$this->mycred_type
		);

		$order->add_order_note( sprintf( _x( 'Refunded %s', '%s = Point amount formatted', 'mycred' ), $this->mycred->format_creds( $refund ) ) );

		if ( $this->profit_sharing_percent > 0 ) {

			$items = $order->get_items();

			foreach ( $items as $item ) {

				$product = mycred_get_post( (int) $item['product_id'] );

				if ( $product === NULL ) continue;

				$percentage = apply_filters( 'mycred_woo_profit_share_refund', $this->profit_sharing_percent, $order, $product, $this );
				if ( $percentage == 0 ) continue;

				$share = ( $percentage / 100 ) * $item['line_total'];

				$this->mycred->add_creds(
					'store_sale_refund',
					$product->post_author,
					0 - $share,
					$this->profit_sharing_refund_log,
					$product->ID,
					array( 'ref_type' => 'post', 'order_id' => $order_id ),
					$this->mycred_type
				);
			}
		}

		do_action( 'mycred_refunded_for_woo', $order, $amount, $reason, $this );

		return true;
	}
}

MyCRED_WooCommerce_Plus_Gateway::instance();