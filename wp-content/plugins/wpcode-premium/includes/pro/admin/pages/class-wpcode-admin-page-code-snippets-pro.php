<?php

/**
 * Code snippets pro admin main list page.
 *
 * @package WPCode
 */

/**
 * Class for the Pro code snippets page.
 */
class WPCode_Admin_Page_Code_Snippets_Pro extends WPCode_Admin_Page_Code_Snippets {

	/**
	 * Reorder the buttons and add version-specific info.
	 *
	 * @param array $type_buttons The type buttons.
	 *
	 * @return array
	 */
	protected function prepare_type_buttons( $type_buttons ) {
		foreach ( $type_buttons as $type_button_key => $type_button ) {
			if ( empty( $type_button['type'] ) ) {
				continue;
			}
			$capability = WPCode_Access::capability_for_code_type( $type_button['type'] );
			if ( ! current_user_can( $capability ) ) {
				unset( $type_buttons[ $type_button_key ] );
			}
		}

		return $type_buttons;
	}
}
