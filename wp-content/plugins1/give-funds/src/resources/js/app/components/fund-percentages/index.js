import { useFundPercentageAPI } from '../../utils';
// Components
import Grid from '../grid';
import Card from '../card';
import Spinner from '../spinner';
import Chart from '../chart';

const { __ } = wp.i18n;

const FundPercentages = () => {
	const [ fetched, querying ] = useFundPercentageAPI();

	const loadingStyle = {
		width: '100%',
		height: '295px',
	};

	return (
		<Grid>
			{ fetched && (
				<Card width={ 4 }>
					<div className="givewp-chart-title">
						<span className="givewp-chart-title-text">{ __( 'Fund Percentages', 'give-funds' ) }</span>
						{ querying && (
							<Spinner />
						) }
					</div>
					{ fetched ? (
						<Chart
							type="doughnut"
							aspectRatio={ 0.6 }
							data={ fetched }
							showLegend={ true }
						/>
					) : (
						<div style={ loadingStyle } />
					) }
				</Card>
			) }
		</Grid>
	);
};

export default FundPercentages;
