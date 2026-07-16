<?php
/**
 * Cookie Consent Integration — WPConsent Session Fallback.
 *
 * When WPConsent blocks AffiliateWP's tracking script (marketing category),
 * this class stores affiliate attribution data in a server-side session
 * (WooCommerce or EDD) instead. These session cookies are classified as
 * strictly necessary and are never blocked by consent plugins.
 *
 * @package     AffiliateWP
 * @subpackage  Integrations
 * @since       2.31.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cookie consent integration class.
 *
 * @since 2.32.0
 */
class Affiliate_WP_Cookie_Consent {

	/**
	 * Whether fallback_track_visit() already handled this request.
	 *
	 * @since 2.32.0
	 *
	 * @var bool
	 */
	private $fallback_tracked = false;

	/**
	 * Constructor.
	 *
	 * @since 2.32.0
	 */
	public function __construct() {

		// Bail if WPConsent is not active.
		if ( ! function_exists( 'wpconsent' ) ) {
			return;
		}

		// Need at least one supported session provider (WooCommerce or EDD).
		if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'EDD' ) ) {
			return;
		}

		// Register AffiliateWP's tracking script with WPConsent for blocking.
		add_filter( 'wpconsent_blocked_scripts', array( $this, 'register_blocked_scripts' ) );

		// Register AffiliateWP's cookies with WPConsent (admin, one-time).
		add_action( 'admin_init', array( $this, 'maybe_register_cookies' ) );

		// Track when fallback_track_visit() already handled this request.
		add_action( 'affwp_tracking_set_affiliate_id', array( $this, 'mark_fallback_tracked' ) );

		// Store affiliate data in session when consent is not given.
		add_action( 'template_redirect', array( $this, 'maybe_store_in_session' ), -9998 );

		// Prevent duplicate visit when tracking.js fires after consent is given.
		// Hooks before track_visit() (priority 9 vs 10) so fraud filters still run normally.
		add_action( 'wp_ajax_nopriv_affwp_track_visit', array( $this, 'maybe_return_session_visit' ), 9 );
		add_action( 'wp_ajax_affwp_track_visit', array( $this, 'maybe_return_session_visit' ), 9 );

		// Provide session fallback values when cookies are empty.
		add_filter( 'affwp_was_referred', array( $this, 'filter_was_referred' ) );
		add_filter( 'affwp_tracking_get_affiliate_id', array( $this, 'filter_tracking_affiliate_id' ) );
		add_filter( 'affwp_get_referring_affiliate_id', array( $this, 'filter_referring_affiliate_id' ), 10, 3 );
		add_filter( 'affwp_tracking_get_visit_id', array( $this, 'filter_visit_id' ) );
	}

	/**
	 * Mark that fallback_track_visit() already handled tracking for this request.
	 *
	 * Fired by the affwp_tracking_set_affiliate_id action inside set_affiliate_id(),
	 * which fallback_track_visit() calls after creating a visit.
	 *
	 * @since 2.32.0
	 */
	public function mark_fallback_tracked() {
		$this->fallback_tracked = true;
	}

	/**
	 * Register AffiliateWP's tracking script as a marketing script with WPConsent.
	 *
	 * @since 2.32.0
	 *
	 * @param array $scripts Categorized scripts array.
	 * @return array Modified scripts array.
	 */
	public function register_blocked_scripts( $scripts ) {
		$scripts['marketing']['affiliatewp'] = array(
			'label'   => 'AffiliateWP',
			'scripts' => array(
				'affiliate-wp/assets/js/tracking',
			),
		);

		return $scripts;
	}

	/**
	 * Register AffiliateWP's cookies with WPConsent for the admin UI.
	 *
	 * Only runs once, tracked via an option flag.
	 *
	 * @since 2.32.0
	 */
	public function maybe_register_cookies() {

		if ( get_option( 'affwp_wpconsent_cookies_registered' ) ) {
			return;
		}

		if ( ! function_exists( 'wpconsent' ) || ! isset( wpconsent()->cookies ) ) {
			return;
		}

		$expiration = affiliate_wp()->settings->get( 'cookie_exp', 1 );
		$duration   = $expiration . ' ' . _n( 'day', 'days', $expiration, 'affiliate-wp' );

		wpconsent()->cookies->add_cookie(
			'affwp_ref',
			'affwp_ref',
			__( 'Stores the referring affiliate ID for commission attribution.', 'affiliate-wp' ),
			'marketing',
			$duration
		);

		wpconsent()->cookies->add_cookie(
			'affwp_ref_visit_id',
			'affwp_ref_visit_id',
			__( 'Stores the visit ID linked to the referring affiliate.', 'affiliate-wp' ),
			'marketing',
			$duration
		);

		wpconsent()->cookies->add_cookie(
			'affwp_campaign',
			'affwp_campaign',
			__( 'Stores the affiliate campaign name for tracking purposes.', 'affiliate-wp' ),
			'marketing',
			$duration
		);

		update_option( 'affwp_wpconsent_cookies_registered', true );
	}

	/**
	 * Store affiliate data in server-side session when consent is not given.
	 *
	 * Runs on template_redirect at priority -9998 (just after AffiliateWP's
	 * fallback tracking at -9999).
	 *
	 * @since 2.32.0
	 */
	public function maybe_store_in_session() {

		// fallback_track_visit() already created a visit on this request.
		if ( $this->fallback_tracked ) {
			return;
		}

		if ( ! $this->should_use_session_fallback() ) {
			return;
		}

		$affiliate_id = affiliate_wp()->tracking->get_fallback_affiliate_id();

		if ( empty( $affiliate_id ) ) {
			return;
		}

		// Resolve username to ID if needed.
		if ( ! is_numeric( $affiliate_id ) ) {
			$affiliate_id = affiliate_wp()->tracking->get_affiliate_id_from_login( $affiliate_id );
		}

		$affiliate_id = absint( $affiliate_id );

		if ( ! affiliate_wp()->tracking->is_valid_affiliate( $affiliate_id ) ) {
			return;
		}

		// A session provider must be available.
		if ( ! $this->has_session() ) {
			return;
		}

		// Check if an affiliate is already stored in the session.
		$existing_ref = $this->session_get( 'affwp_ref' );

		if ( ! empty( $existing_ref ) && ! affiliate_wp()->settings->get( 'referral_credit_last' ) ) {
			return; // Already tracked, and credit-last is not enabled — first affiliate wins.
		}

		// Force-start the session cookie.
		$this->start_session();

		$campaign = isset( $_REQUEST['campaign'] ) ? sanitize_text_field( $_REQUEST['campaign'] ) : '';
		$referrer = ! empty( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : '';

		// Skip if referrer is banned.
		if ( ! empty( $referrer ) && affwp_is_url_banned( $referrer ) ) {
			return;
		}

		/**
		 * Allow skipping visit tracking for consent/privacy reasons.
		 *
		 * This filter is documented in includes/class-tracking.php.
		 */
		if ( true === apply_filters( 'affwp_tracking_skip_track_visit', false, $affiliate_id, true, $referrer, affiliate_wp()->tracking ) ) {
			return;
		}

		// Create visit record server-side.
		$visit_id = affiliate_wp()->visits->add(
			array(
				'affiliate_id' => $affiliate_id,
				'ip'           => affiliate_wp()->tracking->get_ip(),
				'url'          => affiliate_wp()->tracking->get_current_page_url(),
				'campaign'     => $campaign,
				'referrer'     => $referrer,
			)
		);

		// Store in session.
		$this->session_set( 'affwp_ref', $affiliate_id );

		if ( $visit_id ) {
			$this->session_set( 'affwp_ref_visit_id', $visit_id );
		}

		if ( ! empty( $campaign ) ) {
			$this->session_set( 'affwp_campaign', $campaign );
		}
	}

	/**
	 * Filter whether the current visit was referred.
	 *
	 * If the cookie check returned false, check the session.
	 *
	 * @since 2.32.0
	 *
	 * @param bool $was_referred Whether the current visit was referred.
	 * @return bool
	 */
	public function filter_was_referred( $was_referred ) {

		if ( $was_referred ) {
			return true;
		}

		$session_ref = $this->session_get( 'affwp_ref' );

		return ! empty( $session_ref ) && affiliate_wp()->tracking->is_valid_affiliate( absint( $session_ref ) );
	}

	/**
	 * Filter the affiliate ID from the tracking cookie.
	 *
	 * If the cookie returned empty, check the session.
	 *
	 * @since 2.32.0
	 *
	 * @param int|false $affiliate_id Affiliate ID from cookie.
	 * @return int|false
	 */
	public function filter_tracking_affiliate_id( $affiliate_id ) {

		if ( ! empty( $affiliate_id ) ) {
			return $affiliate_id;
		}

		$session_ref = $this->session_get( 'affwp_ref' );

		return ! empty( $session_ref ) ? absint( $session_ref ) : $affiliate_id;
	}

	/**
	 * Filter the referring affiliate ID at checkout time.
	 *
	 * The base integration class caches the affiliate ID in its constructor
	 * (from the cookie). If that was empty, check the session.
	 *
	 * @since 2.32.0
	 *
	 * @param int    $affiliate_id Affiliate ID.
	 * @param string $reference    Referral reference.
	 * @param string $context      Referral context.
	 * @return int
	 */
	public function filter_referring_affiliate_id( $affiliate_id, $reference, $context ) {

		if ( ! empty( $affiliate_id ) ) {
			return $affiliate_id;
		}

		$session_ref = $this->session_get( 'affwp_ref' );

		return ! empty( $session_ref ) ? absint( $session_ref ) : $affiliate_id;
	}

	/**
	 * Filter the visit ID from the tracking cookie.
	 *
	 * If the cookie returned empty, check the session.
	 *
	 * @since 2.32.0
	 *
	 * @param int|false $visit_id Visit ID from cookie.
	 * @return int|false
	 */
	public function filter_visit_id( $visit_id ) {

		if ( ! empty( $visit_id ) ) {
			return $visit_id;
		}

		$session_visit = $this->session_get( 'affwp_ref_visit_id' );

		return ! empty( $session_visit ) ? intval( $session_visit ) : $visit_id;
	}

	/**
	 * Prevent duplicate visit when tracking.js fires after consent is given.
	 *
	 * When the session fallback already stored a visit for this affiliate,
	 * return that visit ID to tracking.js instead of creating a new one.
	 *
	 * Hooked to wp_ajax_affwp_track_visit at priority 9, before track_visit()
	 * at priority 10, so fraud prevention filters are not bypassed.
	 *
	 * @since 2.32.0
	 */
	public function maybe_return_session_visit() {

		$affiliate_id     = isset( $_POST['affiliate'] ) ? absint( $_POST['affiliate'] ) : 0;
		$session_ref      = $this->session_get( 'affwp_ref' );
		$session_visit_id = $this->session_get( 'affwp_ref_visit_id' );

		if ( empty( $session_ref ) || empty( $session_visit_id ) ) {
			return;
		}

		if ( absint( $session_ref ) !== $affiliate_id ) {
			return;
		}

		echo intval( $session_visit_id );
		exit;
	}

	/**
	 * Determine if the session fallback should be used.
	 *
	 * @since 2.32.0
	 *
	 * @return bool
	 */
	private function should_use_session_fallback() {

		// WPConsent must be active.
		if ( ! function_exists( 'wpconsent' ) ) {
			return false;
		}

		// In default_allow (optout) mode, scripts are not blocked initially.
		if ( function_exists( 'wpconsent' ) && wpconsent()->settings->get_option( 'default_allow', 0 ) ) {
			return false;
		}

		// If marketing consent is already given, no fallback needed.
		if ( $this->is_marketing_consent_given() ) {
			return false;
		}

		// A session provider must be available.
		if ( ! $this->has_session() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if marketing consent has been given via WPConsent.
	 *
	 * @since 2.32.0
	 *
	 * @return bool
	 */
	private function is_marketing_consent_given() {

		if ( ! isset( $_COOKIE['wpconsent_preferences'] ) ) {
			return false;
		}

		$prefs_raw = stripslashes( $_COOKIE['wpconsent_preferences'] );

		if ( empty( $prefs_raw ) ) {
			return false;
		}

		$prefs = json_decode( $prefs_raw, true );

		return is_array( $prefs ) && ! empty( $prefs['marketing'] );
	}

	/**
	 * Check if any supported session provider is available.
	 *
	 * @since 2.32.0
	 *
	 * @return bool
	 */
	private function has_session() {
		return $this->has_wc_session() || $this->has_edd_session();
	}

	/**
	 * Check if a WooCommerce session is available.
	 *
	 * @since 2.32.0
	 *
	 * @return bool
	 */
	private function has_wc_session() {
		return function_exists( 'WC' ) && WC()->session instanceof \WC_Session;
	}

	/**
	 * Check if an EDD session is available.
	 *
	 * @since 2.32.0
	 *
	 * @return bool
	 */
	private function has_edd_session() {
		return function_exists( 'EDD' ) && EDD()->session instanceof \EDD\Sessions\Handler;
	}

	/**
	 * Get a value from the available session provider.
	 *
	 * Tries WooCommerce first, then EDD.
	 *
	 * @since 2.32.0
	 *
	 * @param string $key Session key.
	 * @return mixed|null Session value or null.
	 */
	private function session_get( $key ) {

		if ( $this->has_wc_session() ) {
			return WC()->session->get( $key );
		}

		if ( $this->has_edd_session() ) {
			return EDD()->session->get( $key );
		}

		return null;
	}

	/**
	 * Set a value in the available session provider.
	 *
	 * Tries WooCommerce first, then EDD.
	 *
	 * @since 2.32.0
	 *
	 * @param string $key   Session key.
	 * @param mixed  $value Session value.
	 */
	private function session_set( $key, $value ) {

		if ( $this->has_wc_session() ) {
			WC()->session->set( $key, $value );
			return;
		}

		if ( $this->has_edd_session() ) {
			EDD()->session->set( $key, $value );
		}
	}

	/**
	 * Force-start the session cookie for the available provider.
	 *
	 * @since 2.32.0
	 */
	private function start_session() {

		if ( $this->has_wc_session() ) {
			WC()->session->set_customer_session_cookie( true );
			return;
		}

		if ( $this->has_edd_session() ) {
			EDD()->session->maybe_start_session( true );
		}
	}

	/**
	 * Initialize the settings notice for WPConsent promotion.
	 *
	 * This runs even when WPConsent is NOT installed — that's the point.
	 *
	 * @since 2.32.0
	 */
	public static function init_settings_notice() {

		add_action( 'affwp_after_setting_field_cookie_exp', array( __CLASS__, 'render_wpconsent_notice' ) );
		add_action( 'wp_ajax_affwp_dismiss_wpconsent_notice', array( __CLASS__, 'ajax_dismiss_notice' ) );
	}

	/**
	 * Render the WPConsent promotional notice below the Cookie Expiration setting.
	 *
	 * Hooked to `affwp_after_setting_field_cookie_exp` so it renders inside the
	 * cookie_exp field's table cell, directly below the setting.
	 *
	 * @since 2.32.0
	 *
	 * @param array $args The field arguments.
	 */
	public static function render_wpconsent_notice( $args ) {

		// Don't show if WPConsent is already active.
		if ( function_exists( 'wpconsent' ) ) {
			return;
		}

		// Requires WooCommerce or EDD for the session fallback.
		if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'EDD' ) ) {
			return;
		}

		$page_url  = admin_url( 'admin.php?page=affiliate-wp-cookie-consent' );
		$dismissed = get_user_meta( get_current_user_id(), 'affwp_dismiss_wpconsent_notice', true );

		// After dismissal, show only a single-line link.
		if ( $dismissed ) {
			?>
			<p class="description" style="margin-top: 8px;">
				<?php
				printf(
					/* translators: %1$s - opening link tag, %2$s - closing link tag. */
					esc_html__( 'For GDPR-compliant cookie handling, see our %1$sWPConsent integration%2$s.', 'affiliate-wp' ),
					'<a href="' . esc_url( $page_url ) . '">',
					'</a>'
				);
				?>
			</p>
			<?php
			return;
		}

		$nonce = wp_create_nonce( 'affwp_dismiss_wpconsent_notice' );

		?>
		<div id="affwp-wpconsent-notice" class="notice notice-info inline" style="margin: 12px 0 0; display: block; position: relative;">
			<button type="button" class="notice-dismiss" id="affwp-dismiss-wpconsent">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'affiliate-wp' ); ?></span>
			</button>
			<p><strong><?php esc_html_e( 'Affiliate cookie tracking requires visitor consent under GDPR/ePrivacy rules.', 'affiliate-wp' ); ?></strong></p>
			<p><?php esc_html_e( 'Without a consent plugin, affiliate cookies are set without visitor consent, risking GDPR and ePrivacy violations. With one installed, visitors who decline cookies won\'t be tracked and referrals are lost. AffiliateWP integrates with WPConsent to preserve affiliate tracking even when cookies are blocked.', 'affiliate-wp' ); ?></p>
			<p><a href="<?php echo esc_url( $page_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Install WPConsent', 'affiliate-wp' ); ?></a></p>
		</div>
		<script>
		document.getElementById( 'affwp-dismiss-wpconsent' ).addEventListener( 'click', function() {
			document.getElementById( 'affwp-wpconsent-notice' ).style.display = 'none';
			fetch( ajaxurl + '?action=affwp_dismiss_wpconsent_notice&_ajax_nonce=<?php echo esc_js( $nonce ); ?>' );
		} );
		</script>
		<?php
	}

	/**
	 * AJAX handler to dismiss the WPConsent promotional notice.
	 *
	 * @since 2.32.0
	 */
	public static function ajax_dismiss_notice() {

		check_ajax_referer( 'affwp_dismiss_wpconsent_notice' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		update_user_meta( get_current_user_id(), 'affwp_dismiss_wpconsent_notice', 1 );
		wp_send_json_success();
	}
}
