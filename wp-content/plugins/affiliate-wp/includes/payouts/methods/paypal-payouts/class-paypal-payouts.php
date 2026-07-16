<?php
/**
 * Core: PayPal Payouts Bootstrap
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/PayPalPayouts
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

namespace AffiliateWP\Core\Payouts\Methods\PayPalPayouts;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Main PayPal Payouts bootstrap.
 *
 * @since 2.29.0
 */
final class AffiliateWP_PayPal_Payouts {

	/** Singleton *************************************************************/

	/**
	 * Main plugin instance.
	 *
	 * @since 2.29.0
	 * @var   AffiliateWP_PayPal_Payouts
	 * @static
	 */
	private static $instance;

	/**
	 * The version number.
	 *
	 * @since 2.29.0
	 * @var   string
	 */
	private $version = '2.29.0';

	/**
	 * Main AffiliateWP_PayPal_Payouts Instance
	 *
	 * Insures that only one instance of AffiliateWP_PayPal_Payouts exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 2.29.0
	 * @static
	 *
	 * @return \AffiliateWP\Core\Payouts\Methods\PayPalPayouts\AffiliateWP_PayPal_Payouts The one true bootstrap instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AffiliateWP_PayPal_Payouts ) ) {

			self::$instance = new AffiliateWP_PayPal_Payouts();

			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 2.29.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'affiliate-wp' ), '2.29.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 2.29.0
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'affiliate-wp' ), '2.29.0' );
	}

	/**
	 * Sets up plugin constants.
	 *
	 * @since 2.29.0
	 */
	private function setup_constants() {
		// Plugin version - Keep for backward compatibility
		if ( ! defined( 'AFFWP_PP_VERSION' ) ) {
			define( 'AFFWP_PP_VERSION', $this->version );
		}

		// Plugin Folder Path - Update to core path
		if ( ! defined( 'AFFWP_PP_PLUGIN_DIR' ) ) {
			define( 'AFFWP_PP_PLUGIN_DIR', __DIR__ . '/' );
		}

		// Plugin Folder URL - Update to core URL
		if ( ! defined( 'AFFWP_PP_PLUGIN_URL' ) ) {
			define( 'AFFWP_PP_PLUGIN_URL', AFFILIATEWP_PLUGIN_URL . 'includes/payouts/methods/paypal-payouts/' );
		}
	}

	/**
	 * Include required files
	 *
	 * @access private
	 * @since 2.29.0
	 * @return void
	 */
	private function includes() {
		// Only load admin files if we're in admin AND PayPal should be loaded
		// This reduces overhead when PayPal Payouts isn't being used
		if ( is_admin() && $this->should_load_admin() ) {
			require_once AFFWP_PP_PLUGIN_DIR . 'admin/class-paypal-api.php';
			require_once AFFWP_PP_PLUGIN_DIR . 'admin/class-paypal-masspay.php';
			require_once AFFWP_PP_PLUGIN_DIR . 'admin/class-paypal-payouts-admin.php';
		}
	}

	/**
	 * Initialize PayPal Payouts
	 *
	 * @access private
	 * @since 2.29.0
	 * @return void
	 */
	private function init() {
		// Add PayPal to payout methods filter before initializing admin.
		// This ensures PayPal appears in the enabled payout methods list when admin class constructor runs.
		if ( $this->has_2_4() ) {
			add_filter( 'affwp_payout_methods', [ $this, 'add_payout_method_filter' ] );
			add_filter( 'affwp_is_payout_method_enabled', [ $this, 'is_paypal_enabled_filter' ], 10, 2 );
		}

		// Initialize admin only if needed (after filters are registered).
		if ( is_admin() && $this->should_load_admin() ) {
			$this->admin = new \AffiliateWP_PayPal_Payouts_Admin();
		}
	}

	/**
	 * Check if admin should be loaded
	 *
	 * @access private
	 * @since 2.29.0
	 * @return bool
	 */
	private function should_load_admin() {
		// Check if PayPal is enabled
		$is_enabled = affiliate_wp()->settings->get( 'paypal_payouts', false );

		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// On settings page, always load admin class.
		if ( 'affiliate-wp-settings' === $page ) {
			return true;
		}

		// On payouts pages, only load if enabled.
		if ( $page && false !== strpos( $page, 'affiliate-wp-payouts' ) ) {
			return $is_enabled;
		}

		// On referrals page, load if enabled (for bulk actions).
		if ( 'affiliate-wp-referrals' === $page ) {
			return $is_enabled;
		}

		// Only load elsewhere if PayPal is enabled AND has credentials configured.
		return $is_enabled && $this->has_api_credentials();
	}

	/**
	 * Gets the PayPal API credentials
	 *
	 * @access public
	 * @since 2.29.0
	 * @return array
	 */
	public function get_api_credentials() {

		$payout_mode = affiliate_wp()->settings->get( 'paypal_payout_mode', 'masspay' );
		$mode        = $this->is_test_mode() ? 'test' : 'live';

		if ( 'api' == $payout_mode ) {

			$creds = [
				'client_id' => affiliate_wp()->settings->get( 'paypal_' . $mode . '_client_id', '' ),
				'secret'    => affiliate_wp()->settings->get( 'paypal_' . $mode . '_secret', '' ),
			];

		} else {

			$creds = [
				'username'  => affiliate_wp()->settings->get( 'paypal_' . $mode . '_username', '' ),
				'password'  => affiliate_wp()->settings->get( 'paypal_' . $mode . '_password', '' ),
				'signature' => affiliate_wp()->settings->get( 'paypal_' . $mode . '_signature', '' ),
			];

		}

		return $creds;
	}

	/**
	 * Checks if we have API credentials
	 *
	 * @access public
	 * @since 2.29.0
	 * @return bool
	 */
	public function has_api_credentials() {

		$ret         = true;
		$payout_mode = affiliate_wp()->settings->get( 'paypal_payout_mode', 'masspay' );
		$creds       = $this->get_api_credentials();

		if ( 'api' == $payout_mode ) {

			if ( empty( $creds['client_id'] ) ) {
				$ret = false;
			}

			if ( empty( $creds['secret'] ) ) {
				$ret = false;
			}
		} else {

			if ( empty( $creds['username'] ) ) {
				$ret = false;
			}

			if ( empty( $creds['password'] ) ) {
				$ret = false;
			}

			if ( empty( $creds['signature'] ) ) {
				$ret = false;
			}
		}

		return $ret;
	}

	/**
	 * Determines if the user has at least version 2.4 of AffiliateWP.
	 *
	 * @since 2.29.0
	 *
	 * @return bool True if AffiliateWP v2.4 or newer, false otherwise.
	 */
	public function has_2_4() {

		$met = false;

		if ( version_compare( AFFILIATEWP_VERSION, '2.3.5', '>' ) ) {
			$met = true;
		}

		return $met;
	}

	/**
	 * Determines if we are in test mode
	 *
	 * @access public
	 * @since 2.29.0
	 * @return bool
	 */
	public function is_test_mode() {

		return (bool) affiliate_wp()->settings->get( 'paypal_test_mode', false );
	}

	/**
	 * Register the payment method.
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function register_payment_method() {
		// Determine status based on test mode and credentials
		$test_mode       = $this->is_test_mode();
		$has_credentials = $this->has_api_credentials();

		// Determine which credentials we have
		$payout_mode = affiliate_wp()->settings->get( 'paypal_payout_mode', 'masspay' );
		$mode        = $test_mode ? 'test' : 'live';

		if ( 'api' == $payout_mode ) {
			$has_test_creds = ! empty( affiliate_wp()->settings->get( 'paypal_test_client_id', '' ) )
							&& ! empty( affiliate_wp()->settings->get( 'paypal_test_secret', '' ) );
			$has_live_creds = ! empty( affiliate_wp()->settings->get( 'paypal_live_client_id', '' ) )
							&& ! empty( affiliate_wp()->settings->get( 'paypal_live_secret', '' ) );
		} else {
			$has_test_creds = ! empty( affiliate_wp()->settings->get( 'paypal_test_username', '' ) )
							&& ! empty( affiliate_wp()->settings->get( 'paypal_test_password', '' ) )
							&& ! empty( affiliate_wp()->settings->get( 'paypal_test_signature', '' ) );
			$has_live_creds = ! empty( affiliate_wp()->settings->get( 'paypal_live_username', '' ) )
							&& ! empty( affiliate_wp()->settings->get( 'paypal_live_password', '' ) )
							&& ! empty( affiliate_wp()->settings->get( 'paypal_live_signature', '' ) );
		}

		// Check if PayPal Payouts is enabled
		$is_enabled = affiliate_wp()->settings->get( 'paypal_payouts', false );

		// Determine status based on enabled state, mode and credentials
		if ( $is_enabled && $test_mode && $has_test_creds ) {
			$status       = 'active'; // Will show "Sandbox Mode" in custom label
			$custom_label = __( 'Sandbox Mode', 'affiliate-wp' );
		} elseif ( $is_enabled && ! $test_mode && $has_live_creds ) {
			$status       = 'active'; // Will show "Live Mode" in custom label
			$custom_label = __( 'Live Mode', 'affiliate-wp' );
		} elseif ( ! $is_enabled && ( $has_test_creds || $has_live_creds ) ) {
			$status = 'available'; // Has credentials but disabled - NO custom label
			// Don't set custom_label here - we want no badge to show
		} elseif ( $is_enabled && ! $has_credentials ) {
			$status = 'setup_required'; // Enabled but missing credentials
			// Provide context about which mode needs setup
			if ( $test_mode ) {
				$custom_label = __( 'Setup Required (Sandbox)', 'affiliate-wp' );
			} else {
				$custom_label = __( 'Setup Required (Live)', 'affiliate-wp' );
			}
		} else {
			$status = 'available'; // Not enabled and no credentials
		}

		$registration = [
			'name'              => __( 'PayPal Payouts', 'affiliate-wp' ),
			'description'       => __( 'Send instant payments to affiliates via PayPal', 'affiliate-wp' ),
			'icon'              => 'payout-method-paypal-payouts',
			'status'            => $status,
			'type'              => 'core',
			'settings_callback' => [ $this, 'render_settings_panel' ],
			'has_new_settings'  => true,
			'settings_url'      => '#paypal', // For backward compatibility
		];

		// Add custom label if set
		if ( isset( $custom_label ) ) {
			$registration['status_label'] = $custom_label;
		}

		\AffiliateWP_Payment_Methods::register( 'paypal', $registration );
	}

	/**
	 * Render the settings panel.
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function render_settings_panel() {
		include AFFWP_PP_PLUGIN_DIR . 'templates/settings-panel.php';
	}

	/**
	 * Add PayPal to payout methods list
	 *
	 * @since 2.29.0
	 * @param array $payout_methods Existing payout methods
	 * @return array Updated payout methods
	 */
	public function add_payout_method_filter( $payout_methods ) {
		$is_enabled      = affiliate_wp()->settings->get( 'paypal_payouts', false );
		$has_credentials = $this->has_api_credentials();

		if ( ! $is_enabled || ! $has_credentials ) {
			/* translators: 1: PayPal settings link */
			$payout_methods['paypal'] = sprintf( __( 'PayPal - <a href="%s">Enable and/or configure PayPal Payouts</a> to enable this payout method', 'affiliate-wp' ), affwp_admin_url( 'settings', [ 'tab' => 'payouts#paypal' ] ) );
		} else {
			$payout_methods['paypal'] = __( 'PayPal', 'affiliate-wp' );
		}

		return $payout_methods;
	}

	/**
	 * Check if PayPal payout method is enabled
	 *
	 * @since 2.29.0
	 * @param bool   $enabled Whether method is enabled
	 * @param string $payout_method Payout method being checked
	 * @return bool
	 */
	public function is_paypal_enabled_filter( $enabled, $payout_method ) {
		if ( 'paypal' === $payout_method ) {
			return affiliate_wp()->settings->get( 'paypal_payouts', false ) && $this->has_api_credentials();
		}

		return $enabled;
	}
}

/**
 * The main function responsible for returning the one true AffiliateWP_PayPal_Payouts
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $AffiliateWP_PayPal_Payouts = affiliate_wp_paypal(); ?>
 *
 * @since 2.29.0
 * @return \AffiliateWP\Core\Payouts\Methods\PayPalPayouts\AffiliateWP_PayPal_Payouts The one true AffiliateWP_PayPal_Payouts Instance
 */
function affiliate_wp_paypal() {
	return AffiliateWP_PayPal_Payouts::instance();
}
