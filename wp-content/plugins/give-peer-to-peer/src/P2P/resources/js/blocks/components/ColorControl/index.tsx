import React from 'react';

import {useSelect} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import {PanelColorSettings} from '@wordpress/block-editor';

export default function ColorControl({attributes, setAttributes}: {attributes: {accent_color: string}; setAttributes}) {
    const {accent_color} = attributes;
    // @ts-ignore
    const themeColors = useSelect('core/block-editor').getSettings().colors;

    const saveSetting = (name, value) => {
        setAttributes({
            [name]: value,
        });
    };

    const defaultColor = [
        {
            name: 'GiveWP',
            color: '#28c77b',
        },
    ];

    const colors = [...defaultColor, ...themeColors];

    return (
        <PanelColorSettings
            title={__('Color Settings')}
            colorSettings={[
                {
                    colors: colors,
                    value: accent_color,
                    onChange: (value) => saveSetting('accent_color', value),
                    label: __('Primary Color', 'give'),
                },
            ]}
        />
    );
}
