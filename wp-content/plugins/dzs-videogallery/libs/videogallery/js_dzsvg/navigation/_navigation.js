import {
  defaultSettings,
  NAVIGATION_DEFAULT_TIMEOUT,
  NAVIGATION_VIEW_MENU_VERTICAL_DEFAULT_ITEM_WIDTH
} from "./configs/_navigationSettings";
import {is_android, is_ios, is_touch_device} from "../../js_common/_dzs_helpers";
import {view_cssConvertForPx, view_setCssPropsForElement} from "../../shared/_viewFunctions";
import {view_navigation_calculateDims, view_navigation_generateNavigationItem} from "./inc/_navigation-view";


Math.easeIn = function (t, b, c, d) {

  return -c * (t /= d) * (t - 2) + b;

};
Math.easeOut = function (t, b, c, d) {
  t /= d;
  return -c * t * (t - 2) + b;
};

/**
 *
 *
 */
export class DzsNavigation {


  /**
   *
   * @param {DzsVideoGallery|any} parentClass
   * @param argOptions
   * @param $
   *  @property {number}  navigation_mainDimensionNavSize for left / right /// height for top / bottom
   */
  constructor(parentClass, argOptions, $) {


    this.$ = $;
    this.parentClass = parentClass;
    this.initOptions = null;
    this.navAttributes = null;
    this.$mainArea = null;
    /** */
    this.$mainNavigation = null;
    this.$mainNavigationClipped = null;
    this.$mainNavigationItemsContainer = null;
    this.$containerComponent = null;
    this.ultraResponsive = false;
    this.menuPosition = null;
    this.configObj = argOptions;

    this.viewOptions = {
      isSyncMainAreaAndNavigationAreas: true
    }

    // -- dimensions
    this.totalItemsWidth = 0;
    this.totalItemsHeight = 0;
    this.totalAreaWidth = 0;
    this.totalAreaHeight = 0;
    this.mainAreaHeight = 0;

    // -- thumbsAndArrows
    this.currPage = 0;
    this.nav_max_pages = 0;
    this.navigation_extraFixedElementsSize = 0;
    this.navigation_mainDimensionTotalSize = 0;
    this.navigation_mainDimensionClipSize = 0;
    this.navigation_mainDimensionItemSize = 0;
    this.navigation_mainDimensionNavSize = 0;

    this.navigation_customStructure = '';

    this.initClass();

  }

  /**
   *
   * @param {object} navAttributes
   * @returns {object}
   */
  computeNavAttributes(navAttributes) {

    if (navAttributes.menuPosition === 'right' || navAttributes.menuPosition === 'left') {
      navAttributes.navigation_direction = 'vertical';
    }
    if (navAttributes.menuPosition === 'bottom' || navAttributes.menuPosition === 'top') {
      if (navAttributes.navigation_direction === 'auto') {
        navAttributes.navigation_direction = 'horizontal';
      }
    }

    if (navAttributes.navigationType === 'simple') {
      navAttributes.navigation_direction = 'none';
    }

    return navAttributes;
  }

  computeInstanceProps() {

  }

  /**
   *
   * @param {jQuery} $feedItemsContainer
   */
  addNavigationItems($feedItemsContainer) {

    const itemsLength = $feedItemsContainer.find(this.configObj.feedItemNotInitedClass).length;
    for (let i = 0; i < itemsLength; i++) {
      let $currentItemFeed = $feedItemsContainer.find(this.configObj.feedItemNotInitedClass).eq(i);

      let structureMenuItemContentInner = this.navigation_customStructure;

      const final_structureMenuItemContent = view_navigation_generateNavigationItem(structureMenuItemContentInner, $currentItemFeed, this.configObj, this.navAttributes, this.configObj.viewNavigationIsUltibox);

      const $currentItemFeed_ = $currentItemFeed.get(0);

      $currentItemFeed.addClass('nav-treated');


      this.$mainNavigationItemsContainer.append(final_structureMenuItemContent);
      const $justAdded = this.$mainNavigationItemsContainer.children().last();
      for (let i = 0, atts = $currentItemFeed_.attributes, n = atts.length; i < n; i++) {
        if (atts[i].nodeName && atts[i].value) {
          $justAdded.data(atts[i].nodeName, atts[i].value);
        }
      }
    }
  }

  initClass() {
    const selfInstance = this;
    const parentClass = this.parentClass;
    this.configObj = Object.assign(Object.assign(defaultSettings, {}), this.configObj);
    const newOptions = Object.assign({}, this.configObj);
    this.initOptions = {...newOptions};
    this.navAttributes = {...newOptions};
    this.menuPosition = this.navAttributes.menuPosition;

    this.navigation_customStructure = this.navAttributes.navigationStructureHtml;

    const navAttributes = this.computeNavAttributes(this.navAttributes);

    let isMenuMoveLocked = false,
      navMain_mousex = 0,
      navMain_mousey = 0
    ;


    let target_viy = 0,
      target_vix = 0,
      begin_viy = 0,
      begin_vix = 0,
      finish_viy = 0,
      finish_vix = 0,
      change_viy = 0,
      change_vix = 0
    ;


    const OFFSET_BUFFER = 25;
    let DURATION_EASING = 20;


    init();

    function init() {


      if (parentClass) {
        if (parentClass.cgallery) {
          selfInstance.containerComponent = selfInstance.parentClass.cgallery;
        } else {
          if (parentClass.cthis) {
            selfInstance.containerComponent = selfInstance.parentClass.cthis;
          }
        }
      }


      sanitizeInitValues();


      setupStructure();
      if (is_touch_device()) {
        selfInstance.$mainNavigation.parent().addClass('is-touch');
      }

      if (navAttributes.gridClassItemsContainer) {
        selfInstance.$mainNavigationItemsContainer.addClass(navAttributes.gridClassItemsContainer);
      }


      selfInstance.nav_thumbsandarrows_gotoPage = nav_thumbsandarrows_gotoPage;
      selfInstance.handleMouse = handleMouse;

      setTimeout(init_navIsReady, 100);
    }


    function sanitizeInitValues() {


      if (navAttributes.menuPosition === 'left' || navAttributes.menuPosition === 'right') {
        if (isNaN(Number(selfInstance.initOptions.menuItemWidth)) || selfInstance.initOptions.menuItemWidth === '' || selfInstance.initOptions.menuItemWidth === 'default') {
          navAttributes.menuItemWidth = NAVIGATION_VIEW_MENU_VERTICAL_DEFAULT_ITEM_WIDTH;
        }
      }

      if (!navAttributes.viewEnableMediaArea) {
        selfInstance.viewOptions.isSyncMainAreaAndNavigationAreas = false;
      }
    }


    if (selfInstance.initOptions.viewAnimationDuration !== null) {
      DURATION_EASING = selfInstance.initOptions.viewAnimationDuration;
    }

    function init_navIsReady() {
      if (is_touch_device()) {
        navAttributes.isUseEasing = false;
      }


      if (navAttributes.navigationType === 'hover') {
        handleEnterFrame();
      }


      if (navAttributes.isAutoScrollToCurrent) {
        if (navAttributes.navigationType === 'hover') {


          setTimeout(function () {
            animate_to_curr_thumb();

          }, 20);
        }
      }

    }


    function setupStructure() {
      let structure_baseLayout = '<div class="main-navigation dzs-navigation--type-' + selfInstance.initOptions.navigationType + '"><div class="navMain videogallery--navigation--clipped-container navigation--clipped-container"><div class="videogallery--navigation-container navigation--total-container">';
      structure_baseLayout += '</div></div></div>';

      parentClass.$navigationAndMainArea.addClass(`navPosition-${navAttributes.menuPosition} navType-${navAttributes.navigationType}`);

      parentClass.$navigationAndMainArea.append('<div class="sliderMain media--main-area"><div class="sliderCon"></div></div>');
      parentClass.$navigationAndMainArea.append(structure_baseLayout);


      selfInstance.$mainArea = parentClass.$navigationAndMainArea.find('.media--main-area');
      selfInstance.$mainNavigation = parentClass.$navigationAndMainArea.find('.main-navigation');
      selfInstance.$mainNavigationClipped = selfInstance.$mainNavigation.find('.navigation--clipped-container');
      selfInstance.$mainNavigationItemsContainer = selfInstance.$mainNavigation.find('.navigation--total-container');


      if (navAttributes.menuItemWidth === 'default') {
        navAttributes.menuItemWidth = '';
      }
      if (navAttributes.menuItemHeight === 'default') {
        navAttributes.menuItemHeight = '';
      }


      if (navAttributes.menuPosition === 'top' || navAttributes.menuPosition === 'bottom') {

      }


      if (navAttributes.menuPosition === 'top') {
        selfInstance.$mainArea.before(selfInstance.$mainNavigation);
      }


      if (navAttributes.navigationSpace) {
        parentClass.$navigationAndMainArea.css({
          gap: view_cssConvertForPx(navAttributes.navigationSpace)
        })
      }

      if (navAttributes.navigationType === 'scroller') {
        if (navAttributes.navigation_direction === 'horizontal') {
          view_setCssPropsForElement(selfInstance.$mainNavigation, {
            'minHeight': navAttributes.menuItemHeight + 'px'
          })
        }
      }

      if (navAttributes.navigationType === 'thumbsAndArrows') {
        selfInstance.$mainNavigation.prepend('<div class="nav--thumbsAndArrows--arrow thumbs-arrow-left arrow-is-inactive"></div>');
        selfInstance.$mainNavigation.append('<div class="nav--thumbsAndArrows--arrow thumbs-arrow-right"></div>');

        selfInstance.$mainNavigation.find('.thumbs-arrow-left,.thumbs-arrow-right').on('click', handleClick_navigationArrow);
      }

      if (!selfInstance.configObj.viewEnableMediaArea) {
        parentClass.$navigationAndMainArea.addClass('view--disable-video-area');
      }

      parentClass.$navigationAndMainArea.addClass(`layout-builder--menu-items--${navAttributes.navigationSkin}`)
    }

    function handleClick_navigationArrow() {


      var $t = jQuery(this);

      if ($t.hasClass('thumbs-arrow-left')) {
        gotoPrevPage();
      }
      if ($t.hasClass('thumbs-arrow-right')) {
        gotoNextPage();
      }
    }


    function gotoNextPage() {
      var tempPage = selfInstance.currPage;

      tempPage++;
      nav_thumbsandarrows_gotoPage(tempPage);

    }

    function gotoPrevPage() {
      if (selfInstance.currPage === 0) {
        return;
      }

      selfInstance.currPage--;
      nav_thumbsandarrows_gotoPage(selfInstance.currPage);

    }

    /**
     * called only from thumbsandarrows
     * @param {number} targetPageNr
     */
    function nav_thumbsandarrows_gotoPage(targetPageNr) {

      if (targetPageNr > selfInstance.nav_max_pages || navAttributes.navigationType !== 'thumbsAndArrows') {
        return;
      }


      selfInstance.$mainNavigation.find('.nav--thumbsAndArrows--arrow').removeClass('arrow-is-inactive');

      if (targetPageNr === 0) {
        selfInstance.$mainNavigation.find('.thumbs-arrow-left').addClass('arrow-is-inactive');
      }
      if (targetPageNr >= selfInstance.nav_max_pages) {
        selfInstance.$mainNavigation.find('.thumbs-arrow-right').addClass('arrow-is-inactive');
      }

      if (targetPageNr >= selfInstance.nav_max_pages) {
        if (navAttributes.navigation_direction === "vertical") {

          const targetTop = -(selfInstance.navigation_mainDimensionTotalSize - selfInstance.navigation_mainDimensionClipSize);
          view_setCssPropsForElement(selfInstance.$mainNavigationItemsContainer, {
            'top': targetTop,
            'left': 0
          })
        }
        if (navAttributes.navigation_direction === "horizontal") {

          view_setCssPropsForElement(selfInstance.$mainNavigationItemsContainer, {
            'left': -(selfInstance.navigation_mainDimensionTotalSize - selfInstance.navigation_mainDimensionClipSize),
            'top': 0
          })
        }

      } else {
        if (navAttributes.navigation_direction === "vertical") {
          let firstItemInSightWidth = selfInstance.$mainNavigationItemsContainer.children().eq(selfInstance.currPage).height();
          selfInstance.$mainNavigationItemsContainer.css({
            'top': firstItemInSightWidth * -targetPageNr,
            'left': 0
          });

        }

        if (navAttributes.navigation_direction === "horizontal") {

          let firstItemInSightWidth = selfInstance.$mainNavigationItemsContainer.children().eq(selfInstance.currPage).width();
          selfInstance.$mainNavigationItemsContainer.css({
            'left': firstItemInSightWidth * -targetPageNr,
            'top': 0
          });
        }
      }
      selfInstance.currPage = targetPageNr;
    }

    /**
     * handle mouse for the parentClass.$navigationItemsContainer element
     * @param e
     * @returns {boolean}
     */
    function handleMouse(e) {


      navMain_mousey = (e.pageY - selfInstance.$mainNavigationClipped.offset().top)
      navMain_mousex = (e.pageX - selfInstance.$mainNavigationClipped.offset().left)


      if (!is_ios() && !is_android()) {


        if (isMenuMoveLocked) {
          return false;
        }

        if (navAttributes.navigation_direction === "vertical") {


          navigation_prepareAnimateMenuY(navMain_mousey, {
            called_from: "handleMouse"
          });


        }
        if (navAttributes.navigation_direction === "horizontal") {


          navigation_prepareAnimateMenuX(navMain_mousex, {
            called_from: "handleMouse"
          });


        }

      } else {

        return false;
      }

    }


    /**
     * only for navType: "hover"
     * @returns {boolean}
     */
    function handleEnterFrame() {


      if (isNaN(target_viy)) {
        target_viy = 0;
      }

      if (DURATION_EASING === 0) {
        window.requestAnimationFrame(handleEnterFrame);
        return false;
      }

      if (navAttributes.navigation_direction === 'vertical') {
        begin_viy = target_viy;
        change_viy = finish_viy - begin_viy;


        target_viy = Number(Math.easeIn(1, begin_viy, change_viy, DURATION_EASING).toFixed(4));


        if (!is_ios() && !is_android()) {

          view_setCssPropsForElement(parentClass.$navigationItemsContainer, {
            'transform': 'translate3d(0,' + target_viy + 'px,0)'
          })
        }

      }


      if (navAttributes.navigation_direction === 'horizontal') {
        begin_vix = target_vix;
        change_vix = finish_vix - begin_vix;


        target_vix = Number(Math.easeIn(1, begin_vix, change_vix, DURATION_EASING).toFixed(4));


        if (!is_ios() && !is_android()) {
          view_setCssPropsForElement(parentClass.$navigationItemsContainer, {
            'transform': 'translate3d(' + target_vix + 'px,0,0)'
          })
        }

      }


      window.requestAnimationFrame(handleEnterFrame);
    }


    function navigation_getNavPosition(navMain_mouse) {

      const clipSize = (selfInstance.navigation_mainDimensionClipSize);
      let viewMax = (selfInstance.navigation_mainDimensionTotalSize) - clipSize;
      const viewRatio = navMain_mouse / clipSize;
      let finish_viewIndex = (((navMain_mouse + viewRatio * OFFSET_BUFFER + 2 - OFFSET_BUFFER) / clipSize)) * -(selfInstance.navigation_mainDimensionTotalSize);

      if (finish_viewIndex > 0) {
        finish_viewIndex = 0;
      }
      if (finish_viewIndex < -viewMax) {
        finish_viewIndex = -viewMax;
      }


      return finish_viewIndex;
    }

    function navigation_prepareAnimateMenuX(navMain_mousex) {
      finish_vix = navigation_getNavPosition(navMain_mousex);

      if (navAttributes.isUseEasing) {
      } else {
        animate_menu_x(finish_vix);
      }
    }

    function navigation_prepareAnimateMenuY(navMain_mousey) {

      finish_viy = navigation_getNavPosition(navMain_mousey);

      if (navAttributes.isUseEasing) {
      } else {
        view_animateMenuVertical(finish_viy);
      }
    }


    selfInstance.animate_to_curr_thumb = animate_to_curr_thumb;

    function animate_to_curr_thumb() {


      if (is_touch_device()) {

      }


      if (navAttributes.navigationType === 'hover') {


        var $activeNavItem = parentClass.$navigationItemsContainer.find('.dzs-navigation--item').eq(0);


        if (parentClass.$navigationItemsContainer.find('.dzs-navigation--item.active').length) {
          $activeNavItem = parentClass.$navigationItemsContainer.find('.dzs-navigation--item.active').eq(0);
        }


        var rat = ($activeNavItem.offset().top - parentClass.$navigationItemsContainer.offset().top) / (parentClass.$navigationItemsContainer.outerHeight() - selfInstance.$mainNavigationClipped.parent().outerHeight());


        if (navAttributes.navigation_direction === 'vertical') {

          if (parentClass.$navigationItemsContainer.outerHeight() > selfInstance.$mainNavigationClipped.parent().outerHeight()) {

            view_animateMenuVertical(rat * (parentClass.$navigationItemsContainer.outerHeight() - selfInstance.$mainNavigationClipped.parent().outerHeight()), {
              'called_from': 'animate_to_curr_thumb'
            });
          }
        } else {

          if (navAttributes.navigation_direction === 'horizontal') {

            rat = ($activeNavItem.offset().left - parentClass.$navigationItemsContainer.offset().left) / (parentClass.$navigationItemsContainer.outerWidth() - selfInstance.$mainNavigationClipped.outerWidth());


            navigation_prepareAnimateMenuX(rat * selfInstance.$mainNavigationClipped.outerWidth());
          }


        }
      }
      if (navAttributes.navigationType === 'scroller') {

        var coordinateToActiveItem = 0;

        if (parentClass.$navigationItemsContainer.find('.dzs-navigation--item.active').length) {

          coordinateToActiveItem = parentClass.$navigationItemsContainer.find('.dzs-navigation--item.active').offset().top - parentClass.$navigationItemsContainer.eq(0).offset().top;


          setTimeout(function () {

            if (selfInstance.$mainNavigationClipped.get(0).api_scrolly_to) {
              selfInstance.$mainNavigationClipped.get(0).api_scrolly_to(coordinateToActiveItem);
            }
          }, NAVIGATION_DEFAULT_TIMEOUT);
        }

      }
    }


    function animate_menu_x() {


      if (!is_ios() && !is_android()) {
        if (navAttributes.isUseEasing) {

          view_setCssPropsForElement(parentClass.$navigationItemsContainer, {
            'transform': 'translate3d(' + finish_vix + 'px, ' + 0 + 'px, 0)'
          })
        }


      }
    }


    function view_animateMenuVertical(viewIndex, pargs) {


      // -- positive number viewIndexX
      var margs = {

        called_from: "default"
      }

      if (pargs) {
        margs = jQuery.extend(margs, pargs);
      }


      if (!is_touch_device()) {
        if (!navAttributes.isUseEasing) {

          view_setCssPropsForElement(parentClass.$navigationItemsContainer, {
            'transform': 'translate3d(0, ' + (finish_viy) + 'px, 0)'
          })
        } else {
          if ((-finish_viy) < selfInstance.navigation_mainDimensionTotalSize - selfInstance.$mainNavigation.outerHeight()) {
            finish_viy = -(selfInstance.navigation_mainDimensionTotalSize - selfInstance.$mainNavigation.outerHeight());
          }
          finish_viy = -viewIndex;
        }


      } else {
        if (margs.called_from === 'animate_to_curr_thumb') {
          setTimeout(function () {
            selfInstance.$mainNavigation.scrollTop(viewIndex);
          }, 1500);
        }
      }
    }


  }

  calculateDims(pargs = {}) {
    const selfInstance = this;
    view_navigation_calculateDims(selfInstance, pargs)
  }
}
