<?php
/**
 * MemberPress specific auto-insert locations.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Auto_Insert_MemberPress.
 */
class WPCode_Auto_Insert_MemberPress extends WPCode_Auto_Insert_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'memberpress';
	/**
	 * The category of this type.
	 *
	 * @var string
	 */
	public $category = 'ecommerce';

	/**
	 * Load the available options and labels.
	 *
	 * @return void
	 */
	public function init() {
		$this->locations = array(
			'mepr-above-checkout-form'          => array(),
			'mepr-checkout-before-submit'       => array(),
			'mepr-checkout-before-coupon-field' => array(),
			'mepr-account-home-before-name'     => array(),
			'mepr_before_account_subscriptions' => array(),
			'mepr-login-form-before-submit'     => array(),
			'mepr_unauthorized_message_before'  => array(),
			'mepr_unauthorized_message_after'   => array(),
		);
	}

	/**
	 * Load the upgrade strings for this type.
	 *
	 * @return void
	 */
	public function load_upgrade_strings() {
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->label_pill     = 'PRO';
				$this->code_type      = 'pro';
				$this->upgrade_title  = __( 'MemberPress Page Rules is a Pro Feature', 'wpcode-premium' );
				$this->upgrade_text   = __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' );
				$this->upgrade_link   = add_query_arg(
					array(
						'page' => 'wpcode-settings',
					),
					admin_url( 'admin.php' )
				);
				$this->upgrade_button = __( 'Add License Key Now', 'wpcode-premium' );
			} elseif ( ! defined( 'MEPR_VERSION' ) ) {
				$this->label_pill     = 'Not Installed';
				$this->code_type      = 'pro';
				$this->upgrade_title  = __( 'MemberPress is Not Installed', 'wpcode-premium' );
				$this->upgrade_text   = __( 'Please install and activate MemberPress to use this feature.', 'wpcode-premium' );
				$this->upgrade_link   = 'https://memberpress.com?utm_source=wpcode-plugin&utm_medium=auto-insert';
				$this->upgrade_button = __( 'Install MemberPress Now', 'wpcode-premium' );
			}
		}
	}

	/**
	 * Load the label for this type.
	 *
	 * @return void
	 */
	public function load_label() {
		$this->label     = 'MemberPress';
	}

	/**
	 * Load the locations for this type.
	 *
	 * @return void
	 */
	public function load_locations() {
		$this->locations = array(
			'mepr-above-checkout-form'          => array(
				'label'       => __( 'Before the Registration Form', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the MemberPress registration form used for checkout.', 'wpcode-premium' ),
			),
			'mepr-checkout-before-submit'       => array(
				'label'       => __( 'Before Checkout Submit Button', 'wpcode-premium' ),
				'description' => __( 'Insert snippet right before the MemberPress checkout submit button.', 'wpcode-premium' ),
			),
			'mepr-checkout-before-coupon-field' => array(
				'label'       => __( 'Before Checkout Coupon Field', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the MemberPress checkout coupon field.', 'wpcode-premium' ),
			),
			'mepr-account-home-before-name'     => array(
				'label'       => __( 'Before Account First Name', 'wpcode-premium' ),
				'description' => __( 'Insert snippet to the Home tab of the MemberPress Account page before First Name field.', 'wpcode-premium' ),
			),
			'mepr_before_account_subscriptions' => array(
				'label'       => __( 'Before Subscriptions Content', 'wpcode-premium' ),
				'description' => __( 'Insert snippet at the beginning of the Subscriptions tab on the MemberPress Account page.', 'wpcode-premium' ),
			),
			'mepr-login-form-before-submit'     => array(
				'label'       => __( 'Before Login Form Submit', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the Remember Me checkbox on the MemberPress Login page.', 'wpcode-premium' ),
			),
			'mepr_unauthorized_message_before'  => array(
				'label'       => __( 'Before the Unauthorized Message', 'wpcode-premium' ),
				'description' => __( 'Insert a snippet before the notice that access to the content is unauthorized. ', 'wpcode-premium' ),
			),
			'mepr_unauthorized_message_after'   => array(
				'label'       => __( 'After the Unauthorized Message', 'wpcode-premium' ),
				'description' => __( 'Insert a snippet after the notice that access to the content is unauthorized. ', 'wpcode-premium' ),
			),
		);
	}

	/**
	 * WooCommerce-specific hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		// Could probably loop here, but it's easier to find hooked actions this way.
		add_action( 'mepr-above-checkout-form', array( $this, 'above_checkout_form' ) );
		add_action( 'mepr-checkout-before-submit', array( $this, 'before_checkout_submit' ) );
		add_action( 'mepr-checkout-before-coupon-field', array( $this, 'before_checkout_coupon' ) );
		add_action( 'mepr-thank-you-page', array( $this, 'thank_you_page' ) );
		add_action( 'mepr-account-home-before-name', array( $this, 'account_home_before_name' ) );
		add_action( 'mepr_before_account_subscriptions', array( $this, 'account_before_subscriptions' ) );
		add_action( 'mepr-login-form-before-submit', array( $this, 'login_before_submit' ) );

		// Insert snippets before and after the unauthorized message.
		add_filter( 'mepr-unauthorized-message', array( $this, 'before_unauthorized_message' ) );
		add_filter( 'mepr-unauthorized-message', array( $this, 'after_unauthorized_message' ) );
	}

	/**
	 * Output snippets before the checkout form.
	 *
	 * @return void
	 */
	public function above_checkout_form() {
		$this->output_location( 'mepr-above-checkout-form' );
	}

	/**
	 * Output snippets before the thank you page.
	 *
	 * @return void
	 */
	public function thank_you_page() {
		$this->output_location( 'mepr-thank-you-page' );
	}

	/**
	 * Output snippets before the account form first name.
	 *
	 * @return void
	 */
	public function account_home_before_name() {
		$this->output_location( 'mepr-account-home-before-name' );
	}

	/**
	 * Output snippets before the account form first name.
	 *
	 * @return void
	 */
	public function account_before_subscriptions() {
		$this->output_location( 'mepr_before_account_subscriptions' );
	}

	/**
	 * Output snippets before the checkout submit button.
	 *
	 * @return void
	 */
	public function before_checkout_submit() {
		$this->output_location( 'mepr-checkout-before-submit' );
	}

	/**
	 * Output snippets before the checkout coupon field.
	 *
	 * @return void
	 */
	public function before_checkout_coupon() {
		$this->output_location( 'mepr-checkout-before-coupon-field' );
	}

	/**
	 * Output snippets before the login submit button.
	 *
	 * @return void
	 */
	public function login_before_submit() {
		$this->output_location( 'mepr-login-form-before-submit' );
	}

	/**
	 * Output snippets before the unauthorized message.
	 *
	 * @param string $message The unauthorized message.
	 *
	 * @return string
	 */
	public function before_unauthorized_message( $message ) {
		return $this->get_location( 'mepr_unauthorized_message_before' ) . $message;
	}

	/**
	 * Output snippets before the unauthorized message.
	 *
	 * @param string $message The unauthorized message.
	 *
	 * @return string
	 */
	public function after_unauthorized_message( $message ) {
		return $message . $this->get_location( 'mepr_unauthorized_message_after' );
	}

}

new WPCode_Auto_Insert_MemberPress();
