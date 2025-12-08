import React, {useMemo} from 'react';
import {__, sprintf} from '@wordpress/i18n';
import {createInterpolateElement} from '@wordpress/element';

import {getAmountInCurrency} from '@p2p/js/utils';

import styles from './style.module.scss';

interface ProgressbarProps {
    amount: number;
    goal: number;
    sanitizedDetails?: boolean;
}

/**
 *
 *
 * @since 1.6.0
 */
const Progressbar = ({amount, goal, sanitizedDetails}: ProgressbarProps) => {
    const currentAmount = amount && getAmountInCurrency(amount);
    const currentGoal = goal && getAmountInCurrency(goal);

    const translatedString = useMemo(
        () =>
            createInterpolateElement(
                /* translators: 1: Current amount of goal raised 2: Total goal amount */
                sprintf(__('<strong>%s</strong> <span> raised of %s goal</span>'), currentAmount, currentGoal),
                {
                    strong: <strong className={styles.currentAmount} />,
                    span: <span className={styles.currentGoal} />,
                }
            ),
        [currentAmount, currentGoal]
    );

    return (
        <div className={styles.container}>
            <span>{sanitizedDetails && translatedString}</span>
            <div className={styles.progress}>
                {amount > 0 && (
                    <div
                        className={styles.fill}
                        part={'give-progress-bar'}
                        style={{width: (amount / goal) * 100 + '%'}}
                    />
                )}
            </div>
        </div>
    );
};

export default Progressbar;
