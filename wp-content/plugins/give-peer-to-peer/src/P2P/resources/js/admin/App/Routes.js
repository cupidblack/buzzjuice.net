import Campaigns from './Campaigns';
import Teams from './Teams';
import Fundraisers from './Fundraisers';

const Routes = ({campaign, screen}) => {
    switch (screen) {
        case 'teams':
            return <Teams campaign={campaign} />;
        case 'fundraisers':
            return <Fundraisers campaign={campaign} />;
        default:
            return <Campaigns />;
    }
};

export default Routes;
