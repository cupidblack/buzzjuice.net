<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 *
 * @package    Give_WooCommerce_Admin
 * @subpackage Give_WooCommerce_Admin/admin
 */

/**
 * This class is responsible for the action happened in wp-admin(backend).
 *
 * @since      1.0.0
 *
 * @package    Give_WooCommerce_Admin
 * @subpackage Give_WooCommerce_Admin
 * @author     GiveWP <https://givewp.com>
 */
class Give_WooCommerce_Admin {

	/**
	 * Give_WooCommerce_Admin constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Enqueue wp-admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'give_wc_enqueue_scripts' ), 10 );

		// Show notice if WC and Give currency is not same.
		add_action( 'admin_notices', array( $this, 'give_wc_check_currency' ), 10 );

		/**
		 * Register and handle Give setting in WooCommerce's setting page.
		 */
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'give_wc_setting_tab' ), 100 );
		add_action( 'woocommerce_settings_tabs_settings_tab_give', array( $this, 'give_wc_give_setting_fields' ) );
		add_action( 'woocommerce_update_options_settings_tab_give', array( $this, 'give_wc_give_update_setting' ) );

		// Register new field in WC to show the donation forms multi select.
		add_action( 'woocommerce_admin_field_wc_give_donations_forms', array( $this, 'give_wc_get_donation_forms' ), 10, 1 );

		// Register new WC setting field for Give Intro text.
		add_action( 'woocommerce_admin_field_wc_give_intro_text', array( $this, 'give_wc_give_intro_text' ), 10, 1 );

		// Show URL to refer the Give Donation Form.
		add_action( 'woocommerce_after_order_fee_item_name', array( $this, 'give_wc_add_give_reference' ), 10, 2 );

		// Add new meta on the donation edit page.
		add_action( 'give_view_donation_details_sidebar_before', array( $this, 'give_wc_display_reference_metabox' ), 10, 1 );

		// Show admin that this donation is connected to WooCommerce.
		add_action( 'give_view_donation_details_totals_after', array( $this, 'give_wc_show_reference_information' ), 10, 1 );

		// Show WooCommerce icon with status in the donation's list table.
		add_filter( 'give_payments_table_column', array( $this, 'give_wc_show_icon' ), 10, 3 );

		// Add custom JS and style on the WooCommerce Give Setting page.
		add_action( 'admin_head', array( $this, 'give_wc_setting_fields_style_and_script' ), 10 );

		// Handle if Give Donation option setting saved with no donation form selected.
		add_filter( 'woocommerce_admin_settings_sanitize_option_give_wc_donation_forms', array( $this, 'handle_give_wc_donation_forms' ), 10, 2 );

		// When change the form of donation update the fee order item of associated WC order.
		add_action( 'give_update_edited_donation', array( $this, 'give_wc_update_wcorder_fee_data' ) );

		// Add filter to display donation details in order preview modal
		add_filter( 'woocommerce_admin_order_preview_get_order_details', array( $this, 'give_wc_donation_order_modal' ), 10, 2 );
	}

	/**
	 * Save Donation Forms as blank array if no donation form were selected.
	 *
	 * @since 1.0.0
	 *
	 * @param null|array $value
	 *
	 * @return array
	 */
	public function handle_give_wc_donation_forms( $value ) {
		return null === $value ? array() : $value;
	}

	/**
	 * When change the Donation's form update the fee data on the WC order.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $payment_id Give Donation ID.
	 */
	public function give_wc_update_wcorder_fee_data( $payment_id ) {
		// Return, If Donation wasn't created within WC Order.
		if ( ! give_wc_is_woo_donation( $payment_id ) && ! isset( $_POST['give-payment-form-select'] ) ) {
			return;
		}

		// Donation's form id.
		$form_id = give_get_payment_form_id( $payment_id );

		// If donation's form ID has changed.
		if ( $_POST['give-payment-form-select'] !== $form_id ) {
			give_wc_update_wc_order_fee_data( $payment_id, $form_id, $_POST['give-payment-form-select'] );
		}
	}

	/**
	 * Show WC icon on the donation list page.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $value       HTML content.
	 * @param integer $payment_id  Donation ID.
	 * @param string  $column_name column name.
	 *
	 * @return string
	 */
	public function give_wc_show_icon( $value, $payment_id, $column_name ) {
		// If woo donation is not enabled.
		if ( ! give_wc_is_woo_donation( $payment_id ) ) {
			return $value;
		}

		if ( 'status' === $column_name ) {
			$value = sprintf(
				'%1$s <span class="give-item-label give-item-label-orange give-wc-donation-col" data-tooltip="%2$s">%3$s</span>',
				$value,
				__( 'This donation was made with a WooCommerce order.', 'give-woocommerce' ),
				__( 'Woo', 'give-woocommerce' )
			);
		}

		return $value;
	}

	/**
	 * Show notice that the donation was made within in WooCommerce.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $donation_id Donation ID.
	 */
	public function give_wc_show_reference_information( $donation_id ) {
		if ( ! give_wc_is_woo_donation( $donation_id ) ) {
			return;
		}
		?>
		<div class="give-notice give-wc-notice"><span class="give-donation-status-wc"></span>
			<?php esc_html_e( 'This donation was made within the WooCommerce checkout.', 'give-woocommerce' ); ?>
		</div>
		<?php
	}

	/**
	 * Enqueue Admin scripts.
	 *
	 * @since 1.0.0
	 */
	public function give_wc_enqueue_scripts() {
		// Register css for frontend.
		wp_enqueue_style(
			'give-woocommerce-admin',
			GIVE_WOOCOMMERCE_PLUGIN_URL . 'assets/dist/css/admin.css',
			GIVE_WOOCOMMERCE_VERSION,
			false
		);

		// Enqueue WooCommerce admin script.
		wp_enqueue_script(
			'give-wocoomerce-admin',
			GIVE_WOOCOMMERCE_PLUGIN_URL . 'assets/dist/js/admin.js',
			GIVE_WOOCOMMERCE_VERSION,
			false
		);
	}

	/**
	 * Show meta-box on donation edit page to show WooCommerce order ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $payment_id Donation ID.
	 */
	public function give_wc_display_reference_metabox( $payment_id ) {
		// If donation is not related to WooCommerce.
		if ( ! give_wc_is_woo_donation( $payment_id ) ) {
			return;
		}

		// Get WC order ID from the give donation.
		$wc_order_id = give_get_payment_meta( $payment_id, '_give_wc_order_id', true );
		?>
		<div id="give-donation-wc-payments" class="postbox">
			<h3 class="hndle">
				<span><?php _e( 'WooCommerce Order Information', 'give-woocommerce' ); ?></span>
			</h3>
			<div class="inside give-woocommerce-parent-inside">
				<p class="give-donation-wc">
						<span class="give-tooltip give-wc-info"
						      data-tooltip="<?php _e( 'This donation was made with a WooCommerce order.', 'give-woocommerce' ); ?>">
						<?php printf( __( 'WooCommerce Order ID: <a href="%1$s">#%2$d</a>', 'give-woocommerce' ), admin_url( "post.php?post={$wc_order_id}&action=edit" ), $wc_order_id ); ?>
						</span>
				</p>
			</div><!--/.inside -->
		</div>
		<?php
	}

	/**
	 * Show Give Donation Form name with link.
	 *
	 * @since 1.0.0
	 *
	 * @param integer            $item_id   Item ID.
	 * @param \WC_Order_Item_Fee $item_data Item Data.
	 */
	public function give_wc_add_give_reference( $item_id, $item_data ) {
		// Get the order item meta data.
		$give_wc_fee_data = wc_get_order_item_meta( $item_data->get_id(), '_give_wc_form_relation', true );

		// If has no meta data available.
		if ( empty( $give_wc_fee_data ) ) {
			return;
		}
		?>
		<b>
			<?php
			// Get the Give donation ID.
			$give_donation_id = absint( $give_wc_fee_data['donation_id'] );

			echo sprintf( __( 'Give Donation: <a href="%1$s">#%2$s</a>', 'give-woocommerce' ),
				admin_url( "edit.php?post_type=give_forms&page=give-payment-history&view=view-payment-details&id={$give_donation_id }" ),
				$give_donation_id
			); ?>
		</b>
		<?php
	}

	/**
	 * Show notice if WooCommerce and Give currency is not same.
	 *
	 * @since 1.0.0
	 */
	public function give_wc_check_currency() {
		// Check and show notice if WC and Give currency is not same.
		if (
			class_exists( 'WooCommerce' )
			&& get_woocommerce_currency() !== give_get_currency()
		) {

			// Register WP Admin notice.
			Give()->notices->register_notice( array(
				'id'               => 'give-wc-currency-mismatch',
				'type'             => 'error',
				'description'      => sprintf(
					__( 'GiveWP and WooCommerce are not using the same currency. Please go to the <a href="%s">settings page</a> to change the currency.', 'give-woocommerce' ),
					admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=general&section=currency-settings' )
				),
				'dismiss_interval' => 'shortly',
			) );
		}
	}

	/**
	 * Callback for the Give Donation Introduction text.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value field option.
	 */
	public function give_wc_give_intro_text( $value ) {

		// Get previously selected donation forms or default value.
		$input_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] );

		// Get the field description.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		?>
		<tr valign="top" class="give_wc_intro_text">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp">
				<textarea name="<?php echo esc_attr( $value['id'] ); ?>"
				          id="<?php echo esc_attr( $value['id'] ); ?>"
				          style="<?php echo esc_attr( $value['css'] ); ?>"
				          placeholder="<?php echo $value['placeholder']; ?>"
				          class="give-wc-money-field give_input_decimal"
				><?php echo $input_value; ?></textarea><br />
				<p class="description"><?php echo ( $field_description['description'] ) ? $field_description['description'] : ''; // WPCS: XSS ok. ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get Give donation forms - WC donation form selection field render.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Get the setting field data.
	 */
	public function give_wc_get_donation_forms( $value ) {

		// Get previously selected donation forms or default value.
		$selections = (array) WC_Admin_Settings::get_option( $value['id'], $value['default'] );

		// Get the field description.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp">
				<select
						multiple="multiple"
						name="<?php echo esc_attr( $value['id'] ); ?>[]"
						style="width:350px"
						data-placeholder="<?php esc_attr_e( 'Choose Give donation form(s)', 'give-woocommerce' ); ?>"
						aria-label="<?php esc_attr_e( 'Give donation forms.', 'give-woocommerce' ); ?>"
						class="wc-enhanced-select">
					<?php
					// Get the Give Donation forms.
					$give_donations_form = get_posts( array(
						'post_type'      => 'give_forms',
						'posts_per_page' => 9999999999,
					) );

					if ( ! empty( $give_donations_form ) ) {
						foreach ( $give_donations_form as $key => $form ) {
							echo sprintf(
								'<option value="%d" %s > %s </option>', $form->ID,
								selected( in_array( $form->ID, array_values( $selections ) ), true, false ),
								esc_html( $form->post_title )
							);
						}
					}
					?>
				</select> <br />
				<p class="description">
					<?php echo ( $field_description['description'] ) ? $field_description['description'] : ''; // WPCS: XSS ok.?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs.
	 *
	 * @return array
	 */
	public static function give_wc_setting_tab( $settings_tabs ) {
		$settings_tabs['settings_tab_give'] = __( 'Donations', 'give-woocommerce' );

		return $settings_tabs;
	}

	/**
	 * Show field inside the Give setting tab in WC.
	 *
	 * @since 1.0.0
	 */
	public function give_wc_give_setting_fields() {
		// Render Give Donation setting fields on WC setting page.
		woocommerce_admin_fields( $this->give_get_settings() );
	}

	/**
	 * Save the Give setting field from the WC setting field(s).
	 *
	 * @since 1.0.0
	 */
	public function give_wc_give_update_setting() {
		// Handle the updation of the Give Donation setting fields.
		woocommerce_update_options( $this->give_get_settings() );
	}

	/**
	 * Get the array of the Give donation setting fields.
	 *
	 * @since 1.0.0
	 *
	 * @return  array
	 */
	public function give_get_settings() {
		$settings = array(
			array(
				'name' => __( 'GiveWP + WooCommerce Donations', 'give-woocommerce' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'give_wc_donation_setting_title',
			),
			array(
				'title'         => __( 'Enable Donations', 'give-woocommerce' ),
				'type'          => 'radio',
				'default'       => 'disabled',
				'wrapper_class' => 'give_wc_enabled_disabled',
				'id'            => 'give_wc_donation_enabled',
				'desc'          => __( 'Do you want to accept donations with GiveWP on WooCommerce?', 'give-woocommerce' ),
				'desc_tip'      => false,
				'options'       => array(
					'enabled'  => __( 'Enabled', 'give-woocommerce' ),
					'disabled' => __( 'Disabled', 'give-woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Donation Location', 'give-woocommerce' ),
				'type'     => 'radio',
				'id'       => 'give_wc_donation_location',
				'desc_tip' => false,
				'desc'     => __( 'Do you want to ask for a donation on the WooCommerce cart or checkout page?', 'give-woocommerce' ),
				'default'  => 'cart',
				'options'  => array(
					'cart'     => __( 'Cart', 'give-woocommerce' ),
					'checkout' => __( 'Checkout', 'give-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Intro text', 'give-woocommerce' ),
				'desc'        => __( 'The text above will appear before the donation options. This text should be a few sentences at most and should motivate the donor to support your cause.', 'give-woocommerce' ),
				'id'          => 'give_wc_intro_text',
				'css'         => 'width:300px; height: 75px;',
				'placeholder' => __( 'Write an introduction text...', 'give-woocommerce' ),
				'type'        => 'wc_give_intro_text',
				'desc_tip'    => false,
			),
			array(
				'title'    => __( 'Display Text', 'give-woocommerce' ),
				'desc'     => __( 'Enter the text you want to display on the cart page. Available tags include:<br><br> <code>{donation_title}</code>: The title of the donation form. <br><br><code>{donation_amount}</code>: The donation amount. This is useful for "Set" donation form types.', 'give-woocommerce' ),
				'id'       => 'give_wc_display_text',
				'default'  => 'Donate to {donation_title}',
				'type'     => 'text',
				'desc_tip' => false,
			),
			array(
				'title'    => __( 'Donation Forms', 'give-woocommerce' ),
				'id'       => 'give_wc_donation_forms',
				'type'     => 'wc_give_donations_forms',
				'default'  => '',
				'desc'     => __( 'Select the donation forms that you want the customer to choose from.', 'give-woocommerce' ),
				'desc_tip' => false,
				'class'    => 'give-wc-donation-forms wc-enhanced-select',
			),
			array(
				'title'         => __( 'Multi-level Display Style', 'give-woocommerce' ),
				'type'          => 'radio',
				'default'       => 'dropdown',
				'wrapper_class' => 'give_wc_multi_level_display_style',
				'id'            => 'give_wc_multi_level_display_style',
				'desc'          => __( 'Adjust the appearance of your multi-level donation forms.', 'give-woocommerce' ),
				'desc_tip'      => false,
				'options'       => array(
					'dropdown' => __( 'Dropdown', 'give-woocommerce' ),
					'radio'    => __( 'Radios', 'give-woocommerce' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'give_wc_donation_setting_end',
			),
		);

		/**
		 * Setting fields for configure the Give Donation form setting.
		 *
		 * @param array $settings array Give Donation setting field in WC.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'give_wc_global_setting_fields', $settings );
	}

	/**
	 * Add custom JS and CSS to manage the setting fields.
	 *
	 * @since 1.0
	 */
	public function give_wc_setting_fields_style_and_script() {
		if (
			isset( $_GET['tab'] )
			&& 'settings_tab_give' === $_GET['tab']
		) {
			?>
			<script type="text/javascript">
							jQuery( document ).ready( function( $ ) {
								$( 'body' ).find( 'table.form-table' ).addClass( 'give-wc-setting-fields' );
								$( 'td.forminp.forminp-radio' ).each( function( index, em ) {
									var desc = $( this ).find( 'fieldset p' ).text();
									$( this ).find( 'fieldset p' ).remove();
									$( this ).find( 'fieldset' ).append( $( '<p>', { class: 'description', text: desc } ) );
								} );
							} );
			</script>
			<style>
				td.forminp-text .description, .forminp-radio .description {
					display: block;
					margin: 8px 0 5px;
					color: #666;
					font-size: 13px;
				}
			</style>
			<?php
		}
	}

	/**
	 * Display donation details in order preview modal
	 *
	 * @param $order_items
	 * @param $order
	 *
	 * @return mixed
	 */
	public function give_wc_donation_order_modal( $order_items, $order ) {

		$donation_modal_html      = $this->give_wc_donation_order_modal_html( $order );
		$order_items['item_html'] = ! empty( $donation_modal_html )
			? str_replace( '</tbody>', "{$donation_modal_html}</tbody>", $order_items['item_html'] )
			: $order_items['item_html'];

		return $order_items;

	}

	/**
	 * Get donation items to display in the preview as HTML.
	 *
	 * @param $order
	 *
	 * @return string
	 */
	private function give_wc_donation_order_modal_html( $order ) {

		/* @var WC_Order $order */
		$line_items_fee = $order->get_items( 'fee' );

		ob_start();

		// Generate donation information HTML in modal pop-up
		foreach ( $line_items_fee as $item_id => $item ) {
			if( ! $item->meta_exists('_give_wc_form_relation') ) {
				continue;
			}

			?>
			<tr class="wc-order-preview-table__item">
				<td class="name">
					<div class="view">
						<?php echo esc_html( $item->get_name() ? $item->get_name() : __( 'Fee', 'give-woocommerce' ) ); ?>
					</div>
					<?php do_action( 'woocommerce_after_order_fee_item_name', $item_id, $item, null ); ?>
				</td>
				<td class="quantity" width="1%">&nbsp;</td>
				<td class="line_cost" width="1%">
					<div class="view">
						<?php
						echo wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) );

						if ( $refunded = $order->get_total_refunded_for_item( $item_id, 'fee' ) ) {
							echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>';
						}
						?>
					</div>
				</td>
			</tr>
			<?php
		}

		return ob_get_clean();

	}

}

// Initialize the class.
new Give_WooCommerce_Admin();