import { Spinner as SpinnerComponent } from '@p2p/Components/Admin';
import { select, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/Spinner',
	decorators: [ withKnobs ],
};

export const Spinner = () => {
	const options = {
		Tiny: 'tiny',
		Small: 'small',
		Medium: 'medium',
		Large: 'large'
	};

	const size = select( 'size', options, 'tiny' );

	return (
		<SpinnerComponent size={ size } />
	)
}
