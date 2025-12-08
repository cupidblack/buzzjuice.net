// Vendor dependencies
import React from 'react';
import ReactDOM from 'react-dom';

// Internal dependencies
import App from './js/app';
import {getInitialState} from './js/app/utils';

import './css/root.style.scss';

/**
 *
 *
 * @since 1.6.0
 */
const nodeList = document.querySelectorAll('.give-p2p-team-leaderboard-block');

if (nodeList) {
    const containers = Array.from(nodeList);

    containers.map((container: any) => {
        const initialState = getInitialState(container);

        return ReactDOM.render(<App initialState={initialState} href={container.dataset.stylesheet} />, container);
    });
}
