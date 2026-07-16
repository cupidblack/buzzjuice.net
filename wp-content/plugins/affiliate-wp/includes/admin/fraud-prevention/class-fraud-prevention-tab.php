<?php
/**
 * Admin: Fraud Prevention Tab
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Fraud Prevention
 * @copyright   Copyright (c) 2025, Awesome Motive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.31.0
 */

namespace AffiliateWP\Admin\Fraud_Prevention;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets up the Fraud Prevention tab with modern UI.
 *
 * @since 2.31.0
 */
class Fraud_Prevention_Tab {

	/**
	 * Constructor.
	 *
	 * @since 2.31.0
	 */
	public function __construct() {
		// Register the fraud-prevention tab.
		add_filter( 'affwp_settings_tabs', [ $this, 'register_tab' ], 10 );

		// Hook to display custom content for fraud-prevention tab.
		add_action( 'affwp_settings_tab_fraud-prevention_content', [ $this, 'render_content' ] );

		// Add sanitization filter.
		add_filter( 'affwp_settings_fraud-prevention_sanitize', [ $this, 'sanitize_settings' ] );

		// Add flag icons to ID columns.
		add_filter( 'affwp_referral_table_referral_id', [ $this, 'add_flag_icon_to_referral_column' ], 20, 2 );
		add_filter( 'affwp_visit_table_visit_id', [ $this, 'add_flag_icon_to_visit_column' ], 20, 2 );
		add_filter( 'affwp_affiliate_table_name', [ $this, 'add_flag_icon_to_affiliate_column' ], 20, 2 );

		// Add tooltip to status badge for flagged referrals.
		add_filter( 'affwp_referral_table_status', [ $this, 'add_tooltip_to_status_badge' ], 20, 2 );

		// Add fraud alert banner and dropdown to edit referral screen.
		add_action( 'affwp_edit_referral_top', [ $this, 'display_fraud_alert_banner' ], 10, 1 );
		add_action( 'affwp_edit_referral_bottom', [ $this, 'display_flag_dropdown' ], 10, 1 );

		// Add fraud alerts to affiliate review screen.
		add_action( 'affwp_review_affiliate_top', [ $this, 'display_review_fraud_banner' ], 10, 1 );
		add_action( 'affwp_review_affiliate_end', [ $this, 'display_review_fraud_details' ], 10, 1 );
	}

	/**
	 * Cached fraud summary for the affiliate review page.
	 *
	 * Keyed by affiliate_id. Prevents duplicate queries when both
	 * the banner and details methods run for the same affiliate.
	 *
	 * @since 2.31.0
	 *
	 * @var array
	 */
	private $review_fraud_summaries = [];

	/**
	 * Gather all fraud data for an affiliate in a single pass.
	 *
	 * Used on the affiliate review page to display fraud alerts.
	 *
	 * @since 2.31.0
	 *
	 * @param int $affiliate_id Affiliate ID.
	 * @return array {
	 *     Fraud summary data.
	 *
	 *     @type bool   $has_alerts          Whether any fraud alerts exist.
	 *     @type bool   $ip_velocity_flagged Whether the affiliate has an IP velocity flag.
	 *     @type array  $ip_velocity_data    Decoded IP velocity metadata, or null.
	 *     @type string $registration_ip     The affiliate's registration IP.
	 *     @type array  $sibling_affiliates  Other affiliates from the same IP (id, name, status).
	 *     @type int    $rejected_count      Number of rejected referrals.
	 *     @type float  $rejected_amount     Total amount of rejected referrals.
	 *     @type int    $flagged_count       Number of flagged (pending with flag) referrals.
	 *     @type float  $flagged_amount      Total amount of flagged referrals.
	 *     @type array  $flag_breakdown      Counts per flag type.
	 * }
	 */
	private function get_affiliate_fraud_summary( $affiliate_id ) {
		// Return cached result if available.
		if ( isset( $this->review_fraud_summaries[ $affiliate_id ] ) ) {
			return $this->review_fraud_summaries[ $affiliate_id ];
		}

		$summary = [
			'has_alerts'          => false,
			'ip_velocity_flagged' => false,
			'ip_velocity_data'    => null,
			'registration_ip'     => '',
			'sibling_affiliates'  => [],
			'rejected_count'      => 0,
			'rejected_amount'     => 0.0,
			'flagged_count'       => 0,
			'flagged_amount'      => 0.0,
			'flag_breakdown'      => [],
		];

		// Check IP velocity flag.
		$ip_velocity_flag = affwp_get_affiliate_meta( $affiliate_id, 'ip_velocity_flag', true );

		if ( ! empty( $ip_velocity_flag ) ) {
			$summary['ip_velocity_flagged'] = true;
			$summary['has_alerts']          = true;

			$ip_data_json = affwp_get_affiliate_meta( $affiliate_id, 'ip_velocity_data', true );

			if ( ! empty( $ip_data_json ) ) {
				$summary['ip_velocity_data'] = json_decode( $ip_data_json, true );
			}

			// Get registration IP and sibling affiliates.
			$registration_ip = affwp_get_affiliate_meta( $affiliate_id, 'registration_ip', true );

			if ( ! empty( $registration_ip ) ) {
				$summary['registration_ip'] = $registration_ip;

				// Find other affiliates registered from the same IP.
				global $wpdb;

				$affiliate_meta_table = affiliate_wp()->affiliate_meta->table_name;
				$affiliates_table     = affiliate_wp()->affiliates->table_name;

				$sibling_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT am.affiliate_id
						FROM {$affiliate_meta_table} am
						INNER JOIN {$affiliates_table} a ON am.affiliate_id = a.affiliate_id
						WHERE am.meta_key = 'registration_ip'
						AND am.meta_value = %s
						AND am.affiliate_id != %d
						ORDER BY a.date_registered DESC
						LIMIT 10",
						$registration_ip,
						$affiliate_id
					)
				);

				foreach ( $sibling_ids as $sibling_id ) {
					$sibling = affwp_get_affiliate( $sibling_id );
					$name    = affiliate_wp()->affiliates->get_affiliate_name( $sibling_id );

					if ( $name && $sibling ) {
						$summary['sibling_affiliates'][] = [
							'affiliate_id' => intval( $sibling_id ),
							'name'         => $name,
							'status'       => $sibling->status,
						];
					}
				}
			}
		}

		// Query referral stats in a single SQL query.
		global $wpdb;

		$referrals_table = affiliate_wp()->referrals->table_name;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, flag, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount
				FROM {$referrals_table}
				WHERE affiliate_id = %d
				AND (
					status = 'rejected'
					OR (status = 'pending' AND flag != '' AND flag IS NOT NULL)
				)
				GROUP BY status, flag",
				$affiliate_id
			)
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				$count  = intval( $row->count );
				$amount = floatval( $row->total_amount );

				if ( 'rejected' === $row->status ) {
					$summary['rejected_count']  += $count;
					$summary['rejected_amount'] += $amount;
				} else {
					// Pending with a flag = flagged referral.
					$summary['flagged_count']  += $count;
					$summary['flagged_amount'] += $amount;
				}

				// Track flag breakdown.
				if ( ! empty( $row->flag ) ) {
					if ( ! isset( $summary['flag_breakdown'][ $row->flag ] ) ) {
						$summary['flag_breakdown'][ $row->flag ] = 0;
					}
					$summary['flag_breakdown'][ $row->flag ] += $count;
				}
			}

			if ( $summary['rejected_count'] > 0 || $summary['flagged_count'] > 0 ) {
				$summary['has_alerts'] = true;
			}
		}

		$this->review_fraud_summaries[ $affiliate_id ] = $summary;

		return $summary;
	}

	/**
	 * Register the fraud-prevention tab.
	 *
	 * @since 2.31.0
	 *
	 * @param array $tabs Existing tabs.
	 * @return array Modified tabs array.
	 */
	public function register_tab( $tabs ) {
		// Insert fraud-prevention tab after emails tab.
		$new_tabs = [];
		foreach ( $tabs as $key => $label ) {
			$new_tabs[ $key ] = $label;
			if ( 'emails' === $key ) {
				$new_tabs['fraud-prevention'] = __( 'Anti-Fraud', 'affiliate-wp' );
			}
		}

		// If emails tab doesn't exist, just append it.
		if ( ! isset( $new_tabs['fraud-prevention'] ) ) {
			$new_tabs['fraud-prevention'] = __( 'Anti-Fraud', 'affiliate-wp' );
		}

		return $new_tabs;
	}

	/**
	 * Render the fraud prevention tab content.
	 *
	 * @since 2.31.0
	 */
	public function render_content() {
		// Include the view file.
		include __DIR__ . '/views/fraud-prevention-tab.php';
	}

	/**
	 * Get fraud prevention feature configurations.
	 *
	 * @since 2.31.0
	 *
	 * @return array Feature configurations grouped by category.
	 */
	public function get_features() {
		$features = [];

		// Self-Referral Protection (Available to all license tiers).
		$features['self_referral'] = [
			'title'            => __( 'Self-Referral Protection', 'affiliate-wp' ),
			'description'      => __( 'Detects when a logged-in user is an affiliate or when customer email matches affiliate email', 'affiliate-wp' ),
			'required_license' => 'personal', // Available to all tiers (personal, plus, pro)
			'settings'         => [
				[
					'id'                  => 'fraud_prevention_self_referrals',
					'name'                => __( 'Self-Referrals', 'affiliate-wp' ),
					'description'         => __( 'Controls how self-referrals are handled. This applies to both logged-in affiliates (checked during visit tracking) and logged-out customers whose email matches an affiliate (checked during referral creation).', 'affiliate-wp' ),
					'type'                => 'radio',
					'options'             => [
						'allow'  => __( 'Allow', 'affiliate-wp' ),
						'flag'   => __( 'Flag', 'affiliate-wp' ),
						'reject' => __( 'Reject', 'affiliate-wp' ),
					],
					'option_descriptions' => [
						'allow'  => __( 'Visits and referrals are created normally, even for self-referrals.', 'affiliate-wp' ),
						'flag'   => __( 'Logged-in affiliates can generate visits and referrals which are flagged for review but still eligible for payout. Logged-out customers whose email matches an affiliate will also have referrals flagged for review but remain eligible for payout.', 'affiliate-wp' ),
						'reject' => __( 'Logged-in affiliates cannot generate visits. Logged-out customers whose email matches an affiliate will have referrals created with rejected status.', 'affiliate-wp' ),
					],
					'default'             => 'reject',
				],
			],
		];

		// Referring Sites Detection (PRO only, conditional).
		if ( affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {
			$features['referring_sites'] = [
				'title'            => __( 'Referring Sites Detection', 'affiliate-wp' ),
				'description'      => __( 'Verify that visits come from URLs registered in affiliate applications', 'affiliate-wp' ),
				'required_license' => 'pro',
				'settings'         => [
					[
						'id'                  => 'fraud_prevention_referring_sites',
						'name'                => __( 'Referring Sites', 'affiliate-wp' ),
						'description'         => __( 'The referring site is checked against the URL in the affiliate\'s application.', 'affiliate-wp' ),
						'type'                => 'radio',
						'options'             => [
							'allow'  => __( 'Allow', 'affiliate-wp' ),
							'flag'   => __( 'Flag', 'affiliate-wp' ),
							'reject' => __( 'Reject', 'affiliate-wp' ),
						],
						'option_descriptions' => [
							'allow'  => __( 'Visits and referrals are allowed from any referring site.', 'affiliate-wp' ),
							'flag'   => __( 'Visits and referrals are recorded but flagged if the referring site doesn\'t match.', 'affiliate-wp' ),
							'reject' => __( 'Visits and referrals are not recorded if the referring site doesn\'t match.', 'affiliate-wp' ),
						],
						'default'             => 'allow',
					],
				],
			];
		}

		// Conversion Rate Detection (PRO only).
		$features['conversion_rate'] = [
			'title'            => __( 'Conversion Rate Detection', 'affiliate-wp' ),
			'description'      => __( 'Flag affiliates with abnormally high or low conversion rates', 'affiliate-wp' ),
			'required_license' => 'pro',
			'settings'         => [
				[
					'id'                  => 'fraud_prevention_conversion_rate',
					'name'                => __( 'Conversion Rate', 'affiliate-wp' ),
					'description'         => __( 'Checks if an affiliate\'s conversion rate (referrals to visits ratio) is within the defined limits. The affiliate must have at least 10 referrals before this is detected.', 'affiliate-wp' ),
					'type'                => 'radio',
					'options'             => [
						'allow' => __( 'Allow', 'affiliate-wp' ),
						'flag'  => __( 'Flag Visits & Referrals', 'affiliate-wp' ),
					],
					'option_descriptions' => [
						'allow' => __( 'Affiliates can generate referrals regardless of their conversion rate.', 'affiliate-wp' ),
						'flag'  => __( 'Visits and referrals are allowed but flagged if the affiliate\'s conversion rate is outside the defined limits.', 'affiliate-wp' ),
					],
					'default'             => 'allow',
				],
				[
					'id'          => 'fraud_prevention_min_conversion_rate',
					'name'        => __( 'Minimum Conversion Rate (%)', 'affiliate-wp' ),
					'description' => __( 'Minimum acceptable conversion rate percentage.', 'affiliate-wp' ),
					'type'        => 'number',
					'default'     => 2,
					'step'        => 0.01,
					'min'         => 0,
					'max'         => 100,
				],
				[
					'id'          => 'fraud_prevention_max_conversion_rate',
					'name'        => __( 'Maximum Conversion Rate (%)', 'affiliate-wp' ),
					'description' => __( 'Maximum acceptable conversion rate percentage.', 'affiliate-wp' ),
					'type'        => 'number',
					'default'     => 20,
					'step'        => 0.01,
					'min'         => 0,
					'max'         => 100,
				],
			],
		];

		// PPC Traffic Detection (PRO only).
		$features['ppc_detection'] = [
			'title'            => __( 'PPC Traffic Detection', 'affiliate-wp' ),
			'description'      => __( 'Detect and flag visits and referrals from paid advertising traffic', 'affiliate-wp' ),
			'required_license' => 'pro',
			'settings'         => [
				[
					'id'                  => 'fraud_prevention_ppc_traffic',
					'name'                => __( 'PPC Traffic', 'affiliate-wp' ),
					'description'         => __( 'Detects paid advertising traffic from Google Ads, Facebook Ads, and other platforms via click IDs (gclid, fbclid, etc.) and UTM parameters.', 'affiliate-wp' ),
					'type'                => 'radio',
					'options'             => [
						'allow'  => __( 'Allow', 'affiliate-wp' ),
						'flag'   => __( 'Flag', 'affiliate-wp' ),
						'reject' => __( 'Reject', 'affiliate-wp' ),
					],
					'option_descriptions' => [
						'allow'  => __( 'PPC traffic visits and referrals are allowed and recorded normally.', 'affiliate-wp' ),
						'flag'   => __( 'PPC traffic visits and referrals are recorded but flagged for review.', 'affiliate-wp' ),
						'reject' => __( 'PPC traffic visits are not recorded. Referrals are not created.<br>Facebook traffic is always flagged (not rejected) because it doesn\'t distinguish paid from organic visits.', 'affiliate-wp' ),
					],
					'tooltip'             => sprintf(
						/* translators: %1$s: Line break, %2$s: Opening link tag, %3$s: Closing link tag */
						__( 'Detects Google Ads, Facebook Ads, Microsoft Advertising, TikTok, Pinterest, and other ad platforms via click IDs and UTM parameters.%1$s%1$s%2$sLearn more%3$s', 'affiliate-wp' ),
						'<br>',
						'<a href="' . esc_url( affwp_utm_link( 'https://affiliatewp.com/docs/anti-fraud/#ppc-traffic-detection', 'fraud-prevention', 'PPC Traffic Detection Tooltip' ) ) . '" target="_blank" rel="noopener noreferrer" class="text-affwp-brand-600 hover:text-affwp-brand-800 underline">',
						'</a>'
					),
					'default'             => 'allow',
				],
			],
		];

		// IP Velocity Detection (PRO only).
		$require_approval    = affiliate_wp()->settings->get( 'require_approval' );
		$ip_velocity_options = [
			'allow'            => __( 'Allow', 'affiliate-wp' ),
			'flag'             => __( 'Flag', 'affiliate-wp' ),
			'require_approval' => __( 'Require Approval', 'affiliate-wp' ),
		];
		$ip_velocity_descriptions = [
			'allow'  => $require_approval
				? __( 'No additional action. All affiliates already require manual approval.', 'affiliate-wp' )
				: __( 'Affiliate registrations are allowed regardless of IP address history.', 'affiliate-wp' ),
			'flag'   => __( 'Affiliate registrations are allowed but flagged for review if velocity threshold is exceeded.', 'affiliate-wp' ),
			'require_approval' => __( 'Affiliates are set to pending status for manual approval if velocity threshold is exceeded.', 'affiliate-wp' ),
		];

		$features['ip_velocity'] = [
			'title'               => __( 'IP Velocity Detection', 'affiliate-wp' ),
			'description'         => __( 'Detect multiple affiliate registrations from the same IP address', 'affiliate-wp' ),
			'required_license'    => 'pro',
			'ip_logging_disabled'      => affiliate_wp()->settings->get( 'disable_ip_logging' ),
			'require_approval_global'  => (bool) $require_approval,
			'settings'         => [
				[
					'id'                  => 'fraud_prevention_ip_velocity',
					'name'                => __( 'IP Velocity', 'affiliate-wp' ),
					'description'         => __( 'Detects when multiple affiliate accounts are registered from the same IP address within a specified time window.', 'affiliate-wp' ),
					'type'                => 'radio',
					'options'             => $ip_velocity_options,
					'option_descriptions' => $ip_velocity_descriptions,
					'tooltip'             => sprintf(
						/* translators: %1$s: Line break, %2$s: Opening link tag, %3$s: Closing link tag */
						__( 'Requires IP logging to be enabled. If the "Disable IP Address Logging" setting in the Advanced tab is on, this check will not function.%1$s%1$sMultiple registrations from the same IP may occur in shared offices, universities, or households.%1$s%1$s%2$sLearn more%3$s', 'affiliate-wp' ),
						'<br>',
						'<a href="' . esc_url( affwp_utm_link( 'https://affiliatewp.com/docs/anti-fraud/#ip-velocity-detection', 'fraud-prevention', 'IP Velocity Detection Tooltip' ) ) . '" target="_blank" rel="noopener noreferrer" class="text-affwp-brand-600 hover:text-affwp-brand-800 underline">',
						'</a>'
					),
					'default'             => 'allow',
				],
				[
					'id'          => 'fraud_prevention_ip_velocity_threshold',
					'name'        => __( 'Registration Threshold', 'affiliate-wp' ),
					'description' => __( 'Maximum number of affiliate registrations allowed from the same IP before triggering detection.', 'affiliate-wp' ),
					'type'        => 'number',
					'default'     => 3,
					'step'        => 1,
					'min'         => 2,
					'max'         => 100,
				],
				[
					'id'          => 'fraud_prevention_ip_velocity_window',
					'name'        => __( 'Time Window (hours)', 'affiliate-wp' ),
					'description' => __( 'The time window in hours to check for multiple registrations.', 'affiliate-wp' ),
					'type'        => 'number',
					'default'     => 24,
					'step'        => 1,
					'min'         => 1,
					'max'         => 720,
				],
			],
		];

		// Blocked Referring Sites (PERSONAL+ tier).
		$features['blocked_referring_sites'] = [
			'title'            => __( 'Blocked Referring Sites', 'affiliate-wp' ),
			'description'      => __( 'Block visits from specific referring websites.', 'affiliate-wp' ),
			'required_license' => 'personal', // Available to all tiers (personal, plus, pro)
			'settings'         => [
				[
					'id'          => 'referral_url_blacklist',
					'name'        => __( 'Blocked Sites', 'affiliate-wp' ),
					'description' => __( 'Block visits from specific referring websites. Enter one URL per line. This checks the document.referrer (where the visitor came from) and blocks the visit entirely if it matches.', 'affiliate-wp' ),
					'type'        => 'textarea',
					'tooltip'     => __( 'Do not add ad platforms (google.com, facebook.com, etc.) here — use PPC Traffic Detection instead.', 'affiliate-wp' ),
				],
			],
		];

		return apply_filters( 'affwp_fraud_prevention_features', $features );
	}

	/**
	 * Add flag icon to referral ID column.
	 *
	 * Only shows for non-rejected referrals. Rejected referrals show the reason
	 * via the status badge tooltip instead, since they don't need review.
	 *
	 * @since 2.31.0
	 *
	 * @param string          $value    Column value.
	 * @param \AffWP\Referral $referral Referral object.
	 * @return string Modified column value.
	 */
	public function add_flag_icon_to_referral_column( $value, $referral ) {
		// Don't show flag icon if no flag or if referral is rejected.
		// Rejected referrals show the reason via status badge tooltip instead.
		if ( empty( $referral->flag ) || 'rejected' === $referral->status ) {
			return $value;
		}

		// Get tooltip data for the flag type.
		$tooltip_data = $this->get_flag_tooltip_data( $referral->flag, 'referral', $referral->referral_id );

		// Generate tooltip using CORE function with rich format for better styling.
		$tooltip_html = affwp_tooltip( $tooltip_data );

		// Flag icon using core Icons utility.
		$flag_icon = \AffiliateWP\Utils\Icons::get(
			'flag',
			[
				'class'             => 'w-4 h-4 text-red-500 ml-1 cursor-help inline-block',
				'data-tooltip-html' => $tooltip_html,
			]
		);

		return $value . $flag_icon;
	}

	/**
	 * Add flag icon to visit ID column.
	 *
	 * @since 2.31.0
	 *
	 * @param string       $value Column value.
	 * @param \AffWP\Visit $visit  Visit object.
	 * @return string Modified column value.
	 */
	public function add_flag_icon_to_visit_column( $value, $visit ) {
		if ( empty( $visit->flag ) ) {
			return $value;
		}

		// Get tooltip data for the flag type.
		$tooltip_data = $this->get_flag_tooltip_data( $visit->flag, 'visit', $visit->visit_id );

		// Generate tooltip using CORE function with rich format for better styling.
		$tooltip_html = affwp_tooltip( $tooltip_data );

		// Flag icon using core Icons utility.
		$flag_icon = \AffiliateWP\Utils\Icons::get(
			'flag',
			[
				'class'             => 'w-4 h-4 text-red-500 ml-1 cursor-help inline-block',
				'data-tooltip-html' => $tooltip_html,
			]
		);

		return $value . $flag_icon;
	}

	/**
	 * Add flag icon to affiliate name column for IP velocity flagged affiliates.
	 *
	 * @since 2.31.0
	 *
	 * @param string          $value     Column value.
	 * @param \Affwp\Affiliate $affiliate Affiliate object.
	 * @return string Modified column value.
	 */
	public function add_flag_icon_to_affiliate_column( $value, $affiliate ) {
		// Check for IP velocity flag in affiliate meta.
		$ip_velocity_flag = affwp_get_affiliate_meta( $affiliate->affiliate_id, 'ip_velocity_flag', true );

		if ( empty( $ip_velocity_flag ) ) {
			return $value;
		}

		// Build tooltip content.
		$tooltip_data = $this->get_flag_tooltip_data( 'ip_velocity', 'affiliate', $affiliate->affiliate_id );

		// Generate tooltip using CORE function.
		$tooltip_html = affwp_tooltip( $tooltip_data );

		// Flag icon using core Icons utility with red fill (consistent with other flags).
		$flag_icon = \AffiliateWP\Utils\Icons::get(
			'flag',
			[
				'class'             => 'affwp-ip-velocity-flag',
				'width'             => '16',
				'height'            => '16',
				'data-tooltip-html' => $tooltip_html,
			]
		);

		// Wrap in span for proper inline alignment with styles.
		$flag_icon = sprintf(
			'<span style="display:inline-flex;vertical-align:middle;margin-left:4px;cursor:help;">%s</span>',
			$flag_icon
		);

		// Insert the flag icon after the name link, inside the info div.
		// The structure is: <div class="info">...<a class="name">Name</a></div>
		$value = preg_replace(
			'/(<a\s+class="name"[^>]*>[^<]*<\/a>)/',
			'$1' . $flag_icon,
			$value,
			1
		);

		return $value;
	}

	/**
	 * Add tooltip to status badge for flagged referrals.
	 *
	 * Shows the flag reason when hovering over the status badge, providing
	 * admins with immediate context about why a referral was flagged or rejected.
	 *
	 * @since 2.31.0
	 *
	 * @param string          $value    Status badge HTML.
	 * @param \AffWP\Referral $referral Referral object.
	 * @return string Modified status badge HTML with tooltip.
	 */
	public function add_tooltip_to_status_badge( $value, $referral ) {
		if ( empty( $referral->flag ) ) {
			return $value;
		}

		// Get tooltip data for the flag type.
		// Pass status to use correct wording (Rejected vs Flagged).
		$tooltip_data = $this->get_flag_tooltip_data( $referral->flag, 'referral', $referral->referral_id, $referral->status );

		// Generate tooltip content using CORE function with rich format.
		$tooltip_html = affwp_tooltip( $tooltip_data );

		// Add data-tooltip-html attribute directly to the badge span element.
		// The badge HTML starts with <span, so we inject the tooltip attribute there.
		$value = preg_replace(
			'/<span\s+class="/',
			'<span data-tooltip-html="' . esc_attr( $tooltip_html ) . '" style="cursor: help;" class="',
			$value,
			1 // Only replace the first occurrence.
		);

		return $value;
	}

	/**
	 * Get human-readable flag description.
	 *
	 * @since 2.31.0
	 *
	 * @param string $flag_type Flag type.
	 * @param string $context   Context ('referral' or 'visit').
	 * @param int    $object_id Optional. Visit or referral ID for fetching metadata.
	 * @param string $status    Optional. Referral status to determine wording (Rejected vs Flagged).
	 * @return string Flag description.
	 */
	/**
	 * Get tooltip data array for a flag type.
	 *
	 * Returns structured data for rich tooltip display with title, content, type, and optional items.
	 *
	 * @since 2.31.0
	 *
	 * @param string $flag_type Flag type.
	 * @param string $context   Context ('referral' or 'visit').
	 * @param int    $object_id Optional. Visit or referral ID for fetching metadata.
	 * @param string $status    Optional. Referral status to determine wording (Rejected vs Flagged).
	 * @return array Tooltip configuration array for affwp_tooltip().
	 */
	private function get_flag_tooltip_data( $flag_type, $context = 'referral', $object_id = 0, $status = '' ) {
		// Use "Rejected" for rejected referrals, "Flagged" for others.
		$is_rejected = 'rejected' === $status;

		// Define titles for each flag type.
		$titles = [
			'self_referral'   => $is_rejected ? __( 'Self-Referral Rejected', 'affiliate-wp' ) : __( 'Self-Referral Detected', 'affiliate-wp' ),
			'ppc_traffic'     => $is_rejected ? __( 'PPC Traffic Rejected', 'affiliate-wp' ) : __( 'PPC Traffic Detected', 'affiliate-wp' ),
			'conversion_rate' => $is_rejected ? __( 'Conversion Rate Rejected', 'affiliate-wp' ) : __( 'Conversion Rate Alert', 'affiliate-wp' ),
			'referring_site'  => $is_rejected ? __( 'Referring Site Rejected', 'affiliate-wp' ) : __( 'Referring Site Mismatch', 'affiliate-wp' ),
			'ip_velocity'     => __( 'IP Velocity Alert', 'affiliate-wp' ),
		];

		// Define content descriptions for each flag type.
		$descriptions = [
			'self_referral'   => 'referral' === $context
				? __( 'The customer email matches the affiliate\'s email address.', 'affiliate-wp' )
				: __( 'The visitor used their own affiliate link.', 'affiliate-wp' ),
			'ppc_traffic'     => __( 'This originated from paid advertising traffic.', 'affiliate-wp' ),
			'conversion_rate' => __( 'The affiliate\'s conversion rate is outside defined limits.', 'affiliate-wp' ),
			'referring_site'  => __( 'The referring site doesn\'t match the affiliate\'s registered URL.', 'affiliate-wp' ),
			'ip_velocity'     => __( 'Multiple affiliates registered from the same IP address in a short time period.', 'affiliate-wp' ),
		];

		$title   = isset( $titles[ $flag_type ] ) ? $titles[ $flag_type ] : __( 'Fraud Alert', 'affiliate-wp' );
		$content = isset( $descriptions[ $flag_type ] ) ? $descriptions[ $flag_type ] : $flag_type;

		// Build tooltip data array.
		$tooltip_data = [
			'title'   => $title,
			'content' => $content,
			'type'    => 'warning',
		];

		// Add PPC-specific metadata as list items if available.
		if ( 'ppc_traffic' === $flag_type && ! empty( $object_id ) ) {
			$ppc_data = null;

			// For referrals, try stored metadata first.
			if ( 'referral' === $context ) {
				$ppc_signals = affwp_get_referral_meta( $object_id, 'ppc_signals', true );
				if ( ! empty( $ppc_signals ) ) {
					$ppc_data = json_decode( $ppc_signals, true );
				}

				// Fallback for existing referrals without metadata: re-detect from visit URL.
				if ( empty( $ppc_data['platform'] ) ) {
					$referral = affwp_get_referral( $object_id );
					if ( $referral && ! empty( $referral->visit_id ) ) {
						$ppc_data = $this->detect_ppc_from_visit( $referral->visit_id );
					}
				}
			} elseif ( 'visit' === $context ) {
				// Visits don't have metadata — re-detect from the stored URL.
				$ppc_data = $this->detect_ppc_from_visit( $object_id );
			}

			if ( ! empty( $ppc_data['platform'] ) ) {
				$tooltip_data['items'] = [
					sprintf( '%s: %s', __( 'Platform', 'affiliate-wp' ), esc_html( $ppc_data['platform'] ) ),
					sprintf( '%s: %s', __( 'Method', 'affiliate-wp' ), esc_html( $ppc_data['method'] ) ),
				];
			}

		}

		// Add IP velocity metadata as list items if available.
		if ( 'ip_velocity' === $flag_type && 'affiliate' === $context && ! empty( $object_id ) ) {
			$ip_data = affwp_get_affiliate_meta( $object_id, 'ip_velocity_data', true );

			if ( ! empty( $ip_data ) ) {
				$data = json_decode( $ip_data, true );
				if ( ! empty( $data['count'] ) ) {
					$tooltip_data['items'] = [
						sprintf(
							/* translators: %d: number of registrations from same IP */
							__( 'Registrations from IP: %d', 'affiliate-wp' ),
							intval( $data['count'] )
						),
						sprintf(
							/* translators: %d: threshold number */
							__( 'Threshold: %d', 'affiliate-wp' ),
							intval( $data['threshold'] )
						),
						sprintf(
							/* translators: %d: number of hours */
							__( 'Time window: %d hours', 'affiliate-wp' ),
							intval( $data['window_hours'] )
						),
					];
				}
			}
		}

		return $tooltip_data;
	}

	/**
	 * Re-detect PPC platform from a visit's stored URL and referrer.
	 *
	 * Used as a fallback when PPC signals metadata is not available (e.g., visits
	 * don't have metadata, or referrals created before signals were persisted).
	 *
	 * @since 2.31.2
	 *
	 * @param int $visit_id Visit ID.
	 * @return array|null PPC detection result array with 'platform' and 'method', or null.
	 */
	private function detect_ppc_from_visit( $visit_id ) {
		$visit = affwp_get_visit( $visit_id );
		if ( ! $visit ) {
			return null;
		}

		$ppc_detection = new \AffiliateWP\Fraud_Prevention\Checks\PPC_Detection();

		// Try the landing URL first (most common case).
		if ( ! empty( $visit->url ) ) {
			$result = $ppc_detection->detect( $visit->url );
			if ( $result['is_ppc'] ) {
				return $result;
			}
		}

		// Fallback: try the referrer (PPC may have been detected from referrer URL).
		if ( ! empty( $visit->referrer ) ) {
			$result = $ppc_detection->detect( $visit->referrer );
			if ( $result['is_ppc'] ) {
				return $result;
			}
		}

		return null;
	}

	/**
	 * Get human-readable flag description (legacy format).
	 *
	 * @since 2.31.0
	 * @deprecated 2.31.0 Use get_flag_tooltip_data() for rich tooltips.
	 *
	 * @param string $flag_type Flag type.
	 * @param string $context   Context ('referral' or 'visit').
	 * @param int    $object_id Optional. Visit or referral ID for fetching metadata.
	 * @param string $status    Optional. Referral status to determine wording (Rejected vs Flagged).
	 * @return string Flag description.
	 */
	private function get_flag_description( $flag_type, $context = 'referral', $object_id = 0, $status = '' ) {
		// Use "Rejected:" for rejected referrals, "Flagged:" for others.
		$is_rejected = 'rejected' === $status;
		$prefix      = $is_rejected ? __( 'Rejected', 'affiliate-wp' ) : __( 'Flagged', 'affiliate-wp' );

		$descriptions = [
			'self_referral'   => 'referral' === $context
				/* translators: %s: Rejected or Flagged */
				? sprintf( __( '%s: Customer used their own affiliate link', 'affiliate-wp' ), $prefix )
				/* translators: %s: Rejected or Flagged */
				: sprintf( __( '%s: Visitor used their own affiliate link', 'affiliate-wp' ), $prefix ),
			/* translators: %s: Rejected or Flagged */
			'referring_site'  => sprintf( __( '%s: Referring site doesn\'t match affiliate\'s registered URL', 'affiliate-wp' ), $prefix ),
			/* translators: %s: Rejected or Flagged */
			'conversion_rate' => sprintf( __( '%s: Conversion rate outside defined limits', 'affiliate-wp' ), $prefix ),
			/* translators: %s: Rejected or Flagged */
			'ppc_traffic'     => sprintf( __( '%s: Originated from paid advertising traffic', 'affiliate-wp' ), $prefix ),
		];

		$description = isset( $descriptions[ $flag_type ] ) ? $descriptions[ $flag_type ] : $flag_type;

		// Add PPC-specific metadata if available.
		if ( 'ppc_traffic' === $flag_type && ! empty( $object_id ) ) {
			$ppc_data = null;

			if ( 'referral' === $context ) {
				$ppc_signals = affwp_get_referral_meta( $object_id, 'ppc_signals', true );
				if ( ! empty( $ppc_signals ) ) {
					$ppc_data = json_decode( $ppc_signals, true );
				}

				// Fallback for existing referrals without metadata: re-detect from visit URL.
				if ( empty( $ppc_data['platform'] ) ) {
					$referral = affwp_get_referral( $object_id );
					if ( $referral && ! empty( $referral->visit_id ) ) {
						$ppc_data = $this->detect_ppc_from_visit( $referral->visit_id );
					}
				}
			} elseif ( 'visit' === $context ) {
				$ppc_data = $this->detect_ppc_from_visit( $object_id );
			}

			if ( ! empty( $ppc_data['platform'] ) ) {
				$description .= sprintf(
					'<br><br><strong>%s:</strong> %s<br><strong>%s:</strong> %s',
					__( 'Platform', 'affiliate-wp' ),
					esc_html( $ppc_data['platform'] ),
					__( 'Detection Method', 'affiliate-wp' ),
					esc_html( $ppc_data['method'] )
				);
			}
		}

		return $description;
	}

	/**
	 * Get the alert banner message for a fraud alert type.
	 *
	 * @since 2.31.0
	 *
	 * @param string $flag_type The fraud alert type.
	 * @param string $status    The referral status to determine wording (rejected vs flagged).
	 * @return string The banner message.
	 */
	private function get_fraud_alert_message( $flag_type, $status = '' ) {
		// Use "rejected" for rejected referrals, "flagged" for others.
		$is_rejected = 'rejected' === $status;
		$action_word = $is_rejected ? __( 'rejected', 'affiliate-wp' ) : __( 'flagged', 'affiliate-wp' );

		$messages = [
			/* translators: %s: rejected or flagged */
			'self_referral'   => sprintf( __( 'This referral was %s because the customer email matches the affiliate\'s email address.', 'affiliate-wp' ), $action_word ),
			/* translators: %s: rejected or flagged */
			'ppc_traffic'     => sprintf( __( 'This referral was %s because it originated from paid advertising traffic.', 'affiliate-wp' ), $action_word ),
			/* translators: %s: rejected or flagged */
			'conversion_rate' => sprintf( __( 'This referral was %s because the affiliate\'s conversion rate is outside defined limits.', 'affiliate-wp' ), $action_word ),
			/* translators: %s: rejected or flagged */
			'referring_site'  => sprintf( __( 'This referral was %s because the referring site doesn\'t match the affiliate\'s registered URL.', 'affiliate-wp' ), $action_word ),
		];

		return isset( $messages[ $flag_type ] ) ? $messages[ $flag_type ] : '';
	}

	/**
	 * Display fraud alert banner on edit referral screen.
	 *
	 * Shows a visual alert at the top of the edit form when a referral has a fraud alert.
	 *
	 * @since 2.31.0
	 *
	 * @param \AffWP\Referral $referral Referral object.
	 */
	public function display_fraud_alert_banner( $referral ) {
		if ( empty( $referral->flag ) ) {
			return;
		}

		$flag_labels = [
			'self_referral'   => __( 'Self-Referral', 'affiliate-wp' ),
			'ppc_traffic'     => __( 'PPC Traffic', 'affiliate-wp' ),
			'conversion_rate' => __( 'Conversion Rate', 'affiliate-wp' ),
			'referring_site'  => __( 'Referring Site', 'affiliate-wp' ),
		];

		$flag_label = isset( $flag_labels[ $referral->flag ] ) ? $flag_labels[ $referral->flag ] : $referral->flag;
		$message    = $this->get_fraud_alert_message( $referral->flag, $referral->status );
		?>
		<div class="notice notice-warning" style="margin: 15px 0;">
			<p>
				<strong>
				<?php
				/* translators: %s: The fraud alert type (e.g., Self-Referral, PPC Traffic). */
				echo esc_html( sprintf( __( 'Fraud Alert: %s', 'affiliate-wp' ), $flag_label ) );
				?>
				</strong>
			</p>
			<?php if ( $message ) : ?>
				<p><?php echo esc_html( $message ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Display fraud alert dropdown on edit referral screen.
	 *
	 * @since 2.31.0
	 *
	 * @param \AffWP\Referral $referral Referral object.
	 */
	public function display_flag_dropdown( $referral ) {
		$payout   = affwp_get_payout( $referral->payout_id );
		$disabled = $payout ? 'disabled' : '';
		?>
		<table class="form-table">
			<tr class="form-row">
				<th scope="row">
					<label for="flag"><?php esc_html_e( 'Fraud Alert', 'affiliate-wp' ); ?></label>
				</th>
				<td>
					<select name="flag" id="flag" <?php echo esc_attr( $disabled ); ?> class="regular-text">
						<option value=""<?php selected( '', $referral->flag ); ?>>
							<?php esc_html_e( 'None', 'affiliate-wp' ); ?>
						</option>
						<option value="self_referral"<?php selected( 'self_referral', $referral->flag ); ?>>
							<?php esc_html_e( 'Self-Referral', 'affiliate-wp' ); ?>
						</option>
						<option value="referring_site"<?php selected( 'referring_site', $referral->flag ); ?>>
							<?php esc_html_e( 'Referring Site', 'affiliate-wp' ); ?>
						</option>
						<option value="conversion_rate"<?php selected( 'conversion_rate', $referral->flag ); ?>>
							<?php esc_html_e( 'Conversion Rate', 'affiliate-wp' ); ?>
						</option>
						<option value="ppc_traffic"<?php selected( 'ppc_traffic', $referral->flag ); ?>>
							<?php esc_html_e( 'PPC Traffic', 'affiliate-wp' ); ?>
						</option>
					</select>
					<?php if ( $payout ) : ?>
						<p class="description">
							<?php esc_html_e( 'The fraud alert cannot be changed once included in a payout.', 'affiliate-wp' ); ?>
						</p>
					<?php else : ?>
						<p class="description">
							<?php esc_html_e( 'Manually set or clear the fraud alert for this referral.', 'affiliate-wp' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Display fraud alert notice on the affiliate review screen.
	 *
	 * Hooked to `affwp_review_affiliate_top`. Renders a warning notice
	 * above the form table when fraud alerts exist, making them harder
	 * to miss during review.
	 *
	 * @since 2.31.0
	 *
	 * @param \AffWP\Affiliate $affiliate Affiliate object.
	 */
	public function display_review_fraud_banner( $affiliate ) {
		$summary = $this->get_affiliate_fraud_summary( $affiliate->affiliate_id );

		if ( ! $summary['ip_velocity_flagged'] ) {
			return;
		}

		// Build the alert lines.
		$lines = [];

		// IP Velocity line.
		$ip_data = $summary['ip_velocity_data'];
		$count   = count( $summary['sibling_affiliates'] );
		$window  = intval( $ip_data['window_hours'] ?? 24 );
		$ip      = $summary['registration_ip'];

		if ( $count > 0 && ! empty( $ip ) ) {
			$description = sprintf(
				/* translators: 1: number of accounts, 2: IP address, 3: time window in hours */
				esc_html__( '%1$d other accounts registered from IP %2$s within %3$d hours', 'affiliate-wp' ),
				$count,
				esc_html( $ip ),
				$window
			);
		}

		// Sibling affiliate links with status.
		$sibling_lines = [];
		if ( ! empty( $summary['sibling_affiliates'] ) ) {
			foreach ( $summary['sibling_affiliates'] as $sibling ) {
				$status_label = affwp_get_affiliate_status_label( $sibling['status'] );

				$sibling_lines[] = sprintf(
					'&bull; <a href="%s">%s</a> (%s)',
					esc_url( affwp_admin_url( 'affiliates', [ 'affiliate_id' => $sibling['affiliate_id'], 'action' => 'edit_affiliate' ] ) ),
					esc_html( $sibling['name'] ),
					esc_html( $status_label )
				);
			}
		}

		if ( empty( $description ) && empty( $sibling_lines ) ) {
			return;
		}

		?>
		<div class="notice notice-warning" style="margin: 15px 0;">
			<p>
				<strong><?php esc_html_e( 'Flagged for Review', 'affiliate-wp' ); ?></strong><br>
				<?php
				if ( ! empty( $description ) ) {
					echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				}
				?>
			</p>
			<?php if ( ! empty( $sibling_lines ) ) : ?>
				<p style="margin-top: 0;">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content escaped above.
					echo implode( '<br>', $sibling_lines );
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Output a hidden marker when fraud alerts exist for an affiliate.
	 *
	 * Hooked to `affwp_review_affiliate_end`. Outputs a hidden input that
	 * signals review.php to default the decision to "Reject".
	 *
	 * @since 2.31.0
	 *
	 * @param \AffWP\Affiliate $affiliate Affiliate object.
	 */
	public function display_review_fraud_details( $affiliate ) {
		$summary = $this->get_affiliate_fraud_summary( $affiliate->affiliate_id );

		if ( ! $summary['ip_velocity_flagged'] ) {
			return;
		}

		// Hidden marker used by review.php to default to Reject.
		?>
		<tr class="form-row" style="display:none;">
			<td>
				<input type="hidden" id="affwp-fraud-alert-flag" value="1">
			</td>
		</tr>
		<?php
	}

	/**
	 * Sanitize fraud prevention settings.
	 *
	 * @since 2.31.0
	 *
	 * @param array $input The input settings array.
	 * @return array The sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		// Sanitize self-referral setting.
		if ( isset( $input['fraud_prevention_self_referrals'] ) ) {
			$valid_options = [ 'allow', 'flag', 'reject' ];
			if ( ! in_array( $input['fraud_prevention_self_referrals'], $valid_options, true ) ) {
				$input['fraud_prevention_self_referrals'] = 'reject';
			}
		}

		// Sanitize referring sites setting.
		if ( isset( $input['fraud_prevention_referring_sites'] ) ) {
			$valid_options = [ 'allow', 'flag', 'reject' ];
			if ( ! in_array( $input['fraud_prevention_referring_sites'], $valid_options, true ) ) {
				$input['fraud_prevention_referring_sites'] = 'allow';
			}
		}

		// Sanitize conversion rate setting.
		if ( isset( $input['fraud_prevention_conversion_rate'] ) ) {
			$valid_options = [ 'allow', 'flag' ];
			if ( ! in_array( $input['fraud_prevention_conversion_rate'], $valid_options, true ) ) {
				$input['fraud_prevention_conversion_rate'] = 'allow';
			}
		}

		// Sanitize min conversion rate.
		if ( isset( $input['fraud_prevention_min_conversion_rate'] ) ) {
			$input['fraud_prevention_min_conversion_rate'] = floatval( $input['fraud_prevention_min_conversion_rate'] );
			$input['fraud_prevention_min_conversion_rate'] = max( 0, min( 100, $input['fraud_prevention_min_conversion_rate'] ) );
		}

		// Sanitize max conversion rate.
		if ( isset( $input['fraud_prevention_max_conversion_rate'] ) ) {
			$input['fraud_prevention_max_conversion_rate'] = floatval( $input['fraud_prevention_max_conversion_rate'] );
			$input['fraud_prevention_max_conversion_rate'] = max( 0, min( 100, $input['fraud_prevention_max_conversion_rate'] ) );
		}

		// Sanitize PPC traffic setting.
		if ( isset( $input['fraud_prevention_ppc_traffic'] ) ) {
			$valid_options = [ 'allow', 'flag', 'reject' ];
			if ( ! in_array( $input['fraud_prevention_ppc_traffic'], $valid_options, true ) ) {
				$input['fraud_prevention_ppc_traffic'] = 'allow';
			}
		}

		// Sanitize IP velocity setting.
		if ( isset( $input['fraud_prevention_ip_velocity'] ) ) {
			$valid_options = [ 'allow', 'flag', 'require_approval', 'reject' ];
			if ( ! in_array( $input['fraud_prevention_ip_velocity'], $valid_options, true ) ) {
				$input['fraud_prevention_ip_velocity'] = 'allow';
			}
		}

		// Sanitize IP velocity threshold.
		if ( isset( $input['fraud_prevention_ip_velocity_threshold'] ) ) {
			$input['fraud_prevention_ip_velocity_threshold'] = intval( $input['fraud_prevention_ip_velocity_threshold'] );
			$input['fraud_prevention_ip_velocity_threshold'] = max( 2, min( 100, $input['fraud_prevention_ip_velocity_threshold'] ) );
		}

		// Sanitize IP velocity time window.
		if ( isset( $input['fraud_prevention_ip_velocity_window'] ) ) {
			$input['fraud_prevention_ip_velocity_window'] = intval( $input['fraud_prevention_ip_velocity_window'] );
			$input['fraud_prevention_ip_velocity_window'] = max( 1, min( 720, $input['fraud_prevention_ip_velocity_window'] ) );
		}

		return $input;
	}

	/**
	 * Get upgrade modal content for a specific fraud prevention feature.
	 *
	 * @since 2.31.0
	 *
	 * @param string $feature_id Feature identifier.
	 * @return string Modal content HTML.
	 */
	public function get_upgrade_modal_content( $feature_id ) {
		$features = $this->get_features();

		if ( ! isset( $features[ $feature_id ] ) ) {
			return '';
		}

		$feature = $features[ $feature_id ];

		$upgrade_utm_medium = \AffiliateWP\Admin\Education\Non_Pro::get_utm_medium();

		ob_start();
		?>
		<div class="space-y-4">
			<p class="text-sm text-gray-700">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: Feature title */
						__( 'Protect your affiliate program from fraud with %s. This Pro feature helps you identify and prevent suspicious activity.', 'affiliate-wp' ),
						'<strong>' . esc_html( $feature['title'] ) . '</strong>'
					)
				);
				?>
			</p>

			<?php if ( 'ppc_detection' === $feature_id ) : ?>
				<ul class="space-y-2 text-sm text-gray-700">
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Detect paid advertising traffic from Google Ads, Facebook Ads, and 10+ other platforms', 'affiliate-wp' ); ?></span>
					</li>
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Automatically flag or reject referrals from PPC traffic', 'affiliate-wp' ); ?></span>
					</li>
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Prevent affiliates from violating your PPC bidding policies', 'affiliate-wp' ); ?></span>
					</li>
				</ul>
			<?php elseif ( 'conversion_rate' === $feature_id ) : ?>
				<ul class="space-y-2 text-sm text-gray-700">
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Set minimum and maximum conversion rate thresholds', 'affiliate-wp' ); ?></span>
					</li>
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Identify affiliates with suspiciously high conversion rates', 'affiliate-wp' ); ?></span>
					</li>
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Flag visits and referrals for review automatically', 'affiliate-wp' ); ?></span>
					</li>
				</ul>
			<?php elseif ( 'referring_sites' === $feature_id ) : ?>
				<ul class="space-y-2 text-sm text-gray-700">
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Verify traffic comes from affiliate\'s registered website URL', 'affiliate-wp' ); ?></span>
					</li>
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Prevent domain spoofing and unauthorized traffic sources', 'affiliate-wp' ); ?></span>
					</li>
					<li class="flex items-start gap-2">
						<?php echo \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600 flex-shrink-0 mt-0.5' ] ); ?>
						<span><?php esc_html_e( 'Flag or reject referrals from mismatched sources', 'affiliate-wp' ); ?></span>
					</li>
				</ul>
			<?php endif; ?>

			<div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
				<p class="text-sm text-blue-800">
					<strong><?php esc_html_e( 'Bonus:', 'affiliate-wp' ); ?></strong>
					<?php esc_html_e( 'AffiliateWP users get 50% off regular price, automatically applied at checkout.', 'affiliate-wp' ); ?>
				</p>
			</div>

			<p class="text-xs text-gray-500">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: Contact page URL */
						__( 'Have questions? <a href="%s" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-700">Contact our support team</a>.', 'affiliate-wp' ),
						esc_url( affwp_utm_link( 'https://affiliatewp.com/contact/', $upgrade_utm_medium, 'Anti-Fraud Support' ) )
					)
				);
				?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

}

// Initialize the class.
new Fraud_Prevention_Tab();
