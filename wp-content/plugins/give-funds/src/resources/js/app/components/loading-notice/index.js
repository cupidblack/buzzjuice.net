import PropTypes from 'prop-types';
// Components
import Spinner from '../spinner';

const { __ } = wp.i18n;

// Styles
import './style.scss';

const LoadingNotice = ( { notice } ) => {
	return (
		<div className="givewp-loading-notice">
			<div className="givewp-loading-notice-card">
				<Spinner />
				<h2>{ notice }</h2>
			</div>
		</div>
	);
};

LoadingNotice.propTypes = {
	notice: PropTypes.string.isRequired,
};

LoadingNotice.defaultProps = {
	notice: __( 'Loading', 'give-funds' ),
};

export default LoadingNotice;
