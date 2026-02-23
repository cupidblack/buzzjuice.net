import axios from 'axios';
import { useStoreValue } from '../store';
import { useState, useEffect } from 'react';
import { setGiveStatus, setPageLoaded } from '../store/actions';
import { getSampleData } from './sample';

export const getWindowData = ( value, fallback = null ) => {
	if ( window.GiveFunds && value in window.GiveFunds ) {
		return window.GiveFunds[ value ];
	}

	if ( window.GiveFundsMenu && value in window.GiveFundsMenu ) {
		return window.GiveFundsMenu[ value ];
	}

	return fallback;
};

export const useOverviewAPI = () => {
	const [ { period, fundId, processBulk, currency }, dispatch ] = useStoreValue();
	const [ fetched, setFetched ] = useState( null );
	const [ querying, setQuerying ] = useState( false );

	const source = axios.CancelToken.source();

	// Fetch new data when period changes
	useEffect( () => {
		if ( period.startDate && period.endDate ) {
			if ( querying === true ) {
				source.cancel( 'Operation canceled by the user.' );
			}
			setQuerying( true );

			axios.get( '/overview', {
				baseURL: getWindowData( 'apiRoot' ),
				cancelToken: source.token,
				params: {
					currency,
					fundId,
					start: period.startDate.format( 'YYYY-MM-DD' ),
					end: period.endDate.format( 'YYYY-MM-DD' ),
				},
				headers: {
					'content-type': 'application/json',
					'X-WP-Nonce': getWindowData( 'apiNonce' ),
				},
			} )
				.then( function( response ) {
					const status = response.data.status;
					dispatch( setGiveStatus( status ) );

					if ( status === 'no_donations_found' ) {
						const sample = getSampleData();
						setFetched( sample );
					} else {
						setFetched( response.data );
					}

					dispatch( setPageLoaded() );
					setQuerying( false );
				} )
				.catch( function() {
					setQuerying( false );
				} );
		}
	}, [ period, processBulk ] );

	return [ fetched, querying ];
};

export const useDonationsAPI = () => {
	const [ { period, fundId, currentPage, processBulk } ] = useStoreValue();
	const [ fetched, setFetched ] = useState( false );
	const [ querying, setQuerying ] = useState( false );

	const source = axios.CancelToken.source();

	useEffect( () => {
		if ( period.startDate && period.endDate ) {
			setQuerying( true );

			if ( ! processBulk ) {
				axios.get( '/get-donations', {
					baseURL: getWindowData( 'apiRoot' ),
					cancelToken: source.token,
					params: {
						fundId,
						start: period.startDate.format( 'YYYY-MM-DD' ),
						end: period.endDate.format( 'YYYY-MM-DD' ),
						page: currentPage,
					},
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': getWindowData( 'apiNonce' ),
					},
				} )
					.then( function( response ) {
						setFetched( response.data );
						setQuerying( false );
					} )
					.catch( function() {
						setQuerying( false );
					} );
			}
		}
	}, [ period, currentPage, processBulk ] );

	return [ fetched, querying ];
};

export const useReportsAPI = () => {
	const [ { period, currentFund, currency }, dispatch ] = useStoreValue();
	const [ fetched, setFetched ] = useState( false );
	const [ querying, setQuerying ] = useState( false );

	const source = axios.CancelToken.source();

	useEffect( () => {
		if ( period.startDate && period.endDate ) {
			setQuerying( true );
			axios.get( '/get-reports', {
				baseURL: getWindowData( 'apiRoot' ),
				cancelToken: source.token,
				params: {
					currency,
					fundId: currentFund,
					start: period.startDate.format( 'YYYY-MM-DD' ),
					end: period.endDate.format( 'YYYY-MM-DD' ),
				},
				headers: {
					'content-type': 'application/json',
					'X-WP-Nonce': getWindowData( 'apiNonce' ),
				},
			} )
				.then( function( response ) {
					setFetched( response.data );
					dispatch( setPageLoaded() );
					setQuerying( false );
				} )
				.catch( function() {
					setQuerying( false );
				} );
		}
	}, [ period, currentFund ] );

	return [ fetched, querying ];
};

export const useFundPercentageAPI = () => {
	const [ { period, currency } ] = useStoreValue();
	const [ fetched, setFetched ] = useState( false );
	const [ querying, setQuerying ] = useState( false );

	const source = axios.CancelToken.source();

	useEffect( () => {
		if ( period.startDate && period.endDate ) {
			setQuerying( true );
			axios.get( '/get-percentages', {
				baseURL: getWindowData( 'apiRoot' ),
				cancelToken: source.token,
				params: {
					currency,
					start: period.startDate.format( 'YYYY-MM-DD' ),
					end: period.endDate.format( 'YYYY-MM-DD' ),
				},
				headers: {
					'content-type': 'application/json',
					'X-WP-Nonce': getWindowData( 'apiNonce' ),
				},
			} )
				.then( function( response ) {
					setFetched( response.data );
					setQuerying( false );
				} )
				.catch( function() {
					setQuerying( false );
				} );
		}
	}, [ period ] );

	return [ fetched, querying ];
};

