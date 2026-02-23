import {Link} from 'react-router-dom';
import {Button} from '../Button';
import {ArrowIcon} from '@p2p/Components/Icons';
import PropTypes from 'prop-types';
import PlaceholderAvatar from '../PlaceholderAvatar';

import styles from './style.module.scss';
import ProgressBar from '../ProgressBar';
import ProgressBarContainer from '../ProgressBarContainer';
import {getAmountInCurrency} from '@p2p/js/utils';

const {__, sprintf} = wp.i18n;

const SupportCard = ({title, avatar, campaign, amount, goal, amountRaisedPercentage, SecondaryButton}) => {
    return (
        <div className={styles.supportcard}>
            <img className={styles.avatar} src={avatar || PlaceholderAvatar} alt="" />
            <div className={styles.main}>
                <h1 className={styles.title}>{title}</h1>
                <div className={styles.fundraiserpill}>
                    <InfoIcon />
                    <span>
                        {__('Fundraising in support of the', 'give-peer-to-peer')}
                        <a href={campaign.campaign_url} className={styles.campaignlink}>
                            {campaign.campaign_title}
                        </a>
                    </span>
                </div>
                <div className={styles.progressDetails}>
                    <div>
                        <span className={styles.raised}>{getAmountInCurrency(amount)}</span>{' '}
                        {__('raised', 'give-peer-to-peer')}
                    </div>
                    <div className={styles.goal}>
                        {amountRaisedPercentage
                            ? sprintf(__(' %d%% of', 'give-peer-to-peer'), amountRaisedPercentage)
                            : ''}
                        &nbsp;
                        {getAmountInCurrency(goal)} {__('goal', 'give-peer-to-peer')}
                    </div>
                </div>
                <div className={styles.progressbar}>
                    <ProgressBarContainer size={15}>
                        <ProgressBar goal={Number(goal)} amount={Number(amount)} />
                    </ProgressBarContainer>
                </div>
            </div>
            <nav className={styles.nav}>
                <Button as={Link} to="donate" iconAfter={ArrowIcon}>
                    {__('Donate Now', 'give-peer-to-peer')}
                </Button>
                {!!SecondaryButton && SecondaryButton}
                {!SecondaryButton && (
                    <Button color={Button.colors.secondary} as="a" href={`${campaign.campaign_url}/register/`}>
                        {__('Start Fundraising', 'give-peer-to-peer')}
                    </Button>
                )}
            </nav>
        </div>
    );
};
SupportCard.propTypes = {
    title: PropTypes.string,
    avatar: PropTypes.string,
    campaign: PropTypes.object,
};

const InfoIcon = () => {
    // SVG height should match the line-height of the fundraiser pill text.
    return (
        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M10.0003 1.6665C5.39828 1.6665 1.66699 5.39913 1.66699 9.99984C1.66699 14.6032 5.39828 18.3332 10.0003 18.3332C14.6024 18.3332 18.3337 14.6032 18.3337 9.99984C18.3337 5.39913 14.6024 1.6665 10.0003 1.6665ZM10.0003 5.36274C10.7798 5.36274 11.4116 5.9946 11.4116 6.77403C11.4116 7.55347 10.7798 8.18532 10.0003 8.18532C9.22089 8.18532 8.58904 7.55347 8.58904 6.77403C8.58904 5.9946 9.22089 5.36274 10.0003 5.36274ZM11.882 13.8977C11.882 14.1204 11.7015 14.3009 11.4788 14.3009H8.52183C8.29915 14.3009 8.11861 14.1204 8.11861 13.8977V13.0912C8.11861 12.8686 8.29915 12.688 8.52183 12.688H8.92506V10.5375H8.52183C8.29915 10.5375 8.11861 10.3569 8.11861 10.1342V9.32779C8.11861 9.10511 8.29915 8.92457 8.52183 8.92457H10.6724C10.8951 8.92457 11.0756 9.10511 11.0756 9.32779V12.688H11.4788C11.7015 12.688 11.882 12.8686 11.882 13.0912V13.8977Z"
                fill="#1E8CBE"
            />
        </svg>
    );
};

export {SupportCard};
