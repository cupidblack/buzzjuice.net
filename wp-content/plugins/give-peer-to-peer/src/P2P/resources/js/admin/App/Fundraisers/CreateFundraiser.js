import {Card} from '@p2p/Components/Card';
import FundraiserProfileForm from '@p2p/js/admin/App/Fundraisers/FundraiserProfileForm';
import FormContainer from '@p2p/Components/Admin/FormContainer';
import {setFormNavigationId, setFormNavigationStep} from '@p2p/js/admin/App/Teams/utils';

const {__} = wp.i18n;

const CreateFundraiser = ({campaign}) => {
    setFormNavigationId('createFundraiser');
    setFormNavigationStep('2');

    return (
        <FormContainer
            title={__('Creating a new fundraiser', 'give-peer-to-peer')}
            teamImage={campaign.campaign_image}
            showStepNavigation={true}
        >
            <Card title={__('Create your fundraiser', 'give-peer-to-peer')} closeIcon={true}>
                <FundraiserProfileForm campaign={campaign} />
            </Card>
        </FormContainer>
    );
};

export default CreateFundraiser;
