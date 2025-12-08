<?php defined( 'ABSPATH' ) or exit; ?>
<tr class="give-export-donations-p2p-fields">
	<td class="row-title">
		<label><?php esc_html_e( 'P2P Data:', 'give-peer-to-peer' ); ?></label>
	</td>
	<td class="give-field-wrap">
		<div class="give-clearfix">
			<ul class="give-export-option">

				<!-- Campaign Fields -->

				<li class="give-export-option-fields give-export-option-form-fields">
					<ul class="give-export-option-ul give-export-option-fields">

						<li class="give-export-option-label give-export-option-Form-label">
								<span>
									<?php _e( 'Campaign Fields', 'give' ); ?>
								</span>
						</li>

						<li class="give-export-option-start">
							<label for="give-export-p2p-campaign-id">
								<input type="checkbox" checked
									   name="give_give_donations_export_option[p2p_campaign_id]"
									   id="give-export-donation-p2p-campaign-id"><?php _e( 'Campaign ID', 'give' ); ?>
							</label>
						</li>

						<li class="give-export-option-start">
							<label for="give-export-p2p-campaign">
								<input type="checkbox" checked
									   name="give_give_donations_export_option[p2p_campaign]"
									   id="give-export-donation-p2p-campaign"><?php _e( 'Campaign Title', 'give' ); ?>
							</label>
						</li>
					</ul>
				</li>

				<!-- Team Fields -->
				<li class="give-export-option-fields give-export-option-form-fields">
					<ul class="give-export-option-ul give-export-option-fields">

						<li class="give-export-option-label give-export-option-Form-label">
								<span>
									<?php _e( 'Team Fields', 'give' ); ?>
								</span>
						</li>

						<li class="give-export-option-start">
							<label for="give-export-p2p-team-id">
								<input type="checkbox" checked
									   name="give_give_donations_export_option[p2p_team_id]"
									   id="give-export-donation-p2p-team-id"><?php _e( 'Team ID', 'give' ); ?>
							</label>
						</li>

						<li class="give-export-option-start">
							<label for="give-export-p2p-team">
								<input type="checkbox" checked
									   name="give_give_donations_export_option[p2p_team]"
									   id="give-export-donation-p2p-team"><?php _e( 'Team Name', 'give' ); ?>
							</label>
						</li>
					</ul>
				</li>

				<!-- Fundraiser Fields -->
				<li class="give-export-option-fields give-export-option-form-fields">
					<ul class="give-export-option-ul give-export-option-fields">

						<li class="give-export-option-label give-export-option-Form-label">
								<span>
									<?php _e( 'Fundraiser Fields', 'give' ); ?>
								</span>
						</li>

						<li class="give-export-option-start">
							<label for="give-export-p2p-fundraiser-id">
								<input type="checkbox" checked
									   name="give_give_donations_export_option[p2p_fundraiser_id]"
									   id="give-export-donation-p2p-fundraiser-id"><?php _e( 'Fundraiser ID', 'give' ); ?>
							</label>
						</li>

						<li class="give-export-option-start">
							<label for="give-export-p2p-fundraiser-user-id">
								<input type="checkbox" checked
									   name="give_give_donations_export_option[p2p_fundraiser_user_id]"
									   id="give-export-donation-p2p-fundraiser-user-id"><?php _e( 'Fundraiser User ID', 'give' ); ?>
							</label>
						</li>

						<li class="give-export-option-start">
							<label for="give-export-p2p-fundraiser">
								<input type="checkbox" checked
									   name="give_give_donations_export_option[p2p_fundraiser]"
									   id="give-export-donation-p2p-fundraiser"><?php _e( 'Fundraiser Name', 'give' ); ?>
							</label>
						</li>
					</ul>
				</li>

			</ul>
		</div>
	</td>
</tr>
