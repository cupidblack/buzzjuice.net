import {Link, useHistory} from 'react-router-dom';
import {useForm} from 'react-hook-form';
import {useSelector} from '@p2p/js/frontend/App/store';
import API from '@p2p/js/api';
// Components
import {Button, Page} from '@p2p/Components';
import FormContainer from '@p2p/Components/FormContainer';
import {TextField} from '@p2p/Components/Form';
import {Card, CardBody, CardFooter, StripedGroup} from '@p2p/Components/Card';
import {UsernameIcon} from '@p2p/Components/Icons';

import styles from './styles.module.scss';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

const LostPassword = () => {
    const history = useHistory();
    const campaign = useSelector((state) => state.campaign);
    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        formState: {errors, isSubmitting},
    } = useForm();

    const onSubmit = (formData) => {
        const data = {
            ...formData,
            campaign_id: campaign.campaign_id,
        };

        return API.post('send-password-reset-email', data)
            .then(({data}) => {
                history.push(`/lost-password/sent`);
            })
            .catch((error) => {
                setError('login', {
                    message: error.response.data.message,
                });
            });
    };

    return (
        <Page title={sprintf(__('Log in to the %s', 'give-peer-to-peer'), campaign.campaign_title)}>
            <FormContainer
                title={[__('Login to the', 'give-peer-to-peer'), <br />, campaign.campaign_title]}
                image={campaign.campaign_image}
            >
                <Card title={__('Lost Password', 'give-peer-to-peer')}>
                    <form onSubmit={handleSubmit(onSubmit)}>
                        <StripedGroup>
                            <CardBody>
                                <p>
                                    {__('Please enter your username or email address.', 'give-peer-to-peer')}
                                    <br />
                                    {__(
                                        'You will receive an email message with instructions on how to reset your password.',
                                        'give-peer-to-peer'
                                    )}
                                </p>
                            </CardBody>

                            <CardBody style={{padding: 30}}>
                                <TextField
                                    icon={<UsernameIcon />}
                                    placeholder={__('Email or username', 'give-peer-to-peer')}
                                    name="user_handle"
                                    error={'user_handle' in errors}
                                    {...register('user_handle', {required: true})}
                                />
                            </CardBody>

                            <CardBody style={{padding: 30}}>
                                <Button
                                    type="submit"
                                    disabled={isSubmitting}
                                    isLoading={isSubmitting}
                                    onClick={() => clearErrors('login')}
                                >
                                    {__('Get New Password', 'give-peer-to-peer')}
                                </Button>

                                {errors.login && <div className={styles.errorMessage}>{errors.login.message}</div>}

                                <div className={styles.lostPassword}>
                                    <Link to="/login/">{__('Already have an account?', 'give-peer-to-peer')}</Link>
                                </div>
                            </CardBody>

                            <CardFooter>
                                <SecureAndEncrypted />
                            </CardFooter>
                        </StripedGroup>
                    </form>
                </Card>
            </FormContainer>
        </Page>
    );
};

export default LostPassword;
