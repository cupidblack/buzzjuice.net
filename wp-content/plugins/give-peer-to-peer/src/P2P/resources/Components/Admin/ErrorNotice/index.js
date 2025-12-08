import { Notice } from '@p2p/Components/Admin';
import PropTypes from 'prop-types';

const ErrorNotice = ( { notice, reload } ) => {
	return (
		<Notice>
			<h2>{ notice }</h2>
			{ reload && (
				<div>
					Try to <a onClick={ () => window.location.reload() } href="#">reload</a> the browser
				</div>
			) }
		</Notice>
	);
};

ErrorNotice.propTypes = {
	// Error notice
	notice: PropTypes.string,
	// Show reload option
	reload: PropTypes.bool,
};

ErrorNotice.defaultProps = {
	reload: true,
}

export default ErrorNotice;
