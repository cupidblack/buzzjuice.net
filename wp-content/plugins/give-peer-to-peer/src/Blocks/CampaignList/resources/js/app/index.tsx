import React, {useEffect, useRef} from 'react';

import root from 'react-shadow';
import {__} from '@wordpress/i18n';

import Leaderboard from '@p2p/Components/Leaderboard';

import {setShadowRootStyles} from '@p2p/js/utils';

const columns = [
    {name: __('Campaign name', 'give-peer-to-peer'), id: 'name'},
    {name: __('Campaign Goal', 'give-peer-to-peer'), id: 'goal'},
    {name: __('Teams ', 'give-peer-to-peer'), id: 'campaign_team_total'},
    {name: __('Fundraisers ', 'give-peer-to-peer'), id: 'campaign_fundraiser_total'},
    {name: __('Action', 'give-peer-to-peer'), id: 'action'},
];

const leaderboard = {
    endpoint: '/get-campaigns',
    id: 'campaign-list',
    name: __('campaign', 'give-peer-to-peer'),
    title: __('Campaigns', 'give-peer-to-peer'),
    table: {
        columns: columns,
    },
};

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

    return (
        <root.div ref={node} mode={'open'}>
            <link rel="stylesheet" href={href} />
            <Leaderboard initialState={initialState} leaderboard={leaderboard} />
        </root.div>
    );
};

export default App;
