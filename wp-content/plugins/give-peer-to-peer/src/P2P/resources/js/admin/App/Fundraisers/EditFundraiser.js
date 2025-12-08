import {useHistory, useParams} from 'react-router-dom';
import {getEndpoint, useFetcher} from '@p2p/js/api';
// Components
import {ErrorNotice, LoadingNotice} from '@p2p/Components/Admin';
import {Card} from '@p2p/Components/Card';
import FormContainer from '@p2p/Components/Admin/FormContainer';
import {goBackToTeamsListPage} from '@p2p/js/admin/App/Teams/utils';
import FundraiserProfileForm from '@p2p/js/admin/App/Fundraisers/FundraiserProfileForm';

const {__, sprintf} = wp.i18n;

const EditFundraiser = ({campaign}) => {
    const {id: fundraiserId} = useParams();
    const history = useHistory();

    const {data: fundraiser, isLoading, isError} = useFetcher(getEndpoint('/get-fundraiser', {fundraiserId}), {
        revalidateOnFocus: false,
        onError: () => {
            setTimeout(() => {
                goBackToTeamsListPage(history);
            }, 3000);
        },
    });

    if (isLoading) {
        return <LoadingNotice notice={__('Loading team', 'give-peer-to-peer')}/>;
    }

    if (isError) {
        return (
            <ErrorNotice
                reload={false}
                notice={__('Unable to fetch the team. Check the logs for details.', 'give-peer-to-peer')}
            />
        );
    }

    return (
        <FormContainer
            title={sprintf(__('Editing %s', 'give-peer-to-peer'), fundraiser.fundraiser_name)}
            teamImage={campaign.campaign_image}
        >
            <Card title={__('Edit Fundraiser', 'give-peer-to-peer')}>
                <FundraiserProfileForm campaign={campaign} fundraiser={fundraiser}/>
            </Card>
        </FormContainer>
    );
};

export default EditFundraiser;
