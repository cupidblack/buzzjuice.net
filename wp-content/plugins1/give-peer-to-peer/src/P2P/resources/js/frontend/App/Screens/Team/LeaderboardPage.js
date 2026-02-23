import {useState} from 'react';
import {useStore} from '@p2p/js/frontend/App/store';
import {getEndpoint, useFetcher} from '@p2p/js/api';

import {Spinner} from '@p2p/Components/Admin';
import {EmptySection} from '@p2p/Components/EmptySection';
import {UsersIcon} from '@p2p/Components/Icons';
import Pagination from '@p2p/Components/Pagination';
import {Page} from '@p2p/Components';

import {debounce} from '../../utils';
import styles from './Leaderboard.module.scss';
import useTintedBackgroundImage from './useTintedBackgroundImage';
import Card from './Card';
import Search from './Search';
import RankingCards from '@p2p/Components/RankingCard';
import {RankingBadge} from '@p2p/Components/RankingBadge';

const {__, sprintf} = wp.i18n;

/**
 * Team leaderboard
 */
const LeaderboardPage = () => {
    const perPage = 9;

    const [{campaign}] = useStore();
    const [page, setPage] = useState(1);
    const [teamSearch, setTeamSearch] = useState('');

    const tintedBackgroundImage = useTintedBackgroundImage({
        imageURL: campaign.campaign_image,
        hexColor: campaign.primary_color,
        opacity: 0.72,
    });

    const handleTeamSearchChange = debounce(setTeamSearch, 500);

    const {
        response,
        data: teams,
        isLoading: teamSearchLoading,
        isError: teamSearchError,
    } = useFetcher(
        getEndpoint('/get-campaign-teams-search', {
            campaign_id: campaign.campaign_id,
            search: teamSearch,
            showClosedTeams: true,
            page: page,
            per_page: perPage,
        })
    );

    const total = response ? response.total : 0;
    const count = response ? response.count : 0;

    const hasTeams = teams && teams?.length > 0;
    const showSearch = !!((teams && teams.length) || teamSearch);

    return (
        <Page title={sprintf(__('%s Teams', 'give-peer-to-peer'), campaign.campaign_title)}>
            <Card as="article">
                <h1 className={styles.cardTitle}>
                    {__('Start fundraising for', 'give-peer-to-peer')}
                    <br />
                    {campaign.campaign_title}
                </h1>

                <div className={styles.leaderboard} style={{backgroundImage: tintedBackgroundImage}}>
                    <div className={styles.leaderboardHeader}>
                        <h2 className={styles.leaderboardTitle}>{__('All Teams', 'give-peer-to-peer')}</h2>
                        <Search
                            ariaControls="search-results"
                            inputId="team-search"
                            label={__('Searching for a team?', 'give-peer-to-peer')}
                            navLabel={__('leaderboard team search', 'give-peer-to-peer')}
                            onSearchChange={(event) => handleTeamSearchChange(event.target.value)}
                            placeholder={__('Start typing the name of the team...', 'give-peer-to-peer')}
                            showSearch={showSearch}
                        />
                    </div>

                    {teamSearchLoading ? (
                        <div className={styles.loadingIndicator}>
                            <Spinner size="large" />
                        </div>
                    ) : hasTeams ? (
                        <div
                            id="search-results"
                            aria-label={__('Search results', 'give-peer-to-peer')}
                            className={styles.results}
                        >
                            {teams.map(({id, name, story, profile_image, amount, goal}, index) => (
                                <RankingCards
                                    link={`${campaign.campaign_url}/team/${id}`}
                                    viewLabel={__('View Team', 'give-peer-to-peer')}
                                    badge={!teamSearch && 1 === page ? <RankingBadge rank={index + 1} /> : null}
                                    showAvatar={true}
                                    showGoal={true}
                                    showTeamInfo={true}
                                    profileImage={profile_image}
                                    amount={Number(amount)}
                                    goal={Number(goal)}
                                    story={story}
                                    name={name}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className={styles.loadingIndicator}>
                            {teamSearchError || page > 1 ? (
                                __('Something went wrong while loading campaign teams.', 'give-peer-to-peer')
                            ) : teamSearch ? (
                                sprintf(__('Nothing found for search term "%s".', 'give-peer-to-peer'), teamSearch)
                            ) : (
                                <EmptySection
                                    icon={<UsersIcon height={66} width={66} />}
                                    title={__('Be the first to create a team!', 'give-peer-to-peer')}
                                    href={`${campaign.campaign_url}/register/captain`}
                                    subtitle={__(
                                        'Create a team, invite your friends and family, and help make an impact.',
                                        'give-peer-to-peer'
                                    )}
                                    buttonText={__('Create Team', 'give-peer-to-peer')}
                                />
                            )}
                        </div>
                    )}

                    <Pagination
                        perPage={perPage}
                        total={total}
                        currentPage={page}
                        count={count}
                        onPageChange={setPage}
                        label={__('Teams', 'give-peer-to-peer')}
                    />
                </div>
            </Card>
        </Page>
    );
};

export default LeaderboardPage;
