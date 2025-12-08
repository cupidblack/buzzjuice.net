import PropTypes from 'prop-types';

const ProgressBarContainer = ( { className, size, children } ) => {
	return (
		<div className={className} style={ { height: size } }>
			{ children }
		</div>
	);
};

ProgressBarContainer.propTypes = {
    className: PropTypes.string,
	size: PropTypes.number,
	children: PropTypes.node,
};

ProgressBarContainer.defaultProps = {
	size: 20,
};

export default ProgressBarContainer;
