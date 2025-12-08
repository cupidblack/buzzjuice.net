import {Link} from 'react-router-dom';
import cx from 'classnames';

import {useStore} from '@p2p/js/frontend/App/store';
import {Button} from '@p2p/Components';
import CampaignLogo from '@p2p/Components/CampaignLogo';
import ProfileHeader from '@p2p/Components/ProfileHeader';
import Stats from '@p2p/Components/Stats';
import {getAmountInCurrency} from '@p2p/js/utils';
import {ArrowIcon} from '@p2p/Components/Icons';
import styles from './Hero.module.scss';
import logoStyles from '../../../../../css/frontend/logoContainer.module.scss';
import {isCampaignHasTeams} from '@p2p/js/frontend/App/utils';

const {__, _n} = wp.i18n;

/**
 * The Campaign screen’s hero section
 */
export function Hero() {
    const [{campaign, campaignStats}] = useStore();
    const statsItems = [
        {
            name: _n('donation', 'donations', campaignStats.donationsCount, 'give-peer-to-peer'),
            amount: campaignStats.donationsCount,
        },
        {
            name: __('avg. donation', 'give-peer-to-peer'),
            amount: getAmountInCurrency(campaignStats.averageAmount),
        },
        {
            name: _n('fundraiser', 'fundraisers', campaignStats.fundraisersCount, 'give-peer-to-peer'),
            amount: campaignStats.fundraisersCount,
        },
    ];

    if (isCampaignHasTeams(campaignStats)) {
        statsItems.push({
            name: _n('team', 'teams', campaignStats.teamsCount, 'give-peer-to-peer'),
            amount: campaignStats.teamsCount,
        });
    }

    return (
        <div className={styles.hero}>
            <header>
                <div className={logoStyles.logoContainer}>
                    <CampaignLogo />
                    <h1>{campaign.campaign_title}</h1>
                </div>
                <div className={styles.shortDescription} dangerouslySetInnerHTML={{__html: campaign.short_desc}}></div>
                <div className={styles.buttongroup}>
                    <Button as={Link} to={'/register/'} iconAfter={ArrowIcon}>
                        {__('Start Fundraising', 'give-peer-to-peer')}
                    </Button>
                    <Button as={Link} to="/donate/" color={Button.colors.secondary}>
                        {__('Donate Now', 'give-peer-to-peer')}
                    </Button>
                </div>
            </header>
            <div
                className={cx(campaign?.campaign_image ? styles.coverimage : styles.nocoverimage)}
                style={{backgroundImage: `url(${campaign.campaign_image})`}}
            >
                <Stats
                    className={styles.stats}
                    goal={Number(campaign.campaign_goal)}
                    amountRaised={Number(campaignStats.raisedAmount)}
                    amountRaisedPercentage={Number(campaignStats.raisedPercentage)}
                    showProgressDetails={true}
                    items={statsItems}
                />
            </div>
            {campaign.long_desc.trim().length > 0 && (
                <>
                    <ProfileHeader.SubTitle title={__('About this Campaign', 'give-peer-to-peer')} />
                    <ProfileHeader.Story story={campaign.long_desc} />
                </>
            )}
        </div>
    );
}
