<?php
/**
 * This class handles generating and loading actual files for JS and CSS snippets.
 * This is an option in the Snippet settings.
 *
 * @package wpcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WPCode_Snippets_File_Handler {

	/**
	 * The types of snippets we handle.
	 *
	 * @var array
	 */
	protected $types = array( 'js', 'css', 'scss' );

	/**
	 * WPCode_Snippets_File_Handler constructor.
	 */
	public function __construct() {
		add_action( 'wpcode_snippet_after_update', array( $this, 'maybe_update_snippet_file' ), 10, 2 );
		add_filter( 'wpcode_snippet_output_js', array( $this, 'maybe_enqueue_instead' ), 10, 2 );
		add_filter( 'wpcode_snippet_output_css', array( $this, 'maybe_enqueue_instead' ), 10, 2 );
	}

	/**
	 * Maybe update the snippet file.
	 *
	 * @param int            $snippet_id The snippet id.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function maybe_update_snippet_file( $snippet_id, $snippet ) {
		if ( ! in_array( $snippet->get_code_type(), $this->types, true ) ) {
			return;
		}

		$this->update_snippet_file( $snippet );
	}

	/**
	 * Update the snippet file.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	protected function update_snippet_file( $snippet ) {

		if ( 'scss' === $snippet->get_code_type() ) {
			$code = $snippet->get_compiled_code();
		} else {
			$code = $snippet->get_code();
		}
		$code = wp_unslash( $code );

		if ( $snippet->maybe_compress_output() ) {
			$code_type       = $snippet->get_code_type();
			$compress_method = 'compress_' . $code_type;
			if ( method_exists( 'WPCode_Snippets_Compressed', $compress_method ) ) {
				$code = WPCode_Snippets_Compressed::$compress_method( $code );
			}
		}

		$filename = self::get_snippet_file_name( $snippet );

		// If the file exists and (the code is empty or the snippet is not active or the snippet should not be loaded as a file), delete the file.
		if ( file_exists( $filename ) && ( empty( $code ) || ! $snippet->is_active() || ! $snippet->get_load_as_file() ) ) {
			unlink( $filename );

			return;
		}

		// If the code is not empty, create or update the file.
		if ( ! empty( $code ) ) {
			file_put_contents( $filename, $code );
		}
	}

	/**
	 * Get the snippet file name.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public static function get_snippet_file_name( $snippet ) {
		// Let's generate a unique filename by hashing the id.
		$file_name = md5( $snippet->get_id() );
		$file_name .= '.' . self::get_snippet_extension( $snippet );

		$upload_dir = wp_upload_dir();

		$dir = $upload_dir['basedir'] . '/wpcode/assets/';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir . $file_name;
	}

	/**
	 * Maybe enqueue the snippet instead of outputting it.
	 *
	 * @param string         $code The snippet code.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public function maybe_enqueue_instead( $code, $snippet ) {
		// If the snippet is not set to be loaded as a file, return the code.
		if ( ! $snippet->get_load_as_file() ) {
			return $code;
		}
		// Let's check if the file for this snippet exists.
		$filename = self::get_snippet_file_name( $snippet );

		if ( file_exists( $filename ) ) {
			$this->enqueue_snippet( $snippet );

			return '';
		}

		return $code;
	}

	/**
	 * Get the snippet extension.
	 *
	 * @param $snippet
	 *
	 * @return mixed|string
	 */
	public static function get_snippet_extension( $snippet ) {
		if ( 'scss' === $snippet->get_code_type() ) {
			return 'css';
		} else {
			return $snippet->get_code_type();
		}
	}

	/**
	 * Get the file URL for the snippet.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public static function get_file_url( $snippet ) {
		// Let's generate a unique filename by hashing the id.
		$file_name = md5( $snippet->get_id() );

		$file_name .= '.' . self::get_snippet_extension( $snippet );

		$upload_dir = wp_upload_dir();

		$dir = $upload_dir['baseurl'] . '/wpcode/assets/';

		return $dir . $file_name;
	}

	/**
	 * Enqueue the snippet.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function enqueue_snippet( $snippet ) {

		$url     = self::get_file_url( $snippet );
		$version = strtotime( $snippet->modified );

		if ( 'js' === $snippet->get_code_type() ) {
			wp_enqueue_script( 'wpcode-snippet-' . $snippet->get_id(), $url, array(), $version, true );
		} elseif ( 'css' === $snippet->get_code_type() ) {
			wp_enqueue_style( 'wpcode-snippet-' . $snippet->get_id(), $url, array(), $version );
		}
	}
}

new WPCode_Snippets_File_Handler();
