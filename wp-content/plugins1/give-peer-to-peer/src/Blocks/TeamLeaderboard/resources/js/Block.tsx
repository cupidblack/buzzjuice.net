import React from 'react';

import {BlockConfiguration, getBlockType, registerBlockType} from '@wordpress/blocks';

import Edit from './editor/Edit';
import GiveIcon from '@p2p/Components/SVGImages/GiveIcon/GiveIcon';

import schema from './block.json';

/**
 *
 *
 * @since 1.6.0
 */

const {name} = schema;

if (!getBlockType(name)) {
    registerBlockType(schema as BlockConfiguration, {
        icon: <GiveIcon />,
        edit: Edit,
    });
}
