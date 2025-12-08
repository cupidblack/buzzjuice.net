<?php
/**
 * Class that handles generic access helpers.
 *
 * @package WPCode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Access.
 */
class WPCode_Access {

	/**
	 * Check if PHP is disabled.
	 *
	 * @return bool
	 */
	public static function php_disabled() {
		$disabled = defined( 'WPCODE_DISABLE_PHP' ) && WPCODE_DISABLE_PHP;

		if ( ! $disabled && isset( wpcode()->settings ) ) {
			$disabled = (bool) wpcode()->settings->get_option( 'completely_disable_php' );
		}

		return $disabled;
	}

	/**
	 * Get an array of the custom capabilities that WPCode uses.
	 *
	 * @return array
	 */
	public static function capabilities() {
		return wpcode_custom_capabilities();
	}

	/**
	 * Get the custom capability from the settings specific to each code type.
	 *
	 * @param string $code_type The code type to check for.
	 *
	 * @return string
	 */
	public static function capability_for_code_type( $code_type ) {
		switch ( $code_type ) {
			case 'text':
			case 'blocks':
				return 'wpcode_edit_text_snippets';
			case 'html':
			case 'js':
			case 'css':
			case 'scss':
				return 'wpcode_edit_html_snippets';
			case 'php':
			case 'universal':
				return 'wpcode_edit_php_snippets';
			default:
				return '';
		}
	}
}