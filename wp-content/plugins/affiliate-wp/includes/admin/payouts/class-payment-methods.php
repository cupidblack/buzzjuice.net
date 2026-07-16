<?php
/**
 * Payment Methods Registry
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Payouts
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment Methods Registry class.
 *
 * @since 2.29.0
 */
class AffiliateWP_Payment_Methods {

	/**
	 * Registered payment methods.
	 *
	 * @since 2.29.0
	 * @var array
	 */
	private static $methods = [];

	/**
	 * Flag to prevent duplicate registrations.
	 *
	 * @since 2.29.0
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Register a payment method.
	 *
	 * @since 2.29.0
	 *
	 * @param string $id   Method ID.
	 * @param array  $args Method arguments.
	 * @return void
	 */
	public static function register( $id, $args ) {
		self::$methods[ $id ] = wp_parse_args(
			$args,
			[
				'id'                => $id,
				'name'              => '',
				'description'       => '',
				'icon'              => '',
				'status'            => 'available',
				'type'              => 'addon',
				'settings_callback' => null,
				'settings_url'      => '',
				'addon_slug'        => '',
				'learn_more'        => '',
				'install_url'       => '',
				'has_new_settings'  => false,
			]
		);

		// Set install URL for addons.
		if ( 'addon' === self::$methods[ $id ]['type'] && ! empty( self::$methods[ $id ]['addon_slug'] ) ) {
			self::$methods[ $id ]['install_url'] = admin_url( 'admin.php?page=affiliate-wp-add-ons' );
		}
	}

	/**
	 * Get all registered payment methods.
	 *
	 * @since 2.29.0
	 *
	 * @return array Payment methods.
	 */
	public static function get_all() {
		/**
		 * Filters the registered payment methods.
		 *
		 * @since 2.29.0
		 *
		 * @param array $methods Payment methods.
		 */
		return apply_filters( 'affwp_payment_methods', self::$methods );
	}

	/**
	 * Get a specific payment method.
	 *
	 * @since 2.29.0
	 *
	 * @param string $id Method ID.
	 * @return array|false Method data or false if not found.
	 */
	public static function get( $id ) {
		return isset( self::$methods[ $id ] ) ? self::$methods[ $id ] : false;
	}

	/**
	 * Check if a payment method is registered.
	 *
	 * @since 2.29.0
	 *
	 * @param string $id Method ID.
	 * @return bool True if registered, false otherwise.
	 */
	public static function exists( $id ) {
		return isset( self::$methods[ $id ] );
	}

	/**
	 * Get payment methods by type.
	 *
	 * @since 2.29.0
	 *
	 * @param string $type Method type (addon, core, legacy).
	 * @return array Filtered methods.
	 */
	public static function get_by_type( $type ) {
		return array_filter(
			self::$methods,
			function ( $method ) use ( $type ) {
				return $method['type'] === $type;
			}
		);
	}

	/**
	 * Get payment methods by status.
	 *
	 * @since 2.29.0
	 *
	 * @param string $status Method status.
	 * @return array Filtered methods.
	 */
	public static function get_by_status( $status ) {
		return array_filter(
			self::$methods,
			function ( $method ) use ( $status ) {
				return $method['status'] === $status;
			}
		);
	}

	/**
	 * Get active payment methods.
	 *
	 * @since 2.29.0
	 *
	 * @return array Active methods.
	 */
	public static function get_active() {
		return self::get_by_status( 'active' );
	}

	/**
	 * Check if any payment method is active.
	 *
	 * @since 2.29.0
	 *
	 * @return bool True if any method is active, false otherwise.
	 */
	public static function has_active() {
		return ! empty( self::get_active() );
	}
}
