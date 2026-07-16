<?php
/**
 * AffiliateWP DRM.
 *
 * DRM implementation.
 *
 * @package    AffiliateWP
 * @subpackage AffiliateWP\Admin\DRM
 * @author     Darvin da Silveira <ddasilveira@awesomeomotive.com>
 * @copyright  Copyright (c) 2023, Awesome Motive, Inc
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.21.1
 */

namespace AffiliateWP\Admin\DRM;

use Affiliate_WP_Emails;
use AffWP\Admin\Notices_Registry;
use Affiliate_WP_Admin_Notices;
use AffWP\Core\License\License_Data;

/**
 * The main DRM class that controls all related behaviors.
 */
class DRM_Controller {

	/**
	 * Number of days after the last check that it takes to enter the Low level of warnings stage for unlicensed sites.
	 *
	 * @since 2.21.1
	 */
	const UNLICENSED_LOW_LEVEL_STARTS_AT = 14;

	/**
	 * Number of days after the last check that it takes to enter the Medium level of warnings stage for unlicensed sites.
	 *
	 * @since 2.21.1
	 */
	const UNLICENSED_MEDIUM_LEVEL_STARTS_AT = 21;

	/**
	 * Number of days after the last check that it takes to lock AffiliateWP features for unlicensed sites.
	 *
	 * @since 2.21.1
	 */
	const UNLICENSED_LOCKED_STARTS_AT = 30;

	/**
	 * Number of days after the last check that it takes to enter the Low level of warnings stage for sites with invalid licenses.
	 *
	 * @since 2.21.1
	 */
	const INVALID_LICENSE_MEDIUM_LEVEL_STARTS_AT = 7;

	/**
	 * Number of days after the last check that it takes to lock AffiliateWP features for sites with invalid licenses.
	 *
	 * @since 2.21.1
	 */
	const INVALID_LICENSE_LOCKED_STARTS_AT = 21;

	/**
	 * The amount of time that a notice should stay dismissed.
	 *
	 * @since 2.21.1
	 */
	const NOTICE_DISMISS_TIMEOUT = DAY_IN_SECONDS;

	/**
	 * License general info.
	 *
	 * @since 2.21.1
	 *
	 * @var array
	 */
	private array $license_info;

	/**
	 * The last saved DRM state.
	 *
	 * @since 2.21.1
	 *
	 * @var string
	 */
	private string $current_state;

	/**
	 * The DRM level the customer is.
	 *
	 * @since 2.21.1
	 *
	 * @var string
	 */
	private string $level;

	/**
	 * The DRM UTM links.
	 *
	 * @since 2.21.1
	 *
	 * @var array
	 */
	private array $links;

	/**
	 * Enable/disable in-plugin notifications.
	 *
	 * @since 2.21.1
	 *
	 * @var bool
	 */
	private bool $is_in_plugin_notifications_enabled = false;

	/**
	 * The License Data object.
	 *
	 * @since 2.21.1
	 *
	 * @var License_Data
	 */
	private License_Data $license_data;

	/**
	 * Stores notifications data, like:
	 * - Level
	 * - In-plugin sent
	 * - Email sent
	 *
	 * @since 2.21.1
	 *
	 * @var array
	 */
	private array $notifications = array();

	/**
	 * Constructor.
	 *
	 * @since 2.21.1
	 */
	public function __construct() {

		// Initiate all DRM hooks.
		$this->hooks();
	}

	/**
	 * Set all hooks.
	 *
	 * @since 2.21.1
	 */
	private function hooks() {

		// Main actions, everything starts and can end here.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'maybe_shutdown' ) );

		// Actions that changes the plugin behavior based on the DRM state and level.
		add_action( 'affwp_notices_registry_init', array( $this, 'register_notices' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_body_class', array( $this, 'append_body_classes' ) );
		add_action( 'wp_ajax_affiliatewp_handle_license_form_submission', array( $this, 'handle_ajax_license_submission' ) );

		// Filters.
		add_filter( 'site_status_tests', array( $this, 'add_site_health_test' ) );
	}

	/**
	 * Initiate DRM.
	 *
	 * @since 2.21.1
	 */
	public function init() {

		// Restrict admin only.
		if ( ! is_admin() ) {
			return;
		}

		$this->license_data = new License_Data();

		// Store the results of license status and site activated flag.
		$this->license_info = array(
			'status'            => $this->license_data->check_status(),
			'is_site_activated' => $this->license_data->is_license_site_activated(),
		);

		// Load the last known DRM state.
		$this->current_state = $this->get_current_state();

		// Check for any updates in the current DRM state.
		$this->update_current_state();

		// Get the DRM level: active, initiated, low, med, locked.
		$this->level = $this->get_level();

		// Load UTM links.
		$this->links = $this->get_utm_links();

		// Try to notify the user if needed.
		$this->maybe_notify();
	}

	/**
	 * Remove any hooks that changes the behavior of the plugin if the license is fully activated.
	 *
	 * @since 2.21.1
	 */
	public function maybe_shutdown() {

		// Customer has an invalid, or it is unlicensed, DRM warnings or locks should stay.
		if ( 'active' !== $this->level ) {
			return;
		}

		// Remove actions.
		remove_action( 'affwp_notices_registry_init', array( $this, 'register_notices' ) );
		remove_action( 'admin_notices', array( $this, 'maybe_show_notices' ) );
		remove_action( 'admin_menu', array( $this, 'prevent_admin_pages_access' ) );
		remove_action( 'admin_menu', array( $this, 'deregister_submenus' ), 30 );
		remove_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		remove_action( 'admin_body_class', array( $this, 'append_body_classes' ) );
		remove_action( 'admin_init', array( $this, 'send_email' ), 30 );
	}

	/**
	 * Decide if notices should be shown to the user.
	 *
	 * @since 2.21.1
	 */
	public function maybe_show_notices() {

		if ( false !== get_transient( 'affwp_drm_notice' ) ) {
			return; // Do not show notices if the notice was temporarily dismissed already.
		}

		// On AffWP pages at med_level/locked, the modal is the touchpoint — skip the notice.
		if (
			in_array( $this->level, array( 'med_level', 'locked' ), true )
			&& affwp_is_admin_page()
		) {
			return;
		}

		// Display notices to the customer.
		Affiliate_WP_Admin_Notices::show_notice( "{$this->current_state}_{$this->level}" );
	}

	/**
	 * Check if the customer needs to be notified via in-plugin notifications or email.
	 *
	 * @since 2.21.1
	 *
	 * @return void
	 */
	private function maybe_notify() {

		// Customer is using a fully activated license, nothing to notify.
		if ( 'active' === $this->level ) {
			return;
		}

		$this->notifications = get_option( 'affwp_drm_notifications', array() );

		$notification_key = "{$this->current_state}_{$this->level}";

		// Notify by the in-plugin notification API.
		if (
			$this->is_in_plugin_notifications_enabled &&
			! isset( $this->notifications[ $notification_key ]['inplugin'] )
		) {
			$this->add_in_plugin_notification();
		}

		// Notify by email.
		if ( ! isset( $this->notifications[ $notification_key ]['email'] ) ) {
			$this->schedule_email_notification();
		}
	}

	/**
	 * Add the in-plugin notification.
	 *
	 * @since 2.21.1
	 */
	private function add_in_plugin_notification() {

		$notifications = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/in-plugin-notifications.php';

		$notification_key = "{$this->current_state}_{$this->level}";

		if ( ! isset( $notifications[ $notification_key ] ) ) {
			return; // If no notification is registered, it means we should not notify.
		}

		// Add the notification to the DB.
		if ( ! affiliate_wp()->notifications->add( $notifications[ $notification_key ] ) ) {
			return; // Could not notify.
		}

		update_option(
			'affwp_drm_notifications',
			array_merge_recursive(
				$this->notifications,
				array(
					$notification_key => array(
						'inplugin' => strtotime( current_time( 'mysql' ) ),
					),
				)
			),
			false
		);
	}

	/**
	 * Schedule the email notification.
	 *
	 * @since 2.21.1
	 */
	private function schedule_email_notification() {
		add_action( 'admin_init', array( $this, 'send_email' ), 30 );
	}

	/**
	 * Notify by email.
	 *
	 * @since 2.21.1
	 */
	public function send_email() {

		$emails = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/emails.php';

		$notification_key = "{$this->current_state}_{$this->level}";

		if ( ! isset( $emails[ $notification_key ] ) ) {
			return; // If no email is registered, it means we should not notify.
		}

		// Send the email.
		$subject = is_callable( $emails[ $notification_key ]['subject'] )
			? call_user_func( $emails[ $notification_key ]['subject'] )
			: $emails[ $notification_key ]['subject'];

		if ( ! ( new Affiliate_WP_Emails() )->send(
			affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) ),
			$subject,
			is_callable( $emails[ $notification_key ]['message'] )
				? call_user_func( $emails[ $notification_key ]['message'] )
				: $emails[ $notification_key ]['message']
		) ) {
			return; // Could not send the email.
		}

		update_option(
			'affwp_drm_notifications',
			array_merge_recursive(
				$this->notifications,
				array(
					$notification_key => array(
						'email' => strtotime( current_time( 'mysql' ) ),
					),
				)
			),
			false
		);
	}

	/**
	 * Enqueue the DRM lock scripts.
	 *
	 * @since 2.21.1
	 */
	public function enqueue_scripts() {

		if ( ! in_array( $this->level, array( 'locked', 'med_level' ), true ) ) {
			return; // No DRM locks.
		}

		// Only render the DRM panel on AffiliateWP admin pages.
		if ( ! affwp_is_admin_page() ) {
			return;
		}

		// Render the DRM dialog in admin_footer.
		add_action( 'admin_footer', array( $this, 'output_drm_dialog' ) );

		// Enqueue the DRM JS (handles auto-open, dismiss logic, AJAX form).
		affiliate_wp()->scripts->enqueue(
			'affiliatewp-drm',
			array(),
			sprintf(
				'%1$sadmin-drm%2$s.js',
				affiliate_wp()->scripts->get_path(),
				affiliate_wp()->scripts->get_suffix(),
			)
		);

		// Pass DRM config to JS.
		wp_localize_script( 'affiliatewp-drm', 'affwpDrm', $this->get_drm_js_config() );
	}

	/**
	 * Handle the user attempt of activating the license within the blocking modal.
	 *
	 * @since 2.21.1
	 */
	public function handle_ajax_license_submission() {

		if ( ! wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ), 'affiliatewp-education' ) ) {

			wp_send_json_error();

			die;
		}

		$status = $this->license_data->activation_status(
			sanitize_text_field( filter_input( INPUT_POST, 'license_key' ) ),
			true
		);

		if (
			false === $status['license_status'] &&
			( $status['affwp_notice'] ?? '' ) === 'license-http-failure'
		) {
			// If API call fails, set a transient so we can properly handle the customer's feedback.
			set_transient( 'affwp_drm_api_status', 'failed', 3 * HOUR_IN_SECONDS );

			// Send ajax messages.
			wp_send_json_success( $status );

			// Ensure it exits.
			die;
		}

		// It seems API is online again, we can remove the transient.
		delete_transient( 'affwp_drm_api_status' );

		wp_send_json_success( $status );

		die;
	}

	/**
	 * Output the DRM panel inline in the admin page.
	 *
	 * Renders as a positioned div inside #wpbody-content rather than
	 * a floating modal. The sidebar and toolbar are completely unaffected.
	 *
	 * @since 2.32.0
	 */
	public function output_drm_dialog() {

		$is_med_level = 'med_level' === $this->level;
		?>
		<dialog
			id="affwp-drm-panel"
			role="alertdialog"
			aria-labelledby="affwp-drm-title"
			class="affwp-ui fixed inset-0 hidden items-center justify-center p-4 border-0 bg-transparent w-auto h-auto max-w-none max-h-none"
			data-level="<?php echo esc_attr( $this->level ); ?>">
			<div class="absolute inset-0 bg-gray-500/50"></div>
			<div class="relative bg-white rounded-2xl p-8 sm:p-10 overflow-y-auto max-h-[calc(100vh-4rem)] max-w-2xl w-full shadow-2xl">
				<?php if ( $is_med_level ) : ?>
				<button type="button"
					id="affwp-drm-close"
					class="cursor-pointer absolute right-0 top-0 p-4 text-gray-400 hover:text-gray-500 z-10">
					<span class="sr-only"><?php esc_html_e( 'Close', 'affiliate-wp' ); ?></span>
					<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>
				</button>
				<?php endif; ?>
				<?php echo $this->get_drm_modal_header(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $this->get_drm_modal_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</dialog>
		<?php
	}

	/**
	 * Build the DRM modal header HTML.
	 *
	 * @since 2.27.0
	 *
	 * @return string Header HTML.
	 */
	private function get_drm_modal_header() {

		ob_start();
		?>
		<div class="text-center">
			<svg class="size-12 text-red-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.814-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
			</svg>
			<h3 id="affwp-drm-title" class="text-2xl font-bold text-gray-900 mb-2">
				<?php
				if ( 'med_level' === $this->level ) {
					$days = $this->get_days_until_lockout();
					printf(
						/* translators: %s - number of days wrapped in a span */
						_n(
							'Your affiliate management locks in <span class="text-red-600">%d day</span>',
							'Your affiliate management locks in <span class="text-red-600">%d days</span>',
							$days,
							'affiliate-wp'
						), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$days
					);
				} else {
					esc_html_e( 'AffiliateWP has been locked', 'affiliate-wp' );
				}
				?>
			</h3>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Build the DRM modal content HTML.
	 *
	 * @since 2.27.0
	 *
	 * @return string Content HTML.
	 */
	private function get_drm_modal_content() {

		$affiliate_count    = affiliate_wp()->affiliates->count();
		$pending_affiliates = affiliate_wp()->affiliates->count( array( 'status' => 'pending' ) );
		$unpaid_referrals   = affiliate_wp()->referrals->count_by_status( 'unpaid' );
		$unpaid_amount      = affiliate_wp()->referrals->unpaid_earnings( '', 0, false );
		$unpaid_formatted   = affwp_currency_filter( affwp_format_amount( $unpaid_amount ) );
		$api_last_status    = get_transient( 'affwp_drm_api_status' );

		$x_svg = '<svg class="size-5 text-red-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';

		ob_start();
		?>
		<div class="text-center">
			<p class="text-base text-gray-600 max-w-xl mx-auto mb-5">
				<?php
				if ( 'locked' === $this->level ) {
					esc_html_e( 'Renew your license to restore access immediately.', 'affiliate-wp' );
				} else {
					esc_html_e( 'Your AffiliateWP license has expired or is missing.', 'affiliate-wp' );
				}
				?>
			</p>

			<div class="inline-flex flex-col gap-2.5 mb-8 text-left">
				<?php if ( $affiliate_count > 0 ) : ?>
					<div class="flex items-center gap-2.5 text-base font-medium text-gray-800">
						<?php echo $x_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						if ( 'locked' === $this->level ) {
							printf(
								/* translators: %s - formatted affiliate count */
								esc_html__( '%s affiliates left unmanaged', 'affiliate-wp' ),
								number_format_i18n( $affiliate_count )
							);
						} else {
							printf(
								/* translators: %s - formatted affiliate count */
								esc_html__( 'Manage your %s affiliates', 'affiliate-wp' ),
								number_format_i18n( $affiliate_count )
							);
						}
						?>
					</div>
				<?php else : ?>
					<div class="flex items-center gap-2.5 text-base font-medium text-gray-800">
						<?php echo $x_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Register & manage affiliates', 'affiliate-wp' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $pending_affiliates > 0 ) : ?>
					<div class="flex items-center gap-2.5 text-base font-medium text-gray-800">
						<?php echo $x_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						if ( 'locked' === $this->level ) {
							printf(
								/* translators: %s - formatted pending affiliate count */
								esc_html__( '%s affiliates waiting for review', 'affiliate-wp' ),
								number_format_i18n( $pending_affiliates )
							);
						} else {
							printf(
								/* translators: %s - formatted pending affiliate count */
								esc_html__( 'Review %s pending affiliates', 'affiliate-wp' ),
								number_format_i18n( $pending_affiliates )
							);
						}
						?>
					</div>
				<?php endif; ?>

				<?php if ( $unpaid_referrals > 0 ) : ?>
					<div class="flex items-center gap-2.5 text-base font-medium text-gray-800">
						<?php echo $x_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						if ( 'locked' === $this->level && $unpaid_amount > 0 ) {
							printf(
								/* translators: 1: formatted currency amount, 2: formatted referral count */
								esc_html__( '%1$s owed across %2$s referrals', 'affiliate-wp' ),
								esc_html( $unpaid_formatted ),
								number_format_i18n( $unpaid_referrals )
							);
						} elseif ( $unpaid_amount > 0 ) {
							printf(
								/* translators: 1: formatted currency amount, 2: formatted referral count */
								esc_html__( 'Pay out %1$s across %2$s referrals', 'affiliate-wp' ),
								esc_html( $unpaid_formatted ),
								number_format_i18n( $unpaid_referrals )
							);
						} else {
							printf(
								/* translators: %s - formatted unpaid referral count */
								esc_html__( 'Process %s unpaid referrals', 'affiliate-wp' ),
								number_format_i18n( $unpaid_referrals )
							);
						}
						?>
					</div>
				<?php else : ?>
					<div class="flex items-center gap-2.5 text-base font-medium text-gray-800">
						<?php echo $x_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Disburse affiliate payouts', 'affiliate-wp' ); ?>
					</div>
				<?php endif; ?>

			</div>

			<div class="mb-6">
				<?php
				$cta_text = __( 'Get a license', 'affiliate-wp' );

				if ( 'invalid' === $this->current_state && 'locked' === $this->level ) {
					$cta_text = __( 'Restore access now', 'affiliate-wp' );
				} elseif ( 'invalid' === $this->current_state ) {
					$cta_text = __( 'Renew & restore access', 'affiliate-wp' );
				}

				affwp_button( array(
					'text'       => $cta_text,
					'variant'    => 'success',
					'size'       => 'lg',
					'href'       => $this->get_utm_link( 'pricing' ),
					'attributes' => array(
						'target'    => '_blank',
						'autofocus' => true,
					),
					'focus_ring' => false,
					'rounded'    => 'lg',
					'class'      => 'w-full justify-center sm:w-auto sm:px-12',
				) );
				?>
			</div>

			<?php $show_key_form = 'locked' === $this->level || 'failed' === $api_last_status; ?>
			<div class="border-t border-gray-200 pt-5 mt-2 max-w-sm mx-auto">
				<?php if ( ! $show_key_form ) : ?>
				<button
					id="affwp-drm-toggle-license-form"
					type="button"
					class="cursor-pointer w-full py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center justify-center gap-2">
					<?php esc_html_e( 'Already have a license key?', 'affiliate-wp' ); ?>
					<svg class="size-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
					</svg>
				</button>
				<?php else : ?>
				<p class="text-sm text-gray-500 text-center mb-3">
					<?php esc_html_e( 'Already have a license key? Find it on your', 'affiliate-wp' ); ?>
					<a href="<?php echo esc_url( $this->get_utm_link( 'account' ) ); ?>" target="_blank" class="text-affwp-brand-500 hover:underline"><?php esc_html_e( 'account page', 'affiliate-wp' ); ?></a>.
				</p>
				<?php endif; ?>

				<div data-license-form-wrapper <?php echo ! $show_key_form ? 'hidden' : ''; ?> class="<?php echo ! $show_key_form ? 'mt-4' : ''; ?>">
					<?php if ( ! $show_key_form ) : ?>
					<p class="text-sm text-gray-500 mb-3">
						<?php esc_html_e( 'Find it on your', 'affiliate-wp' ); ?>
						<a href="<?php echo esc_url( $this->get_utm_link( 'account' ) ); ?>" target="_blank" class="text-affwp-brand-500 hover:underline"><?php esc_html_e( 'account page', 'affiliate-wp' ); ?></a>.
					</p>
					<?php endif; ?>

					<form id="affwp-drm-ajax-license-activation" class="flex gap-2" autocomplete="off">
						<input
							name="license_key"
							required
							autocomplete="new-password"
							type="password"
							placeholder="<?php esc_attr_e( 'License Key', 'affiliate-wp' ); ?>"
							class="flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The function returns an escaped string.
							echo affiliatewp_tag_attr( 'value', 'failed' === $api_last_status ? $this->license_data->get_license_key() : '' );
							?>
						>
						<?php
						affwp_button( array(
							'text'    => 'failed' === $api_last_status
								? __( 'Try again', 'affiliate-wp' )
								: __( 'Activate', 'affiliate-wp' ),
							'variant' => 'secondary',
							'size'    => 'md',
							'type'    => 'submit',
							'class'   => 'bg-white',
						) );
						?>
					</form>

					<div id="affwp-drm-ajax-messages" class="mt-2 text-sm"></div>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get the number of days until backend lockout.
	 *
	 * @since 2.27.0
	 *
	 * @return int Days remaining.
	 */
	private function get_days_until_lockout() : int {

		try {
			$days_elapsed = $this->days_elapsed();
		} catch ( \Exception $e ) {
			$days_elapsed = 0;
		}

		$locked_starts_at = 'invalid' === $this->current_state
			? self::INVALID_LICENSE_LOCKED_STARTS_AT
			: self::UNLICENSED_LOCKED_STARTS_AT;

		return max( 1, $locked_starts_at - $days_elapsed );
	}

	/**
	 * Get DRM configuration for JavaScript.
	 *
	 * @since 2.27.0
	 *
	 * @return array JS config.
	 */
	private function get_drm_js_config() : array {

		return array(
			'level'            => $this->level,
			'modalId'          => 'affwp-drm-modal',
			'daysUntilLockout' => $this->get_days_until_lockout(),
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'affiliatewp-education' ),
			'strings'          => array(
				'buttonText' => __( 'Verify key', 'affiliate-wp' ),
				'success'    => __( 'Your license was activated successfully. Your page will be reloaded in 3s.', 'affiliate-wp' ),
				'error'      => __( 'Sorry, we could not activate your license at the moment. Please refresh your page and try again.', 'affiliate-wp' ),
				'invalid'    => __( 'The license key provided is invalid. Please check your license key and try again.', 'affiliate-wp' ),
				'expired'    => sprintf(
					/* translators: %s - Link to renew license */
					__( 'The license key provided is expired. <a href="%s" target="_blank">Renew your license</a>.', 'affiliate-wp' ),
					esc_url( $this->get_utm_link( 'account' ) )
				),
			),
		);
	}

	/**
	 * Append a class with the current site DRM level to the body.
	 *
	 * The class is useful for custom styles and other DOM manipulations.
	 *
	 * @since 2.21.1
	 *
	 * @param string $classes The string of wp classes.
	 */
	public function append_body_classes( string $classes ) : string {

		if ( ! affwp_is_admin_page() ) {
			return $classes;
		}

		// For med_level, JS controls the body class so blur can be removed on dismiss.
		if ( 'med_level' === $this->level ) {
			return $classes;
		}

		return "{$classes} affwp-drm-level-{$this->level}";
	}

	/**
	 * Check if Site Health messages should be displayed in admin.
	 *
	 * @since 2.21.1
	 *
	 * @param array $tests Site Health tests array.
	 *
	 * @return array
	 */
	public function add_site_health_test( array $tests ) : array {

		$tests['direct']['affiliatewp_drm'] = array(
			'label' => 'AffiliateWP',
			'test'  => array( $this, 'site_health_test' ),
		);

		return $tests;
	}

	/**
	 * Site Health test.
	 *
	 * @since 2.21.1
	 *
	 * @return array The test result.
	 */
	public function site_health_test() : array {

		if ( ! isset( $this->level ) ) {
			return array();
		}

		$messages = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/site-health.php';

		// Site is fully active.
		if ( 'active' === $this->level && isset( $messages['active'] ) ) {
			return $messages['active'];
		}

		// Site was locked.
		if ( 'locked' === $this->level && isset( $messages['locked'] ) ) {
			return $messages['locked'];
		}

		// Invalid (expired) license message.
		if ( 'invalid' === $this->current_state && isset( $messages['invalid'] ) ) {
			return $messages['invalid'];
		}

		// Unlicensed message.
		if ( 'unlicensed' === $this->current_state && isset( $messages['unlicensed'] ) ) {
			return $messages['unlicensed'];
		}

		// Can not find a Site Health message, nothing will be displayed.
		return array();
	}

	/**
	 * Register all DRM notices.
	 *
	 * @since 2.21.1
	 *
	 * @param Notices_Registry $registry Notices registry API.
	 */
	public function register_notices( Notices_Registry $registry ) {

		$notices = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/notices.php';

		if ( empty( $notices ) ) {
			return; // Can't find any notices.
		}

		foreach ( $notices as $notice_id => $notice ) {

			$registry->add_notice(
				$notice_id,
				array(
					'class'   => array(
						'notice',
						isset( $notice['level'] ) ? "notice-{$notice['level']}" : 'notice-error',
						'notice-drm',
					),
					'message' => function() use ( $notice ) {
						return is_callable( $notice['message'] )
							? call_user_func( $notice['message'] )
							: $notice['message'];
					},
				)
			);
		}
	}

	/**
	 * Calculate and return the number of days since the last time DRM was checked
	 * and returned a no-license or invalid license situation.
	 *
	 * @since 2.21.1
	 *
	 * @return int The number of days.
	 * @throws \Exception Could not generate DateTime results.
	 */
	private function days_elapsed() : int {

		$timestamp = $this->get_last_changed_state_time();

		if ( empty( $timestamp ) ) {
			return 0; // Invalid timestamp.
		}

		$start_date = new \DateTime( current_time( 'Y-m-d' ) );
		$end_date   = new \DateTime( wp_date( 'Y-m-d', $timestamp ) );
		$difference = $end_date->diff( $start_date );

		return absint( $difference->format( '%a' ) );
	}

	/**
	 * Retrieve the current DRM level based on the different states.
	 *
	 * This method utilizes specific constants to compare with the number of days elapsed since the last
	 * state update. The constant values are cumulative, enabling the determination of the current level
	 * of DRM the customer is in.
	 *
	 * The DRM levels are categorized as follows:
	 * - 'active': Indicates that the license is fully active.
	 * - 'initiated': Indicates the grace period started.
	 * - 'low_level': Represents the low phase of an unlicensed site or expired license.
	 * - 'med_level': Represents the medium phase of an unlicensed site or an expired license.
	 * - 'locked': Indicates a locked state due to an extended unlicensed period or an expired license.
	 *
	 * @since 2.21.1
	 *
	 * @return string The DRM level based on the current state.
	 */
	private function get_level() : string {

		// Customer is with a valid and active license.
		if ( 'valid' === $this->current_state ) {
			return 'active';
		}

		try {
			$days_elapsed = $this->days_elapsed();
		} catch ( \Exception $e ) {
			$days_elapsed = 0;
		}

		// Invalid license levels.
		if ( 'invalid' === $this->current_state ) {

			if ( $days_elapsed < self::INVALID_LICENSE_MEDIUM_LEVEL_STARTS_AT ) {
				return 'initiated';
			}

			if ( $days_elapsed < self::INVALID_LICENSE_LOCKED_STARTS_AT ) {
				return 'med_level';
			}

			return 'locked';
		}

		// Unlicensed levels.
		if ( $days_elapsed < self::UNLICENSED_LOW_LEVEL_STARTS_AT ) {
			return 'initiated';
		}

		if ( $days_elapsed < self::UNLICENSED_MEDIUM_LEVEL_STARTS_AT ) {
			return 'low_level';
		}

		if ( $days_elapsed < self::UNLICENSED_LOCKED_STARTS_AT ) {
			return 'med_level';
		}

		return 'locked';
	}

	/**
	 * Remove all DRM related metadata.
	 *
	 * @since 2.21.1
	 */
	public function clean_up_meta() {

		if ( 'valid' === $this->current_state ) {
			return; // The license is valid, nothing should have left at this point.
		}

		delete_option( 'affwp_drm_current_state' );
		delete_option( 'affwp_drm_last_changed_state_time' );
		delete_option( 'affwp_drm_notifications' );
		delete_transient( 'affwp_drm_notice' );
		delete_transient( 'affwp_drm_api_status' );
	}

	/**
	 * Update DRM options and transients accordingly to the new state.
	 *
	 * @since 2.21.1
	 *
	 * @param string $state The new state.
	 */
	private function update_state_metadata( string $state ) {

		// If state is going to change, ensure notifications meta are cleared up to start again.
		if ( get_option( 'affwp_drm_current_state' ) !== $state ) {
			delete_option( 'affwp_drm_notifications' );
		}

		// Update the current state at DB level.
		update_option( 'affwp_drm_current_state', $state, false );

		// Set the time the state has changed.
		update_option( 'affwp_drm_last_changed_state_time', strtotime( current_time( 'mysql' ) ), false );

		// Remove transient notices so we can start over.
		delete_transient( 'affwp_drm_notice' );
	}

	/**
	 * Updates the current DRM state.
	 *
	 * The data will be updated only if the current state has been changed compared to the license status returned,
	 * otherwise we will try to return as soon as we can to not hurt performance.
	 *
	 * @since 2.21.1
	 * @since 2.24.2 Updated to handle known license statuses for DRM.
	 */
	private function update_current_state() {

		// If it is an unknown license status, we ensure that any DRM notice show's up.
		if ( ! in_array(
			$this->license_info['status'] ?? '',
			[
				'valid',
				'invalid',
				'pending',
				'expired',
			],
			true
		) ) {
			$this->current_state = 'valid';
		}

		// License turned to valid, clean up old metadata.
		if (
			'valid' !== $this->current_state &&
			'valid' === $this->license_info['status'] &&
			$this->license_info['is_site_activated']
		) {

			// Clean up all existent meta.
			$this->clean_up_meta();

			// Update the current state.
			$this->current_state = 'valid';

			return;
		}

		// No license was informed yet.
		if (
			'unlicensed' !== $this->current_state &&
			(
				in_array( $this->license_info['status'], array( 'invalid', 'pending' ), true ) ||
				( 'valid' === $this->license_info['status'] && empty( $this->license_info['is_site_activated'] ) )
			)
		) {

			// Update the current state at execution level.
			$this->current_state = 'unlicensed';

			// Update metadata.
			$this->update_state_metadata( $this->current_state );

			return;
		}

		// The license has expired.
		if ( 'invalid' !== $this->current_state && 'expired' === $this->license_info['status'] ) {

			// Update the current state at execution level.
			$this->current_state = 'invalid';

			// Update metadata.
			$this->update_state_metadata( $this->current_state );
		}
	}

	/**
	 * Return the current state for DRM.
	 * Possible values:
	 *  - valid: it means that the license is fully active.
	 *  - invalid: it means an expired license.
	 *  - unlicensed: a license was not informed yet.
	 *
	 * @since 2.21.1
	 *
	 * @return string The current state.
	 */
	private function get_current_state() : string {

		return get_option( 'affwp_drm_current_state', 'valid' );
	}

	/**
	 * Return the last time the DRM state has changed.
	 *
	 * If returns a valid timestamp, is an indication that the site is
	 * under some DRM period: initiated, low, med or locked.
	 *
	 * @since 2.21.1
	 *
	 * @return int Timestamp.
	 */
	private function get_last_changed_state_time() : int {

		return get_option( 'affwp_drm_last_changed_state_time', 0 );
	}

	/**
	 * Retrieve all UTM links.
	 *
	 * @since 2.21.1
	 *
	 * @return array UTM links.
	 */
	private function get_utm_links() : array {

		$links = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/links.php';

		if ( ! empty( $links ) ) {
			return $links;
		}

		// Fallback links.
		return array();
	}


	/**
	 * Attempt to return an specific UTM link.
	 *
	 * @since 2.21.1
	 *
	 * @param string $page The page of the link, usually: home, account, support or pricing.
	 * @param string $purpose The purpose of the link, usually: general or email.
	 *
	 * @return string The UTM link.
	 */
	public function get_utm_link( string $page, string $purpose = 'general' ) : string {

		if ( ! isset( $this->level ) ) {
			return '';
		}

		// Try to return the requested link.
		if ( isset( $this->links[ $this->level ][ $purpose ][ $page ] ) ) {
			return $this->links[ $this->level ][ $purpose ][ $page ];
		}

		// Return the fallback link if a purpose can not be found.
		if (
			! isset( $this->links[ $this->level ][ $purpose ] ) &&
			isset( $this->links[ $page ] )
		) {
			return $this->links[ $page ];
		}

		// Can't find a link for the required UTM.
		return '';
	}

	/**
	 * Retrieves the URL for the license key field with a hash to redirect directly to the corresponding section.
	 *
	 * @since 2.21.1
	 *
	 * @return string The URL for the license key field.
	 */
	public function get_license_key_field_url() : string {
		return sprintf( '%s#license_key', affwp_admin_url( 'settings' ) );
	}
}
