import React from 'react';

import {ArrowVariationLeftIcon, ArrowVariationRightIcon} from '@p2p/Components/Icons';

import styles from './style.module.scss';

/**
 *
 *
 * @since 1.6.0
 */
const Pagination = ({perPage, totalPages, currentPage, count, onPageChange, label}) => {
    const showNext = totalPages > currentPage * perPage;
    const showPrevious = currentPage > 1;

    const nextPage = parseInt(currentPage) + 1;
    const previousPage = parseInt(currentPage) - 1;

    const pageCount = Math.ceil(totalPages / perPage);

    return (
        <nav className={styles.container} aria-label={`${label} pagination`}>
            <ul className={styles.list}>
                <li className={styles.navDirection}>
                    <button role={'button'} disabled={!showPrevious} onClick={() => onPageChange(currentPage - 1)}>
                        <ArrowVariationLeftIcon />
                    </button>
                </li>

                {getDisplayedPages(currentPage, pageCount).map((page) => (
                    <li className={styles.navElement} key={page}>
                        <button
                            part={'give-pagination'}
                            role={'button'}
                            onClick={() => onPageChange(page)}
                            className={page === currentPage ? styles.current : ''}
                            aria-current={page === currentPage ? 'page' : null}
                        >
                            {page}
                        </button>
                    </li>
                ))}

                <li className={styles.navDirection}>
                    <button role={'button'} disabled={!showNext} onClick={() => onPageChange(currentPage + 1)}>
                        <ArrowVariationRightIcon />
                    </button>
                </li>
            </ul>
        </nav>
    );
};

const getDisplayedPages = (currentPage, pageCount) => {
    let pages;

    // Guess the array fill, both left and right.
    if (currentPage === pageCount) {
        pages = [currentPage - 3, currentPage - 2, currentPage - 1, currentPage];
    } else {
        pages = [currentPage - 2, currentPage - 1, currentPage, currentPage + 1];
    }

    // Filter pages that are greater than 0.
    pages = pages.filter((page) => page > 0);

    // Fill array to the right
    while (!pages || pages.length < 4) {
        pages.push(pages[pages.length - 1] + 1);
    }

    // Filter pages that are below the pages upper limit (or the upper limit itself).
    pages = pages.filter((page) => page <= pageCount);

    return pages;
};

export default Pagination;
