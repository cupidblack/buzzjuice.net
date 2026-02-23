<?php
/**
 * Render WC donation title.
 *
 * @since 1.0.0
 *
 * @param integer $form_id Donation Form ID.
 *
 * @return string
 */
function give_wc_render_donation_form_title( $form_id ) {
	// Get the display text.
	$display_text     = WC_Admin_Settings::get_option( 'give_wc_display_text', '{donation_title}' );
	$formatted_amount = give_currency_filter( give_format_amount( give_get_default_form_amount( $form_id ) ) );

	// Handle if display title is empty.
	$display_text = empty( $display_text ) ? '{donation_title}' : $display_text;

	// Remove amount tag if Donation Form supports multi-level.
	if ( give_has_variable_prices( $form_id ) ) {
		$display_text = str_replace( '{donation_amount}', $formatted_amount, $display_text );
	}

	// Get the checkbox text.
	$donation_form_title = str_replace(
		array( '{donation_title}', '{donation_amount}' ),
		array( esc_html( get_the_title( $form_id ) ), $formatted_amount ),
		$display_text
	);

	/**
	 * Allow developers to modify the form title.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $donation_form_title Donation Form Title.
	 * @param integer $form_id             Donation Form Id.
	 */
	return apply_filters( 'give_wc_donation_form_title', $donation_form_title, $form_id );
}

/**
 * Don't display WooCommerce gateway.
 *
 * @since 1.0.0
 *
 * @param array $gateways List of Give's gateway.
 *
 * @return mixed
 */
function give_wc_remove_woocommerce_gateway( $gateways ) {
	// Remove WooCommerce gateway.
	unset( $gateways['woocommerce'] );

	return $gateways;
}

// Don't show WooCommerce gateway to front-end.
add_filter( 'give_enabled_payment_gateways', 'give_wc_remove_woocommerce_gateway', 10, 1 );

// Remove WooCommerce gateway from the Give Setting page.
add_filter( 'give_payment_gateways_order', 'give_wc_remove_woocommerce_gateway', 10, 1 );

/**
 * Update Give Donation associated with it when order was updated.
 *
 * @since 1.0.0
 *
 * @param \WC_Order $wc_order WC Order.
 */
function give_wc_order_updated( $wc_order ) {

	/** @var \Give_WooCommerce_Sync $sync */
	$sync = new Give_WooCommerce_Sync( $wc_order->get_id() );

	// Is Refund Process?
	$is_refund_process = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

	// If This donation is attached to any wc order.
	if ( isset( $is_refund_process ) && 'woocommerce_refund_line_items' !== $is_refund_process && $sync->get_wc_order() ) {
		$sync->update_donation_address();
	}
}

// When order is updated.
add_action( 'woocommerce_order_object_updated_props', 'give_wc_order_updated', 10 );

/**
 * Update Give Donation status when wc order status changed.
 *
 * @since 1.0.0
 *
 * @param integer $order_id WC Order.
 */
function give_wc_order_status_changed( $order_id ) {
	if ( 'shop_order' === get_post_type( $order_id ) ) {
		/** @var \Give_WooCommerce_Sync $sync */
		$sync = new Give_WooCommerce_Sync( $order_id );

		// If This donation is attached to any wc order.
		if ( $sync->get_wc_order() ) {
			$sync->sync_status();
		}
	}
}

// Change Give Donation status when wc order status changes.
add_action( 'woocommerce_order_status_changed', 'give_wc_order_status_changed', 10 );
add_action( 'trashed_post', 'give_wc_order_status_changed', 10 );
add_action( 'untrashed_post', 'give_wc_order_status_changed', 99 );

/**
 * Delete Give donation when wc order deleted.
 *
 * @since 1.0.0
 *
 * @param integer $order_id WC order ID.
 */
function give_wc_order_delete( $order_id ) {
	if ( 'shop_order' === get_post_type( $order_id ) ) {
		/** @var \Give_WooCommerce_Sync $sync */
		$sync = new Give_WooCommerce_Sync( $order_id );

		// If This donation is attached to any wc order.
		if ( $sync->get_wc_order() ) {
			$sync->check_and_delete_donation();
		}
	}
}

// Hook when wc order deleted permanently.
add_action( 'before_delete_post', 'give_wc_order_delete', 10 );

/**
 * Get the donations id by wc order id.
 *
 * @since 1.0.0
 *
 * @param integer $wc_order_id WC Order ID.
 *
 * @throws \Give_WC_Exception
 * @return  array|bool Array of Give Donation IDs, false if doesn't exists.
 */
function give_wc_donation_by_order_id( $wc_order_id ) {

	/** @var \WC_Order $wc_order */
	$wc_order = wc_get_order( $wc_order_id );

	// If WC order is missing.
	if ( ! $wc_order_id ) {
		throw new Give_WC_Exception( __( 'WC order doesn\'t exists', 'give-woocommerce' ) );
	}

	// Get the give donations id.
	$give_donations_id = $wc_order->get_meta( '_give_donations_id' );

	// Return donation IDs or false.
	return ! empty( $give_donations_id ) ? $give_donations_id : false;
}

/**
 * Check if Give Donation is related to any WC order or not.
 *
 * @since 1.0.0
 *
 * @param integer $donation_id Give Donation ID.
 *
 * @return bool
 */
function give_wc_is_woo_donation( $donation_id ) {
	// If donation id is blank.
	if ( empty( $donation_id ) ) {
		return false;
	}

	// Get the value if donation was made within WC_Order.
	$give_wc_enabled = give_get_meta( $donation_id, '_give_is_wc_donation', true );

	/**
	 * Filter the donation type.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $wc_state    Is donation is related to WC or not.
	 * @param integer $donation_id Give Donation ID.
	 */
	return apply_filters( 'give_is_wc_donation', ( 'true' === $give_wc_enabled ), $donation_id );
}

/**
 * Render Donation level fields.
 *
 * @since 1.0.0
 *
 * @param int   $form_id             Donation form ID.
 * @param array $wc_donation_session Donation from the WC Session list.
 */
function give_wc_render_multi_level( $form_id, $wc_donation_session ) {

    $html = '';

	// Get the level display style.
	$level_display_style = WC_Admin_Settings::get_option( 'give_wc_multi_level_display_style' );
	$selected_price_id   = isset( $wc_donation_session['price_id'] ) ? $wc_donation_session['price_id'] : false;

	// Get the prices.
	$prices             = give_get_variable_prices( $form_id );
	$custom_amount      = give_get_meta( $form_id, '_give_custom_amount', true );
	$custom_amount_text = give_get_meta( $form_id, '_give_custom_amount_text', true );

	switch ( $level_display_style ) {

		case 'dropdown':

		    $html .= sprintf(
                '<select id="%1$s" name="give_wc_form-data[%2$s][price_id]" class="%3$s">',
                "give-donation-level-select-{$form_id}",
                $form_id,
                'give-wc-select give-wc-select-level'
            );

			foreach ( $prices as $price ) {
				$level_text = ! empty( $price['_give_text'] ) ?
					$price['_give_text'] :
					give_currency_filter(
						give_format_amount(
							$price['_give_amount'],
							array(
								'sanitize' => false,
							)
						),
						array(
							'currency_code' => give_get_currency( $form_id ),
						)
					);
				$level_text = apply_filters( 'give_form_level_text', $level_text, $form_id, $price );

				// Formatted donation amount.
				$formatted_amount = give_format_amount(
					$price['_give_amount'], array(
						'sanitize' => false,
						'currency' => give_get_currency( $form_id ),
					)
				);

				$selected_level = '';

				if (
					isset( $price['_give_default'] ) &&
					'custom' !== $selected_price_id &&
					false === $selected_price_id
				) {
					$selected_level = 'selected';
				} elseif ( $price['_give_id']['level_id'] === $selected_price_id ) {
					$selected_level = 'selected';
				}

				$html .= sprintf(
					'<option class="%1$s" value="%2$s" data-price-id="%2$s" data-amount="%3$s" %4$s>%5$s</option>',
					"give_wc_level_{$price['_give_id']['level_id']}",
					$price['_give_id']['level_id'],
					$formatted_amount,
					esc_attr( $selected_level ),
					$level_text
				);
			}

			// Custom Amount.
			if ( give_is_setting_enabled( $custom_amount ) ) {
				$html .= sprintf(
					'<option data-price-id="%1$s" class="give-donation-level-custom" value="%1$s" %2$s>%3$s</option>',
					'custom',
					selected( $selected_price_id, 'custom', false ),
					! empty( $custom_amount_text ) ? esc_html( $custom_amount_text ) : __( 'Custom Amount', 'give-woocommerce' )
				);
			}

            $html .= '</select>';
			break;

		case 'radio':
		    $html .= '<ul class="give-wc-donation-level-radio">';

		    foreach ( $prices as $price ) {

				$level_text = ! empty( $price['_give_text'] ) ?
					$price['_give_text'] :
					give_currency_filter(
						give_format_amount(
							$price['_give_amount'],
							array(
								'sanitize' => false,
							)
						),
						array(
							'currency_code' => give_get_currency( $form_id ),
						)
					);
				$level_text = apply_filters( 'give_form_level_text', $level_text, $form_id, $price );

		        // Format the amount.
				$formatted_amount = give_currency_filter(
                    give_format_amount(
                        $price['_give_amount'],
                        array(
                            'sanitize' => false,
                        )
                    )
                );

				$checked_radio    = '';
				if (
					isset( $price['_give_default'] ) &&
					'custom' !== $selected_price_id &&
					false === $selected_price_id
				) {
					$checked_radio = 'checked';
				} elseif ( $price['_give_id']['level_id'] === $selected_price_id ) {
					$checked_radio = 'checked';
				}

				$html .= '<li><label>';
				$html .= sprintf(
                    '<input type="radio" class="%1$s" name="give_wc_form-data[%2$s][price_id]" value="%3$s" data-price-id="%3$s" data-amount="%4$s" %5$s /> %6$s',
                    "give-wc-donation-level give_wc_level_{$price['_give_id']['level_id']}",
                    $form_id,
					$price['_give_id']['level_id'],
                    $formatted_amount,
					esc_attr( $checked_radio ),
                    $level_text
                );
				$html .= '</label></li>';
			}

			// Custom Amount.
			if ( give_is_setting_enabled( $custom_amount ) ) {
			    $html .= '<li><label>';
			    $html .= sprintf(
                    '<input type="radio" class="%1$s" name="give_wc_form-data[%2$s][price_id]" value="%3$s" data-price-id="%3$s" %4$s /> %5$s',
                    'give-wc-donation-level give_wc_level_custom',
                    $form_id,
                    'custom',
					checked( $selected_price_id, 'custom', false ),
					! empty( $custom_amount_text ) ? $custom_amount_text : __( 'Custom Amount', 'give-woocommerce' )
                );
				$html .= '</label></li>';
			}

		    $html .= '</ul>';
			break;
	}

	// Output HTML.
	echo $html;
}

/**
 * Get the template.
 * To override the template in theme make folder named 'give-woocommerce'.
 *
 * @since 1.0.0
 *
 * @param string $slug Template slug.
 * @param string $name Template name.
 */
function give_wc_get_template( $slug, $name = '' ) {
	// Look in yourtheme/slug-name.php and yourtheme/give-woocommerce/slug-name.php
	$template = locate_template( array( "{$slug}-{$name}.php", 'give-woocommerce/' . "{$slug}-{$name}.php" ) );

	// Get default slug-name.php
	if ( ! $template && $name && file_exists( GIVE_WOOCOMMERCE_PLUGIN_DIR . "/templates/{$slug}-{$name}.php" ) ) {
		$template = GIVE_WOOCOMMERCE_PLUGIN_DIR . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/give-woocommerce/slug.php
	if ( ! $template ) {
		$template = include( locate_template( array( "{$slug}.php", 'give-woocommerce/' . "{$slug}.php" ) ) );
	}

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get the form arguments
 *
 * @since 1.0.0
 *
 * @param integer $form_id Donation Form ID.
 *
 * @return mixed
 */
function give_wc_get_form_args( $form_id ) {

	$donation_form_args = array(
		'minimum'       => give_maybe_sanitize_amount( give_get_form_minimum_price( $form_id ) ),
		'maximum'       => give_maybe_sanitize_amount( give_get_form_maximum_price( $form_id ) ),
		'title'         => get_the_title( $form_id ),
		'form_id'       => $form_id,
		'custom_amount' => give_get_meta( $form_id, '_give_custom_amount', true ),
	);

	/**
	 * Allow developers to alter the list of the form arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $form_args List of the donation form arguments.
	 * @param integer $form_id   Donation Form ID.
	 */
	return apply_filters( 'give_wc_form_args', $donation_form_args, $form_id );
}

/**
 * This helper function is responsible to merge the selected donation list
 * to the Donation in the WC current Cart.
 *
 * @since 1.0.0
 *
 * @param array $posted_donations List of the donation which is already in WC Cart session.
 * @param array $append_donations List of the donation which needs to be merged along with the WC cart session.
 *
 * @return array
 */
function give_wc_combine_donations( $posted_donations, $append_donations = array() ) {
	if ( ! empty( $posted_donations ) ) {

		foreach ( $posted_donations as $form_id => $donation_form ) {
			// Remove from the WC session if already selected.
			if ( isset( $append_donations[ $form_id ] ) ) {
				unset( $append_donations[ $form_id ] );
			}

			$posted_amount = give_clean( $donation_form['give-amount'] );
			$posted_amount = (float) give_sanitize_amount( $posted_amount );

			// Ignore, if not selected.
			if ( ! isset( $donation_form['selected'] ) || 'on' !== $donation_form['selected'] || empty( $posted_amount ) ) {
				continue;
			}

			// Get the status of the custom amount.
			$custom_amount = give_get_meta( $form_id, '_give_custom_amount', true );

			// Get the amount.
			$amount = give_is_setting_enabled( $custom_amount ) ? give_clean( $donation_form['give-amount'] ) : give_get_default_form_amount( $form_id );

			// If form has variable prices.
			if ( give_has_variable_prices( $form_id ) ) {

				$price_id = isset( $donation_form['price_id'] ) ? give_clean( $donation_form['price_id'] ) : false;

				// If price id or custom amount were selected.
				if ( false !== $price_id && 'custom' === $price_id && give_is_setting_enabled( $custom_amount ) ) {
					$amount = $posted_amount;
				}else{
					$amount   = give_get_price_option_amount( $form_id, $price_id );
				}

				// Store price id.
				$append_donations[ $form_id ]['price_id'] = $price_id;
			}
			// Store the form_id and form id.
			$append_donations[ $form_id ]['amount'] = give_sanitize_amount_for_db( $amount );
		}
	}

	return $append_donations;
}

// Override and Revert the Give Currency formatting setting from WooCommerce currency setting.
add_action( 'give_wc_before_cart_donation', 'give_wc_set_woocommerce_currency_setting' );
add_action( 'give_wc_after_cart_donation', 'give_wc_set_woocommerce_currency_setting' );

/**
 * Attach/de-attach the filter to modify the currency settings.
 *
 * @since 1.0.0
 */
function give_wc_set_woocommerce_currency_setting() {
	if ( 'give_wc_before_cart_donation' === current_filter() ) {
		add_filter( 'give_get_currency_formatting_settings', 'give_wc_override_currency_formatting', 10, 1 );
		add_filter( 'give_currency', 'give_wc_override_give_currency', 10, 1 );
		add_filter( 'give_get_option_currency_position', 'give_wc_override_position', 10 );
	} else {
		remove_filter( 'give_get_currency_formatting_settings', 'give_wc_override_currency_formatting', 10 );
		remove_filter( 'give_currency', 'give_wc_override_give_currency', 10 );
		remove_filter( 'give_get_option_currency_position', 'give_wc_override_position', 10 );
	}
}

/**
 * Override the Give currency formatting setting.
 *
 * @since 1.0.0
 *
 * @param array $currency_formatting Currency formatting array.
 *
 * @return array
 */
function give_wc_override_currency_formatting( $currency_formatting ) {
	$currency_formatting = wp_parse_args(
		array(
			'number_decimals'     => wc_get_price_decimals(),
			'symbol'              => get_woocommerce_currency_symbol(),
			'decimal_separator'   => esc_attr( wc_get_price_decimal_separator() ),
			'thousands_separator' => esc_attr( wc_get_price_thousand_separator() ),
		), $currency_formatting
	);

	return $currency_formatting;
}

/**
 * Change the Give Currency as per the WooCommerce currency.
 *
 * @since 1.0.0
 *
 * @return string
 */
function give_wc_override_give_currency() {
	return get_woocommerce_currency();
}

/**
 * Override the currency position as per WooCommerce currency setting.
 *
 * @since 1.0.0
 *
 * @param string $give_currency_position Give currency position.
 *
 * @return string
 */
function give_wc_override_position( $give_currency_position ) {
	$currency_position = get_option( 'woocommerce_currency_pos' );

	if ( in_array( $currency_position, array( 'left', 'left_space' ), true ) ) {
		$give_currency_position = 'before';
	}

	if ( in_array( $currency_position, array( 'right', 'right_space' ), true ) ) {
		$give_currency_position = 'after';
	}

	return $give_currency_position;
}

/**
 * Update the WC fee meta data when donation form for any donation has changed.
 *
 * @since 1.0.0
 *
 * @param int $give_donation_id Donation ID.
 * @param int $old_form_id      Old Form ID.
 * @param int $new_form_id      New Form ID.
 *
 * @return bool
 */
function give_wc_update_wc_order_fee_data( $give_donation_id, $old_form_id, $new_form_id ) {
	// Get the WC Order id associated donation.
	$wc_order_id = give_get_meta( $give_donation_id, '_give_wc_order_id', true );

	/** @var WC_Order $wc_order */
	$wc_order = wc_get_order( $wc_order_id );

	if ( ! ( $wc_order instanceof WC_Order ) ) {
		return false;
	}

	// Get the wc order fee.
	$order_fees = $wc_order->get_fees();

	if ( empty( $order_fees ) ) {
		return false;
	}

	foreach ( $order_fees as $fee_data ) {
		// Get the fee meta data.
		$item_meta_data = wc_get_order_item_meta( $fee_data->get_id(), '_give_wc_form_relation', true );

		// Sanitize.
		$fee_form_id = isset( $item_meta_data['new_form_id'] ) ? $item_meta_data['new_form_id'] : absint( $item_meta_data['form_id'] );

		// If the selected form is not same form as old one.
		if ( $fee_form_id === absint( $old_form_id ) && $fee_form_id !== absint( $new_form_id ) ) {

			// Replace it with new form id.
			$item_meta_data['new_form_id'] = $new_form_id;

			// Get the selected price ID.
			$price_id = isset( $_POST['give-variable-price'] ) && 'custom' !== $_POST['give-variable-price'] ? $_POST['give-variable-price'] : null;

			// Update the title of the fee title.
			wc_update_order_item( $fee_data->get_id(), array( 'order_item_name' => give_wc_donation_fee_title( $item_meta_data['new_form_id'], $price_id ) ) );

			// Update WC order fee meta.
			wc_update_order_item_meta( $fee_data->get_id(), '_give_wc_form_relation', $item_meta_data );
		}
	}

	return true;
}

/**
 * Get the title for the WC Order Item title.
 *
 * @since 1.0.0
 *
 * @param integer        $form_id  Donation Form ID.
 * @param integer|string $price_id Donation Form Variable ID.
 *
 * @return string Fee title for the WC Order item.
 */
function give_wc_donation_fee_title( $form_id, $price_id ) {
	// Fee amount title.
	$wc_donation_title = __( 'Donation:', 'give-woocommerce' ) . ' ' . get_the_title( $form_id );

	if ( give_has_variable_prices( $form_id ) && 'custom' !== $price_id ) {
		$wc_donation_title .= ' - ' . give_get_price_option_name( $form_id, $price_id );
	}

	/**
	 * Alter the fee amount title.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $wc_donation_title Title for fee amount.
	 * @param integer $form_id           Donation Form ID.
	 */
	return apply_filters( 'give_wc_donation_title', $wc_donation_title, $form_id );
}
