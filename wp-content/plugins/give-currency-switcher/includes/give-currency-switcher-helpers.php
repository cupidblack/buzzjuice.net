<?php
/**
 * Give - Currency Switcher Helper functions.
 *
 * @package    Give_Currency_Switcher
 * @subpackage Give_Currency_Switcher/includes
 * @author     GiveWP <info@givewp.com>
 */

use GiveCurrencySwitcher\ExchangeRates\Repositories\ExchangeRates;

/**
 * Build the setting array of the currency switcher for
 * global and per form as well.
 *
 * @since 1.0
 *
 * @param string $active_section Get the active tab ID.
 *
 * @param bool   $global These settings are global or not.
 *
 * @return array $settings
 */
function cs_get_setting_fields( $global, $active_section = 'general-settings' ) {

	// Get the current tab.
	$active_section = give_get_current_setting_section() ? give_get_current_setting_section() : $active_section;

	// Store settings fields.
	$settings = [];

	// Append the setting field according to the active section.
	switch ( $active_section ) {
		case 'general-settings':
			// Add initial state option for the currency switcher add-on.
			if ( ! $global ) {
				$options = [
						'global' => __( 'Global Option', 'give-currency-switcher' ),
						'enabled' => __( 'Customize', 'give-currency-switcher' ),
						'disabled' => __( 'Disabled', 'give-currency-switcher' ),
				];
			} else {
				$options = [
						'enabled' => __( 'Enabled', 'give-currency-switcher' ),
						'disabled' => __( 'Disabled', 'give-currency-switcher' ),
				];
			}

			// Enable/Disable Currency Switcher option.
			$settings[] = [
					'name' => __( 'Currency Switcher', 'give-currency-switcher' ),
					'desc' => $global ? __( 'This enables the Give Currency Switcher for all your website\'s donation forms. Note: You can disable the global options and enable and customize options per form as well.', 'give-currency-switcher' ) : __( 'This allows you to customize the Currency Switcher settings for just this donation form. You can disable Currency Switcher for just this form as well or simply use the global settings.', 'give-currency-switcher' ),
					'id' => 'cs_status',
					'wrapper_class' => 'cs_status',
					'type' => 'radio_inline',
					'default' => ( ! $global ) ? 'global' : 'disabled',
					'options' => $options,
			];

			// Whether to show Currency Acronym or not.
			$settings[] = [
					'name' => __( 'Display Currency Acronym', 'give-currency-switcher' ),
					'desc' => __( 'This option will add the currency acronym (USD, EUR, GBP) before the currency symbol on the donation form display to make it easier for donors to recognize currencies. This is helpful when you have multiple currencies sharing the same symbol. For example, MXN and USD share "$" as a symbol. With this option enabled the donation form would display "MXN $" when that currency was selected within the dropdown.', 'give-currency-switcher' ),
					'id' => 'cs_currency_acronym',
					'wrapper_class' => 'cs_general_fields cs_currency_acronym give-hidden',
					'type' => 'radio_inline',
					'default' => 'disabled',
					'options' => [
							'enabled' => __( 'Enabled', 'give-currency-switcher' ),
							'disabled' => __( 'Disabled', 'give-currency-switcher' ),
					],
			];

			// Whether to automatically switch currency or not.
			$settings[] = [
					'name' => __( 'Auto-Switch Currency', 'give-currency-switcher' ),
					'desc' => __( 'Enabling this will automatically switch the currency to the donor\'s currency from their latest donation.', 'give-currency-switcher' ),
					'id' => 'cs_currency_autoswitcher',
					'wrapper_class' => 'cs_general_fields cs_currency_autoswitcher give-hidden',
					'type' => 'radio_inline',
					'default' => 'enabled',
					'options' => [
							'enabled' => __( 'Enabled', 'give-currency-switcher' ),
							'disabled' => __( 'Disabled', 'give-currency-switcher' ),
					],
			];

			// Show donor differences between the base currency and new currency.
			$settings[] = [
					'id' => 'cs_message',
					'name' => __( 'Currency Switcher Message', 'give-currency-switcher' ),
					'type' => 'text',
					'default' => give_cs_get_localized_string( 'cs_message' ),
					'wrapper_class' => 'cs_general_fields cs_message give-hidden',
					'description' => __( 'Displays to the donor the difference between the base currency amount and the new currency amount.', 'give-currency-switcher' ),
					'attributes' => [
							'placeholder' => give_cs_get_localized_string( 'cs_message' ),
					],
			];

			// Get the currencies and list them as checkbox.
			$settings[] = [
					'id' => 'cs_supported_currency',
					'name' => __( 'Supported Currencies', 'give-currency-switcher' ),
					'desc' => __( 'Select the currencies you would like to support. Note: the GiveWP Base Currency set in the Currency settings will be enabled automatically.', 'give-currency-switcher' ),
					'type' => 'cs_support_currency_list',
					'wrapper_class' => 'cs_general_fields cs_supported_currency give-hidden',
					'default' => give_get_currency(),
					'multiple' => true,
					'options' => give_get_currencies( 'all' ),
			];

			// Get the currencies and list them as checkbox.
			$settings[] = [
					'id' => 'cs_exchange_rates',
					'name' => __( 'Exchange Rates', 'give-currency-switcher' ),
					'wrapper_class' => 'cs_general_fields cs_exchange_rates give-hidden',
					'desc' => __( 'By default, exchange rates are set automatically and refresh daily. If you want to override those settings for any reason, the above section allows you to manually set the exchange rates.', 'give-currency-switcher' ),
					'type' => 'exchange_rates',
					'multiple' => true,
			];
			break;
		case 'geolocation':
			// Geo-location enable/disable radio option.
			$settings[] = [
					'name' => __( 'Geolocation', 'give-currency-switcher' ),
					'desc' => sprintf( __( 'Enable automatic selection of Currency depending on Visitors\' location. This feature uses GeoLite data created by <a href="%1$s" target="_blank">MaxMind</a>.', 'give-currency-switcher' ), 'http://www.maxmind.com' ),
					'id' => 'cs_geolocation_state',
					'wrapper_class' => 'cs_geolocation_state',
					'type' => 'radio_inline',
					'default' => 'disabled',
					'options' => [
							'enabled' => __( 'Enabled', 'give-currency-switcher' ),
							'disabled' => __( 'Disabled', 'give-currency-switcher' ),
					],
			];

			// Set Base currency, In case Geo-location fails.
			$settings[] = [
					'name' => __( 'Base Currency (if fails)', 'give-currency-switcher' ),
					'desc' => __( 'Select the currency to use by default when a visitor comes from a country whose currency is not supported by your site, or when geolocation resolution fails.', 'give-currency-switcher' ),
					'id' => 'cs_geo_base_currency',
					'wrapper_class' => 'cs_geo_location_fields cs_geo_base_currency give-hidden',
					'type' => 'select',
					'default' => 'USD',
					'options' => give_get_currencies(),
			];

			break;
		case 'payment-gateway':
			// Currency Switcher payment field.
			$settings[] = [
					'id' => 'cs_payment_gateway',
					'name' => __( 'Payment Gateways', 'give-currency-switcher' ),
					'desc' => __( 'Set the payment gateways available when paying in each currency.', 'give-currency-switcher' ),
					'type' => 'cs_gateway',
			];

			break;
	} // End switch().

	// Help doc URL.
	$settings[] = [
			'name' => __( 'Give - Currency Switcher Settings Docs Link', 'give-currency-switcher' ),
			'id' => 'give_currency_switcher',
			'url' => esc_url( 'http://docs.givewp.com/addon-currency-switcher' ),
			'title' => __( 'Give - Currency Switcher Settings', 'give-currency-switcher' ),
			'type' => $global ? 'give_docs_link' : 'docs_link',
	];

	if ( $global ) {

		// Prepend the start section.
		array_unshift(
				$settings,
				[
						'type' => 'title',
						'id' => 'cs_settings',
				]
		);

		// End the section.
		$settings[] = [
				'type' => 'sectionend',
				'id' => 'cs_settings',
		];
	}

	return $settings;
}

/**
 * Render Currency Switcher custom field in global setting.
 *
 * @since 1.0
 *
 * @param array  $field Array of field option.
 * @param string $options Saved values.
 * @param string $type Field type.
 *
 * @return bool
 */
function give_cs_render_global_option( $field, $options, $type = '' ) {

	// Field render function.
	$render_func = "cs_render_{$type}_field";

	// If the type is empty or function is not exists, return false.
	if ( empty( $type ) || ! function_exists( $render_func ) ) {
		return false;
	}

	$field['style'] = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['name'] = isset( $field['name'] ) ? $field['name'] : $field['id'];
	?>
	<tr valign="top" id="<?php echo esc_attr( $field['id'] ); ?>_field"
		class="<?php echo esc_attr( $field['wrapper_class'] ); ?>">
		<?php if ( ! empty( $field['name'] ) && '&nbsp;' !== $field['name'] ) : ?>
			<th scope="row" class="titledesc">
				<label
						for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
			</th>
		<?php endif; ?>
		<td class="give-forminp">
			<?php
			// Render the field.
			echo call_user_func_array( $render_func, [ $field, $options ] );
			// Print the description of the field.
			echo Give_Admin_Settings::get_field_description( $field );
			?>
		</td>
	</tr>
	<?php
}

/**
 * Render Currency Switcher custom field in meta-box.
 *
 * @since 1.0
 *
 * @param array $field Array of the field options.
 *
 * @return string | bool
 */
function cs_render_metabox_field( $field ) {
	global $thepostid, $post;

	// Render function.
	$render_function = "cs_render_{$field['type']}_field";

	// If the render function is not exists, return false.
	if ( ! function_exists( $render_function ) ) {
		return false;
	}

	// Donation Form ID.
	$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;

	$field['style'] = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value'] = give_get_field_value( $field, $thepostid );
	$field['name'] = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['custom_attributes'] = [];

	if ( ! empty( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
		foreach ( $field['attributes'] as $attribute => $attribute_value ) {
			if ( ! is_array( $attribute_value ) ) {
				$field['custom_attributes'][] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
	}
	ob_start();
	?>
	<div class="give-field-wrap <?php echo esc_attr( $field['wrapper_class'] ); ?>"
		 id="<?php echo esc_attr( $field['id'] ); ?>_field">
		<label
				for="<?php echo esc_attr( give_get_field_name( $field ) ); ?>"><?php echo wp_kses_post( $field['name'] ); ?></label>
		<div class="cs-metabox-field <?php echo ( 'cs_custom_prices' === $field['type'] ) ? 'give-grid-row' : ''; ?>">
			<?php
			// Render the field options.
			echo call_user_func_array( "cs_render_{$field['type']}_field", [ $field, $field['value'] ] );

			// Print the description.
			echo give_get_field_description( $field );
			?>
		</div>
	</div>
	<?php

	// Return field options and value.
	return ob_get_clean();
}

/**
 * Currency Switcher currency selection per gateway custom field in meta-box.
 *
 * {@internal To render custom meta setting field 'cs_gateway' }}
 *
 * @since 1.0
 *
 * @param array $field Field option array.
 *
 * @return bool
 */
function give_cs_gateway( $field ) {

	if ( empty( $field ) ) {
		return false;
	}
	// Render field HTML.
	echo cs_render_metabox_field( $field );
}

/**
 * Callback for the Currency Switcher 'exchange_rates' field in meta-box.
 *
 * {@internal To render custom meta setting field 'exchange_rates' }}
 *
 * @since 1.0
 *
 * @param array $field Field option array.
 *
 * @return bool
 */
function give_exchange_rates( $field ) {

	if ( empty( $field ) ) {
		return false;
	}
	// Render field HTML.
	echo cs_render_metabox_field( $field );
}

/**
 * Callback for the Currency Switcher 'cs_support_currency_list' field in meta box.
 *
 * {@internal To render custom meta setting field 'cs_support_currency_list' }}
 *
 * @since 1.0
 *
 * @param array $field Field option array.
 *
 * @return null
 */
function give_cs_support_currency_list( $field ) {

	if ( empty( $field ) ) {
		return false;
	}

	// Render field HTML.
	echo cs_render_metabox_field( $field );
}

/**
 * Get the Currency Switcher enabled currency or Give Core currencies.
 *
 * @since 1.0
 *
 * @param integer $post_id Donation Form ID.
 * @param bool    $force_form Force if we want to collect data from form forcefully.
 *
 * @return array
 */
function give_cs_get_active_currencies( $post_id, $force_form = false ) {
	$cs_currencies = [];
	$give_currencies = give_get_currencies();

	// Get supported currencies.
	$currencies = isset( $post_id )
			? give_cs_get_option( 'cs_supported_currency', $post_id, '', $force_form )
			: give_cs_get_option( 'cs_supported_currency' );

	if ( ! empty( $currencies ) ) {
		// Storing currencies.
		foreach ( $currencies as $currency ) {
			$cs_currencies[ $currency ] = $give_currencies[ $currency ];
		}
	}

	/**
	 * Modify supporting currencies.
	 *
	 * @param array $cs_currencies support currencies.
	 */
	return apply_filters( 'give_cs_supported_currencies', array_filter( $cs_currencies ) );
}

/**
 * Custom Field exchange rate render callback function.
 *
 * @since 1.0
 *
 * @param array  $field Field array.
 * @param string $saved_value Saved Value.
 *
 * @return string
 */
function cs_render_exchange_rates_field( $field, $saved_value ) {
	global $post, $pagenow;

	// Get the post ID.
	$post_id = isset( $post->ID ) && ! isset( $_GET['page'] ) ? $post->ID : 0;
	$force_form = false;

	if ( ( isset( $_GET['give_tab'] ) || 'post-new.php' === $pagenow ) && ! empty( $post_id ) ) {
		$force_form = true;
	}

	// Get supported currencies.
	$selected_currencies = give_cs_get_active_currencies( $post_id, $force_form );

	// Wp table columns.
	$table_cols = [
			'currency' => __( 'Currency', 'give-currency-switcher' ),
			'exchange_rates' => __( 'Exchange Rate', 'give-currency-switcher' ),
			'set_manually' => __( 'Set Manually', 'give-currency-switcher' ),
			'number_decimal' => __( 'Decimal Number', 'give-currency-switcher' ),
			'rate_markup' => __( 'Rate Markup', 'give-currency-switcher' ),
	];

	// Store exchange rates.
	$exchange_rates_data = [];

	// If there is any currency is enable.
	if ( ! empty( $selected_currencies ) ) {

		// Generate the array for passing it to WP_List_Table.
		foreach ( $selected_currencies as $key => $currency ) {

			// If base currency.
			if ( give_get_currency() === $key ) {
				continue;
			}

			// Exchange rate fields.
			$exchanges_field_values = [
					'exchange_rates' => '',
					'set_manually' => '',
					'number_decimal' => 2,
					'rate_markup' => '',
			];

			// Get values from the saved values.
			if ( isset( $saved_value[ $key ] ) ) {
				if ( isset( $saved_value[ $key ]['exchange_rate'] ) ) {
					$exchanges_field_values['exchange_rates'] = $saved_value[ $key ]['exchange_rate'];
				}
				if ( isset( $saved_value[ $key ]['set_manually'] ) ) {
					$exchanges_field_values['set_manually'] = $saved_value[ $key ]['set_manually'];
				}
				if ( isset( $saved_value[ $key ]['rate_markup'] ) ) {
					$exchanges_field_values['rate_markup'] = $saved_value[ $key ]['rate_markup'];
				}
				if ( isset( $saved_value[ $key ]['number_decimal'] ) ) {
					$number_decimal = filter_var( $saved_value[ $key ]['number_decimal'], FILTER_VALIDATE_INT );

					if ( $number_decimal < 0 ) {
						$number_decimal = 2;
					}

					$exchanges_field_values['number_decimal'] = $number_decimal;
				}
			}

			// Get supported currencies.
			$cs_currency_acronym = give_cs_get_option( 'cs_currency_acronym', $post_id, 'disabled', $force_form );
			$currency_label = give_get_currency_name( $key );
			$give_cs_currency_acronym = give_is_setting_enabled( $cs_currency_acronym );

			$currency_acronym = ( $give_cs_currency_acronym
					? $key . ' ' . give_currency_symbol( $key )
					: give_currency_symbol( $key )
			);

			// Get the currency label.
			$currency_label = "$currency_label ($currency_acronym)";

			$exchange_rates_data[] = [
					'currency' => [
							'value' => $currency_label,
							'currency_key' => $key,
					],
					'exchange_rates' => [
							'value' => $exchanges_field_values['exchange_rates'],
							'name' => $field['id'] . '[' . $key . '][exchange_rate]',
							'class' => 'exchange_rate',
							'placeholder' => __( 'Enter exchange rate', 'give-currency-switcher' ),
					],
					'set_manually' => [
							'value' => $exchanges_field_values['set_manually'],
							'name' => $field['id'] . '[' . $key . '][set_manually]',
							'class' => 'set_manually',
					],
					'number_decimal' => [
							'value' => isset( $exchanges_field_values['number_decimal'] ) ? $exchanges_field_values['number_decimal'] : '',
							'name' => $field['id'] . '[' . $key . '][number_decimal]',
							'class' => 'number_decimal',
					],
					'rate_markup' => [
							'value' => $exchanges_field_values['rate_markup'],
							'name' => $field['id'] . '[' . $key . '][rate_markup]',
							'class' => 'rate_markup',
					],
			];
		} // End foreach().
	} // End if().

	ob_start();
	?>
	<!-- Start of Fetch exchange rate button -->
	<div class="currency-switcher-table <?php echo esc_attr( $field['id'] ); ?>">
		<?php

		// Render the Exchange Rate field in Table view using WP_List_Table.
		cs_render_wp_table_field( $table_cols, $exchange_rates_data, $field['id'] );

		if ( ! empty( $selected_currencies ) ) {
			?>
			<div class="give-cs-exchange-rate-table-bottom">
				<button
						class="button"
						id="cs-update-exchange-rates"
						data-type="<?php echo ( ! $post && ! isset( $post_id ) ) ? 'global' : 'per_form'; ?>"
						data-formid="<?php echo ( $post && isset( $post_id ) ) ? absint( $post_id ) : ''; ?>"
						<?php echo empty( $exchange_rates_data ) ? 'disabled' : ''; ?>
				>
					<?php
					echo __( 'Fetch Exchange Rates', 'give-currency-switcher' );
					?>
				</button>
				<span class="give-cs-loading-animation give-hidden"></span>
			</div>
			<?php
		} // End If()
		?>
	</div>
	<!-- End of the Fetch Exchange rate button -->
	<?php
	return ob_get_clean();
}

/**
 * Render custom field Exchange rates.
 *
 * @since 1.0
 *
 * @param array  $field Field option array.
 * @param string $saved_value Field saved option values.
 *
 * @return mixed|string
 */
function cs_render_cs_support_currency_list_field( $field, $saved_value ) {

	if (
			! isset( $field['options'] )
			&& ! is_array( $field['options'] )
	) {
		return false;
	}

	ob_start();

	// Get the supported currency list.
	give_cs_render_active_currency_list( $field, $saved_value, 'form' );

	return ob_get_clean();
}

/**
 * Call-back function for rendering the supporting payment gateways.
 *
 * @since 1.0
 *
 * @param array  $field Field option array.
 * @param string $option_value Field saved option values.
 *
 * @return string
 */
function cs_render_cs_gateway_field( $field, $option_value ) {
	global $post;

	// Get the options.
	$option_value = empty( $option_value ) ? give_get_option( $field['id'] ) : $option_value;

	// Table columns.
	$table_cols = [
			'currency_key' => __( 'Currency', 'give-currency-switcher' ),
			'enabled_gateway' => __( 'Enabled Gateways', 'give-currency-switcher' ),
	];

	// Table data.
	$table_data = [];
	$post_id = ! empty( $post->ID ) && ! isset( $_GET['page'] ) ? $post->ID : 0;
	$force_form = true;

	if ( isset( $_GET['tab'], $_GET['section'] ) && 'payment-gateway' === $_GET['section'] && 'currency-switcher' === $_GET['tab'] ) {
		$force_form = false;
	}

	$activated_currency = give_cs_get_active_currencies( $post_id, $force_form );

	// If there is any currency is enable.
	if ( ! empty( $activated_currency ) && is_array( $activated_currency ) ) {

		// Loop all of the enabled currencies.
		foreach ( $activated_currency as $key => $currency ) {

			// Get the list of the payment gateways.
			$payment_gateways = give_get_ordered_payment_gateways( give_get_enabled_payment_gateways() );

			$table_data[ $key ] = [
					'currency_key' => $key,
					'currency_field_name' => $field['id'],
					'currency_label' => $currency,
					'currency_gateways' => $payment_gateways,
					'currency_saved_gateways' => isset( $option_value[ $key ] ) ? $option_value[ $key ] : [],
			];
		}
	}

	ob_start();
	?>
	<div class="currency-switcher-table <?php echo esc_attr( $field['id'] ); ?>">
		<?php
		// Render the wp table.
		cs_render_wp_table_field( $table_cols, $table_data, $field['id'] );
		?>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Is per form enabled.
 *
 * Returns true if Currency Switcher is customized on the form.
 * Useful for checking if a form has Currency Switcher customized.
 *
 * @since 1.0
 *
 * @param int $form_id Donation Form ID.
 *
 * @return bool
 */
function give_cs_is_per_form_customized( $form_id ) {
	return apply_filters( 'give_cs_is_per_form_customized', give_is_setting_enabled( give_get_meta( $form_id, 'cs_status', true ) ) );
}

/**
 * Helper function to get the values of any of the Currency Switcher settings fields.
 *
 * @since 1.0
 *
 * @param string  $option_key Currency Switcher setting field key.
 * @param integer $form_id Form ID.
 * @param string  $default Default value.
 * @param bool    $force_form Get the data from form even if the form is not per form customizable.
 *
 * @return bool|mixed
 */
function give_cs_get_option( $option_key, $form_id = 0, $default = '', $force_form = false ) {
	// Get all the give settings.
	$give_settings = give_get_settings();

	// If Form ID is not empty.
	if ( ! empty( $form_id ) ) {
		// Is Donation Form customizable.
		$is_per_form_customizable = give_cs_is_per_form_customized( $form_id );

		// if the form setting is per form based.
		if (
				$is_per_form_customizable
				|| true === $force_form
		) {
			$option_value = give_get_meta( $form_id, $option_key, true );
		}
	}

	// Get the option value from the global setting.
	if ( empty( $option_value ) && true !== $force_form ) {
		$option_value = ( isset( $give_settings[ $option_key ] ) && ! empty( $give_settings[ $option_key ] ) ) ? $give_settings[ $option_key ] : $default;
	}

	/**
	 * Filter the CS setting field.
	 *
	 * @since 1.0
	 *
	 * @param mixed   $option_value Setting output value.
	 * @param string  $option_key Setting key.
	 * @param integer $form_id Donation Form ID.
	 * @param string  $default Setting default value.
	 */
	return apply_filters( 'give_cs_get_option', $option_value, $option_key, $form_id, $default );
}

/**
 * Check if the Currency Switcher is enabled or disabled.
 *
 * @since 1.0
 *
 * @param integer $form_id Donation Form ID - Keep it blank if you want to check globally.
 *
 * @return bool
 */
function give_cs_is_enabled( $form_id = 0 ) {
	// If Form ID is empty.
	if ( empty( $form_id ) ) {
		return give_is_setting_enabled( give_get_option( 'cs_status' ) );
	}

	// Get the status from the Form ID.
	$status = give_get_meta( $form_id, 'cs_status', true );
	$status = ! empty( $status ) ? $status : 'global';

	if ( 'global' === $status ) {
		$status = give_get_option( 'cs_status' );
	}

	// Return the status from the global or per form option.
	return ( give_is_setting_enabled( $status, 'global' ) || give_is_setting_enabled( $status ) );
}

/**
 * Get all of the currencies supported in donation form.
 *
 * @since 1.0
 *
 * @param integer $form_id Form ID.
 *
 * @return array|bool
 */
function give_cs_get_active_currencies_with_gateways( $form_id ) {
	// Return false, if Form ID is empty.
	if ( empty( $form_id ) ) {
		return false;
	}

	// Get the selected payment gateways.
	$payment_gateways = give_cs_get_option( 'cs_payment_gateway', $form_id );

	// Store currency lists.
	$currency_lists = [];

	if ( empty( $payment_gateways ) ) {
		return $currency_lists;
	}

	$currencies = give_cs_get_active_currencies( $form_id );
	$exchange_rates = give_cs_get_form_exchange_rates( $form_id );

	// Check each of the payment getaway.
	foreach ( $payment_gateways as $currency => $gateway_list ) {
		if (
				in_array( give_get_default_gateway( $form_id ), $gateway_list, true )
				&& array_key_exists( $currency, $currencies )
				&& ! empty( $exchange_rates[ $currency ]['exchange_rate'] )
		) {
			$currency_lists[] = $currency;
		}
	}

	// Return all the supported currencies.
	return array_filter( $currency_lists );
}

/**
 * Helper function to Render setting field using WP_List_Table.
 *
 * @since 1.0
 *
 * @param array  $columns Table Columns.
 * @param array  $data Table row data.
 * @param string $setting_key Setting field name.
 */
function cs_render_wp_table_field( $columns, $data, $setting_key ) {
	/**
	 * Get the table Render class.
	 */
	require_once GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/includes/admin/class-give-render-setting-table.php';

	// Get the instance of the table class.
	$setting_table = new Give_Render_Setting_Table( $columns, $data, $setting_key );

	// Prepare header and items.
	$setting_table->prepare_items();

	// Render the wp table.
	$setting_table->display();
}

/**
 * Get Geo Base Currency.
 *
 * @since 1.0
 *
 * @param integer $form_id Donation Form.
 *
 * @return string
 */
function get_geo_location_base_currency( $form_id = 0 ) {

	// Check if the Geo location is form based enabled or not.
	if ( ! give_is_setting_enabled( give_cs_get_option( 'cs_geolocation_state', $form_id ) ) ) {
		return give_get_currency( $form_id );
	}

	return apply_filters( 'give_cs_geo_base_currency', give_cs_get_option( 'cs_geo_base_currency', $form_id ), $form_id );
}

/**
 * Get the current logged user's currency.
 *
 * @since 1.0
 *
 * @param integer $form_id Form ID.
 *
 * @return array|bool
 */
function give_cs_get_donor_currency( $form_id = 0 ) {
	if ( ! give_cs_is_enabled( $form_id ) ) {
		return false;
	}

	if ( is_user_logged_in() ) {

		// Get the current logged user.
		$current_user = wp_get_current_user();

		/** @var object $donor */
		$donor = Give()->donors->get_donor_by( 'user_id', $current_user->ID );

		// If Donor has currency in meta data.
		if ( $donor ) {
			// Get donor currency from meta.
			$donor_currency = Give()->donor_meta->get_meta( $donor->id, '_give_cs_currency', true );

			if ( ! empty( $donor_currency ) ) {
				return [
						'currency' => $donor_currency,
						'came_from' => 'meta',
				];
			}
		}
	}

	// Get GeoLocation option.
	$geo_location = give_cs_get_option( 'cs_geolocation_state', $form_id, 'disabled' );

	if ( ! give_is_setting_enabled( $geo_location ) ) {
		return false;
	}

	// If the autoloader exists or not.
	if ( file_exists( GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/vendor/autoload.php' ) ) {

		// Get the dependency library.
		require_once GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/vendor/autoload.php';

		/**
		 * Give Geo Location - Fetch the currency code based on the donor's country.
		 *
		 * @since 1.0
		 */
		if ( class_exists( 'Give_Geo_location' ) ) {

			// Get the instance of the Give_Geo_location.
			/** @var Give_Geo_location $give_geo_location */
			$give_geo_location = new Give_Geo_location( $form_id );
			$api_errors = $give_geo_location->get_errors();

			if ( ! $api_errors ) {

				// Get Donor's country code.
				$country_code = $give_geo_location->get_visitor_country();
				$country_name = $give_geo_location->get_donor_country_name( $country_code );

				// Get the currency code based on the donor's country code.
				return [
						'currency' => $give_geo_location->get_currency_by_country( $country_code ),
						'came_from' => 'geo_location',
						'country_name' => $country_name,
				];
			}
		}
	}

	return false;
}

/**
 * Check is specific Currency is supported by specific payment gateway.
 *
 * @since 1.0
 *
 * @param string  $payment_gateway Payment Gateway.
 * @param string  $currency Currency Code.
 * @param integer $form_id Donation Form ID.
 *
 * @return bool
 */
function give_cs_is_support_currency( $payment_gateway, $currency, $form_id = 0 ) {

	// Get the supported payment gateways.
	$gateway_lists = give_cs_get_option( 'cs_payment_gateway', $form_id );

	if ( $currency && isset( $gateway_lists[ $currency ] ) && in_array( $payment_gateway, $gateway_lists[ $currency ], true ) ) {
		return true;
	}

	return false;
}

/**
 * Fetch exchange rate from rate API according to configuration.
 *
 * @since 1.0
 *
 * @param integer $form_id Donation Form ID.
 * @param string  $base_currency Base currency.
 *
 * @return array
 */
function cs_fetch_exchange_rates_from_api( $form_id = 0, $base_currency = '' ) {
	$base_currency = $base_currency ?: give_get_currency( $form_id );
	$support_currencies = give_cs_get_option( 'cs_supported_currency', $form_id );

	$rates = give( ExchangeRates::class )->getRates( $base_currency, $support_currencies );

	return empty( $rates )
			? [
					'rates' => false,
					'missed_currency' => $support_currencies,
					'success' => false,
					'error_message' => __( 'Failed to retrieve exchange rates. Please contact admin and view Log', 'give-currency-switcher' ),
			]
			: [
					'rates' => $rates,
					'missed_currency' => [],
					'success' => true,
					'error_message' => '',
			];
}

/**
 * Convert the donation amount into the donor's currency.
 *
 * @since 1.0
 *
 * @param double  $default_amount Default donation amount.
 * @param integer $form_id Donation Form ID.
 * @param array   $cs_geolocation Pass donor's currency.
 *
 * @return double
 */
function give_cs_convert_donation_amount( $default_amount, $form_id, $cs_geolocation = [] ) {

	// Return default amount if donation form has variable prices.
	if ( give_has_variable_prices( $form_id ) ) {
		return $default_amount;
	}

	if ( empty( $cs_geolocation ) ) {
		// Get the donor currency.
		$cs_geolocation = give_cs_get_donor_currency( $form_id );
	}

	$default_currency = give_get_currency( $form_id );
	if ( isset( $cs_geolocation['currency'] ) && ! empty( $cs_geolocation['currency'] ) ) {
		$default_currency = $cs_geolocation['currency'];
	}

	// Get the exchange rates.
	$currency_rate = give_cs_get_form_exchange_rates( $form_id, $default_currency );

	// Is payment supported by
	$is_support_by_gateway = give_cs_is_support_currency( give_get_default_gateway( $form_id ), $default_currency, $form_id );

	// If exchange rate is not set.
	if ( empty( $currency_rate ) || ! $is_support_by_gateway ) {
		return $default_amount;
	}

	if ( ! empty( $currency_rate ) ) {

		// Calculate the amount with exchange rate + rate markup.
		$default_amount *= $currency_rate;
		$custom_prices = give_cs_get_donation_custom_price( $form_id, $default_currency );

		if ( false !== $custom_prices ) {
			$default_amount = give_maybe_sanitize_amount( $custom_prices['raw_amount'], [ 'currency' => $default_currency ] );
		}
	}

	// Format the amount based on the selected currency.
	return $default_amount;
}

/**
 * Convert variable prices into the donor's currency.
 *
 * @since 1.0
 *
 * @param array   $prices Variable prices.
 * @param integer $form_id Donation Form ID.
 * @param string  $donor_currency Donor's currency.
 *
 * @return mixed
 */
function give_cs_convert_variable_prices( $prices, $form_id, $donor_currency = '' ) {

	// Get the donor's currency.
	if ( empty( $donor_currency ) ) {
		$donor_currency = give_get_currency( $form_id );
	}

	// Get the exchange rates.
	$exchange_rates = give_cs_get_form_exchange_rates( $form_id, $donor_currency );

	if ( empty( $exchange_rates ) ) {
		return $prices;
	}

	$currency_formatting_settings = give_get_currency_formatting_settings( $donor_currency );

	// Convert all of the variable prices into the new currencies.
	foreach ( $prices as $key => $price_data ) {
		$amount = $price_data['_give_amount'] * $exchange_rates;
		$price_id = (int) $price_data['_give_id']['level_id'];
		$custom_prices = give_cs_get_donation_custom_price( $form_id, $donor_currency, $price_id );

		if ( false !== $custom_prices ) {
			$amount = $custom_prices['raw_amount'];
		}

		$amount = str_replace( '.', $currency_formatting_settings['decimal_separator'], $amount );

		// Sanitizing the amount.
		$prices[ $key ]['_give_amount'] = give_sanitize_amount_for_db(
				$amount,
				[ 'currency' => $donor_currency ]
		);
	}

	return $prices;
}

/**
 * Callback for converting the give minimum price.
 *
 * @since 1.0
 *
 * @param double|int|string $min_or_max_amount Donation custom minimum amount.
 * @param integer           $form_id Donation Form ID.
 *
 * @return int|mixed
 */
function give_cs_modify_min_max_amount( $min_or_max_amount, $form_id ) {
	if ( ! empty( $min_or_max_amount ) && ( isset( $_POST ) && empty( $_POST ) ) ) {

		// Get the conversion type.
		$conversion_type = 'give_get_set_minimum_price' === current_filter() ? 'min' : 'max';

		// Get the give $maximum_amount amount.
		$min_or_max_amount = give_cs_convert_min_max_amount( $form_id, give_get_currency( $form_id ), $min_or_max_amount, $conversion_type );
		$min_or_max_amount = give_maybe_sanitize_amount( $min_or_max_amount );
	}

	return $min_or_max_amount;
}

add_filter( 'give_get_set_minimum_price', 'give_cs_modify_min_max_amount', 10, 2 );
add_filter( 'give_get_set_maximum_price', 'give_cs_modify_min_max_amount', 10, 2 );

/**
 * Add action to change the currency according to the donor's currency.
 *
 * @status on-hold
 *
 * @since  1.0
 */
function give_cs_manage_donation_currency() {

	if ( give_is_setting_enabled( give_get_option( 'cs_currency_autoswitcher' ), 'enabled' ) ) {

		// Add and remove action when form start.
		add_action( 'give_pre_form', 'give_cs_set_donor_currency', 10 );

	}
}

/**
 * Change the currency based on the donor's currency.
 *
 * @since 1.0
 */
add_action( 'init', 'give_cs_manage_donation_currency', 10 );

/**
 * Set the donor currency on form load.
 *
 * According to the donor's currency fetched through the GeoLocation.
 *
 * @since 1.0
 *
 * @param bool $set
 */
function give_cs_set_donor_currency( $set = true ) {
	if ( $set ) {
		add_filter( 'give_default_form_amount', 'give_cs_convert_donation_amount', 10, 2 );
		add_filter( 'give_get_donation_levels', 'give_cs_convert_variable_prices', 10, 2 );
	} else {
		remove_filter( 'give_default_form_amount', 'give_cs_convert_donation_amount', 10 );
		remove_filter( 'give_get_donation_levels', 'give_cs_convert_variable_prices', 10 );
	}
}

/**
 * Change currency according to the donor's currency.
 *
 * NOTE: This filter will work only if the post type is give_forms.
 *
 * @since 1.0
 *
 * @param string  $currency Currency code.
 * @param integer $form_id Donation Form or Payment ID.
 *
 * @return mixed
 */
function give_cs_replace_currency( $currency, $form_id ) {
	// Bailout: early exit.
	if ( ! $form_id ) {
		return $currency;
	}

	$default_currency = give_get_meta( $form_id, 'give_cs_default_currency', true );
	$has_default_currency = '0' !== $default_currency && ! empty( $default_currency );

	if ( $form_id ) {
		$is_geo_location = give_is_setting_enabled( give_cs_get_option( 'cs_geolocation_state', $form_id, 'disabled' ) );
		$is_auto_currency_switch = give_is_setting_enabled( give_cs_get_option( 'cs_currency_autoswitcher', $form_id, 'disabled' ) );

		$is_load_form_default_currency = is_user_logged_in()
				? ( ! $is_auto_currency_switch && ! $is_geo_location )
				: ! $is_geo_location;

		if ( $is_load_form_default_currency && $has_default_currency ) {
			return $default_currency;
		}
	}

	if ( give_is_setting_enabled( give_get_option( 'cs_currency_autoswitcher' ), 'disabled' ) ) {
		return $currency;
	}

	// Get the Form ID.
	if (
			( ! is_admin() || wp_doing_ajax() ) // It should not be admin.
			&& 'give_forms' === get_post_type( $form_id ) // Check post type.
			&& $form_id > 0 // Form ID must not be empty.
			&& null !== $form_id // Form ID must not be null.
	) {
		// Get donor currency.
		$donor_currency = give_cs_get_donor_currency( $form_id );

		if ( ! empty( $donor_currency['currency'] ) ) {

			// Donor currency.
			$new_currency = $donor_currency['currency'];

			// Get the form currency.
			$form_currencies = give_cs_get_active_currencies( $form_id );

			// Does donor's currency supported by this donation form ?
			if (
					! empty( $form_currencies )
					&& array_key_exists( $new_currency, $form_currencies )
			) {
				// Get the exchange rates.
				$currency_rate = give_cs_get_form_exchange_rates( $form_id, $new_currency );

				// Check if donor's currency has in supported currencies.
				if ( isset( $currency_rate ) && ! empty( $currency_rate ) ) {
					// Get the support the gateways.
					$support_gateways = give_cs_get_option( 'cs_payment_gateway', $form_id );

					if ( isset( $support_gateways[ $new_currency ] ) ) {
						// Get default payment gateway.
						$active_gateway = give_get_default_gateway( $form_id );

						// Default gateway should support donor's currency ;)
						if ( in_array( $active_gateway, $support_gateways[ $new_currency ], true ) ) {
							return $new_currency;
						}
					}
				}
			}
		}
	}

	return $currency;
}

// Change Donation currency.
add_filter( 'give_currency', 'give_cs_replace_currency', 10, 2 );

/**
 * Render callback for custom price.
 *
 * @since 1.0
 *
 * @param array $field Callback field.
 *
 * @return bool
 */
function give_cs_custom_prices( $field ) {
	if ( empty( $field ) ) {
		return false;
	}

	// Render meta-box field.
	echo cs_render_metabox_field( $field );
}

/**
 * Render Global Field
 *
 * @since 1.0
 *
 * @param array  $field Field array.
 * @param string $option_value Option value.
 * @param string $type Field type.
 *
 * @return bool
 */
function cs_render_cs_custom_prices_field( $field, $option_value, $type = '' ) {
	global $post;

	$form_id = isset( $post->ID ) ? $post->ID : 0;

	// Get supported currency.
	$currencies = give_cs_get_active_currencies( $form_id );
	$base_currency = give_get_currency();

	// Remove base currency.
	if ( isset( $currencies[ $base_currency ] ) ) {
		unset( $currencies[ $base_currency ] );
	}

	if ( ! empty( $currencies ) ) {
		$is_processed = false;

		// Store currencies which has exchange rate.
		$currency_rates = [];

		foreach ( $currencies as $currency_code => $currency_name ) {
			// Get exchange rate.
			$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $currency_code );

			if ( ! empty( $exchange_rate ) ) {
				$currency_rates[ $currency_code ] = $exchange_rate;
			}
		}

		if ( ! empty( $currency_rates ) ) {
			// Create custom price columns chunks.
			$columns_chunk = array_chunk( array_keys( $currency_rates ), ceil( count( array_keys( $currency_rates ) ) / 3 ) );
			foreach ( $columns_chunk as $currency_field ) {
				?>
				<ul class="give-grid-col-4 cs_custom_amount_list">
					<?php
					foreach ( $currency_field as $currency_key ) {
						// Make it as processed.
						$is_processed = true;

						$currency_symbol = give_currency_symbol( $currency_key );
						$amount_val = isset( $option_value ) && isset( $option_value[ $currency_key ] ) ? give_clean( $option_value[ $currency_key ] ) : '';
						$tooltip = give_get_currency_name( $currency_key );
						$currency_position = give_get_option( 'currency_position', 'before' );
						?>
						<li class="cs-custom-amount">
							<?php
							echo( ! empty( $field['before_field'] )
									? $field['before_field']
									: ( $currency_position === 'before'
											? '<span class="give-money-symbol give-money-symbol-before" data-tooltip="' . $tooltip . '">' . $currency_symbol . '</span>'
											: ''
									)
							);
							?>
							<input
									type="text"
									name="<?php echo isset( $field['repeat'] ) ? $field['repeatable_field_id'] : '_give_cs_custom_prices'; ?>[<?php echo $currency_key; ?>]"
									id="cs_custom_prices cs_currency_<?php echo $currency_key; ?>"
									class="give-field give-text_small give-money-field"
									value="<?php echo ! empty( $amount_val ) ? give_format_decimal( esc_attr( $amount_val ) ) : ''; ?>"
									placeholder="<?php echo give_format_decimal( '0.00' ); ?>"
							/>
							<?php
							echo( ! empty( $field['after_field'] )
									? $field['after_field']
									: ( $currency_position === 'after'
											? '<span class="give-money-symbol give-money-symbol-after" data-tooltip="' . $tooltip . '">' . $currency_symbol . '</span>'
											: ''
									)
							);
							?>
						</li>
						<?php
					}
					?>
				</ul>
				<?php
			}
		}
		if ( ! $is_processed ) {
			echo __( 'Please set currency\'s exchange rate first.', 'give-currency-switcher' );
		}
	} else {
		echo __( 'Only base Give currency is enabled, please choose few currencies first.', 'give-currency-switcher' );
	}
}

/**
 * Get the donation custom price
 *
 * @since 1.0
 *
 * @param integer $form_id Donation Form ID.
 * @param string  $currency Currency Code.
 * @param integer $price_id Variable price level id.
 *
 * @return bool|array
 */
function give_cs_get_donation_custom_price( $form_id, $currency = '', $price_id = 0 ) {
	if ( empty( $form_id ) || 'custom' === $price_id ) {
		return false;
	}

	// Store Custom prices.
	$custom_prices = [];

	// If this form has variable prices.
	if ( give_has_variable_prices( $form_id ) ) {

		// Get variable prices.
		$variable_prices = give_get_meta( $form_id, '_give_donation_levels', true );

		foreach ( $variable_prices as $price_data ) {
			if ( ! isset( $price_data['_give_cs_custom_prices'][ $currency ] ) ) {
				continue;
			}

			// Get the custom price.
			$custom_amount = $price_data['_give_cs_custom_prices'][ $currency ];
			$level_id = (int) $price_data['_give_id']['level_id'];

			if ( ! empty( $custom_amount ) && absint( $price_id ) === $level_id ) {
				if ( isset( $price_data['_give_currency_price'] ) ) {
					if ( give_is_setting_enabled( $price_data['_give_currency_price'] ) ) {
						$formatted_amount = give_format_amount( $custom_amount, [ 'currency' => $currency ] );
						$custom_prices['amount'] = give_currency_filter( $formatted_amount, [ 'currency_code' => $currency ] );
						$custom_prices['raw_amount'] = $custom_amount;
					}
				}
			}
		}
	} else {
		// Check if custom amount is enabled or disabled.
		$custom_price_option = give_get_meta( $form_id, '_give_currency_price', true );
		if ( give_is_setting_enabled( $custom_price_option ) ) {

			// Get the list of the custom prices.
			$donation_prices = give_get_meta( $form_id, '_give_cs_custom_prices', true );

			if ( ! empty( $custom_price_option ) && ! empty( $donation_prices ) ) {
				// Check if the custom price is set.
				if ( array_key_exists( $currency, $donation_prices ) && ! empty( $donation_prices[ $currency ] ) ) {
					$formatted_amount = give_format_amount( $donation_prices[ $currency ], [ 'currency' => $currency ] );
					$custom_prices['amount'] = give_currency_filter( $formatted_amount, [ 'currency_code' => $currency ] );
					$custom_prices['raw_amount'] = $donation_prices[ $currency ];
				}
			}
		}
	}

	return empty( $custom_prices ) ? false : $custom_prices;
}


/**
 * Convert Form minimum amount in target currency.
 *
 * @since 1.0
 *
 * @param integer            $form_id Donation Form ID.
 * @param string             $target_currency Target currency.
 * @param string|double|bool $amount Minimum,Maximum amount.
 * @param string|double      $type Amount type.
 *
 * @return int|mixed
 */
function give_cs_convert_min_max_amount( $form_id, $target_currency, $amount, $type = 'min' ) {
	$price_id = '';

	// Price ID hidden field for variable (multi-level) donation forms.
	if ( give_has_variable_prices( $form_id ) ) {
		// Get default selected price ID.
		$prices = give_get_variable_prices( $form_id );
		// loop through prices.
		foreach ( $prices as $price ) {
			if ( isset( $price['_give_default'] ) && 'default' === $price['_give_default'] ) {
				$price_id = $price['_give_id']['level_id'];
				break;
			};
		}
	}

	// Get form's exchange rates.
	$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $target_currency );

	// Get the donation form minimum amount.
	if ( empty( $amount ) ) {
		$amount = 'min' === $type
				? give_get_form_minimum_price( $form_id )
				: give_get_form_maximum_price( $form_id );
	}

	// Get the currency formatting.
	$currency_formatting = give_get_currency_formatting_settings( $target_currency );

	if ( ! empty( $exchange_rate ) ) {
		// Convert the amount.
		$final_amount = $amount * $exchange_rate;

		// Format the amount.
		$final_amount = number_format( $final_amount, $currency_formatting['number_decimals'], '.', '' );
	}

	// Get the price id.
	$price_id = isset( $_POST['give-price-id'] ) ? give_clean( $_POST['give-price-id'] ) : $price_id;

	// Get custom amount.
	$custom_prices = give_cs_get_donation_custom_price( $form_id, $target_currency, $price_id );

	if (
			false !== $custom_prices
			&& isset( $final_amount )
			&& (
					( 'min' === $type && $custom_prices['raw_amount'] <= $final_amount ) ||
					( 'max' === $type && $custom_prices['raw_amount'] >= $final_amount )
			)
	) {
		// Set custom price as minimum amount.
		$final_amount = $custom_prices['raw_amount'];
	}

	// Get the min/max amount.
	$amount = (float) ( ! empty( $final_amount ) ? $final_amount : $amount );

	// return minimum amount.
	return number_format( $amount, $currency_formatting['number_decimals'], '.', '' );
}

/**
 * Get amount of all currency from all of the donations.
 *
 * @since 1.0
 *
 * @param integer $form_id Donation Form ID.
 * @param array   $args Payment Query Arguments.
 *
 * @return array
 */
function give_cs_get_donation_amounts( $form_id, $args = [] ) {
	global $wpdb;

	$paymentmeta_table = Give()->payment_meta->table_name;
	$donationmeta_primary_key = Give()->payment_meta->get_meta_type() . '_id';

	$query = $wpdb->prepare(
			"SELECT * FROM $wpdb->donationmeta pm
			   WHERE EXISTS
				(
					SELECT ID, post_status, post_type, {$donationmeta_primary_key}, meta_key, meta_value
			    	FROM {$wpdb->prefix}posts p, $paymentmeta_table pm2
			    	WHERE p.post_status = 'publish'
			    	AND p.post_type = 'give_payment'
			    	AND pm2.payment_id= p.ID
			    	AND pm.payment_id= p.ID
			    	AND pm2.meta_key= '_give_payment_form_id'
			    	AND pm2.meta_value=%d
			   )",
			$form_id
	);

	$donation_amounts = [];

	// Execute the query and get the data in ARRAY format.
	$donation_meta_data = $wpdb->get_results( $query, ARRAY_A );

	// If donation meta is not empty.
	if ( ! empty( $donation_meta_data ) ) {
		$donation_data = [];

		// Group by form donation ID.
		foreach ( $donation_meta_data as $key => $item ) {
			$donation_data[ $item[ $donationmeta_primary_key ] ][ $item['meta_key'] ] = $item['meta_value'];
		}

		foreach ( $donation_data as $donation_id => $donation_meta ) {
			$amount_array = wp_parse_args(
					$donation_meta,
					[
							'_give_cs_base_amount' => '',
							'_give_cs_base_currency' => '',
							'_give_cs_exchange_rate' => '',
					]
			);

			// Get and store the payment amount.
			$donation_amounts[ $donation_id ] = [
					'payment_currency' => $amount_array['_give_payment_currency'],
					'base_amount' => give_maybe_sanitize_amount( $amount_array['_give_cs_base_amount'], [ 'currency' => $amount_array['_give_cs_base_currency'] ] ),
					'exchange_rate' => $amount_array['_give_cs_exchange_rate'],
					'amount' => $amount_array['_give_payment_total'],
			];
		}
	}

	// Return donation amount with currency.
	return $donation_amounts;
}

/**
 * Get the goal income from the donations of specific donation form.
 *
 * @since 1.0
 *
 * @param integer $form_id Donation Form ID.
 *
 * @return float|int
 */
function give_cs_calculate_goal_income( $form_id ) {
	// Get the donation totals.
	$donation_totals = give_cs_get_donation_amounts( $form_id );
	$total_earning = 0;

	if ( empty( $donation_totals ) ) {
		return $total_earning;
	}

	// Base amounts.
	$base_amounts = [];
	$give_base_currency = give_get_currency();

	foreach ( $donation_totals as $donation_id => $donation_data ) {
		// Get the base currency when the payment was creating.
		$give_donation_base_currency = isset( $donation_data['_give_cs_base_currency'] ) ? $donation_data['_give_cs_base_currency'] : '';

		// If currency was changed then get the base amount otherwise, donation's actual amount.
		$donation_income_amount = ! empty( $donation_data['base_amount'] ) ? give_cs_clean_amount( $donation_data['base_amount'] ) : $donation_data['amount'];
		$donation_total = give_maybe_sanitize_amount( $donation_income_amount, [ 'currency' => $give_donation_base_currency ] );

		// Sanitize the amount as per the give base currency.
		$base_amounts[] = give_maybe_sanitize_amount( $donation_total, [ 'currency' => $give_base_currency ] );
	}

	// Additions of all base amounts.
	$total_earning += array_sum( $base_amounts );

	// Get the form currency.
	$form_currency = wp_doing_ajax() && isset( $_POST['selected_currency'] ) ? give_clean( $_POST['selected_currency'] ) : give_get_currency( $form_id );

	// Get exchange rates.
	$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $form_currency );

	if ( ! empty( $exchange_rate ) ) {
		$total_earning = $total_earning * $exchange_rate;
	}

	return $total_earning;
}

/**
 * Get the exchange rate by Donation ID.
 *
 * @since 1.0
 *
 * @param string|integer $form_id Donation Form ID.
 * @param string|bool    $currency_code Currency Code.
 *
 * @return array|int|string
 */
function give_cs_get_form_exchange_rates( $form_id = '', $currency_code = false ) {
	// Get exchange rates.
	$currency_rates = give_cs_get_option( 'cs_exchange_rates', $form_id );
	$support_currencies = give_cs_get_active_currencies( $form_id );

	$rates = [];

	if ( ! empty( $currency_rates ) && ! empty( $support_currencies ) ) {
		foreach ( $currency_rates as $currency_key => $exchange_rate ) {
			// Skip, if the currency is not activate.
			if ( ! array_key_exists( $currency_key, $support_currencies ) ) {
				continue;
			}
			$rates[ $currency_key ] = $exchange_rate;
		}
	}

	if ( ! $currency_code ) {
		return $rates;
	}

	if (
			! empty( $currency_code )
			&& ! empty( $rates )
			&& isset( $rates[ $currency_code ]['exchange_rate'] )
	) {
		// Get the exchange rate.
		$currency_rate = give_cs_clean_amount( $rates[ $currency_code ]['exchange_rate'] );

		// Add markup with the exchange rate.
		if ( ! empty( $rates[ $currency_code ] ['rate_markup'] ) ) {
			$currency_rate += give_cs_clean_amount( give_maybe_sanitize_amount( $rates[ $currency_code ] ['rate_markup'] ) );
		}
	}

	return isset( $currency_rate ) ? $currency_rate : 0;
}

/**
 * Display the number fo the exchange rate.
 *
 * @param string|integer $form_id Donation Form ID.
 * @param string         $currency_code Currency code.
 *
 * @return array|integer
 */
function give_cs_get_number_of_decimals( $form_id = '', $currency_code = '' ) {
	// Get the exchange rates array.
	$currency_rates = give_cs_get_option( 'cs_exchange_rates', $form_id );
	$decimal_values = [];

	if ( ! empty( $currency_rates ) ) {
		foreach ( $currency_rates as $currency_key => $setting ) {
			$decimal_values[ $currency_key ] = empty( $setting['number_decimal'] ) ? 2 : $setting['number_decimal'];
		}

		if ( ! empty( $currency_code ) ) {
			return isset( $decimal_values[ $currency_code ] ) ? $decimal_values[ $currency_code ] : 2;
		}

		// Return the number of the decimals.
		return $decimal_values;
	}

	return 2;
}

/**
 * Validation amount.
 *
 * @see   http://php.net/manual/en/migration71.other-changes.php#migration71.other-changes.apprise-on-arithmetic-with-invalid-strings
 *
 * @since 1.0
 *
 * @param float|string $amount_value Amount value.
 *
 * @return int|string
 */
function give_cs_clean_amount( $amount_value ) {
	// Sanitize amount and return.
	return is_numeric( $amount_value ) ? $amount_value : absint( $amount_value );
}

/**
 * Get and list out all of the supported currencies.
 *
 * @since 1.0
 *
 * @param array        $field Field setting array.
 * @param array|string $option_value Supported currencies array.
 * @param string       $type Global or per form?
 */
function give_cs_render_active_currency_list( $field, $option_value, $type = 'global' ) {
	global $post, $pagenow;

	// Get the post ID.
	$post_id = isset( $post->ID ) ? $post->ID : 0;
	$force_form = false;

	if ( ( isset( $_GET['give_tab'] ) || 'post-new.php' === $pagenow ) && ! empty( $post_id ) ) {
		$force_form = true;
	}

	if ( 'global' === $type ) {
		?>
		<tr valign="top" <?php echo ! empty( $field['wrapper_class'] ) ? 'class="' . $field['wrapper_class'] . '"' : ''; ?>>
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $field['id'] ); ?>">
				<?php echo Give_Admin_Settings::get_field_title( $field ); ?>
			</label>
		</th>
		<td class="give-forminp give-forminp-<?php echo sanitize_title( $field['type'] ); ?> <?php echo( ! empty( $field['class'] ) ? $field['class'] : '' ); ?>">
		<fieldset>
		<?php
	}
	?>
	<div class="cs_currencies give-grid-row">
		<?php
		$count = 0;
		$per_list = ceil( count( $field['options'] ) / 3 );

		// Create chunk list.
		$currency_list_chunks = array_chunk( array_keys( $field['options'] ), ceil( count( array_keys( $field['options'] ) ) / 3 ) );

		foreach ( $currency_list_chunks as $currency_list ) {
			?>
			<ul class="give-grid-col-4">
				<?php
				foreach ( $currency_list as $currency_code ) {
					?>
					<li>
						<label>
							<input
									name="<?php echo esc_attr( $field['id'] ); ?>[]"
									value="<?php echo esc_attr( $currency_code ); ?>"
									type="checkbox"
									<?php
									if ( isset( $field['custom_attributes'] ) ) {
										foreach ( $field['custom_attributes'] as $attribute ) {
											echo $attribute;
										}
									}
									$checked_value = ( 'global' === $type ) ? $option_value : $field['value'];
									echo ( give_get_currency() === $currency_code ) ? 'checked' : ( is_array( $checked_value ) ? checked( in_array( $currency_code, $checked_value ) ) : '' );
									?>
							/>
							<?php

							// Get supported currencies.
							$cs_currency_acronym = give_cs_get_option( 'cs_currency_acronym', $post_id, 'disabled', $force_form );

							$currency_label = give_get_currency_name( $currency_code );
							$give_cs_currency_acronym = give_is_setting_enabled( $cs_currency_acronym );

							printf(
									' %s (%s)',
									$currency_label,
									( $give_cs_currency_acronym ? $currency_code . ' ' . give_currency_symbol( $currency_code ) :
											give_currency_symbol( $currency_code ) )
							);
							?>
						</label>
					</li>
					<?php
				}

				$count ++;
				if ( 0 === ( $count % $per_list ) && count( $field['options'] ) > $count ) {
					echo '</ul><ul class="give-grid-col-4">';
				}
				?>
			</ul>
			<?php
		} // End foreach().
		?>
	</div>
	<?php
	if ( 'global' === $type ) {
		echo Give_Admin_Settings::get_field_description( $field );
		?>
		</fieldset></td></tr>
		<?php
	}
}

/**
 * Get the Give Currency Switcher strings.
 *
 * @since 1.0
 *
 * @param string  $string_key Setting key
 * @param integer $form_id Donation Form ID.
 *
 * @return mixed
 */
function give_cs_get_localized_string( $string_key = '', $form_id = 0 ) {

	switch ( $string_key ) {
		case 'cs_message':
			$setting_string = sprintf( __( 'The current exchange rate is 1.00 %1$s equals %2$s %3$s.', 'give-currency-switcher' ), '{base_currency}', '{new_currency_rate}', '{new_currency}' );
			break;
		default:
			$setting_string = '';
	}

	/**
	 * Filter the setting string.
	 *
	 * @since 1.0
	 *
	 * @param mixed   $setting_string Setting String.
	 * @param integer $form_id Donation Form ID.
	 */
	return apply_filters( "give_cs_localize_string_{$string_key}", $setting_string, $form_id );
}

/**
 * Update Give cs earning amount when increase/decrease event happened.
 *
 * @since 1.1
 *
 * @param float $amount Donation amount.
 * @param int   $form_id Donation Form ID.
 * @param int   $payment_id Donation ID.
 *
 * @return float $amount
 */
function give_cs_update_form_earning( $amount, $form_id, $payment_id = 0 ) {
	// CS enabled.
	$cs_enabled = give_get_meta( $payment_id, '_give_cs_enabled', true );

	// Check if currency was switched for this donation.
	if ( give_is_setting_enabled( $cs_enabled ) && give_has_upgrade_completed( 'give_cs_v11_update_form_earnings' ) ) {

		// If so, Get the base amount.
		$amount = give_get_meta( $payment_id, '_give_cs_base_amount', true );
	}

	// Return the amount.
	return $amount;
}

/**
 * Re-Verify min/max amount.
 *
 * @since 1.2
 *
 * @param bool    $stat
 * @param string  $amount_range
 * @param integer $form_id
 *
 * @return bool
 */
function give_cs_recheck_min_max_amount( $stat, $amount_range, $form_id ) {
	// Return, if not exists.
	if ( ! isset( $_REQUEST['give-cs-form-currency'] ) ) {
		return $stat;
	}

	$selected_currency = give_clean( $_REQUEST['give-cs-form-currency'] );
	$posted_amount = isset( $_REQUEST['give-amount'] ) ? give_clean( $_REQUEST['give-amount'] ) : 0;
	$price_id = isset( $_REQUEST['give-price-id'] ) ? give_clean( $_REQUEST['give-price-id'] ) : null;

	// Sanitized amount.
	$amount = give_maybe_sanitize_amount( $posted_amount, [ 'currency' => $selected_currency ] );

	if ( give_has_variable_prices( $form_id ) && in_array( $price_id, give_get_variable_price_ids( $form_id ) ) ) {

		$price_level_amount = give_get_price_option_amount( $form_id, $price_id );

		// Cannot compare strictly, waiting for PHPUnit https://github.com/impress-org/give/issues/3232.
		if ( $price_level_amount == $amount ) {
			return true;
		}
	}

	switch ( $amount_range ) {
		case 'minimum':
			$minimum_amount = give_maybe_sanitize_amount( give_get_form_minimum_price( $form_id ) );
			$minimum_amount = give_cs_convert_min_max_amount( $form_id, $selected_currency, $minimum_amount, 'min' );

			$stat = ( $minimum_amount <= $amount );
			break;
		case 'maximum':
			$maximum_amount = give_maybe_sanitize_amount( give_get_form_maximum_price( $form_id ) );
			$maximum_amount = give_cs_convert_min_max_amount( $form_id, $selected_currency, $maximum_amount, 'max' );

			$stat = ( $maximum_amount >= $amount );
			break;
	}

	return $stat;
}

add_filter( 'give_verify_minimum_maximum_price', 'give_cs_recheck_min_max_amount', 10, 3 );

/**
 * Modify the amount showing on the recurring message.
 *
 * @since 1.2
 *
 * @param string  $message Recurring message.
 * @param array   $price Price level.
 * @param integer $form_id Donation Form ID.
 *
 * @return string
 */
function give_cs_multi_levels_message( $message, $price, $form_id ) {
	// Get the form currency.
	$form_currency = isset( $_POST['currency'] )
			? give_clean( $_POST['currency'] )
			: give_get_currency( $form_id );

	if ( $form_currency !== give_get_currency() ) {
		// Get exchange rates.
		$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $form_currency );

		if ( ! empty( $exchange_rate ) ) {
			$price['_give_amount'] *= $exchange_rate;
		}

		// Get the amount.
		$amount = sprintf(
				'<span class="amount">%1$s</span>',
				give_currency_filter(
						give_format_amount(
								$price['_give_amount'],
								[
										'sanitize' => false,
								]
						),
						[ 'currency_code' => $form_currency ]
				)
		);

		$message = preg_replace( "#<\s*?span class=\"amount\">(.*?)</span\b[^>]*>#s", $amount, $message );
	}

	// Show message for custom amount whether it is one time or recurring donation.
	return $message;
}

// Change the recurring donation amount.
add_filter( 'give_recurring_multi_levels_notification_message', 'give_cs_multi_levels_message', 10, 3 );

/**
 * Change the currency symbol for the multiple donation amounts.
 *
 * @since 1.0
 *
 * @param string  $price_text Level text.
 * @param integer $form_id Donation Form ID.
 * @param array   $price Level array.
 *
 * @return mixed|string
 */
function give_cs_give_form_level_text( $price_text, $form_id, $price ) {
	// Get the form currency.
	$form_currency = give_get_currency( $form_id );

	if ( ! empty( $price_text ) && empty( $price['_give_text'] ) ) {
		$formatted_amount = give_format_amount(
				$price['_give_amount'],
				[
						'sanitize' => false,
						'currency' => $form_currency,
				]
		);

		// Get the price text.
		$price_text = give_currency_filter( $formatted_amount, [ 'currency_code' => $form_currency ] );
	}

	return $price_text;
}

add_filter( 'give_form_level_text', 'give_cs_give_form_level_text', 10, 3 );

/**
 * Check whether gateway support given currency.
 *
 * @since 1.3.1
 *
 * @param integer $form_id Donation Form ID.
 * @param string  $currency Currency Currency.
 * @param string  $gateway Gateway Gateway.
 *
 * @return bool
 */
function give_cs_is_gateway_support_currency( $form_id, $currency = '', $gateway = '' ) {

	// If currency switcher is not enabled for this form.
	if ( ! give_cs_is_enabled( $form_id ) ) {
		$give_cs_json_obj[ 'form_' . $form_id ] = false;

		return $give_cs_json_obj;
	}

	// Get default currency if not passed.
	if ( empty( $currency ) ) {
		$currency = give_get_currency();
	}

	// Get default gateway if not passed.
	if ( empty( $gateway ) ) {
		$gateway = give_get_default_gateway( $form_id );
	}

	// Get Currency switcher list of gateway for currency.
	$currency_based_gateways = give_cs_get_option( 'cs_payment_gateway', $form_id );
	$currency_based_gateways = ( ! empty( $currency_based_gateways ) && is_array( $currency_based_gateways ) ) ? $currency_based_gateways : [];

	// Get list of gateway which support given currency.
	$currency_supported_gateway = isset( $currency_based_gateways[ $currency ] ) ? $currency_based_gateways[ $currency ] : [];

	return in_array( $gateway, $currency_supported_gateway, true );
}
