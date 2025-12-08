import {HashRouter as Router, Route, Switch} from 'react-router-dom';
// Components
import CreateFundraiser from './CreateFundraiser';
import EditFundraiser from '@p2p/js/admin/App/Fundraisers/EditFundraiser';
import FundraiserListTable from '@p2p/js/admin/App/Fundraisers/FundraiserListTable';
import RegisterFundraiser from '@p2p/js/admin/App/Fundraisers/RegisterFundraiser';
import ProcessComplete from '@p2p/js/admin/App/Fundraisers/ProcessComplete';

const Fundraisers = ({campaign}) => {
    return (
        <Router>
            <Switch>
                <Route exact path="/create-wp-user">
                    <RegisterFundraiser campaign={campaign} />
                </Route>
                <Route exact path="/create-fundraiser-:wp_user_id">
                    <CreateFundraiser campaign={campaign} />
                </Route>
                <Route exact path="/edit-fundraiser-:id">
                    <EditFundraiser campaign={campaign} />
                </Route>
                <Route exact path="/fundraiser-created-:id">
                    <ProcessComplete campaign={campaign} />
                </Route>
                <Route>
                    <FundraiserListTable campaign={campaign} />
                </Route>
            </Switch>
        </Router>
    );
};

export default Fundraisers;
