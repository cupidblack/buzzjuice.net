import React, {useEffect, useRef} from 'react';

import root from 'react-shadow';
import {__} from '@wordpress/i18n';

import RankingCard from '@p2p/Components/RankingCard';
import {Grid, GridItem} from '@p2p/Components/RankingCardGrid';

import useAsyncFetch from '@p2p/js/blocks/hooks/useAsyncFetch';
import {setShadowRootStyles} from '@p2p/js/utils';

/**
 *
 *
 * @since 1.6.0
 */
const App = ({initialState, href}) => {
    const node = useRef(null);

    useEffect(() => {
        setShadowRootStyles(initialState?.accent_color, initialState?.accent_color, node.current);
    }, [initialState?.accent_color, node.current]);

    const parameters = {
        campaignId: initialState.id,
    };

    const campaign = useAsyncFetch('/get-campaign', parameters);
    const {data}: any = campaign;

    return (
        <root.div ref={node} mode={'open'}>
            <link rel="stylesheet" href={href} />
            <Grid columns={'fullWidth'}>
                <GridItem>
                    <RankingCard
                        columns={'fullWidth'}
                        id={'campaign'}
                        link={`${data?.url}`}
                        viewLabel={__('View Campaign', 'give-peer-to-peer')}
                        showAvatar={initialState.show_avatar}
                        showGoal={initialState.show_goal}
                        name={data?.title}
                        profileImage={data?.campaign_logo}
                        amount={Number(data?.amount)}
                        goal={Number(data?.goal)}
                        showCampaignDetails={initialState.show_campaign_info}
                        showDescription={initialState.show_description}
                        story={data?.story}
                        fundraiserTotal={data?.fundraiser_total}
                        teamTotal={data?.team_total}
                        badge={null}
                        isCampaignBoard={false}
                    />
                </GridItem>
            </Grid>
        </root.div>
    );
};

export default App;
