import { LoadingOverlay as LoadingOverlayComponent } from '@p2p/Components/Admin';
import { select, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/LoadingOverlay',
	decorators: [ withKnobs ],
};

export const LoadingOverlay = () => {
	const options = {
		Tiny: 'tiny',
		Small: 'small',
		Medium: 'medium',
		Large: 'large'
	};

	const spinnerSize = select( 'spinnerSize', options, 'tiny' );

	return (
		<LoadingOverlayComponent spinnerSize={ spinnerSize } />
	)
}
