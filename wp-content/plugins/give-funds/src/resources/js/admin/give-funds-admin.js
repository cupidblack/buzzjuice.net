( function() {
	const { __, sprintf } = wp.i18n;
	const fundChoices = document.querySelectorAll( 'input[name="give_funds_form_choice"]' );
	const bulkActionsBtns = document.querySelectorAll( '#give-funds-list-table-form #doaction, #give-funds-list-table-form #doaction2' );
	const donationsBulkActionsBtns = document.querySelectorAll( '#give-payments-filter #doaction, #give-payments-filter #doaction2' );
	const donationFormsBulkActionsBtns = document.querySelectorAll( '.post-type-give_forms #doaction, .post-type-give_forms #doaction2' );
	const deleteFundLinks = document.querySelectorAll( '.give-funds-delete-fund' );
	const publishBtn = document.getElementById( 'publish' );

	const handleSelectOption = ( e ) => {
		showOptionSection( e.currentTarget.value, true );
	};

	const showOptionSection = ( option, hideOther = false ) => {
		if ( hideOther ) {
			document
				.querySelectorAll( '.give_funds_form_options' )
				.forEach( ( fundOption ) => {
					fundOption.classList.add( 'give-hidden' );
				} );
		}
		const elements = document.querySelectorAll( `.give_funds_${ option }_field` );

		elements.forEach( ( element ) => {
			element.classList.remove( 'give-hidden' );
		} );
	};

	const handleDeleteFund = ( e ) => {
		e.preventDefault();

		const deleteUrl = e.target.href;
		const fundName = e.target.dataset.name;

		// eslint-disable-next-line no-undef
		new Give.modal.GiveConfirmModal( {
			modalContent: {
				title: __( 'Delete Fund', 'give-funds' ),
				desc: sprintf( __( 'Are you sure you want to delete fund %s', 'give-funds' ), fundName ),
				cancelBtnTitle: __( 'Cancel', 'give-funds' ),
				confirmBtnTitle: __( 'Delete', 'give-funds' ),
			},
			successConfirm() {
				// Navigate to delete fund url
				window.location.href = deleteUrl;
			},
		} ).render();
	};

	const handleBulkActions = ( e ) => {
		e.preventDefault();

		const actionName = e.currentTarget.id.replace( 'do', '' );
		const funds = document.querySelectorAll( 'input[name*="give-fund"]:checked' );
		const action = document.querySelector( `select[name="${ actionName }"]` );

		if ( ! isNaN( action.value ) ) {
			// eslint-disable-next-line no-undef
			return new Give.modal.GiveWarningAlert( {
				modalContent: {
					title: __( 'No action selected', 'give-funds' ),
					desc: __( 'You must select a bulk action to proceed.', 'give-funds' ),
					cancelBtnTitle: __( 'OK', 'give-funds' ),
				},
			} ).render();
		}

		if ( ! funds.length ) {
			// eslint-disable-next-line no-undef
			return new Give.modal.GiveWarningAlert( {
				modalContent: {
					title: __( 'Select Fund', 'give-funds' ),
					desc: ( 'delete' === action.value )
						? __( 'Select at least one fund to delete.', 'give-funds' )
						: __( 'Select at least one fund in order to reassign its revenue to a different fund.', 'give-funds' ),
					cancelBtnTitle: __( 'OK', 'give-funds' ),
				},
			} ).render();
		}

		if ( 'delete' === action.value ) {
			const description = __( 'Selected funds will be permanently deleted. Any revenue within these funds will be assigned to the default fund.', 'give-funds' );

			// eslint-disable-next-line no-undef
			new Give.modal.GiveConfirmModal( {
				modalContent: {
					title: __( 'Confirm bulk action', 'give-funds' ),
					desc: description,
					cancelBtnTitle: __( 'Cancel', 'give-funds' ),
					confirmBtnTitle: __( 'Delete', 'give-funds' ),
				},
				successConfirm() {
					// Allow some time to close the modal
					window.setTimeout( () => {
						document.querySelector( '#give-funds-list-table-form' ).submit();
					}, 500 );
				},
			} ).render();
		}

		if ( 'reassign' === action.value ) {
			// eslint-disable-next-line no-undef
			new Give.modal.GiveConfirmModal( {
				modalContent: {
					title: __( 'Reassign Revenue to Fund', 'give-funds' ),
					desc: showSelectFundDropDown( __( 'Revenue from the selected funds will be reassigned to this fund:', 'give-funds' ) ),
					cancelBtnTitle: __( 'Cancel', 'give-funds' ),
					confirmBtnTitle: __( 'Reassign', 'give-funds' ),
				},
				successConfirm() {
					const selectFund = document.querySelector( '#give-funds-select-fund' );
					const selectedFund = document.querySelector( 'input[name="give-funds-selected-fund"]' );
					// Set selected fund value
					selectedFund.value = selectFund.value;
					// Allow some time to close the modal
					window.setTimeout( () => {
						document.querySelector( '#give-funds-list-table-form' ).submit();
					}, 500 );
				},
			} ).render();
		}
	};

	const handleDonationsBulkActions = ( e ) => {
		e.preventDefault();

		const actionName = e.currentTarget.id.replace( 'do', '' );
		const donations = document.querySelectorAll( 'input[name*="payment"]:checked' );
		const action = document.querySelector( `select[name="${ actionName }"]` );

		if ( ! donations.length ) {
			// eslint-disable-next-line no-undef
			return new Give.modal.GiveWarningAlert( {
				modalContent: {
					title: __( 'Select Donation', 'give-funds' ),
					desc: __( 'Select at least one donation in order to reassign its revenue to a fund.', 'give-funds' ),
					cancelBtnTitle: __( 'OK', 'give-funds' ),
				},
			} ).render();
		}

		if ( 'reassign' === action.value ) {
			// eslint-disable-next-line no-undef
			new Give.modal.GiveConfirmModal( {
				modalContent: {
					title: __( 'Reassign Revenue to Fund', 'give-funds' ),
					desc: showSelectFundDropDown( __( 'Revenue from the selected donations will be reassigned to this fund:', 'give-funds' ) ),
					cancelBtnTitle: __( 'Cancel', 'give-funds' ),
					confirmBtnTitle: __( 'Reassign', 'give-funds' ),
				},
				successConfirm() {
					const selectedFund = document.querySelector( '#give-funds-select-fund' );
					const form = document.querySelector( '#give-payments-filter' );
					const input = document.createElement( 'input' );
					input.type = 'hidden';
					input.name = 'give-funds-selected-fund';
					input.value = selectedFund.value;

					form.appendChild( input );

					// Allow some time to close the modal
					window.setTimeout( () => {
						form.submit();
					}, 500 );
				},
			} ).render();
		}
	};

	const handleDonationFormsBulkActions = ( e ) => {
		const actionName = e.currentTarget.id.replace( 'do', '' );
		const forms = document.querySelectorAll( 'input[name*="post"]:checked' );
		const action = document.querySelector( `select[name="${ actionName }"]` );

		if ( 'reassign' !== action.value ) {
			return;
		}

		e.preventDefault();

		if ( ! forms.length ) {
			// eslint-disable-next-line no-undef
			return new Give.modal.GiveWarningAlert( {
				modalContent: {
					title: __( 'Select Form', 'give-funds' ),
					desc: __( 'Select at least one form in order to reassign its revenue to a fund.', 'give-funds' ),
					cancelBtnTitle: __( 'OK', 'give-funds' ),
				},
			} ).render();
		}

		// eslint-disable-next-line no-undef
		new Give.modal.GiveConfirmModal( {
			modalContent: {
				title: __( 'Reassign Revenue to Fund', 'give-funds' ),
				// eslint-disable-next-line no-undef
				desc: showSelectFundDropDown( __( 'Revenue from the selected forms will be reassigned to this fund:', 'give-funds' ) ),
				cancelBtnTitle: __( 'Cancel', 'give-funds' ),
				confirmBtnTitle: __( 'Reassign', 'give-funds' ),
			},
			successConfirm() {
				const selectedFund = document.querySelector( '#give-funds-select-fund' );
				const form = document.querySelector( '#posts-filter' );
				const input = document.createElement( 'input' );
				input.type = 'hidden';
				input.name = 'give-funds-selected-fund';
				input.value = selectedFund.value;

				form.appendChild( input );

				// Allow some time to close the modal
				window.setTimeout( () => {
					form.submit();
				}, 500 );
			},
		} ).render();
	};

	const showSelectFundDropDown = ( label ) => {
		// eslint-disable-next-line no-undef
		const selectOptions = GiveFunds.funds.map( ( fund ) => `<option value="${ fund.id }">${ fund.title }</option>` );

		return (
			`
			<div class="give-funds-select-fund-wrap">
				<p>
					<label for="give-funds-select-fund">
						${ label }
					</label>
				</p>
				<p>
					<select id="give-funds-select-fund" class="give-select">
						${ selectOptions }
					</select>
				</p>
			</div>
			`
		);
	};

	const showDonorChoiceWarning = ( e ) => {
		const postType = document.getElementById( 'post_type' );

		if ( postType && 'give_forms' === postType.value ) {
			const choice = document.querySelector( 'input[name="give_funds_form_choice"]:checked' );
			const checked = document.querySelectorAll( '#give_funds_donor_choice option:checked' );

			if ( choice && 'donor_choice' === choice.value && checked.length <= 1 ) {
				e.preventDefault();
				// eslint-disable-next-line no-undef
				return new Give.modal.GiveConfirmModal( {
					modalContent: {
						title: __( "Confirm Donor's Choice Designation", 'give-funds' ),
						desc: __( "Donor's Choice works best when the donor can choose among two or more funds. If all form revenue is intended for a single fund, then Admin's Choice is the recommended option.", 'give-funds' ),
						cancelBtnTitle: __( 'Cancel', 'give-funds' ),
						confirmBtnTitle: __( 'Save Anyway', 'give-funds' ),
					},
					successConfirm() {
						// Allow some time to close the modal
						window.setTimeout( () => {
							document.getElementById( 'post' ).submit();
						}, 500 );
					},
				} ).render();
			}
		}
	};

	/**
	 * Handle Fund choice options
	 */
	fundChoices.forEach( ( choice ) => {
		/**
		 * Add change listener
		 */
		choice.addEventListener( 'change', handleSelectOption );

		// Show initial selected option
		if ( choice.checked ) {
			showOptionSection( choice.value );
		}
	} );

	/**
	 * Handle delete fund
	 */
	deleteFundLinks.forEach( ( link ) => {
		link.addEventListener( 'click', handleDeleteFund, false );
	} );

	/**
	 * Handle bulk actions
	 */
	bulkActionsBtns.forEach( ( button ) => {
		button.addEventListener( 'click', handleBulkActions, false );
	} );

	/**
	 * Handle Donations bulk actions
	 */
	donationsBulkActionsBtns.forEach( ( button ) => {
		button.addEventListener( 'click', handleDonationsBulkActions, false );
	} );

	/**
	 * Handle Donation Forms bulk actions
	 */
	if ( 'pagenow' in window && 'edit-give_forms' === window.pagenow ) {
		donationFormsBulkActionsBtns.forEach( ( button ) => {
			button.addEventListener( 'click', handleDonationFormsBulkActions, false );
		} );
	}

	/**
	 * Handle Donors choice
	 */
	if ( publishBtn ) {
		publishBtn.addEventListener( 'click', showDonorChoiceWarning );
	}
}() );
