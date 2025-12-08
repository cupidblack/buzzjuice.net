import {useEffect} from 'react';
import {useStore} from '@p2p/js/frontend/App/store';
import {setStep} from '@p2p/js/frontend/App/actions';
import {useForm} from 'react-hook-form';
import API, {getEndpoint, useFetcher} from '@p2p/js/api';

// Components
import {Button, Page} from '@p2p/Components';
import FormContainer from '@p2p/Components/FormContainer';
import {Card, CardBody, CardFooter, StripedGroup} from '@p2p/Components/Card';
import {FieldRow, TextField} from '@p2p/Components/Form';
import {ArrowIcon, EmailIcon, PasswordIcon, UsernameIcon} from '@p2p/Components/Icons';
import Spinner from '@p2p/Components/Admin/Spinner';
import {SponsorGrid} from '@p2p/Components/SponsorGrid';

import styles from '../Login/styles.module.scss';
import SignInPill from '@p2p/Components/SignInPill';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

/**
 * @since 1.6.0 Updated to remove dom element when Signinpill component is not visible.
 */
const RegisterFundraiser = ({isCaptain}) => {
    const [{campaign, auth}, dispatch] = useStore();
    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        watch,
        formState: {errors, isSubmitting},
    } = useForm();
    const isRegistered = !!(auth.user_id && !auth.fundraiser_id);

    useEffect(() => {
        sessionStorage.setItem('p2p-navigation-set', isCaptain ? 'createTeam' : 'joinIndividual');
        dispatch(setStep(1));
    }, []);

    const {data, isLoading, isError} = useFetcher(
        isRegistered ? getEndpoint('/get-user-info', {campaignId: campaign.campaign_id}) : null
    );

    const currentPassword = watch('password', '');

    const onSubmit = (formData) => {
        const data = {
            ...formData,
            team_captain: isCaptain,
            campaign_id: campaign.campaign_id,
        };

        const endpoint = isRegistered ? '/create-fundraiser-profile' : '/register-fundraiser';

        return API.post(endpoint, data)
            .then(({data}) => {
                location.href = campaign.campaign_url + data.redirect;
            })
            .catch((error) => {
                const {message} = error.response.data;
                setError('register', {message});
            });
    };

    return (
        <Page title={sprintf(__('Start fundraising for %s', 'give-peer-to-peer'), campaign.campaign_title)}>
            <FormContainer
                title={[__('Start fundraising for', 'give-peer-to-peer'), <br />, campaign.campaign_title]}
                image={campaign.campaign_image}
                showStepNavigation={true}
            >
                {isRegistered && isLoading ? (
                    <Spinner size="large" />
                ) : (
                    <Card
                        title={
                            isCaptain
                                ? __('Register as a Team Captain', 'give-peer-to-peer')
                                : __('Register as an Individual', 'give-peer-to-peer')
                        }
                    >
                        <form onSubmit={handleSubmit(onSubmit)}>
                            <StripedGroup>
                                <CardBody>
                                    <p>{__('Create an account below to get started!', 'give-peer-to-peer')}</p>
                                </CardBody>

                                <CardBody style={{padding: '30px 25px', display: 'grid', gap: '1rem'}}>
                                    <FieldRow>
                                        <TextField
                                            name="firstName"
                                            type="text"
                                            hiddenLabel
                                            label={__('First Name', 'give-peer-to-peer')}
                                            icon={<UsernameIcon />}
                                            error={'firstName' in errors}
                                            defaultValue={data?.firstName}
                                            {...register('firstName', {required: true})}
                                        />

                                        <TextField
                                            name="lastName"
                                            type="text"
                                            hiddenLabel
                                            label={__('Last Name', 'give-peer-to-peer')}
                                            error={'lastName' in errors}
                                            defaultValue={data?.lastName}
                                            {...register('lastName', {required: true})}
                                        />
                                    </FieldRow>

                                    <FieldRow>
                                        <TextField
                                            name="email"
                                            type="email"
                                            hiddenLabel
                                            label={__('Email', 'give-peer-to-peer')}
                                            icon={<EmailIcon />}
                                            readOnly={isRegistered}
                                            error={'email' in errors}
                                            defaultValue={data?.email}
                                            {...register('email', {required: true})}
                                        />
                                    </FieldRow>

                                    {!isRegistered && (
                                        <>
                                            <FieldRow>
                                                <TextField
                                                    name="password"
                                                    type="password"
                                                    hiddenLabel
                                                    label={__('Password', 'give-peer-to-peer')}
                                                    icon={<PasswordIcon />}
                                                    error={'password' in errors}
                                                    {...register('password', {
                                                        required: true,
                                                        minLength: {
                                                            value: 6,
                                                            message: __(
                                                                'Password must have at least 6 characters',
                                                                'give-peer-to-peer'
                                                            ),
                                                        },
                                                    })}
                                                />
                                            </FieldRow>

                                            {errors.password && (
                                                <FieldRow>
                                                    <div className={styles.errorMessage}>{errors.password.message}</div>
                                                </FieldRow>
                                            )}

                                            <FieldRow>
                                                <TextField
                                                    name="password2"
                                                    type="password"
                                                    hiddenLabel
                                                    label={__('Confirm Password', 'give-peer-to-peer')}
                                                    icon={<PasswordIcon />}
                                                    error={'password2' in errors}
                                                    {...register('password2', {
                                                        required: true,
                                                        validate: (value) =>
                                                            value === currentPassword ||
                                                            __('The passwords do not match', 'give-peer-to-peer'),
                                                    })}
                                                />
                                            </FieldRow>

                                            {errors.password2 && (
                                                <FieldRow>
                                                    <div className={styles.errorMessage}>
                                                        {errors.password2.message}
                                                    </div>
                                                </FieldRow>
                                            )}
                                        </>
                                    )}
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

                                    {!isRegistered && <SignInPill />}
                                </CardBody>
                            </StripedGroup>

                            {errors.register && <div className={styles.errorMessage}>{errors.register.message}</div>}

                            <CardFooter>
                                <SecureAndEncrypted />
                            </CardFooter>
                        </form>
                    </Card>
                )}
            </FormContainer>
            {'enabled' === campaign.sponsors_enabled && <SponsorGrid />}
        </Page>
    );
};

export default RegisterFundraiser;
