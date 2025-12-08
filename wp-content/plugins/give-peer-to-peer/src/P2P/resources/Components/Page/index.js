import PropTypes from 'prop-types';
import {Helmet} from 'react-helmet';
import cx from 'classnames';

import styles from './Page.module.scss';

/**
 * The main page layout.
 */
const Page = ({className, title, ...rest}) => [
    <Helmet key="helmet">
        <title>{title}</title>
    </Helmet>,
    <div key="content" part="give-page" className={cx(styles.page, className)} {...rest} />,
];

Page.propTypes = {
    className: PropTypes.string,
    title: PropTypes.string.isRequired,
};

export default Page;
