/**
 * WordPress dependencies
 */
import {__} from '@wordpress/i18n';

/**
 * Options data for block controls
 */

export const p2pFundraiserLeaderboardOptions = {
    columns: [],
    type: [],
};

p2pFundraiserLeaderboardOptions.columns = [
    {value: 'fullWidth', label: __('Full Width', 'give')},
    {value: 'double', label: __('Double', 'give')},
    {value: 'triple', label: __('Triple', 'give')},
    {value: 'max', label: __('Max', 'give-peer-to-peer')},
];

p2pFundraiserLeaderboardOptions.type = [
    {value: 'grid', label: __('Grid', 'give-peer-to-peer')},
    {value: 'table', label: __('Table', 'give-peer-to-peer')},
];
