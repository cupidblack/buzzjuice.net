<?php
/**
 * Notifications for the Pro version.
 *
 * @package WPCode
 */

/**
 * Notifications.
 */
class WPCode_Notifications_Pro extends WPCode_Notifications {

	/**
	 * Get the license type for the current plugin.
	 *
	 * @return string
	 */
	public function get_license_type() {
		if ( isset( wpcode()->license ) ) {
			return wpcode()->license->type();
		} else {
			return '';
		}
	}
}
