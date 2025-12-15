<?php
/**
 * Class that manages the Email.
 *
 * @class      YITH_Role_Changer_User_Email
 * @package    Yithemes
 * @since      Version 1.0.0
 * @author     YITH <plugins@yithemes.com>
 */

if ( ! defined( 'YITH_WCARC_VERSION' ) ) {
	exit( 'Direct access forbidden.' );
}

if ( ! class_exists( 'YITH_Role_Changer_User_Email' ) ) {
	/**
	 * Class YITH_Role_Changer_User_Email
	 *
	 */
	class YITH_Role_Changer_User_Email extends WC_Email {
		/**
		 * Current User ID
		 *
		 * @var int
		 */
		public $user_id = null;
		/**
		 * Current Order ID
		 *
		 * @var int
		 */
		public $order_id = null;

		/** __construct Class Function. */
		public function __construct() {

			$this->id             = 'yith_ywarc_user_email';
			$this->customer_email = true;

			$this->title       = esc_html__( 'Automatic Role Changer email for User', 'yith-automatic-role-changer-for-woocommerce' );
			$this->description = esc_html__( 'The user will receive an email when an order with roles to be assigned goes to the "Completed" or "Processing" status.', 'yith-automatic-role-changer-for-woocommerce' );

			$this->heading = esc_html__( 'Your new role on {site_title}!', 'yith-automatic-role-changer-for-woocommerce' );
			$this->subject = esc_html__( 'Your new role on {site_title}!', 'yith-automatic-role-changer-for-woocommerce' );

			$this->template_html = 'emails/role-changer-user.php';

			add_action( 'yith_wcarc_granted_roles_notification', array( $this, 'trigger' ), 10, 3 );
			add_filter( 'woocommerce_email_styles', array( $this, 'style' ) );

			parent::__construct();
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

			$this->recipient = $order->get_billing_email();

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

			$message = _n(
				"Hi {customer_name},\nThank you for your purchase!\nYou've been assigned a new role on our site:\n{roles_details}\nRegards,\n{site_title}",
				"Hi {customer_name},\nThank you for your purchase!\nYou've been assigned new roles on our site:\n{roles_details}\nRegards,\n{site_title}",
				count( $roles ),
				'yith-automatic-role-changer-for-woocommerce'
			);

			$this->placeholders['{customer_name}'] = $username;
			$this->placeholders['{roles_details}'] = wc_get_template_html(
				'emails/email-roles-details.php',
				array(
					'roles'         => $roles,
					'username'      => $username,
					'sent_to_admin' => false,
				),
				'',
				YITH_WCARC_TEMPLATE_PATH
			);

			$message = $this->format_string( $message );

			$this->object = compact( 'message', 'roles' );

			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);
		}

		/**
		 * Return class' custom style.
		 *
		 * @param mixed $style Previous style (if there is one).
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

		/** Get content as HTML */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array_merge(
					array(
						'email_heading' => $this->get_heading(),
						'sent_to_admin' => false,
						'plain_text'    => false,
						'email'         => $this,
					),
					$this->object
				),
				'',
				YITH_WCARC_TEMPLATE_PATH
			);
		}

		/** Get content as Plain Text */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => false,
					'plain_text'    => true,
					'email'         => $this,
				),
				'',
				YITH_WCARC_TEMPLATE_PATH
			);
		}

		/** Create form fields. */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'    => array(
					'title'   => esc_html__( 'Enable/Disable', 'yith-automatic-role-changer-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Enable this email notification', 'yith-automatic-role-changer-for-woocommerce' ),
					'default' => 'yes',
				),
				'subject'    => array(
					'title'       => esc_html__( 'Subject', 'yith-automatic-role-changer-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s is replaced with default subject. */
					'description' => sprintf( esc_html__( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'yith-automatic-role-changer-for-woocommerce' ), $this->subject ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'heading'    => array(
					'title'       => esc_html__( 'Email Heading', 'yith-automatic-role-changer-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s is replaced with default heading. */
					'description' => sprintf( esc_html__( 'This controls the main heading included in the email notification. Leave blank to use the default heading: <code>%s</code>.', 'yith-automatic-role-changer-for-woocommerce' ), $this->heading ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'email_type' => array(
					'title'       => esc_html__( 'Email type', 'yith-automatic-role-changer-for-woocommerce' ),
					'type'        => 'select',
					/* translators: %s is replaced with default email type. */
					'description' => esc_html__( 'Choose the format of the email to send.', 'yith-automatic-role-changer-for-woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}
}

return new YITH_Role_Changer_User_Email();
