import React from 'react';

import {__} from '@wordpress/i18n';
import {InspectorControls} from '@wordpress/block-editor';
import {Panel, PanelBody, SelectControl, ToggleControl} from '@wordpress/components';

import LayoutSelector from '@p2p/js/blocks/components/LayoutSelector';
import ColorControl from '@p2p/js/blocks/components/ColorControl';

import {p2pCampaignListOptions} from '../data/options';
import './style.scss';

/**
 *
 *
 * @since 1.6.0
 */
const Inspector = ({attributes, setAttributes}) => {
    const {show_avatar, layout, columns, accent_color, show_goal, show_campaign_info, show_description} = attributes;

    const saveSetting = (name, value) => {
        setAttributes({
            [name]: value,
        });
    };
    return (
        <InspectorControls key="inspector">
            <Panel>
                <PanelBody title={__('Layout', 'give-peer-to-peer')} initialOpen={true}>
                    <LayoutSelector label={''} layout={layout} selected={columns} help={__('', 'give-peer-to-peer')} />
                    <SelectControl
                        className="give-p2p-inspector__select"
                        name="type"
                        label={__('Type', 'give-peer-to-peer')}
                        value={layout}
                        options={p2pCampaignListOptions.type}
                        onChange={(value) => saveSetting('layout', value)}
                    />
                    {layout === 'grid' && (
                        <SelectControl
                            className="give-p2p-inspector__select"
                            name="columns"
                            label={__('Columns', 'give-peer-to-peer')}
                            value={columns}
                            options={p2pCampaignListOptions.columns}
                            onChange={(value) => saveSetting('columns', value)}
                        />
                    )}
                </PanelBody>
            </Panel>
            {layout === 'grid' && (
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
            )}
            <ColorControl attributes={attributes} setAttributes={setAttributes} />
        </InspectorControls>
    );
};

export default Inspector;
