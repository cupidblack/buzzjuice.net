import { Select as SelectComponent } from '@p2p/Components/Admin';
import { object, text, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/Select',
	decorators: [ withKnobs ],
};

export const Select = () => {
	const options = object( 'options', [
		{
			value: 'one',
			label: 'One',
		},
		{
			value: 'two',
			label: 'Two',
		},
		{
			value: 'three',
			label: 'Three'
		}
	] );

	const defaultValue = text( 'defaultValue', 'one' );

	return (
		<SelectComponent
			options={ options }
			onChange={ ( selected ) => console.log( selected ) }
			defaultValue={ defaultValue }
		/>
	)
}
