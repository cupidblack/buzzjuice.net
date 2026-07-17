<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	
	function is_mycred_hook_page( $page ) {
		return ( strpos( $page, 'mycred_weforms' ) !== false && strpos( $page, 'hook' ) !== false );
	}

endif;