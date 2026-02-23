import React from 'react';
import {getEndpoint, useFetcher} from '@p2p/js/api';
import {useStore} from '@p2p/js/frontend/App/store';
import {Page} from '@p2p/Components';
import {Tabs} from '@p2p/Components/Tabs';
import {ListTable} from '@p2p/Components/ListTable';
import {SponsorGrid} from '@p2p/Components/SponsorGrid';
import {DonorsTabContent} from './DonorsTabContent';
import {Hero} from './Hero';

import {isCampaignHasTeams} from '../../utils';
import Leaderboard from '@p2p/Components/Leaderboard';

const {__, sprintf} = wp.i18n;

const Campaign = () => {
    const [{donations, campaign, campaignStats}] = useStore();

    const {
        data: donors,
        isLoading: donorsLoading,
        isError: donorsError,
    } = useFetcher(
        getEndpoint('/get-campaign-top-donors', {
            campaign_id: campaign.campaign_id,
        })
    );

    return (
        <Page title={campaign.campaign_title}>
            <Hero />
            <Tabs
                tabs={[
                    {
                        label: __('Recent Donations', 'give-peer-to-peer'),
                        content: (
                            <ListTable
                                donations={donations}
                                emptyTitle={sprintf(
                                    __('Be the first to support %s!', 'give-peer-to-peer'),
                                    campaign.campaign_title
                                )}
                            />
                        ),
                    },
                    {
                        label: __('Top Donors', 'give-peer-to-peer'),
                        content: <DonorsTabContent donors={donors} error={donorsError} loading={donorsLoading} />,
                    },
                ]}
            />
            {isCampaignHasTeams(campaignStats) && (
                <Leaderboard
                    initialState={{
                        id: campaign.campaign_id,
                        per_page: 3,
                        show_pagination: true,
                        show_avatar: true,
                        show_goal: true,
                        show_team_info: true,
                        columns: 'triple',
                        layout: 'grid',
                    }}
                    leaderboard={{
                        endpoint: '/get-campaign-teams-search',
                        name: __('team', 'give-peer-to-peer'),
                        title: __('Teams', 'peer-to-peer'),
                    }}
                    campaign={{url: campaign.campaign_url, page: true}}
                />
            )}

            {!isCampaignHasTeams(campaignStats) && campaign.teams_registration && (
                <Leaderboard
                    initialState={{
                        id: campaign.campaign_id,
                        per_page: 3,
                        campaign_url: campaign.campaign_url,
                        show_pagination: true,
                        show_avatar: true,
                        show_goal: true,
                        show_team_info: true,
                        columns: 'triple',
                        layout: 'grid',
                    }}
                    leaderboard={{
                        endpoint: '/get-campaign-teams-search',
                        name: __('team', 'peer-to-peer'),
                        title: __('Teams', 'peer-to-peer'),
                    }}
                    campaign={{url: campaign.campaign_url, page: true}}
                />
            )}

            <Leaderboard
                initialState={{
                    id: campaign.campaign_id,
                    per_page: 3,
                    show_pagination: true,
                    show_avatar: true,
                    show_goal: true,
                    show_description: true,
                    columns: 'triple',
                    layout: 'grid',
                }}
                leaderboard={{
                    endpoint: '/get-campaign-fundraisers-search',
                    name: __('fundraiser', 'peer-to-peer'),
                    title: __('Fundraisers', 'peer-to-peer'),
                }}
                campaign={{url: campaign.campaign_url, page: true}}
            />
            {'enabled' === campaign.sponsors_enabled && <SponsorGrid />}
        </Page>
    );
};

export default Campaign;
