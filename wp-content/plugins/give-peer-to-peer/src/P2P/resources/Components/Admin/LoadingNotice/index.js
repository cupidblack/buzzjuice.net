import { Spinner, Notice } from '@p2p/Components/Admin';
import PropTypes from 'prop-types';

const LoadingNotice = ( { notice } ) => {
	return (
		<Notice>
			<Spinner />
			<h2>{ notice }</h2>
		</Notice>
	);
};

LoadingNotice.propTypes = {
	// Loading notice
	notice: PropTypes.string,
};

export default LoadingNotice;
