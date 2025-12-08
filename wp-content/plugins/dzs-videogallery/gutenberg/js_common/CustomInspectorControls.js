import React from 'react';
import * as helpers from '../js_common/_helpers';

const {
  TextControl,
  TextareaControl,
  SelectControl,
} = wp.components;

let __ = (arg) => {
  return arg;
};

if (wp.i18n) {
  __ = wp.i18n.__;
}
const {
  PlainText,
  MediaUpload
} = wp.editor;


export default class CustomInspectorControls extends React.Component {
  constructor(props) {
    super(props);
    this.props = props;
  }

  render() {

    const ALLOWED_MEDIA_TYPES = ['audio'];
    let uploadSongLabel = __('Select media');

    let {arr_options} = this.props;
    // const self = this;
    if (typeof arr_options == 'string') {
      arr_options = helpers.decode_json(arr_options);
    }

    if (this.props.action_afterRender) {
      setTimeout(() => {

        this.props.action_afterRender(this);
      }, 50);
    }


    return Object.keys(arr_options).map((key) => {


      let optionName = key;
      let option_array = arr_options[key];

      let Sidenote = null;

      if (option_array.sidenote) {
        Sidenote = (
          <div className="sidenote" dangerouslySetInnerHTML={{__html: option_array.sidenote}}/>
        )
      }

      let divAtts = {
        dataOptionName: optionName,
        className: "zoomsounds-inspector-setting type-" + option_array.type
      };

      if(option_array.setting_extra_classes){
        divAtts.className+=' ' + option_array.setting_extra_classes;
      }

      let htmlExtra = null;
      if(option_array.extra_html_after_input){
        htmlExtra=(<div dangerouslySetInnerHTML={{__html: option_array.extra_html_after_input}}/>)
      }

      const composeInputField = () => {

        const props = this.props;
        const {ignoreOptions} = this.props;

        let option_array_key = key;

        if (helpers.isInteger(option_array_key)) {
          optionName = option_array['name'];
        }



        if ((ignoreOptions).indexOf(optionName) > -1) {
          return '';
        }
        if (!optionName) {
          return '';
        }

        if (optionName === 'cat') {
          option_array.options = dzswtl_settings.cats;
        }

        var args = {
            label: option_array.title,
            value: props.attributes[optionName] ? props.attributes[optionName] : '',
            instanceId: optionName,
            onChange: (value) => {
              props.setAttributes({[optionName]: value});
            }
          }
        ;


        if (option_array.type == 'text') {

          return (
            <TextControl
              {...args}
            />
          )
            ;
        }
        if (option_array.type == 'textarea') {

          return (
            <TextareaControl
              {...args}
            />
          )
            ;
        }

        if (optionName == 'config') {
        }
        if (option_array.type == 'select') {

          if (option_array.choices && !(option_array.options)) {
            option_array.options = option_array.choices;
          }

          return (
            <SelectControl
              {...args}
              options={option_array.options}
            />

          )
            ;
        }


        if (option_array.type == 'attach') {

          if (option_array.upload_type) {

            // args.type = option_array.upload_type;
            args.allowedTypes = [option_array.upload_type];
          }
          args.onChange = null;


          if (props.attributes[optionName]) {
            uploadSongLabel = __('Select another upload');
          }


          return (
          <MediaUpload
            {...args}
            onSelect={(imageObject) => {
              props.setAttributes({[optionName]: imageObject.url});
            }}
            render={({open}) => (
              <div className="render-song-selector">
                {props.attributes[optionName] ? (
                  <PlainText
                    format="string"
                    formattingControls={[]}
                    placeholder={__('Input song name')}
                    onChange={(val) => props.setAttributes({[optionName]: val})}
                    value={props.attributes[optionName]}
                  />
                ) : ""}
                <button className="button-secondary" onClick={open}>{uploadSongLabel}</button>
              </div>
            )}
          />
        )
          ;
        }
      }


      return (<div {...divAtts}>
        {
          option_array.type==='attach' && (<label className="components-base-control__label">{option_array.title}</label>)
        }
        {composeInputField()}
        {htmlExtra}
        {Sidenote}</div>);

    });

  }
}