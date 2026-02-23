import PropTypes from 'prop-types';

import styles from './style.module.scss';

const BlockLink = ( { icon, title, description } ) => {
	return (
		<div className={ styles.blocklink }>
			<aside>{ icon }</aside>
			<div className={styles.content}>
				<h1>{ title }</h1>
				<p>{ description }</p>
			</div>
			<svg width="9" height="17" viewBox="0 0 9 17" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M0.84375 0.621094L0.140625 1.28906C0 1.46484 0 1.74609 0.140625 1.88672L6.50391 8.25L0.140625 14.6484C0 14.7891 0 15.0703 0.140625 15.2461L0.84375 15.9141C1.01953 16.0898 1.26562 16.0898 1.44141 15.9141L8.82422 8.56641C8.96484 8.39062 8.96484 8.14453 8.82422 7.96875L1.44141 0.621094C1.26562 0.445312 1.01953 0.445312 0.84375 0.621094Z" fill="#6B6B6B"/>
			</svg>
		</div>
	);
};

BlockLink.propTypes = {
	icon: PropTypes.node,
	title: PropTypes.string,
	description: PropTypes.string,
	location: PropTypes.string,
};

const BlockLinkGroup = ( { children } ) => {
	return (
		<div className={ styles.blocklinkgroup }>
			{ children }
		</div>
	);
};

BlockLinkGroup.propTypes = {
	children: PropTypes.node,
};

export {
	BlockLink,
	BlockLinkGroup,
};
