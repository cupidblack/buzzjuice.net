import PropTypes from 'prop-types';
import cx from 'classnames';

import styles from './style.module.scss';

const sizes = {
    tiny: 'tiny',
    small: 'small',
    medium: 'medium',
    large: 'large',
};

const Spinner = ({className, size = sizes.small, ...rest}) => (
    <div className={cx(styles.spinner, styles[size], className)} {...rest} />
);

Spinner.propTypes = {
    /** Custom class name for the element */
    className: PropTypes.string,
    /** Spinner size [small, medium, large ] */
    size: PropTypes.oneOf(Object.values(sizes)),
};

Spinner.sizes = sizes;

export default Spinner;
