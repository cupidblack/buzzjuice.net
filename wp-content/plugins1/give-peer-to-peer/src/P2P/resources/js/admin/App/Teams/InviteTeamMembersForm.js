import FormContainer from '@p2p/Components/Admin/FormContainer';
import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import {
    getFormNavigationId,
    getTeam,
    isCreatingTeam,
    setFormNavigationIdAndProcessStep,
} from '@p2p/js/admin/App/Teams/utils';
import {useHistory, useParams} from 'react-router-dom';
import API, {getEndpoint} from '@p2p/js/api';
import {ErrorNotice, LoadingNotice, MultiEmailSelect} from '@p2p/Components/Admin';
import {useForm} from 'react-hook-form';
import styles from '@p2p/js/admin/App/Teams/styles.module.scss';
import {Button} from '@p2p/Components';
import {ArrowIcon} from '@p2p/Components/Icons';
import {FieldRow, FieldRowLabel} from '@p2p/Components/Form';
import {useState} from 'react';
import {mutate} from 'swr';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__} = wp.i18n;

const InviteTeamMembersForm = ({campaign}) => {
    const {id: team_id} = useParams();
    const history = useHistory();
    const {data: team, isLoading: isTeamLoading, isError: isTeamRequestError} = getTeam(team_id);

    const [state, setState] = useState({
        emails: '',
        updated: false,
    });

    setFormNavigationIdAndProcessStep('3');

    const onSubmit = (formData) => {
        if (!state.emails) {
            return;
        }

        const data = {
            campaignId: campaign.campaign_id,
            teamId: team.id,
            emails: state.emails.join(','),
        };
        API.post('admin-send-team-invitation-emails', data).then(() => {
            setState((previousState) => {
                return {
                    ...previousState,
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

                if (getFormNavigationId() === 'createTeam') {
                    history.push(`team-created-${team.id}`);
                } else {
                    history.push(campaign.campaign_url);
                }
            }, 500);
        });
    };

    const {
        register,
        handleSubmit,
        setError,
        clearErrors,
        watch,
        formState: {errors, isSubmitting},
    } = useForm();

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
    return (
        <FormContainer
            title={sprintf(__('Creating a new team', 'give-peer-to-peer'))}
            teamImage={campaign.campaign_image}
            showStepNavigation={isCreatingTeam()}
        >
            <Card title={__('Invite Team Members', 'give-peer-to-peer')}>
                <form onSubmit={handleSubmit(onSubmit)}>
                    <CardBody style={{paddingBottom: '15px', margin: '15px 0'}}>
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

                        {!state.updated ? (
                            <Button
                                type="submit"
                                disabled={isSubmitting}
                                isLoading={isSubmitting}
                                onClick={() => clearErrors('register')}
                                iconAfter={ArrowIcon}
                            >
                                <> {__('Send', 'give-peer-to-peer')}</>
                            </Button>
                        ) : (
                            <Button>
                                <>
                                    {team.id
                                        ? __('Invitations sent', 'give-peer-to-peer')
                                        : __('Team created', 'give-peer-to-peer')}
                                </>
                            </Button>
                        )}

                        {isCreatingTeam() && (
                            <a
                                className={styles.skip}
                                href="#"
                                onClick={(e) => {
                                    e.preventDefault();

                                    history.push(`/team-created-${team.id}`);
                                }}
                            >
                                {__('Skip', 'give-peer-to-peer')}
                            </a>
                        )}
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

export default InviteTeamMembersForm;
