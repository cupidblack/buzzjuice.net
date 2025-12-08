import ReactDOM from 'react-dom';
import OverviewPage from '../app/overview-page';

const container = document.getElementById( 'give-funds-overview' );

if ( container ) {
	ReactDOM.render(
		<OverviewPage />,
		container
	);
}
