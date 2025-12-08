/**
 * Block dependencies
 */

import CustomInspectorControls from "./js_common/CustomInspectorControls";

/**
 * Internal block libraries
 */
const { __ } = wp.i18n;

const { registerBlockType } = wp.blocks;

const {
  InspectorControls
} = wp.editor;

const {
  TextControl,
  SelectControl,
} = wp.components;
import BlockPlayer from './js_dzsvg/BlockPlayer';
import * as DzsHelpers from './js_common/_helpers';

/**
 * Register block
 */

const player_controls = [];
let arr_options = [];
let player_inspector_controls = null;

// -- we use window.dzsvg_gutenberg_player_options_for_js_init
window.onload = function() {



  arr_options = DzsHelpers.convertForGutenbergOptions(window.dzsvg_settings.player_options);

};



export default registerBlockType( 'dzsvg/gutenberg-player',{
  // Block Title
  title: __( 'Video player DZS' ),
  // Block Description
  description: __( 'Insert a dzs video player' ),
  // Block Category
  category: 'common',
  // Block Icon
  icon: 'format-video',
  // Block Keywords
  keywords: [
    __( 'YouTube' ),
    __( 'Vimeo' ),
    __( 'Media' ),
  ],
  attributes: window.dzsvg_gutenberg_player_options_for_js_init,
  // Defining the edit interface
  edit: props => {
    const {
      setAttributes,
      attributes,
      className
    } = props;
    let {backgroundimage} = attributes;



    const onChangeNormal = (label,value) => {

      props.setAttributes({[label]:value});
    }


    let uploadButtonLabel = __('Upload');

    if (props.attributes.dzsap_meta_item_source || props.attributes.source) {
      uploadButtonLabel = __('Select another upload');
    }

    const ignoreOptions = ['the_post_title','type','source', 'featured_media'];


    player_inspector_controls = (
      <CustomInspectorControls
        arr_options={arr_options}
        uploadButtonLabel={uploadButtonLabel}
        ignoreOptions={ignoreOptions}
        action_afterRender={($render)=>{
        }}
        {...props}
      />
    );
    // -- end map



    return [
      !! props.isSelected && (
        <InspectorControls key="inspector">
          <div className="components-panel__body is-opened">
          {player_inspector_controls}
          </div>
        </InspectorControls>
      ),
      <BlockPlayer
        onChangeNormal = {onChangeNormal}
        attributes = {attributes}
        setAttributes = {props.setAttributes}
        />
    ];
  },
  // Defining the front-end interface
  save() {
    // Rendering in PHP
    return null;
  },
});
