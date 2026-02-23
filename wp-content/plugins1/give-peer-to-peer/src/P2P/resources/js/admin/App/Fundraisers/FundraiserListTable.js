import {useEffect, useState} from 'react';
import {Button, Card, CreateButton, ErrorNotice, LoadingNotice, Pagination, Select, Table} from '@p2p/Components/Admin';
import API, {getEndpoint, mutate, useFetcher} from '@p2p/js/api';
import {getAmountInCurrency} from '@p2p/js/utils';
import {ProgressBar} from '@p2p/Components';

import styles from '../styles.module.scss';
import DeleteFundraiserModal from './deleteFundraiserModal';
import {useHistory} from 'react-router-dom';

const {__, sprintf} = wp.i18n;

const FundraisersListTable = ({campaign}) => {
    const history = useHistory();

    const [state, setState] = useState({
        initialLoad: false,
        currentPage: 1,
        currentStatus: 'all',
        currentTeam: '',
        sortColumn: '',
        sortDirection: '',
        pages: 0,
        statuses: [],
        teams: [],
        total: 0,
        isSorting: false,
        isDeletingFundraiser: false,
    });

    const parameters = {
        campaign_id: campaign.campaign_id,
        team_id: state.currentTeam,
        page: state.currentPage,
        sort: state.sortColumn,
        direction: state.sortDirection,
        status: state.currentStatus,
    };

    const {data, isLoading, isError} = useFetcher(getEndpoint('/get-team-fundraisers', parameters), {
        onSuccess: ({response}) => {
            setState((previousState) => {
                return {
                    ...previousState,
                    initialLoad: true,
                    pages: response.pages,
                    statuses: response.statuses,
                    teams: response.teams,
                    total: response.total,
                    currentPage: state.currentPage > response.pages ? 1 : state.currentPage,
                    isSorting: false,
                };
            });
        },
    });

    useEffect(() => {
        const teamParam = new URLSearchParams(window.location.search).get('team');
        const statusParam = new URLSearchParams(window.location.search).get('show');

        if (teamParam) {
            setState((previousState) => {
                return {...previousState, currentTeam: teamParam};
            });
        }

        if (statusParam) {
            setState((previousState) => {
                return {...previousState, currentStatus: statusParam};
            });
        }
    }, [window.location.search]);

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

    const setCurrentTeam = (e) => {
        const team = e.target.value;
        setState((previousState) => {
            return {
                ...previousState,
                currentTeam: team,
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

    const getTeams = () => {
        const defaultTeam = {
            value: 'all',
            label: __('All teams', 'give-peer-to-peer'),
        };

        const teams = Object.entries(state.teams).map(([value, label]) => {
            return {
                label,
                value,
            };
        });

        return [defaultTeam, ...teams];
    };

    const approveFundraiser = (fundraiserId) => {
        const data = {
            fundraiser_id: fundraiserId,
            status: 'active',
        };
        API.post('update-fundraiser-approval', data).then(({data}) => {
            mutate(getEndpoint('/get-team-fundraisers', parameters));
        });
    };

    let columns = [
        {
            key: 'fundraiser_name',
            label: __('Fundraiser Name', 'give-peer-to-peer'),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'fundraiser_goal',
            label: __('Goal', 'give-peer-to-peer'),
            sort: true,
            sortCallback: (direction) => setSortDirectionForColumn('goal', direction),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'team',
            label: __('Team', 'give-peer-to-peer'),
            sort: true,
            sortCallback: (direction) => setSortDirectionForColumn('team_id', direction),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        // {
        // 	key: 'is_captain',
        // 	label: __( 'Captain', 'give-peer-to-peer' ),
        // 	sort: true,
        // 	sortCallback: ( direction ) => setSortDirectionForColumn( 'team_captain', direction ),
        // 	styles: {
        // 		display: 'flex',
        // 		alignItems: 'center',
        // 	},
        // },
        // {
        // 	key: 'date_created',
        // 	label: __( 'Date', 'give-peer-to-peer' ),
        // 	sort: true,
        // 	sortCallback: ( direction ) => setSortDirectionForColumn( 'date_created', direction ),
        // 	styles: {
        // 		display: 'flex',
        // 		alignItems: 'center',
        // 	},
        // },
    ];

    if ('enabled' === campaign.fundraiser_approvals) {
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

    const closeDeleteFundraiserModal = () => {
        mutate(getEndpoint('/get-team-fundraisers', parameters));
        setState((previousState) => {
            return {
                ...previousState,
                isDeletingFundraiser: null,
            };
        });
    };

    const columnFilters = {
        fundraiser_name: (name, fundraiser) => {
            return (
                <div className={styles.titleColumn}>
                    {fundraiser.profile_image && (
                        <div className={styles.profile_image}>
                            <a key={fundraiser.id} href="#">
                                <div className={styles.avatar}>
                                    <img src={fundraiser.profile_image} />
                                </div>
                            </a>
                        </div>
                    )}

                    <div className={styles.info}>
                        <strong>{name}</strong>

                        <div className={styles.columnActions}>
                            <a
                                href="#"
                                onClick={(e) => {
                                    e.preventDefault();
                                    history.push(`/edit-fundraiser-${fundraiser.id}`);
                                }}
                            >
                                {__('Edit', 'give-peer-to-peer')}
                            </a>
                            <span>|</span>
                            <a href={campaign.campaign_url + '/fundraiser/' + fundraiser.id} target="_blank">
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
                                            isDeletingFundraiser: fundraiser,
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
        fundraiser_goal: (goal, fundraiser) => {
            const amountRaised = getAmountInCurrency(fundraiser.amount_raised);
            const goalAmount = getAmountInCurrency(goal);

            return (
                <div className={styles.progressBarWrapper}>
                    <ProgressBar amount={fundraiser.amount_raised} goal={parseInt(goal)} />
                    <div className={styles.amountRaised}>
                        {sprintf(__('%s of %s goal', 'give-peer-to-peer'), amountRaised, goalAmount)}
                    </div>
                </div>
            );
        },

        is_captain: (val, fundraiser) => {
            if (fundraiser.is_captain) {
                return __('Yes', 'give-peer-to-peer');
            }
        },
        status: (val, fundraiser) => {
            return (
                <>
                    {fundraiser.status ? (
                        <div className={styles.approvedIcon}>
                            <span className="dashicons dashicons-yes-alt"></span> {__('Approved', 'give-peer-to-peer')}
                        </div>
                    ) : (
                        <div>
                            <button className="button" onClick={() => approveFundraiser(fundraiser.id)}>
                                {__('Approve', 'give-peer-to-peer')}
                            </button>
                        </div>
                    )}
                </>
            );
        },
    };

    // Initial load
    if (!state.initialLoad && isLoading) {
        return <LoadingNotice notice={__('Loading Fundraisers', 'give-peer-to-peer')} />;
    }

    // Is error?
    if (isError) {
        return (
            <ErrorNotice notice={__('Unable to load fundraisers. Check the logs for details.', 'give-peer-to-peer')} />
        );
    }

    if (!state.total && !state.currentStatus.length) {
        return (
            <div className="give-blank-slate">
                <a className="give-blank-slate__cta button button-primary" href="#">
                    {__('Register Fundraiser', 'give-peer-to-peer')}
                </a>
                <p className="give-blank-slate__help">
                    {__('Need help? Learn more about', 'give-peer-to-peer')}{' '}
                    <a href="http://docs.givewp.com/addon-p2p" target="_blank">
                        {__('P2P Campaigns', 'give-peer-to-peer')}
                    </a>
                    .
                </p>
            </div>
        );
    }

    const blankSlate = {
        desc: __('No fundraisers found', 'give-peer-to-peer'),
        link: `/create-wp-user`,
        button: __(' Create a new fundraiser', 'give-peer-to-peer'),
    };

    return (
        <>
            <div className={styles.headerRow}>
                <Select
                    options={getTeams()}
                    onChange={setCurrentTeam}
                    defaultValue={state.currentTeam}
                    className={styles.headerItem}
                    data-givewp-test="p2p-fundraiser-teams-dropdown"
                />

                <Select
                    options={getStatuses()}
                    onChange={setCurrentStatus}
                    defaultValue={state.currentStatus}
                    className={styles.headerItem}
                    data-givewp-test="p2p-fundraiser-status-dropdown"
                />
                <Button onClick={resetQueryParameters}>{__('Reset', 'give-peer-to-peer')}</Button>
                <CreateButton link={'/create-wp-user'}>{__('Create a fundraiser', 'give-peer-to-peer')}</CreateButton>
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
                    data-givewp-test="p2p-fundraisers-table"
                    blankSlate={blankSlate}
                />
            </Card>

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
            {state.isDeletingFundraiser && (
                <DeleteFundraiserModal
                    fundraiser={state.isDeletingFundraiser}
                    teamName={state.teams[state.isDeletingFundraiser.team_id]}
                    campaign={campaign}
                    closeModal={closeDeleteFundraiserModal}
                />
            )}
        </>
    );
};

export default FundraisersListTable;
