import {useState} from 'react';
import {
    Button,
    Card,
    ErrorNotice,
    Label,
    LoadingNotice,
    Modal,
    Pagination,
    Select,
    Spinner,
    Table,
} from '@p2p/Components/Admin';
import API, {getEndpoint, mutate, useFetcher} from '@p2p/js/api';
import {getAmountInCurrency, getProp} from '@p2p/js/utils';
import {ProgressBar} from '@p2p/Components';

import styles from '../styles.module.scss';

const {__, _n} = wp.i18n;

const Campaigns = () => {
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
    });

    const [cloneCampaignModal, setCloneCampaignModal] = useState({
        isCloning: false,
        visible: false,
    });

    const editCampaignPageUrl = getProp('editCampaignPage');

    const parameters = {
        page: state.currentPage,
        sort: state.sortColumn,
        direction: state.sortDirection,
        status: state.currentStatus,
    };

    const {data, isLoading, isError, isValidating} = useFetcher(getEndpoint('/get-campaigns', parameters), {
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

    const resetQueryParameters = (e) => {
        e.preventDefault();

        // Reset table sort state
        Table.resetSortState();

        setState((previousState) => {
            return {
                ...previousState,
                currentPage: 1,
                currentStatus: '',
                sortColumn: '',
                sortDirection: '',
            };
        });
    };

    const cloneCampaign = (id) => {
        setCloneCampaignModal((previousState) => {
            return {
                ...previousState,
                isCloning: true,
            };
        });

        API.post('/clone-campaign', {
            campaign_id: cloneCampaignModal.campaign_id,
        })
            .then(() => {
                mutate(getEndpoint('/get-campaigns', parameters));
                setCloneCampaignModal({
                    isCloning: false,
                    visible: false,
                });
            })
            .catch((error) =>
                setCloneCampaignModal((previousState) => {
                    const {message} = error.response.data;
                    return {
                        ...previousState,
                        message,
                        error: true,
                    };
                })
            );
    };

    const openCloneCampaignModal = ({campaign_id, campaign_title}) =>
        setCloneCampaignModal({
            isCloning: false,
            visible: true,
            campaign_id,
            campaign_title,
        });

    const closeCloneCampaignModal = () => {
        if (cloneCampaignModal.isCloning) {
            return;
        }

        setCloneCampaignModal({
            isCloning: false,
            visible: false,
        });
    };

    const getCloneCampaignModal = () => {
        const type = cloneCampaignModal?.type === 'error' ? 'error' : 'notice';

        return (
            <Modal type={type} handleClose={closeCloneCampaignModal}>
                {cloneCampaignModal.isCloning ? (
                    <Modal.Content align="center">
                        {cloneCampaignModal?.error ? (
                            <>
                                <h2>
                                    {cloneCampaignModal?.message
                                        ? cloneCampaignModal?.message
                                        : __('Something went wrong!', 'give-peer-to-peer')}
                                </h2>
                                <div>
                                    Try to{' '}
                                    <a onClick={() => window.location.reload()} href="#">
                                        reload
                                    </a>{' '}
                                    the browser
                                </div>
                            </>
                        ) : (
                            <>
                                <Spinner />
                                <div style={{marginTop: 20}}>{__('Duplicating Campaign', 'give-peer-to-peer')}...</div>
                            </>
                        )}
                    </Modal.Content>
                ) : (
                    <>
                        <Modal.Title>
                            <strong>
                                {__('Duplicate Campaign', 'give-peer-to-peer')} {cloneCampaignModal.campaign_title}?
                            </strong>
                            <Modal.CloseIcon onClick={closeCloneCampaignModal} />
                        </Modal.Title>
                        <Modal.Content>
                            <button
                                style={{marginRight: 20}}
                                className="button button-primary"
                                onClick={() => cloneCampaign(cloneCampaignModal.campaign_id)}
                            >
                                {__('Duplicate', 'give-peer-to-peer')}
                            </button>
                            <button className="button" onClick={closeCloneCampaignModal}>
                                {__('Cancel', 'give-peer-to-peer')}
                            </button>
                        </Modal.Content>
                    </>
                )}
            </Modal>
        );
    };

    const columns = [
        {
            key: 'campaign_title',
            label: __('Title', 'give-peer-to-peer'),
            sort: true,
            sortCallback: (direction) => setSortDirectionForColumn('campaign_title', direction),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'campaign_goal',
            label: __('Goal', 'give-peer-to-peer'),
            sort: true,
            sortCallback: (direction) => setSortDirectionForColumn('campaign_goal', direction),
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'teams',
            label: __('Teams', 'give-peer-to-peer'),
            append: true,
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'fundraisers',
            label: __('Fundraisers', 'give-peer-to-peer'),
            append: true,
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
        {
            key: 'campaign_status',
            label: __('Status', 'give-peer-to-peer'),
            append: true,
            styles: {
                display: 'flex',
                alignItems: 'center',
            },
        },
    ];

    const columnFilters = {
        campaign_title: (title, campaign) => {
            return (
                <div className={styles.titleColumn}>
                    <div className={styles.info}>
                        <strong>
                            <a href={`${editCampaignPageUrl}&id=${campaign.campaign_id}`}>{title}</a>
                        </strong>
                        <div className={styles.columnActions}>
                            <a href={`${editCampaignPageUrl}&id=${campaign.campaign_id}`}>
                                {__('Edit', 'give-peer-to-peer')}
                            </a>
                            <span>|</span>
                            <a href={campaign.campaign_url} target="_blank">
                                {__('View', 'give-peer-to-peer')}
                            </a>
                            <span>|</span>
                            <a
                                href="#"
                                onClick={(e) => {
                                    e.preventDefault();
                                    openCloneCampaignModal(campaign);
                                }}
                            >
                                {__('Duplicate', 'give-peer-to-peer')}
                            </a>
                        </div>
                    </div>
                </div>
            );
        },

        campaign_goal: (goal, campaign) => {
            return (
                <div className={styles.progressBarWrapper}>
                    <ProgressBar amount={parseFloat(campaign.campaign_amount_raised)} goal={parseInt(goal)} />
                    <div className={styles.amountRaised}>
                        {getAmountInCurrency(campaign.campaign_amount_raised)}
                        <span> {` of `} </span>
                        {getAmountInCurrency(goal)}
                        <span> {` goal `} </span>
                    </div>
                </div>
            );
        },

        teams: (val, campaign) => {
            return (
                <div className={styles.teamsContainer}>
                    {campaign.teams_total > 0 ? (
                        <span>
                            <a href={`${editCampaignPageUrl}&id=${campaign.campaign_id}&tab=teams`}>
                                {campaign.teams_total} {_n('Team', 'Teams', campaign.teams_total, 'give-peer-to-peer')}
                            </a>
                        </span>
                    ) : (
                        <span>0 {__('Teams', 'give-peer-to-peer')}</span>
                    )}

                    {!!campaign.hasTeamApprovals &&
                        (campaign.teams_pending > 0 ? (
                            <span>
                                <a href={`${editCampaignPageUrl}&id=${campaign.campaign_id}&tab=teams&status=pending`}>
                                    {campaign.teams_pending} {__('Pending approval', 'give-peer-to-peer')}
                                </a>
                            </span>
                        ) : (
                            <span>0 {__('Pending approval', 'give-peer-to-peer')}</span>
                        ))}
                </div>
            );
        },
        fundraisers: (val, campaign) => {
            return (
                <div className={styles.fundraisersContainer}>
                    {campaign.fundraisers_total > 0 ? (
                        <span>
                            <a href={`${editCampaignPageUrl}&id=${campaign.campaign_id}&tab=fundraisers`}>
                                {campaign.fundraisers_total}{' '}
                                {_n('Fundraiser', 'Fundraisers', campaign.fundraisers_total, 'give-peer-to-peer')}
                            </a>
                        </span>
                    ) : (
                        <span>0 {__('Fundraisers', 'give-peer-to-peer')}</span>
                    )}

                    {!!campaign.hasFundraiserApprovals &&
                        (campaign.fundraisers_pending > 0 ? (
                            <span>
                                <a
                                    href={`${editCampaignPageUrl}&id=${campaign.campaign_id}&tab=fundraisers&show=pending`}
                                >
                                    {campaign.fundraisers_pending} {__('Pending approval', 'give-peer-to-peer')}
                                </a>
                            </span>
                        ) : (
                            <span>0 {__('Pending approval', 'give-peer-to-peer')}</span>
                        ))}
                </div>
            );
        },
        campaign_status: (val, campaign) => {
            const types = {
                active: 'success',
                inactive: 'http',
                draft: 'warning',
            };
            const statuses = {
                active: __('Active', 'give-peer-to-peer'),
                inactive: __('Inactive', 'give-peer-to-peer'),
                draft: __('Draft', 'give-peer-to-peer'),
            };
            return (
                <div className={styles.statusContainer}>
                    <Label type={types[campaign.status]} text={statuses[campaign.status]} />
                </div>
            );
        },
    };

    // Initial load
    if (!state.initialLoad && isLoading) {
        return <LoadingNotice notice={__('Loading Campaigns', 'give-peer-to-peer')} />;
    }

    // Is error?
    if (isError) {
        return (
            <ErrorNotice notice={__('Unable to load campaigns. Check the logs for details.', 'give-peer-to-peer')} />
        );
    }

    if (!state.total && !isValidating && state.currentStatus == 'all') {
        return (
            <div className="give-blank-slate">
                <img
                    className="give-blank-slate__image"
                    src={`${getProp('giveRoot')}/assets/dist/images/give-icon-full-circle.svg`}
                    alt="GiveWP Icon"
                />
                <h2 className="give-blank-slate__heading">{__('Ready to start fundraising?', 'give-peer-to-peer')}</h2>
                <p className="give-blank-slate__message">
                    {__('Create your campaign to get started!', 'give-peer-to-peer')}
                </p>
                <a
                    className="give-blank-slate__cta button button-primary"
                    href={`${getProp('adminURL')}edit.php?post_type=give_forms&page=p2p-add-campaign`}
                    style={{padding: '0.5em 1.5em'}}
                >
                    {__('Create Campaign', 'give-peer-to-peer')}
                </a>
                <p className="give-blank-slate__help">
                    {__('Need help? Learn more about', 'give-peer-to-peer')}
                    {` `}
                    <a href="https://docs.givewp.com/addon-p2p" style={{textDecoration: 'none'}} target="_blank">
                        {__('P2P Campaigns', 'give-peer-to-peer')}
                    </a>
                    .
                </p>
            </div>
        );
    }

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
                    data-givewp-test="p2p-campaigns-table"
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

            {cloneCampaignModal.visible && getCloneCampaignModal()}
        </>
    );
};

export default Campaigns;
