import PropTypes from 'prop-types';
import { useStoreValue } from '../../store';
import { setCurrentPage } from '../../store/actions';

const { __ } = wp.i18n;

const Pagination = ( { pages, disabled } ) => {
	const [ { currentPage }, dispatch ] = useStoreValue();

	if ( 1 >= pages ) {
		return false;
	}

	const previousPage = parseInt( currentPage ) - 1;
	const nextPage = parseInt( currentPage ) + 1;

	return (
		<div className="tablenav bottom">
			<div className="tablenav-pages">
				<div className="pagination-links">

					{ ( previousPage > 0 ) ? (
						<>
							<a
								href="#"
								className="tablenav-pages-navspan button"
								onClick={ ( e ) => {
									e.preventDefault();
									if ( ! disabled ) {
										dispatch( setCurrentPage( 1 ) );
									}
								} }
							>
								«
							</a>
							{ ' ' }
							<a
								href="#"
								className="tablenav-pages-navspan button"
								onClick={ ( e ) => {
									e.preventDefault();
									if ( ! disabled ) {
										dispatch( setCurrentPage( parseInt( currentPage ) - 1 ) );
									}
								} }
							>
								‹
							</a>
						</>
					) : (
						<span className="tablenav-pages-navspan button disabled">‹</span>
					) }

					<span className="screen-reader-text">{ __( 'Current Page', 'give-funds' ) }</span>
					<span id="table-paging" className="paging-input">
						<span className="tablenav-paging-text">
							{ ' ' }{ currentPage } { __( 'of', 'give-funds' ) } <span className="total-pages">{ pages }</span>{ ' ' }
						</span>
					</span>

					{ ( nextPage <= pages ) ? (
						<>
							<a
								href="#"
								className="tablenav-pages-navspan button"
								onClick={ ( e ) => {
									e.preventDefault();
									if ( ! disabled ) {
										dispatch( setCurrentPage( parseInt( currentPage ) + 1 ) );
									}
								} }
							>
								›
							</a>
							{ ' ' }
							<a
								href="#"
								className="tablenav-pages-navspan button"
								onClick={ ( e ) => {
									e.preventDefault();
									if ( ! disabled ) {
										dispatch( setCurrentPage( pages ) );
									}
								} }
							>
								»
							</a>
						</>
					) : (
						<span className="tablenav-pages-navspan button disabled">›</span>
					) }
				</div>
			</div>
		</div>
	);
};

Pagination.propTypes = {
	pages: PropTypes.number.isRequired,
	disabled: PropTypes.bool.isRequired,
};

Pagination.defaultProps = {
	pages: 0,
	disabled: false,
};

export default Pagination;
