<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


	if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	function is_mycred_hook_page( $page = '' ){

		// If page parameter is provided, check it
		if ( ! empty( $page ) ) {
			return ( strpos( $page, 'mycred_fluentcart' ) !== false && strpos( $page, 'hook' ) !== false );
		}

		// Otherwise check current screen/page
		if ( ! is_admin() ) {
			return false;
		}

		// Check if we're on the hooks page
		$screen = get_current_screen();
		if ( $screen && isset( $screen->id ) && strpos( $screen->id, 'mycred' ) !== false && strpos( $screen->id, 'hook' ) !== false ) {
			return true;
		}

		// Check GET parameter for hooks page
		if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'mycred' ) !== false && strpos( $_GET['page'], 'hook' ) !== false ) {
			return true;
		}

		return false;
	}
endif;
