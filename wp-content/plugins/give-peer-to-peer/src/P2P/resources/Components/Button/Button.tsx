import React, {forwardRef, Fragment} from 'react';
import cx from 'classnames';
import Spinner from '@p2p/Components/Admin/Spinner';

import styles from './Button.module.scss';

const colors = {
    primary: 'primary',
    secondary: 'secondary',
};

const sizes = {
    tiny: 'tiny',
    small: 'small',
    medium: 'medium',
};

/**
 * Visually a button.
 * Elementally, maybe something else?
 */
export const Button = forwardRef(
    (
        {
            as: Element = 'button',
            children,
            className,
            color = colors.primary,
            iconAfter: IconAfter = Fragment,
            iconBefore: IconBefore = Fragment,
            isLoading = false,
            size = sizes.medium,
            isDisabled,
            onClick,
            ...rest
        }: {
            as: any;
            children?: any;
            color?: string;
            iconAfter?: any;
            iconBefore?: any;
            isLoading?: boolean;
            size?: string;
            isDisabled?: boolean;
            className?: any;
            onClick?: (event) => void;
        },
        ref
    ) => (
        <Element
            ref={ref}
            part={cx('give-button', `give-button--${color}`, `give-button--${size}`)}
            className={cx(styles.base, styles[`color-${color}`], styles[`size-${size}`], className)}
            disabled={isDisabled}
            onClick={onClick}
            {...rest}
        >
            <span className={styles.isLoading} style={{visibility: isLoading ? 'visible' : 'hidden'}}>
                <Spinner size="tiny" className={undefined} />
            </span>
            <span className={styles.container} style={{visibility: isLoading ? 'hidden' : 'visible'}}>
                <IconBefore />
                <span>{children}</span>
                <IconAfter />
            </span>
        </Element>
    )
);

Button.displayName = 'Button';
// @ts-ignore
Button.colors = colors;
// @ts-ignore
Button.sizes = sizes;
