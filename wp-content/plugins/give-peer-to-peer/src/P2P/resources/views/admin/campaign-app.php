<?php
/**
 * @var \GiveP2P\P2P\Models\Campaign $campaign
 * @var array $campaignData
 * @var int $campaignId
 * @var int|null $teamId
 * @var string $screen
 */

?>
<div
    id="give-p2p-campaigns-app"
    data-screen="<?php echo $screen; ?>"
    data-campaign="<?php echo esc_attr(json_encode($campaignData)); ?>"
    data-campaign-id="<?php echo $campaign->getId(); ?>"
    data-team-id="<?php echo $teamId; ?>"
></div>
