import { Label as LabelComponent } from '@p2p/Components/Admin';
import { text, select, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/Label',
	decorators: [ withKnobs ],
};

export const Label = () => {
	const options = {
		Success: 'success',
		Error: 'error',
		Warning: 'warning',
		Info: 'info',
		HTTP: 'HTTP',
		Spam: 'spam',
	};

	const type = select( 'type', options, 'success' );
	const textValue = text( 'text', 'Label text' );

	return (
		<LabelComponent type={ type } text={ textValue } />
	)
}
