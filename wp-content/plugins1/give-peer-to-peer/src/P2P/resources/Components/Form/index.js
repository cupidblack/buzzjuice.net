import {forwardRef, memo} from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

import styles from './style.module.scss';

const Form = ({children}) => {
    return <form>{children}</form>;
};
Form.propTypes = {
    children: PropTypes.node,
};

const FieldRow = ({children, ...rest}) => {
    return (
        <div className={styles.fieldrow} {...rest}>
            {children}
        </div>
    );
};
FieldRow.propTypes = {
    children: PropTypes.node,
};

const FieldLabel = memo(({label, description, required}) => {
    return (
        <div className={styles.label}>
            <strong style={{display: 'inline-flex'}}>{label}</strong>
            {required && <Required />}
            {description && <div>{description}</div>}
        </div>
    );
});

FieldLabel.propTypes = {
    label: PropTypes.string.isRequired,
    description: PropTypes.string,
};

const FieldRowLabel = memo(({label, description, required}) => {
    return (
        <FieldRow>
            <FieldLabel label={label} description={description} required={required} />
        </FieldRow>
    );
});

FieldRowLabel.propTypes = {
    label: PropTypes.string.isRequired,
    description: PropTypes.string,
};

const TextField = forwardRef(({hiddenLabel, name, type, label, icon, error, ...rest}, ref) => {
    const classes = classNames({
        [styles.hasIcon]: icon,
        [styles.hasError]: error,
    });

    return (
        <fieldset className={styles.fieldset}>
            {hiddenLabel && <label hidden>{label}</label>}
            <input ref={ref} name={name} type={type ?? 'text'} placeholder={label} className={classes} {...rest} />
            {icon}
        </fieldset>
    );
});

TextField.defaultProps = {
    error: false,
};

TextField.propTypes = {
    name: PropTypes.string,
    type: PropTypes.string,
    label: PropTypes.string,
    icon: PropTypes.node,
    error: PropTypes.bool,
};

const TextareaField = forwardRef(({name, type, value, error, placeholder, ...rest}, ref) => {
    const classes = classNames({
        [styles.hasError]: error,
    });

    return (
        <fieldset className={styles.fieldset}>
            <textarea ref={ref} className={classes} name={name} value={value} placeholder={placeholder} {...rest} />
        </fieldset>
    );
});

TextareaField.propTypes = {
    name: PropTypes.string,
};

const SelectField = forwardRef(({name, options, label, icon, error, ...rest}, ref) => {
    const classes = classNames({
        [styles.hasIcon]: icon,
        [styles.hasError]: error,
    });

    return (
        <fieldset className={styles.fieldset}>
            <select ref={ref} name={name} className={classes} {...rest}>
                <option value="">{label}</option>
                {options.map((option) => (
                    <option value={option.value} key={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {icon}
        </fieldset>
    );
});

SelectField.defaultProps = {
    error: false,
};

const Checkbox = forwardRef(({name, type, value, error, defaultChecked, label, ...rest}, ref) => {
    const classes = classNames({
        [styles.hasError]: error,
    });

    return (
        <div className={styles.checkbox}>
            <label>
                <input
                    ref={ref}
                    className={classes}
                    name={name}
                    value={value}
                    type={type}
                    defaultChecked={defaultChecked}
                    {...rest}
                />
                <span>{label}</span>
            </label>
        </div>
    );
});

Checkbox.propTypes = {
    name: PropTypes.string,
    type: PropTypes.string,
    defaultChecked: PropTypes.bool,
    label: PropTypes.string,
    error: PropTypes.bool,
};

const Required = () => (
    <span className={styles.fieldRequired} title="Field Required">
        *
    </span>
);

Required.propTypes = {
    title: PropTypes.string,
};

export {Form, FieldRow, FieldLabel, FieldRowLabel, TextField, TextareaField, SelectField, Checkbox, Required};
