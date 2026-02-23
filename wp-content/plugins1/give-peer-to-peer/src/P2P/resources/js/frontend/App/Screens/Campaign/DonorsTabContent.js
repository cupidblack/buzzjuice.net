import PropTypes from 'prop-types';
import { DonorsList } from '@p2p/Components/DonorsList';
import { Spinner } from '@p2p/Components/Admin';

import styles from './DonorsTabContent.module.scss';

const { __ } = wp.i18n;

export function DonorsTabContent( {
	donors = [],
	error = false,
	loading = false,
} ) {
	if ( error ) {
		return (
			<div className={styles.error}>
				{ __( 'Something went wrong', 'give-peer-to-peer' ) }
			</div>
		);
	}

	if ( loading ) {
		return (
			<div className={styles.loading}>
				<Spinner/>
			</div>
		);
	}

	return <DonorsList donors={donors}/>;
}

DonorsTabContent.propTypes = {
	error: PropTypes.bool,
	loading: PropTypes.bool,
	donors: PropTypes.array,
};
