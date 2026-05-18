<?php
if ( ! defined( 'MYCRED_buddyboss_SLUG' ) ) exit;

/**
 * Check Page
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	function is_mycred_hook_page( $page ){
		return ( strpos( $page, 'mycred_buddyboss' ) !== false && strpos( $page, 'hook' ) !== false );
	}
endif;











