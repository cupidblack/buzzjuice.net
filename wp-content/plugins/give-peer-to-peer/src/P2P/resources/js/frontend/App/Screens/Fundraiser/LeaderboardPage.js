import {useState} from 'react';
import {useStore} from '@p2p/js/frontend/App/store';
import {getEndpoint, useFetcher} from '@p2p/js/api';

import {debounce} from '../../utils';
import {Spinner} from '@p2p/Components/Admin';
import {Page} from '@p2p/Components';
import {EmptySection} from '@p2p/Components/EmptySection';
import {UsersIcon} from '@p2p/Components/Icons';
import Pagination from '@p2p/Components/Pagination';
import {RankingBadge} from '@p2p/Components/RankingBadge';

import Card from '../Team/Card';
import Search from '../Team/Search';
import useTintedBackgroundImage from '../Team/useTintedBackgroundImage';
import styles from '../Team/Leaderboard.module.scss';
import RankingCards from '@p2p/Components/RankingCard';

const {__, sprintf} = wp.i18n;

const LoadingIndicator = () => (
    <div className={styles.loadingIndicator}>
        <Spinner size="large" />
    </div>
);

const NoResultContainer = ({children}) => <div className={styles.loadingIndicator}>{children}</div>;

const LeaderboardPage = () => {
    const perPage = 9;

    const [{campaign}] = useStore();
    const [page, setPage] = useState(1);
    const [fundraiserSearch, setFundraiserSearch] = useState('');

    const tintedBackgroundImage = useTintedBackgroundImage({
        imageURL: campaign.campaign_image,
        hexColor: campaign.primary_color,
        opacity: 0.72,
    });

    const {
        response,
        data: fundraiser,
        isLoading: fundraiserSearchLoading,
        isError: fundraiserSearchError,
    } = useFetcher(
        getEndpoint('/get-campaign-fundraisers-search', {
            campaign_id: campaign.campaign_id,
            search: fundraiserSearch,
            page: page,
            per_page: perPage,
        })
    );

    const handleFundraiserSearchChange = debounce(setFundraiserSearch, 500);

    const total = response ? response.total : 0;
    const count = response ? response.count : 0;

    const hasFundraisers = fundraiser && fundraiser?.length > 0;
    const showSearch = !!((fundraiser && fundraiser.length) || fundraiserSearch);

    return (
        <Page title={sprintf(__('%s Fundraisers', 'give-peer-to-peer'), campaign.campaign_title)}>
            <Card as="article">
                <h1 className={styles.cardTitle}>
                    {__('Start fundraising for', 'give-peer-to-peer')}
                    <br />
                    {campaign.campaign_title}
                </h1>
                <div className={styles.leaderboard} style={{backgroundImage: tintedBackgroundImage}}>
                    <div className={styles.leaderboardHeader}>
                        <h2 className={styles.leaderboardTitle}>{__('All Fundraisers', 'give-peer-to-peer')}</h2>
                        <Search
                            ariaControls="search-results"
                            inputId="fundraiser-search"
                            label={__('Searching for a fundraiser?', 'give-peer-to-peer')}
                            navLabel={__('leaderboard fundraiser search', 'give-peer-to-peer')}
                            onSearchChange={(event) => handleFundraiserSearchChange(event.target.value)}
                            placeholder={__("Start typing a fundraiser's name to search.", 'give-peer-to-peer')}
                            showSearch={showSearch}
                        />
                    </div>

                    {fundraiserSearchLoading ? (
                        <LoadingIndicator />
                    ) : hasFundraisers ? (
                        <div
                            id="search-results"
                            aria-label={__('Search results', 'give-peer-to-peer')}
                            className={styles.results}
                        >
                            {fundraiser.map(({id, story, name, profile_image, amount, goal}, index) => (
                                <RankingCards
                                    link={`${campaign.campaign_url}/fundraiser/${id}`}
                                    viewLabel={__('View Fundraiser', 'give-peer-to-peer')}
                                    badge={!fundraiserSearch && 1 === page ? <RankingBadge rank={index + 1} /> : null}
                                    showAvatar={true}
                                    showGoal={true}
                                    showDescription={true}
                                    profileImage={profile_image}
                                    amount={Number(amount)}
                                    goal={Number(goal)}
                                    story={story}
                                    name={name}
                                />
                            ))}
                        </div>
                    ) : (
                        <NoResultContainer>
                            {fundraiserSearchError || page > 1 ? (
                                __('Something went wrong while loading campaign fundraisers.', 'give-peer-to-peer')
                            ) : fundraiserSearch ? (
                                sprintf(
                                    __('Nothing found for search term "%s".', 'give-peer-to-peer'),
                                    fundraiserSearch
                                )
                            ) : (
                                <EmptySection
                                    icon={<UsersIcon height={66} width={66} />}
                                    title={__('Be the first to join as a fundraiser!', 'give-peer-to-peer')}
                                    href={'register/captain'}
                                    subtitle={__(
                                        'Invite your friends and family, and help make an impact.',
                                        'give-peer-to-peer'
                                    )}
                                    buttonText={__('Start Fundraising', 'give-peer-to-peer')}
                                />
                            )}
                        </NoResultContainer>
                    )}

                    <Pagination
                        perPage={perPage}
                        total={total}
                        currentPage={page}
                        count={count}
                        onPageChange={setPage}
                        label={__('Fundraisers', 'give-peer-to-peer')}
                    />
                </div>
            </Card>
        </Page>
    );
};

export default LeaderboardPage;
