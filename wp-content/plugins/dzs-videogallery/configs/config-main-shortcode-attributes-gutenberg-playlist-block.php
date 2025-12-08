<?php
// todo: enter
return array(

  'dzsvg_select_id' => array(
    'type' => 'select',
    'title' => esc_html__("Video Gallery", 'dzsvg'),
    'sidenote' => esc_html__("video gallery select"),
    'default' => 'normal',
    'react_type' => 'string',
    'options' => array()
  ),
  'called_from' => array(
    'type' => 'text',
    'default' => 'from_gutenberg',
  ),


  'settings_separation_mode' => array(
    'type' => 'select',
    'title' => esc_html__("Select a Pagination Method", DZSVG_ID),
    'sidenote' => esc_html__("the title of the song"),
    'default' => 'normal',
    'react_type' => 'string',
    'options' => array(
      array(
        'label' => esc_html__("No pagination", 'dzsvg'),
        'value' => 'normal',
      ),
      array(
        'label' => esc_html__("Pagination", 'dzsvg'),
        'value' => 'pages',
      ),
      array(
        'label' => esc_html__("Scrolling", 'dzsvg'),
        'value' => 'scroll',
      ),
      array(
        'label' => esc_html__("Button load more", 'dzsvg'),
        'value' => 'button',
      ),
    )
  ),
  'settings_separation_pages_number' => array(
    'type' => 'text',
    'react_type' => 'string',
    'title' => esc_html__("Select Number of Items per Page", 'dzsvg'),
    'sidenote' => esc_html__("the title of the song"),
    'default' => '5',
  ),

);
