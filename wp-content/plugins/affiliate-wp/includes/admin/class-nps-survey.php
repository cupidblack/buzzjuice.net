<?php
/**
 * NPS Survey
 *
 * Displays a bottom-right slide-up toast prompting admins to rate AffiliateWP
 * after 30 days of use. Scores are saved locally and relayed to Cloudflare.
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @since       2.31.3
 */

namespace AffiliateWP\Admin;

use AffWP\Core\License\License_Data;
use Sandhills\Utils\Persistent_Dismissible;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NPS Survey class.
 *
 * @since 2.31.3
 */
class NPS_Survey {

	/**
	 * Notice ID for Persistent_Dismissible.
	 *
	 * @since 2.31.3
	 * @var string
	 */
	const NOTICE_ID = 'affwp-nps-survey';

	/**
	 * Days after install before showing the survey.
	 *
	 * @since 2.31.3
	 * @var int
	 */
	const DAYS_BEFORE_SHOWING = 30;

	/**
	 * Dismiss lifespan in seconds (90 days / quarterly).
	 *
	 * @since 2.31.3
	 * @var int
	 */
	const DISMISS_LIFESPAN = 7776000;

	/**
	 * Cloudflare API base URL.
	 *
	 * @since 2.31.3
	 * @var string
	 */
	const CF_API_BASE = 'https://affwp-nps-survey.pages.dev/api';

	/**
	 * Cloudflare config URL.
	 *
	 * @since 2.31.3
	 * @var string
	 */
	const CF_CONFIG_URL = 'https://affwp-nps-survey.pages.dev/config.json';

	/**
	 * WP option key for NPS data (stored in user meta).
	 *
	 * @since 2.31.3
	 * @var string
	 */
	const OPTION_KEY = 'affwp_nps';

	/**
	 * WP option key for site-level impression count.
	 *
	 * Stored as a wp_option (not user meta) so multiple admins
	 * don't double-count impressions for the same site.
	 *
	 * @since 2.31.3
	 * @var string
	 */
	const IMPRESSIONS_KEY = 'affwp_nps_impressions';

	/**
	 * Constructor.
	 *
	 * @since 2.31.3
	 */
	public function __construct() {
		add_action( 'affwp_daily_scheduled_events', array( $this, 'sync_to_cloudflare' ) );
		add_action( 'delete_user', array( $this, 'delete_user_data' ) );

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		add_action( 'admin_footer', array( $this, 'render' ) );
		add_action( 'wp_ajax_affwp_nps_score', array( $this, 'handle_score' ) );
		add_action( 'wp_ajax_affwp_nps_comment', array( $this, 'handle_comment' ) );
		add_action( 'wp_ajax_affwp_dismiss_nps', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Whether the survey should be displayed.
	 *
	 * @since 2.31.3
	 *
	 * @return bool
	 */
	private function should_display() {

		/*
		 * Only on core AffiliateWP admin pages.
		 *
		 * We intentionally bypass affwp_is_admin_page() here because integrations
		 * (WooCommerce, EDD, etc.) filter it to return true on their own pages,
		 * which would cause the NPS survey to appear outside AffiliateWP screens.
		 */
		if ( ! isset( $_GET['page'] ) || 0 !== strpos( sanitize_text_field( $_GET['page'] ), 'affiliate-wp' ) && 0 !== strpos( sanitize_text_field( $_GET['page'] ), 'affwp' ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_affiliates' ) ) {
			return false;
		}

		// Check if 30+ days since install.
		$first_installed = get_option( 'affwp_first_installed', false );

		if ( empty( $first_installed ) || ( time() - $first_installed ) < ( self::DAYS_BEFORE_SHOWING * DAY_IN_SECONDS ) ) {
			return false;
		}

		// Check if this user already submitted a score.
		$nps_data = $this->get_nps_data();

		if ( null !== $nps_data['score'] ) {
			return false;
		}

		// Check if dismissed.
		$dismissed = Persistent_Dismissible::get(
			array(
				'id' => self::NOTICE_ID,
			)
		);

		if ( false !== $dismissed ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the NPS data from user meta for the current user.
	 *
	 * @since 2.31.3
	 *
	 * @param int $user_id Optional. Defaults to current user.
	 * @return array
	 */
	private function get_nps_data( $user_id = 0 ) {
		$defaults = array(
			'score'          => null,
			'comment'        => null,
			'email'          => null,
			'site_url'       => null,
			'license_key'    => null,
			'plugin_version' => null,
			'license_type'   => null,
			'timestamp'      => null,
			'synced'         => false,
			'response_id'    => null,
		);

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return $defaults;
		}

		$data = get_user_meta( $user_id, self::OPTION_KEY, true );

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		return wp_parse_args( $data, $defaults );
	}

	/**
	 * Update the NPS data in user meta for the current user.
	 *
	 * @since 2.31.3
	 *
	 * @param array $data    Data to merge with existing.
	 * @param int   $user_id Optional. Defaults to current user.
	 */
	private function update_nps_data( $data, $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return;
		}

		$existing = $this->get_nps_data( $user_id );
		$merged   = array_merge( $existing, $data );

		update_user_meta( $user_id, self::OPTION_KEY, $merged );
	}

	/**
	 * Get the remote config for follow-up questions.
	 *
	 * @since 2.31.3
	 *
	 * @return array
	 */
	private function get_config() {
		$config = get_transient( 'affwp_nps_config' );

		if ( is_array( $config ) ) {
			return $config;
		}

		return $this->get_default_config();
	}

	/**
	 * Refresh the remote config transient.
	 *
	 * Called via cron to avoid blocking page renders.
	 *
	 * @since 2.31.3
	 */
	private function refresh_config() {
		$response = wp_remote_get(
			self::CF_CONFIG_URL,
			array( 'timeout' => 5 )
		);

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$config = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_array( $config ) ) {
				set_transient( 'affwp_nps_config', $config, WEEK_IN_SECONDS );
			}
		}
	}

	/**
	 * Default config used when remote fetch fails.
	 *
	 * @since 2.31.3
	 *
	 * @return array
	 */
	private function get_default_config() {
		return array(
			'followup' => array(
				'detractor' => __( 'What could we do better?', 'affiliate-wp' ),
				'passive'   => __( 'What would make you rate us higher?', 'affiliate-wp' ),
				'promoter'  => __( 'What do you love most about AffiliateWP?', 'affiliate-wp' ),
			),
		);
	}

	/**
	 * Get the license type string.
	 *
	 * @since 2.31.3
	 *
	 * @return string
	 */
	private function get_license_type() {
		if ( ! class_exists( '\AffWP\Core\License\License_Data' ) ) {
			return '';
		}

		$license_data = new License_Data();
		$license_id   = $license_data->get_license_id();
		$license_type = $license_data->get_license_type( $license_id );

		if ( ! is_string( $license_type ) || empty( $license_type ) ) {
			return '';
		}

		return strtolower( $license_type );
	}

	/**
	 * Get the license key.
	 *
	 * @since 2.31.3
	 *
	 * @return string
	 */
	private function get_license_key() {
		if ( ! class_exists( '\AffWP\Core\License\License_Data' ) ) {
			return '';
		}

		$license = new License_Data();
		$key     = $license->get_license_key() ?: '';

		// Truncate to last 8 chars — avoids storing the full key externally.
		if ( strlen( $key ) > 8 ) {
			$key = substr( $key, -8 );
		}

		return $key;
	}

	/**
	 * Fetch a short-lived API token from the Cloudflare Worker.
	 *
	 * Tokens are cached in a transient for 4 minutes (they expire after 5).
	 *
	 * @since 2.31.3
	 *
	 * @return string|false Token string or false on failure.
	 */
	private function get_api_token() {
		$token = get_transient( 'affwp_nps_api_token' );

		if ( false !== $token ) {
			return $token;
		}

		$response = wp_remote_get(
			self::CF_API_BASE . '/token',
			array( 'timeout' => 5 )
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['token'] ) ) {
			return false;
		}

		// Cache for 4 minutes (tokens expire after 5).
		set_transient( 'affwp_nps_api_token', $body['token'], 4 * MINUTE_IN_SECONDS );

		return $body['token'];
	}

	/**
	 * Send an authenticated POST request to the Cloudflare API.
	 *
	 * Fetches a short-lived token from the Worker and includes it
	 * as a Bearer token. The signing key never leaves Cloudflare.
	 *
	 * @since 2.31.3
	 *
	 * @param string $endpoint API endpoint URL.
	 * @param array  $data     Request body data.
	 * @param int    $timeout  Request timeout in seconds.
	 * @return array|\WP_Error Response or error.
	 */
	private function signed_request( $endpoint, $data, $timeout = 5 ) {
		$token = $this->get_api_token();

		if ( false === $token ) {
			return new \WP_Error( 'nps_token_failed', 'Could not obtain API token.' );
		}

		return wp_remote_post(
			$endpoint,
			array(
				'body'    => wp_json_encode( $data ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
				'timeout' => $timeout,
			)
		);
	}

	/**
	 * Render the toast and modal in admin_footer.
	 *
	 * Relies on Alpine.js which is bundled in admin.js and available
	 * on all AffiliateWP admin pages (guarded by should_display()).
	 *
	 * @since 2.31.3
	 */
	public function render() {

		if ( ! $this->should_display() ) {
			return;
		}

		// Increment site-level impressions (throttled to once per hour per user to reduce DB writes).
		$transient_key = 'affwp_nps_imp_' . get_current_user_id();

		if ( false === get_transient( $transient_key ) ) {
			$count = (int) get_option( self::IMPRESSIONS_KEY, 0 );
			update_option( self::IMPRESSIONS_KEY, $count + 1, false );
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
		}

		// Get config for follow-up questions.
		$config = $this->get_config();
		$nonce  = wp_create_nonce( 'affwp_nps_nonce' );

		?>
		<script>
			window.affwpNps = {
				config: <?php echo wp_json_encode( $config ); ?>,
				nonce: <?php echo wp_json_encode( $nonce ); ?>
			};
		</script>
		<div
			id="affwp-nps-toast"
			class="affwp-ui"
			x-data="affwpNpsSurvey()"
			x-show="visible"
			x-transition:enter="transition ease-out duration-300"
			x-transition:enter-start="opacity-0 translate-y-4"
			x-transition:enter-end="opacity-100 translate-y-0"
			x-transition:leave="transition ease-in duration-200"
			x-transition:leave-start="opacity-100 translate-y-0"
			x-transition:leave-end="opacity-0 translate-y-4"
			x-cloak
			style="position:fixed; bottom:24px; right:24px; z-index:99999; width:380px;"
		>
			<div class="bg-white rounded-lg shadow-xl ring-1 ring-black/5 overflow-hidden">

				<!-- Close button -->
				<button
					type="button"
					@click="dismiss()"
					class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer z-10"
					aria-label="<?php esc_attr_e( 'Dismiss', 'affiliate-wp' ); ?>"
				>
					<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>
				</button>

				<!-- State 1: Score selection -->
				<div x-show="state === 'score'" class="p-5">
					<p class="text-sm font-medium text-gray-900 pr-6 mb-4">
						<?php esc_html_e( 'How likely are you to recommend AffiliateWP to a friend or colleague?', 'affiliate-wp' ); ?>
					</p>

					<div class="flex gap-1 mb-2">
						<template x-for="n in 11" :key="n - 1">
							<button
								type="button"
								@click="selectScore(n - 1)"
								:disabled="submitting"
								:class="scoreColor(n - 1)"
								class="flex-1 h-9 rounded text-xs font-semibold transition-colors cursor-pointer disabled:opacity-50"
								x-text="n - 1"
							></button>
						</template>
					</div>

					<div class="flex justify-between text-xs text-gray-400">
						<span><?php esc_html_e( 'Not likely', 'affiliate-wp' ); ?></span>
						<span><?php esc_html_e( 'Extremely likely', 'affiliate-wp' ); ?></span>
					</div>

					<p x-show="errorMessage" x-cloak x-text="errorMessage" class="text-xs text-red-600 mt-2"></p>
				</div>

				<!-- State 2: Comment -->
				<div x-show="state === 'comment'" x-cloak class="p-5">
					<p class="text-sm font-medium text-gray-900 mb-3" x-text="getFollowup()"></p>

					<textarea
						x-model="comment"
						class="w-full border border-gray-300 rounded-md p-2.5 text-sm resize-none focus:ring-2 focus:ring-affwp-brand-500 focus:border-affwp-brand-500"
						rows="3"
						maxlength="1000"
						:placeholder="'<?php echo esc_js( __( 'Your feedback (optional)', 'affiliate-wp' ) ); ?>'"
					></textarea>

					<div class="flex items-center justify-between mt-3">
						<button
							type="button"
							@click="submitComment()"
							:disabled="submitting"
							class="px-4 py-2 bg-affwp-brand-500 text-white text-sm font-medium rounded-md shadow-sm hover:bg-affwp-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-affwp-brand-500 transition-colors duration-200 ease-in-out cursor-pointer disabled:opacity-50"
						>
							<span x-show="!submitting"><?php esc_html_e( 'Submit', 'affiliate-wp' ); ?></span>
							<span x-show="submitting" x-cloak><?php esc_html_e( 'Sending...', 'affiliate-wp' ); ?></span>
						</button>

						<button
							type="button"
							@click="skip()"
							class="text-sm text-gray-500 hover:text-gray-700 cursor-pointer"
						>
							<?php esc_html_e( 'Skip', 'affiliate-wp' ); ?>
						</button>
					</div>
				</div>

				<!-- State 3: Thank you -->
				<div x-show="state === 'thanks'" x-cloak class="p-5 text-center">
					<svg class="mx-auto h-8 w-8 text-green-500 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
					</svg>
					<p class="text-sm font-medium text-gray-900">
						<?php esc_html_e( 'Thank you for your feedback!', 'affiliate-wp' ); ?>
					</p>
				</div>

			</div>
		</div>
		<script>
			function affwpNpsSurvey() {
				return {
					state: 'score',
					score: null,
					responseId: null,
					comment: '',
					visible: false,
					submitting: false,
					errorMessage: '',
					config: window.affwpNps.config,
					nonce: window.affwpNps.nonce,

					init() {
						setTimeout(() => this.visible = true, 2000);
					},

					getFollowup() {
						if ( this.score === null ) return '';
						const segment = this.score >= 9 ? 'promoter' : this.score >= 7 ? 'passive' : 'detractor';
						return this.config?.followup?.[segment] || "What's the main reason for your score?";
					},

					selectScore(n) {
						this.score = n;
						this.submitting = true;

						wp.ajax.send('affwp_nps_score', {
							data: { score: n, nonce: this.nonce },
							success: (response) => {
								this.responseId = response?.response_id || null;
								this.submitting = false;
								this.errorMessage = '';
								this.state = 'comment';
							},
							error: () => {
								this.submitting = false;
								this.errorMessage = '<?php echo esc_js( __( 'Something went wrong. Please try again.', 'affiliate-wp' ) ); ?>';
							}
						});
					},

					submitComment() {
						this.submitting = true;

						wp.ajax.send('affwp_nps_comment', {
							data: {
								response_id: this.responseId,
								comment: this.comment,
								nonce: this.nonce
							},
							success: () => {
								this.submitting = false;
								this.state = 'thanks';
								setTimeout(() => this.dismiss(), 3000);
							},
							error: () => {
								this.submitting = false;
								this.state = 'thanks';
								setTimeout(() => this.dismiss(), 3000);
							}
						});
					},

					skip() {
						this.dismiss();
					},

					dismiss() {
						this.visible = false;
						wp.ajax.send('affwp_dismiss_nps', {
							data: { nonce: this.nonce }
						});
					},

					scoreColor(n) {
						if (n >= 9) return 'bg-green-500 hover:bg-green-600 text-white';
						if (n >= 7) return 'bg-yellow-400 hover:bg-yellow-500 text-gray-900';
						if (n >= 4) return 'bg-orange-400 hover:bg-orange-500 text-white';
						return 'bg-red-500 hover:bg-red-600 text-white';
					}
				};
			}
		</script>
		<?php
	}

	/**
	 * AJAX handler for score submission.
	 *
	 * @since 2.31.3
	 */
	public function handle_score() {

		check_ajax_referer( 'affwp_nps_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_affiliates' ) ) {
			wp_send_json_error();
		}

		// Prevent duplicate score submissions.
		$existing = $this->get_nps_data();

		if ( null !== $existing['score'] ) {
			// Idempotent: score already saved (e.g. network hiccup on first attempt).
			// Return success so the JS can proceed to the comment step.
			wp_send_json_success( array( 'response_id' => $existing['response_id'] ?? null ) );
			return;
		}

		if ( ! isset( $_POST['score'] ) || ! is_numeric( $_POST['score'] ) ) {
			wp_send_json_error();
		}

		$score = (int) $_POST['score'];

		if ( $score < 0 || $score > 10 ) {
			wp_send_json_error();
		}

		$current_user = wp_get_current_user();

		// Save locally first (guaranteed).
		$this->update_nps_data(
			array(
				'score'          => $score,
				'email'          => $current_user->user_email,
				'site_url'       => home_url(),
				'license_key'    => $this->get_license_key(),
				'plugin_version' => AFFILIATEWP_VERSION,
				'license_type'   => $this->get_license_type(),
				'timestamp'      => time(),
				'synced'         => false,
			)
		);

		// Try to relay to Cloudflare.
		$cf_response = $this->signed_request(
			self::CF_API_BASE . '/score',
			array(
				'score'          => $score,
				'email'          => $current_user->user_email,
				'site_url'       => home_url(),
				'license_key'    => $this->get_license_key(),
				'plugin_version' => AFFILIATEWP_VERSION,
				'license_type'   => $this->get_license_type(),
				'impressions'    => (int) get_option( self::IMPRESSIONS_KEY, 0 ),
			)
		);

		$response_id = null;

		if ( ! is_wp_error( $cf_response ) && 200 === wp_remote_retrieve_response_code( $cf_response ) ) {
			$body        = json_decode( wp_remote_retrieve_body( $cf_response ), true );
			$response_id = $body['response_id'] ?? null;

			$this->update_nps_data(
				array(
					'synced'      => true,
					'response_id' => $response_id,
				)
			);
		}

		wp_send_json_success( array( 'response_id' => $response_id ) );
	}

	/**
	 * AJAX handler for comment submission.
	 *
	 * @since 2.31.3
	 */
	public function handle_comment() {

		check_ajax_referer( 'affwp_nps_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_affiliates' ) ) {
			wp_send_json_error();
		}

		$comment     = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );
		$response_id = isset( $_POST['response_id'] ) ? sanitize_text_field( wp_unslash( $_POST['response_id'] ) ) : null;

		// Save comment locally.
		$this->update_nps_data( array( 'comment' => $comment ) );

		// Relay to Cloudflare.
		$nps_data = $this->get_nps_data();
		$payload  = array( 'comment' => $comment );

		if ( $response_id ) {
			// Update existing CF record.
			$payload['response_id'] = $response_id;
		} else {
			// Score relay failed earlier — send full data so CF can create record.
			$payload['score']          = $nps_data['score'];
			$payload['email']          = $nps_data['email'];
			$payload['site_url']       = $nps_data['site_url'];
			$payload['license_key']    = $nps_data['license_key'];
			$payload['plugin_version'] = $nps_data['plugin_version'];
			$payload['license_type']   = $nps_data['license_type'];
			$payload['impressions']    = (int) get_option( self::IMPRESSIONS_KEY, 0 );
		}

		$cf_response = $this->signed_request( self::CF_API_BASE . '/comment', $payload );

		if ( ! is_wp_error( $cf_response ) && 200 === wp_remote_retrieve_response_code( $cf_response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $cf_response ), true );

			$this->update_nps_data(
				array(
					'synced'      => true,
					'response_id' => $body['response_id'] ?? $response_id,
				)
			);
		} else {
			// Relay failed — mark unsynced so the cron picks it up.
			$this->update_nps_data( array( 'synced' => false ) );
		}

		// Dismiss for 90 days.
		Persistent_Dismissible::set(
			array(
				'id'   => self::NOTICE_ID,
				'life' => self::DISMISS_LIFESPAN,
			)
		);

		wp_send_json_success();
	}

	/**
	 * AJAX handler for dismissing the survey.
	 *
	 * @since 2.31.3
	 */
	public function handle_dismiss() {

		check_ajax_referer( 'affwp_nps_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_affiliates' ) ) {
			wp_send_json_error();
		}

		Persistent_Dismissible::set(
			array(
				'id'   => self::NOTICE_ID,
				'life' => self::DISMISS_LIFESPAN,
			)
		);

		wp_send_json_success();
	}

	/**
	 * Clean up NPS user meta when a user is deleted.
	 *
	 * @since 2.31.3
	 *
	 * @param int $user_id The ID of the user being deleted.
	 */
	public function delete_user_data( $user_id ) {
		delete_user_meta( $user_id, self::OPTION_KEY );
	}

	/**
	 * Cron handler to retry syncing unsynced NPS data to Cloudflare.
	 *
	 * @since 2.31.3
	 */
	public function sync_to_cloudflare() {
		global $wpdb;

		// Refresh remote config for follow-up questions.
		$this->refresh_config();

		// Find users with NPS data (limit to 50 per run to avoid long cron jobs).
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s LIMIT 50",
				self::OPTION_KEY
			)
		);

		if ( empty( $user_ids ) ) {
			return;
		}

		// Sync unsynced scores and site-level impressions.
		$has_unscored_user = false;

		foreach ( $user_ids as $user_id ) {
			$nps_data = $this->get_nps_data( $user_id );

			if ( null === $nps_data['score'] ) {
				$has_unscored_user = true;
				continue;
			}

			// Skip if already synced.
			if ( $nps_data['synced'] ) {
				continue;
			}

			$payload = array(
				'score'          => $nps_data['score'],
				'comment'        => $nps_data['comment'] ?? '',
				'email'          => $nps_data['email'],
				'site_url'       => $nps_data['site_url'],
				'license_key'    => $nps_data['license_key'],
				'plugin_version' => $nps_data['plugin_version'],
				'license_type'   => $nps_data['license_type'],
				'impressions'    => (int) get_option( self::IMPRESSIONS_KEY, 0 ),
			);

			// If we have a response_id, update comment. Otherwise create full record.
			if ( ! empty( $nps_data['response_id'] ) ) {
				$payload['response_id'] = $nps_data['response_id'];
				$endpoint               = self::CF_API_BASE . '/comment';
			} else {
				$endpoint = self::CF_API_BASE . '/score';
			}

			$cf_response = $this->signed_request( $endpoint, $payload, 10 );

			if ( ! is_wp_error( $cf_response ) && 200 === wp_remote_retrieve_response_code( $cf_response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $cf_response ), true );

				$this->update_nps_data(
					array(
						'synced'      => true,
						'response_id' => $body['response_id'] ?? $nps_data['response_id'],
					),
					$user_id
				);
			}
		}

		// Sync site-level impressions (only if there are unscored users still seeing the toast).
		$total_impressions = (int) get_option( self::IMPRESSIONS_KEY, 0 );

		if ( $total_impressions > 0 && $has_unscored_user ) {
			$this->signed_request(
				self::CF_API_BASE . '/impression',
				array(
					'site_hash'   => md5( home_url() ),
					'impressions' => $total_impressions,
				)
			);
		}
	}
}
