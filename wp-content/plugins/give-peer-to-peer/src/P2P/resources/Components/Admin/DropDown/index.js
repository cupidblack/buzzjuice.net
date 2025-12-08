import {useState} from 'react';
import {useController} from 'react-hook-form';
import styles from './style.module.scss';

export default function DropDown({name, control, required = false, options, onChange, defaultValue = '', isDisabled}) {
    const {field} = useController({name, control, defaultValue, rules: {required}});

    const [isOpen, setOpen] = useState(false);
    const [selectedOption, setSelectedOption] = useState(
        options.find((option) => option.value === defaultValue) ?? {label: 'None'}
    );
    const [listItems, setListItems] = useState(options);

    const updateValue = (option) => {
        field.onChange(option.value);

        if (onChange) {
            onChange(option);
        }

        setOpen(!isOpen);
        setSelectedOption(() => option);
    };

    const filterOptions = (e) => {
        const {value: search} = e.target;

        setListItems(() => {
            const filteredListItems = options.filter(
                ({label}) => -1 !== label.toLowerCase().indexOf(search.toLowerCase())
            );

            return filteredListItems ?? options;
        });

        setSelectedOption(() => {
            return {label: search};
        });
    };

    return (
        <fieldset className={styles.container}>
            <div className={styles.wrapper} onClick={() => !isDisabled && setOpen(!isOpen)}>
                <input
                    disabled={isDisabled}
                    className={styles.input}
                    value={selectedOption.label ?? 'None'}
                    onChange={filterOptions}
                />
                <input type="hidden" {...field} />
                <span className={styles.svg} style={{transform: isOpen ? 'scale(1,-1)' : 'none'}}>
                    <svg width="10" height="5" viewBox="0 0 10 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 5L5 0L0 5L10 5Z" fill="#555555" />
                    </svg>
                </span>
            </div>
            {isOpen && (
                <ul className={styles.list}>
                    {listItems.map((option, index) => {
                        return (
                            <li key={index} id="select" onClick={() => updateValue(option)}>
                                {option.label}
                            </li>
                        );
                    })}
                </ul>
            )}
        </fieldset>
    );
}
