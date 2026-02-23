<?php

/**
 * @var $campaignsUrl string
 */

?>
<div style="text-align: center;">
    <p>
        <?php
        echo __(
            'User accounts associated with a Peer-to-Peer Campaign Fundraiser cannot be deleted. To delete a user associated with a Fundraiser, first delete the fundraiser from the Peer-to-Peer Campaign.',
            'give-peer-to-peer'
        ); ?>
    </p>
    <a href="<?php
    echo $campaignsUrl;
    ?>">
        <?php
        echo __('P2P Campaigns', 'give-peer-to-peer'); ?>
    </a>
</div>
