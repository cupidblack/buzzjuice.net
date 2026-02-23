import ReactDOM from 'react-dom';
import CampaignsApp from './App';
import {setRootStyles} from '@p2p/js/utils';

const container = document.getElementById('give-p2p-campaigns-app');

if (container) {
    const campaign = container.dataset.campaign ? JSON.parse(container.dataset.campaign) : {};

    setRootStyles(campaign?.primary_color, campaign?.secondary_color);

    ReactDOM.render(<CampaignsApp campaign={campaign} screen={container.dataset.screen} />, container);
}
