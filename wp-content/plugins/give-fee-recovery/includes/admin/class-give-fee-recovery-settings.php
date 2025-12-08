<?php
/**
 * Fee Recovery Settings Page/Tab
 *
 * @package    Give_Fee_Recovery
 * @subpackage Give_Fee_Recovery/admin
 * @author     GiveWP <https://givewp.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Give_Fee_Recovery_Settings' ) ) :

	/**
	 * Give_Fee_Recovery_Settings.
	 *
	 * @sine 1.0.0
	 */
	class Give_Fee_Recovery_Settings extends Give_Settings_Page {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->id    = 'givefeerecovery';
			$this->label = __( 'Fee Recovery', 'give-fee-recovery' );

			parent::__construct();
		}

		/**
		 * Get settings array.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return array
		 */
		public function get_settings() {

			$is_global = true; // Set Global flag.

			$settings = give_fee_settings( $is_global );

			/**
			 * Filter the Fee Recovery settings.
			 *
			 * @since  1.0.0
			 *
			 * @param  array $settings
			 */
			$settings = apply_filters( 'give_fee_get_settings_' . $this->id, $settings );

			// Output.
			return $settings;
		}

	}

endif;

return new Give_Fee_Recovery_Settings();
