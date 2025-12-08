import {useEffect, useState} from 'react';
import {useStore} from '@p2p/js/frontend/App/store';
import {setStep} from '@p2p/js/frontend/App/actions';
import {useForm} from 'react-hook-form';
import API, {getEndpoint, useFetcher} from '@p2p/js/api';
import {getProp} from '@p2p/js/utils';
// Components
import {Button, Page} from '@p2p/Components';
import {FileUpload, MoneyField, MultiEmailSelect} from '@p2p/Components/Admin';
import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import {Checkbox, FieldRow, FieldRowLabel, TextareaField, TextField} from '@p2p/Components/Form';
import {ImageIcon} from '@p2p/Components/Icons';
import FormContainer from '@p2p/Components/FormContainer';
import {SponsorGrid} from '@p2p/Components/SponsorGrid';

import styles from '@p2p/js/admin/App/Teams/styles.module.scss';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

/**
 * @since 1.6.0 Updated to include error prop for MoneyField Component.
 */
const CreateOrUpdateTeam = () => {
    const [{team, campaign, auth}, dispatch] = useStore();

    const {data: emails} = useFetcher(getEndpoint('/get-email-settings'));

    useEffect(() => {
        dispatch(setStep(2));
    }, []);

    const [state, setState] = useState({
        emails: [],
        file: null,
        file_name: '',
        maxSizeError: false,
        saving: false,
    });

    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        formState: {errors, isSubmitting},
    } = useForm({
        defaultValues: {
            teamName: team?.name ?? '',
            story: team?.story ?? campaign.team_story_placeholder,
            access: team?.access ?? 'public',
            notify_of_fundraisers: team?.notify_of_fundraisers ?? true,
            notify_of_team_donations: team?.notify_of_team_donations ?? true,
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

    const isSaveDisabled = () => isSubmitting || state.maxSizeError;

    const onSubmit = (formData) => {
        setState((previousState) => {
            return {
                ...previousState,
                saving: true,
            };
        });

        const data = new FormData();

        data.append('action', 'wp_handle_upload');
        data.append('campaign_id', campaign.campaign_id);
        data.append('name', formData.teamName);
        data.append('goal', formData.goal);
        data.append('story', formData.story);
        data.append('access', formData.access);
        data.append('emails', state.emails);
        data.append('file', state.file);

        emails?.team_fundraiser_joined && data.append('notify_of_fundraisers', formData.notify_of_fundraisers);
        emails?.donation_team_captain && data.append('notify_of_team_donations', formData.notify_of_team_donations);

        if (team && team.id) {
            data.append('team_id', team.id);
        }

        return API.post(team && team.id ? 'update-team' : 'create-team', data)
            .then((response) => {
                // Async request to send invitation emails.
                const teamID = response.data.teamId ?? team.id;
                API.post('send-team-invitation-emails', {team_id: teamID});

                // Then redirect to view team profile.
                if (team && team.id) {
                    location.href = campaign.campaign_url + '/team/' + team.id;
                } else {
                    location.href =
                        campaign.campaign_url +
                        (auth.fundraiser_id ? '/register/create-profile/' : response.data.redirect);
                }
            })
            .catch((error) => {
                setState((previousState) => {
                    return {
                        ...previousState,
                        saving: false,
                    };
                });

                setError('register', {
                    message: error.response.data.message,
                });
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

    return (
        <Page title={sprintf(__('Start fundraising for the %s', 'give-peer-to-peer'), campaign.campaign_title)}>
            <FormContainer
                title={[__('Start fundraising for the', 'give-peer-to-peer'), <br />, campaign.campaign_title]}
                image={campaign.campaign_image}
                showStepNavigation={!team?.id && !auth.fundraiser_id}
            >
                <Card
                    title={team?.id ? __('Edit Team', 'give-peer-to-peer') : __('Create Team', 'give-peer-to-peer')}
                    closeIcon={team?.id && true}
                >
                    <form onSubmit={handleSubmit(onSubmit)}>
                        <CardBody>
                            <FieldRowLabel
                                label={__('Team name', 'give-peer-to-peer')}
                                description={__(
                                    'Please provide a name for your team. This name will be displayed on the leaderboards and on your team profile page.',
                                    'give-peer-to-peer'
                                )}
                                required
                            />

                            <FieldRow>
                                <TextField
                                    name="name"
                                    label={__('Team Name', 'give-peer-to-peer')}
                                    {...register('teamName', {required: true})}
                                />
                            </FieldRow>
                        </CardBody>

                        <CardBody>
                            <FieldRowLabel
                                label={__('Team story', 'give-peer-to-peer')}
                                description={__(
                                    'Why are you fundraising? Describe in a few short paragraphs why you’re fundraising. Personal stories inspire others to join your team and help the cause!',
                                    'give-peer-to-peer'
                                )}
                                required
                            />

                            <FieldRow>
                                <TextareaField
                                    name="story"
                                    rows={10}
                                    label={__('Team Story', 'give-peer-to-peer')}
                                    {...register('story', {required: true})}
                                />
                            </FieldRow>
                        </CardBody>

                        <CardBody>
                            <FieldRowLabel
                                label={__('Team profile image', 'give-peer-to-peer')}
                                description={__(
                                    'Upload an image for your team. Accepted formats are PNG and JPG. For best results use a 250 x 250 pixel image size.',
                                    'give-peer-to-peer'
                                )}
                            />

                            <FieldRow>
                                <FileUpload
                                    accept=".jpg,.jpeg,.png"
                                    onChange={handleSelectedFile}
                                    onDrop={handleDrop}
                                    primaryColor={campaign.primary_color}
                                >
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
                        </CardBody>

                        <CardBody>
                            <FieldRowLabel label={__('Team fundraising goal', 'give-peer-to-peer')} required />

                            <FieldRow>
                                <MoneyField
                                    defaultAmount={team && team.goal ? Number(team.goal) : Number(campaign.team_goal)}
                                    error={'goal' in errors}
                                    {...register('goal', {required: true})}
                                />
                            </FieldRow>
                        </CardBody>

                        <CardBody>
                            <FieldRowLabel
                                label={__('Allow others to join team', 'give-peer-to-peer')}
                                description={__(
                                    'Select whether you would like anyone to be able to join your team or have it as an invite-only team.',
                                    'give-peer-to-peer'
                                )}
                            />

                            <FieldRow style={{flexDirection: 'column'}}>
                                <label className={styles.radioInput}>
                                    <input
                                        type="radio"
                                        name="access"
                                        value="public"
                                        defaultChecked={true}
                                        {...register('access')}
                                    />{' '}
                                    {__('Public - anyone can join', 'give-peer-to-peer')}
                                </label>

                                <label className={styles.radioInput}>
                                    <input type="radio" name="access" value="private" {...register('access')} />{' '}
                                    {__('Closed - only those you invite can join', 'give-peer-to-peer')}
                                </label>
                            </FieldRow>
                        </CardBody>

                        <CardBody>
                            <FieldRowLabel
                                label={__('Invite team members', 'give-peer-to-peer')}
                                description={__(
                                    'Add team members’ emails below (separated by commas) to send a welcome email to join the team.',
                                    'give-peer-to-peer'
                                )}
                            />

                            <FieldRow>
                                <MultiEmailSelect
                                    onUpdate={(emails) => {
                                        setState((previousState) => {
                                            return {
                                                ...previousState,
                                                emails: emails,
                                            };
                                        });
                                    }}
                                />
                            </FieldRow>
                        </CardBody>
                        <CardBody>
                            {emails?.donation_team_captain && (
                                <FieldRow>
                                    <Checkbox
                                        name="notify_of_team_donations"
                                        type="checkbox"
                                        label={__(
                                            "I would like to be notified of new donations given through my team/'s page.",
                                            'give-peer-to-peer'
                                        )}
                                        defaultChecked={team?.notify_of_team_donations}
                                        error={'notify_of_team_donations' in errors}
                                        {...register('notify_of_team_donations', {required: false})}
                                    />
                                </FieldRow>
                            )}

                            {emails?.team_fundraiser_joined && (
                                <FieldRow>
                                    <Checkbox
                                        name="notify_of_fundraisers"
                                        type="checkbox"
                                        label={__(
                                            'I would like to be notified when a fundraiser has joined my team.',
                                            'give-peer-to-peer'
                                        )}
                                        defaultChecked={team?.notify_of_fundraisers}
                                        error={'notify_of_fundraisers' in errors}
                                        {...register('notify_of_fundraisers', {required: false})}
                                    />
                                </FieldRow>
                            )}
                        </CardBody>

                        <CardBody>
                            <div className={styles.button}>
                                <Button
                                    type="submit"
                                    disabled={isSaveDisabled()}
                                    isLoading={state.saving}
                                    onClick={() => clearErrors('register')}
                                >
                                    {team?.id
                                        ? __('Update Team', 'give-peer-to-peer')
                                        : __('Create Team', 'give-peer-to-peer')}
                                </Button>
                            </div>

                            {state.updated && (
                                <div className={styles.successMessage}>
                                    {team.id
                                        ? __('Team updated', 'give-peer-to-peer')
                                        : __('Team created', 'give-peer-to-peer')}
                                </div>
                            )}

                            {state.updateError && <div className={styles.errorMessage}>{state.updateErrorMessage}</div>}
                        </CardBody>
                        <CardFooter>
                            <SecureAndEncrypted />
                        </CardFooter>
                    </form>
                </Card>
            </FormContainer>
            {'enabled' === campaign.sponsors_enabled && <SponsorGrid />}
        </Page>
    );
};

export default CreateOrUpdateTeam;
