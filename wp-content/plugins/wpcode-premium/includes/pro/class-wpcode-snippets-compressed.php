<?php
/**
 * WPCode_Snippets_Compressed class.
 *
 * @package wpcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This class handles compressing the output of snippets.
 *
 * @package WPCode
 */
class WPCode_Snippets_Compressed {

	/**
	 * The code types we want to compress.
	 *
	 * @var string[]
	 */
	public static $code_types = array(
		'js',
		'css',
		'html',
	);

	/**
	 * WPCode_Snippets_Compressed constructor.
	 */
	public function __construct() {
		add_filter( 'wpcode_snippet_output', array( $this, 'maybe_compress_output' ), 10, 2 );
	}

	/**
	 * Maybe compress the output of the snippet.
	 *
	 * @param string         $output The output of the snippet.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public function maybe_compress_output( $output, $snippet ) {
		$code_type = $snippet->get_code_type();
		if ( ! in_array( $code_type, self::$code_types, true ) ) {
			return $output;
		}

		if ( ! $snippet->maybe_compress_output() ) {
			return $output;
		}

		$compress_method = 'compress_' . $code_type;

		if ( method_exists( $this, $compress_method ) ) {
			$output = $this->{$compress_method}( $output );
		}

		return $output;
	}

	/**
	 * Compress the JavaScript output.
	 *
	 * @param string $code The output of the snippet.
	 *
	 * @return string
	 */
	public static function compress_js( $code ) {
		$code = preg_replace( '/\/\/[^\n\r]*/', '', $code ); // Remove single-line comments.
		$code = preg_replace( '/\/\*[\s\S]*?\*\//', '', $code ); // Remove multi-line comments.
		$code = preg_replace( '/\s+/', ' ', $code ); // Remove extra whitespace.
		$code = preg_replace( '/\s*([{};,:])\s*/', '$1', $code ); // Remove spaces around characters.

		return $code;
	}

	/**
	 * Compress the CSS output.
	 *
	 * @param string $code The output of the snippet.
	 *
	 * @return string
	 */
	public static function compress_css( $code ) {
		$code = preg_replace( '!/\*.*?\*/!s', '', $code ); // Remove comments.
		$code = preg_replace( '/\s+/', ' ', $code ); // Remove whitespace.
		$code = str_replace( array( '; ', ': ', ' {', '{ ', ', ', '} ', ' }' ), array( ';', ':', '{', '{', ',', '}', '}' ), $code );

		return $code;
	}

	/**
	 * Compress the HTML output.
	 *
	 * @param string $code The output of the snippet.
	 *
	 * @return string
	 */
	public static function compress_html( $code ) {
		// Remove HTML comments.
		$code = preg_replace( '/<!--(.*?)-->/', '', $code );

		// Process <style> blocks.
		$code = preg_replace_callback(
			'/<style\b[^>]*>(.*?)<\/style>/is',
			function ( $matches ) {
				$css = $matches[1];
				// Minify CSS: Remove comments and unnecessary whitespace.
				$css = preg_replace( '!/\*.*?\*/!s', '', $css );
				$css = preg_replace( '/\s+/', ' ', $css );
				$css = str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $css );
				return '<style>' . trim( $css ) . '</style>';
			},
			$code
		);

		// Process <script> blocks.
		$code = preg_replace_callback(
			'/<script\b[^>]*>(.*?)<\/script>/is',
			function ( $matches ) {
				$js = $matches[1];
				$js = preg_replace( '/\/\/.*?(\r?\n)/', '', $js );
				$js = preg_replace( '/\/\*.*?\*\//s', '', $js );
				$js = preg_replace( '/\s+/', ' ', $js );
				$js = str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $js );
				return '<script>' . trim( $js ) . '</script>';
			},
			$code
		);

		// Remove unnecessary whitespace outside of <style> and <script>.
		$code = preg_replace( '/>\s+</', '><', $code );
		$code = preg_replace( '/\s+/', ' ', $code );

		return trim( $code );
	}
}

new WPCode_Snippets_Compressed();
