import Card from '../card';

const { __ } = wp.i18n;

import './style.scss';

const NoDonationsNotice = () => {
	return (
		<div className="give-funds-no-donations-notice">
			<Card>
				<div className="give-funds-no-donations-notice-content">
					{ __( 'There are no donations for selected period', 'give-funds' ) }
				</div>
			</Card>
		</div>
	);
};

export default NoDonationsNotice;
