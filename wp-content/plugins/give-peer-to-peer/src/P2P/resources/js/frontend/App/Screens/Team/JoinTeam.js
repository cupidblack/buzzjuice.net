import { useSelector } from '@p2p/js/frontend/App/store';
// Components
import JoinTeamRegister from './JoinTeamRegister';
import JoinTeamFundraiser from './JoinTeamFundraiser';

const JoinTeam = () => {
	const auth = useSelector( state => state.auth );

	if ( auth.is_logged_in && auth.fundraiser_id ) {
		return <JoinTeamFundraiser />;
	}

	return <JoinTeamRegister />;
};

export default JoinTeam;
