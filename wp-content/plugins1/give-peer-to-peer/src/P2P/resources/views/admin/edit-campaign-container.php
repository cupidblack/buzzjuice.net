<?php

use GiveP2P\P2P\Admin\EditCampaign;

/**
 * @var string $content
 * @var string $currentTab
 * @var GiveP2P\P2P\Models\Campaign $campaign
 */

$settingPageUrl = admin_url(
    sprintf(
        'edit.php?post_type=give_forms&page=%1$s&id=%2$s',
        EditCampaign::PAGE_SLUG,
        $campaign->getId()
    )
);

$teamsPageUrl = admin_url(
    sprintf(
        'edit.php?post_type=give_forms&page=%1$s&id=%2$s&tab=teams',
        EditCampaign::PAGE_SLUG,
        $campaign->getId()
    )
);

$fundraiserPageUrl = admin_url(
    sprintf(
        'edit.php?post_type=give_forms&page=%1$s&id=%2$s&tab=fundraisers',
        EditCampaign::PAGE_SLUG,
        $campaign->getId()
    )
);

$campaignPageUrl =
    sprintf(
        '/campaign/%1$s',
        $campaign->getUrl()
    );

?>
<div id="poststuff" class="wrap give-settings-page give-p2p-campaign">
    <div class="give-settings-header">
        <h1 class="wp-heading-inline">
            <?php
            esc_html_e('Edit Campaign', 'give-peer-to-peer'); ?>
            <?php
            if (in_array($currentTab, ['teams', 'fundraisers'])) : ?>
                <span class="give-settings-heading-sep dashicons dashicons-arrow-right-alt2"></span>
                <?php
                echo 'teams' === $currentTab ?
                    esc_html__('Teams', 'give-peer-to-peer') :
                    esc_html__('Fundraisers', 'give-peer-to-peer');
                ?>
            <?php
            endif; ?>
        </h1>
        <a
            href="<?php
            echo $campaignPageUrl; ?>"
            target="_blank"
        >
           <span>
               <?php
               esc_html_e(
                   'View Campaign',
                   'give-peer-to-peer'
               ); ?>

               <?php
               echo " <svg height='13.33' width='13.33' viewBox='0 0 16 16' fill='#69B868' xmlns='http://www.w3.org/2000/svg'>
                            <path
                                    d='M13.1562 9.875H12.2188C12.0944 9.875 11.9752 9.92439 11.8873 10.0123C11.7994 10.1002 11.75 10.2194 11.75 10.3438V13.625H2.375V4.25H6.59375C6.71807 4.25 6.8373 4.20061 6.92521 4.11271C7.01311 4.0248 7.0625 3.90557 7.0625 3.78125V2.84375C7.0625 2.71943 7.01311 2.6002 6.92521 2.51229C6.8373 2.42439 6.71807 2.375 6.59375 2.375H1.90625C1.53329 2.375 1.1756 2.52316 0.911881 2.78688C0.648158 3.0506 0.5 3.40829 0.5 3.78125L0.5 14.0938C0.5 14.4667 0.648158 14.8244 0.911881 15.0881C1.1756 15.3518 1.53329 15.5 1.90625 15.5H12.2188C12.5917 15.5 12.9494 15.3518 13.2131 15.0881C13.4768 14.8244 13.625 14.4667 13.625 14.0938V10.3438C13.625 10.2194 13.5756 10.1002 13.4877 10.0123C13.3998 9.92439 13.2806 9.875 13.1562 9.875ZM14.7969 0.5H11.0469C10.4208 0.5 10.1079 1.25908 10.5488 1.70117L11.5956 2.74795L4.45508 9.88584C4.38953 9.95116 4.33752 10.0288 4.30203 10.1142C4.26654 10.1997 4.24827 10.2913 4.24827 10.3839C4.24827 10.4764 4.26654 10.5681 4.30203 10.6535C4.33752 10.739 4.38953 10.8166 4.45508 10.8819L5.11924 11.5449C5.18456 11.6105 5.26218 11.6625 5.34765 11.698C5.43311 11.7335 5.52474 11.7517 5.61729 11.7517C5.70983 11.7517 5.80146 11.7335 5.88692 11.698C5.97239 11.6625 6.05001 11.6105 6.11533 11.5449L13.2523 4.40586L14.2988 5.45117C14.7383 5.89062 15.5 5.58301 15.5 4.95312V1.20312C15.5 1.01664 15.4259 0.837802 15.2941 0.705941C15.1622 0.574079 14.9834 0.5 14.7969 0.5V0.5Z'
                                    fill='#69B868'
                            />
                       </svg>" ?>
           </span>
        </a>
    </div>
    <div class="wp-header-end"></div>

    <?php
    if (in_array($currentTab, ['teams', 'fundraisers'])): ?>
        <p class="give-p2p-teams-fundraiser-title"><?php
            echo $campaign->getTitle(); ?></p>
    <?php
    endif; ?>

    <div class="give-p2p-menu-container">
        <ul class="give-p2p-top-menu">
            <li class="<?php
            echo ! $currentTab ? 'give-p2p-active' : ''; ?>">
                <a href="<?php
                echo $settingPageUrl; ?>">
                    <?php
                    esc_html_e('Settings', 'give-peer-to-peer'); ?>
                </a>
            </li>

            <li class="<?php
            echo 'teams' === $currentTab ? 'give-p2p-active' : ''; ?>">
                <a href="<?php
                echo $teamsPageUrl; ?>">
                    <?php
                    esc_html_e('Teams', 'give-peer-to-peer'); ?>
                </a>
            </li>

            <li class="<?php
            echo 'fundraisers' === $currentTab ? 'give-p2p-active' : ''; ?>">
                <a href="<?php
                echo $fundraiserPageUrl ?>">
                    <?php
                    esc_html_e('Fundraisers', 'give-peer-to-peer'); ?>
                </a>
            </li>
        </ul>
    </div>

    <?php
    echo $content; ?>

</div>

