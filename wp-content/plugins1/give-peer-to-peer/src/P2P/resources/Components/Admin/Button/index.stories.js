import { Button as ButtonComponent } from '@p2p/Components/Admin';
import { text, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/ButtonLink',
	decorators: [ withKnobs ],
};

export const Button = () => {
	const children = text( 'children', 'ButtonLink text' );
	return (
		<ButtonComponent>
			{ children }
		</ButtonComponent>
	);
};
