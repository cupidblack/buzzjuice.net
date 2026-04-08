<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define the main plugin file path
if ( ! defined( 'EE_MYCRED_PAYMENT_METHOD_PLUGIN_FILE' ) ) {
    define( 'EE_MYCRED_PAYMENT_METHOD_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'EE_MYCRED_PAYMENT_METHOD_BASENAME' ) ) {
    define( 'EE_MYCRED_PAYMENT_METHOD_BASENAME', plugin_basename( EE_MYCRED_PAYMENT_METHOD_PLUGIN_FILE ) );
}

define( 'EE_MYCRED_PAYMENT_METHOD_PATH', plugin_dir_path( __FILE__ ) );

// Initialize the MyCRED Payment Method when admin is ready
add_action( 'admin_init', function () {
	if ( class_exists( 'EE_MyCRED_Payment_Method' ) ) {
		$instance = new EE_MyCRED_Payment_Method();
		$instance->additional_admin_hooks();
	}
} );

if ( ! class_exists( 'EE_Addon' ) ) return;

// EE_MyCRED_Payment_Method class definition
class EE_MyCRED_Payment_Method extends EE_Addon {

	/**
	 * class constructor
	 */
	public function __construct() { }

	public static function register_addon() {

		// register addon via Plugin API
		EE_Register_Addon::register(
			'MyCRED_Payment_Method',
			array(
				'version'              => '1.0',
				'min_core_version'     => '4.6.0',
				'main_file_path'       => EE_MYCRED_PAYMENT_METHOD_PLUGIN_FILE,
				'payment_method_paths' => array( EE_MYCRED_PAYMENT_METHOD_PATH . 'myCRED_Onsite' )
			)
		);
	}

	/**
	 *  additional_admin_hooks
	 *
	 *  @access     public
	 *  @return     void
	 */
	public function additional_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && ! EE_Maintenance_Mode::instance()->level() ) {
			// Adding settings link to the Plugins page
			add_filter( 'plugin_action_links' . EE_MYCRED_PAYMENT_METHOD_BASENAME, array( $this, 'plugin_actions' ) );
		}
	}

	/**
	 * plugin_actions
	 *
	 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public function plugin_actions( $links ) {
		// Before other links, add settings link
		array_unshift( $links, '<a href="admin.php?page=espresso_payments">' . __( 'Settings', 'mycred-toolkit' ) . '</a>' );
		return $links;
	}
}
