import {Link} from 'react-router-dom';
import {useForm} from 'react-hook-form';
import {useSelector} from '@p2p/js/frontend/App/store';
import API from '@p2p/js/api';
// Components
import {Button, Page} from '@p2p/Components';
import FormContainer from '@p2p/Components/FormContainer';
import {FieldRow, TextField} from '@p2p/Components/Form';
import {Card, CardBody, CardFooter, StripedGroup} from '@p2p/Components/Card';
import {ArrowIcon, PasswordIcon, UsernameIcon} from '@p2p/Components/Icons';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

import styles from './styles.module.scss';

const {__, sprintf} = wp.i18n;

const Login = () => {
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

        return API.post('fundraiser-login', data)
            .then(({data}) => {
                /* previouslySelectedRegisterFormUrl is relative path of register form. */
                const previouslySelectedRegisterFormUrl = sessionStorage.getItem('registerFormChoice');

                // Clear data from session.
                sessionStorage.setItem('registerFormChoice', '');

                // We have to redirect the user in order to use the new wp_rest nonce
                location.href = previouslySelectedRegisterFormUrl
                    ? campaign.campaign_url + previouslySelectedRegisterFormUrl
                    : data.redirect;
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
                title={[__('Log in to the', 'give-peer-to-peer'), <br />, campaign.campaign_title]}
                image={campaign.campaign_image}
            >
                <Card title={__('Campaign Login', 'give-peer-to-peer')}>
                    <form onSubmit={handleSubmit(onSubmit)}>
                        <StripedGroup>
                            <CardBody>
                                <p>{__('Sign in below to access your profile.', 'give-peer-to-peer')}</p>
                            </CardBody>

                            <CardBody style={{padding: 30}}>
                                <FieldRow>
                                    <TextField
                                        icon={<UsernameIcon />}
                                        placeholder={__('Email or username', 'give-peer-to-peer')}
                                        name="user_handle"
                                        error={'user_handle' in errors}
                                        {...register('user_handle', {required: true})}
                                    />
                                </FieldRow>

                                <FieldRow style={{marginTop: '1.125rem'}}>
                                    <TextField
                                        icon={<PasswordIcon />}
                                        placeholder={__('Password', 'give-peer-to-peer')}
                                        name="password"
                                        type="password"
                                        error={'password' in errors}
                                        {...register('password', {required: true})}
                                    />
                                </FieldRow>
                            </CardBody>

                            <CardBody style={{paddingBottom: 30}}>
                                <Button
                                    type="submit"
                                    disabled={isSubmitting}
                                    isLoading={isSubmitting}
                                    onClick={() => clearErrors('login')}
                                    iconAfter={ArrowIcon}
                                >
                                    {__('Log in', 'give-peer-to-peer')}
                                </Button>

                                <div className={styles.lostPassword}>
                                    <span>{__('Forgot your password?', 'give-peer-to-peer')}</span>{' '}
                                    <Link to="/lost-password/">{__('Click here', 'give-peer-to-peer')}</Link>
                                </div>

                                {errors.login && <p className={styles.errorMessage}>{errors.login.message}</p>}
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

export default Login;
