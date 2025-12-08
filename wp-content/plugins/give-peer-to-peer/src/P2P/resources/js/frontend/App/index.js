import React from 'react';
import root from 'react-shadow';
import {__, sprintf} from '@wordpress/i18n';
import Notice from '@p2p/Components/Notice';
import {CreateStore, Provider} from './store';
import {stepNavigationReducer} from './reducers';
import Routes from './Routes';
import './styles.scss'; // These are used in the Shadow Root

function PeerToPeerApp({initialState}) {
    const store = CreateStore(
        {
            navigation: stepNavigationReducer,
        },
        initialState
    );

    const [{campaign}] = store;

    return (
        <Provider store={store}>
            <root.div id="give-p2p-host">
                <link rel="stylesheet" href={GiveP2P.shadowRootStylesheet} />
                {!campaign.is_active && (
                    <Notice type="warning">
                        <strong>{__('Notice', 'give-peer-to-peer')}:</strong>{' '}
                        {sprintf(
                            __(
                                'This campaign is set to "%s" and is not visable unless logged in as an administrator.',
                                'give-peer-to-peer'
                            ),
                            campaign.status
                        )}
                    </Notice>
                )}
                <Routes />
            </root.div>
        </Provider>
    );
}

export default PeerToPeerApp;
