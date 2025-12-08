import { useEffect, createRef, useState } from 'react';
// Dependencies
import ChartJS from 'chart.js';
import PropTypes from 'prop-types';
import './style.scss';

// Utilities
import { createConfigReports } from './utils';

// Components
const Reports = ( { aspectRatio, data } ) => {
	const canvas = createRef();
	const config = createConfigReports( data );
	const height = 100 * aspectRatio;
	const datasets = config.data.datasets;

	const [ chartObj, setChartObj ] = useState( null );
	const [ toggle, setToggle ] = useState( null );

	const toggleFund = ( index ) => {
		const status = ! chartObj.getDatasetMeta( index ).hidden;
		chartObj.getDatasetMeta( index ).hidden = status;
		chartObj.update();

		setToggle( ( state ) => {
			return {
				...state,
				[ index ]: status,
			};
		} );
	};

	const isToggled = ( index ) => toggle && toggle.hasOwnProperty( index ) && toggle[ index ];

	const renderLegendsOptions = () => {
		// SHow only if we have 2 or more funds
		if ( datasets.length <= 1 ) {
			return false;
		}

		return (
			<ul className="givewp-funds-legends">
				{ datasets.map( ( fund, i ) => {
					return (
						<li
							key={ i }
							onClick={ () => toggleFund( i ) }
							className={ isToggled( i ) ? 'givewp-funds-linetrough' : '' }
						>
							<div style={ { backgroundColor: fund.borderColor } }> </div>
							{ fund.title }
						</li>
					);
				} ) }
			</ul>
		);
	};

	useEffect( () => {
		const ctx = canvas.current.getContext( '2d' );
		const chart = new ChartJS( ctx, config );

		setChartObj( chart );
		setToggle( {} );

		// Cleanup chart
		return function cleanup() {
			chart.destroy();
		};
	}, [ height, data ] );

	return (
		<div className="givewp-card">
			<div className="content">
				<div className="givewp-chart-canvas" style={ { background: '#FFF' } }>
					<canvas width={ 100 } height={ height } ref={ canvas }> </canvas>
				</div>
				{ renderLegendsOptions() }
			</div>
		</div>
	);
};

Reports.propTypes = {
	title: PropTypes.string,
	// Aspect ratio used to display chart (default 0.6)
	aspectRatio: PropTypes.number,
	// Data object provided by Reports API
	data: PropTypes.object.isRequired,
};

Reports.defaultProps = {
	title: 'Report',
	aspectRatio: 0.6,
	data: null,
};

export default Reports;
