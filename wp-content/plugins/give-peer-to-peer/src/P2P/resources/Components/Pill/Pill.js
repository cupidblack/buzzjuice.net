import {Fragment} from 'react';
import PropTypes from 'prop-types';
import cx from 'classnames';

import styles from './Pill.module.scss';

/**
 * A pill with an icon
 */
const Pill = ({
	as: Element = 'div',
	children,
	className,
	icon: Icon = Fragment,
	...rest
}) => (
	<Element className={cx(styles.pill, className)} {...rest}>
		<Icon />
		<p className={styles.content}>
			{children}
		</p>
	</Element>
);

Pill.propTypes = {
	/**
	 * The root element used for the pill. Keep in mind this restricts wha
	 * elements you can use in the children (like HTML).
	 */
	as: PropTypes.elementType,

	/**
	 * The content of the pill. Must be phrasing content. This is wrapped in a
	 * paragraph tag.
	 */
	children: PropTypes.node.isRequired,

	/**
	 * The class name added to the root element.
	 */
	className: PropTypes.string,

	/**
	 * The icon to place before the content. The component must be an `<svg>` or
	 * `<img>`. For best results exclude width/height from the element.
	 */
	icon: PropTypes.elementType,
};

export default Pill;
