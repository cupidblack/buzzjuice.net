import PropTypes from 'prop-types';
import cx from 'classnames';
import styles from './Card.module.scss';

/**
 * This is a simpler version of the existing card. I would like to promote this
 * card to be a shared component, but I’ll colocate it with the only screen that
 * uses it for now to avoid naming conflict and larger refactor.
 */
const Card = ({as: Element = 'div', className, ...rest}) => (
	<Element className={cx(className, styles.card)} {...rest} />
);

Card.propTypes = {
	as: PropTypes.elementType,
	className: PropTypes.string,
};

export default Card;
