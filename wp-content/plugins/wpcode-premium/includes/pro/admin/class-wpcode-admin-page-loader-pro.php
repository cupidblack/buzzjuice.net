<?php
/**
 * Pro-specific admin page loader.
 * Replaces the classes used for generic pages with pro-specific ones.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Admin_Page_Loader_Pro.
 */
class WPCode_Admin_Page_Loader_Pro extends WPCode_Admin_Page_Loader {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		parent::hooks();
		add_filter( 'plugin_action_links_' . WPCODE_PLUGIN_BASENAME, array( $this, 'pro_action_links' ) );
		add_filter( 'network_admin_plugin_action_links_' . WPCODE_PLUGIN_BASENAME, array(
			$this,
			'network_action_links'
		) );

		add_action( 'network_admin_menu', array( $this, 'register_network_admin_menu' ), 9 );
	}

	/**
	 * Require pro-specific files.
	 *
	 * @return void
	 */
	public function require_files() {
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/trait-wpcode-library-refresh-button.php';
		parent::require_files();
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/trait-wpcode-revisions-display.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/class-wpcode-admin-page-snippet-manager-pro.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/class-wpcode-admin-page-library-pro.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/class-wpcode-admin-page-revisions.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/class-wpcode-admin-page-settings-pro.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/class-wpcode-admin-page-pixel-pro.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/class-wpcode-admin-page-file-editor-pro.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/class-wpcode-admin-page-headers-footers-pro.php';
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/admin/pages/class-wpcode-admin-page-code-snippets-pro.php';
	}

	/**
	 * Override pro-specific pages.
	 *
	 * @return void
	 */
	public function prepare_pages() {
		parent::prepare_pages();

		$this->pages['snippet_manager'] = 'WPCode_Admin_Page_Snippet_Manager_Pro';
		$this->pages['headers_footers'] = 'WPCode_Admin_Page_Headers_Footers_Pro';
		$this->pages['library']         = 'WPCode_Admin_Page_Library_Pro';
		$this->pages['revisions']       = 'WPCode_Admin_Page_Revisions';
		$this->pages['settings']        = 'WPCode_Admin_Page_Settings_Pro';
		$this->pages['pixel']           = 'WPCode_Admin_Page_Pixel_Pro';
		$this->pages['file_editor']     = 'WPCode_Admin_Page_File_Editor_Pro';
		$this->pages['code_snippets']   = 'WPCode_Admin_Page_Code_Snippets_Pro';
	}

	/**
	 * Add pro-specific links.
	 *
	 * @param array $links The links array.
	 *
	 * @return array
	 */
	public function pro_action_links( $links ) {
		if ( isset( $links['wpcodepro'] ) ) {
			unset( $links['wpcodepro'] );
		}
		$custom = array();

		if ( isset( $links['settings'] ) ) {
			$custom['settings'] = $links['settings'];

			unset( $links['settings'] );
		}

		$custom['support'] = sprintf(
			'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
			wpcode_utm_url(
				'https://library.wpcode.com/account/support/',
				'all-plugins',
				'plugin-action-links',
				'support'
			),
			esc_attr__( 'Go to WPCode.com Support page', 'wpcode-premium' ),
			esc_html__( 'Support', 'wpcode-premium' )
		);

		return array_merge( $custom, (array) $links );
	}

	/**
	 * Network-specific plugin links.
	 *
	 * @param array $links The links array.
	 *
	 * @return array
	 */
	public function network_action_links( $links ) {
		$custom = array();

		$custom['settings'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			network_admin_url( 'settings.php?page=wpcode-mu-settings' ),
			esc_html__( 'Settings', 'wpcode-premium' )
		);

		$custom['support'] = sprintf(
			'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
			wpcode_utm_url(
				'https://library.wpcode.com/account/support/',
				'all-plugins',
				'network-plugin-action-links',
				'support'
			),
			esc_attr__( 'Go to WPCode.com Support page', 'wpcode-premium' ),
			esc_html__( 'Support', 'wpcode-premium' )
		);

		return array_merge( $custom, (array) $links );
	}

	/**
	 * Here we'll load network-specific pages.
	 *
	 * @return void
	 */
	public function register_network_admin_menu() {

		$this->pages['network_settings'] = 'WPCode_Admin_Page_Settings_Pro';

		new WPCode_Admin_Page_Settings_Pro();
	}


	/**
	 * Network admin menu pages output.
	 *
	 * @return void
	 */
	public function admin_menu_page() {
		if ( is_multisite() && is_network_admin() ) {
			do_action( 'wpcode_mu_admin_page' );
		} else {
			parent::admin_menu_page();
		}
	}
}
