import React from 'react';

import SamplesHolder from '../js_common/SamplesHolder';
import DzsServerSideRender from "../js_common/DzsServerSideRender";
import {ErrorBoundary} from "../js_common/ErrorBoundary";
const { serverSideRender: ServerSideRender } = wp;

const {
  SelectControl,
} = wp.components;

let __ = (arg) => {
  return arg;
};


if (wp.i18n) {
  __ = wp.i18n.__;
}


export default class BlockPlaylist extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      mainOptions_expanded: false
    }


    this.props = props;
  }


  render() {

    console.log('BlockPlaylist -- render - ', this.props);
    let onClickToggleOptions = () => {
      this.setState({
        mainOptions_expanded: !this.state.mainOptions_expanded
      })
    };
    let editGalleryText = __('Create playlist ');
    if(this.props.mainprops.attributes.dzsvg_select_id){
      editGalleryText = __('Edit playlist from ');
    }

    return (
      <div className={'dzs--gutenberg-block ' + this.props.mainprops.className}>
        <div className={(this.props.mainprops.attributes.expanded ? 'gt-playlist-expanded' : ' ')}>
          <div className="dzsvg-gutenberg-block-container">
            <h4>{__('Video Playlist')}</h4>
            <div className="react-setting-container">
              <div className="react-setting-container--label">{__('Playlist')}</div>
              <div className="react-setting-container--control">

                <SelectControl

                  value={this.props.mainprops.attributes.dzsvg_select_id}
                  options={this.props.newSliders}
                  onChange={(val) => {
                    this.props.mainprops.setAttributes({dzsvg_select_id: val})
                  }}
                />
              </div>
            </div>
            <div className={"sidenote"}>{editGalleryText}<a className={'ultibox-item-delegated'} href={this.props.editUrl}>{__('here')}</a></div>


            <div className="dzs--gutenberg-preview-block">

              <ErrorBoundary>
                <ServerSideRender
                    block={this.props.key_block}
                    attributes={this.props.mainprops.attributes}
                ></ServerSideRender>
              </ErrorBoundary>

              <div className={"button-secondary2 preview-block--expander"} onClick={() => {
                const _c = document.querySelector('.dzs--gutenberg-preview-block');
                if (_c.classList.contains('expanded')) {

                  _c.classList.remove('expanded');
                  _c.querySelector('.expander-icon').innerHTML = '&#x2207;';
                } else {

                  _c.classList.add('expanded');
                  _c.querySelector('.expander-icon').innerHTML = '&#x2206;';
                }
              }}><span className="expander-label">{__("Preview Expand")}</span> <span
                className="expander-icon">&#x2207;</span></div>
            </div>


            <SamplesHolder
              examples_con_opened={this.props.examples_con_opened}
              main_props={this.props.mainprops}
              __={__}
              main_settings={this.props.main_settings}
              import_sample={this.props.import_sample}
              arr_sample_data={this.props.arr_sample_data}
            />


            <div className="dzs--gutenberg--extra-options">
              <h5 onClick={onClickToggleOptions}
                  className="dzs--gutenberg--extra-options--trigger">{!this.state.mainOptions_expanded ? (
                <span>{__('Other options')}  &darr;</span>) : (<span>{__('Retract')}  &uarr;</span>)}</h5>
              <div className="dzs--gutenberg--extra-options--content">
                {this.state.mainOptions_expanded ? this.props.player_inspector_controls : ''}
              </div>
            </div>


          </div>
        </div>
      </div>
    )
  }
}