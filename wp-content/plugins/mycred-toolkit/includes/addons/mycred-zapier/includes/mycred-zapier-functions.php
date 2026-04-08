<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}


if ( ! function_exists( 'mycred_zapier_insert_log' ) ) :
	function mycred_zapier_insert_log( $user_id, $ref, $ref_id = 0, $data = '' ) {

		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mycred_zapier';

		$data = ( is_array( $data ) || is_object( $data ) ) ? serialize( $data ) : $data;

		$wpdb->insert( 
			$table_name, 
			array( 
				'user_id'      => $user_id,
				'ref'          => $ref,
				'ref_id'       => $ref_id,
				'created_time' => time(),
				'data'         => $data
			) 
		);

		return $wpdb->insert_id;
	}
endif;
