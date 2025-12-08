import ReactDOM from 'react-dom';
import App from './App';
import {getInitialState} from './App/utils';
import {setRootStyles} from '@p2p/js/utils';

const container = document.getElementById('p2p-app');

if (container) {
    const initialState = getInitialState(container);

    setRootStyles(initialState.campaign.primary_color, initialState.campaign.secondary_color);
    ReactDOM.render(<App initialState={initialState} />, container);
}
