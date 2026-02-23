import {add_query_arg} from "../js_common/_dzs_helpers";


export const zfolioCategoriesCalculateCatsArray = (catsString, $feedItem, arr_cats) => {

  let catsArray = catsString.split(';');
  for (let j = 0; j < catsArray.length; j++) {
    let the_cat = catsArray[j];
    let theCategoryUnsanitized = catsArray[j];
    if (the_cat) {
      the_cat = the_cat.replace(/ /gi, '-');

      $feedItem.addClass('cat-' + the_cat);

    }
    let isAlreadyAdded = false;

    for (let k = 0; k < arr_cats.length; k++) {
      if (arr_cats[k] === theCategoryUnsanitized) {
        isAlreadyAdded = true;
      }
    }
    if (!isAlreadyAdded) {

      arr_cats.push(theCategoryUnsanitized);
    }
  }
}

export const zfolioCategoriesGenerate = ($categoriesParent, o, _selectorCon, arr_cats, cthis, arr_cats_type, cid) => {

  if ($categoriesParent.length) {
    $categoriesParent.html('');
  }
  if (o.settings_useLinksForCategories === 'on') {
    $categoriesParent.append('<a class="a-category category-all-selector active" href="' + add_query_arg(window.location.href, 'dzsp_defCategory_' + cid, 0) + '">' + o.settings_categories_strall + '</a>');

  } else {
    $categoriesParent.append('<div class="a-category category-all-selector  active">' + o.settings_categories_strall + '</div>');
  }


  for (let i = 0; i < arr_cats.length; i++) {

    let label = cthis.find('.feed-zfolio-zfolio-term[data-termid="' + arr_cats[i] + '"]').eq(0).html();
    if (o.settings_useLinksForCategories === 'on') {
      if (arr_cats_type === 'dataterm') {
        if (label) {
          $categoriesParent.append('<a class="a-category"  href="' + add_query_arg(window.location.href, 'dzsp_defCategory_' + cid, (i + 1)) + '" data-termid="' + arr_cats[i] + '">' + label + '</a>');
        }
      } else {

        $categoriesParent.append('<a class="a-category"  href="' + add_query_arg(window.location.href, 'dzsp_defCategory_' + cid, (i + 1)) + '" >' + arr_cats[i] + '</a>');
      }

    } else {

      if (arr_cats_type === 'dataterm') {


        if (label) {

          $categoriesParent.append('<div class="a-category" data-termid="' + arr_cats[i] + '">' + label + '</div>');
        }
      } else {

        $categoriesParent.append('<div class="a-category" >' + arr_cats[i] + '</div>');
      }
    }
  }


  _selectorCon.removeClass('empty-categories');
}

export function viewFilterCategory(_t, cthis, $itemsCon, _selectorCon, o, goto_category ) {

  let isBreakFunction = false;


  if (!cthis.hasClass('dzszfl-ready-for-transitions')) {

    let isotopeFilterArgs = {};
    isotopeFilterArgs = jQuery.extend(isotopeFilterArgs, o.settings_isotope_settings);


    isotopeFilterArgs.transitionDuration = '0s';

    if (cthis.hasClass('dzs-layout--3-cols')) {
      isotopeFilterArgs.percent_amount = 33.3333;
    }
    if (cthis.hasClass('dzs-layout--4-cols')) {
      isotopeFilterArgs.percent_amount = 25;
    }
    if (cthis.hasClass('dzs-layout--6-cols')) {
      isotopeFilterArgs.percent_amount = 16.6666;
    }


    isotopeFilterArgs.transitionDuration = '0.3s';
    isotopeFilterArgs.transitionDuration = '0.4s';
    $itemsCon.isotope(isotopeFilterArgs);

    cthis.addClass('dzszfl-ready-for-transitions');

    clearTimeout(inter_set_transition_duration);
  }


  if (_t.hasClass('active')) {
    _selectorCon.toggleClass('is-opened');
    isBreakFunction = true;
  }


  var ind = _t.parent().children().index(_t);

  var cat = _t.html();
  if (_t.attr('data-termid')) {

    cat = (_t.attr('data-termid'));
    goto_category(cat, {
      'class_name': 'termid'
    });
  } else {


    if (o.settings_useLinksForCategories !== 'on' || o.settings_useLinksForCategories_enableHistoryApi === 'on') {
      goto_category(cat);
    }
  }

  _selectorCon.removeClass('is-opened');

  if (o.settings_useLinksForCategories === 'on' && o.settings_useLinksForCategories_enableHistoryApi === 'on') {


    const stateObj = {foo: "bar"};
    history.pushState(stateObj, "ZoomFolio Category " + ind, add_query_arg(window.location.href, 'dzsp_defCategory_' + cid, (ind)));


  }

  if (o.settings_useLinksForCategories_enableHistoryApi === 'on') {
    isBreakFunction = true;
  }

  return isBreakFunction;


}