import React from 'react';

import {__} from '@wordpress/i18n';
import {InspectorControls} from '@wordpress/block-editor';
import {Panel, PanelBody, SelectControl, TextControl, ToggleControl} from '@wordpress/components';

import LayoutSelector from '@p2p/js/blocks/components/LayoutSelector';
import ColorControl from '@p2p/js/blocks/components/ColorControl';

import {p2pTeamLeaderboardOptions} from '../data/options';
import './style.scss';

/**
 *
 *
 * @since 1.6.0
 */
const Inspector = ({attributes, setAttributes}) => {
    const {columns, show_avatar, show_goal, accent_color, show_team_info, show_pagination, per_page, layout} =
        attributes;

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
                        options={p2pTeamLeaderboardOptions.type}
                        onChange={(value) => saveSetting('layout', value)}
                    />
                    {layout === 'grid' && (
                        <SelectControl
                            className="give-p2p-inspector__select"
                            name="columns"
                            label={__('Columns', 'give-peer-to-peer')}
                            value={columns}
                            options={p2pTeamLeaderboardOptions.columns}
                            onChange={(value) => saveSetting('columns', value)}
                        />
                    )}
                </PanelBody>
            </Panel>
            <Panel>
                {layout === 'grid' && (
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
                            label={__('Show Team Info', 'give-peer-to-peer')}
                            checked={show_team_info}
                            onChange={(value) => saveSetting('show_team_info', value)}
                        />
                    </PanelBody>
                )}
            </Panel>
            <Panel>
                <PanelBody title={__('Grid interaction', 'give-peer-to-peer')} initialOpen={true}>
                    <TextControl
                        className="give-p2p-inspector__text"
                        type="Number"
                        min={1}
                        name="per_page"
                        label={__('Teams per page', 'give-peer-to-peer')}
                        help={__(
                            'Sets the number of teams to be displayed on the first page load.',
                            'give-peer-to-peer'
                        )}
                        value={per_page}
                        onChange={(value) => saveSetting('per_page', value)}
                    />
                    <ToggleControl
                        className="give-p2p-inspector__toggle"
                        label={__('Show Pagination', 'give-peer-to-peer')}
                        help={__('Enable teams display to multiple pages', 'give-peer-to-peer')}
                        checked={show_pagination}
                        onChange={(value) => saveSetting('show_pagination', value)}
                    />
                </PanelBody>
            </Panel>
            <ColorControl attributes={attributes} setAttributes={setAttributes} />
        </InspectorControls>
    );
};

export default Inspector;
