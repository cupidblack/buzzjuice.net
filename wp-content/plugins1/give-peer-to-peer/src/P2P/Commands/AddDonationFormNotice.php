<?php

namespace GiveP2P\P2P\Commands;

use Give\Framework\Database\DB;
use WP_Post;

/**
 * @since 1.0.0
 */
class AddDonationFormNotice {

	/**
	 * @since 1.0.0
	 */
	public function __invoke( WP_Post $post ) {

		if( 'give_forms' != $post->post_type ) {
			return;
		}

		global $wpdb;

		$query = DB::prepare("SELECT * FROM $wpdb->give_campaigns WHERE form_id = %d", $post->ID);

		$campaign = DB::get_row( $query );

		if( $campaign ) {
			$link = admin_url('edit.php?post_type=give_forms&page=p2p-edit-campaign&id=' . $campaign->id);
			echo "<div class='notice notice-warning' style='padding: 15px;'>";
			echo __('This donation form is attached to a Peer-to-Peer campaign', 'give-peer-to-peer' );
			echo ": <a href='$link'>{$campaign->campaign_title}</a>";
			echo "</div>";
		}
	}
}
