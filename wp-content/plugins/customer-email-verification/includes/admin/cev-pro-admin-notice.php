<?php
/**
 * Admin Notice Handler for Customer Email Verification
 *
 * Handles admin notices for the Customer Email Verification plugin,
 * specifically for promoting Analytics for WooCommerce Subscriptions
 * when the WooCommerce Subscriptions plugin is active.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'CEV_Pro_Admin_Notice' ) ) {

	/**
	 * CEV_Pro_Admin_Notice Class
	 *
	 * Manages admin notices for promoting related WooCommerce products.
	 * Displays a dismissible notice about Analytics for WooCommerce Subscriptions
	 * when the WooCommerce Subscriptions plugin is active.
	 */
	class CEV_Pro_Admin_Notice {

		/**
		 * Instance of this class (Singleton pattern).
		 *
		 * @var CEV_Pro_Admin_Notice
		 */
		private static $instance;

		/**
		 * WooCommerce Subscriptions plugin file path.
		 *
		 * @var string
		 */
		const WOOCOMMERCE_SUBSCRIPTIONS_PLUGIN = 'woocommerce-subscriptions/woocommerce-subscriptions.php';

		/**
		 * Plugin settings page slug.
		 *
		 * @var string
		 */
		const SETTINGS_PAGE_SLUG = 'customer-email-verification-for-woocommerce';

		/**
		 * Option name for tracking if notice has been dismissed.
		 *
		 * @var string
		 */
		const DISMISS_OPTION_NAME = 'cev_introducing_analytics_for_wooCommerce_subscriptions_ignore';

		/**
		 * URL query parameter for dismissing the notice.
		 *
		 * @var string
		 */
		const DISMISS_QUERY_PARAM = 'cev-introducing-analytics-for-wooCommerce-subscriptions-ignore-notice';

		/**
		 * Analytics for WooCommerce Subscriptions product URL.
		 *
		 * @var string
		 */
		const ANALYTICS_PRODUCT_URL = 'https://woocommerce.com/products/analytics-for-woocommerce-subscriptions/';

		/**
		 * Constructor.
		 *
		 * Initializes the class and sets up hooks.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Get the singleton instance of this class.
		 *
		 * Ensures that only one instance of the class is created throughout
		 * the WordPress request lifecycle (Singleton pattern).
		 *
		 * @since 1.0.0
		 * @return CEV_Pro_Admin_Notice The single instance of this class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Initialize hooks and filters.
		 *
		 * Registers WordPress hooks for displaying and dismissing admin notices.
		 * Only displays notice if WooCommerce Subscriptions plugin is active.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function init() {
			// Display notice if WooCommerce Subscriptions is active.
			if ( is_plugin_active( self::WOOCOMMERCE_SUBSCRIPTIONS_PLUGIN ) ) {
				add_action( 'admin_notices', array( $this, 'admin_notice_update' ) );
			}

			// Handle dismiss action for the notice.
			add_action( 'admin_init', array( $this, 'cev_pro_notice_ignore' ) );
		}

		/**
		 * Display admin notice for Analytics for WooCommerce Subscriptions.
		 *
		 * Shows a dismissible notice promoting the Analytics for WooCommerce Subscriptions
		 * plugin when WooCommerce Subscriptions is active. The notice is hidden on the
		 * plugin's settings page and can be dismissed by the user.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function admin_notice_update() {
			// Check if notice has been dismissed.
			if ( $this->is_notice_dismissed() ) {
				return;
			}

			// Don't show notice on plugin settings page.
			if ( $this->is_settings_page() ) {
				return;
			}

			// Generate dismiss URL.
			$dismiss_url = $this->get_dismiss_url();

			// Render the notice.
			$this->render_notice( $dismiss_url );
		}

		/**
		 * Check if the notice has been dismissed.
		 *
		 * @since 1.0.0
		 * @return bool True if dismissed, false otherwise.
		 */
		private function is_notice_dismissed() {
			return (bool) get_option( self::DISMISS_OPTION_NAME, false );
		}

		/**
		 * Check if we're on the plugin settings page.
		 *
		 * @since 1.0.0
		 * @return bool True if on settings page, false otherwise.
		 */
		private function is_settings_page() {
			$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			return self::SETTINGS_PAGE_SLUG === $current_page;
		}

		/**
		 * Get the URL for dismissing the notice.
		 *
		 * @since 1.0.0
		 * @return string Dismiss URL with query parameter.
		 */
		private function get_dismiss_url() {
			return esc_url(
				add_query_arg(
					self::DISMISS_QUERY_PARAM,
					'true',
					admin_url( 'admin.php' )
				)
			);
		}

		/**
		 * Render the admin notice HTML.
		 *
		 * @since 1.0.0
		 * @param string $dismiss_url URL for dismissing the notice.
		 * @return void
		 */
		private function render_notice( $dismiss_url ) {
			?>
			<style>
				/* Styling for the dismissable admin notice */
				.wp-core-ui .notice.cev-dismissable-notice {
					position: relative;
					padding-right: 38px;
					border-left-color: #3b64d3;
				}

				/* Styling for the dismiss button */
				.wp-core-ui .notice.cev-dismissable-notice a.notice-dismiss {
					padding: 9px;
					text-decoration: none;
				}

				/* Styling for the Pro upgrade button */
				.wp-core-ui .button-primary.btn_pro_notice {
					background: transparent;
					color: #395da4;
					border-color: #395da4;
					text-transform: uppercase;
					padding: 0 11px;
					font-size: 12px;
					height: 30px;
					line-height: 28px;
					margin: 5px 0 15px;
				}
			</style>
			<div class="notice updated notice-success cev-dismissable-notice">
				<!-- Dismiss button -->
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="notice-dismiss">
					<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'customer-email-verification' ); ?></span>
				</a>

				<!-- Notice content -->
				<h3 style="margin-top: 10px; color:#3b64d3;font-size:16px">
					<?php esc_html_e( '🚀 Introducing Analytics for WooCommerce Subscriptions', 'customer-email-verification' ); ?>
				</h3>
				<p>
					<?php
					printf(
						/* translators: %s: Link to Analytics for WooCommerce Subscriptions product page */
						esc_html__( 'Get powerful insights with %s — the all-in-one dashboard to track signups, renewals, cancellations, and recurring revenue.', 'customer-email-verification' ),
						'<a href="' . esc_url( self::ANALYTICS_PRODUCT_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Analytics for WooCommerce Subscriptions', 'customer-email-verification' ) . '</a>'
					);
					?>
				</p>
				<p>
					<?php esc_html_e( 'Discover which products and customers drive the most value, reduce churn, and grow your subscription income with data-driven decisions.', 'customer-email-verification' ); ?>
				</p>

				<!-- Action buttons -->
				<a class="button-primary btn_pro_notice" 
				   target="_blank" 
				   rel="noopener noreferrer"
				   href="<?php echo esc_url( self::ANALYTICS_PRODUCT_URL ); ?>" 
				   style="background:#3b64d3;font-size:14px; border:1px solid #3b64d3; margin-bottom:10px; color:#fff;">
					<?php esc_html_e( '👉 Learn More on WooCommerce', 'customer-email-verification' ); ?>
				</a>

				<a class="button-primary ast_notice_btn" 
				   href="<?php echo esc_url( $dismiss_url ); ?>" 
				   style="background:#3b64d3;font-size:14px; border:1px solid #3b64d3; margin-bottom:10px;">
					<?php esc_html_e( 'Dismiss', 'customer-email-verification' ); ?>
				</a>
			</div>
			<?php
		}

		/**
		 * Handle notice dismissal when the ignore parameter is set in the URL.
		 *
		 * Saves an option to prevent the notice from being displayed again
		 * when the user clicks the dismiss button or link.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function cev_pro_notice_ignore() {
			// Check if dismiss parameter is present in URL.
			if ( ! isset( $_GET[ self::DISMISS_QUERY_PARAM ] ) ) {
				return;
			}

			// Verify the parameter value.
			$dismiss_value = sanitize_text_field( wp_unslash( $_GET[ self::DISMISS_QUERY_PARAM ] ) );

			if ( 'true' === $dismiss_value ) {
				// Save option to mark notice as dismissed.
				update_option( self::DISMISS_OPTION_NAME, 'true' );

				// Redirect to remove query parameter from URL.
				$redirect_url = remove_query_arg( self::DISMISS_QUERY_PARAM );
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}
}
