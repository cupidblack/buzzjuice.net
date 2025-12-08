<?php
/**
 * WPCode Usage Tracking Pro
 *
 * @package WPCode
 * @since 2.0.10
 */

/**
 * Class WPCode_Usage_Tracking_Lite
 */
class WPCode_Usage_Tracking_Pro extends WPCode_Usage_Tracking {

	/**
	 * Get the type for the request.
	 *
	 * @return string The plugin type.
	 * @since 2.0.10
	 */
	public function get_type() {
		return 'pro';
	}

	/**
	 * Is the usage tracking enabled?
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return apply_filters( 'wpcode_usage_tracking_is_allowed', true );
	}

	/**
	 * Add pro-specific data to the request.
	 *
	 * @return array
	 */
	public function get_data() {
		$data = parent::get_data();

		$activated = get_option( 'ihaf_activated', array() );

		$data['wpcode_is_pro']       = true;
		$data['wpcode_license_type'] = wpcode()->license->type();
		$data['wpcode_license_key']  = wpcode()->license->get();

		if ( ! empty( $activated['wpcode_pro'] ) ) {
			$data['wpcode_pro_installed_date'] = $activated['wpcode_pro'];
		}

		return $data;
	}
}
