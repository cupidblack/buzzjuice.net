import { Table as TableComponent } from '@p2p/Components/Admin';
import { object, boolean, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/Table',
	decorators: [ withKnobs ],
};

export const Table = () => {
	const columns = object( 'columns', [
		{
			key: 'one',
			label: 'One',
		},
		{
			key: 'two',
			label: 'Two',
		},
		{
			key: 'three',
			label: 'Three'
		}
	] );

	const columnFilters = object( 'columnFilters', {
		one: '(value) => <strong>{value}</strong>'
	} )

	const generatedData = Array.from( { length: 10 }, ( _, i ) => {
		return {
			one: `One Test ${++i}`,
			two: `Two Test ${i}`,
			three: `Three Test ${i}`,
		}
	} );

	const data = object( 'data', generatedData );
	const isLoading = boolean( 'isLoading', false );
	const stripped = boolean( 'stripped', false );

	return (
		<TableComponent
			columns={ columns }
			data={ data }
			columnFilters={ {
				one: (value) => <strong>{value}</strong>
			} }
			isLoading={ isLoading  }
			stripped={ stripped }
		/>
	);
}
