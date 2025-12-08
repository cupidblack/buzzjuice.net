import {BrowserRouter, Route, Switch} from 'react-router-dom';
import {useSelector} from './store';
// Components
import {FundraiserRoute, GuestRoute, RegistrationRoute, TeamRegistrationRoute} from './Protected';
import {Campaign, DonateCampaign} from './Screens/Campaign';
import {Register, RegisterFundraiser, UpdateFundraiserProfile} from './Screens/Register';
import {Login, LostPassword, PasswordResetSent} from './Screens/Login';
import {
    CreateOrUpdateTeam,
    DonateTeam,
    JoinTeam,
    LeaderboardPage as TeamLeaderboardPage,
    SelectTeam,
    Team,
    Teams,
} from './Screens/Team';
import {
    DonateFundraiser,
    Fundraiser,
    LeaderboardPage as FundraiserLeaderboardPage,
    StartFundraising,
} from './Screens/Fundraiser';

const Routes = () => {
    const {base_url} = useSelector((state) => state.campaign);

    return (
        <BrowserRouter basename={base_url}>
            <Switch>
                <Route exact path="/" component={Campaign} />
                <Route exact path="/team-leaderboard/" component={TeamLeaderboardPage} />
                <Route exact path="/fundraiser-leaderboard/" component={FundraiserLeaderboardPage} />
                <Route exact path="/donate/" component={DonateCampaign} />
                <Route exact path="/team/:team_id/" component={Team} />
                <Route exact path="/team/:team_id/join/" component={JoinTeam} />
                <Route exact path="/team/:team_id/donate" component={DonateTeam} />
                <Route exact path="/fundraiser/:fundraiser_id/" component={Fundraiser} />
                <Route exact path="/fundraiser/:fundraiser_id/donate/" component={DonateFundraiser} />

                <FundraiserRoute exact path="/start-fundraising/" component={StartFundraising} />

                <FundraiserRoute exact path="/register/create-profile/">
                    <UpdateFundraiserProfile redirect={(history) => history.push(`/start-fundraising/`)} />
                </FundraiserRoute>
                <FundraiserRoute exact path="/team/:team_id/update">
                    <CreateOrUpdateTeam isUpdatingTeam={true} />
                </FundraiserRoute>
                <FundraiserRoute exact path="/fundraiser/:fundraiser_id/update/" component={UpdateFundraiserProfile} />
                <FundraiserRoute exact path="/select-team/" redirect="/start-fundraising/" component={SelectTeam} />

                <RegistrationRoute exact path="/register/" redirect="/start-fundraising/" component={Register} />
                <RegistrationRoute exact path="/register/teams/" redirect="/start-fundraising/" component={Teams} />

                <RegistrationRoute exact path="/register/individual/" redirect="/start-fundraising/">
                    <RegisterFundraiser isCaptain={false} />
                </RegistrationRoute>

                <TeamRegistrationRoute exact path="/register/captain/" redirect="/start-fundraising/">
                    <RegisterFundraiser isCaptain={true} />
                </TeamRegistrationRoute>
                <TeamRegistrationRoute exact path="/create-team/" redirect="/start-fundraising/">
                    <CreateOrUpdateTeam />
                </TeamRegistrationRoute>

                <GuestRoute exact path="/login/" component={Login} />
                <GuestRoute exact path="/lost-password/" component={LostPassword} />
                <GuestRoute exact path="/lost-password/sent" component={PasswordResetSent} />
            </Switch>
        </BrowserRouter>
    );
};

export default Routes;
