<?php
namespace GiveFunds\Listeners;

class DeleteRelationshipOnFormDelete {
	/**
	 * Deletes form relations on donation form delete
	 */
	public function __invoke( $postId ) {
		if ( 'give_forms' !== get_post_type( $postId ) ) {
			return;
		}

		global $wpdb;
		$wpdb->delete( $wpdb->give_fund_form_relationship, [ 'form_id' => $postId ] );
	}
}
