import { getWindowData } from '../../utils';
import { useStoreValue } from '../../store';
import { setCurrentFund } from '../../store/actions';

const { __ } = wp.i18n;

import './style.scss';

const FundsSelector = () => {
	const [ { currentFund }, dispatch ] = useStoreValue();
	const funds = getWindowData( 'funds', [] );

	const handleSelectChange = ( e ) => {
		const value = e.target.value;
		dispatch( setCurrentFund( value ) );
	};

	return (
		<div className="givewp-select">
			<select
				className="givewp-select-dropdown"
				onChange={ handleSelectChange }
				defaultValue={ currentFund }
			>
				<option value="0">{ __( 'All funds', 'give-funds' ) }</option>
				{ funds.map( ( fund, i ) => <option key={ `fund-${ i }` } value={ fund.id }>{ fund.title }</option> )}
			</select>
		</div>
	);
};

export default FundsSelector;
