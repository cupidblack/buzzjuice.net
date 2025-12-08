import React from 'react';

import {Fragment} from '@wordpress/element';
import {useBlockProps} from '@wordpress/block-editor';

import App from '../app/index';
import Inspector from './inspector';

/**
 *
 *
 * @since 1.6.0
 */

declare global {
    interface Window {
        GiveP2PCampaignList: {
            shadowRootStylesheet: string;
        };
    }
}

const Edit = ({attributes, setAttributes}) => {
    const blockProps = useBlockProps();

    return (
        <div {...blockProps}>
            <Fragment>
                <Inspector attributes={attributes} setAttributes={setAttributes} />
                <App initialState={attributes} href={window.GiveP2PCampaignList.shadowRootStylesheet} />
            </Fragment>
        </div>
    );
};
export default Edit;
