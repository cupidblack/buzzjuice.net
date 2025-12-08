<?php
include_once(DZSVG_PATH.'inc/layout-builder/DzsLayoutBuilder.php');
function dzsvg_init_layout_builder($dzsvg) {


  $dzsvg->layout_builder = new DzsLayoutBuilder($dzsvg, array(
    'ajaxActionName' => DZSVG_LAYOUT_BUILDER_AJAX_ACTION,
    'optionName' => DZSVG_LAYOUTBUILDER_MENU_ITEMS_OPTION_NAME,
    'pageName' => DZSVG_PAGENAME_LAYOUTBUILDER_MENU_ITEMS,
    'i18n_id' => DZSVG_ID,
    'settingsArray' => include(DZSVG_PATH.'configs/config-layout-builder.php'),
    'parent_url' => DZSVG_URL,
  ));;


}
