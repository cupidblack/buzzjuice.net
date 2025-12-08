import {useHistory, useParams} from 'react-router-dom';
import {getEndpoint, useFetcher} from '@p2p/js/api';
// Components
import {ErrorNotice, LoadingNotice} from '@p2p/Components/Admin';
import {Card} from '@p2p/Components/Card';
import FormContainer from '@p2p/Components/Admin/FormContainer';
import {goBackToTeamsListPage, setFormNavigationId, setFormNavigationStep} from '@p2p/js/admin/App/Teams/utils';
import TeamProfileForm from '@p2p/js/admin/App/Teams/TeamProfileForm';

const {__, sprintf} = wp.i18n;

const EditTeam = ({campaign}) => {
    const {id: team_id} = useParams();
    const history = useHistory();

    const {data, isLoading, isError} = useFetcher(getEndpoint('/get-team', {team_id}), {
        revalidateOnFocus: false,
        onError: () => {
            setTimeout(() => {
                goBackToTeamsListPage(history);
            }, 3000);
        },
    });

    if (isLoading) {
        return <LoadingNotice notice={__('Loading team', 'give-peer-to-peer')} />;
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
            title={sprintf(__('Editing %s', 'give-peer-to-peer'), data.name)}
            teamImage={campaign.campaign_image}
        >
            <Card title={__('Edit Team', 'give-peer-to-peer')}>
                <TeamProfileForm campaign={campaign} team={data} />
            </Card>
        </FormContainer>
    );
};

export default EditTeam;
