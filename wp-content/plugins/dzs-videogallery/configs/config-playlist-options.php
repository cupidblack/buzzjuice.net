<?php
/**
 * used in generate-settings-for-view.php to extrapolate options
 */
return array(

  'totalheight' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'',
    'jsName'=>'totalHeight',
    'sanitize_type'=>'sanitize_for_css_dimension',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'totalwidth' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'',
    'jsName'=>'totalWidth',
    'sanitize_type'=>'sanitize_for_css_dimension',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'forcevideoheight' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'',
    'jsName'=>'forceVideoHeight',
    'canBeEmptyString' => false,
    'sanitize_type'=>'sanitize_for_css_dimension',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'settings_menu_overlay' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'off',
    'jsName'=>'settings_menu_overlay',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),

  'start_item' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'default',
    'jsName'=>'startItem',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'init_on' => array(
    'jsName'=>'init_on',
    'default'=>'init',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'mode_normal_video_mode' => array(
    'jsName'=>'mode_normal_video_mode',
    'default'=>'auto',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'canBeEmptyString'=>false,
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'settings_trigger_resize' => array(
    'jsName'=>'settings_trigger_resize',
    'default'=>'0',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'easing_speed' => array(
    'jsName'=>'easing_speed',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'autoplay' => array(
    'jsName'=>'autoplayFirstVideo',
    'default'=>'off',
    'canBeEmptyString'=>false,
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'autoplaynext' => array(
    'jsName'=>'autoplayNext',
    'default'=>'on',
    'canBeEmptyString'=>false,
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'cueFirstVideo' => array(
    'jsName'=>'cueFirstVideo',
    'default'=>'on',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'nav_type' => array(
    'jsName'=>'nav_type',
    'default'=>'thumbs',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'menuposition' => array(
    'jsName'=>'menu_position',
    'default'=>'right',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Right",'dzsvg'),
        'value'=>'right',
      ),
      array(
        'label'=>esc_html__("Bottom",'dzsvg'),
        'value'=>'bottom',
      ),
      array(
        'label'=>esc_html__("Left",'dzsvg'),
        'value'=>'left',
      ),
      array(
        'label'=>esc_html__("Top",'dzsvg'),
        'value'=>'top',
      ),
      array(
        'label'=>esc_html__("No menu",'dzsvg'),
        'value'=>'none',
      ),
    ),
  ),
  'menuitem_width' => array(
    'jsName'=>'menuitem_width',
    'default'=>'default',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'html5designmiw' => array(
    'jsName'=>'menuitem_width',
    'default'=>'default',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'html5designmih' => array(
    'jsName'=>'menuitem_height',
    'default'=>'default',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'navigation_navigationSpace' => array(
    'jsName'=>'navigation_navigationSpace',
    'default'=>'0',
    'sanitize_type'=>'',
  ),
  'html5designmis' => array(
    'jsName'=>'menuitem_space',
    'default'=>'0',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'menuitem_space' => array(
    'jsName'=>'menuitem_space',
    'default'=>'0',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'menuitem_height' => array(
    'jsName'=>'menuitem_height',
    'default'=>'default',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),

  'loop_playlist' => array(
    'jsName'=>'loop_playlist',
    'default'=>'on',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  // -- deprecated
  'menu_description_format' => array(
    'jsName'=>'menu_description_format',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'settings_separation_mode' => array(
    'jsName'=>'settings_separation_mode',
    'default'=>'normal',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'disable_video_title' => array(
    'jsName'=>'disable_videoTitle',
    'default'=>'off',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'displaymode' => array(
    'jsName'=>'settings_mode',
    'default'=>'normal',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'extra_class_slider_con' => array(
    'jsName'=>'extra_class_slider_con',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'nav_type_outer_grid' => array(
    'jsName'=>'nav_type_outer_grid',
    'default'=>'dzs-layout--4-cols',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'nav_type_outer_max_height' => array(
    'jsName'=>'nav_type_outer_max_height',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'logo' => array(
    'jsName'=>'logo',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'logoLink' => array(
    'jsName'=>'logoLink',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'playorder' => array(
    'jsName'=>'playorder',
    'default'=>'normal',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'canBeEmpty'=>false,
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'menu_position' => array(
    'jsName'=>'menu_position',
    'default'=>'right',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'transition' => array(
    'jsName'=>'transition_type',
    'default'=>'slideup',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'extra_classes'=>'',
  ),
  'skin_html5vg' => array(

    'jsName'=>'design_skin',
    'default'=>'',
    'canBeEmpty'=>false,
  ),

  'design_skin' => array(
    'jsName'=>'design_skin',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'nav_type_auto_scroll' => array(
    'jsName'=>'nav_type_auto_scroll',
    'default'=>'off',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'search_field' => array(
    'jsName'=>'search_field',
    'default'=>'off',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'settings_enable_linking' => array(
    'jsName'=>'settings_enable_linking',
    'default'=>'off',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'autoplay_ad' => array( // -- deprecated
    'jsName'=>'autoplay_ad',
    'default'=>'off',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'search_field_con' => array(
    'jsName'=>'search_field_con',
    'default'=>'off',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'settings_secondCon' => array(
    'jsName'=>'settings_secondCon',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  'settings_outerNav' => array(
    'jsName'=>'settings_outerNav',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
  // -- deprecated
  'shareCode' => array(
    'jsName'=>'shareCode',
    'default'=>'',
    'category'=>'developer_options',
    'type'=>'checkbox',
    'sanitize_type'=>'',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("Disable",'dzsvg'),
        'value'=>'off',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),

);