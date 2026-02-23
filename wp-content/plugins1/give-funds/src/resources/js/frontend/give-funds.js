( function() {
	const selectFundDropDowns = document.querySelectorAll( '.give-funds-select' );
	/**
	 * Handle fund selection
	 */
	if ( selectFundDropDowns ) {
		selectFundDropDowns.forEach( ( select ) => {
			select.addEventListener( 'change', function( e ) {
				e.target.parentElement.querySelector( '.give-funds-fund-description' ).innerText = e.target.options[ e.target.selectedIndex ].dataset.description || '';
			} );
		} );
	}
}() );
