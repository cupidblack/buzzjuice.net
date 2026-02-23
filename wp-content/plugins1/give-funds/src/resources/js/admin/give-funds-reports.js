import ReactDOM from 'react-dom';
import ReportsPage from '../app/reports-page';

const container = document.getElementById( 'give-funds-reports' );

if ( container ) {
	ReactDOM.render(
		<ReportsPage />,
		container
	);
}
