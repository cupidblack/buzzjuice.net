<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'MYCRED_ninjaforms_SLUG' ) ) exit;


	if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	function is_mycred_hook_page( $page ){

		return ( strpos( $page, 'mycred_ninjaforms_integration' ) !== false && strpos( $page, 'hook' ) !== false );
	}
endif;