<?php
return array(

  'flex_layout' => array(
    'category' => 'main',
    'type' => 'select',
    'default' => 'vertical',
    'jsName' => 'flex_layout',
    'title' => esc_html__('Layout', DZSVG_ID),
    'extra_classes' => '',
    'sidenote' => esc_html__("select the type of media"),
    'choices' => array(
      array(
        'label' => esc_html__("Vertical", DZSVG_ID),
        'value' => 'vertical',
      ),
      array(
        'label' => esc_html__("Horizontal", DZSVG_ID),
        'value' => 'horizontal',
      ),
    ),
  ),
);
