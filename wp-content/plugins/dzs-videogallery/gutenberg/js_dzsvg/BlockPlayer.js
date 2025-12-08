import React from 'react';


const {
  SelectControl,
  ServerSideRender,
} = wp.components;

const {
  RichText,
  MediaUpload
} = wp.editor;

let __ = (arg) => {
  return arg;
};


if (wp.i18n) {
  __ = wp.i18n.__;
}
import SamplesHolder from '../js_common/SamplesHolder';


export default class BlockPlayer extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      mainOptions_expanded: false
    }


    this.props = props;
  }


  render() {

    const ALLOWED_MEDIA_TYPES = [ 'video' ];

    let uploadSongLabel = __('Upload video');
    let uploadThumbLabel = __('Upload thumbnail');


    let onClickToggleOptions = () => {
      this.setState({
        mainOptions_expanded: !this.state.mainOptions_expanded
      })
    };
    var {props} = this;
    return (
      <div className={ props.className }>
        <div className={ ( props.attributes.theme ? 'click-to-tweet-alt' : 'click-to-tweet' ) }>
          <div className="dzsvg-containers">
            <h6 className={"main-gutenberg-block--title"}><span className="dashicons dashicons-format-video"/> { __( 'Video Player' ) }</h6>
            <div className="react-setting-container dzs--react-setting-container">
              <div className="react-setting-container--label">{ __( 'Title' ) }</div>
              <div className="react-setting-container--control">
                <RichText
                  format="string"
                  formattingControls={ [] }
                  placeholder={ __( 'Input title' ) }
                  onChange={ (value) => {   props.onChangeNormal('the_post_title',value) } }
                  value={ props.attributes.the_post_title }
                />
              </div>
            </div>
            <div className="react-setting-container dzs--react-setting-container">
              <div className="react-setting-container--label">{ __( 'Video Type' ) }</div>
              <div className="react-setting-container--control">

                <SelectControl
                  value={ props.attributes.type }
                  onChange={ (val) => {props.setAttributes({type: val});}  }
                  options={[
                    { label: __('Detect automatically'), value: 'detect' },
                    { label: __('Self hosted'), value: 'video' },
                    { label: __('YouTube'), value: 'youtube' },
                    { label: __('Vimeo'), value: 'vimeo' },
                    { label: __('Inline'), value: 'inline' },
                  ]}
                />
              </div>
            </div>
            <div className="react-setting-container dzs--react-setting-container">
              <div className="react-setting-container--label">{ __( 'Video source' ) }</div>
              <div className="react-setting-container--control">
                <MediaUpload
                  onSelect={(imageObject) => { console.log('imageObject - ', imageObject); props.setAttributes( { source: imageObject.url } ); props.setAttributes( { playerid: imageObject.id } );   } }
                  allowedTypes={ALLOWED_MEDIA_TYPES}
                  value={props.attributes.source} // make sure you destructured backgroundImage from props.attributes!
                  render={({ open }) => (
                    <div className="render-song-selector">
                      <RichText
                        format="string"
                        formattingControls={ [] }
                        className={"force-pre"}
                        placeholder={ __( 'Video URL' ) }
                        onChange={ (val) => {props.setAttributes({source: val}); props.setAttributes( { playerid: '' } );}  }
                        value={ props.attributes.source }
                      />
                      <button className="button-secondary" onClick={open}>{ uploadSongLabel }</button>
                    </div>
                  )}
                />
              </div>
            </div>
            <div className="react-setting-container dzs--react-setting-container">
              <div className="react-setting-container--label">{ __( 'Thumbnail' ) }</div>
              <div className="react-setting-container--control">
                <MediaUpload
                  onSelect={(imageObject) => { console.log('imageObject - ', imageObject); props.setAttributes( { cover: imageObject.url } );   } }
                  allowedTypes={['image']}
                  value={props.attributes.cover} // make sure you destructured backgroundImage from props.attributes!
                  render={({ open }) => (
                    <div className="render-song-selector">
                      <RichText
                        multiline="false"
                        format="string"
                        className={"force-pre"}
                        formattingControls={ [] }
                        placeholder={ __( 'cover' ) }
                        onChange={ (val) => {props.setAttributes({cover: val});}  }
                        value={ props.attributes.cover }
                      />
                      <button className="button-secondary" onClick={open}>{ uploadThumbLabel }</button>
                    </div>
                  )}
                />
              </div>
            </div>

          </div>

          <p>
            <a className="ctt-btn">
              { props.attributes.button }
            </a>
          </p>
        </div>
      </div>
    )
  }
}