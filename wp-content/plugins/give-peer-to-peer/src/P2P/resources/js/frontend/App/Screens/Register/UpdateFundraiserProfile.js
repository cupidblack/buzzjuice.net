import {useEffect, useState} from 'react';
import {useStore} from '@p2p/js/frontend/App/store';
import {setStep} from '@p2p/js/frontend/App/actions';
import {useForm} from 'react-hook-form';
import {canUseFundraiserEmailOption, getProp} from '@p2p/js/utils';
import API, {getEndpoint, useFetcher} from '@p2p/js/api';

// Components
import {Button, Page} from '@p2p/Components';
import FormContainer from '@p2p/Components/FormContainer';
import {FileUpload, MoneyField} from '@p2p/Components/Admin';
import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import {Checkbox, FieldRow, FieldRowLabel, TextareaField} from '@p2p/Components/Form';
import {ArrowIcon, ImageIcon} from '@p2p/Components/Icons';
import {SponsorGrid} from '@p2p/Components/SponsorGrid';

import styles from '../Login/styles.module.scss';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

const UpdateFundraiserProfile = ({redirect, history}) => {
    const [{campaign, fundraiser, navigation}, dispatch] = useStore();

    const {data: emails, isLoading, isError} = useFetcher(getEndpoint('/get-email-settings'));

    useEffect(() => {
        const step = sessionStorage.getItem('p2p-navigation-set') === 'joinIndividual' ? 2 : 3;
        dispatch(setStep(step));
    }, []);

    const [state, setState] = useState({
        file: null,
        file_name: '',
        maxSizeError: false,
    });

    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        formState: {errors, isSubmitting},
    } = useForm({
        defaultValues: {
            story: fundraiser.story ?? campaign.fundraiser_story_placeholder,
            notify_of_donations: fundraiser.notify_of_donations,
        },
    });

    const handleSelectedFile = (files) => {
        const file = files[0];
        const maxSize = parseInt(getProp('maxUploadSize'));
        const isError = file.size > maxSize;

        setState((previousState) => {
            return {
                ...previousState,
                file,
                file_name: file.name,
                maxSizeError: isError,
            };
        });
    };

    const handleDrop = (e) => {
        e.preventDefault();

        if (e.dataTransfer.items) {
            const file = e.dataTransfer.items[0].getAsFile();

            if (file.type.split('/')[0] === 'image') {
                handleSelectedFile([file]);
            }
        }
    };

    const handleClick = () => {
        dispatch(setStep(navigation.currentStep + 1));
        clearErrors('register');
    };

    const isSaveDisabled = () => isSubmitting || state.maxSizeError;

    const onSubmit = (formData) => {
        const data = new FormData();

        data.append('action', 'wp_handle_upload');
        data.append('campaignId', campaign.campaign_id);
        data.append('goal', formData.goal);
        data.append('story', formData.story);
        data.append('file', state.file);

        canUseFundraiserEmailOption(emails, fundraiser?.team_id) &&
            data.append('notify_of_donations', formData.notify_of_donations);

        return API.post('update-fundraiser-profile', data)
            .then(({data}) => {
                if (redirect) {
                    return redirect(history);
                }
                location.href = `${campaign.campaign_url}/fundraiser/${fundraiser.id}`;
            })
            .catch((error) => {
                setError('register', {
                    message: error.response.data.message,
                });
            });
    };

    return (
        <Page title={sprintf(__('Start fundraising for the %s', 'give-peer-to-peer'), campaign.campaign_title)}>
            <FormContainer
                title={[__('Start fundraising for the', 'give-peer-to-peer'), <br />, campaign.campaign_title]}
                image={campaign.campaign_image}
                showStepNavigation={!fundraiser?.id}
            >
                <Card
                    title={
                        !fundraiser.id
                            ? __('Create your Fundraiser Profile', 'give-peer-to-peer')
                            : __('Edit your Fundraiser Profile', 'give-peer-to-peer')
                    }
                    closeIcon={fundraiser.id && true}
                >
                    <form onSubmit={handleSubmit(onSubmit)}>
                        <CardBody>
                            <FieldRowLabel
                                label={__('Why are you fundraising?', 'give-peer-to-peer')}
                                description={__(
                                    'In a few sentences, why are you helping support this fundraiser?',
                                    'give-peer-to-peer'
                                )}
                                required
                            />
                            <FieldRow>
                                <TextareaField
                                    name="story"
                                    rows={10}
                                    error={'story' in errors}
                                    {...register('story', {required: true})}
                                />
                            </FieldRow>

                            <FieldRowLabel
                                label={__('Profile image', 'give-peer-to-peer')}
                                description={__(
                                    'Upload an image for your profile. Accepted formats are PNG and JPG. For best results use a 250 x 250 pixel image size.',
                                    'give-peer-to-peer'
                                )}
                            />

                            <FieldRow>
                                <FileUpload accept=".jpg,.jpeg,.png" onChange={handleSelectedFile} onDrop={handleDrop}>
                                    <div className={styles.uploadDescription}>
                                        <div className={styles.uploadIconContainer}>
                                            <ImageIcon width={24} height={19} className={styles.icon} />
                                            {__('UPLOAD AN IMAGE', 'give-peer-to-peer')}
                                        </div>

                                        {state.maxSizeError ? (
                                            <div className={styles.maxSizeError}>
                                                {state.file_name}{' '}
                                                {__(
                                                    'exceeds the maximum upload size for this site',
                                                    'give-peer-to-peer'
                                                )}
                                            </div>
                                        ) : (
                                            <div>
                                                {state.file_name && (
                                                    <div className={styles.selectedFile}>{state.file_name}</div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </FileUpload>
                            </FieldRow>

                            <FieldRowLabel label={__('Personal fundraising goal', 'give-peer-to-peer')} required />
                            <FieldRow>
                                <MoneyField
                                    name="goal"
                                    defaultAmount={
                                        fundraiser.fundraiser_goal
                                            ? Number(fundraiser.fundraiser_goal)
                                            : Number(campaign.fundraiser_goal)
                                    }
                                    error={'goal' in errors}
                                    {...register('goal', {required: true})}
                                />
                            </FieldRow>
                        </CardBody>

                        <CardBody>
                            {canUseFundraiserEmailOption(emails, fundraiser?.team_id) && (
                                <FieldRow>
                                    <Checkbox
                                        name="notify_of_donations"
                                        type="checkbox"
                                        label={__(
                                            'I would like to be notified of new donations given through my fundraising page.',
                                            'give-peer-to-peer'
                                        )}
                                        defaultChecked={fundraiser?.notify_of_donations ?? true}
                                        error={'notify_of_donations' in errors}
                                        {...register('notify_of_donations', {required: false})}
                                    />
                                </FieldRow>
                            )}

                            <Button
                                type="submit"
                                disabled={isSaveDisabled()}
                                isLoading={isSubmitting}
                                onClick={handleClick}
                                iconAfter={ArrowIcon}
                            >
                                {fundraiser.id
                                    ? __('Update Profile', 'give-peer-to-peer')
                                    : __('Create Profile', 'give-peer-to-peer')}
                            </Button>

                            {errors.register && <div className={styles.errorMessage}>{errors.register.message}</div>}
                        </CardBody>

                        <CardFooter>
                            <SecureAndEncrypted />
                        </CardFooter>
                    </form>
                </Card>
            </FormContainer>
            {'enabled' === campaign.sponsors_enabled && <SponsorGrid />}F
        </Page>
    );
};

export default UpdateFundraiserProfile;
