<?php
/**
 * Give - Currency Switcher Settings Page/Tab
 *
 * @package    Give_Currency_Switcher
 * @subpackage Give_Currency_Switcher/includes/admin
 * @author     GiveWP <https://givewp.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Give_Currency_Switcher_Settings' ) ) :

	/**
	 * Give_Currency_Switcher_Settings.
	 *
	 * @sine 1.0.0
	 */
	class Give_Currency_Switcher_Settings extends Give_Settings_Page {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->id    = 'currency-switcher';
			$this->label = __( 'Currency Switcher', 'give-currency-switcher' );

			// Set default setting section.
			$this->default_tab = 'general-settings';

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

			$settings = cs_get_setting_fields( $is_global );

			/**
			 * Filter the Give - Currency Switcher settings.
			 *
			 * @since  1.0.0
			 *
			 * @param  array $settings
			 */
			$settings = apply_filters( 'give_currency_switcher_get_settings_' . $this->id, $settings );

			// Output.
			return $settings;
		}

		/**
		 * Define currency switcher setting section array.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_sections() {

			$section_tab = Give_Currency_Switcher::$section_tab;

			// Set the currency switcher tab sections.
			return apply_filters( 'give_get_sections_' . $this->id, $section_tab );
		}
	}

endif;

return new Give_Currency_Switcher_Settings();
