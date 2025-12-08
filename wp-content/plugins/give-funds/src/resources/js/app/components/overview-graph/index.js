import Card from '../card';
import Chart from '../chart';
import Spinner from '../spinner';
import { useStoreValue } from '../../store';
import { useOverviewAPI } from '../../utils';
import LoadingNotice from '../loading-notice';

const { __ } = wp.i18n;

const OverviewGraph = () => {
	const [ { pageLoaded, fundTitle } ] = useStoreValue();
	const [ fetched, querying ] = useOverviewAPI();

	const styles = {
		display: pageLoaded ? 'block' : 'none',
		paddingTop: 30,
	};

	return (
		<div>
			{ pageLoaded === false && (
				<LoadingNotice notice={ __( 'Loading Fund activity', 'give-funds' ) } />
			) }

			<div style={ styles }>
				<Card width={ 12 }>
					{ fundTitle && (
						<div className="givewp-chart-title">
							<span className="givewp-chart-title-text">{ fundTitle }</span>
							{ querying && (
								<Spinner />
							) }
						</div>
					) }
					{ fetched ? (
						<Chart
							type="line"
							aspectRatio={ 0.3 }
							data={ fetched }
							showLegend={ false }
						/>
					) : (
						<div style={ {
							width: '100%',
							height: '295px',
						} } />
					) }
				</Card>
			</div>
		</div>
	);
};

export default OverviewGraph;
