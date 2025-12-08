<?php


/**
 * @param array $builderLayerFields
 */
function dzs_layout_builder_mapBuilderLayerFieldsToHtml($builderLayerFields, $category){

  $fout = '';




  $fout = '';
  foreach ($builderLayerFields as $key => $main_option) {
    if ($main_option['category'] !== $category) {
      continue;
    }

    $lab = $key;


    $val = '';

    if (isset($main_option['default']) && $main_option['default']) {
      $val = $main_option['default'];
    }

    if (isset($mainOptions[$lab]) && $mainOptions[$lab]) {
      $val = $mainOptions[$lab];
    }





    $fout .= '-><-';
    $fout .= '<div class="setting">';

    $fout .= '<div class="setting-label"><div class="setting-label--text">' . $main_option['title'].'</div>';
    if (isset($main_option['tooltip']) && $main_option['tooltip']) {
      // todo: move in helper
      $fout.=ClassDzsvgHelpers::admin_generateTooltip($main_option['tooltip']);
    }
    $fout.='</div>'; // -- .end setting-label

    $fout.=dzs_layout_builder_mapBuilderLayerFieldsToHtmlInput($lab, $val, $main_option);

    if (isset($main_option['sidenote']) && $main_option['sidenote']) {
      $fout .= '<div class="sidenote">' . $main_option['sidenote'] . '</div>';
    }

    $fout .= '</div>';
    $fout .= '-<>-';


  }

  return $fout;
}


function dzs_layout_builder_mapBuilderLayerFieldsToHtmlInput($lab, $val, $main_option){

  $argsForInput = array(
    'id' => $lab,
    'val' => '',
    'class' => ' ',
    'seekval' => $val,
  );
  if (isset($main_option['extraAttr'])) {
    $argsForInput['extraattr'] = $main_option['extraAttr'];

  }
  $fout = '';

  if ($main_option['type'] == 'textarea') {
    $fout .= DZSHelpers::generate_input_textarea($lab, $argsForInput);
  }
  if ($main_option['type'] == 'text') {
    $fout .= DZSHelpers::generate_input_text($lab, $argsForInput);
  }
  if ($main_option['type'] == 'select') {
    $argsForInput['class'] = 'dzs-style-me skin-beige';
    $argsForInput['options'] = $main_option['choices'];
    $fout .= DZSHelpers::generate_select($lab, $argsForInput);
  }
  if ($main_option['type'] == 'checkbox') {

    $fout .= DZSHelpers::generate_input_text($lab, array('id' => $lab, 'val' => 'off', 'input_type' => 'hidden'));
    $fout .= '<div class="dzscheckbox skin-nova">';
    $fout .= DZSHelpers::generate_input_checkbox($lab, array('id' => $lab, 'val' => 'on', 'seekval' => $val));
    $fout .= '<label for="' . $lab . '"></label>';
    $fout .= '</div>';
  }

  return $fout;
}