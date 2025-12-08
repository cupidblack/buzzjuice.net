import PropTypes from 'prop-types';
import cx from 'classnames';
import ProgressBar from '@p2p/Components/ProgressBar';
import ProgressBarContainer from '@p2p/Components/ProgressBarContainer';
import {getAmountInCurrency} from '@p2p/js/utils';

import styles from './styles.module.scss';

const {__, sprintf} = wp.i18n;

const Stats = ({className, items, goal, amountRaised, amountRaisedPercentage, showProgressDetails}) => (
    <div className={cx(styles.stats, className)} role="group" aria-label={__('Stats', 'give-peer-to-peer')}>
        <ul className={styles.grid}>
            {items.map((item) => (
                <li key={item.name} className={styles.item}>
                    <span className={styles.amount}>{item.amount}</span> <span>{item.name}</span>
                </li>
            ))}
            {showProgressDetails && (
                <li className={styles.progress}>
                    <div className={styles.totalRaised}>
                        <span className={styles.totalRaisedAmount}>{getAmountInCurrency(amountRaised)}</span>{' '}
                        <span>{__('raised', 'give-peer-to-peer')}</span>
                    </div>
                    <div className={styles.percentageOfGoal}>
                        {amountRaised > 0 &&
                            sprintf(
                                __('%d%% of', 'give-peer-to-peer'),
                                amountRaisedPercentage < 1 ? 1 : amountRaisedPercentage
                            )}{' '}
                        {getAmountInCurrency(goal)} {__('goal', 'give-peer-to-peer')}
                    </div>
                    <ProgressBarContainer className={styles.progressBar} size={15}>
                        <ProgressBar goal={goal} amount={amountRaised} />
                    </ProgressBarContainer>
                </li>
            )}
        </ul>
    </div>
);

Stats.propTypes = {
    className: PropTypes.string,
    items: PropTypes.arrayOf(
        PropTypes.shape({
            name: PropTypes.string.isRequired,
            amount: PropTypes.any.isRequired,
        })
    ),
    goal: PropTypes.number,
    amountRaised: PropTypes.number,
    amountRaisedPercentage: PropTypes.number,
    showProgressDetails: PropTypes.bool,
};

Stats.defaultProps = {
    showProgressDetails: false,
};

export default Stats;
