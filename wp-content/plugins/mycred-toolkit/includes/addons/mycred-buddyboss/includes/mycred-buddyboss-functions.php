<?php




/**
 * Check Page
 * 
 * 
 */
if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	function is_mycred_hook_page( $page ) {
		return ( strpos( $page, 'mycred_buddyboss' ) !== false && strpos( $page, 'hook' ) !== false );
	}
endif;
