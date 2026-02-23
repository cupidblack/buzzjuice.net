import {DzsNavigation} from "../js_dzsvg/navigation/_navigation";
import {svgBackButton, svgForwardButton} from "../js_dzsvg/_dzsvg_svgs";
import {navigation_detectClassesForPosition} from "../js_dzsvg/playlist/_playlistHelpers";
import {dzsvg_playlist_initSearchField} from "./_searchFunctions";


/**
 *
 * called on init
 * @param {DzsVideoGallery} selfClass
 */
export function view_structureGenerateNavigation(selfClass) {

  let structNavigationAndMainArea = '<div class="navigation-and-main-area"></div>'

  const cgallery = selfClass.cgallery;
  const o = selfClass.initOptions;

  if (o.design_shadow === 'on') {
    cgallery.prepend('<div class="shadow"></div>');
  }
  selfClass.cgallery.append(structNavigationAndMainArea);
  selfClass.$navigationAndMainArea = selfClass.cgallery.find('.navigation-and-main-area').eq(0);


  selfClass.$navigationAndMainArea.css('background-color', selfClass.cgallery.css('background-color'));

  const navOptions = {
    navigationType: (o.nav_type === 'thumbs' ? 'hover' : o.nav_type === 'thumbsandarrows' ? 'thumbsAndArrows' : o.nav_type === 'outer' ? 'simple' : o.nav_type),
    menuPosition: o.menu_position,
    menuItemWidth: o.menuitem_width,
    menuItemHeight: o.menuitem_height,
    navigation_mainDimensionSpace: o.navigation_mainDimensionSpace,
    parentSkin: o.design_skin,
    viewNavigationIsUltibox: o.navigation_isUltibox,
    viewEnableMediaArea: (selfClass.viewOptions.enableVideoArea),
    viewAnimationDuration: o.navigation_viewAnimationDuration,
    navigationStructureHtml: selfClass.navigation_customStructure,
  };


  Object.keys(o).forEach((playlistOptionKey)=>{
    if(playlistOptionKey.indexOf('navigation_')===0){
      const newKeyForNav = playlistOptionKey.replace('navigation_','');
      navOptions[newKeyForNav] = o[playlistOptionKey];
    }
  })

  if (o.settings_mode === 'wall') {

    navOptions.gridClassItemsContainer = o.navigation_gridClassItemsContainer;
    navOptions.navigationType = 'simple';
    navOptions.filter_structureMenuItemContent = (final_structureMenuItemContent, $currentItemFeed) => {
      if ($currentItemFeed.attr('data-type')) {
        final_structureMenuItemContent = final_structureMenuItemContent.replace('dzs-navigation--item"', 'dzs-navigation--item" ' + ' data-video-type="' + $currentItemFeed.attr('data-type') + '"');
      }

      return final_structureMenuItemContent;
    }
  }


  selfClass.Navigation = new DzsNavigation(selfClass, navOptions, jQuery);


  selfClass.$sliderMain = cgallery.find('.sliderMain');
  selfClass._sliderCon = cgallery.find('.sliderCon');

  selfClass._mainNavigation = cgallery.find('.main-navigation');

  selfClass._sliderCon.addClass(o.extra_class_slider_con);

  if (o.settings_mode === 'slider') {
    selfClass.$sliderMain.after(selfClass._mainNavigation);
  }


  selfClass.$sliderMain.append('<div class="gallery-buttons"></div>');
  selfClass.$navigationClippedContainer = selfClass.cgallery.find('.navMain');

  selfClass.$navigationItemsContainer = selfClass.cgallery.find('.videogallery--navigation-container').eq(0);


  if (o.settings_mode === 'slider') {
    selfClass.$navigationClippedContainer.append('<div class="rotator-btn-gotoNext">' + svgForwardButton + '</div><div class="rotator-btn-gotoPrev">' + svgBackButton + '</div>');
  }
  if (o.settings_mode === 'rotator') {
    selfClass.$navigationClippedContainer.append('<div class="rotator-btn-gotoNext"></div><div class="rotator-btn-gotoPrev"></div>');
    selfClass.$navigationClippedContainer.append('<div class="descriptionsCon"></div>');
  }


  selfClass.$galleryButtons = selfClass.$sliderMain.children('.gallery-buttons');


  navigation_detectClassesForPosition(o.menu_position, selfClass._mainNavigation, cgallery);


  if (o.search_field === 'on') {
    dzsvg_playlist_initSearchField(selfClass, o);
  }

}
