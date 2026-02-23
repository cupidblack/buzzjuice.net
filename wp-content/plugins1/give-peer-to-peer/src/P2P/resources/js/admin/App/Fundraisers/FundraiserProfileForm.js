import {useHistory, useParams} from 'react-router-dom';
import {useState} from 'react';
import {canUseFundraiserEmailOption, getProp} from '@p2p/js/utils';
import {DropDown, ErrorNotice, FileUpload, LoadingNotice, MoneyField} from '@p2p/Components/Admin';
import {CardBody, CardFooter} from '@p2p/Components/Card';
import styles from '@p2p/js/admin/App/Teams/styles.module.scss';
import {Checkbox, FieldRow, FieldRowLabel, TextareaField} from '@p2p/Components/Form';
import {ArrowIcon, ImageIcon} from '@p2p/Components/Icons';
import {Button} from '@p2p/Components';
import {useForm} from 'react-hook-form';
import API, {getEndpoint, useFetcher} from '@p2p/js/api';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

/**
 * @since 1.6.0 Updated to use correct fundraiser value and include error prop for MoneyField Component.
 */
const FundraiserProfileForm = ({campaign, fundraiser = {}}) => {
    const {wp_user_id: wpUserId} = useParams();
    const history = useHistory();

    const {data: emails} = useFetcher(getEndpoint('/get-email-settings'));

    const {
        data: teams,
        isLoading,
        isError,
    } = useFetcher(
        getEndpoint('/get-teams', {
            campaign_id: campaign.campaign_id,
            per_page: 99999,
            status: 'active',
            sort: 'name',
        })
    );

    const [state, setState] = useState({
        emails: [],
        file: null,
        file_name: fundraiser?.profile_image ? fundraiser.profile_image.split('/').reverse()[0] : null,
        file_url: fundraiser?.profile_image ?? '',
        maxSizeError: false,
        saving: false,
        updated: false,
    });

    const {
        register,
        handleSubmit,
        control,
        setError,
        clearErrors,
        formState: {errors, isSubmitting},
    } = useForm({
        defaultValues: {
            story: fundraiser?.story ?? campaign.team_story_placeholder,
            file_name: state.file_name,
            file_url: state.file_url,
            notify_of_donations: fundraiser?.notify_of_donations ?? true,
        },
    });

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

    const onSubmit = (formData) => {
        const {story, goal, notify_of_donations} = formData;
        setState((previousState) => {
            return {
                ...previousState,
                saving: true,
            };
        });

        const route = fundraiser?.id ? 'admin-update-fundraiser' : 'admin-create-fundraiser';
        const isEditingFundraiser = route === 'admin-update-fundraiser';
        const data = new FormData();

        data.append('action', 'wp_handle_upload');
        data.append('campaignId', campaign.campaign_id);
        data.append('userId', wpUserId);
        data.append('story', story);
        data.append('goal', goal);
        data.append('file', state.file);
        data.append('file_url', state.file_url);

        if (formData.team) {
            data.append('teamId', formData.team);
        }

        canUseFundraiserEmailOption(emails, state.team) && data.append('notify_of_donations', notify_of_donations);

        fundraiser?.id && data.append('fundraiserId', fundraiser.id);

        API.post(route, data)
            .then((response) => {
                const {fundraiserId} = response.data;

                setState((previousState) => {
                    return {
                        ...previousState,
                        saving: false,
                        updated: true,
                    };
                });

                setTimeout(() => {
                    setState((previousState) => {
                        return {
                            ...previousState,
                            updated: false,
                        };
                    });

                    !isEditingFundraiser ? history.push(`/fundraiser-created-${fundraiserId}`) : history.push(`/`);
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

    if (isLoading) {
        return <LoadingNotice notice={__('Loading teams', 'give-peer-to-peer')} />;
    }

    if (isError) {
        return (
            <ErrorNotice
                reload={false}
                notice={__('Unable to fetch the teams. Check the logs for details.', 'give-peer-to-peer')}
            />
        );
    }

    let teamsDropDownOptions = teams.map(({team_id, team_name}) => {
        return {
            label: team_name,
            value: team_id,
        };
    });

    teamsDropDownOptions = [{label: __('None', 'give-peer-to-peer')}].concat(teamsDropDownOptions);

    return (
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
            </CardBody>

            <CardBody>
                <FieldRowLabel
                    label={__('Fundraiser image', 'give-peer-to-peer')}
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
                <FieldRowLabel label={__('Fundraiser goal', 'give-peer-to-peer')} required />

                <FieldRow>
                    <MoneyField
                        defaultAmount={
                            fundraiser && fundraiser.fundraiser_goal
                                ? Number(fundraiser.fundraiser_goal)
                                : Number(campaign.fundraiser_goal)
                        }
                        error={'goal' in errors}
                        {...register('goal', {required: true})}
                    />
                </FieldRow>
            </CardBody>

            <CardBody>
                <FieldRowLabel label={__('Add to a team', 'give-peer-to-peer')} />

                <FieldRow>
                    <DropDown
                        name="team"
                        control={control}
                        options={teamsDropDownOptions}
                        defaultValue={fundraiser?.team_id}
                        isDisabled={fundraiser.is_captain}
                    />
                </FieldRow>
                {fundraiser.is_captain && (
                    <div className={styles.errorMessage}>
                        <p>
                            <span>
                                {__(
                                    'You are unable to join another team because you are currently a team captain for a campaign.',
                                    'give-peer-to-peer'
                                )}
                            </span>
                            <a
                                href="#"
                                onClick={(e) => {
                                    e.preventDefault();
                                    location.href = `/wp-admin/edit.php?post_type=give_forms&page=p2p-edit-campaign&id=${campaign.campaign_id}&tab=teams#/add-team-captain-${fundraiser.team_id}`;
                                }}
                            >
                                {sprintf("Update %s's captaincy.", fundraiser.team)}
                            </a>
                        </p>
                    </div>
                )}
            </CardBody>

            {canUseFundraiserEmailOption(emails, state.team) && (
                <CardBody>
                    <FieldRow>
                        <Checkbox
                            name="notify_of_donations"
                            type="checkbox"
                            defaultChecked={fundraiser?.notify_of_donations}
                            label={__(
                                'I would like to be notified of new donations given through my fundraising page.',
                                'give-peer-to-peer'
                            )}
                            error={'notify_of_donations' in errors}
                            {...register('notify_of_donations', {required: false})}
                        />
                    </FieldRow>
                </CardBody>
            )}

            <CardBody>
                <Button
                    type="submit"
                    disabled={isSaveDisabled()}
                    isLoading={state.saving}
                    onSubmit={() => clearErrors('register')}
                    iconAfter={ArrowIcon}
                >
                    {fundraiser?.id
                        ? __('Update Fundraiser', 'give-peer-to-peer')
                        : __('Create Fundraiser', 'give-peer-to-peer')}
                </Button>

                {errors.register && <div className={styles.errorMessage}>{errors.register.message}</div>}
            </CardBody>

            <CardFooter>
                <SecureAndEncrypted />
            </CardFooter>
        </form>
    );
};

export default FundraiserProfileForm;
