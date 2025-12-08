import React, {useState} from 'react';

import {__, sprintf} from '@wordpress/i18n';
import cx from 'classnames';

import RankingHeader from '@p2p/Components/RankingHeader';
import Pagination from '@p2p/Components/RankingPagination';
import {LoadingIndicator} from '@p2p/js/frontend/App/Screens/Campaign/LoadingIndicator';
import {NoResultContainer} from '@p2p/js/frontend/App/Screens/Campaign/NoResultContainer';
import {UsersIcon} from '@p2p/Components/Icons';
import {EmptySection} from '@p2p/Components/EmptySection';
import GridView from '@p2p/Components/Leaderboard/GridView';
import TableView from '@p2p/Components/Leaderboard/TableView';
import EmptyContainerSearchIcon from '@p2p/Components/SVGImages/EmptyContainerSearchIcon';

import {debounce} from '@p2p/js/frontend/App/utils';
import useAsyncFetch from '@p2p/js/blocks/hooks/useAsyncFetch';

import styles from './style.module.scss';

export interface LeaderboardProps {
    initialState: {
        id: number;
        per_page: number;
        status?: string;
        show_avatar?: boolean;
        show_goal?: boolean;
        show_description?: boolean;
        show_team_info?: boolean;
        show_campaign_info?: boolean;
        show_pagination?: boolean;
        columns?: 'max' | 'fullWidth' | 'double' | 'triple';
        layout?: 'grid' | 'table';
    };
    leaderboard: {
        name: string;
        id: string;
        endpoint: string;
        title: string;
        table?: {
            columns: Array<object>;
        };
    };
    campaign?: {
        url: string;
        page?: boolean;
    };
}

/**
 *
 *
 * @since 1.6.0
 */
const Leaderboard = (props: LeaderboardProps) => {
    const {columns, layout, per_page, status} = props.initialState;
    const {title, endpoint} = props.leaderboard;

    const isFullWidthCard = columns === 'fullWidth';
    const isCampaignBoard = props.leaderboard.id === 'campaign-list';
    const displayRankingSearch = !isCampaignBoard;

    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');

    const leaderboardParams = {
        campaign_id: props.initialState.id,
        search: search,
        page: page,
        per_page: per_page,
    };

    const campaignsParams = {
        page: page,
        per_page: per_page,
        sort: '',
        direction: '',
        status: status,
    };

    const parameters = isCampaignBoard ? campaignsParams : leaderboardParams;

    const componentData = useAsyncFetch(endpoint, parameters);
    const {data, isLoading, isError} = componentData;

    const total = componentData.response ? componentData.response.total : 0;
    const count = componentData.response ? componentData.response.count : 0;

    const handleSearchChange = debounce((value) => setSearch(value), 500, false);

    return (
        <div
            className={cx(styles.container, {
                [styles.maxSizeContainer]: isFullWidthCard,
            })}
        >
            {displayRankingSearch && (
                <RankingHeader
                    onSearchChange={(e) => {
                        handleSearchChange(e.target.value);
                    }}
                    showSearch
                    title={__(`Top ${title}`, 'give-peer-to-peer')}
                    searchLabel={__(`Looking for a ${props.leaderboard.name}'s?`, 'give-peer-to-peer')}
                    searchPlaceholder={__(
                        `Start typing a ${props.leaderboard.name}'s name to search.`,
                        'give-peer-to-peer'
                    )}
                    isCampaignPage={props.campaign.page}
                    viewAllLink={`${props.leaderboard.name}-leaderboard`}
                    viewAllText={`View all ${props.leaderboard.name}s`}
                />
            )}
            {isLoading ? (
                <LoadingIndicator />
            ) : (
                <>
                    {data && data.length ? (
                        <>
                            {layout === 'table' ? (
                                <TableView page={page} isCampaignBoard={isCampaignBoard} data={data} {...props} />
                            ) : (
                                <GridView
                                    page={page}
                                    isCampaignBoard={isCampaignBoard}
                                    search={search}
                                    columns={columns}
                                    data={data}
                                    {...props}
                                />
                            )}
                            {props.initialState.show_pagination && (
                                <Pagination
                                    perPage={props.initialState.per_page}
                                    totalPages={total}
                                    currentPage={page}
                                    count={count}
                                    onPageChange={setPage}
                                    label={__(`${name}'s`, 'give-peer-to-peer')}
                                />
                            )}
                        </>
                    ) : isError ? (
                        <NoResultContainer>
                            {__('Something went wrong while loading...', 'give-peer-to-peer')}
                        </NoResultContainer>
                    ) : !search.length && props.leaderboard.name === 'team' && !data?.length ? (
                        <NoResultContainer>
                            <EmptySection
                                icon={<UsersIcon height={66} width={66} />}
                                title={__('Be the first to create a team!', 'give-peer-to-peer')}
                                href={`${props.campaign?.url}/register/captain`}
                                subtitle={__(
                                    'Create a team, invite your friends and family, and help make an impact.',
                                    'give-peer-to-peer'
                                )}
                                buttonText={__('Create Team', 'give-peer-to-peer')}
                            />
                        </NoResultContainer>
                    ) : !search.length && props.leaderboard.name === 'fundraiser' && !data?.length ? (
                        <NoResultContainer>
                            <EmptySection
                                icon={<UsersIcon height={66} width={66} />}
                                title={__(`Join as a fundraiser!`, 'give-peer-to-peer')}
                                href={`${props.campaign?.url}/register/individual`}
                                subtitle={__(
                                    'Help fundraise as an individual by joining the campaign and sharing.',
                                    'give-peer-to-peer'
                                )}
                                buttonText={__('Start Fundraising', 'give-peer-to-peer')}
                            />
                        </NoResultContainer>
                    ) : (
                        <NoResultContainer>
                            {search && (
                                <EmptySection
                                    icon={<EmptyContainerSearchIcon />}
                                    title={sprintf(
                                        __('Nothing found for search term "%s".', 'give-peer-to-peer'),
                                        search
                                    )}
                                    subtitle={__('Try Searching a different name.', 'give-peer-to-peer')}
                                    buttonText={null}
                                />
                            )}
                        </NoResultContainer>
                    )}
                </>
            )}
        </div>
    );
};

export default Leaderboard;
