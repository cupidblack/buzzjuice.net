<?php
/**
 * Admin: Cookie Consent
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Pages
 * @since       2.31.3
 */

namespace Affwp\Admin\Pages;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Cookie Consent Sub-page.
 *
 * Add interactive admin subpage that allows installing and activating the WPConsent plugin.
 *
 * @since 2.32.0
 */
class Cookie_Consent {

	/**
	 * Admin menu page slug.
	 *
	 * @since 2.32.0
	 *
	 * @var string
	 */
	const SLUG = 'affiliate-wp-cookie-consent';

	/**
	 * Configuration.
	 *
	 * @since 2.32.0
	 *
	 * @var array
	 */
	private $config = [
		'lite_plugin'            => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
		'pro_plugin'             => 'wpconsent-premium/wpconsent-premium.php',
		'lite_wporg_url'         => 'https://wordpress.org/plugins/wpconsent-cookies-banner-privacy-suite/',
		'lite_download_url'      => 'https://downloads.wordpress.org/plugin/wpconsent-cookies-banner-privacy-suite.zip',
		'wpconsent_dashboard'    => 'admin.php?page=wpconsent',
		'wpconsent_setup_wizard' => 'admin.php?page=wpconsent-onboarding',
	];

	/**
	 * Runtime data used for generating page HTML.
	 *
	 * @since 2.32.0
	 *
	 * @var array
	 */
	private $output_data = [];

	/**
	 * Constructor.
	 *
	 * @since 2.32.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Get the instance of a class and store it in itself.
	 *
	 * @since 2.32.0
	 */
	public static function get_instance() {

		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Renders the Cookie Consent page content.
	 *
	 * @since 2.32.0
	 *
	 * @return void
	 */
	public static function display() {
		self::get_instance()->output();
	}

	/**
	 * Hooks.
	 *
	 * @since 2.32.0
	 */
	private function hooks() {

		// Check what page we are on.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.CSRF.NonceVerification

		// Only load if we are actually on the Cookie Consent page.
		if ( self::SLUG !== $page ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'redirect_to_wpconsent_dashboard' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue JS and CSS files.
	 *
	 * @since 2.32.0
	 */
	public function enqueue_assets() {

		$plugin_url = untrailingslashit( AFFILIATEWP_PLUGIN_URL );
		$min        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Lightweight, accessible and responsive lightbox.
		wp_enqueue_style(
			'affiliate-wp-lity',
			"{$plugin_url}/assets/css/lity{$min}.css",
			null,
			'2.4.1'
		);

		wp_enqueue_script(
			'affiliate-wp-lity',
			"{$plugin_url}/assets/js/lity{$min}.js",
			[ 'jquery' ],
			'2.4.1',
			true
		);

		// Shared AM plugin page style.
		wp_enqueue_style(
			'affiliate-wp-am-plugin-page',
			"{$plugin_url}/assets/css/am-plugin-page{$min}.css",
			null,
			AFFILIATEWP_VERSION
		);

		wp_enqueue_script(
			'affiliate-wp-cookie-consent-page',
			"{$plugin_url}/assets/js/cookie-consent-page{$min}.js",
			[ 'jquery' ],
			AFFILIATEWP_VERSION,
			true
		);

		wp_localize_script(
			'affiliate-wp-cookie-consent-page',
			'affiliate_wp_cookie_consent',
			$this->get_js_strings()
		);
	}

	/**
	 * JS Strings.
	 *
	 * @since 2.32.0
	 *
	 * @return array Array of strings.
	 */
	protected function get_js_strings() {

		$error_could_not_install = sprintf(
			wp_kses( /* translators: %s - Lite plugin download URL. */
				__( 'Could not install the plugin automatically. Please <a href="%s">download</a> it and install it manually.', 'affiliate-wp' ),
				[
					'a' => [
						'href' => true,
					],
				]
			),
			esc_url( $this->config['lite_download_url'] )
		);

		$error_could_not_activate = sprintf(
			wp_kses( /* translators: %s - Lite plugin download URL. */
				__( 'Could not activate the plugin. Please activate it on the <a href="%s">Plugins page</a>.', 'affiliate-wp' ),
				[
					'a' => [
						'href' => true,
					],
				]
			),
			esc_url( admin_url( 'plugins.php' ) )
		);

		return [
			'nonce'                    => wp_create_nonce( 'affiliate-wp-admin' ),
			'ajax_url'                 => admin_url( 'admin-ajax.php' ),
			'installing'               => esc_html__( 'Installing...', 'affiliate-wp' ),
			'activating'               => esc_html__( 'Activating...', 'affiliate-wp' ),
			'activated'                => esc_html__( 'WPConsent Installed & Activated', 'affiliate-wp' ),
			'install_now'              => esc_html__( 'Install Now', 'affiliate-wp' ),
			'activate_now'             => esc_html__( 'Activate Now', 'affiliate-wp' ),
			'download_now'             => esc_html__( 'Download Now', 'affiliate-wp' ),
			'plugins_page'             => esc_html__( 'Go to Plugins page', 'affiliate-wp' ),
			'error_could_not_install'  => $error_could_not_install,
			'error_could_not_activate' => $error_could_not_activate,
			'manual_install_url'       => $this->config['lite_download_url'],
			'manual_activate_url'      => admin_url( 'plugins.php' ),
			'setup_wizard_url'         => esc_url( admin_url( $this->config['wpconsent_setup_wizard'] ) ),
			'launch_setup_wizard'      => esc_html__( 'Launch Setup Wizard', 'affiliate-wp' ),
		];
	}

	/**
	 * Generate and output page HTML.
	 *
	 * @since 2.32.0
	 */
	public function output() {
		?>
		<div id="affwp-am-plugin-cookie-consent" class="wrap affwp-am-plugin-page">
			<?php
			$this->output_section_heading();
			$this->output_section_screenshot();
			$this->output_section_step_install();
			$this->output_section_step_setup();
			?>
		</div>
		<?php
	}

	/**
	 * Generate and output heading section HTML.
	 *
	 * @since 2.32.0
	 */
	private function output_section_heading() {

		printf(
			'<section class="top">
				<img class="img-top" src="%1$s" alt="%2$s"/>
				<h1>%3$s</h1>
				<p>%4$s</p>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/cookie-consent/logo-lockup.svg' ),
			esc_attr__( 'AffiliateWP + WPConsent', 'affiliate-wp' ),
			esc_html__( 'GDPR-Compliant Cookie Consent That Doesn\'t Break Affiliate Tracking', 'affiliate-wp' ),
			esc_html__( 'Cookie consent banners block affiliate tracking cookies before a visitor opts in. That means referrals go unrecorded and commissions go unpaid. AffiliateWP includes a built-in integration with WPConsent that preserves affiliate attribution even when cookies are blocked, so your affiliates always get credit.', 'affiliate-wp' )
		);
	}

	/**
	 * Generate and output screenshot section HTML.
	 *
	 * @since 2.32.0
	 */
	private function output_section_screenshot() {

		printf(
			'<section class="screenshot">
				<div class="cont">
					<img src="%1$s" alt="%2$s"/>
					<a href="%3$s" class="hover" data-lity></a>
				</div>
				<ul>
					<li>%4$s</li>
					<li>%5$s</li>
					<li>%6$s</li>
					<li>%7$s</li>
				</ul>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/cookie-consent/screenshot-thumb.png' ),
			esc_attr__( 'WPConsent cookie consent banner', 'affiliate-wp' ),
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/cookie-consent/screenshot-full.png' ),
			esc_html__( 'AffiliateWP\'s built-in integration preserves affiliate attribution even when cookies are blocked.', 'affiliate-wp' ),
			esc_html__( 'Professional consent banner that matches your site\'s design.', 'affiliate-wp' ),
			esc_html__( 'Zero configuration. Install WPConsent and tracking is automatically protected.', 'affiliate-wp' ),
			esc_html__( 'Free and open source. Self-hosted so your data stays on your site.', 'affiliate-wp' )
		);
	}

	/**
	 * Generate and output step 'Install' section HTML.
	 *
	 * @since 2.32.0
	 */
	private function output_section_step_install() {

		$step = $this->get_data_step_install();

		if ( empty( $step ) ) {
			return;
		}

		$button_format       = '<button class="button %3$s" data-plugin="%1$s" data-action="%4$s">%2$s</button>';
		$button_allowed_html = [
			'button' => [
				'class'       => true,
				'data-plugin' => true,
				'data-action' => true,
			],
		];

		if (
			! $this->output_data['plugin_installed'] &&
			! $this->output_data['pro_plugin_installed'] &&
			! current_user_can( 'install_plugins' )
		) {
			$button_format       = '<a class="link" href="%1$s" target="_blank" rel="nofollow noopener">%2$s <span aria-hidden="true" class="dashicons dashicons-external"></span></a>';
			$button_allowed_html = [
				'a'    => [
					'class'  => true,
					'href'   => true,
					'target' => true,
					'rel'    => true,
				],
				'span' => [
					'class'       => true,
					'aria-hidden' => true,
				],
			];
		}

		$button = sprintf( $button_format, esc_attr( $step['plugin'] ), esc_html( $step['button_text'] ), esc_attr( $step['button_class'] ), esc_attr( $step['button_action'] ) );

		printf(
			'<section class="step step-install">
				<aside class="num">
					<img src="%1$s" alt="%2$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%3$s</h2>
					<p>%4$s</p>
					%5$s
				</div>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/' . $step['icon'] ),
			esc_attr__( 'Step 1', 'affiliate-wp' ),
			esc_html( $step['heading'] ),
			esc_html( $step['description'] ),
			wp_kses( $button, $button_allowed_html )
		);
	}

	/**
	 * Generate and output step 'Setup' section HTML.
	 *
	 * @since 2.32.0
	 */
	private function output_section_step_setup() {

		$step = $this->get_data_step_setup();

		if ( empty( $step ) ) {
			return;
		}

		printf(
			'<section class="step step-setup %1$s">
				<aside class="num">
					<img src="%2$s" alt="%3$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%4$s</h2>
					<p>%5$s</p>
					<button class="button %6$s" data-url="%7$s">%8$s</button>
				</div>
			</section>',
			esc_attr( $step['section_class'] ),
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/' . $step['icon'] ),
			esc_attr__( 'Step 2', 'affiliate-wp' ),
			esc_html__( 'Set Up WPConsent', 'affiliate-wp' ),
			esc_html__( 'WPConsent has an intuitive setup wizard to configure your consent banner and cookie blocking.', 'affiliate-wp' ),
			esc_attr( $step['button_class'] ),
			esc_url( admin_url( $this->config['wpconsent_setup_wizard'] ) ),
			esc_html( $step['button_text'] )
		);
	}

	/**
	 * Step 'Install' data.
	 *
	 * @since 2.32.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_install() {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$step = [];

		$step['heading']     = esc_html__( 'Install & Activate WPConsent', 'affiliate-wp' );
		$step['description'] = esc_html__( 'Install the free WPConsent plugin to add cookie consent and protect your affiliate commissions.', 'affiliate-wp' );

		$this->output_data['all_plugins']          = get_plugins();
		$this->output_data['plugin_installed']     = array_key_exists( $this->config['lite_plugin'], $this->output_data['all_plugins'] );
		$this->output_data['pro_plugin_installed'] = array_key_exists( $this->config['pro_plugin'], $this->output_data['all_plugins'] );
		$this->output_data['plugin_activated']     = false;

		if ( ! $this->output_data['plugin_installed'] && ! $this->output_data['pro_plugin_installed'] ) {

			$step['icon']          = 'step-1.svg';
			$step['button_text']   = esc_html__( 'Install WPConsent', 'affiliate-wp' );
			$step['button_class']  = 'button-primary';
			$step['button_action'] = 'install';
			$step['plugin']        = $this->config['lite_download_url'];

			if ( ! current_user_can( 'install_plugins' ) ) {

				$step['heading']     = esc_html__( 'WPConsent', 'affiliate-wp' );
				$step['description'] = '';
				$step['button_text'] = esc_html__( 'WPConsent on WordPress.org', 'affiliate-wp' );
				$step['plugin']      = $this->config['lite_wporg_url'];
			}
		} else {

			$this->output_data['plugin_activated'] = is_plugin_active( $this->config['lite_plugin'] ) || is_plugin_active( $this->config['pro_plugin'] );
			$step['icon']                          = $this->output_data['plugin_activated'] ? 'step-complete.svg' : 'step-1.svg';
			$step['plugin']                        = $this->output_data['pro_plugin_installed'] ? $this->config['pro_plugin'] : $this->config['lite_plugin'];

			if ( $this->output_data['plugin_activated'] ) {
				$step['button_text']   = esc_html__( 'WPConsent Installed & Activated', 'affiliate-wp' );
				$step['button_class']  = 'grey disabled';
				$step['button_action'] = '';
			} else {
				$step['heading']       = esc_html__( 'Activate WPConsent', 'affiliate-wp' );
				$step['description']   = esc_html__( 'WPConsent is installed but not active. Activate it to add cookie consent and protect your affiliate commissions.', 'affiliate-wp' );
				$step['button_text']   = esc_html__( 'Activate WPConsent', 'affiliate-wp' );
				$step['button_class']  = 'button-primary';
				$step['button_action'] = 'activate';
			}
		}

		return $step;
	}

	/**
	 * Step 'Setup' data.
	 *
	 * @since 2.32.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_setup() {

		$step = [
			'icon' => 'step-2.svg',
		];

		if ( $this->output_data['plugin_activated'] ) {
			$step['section_class'] = '';
			$step['button_class']  = 'button-primary';
			$step['button_text']   = esc_html__( 'Launch Setup Wizard', 'affiliate-wp' );
		} else {
			$step['section_class'] = 'grey';
			$step['button_class']  = 'grey disabled';
			$step['button_text']   = esc_html__( 'Start Setup', 'affiliate-wp' );
		}

		return $step;
	}

	/**
	 * Redirect to WPConsent dashboard if already active.
	 *
	 * @since 2.32.0
	 */
	public function redirect_to_wpconsent_dashboard() {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$lite_file = WP_PLUGIN_DIR . '/' . $this->config['lite_plugin'];
		$pro_file  = WP_PLUGIN_DIR . '/' . $this->config['pro_plugin'];

		if (
			( file_exists( $lite_file ) && is_plugin_active( $this->config['lite_plugin'] ) ) ||
			( file_exists( $pro_file ) && is_plugin_active( $this->config['pro_plugin'] ) )
		) {
			wp_safe_redirect( admin_url( $this->config['wpconsent_dashboard'] ) );
			exit;
		}
	}
}

// Init instance.
Cookie_Consent::get_instance();
