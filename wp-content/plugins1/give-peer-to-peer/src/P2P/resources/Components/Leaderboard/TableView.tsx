import React from 'react';

import Table, {TableBody, TableHead, TableRow} from '@p2p/Components/RankingTable';
import RankingTableBadge from '@p2p/Components/SVGImages/RankingTableBadge';

/**
 *
 *
 * @since 1.6.0
 */
const TableView = ({isCampaignBoard, page, data, ...props}) => {
    return (
        <Table>
            <TableHead columns={props.leaderboard.table.columns} />
            <TableBody>
                {data?.map(
                    (
                        {
                            id,
                            name,
                            profile_image,
                            campaign_logo,
                            amount,
                            goal,
                            fundraiser_total,
                            fundraisers_total,
                            teams_total,
                            team_captain,
                            campaign_goal,
                            campaign_amount_raised,
                            campaign_title,
                            campaign_url,
                        },
                        index
                    ) => {
                        const key = `give-p2p-table-row-${name ?? campaign_title ?? id}`;
                        const rowName = name ?? campaign_title;
                        const rowGoal = Number(goal ?? campaign_goal);
                        const rowAmount = Number(amount ?? campaign_amount_raised);
                        const rowLink = isCampaignBoard
                            ? `${campaign_url}`
                            : `${props.campaign?.url}/${props.leaderboard.name}/${id}`;
                        const rowBadge = isCampaignBoard ? null : index < 3 && page === 1 ? (
                            <RankingTableBadge rank={index} />
                        ) : (
                            <p>{index + 1}</p>
                        );
                        const rowImage = profile_image ?? campaign_logo;

                        return (
                            <TableRow
                                key={key}
                                columns={props.leaderboard.table.columns}
                                name={rowName}
                                leaderboardName={props.leaderboard.name}
                                profileImage={rowImage}
                                amount={rowAmount}
                                goal={rowGoal}
                                badge={rowBadge}
                                link={rowLink}
                                fundraiserTotal={fundraiser_total}
                                fundraisersTotal={fundraisers_total}
                                teamsTotal={teams_total}
                                teamCaptain={team_captain}
                            />
                        );
                    }
                )}
            </TableBody>
        </Table>
    );
};

export default TableView;
