<div class="referral-data postbox">
    <h3 class="hndle">
        <?php echo __( 'P2P Campaign', 'give-peer-to-peer' ); ?>
    </h3>
    <div class="inside" style="margin:0;padding:0;">
        <div class="give-admin-box">
            <div class="give-admin-box-inside">

                <!-- CAMPAIGNS -->
                <p>
                    <label class="strong" for="p2pCampaign"><?php _e( 'Campaign', 'give-peer-to-peer' ); ?>:</label>
                    <select class="medium-text" name="p2pCampaign" id="p2pCampaign">
                        <option value=""><?php _e( 'Unassigned', 'give-peer-to-peer' ); ?></option>
                        <?php foreach( $campaigns as $campaign ): ?>
                            <option
                                value="<?php echo $campaign->getId(); ?>"
                                <?php selected( $campaign->getId(), $campaignID ); ?>
                            >
                                <?php echo $campaign->getTitle(); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <!-- TEAMS -->
                <p>
                    <label class="strong" for="p2pTeam"><?php _e( 'Team', 'give-peer-to-peer' ); ?>:</label>
                    <select class="medium-text" name="p2pTeam" id="p2pTeam">
                        <option value=""><?php _e( 'Unassigned', 'give-peer-to-peer' ); ?></option>
                        <?php foreach( $teams as $team ): ?>
                            <option
                                value="<?php echo $team->getId(); ?>"
                                <?php selected( $team->getId(), $teamID ); ?>
                            >
                                <?php echo $team->getName(); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <!-- FUNDRAISERS -->
                <p>
                    <label class="strong" for="p2pFundraiser"><?php _e( 'Fundraiser', 'give-peer-to-peer' ); ?>:</label>
                    <select class="medium-text" name="p2pFundraiser" id="p2pFundraiser">
                        <option value=""><?php _e( 'Unassigned', 'give-peer-to-peer' ); ?></option>
                        <?php foreach( $fundraisers as $fundraiser ): ?>
                            <option
                                value="<?php echo $fundraiser->getId(); ?>"
                                <?php selected( $fundraiser->getId(), $fundraiserID ); ?>
                            >
                                <?php echo get_userdata( $fundraiser->getUserId() )->display_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

            </div>
        </div>
    </div>
</div>

<script>
    jQuery( document ).ready(() => {
        new P2PDonationSourceSelection({
            campaign: '#p2pCampaign',
            team: '#p2pTeam',
            fundraiser: '#p2pFundraiser',
        })
    })
</script>

