import { Link } from 'react-router-dom';
import { getAmountInCurrency } from '@p2p/js/utils';
import { Button } from '@p2p/Components';
import { ArrowIcon } from '@p2p/Components/Icons';

import styles from '@p2p/Components/ListTable/style.module.scss';

const { __ } = wp.i18n;

export const DonorsList = ( { donors } ) => {
	if ( ! donors.length ) {
		return (
			<div className={ styles.empty }>
				<h3 className={ styles.emptyTitle }>{ __( 'Be the first to donate!', 'give-peer-to-peer' ) }</h3>
				<Button as={Link} to="donate" iconAfter={ArrowIcon} className={ styles.emptyButton }>
					{ __( 'Donate Now', 'give-peer-to-peer' ) }
				</Button>
			</div>
		);
	}

	return donors.map( ( { name, amount } ) => (
		<div key={ name + amount } className={ styles.listtablerow }>
			<div>
				<strong>{ name }</strong>
			</div>
			<div>
				<strong className={ styles.amount }>
					{ getAmountInCurrency( amount ) }
				</strong>
			</div>
		</div>
	) );
};
