import {useHistory, useParams} from 'react-router-dom';
import API, {getEndpoint, useFetcher} from '@p2p/js/api';
// Components
import {ErrorNotice, FileUpload, LoadingNotice, MoneyField} from '@p2p/Components/Admin';
import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import {
    getAllApprovedTeamMembers,
    getTeam,
    isCreatingTeam,
    setFormNavigationIdAndProcessStep,
} from '@p2p/js/admin/App/Teams/utils';
import {Checkbox, FieldRow, FieldRowLabel, SelectField, TextareaField, TextField} from '@p2p/Components/Form';
import {ArrowIcon, EmailIcon, ImageIcon, UsernameIcon} from '@p2p/Components/Icons';
import styles from '@p2p/js/admin/App/Teams/styles.module.scss';
import {Button} from '@p2p/Components';
import {useForm} from 'react-hook-form';
import {useState} from 'react';
import {getProp} from '@p2p/js/utils';
import FormContainer from '@p2p/Components/Admin/FormContainer';
import {mutate} from 'swr';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

const TeamCaptainForm = ({campaign}) => {
    const {id: team_id} = useParams();
    const history = useHistory();
    const [state, setState] = useState({
        updated: false,
        file: null,
        file_url: '',
        file_name: '',
        canShowCreatingNewTeamCaptainFormField: true,
        canShowChooseCaptainFromTeamMembersFormField: false,
    });

    const {data: team, isLoading: isTeamLoading, isError: isTeamRequestError} = getTeam(team_id);

    const {data: emails} = useFetcher(getEndpoint('/get-email-settings'));

    const {
        data: eligibleFundraisersFormAsTeamOwnerOptions,
        isLoading: isTeamMembersLoading,
        isError: isTeamMembersRequestError,
    } = getAllApprovedTeamMembers(campaign.campaign_id, team_id, (result) => {
        const {data} = result;

        setState((prevState) => {
            return {
                ...prevState,
                canShowCreatingNewTeamCaptainFormField: data === undefined || !data.length,
                canShowChooseCaptainFromTeamMembersFormField: Array.isArray(data) && !!data.length,
            };
        });
    });

    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        watch,
        formState: {errors, isSubmitting},
    } = useForm({});

    const onSubmit = (formData) => {
        let route, data;

        if (state.canShowCreatingNewTeamCaptainFormField) {
            route = 'admin-add-team-captain';

            data = {
                campaignId: campaign.campaign_id,
                teamId: team.id,
                file: state.file,
                file_url: state.file_url,
                ...formData,
            };
        } else {
            const {captain} = formData;
            route = 'admin-update-team-captain';

            data = {
                campaignId: campaign.campaign_id,
                teamId: team.id,
                captain,
            };
        }

        return API.post(route, data)
            .then(({data}) => {
                setState((prevState) => {
                    return {
                        ...prevState,
                        updated: true,
                    };
                });

                // invalidate cache
                mutate(getEndpoint('/get-team', {team_id: team.id}));

                setTimeout(() => {
                    setState((previousState) => {
                        return {
                            ...previousState,
                            updated: false,
                        };
                    });

                    isCreatingTeam() ? history.push(`/invite-team-members-${team.id}`) : history.push(`/`);
                }, 500);
            })
            .catch((error) => {
                const {message} = error.response.data;
                setError('register', {message});
            });
    };

    const handleSelectedFile = (files) => {
        const file = files[0];
        const maxSize = parseInt(getProp('maxUploadSize'));
        const maxSizeError = file.size > maxSize;

        setState((previousState) => {
            return {
                ...previousState,
                file,
                file_url: '',
                file_name: file.name,
                maxSizeError,
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

    const openMediaLibrary = (e) => {
        e.preventDefault();

        const mediaLibrary = wp.media({
            library: {
                type: ['image'],
            },
        });

        mediaLibrary.on('select', function () {
            const attachment = mediaLibrary.state().get('selection').first().toJSON();

            setState((previousState) => {
                return {
                    ...previousState,
                    file: null,
                    file_name: attachment.filename,
                    file_url: attachment.url,
                };
            });
        });

        mediaLibrary.open();
    };

    const handleTeamCaptainTypeChoice = (teamCaptainType) => {
        setState((prevState) => {
            return {
                ...prevState,
                canShowCreatingNewTeamCaptainFormField: teamCaptainType === 'create-team-captain',
                canShowChooseCaptainFromTeamMembersFormField: teamCaptainType === 'choose-captain-from-team-members',
            };
        });
    };

    if (isTeamLoading) {
        return <LoadingNotice notice={__('Loading team', 'give-peer-to-peer')} />;
    }

    if (isTeamRequestError) {
        return (
            <ErrorNotice
                reload={false}
                notice={__('Unable to fetch the team. Check the logs for details.', 'give-peer-to-peer')}
            />
        );
    }

    if (isTeamMembersLoading) {
        return <LoadingNotice notice={__("Loading fundraiser's list", 'give-peer-to-peer')} />;
    }

    if (isTeamMembersRequestError) {
        return (
            <ErrorNotice
                reload={false}
                notice={__("Unable to fetch the fundraiser's list. Check the logs for details.", 'give-peer-to-peer')}
            />
        );
    }

    setFormNavigationIdAndProcessStep('2');

    return (
        <FormContainer
            title={sprintf(__('Creating a new team', 'give-peer-to-peer'))}
            teamImage={campaign.campaign_image}
            showStepNavigation={isCreatingTeam()}
        >
            <Card title={__('Register Team Captain', 'give-peer-to-peer')}>
                <form onSubmit={handleSubmit(onSubmit)}>
                    <CardBody style={{padding: '20px 20px 0 20px', display: 'grid', gap: '10px'}}>
                        {Array.isArray(eligibleFundraisersFormAsTeamOwnerOptions) &&
                            !!eligibleFundraisersFormAsTeamOwnerOptions.length && (
                                <div style={{paddingBottom: '20px'}}>
                                    <FieldRowLabel
                                        label={__('Team Captain', 'give-peer-to-peer')}
                                        description={__(
                                            'Select whether you would like to register a new user as a team captain or select from existing team members.',
                                            'give-peer-to-peer'
                                        )}
                                    />
                                    <FieldRow style={{flexDirection: 'column'}}>
                                        <label className={styles.radioInput}>
                                            <input
                                                type="radio"
                                                name="team-captain-type"
                                                value="create-team-captain"
                                                defaultChecked={state.canShowCreatingNewTeamCaptainFormField}
                                                onClick={() => handleTeamCaptainTypeChoice('create-team-captain')}
                                                {...register('team-captain-type')}
                                            />
                                            {__('Create New Captain', 'give-peer-to-peer')}
                                        </label>

                                        <label className={styles.radioInput}>
                                            <input
                                                type="radio"
                                                name="team-captain-type"
                                                value="choose-captain-from-team-members"
                                                defaultChecked={state.canShowChooseCaptainFromTeamMembersFormField}
                                                onClick={() =>
                                                    handleTeamCaptainTypeChoice('choose-captain-from-team-members')
                                                }
                                                {...register('team-captain-type')}
                                            />{' '}
                                            {__('Existing team member', 'give-peer-to-peer')}
                                        </label>
                                    </FieldRow>
                                </div>
                            )}

                        {state.canShowCreatingNewTeamCaptainFormField && (
                            <>
                                <FieldRowLabel
                                    label={__('Personal Information', 'give-peer-to-peer')}
                                    description={__(
                                        'Your personal details will be added to your profile.',
                                        'give-peer-to-peer'
                                    )}
                                    required
                                />
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
                            </>
                        )}

                        {state.canShowCreatingNewTeamCaptainFormField && (
                            <FieldRow>
                                <TextField
                                    name="email"
                                    type="email"
                                    label={__('Email', 'give-peer-to-peer')}
                                    icon={<EmailIcon height={21} />}
                                    error={'email' in errors}
                                    {...register('email', {required: true})}
                                />
                            </FieldRow>
                        )}

                        {state.canShowCreatingNewTeamCaptainFormField && (
                            <>
                                <FieldRowLabel
                                    label={__('Why are you fundraising?', 'give-peer-to-peer')}
                                    description={__(
                                        'In a few sentences, why are you helping support this campaign as a team captain?',
                                        'give-peer-to-peer'
                                    )}
                                    required
                                />
                                <FieldRow>
                                    <TextareaField
                                        name="story"
                                        rows={4}
                                        error={'story' in errors}
                                        {...register('story', {required: true})}
                                    />
                                </FieldRow>
                            </>
                        )}

                        {state.canShowCreatingNewTeamCaptainFormField && (
                            <>
                                <FieldRowLabel
                                    label={__('Profile image', 'give-peer-to-peer')}
                                    description={__(
                                        'Upload an image for your profile. Accepted formats are PNG and JPG. For best results use a 250 x 250 pixel image size.',
                                        'give-peer-to-peer'
                                    )}
                                />

                                <FieldRow>
                                    <FileUpload accept=".jpg,.jpeg,.png" onClick={openMediaLibrary} onDrop={handleDrop}>
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
                            </>
                        )}

                        {state.canShowCreatingNewTeamCaptainFormField && (
                            <>
                                <FieldRowLabel label={__('Personal fundraising goal', 'give-peer-to-peer')} required />
                                <FieldRow style={{marginTop: '-0.8725rem'}}>
                                    <MoneyField
                                        name="goal"
                                        defaultAmount={Number(campaign.fundraiser_goal)}
                                        error={'goal' in errors}
                                        {...register('goal', {required: true})}
                                    />
                                </FieldRow>
                            </>
                        )}

                        {state.canShowChooseCaptainFromTeamMembersFormField && (
                            <FieldRow>
                                <SelectField
                                    name="captain"
                                    label={__('Select team captain', 'give-peer-to-peer')}
                                    {...register('captain', {required: true})}
                                    error={errors.hasOwnProperty('captain')}
                                    options={eligibleFundraisersFormAsTeamOwnerOptions}
                                    defaultValue={team.owner_id}
                                />
                            </FieldRow>
                        )}

                        {emails?.donation_individual_fundraiser && state.canShowCreatingNewTeamCaptainFormField && (
                            <>
                                <FieldRow>
                                    <Checkbox
                                        name="notify_of_donations"
                                        type="checkbox"
                                        defaultChecked={true}
                                        label={__(
                                            'I would like to be notified of new donations given through my fundraising page.',
                                            'give-peer-to-peer'
                                        )}
                                        error={'notify_of_donations' in errors}
                                        {...register('notify_of_donations', {required: false})}
                                    />
                                </FieldRow>
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
                        {errors.register && <div className={styles.errorMessage}>{errors.register.message}</div>}
                    </CardBody>

                    <CardFooter>
                        <SecureAndEncrypted />
                    </CardFooter>
                </form>
            </Card>
        </FormContainer>
    );
};

export default TeamCaptainForm;
