import PropTypes from 'prop-types';

import styles from './style.module.scss';

const Card = ({children, title}) => {
    return (
        <div className={styles.card}>
            {!!title && (
                <header>
                    <h3> {title} </h3>
                </header>
            )}
            <div className={styles.content}>{children}</div>
        </div>
    );
};
Card.propTypes = {
    children: PropTypes.node,
    title: PropTypes.string,
    closeIcon: PropTypes.bool,
    link: PropTypes.func,
};

const CardBody = ({children, ...rest}) => {
    return (
        <div className={styles.cardbody} {...rest}>
            {children}
        </div>
    );
};
CardBody.propTypes = {
    children: PropTypes.node,
};

const StripedGroup = ({children}) => {
    return <div className={styles.striped}> {children} </div>;
};
StripedGroup.propTypes = {
    children: PropTypes.node,
};

const CardFooter = ({children}) => {
    return <footer className={styles.cardfooter}>{children}</footer>;
};
CardFooter.propTypes = {
    children: PropTypes.node,
};

export {Card, CardBody, StripedGroup, CardFooter};
