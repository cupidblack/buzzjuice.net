<?php


/**
 * Get the main instance of WPCode.
 *
 * @return WPCode_Premium
 */
function WPCode() {// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return WPCode_Premium::instance();
}
