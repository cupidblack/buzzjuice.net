function postAjax(url, data, success) {
  var params = typeof data == 'string' ? data : Object.keys(data).map(
    function(k){ return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) }
  ).join('&');

  var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
  xhr.open('POST', url);
  xhr.onreadystatechange = function() {
    if (xhr.readyState>3 && xhr.status==200) { success(xhr.responseText); }
  };
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.send(params);
  return xhr;
}
function decode_json(arg) {
  var fout = {};

  if(arg){

    try{

      fout=JSON.parse(arg);
    }catch(err){
    }
  }

  return fout;
}
export function add_query_arg(purl, key,value){
  key = encodeURIComponent(key); value = encodeURIComponent(value);


  var s = purl;
  var pair = key+"="+value;

  var r = new RegExp("(&|\\?)"+key+"=[^\&]*");



  s = s.replace(r,"$1"+pair);
  var addition = '';
  if(s.indexOf(key + '=')>-1){


  }else{
    if(s.indexOf('?')>-1){
      addition = '&'+pair;
    }else{
      addition='?'+pair;
    }
    s+=addition;
  }

  //if value NaN we remove this field from the url
  if(value=='NaN'){
    var regex_attr = new RegExp('[\?|\&]'+key+'='+value);
    s=s.replace(regex_attr, '');
  }


  //if(!RegExp.$1) {s += (s.length>0 ? '&' : '?') + kvp;};

  return s;
}
function dzsvg_setShortcodeAttribute(args, props) {

  props.setAttributes(args);
}

export const sanitizeOptionsForGutenbergOptions = (arr) => {

  var foutArr = {};
  foutArr = arr;

  if(typeof arr=='string'){
    foutArr=decode_json(arr);
  }




  return foutArr;

}
export function isInteger(value) {
  // eslint-disable-next-line eqeqeq
  return !isNaN(parseFloat(value)) && isFinite(value) && Math.floor(value) == value;
}
export const sanitizeOptionsForGutenbergRegisterBlock = (arr) => {

  var foutArr = {};
  var transformedArray = {};
  foutArr = arr;

  if(typeof arr=='string'){
    foutArr=decode_json(arr);
  }



  Object.keys(foutArr).forEach(function(key) {
    var default2 = foutArr[key]['default'] ? foutArr[key]['default'] : '';
    var type2 = foutArr[key]['react_type'] ? foutArr[key]['react_type'] : 'string';

    var finalKey = key;

    if(isInteger(key)){
      finalKey = foutArr[key].name;
    }

    transformedArray[finalKey] = {
      'default':default2,
      'type':type2,
    }

  });


  return transformedArray;

}
export const import_sample = (arg, props, sliders) => {

  if (arg && arg.getAttribute('data-the-name')) {

    var theName = arg.getAttribute('data-the-name');



    var data = {
      action: 'dzsvg_import_item_lib'
      ,demo: theName
    };

    var url = ajaxurl + '';

    var import_type = 'server';


    if(arg.getAttribute('data-import-type')){
      import_type = arg.getAttribute('data-import-type');
    }


    if(import_type==='simple'){

      data.action = 'dzsvg_import_simple_playlist';
      data.name = theName;
    }



    postAjax(url, data, (response) => {





      var fout = decode_json(response);
      let slider_name = '';
      let slider_slug = '';



      if(import_type==='simple') {
        // -- slider_slug sent from import_slider
        if (fout.slider_slug) {
          slider_name = fout.slider_name;
          slider_slug = fout.slider_slug;
        }


        sliders.push({
          'value': slider_slug,
          'label': slider_name,
        });
        dzsvg_setShortcodeAttribute({dzsvg_select_id: slider_slug}, props);
      }else{

        if(fout.settings.import_type==='compound'){
          let block = wp.blocks.createBlock( 'core/freeform', { content: fout.settings.final_shortcode }  );
          // block.attributes.content.push( 'hello world' );
          wp.data.dispatch( 'core/editor' ).insertBlocks( block );
        }
        if(fout.settings.import_type==='playlist'){

          if (fout.settings.slider_slug) {
            slider_name = fout.settings.slider_name;
            slider_slug = fout.settings.slider_slug;
          }


          sliders.push({
            'value': slider_slug,
            'label': slider_name,
          });
        }
      }
    });
  }
};
export function convertForGutenbergOptions(arg){
  var arr_options = {};
  try{
    arr_options = JSON.parse(arg);
  }catch(err){
  }

  arr_options.forEach((el,ind) => {




    let aux = {};

    aux.type = 'string';
    if((el.type)) {
      aux.type = el.type;
    }
    if((el['default'])){

      aux['default'] = el['default'];
    }

    // -- sanitizing
    if(aux.type==='text'){
      aux.type='string';
    }



    if(el.only_for){
      var sw_break = true;
      for(var ind2 in el.only_for){

        if(el.only_for[ind2]=='gutenberg'){
          sw_break = false;
        }
      }

      if(sw_break){
        arr_options[ind] = {};
      }
    }



  })


  return arr_options;
}
export {postAjax,decode_json};