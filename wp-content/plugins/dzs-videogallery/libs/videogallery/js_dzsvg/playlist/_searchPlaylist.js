export function handleSearchFieldChange(selfClass, _navMain, handleResize) {
  return function () {

    var $ = jQuery;
    var _t = $(this);
    var o = selfClass.initOptions;


    if (o.settings_mode === 'wall') {
      selfClass._sliderCon.children().each(function () {
        var _t2 = $(this);


        if (_t.val() === '' || String(String(_t2.find('.menuDescription').eq(0).html()).toLowerCase()).indexOf(_t.val().toLowerCase()) > -1) {

          _t2.show();
        } else {

          _t2.hide();
        }


      });
    }


    if (o.nav_type === 'scroller') {


      if (typeof _navMain.get(0).api_scrolly_to != 'undefined') {
        _navMain.get(0).api_scrolly_to(0);
      }

      setTimeout(function () {

        selfClass.$navigationItemsContainer.css('top', '0')
      }, 100)
    }
    selfClass.$navigationItemsContainer.children().each(function () {
      var _t2 = $(this);


      if (_t.val() === '' || String(String(_t2.find('.dzs-navigation--item-content').eq(0).html()).toLowerCase()).indexOf(_t.val().toLowerCase()) > -1) {

        _t2.show();
      } else {

        if (_t2.hasClass('dzsvg-search-field') === false) {
          _t2.hide();
        }
      }
    });

    handleResize();
  }
}