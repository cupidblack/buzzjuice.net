<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 *
 * @package    Give_Fee_Recovery
 * @subpackage Give_Fee_Recovery/public
 */

use Give\Receipt\DonationReceipt;
use GiveFeeRecovery\Receipt\UpdateDonationReceipt;
use GiveFeeRecovery\Repositories\Settings;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Give_Fee_Recovery
 * @subpackage Give_Fee_Recovery/public
 * @author     GiveWP <https://givewp.com>
 */
class Give_Fee_Recovery_Public {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) ); // Enqueue Script for Public.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) ); // Enqueue Styles for Public.
		add_action( 'give_pre_form_output', array( $this, 'pre_form_output' ), 0, 1 ); // Display Give Fee label.
		add_action( 'give_insert_payment', array( $this, 'insert_payment' ), 10, 2 ); // Add Fee on meta at the time of Donation payment.
		add_action( 'give_donation_receipt_args', array( $this, 'payment_receipt' ), 10, 3 ); // Add actual Donation total and Recovery Fee on Payment receipt.
		add_action( 'give_new_receipt', array( $this, 'addReceiptItems' ), 10, 2 ); // Add actual Donation total and Recovery Fee on Payment receipt.
		add_filter( 'give_goal_amount_funded_percentage_output', array( $this, 'percentage_output' ), 10, 2 ); // Update Goal Progress for Goal Form.
		add_filter( 'give_goal_amount_raised_output', array( $this, 'raised_output' ), 10, 2 ); // Update Income total with subtract Give Fee.
		add_filter( 'shortcode_atts_give_totals', array( $this, 'give_totals_add_attribute' ), 10, 3 ); // Subtract Give Fee from total earning for give_totals shortcode output.
		add_filter( 'give_totals_form_earning', array( $this, 'give_totals' ), 10, 3 ); // Subtract Give Fee from total earning for give_totals shortcode output.
		add_action( 'wp_ajax_give_load_gateway', array( $this, 'gateway_callback' ), 0 );// Give load gateway callback hook.
		add_action( 'wp_ajax_nopriv_give_load_gateway', array( $this, 'gateway_callback' ), 0 );
		add_action( 'give_hidden_fields_before', array( $this, 'show_fee_breakdown' ), 10, 1 );// Show Fee break down before final total.
		add_filter( 'give_donation_form_top', array( $this, 'hidden_field_data' ), 10, 2 );
		add_action( 'give_complete_donation', array( $this, 'store_fee_earnings' ), 10, 1 ); // Save Fee earnings into Donation form meta.
		add_action( 'give_recurring_add_subscription_payment', array( $this, 'store_renewal_donation_fee_earnings' ), 10, 2 );
		add_filter( 'give_donation_amount', array( $this, 'update_donation_amount' ), 10, 4 );
		add_filter( 'give_recurring_subscription_pre_gateway_args', [ $this, 'update_subscription_args' ], 10 );
	}

	/**
	 * Display Fee mode label on front end side below Donation amount.
	 *
	 * @since  1.0.0
	 * @since 1.9.4 Add unique id prefix to each html element to prevent issue when donation form display more than once on a page.
	 * @access public
	 *
	 * @param int $form_id The form ID.
	 * @param float $fee Fee Amount.
	 *
	 * @return mixed | void
	 */
	public function fee_output( $form_id, $fee = 0.00 ) {
		static $count = 1;
		$id_prefix = "$form_id-$count";
		$count ++;

		// This will remove the previous hook action and execute new hook action
		// based on selection from the Backend Checkbox location.
		remove_action( current_action(), array(
			$this,
			'fee_output',
		), 0 );

		// Get the value of fee recovery enable or not.
		$is_fee_recovery = give_get_meta( $form_id, '_form_give_fee_recovery', true );
		$is_fee_recovery = ! empty( $is_fee_recovery ) ? $is_fee_recovery : 'global';

		// Get the Form display type.
		$is_display_style = give_get_meta( $form_id, '_give_payment_display', true );
		$is_fee_disable   = 1;

		// If display type is button then set value as 0.
		if ( 'button' === $is_display_style ) {
			$fee_mode_value = 0;
		} else {
			$fee_mode_value = 1;
		}// End if().

        $is_fee_opted = $fee > 0 ? 'checked="checked"' : '';

		ob_start();

		// Bailout if per form and global fee recovery is not setup.
		if ( give_is_setting_enabled( $is_fee_recovery, 'global' ) && give_is_setting_enabled( give_get_option( 'give_fee_recovery' ) ) ) { ?>
			<input type="hidden" value="<?php echo esc_attr( $is_fee_disable ); ?>" class="give-fee-disable"/>
			<?php
			// Check Fee mode. Is it Donor's Choice or Fee Coverage required.
			if ( give_is_setting_enabled( give_get_option( 'give_fee_mode' ), 'donor_opt_in' ) ) {

				// Checkbox label with Customer option.
				$checkbox_label = give_get_option( 'give_fee_checkbox_label' );
				$fee_message    = str_replace( '{fee_amount}', $fee, $checkbox_label );
				?>

                <fieldset class="give-fee-recovery-donors-choice give-fee-message form-row"
                          id="give-fee-recovery-wrap-<?php echo $id_prefix; ?>">
                    <legend class="give-fee-message-legend"
                            style="display: none;"><?php esc_html_e( 'Would you like to help cover the processing fees?',
							'give-fee-recovery' ); ?></legend>

                    <label
                            for="give_fee_mode_checkbox-<?php echo $id_prefix; ?>"
                            class="give-fee-message-label"
                            data-feemessage="<?php echo esc_attr( $checkbox_label ); ?>"
                            style="font-weight:normal; cursor: pointer;"
                    >
                        <input
                                name="give_fee_mode_checkbox"
                                type="checkbox"
                                id="give_fee_mode_checkbox-<?php echo $id_prefix; ?>"
                                class="give_fee_mode_checkbox"
                                value="<?php echo $fee_mode_value; ?>"
							<?php echo $is_fee_opted; ?>
                        />
                        <span class="give-fee-message-label-text"><?php echo esc_attr( $fee_message ); ?></span>
                    </label>
                </fieldset>
				<?php

			} else {
				// Get Fee Explanation field value.
				$checkbox_label = give_get_option( 'give_fee_explanation' );
				$fee_message    = str_replace( '{fee_amount}', ' ', $checkbox_label );
				?>
				<div class="give-fee-total-wrap fee-coverage-required give-fee-message form-row"
					 id="give-fee-recovery-wrap-<?php echo intval( $form_id ); ?>">
					<div class="give-fee-message-label" data-feemessage="<?php echo esc_attr( $checkbox_label ); ?>"><span class="give-fee-message-label-text"><?php echo
							esc_html( $fee_message ); ?></span></div>
				</div>
				<?php
			}// End if().
		} elseif ( give_is_setting_enabled( $is_fee_recovery ) ) {
			?>
			<input
					type="hidden"
					value="<?php echo esc_attr( $is_fee_disable ); ?>"
					class="give-fee-disable"
			/>
			<?php
			// Per-Form condition.
			$per_form_mode = give_get_meta( $form_id, '_form_give_fee_mode', true );

			// Check Fee mode. Is it Donor Opt-in or Forced Opt-in?
			if ( give_is_setting_enabled( $per_form_mode, 'donor_opt_in' ) ) {

				// Get Opt-in Message.
				$checkbox_label = give_get_meta( $form_id, '_form_give_fee_checkbox_label', true );
				$fee_message    = str_replace( '{fee_amount}', $fee, $checkbox_label );
				?>
				<fieldset class="give-fee-recovery-donors-choice give-fee-message form-row"
						  id="give-fee-recovery-wrap-<?php echo intval( $form_id ); ?>">
					<legend class="give-fee-message-legend"
							style="display: none;"><?php esc_html_e( 'Would you like to help cover the processing fees?',
							'give-fee-recovery' ); ?></legend>

					<label for="give_fee_mode_checkbox-<?php echo intval( $form_id ); ?>"
						   class="give-fee-message-label"
						   data-feemessage="<?php echo esc_attr( $checkbox_label ); ?>"
						   style="font-weight:normal; cursor: pointer;">
						<input
								name="give_fee_mode_checkbox"
								type="checkbox"
								id="give_fee_mode_checkbox-<?php echo intval( $form_id ); ?>"
								class="give_fee_mode_checkbox"
								value="<?php echo intval( $fee_mode_value ); ?>"
							<?php echo $is_fee_opted; ?>
						/>
						<span class="give-fee-message-label-text"><?php echo esc_html( $fee_message ); ?></span>
					</label>
				</fieldset>
				<?php

			} else {
				// Get Opt-in Message.
				$checkbox_label = give_get_meta( $form_id, '_form_give_fee_explanation', true );
				$fee_message    = str_replace( '{fee_amount}', ' ', $checkbox_label );
				?>
				<div class="give-fee-total-wrap fee-coverage-required give-fee-message form-row"
					 id="give-fee-recovery-wrap-<?php echo intval( $form_id ); ?>">
					<div class="give-fee-message-label" data-feemessage="<?php echo esc_attr( $checkbox_label ); ?>"><span
								class="give-fee-message-label-text"><?php echo esc_html( $fee_message ); ?></span></div>
				</div>
				<?php
			}// End if().
		}// End if().

		$message_html = ob_get_clean();

		/**
		 *  Update Fee Recovery message through this Filter.
		 */
		echo $message_html = apply_filters( 'give_fee_recovery_message', $message_html, $form_id );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
     * @since 1.9.5 removed SCRIPT_DEBUG support as base file cannot be used directly
     * @since 1.9.2 Remove give_fee_recovery_site_url WP localized param
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Registering the recovery plugin JS script.
		wp_register_script( GIVE_FEE_RECOVERY_SLUG, GIVE_FEE_RECOVERY_PLUGIN_URL . 'assets/js/give-fee-recovery-public.min.js', array( 'give' ), GIVE_FEE_RECOVERY_VERSION, false );
		wp_enqueue_script( GIVE_FEE_RECOVERY_SLUG );

		wp_localize_script(GIVE_FEE_RECOVERY_SLUG, 'give_fee_recovery_object', array(
            'give_fee_zero_based_currency' => wp_json_encode(array_keys(give_fee_zero_based_currency_code())),
        ));
	}


	/**
	 * Register the Style for the public-facing side of the site.
	 *
     * @since 1.9.5 removed SCRIPT_DEBUG support as base file cannot be used directly
	 * @since    1.7
	 */
	public function enqueue_styles() {
		// Enqueuing give fee recovery frontend side css.
		wp_register_style( GIVE_FEE_RECOVERY_SLUG, GIVE_FEE_RECOVERY_PLUGIN_URL . 'assets/css/give-fee-recovery-frontend.min.css', array(), GIVE_FEE_RECOVERY_VERSION, 'all' );
		wp_enqueue_style( GIVE_FEE_RECOVERY_SLUG );
	}

	/**
	 * Add calculated fee and update total.
	 *
	 * @since  1.0.0
	 * @deprecated Using service AddFeeToDonationAmount
	 * @access public
	 *
	 * @param double $give_total Donation total amount.
	 *
	 * @return mixed
	 */
	public function add_fee( $give_total ) {
		$form_id         = isset( $_POST['give-form-id'] ) ? absint( $_POST['give-form-id'] ) : absint( $_POST['give_form_id'] );
		$chosen_gateway  = isset( $_POST['payment-mode'] ) ? give_clean( $_POST['payment-mode'] ) : give_clean( $_POST['give_payment_mode'] );
		$form_currency   = give_get_currency( $form_id );
		$number_decimals = give_get_price_decimals( $form_currency );

		// Get correct number of decimal based on currency is zero based or not.
		if ( 1 >= $number_decimals && ! give_is_zero_based_currency( $form_currency ) ) {
			$decimals = 2;
		} else {
			$decimals = $number_decimals;
		}

		if ( give_is_zero_based_currency( $form_currency ) ) {
			$decimals = 0;
		}

		$give_total       = give_sanitize_amount( $give_total, array( 'number_decimals' => $decimals, 'currency' => $form_currency ) );
		$percentage       = 0;
		$base_amount      = 0;
		$give_fee_disable = false; // Set Fee Recovery enable.
		$give_fee_status  = true; // Set Fee recovery disable as Gateway enable.

		// Fee Recovery enable/disable Per-Form.
		$is_fee_recovery = give_get_meta( $form_id, '_form_give_fee_recovery', true );
		$is_fee_recovery = ! empty( $is_fee_recovery ) ? $is_fee_recovery : 'global';

		// Check if Give Fee Recovery option is Global or Per-Form.
		if ( give_is_setting_enabled( $is_fee_recovery, 'global' )
		     && give_is_setting_enabled( give_get_option( 'give_fee_recovery' ) )
		) {

			$fee_mode    = give_get_option( 'give_fee_mode' );
			$is_fee_mode = isset( $_POST['give-fee-mode-enable'] ) ? filter_var( $_POST['give-fee-mode-enable'], FILTER_VALIDATE_BOOLEAN ) : false;

			// Check if Fee Configuration option set as All Gateway.
			if ( give_is_setting_enabled( give_get_option( 'give_fee_configuration' ), 'all_gateways' ) ) {

				// Get Global Fee Percentage because Gateway set is All Gateway.
				$percentage = give_get_option( 'give_fee_percentage', 2.90 );

				// Get Global Fee base amount because Gateway set is All Gateway.
				$base_amount = give_get_option( 'give_fee_base_amount', 0.30 );

			} else {
				// Get Fee Percentage and base amount based on each Gateway.
				if ( give_is_setting_enabled( give_get_option( "give_fee_gateway_fee_enable_option_{$chosen_gateway}" ) ) ) {

					// Get Fee Percentage for selected Gateway.
					$percentage = give_get_option( "give_fee_gateway_fee_percentage_{$chosen_gateway}", 2.90 );

					// Get Fee Base amount for selected Gateway.
					$base_amount = give_get_option( "give_fee_gateway_fee_base_amount_{$chosen_gateway}", 0.30 );

				} else {
					$give_fee_disable = true; // Set Fee Recovery disable.
					$give_fee_status  = false; // Set Fee recovery disable as Gateway disable.
				}
			}

			// Add Fee with Give total.
			if ( $give_fee_status ) {
				$give_fee = give_fee_calculate( $percentage, $base_amount, $give_total, $give_fee_disable );
				$give_fee = (float) give_sanitize_amount( $give_fee,
					array(
						'number_decimals' => $decimals,
						'currency'        => $form_currency,
					)
				);

				// Check if Fee Mode is Donor's Choice or Fee Coverage required.
				if ( ! give_is_setting_enabled( $fee_mode, 'donor_opt_in' ) || $is_fee_mode ) {
					$give_total += $give_fee;
				}// End if().
			}

		} elseif ( give_is_setting_enabled( $is_fee_recovery ) ) {

			// Get Per-Form Fee Mode.
			$per_form_gateway = give_get_meta( $form_id, '_form_give_fee_configuration', true );
			$fee_mode         = give_get_meta( $form_id, '_form_give_fee_mode', true );
			$is_fee_mode      = isset( $_POST['give-fee-mode-enable'] ) ? filter_var( $_POST['give-fee-mode-enable'], FILTER_VALIDATE_BOOLEAN ) : false;

			// Check if Fee Configuration option set as All Gateway.
			if ( give_is_setting_enabled( $per_form_gateway, 'all_gateways' ) ) {

				// Get Global Fee Percentage because Gateway set is All Gateway.
				$percentage = give_get_meta( $form_id, '_form_give_fee_percentage', true );
				$percentage = ( false !== $percentage ) ? $percentage : 2.90;

				// Get Global Fee base amount because Gateway set is All Gateway.
				$base_amount = give_get_meta( $form_id, '_form_give_fee_base_amount', true );
				$base_amount = ( false !== $base_amount ) ? $base_amount : 0.30;

			} else {
				// Code for Set per Gateway.
				// Get value for gateway Fee enable option.
				$per_gateway_key = give_get_meta( $form_id, "_form_gateway_fee_enable_{$chosen_gateway}", true );

				// Check if chosen Gateway is enabled.
				if ( give_is_setting_enabled( $per_gateway_key, 'enabled' ) ) {

					// Get Fee Percentage for selected Gateway.
					$percentage = give_get_meta( $form_id, "_form_gateway_fee_percentage_{$chosen_gateway}", true );
					$percentage = ( false !== $percentage ) ? $percentage : 2.90;

					// Get Fee Base amount for selected Gateway.
					$base_amount = give_get_meta( $form_id, "_form_gateway_fee_base_amount_{$chosen_gateway}", true );
					$base_amount = ( false !== $base_amount ) ? $base_amount : 0.30;

				} else {
					$give_fee_disable = true;
					$give_fee_status  = false; // Set Give status.
				}// End if().
			}// End if().

			// Check if Fee Mode is Donor's Choice or Fee Coverage required.
			if ( $give_fee_status ) {
				$give_fee = give_fee_calculate( $percentage, $base_amount, $give_total, $give_fee_disable );
				$give_fee = (float) give_sanitize_amount( $give_fee,
					array(
						'number_decimals' => $decimals,
						'currency'        => $form_currency,
					)
				);

				// Check if Fee Mode is Donor's Choice or Fee Coverage required.
                if (!give_is_setting_enabled($fee_mode, 'donor_opt_in') || $is_fee_mode) {
                    $give_total += $give_fee;
                }// End if().
            }
        }// End if().
        // Return new donation total.
        return $give_total;
    }

    /**
     * Store fee amount of total donation into post meta.
     *
     * @unreleased remove renewal conditional because it will never get called since there is a separate action for that
     * @since  1.0.0
     * @access public
     *
     * @param  int  $payment_id  Newly created payment ID.
     */
    public function insert_payment($payment_id)
    {
        $fee_mode_enabled = isset($_POST['give-fee-mode-enable']) ? filter_var(
            $_POST['give-fee-mode-enable'],
            FILTER_VALIDATE_BOOLEAN
        ) : false;
        $give_fee_status = !empty($_POST['give-fee-status']) ? $_POST['give-fee-status'] : 'disabled';
        $fee_status = '';

        // Set Give Fee donation status based on enabled/disabled.
        if ('enabled' === $give_fee_status && true === $fee_mode_enabled) {
            $fee_status = 'accepted';
        } elseif ('enabled' === $give_fee_status && false === $fee_mode_enabled) {
            $fee_status = 'rejected';
        } elseif ('disabled' === $give_fee_status) {
            $fee_status = 'disabled';
        }

        // If donor did not opt-on only store the status.
        if (!$fee_mode_enabled) {
            // Update Give Fee Status.
            give_update_payment_meta($payment_id, '_give_fee_status', $fee_status);

            return;
        }

        // Get payment data by payment ID.
        $payment_data = new Give_Payment($payment_id);
        $total_donation = $payment_data->total;

        // Get Fee amount.
        $fee_amount = isset($_POST['give-fee-amount']) ? give_sanitize_amount_for_db(
            give_clean($_POST['give-fee-amount'])
        ) : 0;

        // Get actual donation amount.
        $donation_amount = $total_donation - $fee_amount;
        $donation_amount = give_sanitize_amount_for_db($donation_amount);

        // Store Donation amount.
        give_update_payment_meta($payment_id, '_give_fee_donation_amount', $donation_amount);

        // Store total fee amount.
        give_update_payment_meta($payment_id, '_give_fee_amount', $fee_amount);
    }

    /**
     *  Save Fee Recovery data into the Payment meta for Renewal donation.
     *
     * @unreleased use renewal donation amount not the subscription parent donation amount
     * @since  1.3.0
     * @access public
     *
     * @param  int  $payment_id  Renewal Donation ID.
     * @param  int  $parent_id  Parent Donation ID.
     * @param  float  $donation_amount  Donation amount.
     */
    public function insert_data_for_renewal_donation($payment_id, $parent_id, $donation_amount)
    {
        $fee_amount = give_get_meta($parent_id, '_give_fee_amount', true);
        $fee_amount = !empty($fee_amount) ? $fee_amount : 0;

        $fee_status = give_get_meta($parent_id, '_give_fee_status', true);
        $fee_status = !empty($fee_status) ? $fee_status : 'disabled';

        // Store Donation amount for recurring payment.
        give_update_payment_meta(
            $payment_id,
            '_give_fee_donation_amount',
            give_sanitize_amount_for_db($donation_amount - $fee_amount)
        );

        // Store total fee amount for recurring payment.
        give_update_payment_meta($payment_id, '_give_fee_amount', $fee_amount);

        // Update Give Fee Status for recurring payment.
        give_update_payment_meta($payment_id, '_give_fee_status', $fee_status);
    }

	/**
	 * Fires in the payment receipt short-code, after the receipt last item.
	 *
	 * Allows you to add new <td> elements after the receipt last item.
	 *
	 * @since  1.1.2
	 * @access public
	 *
	 * @param array $args
	 * @param int   $donation_id
	 * @param int   $form_id
	 *
	 * @return array
	 */
	public function payment_receipt( $args, $donation_id, $form_id ) {

		// Get the donation currency.
		$payment_currency = give_get_payment_currency_code( $donation_id );

		// Get total Donation amount.
		$total_donation = give_fee_format_amount( give_maybe_sanitize_amount( give_get_meta( $donation_id, '_give_payment_total', true ) ),
			array(
				'donation_id' => $donation_id,
				'currency'    => $payment_currency,
			)
		);

		// Get donation amount.
		$donation_amount = give_fee_format_amount( give_maybe_sanitize_amount( give_get_meta( $donation_id, '_give_fee_donation_amount', true ) ),
			array(
				'donation_id' => $donation_id,
				'currency'    => $payment_currency,
			)
		);

		// Get Fee amount.
		$fee_amount = give_fee_format_amount( give_maybe_sanitize_amount( give_get_meta( $donation_id, '_give_fee_amount', true ) ),
			array(
				'donation_id' => $donation_id,
				'currency'    => $payment_currency,
			)
		);

		if ( isset( $fee_amount ) && give_maybe_sanitize_amount( $fee_amount ) > 0 ) {
			// Add new item to the donation receipt.
			$row_2 = array(
				'name'    => __( 'Donation Fee', 'give-fee-recovery' ),
				'value'   => give_currency_filter( $fee_amount,
					array(
						'currency_code' => $payment_currency,
						'form_id'       => $form_id,
					)
				),
				'display' => true,// true or false | whether you need to display the new item in donation receipt or not.
			);

			$args = give_fee_recovery_array_insert_before( 'total_donation', $args, 'donation_fee_total', $row_2 );
		}

		if ( isset( $total_donation )
		     && isset( $donation_amount )
		     && ( $donation_amount !== $total_donation )
		     && give_maybe_sanitize_amount( $donation_amount ) > 0
		) {
			// Add new item to the donation receipt.
			$row_1 = array(
				'name'    => __( 'Donation Amount', 'give-fee-recovery' ),
				'value'   => give_currency_filter( $donation_amount,
					array(
						'currency_code' => $payment_currency,
						'form_id'       => $form_id,
					)
				),
				'display' => true,// true or false | whether you need to display the new item in donation receipt or not.
			);

			$args = give_fee_recovery_array_insert_before( 'donation_fee_total', $args, 'donation_total_with_fee', $row_1 );
		}

		return $args;

	}

	/**
	 * Add fee related line items to donation receipt.
	 *
	 * @param DonationReceipt $receipt
	 * @since 1.7.9
	 */
	public function addReceiptItems( $receipt ){
		$updateDonationReceipt = new UpdateDonationReceipt( $receipt );
		$updateDonationReceipt->apply();
	}

	/**
	 * Call hook based on fee recovery settings and display checkbox label.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param integer $form_id form id.
	 */
	public function pre_form_output( $form_id ) {

		// Get the value of fee recovery enable or not.
		$is_fee_recovery = give_get_meta( $form_id, '_form_give_fee_recovery', true );
		$is_fee_recovery = ! empty( $is_fee_recovery ) ? $is_fee_recovery : 'global';

		// Bailout if per form and global fee recovery is not setup.
		if ( give_is_setting_enabled( $is_fee_recovery, 'global' )
		     && give_is_setting_enabled( give_get_option( 'give_fee_recovery' ) )
		) {

			// Get Location hook.
			$location_hook = give_get_option( 'give_fee_checkbox_location', 'give_after_donation_levels' );

		} elseif ( give_is_setting_enabled( $is_fee_recovery ) ) {
			// Get Location hook.
			$location_hook = give_get_meta( $form_id, '_form_give_fee_checkbox_location', true );
			$location_hook = ! empty( $location_hook ) ? $location_hook : 'give_after_donation_levels';

		} else {
			// Set default hook.
			$location_hook = 'give_after_donation_levels';
		}// End if().

		/**
		 * Customize Location hook.
		 */
		$location_hook = apply_filters( 'give_fee_recovery_location_hook', $location_hook, $form_id );

		// Dynamic hook based on Global/Per-Form settings.
		add_action( $location_hook, array(
			$this,
			'fee_output',
		), 0, 1 );

	}

	/**
	 * Subtract Fee amount from the Goal Progress.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param float   $progress Goal Progress output.
	 * @param integer $form_id  Goal Donation Form ID.
	 *
	 * @return float | int
	 */
	public function percentage_output( $progress, $form_id ) {

		// Bailout, if goal amount needs recurring count only.
		if (
		empty( $form_id ) ?
			false :
			(bool) give_get_meta( $form_id, '_give_recurring_goal_format', true )
		) {
			return $progress;
		}

		$goal_format = give_get_meta( $form_id, '_give_goal_format', true );

		// Return progress if Goal format set as Number of donation or number of donors.
		if ( in_array( $goal_format, array( 'donation', 'donors' ) ) ) {
			return $progress;
		}

		// Define Output Format.
		$output_type = 'progress';

		return $this->goal_output( $progress, $form_id, $output_type );
	}

	/**
	 * Update Goal Income by Subtract Fee amount.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param float   $income
	 * @param integer $form_id
	 *
	 * @return float | int
	 */
	public function raised_output( $income, $form_id ) {
		// Define Output Format.
		$output_type = 'income';

		return $this->goal_output( $income, $form_id, $output_type );
	}

	/**
	 * Add custom attribute to give_totals shortcode
	 *
	 * @since 1.7.3
	 *
	 * @param array $out
	 * @param array $pairs
	 * @param array $atts
	 *
	 * @return mixed
	 */
	public function give_totals_add_attribute( $out, $pairs, $atts ){
		$key = 'include_fees';

		$out[$key] = false;
		if( array_key_exists( 'include_fees', $atts ) ) {
			$out[$key] = $atts[$key];
		}

		return $out;
	}


	/**
	 * Remove fee amount from form earning for give_total shortcode
	 *
	 * @since 1.7.3
	 *
	 * @param       $earning_sum
	 * @param int   $form_id
	 * @param array $atts Shortcode attributes
	 *
	 * @return string
	 */
	public function give_totals( $earning_sum, $form_id, $atts ) {
		if( $atts['include_fees'] ) {
			return $earning_sum;
		}

		$form_fee_earning = give_get_meta( $form_id, '_give_form_fee_earnings', true );
		$form_fee_earning = ! empty( $form_fee_earning ) ? $form_fee_earning : 0;
		$earning_sum      = $earning_sum - $form_fee_earning;

		return $earning_sum;
	}

	/**
	 * Goat output based on Income or Progress format.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @param float  $output      Output Format : income | progress.
	 * @param int    $form_id     Form ID.
	 * @param string $output_type Output type: income | progress.
	 *
	 * @return float | int
	 */
	public function goal_output( $output, $form_id, $output_type ) {
		// Get Donation Form Object.
		$form = new Give_Donate_Form( $form_id );

		// Get Fee earnings per Form.
		if ( give_has_upgrade_completed( 'give_fee_recovery_v151_form_fee_earnings' ) ) {
			$earnings = give_get_meta( $form_id, '_give_form_fee_earnings', true );
		} else {
			$earnings = give_fee_number_format( give_get_fee_earnings( $form_id ) );
		}

		$earnings = ! empty( $earnings ) ? $earnings : 0;

		// If Give fee exist then subtract from the total Income and calculate Progress.
		if ( ! empty( $earnings ) ) {
			$goal   = $form->goal; // Get Form Goal value.
			$income = round( give_get_form_earnings_stats( $form_id ), 2 );// Get total Form earnings.
			$income = $income - $earnings;    // Subtract Recovery Fee from total income.
			$output = $income;
			if ( 'progress' === $output_type ) {
				$output = round( ( $income / $goal ) * 100, 2 );// Calculate Goal Progress.
			}
		}// End if().

		return $output;
	}

	/**
	 * Call function based on Give load gateway ajax callback.
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public function gateway_callback() {
		// Get Form id from the ajax callback.
		$form_id = ! empty( $_POST['give_form_id'] ) ? absint( $_POST['give_form_id'] ) : 0;

		if ( ! empty( $form_id ) ) {
			$this->pre_form_output( $form_id );
		}

	}

	/**
	 * Show Fee break down before the final total.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return bool
	 */
	public function show_fee_breakdown( $form_id ) {
		// Get the value of fee recovery enable or not.
		$breakdown_text = apply_filters( 'give_fee_break_down_message', __( '{amount} donation plus {fee_amount} to help cover fees.', 'give-fee-recovery' ) );// Breakdown message.

		echo sprintf(
			'<p class="fee-break-down-message %1$s" data-breakdowntext="%2$s" style="display: none;">%2$s</p>',
			"fee-break-down-message-{$form_id}",
			$breakdown_text
		);

	}

	/**
	 * Fee Recovery Form HTML tags.
	 *
     * @since  1.8.0 add support for max fee coverage.
	 * @since  1.3.1
	 * @access public
	 *
	 * @param int   $form_id
	 * @param array $args
	 */
	public function hidden_field_data( $form_id, $args ) {

		$give_fee_status  = true; // Set Fee Recovery enable/disable.
		$is_break_down    = true; // Set Fee break-down enable/disable.
		$give_fee_disable = false; // Set Fee Recovery disable based on Per-Gateway.

		$default_percentage = give_sanitize_amount_for_db( give_format_decimal( array(
			'amount'   => 2.90,
			'currency' => give_get_currency( $form_id ),
		) ) );

		$default_additional_fee_amount = give_sanitize_amount_for_db( give_format_decimal( array(
			'amount'   => 0.30,
			'currency' => give_get_currency( $form_id ),
		) ) );

		// Fee Recovery enable/disable Per-Form.
		$is_fee_recovery = give_get_meta( $form_id, '_form_give_fee_recovery', true );
		$is_fee_recovery = ! empty( $is_fee_recovery ) ? $is_fee_recovery : 'global';

		// Check if Give Fee Recovery option is Global or Per-Form.
		if (
			give_is_setting_enabled( $is_fee_recovery, 'global' ) &&
			give_is_setting_enabled( give_get_option( 'give_fee_recovery' ) )
		) {

			// Set Fee break-down disable if not enabled from the Backend.
			if ( ! give_is_setting_enabled( give_get_option( 'give_fee_breakdown', 'enabled' ) ) ) {
				$is_break_down = false;
			}

			// Prepare Fee data.
			$fee_data = array();

			// Check if Fee Configuration option set as All Gateway.
			if ( give_is_setting_enabled( give_get_option( 'give_fee_configuration' ), 'all_gateways' ) ) {

				// Get Global Fee Percentage because Gateway set is All Gateway.
				$percentage = give_get_option( 'give_fee_percentage', $default_percentage );

				// Get Global Fee base amount because Gateway set is All Gateway.
				$base_amount = give_get_option( 'give_fee_base_amount', $default_additional_fee_amount );

				$fee_data['all_gateways'] = array(
					'percentage'       => $percentage,
					'base_amount'      => $base_amount,
					'give_fee_disable' => false, // Set Fee Recovery enable.
					'give_fee_status'  => true, // Set Fee recovery disable as Gateway enable.
					'is_break_down'    => $is_break_down, // Show Fee Break down if enabled.
					'maxAmount'        => Settings::getMaximumFeeAmount( $form_id ),
				);

			} else {

				// Get all enabled Payment gateways.
				$all_enable_gateways = give_get_enabled_payment_gateways();

				// Get Fee Percentage and base amount based on each Gateway.
				foreach ( $all_enable_gateways as $gateway_key => $enable_gateway ) {
					if ( give_is_setting_enabled( give_get_option( "give_fee_gateway_fee_enable_option_{$gateway_key}" ) ) ) {

						// Get Fee Percentage for selected Gateway.
						$percentage = give_get_option( "give_fee_gateway_fee_percentage_{$gateway_key}", 2.90 );

						// Get Fee Base amount for selected Gateway.
						$base_amount = give_get_option( "give_fee_gateway_fee_base_amount_{$gateway_key}", 0.30 );

						$fee_data[ $gateway_key ] = array(
							'percentage'       => $percentage,
							'base_amount'      => $base_amount,
							'give_fee_disable' => false, // Set Fee Recovery enable.
							'give_fee_status'  => true, // Set Fee recovery disable as Gateway enable.
							'is_break_down'    => $is_break_down, // Do not show Fee break down if Gateway disabled.
							'maxAmount'        => Settings::getMaximumFeeAmount( $form_id, $gateway_key ),
						);

					} else {
						$fee_data[ $gateway_key ] = array(
							'percentage'       => 0,
							'base_amount'      => 0,
							'give_fee_disable' => true, // Set Fee Recovery disable.
							'give_fee_status'  => false, // Set Fee recovery disable as Gateway disable.
							'is_break_down'    => false, // Do not show Fee break down if Gateway disabled.
							'maxAmount'        => Settings::getMaximumFeeAmount( $form_id, $gateway_key ),
						);
					}// End if().
				} // End foreach().
			}// End if().

			// Build Fee array.
			$fee_array = array(
				'fee_data'         => $fee_data,
				'give_fee_status'  => $give_fee_status,
				'give_fee_disable' => $give_fee_disable,
				'is_break_down'    => $is_break_down,
				'fee_mode'         => give_get_option( 'give_fee_mode', 'donor_opt_in' ),
				'is_fee_mode'      => true,
				'fee_recovery'     => true,
			);

		} elseif ( give_is_setting_enabled( $is_fee_recovery ) ) {

			$fee_break_down = give_get_meta( $form_id, '_form_breakdown', true );
			$fee_break_down = ! empty( $fee_break_down ) ? $fee_break_down : 'enabled';

			// Set Fee break-down.
			if ( ! give_is_setting_enabled( $fee_break_down ) ) {
				$is_break_down = false;
			}

			// Get Per-Form Fee Mode.
			$give_per_form = give_get_meta( $form_id, '_form_give_fee_configuration', true );
			$fee_mode      = give_get_meta( $form_id, '_form_give_fee_mode', true );

			// Store Fee data.
			$fee_data = array();

			// Check if Fee Configuration option set as All Gateway.
			if ( give_is_setting_enabled( $give_per_form, 'all_gateways' ) ) {

				// Get Global Fee Percentage because Gateway set is All Gateway.
				$percentage = give_get_meta( $form_id, '_form_give_fee_percentage', true );
				$percentage = ( false !== $percentage ) ? $percentage : $default_additional_fee_amount;

				// Get Global Fee base amount because Gateway set is All Gateway.
				$base_amount = give_get_meta( $form_id, '_form_give_fee_base_amount', true );
				$base_amount = ( false !== $base_amount ) ? $base_amount : $default_additional_fee_amount;

				$fee_data['all_gateways'] = array(
					'percentage'       => $percentage,
					'base_amount'      => $base_amount,
					'give_fee_disable' => false, // Set Fee Recovery enable.
					'give_fee_status'  => true, // Set Fee recovery disable per form.
					'is_break_down'    => $is_break_down, // Show Fee Break down if enabled.
					'maxAmount'        => Settings::getMaximumFeeAmount( $form_id ),
				);

			} else {
				// Get all enabled Payment gateways.
				$all_enable_gateways = give_get_enabled_payment_gateways();

				// Get Fee Percentage and base amount based on each Gateway.
				foreach ( $all_enable_gateways as $gateway_key => $enable_gateway ) {

					$per_gateway_key = give_get_meta( $form_id, "_form_gateway_fee_enable_{$gateway_key}", true );

					if ( give_is_setting_enabled( $per_gateway_key, 'enabled' ) ) {
                        // Get Fee Percentage for selected Gateway.
                        $percentage_key = '_form_gateway_fee_percentage_' . $gateway_key;
                        $percentage = give_get_meta($form_id, $percentage_key, true);
                        $percentage = (false !== $percentage) ? $percentage : $default_percentage;

                        // Get Fee Base amount for selected Gateway.
                        $base_amount_key = '_form_gateway_fee_base_amount_' . $gateway_key;
                        $base_amount = give_get_meta($form_id, $base_amount_key, true);
                        $base_amount = (false !== $base_amount) ? $base_amount : $default_additional_fee_amount;

                        $fee_data[$gateway_key] = array(
                            'percentage' => $percentage,
                            'base_amount' => $base_amount,
                            'give_fee_disable' => false, // Set Fee Recovery enable.
                            'give_fee_status' => true, // Set Fee recovery disable as Gateway enable.
                            'is_break_down' => $is_break_down, // Do not show Fee break down if Gateway disabled.
                            'maxAmount' => Settings::getMaximumFeeAmount($form_id, $gateway_key),
                        );
                    } else {
                        $fee_data[$gateway_key] = array(
                            'percentage' => 0,
                            'base_amount' => 0,
                            'give_fee_disable' => true, // Set Fee Recovery disable.
                            'give_fee_status' => false, // Set Fee recovery disable as Gateway disable.
                            'is_break_down' => false, // Do not show Fee break down if Gateway disabled.
                            'maxAmount' => Settings::getMaximumFeeAmount($form_id, $gateway_key),
                        );
                    }// End if().
                } // End foreach().
            }// End if().

            // Build Fee array.
            $fee_array = array(
                'fee_data' => $fee_data,
                'give_fee_status' => $give_fee_status,
                'give_fee_disable' => $give_fee_disable,
                'is_break_down' => $is_break_down,
                'fee_mode' => $fee_mode,
                'is_fee_mode' => true,
                'fee_recovery' => true,
            );
        } else {
            $fee_array = array(
                'fee_recovery' => false,
            );
        }// End if().

        /**
         * Customize Give Fee Recovery HTML Tag.
         */
        echo sprintf(
            '<input type="hidden" name="give-fee-recovery-settings" value="%s" />',
            esc_js(
                wp_json_encode(
                    apply_filters(
                        'give_fee_recovery_hidden_input_json',
                        $fee_array,
                        $form_id
                    )
                )
            )
        );
    }

    /**
     * Store Fee earnings for each Form.
     *
     * @since  1.5.1
     * @access public
     *
     * @param  int  $payment_id  Payment ID.
     */
    public function store_fee_earnings($payment_id)
    {
        if (give_has_upgrade_completed('give_fee_recovery_v151_form_fee_earnings')) {
            give_fee_store_form_fee_meta($payment_id);
        }
    }

    /**
     * Store fee earnings into Form meta for renewal donation.
     *
     * @since 1.5.1
     *
     * @param  Give_Payment  $payment  Renewal Payment
     * @param  object  $subscription
     */
    public function store_renewal_donation_fee_earnings($payment, $subscription)
    {
        if (give_has_upgrade_completed('give_fee_recovery_v151_form_fee_earnings')) {
            $payment_id = $payment->parent_payment;
            give_fee_store_form_fee_meta($payment_id);
        }

        $this->insert_data_for_renewal_donation($payment->ID, $payment->parent_payment, $payment->total);
    }


    /**
     * Filter Donation amount.
     *
     * @since 1.7
     *
     * @param  string  $formatted_amount  Formatted/Un-formatted amount.
     * @param  float  $amount  Donation amount.
     * @param  int  $donation_id  Donation ID.
     * @param  array  $format_args  Formatted amount args.
     *
     * @return string $formatted_amount
     */
    public function update_donation_amount($formatted_amount, $amount, $donation_id, $format_args)
    {
        $fee_amount = give_get_meta($donation_id, '_give_fee_amount', true);
        $fee_amount = !empty($fee_amount) ? $fee_amount : 0;

        if (!empty($fee_amount)) {
            $donation_amount = give_get_meta($donation_id, '_give_fee_donation_amount', true);
            $donation_amount = !empty($donation_amount) ? $donation_amount : 0;

            // Get new Payment total by sum of Donation amount and Fee amount.
            $payment_total = $donation_amount + $fee_amount;

            // Return formatted amount, if amount and payment total match.
            if ($amount === $payment_total) {
                return $formatted_amount;
            }

            // Get number of decimal from the Fee amount.
            $fee_decimal_number = strlen(substr($fee_amount, strpos($fee_amount, ".") + 1));

            $currency = give_get_payment_currency_code($donation_id);
            $number_decimals = give_get_price_decimals($currency);
            $decimal_point = ($number_decimals > $fee_decimal_number) ? $number_decimals : $fee_decimal_number;

            $payment_total = $formatted_amount = round(
                floatval($payment_total),
                $decimal_point
            );

            $currency_code = give_get_payment_currency_code($donation_id);

            if ($format_args['amount'] || $format_args['currency']) {
                if ($format_args['amount']) {
                    $formatted_amount = give_format_amount(
                        $payment_total,
                        !is_array($format_args['amount']) ?
                            array(
                                'sanitize' => false,
                                'currency' => $currency_code,
                            ) :
                            $format_args['amount']
                    );
                }

                if ($format_args['currency']) {
                    $formatted_amount = give_currency_filter(
                        $formatted_amount,
                        !is_array($format_args['currency']) ?
                            array('currency_code' => $currency_code) :
                            $format_args['currency']
                    );
                }
            }

		}

		/**
		 * Filter Fee recovery Donation amount.
		 *
		 * @since 1.7
		 *
		 * @param string $formatted_amount Formatted/Un-formatted amount.
		 * @param float  $amount           Donation amount.
		 * @param int    $donation_id      Donation ID.
		 * @param array  $format_args      Formatted args.
		 */
		return apply_filters( 'give_fee_donation_amount', (string) $formatted_amount, $amount, $donation_id, $format_args );
	}

	/**
	 * This function is used to update subscription arguments on processing recurring donation.
	 * Note: with recurring 1.10.3, you can use second argument
	 *
	 * @param  array  $args  List of subscription arguments.
	 *
	 * @return array
	 * @since 1.7.9
	 *
	 */
	public function update_subscription_args( $args ) {
		$isFeeEnabled = ! empty( $_POST['give-fee-mode-enable'] ) ?
			filter_var( $_POST['give-fee-mode-enable'], FILTER_VALIDATE_BOOLEAN ) :
			false;
		$feeAmount    = ! empty( $_POST['give-fee-amount'] ) ?
			give_clean( $_POST['give-fee-amount'] ) :
			0;

		// Assign fee amount to subscription
		$args['recurring_fee_amount'] = $isFeeEnabled ? $feeAmount : 0;

		return $args;
	}

}
