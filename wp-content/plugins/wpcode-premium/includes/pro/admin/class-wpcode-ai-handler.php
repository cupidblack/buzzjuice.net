<?php
/**
 * WPCode AI Handler.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPCode AI Handler class.
 */
class WPCode_AI_Handler {

	/**
	 * Base URL for the AI server.
	 *
	 * @var string
	 */
	private $base_url = 'https://wpcode.com/aiapi/v1/';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpcode_ai_generate_snippet', array( $this, 'generate_snippet' ) );
		add_action( 'wp_ajax_wpcode_ai_improve_snippet', array( $this, 'improve_snippet' ) );
	}

	/**
	 * Get the URL for the AI server.
	 *
	 * @param string $endpoint The endpoint to get the URL for.
	 *
	 * @return string
	 */
	private function get_url( $endpoint ) {
		if ( defined( 'WPCODE_AI_URL' ) ) {
			return WPCODE_AI_URL . $endpoint;
		}

		return $this->base_url . $endpoint;
	}

	/**
	 * Generate a snippet using the AI server.
	 *
	 * @return void
	 */
	public function generate_snippet() {
		check_ajax_referer( 'wpcode_admin' );

		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
			wp_send_json_error();
		}

		$text = isset( $_POST['text'] ) ? sanitize_text_field( wp_unslash( $_POST['text'] ) ) : '';

		if ( empty( $text ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please describe what snippet you want to generate.', 'wpcode-premium' ),
				)
			);
		}

		$response = $this->make_request(
			'snippet',
			array(
				'text' => $text,
			)
		);

		$snippet = $this->process_snippet( $response );

		$snippet_data = array(
			'code'      => $snippet['code'],
			'location'  => $snippet['location'],
			'code_type' => $snippet['code_type'],
			'title'     => $snippet['title'],
		);

		// Let's check if CL rule data is available.
		if ( isset( $snippet['cl_rule_type'] ) && isset( $snippet['cl_operator'] ) && isset( $snippet['cl_value'] ) ) {
			$snippet_data['cl_rule_type'] = $snippet['cl_rule_type'];
			$snippet_data['cl_operator']  = $snippet['cl_operator'];
			$snippet_data['cl_value']     = $snippet['cl_value'];
		}

		wp_send_json_success(
			$snippet_data
		);
	}

	/**
	 * Improve or make changes to a snippet using the AI server.
	 *
	 * @return void
	 */
	public function improve_snippet() {
		check_ajax_referer( 'wpcode_admin' );

		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
			wp_send_json_error();
		}

		$text = isset( $_POST['text'] ) ? sanitize_text_field( wp_unslash( $_POST['text'] ) ) : '';
		$code = isset( $_POST['code'] ) ? $_POST['code'] : ''; // phpcs:ignore

		if ( empty( $text ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please describe the changes that you want to make to your snippet.', 'wpcode-premium' ),
				)
			);
		}
		if ( empty( $code ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No code to modify has been detected, please use the Generate button from the Add new snippet menu if you wish to generate a new snippet.', 'wpcode-premium' ),
				)
			);
		}

		$response = $this->make_request(
			'improve',
			array(
				'text' => $text,
				'code' => $code,
			)
		);

		$snippet = $this->process_snippet( $response );

		$snippet_data = array(
			'code' => $snippet['code'],
		);

		wp_send_json_success(
			$snippet_data
		);
	}

	/**
	 * Make a request to the AI server.
	 *
	 * @param string $endpoint The endpoint to make the request to.
	 * @param array  $data The data to send in the request.
	 *
	 * @return WP_Error|array
	 */
	private function make_request( $endpoint, $data ) {
		$ai_url = $this->get_url( $endpoint );

		$headers                        = wpcode()->library->get_authenticated_headers();
		$headers['Content-Type']        = 'application/json';
		$headers['X-WPCode-LicenseKey'] = wpcode()->license->get();

		$response = wp_remote_post(
			$ai_url,
			array(
				'body'    => wp_json_encode(
					$data
				),
				'headers' => $headers,
				'timeout' => 60,
			)
		);

		return $response;
	}

	/**
	 * Process the snippet response.
	 *
	 * @param WP_Error|array $response The response from the AI server.
	 *
	 * @return array
	 */
	private function process_snippet( $response ) {
		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Could not connect to the AI server.', 'wpcode-premium' ),
				)
			);
		}

		$body    = wp_remote_retrieve_body( $response );
		$snippet = json_decode( $body, true );

		if ( isset( $snippet['error'] ) ) {
			$error_message = __( 'Could not connect to the AI server.', 'wpcode-premium' );

			if ( ! empty( $snippet['error_message'] ) ) {
				$error_message = $snippet['error_message'];
			}
			wp_send_json_error(
				array(
					'message' => esc_html( $error_message ),
				)
			);
		}

		return $snippet;
	}
}

new WPCode_AI_Handler();
