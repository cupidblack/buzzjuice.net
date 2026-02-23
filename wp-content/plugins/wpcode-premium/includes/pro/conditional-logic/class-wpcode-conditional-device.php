<?php
/**
 * Class that handles conditional logic for device type
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WPCode_Conditional_Device class.
 */
class WPCode_Conditional_Device extends WPCode_Conditional_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'device';

	/**
	 * The type category.
	 *
	 * @var string
	 */
	public $category = 'who';

	/**
	 * Set the translatable label.
	 *
	 * @return void
	 */
	protected function set_label() {
		$this->label = __( 'Device', 'wpcode-premium' );
	}

	/**
	 * Load evaluation-only options (without translations) for frontend use.
	 * This method is called when translations are not yet loaded to avoid WP 6.7 notices.
	 *
	 * @return void
	 */
	public function load_evaluation_options() {
		$this->options = array(
			'device_type'  => array(
				'callback' => array( $this, 'get_device_type' ),
			),
			'browser'      => array(
				'callback' => array( $this, 'get_browser_type' ),
			),
			'os'           => array(
				'callback' => array( $this, 'get_os_type' ),
			),
			'cookie_name'  => array(
				'callback' => array( $this, 'get_cookie_names' ),
			),
			'cookie_value' => array(
				'callback' => array( $this, 'get_cookie_values' ),
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
			'device_type'  => array(
				'label'       => __( 'Device Type', 'wpcode-premium' ),
				'description' => __( 'Target either desktop or mobile devices.', 'wpcode-premium' ),
				'type'        => 'select',
				'options'     => array(
					array(
						'label' => __( 'Desktop', 'wpcode-premium' ),
						'value' => 'desktop',
					),
					array(
						'label' => __( 'Mobile', 'wpcode-premium' ),
						'value' => 'mobile',
					),
				),
				'callback'    => array( $this, 'get_device_type' ),
			),
			'browser'      => array(
				'label'           => __( 'Browser Type', 'wpcode-premium' ),
				'description'     => __( 'Target specific visitor web browsers.', 'wpcode-premium' ),
				'type'            => 'select',
				'options'         => array(
					array(
						'label' => __( 'Chrome', 'wpcode-premium' ),
						'value' => 'chrome',
					),
					array(
						'label' => __( 'Firefox', 'wpcode-premium' ),
						'value' => 'firefox',
					),
					array(
						'label' => __( 'Safari', 'wpcode-premium' ),
						'value' => 'safari',
					),
					array(
						'label' => __( 'Opera', 'wpcode-premium' ),
						'value' => 'opera',
					),
					array(
						'label' => __( 'IE', 'wpcode-premium' ),
						'value' => 'ie',
					),
					array(
						'label' => __( 'Edge', 'wpcode-premium' ),
						'value' => 'edge',
					),
				),
				'multiple'        => true,
				'callback'        => array( $this, 'get_browser_type' ),
				'operator_labels' => array(
					'='  => __( 'Is one of', 'wpcode-premium' ),
					'!=' => __( 'Is not one of', 'wpcode-premium' ),
				),
			),
			'os'           => array(
				'label'           => __( 'Operating System', 'wpcode-premium' ),
				'description'     => __( 'Target operating systems like Windows, Mac OS or Linux.', 'wpcode-premium' ),
				'type'            => 'select',
				'options'         => array(
					array(
						'label' => __( 'Windows', 'wpcode-premium' ),
						'value' => 'windows',
					),
					array(
						'label' => __( 'Mac', 'wpcode-premium' ),
						'value' => 'mac',
					),
					array(
						'label' => __( 'Linux', 'wpcode-premium' ),
						'value' => 'linux',
					),
					array(
						'label' => __( 'Android', 'wpcode-premium' ),
						'value' => 'android',
					),
					array(
						'label' => __( 'iOS', 'wpcode-premium' ),
						'value' => 'ios',
					),
				),
				'multiple'        => true,
				'operator_labels' => array(
					'='  => __( 'Is one of', 'wpcode-premium' ),
					'!=' => __( 'Is not one of', 'wpcode-premium' ),
				),
				'callback'        => array( $this, 'get_os_type' ),
			),
			'cookie_name'  => array(
				'label'       => __( 'Cookie Name', 'wpcode-premium' ),
				'description' => __( 'Load or hide a snippet by cookie name.', 'wpcode-premium' ),
				'type'        => 'text',
				'callback'    => array( $this, 'get_cookie_names' ),
			),
			'cookie_value' => array(
				'label'       => __( 'Cookie Value', 'wpcode-premium' ),
				'description' => __( 'Load or hide a snippet by cookie value.', 'wpcode-premium' ),
				'type'        => 'text',
				'callback'    => array( $this, 'get_cookie_values' ),
			),
		);
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				$this->options['device_type']['upgrade'] = array(
					'title'  => __( 'Device Rules are a Pro Feature', 'wpcode-premium' ),
					'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
					'link'   => add_query_arg(
						array(
							'page' => 'wpcode-settings',
						),
						admin_url( 'admin.php' )
					),
					'button' => __( 'Add License Key Now', 'wpcode-premium' ),
				);
			}
		}
	}

	/**
	 * Get the Device type
	 *
	 * @return string
	 */
	public function get_device_type() {
		return wp_is_mobile() ? 'mobile' : 'desktop';
	}

	/**
	 * Get the browser type.
	 *
	 * @return string|void
	 */
	public function get_browser_type() {
		$user_agent = $this->get_user_agent();
		if ( strpos( $user_agent, 'Chrome' ) !== false ) {
			return 'chrome';
		}
		if ( strpos( $user_agent, 'Firefox' ) !== false ) {
			return 'firefox';
		}
		if ( strpos( $user_agent, 'Safari' ) !== false ) {
			return 'safari';
		}
		if ( strpos( $user_agent, 'Opera' ) !== false ) {
			return 'opera';
		}
		if ( strpos( $user_agent, 'Edge' ) !== false ) {
			return 'edge';
		}
		if ( strpos( $user_agent, 'Trident' ) !== false ) {
			return 'ie';
		}

		return '';
	}

	/**
	 * Get the OS type.
	 *
	 * @return string
	 */
	public function get_os_type() {
		$user_agent = $this->get_user_agent();
		if ( strpos( $user_agent, 'Windows' ) !== false ) {
			return 'windows';
		}
		if ( strpos( $user_agent, 'Macintosh' ) !== false ) {
			return 'mac';
		}
		if ( strpos( $user_agent, 'Linux' ) !== false ) {
			return 'linux';
		}
		if ( strpos( $user_agent, 'Android' ) !== false ) {
			return 'android';
		}
		if ( strpos( $user_agent, 'iPhone' ) !== false || strpos( $user_agent, 'iPad' ) !== false ) {
			return 'ios';
		}

		return '';
	}

	/**
	 * Get the user agent.
	 *
	 * @return string
	 */
	public function get_user_agent() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}

	/**
	 * Get an array of all the cookie names currently set.
	 *
	 * @return array
	 */
	public function get_cookie_names() {
		if ( ! isset( $_COOKIE ) ) {
			return array();
		}

		return array_keys( $_COOKIE );
	}

	/**
	 * Get an array of all the cookie values currently set.
	 *
	 * @return array
	 */
	public function get_cookie_values() {
		if ( ! isset( $_COOKIE ) ) {
			return array();
		}

		return array_values( $_COOKIE );
	}
}

new WPCode_Conditional_Device();
