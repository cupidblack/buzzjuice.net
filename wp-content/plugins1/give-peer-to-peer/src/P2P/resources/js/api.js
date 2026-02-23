import axios from 'axios';
import useSWR, { mutate } from 'swr';
import { getProp } from './utils';

const API = axios.create( {
	baseURL: getProp( 'apiRoot' ),
	headers: {
		'Content-Type': 'application/json',
		'X-WP-Nonce': getProp( 'apiNonce' ),
	},
} );

export default API;

export { mutate };

export const CancelToken = axios.CancelToken.source();

// SWR Fetcher
export const Fetcher = ( endpoint ) => API.get( endpoint ).then( ( res ) => {
	const { data, ...rest } = res.data;
	return {
		data,
		response: rest,
	};
} );

export const useFetcher = ( endpoint, params = {} ) => {
	const { data, error, isValidating } = useSWR( endpoint, Fetcher, params );
	return {
		data: data ? data.data : undefined,
		isLoading: ! error && ! data,
		isError: error,
		isValidating,
		response: data ? data.response : undefined,
	};
};

// GET endpoint with additional parameters
export const getEndpoint = ( endpoint, data ) => {
	if ( data ) {
		const queryString = new URLSearchParams( data );
		// pretty url?
		const separator = ( getProp( 'apiRoot' ).indexOf( '?' ) === -1 ) ? '?' : '&';

		return endpoint + separator + queryString.toString();
	}

	return endpoint;
};
