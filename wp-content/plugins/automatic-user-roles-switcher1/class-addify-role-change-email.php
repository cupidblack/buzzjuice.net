<?php
/**
 * Main class start.
 *
 * @package : email
 */

defined( 'ABSPATH' ) || exit;
/**
 *  Addify_Role_Change_Email class start.
 */

class Addify_Role_Change_Email extends WC_Email {

	public function __construct() {

		$this->id             = 'wc_automatic_role_changer';
		
		$this->title          = __( 'Role Changed Email to Customer', 'custom-wc-email' );
		
		$this->customer_email = true;
		
		$this->description    = __( 'Role changed emails are sent to customer .', 'custom-wc-email' );
		
		$this->template_base  = AFARC_PLUGIN_DIR; // Base directory.
		
		$this->template_html  = 'emails/html-role-change-email.php';
		
		$this->template_plain = 'emails/plain-role-change-email.php';
		
		$this->placeholders   = array(
			
			'{customer_full_name}'        => '',
			
			'{customer_switch_from_role}' => '',
			
			'{customer_switch_to_role}'   => '',
		);

		add_action( 'addify_automayic_role_changed_email', array( $this, 'trigger' ), 10, 1 );
		
		// Call to the  parent constructor.
		parent::__construct();
	}
	
	public function get_default_subject() {
		
		return __( '[{site_title}]: Your role has been changed', 'custom-wc-email' );
	}
	
	public function get_default_heading() {
	
		return __( 'Role Changed', 'woocommerce' );
	}

	/**
	 * Function trigger.s
	 *
	 * @param int $user_id .
	 *
	 * @param int $user .
	 */
	public function trigger( $user_id, $user = false ) {
		
		$this->setup_locale();

		if ( $user_id && ! is_a( $user, 'WP_User' ) ) {
		
			$user = get_user_by( 'id', $user_id );
		}

		$history_from_user_profile = (array) get_user_meta( $user_id, 'af_arc_data', true );
		
		$history_user_profile_new  = end( $history_from_user_profile );

		$username   = $user->user_login;
		
		$first_name = get_user_meta( $user_id, 'billing_first_name', true );
		
		$last_name  = get_user_meta( $user_id, 'billing_last_name', true );
		
		$full_name  = $first_name . ' ' . $last_name;

		if ( is_a( $user, 'WP_User' ) ) {
		
			$this->object                                      = $user;
		
			$this->recipient                                   = $user->user_email;
		
			$this->placeholders['{customer_full_name}']        = $full_name;
		
			$this->placeholders['{customer_switch_from_role}'] = $history_user_profile_new['switch_from_role'];
		
			$this->placeholders['{customer_switch_to_role}']   = $history_user_profile_new['switch_to_role'];

		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
		
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Function get_email_content.
	 */
	public function get_email_content() {

		$email_content = get_option( 'arc_email_field' );
	
		$email_content = wpautop( wptexturize( $email_content ) );

		return apply_filters( 'addify_email_content_' . $this->id, $this->format_string( $email_content ), $this->object, $this );
	}

	/**
	 * Function get_content_html.
	 */
	
	public function get_content_html() {
	
		return wc_get_template_html(
	
			$this->template_html,
	
			array(
			
				'order'              => $this->object,
			
				'email_heading'      => $this->get_heading(),
			
				'additional_content' => $this->get_additional_content(),
			
				'content'            => $this->get_email_content(),
			
				'sent_to_admin'      => false,
			
				'plain_text'         => false,
			
				'email'              => $this,
			),
			
			$this->template_base,
			
			$this->template_base
		);
	}

	/**
	 * Function get_content_plain.
	 */
	
	public function get_content_plain() {
	
		return wc_get_template_html(
	
			$this->template_plain,
	
			array(
			
				'order'              => $this->object,
			
				'email_heading'      => $this->get_heading(),
			
				'additional_content' => $this->get_additional_content(),
			
				'content'            => $this->get_email_content(),
			
				'sent_to_admin'      => false,
			
				'plain_text'         => true,
			
				'email'              => $this,
			),

			$this->template_base,
			
			$this->template_base
		);
	}
		
	/**
	 * Function init_form_fields.
	 */
	
	public function init_form_fields() {

		/* translators: %s: placeholder */
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
		
		$this->form_fields = array(
		
			'enabled'            => array(
		
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
		
				'type'    => 'checkbox',
		
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
		
				'default' => 'yes',
			),

			// subject  in customer cancelled order email.
			'subject'            => array(
		
				'title'       => __( 'Subject', 'woocommerce' ),
		
				'type'        => 'text',
		
				'desc_tip'    => true,
		
				'description' => $placeholder_text,
		
				'placeholder' => $this->get_default_subject(),
		
				'default'     => '',
			),

			// heading  in customer cancelled order email.
			'heading'            => array(
			
				'title'       => __( 'Email heading', 'woocommerce' ),
			
				'type'        => 'text',
			
				'desc_tip'    => true,
			
				'description' => $placeholder_text,
			
				'placeholder' => $this->get_default_heading(),
			
				'default'     => '',
			),

			// additional content  in customer cancelled order email.
			'additional_content' => array(
			
				'title'       => __( 'Additional content', 'woocommerce' ),
			
				'description' => __( 'Text to appear below the main email content.', 'woocommerce' ) . ' ' . $placeholder_text,
			
				'css'         => 'width:400px; height: 75px;',
			
				'placeholder' => __( 'N/A', 'woocommerce' ),
			
				'type'        => 'textarea',
			
				'default'     => $this->get_default_additional_content(),
			
				'desc_tip'    => true,
			),
			
			// email type  in customer cancelled order email.
			'email_type'         => array(
			
				'title'       => __( 'Email type', 'woocommerce' ),
			
				'type'        => 'select',
			
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
			
				'default'     => 'html',
			
				'class'       => 'email_type wc-enhanced-select',
			
				'options'     => $this->get_email_type_options(),
			
				'desc_tip'    => true,
			),
		);
	}
}
