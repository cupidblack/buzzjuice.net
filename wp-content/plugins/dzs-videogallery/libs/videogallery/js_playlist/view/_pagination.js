// import {add_query_arg} from "../../js_common/_dzs_helpers";
// import {PLAYLIST_PAGINATION_QUERY_ARG} from "../../configs/Constants";


import {PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON} from "../../configs/Constants";

/**
 *
 * @param {DzsVideoGallery} selfClass
 */
export function playlist_pagination_scrollSetup(selfClass){

  var $ = jQuery;
  var cgallery = selfClass.cgallery;
  var o = selfClass.initOptions;


  if (o.settings_separation_mode === 'button') {
    selfClass.cgallery.append(`<div class="${PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON} btn_ajax_loadmore">Load More</div>`);
    selfClass.cgallery.on('click', `.${PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`, handleClickBtnAjaxLoadMore);
    if (o.settings_separation_pages.length === 0) {
      selfClass.cgallery.find(`.${PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`).hide();
    }
  }



  if (o.settings_separation_mode === 'scroll') {
    $(window).on('scroll', handleScroll);
  }


  // -- functions hoisting


  function handleClickBtnAjaxLoadMore(e) {

    if (selfClass.isBusyAjax === true || selfClass.ind_ajaxPage >= o.settings_separation_pages.length) {
      return;
    }
    selfClass.cgallery.find(`.${PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`).addClass('disabled')
    ajax_load_nextpage();
  }



  function ajax_load_nextpage() {

    selfClass.cgallery.parent().children('.preloader').addClass('is-visible');

    $.ajax({
      url: o.settings_separation_pages[selfClass.ind_ajaxPage],
      success: function (response) {


        setTimeout(function () {

          selfClass.$feedItemsContainer.append(response);
          selfClass.reinit({
            'called_from': 'ajax_load_nextpage'
          });
          cgallery.find(`.${PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`).removeClass('disabled');


          if (selfClass.ind_ajaxPage >= o.settings_separation_pages.length) {
            selfClass.cgallery.find(`.${PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON}`).addClass(PLAYLIST_VIEW_CLASS_AJAX_LOAD_MORE_BUTTON+'--is-hidden');
          }



          setTimeout(function () {
            selfClass.isBusyAjax = false;
            selfClass.cgallery.parent().children('.preloader').removeClass('is-visible');
            selfClass.ind_ajaxPage++;



          }, 10);
        }, 10);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        selfClass.ind_ajaxPage++;
        selfClass.cgallery.parent().children('.preloader').removeClass('is-visible');

      }
    });

    selfClass.isBusyAjax = true;
  }



  function handleScroll(){


    var _t = $(this);//==window
    let wh = $(window).height();
    if (selfClass.isBusyAjax === true || selfClass.ind_ajaxPage >= selfClass.initOptions.settings_separation_pages.length) {
      return;
    }


    if ((_t.scrollTop() + wh) > (cgallery.offset().top + cgallery.height() - 10)) {
      ajax_load_nextpage();
    }
  }





}

// -- this was on init




// if (o.settings_separation_mode === 'pages') {
//
//   let dzsvg_page = get_query_arg(window.location.href, PLAYLIST_PAGINATION_QUERY_ARG);
//
//
//   if (typeof dzsvg_page == "undefined") {
//     dzsvg_page = 1;
//   }
//   dzsvg_page = parseInt(dzsvg_page, 10);
//
//
//   if (dzsvg_page === 0 || isNaN(dzsvg_page)) {
//     dzsvg_page = 1;
//   }
//
//   if (dzsvg_page > 0 && o.settings_separation_pages_number < nrChildren) {
//
//     // if (o.settings_separation_pages_number * dzsvg_page <= nrChildren) {
//     //   for (elimi = o.settings_separation_pages_number * dzsvg_page - 1; elimi >= o.settings_separation_pages_number * (dzsvg_page - 1); elimi--) {
//     //     cgallery.children().eq(elimi).addClass('from-pagination-do-not-eliminate');
//     //   }
//     // } else {
//     //   for (elimi = nrChildren - 1; elimi >= nrChildren - o.settings_separation_pages_number; elimi--) {
//     //     cgallery.children().eq(elimi).addClass('from-pagination-do-not-eliminate');
//     //   }
//     // }
//     //
//     // cgallery.children().each(function () {
//     //   const $videoItem = $(this);
//     //   if (!$videoItem.hasClass('from-pagination-do-not-eliminate')) {
//     //     $videoItem.remove();
//     //   }
//     // })
//
//
//     // const str_pagination = view_playlist_buildPagination(selfClass, dzsvg_page);
//     // cgallery.after(str_pagination);
//
//   }
// }
// /**
//  *
//  * @param {DzsVideoGallery} selfClass
//  * @param dzsvg_page
//  * @returns {string}
//  */
// export function view_playlist_buildPagination(selfClass, dzsvg_page) {
//   var settings_separation_nr_pages = 0;
//
//   var nrChildren = selfClass.cgallery.children().length;
//   let str_pagination = '<div class="con-dzsvg-pagination">';
//   settings_separation_nr_pages = Math.ceil(nrChildren / selfClass.initOptions.settings_separation_pages_number);
//
//
//   for (let i = 0; i < settings_separation_nr_pages; i++) {
//     let str_active = '';
//     if ((i + 1) === dzsvg_page) {
//       str_active = ' active';
//     }
//     str_pagination += '<a class="pagination-number ' + str_active + '" href="' + add_query_arg(window.location.href, PLAYLIST_PAGINATION_QUERY_ARG, (i + 1)) + '">' + (i + 1) + '</a>'
//   }
//
//   str_pagination += '</div>';
//
//   return str_pagination;
// }