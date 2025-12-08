import React from 'react';
import cx from 'classnames';
import './style.scss';

interface SelectorProps {
    display: number;
    selected?: string;
    type?: string;
}
export function Selector({display, selected, type}: SelectorProps) {
    const displayValue = String(display);
    return (
        <div
            className={cx(
                'give_column_selector_container',
                type === 'table' && 'give_layout_selector_table_container',
                {
                    give_column_selector_selected: displayValue === selected,
                    give_table_layout_selected: displayValue === 'type',
                }
            )}
        >
            <>
                {Array(display)
                    .fill(null)
                    .map((val, i) => (
                        <div key={i} className="give_column_selector_box">
                            {' '}
                        </div>
                    ))}
            </>
        </div>
    );
}

export function Row({children}) {
    return <div className="give_column_selector_row">{children}</div>;
}

interface LayoutSelectorProps {
    layout: string;
    selected?: string;
    help?: string;
    label?: string;
}
export default function LayoutSelector({layout, label, selected, help}: LayoutSelectorProps) {
    return (
        <div className="give_column_selector">
            {label && <p>{label}</p>}

            {help && <p className="give_column_selector_help_text">{help}</p>}
            <Row>
                {layout === 'table' ? (
                    <Selector display={3} type={'table'} />
                ) : selected === 'fullWidth' ? (
                    <Selector display={1} selected={selected} />
                ) : selected === 'double' ? (
                    <Selector display={2} selected={selected} />
                ) : selected === 'triple' ? (
                    <Selector display={3} selected={selected} />
                ) : (
                    <Selector display={4} selected={selected} />
                )}
            </Row>
        </div>
    );
}
