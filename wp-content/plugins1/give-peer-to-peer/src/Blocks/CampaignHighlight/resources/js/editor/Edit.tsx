import React from 'react';

import {Fragment} from '@wordpress/element';
import {useBlockProps} from '@wordpress/block-editor';

import Inspector from './inspector';
import CampaignSelector from '@p2p/js/blocks/components/CampaignSelector';
import useCampaigns from '@p2p/js/blocks/hooks/useCampaigns';
import App from '../app';

/**
 *
 *
 * @since 1.6.0
 */

declare global {
    interface Window {
        GiveP2PCampaignHighlight: {
            shadowRootStylesheet: string;
        };
    }
}

const Edit = ({attributes, setAttributes}) => {
    const blockProps = useBlockProps();

    const campaigns = useCampaigns();
    const {id} = attributes;

    return (
        <div {...blockProps}>
            {id === '' ? (
                <CampaignSelector campaigns={campaigns} setAttributes={setAttributes} />
            ) : (
                <Fragment>
                    <Inspector attributes={attributes} setAttributes={setAttributes} />
                    <App initialState={attributes} href={window.GiveP2PCampaignHighlight.shadowRootStylesheet} />
                </Fragment>
            )}
        </div>
    );
};
export default Edit;
