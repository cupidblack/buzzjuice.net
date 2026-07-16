<?php
/**
 * Fraud Detector Core Engine
 *
 * @package     AffiliateWP
 * @subpackage  Fraud Prevention
 * @copyright   Copyright (c) 2025, Awesome Motive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.28.0
 */

namespace AffiliateWP\Fraud_Prevention;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main fraud detection engine.
 *
 * Coordinates all fraud detection checks and applies flags to referrals, visits, and affiliates.
 *
 * @since 2.31.0
 */
class Fraud_Detector {

	/**
	 * Singleton instance.
	 *
	 * @since 2.31.0
	 * @var   Fraud_Detector
	 */
	private static $instance;

	/**
	 * Self-Referral checker instance.
	 *
	 * @since 2.31.0
	 * @var   Checks\Self_Referral_Check
	 */
	public $self_referral_check;

	/**
	 * PPC detection instance.
	 *
	 * @since 2.31.0
	 * @var   Checks\PPC_Detection
	 */
	public $ppc_detection;

	/**
	 * Referring Site checker instance.
	 *
	 * @since 2.31.0
	 * @var   Checks\Referring_Site_Check
	 */
	public $referring_site_check;

	/**
	 * Conversion Rate checker instance.
	 *
	 * @since 2.31.0
	 * @var   Checks\Conversion_Rate_Check
	 */
	public $conversion_rate_check;

	/**
	 * IP Velocity checker instance.
	 *
	 * @since 2.31.0
	 * @var   Checks\IP_Velocity_Check
	 */
	public $ip_velocity_check;

	/**
	 * Get singleton instance.
	 *
	 * @since 2.31.0
	 *
	 * @return Fraud_Detector
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Fraud_Detector ) ) {
			self::$instance = new Fraud_Detector();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize fraud detection system.
	 *
	 * @since 2.31.0
	 */
	private function init() {
		// Initialize check modules.
		$this->self_referral_check   = new Checks\Self_Referral_Check();
		$this->ppc_detection         = new Checks\PPC_Detection();
		$this->referring_site_check  = new Checks\Referring_Site_Check();
		$this->conversion_rate_check = new Checks\Conversion_Rate_Check();
		$this->ip_velocity_check     = new Checks\IP_Velocity_Check();

		// Hook into affiliate registration to capture IP.
		add_action( 'affwp_insert_affiliate', [ $this, 'capture_registration_ip' ], 10, 1 );

		// IP Velocity Check - detect multiple registrations from same IP.
		// Must use affwp_add_new_affiliate (not affwp_insert_affiliate) because
		// affwp_add_affiliate() calls affwp_set_affiliate_status() AFTER the insert hook,
		// which would overwrite any status change we make.
		add_action( 'affwp_add_new_affiliate', [ $this, 'check_ip_velocity' ], 10, 1 );

		// Hook into visit tracking for PPC detection and referring site checking.
		add_filter( 'affwp_tracking_skip_track_visit', [ $this, 'check_ppc_traffic' ], 10, 4 );
		add_filter( 'affwp_tracking_skip_track_visit', [ $this, 'check_referring_site' ], 10, 4 );
		add_filter( 'affwp_pre_insert_visit_data', [ $this, 'flag_visit' ], 10, 1 );

		// Hook into referral creation for fraud flagging.
		add_filter( 'affwp_insert_pending_referral', [ $this, 'flag_referral' ], 10, 6 );

		// Hook into self-referral checking (email-based).
		add_filter( 'affwp_is_customer_email_affiliate_email', [ $this->self_referral_check, 'check_email_match' ], 20, 1 );
		add_filter( 'affwp_tracking_is_valid_affiliate', [ $this->self_referral_check, 'maybe_override_tracking' ], 20, 2 );
	}

	/**
	 * Capture affiliate registration IP address.
	 *
	 * Stores the IP address when an affiliate registers for future fraud detection.
	 *
	 * @since 2.31.0
	 *
	 * @param int $affiliate_id Affiliate ID.
	 */
	public function capture_registration_ip( $affiliate_id ) {
		$registration_ip = affwp_fraud_get_ip();

		if ( ! empty( $registration_ip ) ) {
			affwp_update_affiliate_meta( $affiliate_id, 'registration_ip', $registration_ip );
			affwp_update_affiliate_meta( $affiliate_id, 'registration_date', current_time( 'mysql' ) );
		}
	}

	/**
	 * Check IP velocity after affiliate registration.
	 *
	 * If multiple registrations from the same IP exceed the threshold,
	 * flag or set the affiliate to pending status based on settings.
	 *
	 * @since 2.31.0
	 *
	 * @param int $affiliate_id Affiliate ID.
	 */
	public function check_ip_velocity( $affiliate_id ) {
		// IP velocity is a Pro feature.
		if ( ! function_exists( 'affwp_can_access_pro_features' ) || ! affwp_can_access_pro_features() ) {
			return;
		}

		$registration_ip = affwp_get_affiliate_meta( $affiliate_id, 'registration_ip', true );

		if ( empty( $registration_ip ) ) {
			return;
		}

		$result = $this->ip_velocity_check->check( $registration_ip );

		if ( ! $result['is_violation'] ) {
			return;
		}

		// Store the IP velocity violation metadata.
		// Note: $result['count'] already includes the current registration because
		// capture_registration_ip() runs on affwp_insert_affiliate before this check
		// runs on affwp_add_new_affiliate, so count_registrations_from_ip() counts it.
		affwp_update_affiliate_meta( $affiliate_id, 'ip_velocity_flag', true );
		affwp_update_affiliate_meta(
			$affiliate_id,
			'ip_velocity_data',
			wp_json_encode(
				[
					'count'        => $result['count'],
					'threshold'    => $result['threshold'],
					'window_hours' => $result['window_hours'],
					'detected_at'  => current_time( 'mysql' ),
				]
			)
		);

		// Log the violation.
		affiliate_wp()->utils->log( $result['message'] . sprintf( ' Affiliate ID: %d', $affiliate_id ) );

		// Take action based on setting.
		if ( 'require_approval' === $result['action'] || 'reject' === $result['action'] ) {
			// Set affiliate to pending status for manual review.
			affwp_update_affiliate(
				[
					'affiliate_id' => $affiliate_id,
					'status'       => 'pending',
				]
			);

			affiliate_wp()->utils->log(
				sprintf(
					'Affiliate #%d set to pending status due to IP velocity violation.',
					$affiliate_id
				)
			);
		}
		// For 'flag' action, the metadata is already stored above.
		// Admin can see flagged affiliates via the metadata.
	}

	/**
	 * Check for PPC traffic and optionally skip visit tracking.
	 *
	 * Checks the landing page URL (from AJAX), current request parameters,
	 * and the referrer URL to detect PPC traffic from various sources.
	 *
	 * @since 2.31.0
	 *
	 * @param bool   $skip_visit         Whether to skip tracking a visit.
	 * @param int    $affiliate_id       Affiliate ID.
	 * @param bool   $is_valid_affiliate Whether the affiliate is valid.
	 * @param string $referrer           Visit referrer.
	 * @return bool Whether to skip the visit.
	 */
	public function check_ppc_traffic( $skip_visit, $affiliate_id, $is_valid_affiliate, $referrer ) {
		// PPC detection is a Pro feature.
		if ( ! function_exists( 'affwp_can_access_pro_features' ) || ! affwp_can_access_pro_features() ) {
			return $skip_visit;
		}

		$ppc_setting = affiliate_wp()->settings->get( 'fraud_prevention_ppc_traffic', 'allow' );

		// If set to allow, don't do anything.
		if ( 'allow' === $ppc_setting ) {
			return $skip_visit;
		}

		$ppc_detection = [ 'is_ppc' => false ];

		// First, check the landing page URL (sent via AJAX in $_POST['url']).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- AJAX request from tracking script.
		if ( ! empty( $_POST['url'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- AJAX request from tracking script.
			$landing_url   = sanitize_text_field( wp_unslash( $_POST['url'] ) );
			$ppc_detection = $this->ppc_detection->detect( $landing_url );
		}

		// If not found in landing URL, check current request parameters (for non-AJAX scenarios).
		if ( ! $ppc_detection['is_ppc'] ) {
			$ppc_detection = $this->ppc_detection->detect_from_request();
		}

		// If still not found, check referrer URL.
		if ( ! $ppc_detection['is_ppc'] ) {
			$ppc_detection = $this->ppc_detection->detect( $referrer );
		}

		if ( $ppc_detection['is_ppc'] ) {
			// Flag the visit (will be picked up by flag_visit filter).
			add_filter( 'affwp_fraud_prevention_flag_ppc_traffic', '__return_true' );
			add_filter(
				'affwp_fraud_prevention_ppc_platform',
				function () use ( $ppc_detection ) {
					return $ppc_detection['platform'];
				}
			);
			add_filter(
				'affwp_fraud_prevention_ppc_method',
				function () use ( $ppc_detection ) {
					return $ppc_detection['method'];
				}
			);

			// If set to reject, skip the visit entirely.
			if ( 'reject' === $ppc_setting ) {

				// Facebook/Meta: fbclid is present in both paid and organic traffic,
				// so we downgrade from Reject to Flag to avoid blocking legitimate visitors.
				if ( 'fbclid' === $ppc_detection['param'] ) {
					affiliate_wp()->utils->log(
						sprintf(
							'Facebook/Meta traffic detected (fbclid) for affiliate #%d. Downgraded from Reject to Flag because fbclid cannot reliably distinguish paid from organic traffic.',
							$affiliate_id
						)
					);

					return $skip_visit;
				}

				affiliate_wp()->utils->log(
					sprintf(
						'PPC traffic detected from %s for affiliate #%d. Visit was not recorded. (Method: %s)',
						$ppc_detection['platform'],
						$affiliate_id,
						$ppc_detection['method']
					)
				);
				return true;
			}
		}

		return $skip_visit;
	}

	/**
	 * Check for referring site violations.
	 *
	 * @since 2.31.0
	 *
	 * @param bool   $skip_visit         Whether to skip tracking a visit.
	 * @param int    $affiliate_id       Affiliate ID.
	 * @param bool   $is_valid_affiliate Whether the affiliate is valid.
	 * @param string $referrer           Visit referrer.
	 * @return bool Whether to skip the visit.
	 */
	public function check_referring_site( $skip_visit, $affiliate_id, $is_valid_affiliate, $referrer ) {
		// Check referring site (Pro feature).
		$result = $this->referring_site_check->check( $affiliate_id, $referrer );

		if ( $result['is_violation'] ) {
			// Flag the visit (will be picked up by flag_visit filter).
			add_filter( 'affwp_fraud_prevention_flag_referring_site', '__return_true' );

			// If set to reject, skip the visit entirely.
			if ( 'reject' === $result['action'] ) {
				affiliate_wp()->utils->log(
					sprintf(
						'Referring site violation detected for affiliate #%d. Visit was not recorded. %s',
						$affiliate_id,
						$result['message']
					)
				);
				return true;
			}
		}

		return $skip_visit;
	}

	/**
	 * Flag a visit before it's created.
	 *
	 * @since 2.31.0
	 *
	 * @param array $data Visit data.
	 * @return array Modified visit data with flag.
	 */
	public function flag_visit( $data ) {
		// Check for PPC traffic flag.
		if ( has_filter( 'affwp_fraud_prevention_flag_ppc_traffic' ) ) {
			$data['flag'] = 'ppc_traffic';
			// NOTE: Visits don't have a metadata system, so PPC signals (platform, method)
			// are kept in filters and transferred to referrals when created.
		}

		// Check for referring site flag.
		if ( empty( $data['flag'] ) && has_filter( 'affwp_fraud_prevention_flag_referring_site' ) ) {
			$data['flag'] = 'referring_site';
		}

		return $data;
	}

	/**
	 * Flag a referral before it's created.
	 *
	 * Applies fraud detection checks and flags referrals as needed.
	 *
	 * @since 2.31.0
	 *
	 * @param array  $args         Arguments sent to referrals->add().
	 * @param float  $amount       Calculated referral amount.
	 * @param string $reference    Referral reference.
	 * @param string $description  Referral description.
	 * @param int    $affiliate_id Affiliate ID.
	 * @param int    $visit_id     Visit ID.
	 * @return array Modified referral args with flag.
	 */
	public function flag_referral( $args, $amount, $reference, $description, $affiliate_id, $visit_id ) {
		// Initialize meta array if it doesn't exist.
		if ( ! isset( $args['_meta'] ) ) {
			$args['_meta'] = [];
		}

		$visit = false;
		if ( ! empty( $visit_id ) ) {
			$visit = affwp_get_visit( $visit_id );
		}

		// Priority 1: Apply flag from visit if flagged (PPC traffic detection happens at visit level).
		// Validate the visit flag against current settings before inheriting it,
		// because the visit may have been flagged under a previous setting.
		if ( empty( $args['flag'] ) && $visit && ! empty( $visit->flag ) ) {
			$inherit_visit_flag = true;

			// Don't inherit self_referral flag if self-referrals are now allowed.
			if ( 'self_referral' === $visit->flag ) {
				$self_referral_setting = affiliate_wp()->settings->get( 'fraud_prevention_self_referrals', 'reject' );

				if ( 'allow' === $self_referral_setting ) {
					$inherit_visit_flag = false;

					// Clear the stale flag from the visit.
					affiliate_wp()->visits->update( $visit->visit_id, [ 'flag' => '' ] );
				}
			}

			if ( $inherit_visit_flag ) {
				$args['flag'] = $visit->flag;

				// Copy PPC metadata to referral (visits don't have metadata, but we can get it from filters).
				if ( 'ppc_traffic' === $visit->flag ) {
					$platform = '';
					$method   = '';

					// Try filters first (same-request scenario).
					if ( has_filter( 'affwp_fraud_prevention_flag_ppc_traffic' ) ) {
						$platform = apply_filters( 'affwp_fraud_prevention_ppc_platform', '' );
						$method   = apply_filters( 'affwp_fraud_prevention_ppc_method', '' );
					}

					// Fallback: re-detect from stored visit URL (cross-request scenario).
					if ( empty( $platform ) && ! empty( $visit->url ) ) {
						$ppc_redetect = $this->ppc_detection->detect( $visit->url );
						if ( $ppc_redetect['is_ppc'] ) {
							$platform = $ppc_redetect['platform'];
							$method   = $ppc_redetect['method'];
						}
					}

					// Second fallback: try the referrer URL.
					if ( empty( $platform ) && ! empty( $visit->referrer ) ) {
						$ppc_redetect = $this->ppc_detection->detect( $visit->referrer );
						if ( $ppc_redetect['is_ppc'] ) {
							$platform = $ppc_redetect['platform'];
							$method   = $ppc_redetect['method'];
						}
					}

					if ( ! empty( $platform ) ) {
						$args['_meta']['ppc_signals'] = wp_json_encode(
							[
								'platform'    => $platform,
								'method'      => $method,
								'detected_at' => current_time( 'mysql' ),
							]
						);
					}
				}
			}
		}

		// Priority 2: Check for self-referral (email match).
		if ( empty( $args['flag'] ) && has_filter( 'affwp_fraud_prevention_flag_self_referral' ) ) {
			$args['flag'] = 'self_referral';
		}

		// Priority 3: Check for conversion rate anomalies (Pro feature).
		if ( empty( $args['flag'] ) ) {
			$conversion_result = $this->conversion_rate_check->check( $affiliate_id );

			if ( $conversion_result['is_violation'] ) {
				$args['flag'] = 'conversion_rate';

				// Store conversion rate metadata.
				$args['_meta']['conversion_rate_data'] = wp_json_encode(
					[
						'conversion_rate' => $conversion_result['conversion_rate'],
						'referral_count'  => $conversion_result['referral_count'],
						'visit_count'     => $conversion_result['visit_count'],
						'detected_at'     => current_time( 'mysql' ),
					]
				);
			}
		}

		// Priority 4: Store purchase IP for future IP-based checks (Phase 2).
		// NOTE: We cannot rely on the customer table IP because it gets overwritten on repeat purchases.
		// The customer table stores the MOST RECENT purchase IP, not the original.
		// For accurate IP match detection, we need the IP from THIS specific purchase.
		$purchase_ip = affwp_fraud_get_ip();
		if ( ! empty( $purchase_ip ) ) {
			$args['_meta']['purchase_ip']   = $purchase_ip;
			$args['_meta']['purchase_date'] = current_time( 'mysql' );
		}

		// If we flagged the referral, also flag the visit (if not already flagged).
		if ( $visit && ! empty( $args['flag'] ) && empty( $visit->flag ) ) {
			affiliate_wp()->visits->update( $visit->visit_id, [ 'flag' => $args['flag'] ] );
		}

		return $args;
	}
}
