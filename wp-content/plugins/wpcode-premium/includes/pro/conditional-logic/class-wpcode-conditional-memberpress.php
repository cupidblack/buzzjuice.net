<?php
/**
 * Class that handles conditional logic related to MemberPress.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WPCode_Conditional_MemberPress class.
 */
class WPCode_Conditional_MemberPress extends WPCode_Conditional_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'memberpress';

	/**
	 * The type category.
	 *
	 * @var string
	 */
	public $category = 'ecommerce';

	/**
	 * Set the translatable label.
	 *
	 * @return void
	 */
	protected function set_label() {
		$this->label = 'MemberPress';
	}

	/**
	 * Hooks specific to this conditional type.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_ajax_wpcode_memberpress_get_memberships', array( $this, 'ajax_get_memberships' ) );
	}

	/**
	 * Load evaluation-only options (without translations) for frontend use.
	 * This method is called when translations are not yet loaded to avoid WP 6.7 notices.
	 *
	 * @return void
	 */
	public function load_evaluation_options() {
		$this->options = array(
			'memberpress_page' => array(
				'callback' => array( $this, 'get_page_type' ),
			),
			'memberpress_user' => array(
				'callback' => array( $this, 'get_membership' ),
			),
		);
	}

	/**
	 * Set the type options for the admin mainly.
	 *
	 * @return void
	 */
	public function load_type_options() {
		$this->options = array(
			'memberpress_page' => array(
				'label'       => __( 'MemberPress Page', 'wpcode-premium' ),
				'description' => __( 'Load the snippet on specific MemberPress pages.', 'wpcode-premium' ),
				'type'        => 'select',
				'options'     => array(
					array(
						'label' => __( 'Registration Page', 'wpcode-premium' ),
						'value' => 'registration',
					),
					array(
						'label' => __( 'Thank You Page', 'wpcode-premium' ),
						'value' => 'thankyou',
					),
					array(
						'label' => __( 'Account Page', 'wpcode-premium' ),
						'value' => 'account',
					),
					array(
						'label' => __( 'Login Page', 'wpcode-premium' ),
						'value' => 'login',
					),
				),
				'callback'    => array( $this, 'get_page_type' ),
			),
			'memberpress_user' => array(
				'label'           => __( 'MemberPress User', 'wpcode-premium' ),
				'description'     => __( 'Check if the current user has a specific MemberPress subscription active.', 'wpcode-premium' ),
				'type'            => 'ajax',
				'options'         => 'wpcode_memberpress_get_memberships',
				'callback'        => array( $this, 'get_membership' ),
				'labels_callback' => array( $this, 'get_membership_labels' ),
				'operator_labels' => array(
					'='  => __( 'Is active on', 'wpcode-premium' ),
					'!=' => __( 'Is not active on', 'wpcode-premium' ),
				),
				'placeholder'     => __( 'Choose membership', 'wpcode-premium' ),
				'multiple'        => true,
			),
		);
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->options['memberpress_page']['upgrade']          = array(
					'title'  => __( 'MemberPress Page Rules is a Pro Feature', 'wpcode-premium' ),
					'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
					'link'   => add_query_arg(
						array(
							'page' => 'wpcode-settings',
						),
						admin_url( 'admin.php' )
					),
					'button' => __( 'Add License Key Now', 'wpcode-premium' ),
				);
				$this->options['memberpress_user']['upgrade']          = $this->options['memberpress_page']['upgrade'];
				$this->options['memberpress_user']['upgrade']['title'] = __( 'MemberPress Active Membership Rules is a Pro Feature', 'wpcode-premium' );
			} elseif ( ! defined( 'MEPR_VERSION' ) ) {
				$this->options['memberpress_page']['upgrade'] = array(
					'title'  => __( 'MemberPress is Not Installed', 'wpcode-premium' ),
					'text'   => __( 'Please install and activate MemberPress to use this feature.', 'wpcode-premium' ),
					'link'   => 'https://memberpress.com?utm_source=wpcode-plugin&utm_medium=conditional-logic',
					'button' => __( 'Install MemberPress Now', 'wpcode-premium' ),
				);
				$this->options['memberpress_user']['upgrade'] = $this->options['memberpress_page']['upgrade'];
				$this->set_label();// Reset label.
				$this->label = $this->label . __( ' (Not Installed)', 'wpcode-premium' );
			}
		}
	}

	/**
	 * Get the MemberPress page type.
	 *
	 * @return string
	 */
	public function get_page_type() {
		if ( ! class_exists( 'MeprProduct' ) || ! class_exists( 'MeprUser' ) ) {
			return '';
		}
		global $post;

		if ( $this->is_thankyou_page() ) {
			return 'thankyou';
		}
		if ( MeprProduct::is_product_page( $post ) ) {
			return 'registration';
		}
		if ( MeprUser::is_account_page( $post ) ) {
			return 'account';
		}
		if ( $this->is_login_page() ) {
			return 'login';
		}

		return '';
	}

	/**
	 * Check if the current page is a MemberPress thank you page.
	 *
	 * @return bool
	 */
	public function is_thankyou_page() {
		$req = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $req['membership'] ) || ! isset( $req['membership_id'] ) || ( ! isset( $req['trans_num'] ) && ! isset( $req['transaction_id'] ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the current page is the MemberPress Login Page.
	 *
	 * @return bool
	 */
	public function is_login_page() {
		$mepr_options  = MeprOptions::fetch();
		$login_page_id = ( ! empty( $mepr_options->login_page_id ) && $mepr_options->login_page_id > 0 ) ? $mepr_options->login_page_id : 0;

		return is_page( $login_page_id );
	}

	/**
	 * Get the MemberPress memberships via AJAX.
	 *
	 * @return void
	 */
	public function ajax_get_memberships() {
		check_ajax_referer( 'wpcode_admin' );

		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
			wp_send_json_error();
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		// Get memberpress memberships by term.
		$memberships = MeprProduct::get_all();

		// Filter memberships by $term.
		if ( ! empty( $term ) ) {
			$memberships = array_filter(
				$memberships,
				function ( $membership ) use ( $term ) {
					return false !== stripos( $membership->post_title, $term );
				}
			);
		}

		$results = array();

		foreach ( $memberships as $membership ) {
			$results[] = array(
				'id'   => $membership->ID,
				'text' => $membership->post_title,
			);
		}

		wp_send_json(
			array(
				'results' => $results,
			)
		);
	}

	/**
	 * Get the current user's active MemberPress membership(s).
	 *
	 * @return array
	 */
	public function get_membership() {
		// Get the current user.
		$user = wp_get_current_user();
		// Get the current user MemberPress memberships.
		$user = new MeprUser( $user->ID );
		// Get the current user's active MemberPress memberships.
		$memberships = $user->active_product_subscriptions();

		return $memberships;
	}

	/**
	 * Get the current user's active MemberPress membership(s) labels.
	 *
	 * @param array $values The membership IDs.
	 *
	 * @return array
	 */
	public function get_membership_labels( $values ) {
		$labels = array();
		foreach ( $values as $membership_id ) {
			$membership = MeprProduct::get_one( $membership_id );
			if ( is_null( $membership ) || is_wp_error( $membership ) ) {
				continue;
			}
			$labels[] = array(
				'value' => $membership_id,
				'label' => $membership->post_title,
			);
		}

		return $labels;
	}
}

new WPCode_Conditional_MemberPress();
