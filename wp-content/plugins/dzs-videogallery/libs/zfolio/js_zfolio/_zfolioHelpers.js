export function zfolioInitialSetup(cthis, o) {


  if (cthis.hasClass('skin-qucreative')) {
    o.design_skin = 'skin-qucreative';
  }
  if (cthis.hasClass('skin-material')) {
    o.design_skin = 'skin-material';
  }
  if (cthis.hasClass('skin-forwall')) {
    o.design_skin = 'skin-forwall';
    if (o.selector_con_skin === 'default') {
      o.selector_con_skin = 'selector-con-for-skin-forwall';
    }
  }
  if (cthis.hasClass('skin-melbourne')) {
    o.design_skin = 'skin-melbourne';
    if (o.selector_con_skin === 'default') {
      o.selector_con_skin = 'selector-con-for-skin-melbourne';
    }
  }
  if (cthis.hasClass('skin-silver')) {
    o.design_skin = 'skin-silver';
    if (o.selector_con_skin === 'default') {
      o.selector_con_skin = 'selector-con-for-skin-silver';
    }
  }
  if (cthis.hasClass('skin-gazelia')) {
    o.design_skin = 'skin-gazelia';
  }
  if (cthis.hasClass('skin-lazarus')) {
    o.design_skin = 'skin-lazarus';
  }
  if (cthis.hasClass('skin-alba')) {
    o.design_skin = 'skin-alba';
  }
  if (cthis.hasClass('skin-woo')) {
    o.design_skin = 'skin-woo';
  }
  if (cthis.hasClass('skin-noskin')) {
    o.design_skin = 'skin-noskin';
  }


  if (o.selector_con_skin === 'default') {
    o.selector_con_skin = 'selector-con-for-skin-melbourne';
  }

  cthis.addClass('mode-' + o.settings_mode);


  if (o.settings_ajax_method_curritems_per_page === 'auto') {
    if (cthis.hasClass('dzs-layout--6-cols')) {
      o.settings_ajax_method_curritems_per_page = '6';
    }
    if (cthis.hasClass('dzs-layout--5-cols')) {
      o.settings_ajax_method_curritems_per_page = '5';
    }
    if (cthis.hasClass('dzs-layout--4-cols')) {
      o.settings_ajax_method_curritems_per_page = '4';
    }
    if (cthis.hasClass('dzs-layout--3-cols')) {
      o.settings_ajax_method_curritems_per_page = '3';
    }
    if (cthis.hasClass('dzs-layout--2-cols')) {
      o.settings_ajax_method_curritems_per_page = '2';
    }
  }


  if (o.settings_ajax_method_curritems_per_page === 'auto') {

    o.settings_ajax_method_curritems_per_page = '4';
  }
}

export function zfolioGetContentCon(cthis) {

  if (cthis.parent().parent().parent().parent().parent().parent().parent().parent().hasClass('the-content-con')) {
    return cthis.parent().parent().parent().parent().parent().parent().parent().parent();
  }
  if (cthis.parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().hasClass('the-content-con')) {
    return cthis.parent().parent().parent().parent().parent().parent().parent().parent().parent().parent();
  }

  return null;
}