/**
 * Block dependencies
 */

/**
 * Internal block libraries
 */


import './block_playlist.scss';
import BlockPlaylist from './js_dzsvg/BlockPlaylist';
import CustomInspectorControls from './js_common/CustomInspectorControls';
import {inlineUltiboxEdit} from './js_dzsvg/helpers/_inline-ultibox-ediit';
import {
  add_query_arg,
  import_sample,
  sanitizeOptionsForGutenbergOptions,
  sanitizeOptionsForGutenbergRegisterBlock
} from "./js_common/_helpers";
import {arr_sample_data} from "../configs/sampledata-playlists";
import {REGISTER_POST_TYPE} from "../configs/config";

var __ = (arg) => {
  return arg;
};

const {
  InspectorControls
} = wp.editor;

if (wp.i18n) {
  __ = wp.i18n.__;
}

const {registerBlockType} = wp.blocks;


/**
 * Register block
 */
const dzsvg_blockOptions = sanitizeOptionsForGutenbergOptions(window.dzsvg_gutenberg_block_playlist_options);
const dzsvg_registerBlockOptions = sanitizeOptionsForGutenbergRegisterBlock(window.dzsvg_gutenberg_block_playlist_options);
const key_block = 'dzsvg/gutenberg-playlist';

if (registerBlockType) {
  registerBlockType(key_block, {
    // Block Title
    title: __('Video Playlist') + ' ' + 'DZS',
    // Block Description
    description: __('Powerful video player playlist'),
    // Block Category
    category: 'common',
    // Block Icon
    icon: 'format-video',
    // Block Keywords
    keywords: [
      __('Video playlist'),
      __('Gallery'),
      __('Media'),
      __('Video'),
    ],
    attributes: dzsvg_registerBlockOptions,
    // Defining the edit interface
    edit: props => {
      const {
        attributes
      } = props;

      var main_settings = window.dzsvg_settings;

      let PlayerInspectorControl = null;


      function import_sample_local(arg) {
        import_sample(arg, props, sliders);
      }


      let uploadButtonLabel = __('Upload');

      if (props.attributes.dzsap_meta_item_source || props.attributes.source) {
        uploadButtonLabel = __('Select another upload');
      }

      const ignoreOptions = ['dzsvg_select_id', 'called_from', 'id'];

      PlayerInspectorControl = (
        <CustomInspectorControls
          arr_options={dzsvg_blockOptions}
          ignoreOptions={ignoreOptions}
          {...props}
        />
      );


      const examples_con_opened = props.attributes.examples_con_opened;
      const arr_sample_data_local = arr_sample_data;
      const sliders = main_settings.sliders;
      const newSliders = [...sliders];
      if (newSliders[0] && newSliders[0].label !== __("Select slider")) {
        // -- add first item
        Array.prototype.unshift.apply(newSliders, [{
          label: __("Select slider"),
          value: '',
        }]);
      }


      window.dzsvg_gutenberg_update_current_playlist = (newSliderOption) => {
        inlineUltiboxEdit(newSliderOption, props, sliders, newSliders);
        selectedTermId = newSliderOption.value;
      };


      let selectedTermId = '';


      if (props.attributes.dzsvg_select_id) {
        sliders.forEach((arg) => {
          if (props.attributes.dzsvg_select_id === arg.value) {
            selectedTermId = arg.term_id;
          }
        })
      }


      let editUrl;

      if (selectedTermId) {
        editUrl = add_query_arg(dzsvg_settings.admin_url + 'term.php', 'taxonomy', 'dzsvg_sliders');
        editUrl = add_query_arg(editUrl, 'post_type', REGISTER_POST_TYPE);
        editUrl = add_query_arg(editUrl, 'dzsvg_gallery_inline_ultibox_edit', 'on');
        editUrl = add_query_arg(editUrl, 'tag_ID', selectedTermId);
      } else {
        editUrl = add_query_arg(dzsvg_settings.admin_url + 'admin.php', 'dzsvg_action', 'create_new_gallery');
      }
      return [
        !!props.isSelected && (
          <InspectorControls key="inspector">
            {PlayerInspectorControl}
          </InspectorControls>
        ),
        <BlockPlaylist
          mainprops={props}
          newSliders={newSliders}
          editUrl={editUrl}
          key_block={key_block}
          main_settings={main_settings}
          import_sample={import_sample_local}
          arr_sample_data={arr_sample_data_local}
          player_inspector_controls={PlayerInspectorControl}
        />

      ];
    },

    save() {
      // -- Rendering in PHP
      return null;
    },
  })
}
;

