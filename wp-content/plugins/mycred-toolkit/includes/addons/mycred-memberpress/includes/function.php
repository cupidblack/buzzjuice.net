<?php 
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Check Page
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	function is_mycred_hook_page( $page ) {
		return ( strpos( $page, 'mycred' ) !== false && strpos( $page, 'hook' ) !== false );
	}
endif;


if ( ! function_exists( 'custom_limit' ) ) :
	function custom_limit() {
		return array(
			'x' => __('No limit', 'mycred-toolkit'),
			'd' => __('/ Day', 'mycred-toolkit'),
			'w' => __('/ Week', 'mycred-toolkit'),
			'm' => __('/ Month', 'mycred-toolkit'),
		);
	} 
endif;
