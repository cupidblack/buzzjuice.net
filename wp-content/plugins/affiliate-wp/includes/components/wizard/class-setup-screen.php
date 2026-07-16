<?php
/**
 * Wizard: Setup Screen
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Wizard
 * @copyright   Copyright (c) 2023, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.13.0
 */

namespace AffWP\Components\Wizard;

use AffWP\Components\Addons\Installer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Class for implementing the post-wizard setup screen.
 *
 * @since 2.13.0
 */
class Setup_Screen {
	/**
	 * Admin menu page slug.
	 *
	 * @since 2.13.0
	 *
	 * @var string
	 */
	const SLUG = 'affiliate-wp-setup-screen';

	/**
	 * Configuration.
	 *
	 * @since 2.13.0
	 *
	 * @var array
	 */
	private $config = [
		'portal_slug'   => 'affiliatewp-affiliate-portal/affiliatewp-affiliate-portal.php',
		'upgrade_url'   => 'https://affiliatewp.com/account/downloads?utm_source=WordPress&amp;utm_campaign=plugin&amp;utm_medium=setup&amp;utm_content=upgrade+Affiliate+Portal',
		'downloads_url' => 'https://affiliatewp.com/account/downloads/?utm_source=WordPress&amp;utm_campaign=plugin&amp;utm_medium=setup&amp;utm_content=download+Affiliate+Portal',
	];

	/**
	 * Current step number counter for dynamic numbering.
	 *
	 * @since 2.30.0
	 *
	 * @var int
	 */
	private $current_step_number = 0;

	/**
	 * Constructor.
	 *
	 * @since 2.13.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Get the instance of a class and store it in itself.
	 *
	 * @since 2.13.0
	 */
	public static function get_instance() {

		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Renders the Setup Screen page content.
	 *
	 * @since 2.13.0
	 */
	public static function display() {
		self::get_instance()->output();
	}

	/**
	 * Hooks.
	 *
	 * @since 2.13.0
	 */
	private function hooks() {

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_affwp_customize_registration_step', [ $this, 'ajax_customize_form_intent_complete' ] );
			add_action( 'wp_ajax_affwp_add_yourself_step', [ $this, 'ajax_add_yourself_intent_complete' ] );
		}

		// Check for setup screen page.
		// Check the display option bool.
		// Check if current user is allowed to save settings.
		if ( ! isset( $_GET['page'] ) ||
					self::SLUG !== filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ||
					! get_option( 'affwp_display_setup_screen' ) ||
					! current_user_can( 'manage_affiliate_options' ) ) {
			return;
		}

		// Check if setup screen should be dismissed.
		if ( ! empty( $_GET['affwp_dismiss_setup'] ) && $_GET['affwp_dismiss_setup'] ) {
			update_option( 'affwp_display_setup_screen', false );
			wp_safe_redirect( affwp_admin_url( 'affiliate-wp' ) );
			exit;
		}

		// Don't show any admin notices on this page.
		add_action(
			'in_admin_header',
			function () {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			},
			1000
		);

		add_action( 'admin_enqueue_scripts', [ $this, 'affwp_enqueue_setup_assets' ] );
	}

	/**
	 * Enqueue JS and CSS files.
	 *
	 * @since 2.13.0
	 */
	public function affwp_enqueue_setup_assets() {
		$plugin_url = untrailingslashit( AFFILIATEWP_PLUGIN_URL );
		$min        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Setup screen page script.
		wp_enqueue_script(
			'affiliate-wp-setup-screen',
			"{$plugin_url}/assets/js/setup-screen{$min}.js",
			[ 'jquery' ],
			AFFILIATEWP_VERSION,
			true
		);

		wp_localize_script(
			'affiliate-wp-setup-screen',
			'affiliatewpSetupScreen',
			$this->get_js_strings()
		);
	}

	/**
	 * JS Strings.
	 *
	 * @since 2.13.0
	 *
	 * @return array Array of strings.
	 */
	private function get_js_strings() {

		return [
			'nonce'                       => wp_create_nonce( 'affiliate-wp-admin' ),
			'ajax_url'                    => admin_url( 'admin-ajax.php' ),
			'accessing'                   => esc_html__( 'Accessing...', 'affiliate-wp' ),
			'adding'                      => esc_html__( 'Adding...', 'affiliate-wp' ),
			'installing'                  => esc_html__( 'Installing...', 'affiliate-wp' ),
			'activating'                  => esc_html__( 'Activating...', 'affiliate-wp' ),
			'setup_screen_error'          => esc_html__( 'Something went wrong. Please try again.', 'affiliate-wp' ),
			'step_complete'               => esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/step-complete.svg' ),
			'registration_step_complete'  => __( 'Affiliate Registration Form Created', 'affiliate-wp' ),
			'add_affiliate_step_complete' => __( 'Affiliate Added', 'affiliate-wp' ),
			'portal_step_complete'        => __( 'Affiliate Portal Activated', 'affiliate-wp' ),
			'manual_install_url'          => esc_url( $this->config['downloads_url'] ),
			'manual_activate_url'         => admin_url( 'plugins.php' ),
			'download_now'                => esc_html__( 'Download Now', 'affiliate-wp' ),
			'plugins_page'                => esc_html__( 'Go to Plugins page', 'affiliate-wp' ),
			'error_could_not_install'     => sprintf(
				wp_kses( /* translators: %s - AffiliateWP.com downloads page. */
					__( 'Could not install the plugin automatically. Please <a href="%s">download</a> it and install it manually.', 'affiliate-wp' ),
					[
						'a' => [
							'href' => true,
						],
					]
				),
				esc_url( $this->config['downloads_url'] )
			),
			'error_could_not_activate'    => sprintf(
				wp_kses( /* translators: %s - Admin plugins page URL. */
					__( 'Could not activate the plugin. Please activate it on the <a href="%s">Plugins page</a>.', 'affiliate-wp' ),
					[
						'a' => [
							'href' => true,
						],
					]
				),
				esc_url( admin_url( 'plugins.php' ) )
			),
		];
	}

	/**
	 * Generate and output page HTML.
	 *
	 * @since 2.13.0
	 */
	public function output() {
		?>
		<div id="affwp-setup-screen-page" class="wrap">
			<?php
			// Make localized strings available to Alpine.js
			?>
			<script>
				window.affiliatewpSetupScreen = <?php echo wp_json_encode( $this->get_js_strings() ); ?>;
			</script>
			<?php

			// Tailwind sections
			?>
			<div class="max-w-5xl affwp-ui">
				<!-- Setup Header -->
				<div class="pt-4 mb-4 sm:mb-6">
					<h2 class="mb-1 text-lg font-semibold text-gray-900 sm:text-xl">
						<?php esc_html_e( 'Setup', 'affiliate-wp' ); ?>
					</h2>
					<p class="text-sm text-gray-600 sm:text-base">
						<?php esc_html_e( 'Get your affiliate program up and running in just a few steps.', 'affiliate-wp' ); ?>
					</p>
				</div>





				<!-- Setup Cards -->
				<div class="space-y-4">
			<?php
			// Initialize step counter for dynamic numbering.
			$this->current_step_number = 0;

			$this->output_section_step_registration_form();
			$this->output_section_step_add_yourself();
			$this->output_section_step_stripe_payouts();
			$this->output_section_step_paypal_payouts();
			$this->output_section_step_store_credit();
			$this->output_section_step_portal_addon();
			?>
				</div><!-- /.space-y-4 -->
			</div><!-- /.affwp-ui -->
			<?php

			$this->output_section_dismiss();

			?>
		</div>
		<?php
	}

	/**
	 * Generate and output step 'Registration Form' section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_step_registration_form() {
		$step = $this->get_data_step_registration_form();

		if ( empty( $step ) ) {
			return;
		}

		// Increment step counter.
		++$this->current_step_number;

		// Build card content
		ob_start();
		?>
			<div class="flex gap-3 items-start sm:gap-4">
				<!-- Icon -->
				<div class="flex-shrink-0">
					<?php if ( 'step-complete.svg' === $step['icon'] ) : ?>
						<div class="flex justify-center items-center w-10 h-10 sm:h-12 sm:w-12">
							<svg class="w-10 h-10 text-green-600 sm:h-12 sm:w-12" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
							</svg>
						</div>
					<?php else : ?>
						<div class="flex justify-center items-center w-10 h-10 bg-gray-100 rounded-full sm:h-12 sm:w-12">
							<span class="text-xl font-bold text-gray-600 sm:text-2xl"><?php echo absint( $this->current_step_number ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<!-- Content and Action -->
				<div class="flex-1">
					<!-- Heading -->
					<div class="mb-1">
						<h3 class="text-base font-semibold text-gray-900 sm:text-lg">
							<?php echo esc_html( $step['heading'] ); ?>
						</h3>
					</div>

					<!-- Description -->
					<p class="mb-3 text-xs text-gray-600 sm:text-sm">
						<?php echo esc_html( $step['description'] ); ?>
					</p>

					<!-- Action button -->
					<div class="flex gap-2 items-center">
						<?php if ( 'step-complete.svg' === $step['icon'] ) : ?>
							<?php
							// Render secondary button for edit action.
							echo affwp_render_button(
								[
									'text'    => esc_html( $step['link_text'] ),
									'href'    => esc_url( $step['button_url'] ),
									'variant' => 'secondary',
									'size'    => 'sm',
								]
							);
							?>
						<?php else : ?>
							<?php
							// Use button component instead of raw HTML
							echo affwp_render_button(
								[
									'text'    => esc_html( $step['button_text'] ),
									'href'    => esc_url( $step['button_url'] ),
									'variant' => 'primary',
									'size'    => 'sm',
								]
							);
							?>
						<?php endif; ?>
					</div>
				</div>
		</div>
		<?php
		$card_content = ob_get_clean();

		// Render the card
		echo affwp_render_card(
			[
				'variant'   => 'default',
				'size'      => 'md',
				'content'   => $card_content,
				'hoverable' => true,
			]
		);
	}

	/**
	 * Generate and output step 'Add Yourself' as an affiliate section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_step_add_yourself() {
		$step = $this->get_data_step_add_yourself();

		if ( empty( $step ) ) {
			return;
		}

		// Increment step counter.
		++$this->current_step_number;

		// Using Card component with Alpine.js
		$is_completed = ( 'step-complete.svg' === $step['icon'] );

		// Prepare Alpine.js data attribute value
		$is_completed_js = $is_completed ? 'true' : 'false';
		$button_text_js  = esc_js( $step['button_text'] );

		$alpine_data_value = "{
				isLoading: false,
				error: '',
				showError: false,
				isCompleted: {$is_completed_js},
				buttonText: '{$button_text_js}',
				originalButtonText: '{$button_text_js}',
				async addYourself() {
					if (this.isLoading) return;

					this.error = '';
					this.showError = false;
					this.isLoading = true;
					this.buttonText = window.affiliatewpSetupScreen.adding;

					try {
						const response = await fetch(window.affiliatewpSetupScreen.ajax_url, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: new URLSearchParams({
								action: 'affwp_add_yourself_step',
								nonce: window.affiliatewpSetupScreen.nonce
							})
						});

						const data = await response.json();

						if (data.success) {
							this.buttonText = window.affiliatewpSetupScreen.add_affiliate_step_complete;
							this.isCompleted = true;
							// Reload page to show edit link
							setTimeout(() => window.location.reload(), 500);
						} else {
							this.error = window.affiliatewpSetupScreen.setup_screen_error;
							this.showError = true;
							this.buttonText = this.originalButtonText;
							this.isLoading = false;
						}
					} catch (error) {
						this.error = window.affiliatewpSetupScreen.setup_screen_error;
						this.showError = true;
						this.buttonText = this.originalButtonText;
						this.isLoading = false;
					}
				}
		}";

		// Build card content
		ob_start();
		?>
			<!-- Main content row -->
			<div class="flex gap-3 items-start sm:gap-4">
				<!-- Icon -->
				<div class="flex-shrink-0">
					<div x-show="isCompleted" class="flex justify-center items-center w-10 h-10 sm:h-12 sm:w-12">
						<svg class="w-10 h-10 text-green-600 sm:h-12 sm:w-12" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
						</svg>
					</div>
					<div x-cloak x-show="!isCompleted" class="flex justify-center items-center w-10 h-10 bg-gray-100 rounded-full sm:h-12 sm:w-12">
						<span class="text-xl font-bold text-gray-600 sm:text-2xl"><?php echo absint( $this->current_step_number ); ?></span>
					</div>
				</div>

				<!-- Content and Action -->
				<div class="flex-1">
					<!-- Heading -->
					<div class="mb-1">
						<h3 class="text-base font-semibold text-gray-900 sm:text-lg">
							<?php echo esc_html( $step['heading'] ); ?>
						</h3>
					</div>

					<!-- Description -->
					<p class="mb-3 text-xs text-gray-600 sm:text-sm">
						<?php echo esc_html( $step['description'] ); ?>
					</p>

					<!-- Action button -->
					<div class="flex flex-col gap-2 items-start">
						<div class="flex gap-2 items-center">
							<!-- Show button if not completed -->
							<div x-cloak x-show="!isCompleted">
								<?php
								// Render button using component system with Alpine.js support.
								echo affwp_render_button(
									[
										'text'         => esc_js( $step['button_text'] ),
										'variant'      => 'primary',
										'size'         => 'sm',
										'alpine_text'  => 'buttonText',
										'dynamic_icon' => [
											'name'     => 'loading',
											'show_condition' => 'isLoading',
											'position' => 'right',
										],
										'attributes'   => [
											'@click'    => 'addYourself',
											':disabled' => 'isLoading',
											':class'    => "{'cursor-not-allowed': isLoading, 'cursor-pointer': !isLoading}",
										],
									]
								);
								?>
							</div>

							<!-- Show edit link if completed and link exists -->
							<?php if ( ! empty( $step['edit_link'] ) ) : ?>
								<?php
								// Parse the edit link to extract URL and text
								preg_match( '/href="([^"]*)"[^>]*>([^<]*)</', $step['edit_link'], $matches );
								$edit_url  = ! empty( $matches[1] ) ? $matches[1] : '#';
								$edit_text = ! empty( $matches[2] ) ? $matches[2] : __( 'Edit Affiliate', 'affiliate-wp' );
								?>
								<div x-show="isCompleted" x-cloak>
									<?php
									// Render secondary button for edit affiliate action.
									echo affwp_render_button(
										[
											'text'    => esc_html( $edit_text ),
											'href'    => esc_url( $edit_url ),
											'variant' => 'secondary',
											'size'    => 'sm',
										]
									);
									?>
								</div>
							<?php endif; ?>
						</div>
						<!-- Error message with smooth height animation using Alpine Collapse -->
						<div x-show="showError" x-collapse class="mt-2">
							<p class="text-sm text-affwp-error" x-text="error"></p>
						</div>
					</div>
				</div>
		</div>
		<?php
		$card_content = ob_get_clean();

		// Render the card with Alpine.js x-data
		affwp_card(
			[
				'content'    => $card_content,
				'size'       => 'sm',
				'attributes' => [
					'x-data' => $alpine_data_value,
				],
				'class'      => 'p-4 sm:p-5 lg:p-6',
			]
		);
	}

	/**
	 * Generate and output step 'Portal Addon' section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_step_portal_addon() {
		$step = $this->get_data_step_portal_addon();

		if ( empty( $step ) ) {
			return;
		}

		// Increment step counter.
		++$this->current_step_number;

		$is_completed = ( 'step-complete.svg' === $step['icon'] );
		$is_upgrade   = ( 'upgrade' === $step['button_action'] );
		?>
			<div class="overflow-hidden bg-white rounded-lg border border-gray-200 transition-all duration-200 hover:border-gray-300"
				x-data="{
					isLoading: false,
					error: '',
					showError: false,
					isCompleted: <?php echo $is_completed ? 'true' : 'false'; ?>,
					isUpgrade: <?php echo $is_upgrade ? 'true' : 'false'; ?>,
					buttonText: '<?php echo esc_js( $step['button_text'] ); ?>',
					originalButtonText: '<?php echo esc_js( $step['button_text'] ); ?>',
					buttonAction: '<?php echo esc_js( $step['button_action'] ); ?>',
					buttonPlugin: '<?php echo esc_js( $step['button_plugin'] ); ?>',
					buttonUrl: '',
					async handlePortalAddon() {
						if (this.isLoading) return;

						// Handle goto-url action
						if (this.buttonAction === 'goto-url') {
							// Open Download Now in new window, others in same window
							if (this.buttonText === window.affiliatewpSetupScreen.download_now) {
								window.open(this.buttonUrl, '_blank');
							} else {
								window.location.href = this.buttonUrl;
							}
							return;
						}

						// Handle upgrade action
						if (this.buttonAction === 'upgrade') {
							window.open(this.buttonPlugin, '_blank');
							return;
						}

						this.error = '';
						this.showError = false;
						this.isLoading = true;

						// Update button text based on action
						if (this.buttonAction === 'activate') {
							this.buttonText = window.affiliatewpSetupScreen.activating;
						} else if (this.buttonAction === 'install') {
							this.buttonText = window.affiliatewpSetupScreen.installing;
						}

						// Determine AJAX action
						let ajaxAction = '';
						if (this.buttonAction === 'activate') {
							ajaxAction = 'affwp_activate_plugin';
						} else if (this.buttonAction === 'install') {
							ajaxAction = 'affwp_install_plugin';
						} else {
							this.isLoading = false;
							return;
						}

						try {
							const response = await fetch(window.affiliatewpSetupScreen.ajax_url, {
								method: 'POST',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded',
								},
								body: new URLSearchParams({
									action: ajaxAction,
									nonce: window.affiliatewpSetupScreen.nonce,
									plugin: this.buttonPlugin
								})
							});

							const data = await response.json();

							// Check if installation/activation was successful
							const isSuccessful = this.buttonAction === 'install' ?
								(data.success && data.data && data.data.is_activated) :
								data.success;

							if (isSuccessful) {
								this.buttonText = window.affiliatewpSetupScreen.portal_step_complete;
								this.isCompleted = true;
								this.isLoading = false;
							} else {
								// Handle failure - convert to download/activate link
								const activationFail = (this.buttonAction === 'install' && data.success && !data.data.is_activated) || this.buttonAction === 'activate';

								if (!activationFail) {
									// Installation failed
									this.buttonUrl = window.affiliatewpSetupScreen.manual_install_url;
									this.buttonText = window.affiliatewpSetupScreen.download_now;
									this.error = window.affiliatewpSetupScreen.error_could_not_install;
									this.showError = true;
								} else {
									// Activation failed
									this.buttonUrl = window.affiliatewpSetupScreen.manual_activate_url;
									this.buttonText = window.affiliatewpSetupScreen.plugins_page;
									this.error = window.affiliatewpSetupScreen.error_could_not_activate;
									this.showError = true;
								}

								this.buttonAction = 'goto-url';
								this.isLoading = false;
							}
						} catch (error) {
							this.buttonText = this.originalButtonText;
							this.error = window.affiliatewpSetupScreen.setup_screen_error;
							this.showError = true;
							this.isLoading = false;
						}
					}
				}">
				<div class="p-4 sm:p-5 lg:p-6">
					<!-- Main content row -->
					<div class="flex gap-3 items-start sm:gap-4">
						<!-- Icon -->
						<div class="flex-shrink-0">
							<div x-cloak x-show="isCompleted" class="flex justify-center items-center w-10 h-10 sm:h-12 sm:w-12">
								<svg class="w-10 h-10 text-green-600 sm:h-12 sm:w-12" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
						</svg>
					</div>
					<div x-cloak x-show="!isCompleted" class="flex justify-center items-center w-10 h-10 bg-gray-100 rounded-full sm:h-12 sm:w-12">
						<span class="text-xl font-bold text-gray-600 sm:text-2xl"><?php echo absint( $this->current_step_number ); ?></span>
					</div>
				</div>

						<!-- Content and Action -->
						<div class="flex-1">
							<!-- Heading and badges -->
							<div class="flex flex-wrap gap-2 items-center mb-1">
								<h3 class="text-base font-semibold text-gray-900 sm:text-lg">
									<?php
									// Extract just the heading text without HTML
									$heading_text = strip_tags( $step['heading'] );
									echo esc_html( $heading_text );
									?>
								</h3>
							</div>

							<!-- Description -->
							<p class="mb-3 text-xs text-gray-600 sm:text-sm">
								<?php
								// Extract description text
								$desc_text = strip_tags( $step['description'] );
								// Check if this is the upgrade version with special offer
								if ( strpos( $step['description'], 'Special Upgrade Offer' ) !== false ) {
									?>
									<?php esc_html_e( 'Using the Affiliate Portal addon, you can give your affiliates a premium experience, ensuring they have everything they need to perform.', 'affiliate-wp' ); ?>
									<span class="inline-flex items-center mt-2">
										<span class="mr-2 font-medium text-orange-600"><?php esc_html_e( 'Special Upgrade Offer:', 'affiliate-wp' ); ?></span>
										<span class="text-gray-600"><?php esc_html_e( 'Get 50% off the regular price, automatically applied at checkout.', 'affiliate-wp' ); ?></span>
									</span>
									<?php
								} else {
									echo esc_html( $desc_text );
								}
								?>
							</p>

							<!-- Action button -->
							<div class="flex flex-col gap-2 items-start">
								<!-- Show button if not completed -->
								<div x-cloak x-show="!isCompleted">
									<?php
									// Render button using component system with Alpine.js support.
									echo affwp_render_button(
										[
											'text'         => esc_js( $step['button_text'] ),
											'variant'      => $is_upgrade ? 'warning' : 'primary',
											'size'         => 'sm',
											'alpine_text'  => 'buttonText',
											'dynamic_icon' => [
												'name'     => 'loading',
												'show_condition' => 'isLoading',
												'position' => 'right',
											],
											'attributes'   => [
												'@click' => 'handlePortalAddon',
												':disabled' => 'isLoading',
												':class' => "{'cursor-not-allowed': isLoading, 'cursor-pointer': !isLoading}",
											],
										]
									);
									?>
								</div>
								<!-- Error message with smooth height animation using Alpine Collapse -->
								<div x-show="showError" x-collapse class="mt-2">
									<p class="text-sm text-affwp-error" x-html="error"></p>
								</div>
							</div>
						</div>
					</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Generate and output step 'Stripe Payouts' section HTML.
	 *
	 * @since 2.30.0
	 */
	private function output_section_step_stripe_payouts() {
		$step = $this->get_data_step_stripe_payouts();

		if ( empty( $step ) ) {
			return;
		}

		// Increment step counter.
		++$this->current_step_number;

		// Build card content
		ob_start();
		?>
			<div class="flex gap-3 items-start sm:gap-4">
				<!-- Icon -->
				<div class="flex-shrink-0">
					<?php if ( 'step-complete.svg' === $step['icon'] ) : ?>
						<div class="flex justify-center items-center w-10 h-10 sm:h-12 sm:w-12">
							<svg class="w-10 h-10 text-green-600 sm:h-12 sm:w-12" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
							</svg>
						</div>
					<?php else : ?>
						<div class="flex justify-center items-center w-10 h-10 bg-gray-100 rounded-full sm:h-12 sm:w-12">
							<span class="text-xl font-bold text-gray-600 sm:text-2xl"><?php echo absint( $this->current_step_number ); ?></span>
						</div>
					<?php endif ; ?>
				</div>

				<!-- Content and Action -->
				<div class="flex-1">
					<!-- Heading -->
					<div class="mb-1">
						<h3 class="text-base font-semibold text-gray-900 sm:text-lg">
							<?php echo esc_html( $step['heading'] ); ?>
						</h3>
					</div>

					<!-- Description -->
					<p class="mb-3 text-xs text-gray-600 sm:text-sm">
						<?php echo esc_html( $step['description'] ); ?>
					</p>

					<!-- Action button -->
					<div class="flex gap-2 items-center">
						<?php if ( 'step-complete.svg' === $step['icon'] ) : ?>
							<?php
							// Render secondary button for edit action.
							echo affwp_render_button(
								[
									'text'    => esc_html( $step['link_text'] ),
									'href'    => esc_url( $step['button_url'] ),
									'variant' => 'secondary',
									'size'    => 'sm',
								]
							);
							?>
						<?php else : ?>
							<?php
							// Use button component instead of raw HTML
							echo affwp_render_button(
								[
									'text'    => esc_html( $step['button_text'] ),
									'href'    => esc_url( $step['button_url'] ),
									'variant' => 'primary',
									'size'    => 'sm',
								]
							);
							?>
						<?php endif; ?>
					</div>
				</div>
		</div>
		<?php
		$card_content = ob_get_clean();

		// Render the card
		echo affwp_render_card(
			[
				'variant'   => 'default',
				'size'      => 'md',
				'content'   => $card_content,
				'hoverable' => true,
			]
		);
	}

	/**
	 * Generate and output step 'PayPal Payouts' section HTML.
	 *
	 * @since 2.30.0
	 */
	private function output_section_step_paypal_payouts() {
		$step = $this->get_data_step_paypal_payouts();

		if ( empty( $step ) ) {
			return;
		}

		// Increment step counter.
		++$this->current_step_number;

		// Build card content
		ob_start();
		?>
			<div class="flex gap-3 items-start sm:gap-4">
				<!-- Icon -->
				<div class="flex-shrink-0">
					<?php if ( 'step-complete.svg' === $step['icon'] ) : ?>
						<div class="flex justify-center items-center w-10 h-10 sm:h-12 sm:w-12">
							<svg class="w-10 h-10 text-green-600 sm:h-12 sm:w-12" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
							</svg>
						</div>
					<?php else : ?>
						<div class="flex justify-center items-center w-10 h-10 bg-gray-100 rounded-full sm:h-12 sm:w-12">
							<span class="text-xl font-bold text-gray-600 sm:text-2xl"><?php echo absint( $this->current_step_number ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<!-- Content and Action -->
				<div class="flex-1">
					<!-- Heading -->
					<div class="mb-1">
						<h3 class="text-base font-semibold text-gray-900 sm:text-lg">
							<?php echo esc_html( $step['heading'] ); ?>
						</h3>
					</div>

					<!-- Description -->
					<p class="mb-3 text-xs text-gray-600 sm:text-sm">
						<?php echo esc_html( $step['description'] ); ?>
					</p>

					<!-- Action button -->
					<div class="flex gap-2 items-center">
						<?php if ( 'step-complete.svg' === $step['icon'] ) : ?>
							<?php
							// Render secondary button for edit action.
							echo affwp_render_button(
								[
									'text'    => esc_html( $step['link_text'] ),
									'href'    => esc_url( $step['button_url'] ),
									'variant' => 'secondary',
									'size'    => 'sm',
								]
							);
							?>
						<?php else : ?>
							<?php
							// Use button component instead of raw HTML
							echo affwp_render_button(
								[
									'text'    => esc_html( $step['button_text'] ),
									'href'    => esc_url( $step['button_url'] ),
									'variant' => 'primary',
									'size'    => 'sm',
								]
							);
							?>
						<?php endif; ?>
					</div>
				</div>
		</div>
		<?php
		$card_content = ob_get_clean();

		// Render the card
		echo affwp_render_card(
			[
				'variant'   => 'default',
				'size'      => 'md',
				'content'   => $card_content,
				'hoverable' => true,
			]
		);
	}

	/**
	 * Generate and output dismiss section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_dismiss() {
		?>
		<div class="mt-8 max-w-5xl">
			<?php
			echo affwp_admin_link(
				'setup-screen',
				__( 'Dismiss Setup Screen', 'affiliate-wp' ),
				[ 'affwp_dismiss_setup' => '1' ],
				[ 'class' => 'text-sm text-gray-500 hover:text-gray-700 underline' ]
			);
			?>
		</div>
		<?php
	}

	/**
	 * Step 'Registration Form' data.
	 *
	 * @since 2.13.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_registration_form() {

		$step = [];

		// Get Affiliate Registration page ID.
		$page_id = affiliate_wp()->settings->get( 'affiliates_registration_page' );

		// Should be disabled by default because it's complete on plugin install.
		$step['heading']      = esc_html__( 'Create Your First Affiliate Registration Form', 'affiliate-wp' );
		$step['description']  = esc_html__( 'Every successful affiliate program begins with a registration form.', 'affiliate-wp' );
		$step['icon']         = 'step-complete.svg';
		$step['button_text']  = esc_html__( 'Affiliate Registration Form Created', 'affiliate-wp' );
		$step['button_class'] = esc_attr__( 'grey disabled', 'affiliate-wp' );
		$step['button_url']   = admin_url( sprintf( 'post.php?post=%1$s&action=edit', $page_id ) );
		$step['link_text']    = esc_html__( 'Edit Form', 'affiliate-wp' );
		$step['link_class']   = 'affwp-setup-edit-link';

		// If no affiliate area page is set, disable button.
		if ( empty( $page_id ) ) {
			$step['link_text']  = esc_html__( 'Select Affiliate Area Page', 'affiliate-wp' );
			$step['button_url'] = affwp_admin_url( 'settings' );
		}

		return $step;
	}

	/**
	 * Step 'Add Yourself' as an affiliate data.
	 *
	 * @since 2.13.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_add_yourself() {

		$step = [];

		$step['heading']      = esc_html__( 'Add Your First Affiliate', 'affiliate-wp' );
		$step['description']  = esc_html__( 'Add yourself as your very first affiliate account so you can test AffiliateWP.', 'affiliate-wp' );
		$step['icon']         = 'step-2.svg';
		$step['button_text']  = esc_html__( 'Add Affiliate', 'affiliate-wp' );
		$step['button_class'] = 'button-primary';
		$step['edit_link']    = '';

		// Disable if completed or intent was completed by an admin.
		$setup_intent = get_option( 'affwp_setup_intent' );

		// Check if current user is an affiliate.
		$user_id = get_current_user_id();

		if ( affwp_is_affiliate( $user_id ) ) {
				$step['icon']          = 'step-complete.svg';
				$step['button_class']  = 'grey disabled';
				$step['button_action'] = '';
				$step['button_text']   = esc_html__( 'Affiliate Created', 'affiliate-wp' );
				$step['edit_link']     = affwp_admin_link(
					'affiliates',
					__( 'Edit Affiliate', 'affiliate-wp' ),
					[
						'affwp_notice' => false,
						'action'       => 'edit_affiliate',
						'affiliate_id' => affwp_get_affiliate_id( $user_id ),
					],
					[
						'class' => 'affwp-setup-edit-link',
					]
				);
		}

		return $step;
	}

	/**
	 * Step 'Portal Addon' data.
	 *
	 * @since 2.13.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_portal_addon() {

		$step = [];

		$step['heading']      = sprintf(
			'%1$s with the <span>%2$s</span>',
			esc_html__( 'Level Up Your Affiliate Area', 'affiliate-wp' ),
			esc_html__( 'Affiliate Portal Addon', 'affiliate-wp' )
		);
		$step['description']  = sprintf(
			'<p>%1$s</p>',
			esc_html__( 'Using the Affiliate Portal addon, you can give your affiliates a premium experience, ensuring they have everything they need to perform.', 'affiliate-wp' ),
		);
		$step['button_text']  = esc_html__( 'Install Now', 'affiliate-wp' );
		$step['icon']         = 'step-3.svg';
		$step['button_class'] = 'button-primary';

		$all_plugins      = get_plugins();
		$portal_installed = array_key_exists( $this->config['portal_slug'], $all_plugins );

		// Step is complete if active and installed.
		if ( $portal_installed && is_plugin_active( $this->config['portal_slug'] ) ) {
				$step['icon']          = 'step-complete.svg';
				$step['button_class']  = 'grey disabled';
				$step['button_action'] = '';
				$step['button_plugin'] = '';
				$step['button_text']   = esc_html__( 'Affiliate Portal Activated', 'affiliate-wp' );

				return $step;
		}

		// If Portal is installed but not active, activate it.
		if ( $portal_installed ) {
			$step['button_action'] = 'activate';
			$step['button_plugin'] = $this->config['portal_slug'];
			$step['button_text']   = esc_html__( 'Activate Now', 'affiliate-wp' );

			return $step;
		}

		// If not installed, check license.
		$license_data   = affiliate_wp()->settings->get( 'license_status', '' );
		$license_status = is_object( $license_data ) ? $license_data->license : $license_data;
		$price_id       = isset( $license_data->price_id ) ? intval( $license_data->price_id ) : false;

		// If license is not valid or Professional, link to their AffiliateWP.com account page to upgrade.
		if ( 'valid' !== $license_status || $price_id < 2 ) {
			$step['description']   = sprintf(
				'<p>%1$s</p><p class="affwp-desc-offer"><span>%2$s</span> %3$s</p>',
				esc_html__( 'Using the Affiliate Portal addon, you can give your affiliates a premium experience, ensuring they have everything they need to perform.', 'affiliate-wp' ),
				esc_html__( 'Special Upgrade Offer:', 'affiliate-wp' ),
				esc_html__( 'Get 50% off the regular price, automatically applied at checkout.', 'affiliate-wp' )
			);
			$step['button_text']   = esc_html__( 'Upgrade to Pro and Save 50%', 'affiliate-wp' );
			$step['button_action'] = 'upgrade';
			$step['button_plugin'] = esc_url( $this->config['downloads_url'] );

			return $step;
		}

		// Otherwise, install and activate the Affiliate Portal addon.
		$step['button_action'] = 'install';
		$step['button_plugin'] = ( new Installer() )->get_addon_url( 570647 );

		return $step;
	}

	/**
	 * Step 'Stripe Payouts' data.
	 *
	 * @since 2.30.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_stripe_payouts() {

		$setup_intent = get_option( 'affwp_setup_intent' );

		// Only show this step if user explicitly showed intent (set to 1) during wizard.
		if ( ! isset( $setup_intent['intent_setup_stripe'] ) || 1 !== (int) $setup_intent['intent_setup_stripe'] ) {
			return [];
		}

		$step = [];

		// Check if Stripe Payouts is already configured.
		$is_configured = function_exists( 'affwp_stripe_payouts_is_configured' ) && affwp_stripe_payouts_is_configured();

		$step['heading']      = esc_html__( 'Set Up Stripe Payouts', 'affiliate-wp' );
		$step['description']  = esc_html__( 'Configure Stripe Payouts to pay your affiliates directly to their bank accounts.', 'affiliate-wp' );
		$step['icon']         = $is_configured ? 'step-complete.svg' : 'step-stripe.svg';
		$step['button_text']  = $is_configured ? esc_html__( 'Stripe Payouts Configured', 'affiliate-wp' ) : esc_html__( 'Configure Stripe Payouts', 'affiliate-wp' );
		$step['button_class'] = $is_configured ? 'grey disabled' : 'button-primary';
		$step['button_url']   = affwp_admin_url( 'settings', [ 'tab' => 'payouts' ] ) . '#stripe';
		$step['link_text']    = esc_html__( 'Configure', 'affiliate-wp' );
		$step['link_class']   = 'affwp-setup-edit-link';

		return $step;
	}

	/**
	 * Step 'PayPal Payouts' data.
	 *
	 * @since 2.30.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_paypal_payouts() {

		$setup_intent = get_option( 'affwp_setup_intent' );

		// Only show this step if user explicitly showed intent (set to 1) during wizard.
		if ( ! isset( $setup_intent['intent_setup_paypal'] ) || 1 !== (int) $setup_intent['intent_setup_paypal'] ) {
			return [];
		}

		$step = [];

		// Check if PayPal Payouts is configured with credentials.
		$is_enabled    = affiliate_wp()->settings->get( 'paypal_payouts', false );
		$is_configured = false;

		if ( $is_enabled && function_exists( 'affiliate_wp_paypal' ) ) {
			$is_configured = affiliate_wp_paypal()->has_api_credentials();
		}

		$step['heading']      = esc_html__( 'Set Up PayPal Payouts', 'affiliate-wp' );
		$step['description']  = esc_html__( 'Configure PayPal Payouts to pay your affiliates with one click.', 'affiliate-wp' );
		$step['icon']         = $is_configured ? 'step-complete.svg' : 'step-paypal.svg';
		$step['button_text']  = $is_configured ? esc_html__( 'PayPal Payouts Configured', 'affiliate-wp' ) : esc_html__( 'Configure PayPal Payouts', 'affiliate-wp' );
		$step['button_class'] = $is_configured ? 'grey disabled' : 'button-primary';
		$step['button_url']   = affwp_admin_url( 'settings', [ 'tab' => 'payouts' ] ) . '#paypal';
		$step['link_text']    = esc_html__( 'Configure', 'affiliate-wp' );
		$step['link_class']   = 'affwp-setup-edit-link';

		return $step;
	}

	/**
	 * Generate and output step 'Store Credit' section HTML.
	 *
	 * @since 2.30.0
	 */
	private function output_section_step_store_credit() {
		$step = $this->get_data_step_store_credit();

		if ( empty( $step ) ) {
			return;
		}

		// Increment step counter.
		++$this->current_step_number;

		// Build card content
		ob_start();
		?>
			<div class="flex gap-3 items-start sm:gap-4">
				<!-- Icon -->
				<div class="flex-shrink-0">
					<?php if ( 'step-complete.svg' === $step['icon'] ) : ?>
						<div class="flex justify-center items-center w-10 h-10 sm:h-12 sm:w-12">
							<svg class="w-10 h-10 text-green-600 sm:h-12 sm:w-12" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
							</svg>
						</div>
					<?php else : ?>
						<div class="flex justify-center items-center w-10 h-10 bg-gray-100 rounded-full sm:h-12 sm:w-12">
							<span class="text-xl font-bold text-gray-600 sm:text-2xl"><?php echo absint( $this->current_step_number ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<!-- Content and Action -->
				<div class="flex-1">
					<!-- Heading -->
					<div class="mb-1">
						<h3 class="text-base font-semibold text-gray-900 sm:text-lg">
							<?php echo esc_html( $step['heading'] ); ?>
						</h3>
					</div>

					<!-- Description -->
					<p class="mb-3 text-xs text-gray-600 sm:text-sm">
						<?php echo esc_html( $step['description'] ); ?>
					</p>

					<!-- Action button -->
					<div class="flex gap-2 items-center">
						<?php if ( 'step-complete.svg' === $step['icon'] ) : ?>
							<?php
							// Render secondary button for edit action.
							echo affwp_render_button(
								[
									'text'    => esc_html( $step['link_text'] ),
									'href'    => esc_url( $step['button_url'] ),
									'variant' => 'secondary',
									'size'    => 'sm',
								]
							);
							?>
						<?php else : ?>
							<?php
							// Use button component instead of raw HTML
							echo affwp_render_button(
								[
									'text'    => esc_html( $step['button_text'] ),
									'href'    => esc_url( $step['button_url'] ),
									'variant' => 'primary',
									'size'    => 'sm',
								]
							);
							?>
						<?php endif; ?>
					</div>
				</div>
		</div>
		<?php
		$card_content = ob_get_clean();

		// Render the card
		echo affwp_render_card(
			[
				'variant'   => 'default',
				'size'      => 'md',
				'content'   => $card_content,
				'hoverable' => true,
			]
		);
	}

	/**
	 * Step 'Store Credit' data.
	 *
	 * @since 2.30.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_store_credit() {

		$setup_intent = get_option( 'affwp_setup_intent' );

		// Only show this step if user explicitly showed intent (set to 1) during wizard.
		if ( ! isset( $setup_intent['intent_setup_store_credit'] ) || 1 !== (int) $setup_intent['intent_setup_store_credit'] ) {
			return [];
		}

		$step = [];

		// Check if Store Credit is already configured (enabled in settings).
		$is_configured = (bool) affiliate_wp()->settings->get( 'store-credit', false );

		$step['heading']      = esc_html__( 'Set Up Store Credit', 'affiliate-wp' );
		$step['description']  = esc_html__( 'Configure Store Credit to pay your affiliates with store credit for your shop.', 'affiliate-wp' );
		$step['icon']         = $is_configured ? 'step-complete.svg' : 'step-store-credit.svg';
		$step['button_text']  = $is_configured ? esc_html__( 'Store Credit Configured', 'affiliate-wp' ) : esc_html__( 'Configure Store Credit', 'affiliate-wp' );
		$step['button_class'] = $is_configured ? 'grey disabled' : 'button-primary';
		$step['button_url']   = affwp_admin_url( 'settings', [ 'tab' => 'payouts' ] ) . '#store-credit';
		$step['link_text']    = esc_html__( 'View Settings', 'affiliate-wp' );
		$step['link_class']   = 'affwp-setup-edit-link';

		return $step;
	}

	/**
	 * Ajax endpoint. Customize registration form setup intent complete.
	 *
	 * @since 2.13.0
	 */
	public function ajax_customize_form_intent_complete() {

		// Security check.
		if ( ! check_ajax_referer( 'affiliate-wp-admin', 'nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
				]
			);
		}

		$setup_intent = get_option( 'affwp_setup_intent' );

		// Update setup intent so we know this step is complete.
		update_option(
			'affwp_setup_intent',
			array_merge(
				is_array( $setup_intent )
					? $setup_intent
					: [],
				[ 'affwp_customize_registration_complete' => 1 ],
			)
		);

		wp_send_json_success();
	}

	/**
	 * Ajax endpoint. Add yourself as an affiliate intent complete.
	 *
	 * @since 2.13.0
	 */
	public function ajax_add_yourself_intent_complete() {

		// Security check.
		if ( ! check_ajax_referer( 'affiliate-wp-admin', 'nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
				]
			);
		}

		// Add yourself as an affiliate.
		$user_id = get_current_user_id();

		$params = [
			'user_id'             => $user_id,
			'status'              => 'active',
			'registration_method' => 'setup_screen',
		];
		if ( false === affwp_add_affiliate( $params ) ) {
			wp_send_json_error(
				[
					'error' => esc_html__( 'Something went wrong adding you as an affiliate. Please try again.', 'affiliate-wp' ),
				]
			);
		}

		wp_send_json_success();
	}
}
