import {useState} from 'react';
import {useForm} from 'react-hook-form';
import {Button, Modal} from '@p2p/Components/Admin';
import {TextField} from '@p2p/Components/Form';
import API, {getEndpoint} from '@p2p/js/api';

const {__, sprintf} = wp.i18n;

export default ({team, campaign, closeModal}) => {
    const [state, setState] = useState({
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

    const deleteTeam = (formData, e) => {
        e.preventDefault();

        const data = {
            team_id: team.team_id,
        };

        return API.post(getEndpoint('/delete-team-strategy'), data)
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
                <strong>{sprintf(__('Delete Team %s?', 'give-peer-to-peer'), team.team_name)}</strong>
                <Modal.CloseIcon onClick={closeModal} />
            </Modal.Title>
            <Modal.Content style={{marginTop: 0}}>
                <p>
                    {sprintf(
                        __(
                            'In deleting the %s team, would you also like to delete its %d members as well? If not, members will become independent Fundraisers of the %s campaign, no longer associated with this team. At that point the Fundraisers may choose to join another team.',
                            'give-peer-to-peer'
                        ),
                        team.team_name,
                        team.fundraisers_total,
                        campaign.campaign_title
                    )}
                </p>

                <p>
                    {__(
                        'If you are sure this is what you want to do, please enter the team name into the input below and press the delete button.',
                        'give-peer-to-peer'
                    )}
                </p>

                <form style={{margin: '20px 0'}} onSubmit={handleSubmit(deleteTeam)}>
                    <TextField
                        autoComplete="off"
                        {...register('confirmTeamName', {
                            required: true,
                            validate: (value) => value === team.team_name,
                        })}
                        placeholder={__("Type the team's name to confirm deletion", 'give-peer-to-peer')}
                    />

                    {state.deleteError && <div style={{color: '#d75a4b'}}>{state.deleteError}</div>}

                    <div style={{marginTop: '20px', display: 'flex', justifyContent: 'space-between'}}>
                        <Button className="button button-primary" onClick={closeModal}>
                            {__('Cancel', 'give-peer-to-peer')}
                        </Button>
                        <Button className="button button-secondary" type="submit" disabled={isSubmitting || !isValid}>
                            {__('Delete Team', 'give-peer-to-peer')}
                        </Button>
                    </div>
                </form>
            </Modal.Content>
        </Modal>
    );
};
