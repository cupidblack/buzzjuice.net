import {useHistory} from 'react-router-dom';
import {useState} from 'react';
import {getProp} from '@p2p/js/utils';
import API, {getEndpoint, useFetcher} from '@p2p/js/api';
import {FileUpload, MoneyField} from '@p2p/Components/Admin';
import {CardBody, CardFooter} from '@p2p/Components/Card';
import styles from '@p2p/js/admin/App/Teams/styles.module.scss';
import {Checkbox, FieldRow, FieldRowLabel, TextareaField, TextField} from '@p2p/Components/Form';
import {ArrowIcon, ImageIcon} from '@p2p/Components/Icons';
import {Button} from '@p2p/Components';
import {useForm} from 'react-hook-form';
import {mutate} from 'swr';
import {isCreatingTeam} from '@p2p/js/admin/App/Teams/utils';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__} = wp.i18n;

/**
 * @since 1.6.0 Updated to include error prop for MoneyField Component.
 */
const TeamProfileForm = ({campaign, team = {}}) => {
    const history = useHistory();
    const [state, setState] = useState({
        file: null,
        file_name: team?.profile_image ? team.profile_image.split('/').reverse()[0] : null,
        file_url: team?.profile_image ?? '',
        maxSizeError: false,
        saving: false,
        updated: false,
    });
    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        formState: {errors, isSubmitting},
    } = useForm({
        defaultValues: {
            name: team?.name ?? '',
            story: team?.story ?? campaign.team_story_placeholder,
            access: team?.access ?? 'public',
            file_name: state.file_name,
            file_url: state.file_url,
            notify_of_fundraisers: team?.notify_of_fundraisers ?? true,
            notify_of_team_donations: team?.notify_of_team_donations ?? true,
        },
    });

    const {data: emails} = useFetcher(getEndpoint('/get-email-settings'));

    const isSaveDisabled = () => isSubmitting || state.maxSizeError;

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

    const updateTeam = (formData) => {
        setState((previousState) => {
            return {
                ...previousState,
                saving: true,
            };
        });

        const data = new FormData();

        data.append('action', 'wp_handle_upload');
        data.append('campaignId', campaign.campaign_id);
        data.append('name', formData.name);
        data.append('goal', formData.goal);
        data.append('story', formData.story);
        data.append('access', formData.access);
        data.append('file', state.file);
        data.append('file_url', state.file_url);

        emails?.team_fundraiser_joined && data.append('notify_of_fundraisers', formData.notify_of_fundraisers);
        emails?.donation_team_captain && data.append('notify_of_team_donations', formData.notify_of_team_donations);

        team?.id && data.append('teamId', team.id);

        API.post(team?.id ? 'admin-update-team' : 'admin-create-team', data)
            .then((response) => {
                const {teamId} = response.data;

                setState((previousState) => {
                    return {
                        ...previousState,
                        saving: false,
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

                    isCreatingTeam() ? history.push(`/add-team-captain-${teamId}`) : history.push(`/`);
                }, 500);
            })
            .catch((error) => {
                setState((previousState) => {
                    return {
                        ...previousState,
                        saving: false,
                    };
                });

                setError('register', {
                    message: error.response?.data.message ?? error.message,
                });
            });
    };

    return (
        <form onSubmit={handleSubmit(updateTeam)}>
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
                        {...register('name', {required: true})}
                        error={errors.hasOwnProperty('name')}
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
                        label={__('Team Story', 'give-peer-to-peer')}
                        rows={4}
                        {...register('story', {required: true})}
                        error={errors.hasOwnProperty('story')}
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
                    <FileUpload accept=".jpg,.jpeg,.png" onClick={openMediaLibrary} onDrop={handleDrop}>
                        <div className={styles.uploadDescription}>
                            <div className={styles.uploadIconContainer}>
                                <ImageIcon width={24} height={19} className={styles.icon} />
                                {state.file_name
                                    ? __('UPLOAD A NEW IMAGE', 'give-peer-to-peer')
                                    : __('UPLOAD AN IMAGE', 'give-peer-to-peer')}
                            </div>

                            {state.maxSizeError ? (
                                <div className={styles.maxSizeError}>
                                    {state.file_name}{' '}
                                    {__('exceeds the maximum upload size for this site', 'give-peer-to-peer')}
                                </div>
                            ) : (
                                <div>
                                    {state.file_name && <div className={styles.selectedFile}>{state.file_name}</div>}
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
                            defaultChecked={team.access === 'public'}
                            {...register('access')}
                        />{' '}
                        {__('Public', 'give-peer-to-peer')}
                    </label>

                    <label className={styles.radioInput}>
                        <input
                            type="radio"
                            name="access"
                            value="private"
                            defaultChecked={team.access === 'private'}
                            {...register('access')}
                        />{' '}
                        {__('Closed', 'give-peer-to-peer')}
                    </label>
                </FieldRow>
            </CardBody>

            <CardBody>
                {emails?.donation_team_captain && (
                    <FieldRow>
                        <Checkbox
                            name="notify_of_team_donations"
                            type="checkbox"
                            defaultChecked={team?.notify_of_team_donations}
                            label={__(
                                "I would like to be notified of new donations given through my team/'s page.",
                                'give-peer-to-peer'
                            )}
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
                <Button
                    type="submit"
                    disabled={isSaveDisabled()}
                    isLoading={state.saving}
                    onClick={() => clearErrors('register')}
                    iconAfter={ArrowIcon}
                >
                    {team?.id ? __('Update', 'give-peer-to-peer') : __('Continue', 'give-peer-to-peer')}{' '}
                </Button>
                {errors.register && <div className={styles.errorMessage}>{errors.register.message}</div>}
            </CardBody>

            <CardFooter>
                <SecureAndEncrypted />
            </CardFooter>
        </form>
    );
};

export default TeamProfileForm;
