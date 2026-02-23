<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 *
 * @package    Give_Currency_Switcher
 * @subpackage Give_Currency_Switcher/includes/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Give_Currency_Switcher
 * @subpackage Give_Currency_Switcher/includes/admin
 * @author     GiveWP <info@givewp.com>
 */
class Give_Currency_Switcher_Admin {

	/**
	 * It indicates whether settings is for global or not.
	 *
	 * @var boolean $is_global
	 * @since 1.0
	 */
	private $is_global;

	/**
	 * Store the meta key for the currency setting option.
	 *
	 * @var string $meta_key
	 * @since 1.0
	 */
	private $meta_key;

	/**
	 * Get the donation Form ID.
	 *
	 * @var string $donation_id Store donation id.
	 * @since 1.0
	 */
	private $donation_id;

	/**
	 * Setting key.
	 *
	 * @since 1.0
	 * @var string $setting_id
	 */
	private $setting_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Set the global to true.
		$this->is_global = true;

		// Store Form ID.
		$this->donation_id = null;

		// Set the meta key.
		$this->meta_key = 'currency_switcher';

		// Setting key.
		$this->setting_id = 'currency-switcher';

		// Enqueue Styles for Admin.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		// Enqueue Script.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Add Give - Currency Switcher settings.
		add_filter( 'give-settings_get_settings_pages', array( $this, 'global_settings' ), 10, 1 );

		// Register Give - Currency Switcher Section and Settings on Per-Form.
		add_filter( 'give_metabox_form_data_settings', array( $this, 'per_form_settings' ), 10, 2 );

		// Introducing new field 'exchange_rates' for currency switcher.
		add_action( 'give_admin_field_exchange_rates', array( $this, 'give_cs_render_exchange_rate_field' ), 10, 2 );

		// Introducing new field 'exchange_rates' for currency switcher.
		add_action( 'give_admin_field_cs_gateway', array( $this, 'give_cs_render_currency_switcher_gateway' ), 10, 2 );

		// Update exchange rates.
		add_action( 'wp_ajax_cs_update_exchange_rates', array( $this, 'give_cs_update_exchange_rates_ajax' ), 10, 1 );

		// Show switched currency related data in donation edit page.
		add_action( 'give_donation_details_tbody_after', array( $this, 'give_cs_donation_currency_meta_data' ), 10, 1 );

		// Initially we need to store some currency switcher related data.
		add_action( 'give_post_process_give_forms_meta', array( $this, 'give_cs_save_custom_prices' ), 10, 2 );

		// Show warning when currency changed.
		add_action( 'admin_notices', array( $this, 'give_cs_base_currency_changed_notice' ), 10 );

		// Add custom field in metabox.
		add_filter( 'give_metabox_form_data_settings', array( $this, 'cs_custom_prices' ), 10, 2 );
		add_filter( 'give_donation_levels_table_row', array( $this, 'give_cs_custom_variable_prices_callback' ), 10, 1 );

		// Give total donation amount.
		add_filter( 'give_get_total_donation_amount', array( $this, 'give_cs_donor_donations_amount' ), 10, 2 );

		// Alter Payment amount on donor page.
		add_filter( 'give_get_donation_amount', array( $this, 'give_cs_donor_donation_amount' ), 10, 4 );

		// Update payment meta data.
		add_action( 'give_recurring_record_payment', array( $this, 'cs_give_recurring_record_payment' ), 10, 3 );

		// Render the supporting currency fields.
		add_action( 'give_admin_field_cs_support_currency_list', array( $this, 'give_cs_render_currency_list_field' ), 10, 2 );

		// When update currency switcher general setting.
		add_action( 'give_update_options_currency-switcher_general-settings', array( $this, 'cs_payment_gateway_update' ), 10, 2 );

		// Return proper donation amount based on the type context.
		add_filter( 'give_donation_amount', array( $this, 'give_cs_donation_amount' ), 10, 4 );

		// Change the donation total income.
		add_filter( 'give_get_form_earnings_stats', array( $this, 'give_cs_update_donation_goal_amount' ), 10, 3 );

		// Return base amount if currency was changed.
		add_filter( 'give_get_earnings_by_date', array( $this, 'give_cs_give_earnings_amount' ), 10, 2 );

		// Change donor's total amount.
		add_filter( 'give_export_set_donor_data', array( $this, 'give_cs_export_set_donor_data' ), 10, 2 );

		// Deprecation notice.
		add_action( 'admin_notices', array( $this, 'give_cs_deprecated_notices' ), 10 );

		// When update/edit the donation.
		add_action( 'give_updated_edited_donation', array( $this, 'give_cs_update_base_amount' ), 10, 1 );

		// Filter the donation level title.
		add_filter( 'give_get_donation_form_title', array( $this, 'give_cs_donation_form_title' ), 10, 2 );

		// Sanitize the custom amount of the various variable prices when saving the donation
		add_filter( 'give_pre_save_form_meta_value', array( $this, 'give_cs_db_sanitize_variable_custom_amount' ), 10, 2 );

		// Register saving callback for each tab.
		foreach ( Give_Currency_Switcher::$section_tab as $tab_key => $tab_label ) {

			// Sanitizing the function name.
			$tab_callback = str_replace( '-', '_', $tab_key );

			// If the method is exists.
			if ( method_exists( $this, $tab_callback ) ) {

				// Save exchange_rates field value.
				add_action( "give_update_options_{$this->setting_id}_{$tab_key}", array( $this, $tab_callback . '_update_options' ), 10, 2 );
			}
		}

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function enqueue_styles() {
		global $post_type;

		if ( ( isset( $_GET['page'] ) && 'give-settings' === $_GET['page'] ) || ( isset( $_GET['post_type'] ) && 'give_forms' === $_GET['post_type'] ) || ( 'give_forms' === $post_type ) ) {

			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_register_style( GIVE_CURRENCY_SWITCHER_SLUG, GIVE_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/css/give-currency-switcher-admin' . $suffix . '.css', array(), GIVE_CURRENCY_SWITCHER_VERSION, 'all' );
			wp_enqueue_style( GIVE_CURRENCY_SWITCHER_SLUG );

		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function enqueue_scripts() {
		global $post_type;

		if ( (
				 isset( $_GET['page'] )
				 && 'give-settings' === $_GET['page'] )
			 || ( isset( $_GET['post_type'] )
				  && 'give_forms' === $_GET['post_type'] )
			 || ( 'give_forms' === $post_type )
		) {

			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_register_script( GIVE_CURRENCY_SWITCHER_SLUG, GIVE_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/js/give-currency-switcher-admin' . $suffix . '.js', array( 'jquery' ), GIVE_CURRENCY_SWITCHER_VERSION, false );
			wp_enqueue_script( GIVE_CURRENCY_SWITCHER_SLUG );

			wp_localize_script(
				GIVE_CURRENCY_SWITCHER_SLUG, 'cs_admin_vars', array(
					'default_currency'      => give_get_currency(),
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'update_exchange_nonce' => wp_create_nonce( 'update_exchange_nonce' ),
					'failed_message'        => __( 'Unable to fetch the data please check Give log.', 'give-currency-switcher' ),
					'failed_fetch'          => __( 'Couldn\'t fetch exchange rate for: ', 'give-currency-switcher' ),
					'decimal_number_help'   => __( 'The number of decimals will be used to display and to round and the prices. Rounding will be mathematical, with halves rounded up.', 'give-currency-switcher' ),
					'rate_markup_help'      => __(
						'If specified, this markup will be added to the standard exchange rate.',
						'give-currency-switcher'
					),
					'messages'              => array(
						'exchange_rate_update' => __( 'Exchange rates successfully updated!', 'give-currency-switcher' ),
						'missed_rates_for'     => __( 'Couldn\'t fetch the exchange rate for: ', 'give-currency-switcher' ),
					),
				)
			);

		}// End if().

	}

	/**
	 * Add custom core plugin setting.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $settings Give Settings.
	 *
	 * @return array
	 */
	public function global_settings( $settings ) {

		$settings[] = include GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/includes/admin/class-give-currency-switcher-settings.php';

		return $settings;
	}

	/**
	 * Register 'Currency Switcher' menu on edit donation form.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $setting section array.
	 * @param int   $post_id donation Form ID.
	 *
	 * @return array $settings return the Currency Switcher sections array.
	 */
	public function per_form_settings( $setting, $post_id ) {

		// Set the global to false.
		$this->is_global = false;

		// Store donation id.
		$this->donation_id = $post_id;

		// Appending the per form tribute options.
		$setting[ "{$this->meta_key}_sub_fields" ] = apply_filters(
			'give_currency_switcher_per_form_options', array(
				'id'         => "{$this->meta_key}_general",
				'title'      => __( 'Currency Switcher', 'give-currency-switcher' ),
				'icon-html'  => '<span class="cs-icon-exchange"></span>',
				'fields'     => $this->get_setting_fields( 'general-settings' ),
				'sub-fields' => array(
					array(
						'id'        => "{$this->meta_key}_geolocation",
						'title'     => __( 'Geolocation', 'give-currency-switcher' ),
						'icon-html' => '<span class="dashicons dashicons-arrow-right-alt2"></span>',
						'fields'    => $this->get_setting_fields( 'geolocation' ),
					),
					array(
						'id'        => "{$this->meta_key}_gateway",
						'title'     => __( 'Gateways', 'give-currency-switcher' ),
						'icon-html' => '<span class="dashicons dashicons-arrow-right-alt2"></span>',
						'fields'    => $this->get_setting_fields( 'payment-gateway' ),
					),
				),
			)
		);

		// Get array of selected supported currencies.
		$selected_currencies = give_get_meta( $post_id, 'cs_supported_currency', true );

		// Array that holds default currency options.
		$default_currency_options = array();

		if ( is_array( $selected_currencies ) ) {
			foreach ( $selected_currencies as $key ) {
				$default_currency_options[ $key ] = sprintf( '%1$s (%2$s)', give_get_currency_name( $key ), give_currency_symbol( $key ) );
			}
		}

		$default_currency_options[0] = __( '-- Select a default currency --', 'give-currency-switcher' );

		// Move default currency to the 4th position in the fields array.
		array_splice(
			$setting[ "{$this->meta_key}_sub_fields" ]['fields'], 4, 0, array(
				array(
					'id'            => 'give_cs_default_currency',
					'name'          => __( 'Default Currency', 'give-currency-switcher' ),
					'type'          => 'select',
					'options'       => $default_currency_options,
					'wrapper_class' => 'cs_general_fields give_cs_default_currency give-hidden',
				),
			)
		);

		return $setting;
	}

	/**
	 * Get the Setting fields for the per form setting.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $section Setting section.
	 * @param array  $setting Setting array.
	 *
	 * @return array
	 */
	public function get_setting_fields( $section, $setting = array() ) {

		// Get the setting for specific section.
		$currency_settings = cs_get_setting_fields( $this->is_global, $section );

		// Return the array with new setting appended into it.
		return array_merge( $currency_settings, $setting );
	}

	/**
	 * Exchange Rate field callback.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $field        Field key.
	 * @param string $option_value Field Value.
	 */
	public function give_cs_render_exchange_rate_field( $field, $option_value ) {
		// Render custom setting field.
		give_cs_render_global_option( $field, $option_value, $field['type'] );
	}

	/**
	 * Render payment gateway field for the currency switcher.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $field        Field key.
	 * @param string $option_value Field Value.
	 */
	public function give_cs_render_currency_switcher_gateway( $field, $option_value ) {
		// Render payment gateway field.
		give_cs_render_global_option( $field, $option_value, $field['type'] );
	}

	/**
	 * Callback to save the General setting.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function general_settings_update_options() {
		if ( isset( $_POST['cs_exchange_rates'] ) ) {

			// Get the old values.
			$old_values = give_get_option( 'cs_exchange_rates' );

			// Update option.
			give_update_option( 'cs_exchange_rates', array_merge( $_POST['cs_exchange_rates'], $old_values ) );
		}
	}

	/**
	 * Callback to save the currency switcher payment gateway section's options.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function payment_gateway_update_options() {

		if ( isset( $_POST['cs_payment_gateway'] ) ) {

			// Get the old values.
			$old_values = give_get_option( 'cs_payment_gateway' );

			// Update option.
			give_update_option( 'cs_payment_gateway', array_merge( $_POST['cs_payment_gateway'], $old_values ) );
		}
	}

	/**
	 * Update exchange rates based on the selected API.
	 *
	 * Callback for the ajax action 'cs_update_exchange_rates'.
	 *
	 * @since 1.0
	 */
	public function give_cs_update_exchange_rates_ajax() {

		// Check nonce.
		check_ajax_referer( 'update_exchange_nonce', 'nonce' );

		// Get the donation form ID.
		$form_id = ! empty( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		// Get the exchange rates.
		$response = cs_fetch_exchange_rates_from_api( $form_id, give_get_currency() );

		// Check if any of the rates contain boolean false.
		$contains_false = in_array( false, array_values( $response['rates'] ), true );

		// Send response.
		if ( $contains_false ) {
			wp_send_json_error( $response );
		} else {
			wp_send_json_success( $response );
		}
	}

	/**
	 * Show exchange rate data.
	 *
	 * @since 1.0
	 *
	 * @param integer $payment_id Donation Payment ID.
	 */
	public function give_cs_donation_currency_meta_data( $payment_id ) {

		// Check if currency is switched in this payment.
		$is_currency_switched = give_get_meta( $payment_id, '_give_cs_enabled', true );

		if ( give_is_setting_enabled( $is_currency_switched ) ) {
			?>
			<p>
				<strong><?php echo __( 'Currency Switched:', 'give-currency-switcher' ); ?></strong><br>
				<?php
				esc_html_e( 'Exchange Rates: ', 'give-currency-switcher' );

				$rate = give_get_meta( $payment_id, '_give_cs_exchange_rate', true );
				echo number_format( $rate, 2, '.', '' );
				?>
				<br />
				<?php
				esc_html_e( 'Base Currency: ', 'give-currency-switcher' );
				$base_currency = give_get_meta( $payment_id, '_give_cs_base_currency', true );
				echo give_get_currency_name( $base_currency ) . ' ( ' . give_currency_symbol( $base_currency ) . ' ) <br/>';

				esc_html_e( 'Base total: ', 'give-currency-switcher' );
				$base_amount = give_get_meta( $payment_id, '_give_cs_base_amount', true );
				echo give_currency_filter(
					give_format_amount( $base_amount, array( 'currency_code' => $base_currency ) ), array(
						'currency_code' => $base_currency,
					)
				);
				?>
			</p>
			<?php
		}
	}

	/**
	 * Save Form meta data custom fields.
	 *
	 * @since 1.0
	 *
	 * @param integer $form_id  Form ID.
	 * @param array   $post_obj Posted data.
	 */
	public function give_cs_save_custom_prices( $form_id, $post_obj ) {

		if ( isset( $_POST['cs_supported_currency'] ) ) {

			// Get the supported currency.
			$selected_currencies = $_POST['cs_supported_currency'];

			// All of the active gateways.
			$payment_gateways = give_get_ordered_payment_gateways( give_get_enabled_payment_gateways() );

			if ( ! empty( $selected_currencies ) ) {

				// Currency Switcher payment gateway list.
				$gateway_list = isset( $_POST['cs_payment_gateway'] ) ? $_POST['cs_payment_gateway'] : array();

				foreach ( $selected_currencies as $currency ) {

					if ( ! isset( $gateway_list[ $currency ] ) ) {
						$gateway_list[ $currency ] = array_keys( $payment_gateways );
					}
				}

				// Update payment gateways values.
				give_update_meta( $form_id, 'cs_payment_gateway', $gateway_list );
			}
		}

		// Save if custom price option.
		if ( isset( $_POST['_give_currency_price'] ) ) {
			// Update payment gateways values.
			give_update_meta( $form_id, '_give_currency_price', give_clean( $_POST['_give_currency_price'] ) );

			// Save custom price.
			if ( isset( $_POST['_give_cs_custom_prices'] ) && ! empty( $_POST['_give_cs_custom_prices'] ) ) {
				$custom_amounts = array();

				// Get the custom amount.
				$posted_custom_amount = array_map( 'give_clean', $_POST['_give_cs_custom_prices'] );

				// Get all of the the custom amount.
				foreach ( $posted_custom_amount as $key => $amount ) {

					// Un-format the amount.
					$unformatted_amount     = give_sanitize_amount_for_db( $amount );
					$amount_to_check        = (float) $unformatted_amount;
					$custom_amounts[ $key ] = ! empty( $amount_to_check ) ? $unformatted_amount : '';
				}
				give_update_meta( $form_id, '_give_cs_custom_prices', $custom_amounts );
			}
		}
	}

	/**
	 * When update the supporting general setting and exchange rates update supported payment gateways.
	 *
	 * @since 1.0
	 */
	public function cs_payment_gateway_update() {

		if ( isset( $_POST['cs_supported_currency'] ) ) {

			// Get the supported currency.
			$selected_currencies = $_POST['cs_supported_currency'];

			// All of the active gateways.
			$payment_gateways = give_get_ordered_payment_gateways( give_get_enabled_payment_gateways() );

			if ( ! empty( $selected_currencies ) ) {

				// Currency Switcher payment gateway list.
				$gateway_list = isset( $_POST['cs_payment_gateway'] ) ? $_POST['cs_payment_gateway'] : array();

				foreach ( $selected_currencies as $currency ) {

					// Get saved gateway.
					$saved_gateway = give_cs_get_option( 'cs_payment_gateway' );

					if ( ! isset( $gateway_list[ $currency ] ) && ! isset( $saved_gateway[ $currency ] ) || empty( $saved_gateway[ $currency ] ) ) {
						$gateway_list[ $currency ] = array_keys( $payment_gateways );
					} else {
						$gateway_list[ $currency ] = $saved_gateway[ $currency ];
					}
				}

				// Update payment gateways values.
				give_update_option( 'cs_payment_gateway', $gateway_list );
			}
		}
	}

	/**
	 * When base currency has changed show warning/notice to the admin
	 * so that they can update the exchange rates in "Currency Switcher" options.
	 *
	 * @since 1.0
	 */
	public function give_cs_base_currency_changed_notice() {

		if ( ! is_admin() || ! isset( $_POST['currency'] ) ) {
			return;
		}

		// In case base currency has been changed.
		if ( give_get_currency() !== $_POST['currency'] ) {

			/**
			 * Show admin warning which suggest to update the exchange rates.
			 */
			Give()->notices->register_notice(
				array(
					'id'          => 'cs_base_currency_changed',
					'type'        => 'warning',
					'description' => sprintf( __( 'Look like you have changed the currency. Keep your your exchange rates up to date <a href="%s"> Exchange Rate settings.</a>.', 'give-currency-switcher' ), admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=currency-switcher' ) ),
					'show'        => true,
				)
			);
		}
	}

	/**
	 * Append new meta-box field to the Give Donation option tab.
	 *
	 * @since 1.0
	 *
	 * @param array   $settings Setting array.
	 * @param integer $post_id  Form ID.
	 *
	 * @return array Setting array
	 */
	public function cs_custom_prices( $settings, $post_id ) {
		$is_enabled = give_cs_is_enabled( $post_id );
		if ( ! $is_enabled ) {
			return $settings;
		}

		// Remove last setting array.
		$last_field = end( $settings['form_field_options']['fields'] );

		// Remove last setting field.
		array_pop( $settings['form_field_options']['fields'] );

		$fields = array(
			array(
				'name'        => __( 'Custom Prices', 'give-currency-switcher' ),
				'description' => __( 'Add price for each currency.', 'give-currency-switcher' ),
				'id'          => '_give_currency_price',
				'type'        => 'radio_inline',
				'default'     => 'disabled',
				'options'     => array(
					'enabled'  => __( 'Enabled', 'give-currency-switcher' ),
					'disabled' => __( 'Disabled', 'give-currency-switcher' ),
				),
			),
			array(
				'name'        => __( 'Set Amount', 'give-currency-switcher' ),
				'description' => __( 'Add custom amount for various currency.', 'give-currency-switcher' ),
				'id'          => '_give_cs_custom_prices',
				'type'        => 'cs_custom_prices',
			),
		);

		foreach ( $fields as $k => $field ) {
			$settings['form_field_options']['fields'][] = $field;
		}

		// Restore last field.
		$settings['form_field_options']['fields'][] = $last_field;

		return $settings;
	}

	/**
	 * Custom currency price.
	 *
	 * @since 1.0
	 *
	 * @param array $fields Fields array.
	 *
	 * @return array $fields donations levels.
	 */
	public function give_cs_custom_variable_prices_callback( $fields ) {
		$form_id    = give_get_admin_post_id();
		$is_enabled = give_cs_is_enabled( $form_id );

		if ( ! $is_enabled ) {
			return $fields;
		}

		$fields[] = array(
			'name'        => __( 'Currency Amounts', 'give-currency-switcher' ),
			'description' => __( 'Customize the donation amounts for each currency.', 'give-currency-switcher' ),
			'id'          => '_give_currency_price',
			'class'       => 'cs_custom_price_option',
			'type'        => 'radio_inline',
			'default'     => 'disabled',
			'options'     => array(
				'enabled'  => __( 'Custom', 'give-currency-switcher' ),
				'disabled' => __( 'Exchange Rate', 'give-currency-switcher' ),
			),
		);

		$fields[] = array(
			'name'          => __( 'Currency Amount', 'give-currency-switcher' ),
			'description'   => sprintf( '%s <br/> %s', __( 'Set the custom price for the currency.', 'give-currency-switcher' ), __( 'Note: You need to set the exchange rate first to set the custom price for a specific currency.', 'give-currency-switcher' ) ),
			'id'            => '_give_cs_custom_prices',
			'wrapper_class' => 'cs_custom_prices_wrap give-hidden',
			'type'          => 'cs_custom_prices',
		);

		return $fields;
	}

	/**
	 * Calculate and change the donation goal income.
	 *
	 * @since 1.0
	 *
	 * @param double            $earnings  Goal Income.
	 * @param integer           $form_id   Donation Form.
	 * @param \Give_Donate_Form $give_form Donation Form
	 *
	 * @return float|int
	 */
	public function give_cs_update_donation_goal_amount( $earnings, $form_id, $give_form ) {
		// If manual upgrade not completed, proceed with backward compatible code.
		if ( ! give_has_upgrade_completed( 'give_cs_v11_update_form_earnings' ) ) {
			if ( ! isset( $_POST['give_action'] ) ) {

				// Get goal total earning.
				$total_earning = give_cs_calculate_goal_income( $form_id );

				if ( ! empty( $total_earning ) ) {
					return $total_earning;
				}
			}
		}

		return $earnings;
	}

	/**
	 * Donor's total donation amount.
	 *
	 * @since 1.0
	 *
	 * @param string|float $donation_amount Donation amount.
	 * @param integer      $donor_id        Donation ID.
	 *
	 * @return string|float
	 */
	public function give_cs_donor_donations_amount( $donation_amount, $donor_id ) {

		// Total Donation amount.
		$total_donation_amount = 0;
		$payments_ids          = array();

		// Get donor.
		$donor = Give()->donors->get_donor_by( 'id', $donor_id );

		if ( strpos( $donor->payment_ids, ',' ) !== false ) {
			// Get payment ids.
			$payments_ids = explode( ',', $donor->payment_ids );
		} else {
			$payments_ids[] = $donor->payment_ids;
		}

		// if donor has made some donations.
		if ( ! empty( $payments_ids ) ) {
			foreach ( $payments_ids as $donation_id ) {

				// Add payment donation amount.
				$payment_base_total = give_get_meta( $donation_id, '_give_cs_base_amount', true );

				// Base total.
				if ( ! empty( $payment_base_total ) ) {
					$total_donation_amount += (float) give_maybe_sanitize_amount( $payment_base_total, array( 'currency' => give_get_currency() ) );
				} else {
					// If base amount is not there.
					$total_donation_amount += (float) give_donation_amount( $donation_id );
				}
			}
		}

		if ( $total_donation_amount > 0 ) {
			return $total_donation_amount;
		}

		return $donation_amount;
	}

	/**
	 * Filter donation amount on donor page.
	 *
	 * @since 1.0
	 *
	 * @param string  $formatted_amount Formatted donation amount.
	 * @param float   $amount           Donation amount.
	 * @param integer $payment_id       Donation ID.
	 * @param string  $type             Display type.
	 *
	 * @return string
	 */
	public function give_cs_donor_donation_amount( $formatted_amount, $amount, $payment_id, $type ) {

		// If it's for donor.
		if ( 'donor' === $type ) {

			// Check if currency is switched in this payment.
			$is_currency_switched = give_get_meta( $payment_id, '_give_cs_enabled', true );

			// If give currency was switched in this donation.
			if ( give_is_setting_enabled( $is_currency_switched ) ) {

				$cs_amount        = give_get_meta( $payment_id, '_give_payment_total', true );
				$cs_base_amount   = give_get_meta( $payment_id, '_give_cs_base_amount', true );
				$cs_base_currency = give_get_meta( $payment_id, '_give_cs_base_currency', true );
				$payment_currency = give_get_payment_currency_code( $payment_id );

				// Get the total donation amount.
				$payment_amount = give_currency_filter(
					give_format_amount(
						$cs_amount, array(
							'sanitize' => false,
							'currency' => $payment_currency,
						)
					), $payment_currency
				);

				// Get the total donation amount.
				$payment_base_amount = give_currency_filter(
					give_format_amount(
						$cs_base_amount, array(
							'sanitize' => false,
							'currency' => $cs_base_currency,
						)
					), $cs_base_currency
				);

				// Add base amount with total donation amount.
				return sprintf( '<span data-tooltip="%1$s">%2$s</span> ( %3$s )', give_get_payment_currency( $payment_id ), $payment_amount, $payment_base_amount );
			}
		}

		// Else, remove amount as it is.
		return $formatted_amount;
	}

	/**
	 * Return donation amount if it is for report than return base amount instead of
	 * Converted donation amount.
	 *
	 * @since 1.0
	 *
	 * @param string $formatted_amount Formatted donation amount.
	 * @param float  $donation_amount  Un-formatted donation amount.
	 * @param int    $donation_id      Donation ID.
	 * @param array  $format_args      Amount formatting array.
	 *
	 * @return mixed
	 */
	public function give_cs_donation_amount( $formatted_amount, $donation_amount, $donation_id, $format_args ) {

		// If format args contain type.
		if ( isset( $format_args['type'] ) ) {
			// Backward compatibility.
			if ( $donation_id instanceof Give_Payment ) {
				$donation_id = $donation_id->ID;
			}

			$is_currency_switcher_active = give_get_meta( $donation_id, '_give_cs_enabled', true );

			if ( give_is_setting_enabled( $is_currency_switcher_active ) ) {

				$base_total             = give_get_meta( $donation_id, '_give_cs_base_amount', true );
				$donation_currency      = give_get_payment_currency_code( $donation_id );
				$donation_base_currency = give_get_meta( $donation_id, '_give_cs_base_currency', true );
				$decimal_precision      = ( 'BTC' !== $donation_currency ) ? 6 : 10;

				switch ( $format_args['type'] ) {

					case 'donor':

						$formatted_base_amount = give_currency_filter(
							give_format_amount(
								round( $base_total, $decimal_precision ),
								array(
									'currency' => $donation_base_currency,
								)
							),
							array(
								'currency_code' => $donation_base_currency,
							)
						);

						// Add base amount with total donation amount.
						return sprintf(
							'<span data-tooltip="%1$s">%2$s</span> ( %3$s )',
							give_get_payment_currency( $donation_id ),
							$formatted_amount,
							$formatted_base_amount
						);
					case 'stats':

						return give_format_amount(
							round( $base_total, $decimal_precision ), array(
								'sanitize' => false,
								'currency' => $donation_currency,
							)
						);

				} // End switch().
			} // End if().
		} // End if().

		return $formatted_amount;
	}

	/**
	 * When creating renew donation of any subscription donation, here in this function we are
	 * updating parent payment's exchange rate, base currency and base amount according to the
	 * custom amount.
	 *
	 * @since 1.0
	 *
	 * @param Give_Payment $payment           Renew payment.
	 * @param integer      $parent_payment_id Subscription parent payment ID.
	 * @param float        $amount            Donation amount.
	 *
	 * @return bool
	 */
	public function cs_give_recurring_record_payment( $payment, $parent_payment_id, $amount ) {

		// Bail out, if not a recurring parent donation.
		if ( empty( $parent_payment_id ) ) {
			return false;
		}

		// If the currency is switched for this payment.
		$is_currency_switcher_active = give_get_meta( $parent_payment_id, '_give_cs_enabled', true );

		if ( give_is_setting_enabled( $is_currency_switcher_active ) ) {

			$default_currency = give_get_currency( $payment->form_id );
			$exchange_rate    = give_cs_get_form_exchange_rates( $payment->form_id, $payment->currency );

			// Get exchange rate from the payment meta, if not exists.
			if ( empty( $exchange_rate ) ) {

				// Get donation's exchange rate.
				$exchange_rate = give_get_meta( $parent_payment_id, '_give_cs_exchange_rate', true );
			}

			// Get parent base currency.
			$donation_base_currency = give_get_meta( $parent_payment_id, '_give_cs_base_currency', true );

			if ( ! empty( $donation_base_currency ) && $donation_base_currency === $default_currency ) {

				// Get the base amount.
				$decimal_precision = ( 'BTC' !== $donation_base_currency ) ? 6 : 10;
				$base_amount       = round( $amount / $exchange_rate, $decimal_precision );

				if ( ! empty( $base_amount ) ) {
					give_update_meta( $payment->ID, '_give_cs_enabled', 'enabled' );
					give_update_meta( $payment->ID, '_give_cs_exchange_rate', $exchange_rate );
					give_update_meta( $payment->ID, '_give_cs_base_currency', $default_currency );
					give_update_meta( $payment->ID, '_give_cs_base_amount', $base_amount );
				}
			}
		}
	}

	/**
	 * Render the supporting columns
	 *
	 * @since 1.0
	 *
	 * @param $field
	 * @param $option_value
	 */
	public function give_cs_render_currency_list_field( $field, $option_value ) {
		// Get the supported currency list.
		give_cs_render_active_currency_list( $field, $option_value );
	}

	/**
	 * Modify the donation total amount.
	 *
	 * @since 1.0
	 *
	 * @param array       $data Donation data.
	 * @param \Give_Donor $donor
	 *
	 * @return array
	 */
	public function give_cs_export_set_donor_data( $data, $donor ) {
		// Get the donor by id.
		$donor_obj             = Give()->donors->get_donor_by( 'id', $donor->id );
		$donor_donation_amount = $this->give_cs_donor_donations_amount( $donor_obj->purchase_value, $donor->id );

		// Check if donor donation amount is not blank.
		if ( ! empty( $donor_donation_amount ) ) {
			$data['donation_sum'] = $donor_donation_amount;
		}

		return $data;
	}

	/**
	 * Return donation's base total amount for report calculation.
	 *
	 * @since 1.0
	 * @since 1.1 Update the form earning to the sum of the base total.
	 *
	 * @param float  $earning_totals Donation total amount.
	 * @param string $donations      Donation IDs.
	 *
	 * @return mixed
	 */
	public function give_cs_give_earnings_amount( $earning_totals, $donations ) {
		// If manual upgrade not completed, proceed with backward compatible code.
		if ( ! give_has_upgrade_completed( 'give_cs_v11_update_form_earnings' ) ) {

			// Get the donation IDs.
			$donation_ids = explode( ',', $donations );
			if ( ! empty( $donation_ids ) ) {

				// Set earning totals to 0.
				$earning_totals = 0;

				foreach ( $donation_ids as $donation_id ) {
					$base_amount     = give_get_meta( $donation_id, '_give_cs_base_amount', true );
					$earning_totals += $base_amount ? $base_amount : give_get_payment_total( $donation_id );
				}
			}
		}

		return $earning_totals;
	}

	/**
	 * Show deprecation notice.
	 *
	 * @since 1.0.4
	 */
	public function give_cs_deprecated_notices() {
		// Google finance API deprecation notice.
		$exchange_rate = give_cs_get_option( 'cs_exchange_rates_providers' );

		if ( 'google_finance' === $exchange_rate ) {
			/**
			 * Show admin warning which suggest to update the exchange rates.
			 */
			Give()->notices->register_notice(
				array(
					'id'          => 'cs_google_api_deprecated',
					'type'        => 'warning',
					'description' => sprintf( __( 'Google Finance API is now deprecated. Please update your <a href="%s">Exchange Rate Provider.</a>', 'give-currency-switcher' ), admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=currency-switcher&section=exchange-rates-api' ) ),
					'show'        => true,
				)
			);
		}
	}

	/**
	 * Convert the changed amount into the base currency.
	 *
	 * @since 1.1
	 *
	 * @param int $payment_id Donation ID.
	 */
	public function give_cs_update_base_amount( $payment_id ) {
		// Is cs enabled.
		$cs_enabled = give_get_meta( $payment_id, '_give_cs_enabled', true );
		if ( give_is_setting_enabled( $cs_enabled ) ) {

			// Get the payment total.
			$payment_total = give_get_meta( $payment_id, '_give_payment_total', true );

			// Get the payment total from the $_POST or meta.
			$new_total = ! empty( $_POST['give-payment-total'] ) ? $_POST['give-payment-total'] : $payment_total;
			$new_total = is_numeric( $new_total ) ? $new_total : 0;

			$exchange_rate       = give_get_meta( $payment_id, '_give_cs_exchange_rate', true );
			$payment_base_amount = give_get_meta( $payment_id, '_give_cs_base_amount', true );

			// Convert the amount to base currency.
			$new_base_amount = $new_total / $exchange_rate;
			$new_base_amount = round( $new_base_amount, 0 );

			give_update_meta( $payment_id, '_give_cs_base_amount', $new_base_amount );

			// Get the form id.
			$form_id = give_get_payment_form_id( $payment_id );

			/** @var \Give_Donate_Form $new_form */
			$new_form = new Give_Donate_Form( $form_id );

			if ( $new_base_amount > $payment_base_amount ) {
				$diff_amount = $new_base_amount - $payment_base_amount;
				$new_form->increase_earnings( $diff_amount );
			} else {
				$diff_amount = $payment_base_amount - $new_base_amount;
				$new_form->decrease_earnings( $diff_amount );
			}
		}
	}

	/**
	 * Customize the donation custom amount label.
	 *
	 * @since 1.1
	 *
	 * @param string  $form_title_html Donation form title.
	 * @param integer $donation_id     Donation form ID.
	 *
	 * @return string
	 */
	public function give_cs_donation_form_title( $form_title_html, $donation_id ) {
		if ( ! is_admin() ) {
			return $form_title_html;
		}

		// Get the donation form id.
		$form_id = give_get_payment_form_id( $donation_id );

		$give_cs_enabled    = give_get_meta( $donation_id, '_give_cs_enabled', true );
		$form_custom_amount = give_get_meta( $form_id, '_give_custom_amount', true );

		if (
			give_is_setting_enabled( $give_cs_enabled )
			&& ! give_has_variable_prices( $form_id )
			&& give_is_setting_enabled( $form_custom_amount )
		) {
			// Get the base amount.
			$base_amount = give_get_meta( $donation_id, '_give_cs_base_amount', true );
			$base_amount = give_format_amount( $base_amount, array( 'sanitize' => false ) );

			// Get the donation set amount.
			$form_set_amount = give_get_meta( $form_id, '_give_set_price', true );
			$form_set_amount = give_format_amount( $form_set_amount, array( 'sanitize' => false ) );

			// Check if the base amount and the donation default amount is same.
			if ( $form_set_amount === $base_amount ) {
				$form_title_html = '';
			}
		}

		// Return the donation form title.
		return $form_title_html;
	}

	/**
	 * Sanitize the custom amount of the variable price levels.
	 *
	 * @since 1.1.1
	 *
	 * @param array  $form_meta     Form meta data.
	 * @param string $form_meta_key Form meta key.
	 *
	 * @return mixed
	 */
	public function give_cs_db_sanitize_variable_custom_amount( $form_meta, $form_meta_key ) {

		// Return default, if meta key is not match.
		if ( '_give_donation_levels' !== $form_meta_key ) {
			return $form_meta;
		}

		if ( ! empty( $form_meta ) ) {
			// Process the meta to modify.
			foreach ( $form_meta as $key => $meta_data ) {
				if (
					isset( $meta_data['_give_cs_custom_prices'] )
					&& ! empty( $meta_data['_give_cs_custom_prices'] )
				) {
					foreach ( $meta_data['_give_cs_custom_prices'] as $currency_key => $amount ) {
						$unformatted_amount = give_sanitize_amount_for_db( $amount );
						$amount_in_float    = (float) $unformatted_amount;

						$form_meta[ $key ]['_give_cs_custom_prices'][ $currency_key ] = ! empty( $amount_in_float ) ? $unformatted_amount : '';
					}
				}
			}
		}

		return $form_meta;
	}
}
