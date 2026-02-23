import Grid from '../grid';
import Card from '../card';
import Reports from '../chart/Reports';
import Spinner from '../spinner';
import { useStoreValue } from '../../store';
import { useReportsAPI } from '../../utils';
import LoadingNotice from '../loading-notice';
import NoDataNotice from '../no-data-notice';

const { __ } = wp.i18n;

const ReportsGraph = () => {
	const [ { giveStatus, pageLoaded } ] = useStoreValue();
	const [ fetched, querying ] = useReportsAPI();

	return (
		<>
			{ giveStatus === 'no_donations_found' && (
				<NoDataNotice />
			) }

			{ pageLoaded === false && (
				<LoadingNotice notice={ __( 'Loading Funds reports', 'give-funds' ) } />
			) }

			<Grid visible={ pageLoaded }>
				<Card width={ 12 }>
					<div className="givewp-chart-title">
						<span className="givewp-chart-title-text">{ __( 'Reports', 'give-funds' ) }</span>
						{ querying && (
							<Spinner />
						) }
					</div>
					{ fetched ? (
						<Reports
							type="line"
							aspectRatio={ 0.3 }
							data={ fetched }
						/>
					) : (
						<div style={ {
							width: '100%',
							height: '295px',
						} } />
					) }
				</Card>
			</Grid>
		</>
	);
};

export default ReportsGraph;
