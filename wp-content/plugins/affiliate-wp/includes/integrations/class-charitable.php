<?php

/**
 * Class Affiliate_WP_Charitable.
 *
 * @package AffiliateWP
 * @subpackage Integrations
 * @since 2.30.0
 */

#[\AllowDynamicProperties]

/**
 * Implements an integration for Charitable.
 *
 * @since 2.30.0
 *
 * @see Affiliate_WP_Base
 */
class Affiliate_WP_Charitable extends Affiliate_WP_Base {

	/**
	 * The context for referrals. This refers to the integration that is being used.
	 *
	 * @var string
	 * @access  public
	 * @since   2.30.0
	 */
	public $context = 'charitable';

	/**
	 * Check if Charitable is active.
	 *
	 * @since 2.30.0
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	public function plugin_is_active() {
		return function_exists( 'charitable' ) || class_exists( 'Charitable' );
	}

	/**
	 * Get things started.
	 *
	 * @access  public
	 * @since   2.30.0
	 */
	public function init() {

		$this->referral_type = 'donation';

		// Add pending referral when donation is created.
		add_action( 'charitable_after_save_donation', [ $this, 'add_pending_referral' ], 10, 2 );

		// Mark referral complete when donation is completed.
		add_action( 'charitable_donation_status_changed', [ $this, 'process_donation_status_change' ], 10, 3 );

		// Handle donation deletion.
		add_action( 'trashed_post', [ $this, 'revoke_referral' ] );
		add_action( 'deleted_post', [ $this, 'revoke_referral' ] );

		// Add reference link in AffiliateWP admin.
		add_filter( 'affwp_referral_reference_column', [ $this, 'reference_link' ], 10, 2 );

		// Add AffiliateWP section to Campaign Settings meta box.
		add_filter( 'charitable_default_campaign_sections', [ $this, 'register_campaign_section' ] );
		add_filter( 'charitable_default_campaign_fields', [ $this, 'register_campaign_fields' ] );
		add_action( 'charitable_campaign_save', [ $this, 'save_campaign_meta' ], 10 );

		// Block editor support.
		add_action( 'charitable_campaign_builder_marketing_sidebar', [ $this, 'render_builder_sidebar_tab' ], 11 );
		add_action( 'charitable_campaign_builder_marketing_panels', [ $this, 'render_builder_panel' ], 11 );
		add_action( 'charitable_builder_save_campaign', [ $this, 'save_campaign_builder_meta' ], 10, 4 );

		// Enqueue campaign builder styles.
		add_action( 'admin_head', [ $this, 'enqueue_builder_styles' ] );
	}

	/**
	 * Records a pending referral when a donation is created.
	 *
	 * @param int                           $donation_id The donation ID.
	 * @param Charitable_Donation_Processor $processor The donation processor.
	 * @access  public
	 * @since   2.30.0
	 */
	public function add_pending_referral( $donation_id, $processor ) {

		// Check if referred.
		if ( ! $this->was_referred() ) {
			return false;
		}

		// Get the donation object.
		$donation = charitable_get_donation( $donation_id );

		if ( ! $donation ) {
			return false;
		}

		// Check for existing referral.
		$existing_referral = affwp_get_referral_by( 'reference', $donation_id, $this->context );

		// Check if it's a WP_Error or false (no referral found).
		if ( ! is_wp_error( $existing_referral ) && $existing_referral ) {
			return false;
		}

		// Get customer email.
		$customer_email = $donation->get_donor()->get_email();

		// Get referral description.
		$description = $this->get_referral_description( $donation );

		// Create draft referral.
		$referral_id = $this->insert_draft_referral(
			$this->affiliate_id,
			[
				'reference'   => $donation_id,
				'description' => $description,
			]
		);

		if ( ! $referral_id ) {
			$this->log( 'Draft referral creation failed.' );
			return false;
		}

		// Get referral total.
		$referral_total = $this->get_referral_total( $donation );

		// Hydrate the referral with the amount first (works for both success and failure).
		$this->hydrate_referral(
			$referral_id,
			[
				'amount' => $referral_total,
			]
		);

		// Check if referrals should be created for this donation.
		if ( ! $this->should_create_referral( $donation_id ) ) {
			$this->log( sprintf( 'Draft referral rejected because campaign does not allow referrals. Amount: %s', affwp_currency_filter( affwp_format_amount( $referral_total ) ) ) );
			$this->mark_referral_failed( $referral_id );
			return false;
		}

		// Customers cannot refer themselves.
		if ( $this->is_affiliate_email( $customer_email, $this->affiliate_id ) ) {
			$this->log( sprintf( 'Referral not created because affiliate\'s own account was used. Amount: %s', affwp_currency_filter( affwp_format_amount( $referral_total ) ) ) );
			$this->mark_referral_rejected( $referral_id );
			return false;
		}

		// If we get here, the referral passes all checks - mark it as pending.
		$this->hydrate_referral(
			$referral_id,
			[
				'status' => 'pending',
			]
		);

		$this->log( sprintf( 'Charitable referral #%d updated to pending successfully.', $referral_id ) );

		// Add note to donation.
		$this->insert_donation_note( $donation_id );
	}

	/**
	 * Process donation status changes.
	 *
	 * @param Charitable_Donation $donation   The donation object.
	 * @param string              $new_status The new donation status.
	 * @param string              $old_status The old donation status.
	 * @access  public
	 * @since   2.30.0
	 */
	public function process_donation_status_change( $donation, $new_status, $old_status ) {

		// Get the referral.
		$referral = affwp_get_referral_by( 'reference', $donation->ID, $this->context );

		if ( ! $referral ) {
			return;
		}

		switch ( $new_status ) {
			case 'charitable-completed':
				$this->complete_referral( $referral );
				break;

			case 'charitable-refunded':
			case 'charitable-cancelled':
			case 'charitable-failed':
				// Always reject the referral instead of deleting it to maintain audit trail.
				$this->reject_referral( $referral );
				break;

			case 'charitable-pending':
			case 'charitable-processing':
				// Set back to pending if it was rejected.
				if ( 'rejected' === $referral->status ) {
					affwp_set_referral_status( $referral, 'pending' );
				}
				break;
		}
	}

	/**
	 * Revoke referral when a donation is trashed or deleted.
	 *
	 * @param int $donation_id The donation ID.
	 * @access  public
	 * @since   2.30.0
	 */
	public function revoke_referral( $donation_id ) {

		if ( ! affiliate_wp()->settings->get( 'revoke_on_refund' ) ) {
			return;
		}

		if ( ! class_exists( 'Charitable' ) || Charitable::DONATION_POST_TYPE !== get_post_type( $donation_id ) ) {
			return;
		}

		$this->reject_referral( $donation_id );
	}

	/**
	 * Get the referral total for a donation.
	 *
	 * @param Charitable_Donation $donation The donation object.
	 * @return float The referral total.
	 * @access  private
	 * @since   2.30.0
	 */
	private function get_referral_total( $donation ) {

		$donation_id = $donation->ID;
		$total       = $donation->get_total_donation_amount();

		// Check for campaign-specific rates.
		$campaign_donations = $donation->get_campaign_donations();

		if ( ! empty( $campaign_donations ) ) {
			$custom_total    = 0;
			$has_custom_rate = false;

			foreach ( $campaign_donations as $campaign_donation ) {
				$rate      = affwp_sanitize_referral_rate( get_post_meta( $campaign_donation->campaign_id, '_affwp_charitable_referral_rate', true ) );
				$rate_type = get_post_meta( $campaign_donation->campaign_id, '_affwp_charitable_referral_rate_type', true );

				// Check if either custom rate or custom rate type is set.
				if ( ! empty( $rate ) || ! empty( $rate_type ) ) {
					$has_custom_rate = true;
					$decimals        = affwp_get_decimal_count();

					// If rate is empty but rate_type is set, get the raw global/affiliate rate.
					if ( empty( $rate ) && ! empty( $rate_type ) ) {
						// Get raw affiliate rate.
						$affiliate_rate = affiliate_wp()->affiliates->get_column( 'rate', $this->affiliate_id );
						$affiliate_rate = affwp_abs_number_round( $affiliate_rate );

						// If no affiliate rate, get global rate.
						if ( null === $affiliate_rate ) {
							$rate = affiliate_wp()->settings->get( 'referral_rate', 20 );
							$rate = affwp_abs_number_round( $rate );
						} else {
							$rate = $affiliate_rate;
						}
					}

					// If no rate type set, use site default.
					if ( empty( $rate_type ) ) {
						$rate_type = affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' );
					}

					// Convert percentage to decimal (e.g., 20 becomes 0.20).
					$rate = ( 'percentage' === $rate_type ) ? $rate / 100 : $rate;

					// Calculate referral amount based on type.
					$custom_total += ( 'percentage' === $rate_type )
						? round( $campaign_donation->amount * $rate, $decimals )
						: $rate;
				} else {
					// No custom rate or rate type set, use default rate calculation for this campaign amount.
					$custom_total += $this->calculate_referral_amount( $campaign_donation->amount, $donation_id, 0, $this->affiliate_id );
				}
			}

			if ( $has_custom_rate ) {
				return $custom_total;
			}
		}

		// Use default calculation.
		return $this->calculate_referral_amount( $total, $donation_id, 0, $this->affiliate_id );
	}

	/**
	 * Get the referral description for a donation.
	 *
	 * @param Charitable_Donation $donation The donation object.
	 * @return string The referral description.
	 * @access  private
	 * @since   2.30.0
	 */
	private function get_referral_description( $donation ) {

		$campaign_donations = $donation->get_campaign_donations();
		$campaign_donation  = ! empty( $campaign_donations ) ? reset( $campaign_donations ) : null;
		$campaign_name      = ( $campaign_donation && ! empty( $campaign_donation->campaign_id ) )
			? get_the_title( $campaign_donation->campaign_id )
			: '';

		return ! empty( $campaign_name )
			/* translators: %s: The campaign name */
			? sprintf( __( 'Donation to %s', 'affiliate-wp' ), $campaign_name )
			: __( 'Donation', 'affiliate-wp' );
	}

	/**
	 * Insert a note about the referral in the donation.
	 *
	 * @param int $donation_id The donation ID.
	 * @access  private
	 * @since   2.30.0
	 */
	private function insert_donation_note( $donation_id ) {

		$referral = affwp_get_referral_by( 'reference', $donation_id, $this->context );

		if ( ! $referral ) {
			return;
		}

		$affiliate = affwp_get_affiliate( $referral->affiliate_id );
		$user_info = get_userdata( $affiliate->user_id );
		$name      = $user_info ? $user_info->display_name : __( 'Unknown', 'affiliate-wp' );

		$note = sprintf(
			/* translators: 1: The referral ID, 2: The affiliate ID, 3: The affiliate name */
			__( 'Referral #%1$d created for affiliate #%2$d (%3$s).', 'affiliate-wp' ),
			$referral->referral_id,
			$affiliate->ID,
			$name
		);

		// Get existing log.
		$log = get_post_meta( $donation_id, '_donation_log', true );

		if ( ! is_array( $log ) ) {
			$log = [];
		}

		// Add new log entry.
		$log[] = [
			'time'    => time(),
			'message' => $note,
		];

		// Update the log.
		update_post_meta( $donation_id, '_donation_log', $log );
	}

	/**
	 * Setup the reference link in the Referrals table.
	 *
	 * @param string $reference The reference ID.
	 * @param object $referral  The referral object.
	 * @return string
	 * @access  public
	 * @since   2.30.0
	 */
	public function reference_link( $reference, $referral ) {

		if ( empty( $referral->context ) || 'charitable' !== $referral->context ) {
			return $reference;
		}

		$url = admin_url( 'post.php?action=edit&post=' . $reference );

		return '<a href="' . esc_url( $url ) . '">' . esc_html( $reference ) . '</a>';
	}

	/**
	 * Get the "Site Default" label with the actual default rate type.
	 *
	 * @return string The formatted label (e.g., "Site Default - Percentage (%)")
	 * @access  private
	 * @since   2.30.0
	 */
	private function get_site_default_rate_type_label() {
		$default_rate_type  = affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' );
		$rate_types         = affwp_get_affiliate_rate_types();
		$default_type_label = isset( $rate_types[ $default_rate_type ] )
			? $rate_types[ $default_rate_type ]
			: __( 'Percentage (%)', 'affiliate-wp' );

		return sprintf(
			/* translators: %s: The site's default referral rate type (e.g., "Percentage (%)" or "Flat NZD") */
			__( 'Site Default - %s', 'affiliate-wp' ),
			$default_type_label
		);
	}

	/**
	 * Register the AffiliateWP section in Charitable's Campaign Settings.
	 *
	 * @param array $sections The existing sections.
	 * @return array
	 * @access  public
	 * @since   2.30.0
	 */
	public function register_campaign_section( $sections ) {
		$sections['admin']['campaign-affiliatewp'] = __( 'AffiliateWP', 'affiliate-wp' );
		return $sections;
	}

	/**
	 * Register AffiliateWP fields in Charitable's campaign fields registry.
	 *
	 * @param array $fields The existing fields.
	 * @return array
	 * @access  public
	 * @since   2.30.0
	 */
	public function register_campaign_fields( $fields ) {
		$site_default_label = $this->get_site_default_rate_type_label();

		$fields['_affwp_charitable_referral_rate_type'] = [
			'label'          => __( 'Referral Rate Type', 'affiliate-wp' ),
			'data_type'      => 'meta',
			'value_callback' => function ( $campaign ) {
				return get_post_meta( $campaign->ID, '_affwp_charitable_referral_rate_type', true );
			},
			'admin_form'     => [
				'section'     => 'campaign-affiliatewp',
				'type'        => 'select',
				'priority'    => 10,
				'options'     => array_merge(
					[ '' => $site_default_label ],
					affwp_get_affiliate_rate_types()
				),
				'description' => __( 'Choose how the referral rate should be calculated for this campaign.', 'affiliate-wp' ),
			],
		];

		$fields['_affwp_charitable_referral_rate'] = [
			'label'          => __( 'Referral Rate', 'affiliate-wp' ),
			'data_type'      => 'meta',
			'value_callback' => function ( $campaign ) {
				return get_post_meta( $campaign->ID, '_affwp_charitable_referral_rate', true );
			},
			'admin_form'     => [
				'section'     => 'campaign-affiliatewp',
				'type'        => 'text',
				'priority'    => 20,
				'placeholder' => __( 'e.g., 20 or 10.50', 'affiliate-wp' ),
				'description' => __( 'Enter a number for the referral rate. The type (percentage or flat amount) is determined by the Rate Type field above. Leave blank to use the default rate.', 'affiliate-wp' ),
				'attrs'       => [
					'pattern'   => '[0-9]+(\.[0-9]+)?',
					'inputmode' => 'decimal',
					'title'     => __( 'Please enter a valid number (decimals allowed)', 'affiliate-wp' ),
				],
			],
		];

		$fields['_affwp_charitable_referrals_disabled'] = [
			'label'          => __( 'Disable referrals for this campaign', 'affiliate-wp' ),
			'data_type'      => 'meta',
			'value_callback' => function ( $campaign ) {
				return get_post_meta( $campaign->ID, '_affwp_charitable_referrals_disabled', true );
			},
			'admin_form'     => [
				'section'  => 'campaign-affiliatewp',
				'type'     => 'checkbox',
				'priority' => 30,
			],
		];

		return $fields;
	}

	/**
	 * Save campaign meta from legacy editor.
	 *
	 * @param WP_Post $post The post object.
	 * @access  public
	 * @since   2.30.0
	 */
	public function save_campaign_meta( $post ) {
		if ( 'campaign' !== $post->post_type ) {
			return;
		}

		$campaign_id = $post->ID;

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $campaign_id ) ) {
			return;
		}

		// Don't check nonce - Charitable already validated it.
		// phpcs:disable WordPress.Security.NonceVerification.Missing

		// Charitable prefixes field names with _campaign_ in the POST data
		// Save referral rate type.
		if ( isset( $_POST['_campaign__affwp_charitable_referral_rate_type'] ) && ! empty( $_POST['_campaign__affwp_charitable_referral_rate_type'] ) ) {
			$rate_type = sanitize_key( $_POST['_campaign__affwp_charitable_referral_rate_type'] );
			$types     = affwp_get_affiliate_rate_types();

			// Validate against allowed types.
			if ( array_key_exists( $rate_type, $types ) ) {
				update_post_meta( $campaign_id, '_affwp_charitable_referral_rate_type', $rate_type );
			} else {
				delete_post_meta( $campaign_id, '_affwp_charitable_referral_rate_type' );
			}
		} else {
			delete_post_meta( $campaign_id, '_affwp_charitable_referral_rate_type' );
		}

		// Save referral rate with proper sanitization.
		if ( isset( $_POST['_campaign__affwp_charitable_referral_rate'] ) && ! empty( $_POST['_campaign__affwp_charitable_referral_rate'] ) ) {
			$rate = affwp_sanitize_referral_rate( $_POST['_campaign__affwp_charitable_referral_rate'] );

			if ( ! empty( $rate ) ) {
				update_post_meta( $campaign_id, '_affwp_charitable_referral_rate', $rate );
			} else {
				delete_post_meta( $campaign_id, '_affwp_charitable_referral_rate' );
			}
		} else {
			delete_post_meta( $campaign_id, '_affwp_charitable_referral_rate' );
		}

		// Save disabled status.
		if ( isset( $_POST['_campaign__affwp_charitable_referrals_disabled'] ) ) {
			update_post_meta( $campaign_id, '_affwp_charitable_referrals_disabled', 1 );
		} else {
			delete_post_meta( $campaign_id, '_affwp_charitable_referrals_disabled' );
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Save campaign builder meta data.
	 *
	 * @param int   $campaign_id           The campaign ID.
	 * @param array $campaign_settings_v2  The campaign settings array.
	 * @param array $campaign_post         The raw post data.
	 * @param bool  $is_preview            Whether this is a preview save.
	 * @access  public
	 * @since   2.30.0
	 */
	public function save_campaign_builder_meta( $campaign_id, $campaign_settings_v2, $campaign_post, $is_preview ) {

		// Don't save meta for preview mode.
		if ( $is_preview ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $campaign_id ) ) {
			return;
		}

		// Nonce is verified by Charitable before this hook fires.
		// phpcs:disable WordPress.Security.NonceVerification.Missing

		// Check if our AffiliateWP data is present in the settings.
		if ( ! isset( $campaign_settings_v2['settings']['marketing']['affiliatewp'] ) ) {
			return;
		}

		$affwp_settings = $campaign_settings_v2['settings']['marketing']['affiliatewp'];

		// Save referral rate type.
		if ( isset( $affwp_settings['referral_rate_type'] ) && ! empty( $affwp_settings['referral_rate_type'] ) ) {
			$rate_type = sanitize_key( $affwp_settings['referral_rate_type'] );
			$types     = affwp_get_affiliate_rate_types();

			// Validate against allowed types.
			if ( array_key_exists( $rate_type, $types ) ) {
				update_post_meta( $campaign_id, '_affwp_charitable_referral_rate_type', $rate_type );
			} else {
				delete_post_meta( $campaign_id, '_affwp_charitable_referral_rate_type' );
			}
		} else {
			delete_post_meta( $campaign_id, '_affwp_charitable_referral_rate_type' );
		}

		// Save referral rate with proper sanitization.
		if ( isset( $affwp_settings['referral_rate'] ) && ! empty( $affwp_settings['referral_rate'] ) ) {
			$rate = affwp_sanitize_referral_rate( $affwp_settings['referral_rate'] );

			if ( ! empty( $rate ) ) {
				update_post_meta( $campaign_id, '_affwp_charitable_referral_rate', $rate );
			} else {
				delete_post_meta( $campaign_id, '_affwp_charitable_referral_rate' );
			}
		} else {
			delete_post_meta( $campaign_id, '_affwp_charitable_referral_rate' );
		}

		// Save disabled status.
		if ( isset( $affwp_settings['referrals_disabled'] ) && $affwp_settings['referrals_disabled'] ) {
			update_post_meta( $campaign_id, '_affwp_charitable_referrals_disabled', 1 );
		} else {
			delete_post_meta( $campaign_id, '_affwp_charitable_referrals_disabled' );
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Enqueue styles for the campaign builder.
	 *
	 * @access  public
	 * @since   2.30.0
	 */
	public function enqueue_builder_styles() {
		// Only load on campaign builder page.
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( 'charitable-campaign-builder' !== $page ) {
			return;
		}

		?>
		<style>
			/* White background for AffiliateWP icon */
			.charitable-panel-sidebar-section-affiliatewp .charitable-builder-sidebar-icon {
				background: #fff;
				padding: 8px;
				border-radius: 4px;
				box-shadow: 0 0 0 1px rgba(0,0,0,0.05);
			}
			/* Adjust background when active */
			.charitable-panel-sidebar-section-affiliatewp.active .charitable-builder-sidebar-icon {
				background: rgba(255,255,255,0.9);
			}
		</style>
		<?php
	}

	/**
	 * Render the sidebar tab for the block editor marketing panel.
	 *
	 * @access  public
	 * @since   2.30.0
	 */
	public function render_builder_sidebar_tab() {
		// Determine if this tab should be active by default (it shouldn't be).
		$active    = apply_filters( 'charitable_campaign_builder_marketing_sidebar_active', false, 'affiliatewp' );
		$css_class = $active ? 'active' : '';

		echo '<a href="#" class="charitable-panel-sidebar-section charitable-panel-sidebar-section-affiliatewp ' . esc_attr( $css_class ) . '" data-section="affiliatewp">'
			. '<img class="charitable-builder-sidebar-icon" src="' . esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/logo-affwp.svg' ) . '" alt="AffiliateWP" />'
			. esc_html__( 'AffiliateWP', 'affiliate-wp' )
			. ' <i class="fa fa-angle-right charitable-toggle-arrow"></i></a>';
	}

	/**
	 * Render the panel content for the block editor marketing panel.
	 *
	 * @access  public
	 * @since   2.30.0
	 */
	public function render_builder_panel() {
		// Get current campaign ID from the builder.
		$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;

		// Ensure campaign ID is valid.
		if ( $campaign_id && get_post_type( $campaign_id ) !== 'campaign' ) {
			$campaign_id = 0;
		}

		$rate_type = '';
		$rate      = '';
		$disabled  = '';

		if ( $campaign_id ) {
			$rate_type = get_post_meta( $campaign_id, '_affwp_charitable_referral_rate_type', true );
			$rate      = get_post_meta( $campaign_id, '_affwp_charitable_referral_rate', true );
			$disabled  = get_post_meta( $campaign_id, '_affwp_charitable_referrals_disabled', true );
		}

		// Charitable_Builder_Form_Fields is always available in the campaign builder.
		if ( ! class_exists( 'Charitable_Builder_Form_Fields' ) ) {
			return;
		}

		$form_fields = new Charitable_Builder_Form_Fields();

		?>
		<div class="charitable-panel-content-section charitable-panel-content-section-affiliatewp" data-panel="affiliatewp">
			<div class="charitable-panel-content-section-title">
				<?php esc_html_e( 'AffiliateWP Settings', 'affiliate-wp' ); ?>
			</div>

			<div class="charitable-panel-content-section-interior">
				<?php
				$integrations       = affiliate_wp()->settings->get( 'integrations' );
				$charitable_enabled = is_array( $integrations ) && array_key_exists( 'charitable', $integrations );

				if ( ! $charitable_enabled ) :
					?>
					<div class="charitable-panel-content-section-notice">
						<p><?php esc_html_e( 'AffiliateWP integration needs to be enabled. Please go to AffiliateWP → Settings → Integrations and enable Charitable.', 'affiliate-wp' ); ?></p>
					</div>
					<?php
				else :
					$site_default_label = $this->get_site_default_rate_type_label();

					// Charitable's form field methods return escaped HTML.
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

					// Referral Rate Type Field.
					echo $form_fields->generate_dropdown(
						$rate_type,
						esc_html__( 'Referral Rate Type', 'affiliate-wp' ),
						[
							'id'       => 'settings-marketing-affiliatewp-referral_rate_type',
							'name'     => 'settings__marketing__affiliatewp__referral_rate_type',
							'options'  => array_merge(
								[ '' => $site_default_label ],
								affwp_get_affiliate_rate_types()
							),
							'tooltip'  => esc_html__( 'Choose how the referral rate should be calculated for this campaign.', 'affiliate-wp' ),
							'field_id' => 'referral_rate_type',
						]
					);

					// Referral Rate Field.
					echo $form_fields->generate_text(
						$rate,
						esc_html__( 'Referral Rate', 'affiliate-wp' ),
						[
							'id'          => 'settings-marketing-affiliatewp-referral_rate',
							'name'        => 'settings__marketing__affiliatewp__referral_rate',
							'placeholder' => esc_html__( 'e.g., 20 or 10', 'affiliate-wp' ),
							'tooltip'     => esc_html__( 'Enter a number for the referral rate. The type (percentage or flat amount) is determined by the Rate Type field above. Leave blank to use the default rate.', 'affiliate-wp' ),
							'field_id'    => 'referral_rate',
						]
					);

					// Disable Referrals Toggle.
					echo $form_fields->generate_toggle(
						$disabled,
						esc_html__( 'Disable referrals for this campaign', 'affiliate-wp' ),
						[
							'id'            => 'settings-marketing-affiliatewp-referrals_disabled',
							'name'          => 'settings__marketing__affiliatewp__referrals_disabled',
							'checked_value' => '1',
							'field_id'      => 'referrals_disabled',
						]
					);

					// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				endif;
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if referrals should be created for this donation.
	 *
	 * @param int $donation_id The donation ID.
	 * @return bool True if referrals should be created, false otherwise.
	 * @access  public
	 * @since   2.30.0
	 */
	public function should_create_referral( $donation_id ) {

		$donation = charitable_get_donation( $donation_id );

		if ( ! $donation ) {
			return false;
		}

		// Check if any campaigns have referrals disabled.
		$campaign_donations = $donation->get_campaign_donations();

		foreach ( $campaign_donations as $campaign_donation ) {
			$disabled = get_post_meta( $campaign_donation->campaign_id, '_affwp_charitable_referrals_disabled', true );

			if ( $disabled ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Retrieves the customer details for a donation.
	 *
	 * @param int $donation_id The ID of the donation to retrieve customer details for.
	 *                        If no donation ID is provided, the customer details will be retrieved from the current donation.
	 * @return array An array of the customer details. Array keys include: user_id, email, first_name, last_name, and ip.
	 * @access  public
	 * @since   2.30.0
	 */
	public function get_customer( $donation_id = 0 ) {

		$customer = [];

		$donation = charitable_get_donation( $donation_id );

		if ( ! $donation ) {
			return $customer;
		}

		$donor = $donation->get_donor();

		if ( ! $donor ) {
			return $customer;
		}

		$user                   = $donor->get_user();
		$customer['user_id']    = $user ? $user->ID : 0;
		$customer['email']      = $donor->get_email();
		$customer['first_name'] = $donor->get_donor_meta( 'first_name' );
		$customer['last_name']  = $donor->get_donor_meta( 'last_name' );
		$customer['ip']         = affiliate_wp()->tracking->get_ip();

		return $customer;
	}

	/**
	 * Retrieves the visit ID.
	 *
	 * @return int The visit ID.
	 * @access  public
	 * @since   2.30.0
	 */
	public function get_visit_id() {
		return affiliate_wp()->tracking->get_visit_id();
	}
}
