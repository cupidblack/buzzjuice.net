import { Fragment, useState } from 'react';
import axios from 'axios';
import Card from '../card';
import './style.scss';
import { getWindowData } from '../../utils';
import { useStoreValue } from '../../store';
import { setProcessBulkActions } from '../../store/actions';
import LoadingOverlay from '../loading-overlay';

const { __ } = wp.i18n;

const Table = ( { title, labels, rows, querying } ) => {
	const [ { fundId }, dispatch ] = useStoreValue();
	const [ checked, setChecked ] = useState( {} );
	const funds = getWindowData( 'funds' );

	const handleCheck = ( e, donationId ) => {
		const isChecked = e.currentTarget.checked;

		setChecked( ( state ) => {
			return {
				...state,
				[ donationId ]: isChecked,
			};
		} );
	};

	const handleCheckAll = ( e ) => {
		let checkedIds = {};
		const isChecked = e.currentTarget.checked;

		if ( isChecked ) {
			Object.keys( rows ).forEach( ( id ) => {
				checkedIds = {
					...checkedIds,
					[ id ]: true,
				};
			} );
		}

		setChecked( checkedIds );
	};

	const isChecked = ( donationId ) => checked.hasOwnProperty( donationId ) && checked[ donationId ];

	const hasCheckedItems = () => {
		const checkedItems = Object.keys( checked ).filter( ( item ) => isChecked( item ) );
		return ( checkedItems.length > 0 );
	};

	const showSelectFundDropDown = ( label ) => {
		// eslint-disable-next-line no-undef
		const selectOptions = GiveFunds.funds.map( ( fund ) => {
			const disabled = ( fund.id === parseInt( fundId ) ) ? 'disabled' : '';
			return `<option value="${ fund.id }" ${ disabled }>${ fund.title }</option>`;
		} );

		return (
			`
			<div class="give-funds-select-fund-wrap">
				<p>
					<label for="give-funds-select-fund">
						${ label }
					</label>
				</p>
				<p>
					<select
						id="give-funds-select-fund-react"
						class="give-select"
					>
						${ selectOptions }
					</select>
				</p>
			</div>
			`
		);
	};

	const handleReassign = () => {
		// eslint-disable-next-line no-undef
		new Give.modal.GiveConfirmModal( {
			modalContent: {
				title: __( 'Reassign Revenue to Fund', 'give-funds' ),
				desc: showSelectFundDropDown( __( 'Revenue from the selected donations will be reassigned to this fund:', 'give-funds' ) ),
				cancelBtnTitle: __( 'Cancel', 'give-funds' ),
				confirmBtnTitle: __( 'Reassign', 'give-funds' ),
			},
			successConfirm() {
				const selectFund = document.getElementById( 'give-funds-select-fund-react' );
				const source = axios.CancelToken.source();
				const selectedDonationIds = Object.keys( checked ).map( ( item ) => item );

				dispatch( setProcessBulkActions( true ) );

				axios.post( '/reassign-fund', {
					// eslint-disable-next-line no-undef
					fundId: selectFund ? selectFund.value : GiveFunds.funds[ 0 ].id,
					donations: selectedDonationIds,
				}, {
					baseURL: getWindowData( 'apiRoot' ),
					cancelToken: source.token,
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': getWindowData( 'apiNonce' ),
					},
				} )
					.then( function() {
						dispatch( setProcessBulkActions( false ) );
					} )
					.catch( function() {
						dispatch( setProcessBulkActions( false ) );
					} );
			},
		} ).render();
	};

	const getLabels = () => Object.entries( labels ).map( ( [ key, value ] ) => {
		return (
			<div className="givewp-table-label" key={ `label-${ key }` }>
				{ value }
			</div>
		);
	} );

	const getData = () => Object.entries( rows ).map( ( [ donationId, row ] ) => {
		// Row item
		const rowItems = Object.entries( row ).map( ( [ key, data ] ) => {
			switch ( key ) {
				case 'donation':
					return (
						<div className="givewp-table-row-item give-funds-display-block" key={ key }>
							<div className="give-funds-donation-links">
								<a href={ data.donatinLink } target="_blank" rel="noreferrer">
									{ data.number }
								</a>
								{ __( 'by', 'give-funds' ) }
								<a href={ data.donorLink } target="_blank" rel="noreferrer">
									{ data.donor }
								</a>
							</div>
							<div>
								<a
									href={ data.email.link }
									rel="tooltip"
									aria-label={ data.email.label }
									className="hint--top hint--bounce"
								>
									{ data.email.value }
								</a>
							</div>
						</div>
					);

				case 'amount':
					return (
						<div className="givewp-table-row-item give-funds-display-block" key={ key }>
							<div>
								{ data.amount }
							</div>
							<small>
								{ __( 'via', 'give-funds' ) }{` `}{ data.gateway }
							</small>
						</div>
					);

				case 'donationForm':
					return (
						<div className="givewp-table-row-item" key={ key }>
							<a href={ data.link } target="_blank" rel="noreferrer">
								{ data.name }
							</a>
						</div>
					);

				default:
					return (
						<div className="givewp-table-row-item" key={ key }>
							{ data }
						</div>
					);
			}
		} );

		return (
			<div className="givewp-table-row" key={ donationId }>
				<div className="givewp-table-row-item give-funds-select-column" key={`row-${ donationId }`}>
					<input
						type="checkbox"
						className="give-funds-checkbox"
						onChange={ ( e ) => handleCheck( e, donationId ) }
						checked={ isChecked( donationId ) }
					/>
				</div>
				{ rowItems }
			</div>
		);
	} );

	return (
		<Fragment>

			<div className="give-funds-bulk-actions-row">
				<h2 className="give-funds-table-title">
					{ __( 'Recent Donations', 'give-funds' ) }
				</h2>
				{ ( funds.length > 1 ) && (
					<div className="give-funds-bulk-action">
						{ hasCheckedItems() ? (
							<button
								className="button"
								onClick={ handleReassign }
							>
								{ __( 'Reassign Fund', 'give-funds' ) }
							</button>
						) : (
							<button
								className="button hint--top hint--bounce"
								disabled={ true }
								rel="tooltip"
								aria-label={ __( 'Select Donations', 'give-funds' ) }
							>
								{ __( 'Reassign Fund', 'give-funds' ) }
							</button>
						) }
					</div>
				) }
			</div>

			<Card width={ 12 }>
				{ querying && (
					<LoadingOverlay />
				) }
				{ title && ( <div className="givewp-table-title">
					{ title }
				</div> ) }
				<div className="givewp-table">
					<div className="givewp-table-header">
						<div className="givewp-table-label give-funds-select-column" key="label">
							<input
								type="checkbox"
								onChange={ handleCheckAll }
							/>
						</div>
						{ getLabels() }
					</div>
					{ getData() }
				</div>
			</Card>

		</Fragment>
	);
};

export default Table;
