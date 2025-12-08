<?php
/**
 * Pro File editor page.
 *
 * @package WPCode
 */

/**
 * Class for the file editor page.
 */
class WPCode_Admin_Page_File_Editor_Pro extends WPCode_Admin_Page_File_Editor {

	/**
	 * The page slug.
	 *
	 * @var string
	 */
	public function page_hooks() {
		parent::page_hooks();

		add_action( 'admin_init', array( $this, 'maybe_show_physical_file_notice' ) );
		// Listen for the form submission.
		add_action( 'admin_init', array( $this, 'submit_listener' ) );
	}

	/**
	 * Listen for submit events and save the code specific to the view.
	 *
	 * @return void
	 */
	public function submit_listener() {
		if ( ! current_user_can( 'wpcode_file_editor' ) ) {
			// User doesn't have the required capability.
			return;
		}

		if ( ! isset( $_REQUEST[ 'wpcode-edit-' . $this->view . '-nonce' ] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST[ 'wpcode-edit-' . $this->view . '-nonce' ] ), 'wpcode-edit-' . $this->view ) ) {
			// Nonce is missing, so we're not even going to try.
			return;
		}

		if ( ! in_array( $this->view, array_keys( $this->views ), true ) ) {
			// Invalid view.
			return;
		}

		if ( ! isset( $_REQUEST[ 'wpcode_file_' . $this->view ]['content'] ) ) {
			// No input to process.
			return;
		}

		$value = array(
			'content' => sanitize_textarea_field( wp_unslash( $_REQUEST[ 'wpcode_file_' . $this->view ]['content'] ) ),
			'enabled' => isset( $_REQUEST[ 'wpcode_file_' . $this->view ]['enabled'] ),
		);

		// Let's save the view code to an option that we can later use to generate the file.
		update_option( 'wpcode_file_' . $this->view, $value, false );

		wp_safe_redirect(
			add_query_arg(
				array(
					'message' => 1,
				),
				$this->get_page_action_url()
			)
		);
		exit;
	}

	/**
	 * Get the value to display in the editor for the current view.
	 *
	 * @return array
	 */
	public function get_value() {
		return get_option(
			'wpcode_file_' . $this->view,
			array(
				'enabled' => true,
				'content' => '',
			)
		);
	}

	/**
	 * Wrap this page in a form tag.
	 *
	 * @return void
	 */
	public function output() {
		?>
		<form action="<?php echo esc_url( $this->get_page_action_url() ); ?>" method="post">
			<?php parent::output(); ?>
		</form>
		<?php
	}

	/**
	 * Output the page content.
	 *
	 * @return void
	 */
	public function output_content() {
		$this->file_editor_area();
	}

	/**
	 * If the file you're trying to edit exists on the server, show a notice.
	 *
	 * @return void
	 */
	public function maybe_show_physical_file_notice() {
		$filename = $this->views[ $this->view ];

		if ( file_exists( ABSPATH . $filename ) ) {
			$this->set_error_message(
				sprintf(
				// Translators: %s is the filename.
					esc_html__( 'WPCode has detected a physical %s file in the root folder of your WordPress installation. In order to manage the file with WPCode you will need to remove the physical file from your server.', 'wpcode-premium' ),
					'<code>' . esc_html( $filename ) . '</code>'
				)
			);
		} else {
			$this->can_edit = true;

			if ( method_exists( $this, 'validate_' . $this->view ) ) {
				$errors = call_user_func( array( $this, 'validate_' . $this->view ), $this->get_value()['content'] );
				if ( ! empty( $errors ) ) {
					$this->set_error_message( implode( '<br>', $errors ) );
				}
			}
		}

		if ( isset( $_GET['message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->set_success_message( __( 'File saved successfully.', 'wpcode-premium' ) );
		}
	}

	/**
	 * App-ads.txt has the same rules as ads.txt.
	 *
	 * @param string $content The content of the app-ads.txt file.
	 *
	 * @return array
	 */
	public function validate_appadstxt( $content ) {
		return $this->validate_adstxt( $content );
	}

	/**
	 * Goes through the content of an ads.txt file and returns an array of errors.
	 *
	 * @param string $content The content of the ads.txt file.
	 *
	 * @return array
	 */
	public function validate_adstxt( $content ) {

		$errors = array();
		// Let's split the content by line.
		$lines = explode( "\n", $content );

		// Let's remove empty lines.
		$lines = array_filter( $lines );

		// Let's loop through the lines and validate each line for a correct ads.txt format.
		foreach ( $lines as $i => $line ) {
			// First, let's skip comments.
			if ( 0 === strpos( $line, '#' ) ) {
				continue;
			}
			// Ignore comments at the end of the line.
			$line = explode( '#', $line );
			$line = trim( $line[0] );

			if ( empty( $line ) ) {
				continue;
			}

			// Let's check if this line is for a variable.
			// Ads.txt files support the following variables: contact, inventorypartnerdomain, subdomain, ownerdomain and managerdomain.
			// If the line starts with one of these followed by a = sign, then it's a variable and we should skip it.
			$variables = array(
				'contact',
				'inventorypartnerdomain',
				'subdomain',
				'ownerdomain',
				'managerdomain',
			);
			// Let's do a check that is case insensitive.
			$line_lowercase = strtolower( $line );
			foreach ( $variables as $variable ) {
				if ( 0 === strpos( $line_lowercase, $variable . '=' ) ) {
					continue 2;
				}
			}

			// Each line should have 3 or 4 comma-separated fields.
			$fields = explode( ',', $line );
			// First field should be The canonical domain name of the system where bidders connect.
			if ( empty( $fields[0] ) ) {
				// Let's add an error and mention the line number.
				// translators: %d is the line number.
				$errors[] = sprintf( __( 'Line %d: The first field should be a domain name and it\'s currently empty.', 'wpcode-premium' ), $i + 1 );
			} else {
				// Let's check if the domain is valid.
				$domain = strtolower( trim( $fields[0] ) );
				// Check if it's a valid domain.
				if ( ! preg_match( '/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/', $domain ) ) {
					// Let's add an error and mention the line number.
					// translators: %1$d is the line number, %2$s is the domain name.
					$errors[] = sprintf( __( 'Line %1$d: The first field should be a valid domain name and it\'s currently "%2$s".', 'wpcode-premium' ), $i + 1, $domain );
				}
			}

			// Second field should be the publisher's account ID.
			if ( empty( $fields[1] ) ) {
				// Let's add an error and mention the line number.
				// translators: %d is the line number.
				$errors[] = sprintf( __( 'Line %d: The second field should be a publisher\'s account ID and it\'s currently empty.', 'wpcode-premium' ), $i + 1 );
			} else {
				// Let's check if the account ID is valid.
				$account_id = trim( $fields[1] );
				if ( ! preg_match( '/^[\w-]+$/', $account_id ) ) {
					// Let's add an error and mention the line number.
					// translators: %1$s is the line number, %2$s is the account ID.
					$errors[] = sprintf( __( 'Line %1$d: The second field should be a valid publisher\'s account ID and it\'s currently "%2$s".', 'wpcode-premium' ), $i + 1, $account_id );
				}
			}

			// Third field should be either DIRECT or RESELLER.
			if ( empty( $fields[2] ) ) {
				// Let's add an error and mention the line number.
				// translators: %s is the line number.
				$errors[] = sprintf( __( 'Line %d: The third field should be either DIRECT or RESELLER and it\'s currently empty.', 'wpcode-premium' ), $i + 1 );
			} else {
				// Let's check if the account ID is valid.
				$account_id = trim( $fields[2] );
				// Let's make this caseinsensitive.
				$account_id = strtoupper( $account_id );
				if ( ! in_array( $account_id, array( 'DIRECT', 'RESELLER' ), true ) ) {
					// Let's add an error and mention the line number.
					// translators: %d is the line number %s is the invalid field.
					$errors[] = sprintf( __( 'Line %1$d: The third field should be either DIRECT or RESELLER and it\'s currently "%2$s".', 'wpcode-premium' ), $i + 1, $account_id );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			// Prepend a message to explain that we found validation errors.
			array_unshift(
				$errors,
				esc_html__( 'We detected the following validation errors in your file, please fix them to avoid disruptions:', 'wpcode-premium' )
			);
		}

		return $errors;
	}

	/**
	 * Validate a robots.txt file.
	 *
	 * @param string $content The content of the robots.txt file.
	 *
	 * @return array an array of errors, if any.
	 */
	public function validate_robotstxt( $content ) {
		$errors = array();

		// Let's split it up line by line.
		$lines = explode( "\n", $content );

		$allowed_directives = array(
			'User-agent',
			'Allow',
			'Disallow',
			'Sitemap',
			'Crawl-delay',
			'Clean-param',
		);

		// Let's loop through each line and make sure it's valid for robots.txt content.
		foreach ( $lines as $i => $line ) {

			$line = trim( $line );

			// Let's skip empty lines.
			if ( empty( $line ) ) {
				continue;
			}
			// Let's skip comments.
			if ( 0 === strpos( $line, '#' ) ) {
				continue;
			}
			// Let's make sure the line has a colon.
			if ( false === strpos( $line, ':' ) ) {
				// Let's add an error and mention the line number.
				// translators: %d is the line number.
				$errors[] = sprintf( __( 'Line %d: The line should have a colon.', 'wpcode-premium' ), $i + 1 );
				continue;
			}
			// Let's split the line into two parts.
			$parts = explode( ':', $line );
			// Let's make sure the first part is either User-agent or Allow or Disallow or Sitemap.
			if ( ! in_array( trim( $parts[0] ), $allowed_directives, true ) ) {
				// Let's add an error and mention the line number.
				// translators: %1$d is the line number, %2$s is the invalid field.
				$errors[] = sprintf( __( 'Line %1$d: The first part of the line should be either User-agent or Allow or Disallow or Sitemap and it\'s currently "%2$s".', 'wpcode-premium' ), $i + 1, trim( $parts[0] ) );
			}
			// Let's make sure the second part is not empty.
			if ( empty( $parts[1] ) ) {
				// Let's add an error and mention the line number.
				// translators: %d is the line number.
				$errors[] = sprintf( __( 'Line %d: The second part of the line should not be empty.', 'wpcode-premium' ), $i + 1 );
			}
		}

		return $errors;
	}
}

