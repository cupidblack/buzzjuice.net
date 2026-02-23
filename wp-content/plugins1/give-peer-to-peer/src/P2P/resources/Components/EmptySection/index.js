import PropTypes from 'prop-types';

import {Button} from '../Button';

import styles from './style.module.scss';

const {__} = wp.i18n;

/**
 * Changes made to only render a button if buttonText is supplied to the component.
 * @since 1.6.0
 */
const EmptySection = ({title, subtitle, href, buttonText, icon}) => {
    return (
        <div className={styles.emptysectioncontainer}>
            <div className={styles.inner}>
                {!!icon && icon}
                <h3 className={styles.title}>{title}</h3>
                <p>{subtitle}</p>
                {buttonText && (
                    <Button as="a" href={href} color={Button.colors.secondary}>
                        {buttonText}
                    </Button>
                )}{' '}
            </div>
        </div>
    );
};
EmptySection.propTypes = {
    title: PropTypes.string,
    subtitle: PropTypes.string,
    href: PropTypes.string,
    icon: PropTypes.element,
};

export {EmptySection};
