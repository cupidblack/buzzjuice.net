import {useState} from 'react';
import classNames from 'classnames';
import {useStore} from '@p2p/js/frontend/App/store';
import {Page} from '@p2p/Components';
import DonateFormContainer from '@p2p/Components/DonateFormContainer';
import {SponsorGrid} from '@p2p/Components/SponsorGrid';
import Spinner from '@p2p/Components/Admin/Spinner';
import {iframeContainerMutationObserver} from '../../utils';

import styles from './styles.module.scss';

const {__, sprintf} = wp.i18n;

const DonateCampaign = () => {
    const [{campaign}] = useStore();
    const [isVisible, setIsVisible] = useState(false);

    return (
        <Page title={sprintf(__('Make a donation to %s', 'give-peer-to-peer'), campaign.campaign_title)}>
            <DonateFormContainer
                title={[__('Make a donation to', 'give-peer-to-peer'), <br />, campaign.campaign_title]}
                backgroundImage={campaign.campaign_image}
                logoImage={campaign.campaign_logo}
                isCampaign={true}
            >
                {!isVisible && <Spinner size="large" />}

                <iframe
                    className={classNames(styles.iframe, {[styles.visible]: isVisible})}
                    src={campaign.iframe_url}
                    frameBorder="0"
                    scrolling="no"
                    onLoad={(element) => {
                        setIsVisible(true);
                        iframeContainerMutationObserver(element);
                    }}
                />
            </DonateFormContainer>
            {'enabled' === campaign.sponsors_enabled && <SponsorGrid />}
        </Page>
    );
};

export default DonateCampaign;
