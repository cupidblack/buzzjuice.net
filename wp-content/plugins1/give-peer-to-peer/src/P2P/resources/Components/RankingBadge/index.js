import PropTypes from 'prop-types';
import {__} from '@wordpress/i18n';

import styles from './style.module.scss';

const ranks = [
    {
        label: __('1st Place', 'give-peer-to-peer'),
        color: '#d4af37',
    },
    {
        label: __('2nd Place', 'give-peer-to-peer'),
        color: '#a3b2b4',
    },
    {
        label: __('3rd Place', 'give-peer-to-peer'),
        color: '#cd7f32',
    },
];

/** 🎵 No time for losers 🎵 */
const hasNoBadge = (rank) => Number.isNaN(rank) || rank < 0 || rank > ranks.length;

/**
 * A ranking badge.
 */
export const RankingBadge = ({rank}) => {
    if (hasNoBadge(rank)) return null;

    const {color, label} = ranks[rank - 1];

    return (
        <span className={styles.rankingbadge}>
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M8.05516 5.33057L6.02986 1.95527C5.94659 1.81642 5.82877 1.70151 5.68789 1.62172C5.54701 1.54194 5.38786 1.5 5.22596 1.5H1.96961C1.59021 1.5 1.36814 1.92686 1.58553 2.2377L4.84539 6.89473C5.71609 6.08115 6.82351 5.52275 8.05516 5.33057ZM16.0304 1.5H12.774C12.4447 1.5 12.1394 1.67285 11.9701 1.95527L9.9448 5.33057C11.1764 5.52275 12.2839 6.08115 13.1546 6.89443L16.4144 2.2377C16.6318 1.92686 16.4097 1.5 16.0304 1.5ZM8.99998 6.1875C6.15232 6.1875 3.84373 8.49609 3.84373 11.3438C3.84373 14.1914 6.15232 16.5 8.99998 16.5C11.8476 16.5 14.1562 14.1914 14.1562 11.3438C14.1562 8.49609 11.8476 6.1875 8.99998 6.1875ZM11.7105 10.7947L10.5993 11.8775L10.8621 13.4074C10.909 13.6816 10.6201 13.8911 10.3743 13.7616L8.99998 13.0395L7.62596 13.7616C7.37986 13.892 7.09129 13.6813 7.13816 13.4074L7.40096 11.8775L6.28973 10.7947C6.08992 10.6002 6.20037 10.2606 6.47576 10.2208L8.0118 9.99697L8.69822 8.60478C8.76004 8.47939 8.87957 8.41758 8.99939 8.41758C9.1198 8.41758 9.24021 8.48027 9.30203 8.60478L9.98846 9.99697L11.5245 10.2208C11.7999 10.2606 11.9103 10.6002 11.7105 10.7947Z"
                    fill={color}
                />
            </svg>
            {label}
        </span>
    );
};

RankingBadge.propTypes = {
    rank: PropTypes.number,
};
