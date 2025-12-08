import {playlist_navigationStructureAssignVars} from "../_navigation-helpers";
import {view_setCssPropsForElement} from "../../../shared/_viewFunctions";
import {sanitizeToCssPx} from "../../../js_common/_dzs_helpers";


/**
 *
 * @param {DzsNavigation} selfInstance
 * @param pargs
 * @returns {{navigation_mainDimensionNavSize: number, navigation_mainDimensionTotalSize: *}}
 */
export function view_navigation_calculateDims(selfInstance, pargs = {}){


  const calculateDimsArgs = Object.assign({
    forceMainAreaHeight: null
  }, pargs)


  const parentClass = selfInstance.parentClass;
  const navAttributes = selfInstance.navAttributes;

  selfInstance.mainAreaHeight = calculateDimsArgs.forceMainAreaHeight ? calculateDimsArgs.forceMainAreaHeight : selfInstance.$mainArea.outerHeight();


  let totalAreaHeightPixels = 0;
  selfInstance.totalAreaWidth = parentClass.$navigationAndMainArea.outerWidth();
  selfInstance.totalAreaHeight = parentClass.$navigationAndMainArea.outerHeight();
  let mainNavigationDesiredWidth = navAttributes.menuItemWidth;


  if (navAttributes.navigation_direction === 'vertical') {
    selfInstance.navigation_mainDimensionTotalSize = selfInstance.$mainNavigationItemsContainer.height();
    selfInstance.navigation_mainDimensionClipSize = selfInstance.$mainNavigationClipped.height();
    selfInstance.navigation_mainDimensionItemSize = selfInstance.$mainNavigationItemsContainer.children().eq(0).height();
  }

  if (navAttributes.navigation_direction === 'horizontal') {
    selfInstance.navigation_mainDimensionTotalSize = selfInstance.$mainNavigationItemsContainer.width();
    selfInstance.navigation_mainDimensionClipSize = selfInstance.$mainNavigationClipped.width();
    selfInstance.navigation_mainDimensionItemSize = selfInstance.$mainNavigationItemsContainer.children().eq(0).width();
  }
  selfInstance.nav_max_pages = Math.ceil((selfInstance.navigation_mainDimensionTotalSize / selfInstance.navigation_mainDimensionClipSize));

  parentClass.$navigationAndMainArea.children().each(function () {
    const $navigationChild = selfInstance.$(selfInstance);
    totalAreaHeightPixels += $navigationChild.get(0).scrollHeight;
  })


  // -- ultra-responsive
  // todo: remove dependency on parentClass
  if (selfInstance.configObj.viewEnableMediaArea && (navAttributes.menuPosition === 'right' || navAttributes.menuPosition === 'left')) {

    selfInstance.navigation_mainDimensionNavSize = navAttributes.menuItemWidth;
    if (selfInstance.totalAreaWidth - mainNavigationDesiredWidth < mainNavigationDesiredWidth) {

      if (selfInstance.containerComponent) {
        selfInstance.containerComponent.addClass('ultra-responsive');
      }
      parentClass.$navigationAndMainArea.addClass('nav-is-ultra-responsive');
      selfInstance.ultraResponsive = true;
    } else {

      parentClass.$navigationAndMainArea.removeClass('nav-is-ultra-responsive');
      selfInstance.ultraResponsive = false;
    }
  }


  if (navAttributes.menuPosition === 'top' || navAttributes.menuPosition === 'bottom') {

  }
  if (navAttributes.menuPosition === 'right' || navAttributes.menuPosition === 'left') {
    if (!selfInstance.ultraResponsive) {
      if (navAttributes.menuItemWidth) {


        view_setCssPropsForElement(selfInstance.$mainNavigation, {
          'flex-basis': `${sanitizeToCssPx(navAttributes.menuItemWidth)}`
        })
      }

      if (selfInstance.viewOptions.isSyncMainAreaAndNavigationAreas) {
        view_setCssPropsForElement(selfInstance.$mainNavigation, {
          'height': `${selfInstance.mainAreaHeight}`
        })
      }

      if(navAttributes.navigation_mainDimensionSpace){

        view_setCssPropsForElement(parentClass.$navigationAndMainArea, {
          'grid-gap': `${sanitizeToCssPx(navAttributes.navigation_mainDimensionSpace)}`
        })
      }
    } else {

    }

  }


  let navWidth = 0;


  selfInstance.totalItemsWidth = parentClass.$navigationItemsContainer.outerWidth();
  selfInstance.totalItemsHeight = parentClass.$navigationItemsContainer.outerHeight();

  // -- hover
  if (navAttributes.navigationType === 'hover' || navAttributes.navigationType === 'thumbsAndArrows') {
    if (navAttributes.navigation_direction === 'horizontal') {

      navWidth = 0;
      parentClass.$navigationItemsContainer.children().each(function () {
        const $t = selfInstance.$(this);
        navWidth += $t.outerWidth();
      });



      if (navWidth > selfInstance.totalAreaWidth) {
        selfInstance.$mainNavigation.off('mousemove', selfInstance.handleMouse);
        selfInstance.$mainNavigation.on('mousemove', selfInstance.handleMouse);
        selfInstance.containerComponent.removeClass('navWidth-bigger-then-totalWidth')

      } else {

        selfInstance.containerComponent.addClass('navWidth-bigger-then-totalWidth')

        view_setCssPropsForElement(parentClass.$navigationItemsContainer, {'left': ''})
        selfInstance.$mainNavigation.off('mousemove', selfInstance.handleMouse);

      }

    }
    if (navAttributes.navigation_direction === 'vertical') {
      if (selfInstance.totalItemsHeight > selfInstance.totalAreaHeight) {
        selfInstance.$mainNavigation.off('mousemove', selfInstance.handleMouse);
        selfInstance.$mainNavigation.on('mousemove', selfInstance.handleMouse);
      } else {
        view_setCssPropsForElement(parentClass.$navigationItemsContainer, {'top': ''})
        selfInstance.$mainNavigation.off('mousemove', selfInstance.handleMouse);
      }


    }

  }


  return {
    navigation_mainDimensionTotalSize: selfInstance.navigation_mainDimensionTotalSize,
    navigation_mainDimensionNavSize: selfInstance.navigation_mainDimensionNavSize,
  }
}


/**
 *
 * @param {string} structureMenuItemContentInner
 * @param {jQuery} $currentItemFeed
 * @param {object} configObj
 * @param {object} navAttributes
 * @param {boolean} isUltiboxItem
 * @returns string
 */
export function view_navigation_generateNavigationItem(structureMenuItemContentInner, $currentItemFeed, configObj, navAttributes, isUltiboxItem = false){

  let final_structureMenuItemContent = '';


  if (structureMenuItemContentInner) {
    structureMenuItemContentInner = playlist_navigationStructureAssignVars($currentItemFeed, structureMenuItemContentInner);
    // -- add parent default skin todo: we will have navigation skin
    structureMenuItemContentInner = structureMenuItemContentInner.replace('<div class="layout-builder--structure', '<div class="layout-builder--structure layout-builder--parent-style-' + navAttributes.parentSkin);
  }


  let navigationItemDomTag = 'div';
  let navigationItemExtraAttr = ' ';
  let navigationItemExtraClasses = ' ';


  if ($currentItemFeed.data('dzsvg-curatedtype-from-gallery') === 'link') {
    navigationItemDomTag = 'a';
    if ($currentItemFeed.attr('data-source')) {
      navigationItemExtraAttr += ' href="' + $currentItemFeed.attr('data-source') + '"';
    }
    if ($currentItemFeed.attr('data-target')) {
      navigationItemExtraAttr += ' target="' + $currentItemFeed.attr('data-target') + '"';
    }
  }


  if(isUltiboxItem){
    navigationItemExtraClasses+=' ultibox-item-delegated';

    if($currentItemFeed.hasClass('vplayer-tobe')){
      navigationItemExtraAttr+=' data-type="video"';
      if($currentItemFeed.attr('data-type')){

        navigationItemExtraAttr+=` data-video-type="${$currentItemFeed.attr('data-type')}"`;
      }
      if($currentItemFeed.attr('data-sourcevp')){

        navigationItemExtraAttr+=` data-source="${$currentItemFeed.attr('data-sourcevp')}"`;
      }

    }
  }

  // -- generating final_structureMenuItemContent

  final_structureMenuItemContent += '<' + navigationItemDomTag + ' class=" dzs-navigation--item ';
  final_structureMenuItemContent+=navigationItemExtraClasses;
  final_structureMenuItemContent+='"';
  final_structureMenuItemContent += navigationItemExtraAttr;
  final_structureMenuItemContent += '>';
  final_structureMenuItemContent += `<div class=" dzs-navigation--item-content">`;
  final_structureMenuItemContent += `${structureMenuItemContentInner}</div>`;


  final_structureMenuItemContent += '</' + navigationItemDomTag + '>';


  // -- function
  if (configObj.filter_structureMenuItemContent) {
    final_structureMenuItemContent = configObj.filter_structureMenuItemContent(final_structureMenuItemContent, $currentItemFeed);
  }

  return final_structureMenuItemContent;

}