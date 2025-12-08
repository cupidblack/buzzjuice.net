<?php
/**
 * Pro-specific settings admin page.
 *
 * @package WPCode
 */

/**
 * Pro-specific settings admin page.
 */
class WPCode_Admin_Page_Settings_Pro extends WPCode_Admin_Page_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( $this->is_multisite_core() ) {
			$this->page_slug = 'wpcode-mu-settings';
		}
		parent::__construct();

		$this->register_network_page();
	}

	/**
	 * Register the network settings page.
	 *
	 * @return void
	 */
	public function register_network_page() {
		add_action( 'network_admin_menu', array( $this, 'add_network_page' ) );
	}

	/**
	 * Add the network settings page.
	 *
	 * @return void
	 */
	public function add_network_page() {
		add_filter( 'admin_body_class', array( $this, 'add_wpcode_classname' ) );
		add_submenu_page(
			'settings.php',
			$this->page_title,
			'WPCode',
			'wpcode_edit_snippets',
			$this->page_slug,
			array(
				wpcode()->admin_page_loader,
				'admin_menu_page',
			)
		);
	}

	/**
	 * Add page-specific hooks.
	 *
	 * @return void
	 */
	public function page_hooks() {
		parent::page_hooks();

		add_action( 'admin_init', array( $this, 'save_access_settings' ) );

		add_filter( 'wpcode_admin_js_data', array( $this, 'add_js_data' ) );

		if ( $this->is_multisite_core() ) {
			add_action( 'wpcode_mu_admin_page', array( $this, 'output' ) );
			add_action( 'wpcode_mu_admin_page', array( $this, 'output_footer' ) );

			$this->views = array(
				'general' => __( 'Network Settings', 'wpcode-premium' ),
			);
		}
	}

	/**
	 * Check if we are on the network settings page using this actual class.
	 *
	 * @return bool
	 */
	public function is_multisite_core() {
		return is_multisite() && is_network_admin() && 'WPCode_Admin_Page_Settings_Pro' === get_class( $this );
	}

	/**
	 * Hide the top-right menu for the network settings page.
	 *
	 * @return void
	 */
	public function output_header_right() {
		if ( $this->is_multisite_core() ) {
			return;
		}
		parent::output_header_right();
	}

	/**
	 * Extend the settings page with pro-specific fields.
	 *
	 * @return void
	 */
	public function output_view_general() {
		if ( $this->is_multisite_core() ) {
			$this->multisite_addon();
			$this->metabox_row(
				__( 'License Key', 'wpcode-premium' ),
				$this->get_license_key_field(),
				'wpcode-setting-license-key'
			);

			return;
		}

		$this->metabox_row(
			__( 'License Key', 'wpcode-premium' ),
			$this->get_license_key_field(),
			'wpcode-setting-license-key'
		);

		$this->common_settings();

		$this->uninstall_setting();

		wp_nonce_field( $this->action, $this->nonce_name );

		?>
		<button class="wpcode-button" type="submit">
			<?php esc_html_e( 'Save Changes', 'wpcode-premium' ); ?>
		</button>
		<?php
	}

	/**
	 * Give users a way to activate the Multisite Addon on this page.
	 *
	 * @return void
	 */
	public function multisite_addon() {
		$addon_name = 'wpcode-multisite';

		ob_start();
		if ( wpcode()->license->is_addon_allowed( $addon_name, true ) ) {
			// Is the addon installed?
			$addon_data  = wpcode()->addons->get_addon( $addon_name );
			$button_text = __( 'Install Addon Now', 'wpcode-premium' );
			if ( ! empty( $addon_data->installed ) ) {
				$button_text = __( 'Activate Addon', 'wpcode-premium' );
			}
			?>
			<button class="wpcode-button wpcode-button-install-addon" data-addon="<?php echo esc_attr( $addon_name ); ?>">
				<?php echo esc_html( $button_text ); ?>
			</button>
			<p><?php esc_html_e( 'Install the WPCode Multisite Addon to manage snippets across the whole network.', 'wpcode-premium' ); ?></p>
			<?php
		} else {
			?>
			<p>
				<strong><?php esc_html_e( 'The WPCode Multisite addon is available on the Elite plan.', 'wpcode-premium' ); ?></strong>
			</p>
			<p><?php esc_html_e( 'Please activate your license key below to get started.', 'wpcode-premium' ); ?></p>
			<?php
		}

		$addon_button = ob_get_clean();

		$this->metabox_row(
			__( 'Multisite Addon', 'wpcode-premium' ),
			$addon_button,
			'wpcode-multisite-addon-button'
		);
	}

	/**
	 * License key field for the Pro settings page.
	 *
	 * @return false|string
	 */
	public function get_license_key_field() {
		$license      = wpcode()->license->get_option( is_network_admin() );
		$key          = ! empty( $license['key'] ) ? $license['key'] : '';
		$type         = ! empty( $license['type'] ) ? $license['type'] : '';
		$is_valid_key = ! empty( $key ) &&
		                ( isset( $license['is_expired'] ) && $license['is_expired'] === false ) &&
		                ( isset( $license['is_disabled'] ) && $license['is_disabled'] === false ) &&
		                ( isset( $license['is_invalid'] ) && $license['is_invalid'] === false );

		$hide        = $is_valid_key ? '' : 'wpcode-hide';
		$account_url = wpcode_utm_url(
			'https://library.wpcode.com/account/downloads/',
			'settings-page',
			'license-key',
			'account'
		);

		ob_start();
		?>
		<span class="wpcode-setting-license-wrapper">
			<input type="password" id="wpcode-setting-license-key" value="<?php echo esc_attr( $key ); ?>" class="wpcode-input-text" <?php disabled( $is_valid_key ); ?>>
		</span>
		<button type="button" id="wpcode-setting-license-key-verify" class="wpcode-button <?php echo $is_valid_key ? 'wpcode-hide' : ''; ?>"><?php esc_html_e( 'Verify Key', 'wpcode-premium' ); ?></button>
		<button type="button" id="wpcode-setting-license-key-deactivate" class="wpcode-button <?php echo esc_attr( $hide ); ?>"><?php esc_html_e( 'Deactivate Key', 'wpcode-premium' ); ?></button>
		<button type="button" id="wpcode-setting-license-key-deactivate-force" class="wpcode-button wpcode-hide"><?php esc_html_e( 'Force Deactivate Key', 'wpcode-premium' ); ?></button>
		<p class="type <?php echo esc_attr( $hide ); ?>">
			<?php
			printf(
			/* translators: %s: the license type */
				esc_html__( 'Your license key level is %s.', 'wpcode-premium' ),
				'<strong>' . esc_html( $type ) . '</strong>'
			);
			?>
		</p>
		<p>
			<?php
			printf(
			/* translators: %1$s: opening link tag, %2$s: closing link tag */
				esc_html__( 'You can find your license key in your %1$sWPCode account%2$s.', 'wpcode-premium' ),
				'<a href="' . esc_url( $account_url ) . '" target="_blank">',
				'</a>'
			);
			?>
		</p>
		<?php

		return ob_get_clean();
	}

	/**
	 * Output the form for the access management tab.
	 *
	 * @return void
	 */
	public function output_view_access() {

		$can_access = wpcode()->license->license_can( 'pro', is_multisite() && is_network_admin() );

		if ( ! $can_access ) {
			echo '<div class="wpcode-blur-area">';
		}

		$this->access_view_content();

		if ( ! $can_access ) {
			echo '</div>';
			echo $this->get_access_overlay();
		} else {
			// Nonce field.
			wp_nonce_field( 'wpcode_settings_access_save', 'wpcode_settings_access_nonce' );
		}
	}

	/**
	 * Process and Save access settings if any are set.
	 *
	 * @return void
	 */
	public function save_access_settings() {
		if ( ! isset( $_POST['wpcode_settings_access_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wpcode_settings_access_nonce'] ), 'wpcode_settings_access_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'wpcode_activate_snippets' ) ) {
			return;
		}

		$capabilities = array_keys( $this->get_capabilites() );

		foreach ( $capabilities as $capability ) {
			if ( isset( $_POST[ 'wpcode_capability_' . $capability ] ) ) {
				$roles = array_map( 'sanitize_key', $_POST[ 'wpcode_capability_' . $capability ] );
				wpcode()->settings->update_option( $capability, $roles );
			} else {
				wpcode()->settings->update_option( $capability, array() );
			}
		}

		if ( isset( $_POST['completely_disable_php'] ) ) {
			wpcode()->settings->update_option( 'completely_disable_php', true );
		} else {
			wpcode()->settings->update_option( 'completely_disable_php', false );
		}

		wp_safe_redirect( $this->get_page_action_url() );
		exit;
	}

	/**
	 * Show the PHP setting if PHP is not disabled.
	 *
	 * @return void
	 */
	public function php_setting() {
		if ( ! defined( 'WPCODE_DISABLE_PHP' ) || ! WPCODE_DISABLE_PHP ) {
			parent::php_setting();
		}
	}

	/**
	 * Get the capabilities for the access settings page.
	 *
	 * @return array[]
	 */
	public function get_capabilites() {
		$capabilities = WPCode_Access::capabilities();
		if ( WPCode_Access::php_disabled() ) {
			// If PHP is disabled, don't show the capability to edit php setting.
			unset( $capabilities['wpcode_edit_php_snippets'] );
		}

		return $capabilities;
	}

	/**
	 * Access control overlay.
	 *
	 * @return string
	 */
	public function get_access_overlay() {
		$text = sprintf(
		// translators: %1$s and %2$s are <u> tags.
			'<p>' . __( 'Improve the way you and your team manage your snippets with the WPCode Access Control settings. Enable other users on your site to manage different types of snippets or configure Conversion Pixels settings and update configuration files. This feature is available on the %1$sWPCode Pro%2$s plan or higher.', 'wpcode-premium' ) . '</p>',
			'<u>',
			'</u>'
		);

		return self::get_upsell_box(
			__( 'Access Control is not available on your plan', 'wpcode-premium' ),
			$text,
			array(
				'text' => __( 'Upgrade Now', 'wpcode-premium' ),
				'url'  => wpcode_utm_url( 'https://library.wpcode.com/account/downloads/', 'settings', 'tab-' . $this->view, 'upgrade-to-pro' ),
			),
			array(),
			array(
				__( 'Save time and improve website management with your team', 'wpcode-premium' ),
				__( 'Delegate snippet management to other users with full control', 'wpcode-premium' ),
				__( 'Enable other users to set up ads & 3rd party services', 'wpcode-premium' ),
				__( 'Choose if PHP snippets should be enabled on the site', 'wpcode-premium' ),
			)
		);
	}

	/**
	 * Add license strings to the JS object for the Pro settings page.
	 *
	 * @param string[] $data The translation strings.
	 *
	 * @return string[]
	 */
	public function add_js_data( $data ) {
		$data['license_error_title'] = __( 'We encountered an error activating your license key', 'wpcode-premium' );
		$data['multisite']           = is_network_admin();

		return $data;
	}

	/**
	 * Output the form for the error notifications tab.
	 *
	 * @return void
	 */
	public function error_view_fields() {
		$this->error_logging_field();
		?>
		<h2><?php esc_html_e( 'Email Notifications', 'wpcode-premium' ); ?></h2>
		<p>
			<?php esc_html_e( 'Receive email notifications when snippets throw errors or are automatically deactivated.', 'wpcode-premium' ); ?>
		</p>
		<?php $this->wp_mail_smtp_notice(); ?>
		<hr/>
		<?php
		$this->error_emails_fields();
	}

	/**
	 * Process settings before they are saved in the context of this child class.
	 * In other words, process pro-specific settings while keeping the save logic in the main class that is extended.
	 *
	 * @param array $settings The settings to be saved.
	 *
	 * @return array
	 */
	public function before_save( $settings ) {
		if ( ! isset( $_REQUEST[ $this->nonce_name ] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST[ $this->nonce_name ] ), $this->action ) ) {
			// Nonce is missing, so we're not even going to try.
			return $settings;
		}

		if ( 'errors' === $this->view ) {
			$emails_errors_addresses = isset( $_POST['wpcode-emails-errors-addresses'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcode-emails-errors-addresses'] ) ) : '';
			if ( ! empty( $emails_errors_addresses ) ) {
				$emails_errors_addresses = $this->clean_emails( $emails_errors_addresses );
			}
			$emails_deactivated_addresses = isset( $_POST['wpcode-emails-deactivated-addresses'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcode-emails-deactivated-addresses'] ) ) : '';
			if ( ! empty( $emails_deactivated_addresses ) ) {
				$emails_deactivated_addresses = $this->clean_emails( $emails_deactivated_addresses );
			}

			$settings['emails_errors']                = isset( $_POST['wpcode-emails-errors'] );
			$settings['emails_errors_addresses']      = $emails_errors_addresses;
			$settings['emails_deactivated']           = isset( $_POST['wpcode-emails-deactivated'] );
			$settings['emails_deactivated_addresses'] = $emails_deactivated_addresses;
		}

		return $settings;
	}

	/**
	 * Takes a comma separated list of emails and sanitizes each one.
	 *
	 * @param string $emails The comma separated list of emails.
	 *
	 * @return string
	 */
	public function clean_emails( $emails ) {
		$emails = explode( ',', $emails );
		$emails = array_map( 'sanitize_email', $emails );

		return implode( ',', $emails );
	}

	/**
	 * Add the admin-body class as it gets removed when we remove the submenu item.
	 *
	 * @param string $classes The admin body classes.
	 *
	 * @return string
	 */
	public function add_wpcode_classname( $classes ) {
		$classes .= ' wpcode-admin-page';

		if ( ! empty( wpcode()->settings->get_option( 'dark_mode' ) ) ) {
			$classes .= ' wpcode-dark-mode';
		}

		return $classes;
	}
}
