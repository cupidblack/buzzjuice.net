import {HashRouter as Router, Route, Switch} from 'react-router-dom';
// Components
import EditTeam from './EditTeam';
import TeamsListTable from './TeamsListTable';
import CreateTeam from './CreateTeam';
import TeamCaptainForm from '@p2p/js/admin/App/Teams/TeamCaptainForm';
import InviteTeamMembersForm from '@p2p/js/admin/App/Teams/InviteTeamMembersForm';
import ProcessComplete from '@p2p/js/admin/App/Teams/ProcessComplete';

const Teams = ({campaign}) => {
    return (
        <Router>
            <Switch>
                <Route exact path="/create-team">
                    <CreateTeam campaign={campaign} />
                </Route>
                <Route exact path="/edit-team-:id">
                    <EditTeam campaign={campaign} />
                </Route>
                <Route exact path="/add-team-captain-:id">
                    <TeamCaptainForm campaign={campaign} />
                </Route>
                <Route exact path="/invite-team-members-:id">
                    <InviteTeamMembersForm campaign={campaign} />
                </Route>
                <Route exact path="/team-created-:id">
                    <ProcessComplete campaign={campaign} />
                </Route>
                <Route>
                    <TeamsListTable campaign={campaign} />
                </Route>
            </Switch>
        </Router>
    );
};

export default Teams;
