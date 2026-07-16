<?php
/**
 * Admin: Theme CSS Remover
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to remove theme CSS from AffiliateWP admin pages.
 *
 * @since 2.29.0
 */
class AffiliateWP_Theme_CSS_Remover {

	/**
	 * Constructor.
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		// Hook into multiple places to catch theme styles loaded at different times.
		add_action( 'admin_enqueue_scripts', [ $this, 'remove_theme_styles' ], 999 );
		add_action( 'admin_print_styles', [ $this, 'remove_theme_styles' ], 999 );
		add_action( 'admin_head', [ $this, 'remove_theme_styles' ], 1 );

		// Also filter style loader tags to prevent output.
		add_filter( 'style_loader_tag', [ $this, 'filter_style_tags' ], 999, 2 );
	}

	/**
	 * Whitelisted theme slugs whose CSS should not be removed.
	 *
	 * @since 2.32.0
	 *
	 * @var string[]
	 */
	private $whitelisted_themes = [
		'astra',
	];

	/**
	 * Get the list of whitelisted theme slugs.
	 *
	 * @since 2.32.0
	 *
	 * @return string[] Array of theme slugs.
	 */
	private function get_whitelisted_themes() {

		/**
		 * Filters the list of theme slugs whose CSS is preserved on AffiliateWP admin pages.
		 *
		 * @since 2.32.0
		 *
		 * @param string[] $themes Array of theme directory slugs to whitelist.
		 */
		return apply_filters( 'affwp_theme_css_remover_whitelist', $this->whitelisted_themes );
	}

	/**
	 * Check if a style URL belongs to a whitelisted theme.
	 *
	 * @since 2.32.0
	 *
	 * @param string $src The style source URL.
	 * @return bool True if the style belongs to a whitelisted theme.
	 */
	private function is_whitelisted_theme_style( $src ) {
		$theme_root_uri = get_theme_root_uri();

		// Extract the path after the theme root URI.
		$pos = strpos( $src, $theme_root_uri );

		if ( $pos === false ) {
			return false;
		}

		// Get the first path segment after the theme root (the theme slug).
		$relative  = substr( $src, $pos + strlen( $theme_root_uri ) + 1 );
		$theme_dir = strtok( $relative, '/' );

		return in_array( $theme_dir, $this->get_whitelisted_themes(), true );
	}

	/**
	 * Remove theme styles from AffiliateWP admin pages.
	 *
	 * @since 2.29.0
	 *
	 * @return void
	 */
	public function remove_theme_styles() {
		// Only run on AffiliateWP admin pages.
		if ( ! affwp_is_admin_page() ) {
			return;
		}

		global $wp_styles;

		if ( ! is_object( $wp_styles ) ) {
			return;
		}

		// Get theme directories.
		$theme_url = get_theme_root_uri();

		// Loop through all registered styles.
		foreach ( $wp_styles->registered as $handle => $style ) {
			if ( ! isset( $style->src ) || empty( $style->src ) ) {
				continue;
			}

			// Check if the style is from the themes directory.
			if ( strpos( $style->src, $theme_url ) !== false ) {

				// Skip whitelisted themes.
				if ( $this->is_whitelisted_theme_style( $style->src ) ) {
					continue;
				}

				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}

	/**
	 * Filter style tags to prevent theme styles from being output.
	 *
	 * @since 2.29.0
	 *
	 * @param string $tag    The style tag HTML.
	 * @param string $handle The style handle.
	 * @return string The filtered tag (empty string for theme styles).
	 */
	public function filter_style_tags( $tag, $handle ) {
		// Only run on AffiliateWP admin pages.
		if ( ! affwp_is_admin_page() ) {
			return $tag;
		}

		// Get theme URL.
		$theme_url = get_theme_root_uri();

		// If the tag contains the theme URL, block it (unless whitelisted).
		if ( strpos( $tag, $theme_url ) !== false && ! $this->is_whitelisted_theme_style( $tag ) ) {
			return '';
		}

		return $tag;
	}
}

// Initialize the class.
new AffiliateWP_Theme_CSS_Remover();
