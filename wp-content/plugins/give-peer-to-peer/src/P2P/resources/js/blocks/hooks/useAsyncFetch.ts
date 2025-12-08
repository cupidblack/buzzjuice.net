import {getProp} from '@p2p/js/utils';
import {useEffect, useState} from 'react';

export default function useAsyncFetch(endpoint, parameters) {
    const [data, SetData] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [isError, setIsError] = useState(null);
    const [response, setResponse] = useState(null);

    const params = JSON.stringify(parameters);
    const searchParams = new URLSearchParams(JSON.parse(params));

    const baseUrl = getProp('apiRoot');
    const endpoints = String(endpoint);
    const url = `${baseUrl}${endpoints}?${searchParams}`;

    useEffect(() => {
        const fetchData = async () => {
            setIsLoading(true);
            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': getProp('apiNonce'),
                    },
                });
                const data = await response.json();
                setResponse(data);
                SetData(data.data);
            } catch (error) {
                setIsError(error);
            }
            setIsLoading(false);
        };
        fetchData();
    }, [params]);

    return {data, response, isError, isLoading};
}
