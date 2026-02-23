import React, {useEffect, useRef} from 'react';

import {__} from '@wordpress/i18n';

import Leaderboard from '@p2p/Components/Leaderboard';
import useAsyncFetch from '@p2p/js/blocks/hooks/useAsyncFetch';
import root from 'react-shadow';
import {setShadowRootStyles} from '@p2p/js/utils';

const columns = [
    {name: __('Rank', 'give-peer-to-peer'), id: 'rank'},
    {name: __('Fundraiser name', 'give-peer-to-peer'), id: 'name'},
    {name: __('Fundraiser goal', 'give-peer-to-peer'), id: 'goal'},
    {name: __('Action', 'give-peer-to-peer'), id: 'action'},
];

const leaderboard = {
    endpoint: '/get-campaign-fundraisers-search',
    id: 'fundraiser-leaderboard',
    name: __('fundraiser', 'give-peer-to-peer'),
    title: __('Fundraisers', 'give-peer-to-peer'),
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

    const parameters = {
        campaignId: initialState.id,
    };

    const campaign = useAsyncFetch('/get-campaign', parameters);
    const {data}: any = campaign;

    return (
        <root.div ref={node} mode={'open'}>
            <link rel="stylesheet" href={href} />
            <Leaderboard initialState={initialState} campaign={data} leaderboard={leaderboard} />
        </root.div>
    );
};

export default App;
