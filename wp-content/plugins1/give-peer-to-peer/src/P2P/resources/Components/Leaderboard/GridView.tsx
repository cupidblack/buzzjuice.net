import {Grid, GridItem} from '@p2p/Components/RankingCardGrid';
import {RankingBadge} from '@p2p/Components/RankingBadge';
import RankingCard from '@p2p/Components/RankingCard';
import React from 'react';

/**
 *
 *
 * @since 1.6.0
 */
const GridView = ({columns, search, isCampaignBoard, page, data, ...props}) => {
    return (
        <Grid columns={columns}>
            {data?.map(
                (
                    {
                        id,
                        campaign_id,
                        name,
                        campaign_title,
                        profile_image,
                        campaign_logo,
                        amount,
                        campaign_amount_raised,
                        goal,
                        campaign_goal,
                        story,
                        fundraiser_total,
                        fundraisers_total,
                        team_total,
                        teams_total,
                        team_captain,
                        campaign_long_desc,
                        campaign_url,
                    },
                    index
                ) => {
                    const cardName = name ?? campaign_title;
                    const cardLink = isCampaignBoard
                        ? `${campaign_url}`
                        : `${props.campaign?.url}/${props.leaderboard.name}/${id}`;
                    const cardGoal = Number(goal ?? campaign_goal);
                    const cardAmount = Number(amount ?? campaign_amount_raised);
                    const cardStory = story ?? campaign_long_desc;
                    const cardBadge = isCampaignBoard
                        ? null
                        : !search && page === 1 && <RankingBadge rank={index + 1} />;
                    const cardImage = profile_image ?? campaign_logo;

                    return (
                        <GridItem key={`give-p2p-grid-item-${id}-${index}`} id={`give-p2p-grid-item-${id}`}>
                            <RankingCard
                                link={cardLink}
                                name={cardName}
                                profileImage={cardImage}
                                amount={cardAmount}
                                goal={cardGoal}
                                badge={cardBadge}
                                viewLabel={`View ${props.leaderboard.name}`}
                                story={cardStory}
                                showAvatar={props.initialState.show_avatar}
                                showGoal={props.initialState.show_goal}
                                showDescription={props.initialState.show_description}
                                showTeamInfo={props.initialState.show_team_info}
                                showCampaignDetails={props.initialState.show_campaign_info}
                                columns={columns}
                                fundraiserTotal={fundraiser_total}
                                fundraisersTotal={fundraisers_total}
                                teamTotal={team_total}
                                teamsTotal={teams_total}
                                teamCaptain={team_captain}
                                isCampaignBoard={isCampaignBoard}
                            />
                        </GridItem>
                    );
                }
            )}
        </Grid>
    );
};

export default GridView;
