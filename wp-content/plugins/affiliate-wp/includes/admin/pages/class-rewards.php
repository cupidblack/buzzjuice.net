<?php
/**
 * Admin: Rewards
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2022, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.30.3
 */

namespace Affwp\Admin\Pages;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Rewards Sub-page.
 *
 * Add interactive admin subpage that allows installing and activating the RewardsWP plugin.
 *
 * @since 2.30.3
 */
class Rewards {

	/**
	 * Admin menu page slug.
	 *
	 * @since 2.30.3
	 *
	 * @var string
	 */
	const SLUG = 'affiliate-wp-rewards';

	/**
	 * Configuration.
	 *
	 * @since 2.30.3
	 *
	 * @var array
	 */
	private $config = [
		'lite_plugin'       => 'rewardswp/rewardswp.php',
		'lite_wporg_url'    => 'https://wordpress.org/plugins/rewardswp/',
		'lite_download_url' => 'https://downloads.wordpress.org/plugin/rewardswp.zip',
		'rwp_dashboard'     => 'admin.php?page=rewardswp',
		'rwp_setup_wizard'  => 'admin.php?page=rewardswp&setup=1',
	];

	/**
	 * Runtime data used for generating page HTML.
	 *
	 * @since 2.30.3
	 *
	 * @var array
	 */
	private $output_data = [];

	/**
	 * Constructor.
	 *
	 * @since 2.30.3
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Get the instance of a class and store it in itself.
	 *
	 * @since 2.30.3
	 */
	public static function get_instance() {

		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Renders the Rewards page content.
	 *
	 * @since 2.30.3
	 *
	 * @return void
	 */
	public static function display() {
		self::get_instance()->output();
	}

	/**
	 * Hooks.
	 *
	 * @since 2.30.3
	 */
	private function hooks() {

		// Check what page we are on.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.CSRF.NonceVerification

		// Only load if we are actually on the Rewards page.
		if ( self::SLUG !== $page ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'redirect_to_rwp_dashboard' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue JS and CSS files.
	 *
	 * @since 2.30.3
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
			'affiliate-wp-rewards',
			"{$plugin_url}/assets/js/rewards{$min}.js",
			[ 'jquery' ],
			AFFILIATEWP_VERSION,
			true
		);

		wp_localize_script(
			'affiliate-wp-rewards',
			'affiliate_wp_rewards',
			$this->get_js_strings()
		);
	}

	/**
	 * JS Strings.
	 *
	 * @since 2.30.3
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
			'activated'                => esc_html__( 'RewardsWP Installed & Activated', 'affiliate-wp' ),
			'install_now'              => esc_html__( 'Install Now', 'affiliate-wp' ),
			'activate_now'             => esc_html__( 'Activate Now', 'affiliate-wp' ),
			'download_now'             => esc_html__( 'Download Now', 'affiliate-wp' ),
			'plugins_page'             => esc_html__( 'Go to Plugins page', 'affiliate-wp' ),
			'error_could_not_install'  => $error_could_not_install,
			'error_could_not_activate' => $error_could_not_activate,
			'manual_install_url'       => $this->config['lite_download_url'],
			'manual_activate_url'      => admin_url( 'plugins.php' ),
			'rwp_setup_wizard_url'     => esc_url( admin_url( $this->config['rwp_setup_wizard'] ) ),
			'launch_setup_wizard'      => esc_html__( 'Launch Setup Wizard', 'affiliate-wp' ),
		];
	}

	/**
	 * Generate and output page HTML.
	 *
	 * @since 2.30.3
	 */
	public function output() {
		?>
		<div id="affwp-am-plugin-rewards" class="wrap affwp-am-plugin-page">
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
	 * @since 2.30.3
	 */
	private function output_section_heading() {

		printf(
			'<section class="top">
				<img class="img-top" src="%1$s" alt="%2$s"/>
				<h1>%3$s</h1>
				<p>%4$s</p>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/rewards/logo-lockup.svg' ),
			esc_attr__( 'AffiliateWP + RewardsWP', 'affiliate-wp' ),
			esc_html__( 'Turn Customers Into Brand Advocates', 'affiliate-wp' ),
			esc_html__( 'RewardsWP adds a customer loyalty and referral program to your store — free. Built by the AffiliateWP team to complement your affiliate program.', 'affiliate-wp' )
		);
	}

	/**
	 * Generate and output screenshot section HTML.
	 *
	 * @since 2.30.3
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
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/rewards/rewardswp-rewards-widget-thumb.png' ),
			esc_attr__( 'RewardsWP rewards widget on a product page', 'affiliate-wp' ),
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/rewards/rewardswp-rewards-widget.png' ),
			esc_html__( 'Increase repeat purchases with loyalty points customers earn automatically.', 'affiliate-wp' ),
			esc_html__( 'Grow your customer base through word-of-mouth referrals.', 'affiliate-wp' ),
			esc_html__( 'Delight customers with a beautiful on-site rewards widget.', 'affiliate-wp' ),
			esc_html__( 'Built by the AffiliateWP team — works alongside your affiliate program.', 'affiliate-wp' )
		);
	}

	/**
	 * Generate and output step 'Install' section HTML.
	 *
	 * @since 2.30.3
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
	 * @since 2.30.3
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
			esc_html__( 'Set Up Your Rewards Program', 'affiliate-wp' ),
			esc_html__( 'Run the RewardsWP setup wizard to configure your loyalty points, rewards, and referral program.', 'affiliate-wp' ),
			esc_attr( $step['button_class'] ),
			esc_url( admin_url( $this->config['rwp_setup_wizard'] ) ),
			esc_html( $step['button_text'] )
		);
	}

	/**
	 * Step 'Install' data.
	 *
	 * @since 2.30.3
	 *
	 * @return array Step data.
	 */
	private function get_data_step_install() {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$step = [];

		$step['heading']     = esc_html__( 'Install & Activate RewardsWP', 'affiliate-wp' );
		$step['description'] = esc_html__( 'Install the free RewardsWP plugin to start rewarding your customers.', 'affiliate-wp' );

		$this->output_data['all_plugins']      = get_plugins();
		$this->output_data['plugin_installed'] = array_key_exists( $this->config['lite_plugin'], $this->output_data['all_plugins'] );
		$this->output_data['plugin_activated'] = false;

		if ( ! $this->output_data['plugin_installed'] ) {

			$step['icon']          = 'step-1.svg';
			$step['button_text']   = esc_html__( 'Install RewardsWP', 'affiliate-wp' );
			$step['button_class']  = 'button-primary';
			$step['button_action'] = 'install';
			$step['plugin']        = $this->config['lite_download_url'];

			if ( ! current_user_can( 'install_plugins' ) ) {

				$step['heading']     = esc_html__( 'RewardsWP', 'affiliate-wp' );
				$step['description'] = '';
				$step['button_text'] = esc_html__( 'RewardsWP on WordPress.org', 'affiliate-wp' );
				$step['plugin']      = $this->config['lite_wporg_url'];
			}
		} else {

			$this->output_data['plugin_activated'] = is_plugin_active( $this->config['lite_plugin'] );
			$step['icon']                          = $this->output_data['plugin_activated'] ? 'step-complete.svg' : 'step-1.svg';
			$step['button_text']                   = $this->output_data['plugin_activated'] ? esc_html__( 'RewardsWP Installed & Activated', 'affiliate-wp' ) : esc_html__( 'Activate RewardsWP', 'affiliate-wp' );
			$step['button_class']                  = $this->output_data['plugin_activated'] ? 'grey disabled' : 'button-primary';
			$step['button_action']                 = $this->output_data['plugin_activated'] ? '' : 'activate';
			$step['plugin']                        = $this->config['lite_plugin'];
		}

		return $step;
	}

	/**
	 * Step 'Setup' data.
	 *
	 * @since 2.30.3
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
	 * Redirect to RewardsWP dashboard.
	 * We need this function because `is_plugin_active()` is only available after `admin_init` action.
	 *
	 * @since 2.30.3
	 */
	public function redirect_to_rwp_dashboard() {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugin_file = WP_PLUGIN_DIR . '/' . $this->config['lite_plugin'];

		// Check file_exists() too — handles manual deletion where active_plugins option isn't cleaned up.
		if ( file_exists( $plugin_file ) && is_plugin_active( $this->config['lite_plugin'] ) ) {
			wp_safe_redirect( admin_url( $this->config['rwp_dashboard'] ) );
			exit;
		}
	}
}

// Init instance.
Rewards::get_instance();
