import {VIEW_ISOTOPE_ITEM_CLASS} from "./_constants";

export const zfolioDefaultSettings = {
  design_item_thumb_just_use_img: "off" // -- just use images tags for auto width and height
  , settings_autoHeight: 'on'
  , settings_skin: 'skin-default'
  , settings_mode: 'isotope'// -- "isotope" or "simple" or "scroller"
  , settings_disableCats: 'off'
  , settings_clickaction: 'none'
  , title: ''
  , design_total_height_full: 'off'
  , pagination_method: 'normal' // pagination or scroll
  , design_item_width: '0'
  , design_item_height: '0'
  , design_item_height_same_as_width: 'off' // ==deprecated, use thumbh 1/1
  , design_sizecontain_forfeatureimage: 'off' // -- use size contain for feature image
  , design_thumbw: ''
  , init_on: 'init'
  , design_item_thumb_height: ''// -- default thumbh, values like "2/3" ( of width )  are accepted or "proportional" to just calculate for each item individually
  , design_categories_pos: 'top' // -- top or bottom
  , design_categories_align: 'auto' // -- auto, alignleft, aligncenter or alignright
  , design_specialgrid_chooser_align: 'auto' // -- auto, alignleft, aligncenter or alignright
  , design_pageContent_pos: 'top'
  , design_categories_style: 'normal' // normal or dropdown
  , design_waitalittleforallloaded: 'off' //wait for the items to arrange first before making the portfolio visible
  , use_scroll_lazyloading_for_images: 'off' // -- set images to lazy load on scroll
  , settings_ajax_method: 'off' // -- "off" / "curritems" for the current items in the queue / "on" for the pages array
  , settings_ajax_method_curritems_per_page: '5' // -- number of items to be loaded at a time
  , settings_ajax_method_curritems_per_page_initial: '' // -- number of items to be loaded at a time
  , settings_ajax_pagination_method: 'scroll'// -- choose between scroll and button mode NEW pages
  , settings_ajax_pages: []
  , settings_lightboxlibrary: 'zoombox'
  , item_inner_addid: ''
  , settings_preloadall: 'off'
  , settings_add_loaded_on_images: 'off' // -- add a loaded class on the image items when laoded
  , settings_useLinksForCategories: 'off'
  , settings_useLinksForCategories_enableHistoryApi: 'off'
  , item_link_thumb_con_to: "link"
  , item_link_whole_to: "none"
  , disable_itemmeta: "off"
  , filters_enable: "on"
  , disable_cats: "off" // -- disable the categories display
  , wall_settings: {}
  , settings_enableHistory: 'off' // history api for link type items
  , audioplayer_swflocation: 'ap.swf'
  , videoplayer_swflocation: 'preview.swf'
  , settings_makeFunctional: true
  , settings_defaultCat: '' // == default a category at start
  , settings_forceCats: [] // == force categories in this order
  , settings_categories_strall: 'All' // == the name of the all category select
  , settings_categories_strselectcategory: 'Select Category'
  , settings_set_forced_width: "off" // -- set a javascript calculated width on the item
  , settings_isotope_settings: {
    getSortData: {
      sorter: function (itemElem) { // function
        var weight = $(itemElem).attr('data-sort');

        if (weight) {

          return parseInt(weight.replace(/[\(\)]/g, ''));
        } else {
          return 0;
        }
      }
    }

    , itemSelector: '.' + VIEW_ISOTOPE_ITEM_CLASS
    , sortBy: 'sorter'
    , percentPosition: true
    , columnWidth: '.grid-sizer'
    , gutter: '.gutter-sizer',
     layoutMode: 'packery',
    masonry: {
      columnWidth: '.grid-sizer',
      percentPosition: true
    },
    // -- packery does not sort whel percent Position
     packery: {
      // use outer width of grid-sizer for columnWidth
      columnWidth: '.grid-sizer',
       percentPosition: true
    }
  }
  , scroller_settings: {}
  , zoombox_settings: {}

  , item_extra_class: ''
  , responsive_fallback_tablet: ''
  , responsive_fallback_mobile: ''
  , excerpt_con_transition: 'zoom' // -- wipe or zoom
  , excerpt_con_resize_videos: 'off' // -- resize videos in the excerpt con based on a responsive ratio.
  , excerpt_con_responsive_ratio: '810' // -- the responsive width on which the height is based ( height should already be set on the element )
  , selector_con_skin: 'default' // -- select a selector con so the categories would be outside the zfolio
  , selector_con_generate_categories: "auto" // -- select a selector con so the categories would be outside the zfolio jQuery("#selector-con-for-zfolio2")
  , outer_con_selector_con: null // -- select a selector con so the categories would be outside the zfolio jQuery("#selector-con-for-zfolio2")
  , outer_con_search_con: null // -- select a selector con so the categories would be outside the zfolio
  , pagination_selector: null // -- select a pagination con so the zfolio can be paginated


};