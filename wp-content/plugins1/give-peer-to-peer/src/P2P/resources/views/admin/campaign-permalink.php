<?php
/**
 * @var GiveP2P\P2P\Models\Campaign $campaign
 */
?>
<div class="give-p2p-permalink-box">
	<strong><?php esc_html_e( 'Permalink', 'give-peer-to-peer' ); ?>:</strong>
	<span class="give-p2p-preview-campaign-url">
		 <a
			 href="<?php echo home_url( 'campaign/' . $campaign->getUrl() ); ?>/"
			 target="_blank" id="give-p2p-campaign-preview-url">
			 <?php echo home_url( 'campaign' ); ?>/<strong id="give-p2p-campaign-url"><?php echo $campaign->getUrl(); ?></strong>/</a>

		<button type="button" class="button button-small give-p2p-edit-campaign-slug-btn">
			<?php esc_html_e( 'Edit', 'give-peer-to-peer' ); ?>
		</button>
	 </span>
	<span class="give-p2p-edit-campaign-url">
		<?php echo home_url( 'campaign' ); ?>/<input type="text" id="give-p2p-campaign-slug" name="campaign_url" value="<?php echo $campaign->getUrl(); ?>"/>/
		<button type="button" class="button button-small give-p2p-save-campaign-slug-btn" data-campaign="<?php echo $campaign->getId(); ?>">
			<?php esc_html_e( 'OK', 'give-peer-to-peer' ); ?>
		</button>
		<a href="#" class="give-p2p-cancel-campaign-url-edit">
			<?php esc_html_e( 'Cancel', 'give-peer-to-peer' ); ?>
		</a>
	</span>
</div>
