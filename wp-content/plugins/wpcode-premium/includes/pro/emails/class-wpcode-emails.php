<?php
/**
 * Emails.
 *
 * This class handles all (notification) emails sent by WPCode.
 *
 * Heavily influenced by the AffiliateWP plugin by Pippin Williamson.
 * https://github.com/AffiliateWP/AffiliateWP/blob/master/includes/emails/class-affwp-emails.php
 *
 * @since 2.1.7
 * @package WPCode
 */

/**
 * Class WPCode_Emails
 *
 * @since 2.1.7
 */
class WPCode_Emails {

	/**
	 * Store the from address.
	 *
	 * @since 2.1.7
	 *
	 * @var string
	 */
	private $from_address;

	/**
	 * Store the from name.
	 *
	 * @since 2.1.7
	 *
	 * @var string
	 */
	private $from_name;

	/**
	 * Store the reply-to address.
	 *
	 * @since 2.1.7
	 *
	 * @var bool|string
	 */
	private $reply_to = false;

	/**
	 * Store the reply-to name.
	 *
	 * @since 2.1.7
	 *
	 * @var bool|string
	 */
	private $reply_to_name = false;

	/**
	 * Store the carbon copy addresses.
	 *
	 * @since 2.1.7
	 *
	 * @var string
	 */
	private $cc = false;

	/**
	 * Store the email content type.
	 *
	 * @since 2.1.7
	 *
	 * @var string
	 */
	private $content_type;

	/**
	 * Store the email headers.
	 *
	 * @since 2.1.7
	 *
	 * @var string
	 */
	private $headers;

	/**
	 * Whether to send email in HTML.
	 *
	 * @since 2.1.7
	 *
	 * @var bool
	 */
	private $html = true;

	/**
	 * Get things going.
	 *
	 * @since 2.1.7
	 */
	public function __construct() {

		if ( 'none' === $this->get_template() ) {
			$this->html = false;
		}

		add_action( 'wpcode_email_send_before', array( $this, 'send_before' ) );
		add_action( 'wpcode_email_send_after', array( $this, 'send_after' ) );
	}

	/**
	 * Set a property.
	 *
	 * @param string $key Object property key.
	 * @param mixed  $value Object property value.
	 *
	 * @since 2.1.7
	 */
	public function __set( $key, $value ) {

		$this->$key = $value;
	}

	/**
	 * Get the email from name.
	 *
	 * @return string The email from name
	 * @since 2.1.7
	 */
	public function get_from_name() {

		$this->from_name = get_bloginfo( 'name' );

		return apply_filters( 'wpcode_email_from_name', sanitize_text_field( $this->from_name ), $this );
	}

	/**
	 * Get the email from address.
	 *
	 * @return string The email from address.
	 * @since 2.1.7
	 */
	public function get_from_address() {

		$this->from_address = get_option( 'admin_email' );

		return apply_filters( 'wpcode_email_from_address', sanitize_email( $this->from_address ), $this );
	}

	/**
	 * Get the email reply-to.
	 *
	 * @return string The email reply-to address.
	 * @since 2.1.7
	 */
	public function get_reply_to() {
		return apply_filters( 'wpcode_email_reply_to', sanitize_email( $this->reply_to ), $this );
	}

	/**
	 * Get the email carbon copy addresses.
	 *
	 * @return string The email reply-to address.
	 * @since 2.1.7
	 */
	public function get_cc() {

		if ( ! empty( $this->cc ) ) {
			$addresses = array_map( 'trim', explode( ',', $this->cc ) );

			foreach ( $addresses as $key => $address ) {
				if ( ! is_email( $address ) ) {
					unset( $addresses[ $key ] );
				}
			}

			$this->cc = implode( ',', $addresses );
		}

		return apply_filters( 'wpcode_email_cc', sanitize_email( $this->cc ), $this );
	}

	/**
	 * Get the email content type.
	 *
	 * @return string The email content type.
	 * @since 2.1.7
	 */
	public function get_content_type() {

		if ( ! $this->content_type && $this->html ) {
			$this->content_type = apply_filters( 'wpcode_email_default_content_type', 'text/html', $this );
		} elseif ( ! $this->html ) {
			$this->content_type = 'text/plain';
		}

		return apply_filters( 'wpcode_email_content_type', $this->content_type, $this );
	}

	/**
	 * Get the email headers.
	 *
	 * @return string The email headers.
	 * @since 2.1.7
	 */
	public function get_headers() {

		if ( ! $this->headers ) {
			$this->headers = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";

			if ( $this->get_reply_to() ) {
				$this->headers .= $this->reply_to_name ?
					"Reply-To: {$this->reply_to_name} <{$this->get_reply_to()}>\r\n" :
					"Reply-To: {$this->get_reply_to()}\r\n";
			}

			if ( $this->get_cc() ) {
				$this->headers .= "Cc: {$this->get_cc()}\r\n";
			}

			$this->headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";
		}

		return apply_filters( 'wpcode_email_headers', $this->headers, $this );
	}

	/**
	 * Build the email.
	 *
	 * @param string $message The email message.
	 *
	 * @return string
	 * @since 2.1.7
	 */
	public function build_email( $message ) {

		// Plain text email shortcut.
		if ( false === $this->html ) {
			return apply_filters( 'wpcode_email_message', wp_kses_post( $message ), $this );
		}

		/*
		 * Generate an HTML email.
		 */

		ob_start();

		$this->get_template_part( 'header', $this->get_template(), true );

		// Hooks into the email header.
		do_action( 'wpcode_email_header', $this );

		$this->get_template_part( 'body', $this->get_template(), true );

		// Hooks into the email body.
		do_action( 'wpcode_email_body', $this );

		$this->get_template_part( 'footer', $this->get_template(), true );

		// Hooks into the email footer.
		do_action( 'wpcode_email_footer', $this );
		$message = nl2br( $message );
		$body    = ob_get_clean();

		$message = str_replace( '{email}', $message, $body );

		$message = make_clickable( $message );

		return apply_filters( 'wpcode_email_message', $message, $this );
	}

	/**
	 * Send the email.
	 *
	 * @param string $to The To address.
	 * @param string $subject The subject line of the email.
	 * @param string $message The body of the email.
	 * @param array  $attachments Attachments to the email.
	 *
	 * @return bool
	 * @since 2.1.7
	 */
	public function send( $to, $subject, $message, $attachments = array() ) {
		// Don't send anything if emails have been disabled.
		if ( $this->is_email_disabled() ) {
			return false;
		}

		// Don't send if email address is invalid.
		if ( ! is_email( $to ) ) {
			return false;
		}

		// Hooks before email is sent.
		do_action( 'wpcode_email_send_before', $this );

		/*
		 * Allow to filter data on per-email basis,
		 * useful for localizations based on recipient email address, form settings,
		 * or for specific notifications - whatever available in WPCode_Emails class.
		 */
		$data = apply_filters(
			'wpcode_emails_send_email_data',
			array(
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $this->get_headers(),
				'attachments' => $attachments,
			),
			$this
		);

		// Let's do this NOW.
		$result = wp_mail(
			$data['to'],
			$this->get_prepared_subject( $data['subject'] ),
			$this->build_email( $data['message'] ),
			$data['headers'],
			$data['attachments']
		);

		/**
		 * Hooks after the email is sent.
		 *
		 * @param WPCode_Emails $this Current instance of this object.
		 *
		 * @since 2.1.7
		 */
		do_action( 'wpcode_email_send_after', $this );

		return $result;
	}

	/**
	 * Add filters/actions before the email is sent.
	 *
	 * @since 2.1.7
	 */
	public function send_before() {

		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	/**
	 * Remove filters/actions after the email is sent.
	 *
	 * @since 2.1.7
	 */
	public function send_after() {

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	/**
	 * Convert text formatted HTML. This is primarily for turning line breaks
	 * into <p> and <br/> tags.
	 *
	 * @param string $message Text to convert.
	 *
	 * @return string
	 * @since 2.1.7
	 */
	public function text_to_html( $message ) {

		if ( 'text/html' === $this->content_type || true === $this->html ) {
			$message = wpautop( $message );
		}

		return $message;
	}

	/**
	 * Email kill switch if needed.
	 *
	 * @return bool
	 * @since 2.1.7
	 */
	public function is_email_disabled() {

		return (bool) apply_filters( 'wpcode_disable_all_emails', false, $this );
	}

	/**
	 * Get the enabled email template.
	 *
	 * @return string When filtering return 'none' to switch to text/plain email.
	 * @since 2.1.7
	 */
	public function get_template() {
		return apply_filters( 'wpcode_email_template', 'default' );
	}

	/**
	 * Retrieve a template part. Taken from bbPress.
	 *
	 * @param string $slug Template file slug.
	 * @param string $name Optional. Default null.
	 * @param bool   $load Maybe load.
	 *
	 * @return string
	 * @since 2.1.7
	 */
	public function get_template_part( $slug, $name = null, $load = true ) {

		// Setup possible parts.
		$templates = array();

		if ( isset( $name ) ) {
			$templates[] = $slug . '-' . $name . '.php';
		}

		$templates[] = $slug . '.php';

		// Return the part that is found.
		return $this->locate_template( $templates, $load, false );
	}

	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * Search in the STYLESHEETPATH before TEMPLATEPATH so that themes which
	 * inherit from a parent theme can just overload one file. If the template is
	 * not found in either of those, it looks in the theme-compat folder last.
	 *
	 * Taken from bbPress.
	 *
	 * @param string|array $template_names Template file(s) to search for, in order.
	 * @param bool         $load If true the template file will be loaded if it is found.
	 * @param bool         $require_once Whether to require_once or require. Default true.
	 *                                     Has no effect if $load is false.
	 *
	 * @return string The template filename if one is located.
	 * @since 2.1.7
	 */
	public function locate_template( $template_names, $load = false, $require_once = true ) {

		// No file found yet.
		$located = false;

		// Try to find a template file.
		foreach ( (array) $template_names as $template_name ) {

			// Continue if template is empty.
			if ( empty( $template_name ) ) {
				continue;
			}

			// Trim off any slashes from the template name.
			$template_name = ltrim( $template_name, '/' );

			$plugin_path = WPCODE_PLUGIN_PATH;

			if ( file_exists( $plugin_path . 'includes/pro/emails/templates/' . $template_name ) ) {
				$located = $plugin_path . 'includes/pro/emails/templates/' . $template_name;
				break;
			}
		}

		if ( ( true === $load ) && ! empty( $located ) ) {
			load_template( $located, $require_once );
		}

		return $located;
	}

	/**
	 * Perform email subject preparation: process tags, remove new lines, etc.
	 *
	 * @param string $subject Email subject to post-process.
	 *
	 * @return string
	 * @since 1.6.1
	 */
	private function get_prepared_subject( $subject ) {
		$subject = trim( str_replace( array( "\r\n", "\r", "\n" ), ' ', $subject ) );

		return sanitize_text_field( $subject );
	}
}
