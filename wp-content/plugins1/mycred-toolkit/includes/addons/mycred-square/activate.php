<?php

function mcs_activate_plugin() {

	global $wpdb;

	$message = array();

			// WordPress check
	$wp_version = $GLOBALS['wp_version'];
	if ( version_compare( $wp_version, '4.0', '<' ) ) {
		$message[] = esc_html(__( 'This myCRED Add-on requires WordPress 4.0 or higher. Version detected:', 'mycred-toolkit' )) . ' ' . $wp_version;
	}

			// PHP check
	$php_version = phpversion();
	if ( version_compare( $php_version, '5.3.3', '<' ) ) {
		$message[] = esc_html(__( 'This myCRED Add-on requires PHP 5.3.3 or higher. Version detected: ', 'mycred-toolkit' )) . ' ' . $php_version;
	}

			// SQL check
	$sql_version = $wpdb->db_version();
	if ( version_compare( $sql_version, '5.0', '<' ) ) {
		$message[] = esc_html(__( 'This myCRED Add-on requires SQL 5.0 or higher. Version detected: ', 'mycred-toolkit' )) . ' ' . $sql_version;
	}

			// myCRED Check
	if ( defined( 'MS_myCRED_VERSION' ) && version_compare( MS_myCRED_VERSION, '1.6', '<' ) ) {
		$message[] = esc_html(__( 'This add-on requires myCRED 1.6 or higher. Version detected:', 'mycred-toolkit' )) . ' ' . MS_myCRED_VERSION;
	}

			// Not empty $message means there are issues
	if ( ! empty( $message ) ) {

		$error_message = implode( "\n", $message );
		die( esc_html(esc_html__( 'Sorry but your WordPress installation does not reach the minimum requirements for running this add-on. The following errors were given:', 'mycred-toolkit' )) . "\n" . esc_html( $error_message) );

	}
}

mcs_activate_plugin();