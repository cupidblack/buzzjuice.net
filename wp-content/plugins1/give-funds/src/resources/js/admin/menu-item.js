const { __ } = wp.i18n;
const { addFilter } = wp.hooks;
import { getWindowData } from '../app/utils';

addFilter( 'givewp-reports-page-menu-links', 'Give', ( data ) => {
	data.push( {
		href: getWindowData( 'reportsUrl' ),
		text: __( 'Funds', 'give-funds' ),
	} );
	return data;
} );

