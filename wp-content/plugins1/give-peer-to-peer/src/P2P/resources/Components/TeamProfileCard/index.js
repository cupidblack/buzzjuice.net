import {getEndpoint, useFetcher} from '@p2p/js/api';
import {ProfileCard} from '@p2p/Components/ProfileCard';
import Spinner from '@p2p/Components/Admin/Spinner';
import PlaceholderTeamAvatar from '@p2p/Components/PlaceholderTeamAvatar';

const {__, sprintf, _n} = wp.i18n;

const TeamProfileCard = ({teamId, campaignUrl}) => {
    const {data: team, isLoading, isError} = useFetcher(getEndpoint('/get-team', {team_id: teamId}));

    if (isLoading) {
        return <Spinner size="medium" />;
    }

    return (
        <ProfileCard>
            <ProfileCard.Image src={team.profile_image || PlaceholderTeamAvatar} alt={team.name} />
            <ProfileCard.Body>
                <ProfileCard.Title>{team.name}</ProfileCard.Title>

                <div>
                    {sprintf(
                        _n('%d Member', '%d Members', team.fundraiser_count, 'give-peer-to-peer'),
                        team.fundraiser_count
                    )}
                </div>
                <div>
                    {__('Team Captain', 'give-peer-to-peer')}: {` `}
                    <a href={`${campaignUrl}/fundraiser/${team.owner_id}`}>{team.captain}</a>
                </div>
            </ProfileCard.Body>
        </ProfileCard>
    );
};

export default TeamProfileCard;
