<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 *
 * @package    Give_Fee_Recovery
 * @subpackage Give_Fee_Recovery/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Give_Fee_Recovery
 * @subpackage Give_Fee_Recovery/admin
 * @author     GiveWP <https://givewp.com>
 */
class Give_Fee_Recovery_Admin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function __construct() {

		// Enqueue Script and Style for Admin.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Add Global Fee Recovery settings.
		add_filter( 'give-settings_get_settings_pages', array( $this, 'global_settings' ), 10, 1 );

		// Register new type in Give Recovery Fee settings for Gateway inside fields.
		add_action( 'give_admin_field_give_fee_gateways_fields', array( $this, 'gateways_fields_settings' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'gateway_field_save' ) );

		// Register new type in Give Recovery Fee settings for Fee Percentage and Fee Base amount.
		add_action( 'give_admin_field_fee_recovery_percentage_text', array( $this, 'percentage_text_settings' ), 10, 2 );
		add_action( 'give_admin_field_fee_recovery_base_amount_text', array( $this, 'base_amount_text_settings' ), 10, 2 );

		// Register Fee Recovery Section and Settings on Give Donation form.
		add_action( 'give_metabox_form_data_settings', array( $this, 'per_form_settings' ), 10, 1 );
		add_filter( 'give_form_fee_donation_metabox_fields', array( $this, 'per_form_callback' ), 10, 1 );

		// Handle the save action in Per-Form Gateway fields settings.
		add_action( 'give_post_process_give_forms_meta', array( $this, 'per_form_save' ), 10, 1 );

		// Add Fee Recovery on Donation detail section.
		add_action( 'give_donation_details_thead_before', array( $this, 'donation_detail' ), 10, 1 );

		// Include Fee Report settings.
		add_filter( 'give-reports_get_settings_pages', array( $this, 'report_page' ) );

		// Add new Fee Column.
		add_action( 'give_payments_table_columns', array( $this, 'columns' ), 10, 1 );

		// Add new Fee Column data.
		add_action( 'give_payments_table_column', array( $this, 'column_data' ), 9, 3 );

		// Make Fee Column sortable.
		add_action( 'give_payments_table_sortable_columns', array( $this, 'sortable_column' ), 10, 1 );

		// Update Sorting based on the Fee meta value.
		add_action( 'give_pre_get_payments', array( $this, 'pre_get_payments' ), 10, 1 );

		// Hook before update donation purchase.
		add_action( 'give_update_edited_donation', array( $this, 'before_update_donation' ), 10, 1 );

		// Hook after update donation purchase.
		add_action( 'give_updated_edited_donation', array( $this, 'after_updated_edited_donation' ), 10, 1 );

		// Add report filter hook for customize redirect url.
		add_action( 'give_filter_reports', array( $this, 'parse_report_dates' ), 0, 1 );

		// Hook update donation payment.
		add_action( 'give_view_donation_details_totals_after', array( $this, 'show_fee_order_detail' ), 10, 1 );

		// Update Global value before save.
		add_filter( 'give_admin_settings_sanitize_option', array( $this, 'pre_save_global_value' ), 10, 2 );

		// Override Give Email tags.
		add_action( 'give_add_email_tags', array( $this, 'email_tags' ), 999999 );

		// Modify amount tag for Preview when Give core >= 2.0 .
		add_filter( 'give_email_tag_amount', array( $this, 'preview_amount_tag' ), 10, 2 );

		// Decrease Form Fee earnings on Delete Donation.
		add_action( 'give_payment_delete', array( $this, 'decrease_form_fee_earnings_on_delete' ), 10, 1 );

		// Add new tool options for recount form fee earnings.
		add_action( 'give_recount_tool_options', array( $this, 'add_fee_earnings_recount_options' ) );

		// Add descriptions for the tools.
		add_action( 'give_recount_tool_descriptions', array( $this, 'give_fee_tool_descriptions' ) );

		// Include Batch export file.
		add_action( 'give_batch_export_class_include', array( $this, 'give_fee_include_batch_export_class' ), 10, 1 );

		// Reset form fee earnings for clone form.
		add_filter( 'give_duplicate_form_reset_stat_meta_keys', array( $this, 'reset_form_fee_earnings' ), 10, 1 );

		// Exclude Fee from the renewal amount and get price_id.
		add_filter( 'give_recurring_renewal_price_id', array( $this, 'exclude_fee_from_renewal_amount' ), 10, 3 );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
     * @since 1.9.5 removed SCRIPT_DEBUG support as base file cannot be used directly
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $post_type;

		if ( ( isset( $_GET['page'] ) && 'give-settings' === $_GET['page'] )
		     || ( isset( $_GET['post_type'] )
		          && 'give_forms' === $_GET['post_type'] )
		     || ( 'give_forms' === $post_type ) ) {

			// Enqueuing give fee recovery admin side css.
			wp_register_style( GIVE_FEE_RECOVERY_SLUG, GIVE_FEE_RECOVERY_PLUGIN_URL . 'assets/css/give-fee-recovery-admin.min.css', array(), GIVE_FEE_RECOVERY_VERSION, 'all' );
			wp_enqueue_style( GIVE_FEE_RECOVERY_SLUG );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
     * @since 1.9.5 removed SCRIPT_DEBUG support as base file cannot be used directly
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if ( give_is_admin_page() ) {
			// Enqueuing give fee recovery admin JS script.
			wp_register_script( GIVE_FEE_RECOVERY_SLUG, GIVE_FEE_RECOVERY_PLUGIN_URL . 'assets/js/give-fee-recovery-admin.min.js', array( 'give-admin-scripts' ),
				GIVE_FEE_RECOVERY_VERSION, false );
			wp_enqueue_script( GIVE_FEE_RECOVERY_SLUG );

			wp_localize_script(GIVE_FEE_RECOVERY_SLUG, 'give_fee_recovery_object', array(
                'give_fee_zero_based_currency' => wp_json_encode(array_keys(give_fee_zero_based_currency_code())),
                'give_fee_currency_code'       => give_get_currency(),
            ));
		}

	}

	/**
	 * Add Give Fee Recovery setting section.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $settings Give Settings.
	 *
	 * @return array $settings Give Settings.
	 */
	public function global_settings( $settings ) {

		$settings[] = include GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/admin/class-give-fee-recovery-settings.php';

		return $settings;
	}

	/**
	 * It will list out per gateway fee recovery setting on global Fee Recovery
	 * settings page.
	 *
	 * @since   1.0.0
	 * @access  public
	 *
	 * @param   array  $value        Pass various value from Setting api array.
	 * @param   string $option_value Option value for button.
	 *
	 * @return void
	 */
	public function gateways_fields_settings( $value, $option_value ) {

		// Return if value type is not set.
		if ( ! isset( $value['type'] ) ) {
			return;
		}

		// Get Give payment gateways in a ordered list.
		$gateways = give_get_ordered_payment_gateways( give_get_enabled_payment_gateways() );

		// Return if there is no Gateway.
		if ( ! isset( $gateways ) ) {
			return;
		}
		?>
		<tr valign="top <?php echo esc_attr( $value['id'] ); ?>" <?php echo ! empty( $value['wrapper_class'] ) ? 'class="' . $value['wrapper_class'] . '"' : ''; ?>>
			<td colspan="2">
				<?php
				foreach ( $gateways as $gateway_key => $gateway ) :
					?>
					<fieldset class="give_fee_gateway">
						<legend><?php echo $gateway['admin_label']; ?></legend>

						<table class="form-table give-setting-tab-body give-setting-tab-body-gateways">
							<tbody>
							<?php
							// Fee Recovery gateway fields.
							$gateway_fields = $value['all_fields'];
							$fields         = array();

							// Loop for the gateway's field.
							foreach ( $gateway_fields as $key => $gateway_field ) {

								// Store reconstruct array from the Gateway fields.
								$custom_fields = array();
								if ( isset( $gateway_field ) && is_array( $gateway_field ) ) {

									// Loop for reconstruct array.
									foreach ( $gateway_field as $field_key => $field ) {
										if ( 'id' === $field_key ) {
											// Append gateway slug.
											$custom_fields[ $field_key ] = $field . '_' . $gateway_key;
										} else {
											$custom_fields[ $field_key ] = $field;
										}
									}
								}
								// Storing array in new reconstruct array.
								$fields[ $key ] = $custom_fields;
							}

							// Output custom Give Fee Recovery Gateway Configuration.
							Give_Admin_Settings::output_fields( $fields, 'give_settings' );
							?>
							</tbody>
						</table>
					</fieldset>
				<?php
				endforeach;
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save Custom Fee Recovery Gateway's inside fields settings.
	 *
     * @since  1.8.0 add support for max fee coverage.
	 * @since  1.0.0
	 * @access public
	 *
	 * @return bool
	 */
	public function gateway_field_save() {

		// Get current section.
		$current_section = give_get_current_setting_tab();

		// Return if current section is not givefeerecovery.
		if ( 'givefeerecovery' !== $current_section ) {
			return false;
		}

		if ( empty( $_REQUEST['_give-save-settings'] ) || ! wp_verify_nonce( $_REQUEST['_give-save-settings'], 'give-save-settings' ) ) {
			return false;
		}

		$post_data = give_clean( $_POST ); // WPCS: input var ok, CSRF ok.

		// Return if submitted donation's form data is empty.
		if ( empty( $post_data ) ) {
			return false;
		}

		// Update option of Give Fee recovery custom Payment gateway Configuration.
		foreach ( $post_data as $option_name => $option_value ) {

			if ( false !== strpos( $option_name, 'give_fee_gateway_fee' ) ) {

				if ( false !== strpos( $option_name, 'give_fee_gateway_fee_percentage' ) ) {
					$option_value = ( '' !== $option_value ) ? give_fee_number_format( $option_value ) : '2.90';
					$option_value = give_sanitize_amount_for_db( $option_value );
				} elseif ( false !== strpos( $option_name, 'give_fee_gateway_fee_base_amount' ) ) {
					$option_value = ( '' !== $option_value ) ? give_fee_number_format( $option_value ) : '0.30';
					$option_value = give_sanitize_amount_for_db( $option_value );
				} elseif ( false !== strpos( $option_name, 'give_fee_gateway_maximum_fee_amount' ) ) {
					$option_value = ( '' !== $option_value ) ? give_fee_number_format( $option_value ) : '0.00';
					$option_value = give_sanitize_amount_for_db( $option_value );
				}

				// Update option.
				give_update_option( $option_name, $option_value );
			}

			if ( false !== strpos( $option_name, 'give_fee_percentage' ) ) {
				$option_value = ( '' !== $option_value ) ? give_fee_number_format( $option_value ) : '2.90';
				$option_value = give_sanitize_amount_for_db( $option_value );
				give_update_option( $option_name, $option_value );
			} elseif ( false !== strpos( $option_name, 'give_fee_base_amount' ) ) {
				$option_value = ( '' !== $option_value ) ? give_fee_number_format( $option_value ) : '0.30';
				$option_value = give_sanitize_amount_for_db( $option_value );
				give_update_option( $option_name, $option_value );
			} elseif ( false !== strpos( $option_name, 'give_fee_maximum_fee_amount' ) ) {
				$option_value = ( '' !== $option_value ) ? give_fee_number_format( $option_value ) : '0.00';
				$option_value = give_sanitize_amount_for_db( $option_value );
				give_update_option( $option_name, $option_value );
			}
		}

		return true;
	}

	/**
	 * It's register Fee Percentage text amount field in GiveWP Setting API.
	 * Setting API.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array  $value        value of Percentage text amount.
	 * @param string $option_value option Value of Percentage text amount.
	 */
	public function percentage_text_settings( $value, $option_value ) {

		$value['style']         = isset( $value['style'] ) ? $value['style'] : '';
		$value['wrapper_class'] = isset( $value['wrapper_class'] ) ? $value['wrapper_class'] : '';
		$value['type']          = isset( $value['type'] ) ? $value['type'] : 'text';
		$value['after_field']   = '';
		$data_type              = empty( $value['data_type'] ) ? '' : $value['data_type'];

		// Get option value by option ID.
		$option_value = Give_Admin_Settings::get_option( 'give_settings', $value['id'], $value['default'] );

		// Check if Data type is set or not.
		if ( isset( $data_type ) ) {
			$value['attributes']['class'] .= ' give_input_decimal';
			$option_value                 = ( '' !== $option_value ) ? $option_value : '2.90';
			$value['after_field']         = '<span class="give-percentage-symbol give-percentage-symbol-after">%</span>';
		}

		?>
		<tr valign="top give_fee_percentage" <?php echo ! empty( $value['wrapper_class'] ) ? 'class="' . $value['wrapper_class'] . '"' : ''; ?>>
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_attr( $value['name'] ); ?></label>
			</th>
			<td class="fee-recovery-give_fee_percentage" colspan="2">
				<input type="<?php echo esc_attr( $value['type'] ); ?>"
				       style="<?php echo esc_attr( $value['style'] ); ?>"
				       name="<?php echo esc_attr( $value['id'] ); ?>"
				       id="<?php echo esc_attr( $value['id'] ); ?>"
				       value="<?php echo esc_attr( give_format_decimal( $option_value ) ); ?>"
					<?php echo give_get_custom_attributes( $value ); ?>
				/>
				<?php echo $value['after_field']; ?>
				<p class="give-field-description"><?php echo give_get_field_description( $value ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * It's register Fee Base amount text field in GiveWP Setting API.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $value        Field value array.
	 * @param mixed $option_value Field value.
	 */
	public function base_amount_text_settings( $value, $option_value ) {

		$value['style']         = isset( $value['style'] ) ? $value['style'] : '';
		$value['wrapper_class'] = isset( $value['wrapper_class'] ) ? $value['wrapper_class'] : '';
		$option_value           = Give_Admin_Settings::get_option( 'give_settings', $value['id'], $value['default'] );
		$value['type']          = isset( $value['type'] ) ? $value['type'] : 'text';
		$value['after_field']   = '';
		$value['before_field']  = '';
		$data_type              = empty( $value['data_type'] ) ? '' : $value['data_type'];

		switch ( $data_type ) {
			case 'price':
				$option_value          = ( '' !== $option_value ) ? $option_value : '0.30';
				$value['after_field']  = '<span class="give-money-symbol give-money-symbol-after">' . give_currency_symbol() . '</span>';
				$value['before_field'] = '<span class="give-money-symbol give-money-symbol-before">' . give_currency_symbol() . '</span>';
				break;

			case 'decimal':
				$value['attributes']['class'] .= ' give_input_decimal';
				$option_value                 = ( '' !== $option_value ) ? $option_value : '0.30';
				$value['after_field']         = '<span class="give-money-symbol give-money-symbol-after">' . give_currency_symbol() . '</span>';
				$value['before_field']        = '<span class="give-money-symbol give-money-symbol-before">' . give_currency_symbol() . '</span>';
				break;

			default:
				break;
		}// End switch().
		?>
		<tr valign="top give_fee_base_amount" <?php echo ! empty( $value['wrapper_class'] ) ? 'class="' . $value['wrapper_class'] . '"' : ''; ?>>
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_attr( $value['name'] ); ?></label>
			</th>
			<td class="fee-recovery-give_fee_base_amount" colspan="2">
				<?php echo 'before' === give_get_option( 'currency_position' ) ? $value['before_field'] : ''; ?>
				<input
						type="<?php echo esc_attr( $value['type'] ); ?>"
						style="<?php echo esc_attr( $value['style'] ); ?>"
						name="<?php echo esc_attr( $value['id'] ); ?>"
						id="<?php echo esc_attr( $value['id'] ); ?>"
						value="<?php echo esc_attr( give_format_decimal( $option_value ) ); ?>"
					<?php echo give_get_custom_attributes( $value ); ?>
				/>
				<?php echo 'after' === give_get_option( 'currency_position' ) ? $value['after_field'] : ''; ?>
				<p class="give-field-description"><?php echo give_get_field_description( $value ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Register Setting fields for 'Fee Recovery' section in donation form edit page.
	 *
	 * @since  1.0.0
	 * @since  1.3.0 Reform Settings.
	 * @access public
	 *
	 * @return array
	 */
	public function per_form_callback() {

		$is_global = false; // Set Global flag.

		$settings = give_fee_settings( $is_global );

		return $settings;
	}

	/**
	 * Register 'Fee Recovery' section on edit donation form page.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $setting section array.
	 *
	 * @return array $settings return the fee recovery sections array.
	 */
	public function per_form_settings( $setting ) {

		// Appending the form fee option section.
		$setting['form_fee_options'] = apply_filters(
			'give_forms_fee_options', array(
				'id'        => 'form_fee_options',
				'title'     => __( 'Fee Recovery', 'give-fee-recovery' ),
				'icon-html' => '<span class="give-icon give-icon-fee-recovery"></span>',
				'fields'    => apply_filters( 'give_form_fee_donation_metabox_fields', array() ),
			)
		);

		return $setting;
	}

	/**
	 * Save Per-Form Gateway inside fields settings.
	 *
     * @since  1.8.0 add support for max fee coverage.
	 * @since  1.0.0
	 * @access public
	 *
	 * @param int $post_id Give Form ID.
	 *
	 * @return bool
	 */
	public function per_form_save( $post_id ) {
		// Return if Post is not set.
		if ( ! isset( $_POST ) ) {
			return false;
		}

		$post_data = give_clean( $_POST ); // WPCS: input var ok, CSRF ok.

		// Save Per-Form Settings.
		foreach ( $post_data as $option_key => $option_value ) {

			// Clean the value.
			$form_meta_value = $post_data[ $option_key ];

			// Check if option key is related to gateway field setting.
			if ( false !== strpos( $option_key, '_form_gateway_fee_' ) ) {

				if ( false !== strpos( $option_key, '_form_gateway_fee_percentage_' ) ) {
					$form_meta_value = ( '' !== $form_meta_value ) ? give_fee_number_format( $form_meta_value ) : '2.90';
					$form_meta_value = give_sanitize_amount_for_db( $form_meta_value );
				} elseif ( false !== strpos( $option_key, '_form_gateway_fee_base_amount_' ) ) {
					$form_meta_value = ( '' !== $form_meta_value ) ? give_fee_number_format( $form_meta_value ) : '0.30';
					$form_meta_value = give_sanitize_amount_for_db( $form_meta_value );
				} elseif ( false !== strpos( $option_key, '_form_gateway_fee_maximum_fee_amount_' ) ) {
					$form_meta_value = ( '' !== $form_meta_value ) ? give_fee_number_format( $form_meta_value ) : '0.00';
					$form_meta_value = give_sanitize_amount_for_db( $form_meta_value );
				}

				// Update field value inside the post id.
				give_update_meta( $post_id, $option_key, $form_meta_value );
			}

			if ( false !== strpos( $option_key, '_form_give_fee_' ) ) {
				if ( false !== strpos( $option_key, '_form_give_fee_percentage' ) ) {
					$form_meta_value = ( '' !== $form_meta_value ) ? give_fee_number_format( $form_meta_value ) : '2.90';
					$form_meta_value = give_sanitize_amount_for_db( $form_meta_value );
					give_update_meta( $post_id, $option_key, $form_meta_value );
				} elseif ( false !== strpos( $option_key, '_form_give_fee_base_amount' ) ) {
					$form_meta_value = ( '' !== $form_meta_value ) ? give_fee_number_format( $form_meta_value ) : '0.30';
					$form_meta_value = give_sanitize_amount_for_db( $form_meta_value );
					give_update_meta( $post_id, $option_key, $form_meta_value );
				} elseif ( false !== strpos( $option_key, '_form_give_fee_maximum_fee_amount' ) ) {
					$form_meta_value = ( '' !== $form_meta_value ) ? give_fee_number_format( $form_meta_value ) : '0.00';
					$form_meta_value = give_sanitize_amount_for_db( $form_meta_value );
					give_update_meta( $post_id, $option_key, $form_meta_value );
				}
			}

		}// End foreach().

		return true;
	}

	/**
	 * Add Give Fee in donation details section.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param int $payment_id Payment id.
	 */
	public function donation_detail( $payment_id ) {
		$payment_currency = give_get_payment_currency_code( $payment_id );
		$number_decimals  = give_get_price_decimals( $payment_currency );

		// Get total Donation amount.
		$total_donation = give_fee_format_amount(
			give_maybe_sanitize_amount( give_get_meta( $payment_id, '_give_payment_total', true ),
				array(
					'number_decimals' => $number_decimals,
					'currency'        => $payment_currency,
				)
			),
			array(
				'donation_id' => $payment_id,
				'currency'    => $payment_currency,
			)
		);
		// Get donation amount.
		$donation_amount = give_fee_format_amount(
			give_maybe_sanitize_amount(
				give_get_meta( $payment_id, '_give_fee_donation_amount', true ),
				array(
					'number_decimals' => $number_decimals,
					'currency'        => $payment_currency,
				)
			),
			array(
				'donation_id' => $payment_id,
				'currency'    => $payment_currency,
			)
		);

		$subscription_id = give_get_meta( $payment_id, 'subscription_id', true );

		if ( ! empty( $subscription_id ) && $this->is_recurring_active() ) {

			$subscription      = new Give_Subscription( $subscription_id );
			$parent_payment_id = $subscription->get_original_payment_id();
			$donation_amount = give_fee_format_amount(
                give_maybe_sanitize_amount(give_get_meta($payment_id, '_give_fee_donation_amount', true),
                    array(
                        'number_decimals' => $number_decimals,
                        'currency' => $payment_currency,
                    )
                ),
                array(
                    'donation_id' => $payment_id,
                    'currency' => $payment_currency,
                )
            );

			$give_fee_amount = give_fee_format_amount(
                give_maybe_sanitize_amount(give_get_meta($payment_id, '_give_fee_amount', true),
                    array(
                        'number_decimals' => $number_decimals,
                        'currency' => $payment_currency,
                    )
                ),
				array(
					'donation_id' => $payment_id,
					'currency'    => $payment_currency,
				)
			);

		} else {
            // Get Fee amount.
            $give_fee_amount = give_fee_format_amount(
                give_maybe_sanitize_amount(give_get_meta($payment_id, '_give_fee_amount', true),
                    array(
                        'number_decimals' => $number_decimals,
                        'currency' => $payment_currency,
                    )
                ),
                array(
                    'donation_id' => $payment_id,
                    'currency' => $payment_currency,
                )
            );
        }

        // Display Donation amount if total donation and donation amount not same.
        if (isset($total_donation, $donation_amount) && $donation_amount !== $total_donation && $donation_amount > 0) {
            ?>
            <p>
                <strong><?php
                    esc_html_e('Donation Amount:', 'give-fee-recovery'); ?></strong><br>
                <?php
                echo esc_html(
                    give_currency_filter(
                        $donation_amount,
                        array(
                            'currency_code' => $payment_currency,
                        )
                    )
                );

                // Display Donation fee if set.
                if (isset($give_fee_amount) && give_maybe_sanitize_amount($give_fee_amount) > 0) {
                    echo ' ' . sprintf(
                            __('+ %s for fees', 'give-fee-recovery'),
                            esc_html(
                                give_currency_filter(
                                    give_fee_format_amount(
                                        $give_fee_amount,
                                        array(
                                            'currency' => $payment_currency,
                                            'donation_id' => $payment_id,
                                        )
                                    ),
                                    array(
                                        'currency_code' => $payment_currency,
                                    )
                                )
                            )
                        );
                }
                ?>
            </p>
            <?php
        }
	}

	/**
	 * Include Fee report on admin settings.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $settings Report Settings tab names.
	 *
	 * @return array Settings.
	 */
	public function report_page( $settings ) {
		// Fee.
		$settings[] = include GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/admin/class-fee-reports.php';

		// Output.
		return $settings;
	}

	/**
	 * Checks if the Recurring Addon is active or not.
	 *
	 * @since 1.7
	 *
	 * @return boolean
	 */
	public function is_recurring_active() {
		return defined( 'GIVE_RECURRING_VERSION' );
	}

	/**
	 * Add new Fee Column.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $columns Retrieve all columns.
	 *
	 * @return array
	 */
	public function columns( $columns ) {
		// Unsent Default Detail column and re-arrange it.
		unset( $columns['details'] );

		$columns['givefeerecovery'] = __( 'Fee', 'give-fee-recovery' );
		$columns['details']         = __( 'Details', 'give-fee-recovery' );

		return $columns;
	}

	/**
	 * Show Recovery Fee.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $value       Column Value.
	 * @param int    $payment_id  Payment ID.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	public function column_data( $value, $payment_id, $column_name ) {

		$subscription_id = give_get_meta( $payment_id, 'subscription_id', true );

		if ( ! empty( $subscription_id ) && $this->is_recurring_active() ) {
			$subscription_id = give_get_meta( $payment_id, 'subscription_id', true );
			$subscription    = new Give_Subscription( $subscription_id );
			$payment_id      = $subscription->get_original_payment_id();
		}

		if ( 'givefeerecovery' === $column_name ) {
			$fee             = give_get_meta( $payment_id, '_give_fee_amount', true );
			$donation_status = give_get_meta( $payment_id, '_give_fee_status', true );
			$donation_status = ! empty( $donation_status ) ? $donation_status : 'disabled';
			$currency        = give_get_payment_currency_code( $payment_id );
			$number_decimals =  give_get_price_decimals( $currency );

			// Show Fee, if set.
			if ( ! empty( $fee ) ) {
				$fee = give_maybe_sanitize_amount( $fee,
					array(
						'number_decimals' => $number_decimals,
						'currency'        => $currency,
					)
				);

				$value = esc_html( give_currency_filter(
						give_fee_format_amount(
							$fee,
							array(
								'donation_id' => $payment_id,
								'currency'    => $currency,
							)
						),
						array(
							'currency_code' => $currency,
						)
					)
				);

			} else {
				$value = ucfirst( $donation_status );
			}
		}// End if().

		return $value;
	}

	/**
	 * Sortable Fee column.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $columns Report Column array.
	 *
	 * @return array
	 */
	public function sortable_column( $columns ) {
		$columns['givefeerecovery'] = array( 'givefeerecovery', true );

		return $columns;
	}

	/**
	 * Sort by give fee amount.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param object $payment_data_query donation payment data.
	 */
	public function pre_get_payments( $payment_data_query ) {

		// If Order by Fee is set then, apply filter based on it.
		if ( isset( $payment_data_query->args['orderby'] ) && 'givefeerecovery' === $payment_data_query->args['orderby'] ) {

			// Set Meta query.
			$payment_data_query->args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_give_fee_amount',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_give_fee_amount',
					'compare' => 'EXISTS',
				),
			);
			$payment_data_query->args['orderby']    = 'meta_value_num';
		}// End if().
	}

	/**
	 * Override Give Email tags
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public function email_tags() {

		// Remove amount email tag.
		give_remove_email_tag( 'amount' );

		// Add amount email tag with custom function.
		give_add_email_tag(
			'amount', __( 'The total donation amount with currency sign.', 'give-fee-recovery' ), array(
				$this,
				'give_fee_email_tag_amount',
			)
		);
	}

	/**
	 * Add Custom call back function for {amount} tag.
	 *
	 * @since  1.1.0
	 *
	 * @param int|array $tag_args give payment id.
	 *
	 * @return string
	 */
	function give_fee_email_tag_amount( $tag_args ) {

		// Backward compatibility.
		$payment_id = $tag_args;
		if ( isset( $tag_args ) && is_array( $tag_args ) ) {
			$payment_id = $tag_args['payment_id'];
		}

		// Get Fee Recovery amount string.
		$give_amount = $this->fee_amount_string( $payment_id );

		return html_entity_decode( $give_amount, ENT_COMPAT, 'UTF-8' );

	}

	/**
	 * Get Fee Recovery amount string.
	 *
     * @param int $donation_id Donation ID.
     *
	 * @since 1.3.7
	 *
	 * @return string
	 */
	function fee_amount_string( $donation_id ) {

		// Define required variables.
		$currency_code   = give_get_payment_currency_code( $donation_id );
		$donation_total  = give_donation_amount( $donation_id );
		$fee_amount      = give_get_meta( $donation_id, '_give_fee_amount', true );
		$donation_amount = give_get_meta( $donation_id, '_give_fee_donation_amount', true );

		// Default Donation Amount Output.
		$final_donation_amount = give_currency_filter(
			give_fee_format_amount( $donation_total,
				array(
					'donation_id' => $donation_id,
					'currency'    => $currency_code,
				)
			),
			array(
				'currency_code' => $currency_code,
			)
		);

		// Check if fees amount is not empty.
		if ( ! empty( $fee_amount ) ) {

			$donation_amount =
				give_currency_filter(
					give_fee_format_amount(
                        $donation_amount,
						array(
							'donation_id' => $donation_id,
							'currency'    => $currency_code,
						)
					), array(
						'currency_code' => $currency_code,
					)
				);

			// Format fees amount.
			$fee_amount =
				give_currency_filter(
					give_fee_format_amount(
                        $fee_amount,
						array(
							'donation_id' => $donation_id,
							'currency'    => $currency_code,
						)
					), array(
						'currency_code' => $currency_code,
					)
				);

			// Update final donation amount with fees breakdown.
			$final_donation_amount = sprintf(
                __( '%1$s (%2$s donation + %3$s for fees)', 'give-fee-recovery' ),
				$final_donation_amount,
                $donation_amount,
                $fee_amount
            );
		}

		// Output final donation amount information with fees breakdown, if fees added.
		return $final_donation_amount;
	}


	/**
	 * Modify {amount} tag for the Preview Purpose in Give 2.0
	 *
	 * @since  1.1.0
	 * @update 1.3.7
	 *
	 * @access public
	 *
	 * @param string $amount
	 * @param array  $tag_args
	 *
	 * @return string
	 */
	public function preview_amount_tag( $amount, $tag_args ) {

		if ( isset( $tag_args['payment_id'] ) && ! empty( $tag_args['payment_id'] ) ) {
			$payment_id = $tag_args['payment_id'];

			$give_amount = $this->fee_amount_string( $payment_id );

			$amount = html_entity_decode( $give_amount, ENT_COMPAT, 'UTF-8' );
		}

		/**
		 * Filter the {amount} email template tag output.
		 *
		 * @since 1.3.6
		 *
		 * @param string $amount
		 * @param array  $tag_args
		 */
		$amount = apply_filters( 'give_fee_recovery_email_tag_amount', $amount, $tag_args );

		return $amount;

	}

	/**
	 * Check validation before process update donation.
	 *
	 * @since  1.1.0
	 * @access private
	 *
	 * @param integer $payment_id Payment ID.
	 *
	 * @return void
	 */
	function before_update_donation( $payment_id ) {
		$payment_total   = give_get_meta( $payment_id, '_give_payment_total', true );
		$new_total       = ! empty( $_POST['give-payment-total'] ) ? give_sanitize_amount( $_POST['give-payment-total'] ) : $payment_total;
		$give_fee_amount = ! empty( $_POST['give-payment-fee-amount'] ) ? give_sanitize_amount( $_POST['give-payment-fee-amount'] ) : 0;

		// Die, if new total is less than to fee recovery amount.
		if ( ! empty( $new_total ) && $new_total < $give_fee_amount && ! empty( $give_fee_amount ) ) {

			$current_page_url = $_SERVER['REQUEST_URI'];

			wp_die(
				sprintf(
					'%1$s <a href="%2$s">%3$s</a>',
					__( 'Give total donation should be greater than the Give fee amount.', 'give-fee-recovery' ),
					$current_page_url,
					__( 'Go back', 'give-fee-recovery' )
				),
				__( 'Error', 'give-fee-recovery' ),
				array(
					'response' => 400,
				)
			);
		}
	}

	/**
	 * Process the payment details edit.
	 *
	 * @since  1.1.0
	 * @access private
	 *
	 * @param integer $payment_id Payment ID.
	 *
	 * @return      void
	 */
	function after_updated_edited_donation( $payment_id ) {
		$payment_total   = give_get_meta( $payment_id, '_give_payment_total', true );
		$new_total       = ! empty( $_POST['give-payment-total'] ) ? give_fee_number_format( $_POST['give-payment-total'], true, $payment_id ) : $payment_total;
		$new_total       = is_numeric( $new_total ) ? $new_total : 0;
		$give_fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );
		$give_fee_amount = ! empty( $give_fee_amount ) ? give_fee_number_format( $give_fee_amount, true, $payment_id ) : 0;

		// Get Give payment Fee amount.
		$new_fee_amount = ! empty( $_POST['give-payment-fee-amount'] ) ? give_fee_number_format( $_POST['give-payment-fee-amount'], true, $payment_id ) : 0;

		// Disable Fee status.
		// Set 0 for donation amount and Fee amount if Admin update total donation to 0.
		if ( empty( $new_total ) ) {

			// Update give fee status to disabled when fee amount is 0.
			give_update_payment_meta( $payment_id, '_give_fee_status', 'disabled' );
			give_update_payment_meta( $payment_id, '_give_fee_donation_amount', 0 );
			give_update_payment_meta( $payment_id, '_give_fee_amount', 0 );
		}// End if().

		// Check if upgrade complete then Fee form earnings increase/decrease work.
		if ( give_has_upgrade_completed( 'give_fee_recovery_v151_form_fee_earnings' ) ) {
			// Increase/Decrease Form fee earnings.
			if ( $new_fee_amount > $give_fee_amount ) {
				// Get diff fee amount.
				$diff = $new_fee_amount - $give_fee_amount;

				// Increase Form Fee earnings.
				give_fee_increase_form_fee_amount( $payment_id, $diff );

			} else {
				// Get diff fee amount.
				$diff = $give_fee_amount - $new_fee_amount;

				// Decrease Form Fee earnings.
				give_fee_decrease_form_fee_amount( $payment_id, $diff );
			}
		}// End if().

		if ( ! empty( $new_fee_amount ) && ( $give_fee_amount !== $new_fee_amount ) ) {

			// Update new total donation in payment meta.
			give_update_payment_meta( $payment_id, '_give_fee_amount', $new_fee_amount );
			give_update_payment_meta( $payment_id, '_give_fee_status', 'accepted' );

		} else {

			if ( ! empty( $new_total ) && empty( $new_fee_amount ) ) {
				$new_total = $new_total - $give_fee_amount;

				$give_fee_status = give_get_payment_meta( $payment_id, '_give_fee_status', true );

				if ( 'rejected' === $give_fee_status && ! empty( $give_fee_status ) ) {
					$give_fee_status = 'rejected';
				} else {
					$give_fee_status = 'disabled';
				}

				// Update give fee status to disabled when fee amount is 0.
				give_update_payment_meta( $payment_id, '_give_fee_status', $give_fee_status );

				// Update new total donation in payment meta.
				give_update_payment_meta( $payment_id, '_give_payment_total', $new_total );
				give_update_payment_meta( $payment_id, '_give_fee_amount', $new_fee_amount );
			}// End if().
		}// End if().

		$give_fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );
		$give_fee_amount = ! empty( $give_fee_amount ) ? give_sanitize_amount( $give_fee_amount ) : 0;
		$new_total       = give_get_meta( $payment_id, '_give_payment_total', true );
		$new_total       = is_numeric( $new_total ) ? $new_total : 0;

		// Calculate Donation total.
		// Get new Donation total by less fee amount from the new total amount.
		$donation_amount = $new_total - $give_fee_amount;

		if ( empty( $new_fee_amount ) ) {
			$donation_amount = 0;
		}// End if().

		// Update new donation amount in payment meta.
		give_update_payment_meta( $payment_id, '_give_fee_donation_amount', $donation_amount );
	}

	/**
	 * Grabs all of the selected date info and then redirects appropriately.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param array $data Parameters from the Settings page.
	 */
	public function parse_report_dates( $data ) {

		$dates = give_get_report_dates();

		$view = give_get_reporting_view();
		$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'earnings';
		$id   = isset( $_GET['form-id'] ) ? absint( $_GET['form-id'] ) : null;

		// Get Current setting section.
		$section          = give_get_current_setting_section();
		$give_fee_section = isset( $section ) ? sanitize_text_field( $section ) : '';

		$section_param = '';
		if ( ! empty( $give_fee_section ) ) {
			$section_param = '&section=' . esc_attr( $give_fee_section );
		}

		wp_redirect( add_query_arg( $dates, admin_url( 'edit.php?post_type=give_forms&page=give-reports&legacy=true&tab=' . esc_attr( $tab ) . $section_param . '&view=' . esc_attr( $view ) . '&form-id=' . absint( $id ) ) ) );
		give_die();
	}

	/**
	 * Fires in order details page, after the sidebar update-donation metabox.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param int $payment_id Payment id.
	 */
	public function show_fee_order_detail( $payment_id ) {
		$payment         = new Give_Payment( $payment_id );
		$subscription_id = give_get_meta( $payment_id, 'subscription_id', true );

		if ( ! empty( $subscription_id ) && $this->is_recurring_active() ) {
			$subscription_id   = give_get_meta( $payment_id, 'subscription_id', true );
			$subscription      = new Give_Subscription( $subscription_id );
			$parent_payment_id = $subscription->get_original_payment_id();
			$give_fee_amount   = give_fee_format_amount(
				give_get_meta( $parent_payment_id, '_give_fee_amount', true ),
				array(
					'donation_id' => $payment_id,
				)
			);
		} else {
			$give_fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );
		}

		$give_fee_amount = ! empty( $give_fee_amount ) ? give_fee_number_format( $give_fee_amount, true, $payment_id ) : 0;
		?>
		<div class="give-order-payment give-admin-box-inside">
			<p>
				<label for="give-payment-fee-amount" class="strong"><?php esc_html_e( 'Fee Amount:', 'give-fee-recovery' ); ?></label>&nbsp;
				<?php echo give_currency_symbol( $payment->currency ); ?>
				<input
						id="give-payment-fee-amount"
						placeholder="0.00"
						name="give-payment-fee-amount"
						type="text"
						class="small-text give-price-field"
						value="<?php echo esc_attr( give_format_decimal( $give_fee_amount ) ); ?>"
				/>
			</p>
		</div>
		<?php
	}

	/**
	 * Update Fee recovery amount and Fee Percentage before save.
	 *
	 * @param string $value  Option value.
	 * @param array  $option Settings Option array.
     *
     * @since 1.8.0 add support for max fee coverage.
	 *
	 * @return string $value
	 */
	public function pre_save_global_value( $value, $option ) {
		if ( false !== strpos( $option['id'], 'give_fee_percentage' ) ) {
			$value = ( '' !== $value ) ? give_fee_number_format( $value ) : '2.90';
			$value = give_sanitize_amount_for_db( $value );
			give_update_option( $option['id'], $value );
		} elseif ( false !== strpos( $option['id'], 'give_fee_base_amount' ) ) {
			$value = ( '' !== $value ) ? give_fee_number_format( $value ) : '0.30';
			$value = give_sanitize_amount_for_db( $value );
			give_update_option( $option['id'], $value );
		} elseif ( false !== strpos( $option['id'], 'give_fee_maximum_fee_amount' ) ) {
			$value = ( '' !== $value ) ? give_fee_number_format( $value ) : '0.00';
			$value = give_sanitize_amount_for_db( $value );
			give_update_option( $option['id'], $value );
		}

		return $value;
	}

	/**
	 * Decrease Form fee earnings on Payment Delete.
	 *
	 * @access public
	 * @since  1.5.1
	 *
	 * @param int $payment_id
	 */
	public function decrease_form_fee_earnings_on_delete( $payment_id ) {

		$fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );
		$fee_amount = ! empty( $fee_amount ) ? $fee_amount : 0;

		// Decrease Form Fee earnings.
		give_fee_decrease_form_fee_amount( $payment_id, $fee_amount );
	}

	/**
	 * Add new options to recalculate Form fee earnings.
	 *
	 * @access public
	 * @since  1.5.1
	 *
	 * @param void
	 */
	public function add_fee_earnings_recount_options() {
		// Bailout if Fee recovery earnings upgrade not complete.
		if ( ! give_has_upgrade_completed( 'give_fee_recovery_v151_form_fee_earnings' ) ) {
			return;
		}
		?>
		<option data-type="recount-form"
		        value="Give_Tools_Recount_Form_Fee_Earnings"><?php esc_html_e( 'Recalculate Form Fee Earnings for a Form', 'give-fee-recovery' ); ?></option>
		<option data-type="recount-all-fee-earnings"
		        value="Give_Tools_Recount_All_Form_Fee_Earnings"><?php esc_html_e( 'Recalculate Form Fee Earnings for All Forms', 'give-fee-recovery' ); ?></option>
		<?php
	}

	/**
	 * Add description for the new tool options.
	 *
	 * @access public
	 * @since  1.5.1
	 */
	public function give_fee_tool_descriptions() {
		?>
		<span id="recount-form-fee-earnings"><?php esc_html_e( 'Recalculate the form fee earnings for a specific form.', 'give-fee-recovery' ); ?></span>
		<span id="recount-all-fee-earnings"><?php esc_html_e( 'Recalculate the form fee earnings for the all forms.', 'give-fee-recovery' ); ?></span>
		<?php
	}

	/**
	 * Loads the tools batch processing classes.
	 *
	 * @since  1.5.1
	 *
	 * @param  string $class The class being requested to run for the batch export.
	 *
	 * @return void
	 */
	public function give_fee_include_batch_export_class( $class ) {
		switch ( $class ) {

			case 'Give_Tools_Recount_Form_Fee_Earnings':
				require_once GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/admin/tools/data/class-give-tools-recount-form-fee-earnings.php';
				break;

			case 'Give_Tools_Recount_All_Form_Fee_Earnings':
				require_once GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/admin/tools/data/class-give-tools-recount-all-form-fee-earnings.php';
				break;
		}
	}

	/**
	 * Reset Form fee earnings on cloning form.
	 *
	 * @param array $meta_keys
	 *
	 * @since 1.7.1
	 *
	 * @return array $meta_keys
	 */
	public function reset_form_fee_earnings( $meta_keys ) {
		$meta_keys[] = '_give_form_fee_earnings';

		return $meta_keys;
	}


	/**
	 * Exclude Fee from renewal amount and get new price_id.
	 *
	 * @since 1.7.1
	 *
	 * @param integer       $price_id Price ID.
	 * @param float         $amount   Renewal amount
	 * @param \Give_Payment $payment  Renewal Payment
	 *
	 * @return integer $price_id
	 */
	public function exclude_fee_from_renewal_amount( $price_id, $amount, $payment ) {

		$payment_id = $payment->ID;
		$form_id    = $payment->form_id;

		// Get Fee amount.
		$fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );
		$fee_amount = ! empty( $fee_amount ) ? $fee_amount : 0;

		// Exclude Fee amount from renewal amount and get new price_id.
		if ( ! empty( $fee_amount ) ) {
			$amount   -= $fee_amount;
			$price_id = give_get_price_id( $form_id, $amount );
		}

		return $price_id;
	}
}
