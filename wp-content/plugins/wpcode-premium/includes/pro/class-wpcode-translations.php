<?php
/**
 * This class handles translations for the premium plugin.
 *
 * @package WPcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Translations
 */
class WPCode_Translations {

	/**
	 * List of active WPCode plugins.
	 *
	 * @var array
	 */
	private $plugins = array();

	/**
	 * List of installed translations.
	 *
	 * @var array
	 */
	private $installed_translations = array();

	/**
	 * List of available languages.
	 *
	 * @var array
	 */
	private $available_languages = array();

	/**
	 * Full URL for the plugin/addon handled by our redirection at wpcode.com.
	 *
	 * @var string
	 */
	const API_URL = 'https://translations.wpcode.com/%s/packages.json';

	/**
	 * The instance of the core class used for updating/installing language packs (translations).
	 *
	 * @var Language_Pack_Upgrader
	 */
	private $upgrader;

	/**
	 * Upgrader Skin for Automatic WordPress Upgrades.
	 *
	 * @since 1.6.5
	 *
	 * @var Automatic_Upgrader_Skin
	 */
	private $skin;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! $this->allow_load() ) {
			return;
		}

		$this->load();
	}

	/**
	 * Whether the integration should be loaded.
	 *
	 * @return bool
	 */
	public function allow_load() {

		if ( ! is_admin() ) {
			return false;
		}

		// For WordPress versions 4.9.0-4.9.4 this file must be included before the current_user_can() check.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		if ( ! current_user_can( 'install_languages' ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';

		return wp_can_install_language_pack();
	}

	/**
	 * Load the class.
	 */
	public function load() {

		global $pagenow;

		if ( 'update-core.php' === $pagenow ) {

			// Clear cache for translations.
			add_action( 'set_site_transient_update_plugins', array( $this, 'clear_translations_cache' ) );

			// Add translations to the list of available for download.
			add_filter( 'site_transient_update_plugins', array( $this, 'register_t15s_translations' ) );
		}

		// Download translations on plugin activation.
		add_action( 'activate_plugin', array( $this, 'activate_plugin' ) );

		// Download translations when plugin upgrade from Lite to Pro.
		add_action( 'wpcode_plugin_activation', array( $this, 'upgrade_core' ) );

		// Remove translation cache for a plugin on deactivation.
		// Translation removal is handled on plugin removal by WordPress.
		add_action( 'deactivate_plugin', array( $this, 'clear_plugin_translation_cache' ) );

		// Download translations for all addons when language for the site has been changed.
		add_action( 'update_option_WPLANG', array( $this, 'download_plugins_translations' ) );

		// Download translations on plugin activation on Plugins page.
		if (
			'plugins.php' === $pagenow &&
			get_transient( 'wpcode_just_activated' ) &&
			( ! empty( $_GET['activate'] ) || ! empty( $_GET['activate-multi'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			$this->download_plugins_translations();
		}
	}

	/**
	 * Clear existing translation cache.
	 */
	public function clear_translations_cache() {

		foreach ( $this->get_wpcode_plugins() as $slug ) {
			delete_site_transient( $this->get_cache_key( $slug ) );
		}
	}


	/**
	 * Get a list of active WPCode plugins.
	 *
	 * @return array
	 */
	private function get_wpcode_plugins() {

		if ( ! empty( $this->plugins ) ) {
			return $this->plugins;
		}

		$plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $key => $plugin_file ) {
			$slug = dirname( $plugin_file );

			if ( ! $this->is_wpcode_plugin( $slug ) ) {
				unset( $plugins[ $key ] );
				continue;
			}

			$plugins[ $key ] = $slug;
		}

		$plugins[] = 'insert-headers-and-footers';

		$this->plugins = $plugins;

		return $this->plugins;
	}

	/**
	 * Whether the provided slug belongs to the WPCode plugin or one of its addons.
	 *
	 * @param string $slug Plugin slug.
	 *
	 * @return bool
	 */
	private function is_wpcode_plugin( $slug ) {
		return strpos( $slug, 'wpcode' ) === 0 || strpos( $slug, 'insert-headers-and-footers' ) === 0;
	}

	/**
	 * Get a dynamic cache key which has the plugin slug in its name.
	 *
	 * @param string $slug Slug.
	 *
	 * @return string
	 */
	private function get_cache_key( $slug ) {

		return "wpcode_t15s_{$slug}";
	}

	/**
	 * Register all translations from our Translations endpoint.
	 *
	 * @param object $value Value of the `update_plugins` transient option.
	 *
	 * @return stdClass
	 */
	public function register_t15s_translations( $value ) {

		if ( ! $value ) {
			$value = new stdClass();
		}

		if ( ! isset( $value->translations ) ) {
			$value->translations = array();
		}

		foreach ( $this->get_wpcode_plugins() as $slug ) {
			$translations = $this->get_translations( $slug );

			if ( empty( $translations['translations'] ) ) {
				continue;
			}

			foreach ( $translations['translations'] as $translation ) {
				$value->translations[] = $translation;
			}
		}

		return $value;
	}

	/**
	 * Get a list of needed translations for the plugin.
	 *
	 * @param string $slug Plugin slug.
	 *
	 * @return array
	 */
	private function get_translations( $slug ) {

		$translations           = $this->get_available_translations( $slug );
		$available_languages    = $this->get_available_languages();
		$installed_translations = $this->get_installed_translations();

		foreach ( $translations['translations'] as $key => $translation ) {
			if ( empty( $translation['language'] ) || ! in_array( $translation['language'], $available_languages, true ) ) {
				unset( $translations['translations'][ $key ] );
			}

			// Skip languages which were updated locally.
			if ( isset( $installed_translations[ $slug ][ $translation['language'] ]['PO-Revision-Date'], $translation['updated'] ) ) {
				$local  = strtotime( $installed_translations[ $slug ][ $translation['language'] ]['PO-Revision-Date'] );
				$remote = strtotime( $translation['updated'] );

				if ( $local >= $remote ) {
					unset( $translations['translations'][ $key ] );
				}
			}
		}

		return $translations;
	}

	/**
	 * Get all available translations for the plugin.
	 *
	 * @param string $slug Plugin slug.
	 *
	 * @return array Translation data.
	 * @since 1.6.5
	 *
	 */
	private function get_available_translations( $slug ) {

		$translations = get_site_transient( $this->get_cache_key( $slug ) );

		if ( false !== $translations ) {
			return $translations;
		}

		$translations = json_decode(
			wp_remote_retrieve_body(
				wp_remote_get(
					sprintf( self::API_URL, $slug ),
					array(
						'timeout' => 2,
					)
				)
			),
			true
		);

		if ( ! is_array( $translations ) || empty( $translations['translations'] ) ) {
			$translations = array( 'translations' => array() );
		}

		// Convert translations from API to a WordPress standard.
		foreach ( $translations['translations'] as $key => $translation ) {
			$translations['translations'][ $key ]['type'] = 'plugin';
			$translations['translations'][ $key ]['slug'] = $slug;
		}

		set_site_transient( $this->get_cache_key( $slug ), $translations );

		return $translations;
	}

	/**
	 * Get available languages.
	 *
	 * @return array
	 */
	private function get_available_languages() {

		if ( $this->available_languages ) {
			return $this->available_languages;
		}

		$this->available_languages = get_available_languages();

		return $this->available_languages;
	}

	/**
	 * Get installed translations.
	 *
	 * @return array
	 */
	private function get_installed_translations() {

		if ( $this->installed_translations ) {
			return $this->installed_translations;
		}

		$this->installed_translations = wp_get_installed_translations( 'plugins' );

		return $this->installed_translations;
	}

	/**
	 * Download core languages when upgrading from lite to pro version.
	 * The upgrade process runs in a silent mode and skips activation hooks.
	 *
	 * @since 1.6.8
	 */
	public function upgrade_core() {
		$this->activate_plugin( 'wpcode-premium/wpcode.php' );
		$this->activate_plugin( 'insert-headers-and-footers/ihaf.php' ); // Let's also update the lite plugin translation here.
	}

	/**
	 * Download translations for the plugin after its activation.
	 *
	 * @param string $plugin Plugin main file.
	 *
	 * @since 1.6.5
	 *
	 */
	public function activate_plugin( $plugin ) {

		$slug = dirname( $plugin );

		if ( ! $this->is_wpcode_plugin( $slug ) ) {
			return;
		}

		$translations = $this->get_translations( $slug );

		if ( empty( $translations['translations'] ) ) {
			return;
		}

		$this->download_plugin_translations( $slug, $translations['translations'] );
	}

	/**
	 * Load required libraries.
	 *
	 * @since 1.6.5
	 */
	private function load_download_requirements() {

		$this->skin     = new Automatic_Upgrader_Skin();
		$this->upgrader = new Language_Pack_Upgrader( $this->skin );
	}

	/**
	 * Download translations for the plugin.
	 *
	 * @param string $slug Slug of plugin.
	 * @param array  $translations List of available translations.
	 *
	 * @since 1.6.5
	 *
	 */
	private function download_plugin_translations( $slug, $translations ) {

		$this->load_download_requirements();

		$available_translations = $this->get_available_plugin_translations( $translations, $slug );

		foreach ( $available_translations as $language ) {
			if ( ! is_object( $language ) ) {
				$language = (object) $language;
			}

			$this->skin->language_update = $language;

			$this->upgrader->run(
				array(
					'package'                     => $language->package,
					'destination'                 => WP_LANG_DIR . '/plugins',
					'abort_if_destination_exists' => false,
					'is_multi'                    => true,
					'hook_extra'                  => array(
						'language_update_type' => $language->type,
						'language_update'      => $language,
					),
				)
			);
		}
	}

	/**
	 * Get available translations for the plugin.
	 *
	 * @param array  $translations List of translations.
	 * @param string $slug Plugin slug.
	 *
	 * @return array
	 * @since 1.6.5
	 *
	 */
	private function get_available_plugin_translations( $translations, $slug ) {

		$available_languages = $this->get_available_languages();

		if ( empty( $available_languages ) ) {
			return array();
		}

		foreach ( $translations as $key => $language ) {
			if ( ! is_object( $language ) ) {
				$language = (object) $language;
			}
			if (
				( ! property_exists( $language, 'slug' ) || ! property_exists( $language, 'language' ) ) ||
				$slug !== $language->slug ||
				! in_array( $language->language, $available_languages, true )
			) {
				unset( $translations[ $key ] );
				continue;
			}
		}

		return $translations;
	}

	/**
	 * Clear existing translation cache for a specific plugin.
	 *
	 * @param string $plugin Plugin slug.
	 */
	public function clear_plugin_translation_cache( $plugin ) {

		$slug = dirname( $plugin );

		if ( ! $this->is_wpcode_plugin( $slug ) ) {
			return;
		}

		delete_site_transient( $this->get_cache_key( $slug ) );
	}

	/**
	 * Download translations for all WPCode plugins.
	 *
	 * @since 1.6.5
	 */
	public function download_plugins_translations() {

		foreach ( $this->get_wpcode_plugins() as $slug ) {
			$translations = $this->get_translations( $slug );

			if ( ! empty( $translations['translations'] ) ) {
				$this->download_plugin_translations( $slug, $translations['translations'] );
			}
		}
	}
}

new WPCode_Translations();
