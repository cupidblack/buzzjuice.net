import {Link} from 'react-router-dom';
import {useStore} from '@p2p/js/frontend/App/store';
import {getEndpoint, useFetcher} from '@p2p/js/api';
import {getAmountInCurrency} from '@p2p/js/utils';
import {Button, Page} from '@p2p/Components';
import ProfileHeader from '@p2p/Components/ProfileHeader';
import {ListTable} from '@p2p/Components/ListTable';
import {SupportCard} from '@p2p/Components/SupportCard';
import {Tabs} from '@p2p/Components/Tabs';
import {Spinner} from '@p2p/Components/Admin';
import {DonorsList} from '@p2p/Components/DonorsList';
import {SponsorGrid} from '@p2p/Components/SponsorGrid';
import StatsContainer from '@p2p/Components/StatsContainer';
import {EmailIcon2, FacebookIcon, TwitterIcon} from '@p2p/Components/Icons';
import ProfileLogin from '@p2p/Components/ProfileLogin';

import styles from './styles.module.scss';
import PlaceholderTeamAvatar from '@p2p/Components/PlaceholderTeamAvatar';
import {Grid, GridItem} from '@p2p/Components/RankingCardGrid';
import RankingCards from '@p2p/Components/RankingCard';

const {__, _n, sprintf} = wp.i18n;

const Team = () => {
    const [{campaign, team, donations, teamStats, auth}] = useStore();

    const {
        data: teamMembers,
        isLoading: teamMembersLoading,
        isError: teamMembersError,
    } = useFetcher(getEndpoint('/get-team-fundraisers-all', {team_id: team.id}));
    const {
        data: donors,
        isLoading: donorsLoading,
        isError: donorsError,
    } = useFetcher(getEndpoint('/get-team-top-donors', {team_id: team.id}));

    const isPublic = team.access === 'public';
    const shareURL = campaign.campaign_url + '/team/' + team.id;
    const shareText = sprintf(
        __('Support %s in fundraising for %s', 'give-peer-to-peer'),
        team.name,
        campaign.campaign_title
    );

    const handleClickShareFacebook = () => {
        // Open new window with prompt for Facebook sharing
        window.Give.share.fn.facebook(shareURL);
    };

    const handleClickShareTwitter = () => {
        // Open new window with prompt for Twitter sharing
        window.Give.share.fn.twitter(shareURL, shareText);
    };

    const getDonorsTabContent = () => {
        if (donorsError) {
            return (
                <div style={{padding: 20, textAlign: 'center', color: 'red'}}>
                    {__('Something went wrong', 'give-peer-to-peer')}
                </div>
            );
        }

        if (donorsLoading) {
            return (
                <div style={{textAlign: 'center', padding: 20}}>
                    <Spinner />
                </div>
            );
        }

        return <DonorsList donors={donors} />;
    };

    return (
        <Page
            title={sprintf(
                __('%s fundraising in support of %s', 'give-peer-to-peer'),
                team.name,
                campaign.campaign_title
            )}
            className={styles.page}
        >
            {auth && auth.user_id == team.captain_user_id && (
                <Link to="update" className={styles.editlink}>
                    {__('Edit team', 'give-peer-to-peer')}
                    &nbsp;
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.9844 4.49878L14.3333 6.84774C14.4323 6.9467 14.4323 7.10815 14.3333 7.20711L8.64583 12.8946L6.22917 13.1628C5.90625 13.1993 5.63281 12.9259 5.66927 12.6029L5.9375 10.1863L11.625 4.49878C11.724 4.39982 11.8854 4.39982 11.9844 4.49878ZM16.2031 3.90243L14.9323 2.63159C14.5365 2.23576 13.8932 2.23576 13.4948 2.63159L12.5729 3.55347C12.474 3.65243 12.474 3.81388 12.5729 3.91284L14.9219 6.2618C15.0208 6.36076 15.1823 6.36076 15.2812 6.2618L16.2031 5.33992C16.599 4.94149 16.599 4.29826 16.2031 3.90243ZM11.5 11.3477V13.9988H3.16667V5.66545H9.15104C9.23437 5.66545 9.3125 5.63159 9.3724 5.5743L10.4141 4.53263C10.612 4.33472 10.4714 3.99878 10.1927 3.99878H2.75C2.0599 3.99878 1.5 4.55868 1.5 5.24878V14.4154C1.5 15.1055 2.0599 15.6654 2.75 15.6654H11.9167C12.6068 15.6654 13.1667 15.1055 13.1667 14.4154V10.3061C13.1667 10.0274 12.8307 9.8894 12.6328 10.0847L11.5911 11.1264C11.5339 11.1863 11.5 11.2644 11.5 11.3477Z" />
                    </svg>
                </Link>
            )}

            <ProfileHeader
                name={team.name}
                campaign={campaign}
                avatar={team.profile_image || PlaceholderTeamAvatar}
                SecondaryButton={
                    isPublic && (
                        <Button as={Link} to="join" color={Button.colors.secondary}>
                            {__('Join Team', 'give-peer-to-peer')}
                        </Button>
                    )
                }
            >
                <StatsContainer coverImage={campaign.campaign_image}>
                    <StatsContainer.Stats
                        goal={Number(team.goal)}
                        amountRaised={Number(teamStats.raisedAmount)}
                        amountRaisedPercentage={Number(teamStats.raisedPercentage)}
                        showProgressDetails={true}
                        items={[
                            {
                                name: __('avg. donation', 'give-peer-to-peer'),
                                amount: getAmountInCurrency(teamStats.averageAmount),
                            },
                            {
                                name: _n('donation', 'donations', teamStats.donationsCount, 'give-peer-to-peer'),
                                amount: teamStats.donationsCount,
                            },
                            {
                                name: _n('donor', 'donors', teamStats.donorsCount, 'give-peer-to-peer'),
                                amount: teamStats.donorsCount,
                            },
                        ]}
                    />

                    <StatsContainer.Content>
                        <StatsContainer.InfoText>
                            {__('Help us spread the word!', 'give-peer-to-peer')}
                        </StatsContainer.InfoText>

                        <StatsContainer.ShareButtons>
                            <button onClick={handleClickShareFacebook}>
                                <FacebookIcon /> Facebook
                            </button>
                            <button onClick={handleClickShareTwitter}>
                                <TwitterIcon /> Twitter
                            </button>
                            <button
                                onClick={() =>
                                    (window.location = sprintf('mailto:?subject=%s&body=%s', shareText, shareURL))
                                }
                            >
                                <EmailIcon2 /> Email
                            </button>
                        </StatsContainer.ShareButtons>
                    </StatsContainer.Content>
                </StatsContainer>

                {team.story.trim().length > 0 && (
                    <>
                        <ProfileHeader.SubTitle title={__("Why we're fundraising", 'give-peer-to-peer')} />
                        <ProfileHeader.Story story={team.story} />
                    </>
                )}
            </ProfileHeader>

            {!auth.is_logged_in && <ProfileLogin label={__('Team Captain?', 'give-peer-to-peer')} />}

            <Tabs
                tabs={[
                    {
                        label: __('Recent Donations', 'give-peer-to-peer'),
                        content: (
                            <ListTable
                                donations={donations}
                                emptyTitle={sprintf(__('Be the first to support %s!', 'give-peer-to-peer'), team.name)}
                                emptyContent={
                                    <p>
                                        {sprintf(
                                            __('Help %s reach their goal to support', 'give-peer-to-peer'),
                                            team.name
                                        )}{' '}
                                        <a href={campaign.campaign_url}>{campaign.campaign_title}</a>.
                                    </p>
                                }
                            />
                        ),
                    },
                    {
                        label: __('Top Donors', 'give-peer-to-peer'),
                        content: getDonorsTabContent(),
                    },
                ]}
            />

            <h2 className={styles.header}>{__('Team Members', 'give-peer-to-peer')}</h2>

            <Grid columns={'triple'}>
                {!teamMembersLoading &&
                    !teamMembersError &&
                    teamMembers &&
                    teamMembers.map(({id, name, story, profile_image, amount, goal}) => (
                        <GridItem key={id}>
                            <RankingCards
                                link={`${campaign.campaign_url}/fundraiser/${id}`}
                                viewLabel={__('View Fundraiser', 'give-peer-to-peer')}
                                badge={null}
                                showAvatar={true}
                                showGoal={true}
                                showDescription={true}
                                profileImage={profile_image}
                                amount={Number(amount)}
                                goal={Number(goal)}
                                story={story}
                                name={name}
                            />
                        </GridItem>
                    ))}
            </Grid>

            {'enabled' === campaign.sponsors_enabled && <SponsorGrid />}

            <h2 className={styles.header}>{__('Support this team', 'give-peer-to-peer')}</h2>

            <SupportCard
                title={team.name}
                campaign={campaign}
                avatar={team.profile_image || PlaceholderTeamAvatar}
                amount={Number(teamStats.raisedAmount)}
                goal={Number(team.goal)}
                amountRaisedPercentage={Number(teamStats.raisedPercentage)}
                SecondaryButton={
                    isPublic && (
                        <Button as={Link} to="join" color={Button.colors.secondary} style={{fontSize: '1rem'}}>
                            {__('Join Team', 'give-peer-to-peer')}
                        </Button>
                    )
                }
            />
        </Page>
    );
};

export default Team;
