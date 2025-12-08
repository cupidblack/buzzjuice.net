<?php



function dzsvg_cornerstone_init(){

  // -- CornerStone
  add_action('wp_enqueue_scripts', 'dzsvg_enqueue');
  add_action('cornerstone_register_elements', 'dzsvg_register_elements');
  add_filter('cornerstone_icon_map', 'dzsvg_icon_map');
  add_action('_cornerstone_home_before', 'dzsvg_home_before');
  add_action('cornerstone_before_wp_editor', 'dzsvg_home_before');
  add_action('cornerstone_load_builder', 'dzsvg_home_before');


  /**
   * enqueue in customizer
   */
  function dzsvg_home_before() {
    wp_enqueue_script('dzsvg-admin-for-cornerstone', DZSVG_URL . 'assets/admin/admin-for-cornerstone.js', array('jquery'));
    wp_enqueue_script('dzsvg-admin-global', DZSVG_URL . 'admin/admin_global.js', array('jquery'));
    wp_enqueue_style('dzsvg-admin-global', DZSVG_URL . '/admin/admin_global.css');

  }

  function dzsvg_register_elements() {
    if (function_exists('cornerstone_register_element')) {
      cornerstone_register_element('CS_DZSVG', 'dzsvg', DZSVG_PATH . 'inc/cornerstone/dzsvg');
      cornerstone_register_element('CS_DZSVG_PLAYLIST', 'dzsvg_playlist', DZSVG_PATH . 'inc/cornerstone/dzsvg_playlist');
    }
  }

  function dzsvg_enqueue() {
    ClassDzsvgHelpers::enqueueDzsVpPlayer();
  }

  function dzsvg_icon_map($icon_map) {
    $icon_map['dzsvg'] = DZSVG_URL . '/assets/svg/icons.svg';
    return $icon_map;
  }
}