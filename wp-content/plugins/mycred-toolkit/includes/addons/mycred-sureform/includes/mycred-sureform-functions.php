<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


	if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	function is_mycred_hook_page( $page ){

		return ( strpos( $page, 'mycred_sureform' ) !== false && strpos( $page, 'hook' ) !== false );
	}
endif;