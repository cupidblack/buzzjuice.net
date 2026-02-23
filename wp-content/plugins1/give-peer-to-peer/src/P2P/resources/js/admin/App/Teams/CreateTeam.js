import TeamProfileForm from '@p2p/js/admin/App/Teams/TeamProfileForm';
import FormContainer from '@p2p/Components/Admin/FormContainer';
import {Card} from '@p2p/Components/Card';
import {setFormNavigationId, setFormNavigationStep} from '@p2p/js/admin/App/Teams/utils';
import {useHistory} from 'react-router-dom';

const {__} = wp.i18n;

const CreateTeam = ({campaign}) => {
    const history = useHistory();

    setFormNavigationId('createTeam');
    setFormNavigationStep('1');

    return (
        <FormContainer
            title={__('Creating a new team', 'give-peer-to-peer')}
            teamImage={campaign.campaign_image}
            showStepNavigation
        >
            <Card title={__('Create Your Team', 'give-peer-to-peer')}>
                <TeamProfileForm campaign={campaign} />
            </Card>
        </FormContainer>
    );
};

export default CreateTeam;
