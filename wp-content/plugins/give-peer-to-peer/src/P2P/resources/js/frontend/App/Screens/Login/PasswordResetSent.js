import {useSelector} from '@p2p/js/frontend/App/store';
// Components
import {Page} from '@p2p/Components';
import FormContainer from '@p2p/Components/FormContainer';
import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

const PasswordResetSent = () => {
    const campaign = useSelector((state) => state.campaign);

    const title = sprintf(__('Log in to the %s', 'give-peer-to-peer'), campaign.campaign_title);

    return (
        <Page title={title}>
            <FormContainer title={title} image={campaign.campaign_image}>
                <Card title={__('Lost Password', 'give-peer-to-peer')}>
                    <CardBody>
                        <p>{__('Check your email for the confirmation link.', 'give-peer-to-peer')}</p>
                    </CardBody>

                    <CardFooter>
                        <SecureAndEncrypted />
                    </CardFooter>
                </Card>
            </FormContainer>
        </Page>
    );
};

export default PasswordResetSent;
