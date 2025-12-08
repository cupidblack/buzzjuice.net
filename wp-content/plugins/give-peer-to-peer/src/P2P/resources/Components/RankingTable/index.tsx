import React from 'react';

import {__} from '@wordpress/i18n';
import cx from 'classnames';

import styles from './style.module.scss';
import {Button} from '@p2p/Components';
import PlaceholderAvatar from '@p2p/Components/PlaceholderAvatar';
import Progressbar from '@p2p/Components/ProgressBar';

/**
 *
 *
 * @since 1.6.0
 */
export const TableHead = ({columns}) => {
    return (
        <thead>
            <tr>
                {columns?.map((column) => (
                    <th
                        key={column.name}
                        className={cx(styles.tableColumnHead, {
                            [styles[column.id]]: true,
                        })}
                        part={'give-table__column'}
                        scope="col"
                        aria-sort="none"
                    >
                        {column.name}
                    </th>
                ))}
            </tr>
        </thead>
    );
};

/**
 *
 *
 * @since 1.6.0
 */
export const TableBody = ({children}) => {
    return <tbody>{children}</tbody>;
};

/**
 *
 *
 * @since 1.6.0
 */
export const TableRow = ({
    columns,
    leaderboardName,
    name,
    profileImage,
    amount,
    goal,
    badge,
    link,
    fundraiserTotal,
    fundraisersTotal,
    teamsTotal,
    teamCaptain,
}) => {
    const renderTableData = (column) => {
        switch (column.id) {
            case 'rank':
                return badge;
            case 'name':
                return (
                    <span>
                        <img className={styles.avatar} src={profileImage || PlaceholderAvatar} alt="" />
                        <span>{name}</span>
                    </span>
                );
            case 'goal':
                return <Progressbar sanitizedDetails amount={amount} goal={goal} />;
            case 'action':
                return (
                    <Button as="a" href={link} size={Button.sizes.tiny} color={Button.colors.secondary}>
                        {__(`View ${leaderboardName}`, 'give-peer-to-peer')}
                    </Button>
                );
            case 'team_captain':
                return <>{teamCaptain ?? __('Not available', 'give-peer-to-peer')}</>;
            case 'members':
                return <>{`${fundraiserTotal}`}</>;
            case 'campaign_fundraiser_total':
                return <>{`${fundraisersTotal}`}</>;
            case 'campaign_team_total':
                return <>{`${teamsTotal}`}</>;

            default:
        }
        return false;
    };
    return (
        <tr className={styles.tableRow} part={'give-table__row'}>
            {columns.map((column) => {
                return (
                    <TableCell key={column.id} classname={column.id}>
                        {renderTableData(column)}
                    </TableCell>
                );
            })}
        </tr>
    );
};

/**
 *
 *
 * @since 1.6.0
 */
const TableCell = ({children, classname}: any) => {
    return (
        <td
            className={cx(styles.tableCell, {
                [styles[classname]]: true,
            })}
            part={'give-table__cell'}
        >
            {children}
        </td>
    );
};

/**
 *
 *
 * @since 1.6.0
 */
export default function Table({children}) {
    return (
        <table className={styles.table} part={'give-table'}>
            <caption className={styles.tableCaption}>{__('Ranking Table', 'give-peer-to-peer')}</caption>
            {children}
        </table>
    );
}
