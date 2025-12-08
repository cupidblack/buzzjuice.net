<?php
/**
 * Give Currency Switcher frontend.
 *
 * @package    Give_Currency_Switcher
 * @subpackage Frontend
 * @copyright  Copyright (c) 2016, GiveWP
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class.
 */
class Give_Currency_Switcher_Frontend {

	/**
	 * Fee amount.
	 *
	 * @var float $fee_amount Fees amount.
	 */
	public $fee_amount;

	/**
	 * @var integer $rate_decimal_number
	 */
	public $rate_decimal_number;

	/**
	 * Frontend actions.
	 */
	public function __construct() {
		// Load styles and scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		// Create and set array of the exchange rates to the input field to get it from the JS later on.
		add_action( 'give_donation_form_top', [ $this, 'give_cs_json_object' ], 10, 1 );

		// Add JSON data to the footer.
		add_action( 'wp_footer', [ $this, 'give_cs_json_data' ], 10 );

		// Add JSON data to the Multi-Step form footer.
		add_action( 'give_embed_footer', [ $this, 'give_cs_json_data' ], 10 );

		// Currency switcher front-end HTML.
		add_action( 'give_after_donation_amount', [ $this, 'give_cs_currency_switcher_frontend_html' ], 11, 1 );

		// Show notice when currency is switched to donor's currency.
		add_action( 'give_pre_form', [ $this, 'give_cs_show_currency_changed_notice' ], 10, 2 );

		// Store currency switcher meta data like base amount, exchange rates with payment.
		add_action( 'give_insert_payment', [ $this, 'give_cs_store_switched_currency_meta_data' ], 10, 1 );

		// Convert goal raised amount to donor currency.
		add_filter( 'give_goal_amount_raised_output', [ $this, 'give_cs_goal_raised_output' ], 20, 2 );

		// Convert goal amount to switched currency.
		add_filter( 'give_get_set_goal', [ $this, 'give_cs_convert_goal_target_amount' ], 10, 2 );

		// Change currency form tags according to the donor's currency.
		add_filter( 'give_form_html_tags', [ $this, 'give_cs_form_html_tags' ], 10, 2 );

		// Convert amount according to the switched currency.
		add_filter( 'give_fee_recovery_hidden_input_json', [ $this, 'give_cs_fee_recovery_json_arg' ], 10, 2 );

		// Convert base fee amount.
		add_filter( 'give_fee_base_amount', [ $this, 'give_cs_give_fee_base_amount' ], 10, 1 );
		add_filter( 'give_fee_earning_amount', [ $this, 'give_fee_earning_amount' ], 10, 2 );

		// Replace currency while creating donation.
		add_filter( 'give_currency', [ $this, 'give_cs_switch_currency' ] );

		// When increase/decrease the form earning.
		add_filter( 'give_increase_form_earnings_amount', [ $this, 'give_cs_increase_amount' ], 10, 3 );
		add_filter( 'give_decrease_form_earnings_amount', [ $this, 'give_cs_decrease_earning' ], 10, 3 );

		// Convert the fee amount to the switched currency.
		add_filter( 'give_fee_recovery_fee_amount', [ $this, 'give_cs_convert_fee_amount' ], 10, 2 );

		// Add filter to prevent donation  invalidation.
		// https://github.com/impress-org/give-currency-switcher/issues/228
		add_action( 'give_checkout_error_checks', [ $this, 'pre_checkout_error_checks' ], 0, 1 );

		// Validate Donation Form.
		add_action( 'give_checkout_error_checks', [ $this, 'checkout_error_checks' ], 10, 1 ); // Pre-process donation check validation.

		add_filter( 'give_goal_amounts', [ $this, 'add_currency_switched_amounts' ], 10, 2 );
		add_filter( 'give_goal_raised_amounts', [ $this, 'add_currency_switched_amounts' ], 10, 2 );
	}

	/**
	 * This function will add switched currency amounts.
	 *
	 * @since  2.5.4
	 * @access public
	 *
	 * @param array $amounts List of donation amount based on currency active.
	 * @param int   $form_id Donation Form ID.
	 *
	 * @return array
	 */
	public function add_currency_switched_amounts( $amounts, $form_id ) {

		$currencies           = give_cs_get_active_currencies_with_gateways( $form_id );
		$global_base_currency = give_get_option( 'currency' );
		$base_currency        = give_get_currency( $form_id );

		// Always use global base currency to format amount for active currencies
		if ( $base_currency !== $global_base_currency ) {
			$exchange_rate                    = give_cs_get_form_exchange_rates( $form_id, $base_currency );
			$amounts[ $global_base_currency ] = $amounts[ $base_currency ] / $exchange_rate;

			unset( $amounts[ $base_currency ] );
			$base_currency = $global_base_currency;
		}

		// Loop through additional currencies to set human readable amount.
		foreach ( $currencies as $currency ) {

			$exchange_rate   = give_cs_get_form_exchange_rates( $form_id, $currency );
			$donation_amount = $amounts[ $base_currency ] * $exchange_rate;

			$amounts[ $currency ] = give_currency_filter(
				give_human_format_large_amount(
					give_format_amount(
						$donation_amount,
						[
							'sanitize' => false,
							'currency' => $currency,
							'decimal'  => false,
						]
					),
					[
						'currency' => $currency,
					]
				),
				[
					'currency_code' => $currency,
				]
			);
		}

		// Set human readable amount for base currency.
		$amounts[ $base_currency ] = give_currency_filter(
			give_human_format_large_amount(
				give_format_amount(
					$amounts[ $base_currency ],
					[
						'sanitize' => false,
						'currency' => $base_currency,
						'decimal'  => false,
					]
				),
				[
					'currency' => $base_currency,
				]
			),
			[
				'currency_code' => $base_currency,
			]
		);

		return $amounts;
	}

	/**
	 * Define currency.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string|array $currency Current currency.
	 *
	 * @return string
	 */
	public function give_cs_switch_currency( $currency ) {
		if ( ! empty( $_POST['give-cs-form-currency'] ) ) {
			$currency = give_clean( $_POST['give-cs-form-currency'] );
		}

		return $currency;
	}

	/**
	 * Load scripts.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function load_scripts() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( 'give-currency-switcher', GIVE_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/js/give-currency-switcher-frontend' . $suffix . '.js', [ 'jquery' ], GIVE_CURRENCY_SWITCHER_VERSION, true );
		wp_enqueue_script( 'give-currency-switcher' );

		// Localize data to JS.
		wp_localize_script(
			'give-currency-switcher',
			'give_currency_switcher',
			[
				'nonce'                        => wp_create_nonce( 'switch_currency_nonce' ),
				'notice_dismiss_image'         => esc_url( GIVE_PLUGIN_URL . 'assets/dist/images/close.svg' ),
				'currency_not_support_message' => sprintf( __( '<b>%1$s</b> is not supported by <b>%2$s</b> gateway.', 'give-currency-switcher' ), '{currency_code}', '{payment_gateway}' ),
				'cs_custom_price_message'      => give_cs_get_localized_string( 'cs_custom_amount' ),
				'setting_vars'                 => [
					'base_currency' => give_get_currency(),
				],
			]
		);
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function enqueue_styles() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( 'give-currency-switcher-style', GIVE_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/css/give-currency-switcher-frontend' . $suffix . '.css', [], GIVE_CURRENCY_SWITCHER_VERSION, 'all' );
		wp_enqueue_style( 'give-currency-switcher-style' );
	}

	/**
	 * Show the Currency Switcher option above the donation form.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param integer $form_id Form ID.
	 *
	 * @return bool|null
	 */
	public function give_cs_currency_switcher_frontend_html( $form_id ) {
		// Return false, if currency switcher is not enabled.
		if ( ! give_cs_is_enabled( $form_id ) ) {
			return false;
		}

		// Get all of the supported currencies by default payment gateway.
		$currencies         = give_cs_get_active_currencies_with_gateways( $form_id );
		$form_currency      = give_get_currency( $form_id );
		$give_base_currency = give_get_option( 'currency', 'USD' );
		$default_amount     = 0.00;

		// Get the exchange rates.
		$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $form_currency );

		if ( ! empty( $exchange_rate ) ) {
			$variable_custom_amounts = [];

			// If form has variable prices.
			if ( give_has_variable_prices( $form_id ) ) {
				// Get the variable prices.
				$prices = give_get_variable_prices( $form_id );

				foreach ( $prices as $price ) {
					if ( isset( $price['_give_default'] ) && 'default' === $price['_give_default'] ) {
						$default_amount = $price['_give_amount'];
					}

					// Get form's exchange rates.
					$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $form_currency );
					$custom_amount = give_cs_get_donation_custom_price( $form_id, $form_currency, $price['_give_id']['level_id'] );

					$variable_custom_amounts[ $price['_give_id']['level_id'] ] = [
						'converted-amount' => ! empty( $exchange_rate ) ? $price['_give_amount'] * $exchange_rate : $price['_give_amount'],
						'custom-amount'    => ( $custom_amount ) ? $custom_amount['raw_amount'] : '',
					];
				}
			} else {
				// Otherwise, get default amount.
				$default_amount = give_get_meta( $form_id, '_give_set_price', true );
			}
			// Convert amount to target currency.
			$sanitize_custom_amount = $default_amount * $exchange_rate;
		}

		$gateway = give_get_default_gateway( $form_id );

		// Include base currency, if not exists. Also, Currency should support gateway.
		if (
			! in_array( $give_base_currency, $currencies, true )
			&& give_cs_is_gateway_support_currency( $form_id, $give_base_currency, $gateway )
		) {
			array_unshift( $currencies, $give_base_currency );
		}
		?>
		<fieldset
			class="give-currency-switcher-wrap form-row form-row-wide<?php echo( empty( $currencies ) ? ' give-hidden' : '' ); ?>"
			data-give_form_id="<?php echo esc_attr( $form_id ); ?>">
			<legend class="give-cs-msg-legend give-hidden">&nbsp;</legend>
			<select name="give-cs-currency" class="give-cs-select-currency give-hidden">
				<?php foreach ( $currencies as $currency_code ) : ?>
					<option
						value="<?php echo esc_attr( $currency_code ); ?>" <?php selected( $currency_code, $form_currency, true ); ?>>
						<?php echo esc_html( give_get_currency_name( $currency_code ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<input type="hidden" name="give-cs-base-currency" value="<?php echo esc_attr( $give_base_currency ); ?>"/>
			<input type="hidden" name="give-cs-exchange-rate" value="<?php echo esc_attr( $exchange_rate ); ?>"/>
			<input type="hidden" name="give-cs-form-currency" value="<?php echo esc_attr( $form_currency ); ?>"/>
			<?php

			// Store all of the converted variable prices along with custom amount if exists.
			if ( ! empty( $variable_custom_amounts ) ) {
				echo sprintf( '<span class="give-cs-variable-amount"  data-custom_amount="[%s]"></span>', esc_js( json_encode( $variable_custom_amounts, JSON_FORCE_OBJECT ) ) );
			}

			// For the first time If currency selected by default has custom amount.
			if ( isset( $sanitize_custom_amount ) ) {
				echo sprintf( '<input type="hidden" name="give-cs-custom-amount" value="%s" />', esc_attr( $sanitize_custom_amount ) );
			}

			// Get the exchange rate message.
			$cs_message = give_cs_get_option( 'cs_message', $form_id, give_cs_get_localized_string( 'cs_message' ) );

			/**
			 * This is support for WPML's string translation.
			 * It requires the database string to be stored in a key - value pair setting,
			 * so we save the string corresponding to a new key 'cs_message_translatable'.
			 *
			 * @link https://wpml.org/documentation/getting-started-guide/string-translation/#admin_texts
			 */
			update_option( 'cs_message_translatable', $cs_message );

			$cs_message_translatable = get_option( 'cs_message_translatable' );

			// Class to hide currency switcher message by default.
			$hide_message_class = '';

			if ( ! is_null( $form_id ) ) {
				// Check if geolocation is enabled.
				$geo_location = give_cs_get_option( 'cs_geolocation_state', $form_id, 'disabled' );

				if ( ! is_user_logged_in() && ! give_is_setting_enabled( $geo_location ) ) {

					// Get default currency.
					$default_currency = give_get_meta( $form_id, 'give_cs_default_currency', true );

					if ( '0' !== $default_currency ) {
						$hide_message_class = 'give-cs-hide-message';
					}
				}
			}

			?>
			<div
				class="give-currency-switcher-msg-wrap <?php echo ( $give_base_currency === $form_currency ) ? 'give-hidden' : ''; ?>">
				<span
					class="give-currency-switcher-msg <?php echo esc_attr( $hide_message_class ); ?>"
					data-rawtext="<?php echo esc_html( $cs_message_translatable ); ?>">
							<?php
							// Default price id.
							$price_id = - 1;

							// If donation form has variable prices.
							if ( give_has_variable_prices( $form_id ) ) {
								// Get form variable price id.
								$price_id = give_get_price_id( $form_id, $default_amount );
							}

							// Get custom cs amount.
							$custom_amounts            = give_cs_get_donation_custom_price( $form_id, $form_currency, $price_id );
							$this->rate_decimal_number = give_cs_get_number_of_decimals( $form_id, $form_currency );

							if ( $give_base_currency !== $form_currency && ! $custom_amounts ) {

								// Set the number of decimal for exchange rate.
								add_filter(
									'give_sanitize_amount_decimals',
									[
										$this,
										'give_cs_override_decimal_number',
									]
								);

								// Replace the tags with values.
								$cs_message_translatable = str_replace(
									[ '{new_currency_rate}', '{base_currency}', '{new_currency}' ],
									[
										give_format_amount(
											$exchange_rate,
											[
												'sanitize' => false,
												'currency' => $form_currency,
											]
										),
										$give_base_currency,
										$form_currency,
									],
									$cs_message_translatable
								);

								// Remove the number of decimal set for exchange rate.
								remove_filter(
									'give_sanitize_amount_decimals',
									[
										$this,
										'give_cs_override_decimal_number',
									]
								);

								// Show message for currency exchange rates with base currency.
								echo wp_kses_post( $cs_message_translatable );
							} elseif ( $custom_amounts ) {
								// Show message for custom amount.
								echo wp_kses_post( give_cs_get_localized_string( 'cs_custom_amount' ) );
							}
							?>
				</span>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Override the number of decimal when rending the rate in front-end.
	 *
	 * @since 1.2.1
	 *
	 * @param integer $number_of_decimals Number of decimals.
	 *
	 * @return mixed
	 */
	public function give_cs_override_decimal_number( $number_of_decimals ) {
		return $this->rate_decimal_number;
	}

	/**
	 * Convert fee amount when create/update/delete the donation with fee amount.
	 *
	 * @since 1.1
	 *
	 * @param float   $fee_amount Donation fee amount.
	 * @param integer $payment_id Donation ID.
	 *
	 * @return float
	 */
	public function give_cs_convert_fee_amount( $fee_amount, $payment_id ) {
		// Check if donation was submitted.
		if (
			isset( $_POST['give-cs-form-currency'], $_POST['give-cs-base-currency'] )
			&& $_POST['give-cs-form-currency'] !== $_POST['give-cs-base-currency']
		) {
			if ( isset( $_POST['give-cs-exchange-rate'] ) ) {
				// Get the base amount.
				$fee_amount = (float) give_cs_clean_amount( $fee_amount ) / give_cs_clean_amount( $_POST['give-cs-exchange-rate'] );
			}
		} elseif ( $payment_id ) {
			// Is cs enabled?
			$cs_enabled = give_get_meta( $payment_id, '_give_cs_enabled', true );

			// Check if CS is enabled.
			if ( give_is_setting_enabled( $cs_enabled ) ) {
				$exchange_rate = give_get_meta( $payment_id, '_give_cs_exchange_rate', true );
				$exchange_rate = empty( $exchange_rate ) ? 0 : $exchange_rate;
				$fee_amount    = (float) $fee_amount / $exchange_rate;
			}
		}

		/**
		 * Filter the currency switcher fee amount.
		 *
		 * @since 1.1
		 */
		return apply_filters( 'give_cs_fee_amount', round( $fee_amount, 2 ), $payment_id );
	}

	/**
	 * Store custom data into the payment meta when donation created.
	 *
	 * @since 1.0
	 *
	 * @param integer $payment_id Payment ID.
	 *
	 * @return bool
	 */
	public function give_cs_store_switched_currency_meta_data( $payment_id ) {
		if ( ! isset( $_POST['give-cs-form-currency'], $_POST['give-cs-base-currency'] ) ) {
			return false;
		}

		// Get the donation's donor id.
		$donor_id = give_get_payment_donor_id( $payment_id );

		// Check if logged and get customer id is not missing.
		if ( ! empty( $donor_id ) && is_user_logged_in() ) {
			Give()->donor_meta->update_meta( $donor_id, '_give_cs_currency', give_clean( $_POST['give-cs-form-currency'] ) );
		}

		// If base and chosen currency are not same.
		if ( $_POST['give-cs-form-currency'] !== $_POST['give-cs-base-currency'] ) {
			// Sanitized form amount.
			$give_form_amount = give_maybe_sanitize_amount(
				give_clean( $_POST['give-amount'] ),
				[
					'currency' => give_clean( $_POST['give-cs-form-currency'] ),
				]
			);

			// Clean form amount.
			$give_form_amount = give_cs_clean_amount( give_sanitize_amount( $give_form_amount ) );

			// If fee mode is enabled.
			if ( isset( $_POST['give-fee-mode-enable'] ) && 'true' === $_POST['give-fee-mode-enable'] ) {
				$give_form_amount = isset( $_POST['give-fee-amount'] ) ? $give_form_amount + give_cs_clean_amount( $_POST['give-fee-amount'] ) : $give_form_amount;
			}

			// Get the base amount.
			$base_amount        = give_cs_clean_amount( $give_form_amount ) / give_cs_clean_amount( $_POST['give-cs-exchange-rate'] );
			$format_base_amount = give_format_decimal( $base_amount, true, false );

			// Store currency switched data as payment meta.
			give_update_payment_meta( $payment_id, '_give_cs_base_currency', give_clean( $_POST['give-cs-base-currency'] ) );
			give_update_payment_meta( $payment_id, '_give_cs_exchange_rate', give_clean( $_POST['give-cs-exchange-rate'] ) );
			give_update_payment_meta( $payment_id, '_give_cs_enabled', 'enabled' );

			// Format the base amount.
			give_update_payment_meta( $payment_id, '_give_cs_base_amount', give_sanitize_amount_for_db( $format_base_amount ) );
		}
	}

	/**
	 * Goal goal amount.
	 *
	 * @since 1.0
	 *
	 * @param double  $goal    target earning.
	 * @param integer $form_id Donation Form ID.
	 *
	 * @return double Goal amount.
	 */
	public function give_cs_goal_raised_output( $goal, $form_id ) {
		// Get the form currency.
		$form_currency = give_get_currency( $form_id );

		// Get exchange rate.
		$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $form_currency );
		$total_earning = $goal;

		// Calculate the donation income amount with form currency with Backward compatibility.
		if ( ! give_has_upgrade_completed( 'give_cs_v11_update_form_earnings' ) ) {
			$total_earning = give_cs_calculate_goal_income( $form_id );
		} elseif ( ! empty( $exchange_rate ) && give_get_currency() !== $form_currency ) {
			$total_earning = $exchange_rate * $goal;
		}

		if (
			! empty( $total_earning )
			&& ! is_admin()
			&& defined( 'GIVE_FEE_RECOVERY_VERSION' )
		) {
			if (
				! give_has_upgrade_completed( 'give_fee_recovery_v151_form_fee_earnings' )
				&& function_exists( 'give_get_fee_earnings' )
			) {
				// Get Fee earnings per Form.
				$fee_earning = give_get_fee_earnings( $form_id );

				if (
					! empty( $exchange_rate )
					&& give_get_currency() === $form_currency
				) {
					$fee_earning *= $exchange_rate;
				}
			} else {
				// Get Fee earnings per Form.
				$fee_earning = give_get_meta( $form_id, '_give_form_fee_earnings', true );
			}

			// Backward compatibility.
			// Note: In Fee Recovery 1.5.1 or greater then 1.5.1 will minus the earning amount itself.
			if ( version_compare( GIVE_FEE_RECOVERY_VERSION, '1.5.1', '<' ) ) {
				// Remove the fee earning amount.
				$total_earning = $total_earning - give_maybe_sanitize_amount( $fee_earning );
			}
		}

		return $total_earning;
	}

	/**
	 * Auto switched currency notice.
	 *
	 * Displays a notice on the frontend for donor's currency.
	 *
	 * @since 1.0
	 *
	 * @param integer $form_id Donation From ID.
	 * @param array   $args    Additional arguments.
	 */
	function give_cs_show_currency_changed_notice( $form_id, $args ) {

		// Store message to this variable to show later.
		$message = '';

		// Get the base currency.
		$give_base_currency = give_get_currency();

		// Get the donor's currency.
		$form_currency = give_get_currency( $form_id );

		if ( $give_base_currency !== $form_currency ) {

			/**
			 * Get the donor currency.
			 *
			 * Priority #1: If user meta has preferred currency in user_meta table.
			 * Priority #2: If User never made donation then get the currency based on the donor's currency.
			 * Priority #3: If donor's currency not support in form show default.
			 */
			$donor_currency = give_cs_get_donor_currency( $form_id );

			// If donor's currency is different than base currency.
			if ( isset( $donor_currency['currency'] ) ) {
				if ( 'meta' === $donor_currency['came_from'] ) {
					// If donor has already made donation with different currency.
					$message = sprintf(
						__( 'Currency auto switched to your preferred currency <b>%s</b>.', 'give-currency-switcher' ),
						give_get_currency_name( $form_currency )
					);
				} elseif ( 'geo_location' === $donor_currency['came_from'] ) {
					// Message if currency was changed using donor's country.
					$message = sprintf(
						__( 'Your country is <b>%1$s</b> so currency auto changed to <b>%2$s</b>', 'give-currency-switcher' ),
						$donor_currency['country_name'],
						give_get_currency_name( $donor_currency['currency'] )
					);
				}

				/**
				 * Update message of Currency changed.
				 *
				 * @since 1.0
				 *
				 * @param string $message        Currency changed message.
				 * @param string $donor_currency Donor Currency.
				 */
				$message = apply_filters( 'give_cs_currency_changed_message', $message, $donor_currency );

				// If message is not empty.
				if ( ! empty( $message ) ) {

					ob_start();
					Give_Notices::print_frontend_notice(
						$message,
						true,
						'success',
						[
							'dismiss_type' => 'manual',
							'dismissible'  => true,
						]
					);
					$notices_html = ob_get_clean();

					// Show notices.
					echo sprintf( '<div class="give-cs-notices"> %s </div>', $notices_html );
				}
			}
		}
	}

	/**
	 * Give form additional hidden fields.
	 *
	 * @since 1.0
	 * @unlreased Added logic to match currency formatting settings with custom base currency formatting settings.
	 *
	 * @param integer $form_id Donation Form ID.
	 */
	public function give_cs_json_object( $form_id ) {
		global $give_cs_json_obj;

		// If currency switcher is not enabled for this form.
		if ( ! give_cs_is_enabled( $form_id ) ) {
			$give_cs_json_obj[ 'form_' . $form_id ] = false;

			return;
		}

		// Get all of the supported currencies.
		$support_currencies = give_cs_get_active_currencies( $form_id );

		// Get the setting options.
		$cs_payment_gateway   = give_cs_get_option( 'cs_payment_gateway', $form_id );
		$gateway_currency     = ( ! empty( $cs_payment_gateway ) ) ? $cs_payment_gateway : [];
		$global_base_currency = $base_currency = give_get_option( 'currency', 'USD' );

		// Get currency switcher status.
		$is_enable = give_get_meta( $form_id, 'cs_status', true );

		// Check if per form customizable.
		if ( give_cs_is_per_form_customized( $form_id ) && give_is_setting_enabled( $is_enable ) ) {
			$base_currency = give_get_meta( $form_id, 'give_cs_default_currency', true );
		}

		$all_currencies  = give_get_currencies();
		$price_variables = give_has_variable_prices( $form_id ) ? give_get_variable_prices( $form_id ) : [];

		// Include base currency.
		if ( ! array_key_exists( $base_currency, array_keys( $support_currencies ) ) ) {
			$support_currencies[ $base_currency ] = $all_currencies[ $base_currency ];
		}

		// Store script data.
		$script_var           = [];
		$supported_currencies = [];

		// Include base currency if not exists.
		if ( ! isset( $gateway_currency[ $base_currency ] ) ) {
			$gateway_currency[ $base_currency ] = array_keys( give_get_ordered_payment_gateways( give_get_enabled_payment_gateways() ) );
		}

		// Go through each of the activate currencies.
		foreach ( $support_currencies as $currency_code => $currency_label ) {

			if ( isset( $gateway_currency[ $currency_code ] ) ) {
				foreach ( $gateway_currency[ $currency_code ] as $gateway ) {

					// Get the exchange rate.
					$exchange_rate           = give_cs_get_form_exchange_rates( $form_id, $currency_code );
					$rate_number_of_decimals = give_cs_get_number_of_decimals( $form_id, $currency_code );

					// Do not include if the currency has no exchange rate.
					if ( empty( $exchange_rate ) && $global_base_currency !== $currency_code ) {
						continue;
					}

					// Get the supported gateway lists.
					$support_gateways = isset( $gateway_currency[ $currency_code ] ) ? $gateway_currency[ $currency_code ] : [];

					// Store exchange rate.
					$script_var['exchange_rates'][ $currency_code ] = $exchange_rate;
					$script_var['decimal_number'][ $currency_code ] = $rate_number_of_decimals;

					if ( ! empty( $price_variables ) ) {
						foreach ( $price_variables as $price ) {
							$price_id = $price['_give_id']['level_id'];
							$script_var['custom_amounts'][ $currency_code ][ $price_id ] = give_cs_get_donation_custom_price( $form_id, $currency_code, $price_id );
						}
					} else {
						$script_var['custom_amounts'][ $currency_code ] = give_cs_get_donation_custom_price( $form_id, $currency_code );
					}

					// Support gateway.
					$script_var['support_gateways'][ $currency_code ] = [
						'gateways'       => $support_gateways,
						'currency_label' => give_get_currency_name( $currency_code ),
					];

					// Insert as supported currency.
					$supported_currencies[] = $currency_code;
				}
			}

			// If currency is not empty.
			if ( ! empty( $supported_currencies ) ) {

				// Remove duplicate currency.
				$supported_currencies = array_filter( $supported_currencies );

				// Get all the currency.
				$give_currencies = give_get_currencies( 'all' );
				$give_symbols    = give_currency_symbols( true );

				foreach ( $supported_currencies as $currency_key ) {

					// Store all supported currency.
					$give_cs_json_obj['supported_currency'][ $currency_key ]           = $give_currencies[ $currency_key ];
					$give_cs_json_obj['supported_currency'][ $currency_key ]['symbol'] = $give_symbols[ $currency_key ];

					// Apply global formatting setting to global currency in formatting setting.
					// By default currency formatting setting set to standard formatting setting and hard coded in give/includes/currencies-list.php
					// Admin has plugin setting to add custom currency formatting setting which utilize by currency formatting logics.
					if ( $global_base_currency === $currency_key ) {
						$give_options = give_get_settings();

						$setting_args = [
							'currency_position'   => $give_options['currency_position'],
							'thousands_separator' => $give_options['thousands_separator'],
							'decimal_separator'   => $give_options['decimal_separator'],
							'number_decimals'     => $give_options['number_decimals'],
						];

						$give_cs_json_obj['supported_currency'][ $currency_key ]['setting'] = wp_parse_args( $setting_args, $give_cs_json_obj['supported_currency'][ $currency_key ]['setting'] );
					}
				}
			}
		}

		$give_cs_json_obj[ 'form_' . $form_id ] = apply_filters( 'give_cs_json_obj', $script_var, $form_id );

		if ( ! empty( $price_variables ) ) {
			foreach ( $price_variables as $price ) {
				$give_cs_json_obj[ 'form_' . $form_id ]['variable_prices'][ $price['_give_id']['level_id'] ] = [
					'has_label' => ! empty( $price['_give_text'] ),
				];
			}
		}

		// Give backward.
		$give_cs_json_obj[ 'form_' . $form_id ]['minimum_amount'] = version_compare( GIVE_VERSION, '2.1.0', '>=' )
			? give_get_meta( $form_id, '_give_custom_amount_range_minimum', true )
			: give_get_meta( $form_id, '_give_custom_amount_minimum', true );

		if ( version_compare( GIVE_VERSION, '2.1.0', '>=' ) ) {
			$give_cs_json_obj[ 'form_' . $form_id ]['maximum_amount'] = give_get_meta( $form_id, '_give_custom_amount_range_maximum', true );
		}

		// Show acronym.
		$give_cs_json_obj[ 'form_' . $form_id ]['currency_acronym'] = give_cs_get_option( 'cs_currency_acronym', $form_id, 'disabled' );

		$options = Give\Helpers\Form\Template::getOptions( $form_id );

		$give_cs_json_obj[ 'form_' . $form_id ]['decimals_enabled'] = isset( $options['payment_amount'][ 'decimals_enabled'] )
			? $options['payment_amount'][ 'decimals_enabled']
			: 'disabled';
	}

	/**
	 * Modify the fee amount according to the switched currency.
	 *
	 * @since 1.0
	 *
	 * @param array   $fee_array Fee recovery amount array.
	 * @param integer $form_id   Donation Form ID.
	 *
	 * @return array
	 */
	public function give_cs_fee_recovery_json_arg( $fee_array, $form_id ) {

		// Get the form currency.
		$form_currency = give_get_currency( $form_id );
		$base_currency = give_get_currency();

		// Get the exchange rates.
		$exchange_rates = give_cs_get_form_exchange_rates( $form_id, $form_currency );

		// If form currency and base currency is not same.
		if ( $form_currency !== $base_currency && ! empty( $fee_array['fee_data'] ) && ! empty( $exchange_rates ) ) {

			// If all gateway setting is enabled.
			if ( isset( $fee_array['fee_data']['all_gateways'] ) ) {
				// Base amount.
				$fee_base_amount = $fee_array['fee_data']['all_gateways']['base_amount'];
				// Convert amount.
				$converted_amount = give_maybe_sanitize_amount( $fee_base_amount ) * $exchange_rates;

				// Format the amount.
				$fee_array['fee_data']['all_gateways']['base_amount'] = $converted_amount;
			} else {
				// If per gateway setting is enabled.
				foreach ( $fee_array['fee_data'] as $gateway_slug => $fee_data ) {
					// Get the base amount.
					$fee_base_amount = $fee_array['fee_data'][ $gateway_slug ]['base_amount'];
					// Convert amount.
					$converted_amount = give_maybe_sanitize_amount( $fee_base_amount ) * $exchange_rates;

					// Format the amount.
					$fee_array['fee_data'][ $gateway_slug ]['base_amount'] = $converted_amount;
				}
			}
		}

		return $fee_array;
	}

	/**
	 * Convert fee base amount.
	 *
	 * @since 1.0
	 *
	 * @param float $base_amount Fee base amount.
	 *
	 * @return  float
	 */
	public function give_cs_give_fee_base_amount( $base_amount ) {
		if (
			isset( $_POST['give-form-id'], $_POST['give-cs-form-currency'] )
			&& ! empty( $_POST['give-cs-form-currency'] )
		) {
			$exchange_rates = give_cs_get_form_exchange_rates( give_clean( $_POST['give-form-id'] ), give_clean( $_POST['give-cs-form-currency'] ) );

			if ( ! empty( $exchange_rates ) ) {
				// Convert donation base fee amount.
				$base_amount *= $exchange_rates;
			}
		}

		return $base_amount;
	}

	/**
	 * Change Form's currency setting according to the donor's currency.
	 *
	 * @since 1.0
	 *
	 * @param array             $tags_array HTML tags array.
	 * @param \Give_Donate_Form $form       Donation Form.
	 *
	 * @return array
	 */
	public function give_cs_form_html_tags( $tags_array, $form ) {
		// Get the goal option.
		$goal_option = give_get_meta( $form->ID, '_give_goal_option', true );

		// If Goal option is enabled.
		if ( give_is_setting_enabled( $goal_option ) ) {
			// Get the goal format.
			$goal_format = give_get_meta( $form->ID, '_give_goal_format', true );

			// Set the goal format to the form data attribute.
			$tags_array['data-goal_format'] = $goal_format;
		}

		// Donation form amounts.
		$form_amounts          = [];
		$form_amounts_for_math = [];

		// unset all the filter.
		give_cs_set_donor_currency( false );

		// If donation form has variable prices.
		if ( give_has_variable_prices( $form->ID ) ) {
			// Get the variable prices.
			$form_levels = give_get_variable_prices( $form->ID );

			// Get the amount of the variable prices.
			foreach ( $form_levels as $price_id => $form_level ) {
				// Store the amount.
				$form_amounts[ $form_level['_give_id']['level_id'] ] = give_maybe_sanitize_amount( $form_level['_give_amount'] );
			}
		} else {
			// Get the donation form simple amount.
			$form_amounts[] = give_maybe_sanitize_amount( give_get_meta( $form->ID, '_give_set_price', true ) );
		}

		// reset all the filter
		give_cs_set_donor_currency();

		$form_amounts['custom']          = 0;
		$form_amounts_for_math['custom'] = 0;

		// Store the donation form amounts.
		$tags_array['data-give_cs_base_amounts'] = esc_attr( json_encode( $form_amounts ) );
		$tags_array['data-currency_position']    = give_get_option( 'currency_position', 'before' );

		return $tags_array;
	}

	/**
	 * Convert fee earning amount.
	 *
	 * @since 1.0
	 *
	 * @param double  $fee_amount Fee amount.
	 * @param integer $payment_id Donation ID.
	 *
	 * @return float|int
	 */
	public function give_fee_earning_amount( $fee_amount, $payment_id ) {
		$currency_switched = give_get_meta( $payment_id, '_give_cs_enabled', true );
		$form_id           = give_get_payment_form_id( $payment_id );
		$form_currency     = wp_doing_ajax() && isset( $_POST['selected_currency'] ) ? give_clean( $_POST['selected_currency'] ) : give_get_currency( $form_id );

		if ( give_is_setting_enabled( $currency_switched ) ) {
			$exchange_rate = give_get_meta( $payment_id, '_give_cs_exchange_rate', true );
			$fee_amount    = $fee_amount / $exchange_rate;
		}

		// Get Fee earnings per Form.
		$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $form_currency );

		if ( ! empty( $exchange_rate ) ) {
			$fee_amount = $fee_amount * $exchange_rate;
		}

		return $fee_amount;
	}

	/**
	 * Convert goal target amount.
	 *
	 * @since 1.0
	 *
	 * @param double  $goal_amount Donation goal target amount.
	 * @param integer $form_id     Donation Form ID.
	 *
	 * @return string $goal_amount
	 */
	public function give_cs_convert_goal_target_amount( $goal_amount, $form_id ) {

		$base_currency        = give_get_currency();
		$form_currency        = give_get_currency( $form_id );
		$goal_format          = give_get_form_goal_format( $form_id );
		$numeric_goal_formats = [ 'amount', 'percentage' ];

		// Update Goal, only if goal format is numeric.
		if (
			in_array( $goal_format, $numeric_goal_formats, true ) &&
			$form_currency !== $base_currency
		) {
			// Get exchange rates.
			$exchange_rate = give_cs_get_form_exchange_rates( $form_id, $form_currency );

			if ( ! empty( $exchange_rate ) ) {
				$goal_amount = $goal_amount * $exchange_rate;
			}
		}

		return $goal_amount;
	}

	/**
	 * Give Currency Switcher JSON data.
	 *
	 * @since 1.0
	 */
	public function give_cs_json_data() {
		global $give_cs_json_obj;
		?>
		<script type="text/javascript">
			/* <![CDATA[ */
			var give_cs_json_obj = '<?php echo addslashes( wp_json_encode( $give_cs_json_obj ) ); ?>';
			/* ]]> */
		</script>
		<?php
	}

	/**
	 * Increase amount.
	 *
	 * @since 1.1
	 *
	 * @param float   $amount     Increased amount.
	 * @param integer $form_id    Donation Form ID.
	 * @param integer $payment_id Donation ID.
	 *
	 * @return float
	 */
	public function give_cs_increase_amount( $amount, $form_id, $payment_id ) {
		return give_cs_update_form_earning( $amount, $form_id, $payment_id );
	}

	/**
	 * Decrease the amount from the donation form.
	 *
	 * @since 1.1
	 *
	 * @param float   $amount     Donation amount.
	 * @param integer $form_id    Donation Form ID.
	 * @param integer $payment_id Donation ID.
	 *
	 * @return float
	 */
	public function give_cs_decrease_earning( $amount, $form_id, $payment_id ) {
		return give_cs_update_form_earning( $amount, $form_id, $payment_id );
	}

	/**
	 * Fires after validating donation form fields.
	 *
	 * Allow you to hook to donation form errors.
	 *
	 * @since  1.3.1
	 * @access public
	 *
	 * @param bool|array $valid_data Validate fields.
	 *
	 * @return void
	 */
	public function checkout_error_checks( $valid_data ) {
		// Sanitize Posted Data.
		$post_data = give_clean( $_POST ); // WPCS: input var ok, CSRF ok.
		$form_id   = $post_data['give-form-id'];

		// Bailout.
		if ( ! give_cs_is_enabled( $form_id ) ) {
			return;
		}

		$form_currency = $post_data['give-cs-form-currency'];
		$gateway       = $post_data['give-gateway'];

		// Show error message if currency not support gateway
		if ( ! give_cs_is_gateway_support_currency( $form_id, $form_currency, $gateway ) ) {
			$notices_html = sprintf(
				'<b>%1$s</b> %2$s <b> %3$s</b> %4$s.',
				esc_attr( $form_currency ),
				__( 'is not supported by', 'give-currency-switcher' ),
				esc_attr( give_get_gateway_checkout_label( $gateway ) ),
				__( 'gateway. Please choose other gateway', 'give-currency-switcher' )
			);

			give_set_error( 'give_cs_currency_not_support_gateway', $notices_html );
		}

	}

	/**
	 * Update donor currency to prevent donation amount invalidation
	 *
	 * @since 1.3.6
	 */
	public function pre_checkout_error_checks() {
		if ( ! has_filter( 'give_default_form_amount', 'give_cs_convert_donation_amount' ) ) {
			give_cs_set_donor_currency();

			// Remove added filters.
			add_action( 'give_checkout_error_checks', [ $this, 'post_checkout_error_checks' ], 999, 1 );
		}
	}

	/**
	 *  Remove filters added on give_checkout_error_checks filter
	 *
	 * @since 1.3.6
	 */
	public function post_checkout_error_checks() {
		give_cs_set_donor_currency( false );
	}
}
