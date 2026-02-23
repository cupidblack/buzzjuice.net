<?php
/**
 * Class that manages the Email.
 *
 * @class      YITH_Role_Changer_Admin_Email
 * @since      Version 1.0.0
 * @author     YITH <plugins@yithemes.com>
 * @package    YITH\AutomaticRoleChanger\Classes
 */

if ( ! defined( 'YITH_WCARC_VERSION' ) ) {
	exit( 'Direct access forbidden.' );
}

if ( ! class_exists( 'YITH_Role_Changer_Admin_Email' ) ) {
	/**
	 * Class YITH_Role_Changer_Admin_Email
	 *
	 */
	class YITH_Role_Changer_Admin_Email extends WC_Email {

		/** __construct function */
		public function __construct() {

			$this->id = 'yith_ywarc_admin_email';

			$this->title       = esc_html__( 'Automatic Role Changer email for Admin', 'yith-automatic-role-changer-for-woocommerce' );
			$this->description = esc_html__( 'The administrator will receive an email when an order with roles to be assigned goes to the "Completed" or "Processing" status.', 'yith-automatic-role-changer-for-woocommerce' );

			$this->heading = esc_html__( '{site_title} - new role change', 'yith-automatic-role-changer-for-woocommerce' );
			$this->subject = esc_html__( '{site_title} - new role change', 'yith-automatic-role-changer-for-woocommerce' );

			$this->template_html = 'emails/role-changer-admin.php';

			add_action( 'yith_wcarc_granted_roles_notification', array( $this, 'trigger' ), 10, 3 );
			add_filter( 'woocommerce_email_styles', array( $this, 'style' ) );

			parent::__construct();
			$this->recipient  = $this->get_option( 'recipient', get_option( 'admin_email' ) );
			$this->email_type = 'html';
		}

		/**
		 * The trigger to change all class variables.
		 *
		 * @param array    $rules   Valid Rules.
		 * @param int      $user_id ID of affected user.
		 * @param WC_Order $order   ID of order.
		 */
		public function trigger( $rules, $user_id, $order ) {
			if ( ! $this->is_enabled() || ! $rules ) {
				return;
			}

			$user     = new WP_User( $user_id );
			$username = $user->display_name ? $user->display_name : $user->nickname;
			$roles    = [];

			foreach ( $rules as $rule ) {
				$rule_to_display = yith_wcarc_get_rule_data_to_display( $rule );
				if ( ! empty( $rule_to_display['roles'] ) ) {
					foreach ( $rule_to_display['roles'] as $role_name ) {
						$roles[] = array(
							'name' => $role_name,
							'from' => $rule_to_display['from'] ?? '',
							'to'   => $rule_to_display['to'] ?? '',
						);
					}
				}
			}

			$message = __( "Hi \nOrder {order_number_link} on {site_title} triggered a role change for the user {customer_name}:\n{roles_details}\nRegards,\n{site_title}", 'yith-automatic-role-changer-for-woocommerce' );

			$this->placeholders['{order_number_link}'] = sprintf( '<a href="%s">#%s</a>', $order->get_edit_order_url(), $order->get_order_number() );
			$this->placeholders['{customer_name}']     = $username;
			$this->placeholders['{roles_details}']     = wc_get_template_html(
				'emails/email-roles-details.php',
				array(
					'roles'         => $roles,
					'username'      => $username,
					'sent_to_admin' => true,
				),
				'',
				YITH_WCARC_TEMPLATE_PATH
			);

			$message = $this->format_string( $message );

			$this->object = compact( 'message', 'roles', 'username' );

			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);
		}

		/**
		 * Return custom style
		 *
		 * @param mixed $style Custom and class-related style.
		 *
		 * @return mixed
		 */
		public function style( $style ) {
			$style .= '.yith_wcarc_gained_role{
					background: #f6f6f6;
					padding: 24px;
					text-align: center;
					line-height: 1.5;
					margin-top: 20px;
					margin-bottom: 20px;
				}
				
				.yith_wcarc_gained_role__message{
					font-size: .9em;
				}
				.yith_wcarc_gained_role__role{
					font-size: 2em;
					font-weight: 500;
				}
				
				.yith_wcarc_gained_role__dates{
					font-size: .9em;
					margin-top: 4px;
				}';

			return $style;
		}

		/**
		 * Return HTML content
		 *
		 * @return mixed
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array_merge(
					array(
						'email_heading' => $this->get_heading(),
						'sent_to_admin' => true,
						'plain_text'    => false,
						'email'         => $this,
					),
					$this->object
				),
				'',
				YITH_WCARC_TEMPLATE_PATH
			);
		}

		/** Create form fields. */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'   => array(
					'title'   => esc_html__( 'Enable/Disable', 'yith-automatic-role-changer-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Enable this email notification', 'yith-automatic-role-changer-for-woocommerce' ),
					'default' => 'yes',
				),
				'recipient' => array(
					'title'       => esc_html__( 'Recipient(s)', 'yith-automatic-role-changer-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s is replaced with default recipients. */
					'description' => sprintf( esc_html__( 'Enter the recipients (comma-separated) for this email. Defaults to %s.', 'yith-automatic-role-changer-for-woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'subject'   => array(
					'title'       => esc_html__( 'Subject', 'yith-automatic-role-changer-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s is replaced with default subject */
					'description' => sprintf( esc_html__( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'yith-automatic-role-changer-for-woocommerce' ), $this->subject ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'heading'   => array(
					'title'       => esc_html__( 'Email Heading', 'yith-automatic-role-changer-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s is replaced with default heading */
					'description' => sprintf( esc_html__( 'This controls the main heading included in the email notification. Leave blank to use the default heading: <code>%s</code>.', 'yith-automatic-role-changer-for-woocommerce' ), $this->heading ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
			);
		}

	}

}

return new YITH_Role_Changer_Admin_Email();
