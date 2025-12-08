import {useState} from 'react';
import {useForm} from 'react-hook-form';
import {Button, Modal, Spinner} from '@p2p/Components/Admin';
import {SelectField, TextField} from '@p2p/Components/Form';
import API, {getEndpoint, useFetcher} from '@p2p/js/api';

const {__, sprintf} = wp.i18n;

const DeleteFundraiserModal = ({fundraiser, teamName, campaign, closeModal}) => {
    const strategies = window.p2pReallocationStrategies;

    const [state, setState] = useState({
        strategy: null,
        otherTeamMembers: [],
        deleteError: '',
    });

    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        watch,
        formState: {isValid, errors, isSubmitting},
    } = useForm({
        mode: 'onChange',
    });

    const {data, isLoading, isError} = useFetcher(
        getEndpoint('/get-delete-fundraiser-strategy', {fundraiser_id: fundraiser.id}),
        {
            onSuccess: ({response}) => {
                setState((previousState) => {
                    return {
                        ...previousState,
                        strategy: response.strategy,
                    };
                });
            },
        }
    );

    const {otherTeamMembers, otherTeamMembersLoading, otherTeamMembersError} = useFetcher(
        getEndpoint('/get-team-fundraisers-all', {team_id: fundraiser.team_id}),
        {
            onSuccess: ({data}) => {
                setState((previousState) => {
                    return {
                        ...previousState,
                        otherTeamMembers: data
                            .filter((teamFundraiser) => teamFundraiser.id !== fundraiser.id)
                            .map((teamFundraiser) => {
                                return {
                                    value: teamFundraiser.id,
                                    label: teamFundraiser.name,
                                };
                            }),
                    };
                });
            },
        }
    );

    const deleteFundraiser = (formData, e) => {
        e.preventDefault();

        const data = {
            strategy: state.strategy,
            fundraiser_id: fundraiser.id,
            new_team_owner_fundraiser_id: formData.teamOwnerReassignment,
        };

        return API.post(getEndpoint('/delete-fundraiser-strategy'), data)
            .then(({data}) => {
                closeModal();
            })
            .catch((error) => {
                setState((previousState) => {
                    return {
                        ...previousState,
                        deleteError: error.response.data.message,
                    };
                });
            });
    };

    return (
        <Modal type={'error'} handleClose={closeModal}>
            <Modal.Title style={{marginBottom: 0}}>
                <strong>{sprintf(__('Delete Fundraiser %s?', 'give-peer-to-peer'), fundraiser.fundraiser_name)}</strong>
                <Modal.CloseIcon onClick={closeModal} />
            </Modal.Title>
            <Modal.Content style={{marginTop: 0}}>
                {isLoading ? (
                    <div style={{display: 'flex', justifyContent: 'center'}}>
                        <Spinner />
                    </div>
                ) : (
                    <>
                        {strategies.CAMPAIGN_FUNDRAISER_STRATEGY === state.strategy && (
                            <p>
                                {sprintf(
                                    __(
                                        'Once %s is deleted any donations associated with the Fundraiser will be reassigned to the %s campaign.',
                                        'give-peer-to-peer'
                                    ),
                                    fundraiser.fundraiser_name,
                                    campaign.campaign_title
                                )}
                            </p>
                        )}

                        {strategies.TEAM_ONLY_FUNDRAISER_STRATEGY === state.strategy && (
                            <p>
                                {sprintf(
                                    __(
                                        'The team has no other members so the team will be deleted when the fundraiser is deleted. Any donations associated with either %s or %s will be reassigned to the %s campaign.',
                                        'give-peer-to-peer'
                                    ),
                                    fundraiser.fundraiser_name,
                                    teamName,
                                    campaign.campaign_title
                                )}
                            </p>
                        )}

                        {strategies.TEAM_MEMBER_STRATEGY === state.strategy && (
                            <p>
                                {sprintf(
                                    __(
                                        'Once %s is deleted any donations associated with the Fundraiser will be reassigned to the %s team.',
                                        'give-peer-to-peer'
                                    ),
                                    fundraiser.fundraiser_name,
                                    teamName
                                )}
                            </p>
                        )}

                        {strategies.TEAM_OWNER_STRATEGY === state.strategy && (
                            <>
                                <p>
                                    {sprintf(
                                        __(
                                            '%s is the owner of the %s team. In order to delete this Fundraiser, you must assign another member as the owner of the team.',
                                            'give-peer-to-peer'
                                        ),
                                        fundraiser.fundraiser_name,
                                        teamName
                                    )}
                                </p>
                                <SelectField
                                    label={__('Select a new team owner', 'give-peer-to-peer')}
                                    {...register('teamOwnerReassignment', {
                                        required: true,
                                    })}
                                    options={state.otherTeamMembers}
                                />
                                <p>
                                    {sprintf(
                                        __(
                                            'Once %s is deleted any donations associated with the Fundraiser will be reassigned to the %s team.',
                                            'give-peer-to-peer'
                                        ),
                                        fundraiser.fundraiser_name,
                                        teamName
                                    )}
                                </p>
                            </>
                        )}

                        <p>
                            {__(
                                'If you are sure this is what you want to do, please enter the Fundraiser’s name into the input below and press the delete button.',
                                'give-peer-to-peer'
                            )}
                        </p>

                        <form style={{margin: '20px 0'}} onSubmit={handleSubmit(deleteFundraiser)}>
                            <TextField
                                autoComplete="off"
                                {...register('confirmFundraiserName', {
                                    required: true,
                                    validate: (value) => value === fundraiser.fundraiser_name,
                                })}
                                placeholder={__("Type the fundraiser's name to confirm deletion", 'give-peer-to-peer')}
                            />

                            {state.deleteError && <div style={{color: '#d75a4b'}}>{state.deleteError}</div>}

                            <div style={{marginTop: '20px', display: 'flex', justifyContent: 'space-between'}}>
                                <Button className="button button-primary" onClick={closeModal}>
                                    {__('Cancel', 'give-peer-to-peer')}
                                </Button>
                                <Button
                                    className="button button-secondary"
                                    type="submit"
                                    disabled={isSubmitting || !isValid}
                                >
                                    {__('Delete Fundraiser', 'give-peer-to-peer')}
                                </Button>
                            </div>
                        </form>
                    </>
                )}
            </Modal.Content>
        </Modal>
    );
};

export default DeleteFundraiserModal;
