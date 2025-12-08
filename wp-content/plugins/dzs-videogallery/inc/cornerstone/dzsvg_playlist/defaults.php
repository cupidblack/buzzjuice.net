<?php

/**
 * Defaults Values
 */


$aux = array(
  'id' => '',
  'class' => '',
  'style' => '',
  'slider' => 'default',
  'db' => '',
  'category' => '',
  'fullscreen' => 'off', 'settings_separation_mode' => 'normal', // === normal ( no pagination ) or pages or scroll or button
  'settings_separation_pages_number' => '5', //=== the number of items per 'page'
  'settings_separation_paged' => '0',//=== the page number
  'return_mode' => 'normal' // -- "normal" returns the whole gallery, "items" returns the items array, "parsed items" returns the parsed items ( for pagination for example )
);


return $aux;