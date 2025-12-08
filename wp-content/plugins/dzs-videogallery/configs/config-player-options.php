<?php
return array(

  'settings_video_end_reset_time' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'on',
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
    'category'=>'developer_options',
    'type'=>'select',
    'default'=>'off',
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
  'autoplayWithVideoMuted' => array(
    'category'=>'developer_options',
    'type'=>'select',
    'default'=>'auto',
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
  'settings_extrahtml_before_right_controls' => array(
    'category'=>'developer_options',
    'type'=>'select',
    'default'=>'',
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
  'design_skin' => array(
    'category'=>'developer_options',
    'type'=>'select',
    'default'=>'skin_default',
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
  'cueVideo' => array(
    'category'=>'developer_options',
    'type'=>'select',
    'default'=>'on',
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
  'ad_show_markers' => array(
    'category'=>'developer_options',
    'type'=>'select',
    'default'=>'off',
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
  'preload_method' => array(
    'category'=>'developer_options',
    'type'=>'select',
    'default'=>'metadata',
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
  'settings_mouse_out_delay' => array(
    'category'=>'developer_options',
    'type'=>'text',
    'default'=>'100',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),

  ),
  'defaultvolume' => array(
    'category'=>'developer_options',
    'type'=>'text',
    'default'=>'last',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
  ),
  'playfrom' => array(
    'category'=>'developer_options',
    'type'=>'text',
    'default'=>'default',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
  ),
  'settings_disableVideoArray' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'off',
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
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'init',
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
  'settings_big_play_btn' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'off',
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
  'end_exit_fullscreen' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'on',
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
  'vimeo_is_chromeless' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'off',
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
  'settings_ios_usecustomskin' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'on',
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
  'settings_ios_playinline' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'on',
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
  'settings_disable_mouse_out' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'off',
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
  'settings_video_overlay' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'off',
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
  'vimeo_title' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'1',
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
  'yt_customskin' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'jsName'=>'settings_youtube_usecustomskin',
    'default'=>'on',
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
  'vimeo_byline' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'0',
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
  'vimeo_portrait' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'1',
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
  'vimeo_badge' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'0',
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
  'vimeo_color' => array(
    'category'=>'developer_options',
    'type'=>'checkbox',
    'default'=>'ffffff',
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
  'video_description_style' => array(
    'category'=>'developer_options',
    'type'=>'select',
    'default'=>'none',
    'select_type'=>'opener-listbuttons ',
    'title'=>esc_html__('Enable legacy','dzsvg').' <strong>Gutenberg</strong> '.esc_html__('blocks','dzsvg'),
    'extra_classes'=>'',
    'sidenote'=>__("select the type of media"),
    'choices'=>array(
      array(
        'label'=>esc_html__("No style",'dzsvg'),
        'value'=>'none',
      ),
      array(
        'label'=>esc_html__("Enable",'dzsvg'),
        'value'=>'on',
      ),
    ),
  ),
);