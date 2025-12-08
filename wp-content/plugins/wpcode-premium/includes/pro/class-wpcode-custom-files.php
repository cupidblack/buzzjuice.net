<?php
/**
 * WPCode Custom Files Output.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output custom files that we have a value for.
 *
 * @package WPCode
 */
class WPCode_Custom_Files_Output {

	/**
	 * Array of files available to output.
	 * Keys are the names of the option used in WPCode and the values are the URLs to check for.
	 *
	 * @var string[]
	 */
	public $files = array(
		'adstxt'          => array(
			'filename' => 'ads.txt',
		),
		'appadstxt'       => array(
			'filename' => 'app-ads.txt',
		),
		'serviceworkerjs' => array(
			'filename'     => 'service-worker.js',
			'content-type' => 'application/javascript',
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_output_file' ) );
		add_filter( 'robots_txt', array( $this, 'maybe_append_robotstxt' ), 50500 );
	}

	/**
	 * Maybe output a file using a value from the db.
	 *
	 * @return void
	 */
	public function maybe_output_file() {

		$request = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : false;
		foreach ( $this->files as $key => $file_info ) {
			$path = $file_info['filename'];
			if ( '/' . $path === $request || "/{$path}?" === substr( $request, 0, strlen( $path ) + 2 ) ) {
				// Load the data from the options.
				$value = get_option( 'wpcode_file_' . $key, false );

				if ( empty( $value ) || ! is_array( $value ) || empty( $value['content'] ) || empty( $value['enabled'] ) ) {
					// If we don't have anything to output just let it go to a 404.
					return;
				}

				header_remove( 'x-powered-by' );
				header( 'Content-Type: ' . $this->get_content_type( $key ) );

				echo $this->escape_output( apply_filters( 'wpcode_file_' . $key, $value['content'] ), $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				die();
			}
		}
	}

	/**
	 * Escape output specific to the type of file.
	 *
	 * @param string $value The file contents to escape.
	 * @param string $type The type of file.
	 *
	 * @return string
	 */
	public function escape_output( $value, $type ) {
		if ( 'serviceworkerjs' === $type ) {
			return $value;
		}

		return esc_html( $value );
	}

	/**
	 * Get the content type for a file.
	 *
	 * @param string $file The file to get the content type for.
	 *
	 * @return string
	 */
	public function get_content_type( $file ) {
		if ( isset( $this->files[ $file ]['content-type'] ) ) {
			return $this->files[ $file ]['content-type'];
		}

		return 'text/plain';
	}

	/**
	 * Maybe append to the robots.txt file if we have data in the WPCode file editor.
	 *
	 * @param string $contents The robots.txt contents.
	 *
	 * @return string
	 */
	public function maybe_append_robotstxt( $contents ) {

		$value = get_option(
			'wpcode_file_robotstxt',
			array(
				'enabled' => true,
				'content' => '',
			)
		);

		if ( ! empty( $value['content'] ) && ! empty( $value['enabled'] ) ) {
			$contents .= "\n" . esc_html( $value['content'] );
		}

		return $contents;
	}

}

new WPCode_Custom_Files_Output();
