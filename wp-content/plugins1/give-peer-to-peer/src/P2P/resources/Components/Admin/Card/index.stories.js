import { Card as CardComponent } from '@p2p/Components/Admin';
import { text, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/Card',
	decorators: [ withKnobs ],
};

export const Card = () => {
	const children = text( 'children', 'Card content' );
	return (
		<CardComponent>
			{ children }
		</CardComponent>
	);
}
