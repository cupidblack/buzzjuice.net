function dep_init(){

  jQuery(document).on('change.dzsdepe', '.dzs-dependency-field,*[name="0-settings-vpconfig"], *[name="dzsvg_meta_item_type"]', handle_change);


  setTimeout(function () {
    jQuery('.dzs-dependency-field').trigger('change');
  }, 1000);
}

function handle_change(e) {


  var _t = jQuery(this);
  if (_t.hasClass('dzs-dependency-field')) {
    check_dependency_settings();
  }


  if (_t.attr('name') === 'dzsvg_meta_item_type') {


    var val = _t.val();



    var _con = null;

    if (_t.parent().parent().parent().hasClass('select-hidden-con')) {
      _con = _t.parent().parent().parent();
    }


    if (_con) {
      _con.removeClass('type_youtube type_vimeo type_video type_inline');
      _con.addClass('type_' + val);
    }


  }
  if (_t.attr('name') == '0-settings-vpconfig') {


    var ind = 0;

    _t.children().each(function () {
      var _t2 = jQuery(this);

      if (_t2.prop('selected')) {
        ind = _t2.parent().children().index(_t2) - 1;
        return false;
      }
    });

    jQuery('#quick-edit').attr('href', add_query_arg(jQuery('#quick-edit').attr('href'), 'currslider', ind));


  }

}


function check_dependency_settings() {
  jQuery('*[data-dependency]').each(function () {
    var _t = jQuery(this);



    var margs = {
      target_attribute: 'name'
    }


    var str_dependency = _t.attr('data-dependency');
    str_dependency = str_dependency.replace(/{{quot}}/g, '"');

    str_dependency = str_dependency.replace(/^""/g, '');
    str_dependency = str_dependency.replace(/""$/g, '');

    // -- remove last and start "
    str_dependency = str_dependency.replace(/^"/g, '');
    str_dependency = str_dependency.replace(/"$/g, '');

    var dep_arr = [];


    try {
      dep_arr = JSON.parse(str_dependency);

      var target_attribute = margs.target_attribute;

      var target_con = jQuery(document);


      if (dep_arr[0]) {
        var _c = null;


        if (dep_arr[0].lab) {
          _c = jQuery('*[name="' + dep_arr[0].lab + '"]:not(.fake-input)').eq(0);
        }
        if (dep_arr[0].label) {
          _c = jQuery('*[name="' + dep_arr[0].label + '"]:not(.fake-input)').eq(0);
        }
        if (dep_arr[0].element) {
          _c = jQuery('*[name="' + dep_arr[0].element + '"]:not(.fake-input)').eq(0);
        }


        if (_c) {

          var cval = _c.val();


          var isShowing = false;


          if (dep_arr[0].val) {

            for (var i3 in dep_arr[0].val) {
              if (_c.val() === dep_arr[0].val[i3]) {
                isShowing = true;
                break;

              }
            }
          }

          if (dep_arr.relation) {



            for (var i in dep_arr) {
              if (i == 'relation') {
                continue;
              }


              if (dep_arr[i].value) {
                if (dep_arr.relation == 'AND') {
                  isShowing = false;
                }


                if (dep_arr[0].element) {
                  _c = target_con.find('*[' + target_attribute + '="' + dep_arr[i].element + '"]:not(.fake-input)').eq(0);
                }


                for (var i3 in dep_arr[i].value) {


                  if (_c.val() == dep_arr[i].value[i3]) {


                    if (_c.attr('type') == 'checkbox') {
                      if (_c.val() == dep_arr[i].value[i3] && _c.prop('checked')) {

                        isShowing = true;
                      }
                    } else {

                      isShowing = true;
                    }

                    break;

                  }


                  if (dep_arr[i].value[i3] == 'anything_but_blank' && cval) {

                    isShowing = true;
                    break;
                  }
                }

                if (dep_arr.relation == 'AND') {
                  if (!isShowing) {
                    break;
                  }
                }
              }

            }

          } else {

            if (dep_arr[0].value) {

              for (var i3 in dep_arr[0].value) {
                if (_c.val() == dep_arr[0].value[i3]) {


                  if (_c.attr('type') === 'checkbox') {
                    if (_c.val() === dep_arr[0].value[i3] && _c.prop('checked')) {

                      isShowing = true;
                    }
                  } else {

                    isShowing = true;
                  }

                  break;

                }


                if (dep_arr[0].value[i3] === 'anything_but_blank' && cval) {

                  isShowing = true;
                  break;
                }
              }
            }
          }

          if (isShowing) {
            _t.css('display','');
            _t.addClass('dzs-dependency-recently-revealed');
            setTimeout(function(_arg){
              _arg.removeClass('dzs-dependency-recently-revealed');
            },500,_t);
          } else {
            _t.hide();
          }
        }


      }
    } catch (err) {
      console.error('json dependency error - ', str_dependency);
      console.error(err);
    }
  })
}




exports.dep_init = dep_init;