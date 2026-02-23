// License: GPLv2+
// -- this is javascript

var __ = wp.i18n.__; // The __() for internationalization.
var dzsvgCreateElement = wp.element.createElement,
  registerBlockType = wp.blocks.registerBlockType,
  ServerSideRender = wp.components.ServerSideRender,
  TextControl = wp.components.TextControl,
  InspectorControls = wp.editor.InspectorControls;

var SelectControl = wp.components.SelectControl;

/*
 * Here's where we register the block in JavaScript.
 *
 * It's not yet possible to register a block entirely without JavaScript, but
 * that is something I'd love to see happen. This is a barebones example
 * of registering the block, and giving the basic ability to edit the block
 * attributes. (In this case, there's only one attribute, 'foo'.)
 */

var blockKey = 'dzsvg/gutenberg-block';
registerBlockType(blockKey, {
  title: __('Video gallery ( legacy DZS )'),
  icon: 'screenoptions',
  category: 'widgets',

  /*
   * In most other blocks, you'd see an 'attributes' property being defined here.
   * We've defined attributes in the PHP, that information is automatically sent
   * to the block editor, so we don't need to redefine it here.
   */

  edit: function (props) {


    var arr_options = [];

    try {
      arr_options = JSON.parse(window.dzsvg_options_shortcode_generator)
    } catch (err) {

    }
    var foutarr = [
      /*
       * The ServerSideRender element uses the REST API to automatically call
       * php_block_render() in your PHP code whenever it needs to get an updated
       * view of the block.
       */
      dzsvgCreateElement(ServerSideRender, {
        block: blockKey,
        attributes: props.attributes,
      }),
      /*
       * InspectorControls lets you add controls to the Block sidebar. In this case,
       * we're adding a TextControl, which lets us edit the 'foo' attribute (which
       * we defined in the PHP). The onChange property is a little bit of magic to tell
       * the block editor to update the value of our 'foo' property, and to re-render
       * the block.
       */

      dzsvgCreateElement(InspectorControls, {},
        dzsvgCreateElement(
          SelectControl,
          {
            label: __('Select gallery'),
            value: props.attributes.dzsvg_select_id ? props.attributes.dzsvg_select_id : '',
            instanceId: 'dzsvg_select_id',
            onChange: function (value) {
              props.setAttributes({dzsvg_select_id: value});
            },
            options: dzsvg_settings.sliders,
          }
        ),
      )

    ];

    arr_options.forEach((val, ind) => {
      var args = {
        label: val.title,
        value: props.attributes[val.name] ? props.attributes[val.name] : '',
        instanceId: val.name,
        onChange: function (value) {
          var argobj = {};
          argobj[val.name] = value;
          props.setAttributes(argobj);
        },
        options: dzsvg_settings.sliders,
      };
      var typeControl = TextControl;
      if (val.type == 'select') {
        typeControl = SelectControl;
        args.options = val.options;
      }


      foutarr.push(dzsvgCreateElement(InspectorControls, {}, dzsvgCreateElement(
        typeControl,
        args
      )));

    })


    return foutarr;
  },

  // We're going to be rendering in PHP, so save() can just return null.
  save: function () {
    return null;
  },
});