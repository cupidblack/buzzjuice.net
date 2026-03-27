<?php
/**
 * Shortcodes Handler
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//remove widgetopts_pages transient when new page created
if( !function_exists( 'widgetopts_delete_transient_pages' ) ){
	add_action( 'transition_post_status', 'widgetopts_delete_transient_pages', 10, 3 );
	function widgetopts_delete_transient_pages( $new_status, $old_status, $post  ){
		global $widgetopts_types;

		if ( 'page' == $post->post_type ){
	        delete_option( 'widgetopts_global_all_pages' );
	    }

		if( 'publish' == $new_status && 'publish' != $old_status ){
			if( is_array( $widgetopts_types ) && !empty( $widgetopts_types ) && !in_array( $post->post_type, $widgetopts_types ) ){
				delete_option( 'widgetopts_global_types' );
			}
		}
	}
}

if( !function_exists( 'widgetopts_delete_transient_on_insert' ) ){
	add_filter( 'wp_insert_post_data', 'widgetopts_delete_transient_on_insert', '99', 2 );
	function widgetopts_delete_transient_on_insert( $data , $postarr ){
		if( isset( $data[ 'post_type' ] ) && $data[ 'post_type' ] == 'page' ){
			delete_option( 'widgetopts_global_all_pages' );
		}
		return $data;
	}
}

//remove widgetopts_categories transient when new category created
if( !function_exists( 'widgetopts_delete_transient_terms' ) ){
	add_action( 'create_term', 'widgetopts_delete_transient_terms', 10, 3 );
	add_action( 'edit_term', 'widgetopts_delete_transient_terms', 10, 3 );
	add_action( 'delete_term', 'widgetopts_delete_transient_terms', 10, 3 );
	function widgetopts_delete_transient_terms( $term_id, $tt_id, $taxonomy ){
		global $widgetopts_taxonomies;

		delete_transient( 'widgetopts_taxonomy_' . $taxonomy );
		delete_transient( 'widgetopts_taxonomy_' . str_replace( '-', '__', $taxonomy ) );

		if( is_array( $widgetopts_taxonomies ) && !empty( $widgetopts_taxonomies ) && !in_array( $taxonomy, $widgetopts_taxonomies ) ){
			delete_option( 'widgetopts_global_taxonomies' );
		}
	}
}
?>
