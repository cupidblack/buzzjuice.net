import {useState} from 'react';
import {getEndpoint, useFetcher} from '@p2p/js/api';
import {useStore} from '@p2p/js/frontend/App/store';

import {debounce} from '../../utils';
import CampaignLogo from '@p2p/Components/CampaignLogo';
import {Page} from '@p2p/Components';
import {Spinner} from '@p2p/Components/Admin';
import StepNavigation from '@p2p/Components/StepNavigation';

import styles from '../Team/Leaderboard.module.scss';
import logoStyles from '../../../../../css/frontend/logoContainer.module.scss';
import useTintedBackgroundImage from './useTintedBackgroundImage';
import Card from '../Team/Card';
import Search from '../Team/Search';
import RankingCards from '@p2p/Components/RankingCard';

const {__, sprintf} = wp.i18n;

const SelectTeam = () => {
    const [{campaign}] = useStore();
    const [search, setSearch] = useState('');

    const {
        data: teams,
        isLoading: teamsLoading,
        isError: teamsError,
    } = useFetcher(getEndpoint('/get-campaign-teams-search', {campaign_id: campaign.campaign_id, search: search}));

    const tintedBackgroundImage = useTintedBackgroundImage({
        imageURL: campaign.campaign_image,
        hexColor: campaign.primary_color,
        opacity: 0.72,
    });

    const handleSearchChange = debounce(setSearch, 500);

    const hasTeams = teams?.length > 0;

    return (
        <Page title={sprintf(__('Start fundraising for the %s', 'give-peer-to-peer'), campaign.campaign_title)}>
            <Card as="article">
                <h1 className={styles.cardTitle}>
                    <div className={logoStyles.logoContainer}>
                        <CampaignLogo />
                    </div>
                    {__('Start fundraising for', 'give-peer-to-peer')}
                    <br />
                    {campaign.campaign_title}
                </h1>
                <StepNavigation />
                <div className={styles.leaderboard} style={{backgroundImage: tintedBackgroundImage}}>
                    <div className={styles.leaderboardHeader}>
                        <h2 className={styles.leaderboardTitle}>{__('Find a Team to Join', 'give-peer-to-peer')}</h2>
                        <Search
                            ariaControls="search-results"
                            inputId="team-search"
                            label={__('Searching for a team?', 'give-peer-to-peer')}
                            navLabel={__('leaderboard team search', 'give-peer-to-peer')}
                            onSearchChange={(event) => handleSearchChange(event.target.value)}
                            placeholder={__('Start typing the name of the team...', 'give-peer-to-peer')}
                            showSearch={true}
                        />
                    </div>
                    {teamsLoading ? (
                        <div className={styles.loadingIndicator}>
                            <Spinner size="large" />
                        </div>
                    ) : hasTeams ? (
                        <div
                            id="search-results"
                            aria-label={__('Search results', 'give-peer-to-peer')}
                            className={styles.results}
                        >
                            {teams.map(({id, name, story, profile_image, amount, goal}) => (
                                <RankingCards
                                    key={id}
                                    link={`${campaign.campaign_url}/team/${id}/join`}
                                    viewLabel={__('Join Team', 'give-peer-to-peer')}
                                    badge={null}
                                    showAvatar={true}
                                    showGoal={true}
                                    name={name}
                                    profileImage={profile_image}
                                    amount={Number(amount)}
                                    goal={Number(goal)}
                                    story={story}
                                />
                            ))}
                            key={id}
                            link={`${campaign.campaign_url}/team/${id}/join`}
                            name={name}
                            avatar={profile_image}
                            amount={Number(amount)}
                            goal={Number(goal)}
                            viewLabel={__('Join Team', 'give-peer-to-peer')}
                        </div>
                    ) : (
                        <div className={styles.loadingIndicator}>
                            {sprintf(__('Nothing found for search term "%s".', 'give-peer-to-peer'), search)}
                        </div>
                    )}
                </div>
            </Card>
        </Page>
    );
};

export default SelectTeam;
