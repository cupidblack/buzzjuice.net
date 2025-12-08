import {useState} from 'react';
import {useHistory} from 'react-router-dom';
import {Button, Card, CreateButton, ErrorNotice, LoadingNotice, Pagination, Select, Table} from '@p2p/Components/Admin';
import API, {getEndpoint, mutate, useFetcher} from '@p2p/js/api';
import {getAmountInCurrency, getProp} from '@p2p/js/utils';
import {ProgressBar} from '@p2p/Components';
import PlaceholderTeamAvatar from '@p2p/Components/PlaceholderTeamAvatar';

import styles from '../styles.module.scss';
import DeleteTeamModal from './DeleteTeamModal';
import {clearFormNavigationId, isCreatingTeam} from '@p2p/js/admin/App/Teams/utils';

const {__, _n, sprintf} = wp.i18n;

const TeamsListTable = ({campaign}) => {
    const history = useHistory();

    const [state, setState] = useState({
        initialLoad: false,
        currentPage: 1,
        currentStatus: 'all',
        sortColumn: '',
        sortDirection: '',
        pages: 0,
        statuses: [],
        total: 0,
        isSorting: false,
        isDeletingTeam: false,
    });

    if (isCreatingTeam()) {
        clearFormNavigationId();
    }

    const editCampaignPageUrl = getProp('editCampaignPage');

    const parameters = {
        campaign_id: campaign.campaign_id,
        page: state.currentPage,
        sort: state.sortColumn,
        direction: state.sortDirection,
        status: state.currentStatus,
    };

    const {data, isLoading, isError} = useFetcher(getEndpoint('/get-teams', parameters), {
        onSuccess: ({response}) => {
            setState((previousState) => {
                return {
                    ...previousState,
                    initialLoad: true,
                    pages: response.pages,
                    statuses: response.statuses,
                    total: response.total,
                    currentPage: state.currentPage > response.pages ? 1 : state.currentPage,
                    isSorting: false,
                };
            });
        },
    });

    const resetQueryParameters = (e) => {
        e.preventDefault();

        // Reset table sort state
        Table.resetSortState();

        setState((previousState) => {
            return {
                ...previousState,
                currentPage: 1,
                currentStatus: 'all',
                currentTeam: '',
                sortColumn: '',
                sortDirection: '',
            };
        });
    };

    const setSortDirectionForColumn = (column, direction) => {
        setState((previousState) => {
            return {
                ...previousState,
                sortColumn: column,
                sortDirection: direction,
                isSorting: true,
            };
        });
    };

    const setCurrentPage = (currentPage) => {
        setState((previousState) => {
            return {
                ...previousState,
                currentPage,
            };
        });
    };

    const setCurrentStatus = (e) => {
        const status = e.target.value;
        setState((previousState) => {
            return {
                ...previousState,
                currentStatus: status,
            };
        });
    };

    const getStatuses = () => {
        const defaultStatus = {
            value: 'all',
            label: __('All statuses', 'give-peer-to-peer'),
        };

        const statuses = Object.entries(state.statuses).map(([value, label]) => {
            return {
                label,
                value,
            };
        });

        return [defaultStatus, ...statuses];
    };

    const approveTeam = (teamId) => {
        const postData = {
            team_id: teamId,
            status: 'active',
        };
        API.post('update-team-approval', postData)
            .then(() => {
                mutate(getEndpoint('/get-teams', parameters));

                /**
                 * @since 1.3.0 Send team invitation emails when the team is approved.
                 */
                API.post('send-team-invitation-emails', {team_id: postData.team_id});
            })
            .catch((error) => {
                alert(error.response.data.message);
            });
    };

    let columns = [
        {
            key: 'team_name',
            label: __('Team Name', 'give-peer-to-peer'),
            sort: true,
            sortCallback: (direction) => setSortDirectionForColumn('name', direction),
            styles: {
                // flex: 1.5,
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'team_goal',
            label: __('Goal', 'give-peer-to-peer'),
            sort: true,
            sortCallback: (direction) => setSortDirectionForColumn('goal', direction),
            styles: {
                // flex: 1,
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'members',
            label: __('Members', 'give-peer-to-peer'),
            append: true,
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'captain',
            label: __('Captain', 'give-peer-to-peer'),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'status',
            label: __('Status', 'give-peer-to-peer'),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'action_items',
            label: __('Action Items', 'give-peer-to-peer'),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
    ];

    if ('enabled' === campaign.team_approvals) {
        columns.push({
            key: 'status',
            label: __('Status', 'give-peer-to-peer'),
            sort: true,
            sortCallback: (direction) => setSortDirectionForColumn('status', direction),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        });
    }

    const closeDeleteTeamModal = () => {
        mutate(getEndpoint('/get-teams', parameters));
        setState((previousState) => {
            return {
                ...previousState,
                isDeletingTeam: null,
            };
        });
    };

    const columnFilters = {
        team_name: (name, team) => {
            return (
                <div className={styles.titleColumn}>
                    <div className={styles.avatar}>
                        <a
                            key={team.team_id}
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                history.push(`/edit-team-${team.team_id}`);
                            }}
                        >
                            <img style={{width: 55, height: 55}} src={team.profile_image || PlaceholderTeamAvatar} />
                        </a>
                    </div>

                    <div className={styles.info}>
                        <strong className={styles.teamTitle}>{name}</strong>
                        <div className={styles.columnActions}>
                            <a
                                href="#"
                                onClick={(e) => {
                                    e.preventDefault();
                                    history.push(`/edit-team-${team.team_id}`);
                                }}
                            >
                                {__('Edit', 'give-peer-to-peer')}
                            </a>
                            <span>|</span>
                            <a href={campaign.campaign_url + '/team/' + team.team_id} target="_blank">
                                {__('View', 'give-peer-to-peer')}
                            </a>
                            <span>|</span>
                            <a
                                className={styles.delete}
                                href="#"
                                onClick={() => {
                                    setState((previousState) => {
                                        return {
                                            ...previousState,
                                            isDeletingTeam: team,
                                        };
                                    });
                                }}
                            >
                                {__('Delete', 'give-peer-to-peer')}
                            </a>
                        </div>
                    </div>
                </div>
            );
        },
        team_goal: (goal, team) => {
            const amountRaised = getAmountInCurrency(team.amount_raised);
            const goalAmount = getAmountInCurrency(goal);

            return (
                <div className={styles.progressBarWrapper}>
                    <ProgressBar amount={team.amount_raised} goal={parseInt(goal)} />
                    <div className={styles.amountRaised}>
                        {sprintf(__('%s of %s goal', 'give-peer-to-peer'), amountRaised, goalAmount)}
                    </div>
                </div>
            );
        },

        members: (val, team) => {
            return (
                <div className={styles.membersContainer}>
                    {team.fundraisers_total > 0 ? (
                        <span>
                            <a
                                href={`${editCampaignPageUrl}&id=${campaign.campaign_id}&team=${team.team_id}&tab=fundraisers`}
                            >
                                {team.fundraisers_total}{' '}
                                {_n('Member', 'Members', team.fundraisers_total, 'give-peer-to-peer')}
                            </a>
                        </span>
                    ) : (
                        <span>0 {__('Members', 'give-peer-to-peer')}</span>
                    )}

                    {campaign.fundraiser_approvals === 'enabled' &&
                        (team.fundraisers_pending > 0 ? (
                            <span>
                                <a
                                    href={`${editCampaignPageUrl}&id=${campaign.campaign_id}&team=${team.team_id}&tab=fundraisers&show=pending`}
                                >
                                    {team.fundraisers_pending} {__('Pending approval', 'give-peer-to-peer')}
                                </a>
                            </span>
                        ) : (
                            <span>0 {__('Pending approval', 'give-peer-to-peer')}</span>
                        ))}
                </div>
            );
        },
        captain: (val, team) => {
            return (
                <>
                    {team.team_captains.length ? (
                        team?.team_captains.map((captain) => {
                            return (
                                <a
                                    key={captain.id}
                                    className={styles.avatar}
                                    href={campaign.campaign_url + '/fundraiser/' + captain.id}
                                >
                                    {captain.avatar && (
                                        <img
                                            src={captain.avatar}
                                            alt={__('Team captain profile image', 'give-peer-to-peer')}
                                        />
                                    )}
                                    <strong>{captain.name}</strong>
                                </a>
                            );
                        })
                    ) : (
                        <a
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                history.push(`add-team-captain-${team.team_id}`);
                            }}
                        >
                            {__('Add Team Captain', 'give-peer-to-peer')}
                        </a>
                    )}
                </>
            );
        },
        status: (val, team) => {
            return (
                <>
                    {team.team_status ? (
                        <div className={styles.approvedIcon}>
                            <span className="dashicons dashicons-yes-alt"></span> {__('Approved', 'give-peer-to-peer')}
                        </div>
                    ) : (
                        <div>
                            <button className="button" onClick={() => approveTeam(team.team_id)}>
                                {__('Approve', 'give-peer-to-peer')}
                            </button>
                        </div>
                    )}
                </>
            );
        },
        action_items: (val, team) => {
            return (
                <div className={styles.info}>
                    <div className={styles.columnActions}>
                        {team.team_captains.length ? (
                            <>
                                <a
                                    href=""
                                    onClick={(e) => {
                                        e.preventDefault();
                                        history.push(`add-team-captain-${team.team_id}`);
                                    }}
                                >
                                    {__('Edit Captain', 'give-peer-to-peer')}
                                </a>
                                <span>|</span>
                            </>
                        ) : (
                            ''
                        )}
                        <a
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                history.push(`invite-team-members-${team.team_id}`);
                            }}
                        >
                            {__('Invite members', 'give-peer-to-peer')}
                        </a>
                    </div>
                </div>
            );
        },
    };

    // Initial load
    if (!state.initialLoad && isLoading) {
        return <LoadingNotice notice={__('Loading Teams', 'give-peer-to-peer')} />;
    }

    // Is error?
    if (isError) {
        return <ErrorNotice notice={__('Unable to load teams. Check the logs for details.', 'give-peer-to-peer')} />;
    }

    if (!state.total && !state.currentStatus.length) {
        return (
            <div className="give-blank-slate">
                <CreateButton link={'/create-team'}>{__('Create a team', 'give-peer-to-peer')}</CreateButton>
                <p className="give-blank-slate__help">
                    {__('Need help? Learn more about', 'give-peer-to-peer')}{' '}
                    <a href="https://docs.givewp.com/addon-p2p" target="_blank">
                        {__(' P2P Campaigns', 'give-peer-to-peer')}
                    </a>
                    .
                </p>
            </div>
        );
    }

    const blankSlate = {
        desc: __('No teams found', 'give-peer-to-peer'),
        link: `/create-team`,
        button: __(' Create a new team', 'give-peer-to-peer'),
    };

    return (
        <>
            <div className={styles.headerRow}>
                <Select
                    options={getStatuses()}
                    onChange={setCurrentStatus}
                    defaultValue={state.currentStatus}
                    className={styles.headerItem}
                    data-givewp-test="p2p-campaign-status-dropdown"
                />
                <Button onClick={resetQueryParameters}>{__('Reset', 'give-peer-to-peer')}</Button>

                <CreateButton link={'/create-team'}>{__('Create a team', 'give-peer-to-peer')}</CreateButton>
                <div className={styles.pagination}>
                    <Pagination
                        currentPage={state.currentPage}
                        setPage={setCurrentPage}
                        totalPages={state.pages}
                        disabled={isLoading}
                    />
                </div>
            </div>

            <Card>
                <Table
                    columns={columns}
                    data={data}
                    columnFilters={columnFilters}
                    isLoading={isLoading}
                    isSorting={state.isSorting}
                    stripped={false}
                    data-givewp-test="p2p-teams-table"
                    blankSlate={blankSlate}
                />
            </Card>

            {state.pages > 0 && (
                <div className={styles.footerRow}>
                    <div className={styles.pagination}>
                        <Pagination
                            currentPage={state.currentPage}
                            setPage={setCurrentPage}
                            totalPages={state.pages}
                            disabled={isLoading}
                        />
                    </div>
                </div>
            )}

            {state.isDeletingTeam && (
                <DeleteTeamModal team={state.isDeletingTeam} campaign={campaign} closeModal={closeDeleteTeamModal} />
            )}
        </>
    );
};

export default TeamsListTable;
