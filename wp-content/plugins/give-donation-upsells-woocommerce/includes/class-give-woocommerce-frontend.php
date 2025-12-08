<?php
/**
 * The front-end functionality of the plugin.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 *
 * @package    Give_WooCommerce_Frontend
 * @subpackage Give_WooCommerce_Frontend
 */

/**
 * The front-end functionality of the plugin.
 *
 * @package    Give_WooCommerce_Frontend
 * @subpackage Give_WooCommerce_Frontend
 * @author     GiveWP <https://givewp.com>
 */
class Give_WooCommerce_Frontend {

	/**
	 * Give_WooCommerce_Frontend constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Enqueue the scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Add Give Donation amount to cart total as fee.
		add_action( 'woocommerce_cart_calculate_fees', [ $this, 'give_wc_add_donation_amount' ], 10 );

		// Add custom meta to the WC_ORDER.
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'give_wc_create_donations' ], 10, 1 );

		// List out the Give Donations.
		add_action( 'woocommerce_cart_contents', [ $this, 'give_wc_list_give_donations' ], 10 );
		add_action( 'woocommerce_checkout_after_customer_details', [ $this, 'give_wc_list_give_donations' ], 10 );

		// Update the Give Donation data when update the cart.
		add_action( 'woocommerce_update_cart_action_cart_updated', [
			$this,
			'give_wc_update_wc_forms_session',
		], 10 );
		add_action( 'woocommerce_checkout_update_order_review', [ $this, 'give_wc_update_wc_forms_session' ], 10 );
		add_action( 'woocommerce_before_cart_contents', [ $this, 'give_wc_update_wc_forms_session' ], 10 );

		// Update Give Donation data when open the checkout page.
		add_action( 'woocommerce_init', [ $this, 'give_wc_update_donations_on_checkout' ], 10 );

		add_action( 'wp_ajax_give_wc_process_donations', [ $this, 'give_wc_store_donations_to_cart' ] );
		add_action( 'wp_ajax_nopriv_give_wc_process_donations', [ $this, 'give_wc_store_donations_to_cart' ] );

		// Modify the donation name on the shop table.
		add_filter( 'woocommerce_get_order_item_totals', [ $this, 'give_wc_donation_item_label' ], 10, 1 );

		// Add Give Donation restriction message on Woo Discount Page.
		add_action( 'woocommerce_coupon_options_usage_restriction', [
			$this,
			'give_wc_show_donation_restriction_msg',
		], 10 );
	}

	/**
	 * Remove the colon from the donation item name from the shop table.
	 *
	 * @since 1.0.0
	 *
	 * @param array $total_rows List of the item.
	 *
	 * @return array
	 */
	public function give_wc_donation_item_label( $total_rows ) {
		if ( ! empty( $total_rows ) ) {
			foreach ( $total_rows as $key => $data ) {
				$exp_key = explode( '_', $key );
				if (
					isset( $exp_key[0] )
					&& 'fee' === $exp_key[0]
					&& strpos( $total_rows[ $key ]['label'], 'Donation:' ) !== false
				) {
					// Remove last colon(:) from the item label..
					$total_rows[ $key ]['label'] = rtrim( $total_rows[ $key ]['label'], ':' );
				}
			}
		}

		return $total_rows;
	}

	/**
	 * When rendering the checkout button and if there is any selected donation available in option table
	 * then get and set them in WC cart session.
	 *
	 * @link  https://github.com/impress-org/give-donation-upsells-woocommerce/issues/24
	 *
	 * @since 1.0.0
	 */
	public function give_wc_update_donations_on_checkout() {
		$session_id = md5( session_id() );
		$option_key = "give_wc_cart_{$session_id}";
		$give_donation_data = get_option( $option_key, false );

		if ( false !== $give_donation_data && null !== WC()->session ) {
			delete_option( $option_key );
			WC()->session->set( 'give_wc_donation_forms', $give_donation_data );
		}
	}

	/**
	 * Add donations to the cart when click on the "Proceed to Checkout".
	 *
	 * @since 1.0.0
	 */
	public function give_wc_store_donations_to_cart() {
		$success = false;

		if ( isset( $_POST['give_wc_session_id'], $_POST['give_wc_form-data'] ) ) {
			$give_wc_session_key = 'give_wc_cart_' . give_clean( $_POST['give_wc_session_id'] );

			// Get the cart from the database.
			$give_wc_cart = get_option( $give_wc_session_key, [] );

			if ( ! empty( $_POST['give_wc_form-data'] ) ) {
				update_option( $give_wc_session_key, give_wc_combine_donations( give_clean( $_POST['give_wc_form-data'] ), $give_wc_cart ) );
				$success = true;
			}
		}

		// Return the response.
		wp_die( wp_json_encode( [ 'success' => $success ] ) );
	}

	/**
	 * When Update the cart add the Give Donation to the WC Cart session.
	 *
	 * This method has major role in this add-on which stores the Give Donation's data to the WC Cart
	 * Also, this method is responsible to manage the donation data in came from both cart and checkout
	 * page.
	 *
	 * @since 1.0.0
	 */
	public function give_wc_update_wc_forms_session() {

		$post_data_output = [];

		// Parse the post data early.
		if ( isset( $_POST['post_data'] ) ) {
			// Don't sanitize the `post_data` parameter of $_POST without parsing as `give_clean` will mess with results.
			parse_str( $_POST['post_data'], $post_data_output );
		}

		// Sanitize the posted variables.
		$posted_data = give_clean( $_POST );
		$posted_data['post_data'] = give_clean( $post_data_output );

		// If the data were passed from the cart page.
		$give_donation_data = isset( $posted_data['give_wc_form-data'] ) ? $posted_data['give_wc_form-data'] : [];

		// If the data were passed through the checkout page.
		if ( is_checkout() && empty( $give_donation_data ) ) {

			// Get the donation form data.
			$give_donation_data = isset( $posted_data['post_data']['give_wc_form-data'] ) ? $posted_data['post_data']['give_wc_form-data'] : [];
		}

		// Get the selected Give donations.
		$give_donation_forms = WC()->session->get( 'give_wc_donation_forms' );

		// If donation forms were selected.
		if ( ! empty( $give_donation_data ) ) {
			$give_donation_forms = give_wc_combine_donations( $give_donation_data, $give_donation_forms );

			// Store in session.
			WC()->session->set( 'give_wc_donation_forms', $give_donation_forms );
		}

		if ( ! empty( $give_donation_forms ) ) {
			// Update session data to the database.
			update_option( 'give_wc_cart_' . md5( session_id() ), $give_donation_forms );
		}
	}

	/**
	 * Create Give donation when making WC Orders.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $order_id WC order ID.
	 *
	 */
	public function give_wc_create_donations( $order_id ) {
		// Proceed only if WC class exists.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Get the Give Donation Form from the session.
		$give_donation_forms = WC()->session->get( 'give_wc_donation_forms' );

		// Get the relation of the fee + form.
		$give_wc_fee_forms_relations = WC()->session->get( 'give_wc_fee_forms_relations' );

		/** @var \WC_Order $wc_order Get the order data. */
		$wc_order = wc_get_order( $order_id );

		// Store Give Donation ids.
		$give_donations_id = [];

		if ( ! empty( $give_donation_forms ) ) {

			/** @var \WP_User $user_info Get the user data */
			$user_info = get_userdata( $wc_order->get_user_id() );

			// WC order billing address.
			$billing_address = $wc_order->get_address( 'billing' );

			$first_name = isset( $user_info->data->first_name ) ? $user_info->data->first_name : $billing_address['first_name'];
			$last_name = isset( $user_info->data->last_name ) ? $user_info->data->last_name : $billing_address['last_name'];
			$user_email = isset( $user_info->data->user_email ) ? $user_info->data->user_email : $billing_address['email'];
			$user_id = isset( $user_info->ID ) ? $user_info->ID : 0;

			// Get the wc order user ID.
			$wp_user_id = $wc_order->get_user_id();

			/** @var Give_Donor $donor Give donor */
			$donor = new Give_Donor( $user_email, false );

			// Attach WP USER ID.
			if ( $donor->id > 0 && empty( $donor->user_id ) && ! empty( $wp_user_id ) ) {
				$donor->update( [ 'user_id' => $wc_order->get_user_id() ] );
			}

			if ( ! ( $donor->id > 0 ) ) {

				// Create Give Donor.
				$donor->create(
					[
						'email' => $user_email,
						'name' => trim( "{$first_name} {$last_name}" ),
						'user_id' => $wp_user_id,
					]
				);
			}

			// Go through all of the selected Give's Donation forms.
			foreach ( $give_donation_forms as $give_form_id => $form_data ) {
				$give_form = new Give_Donate_Form( $give_form_id );

				$price_id = ( isset( $form_data['price_id'] ) && null !== $form_data['price_id'] ) ? $form_data['price_id'] : 'custom';
				if ( 'custom' !== $price_id ) {
					// Get the variable amounts.
					$level_info = $give_form->get_level_info( $price_id );
					$price = give_sanitize_amount_for_db( $level_info['_give_amount'] );
				} else {
					// Get the donation amount.
					$price = give_sanitize_amount_for_db( $form_data['amount'] );
				}

				$payment_id = give_insert_payment( [
					'donor_id' => $donor->id,
					'gateway' => 'woocommerce',
					'give_form_id' => $give_form->get_ID(),
					'give_form_title' => get_the_title( $give_form ),
					'give_price_id' => $price_id,
					'price' => $price,
					'status' => $wc_order->get_status(),
					'currency' => get_woocommerce_currency(),
					'user_info' => [
						'first_name' => $first_name,
						'last_name' => $last_name,
						'id' => $user_id,
						'email' => $user_email,
						'address' => [
							'line1' => isset( $billing_address['address_1'] ) ? give_clean( $billing_address['address_1'] ) : '',
							'line2' => isset( $billing_address['address_2'] ) ? give_clean( $billing_address['address_2'] ) : '',
							'city' => isset( $billing_address['city'] ) ? give_clean( $billing_address['city'] ) : '',
							'country' => isset( $billing_address['country'] ) ? give_clean( $billing_address['country'] ) : '',
							'state' => isset( $billing_address['state'] ) ? give_clean( $billing_address['state'] ) : '',
							'zip' => isset( $billing_address['postcode'] ) ? give_clean( $billing_address['postcode'] ) : '',
						],
					],
				] );

				$payment = new Give_Payment( $payment_id );

				if ( has_filter( 'give_wc_create_donation' ) ) {
					/**
					 * Filter to change payment data before it's being saving after WC order has been created.
					 *
					 * @since 1.0.0
					 *
					 * @param array $order_id WC order id.
					 * @param array $payment Give Donation payment array data.
					 */
					$payment = apply_filters( 'give_wc_create_donation', $payment, $order_id );

					$payment->save();
				}

				// Add donation note.
				give_insert_payment_note(
					$payment_id, sprintf(
						__( 'This donation was created with WooCommerce order id: <a href="%2$s" target="_blank"> #%1$s view order</a> ', 'give-woocommerce' ),
						absint( $order_id ),
						admin_url( "post.php?post={$order_id}&action=edit" )
					)
				);

				// Store payment ID.
				$give_donations_id[] = $payment->ID;

				// Store the donation id with the fee + format relation array.
				if ( isset( $give_wc_fee_forms_relations[ $give_form_id ] ) ) {
					$give_wc_fee_forms_relations[ $give_form_id ]['donation_id'] = $payment->ID;
				}

				// Add meta related to wc.
				$payment->update_meta( '_give_is_wc_donation', 'true' );
				$payment->update_meta( '_give_wc_order_id', $order_id );

				// Add order note when donation created.
				$wc_order->add_order_note( sprintf( __( 'Donation "%s" has been created', 'give-woocommerce' ), $payment->form_title ) );
			}
		}

		// Remove donation form from the wc session.
		WC()->session->__unset( 'give_wc_donation_forms' );
		WC()->session->__unset( 'give_wc_fee_forms_relations' );

		// Store Donation ids to the wc order meta.
		$wc_order->update_meta_data( '_give_donations_id', $give_donations_id );
		$wc_order->save();

		// Get the donation list from the WC Order.
		$give_wc_fee_donations = $wc_order->get_fees();

		if ( is_array( $give_wc_fee_forms_relations ) ) {
			// Go through each of the fee form relations.
			foreach ( $give_wc_fee_forms_relations as $form_id => $fee_data ) {

				if ( is_array( $give_wc_fee_forms_relations ) ) {
					// Go through each of the Fee(Donation) from the WC order.
					foreach ( $give_wc_fee_donations as $fee_id => $order_fee_data ) {

						// If WC order fee title match the title from the session.
						if ( $order_fee_data->get_name() === $fee_data['fee_title'] ) {
							// Add order item meta.
							wc_add_order_item_meta( $order_fee_data->get_id(), '_give_wc_form_relation', $give_wc_fee_forms_relations[ $form_id ] );
						}
					}
				}
			}

			/** @var Give_WooCommerce_Sync $sync */
			$sync = new Give_WooCommerce_Sync( $wc_order );

			// If This donation is attached to any wc order.
			if ( $sync->get_wc_order() ) {
				$sync->sync_status();
			}
		}
	}

	/**
	 * Get the chosen Give Donation form from the \WC_Session and add fee one by one
	 * on the cart total.
	 *
	 * @since 1.0.0
	 */
	public function give_wc_add_donation_amount() {

		// Get the Give Donation Form from the session.
		$wc_session_donations = WC()->session->get( 'give_wc_donation_forms' );
		$wc_fee_forms_relations = WC()->session->get( 'give_wc_fee_forms_relations' );

		// Get the Give Donation status.
		$is_give_enabled = give_is_setting_enabled( WC_Admin_Settings::get_option( 'give_wc_donation_enabled' ) );

		if ( ! empty( $wc_session_donations ) && $is_give_enabled ) {

			// Get the enabled Give donation form.
			$chosen_donations = WC_Admin_Settings::get_option( 'give_wc_donation_forms' );

			// Go through from all of the Donation Form.
			foreach ( $wc_session_donations as $form_id => $form ) {

				// Skip if donation form is not enabled.
				if ( ! in_array( (string) $form_id, $chosen_donations, true ) ) {
					continue;
				}

				// Get the price ID.
				$price_id = isset( $form['price_id'] ) ? $form['price_id'] : null;

				// Get the donation form title.
				$wc_donation_title = give_wc_donation_fee_title( $form_id, $price_id );

				// Add Give Donation amount as fee on the cart total.
				WC()->cart->add_fee( $wc_donation_title, $form['amount'] );

				// Store the relation of the form with fee.
				$wc_fee_forms_relations[ $form_id ] = [
					'form_id' => $form_id,
					'fee_title' => $wc_donation_title,
					'amount' => $form['amount'],
				];
			}
			// Store the relation of fee and forms.
			WC()->session->set( 'give_wc_fee_forms_relations', $wc_fee_forms_relations );
		} else {
			// Remove if already set.
			WC()->session->__unset( 'give_wc_donation_forms' );

			// Remove the session if exists.
			WC()->session->__unset( 'give_wc_fee_forms_relations' );
		}
	}

	/**
	 * Enqueue style and js files.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		if ( ! is_user_logged_in() ) {
			wp_enqueue_style( 'dashicons' );
		}

		// Registering the recovery plugin JS script.
		wp_enqueue_script(
			'give-woocommerce-frontend',
			GIVE_WOOCOMMERCE_PLUGIN_URL . 'assets/dist/js/frontend.js',
			[ 'jquery' ],
			GIVE_WOOCOMMERCE_VERSION,
			false
		);

		// Register css for frontend.
		wp_enqueue_style(
			'give-woocommerce-frontend',
			GIVE_WOOCOMMERCE_PLUGIN_URL . 'assets/dist/css/frontend.css',
			GIVE_WOOCOMMERCE_VERSION,
			false
		);

		$give_currency_position = 'before';

		if ( in_array( get_option( 'woocommerce_currency_pos' ), [ 'right', 'right_space' ], true ) ) {
			$give_currency_position = 'after';
		}

		// Pass the variables to the JS file.
		wp_localize_script(
			'give-woocommerce-frontend', 'give_wc_vars', [
				'level_display_style' => WC_Admin_Settings::get_option( 'give_wc_multi_level_display_style' ),
				'donation_location' => WC_Admin_Settings::get_option( 'give_wc_donation_location' ),
				'intro_text' => WC_Admin_Settings::get_option( 'give_wc_intro_text' ),
				'donation_title_placeholder' => WC_Admin_Settings::get_option( 'give_wc_display_text' ),
				'admin_ajax' => admin_url( 'admin-ajax.php' ),
				'checkout_url' => wc_get_checkout_url(),
				'checkout_error' => __( "Couldn't add donations to cart, Do you still want to proceed checkout?", 'give-woocommerce' ),
				'currency_settings' => [
					'currency' => get_woocommerce_currency(),
					'decimal_numbers' => wc_get_price_decimals(),
					'symbol' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_COMPAT, 'UTF-8' ),
					'decimal_sep' => esc_attr( wc_get_price_decimal_separator() ),
					'thousand_sep' => esc_attr( wc_get_price_thousand_separator() ),
					'currency_position' => $give_currency_position,
				],
			]
		);
	}

	/**
	 * Render the Give Donation form
	 *
	 * @since 1.0.0
	 *
	 * @param array|int $donation_forms Array of the Give Donation form IDs.
	 *
	 */
	public static function render_donation_forms( $donation_forms ) {
		// Make the donation array.
		$donation_forms = is_array( $donation_forms ) ? $donation_forms : (array) $donation_forms;

		// If donations form array is not empty.
		if ( ! empty( $donation_forms ) ) {

			// Collect donation form which is exists.
			$exists_forms = array_filter(
				$donation_forms, function ( $form ) {
				return get_post( $form );
			}
			);

			// If no form exists.
			if ( empty( $exists_forms ) ) {
				return;
			}

			// Get list of the Give Donation from the WC Session.
			$wc_session = WC()->session->get( 'give_wc_donation_forms' );

			// Render each of the donation form.
			foreach ( $exists_forms as $form_id ) {
				// Get the selected donation form.
				$donations_forms = ! empty( $wc_session ) ? $wc_session : [];

				// Get the description.
				$form_description = has_excerpt( $form_id ) ? get_the_excerpt( $form_id ) : '';
				$form_args = '';

				foreach ( give_wc_get_form_args( $form_id ) as $tag => $value ) {
					$form_args .= " data-{$tag}=\"{$value}\" ";
				}

				set_query_var( 'form_ids', array_keys( $donations_forms ) );
				set_query_var( 'form_id', $form_id );
				set_query_var( 'form_description', $form_description );
				set_query_var( 'form_args', $form_args );
				set_query_var( 'display_type', WC_Admin_Settings::get_option( 'give_wc_multi_level_display_style' ) );

				// Pass donation forms from wc session.
				set_query_var( 'wc_donation_session', isset( $donations_forms[ $form_id ] ) ? $donations_forms[ $form_id ] : [] );

				// If this form already amount.
				if ( ! isset( $donations_forms[ $form_id ]['amount'] ) ) {
					$default_amount = give_has_variable_prices( $form_id ) && isset( $donations_forms[ $form_id ]['price_id'] )
						? give_get_price_option_amount( $form_id, $donations_forms[ $form_id ]['price_id'] )
						: give_get_default_form_amount( $form_id );
				} else {
					// Get the default amount.
					$default_amount = $donations_forms[ $form_id ]['amount'];
				}

				// Pass default amount and session id.
				set_query_var( 'default_amount', $default_amount );
				set_query_var( 'give_wc_session_id', md5( session_id() ) );
				set_query_var( 'form_custom_amount', give_is_setting_enabled( give_get_meta( $form_id, '_give_custom_amount', true ) ) );
				set_query_var( 'currency_position', give_get_currency_position() );

				// Call template to render the single donation.
				give_wc_get_template( 'donation', 'single' );
			}
		}
	}

	/**
	 * List the donations.
	 *
	 * @since 1.0.0
	 */
	public function give_wc_list_give_donations() {

		// Get the Give Donation status and location.
		$give_donation_enabled = WC_Admin_Settings::get_option( 'give_wc_donation_enabled' );
		$give_donation_location = WC_Admin_Settings::get_option( 'give_wc_donation_location' );
		$give_donations = WC_Admin_Settings::get_option( 'give_wc_donation_forms' );

		// Check if Give Donation is enabled for WC.
		if (
			is_array( $give_donations )
			&& ! empty( $give_donations )
			&& give_is_setting_enabled( $give_donation_enabled )
			&& (
				(
					is_checkout()
					&& 'checkout' === $give_donation_location
				)
				|| (
					is_cart()
					&& 'cart' === $give_donation_location
				)
			)
			&& apply_filters( 'give_wc_should_donation_upsell_display', true, $give_donation_location, $give_donations )
		) {

			/**
			 * Before Donation listing.
			 */
			do_action( 'give_wc_before_cart_donation' );

			// Call template anywhere.
			give_wc_get_template( 'donation', 'wrapper' );

			/**
			 * After Donation listing HTMl.
			 */
			do_action( 'give_wc_after_cart_donation' );
		}
	}

	/**
	 * Notify the admin that the discount/coupon never applies on the Give Donation on Cart/Checkout page.
	 *
	 * @since 1.0.0
	 */
	public function give_wc_show_donation_restriction_msg() {
		?>
        <div class="options_group">
            <p class="form-field give_wc_discount_notice ">
				<?php
				echo sprintf(
					'<b>%1$s</b> %2$s',
					__( 'NOTE:', 'give-woocommerce' ),
					__( 'Discounts are never applied to Give Donations at checkout.', 'give-woocommerce' )
				);
				?>
            </p>
        </div>
		<?php
	}
}

// Initialize the class.
new Give_WooCommerce_Frontend();
