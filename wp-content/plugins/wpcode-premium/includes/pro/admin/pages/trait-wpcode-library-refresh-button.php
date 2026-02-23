<?php
/**
 * Trait for the library refresh button (pro version).
 *
 * @package WPCode
 */

/**
 * Trait WPCode_Library_Refresh_Button.
 */
trait WPCode_Library_Refresh_Button {

	/**
	 * Add the "Check for Updates" button to the header.
	 * Pro version - includes update check logic with tooltip and data attributes.
	 *
	 * @return void
	 */
	public function add_check_for_updates_button() {
		// Check if the button was clicked recently (within the last minute).
		$last_update    = get_transient( 'wpcode_library_cache_last_update' );
		$tooltip_text   = esc_html__( 'Check for updates to your private library snippets.', 'wpcode-premium' );
		$data_attribute = '';
		if ( false !== $last_update ) {
			$time_diff = time() - $last_update;
			if ( $time_diff < 60 ) { // Less than 1 minute.
				$data_attribute = ' data-recently-updated=1';
			}
		}
		echo '<span class="wpcode-help-tooltip"><button type="button" id="wpcode-check-for-updates" class="wpcode-button-just-icon"' . esc_attr( $data_attribute ) . '>' . wp_kses( get_wpcode_icon( 'sync', 16, 16, '0 0 20 21' ), wpcode_get_icon_allowed_tags() ) . '</button>';
		echo '<span class="wpcode-help-tooltip-text wpcode-help-tooltip-text-down wpcode-check-updates-tooltip">' . esc_html( $tooltip_text ) . '</span></span>';
	}
}
