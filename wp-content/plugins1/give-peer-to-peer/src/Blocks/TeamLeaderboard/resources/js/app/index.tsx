import React, {useEffect, useRef} from 'react';
import Leaderboard from '@p2p/Components/Leaderboard';
import {__} from '@wordpress/i18n';
import useAsyncFetch from '@p2p/js/blocks/hooks/useAsyncFetch';
import root from 'react-shadow';
import {setShadowRootStyles} from '@p2p/js/utils';

const columns = [
    {name: __('Rank', 'give-peer-to-peer'), id: 'rank'},
    {name: __('Team name', 'give-peer-to-peer'), id: 'name'},
    {name: __('Team goal', 'give-peer-to-peer'), id: 'goal'},
    {name: __('Team Captain', 'give-peer-to-peer'), id: 'team_captain'},
    {name: __('Members', 'give-peer-to-peer'), id: 'members'},
    {name: __('Action', 'give-peer-to-peer'), id: 'action'},
];

const leaderboard = {
    endpoint: '/get-campaign-teams-search',
    id: 'team-leaderboard',
    name: __('team', 'give-peer-to-peer'),
    title: __('Teams', 'give-peer-to-peer'),
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
