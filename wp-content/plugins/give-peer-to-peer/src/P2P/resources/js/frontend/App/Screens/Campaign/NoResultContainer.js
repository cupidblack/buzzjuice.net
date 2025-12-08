import PropTypes from 'prop-types';
import styles from './LoadingIndicator.module.scss';

export const NoResultContainer = ( { children = null } ) => (
	<div className={ styles.loadingIndicator }>
		{ children }
	</div>
);

NoResultContainer.propTypes = {
	children: PropTypes.node,
};

