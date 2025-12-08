import { Pagination as PaginationComponent } from '@p2p/Components/Admin';
import { boolean, number, withKnobs } from '@storybook/addon-knobs';

export default {
	title: 'Give/Pagination',
	decorators: [ withKnobs ],
};

export const Pagination = () => {
	const currentPage = number( 'currentPage', 1 );
	const totalPages = number( 'totalPages', 10 );
	const disabled = boolean( 'disabled', false );

	return (
		<PaginationComponent
			currentPage={ currentPage }
			setPage={ () => {} }
			totalPages={ totalPages }
			disabled={ disabled }
		/>
	)
}
