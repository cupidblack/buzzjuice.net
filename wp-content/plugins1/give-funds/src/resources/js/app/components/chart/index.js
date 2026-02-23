// Dependencies
import ChartJS from 'chart.js';
import { useEffect, createRef, useState } from 'react';
import PropTypes from 'prop-types';
import './style.scss';

// Utilities
import { createConfig } from './utils';

// Components
import Legend from '../legend';

const Chart = ( { type, aspectRatio, data, showLegend } ) => {
	const canvas = createRef();
	const config = createConfig( type, data );
	const height = 100 * aspectRatio;
	const [ legend, setupLegend ] = useState( null );

	useEffect( () => {
		// Setup chart
		const ctx = canvas.current.getContext( '2d' );
		const chart = new ChartJS( ctx, config );

		if ( showLegend ) {
			setupLegend( <Legend data={ data } chartRef={ chart } /> );
		}

		// Cleanup chart
		return function cleanup() {
			chart.destroy();
		};
	}, [ height, data ] );

	let bgColor = '#F4F4F4';
	if ( showLegend === false ) {
		bgColor = '#FFF';
	}

	return (
		<div className="givewp-card">
			<div className="content">
				<div className="givewp-chart-canvas" style={ { background: bgColor } }>
					<canvas width={ 100 } height={ height } ref={ canvas }></canvas>
				</div>
				{ legend }
			</div>
		</div>
	);
};

Chart.propTypes = {
	// Chart type (see https://www.chartjs.org/docs/2.8.0/charts/)
	type: PropTypes.string,
	title: PropTypes.string,
	// Aspect ratio used to display chart (default 0.6)
	aspectRatio: PropTypes.number,
	// Data object provided by Reports API
	data: PropTypes.object.isRequired,
};

Chart.defaultProps = {
	type: 'bar',
	title: 'Report',
	aspectRatio: 0.6,
	data: null,
};

export default Chart;
