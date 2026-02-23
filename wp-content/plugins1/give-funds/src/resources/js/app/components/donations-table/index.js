import { useDonationsAPI } from '../../utils';
// Components
import Table from '../table';
import LoadingNotice from '../loading-notice';
import Pagination from '../pagination';
import NoDonationsNotice from '../no-donations-notice';

const { __ } = wp.i18n;

const DonationsTable = ( { title } ) => {
	const [ fetched, querying ] = useDonationsAPI();

	if ( ! fetched ) {
		return <LoadingNotice notice={ __( 'Loading Recent Donations', 'give-funds' ) } />;
	}

	if ( ! fetched.pages ) {
		return <NoDonationsNotice />;
	}

	const styles = {
		display: ( !! fetched.pages ) ? 'block' : 'none',
		marginTop: 30,
	};

	return (
		<>
			<div className="give-funds-donations-table-container" style={ styles }>
				{ fetched && (
					<Table
						key={ ( new Date() ).getMilliseconds() }
						title={ title }
						labels={ fetched.labels }
						rows={ fetched.rows }
						querying={ querying }
					/>
				) }
			</div>

			<Pagination pages={ fetched.pages } disabled={ querying } />
		</>
	);
};

export default DonationsTable;
