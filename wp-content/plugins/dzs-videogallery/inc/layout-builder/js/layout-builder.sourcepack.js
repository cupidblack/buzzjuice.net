"use strict";
import {serializeFormToJson, getHtmlAttributeWithRegex} from './js_common/_helpers';

String.prototype.insert_at = function (index, string) {
  return this.substr(0, index) + string + this.substr(index);
}
import {$es} from './js_common/_esjquery';

class LayoutBuilder {

  constructor() {


    console.log({$es});


    this.classInit();

  }

  classInit() {

    const $theForm_ = document.querySelector('.layout--main-con');
    const $layoutBuilderLayers_ = document.querySelector('.layout-builder--layers');
    const $theOutput_ = document.getElementById('lb-output');


    setTimeout(function () {
      jQuery('#layout-builder--layers-con').nestable({

        'maxDepth': 4,
        expandBtnHTML: '<button class="collapse-btn" data-action="expand" type="button"><i class="fa fa-caret-down" ></i></button>',
        collapseBtnHTML: '<button class="collapse-btn" data-action="collapse" type="button"><i class="fa fa-caret-up" ></i></button>',

        action_drag_stop: function () {
          console.log('action_drag_stop');
          handleReorder();
        }
      });
    }, 1000);
    $theForm_.addEventListener('submit', function (ev) {
      actionSubmitForm(ev);
    });

    $es(document).on('click', '.add-element, .btn-save-layout', handleClick);


    handleInit();

    function handleInit() {

      mapInitialConfigToItems();

      mapJsonStructureToInputs();
    }

    function mapInitialConfigToItems() {
      console.log($es('#lb-output').val());
      const initialFeed = $es('#lb-output').val();

      try {

        const initialFeedObj = JSON.parse(initialFeed);
        console.log('initialFeedObj - ', {initialFeedObj});


        if (initialFeedObj.items) {

          initialFeedObj.items.forEach(item => {

            $es($layoutBuilderLayers_).append(mapBuilderItemToHtml(layoutBuilderStructure.builder_item, item));
          })
        }
      } catch (err) {
        console.log(err);
      }
    }


    function handleReorder() {
      console.log('handleReorder()');
      mapJsonStructureToInputs();
      console.log('$es(\'.layout-builder--builder-item\') - ', $es('.layout-builder--builder-item'));
    }

    function mapJsonStructureToInputs() {

      $es('.layout-builder--builder-item').each(($el, index) => {
        console.log('$el2 - ', $el, index);

        $el.attr('data-lbindex', index);


        $el.find('*[data-lbkey]').each($elInput => {
          console.log('$elInput - ', $elInput);


          let jsonStructure = {
            items: []
          };
          jsonStructure.items[index] = {};
          jsonStructure.items[index][$elInput.attr('data-lbkey')] = 'the-value';
          $elInput.attr('data-json-structure', JSON.stringify(jsonStructure))
        })


      })
    }

    function mapBuilderItemToHtml(builderItemString, builderItemArgs) {

      let builderItemHtml = builderItemString;


      var regexItems = (/-><-[\s|\S]*?-<>-/g);

      var regexItemsResultsAux = builderItemHtml.matchAll(regexItems);


      const regexItemsResults = Array.from(regexItemsResultsAux);

      for (let i = regexItemsResults.length - 1; i >= 0; i--) {
        const regexItemsResult = regexItemsResults[i];
        let regexItemsResultHtml = regexItemsResult[0];

        var defaultValue = '';

        var lbKey = getHtmlAttributeWithRegex('data-lbkey', regexItemsResult);

        if (getHtmlAttributeWithRegex('default', regexItemsResult)) {
          defaultValue = getHtmlAttributeWithRegex('default', regexItemsResult);
        }
        var setValue = defaultValue;

        if (builderItemArgs[lbKey]) {
          setValue = builderItemArgs[lbKey];
        }

        regexItemsResultHtml = regexItemsResultHtml.replace('{{thevalue}}', setValue);
        regexItemsResultHtml = regexItemsResultHtml.replace(/-><-/g, '');
        regexItemsResultHtml = regexItemsResultHtml.replace(/-<>-/g, '');

        builderItemHtml = builderItemHtml.replace(regexItemsResult[0], '');

        builderItemHtml = builderItemHtml.insert_at(regexItemsResult.index, regexItemsResultHtml);

      }

      builderItemHtml = builderItemHtml.replace(/-><-/g, '');
      builderItemHtml = builderItemHtml.replace(/-<>-/g, '');

      return builderItemHtml

    }

    /**
     *
     * @param {Event} ev
     * @return {boolean}
     */
    function actionSubmitForm(ev) {


      $theOutput_.value = JSON.stringify(serializeFormToJson($theForm_, $theOutput_));

      window.es_ajax({
        'method': 'POST',
        'url': window.ajaxurl,
        'data': {
          'action': window.layout_builder_settings.ajaxActionName,
          'postdata': $theOutput_.value,
          'layout_builder_id': get_query_arg(window.location.href, 'layout_builder_id'),
          // 'postdata': 'ab',
        },
        'success': function () {
          console.log('successssss');
        }
      })

      ev.preventDefault();
      return false;
    }

    /**
     *
     * @param {MouseEvent} e
     */
    function handleClick(e) {



      var $t = $es(this);

      if ($t.hasClass('btn-save-layout')) {

        actionSubmitForm(e);
      }
      if ($t.hasClass('add-element')) {

        console.log('mapBuilderItemToHtml(layoutBuilderStructure.builder_item, {}) -', mapBuilderItemToHtml(layoutBuilderStructure.builder_item, {}));
        $es($layoutBuilderLayers_).append(mapBuilderItemToHtml(layoutBuilderStructure.builder_item, {}));

        console.log("CEVA");

        handleReorder();

      }
    }
  }


}

window.addEventListener('DOMContentLoaded', (event) => {
  console.log('DOM fully loaded and parsed');


  var lb = new LayoutBuilder();
});
