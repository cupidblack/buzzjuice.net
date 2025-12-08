import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';

import { Button } from '../Button';
import { ArrowIcon } from '@p2p/Components/Icons';

import styles from './style.module.scss';
import {getAmountInCurrency} from "@p2p/js/utils";

const { __ } = wp.i18n;

const ListTable = ( { donations, emptyTitle, emptyContent } ) => {
	if ( ! donations.length ) {
		return (
			<div className={styles.empty}>
				<h3 className={styles.emptyTitle}>{emptyTitle}</h3>
				{ emptyContent && (
					<div className={styles.emptyContent}>
						{emptyContent}
					</div>
				) }
				<Button as={Link} to="donate" iconAfter={ArrowIcon} className={styles.emptyButton}>
					{ __( 'Donate Now', 'give-peer-to-peer' ) }
				</Button>
			</div>
		);
	}
	return (
		<div>
			{ donations.map( ( props, index ) => <ListTableRow {...props } key={index} /> ) }
		</div>
	);
};

ListTable.propTypes = {
	donations: PropTypes.array,
	emptyTitle: PropTypes.string.isRequired,
	emptyContent: PropTypes.node,
};

ListTable.Empty = ({title, description}) => (
	<div className={styles.emptylisttable}>
		<h3>{title}</h3>
		{description && <p>{description}</p>}
		<Button as={Link} href="donate" iconAfter={ArrowIcon}>
			{ __( 'Donate Now', 'give-peer-to-peer' ) }
		</Button>
	</div>
);

ListTable.Empty.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string,
};

const ListTableRow = ( { donorName, donationType, relativeDateString, teamOrFundraiserName, amount, sourceLink } ) => (
	<p className={styles.listtablerow}>
		<span>
			<span className={styles.donorName}>{ donorName }</span> donated a <span className={styles.donationType}>{ donationType }</span> donation { relativeDateString } through <a className={styles.teamOrFundraiserName} href={ sourceLink }>{ teamOrFundraiserName }</a>
		</span>
		<span className={styles.donationAmount}>{ getAmountInCurrency( amount ) }</span>
	</p>
);
ListTableRow.propTypes = {
	donorName: PropTypes.string,
	donationType: PropTypes.string,
	relativeDateString: PropTypes.string,
	teamOrFundraiserName: PropTypes.string,
	amount: PropTypes.number,
};

export {
	ListTable,
	ListTableRow,
};
