import React from 'react';

import {__} from '@wordpress/i18n';
import {InspectorControls} from '@wordpress/block-editor';
import {Panel, PanelBody, ToggleControl} from '@wordpress/components';

import ColorControl from '@p2p/js/blocks/components/ColorControl';

import './style.scss';

/**
 *
 *
 * @since 1.6.0
 */
const Inspector = ({attributes, setAttributes}) => {
    const {show_avatar, show_goal, show_campaign_info, show_description} = attributes;

    const saveSetting = (name, value) => {
        setAttributes({
            [name]: value,
        });
    };

    return (
        <InspectorControls key="inspector">
            <Panel>
                <PanelBody title={__('Display Elements', 'give-peer-to-peer')} initialOpen={true}>
                    <ToggleControl
                        className="give-p2p-inspector__toggle"
                        label={__('Show Avatar', 'give-peer-to-peer')}
                        checked={show_avatar}
                        onChange={(value) => saveSetting('show_avatar', value)}
                    />
                    <ToggleControl
                        className="give-p2p-inspector__toggle"
                        label={__('Show Goal', 'give-peer-to-peer')}
                        checked={show_goal}
                        onChange={(value) => saveSetting('show_goal', value)}
                    />
                    <ToggleControl
                        className="give-p2p-inspector__toggle"
                        label={__('Show Description', 'give-peer-to-peer')}
                        checked={show_description}
                        onChange={(value) => saveSetting('show_description', value)}
                    />
                    <ToggleControl
                        className="give-p2p-inspector__toggle"
                        label={__('Show Campaign Info', 'give-peer-to-peer')}
                        checked={show_campaign_info}
                        onChange={(value) => saveSetting('show_campaign_info', value)}
                    />
                </PanelBody>
            </Panel>
            <ColorControl attributes={attributes} setAttributes={setAttributes} />
        </InspectorControls>
    );
};

export default Inspector;
