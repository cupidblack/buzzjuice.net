<?php


if ( ! function_exists( 'dzsvg_handle_activated_plugin' ) ) {
	function dzsvg_handle_activated_plugin( $plugin = '' ) {
		$isRedirect = false;
		if ( $plugin == plugin_basename( __FILE__ ) ) {
			if ( ! get_option( 'dzsvg_shown_intro' ) ) {
				$isRedirect = true;
			}
		}
		if ( defined( 'DZSVG_PREVIEW' ) && DZSVG_PREVIEW == 'YES' ) {
			$isRedirect = true;
		}


		if ( $isRedirect ) {

			exit( wp_redirect( admin_url( 'admin.php?page=dzsvg-about' ) ) );
		}
	}
}