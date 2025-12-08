/**
 * WordPress dependencies
 */
import {__} from '@wordpress/i18n';

/**
 * Options data for various block controls
 */

export const p2pTeamLeaderboardOptions = {
    columns: [],
    type: [],
};

p2pTeamLeaderboardOptions.columns = [
    {value: 'fullWidth', label: __('Full Width', 'give-peer-to-peer')},
    {value: 'double', label: __('Double', 'give-peer-to-peer')},
    {value: 'triple', label: __('Triple', 'give-peer-to-peer')},
    {value: 'max', label: __('Max', 'give-peer-to-peer')},
];

p2pTeamLeaderboardOptions.type = [
    {value: 'grid', label: __('Grid', 'give-peer-to-peer')},
    {value: 'table', label: __('Table', 'give-peer-to-peer')},
];
