<?php
/**
 * Class Give WooCommerce exception.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 *
 * @package    Give_WC_Exception
 * @subpackage Give_WC_Exception
 */

/**
 * Throw custom exceptions.
 *
 * @package    Give_WC_Exception
 * @subpackage Give_WC_Exception
 * @author     GiveWP <https://givewp.com>
 */
class Give_WC_Exception extends Exception {

	/**
	 * Give_WC_Exception constructor.
	 *
	 * @param string $message Exception message.
	 * @param int    $code
	 */
	public function __construct( $message, $code = 0 ) {
		parent::__construct( $message, $code );
	}

	/**
	 * Custom string representation of object
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

	/**
	 * Store the error to give log section.
	 *
	 * @since 1.0.0
	 */
	public function give_wc_log_error() {
		/**
		 * @Todo #1: Create Give log section.
		 * @Todo #2: Store the exception message.
		 */
	}
}