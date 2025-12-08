import { getWindowData } from '../utils';
import moment from 'moment';
// Store related dependencies
import { StoreProvider } from '../store';
import { reducer } from '../store/reducer';
// Components
import FundsSelector from '../components/funds-selector-dropdown';
import PeriodSelector from '../components/period-selector';
import ReportsGraph from '../components/reports-graph';
import FundPercentages from '../components/fund-percentages';

const { __ } = wp.i18n;

import './style.scss';

const ReportsPage = () => {
	const initialState = {
		period: {
			startDate: moment().hour( 0 ).subtract( 7, 'days' ),
			endDate: moment().hour( 23 ),
			range: 'week',
		},
		pageLoaded: false,
		currentFund: null,
		currency: getWindowData( 'currency' ),
	};

	const OverviewUrl = getWindowData( 'adminUrl' ) + 'edit.php?post_type=give_forms&page=give-reports';
	const LegacyUrl = getWindowData( 'adminUrl' ) + 'edit.php?post_type=give_forms&page=give-reports&legacy=true';

	return (
		<StoreProvider initialState={ initialState } reducer={ reducer }>
			<div className="wrap give-settings-page" style={ { position: 'relative' } }>
				<div className="give-settings-header">
					<h1 className="wp-heading-inline">
						{ __( 'Reports', 'give-funds' ) }
						{` `}
						<span className="give-settings-heading-sep dashicons dashicons-arrow-right-alt2"></span>
						{` `}
						{ __( 'Funds', 'give-funds' ) }
					</h1>
					<div className="give-funds-filters ">
						<FundsSelector />
						<PeriodSelector />
					</div>
				</div>
				<div className="nav-tab-wrapper give-nav-tab-wrapper">
					<a className="nav-tab" href={ OverviewUrl } >{ __( 'Overview', 'give' ) }</a>
					<a className="nav-tab nav-tab-active" href="#" >{ __( 'Funds', 'give-funds' ) }</a>
					<a className="nav-tab" href={ LegacyUrl } >{ __( 'Legacy Reports', 'give' ) }</a>
				</div>
				<ReportsGraph />
				<FundPercentages />
			</div>
		</StoreProvider>
	);
};

export default ReportsPage;
