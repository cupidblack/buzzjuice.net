import FormContainer from '@p2p/Components/Admin/FormContainer';
import {Card, CardBody, CardFooter, StripedGroup} from '@p2p/Components/Card';
import {FieldRow, TextField} from '@p2p/Components/Form';
import {ArrowIcon, EmailIcon, UsernameIcon} from '@p2p/Components/Icons';
import styles from '@p2p/js/frontend/App/Screens/Login/styles.module.scss';
import {Button} from '@p2p/Components';
import {useForm} from 'react-hook-form';
import {useHistory} from 'react-router-dom';
import {setFormNavigationId, setFormNavigationStep} from '@p2p/js/admin/App/Teams/utils';
import API from '@p2p/js/api';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__} = wp.i18n;

const RegisterFundraiser = ({campaign}) => {
    setFormNavigationId('createFundraiser');
    setFormNavigationStep('1');

    const history = useHistory();

    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        watch,
        formState: {errors, isSubmitting},
    } = useForm();

    const onSubmit = (formData) => {
        const data = {
            ...formData,
            campaignId: campaign.campaign_id,
        };

        return API.post('/admin-add-wp-user', data)
            .then(({data}) => {
                const {userId} = data;

                history.push(`/create-fundraiser-${userId}`);
            })
            .catch((error) => {
                const {message} = error.response.data;
                setError('register', {message});
            });
    };

    return (
        <FormContainer
            title={__('Creating a new fundraiser', 'give-peer-to-peer')}
            teamImage={campaign.campaign_image}
            showStepNavigation={true}
        >
            <Card title={__('Register a new fundraiser', 'give-peer-to-peer')}>
                <form onSubmit={handleSubmit(onSubmit)}>
                    <StripedGroup>
                        <CardBody>
                            <p>{__('Create an account below to get started!', 'give-peer-to-peer')}</p>
                        </CardBody>

                        <CardBody style={{padding: '30px 25px'}}>
                            <FieldRow>
                                <TextField
                                    name="firstName"
                                    type="text"
                                    label={__('First Name', 'give-peer-to-peer')}
                                    icon={<UsernameIcon height={21} />}
                                    error={'firstName' in errors}
                                    {...register('firstName', {required: true})}
                                />
                                <TextField
                                    name="lastName"
                                    type="text"
                                    label={__('Last Name', 'give-peer-to-peer')}
                                    error={'lastName' in errors}
                                    {...register('lastName', {required: true})}
                                />
                            </FieldRow>

                            <FieldRow style={{marginTop: 12}}>
                                <TextField
                                    name="email"
                                    type="email"
                                    label={__('Email', 'give-peer-to-peer')}
                                    icon={<EmailIcon height={21} />}
                                    error={'email' in errors}
                                    {...register('email', {required: true})}
                                />
                            </FieldRow>
                        </CardBody>

                        <CardBody>
                            <Button
                                type="submit"
                                disabled={isSubmitting}
                                isLoading={isSubmitting}
                                onClick={() => clearErrors('register')}
                                iconAfter={ArrowIcon}
                            >
                                {__('Register', 'give-peer-to-peer')}
                            </Button>

                            {errors.register && <div className={styles.errorMessage}>{errors.register.message}</div>}
                        </CardBody>
                    </StripedGroup>

                    <CardFooter>
                        <SecureAndEncrypted />
                    </CardFooter>
                </form>
            </Card>
        </FormContainer>
    );
};

export default RegisterFundraiser;
