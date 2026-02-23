import { getWindowData } from '../utils';
import moment from 'moment';
import OverviewGraph from '../components/overview-graph';
import DonationsTable from '../components/donations-table';
import PeriodSelector from '../components/period-selector';
// Store related dependencies
import { StoreProvider } from '../store';
import { reducer } from '../store/reducer';

const { __ } = wp.i18n;

import './style.scss';

const OverviewPage = () => {
	// Initial app state (available in component through useStoreValue)
	const initialState = {
		// Initial period range (defaults to the past week)
		//fundId: ( new URLSearchParams( window.location.search ) ).get( 'id' ),
		fundId: document.getElementById( 'give-funds-report-fund-id' ).value,
		fundTitle: document.getElementById( 'give-funds-report-fund-title' ).value,
		period: {
			startDate: moment().hour( 0 ).subtract( 7, 'days' ),
			endDate: moment().hour( 23 ),
			range: 'week',
		},
		// giveStatus: null
		pageLoaded: false,
		currentPage: 1,
		processBulk: false,
		currency: getWindowData( 'currency' ),
	};

	const editUrl = getWindowData( 'adminUrl' ) + 'edit.php?post_type=give_forms&page=give-edit-fund&id=' + initialState.fundId;

	return (
		<StoreProvider initialState={ initialState } reducer={ reducer }>
			<div className="wrap give-settings-page" style={ { position: 'relative' } }>
				<div className="give-settings-header">
					<h1 className="wp-heading-inline">{ __( 'Edit Fund', 'give-funds' ) }</h1>
					<div className="give-funds-filters ">
						<PeriodSelector />
					</div>
				</div>
				<div className="nav-tab-wrapper give-nav-tab-wrapper">
					<a className="nav-tab nav-tab-active" href="#" >{ __( 'Overview', 'give-funds' ) }</a>
					<a className="nav-tab" href={ editUrl } >{ __( 'Edit Fund', 'give-funds' ) }</a>
				</div>
				<OverviewGraph />
				<DonationsTable />
			</div>
		</StoreProvider>
	);
};

export default OverviewPage;
