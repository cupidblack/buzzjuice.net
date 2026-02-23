import {__} from '@wordpress/i18n';
import useAsyncFetch from '@p2p/js/blocks/hooks/useAsyncFetch';

export default function useCampaigns() {
    const endpoint = '/get-campaigns';

    const parameters = {
        page: '1',
        per_page: '50',
        sort: '',
        direction: '',
        status: 'all',
    };

    const campaigns = useAsyncFetch(endpoint, parameters);

    const {data, isLoading} = campaigns;

    const campaignOptions =
        !isLoading && data && data?.length > 0
            ? data?.map(({campaign_id, campaign_title}) => {
                  return {
                      label: __(campaign_title, 'give-peer-to-peer'),
                      value: String(campaign_id),
                  };
              })
            : [];

    return {campaignOptions, isLoading};
}
