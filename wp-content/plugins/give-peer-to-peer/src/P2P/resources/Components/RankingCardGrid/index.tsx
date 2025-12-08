import cx from 'classnames';
import styles from './style.module.scss';
import {ReactNode} from 'react';

interface GridProps {
    children?: ReactNode | undefined;
    columns: 'fullWidth' | 'double' | 'triple' | 'max';
}

/**
 *
 *
 * @since 1.6.0
 */
export const Grid = ({children, columns}: GridProps) => {
    return (
        <div
            className={cx(styles.grid, {
                [styles[columns]]: true,
            })}
            part={`give-grid give-grid--${columns}`}
        >
            {children}
        </div>
    );
};

export const GridItem = ({children}) => {
    return <div className={styles.gridItem}>{children}</div>;
};
