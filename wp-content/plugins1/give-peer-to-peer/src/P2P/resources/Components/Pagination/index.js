import PropTypes from 'prop-types';
import styles from './styles.module.scss';
import {ArrowIcon} from '@p2p/Components/Icons';

const {__, sprintf} = wp.i18n;

const Pagination = ({perPage, total, currentPage, count, onPageChange, label}) => {
    const showNext = total > currentPage * perPage;
    const showPrevious = currentPage > 1;

    const showingLower = count ? (currentPage - 1) * perPage + 1 : 0;
    const showingUpper = Math.min(currentPage * perPage, total);

    const pageUpper = Math.ceil(total / perPage);

    return (
        <div className={styles.container}>
            {sprintf(
                __('Showing %d - %d of %d Total %s', 'give-peer-to-peer'),
                showingLower,
                showingUpper,
                total,
                label
            )}

            <nav aria-label={`${label} pagination`}>
                <ul>
                    {!!showPrevious && (
                        <li>
                            <button onClick={() => onPageChange(currentPage - 1)}>
                                <ArrowIcon
                                    height={24}
                                    width={24}
                                    style={{
                                        transform: 'scale(-1,1)',
                                    }}
                                />
                            </button>
                        </li>
                    )}

                    {getDisplayedPages(currentPage, pageUpper).map((page) => {
                        return (
                            <li key={page}>
                                <button
                                    onClick={() => onPageChange(page)}
                                    className={page === currentPage ? styles.current : ''}
                                    aria-current={page === currentPage ? 'page' : null}
                                >
                                    {page}
                                </button>
                            </li>
                        );
                    })}

                    {!!showNext && (
                        <li>
                            <button onClick={() => onPageChange(currentPage + 1)}>
                                <ArrowIcon height={24} width={24} />
                            </button>
                        </li>
                    )}
                </ul>
            </nav>
        </div>
    );
};

Pagination.propTypes = {
    perPage: PropTypes.number,
    total: PropTypes.number,
    currentPage: PropTypes.number,
    count: PropTypes.number,
    onPageChange: PropTypes.func,
    label: PropTypes.string,
};

Pagination.defaultProps = {
    label: '',
};

function getDisplayedPages(currentPage, pageUpper) {
    let pages;

    // Guess the array fill, both left and right.
    if (currentPage === pageUpper) {
        pages = [currentPage - 4, currentPage - 3, currentPage - 2, currentPage - 1, currentPage];
    } else {
        pages = [currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2];
    }

    // Filter pages that are greater than 0.
    pages = pages.filter((page) => page > 0);

    // Fill array to the right
    while (!pages || pages.length < 5) {
        pages.push(pages[pages.length - 1] + 1);
    }

    // Filter pages that are below the pages upper limit (or the upper limit itself).
    pages = pages.filter((page) => page <= pageUpper);

    return pages;
}

export default Pagination;
