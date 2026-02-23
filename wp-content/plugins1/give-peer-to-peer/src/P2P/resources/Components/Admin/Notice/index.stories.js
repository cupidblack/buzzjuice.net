import { Notice as NoticeComponent } from '@p2p/Components/Admin';
import { text, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/Notice',
	decorators: [ withKnobs ],
};

export const Notice = () => {
	const children = text( 'children', 'Notice content' );
	return (
		<NoticeComponent>
			{ children }
		</NoticeComponent>
	);
}
