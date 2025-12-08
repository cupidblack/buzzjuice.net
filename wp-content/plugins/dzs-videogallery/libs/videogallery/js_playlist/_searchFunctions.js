import {svgSearchIcon} from "../js_dzsvg/_dzsvg_svgs";
import {handleSearchFieldChange} from "../js_dzsvg/playlist/_searchPlaylist";

/**
 *
 * @param {DzsVideoGallery} selfClass
 * @param {object} o
 */
export function dzsvg_playlist_initSearchField(selfClass, o){

  // -- setup search field
    if (o.search_field_con) {
      selfClass.$searchFieldCon = jQuery(o.search_field_con);
    }else{

      dzsvg_playlist_addSearchField(selfClass);

    }
    selfClass.$searchFieldCon.on('keyup', handleSearchFieldChange(selfClass, selfClass.$navigationItemsContainer.parent(), selfClass.handleResize));

}

/**
 *
 * @param {DzsVideoGallery} selfClass
 */
export function dzsvg_playlist_addSearchField(selfClass){

  var struct_searchFieldString = '';
  struct_searchFieldString = '<div class="dzsvg-search-field"><input type="text" placeholder="search..."/>' + svgSearchIcon + '</div>';
  if (selfClass._mainNavigation.hasClass('menu-moves-vertically')) {
    selfClass._mainNavigation.prepend(struct_searchFieldString);
  } else {
    selfClass.$navigationItemsContainer.prepend(struct_searchFieldString);
  }

  selfClass.$searchFieldCon = selfClass.cgallery.find('.dzsvg-search-field > input');
}