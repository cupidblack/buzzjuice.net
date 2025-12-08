import PropTypes from 'prop-types';
import {memo} from 'react';

import SearchIcon from '@p2p/Components/SearchIcon';

import styles from './Search.module.scss';

/**
 * Ideally, this does not need to be memoized and the parent component should
 * just use hooks instead of our debounce.
 */
const Search = memo(({ariaControls, inputId, label, navLabel, onSearchChange, placeholder, showSearch}) => {
	if (!showSearch) {
		return null;
	}

	return (
		<nav className={styles.search} aria-label={navLabel}>
			<label className={styles.searchLabel} htmlFor={inputId}>
				{label}
			</label>
			<div className={styles.iconContainer}>
				<SearchIcon />
				<input
					type="search"
					id={inputId}
					aria-controls={ariaControls}
					className={styles.searchControl}
					placeholder={placeholder}
					onChange={onSearchChange}
				/>
			</div>
		</nav>
	);
});

Search.displayName = 'Search';

Search.propTypes = {
	ariaControls: PropTypes.string.isRequired,
	inputId: PropTypes.string.isRequired,
	label: PropTypes.string.isRequired,
	navLabel: PropTypes.string.isRequired,
	onSearchChange: PropTypes.func.isRequired,
	placeholder: PropTypes.string.isRequired,
	showSearch: PropTypes.bool.isRequired,
};

export default Search;
